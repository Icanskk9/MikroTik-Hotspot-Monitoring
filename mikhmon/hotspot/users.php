<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// hide all error
error_reporting(0);
ini_set('max_execution_time', 300);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  if ($prof == "all") {
    $getuser = $API->comm("/ip/hotspot/user/print");
    $TotalReg = count($getuser);

    $counttuser = $API->comm("/ip/hotspot/user/print", array(
      "count-only" => ""
    ));

  } elseif ($prof != "all") {
    $getuser = $API->comm("/ip/hotspot/user/print", array(
      "?profile" => "$prof",
    ));
    $TotalReg = count($getuser);

    $counttuser = $API->comm("/ip/hotspot/user/print", array(
      "count-only" => "",
      "?profile" => "$prof",
    ));

  }
  if ($comm != "") {
    $getuser = $API->comm("/ip/hotspot/user/print", array(
      "?comment" => "$comm",
    ));
    $TotalReg = count($getuser);

    $counttuser = $API->comm("/ip/hotspot/user/print", array(
      "count-only" => "",
      "?comment" => "$comm",
    ));
    
  }
  $exp = $_GET['exp'];
  if ($exp != "") {
    $getuser = $API->comm("/ip/hotspot/user/print", array(
      "?limit-uptime" => "1s",
    ));
    
    $counttuser = $API->comm("/ip/hotspot/user/print", array(
      "count-only" => "",
      "?limit-uptime" => "1s",
    ));
    
  }
  $getprofile = $API->comm("/ip/hotspot/user/profile/print");
  $TotalReg2 = count($getprofile);

  // Ringkasan statistik read-only untuk metric strip
  $enabledUsers = 0;
  $usedUsers = 0;
  $expiredOrLocked = 0;
  for ($k = 0; $k < $TotalReg; $k++) {
    $uItem = $getuser[$k];
    $isDis = (isset($uItem['disabled']) && $uItem['disabled'] == "true");
    $isExp = (isset($uItem['limit-uptime']) && $uItem['limit-uptime'] == "1s");
    $rawUp = isset($uItem['uptime']) ? $uItem['uptime'] : "";

    if (!$isDis) {
      $enabledUsers++;
    }
    if ($rawUp != "" && $rawUp != "0s" && $rawUp != "00:00:00") {
      $usedUsers++;
    }
    if ($isDis || $isExp) {
      $expiredOrLocked++;
    }
  }
}
?>

