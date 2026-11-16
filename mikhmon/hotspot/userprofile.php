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
if (!isset($_SESSION["mikhmon"])) {
	echo '
<html>
<head><title>403 Forbidden</title></head>
<body bgcolor="white">
<center><h1>403 Forbidden</h1></center>
<hr><center>nginx/1.14.0</center>
</body>
</html>
';
} else {

	// get user profile
	$getprofile = $API->comm("/ip/hotspot/user/profile/print");
	$TotalReg = count($getprofile);
	// count user profile
	$countprofile = $API->comm("/ip/hotspot/user/profile/print", array(
		"count-only" => "",
	));

	// Kumpulkan data baris & ringkasan statistik (read-only)
	$profileRows = array();
	$activeSchedulers = 0;
	$lockedProfiles = 0;
	$pricedProfiles = 0;

	for ($i = 0; $i < $TotalReg; $i++) {
		$profiledetalis = $getprofile[$i];
		$pid = $profiledetalis['.id'];
		$pname = $profiledetalis['name'];
		$psharedu = $profiledetalis['shared-users'];
		$pratelimit = $profiledetalis['rate-limit'];
		$ponlogin = $profiledetalis['on-login'];

		$getmonexpired = $API->comm("/system/scheduler/print", array(
			"?name" => "$pname",
		));
		$monexpired = $getmonexpired[0];
		$pmon = $monexpired['name'];
		$chkpmon = $monexpired['disabled'];
		$isMonActive = (!empty($pmon) && $chkpmon != "true");
		if ($isMonActive) {
			$activeSchedulers++;
		}

		$onLoginParts = explode(",", $ponlogin);
		$expmode = isset($onLoginParts[1]) ? $onLoginParts[1] : "";
		if ($expmode == "rem") {
			$expmodeLabel = "Remove";
		} elseif ($expmode == "ntf") {
			$expmodeLabel = "Notice";
		} elseif ($expmode == "remc") {
			$expmodeLabel = "Remove & Record";
		} elseif ($expmode == "ntfc") {
			$expmodeLabel = "Notice & Record";
		} else {
			$expmodeLabel = "";
		}

		$validity = isset($onLoginParts[3]) ? $onLoginParts[3] : "";
		$rawPrice = isset($onLoginParts[2]) ? trim($onLoginParts[2]) : "";
		$rawSprice = isset($onLoginParts[4]) ? trim($onLoginParts[4]) : "";
		$lockUser = isset($onLoginParts[6]) ? trim($onLoginParts[6]) : "";

		if (strtolower($lockUser) == "enable") {
			$lockedProfiles++;
		}
		if (($rawPrice != "" && $rawPrice != "0") || ($rawSprice != "" && $rawSprice != "0")) {
			$pricedProfiles++;
		}

		$profileRows[] = array(
			'pid' => $pid,
			'pname' => $pname,
			'psharedu' => $psharedu,
			'pratelimit' => $pratelimit,
			'isMonActive' => $isMonActive,
			'expmodeLabel' => $expmodeLabel,
			'validity' => $validity,
			'rawPrice' => $rawPrice,
			'rawSprice' => $rawSprice,
			'lockUser' => $lockUser,
		);
	}
?>

<style>
  #mhm-userprofile {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-userprofile .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-userprofile .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-userprofile .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-userprofile .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }

  /* Buttons */
  #mhm-userprofile .lx-actions {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
  }
  #mhm-userprofile .lx-btn {
    height: 33px;
    padding: 0 13px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: #18181b !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  #mhm-userprofile .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-userprofile .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-userprofile .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-userprofile .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-userprofile .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-userprofile .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }

  /* Integrated Minimalist Metric Strip */
  #mhm-userprofile .lx-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7;
    background: #fafafa;
  }
  @media (max-width: 768px) {
    #mhm-userprofile .lx-metrics {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  #mhm-userprofile .lx-metric-item {
    padding: 14px 24px;
    border-right: 1px solid #e4e4e7;
  }
  #mhm-userprofile .lx-metric-item:last-child {
    border-right: none;
  }
  @media (max-width: 768px) {
    #mhm-userprofile .lx-metric-item:nth-child(2) {
      border-right: none;
    }
    #mhm-userprofile .lx-metric-item:nth-child(1),
    #mhm-userprofile .lx-metric-item:nth-child(2) {
      border-bottom: 1px solid #e4e4e7;
    }
  }
  #mhm-userprofile .lx-metric-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-userprofile .lx-metric-val {
    font-size: 18px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  #mhm-userprofile .lx-dot-live {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    flex-shrink: 0;
  }

  /* Table */
  #mhm-userprofile .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: 72vh;
  }
  #mhm-userprofile #tFilter {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    white-space: nowrap;
    text-align: left;
    background: #ffffff !important;
  }
  #mhm-userprofile #tFilter thead th {
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
    vertical-align: middle !important;
  }
  #mhm-userprofile #tFilter tbody td {
    padding: 13px 18px !important;
    border-top: none !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 13px !important;
    color: #27272a !important;
    vertical-align: middle !important;
  }
  #mhm-userprofile #tFilter tbody tr:last-child td {
    border-bottom: none !important;
  }
  #mhm-userprofile #tFilter tbody tr {
    transition: background-color 0.12s ease;
  }
  #mhm-userprofile #tFilter tbody tr:hover {
    background-color: #fafafa !important;
  }

  /* Cell Elements */
  #mhm-userprofile .lx-action-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-userprofile .lx-icon-btn {
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
  #mhm-userprofile .lx-icon-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-userprofile .lx-icon-btn.is-danger:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-userprofile .lx-icon-btn.is-disabled {
    opacity: 0.35;
    cursor: not-allowed;
    pointer-events: none;
  }

  #mhm-userprofile .lx-prof-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #09090b !important;
    text-decoration: none;
    letter-spacing: -0.01em;
  }
  #mhm-userprofile .lx-prof-link i.fa-edit {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-userprofile .lx-prof-link:hover {
    text-decoration: underline;
  }
  #mhm-userprofile .lx-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }
  #mhm-userprofile .lx-status-dot.is-active {
    background: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.16);
  }
  #mhm-userprofile .lx-status-dot.is-inactive {
    background: #f59e0b;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.16);
  }

  #mhm-userprofile .lx-pill {
    display: inline-block;
    font-size: 11.5px;
    font-weight: 500;
    color: #18181b;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 5px;
  }
  #mhm-userprofile .lx-pill.is-enabled {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
  }
  #mhm-userprofile .lx-pill.is-disabled {
    background: #fafafa;
    border: 1px solid #e4e4e7;
    color: #71717a;
  }
  #mhm-userprofile .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    color: #3f3f46;
    font-variant-numeric: tabular-nums;
  }
  #mhm-userprofile .lx-empty {
    color: #d4d4d8;
  }

  /* Footer */
  #mhm-userprofile .lx-footer {
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

  /* Custom Confirmation Modal */
  .lx-up-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(9, 9, 11, 0.48);
    backdrop-filter: blur(2px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  .lx-up-overlay.is-open {
    display: flex;
  }
  .lx-up-dialog {
    width: 100%;
    max-width: 400px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.25);
    overflow: hidden;
    color: #18181b;
    animation: lxPopIn 0.14s ease-out;
  }
  @keyframes lxPopIn {
    from { opacity: 0; transform: scale(0.97) translateY(4px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }
  .lx-up-dialog-head {
    padding: 16px 20px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .lx-up-dialog-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
  }
  .lx-up-dialog-close {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #71717a;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .lx-up-dialog-close:hover {
    background: #f4f4f5;
    color: #09090b;
  }
  .lx-up-dialog-body {
    padding: 18px 20px;
  }
  .lx-up-dialog-foot {
    padding: 13px 20px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
  }
  .lx-up-btn-modal {
    height: 34px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #18181b;
    font-size: 12.5px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
  }
  .lx-up-btn-modal:hover {
    background: #fafafa;
    border-color: #d4d4d8;
  }
  .lx-up-btn-modal.is-danger {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff;
  }
  .lx-up-btn-modal.is-danger:hover {
    background: #b91c1c;
    border-color: #b91c1c;
  }
</style>

<div id="mhm-userprofile">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">User Profile</h3>
      <span class="lx-count">
        <?php
        if ($countprofile < 2) {
          echo $countprofile . " item";
        } elseif ($countprofile > 1) {
          echo $countprofile . " items";
        } else {
          echo "0 item";
        }
        ?>
      </span>
    </div>

    <div class="lx-actions">
      <a class="lx-btn is-primary" href="./?user-profile=add&session=<?= $session; ?>" title="Add User">
        <i class="fa fa-user-plus"></i>
        <span>Add</span>
      </a>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Reload data">
        <i class="fa fa-refresh"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Integrated Metric Strip -->
  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Profiles</div>
      <div class="lx-metric-val"><?= number_format((int)$countprofile); ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Scheduler Aktif</div>
      <div class="lx-metric-val">
        <span class="lx-dot-live"></span>
        <span><?= number_format($activeSchedulers); ?></span>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Paket Berharga</div>
      <div class="lx-metric-val"><?= number_format($pricedProfiles); ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Lock User Aktif</div>
      <div class="lx-metric-val"><?= number_format($lockedProfiles); ?></div>
    </div>
  </div>

  <!-- Table -->
  <div class="lx-table-wrap">
    <table id="tFilter" class="table">
      <thead>
        <tr>
          <th style="min-width:96px; text-align:center;">Aksi</th>
          <th><?= $_name ?></th>
          <th style="text-align:center;">Shared Users</th>
          <th>Rate Limit</th>
          <th><?= $_expired_mode ?></th>
          <th><?= $_validity ?></th>
          <th style="text-align:right;"><?= $_price . " " . $currency; ?></th>
          <th style="text-align:right;"><?= $_selling_price . " " . $currency; ?></th>
          <th><?= $_lock_user ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($profileRows as $row) {
          $pid = $row['pid'];
          $pname = $row['pname'];
          $psharedu = $row['psharedu'];
          $pratelimit = $row['pratelimit'];
          $isMonActive = $row['isMonActive'];
          $expmodeLabel = $row['expmodeLabel'];
          $validity = $row['validity'];
          $rawPrice = $row['rawPrice'];
          $rawSprice = $row['rawSprice'];
          $lockUser = $row['lockUser'];
          $isDefaultProf = (strtolower($pname) === "default");
          $delUrl = "./?remove-user-profile=" . urlencode($pid) . "&pname=" . urlencode($pname) . "&session=" . urlencode($session);

          if ($rawPrice == "" || $rawPrice == "0") {
            $dispPrice = "";
          } else {
            if ($currency == in_array($currency, $cekindo['indo'])) {
              $dispPrice = number_format((float)$rawPrice, 0, ",", ".");
            } else {
              $dispPrice = number_format((float)$rawPrice, 2);
            }
          }

          if ($rawSprice == "" || $rawSprice == "0") {
            $dispSprice = "";
          } else {
            if ($currency == in_array($currency, $cekindo['indo'])) {
              $dispSprice = number_format((float)$rawSprice, 0, ",", ".");
            } else {
              $dispSprice = number_format((float)$rawSprice, 2);
            }
          }
          ?>
          <tr>
            <td style="text-align:center;">
              <div class="lx-action-group">
                <?php if ($isDefaultProf) { ?>
                  <span class="lx-icon-btn is-disabled" title="Profil default MikroTik tidak dapat dihapus">
                    <i class="fa fa-trash-o"></i>
                  </span>
                <?php } else { ?>
                  <span
                    class="lx-icon-btn is-danger pointer"
                    data-name="<?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                    data-url="<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>"
                    onclick="openDelProfModal(this);"
                    title="Hapus profil <?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                  >
                    <i class="fa fa-trash-o"></i>
                  </span>
                <?php } ?>
                <a
                  class="lx-icon-btn"
                  title="Open User by profile <?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                  href="./?hotspot=users&profile=<?= urlencode($pname); ?>&session=<?= urlencode($session); ?>"
                >
                  <i class="fa fa-users"></i>
                </a>
                <a
                  class="lx-icon-btn"
                  title="Edit User Profile <?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                  href="./?user-profile=<?= urlencode($pid); ?>&session=<?= urlencode($session); ?>"
                >
                  <i class="fa fa-edit"></i>
                </a>
              </div>
            </td>

            <td>
              <a
                class="lx-prof-link"
                title="Open User Profile <?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                href="./?user-profile=<?= urlencode($pid); ?>&session=<?= urlencode($session); ?>"
              >
                <span
                  class="lx-status-dot <?= $isMonActive ? 'is-active' : 'is-inactive'; ?>"
                  title="<?= $isMonActive ? 'Scheduler aktif' : 'Scheduler tidak aktif'; ?>"
                ></span>
                <span><?= htmlspecialchars($pname); ?></span>
              </a>
            </td>

            <td style="text-align:center;">
              <?= ($psharedu != "") ? "<span class='lx-mono'>" . htmlspecialchars($psharedu) . "</span>" : "<span class='lx-empty'>&mdash;</span>"; ?>
            </td>

            <td>
              <?= ($pratelimit != "") ? "<span class='lx-mono'>" . htmlspecialchars($pratelimit) . "</span>" : "<span class='lx-empty'>&mdash;</span>"; ?>
            </td>

            <td>
              <?= ($expmodeLabel != "") ? "<span class='lx-pill'>" . htmlspecialchars($expmodeLabel) . "</span>" : "<span class='lx-empty'>&mdash;</span>"; ?>
            </td>

            <td>
              <?= ($validity != "") ? "<span class='lx-mono'>" . htmlspecialchars($validity) . "</span>" : "<span class='lx-empty'>&mdash;</span>"; ?>
            </td>

            <td style="text-align:right;">
              <?= ($dispPrice != "") ? "<span class='lx-mono'>" . htmlspecialchars($dispPrice) . "</span>" : "<span class='lx-empty'>&mdash;</span>"; ?>
            </td>

            <td style="text-align:right;">
              <?= ($dispSprice != "") ? "<span class='lx-mono' style='font-weight:600; color:#09090b;'>" . htmlspecialchars($dispSprice) . "</span>" : "<span class='lx-empty'>&mdash;</span>"; ?>
            </td>

            <td>
              <?php
              if (strtolower($lockUser) == "enable") {
                echo "<span class='lx-pill is-enabled'>" . htmlspecialchars($lockUser) . "</span>";
              } elseif ($lockUser != "") {
                echo "<span class='lx-pill is-disabled'>" . htmlspecialchars($lockUser) . "</span>";
              } else {
                echo "<span class='lx-empty'>&mdash;</span>";
              }
              ?>
            </td>
          </tr>
          <?php
        }
        ?>
      </tbody>
    </table>
  </div>

  <!-- Footer -->
  <div class="lx-footer">
    <div>Titik hijau menandakan <strong style="color:#18181b;">Scheduler</strong> expired mode aktif di MikroTik</div>
    <div>Klik nama profil atau ikon edit untuk mengubah parameter paket</div>
  </div>
</div>

<!-- Modal Konfirmasi Hapus User Profile -->
<div id="lxDelProfModal" class="lx-up-overlay" onclick="if(event.target===this) closeDelProfModal();">
  <div class="lx-up-dialog">
    <div class="lx-up-dialog-head">
      <h4 class="lx-up-dialog-title">Hapus User Profile</h4>
      <button type="button" class="lx-up-dialog-close" onclick="closeDelProfModal();"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-up-dialog-body">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Yakin ingin menghapus profil paket <strong id="delProfTargetName" style="color:#09090b;"></strong> beserta scheduler monitoring-nya secara permanen?
      </p>
    </div>
    <div class="lx-up-dialog-foot">
      <button type="button" class="lx-up-btn-modal" onclick="closeDelProfModal();">Batal</button>
      <button type="button" id="delProfConfirmBtn" class="lx-up-btn-modal is-danger">
        <i class="fa fa-trash-o"></i>
        <span>Hapus Profil</span>
      </button>
    </div>
  </div>
</div>

<script>
  function openDelProfModal(el) {
    var profName = el.getAttribute('data-name') || '';
    var delUrl = el.getAttribute('data-url') || '';
    document.getElementById('delProfTargetName').textContent = profName;
    document.getElementById('delProfConfirmBtn').onclick = function() {
      closeDelProfModal();
      loadpage(delUrl);
    };
    document.getElementById('lxDelProfModal').classList.add('is-open');
  }

  function closeDelProfModal() {
    document.getElementById('lxDelProfModal').classList.remove('is-open');
  }
</script>
<?php
}
?>