<style>
  #mhm-users {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-users .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-users .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-users .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-users .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-users .lx-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: #18181b;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    padding: 2px 9px;
    border-radius: 6px;
  }
  #mhm-users #loader {
    font-size: 12px;
    color: #71717a;
  }

  /* Buttons */
  #mhm-users .lx-actions {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
  }
  #mhm-users .lx-btn,
  #mhm-users-modal .lx-btn {
    height: 33px;
    padding: 0 12px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: #18181b !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  #mhm-users .lx-btn:hover,
  #mhm-users-modal .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-users .lx-btn:active,
  #mhm-users-modal .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-users .lx-btn i,
  #mhm-users-modal .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-users .lx-btn.is-primary,
  #mhm-users-modal .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-users .lx-btn.is-primary i,
  #mhm-users-modal .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-users .lx-btn.is-primary:hover,
  #mhm-users-modal .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }
  #mhm-users .lx-btn.is-danger,
  #mhm-users-modal .lx-btn.is-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-users .lx-btn.is-danger i,
  #mhm-users-modal .lx-btn.is-danger i {
    color: #dc2626;
  }
  #mhm-users .lx-btn.is-danger:hover,
  #mhm-users-modal .lx-btn.is-danger:hover {
    background: #fee2e2;
    border-color: #fca5a5;
  }

  /* Integrated Minimalist Metric Strip */
  #mhm-users .lx-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7;
    background: #fafafa;
  }
  @media (max-width: 768px) {
    #mhm-users .lx-metrics {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  #mhm-users .lx-metric-item {
    padding: 14px 24px;
    border-right: 1px solid #e4e4e7;
  }
  #mhm-users .lx-metric-item:last-child {
    border-right: none;
  }
  @media (max-width: 768px) {
    #mhm-users .lx-metric-item:nth-child(2) {
      border-right: none;
    }
    #mhm-users .lx-metric-item:nth-child(1),
    #mhm-users .lx-metric-item:nth-child(2) {
      border-bottom: 1px solid #e4e4e7;
    }
  }
  #mhm-users .lx-metric-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-users .lx-metric-val {
    font-size: 18px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  #mhm-users .lx-dot-live {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    flex-shrink: 0;
  }

  /* Filter & Print Toolbar */
  #mhm-users .lx-filterbar {
    padding: 14px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e4e4e7;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  #mhm-users .lx-filter-left {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex: 1;
    min-width: 260px;
  }
  #mhm-users .lx-search {
    position: relative;
    width: 220px;
    max-width: 100%;
  }
  #mhm-users .lx-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #a1a1aa;
    pointer-events: none;
  }
  #mhm-users #filterTable {
    width: 100% !important;
    height: 34px !important;
    padding: 0 12px 0 32px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #09090b !important;
    box-shadow: none !important;
    margin: 0 !important;
    transition: all 0.15s ease !important;
  }
  #mhm-users #filterTable::placeholder {
    color: #a1a1aa !important;
  }
  #mhm-users #filterTable:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  #mhm-users .lx-select {
    height: 34px !important;
    padding: 0 28px 0 11px !important;
    background-color: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    font-weight: 500 !important;
    color: #18181b !important;
    cursor: pointer;
    outline: none !important;
    max-width: 240px;
    transition: all 0.15s ease;
  }
  #mhm-users .lx-select:hover {
    border-color: #d4d4d8 !important;
    background-color: #ffffff !important;
  }
  #mhm-users .lx-select:focus {
    border-color: #18181b !important;
    background-color: #ffffff !important;
  }
  #mhm-users .lx-select.is-highlight {
    border-color: #18181b !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08) !important;
  }
  #mhm-users .lx-print-group {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }

  /* Table */
  #mhm-users .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 70vh;
  }
  #mhm-users #dataTable {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    white-space: nowrap;
    text-align: left;
    background: #ffffff !important;
  }
  #mhm-users #dataTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #ffffff !important;
    color: #71717a !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    padding: 12px 18px !important;
    border-top: none !important;
    border-bottom: 1px solid #e4e4e7 !important;
    user-select: none;
  }
  #mhm-users #dataTable thead th.pointer {
    cursor: pointer;
    transition: color 0.15s ease;
  }
  #mhm-users #dataTable thead th.pointer:hover {
    color: #09090b !important;
  }
  #mhm-users #dataTable tbody td {
    padding: 12px 18px !important;
    border-top: none !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 13px !important;
    color: #27272a !important;
    vertical-align: middle !important;
  }
  #mhm-users #dataTable tbody tr:last-child td {
    border-bottom: none !important;
  }
  #mhm-users #dataTable tbody tr {
    transition: background-color 0.12s ease;
  }
  #mhm-users #dataTable tbody tr:hover {
    background-color: #fafafa !important;
  }

  /* Cell Elements */
  #mhm-users .lx-action-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-users .lx-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #52525b !important;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
  }
  #mhm-users .lx-icon-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-users .lx-icon-btn.is-danger:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-users .lx-icon-btn.is-locked {
    background: #fffbeb;
    border-color: #fde68a;
    color: #b45309 !important;
  }
  #mhm-users .lx-icon-btn.is-locked:hover {
    background: #fef3c7;
    border-color: #fcd34d;
  }

  #mhm-users .lx-user-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #09090b !important;
    text-decoration: none;
    letter-spacing: -0.01em;
  }
  #mhm-users .lx-user-link i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-users .lx-user-link:hover {
    text-decoration: underline;
  }
  #mhm-users .lx-profile-pill {
    display: inline-block;
    font-size: 11.5px;
    font-weight: 500;
    color: #18181b;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 5px;
  }
  #mhm-users .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    color: #3f3f46;
    font-variant-numeric: tabular-nums;
  }
  #mhm-users .lx-comment-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    color: #3f3f46 !important;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    padding: 2px 8px;
    border-radius: 5px;
    text-decoration: none !important;
    transition: all 0.15s ease;
  }
  #mhm-users .lx-comment-link:hover {
    background: #f4f4f5;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-users .lx-comment-link.is-expired {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-users .lx-empty {
    color: #d4d4d8;
  }

  /* Footer */
  #mhm-users .lx-footer {
    padding: 12px 24px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11.5px;
    color: #71717a;
  }

  /* Custom Dialog Modal (Alert & Confirm) */
  #mhm-users-modal {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(9, 9, 11, 0.42);
    backdrop-filter: blur(2px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #mhm-users-modal.is-open {
    display: flex;
  }
  #mhm-users-modal .lx-dialog {
    width: 100%;
    max-width: 400px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 20px 45px -15px rgba(9, 9, 11, 0.22);
    overflow: hidden;
    animation: lxDialogPop 0.15s ease-out;
  }
  @keyframes lxDialogPop {
    from { opacity: 0; transform: translateY(6px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  #mhm-users-modal .lx-dialog-body {
    padding: 20px 22px 16px;
    display: flex;
    align-items: flex-start;
    gap: 13px;
  }
  #mhm-users-modal .lx-dialog-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #d97706;
  }
  #mhm-users-modal .lx-dialog-icon.is-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
  }
  #mhm-users-modal .lx-dialog-title {
    margin: 0 0 5px;
    font-size: 14.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
  }
  #mhm-users-modal .lx-dialog-msg {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: #52525b;
  }
  #mhm-users-modal .lx-dialog-footer {
    padding: 12px 22px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
  }
</style>

<div id="mhm-users">
  <?php
  if ($counttuser == 0) {
    echo "<script>window.location='./?hotspot=users&profile=all&session=" . $session . "'</script>";
  }
  ?>

  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title"><?= $_users ?></h3>
      <span class="lx-count">
        <?= (int)$counttuser; ?> <?= ($counttuser > 1) ? 'users' : 'user'; ?>
      </span>

      <?php if ($prof != "" && $prof != "all"): ?>
        <span class="lx-filter-chip">
          <i class="fa fa-pie-chart" style="font-size:11px; color:#71717a;"></i>
          <span><?= htmlspecialchars($prof); ?></span>
        </span>
      <?php endif; ?>

      <?php if ($comm != ""): ?>
        <span class="lx-filter-chip">
          <i class="fa fa-tag" style="font-size:11px; color:#71717a;"></i>
          <span><?= htmlspecialchars($comm); ?></span>
        </span>
      <?php endif; ?>

      <?php if (($prof != "" && $prof != "all") || $comm != "" || $exp != ""): ?>
        <a class="lx-btn" style="height:26px; padding:0 9px; font-size:11.5px;" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
          <i class="fa fa-times"></i>
          <span><?= $_show_all ?></span>
        </a>
      <?php endif; ?>

      <small id="loader" style="display: none;">
        <i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?>
      </small>
    </div>

    <div class="lx-actions">
      <a class="lx-btn is-primary" href="./?hotspot-user=add&session=<?= $session; ?>" title="Add User">
        <i class="fa fa-user-plus"></i>
        <span><?= $_add ?></span>
      </a>
      <a class="lx-btn" href="./?hotspot-user=generate&session=<?= $session; ?>" title="Generate User">
        <i class="fa fa-users"></i>
        <span><?= $_generate ?></span>
      </a>
      <a class="lx-btn" href="<?= str_replace("=users", "=export-users", $url); ?>&export=script" title="Download User List as Mikrotik Script">
        <i class="fa fa-download"></i>
        <span>Script</span>
      </a>
      <a class="lx-btn" href="<?= str_replace("=users", "=export-users", $url); ?>&export=csv" title="Download User List as CSV">
        <i class="fa fa-download"></i>
        <span>CSV</span>
      </a>
    </div>
  </div>

  <!-- Integrated Metric Strip -->
  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Users</div>
      <div class="lx-metric-val"><?= number_format((int)$counttuser); ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Status Aktif</div>
      <div class="lx-metric-val">
        <span class="lx-dot-live"></span>
        <span><?= number_format($enabledUsers); ?></span>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Sudah Login</div>
      <div class="lx-metric-val"><?= number_format($usedUsers); ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Expired / Terkunci</div>
      <div class="lx-metric-val"><?= number_format($expiredOrLocked); ?></div>
    </div>
  </div>

  <!-- Filter & Batch Print Toolbar -->
  <div class="lx-filterbar">
    <div class="lx-filter-left">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="filterTable" type="text" class="form-control" placeholder="<?= $_search ?>">
      </div>

      <select class="lx-select" onchange="location = this.value; loader()" title="Filter by Profile">
        <option><?= ($prof != "" && $prof != "all") ? htmlspecialchars($prof) : $_profile; ?></option>
        <option value="./?hotspot=users&profile=all&session=<?= $session; ?>"><?= $_show_all ?></option>
        <?php
        for ($i = 0; $i < $TotalReg2; $i++) {
          $profile = $getprofile[$i];
          echo "<option value='./?hotspot=users&profile=" . $profile['name'] . "&session=" . $session . "'>" . htmlspecialchars($profile['name']) . "</option>";
        }
        ?>
      </select>

      <select class="lx-select" id="comment" name="comment" onchange="location = './?hotspot=users&comment='+ this.value +'&session=<?= $session; ?>';">
        <?php
        if ($comm != "") {
        } else {
          echo "<option value=''>" . $_comment . "</option>";
        }
        $TotalReg = count($getuser);
        for ($i = 0; $i < $TotalReg; $i++) {
          $ucomment = $getuser[$i]['comment'];
          $uprofile = $getuser[$i]['profile'];
          $acomment .= "," . $ucomment . "#" . $uprofile;
        }

        $ocomment = explode(",", $acomment);
        $comments = array_count_values($ocomment);
        foreach ($comments as $tcomment => $value) {
          if (is_numeric(substr($tcomment, 3, 3))) {
            echo "<option value='" . explode("#", $tcomment)[0] . "' >" . explode("#", $tcomment)[0] . " " . explode("#", $tcomment)[1] . " [" . $value . "]</option>";
          }
        }
        ?>
      </select>
    </div>

    <div class="lx-print-group">
      <?php if ($comm != "") { ?>
        <button
          type="button"
          class="lx-btn is-danger"
          onclick="lxConfirmModal('Hapus Batch Comment', 'Are you sure to delete username by comment (<?= htmlspecialchars($comm, ENT_QUOTES); ?>)?', function(){ loadpage('./?remove-hotspot-user-by-comment=<?= $comm; ?>&session=<?= $session; ?>'); loader(); });"
          title="Remove user by comment <?= htmlspecialchars($comm, ENT_QUOTES); ?>"
        >
          <i class="fa fa-trash"></i>
          <span><?= $_by_comment ?></span>
        </button>
      <?php } elseif ($exp == "1") { ?>
        <button
          type="button"
          class="lx-btn is-danger"
          onclick="lxConfirmModal('Hapus User Expired', 'Are you sure to delete users?', function(){ loadpage('./?remove-hotspot-user-expired=1&session=<?= $session; ?>'); loader(); });"
          title="Remove user expired"
        >
          <i class="fa fa-trash"></i>
          <span>Expired Users</span>
        </button>
      <?php } ?>

      <script>
        var lxConfirmCallback = null;

        function lxAlertModal(title, msg) {
          var modal = document.getElementById('mhm-users-modal');
          var iconBox = document.getElementById('lx-dialog-icon');
          var iconEl = iconBox.querySelector('i');
          document.getElementById('lx-dialog-title').textContent = title;
          document.getElementById('lx-dialog-msg').textContent = msg;
          iconBox.className = 'lx-dialog-icon';
          iconEl.className = 'fa fa-exclamation-triangle';

          document.getElementById('lx-dialog-cancel').style.display = 'none';
          var okBtn = document.getElementById('lx-dialog-ok');
          okBtn.className = 'lx-btn is-primary';
          okBtn.textContent = 'Mengerti';
          lxConfirmCallback = function() {
            var commSel = document.getElementById('comment');
            if (commSel) {
              commSel.classList.add('is-highlight');
              commSel.focus();
              setTimeout(function() { commSel.classList.remove('is-highlight'); }, 1800);
            }
          };

          modal.classList.add('is-open');
          okBtn.focus();
        }

        function lxConfirmModal(title, msg, onConfirm) {
          var modal = document.getElementById('mhm-users-modal');
          var iconBox = document.getElementById('lx-dialog-icon');
          var iconEl = iconBox.querySelector('i');
          document.getElementById('lx-dialog-title').textContent = title;
          document.getElementById('lx-dialog-msg').textContent = msg;
          iconBox.className = 'lx-dialog-icon is-danger';
          iconEl.className = 'fa fa-trash';

          document.getElementById('lx-dialog-cancel').style.display = 'inline-flex';
          var okBtn = document.getElementById('lx-dialog-ok');
          okBtn.className = 'lx-btn is-danger';
          okBtn.textContent = 'Hapus';
          lxConfirmCallback = onConfirm;

          modal.classList.add('is-open');
          okBtn.focus();
        }

        function lxCloseModal() {
          document.getElementById('mhm-users-modal').classList.remove('is-open');
          lxConfirmCallback = null;
        }

        function lxProceedModal() {
          var cb = lxConfirmCallback;
          lxCloseModal();
          if (typeof cb === 'function') cb();
        }

        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            var modal = document.getElementById('mhm-users-modal');
            if (modal && modal.classList.contains('is-open')) {
              lxCloseModal();
            }
          }
        });

        function printV(a, b) {
          var comm = document.getElementById('comment').value;
          var url = "./voucher/print.php?id=" + comm + "&" + a + "=" + b + "&session=<?= $session; ?>";
          if (comm === "") {
            <?php if ($currency == in_array($currency, $cekindo['indo'])) { ?>
            lxAlertModal('Pilih Batch Comment', 'Silakan pilih salah satu Comment terlebih dulu sebelum mencetak voucher!');
            <?php } else { ?>
            lxAlertModal('Select Comment Batch', 'Please choose one of the Comments first!');
            <?php } ?>
          } else {
            var win = window.open(url, '_blank');
            win.focus();
          }
        }
      </script>

      <button type="button" class="lx-btn is-primary" title="Print" onclick="printV('qr','no');">
        <i class="fa fa-print"></i>
        <span><?= $_print_default ?></span>
      </button>
      <button type="button" class="lx-btn" title="Print QR" onclick="printV('qr','yes');">
        <i class="fa fa-qrcode"></i>
        <span><?= $_print_qr ?></span>
      </button>
      <button type="button" class="lx-btn" title="Print Small" onclick="printV('small','yes');">
        <i class="fa fa-print"></i>
        <span><?= $_print_small ?></span>
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="lx-table-wrap">
    <table id="dataTable" class="table">
      <thead>
        <tr>
          <th style="min-width:84px; text-align:center;" id="cuser"><?= $counttuser; ?></th>
          <th style="min-width:50px;" class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Server</th>
          <th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i><?= $_name ?></th>
          <th style="text-align:center;">Print</th>
          <th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i><?= $_profile ?></th>
          <th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Mac Address</th>
          <th style="text-align:right;" class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i><?= $_uptime_user ?></th>
          <th style="text-align:right;" class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Bytes In</th>
          <th style="text-align:right;" class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Bytes Out</th>
          <th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i><?= $_comment ?></th>
        </tr>
      </thead>
      <tbody id="tbody">
        <?php
        for ($i = 0; $i < $TotalReg; $i++) {
          $userdetails = $getuser[$i];
          $uid = $userdetails['.id'];
          $userver = $userdetails['server'];
          $uname = $userdetails['name'];
          $upass = $userdetails['password'];
          $uprofile = $userdetails['profile'];
          $umacadd = $userdetails['mac-address'];
          $uuptime = formatDTM($userdetails['uptime']);
          $ubytesi = formatBytes($userdetails['bytes-in'], 2);
          $ubyteso = formatBytes($userdetails['bytes-out'], 2);

          $ucomment = $userdetails['comment'];
          $udisabled = $userdetails['disabled'];
          $utimelimit = $userdetails['limit-uptime'];
          if ($utimelimit == '1s') {
            $utimelimit = ' expired';
          } else {
            $utimelimit = ' ' . $utimelimit;
          }
          $udatalimit = $userdetails['limit-bytes-total'];
          if ($udatalimit == '') {
            $udatalimit = '';
          } else {
            $udatalimit = ' ' . formatBytes($udatalimit, 2);
          }

          echo "<tr>";
          ?>
          <td style="text-align:center;">
            <div class="lx-action-group">
              <span
                class="lx-icon-btn is-danger pointer"
                onclick="lxConfirmModal('Hapus User', 'Are you sure to delete username (<?= htmlspecialchars($uname, ENT_QUOTES); ?>)?', function(){ loadpage('./?remove-hotspot-user=<?= $uid; ?>&session=<?= $session; ?>'); });"
                title="Remove <?= htmlspecialchars($uname, ENT_QUOTES); ?>"
              >
                <i class="fa fa-trash-o"></i>
              </span>
              <?php
              if ($udisabled == "true") {
                $uriprocess = "'./?enable-hotspot-user=" . $uid . "&session=" . $session . "'";
                echo '<span class="lx-icon-btn is-locked pointer" title="Enable User ' . htmlspecialchars($uname, ENT_QUOTES) . '" onclick="loadpage(' . $uriprocess . ')"><i class="fa fa-lock"></i></span>';
              } else {
                $uriprocess = "'./?disable-hotspot-user=" . $uid . "&session=" . $session . "'";
                echo '<span class="lx-icon-btn pointer" title="Disable User ' . htmlspecialchars($uname, ENT_QUOTES) . '" onclick="loadpage(' . $uriprocess . ')"><i class="fa fa-unlock"></i></span>';
              }
              ?>
            </div>
          </td>
          <?php
          echo "<td style='color:#52525b; font-weight:500;'>" . ($userver != "" ? htmlspecialchars($userver) : "<span class='lx-empty'>&mdash;</span>") . "</td>";

          if ($uname == $upass) {
            $usermode = "vc";
          } else {
            $usermode = "up";
          }
          $popup = "javascript:window.open('./voucher/print.php?user=" . $usermode . "-" . $uname . "&qr=no&session=" . $session . "','_blank','width=320,height=550').print();";
          $popupQR = "javascript:window.open('./voucher/print.php?user=" . $usermode . "-" . $uname . "&qr=yes&session=" . $session . "','_blank','width=320,height=550').print();";

          echo "<td><a class='lx-user-link' title='Open User " . htmlspecialchars($uname, ENT_QUOTES) . "' href='./?hotspot-user=" . $uid . "&session=" . $session . "'><i class='fa fa-edit'></i><span>" . htmlspecialchars($uname) . "</span></a></td>";

          echo "<td style='text-align:center;'>";
          echo "<div class='lx-action-group'>";
          echo "<a class='lx-icon-btn' title='Print " . htmlspecialchars($uname, ENT_QUOTES) . "' href=\"" . $popup . "\"><i class='fa fa-print'></i></a>";
          echo "<a class='lx-icon-btn' title='Print QR " . htmlspecialchars($uname, ENT_QUOTES) . "' href=\"" . $popupQR . "\"><i class='fa fa-qrcode'></i></a>";
          echo "</div>";
          echo "</td>";

          echo "<td>" . ($uprofile != "" ? "<span class='lx-profile-pill'>" . htmlspecialchars($uprofile) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
          echo "<td>" . ($umacadd != "" ? "<span class='lx-mono'>" . htmlspecialchars($umacadd) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
          echo "<td style='text-align:right;'><span class='lx-mono'>" . htmlspecialchars($uuptime) . "</span></td>";
          echo "<td style='text-align:right;'><span class='lx-mono'>" . htmlspecialchars($ubytesi) . "</span></td>";
          echo "<td style='text-align:right;'><span class='lx-mono'>" . htmlspecialchars($ubyteso) . "</span></td>";

          echo "<td>";
          if ($uname == "default-trial") {
            echo "<span class='lx-empty'>&mdash;</span>";
          } elseif (substr($ucomment, 0, 3) == "vc-" || substr($ucomment, 0, 3) == "up-") {
            echo "<a class='lx-comment-link' href='./?hotspot=users&comment=" . $ucomment . "&session=" . $session . "' title='Filter by " . htmlspecialchars($ucomment, ENT_QUOTES) . "'><i class='fa fa-search' style='font-size:10.5px; color:#71717a;'></i><span>" . htmlspecialchars(trim($ucomment . " " . $udatalimit . " " . $utimelimit)) . "</span></a>";
          } elseif ($utimelimit == ' expired') {
            echo "<a class='lx-comment-link is-expired' href='./?hotspot=users&profile=all&exp=1&session=" . $session . "' title='Filter by expired'><i class='fa fa-search' style='font-size:10.5px;'></i><span>" . htmlspecialchars(trim($ucomment . " " . $udatalimit . " " . $utimelimit)) . "</span></a>";
          } else {
            echo ($ucomment != "" ? "<span style='color:#52525b; font-size:12.5px;'>" . htmlspecialchars($ucomment) . "</span>" : "<span class='lx-empty'>&mdash;</span>");
          }
          echo "</td>";
          echo "</tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <!-- Footer -->
  <div class="lx-footer">
    <div>Pilih batch <strong style="color:#18181b;">Comment</strong> sebelum mencetak massal voucher</div>
    <div>Klik header kolom untuk mengurutkan</div>
  </div>
</div>

<!-- Custom Alert / Confirm Modal -->
<div id="mhm-users-modal" onclick="if(event.target === this) lxCloseModal();">
  <div class="lx-dialog" role="dialog" aria-modal="true" aria-labelledby="lx-dialog-title">
    <div class="lx-dialog-body">
      <div id="lx-dialog-icon" class="lx-dialog-icon">
        <i class="fa fa-exclamation-triangle"></i>
      </div>
      <div>
        <h4 id="lx-dialog-title" class="lx-dialog-title">Perhatian</h4>
        <p id="lx-dialog-msg" class="lx-dialog-msg"></p>
      </div>
    </div>
    <div class="lx-dialog-footer">
      <button type="button" id="lx-dialog-cancel" class="lx-btn" onclick="lxCloseModal()">Batal</button>
      <button type="button" id="lx-dialog-ok" class="lx-btn is-primary" onclick="lxProceedModal()">OK</button>
    </div>
  </div>
</div>