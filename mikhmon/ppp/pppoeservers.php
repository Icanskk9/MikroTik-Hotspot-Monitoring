<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  // Aksi cepat: Enable / Disable / Remove PPPoE Server
  if (isset($_GET['act']) && isset($_GET['sid']) && $_GET['sid'] != "") {
    $act = $_GET['act'];
    $sid = $_GET['sid'];
    if ($act == "enable") {
      $API->comm("/interface/pppoe-server/server/enable", array(".id" => $sid));
    } elseif ($act == "disable") {
      $API->comm("/interface/pppoe-server/server/disable", array(".id" => $sid));
    } elseif ($act == "remove") {
      $API->comm("/interface/pppoe-server/server/remove", array(".id" => $sid));
    }
    echo "<script>window.location='./?ppp=servers&session=" . $session . "'</script>";
    exit;
  }

  // Tambah PPPoE Server baru
  if (isset($_POST['add_pppoeserver'])) {
    $srv_name = trim($_POST['service_name']);
    $srv_iface = trim($_POST['interface']);
    $srv_prof = trim($_POST['default_profile']);
    $srv_oneses = trim($_POST['one_session']);
    $srv_mtu = trim($_POST['max_mtu']);
    $srv_auth = isset($_POST['auth']) && is_array($_POST['auth']) ? implode(",", $_POST['auth']) : "pap,chap,mschap1,mschap2";

    if ($srv_name != "" && $srv_iface != "") {
      $params = array(
        "service-name" => $srv_name,
        "interface" => $srv_iface,
        "default-profile" => ($srv_prof != "" ? $srv_prof : "default"),
        "one-session-per-host" => ($srv_oneses == "yes" ? "yes" : "no"),
        "authentication" => $srv_auth,
        "disabled" => "no",
      );
      if ($srv_mtu != "") {
        $params["max-mtu"] = $srv_mtu;
        $params["max-mru"] = $srv_mtu;
      }

      $API->comm("/interface/pppoe-server/server/add", $params);
      echo "<script>window.location='./?ppp=servers&session=" . $session . "'</script>";
      exit;
    }
  }

  // Edit PPPoE Server
  if (isset($_POST['edit_pppoeserver'])) {
    $es_id = trim($_POST['server_id']);
    $es_name = trim($_POST['service_name']);
    $es_iface = trim($_POST['interface']);
    $es_prof = trim($_POST['default_profile']);
    $es_oneses = trim($_POST['one_session']);
    $es_mtu = trim($_POST['max_mtu']);
    $es_auth = isset($_POST['auth']) && is_array($_POST['auth']) ? implode(",", $_POST['auth']) : "pap,chap,mschap1,mschap2";

    if ($es_id != "" && $es_name != "" && $es_iface != "") {
      $params = array(
        ".id" => $es_id,
        "service-name" => $es_name,
        "interface" => $es_iface,
        "default-profile" => ($es_prof != "" ? $es_prof : "default"),
        "one-session-per-host" => ($es_oneses == "yes" ? "yes" : "no"),
        "authentication" => $es_auth,
      );
      if ($es_mtu != "") {
        $params["max-mtu"] = $es_mtu;
        $params["max-mru"] = $es_mtu;
      }

      $API->comm("/interface/pppoe-server/server/set", $params);
      echo "<script>window.location='./?ppp=servers&session=" . $session . "'</script>";
      exit;
    }
  }

  $getservers = $API->comm("/interface/pppoe-server/server/print");
  $TotalReg = count($getservers);

  $getifaces = $API->comm("/interface/print");
  $TotalIface = count($getifaces);

  $getprofiles = $API->comm("/ppp/profile/print");
  $TotalProf = count($getprofiles);

  $activeServers = 0;
  $disabledServers = 0;
  $oneSessionCount = 0;
  for ($j = 0; $j < $TotalReg; $j++) {
    if (isset($getservers[$j]['disabled']) && $getservers[$j]['disabled'] == "true") {
      $disabledServers++;
    } else {
      $activeServers++;
    }
    if (isset($getservers[$j]['one-session-per-host']) && ($getservers[$j]['one-session-per-host'] == "true" || $getservers[$j]['one-session-per-host'] == "yes")) {
      $oneSessionCount++;
    }
  }
}
?>

<style>
  #mhm-pppoeservers {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-pppoeservers .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-pppoeservers .lx-title-group { display: flex; align-items: center; gap: 10px; }
  #mhm-pppoeservers .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-pppoeservers .lx-count {
    font-size: 11.5px; font-weight: 500; color: #71717a; background: #f4f4f5; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-pppoeservers .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-pppoeservers .lx-search { position: relative; width: 230px; max-width: 100%; }
  #mhm-pppoeservers .lx-search i {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 12px; color: #a1a1aa; pointer-events: none;
  }
  #mhm-pppoeservers #filterTable {
    width: 100% !important; height: 34px !important; padding: 0 12px 0 32px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  #mhm-pppoeservers #filterTable:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  #mhm-pppoeservers .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
  }
  #mhm-pppoeservers .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-pppoeservers .lx-btn.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; }
  #mhm-pppoeservers .lx-btn.is-primary:hover { background: #27272a; }

  #mhm-pppoeservers .lx-metrics {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7; background: #fafafa;
  }
  @media (max-width: 640px) { #mhm-pppoeservers .lx-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  #mhm-pppoeservers .lx-metric-item { padding: 14px 24px; border-right: 1px solid #e4e4e7; }
  #mhm-pppoeservers .lx-metric-item:last-child { border-right: none; }
  #mhm-pppoeservers .lx-metric-label {
    font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #71717a; margin-bottom: 4px;
  }
  #mhm-pppoeservers .lx-metric-val {
    font-size: 18px; font-weight: 600; color: #09090b; font-variant-numeric: tabular-nums;
    display: flex; align-items: center; gap: 7px;
  }
  #mhm-pppoeservers .lx-dot-live { width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block; }

  #mhm-pppoeservers .lx-table-wrap { width: 100%; overflow-x: auto; max-height: 70vh; }
  #mhm-pppoeservers #dataTable {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-pppoeservers #dataTable thead th {
    position: sticky; top: 0; z-index: 5; background: #ffffff !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 500 !important; text-transform: uppercase !important;
    letter-spacing: 0.06em !important; padding: 12px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppoeservers #dataTable thead th.pointer { cursor: pointer; }
  #mhm-pppoeservers #dataTable tbody td {
    padding: 13px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 13px !important; color: #27272a !important;
  }
  #mhm-pppoeservers #dataTable tbody tr:hover { background-color: #fafafa !important; }

  #mhm-pppoeservers .lx-action-group { display: inline-flex; align-items: center; gap: 5px; }
  #mhm-pppoeservers .lx-icon-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
    border-radius: 6px; border: 1px solid #e4e4e7; background: #ffffff; color: #52525b; font-size: 12px; cursor: pointer;
  }
  #mhm-pppoeservers .lx-icon-btn:hover { background: #fafafa; border-color: #d4d4d8; color: #09090b; }
  #mhm-pppoeservers .lx-icon-btn.is-danger:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
  #mhm-pppoeservers .lx-icon-btn.is-locked { background: #fffbeb; border-color: #fde68a; color: #b45309; }

  #mhm-pppoeservers .lx-pill-status {
    display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;
  }
  #mhm-pppoeservers .lx-pill-status.is-active { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-pppoeservers .lx-pill-status.is-disabled { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }

  #mhm-pppoeservers .lx-badge-iface {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px; font-weight: 600; color: #18181b; background: #f4f4f5; border: 1px solid #e4e4e7;
    padding: 2px 8px; border-radius: 5px;
  }
  #mhm-pppoeservers .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #3f3f46; }
  #mhm-pppoeservers .lx-name { font-weight: 600; color: #09090b; }
  #mhm-pppoeservers .lx-footer {
    padding: 12px 24px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; font-size: 11.5px; color: #71717a;
  }

  /* Modals */
  .lx-overlay {
    position: fixed; inset: 0; z-index: 9999; background: rgba(9, 9, 11, 0.45);
    backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; padding: 16px;
  }
  .lx-overlay.is-open { display: flex; }
  .lx-dialog {
    width: 100%; max-width: 490px; background: #ffffff; border: 1px solid #e4e4e7;
    border-radius: 12px; box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.22); overflow: hidden; color: #18181b;
  }
  .lx-dialog-head {
    padding: 16px 20px; border-bottom: 1px solid #f4f4f5; display: flex; align-items: center; justify-content: space-between;
  }
  .lx-dialog-title { margin: 0; font-size: 15px; font-weight: 600; color: #09090b; }
  .lx-dialog-close { width: 28px; height: 28px; border-radius: 6px; border: none; background: transparent; color: #71717a; cursor: pointer; }
  .lx-dialog-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
  .lx-form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  @media (max-width: 480px) { .lx-form-row { grid-template-columns: minmax(0, 1fr); } }
  .lx-field { display: flex; flex-direction: column; gap: 5px; }
  .lx-field label { font-size: 12px; font-weight: 500; color: #3f3f46; margin: 0; }
  .lx-field label span { font-weight: 400; color: #a1a1aa; }
  .lx-input, .lx-select {
    width: 100% !important; height: 36px !important; padding: 0 11px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  .lx-input:focus, .lx-select:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  .lx-mono-input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
  .lx-auth-grid {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px;
    background: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; padding: 8px 10px;
  }
  .lx-auth-item {
    display: flex; align-items: center; gap: 6px; font-size: 12px; color: #27272a; cursor: pointer; margin: 0;
  }
  .lx-dialog-foot {
    padding: 13px 20px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
  }
  .lx-btn-modal {
    height: 34px; padding: 0 14px; border-radius: 8px; border: 1px solid #e4e4e7;
    background: #ffffff; color: #18181b; font-size: 12.5px; font-weight: 500; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .lx-btn-modal.is-primary { background: #18181b; border-color: #18181b; color: #ffffff; }
  .lx-btn-modal.is-danger { background: #dc2626; border-color: #dc2626; color: #ffffff; }
</style>

<div id="mhm-pppoeservers">
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">PPPoE Servers (Interface Distribusi)</h3>
      <span class="lx-count"><?= $TotalReg; ?> server</span>
    </div>

    <div class="lx-actions">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="filterTable" type="text" class="form-control" placeholder="Cari service atau interface...">
      </div>
      <button type="button" onclick="openPSrvModal('lxAddPSrvModal');" class="lx-btn is-primary">
        <i class="fa fa-plus" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Tambah Server</span>
      </button>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Refresh">
        <i class="fa fa-refresh" style="font-size:11.5px; color:#71717a;"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Server</div>
      <div class="lx-metric-val"><?= $TotalReg; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Server Aktif</div>
      <div class="lx-metric-val"><span class="lx-dot-live"></span><span><?= $activeServers; ?></span></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Nonaktif</div>
      <div class="lx-metric-val"><?= $disabledServers; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">One Session / Host</div>
      <div class="lx-metric-val"><?= $oneSessionCount; ?></div>
    </div>
  </div>

  <div class="lx-table-wrap">
    <table id="dataTable" class="table">
      <thead>
        <tr>
          <th style="text-align:center; width:115px;">Aksi</th>
          <th style="width:95px;">Status</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Service Name</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Interface</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Default Profile</th>
          <th>One Session / Host</th>
          <th>Max MTU / MRU</th>
          <th>Authentication</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($i = 0; $i < $TotalReg; $i++) {
          $srv = $getservers[$i];
          $sid = $srv['.id'];
          $sname = $srv['service-name'];
          $siface = $srv['interface'];
          $sprof = $srv['default-profile'];
          $sonesesRaw = $srv['one-session-per-host'];
          $isOneSes = ($sonesesRaw == "true" || $sonesesRaw == "yes");
          $smtu = $srv['max-mtu'];
          $sauth = $srv['authentication'];
          $sdis = $srv['disabled'];
          $delUrl = "./?ppp=servers&act=remove&sid=" . urlencode($sid) . "&session=" . urlencode($session);
          ?>
          <tr>
            <td style="text-align:center;">
              <div class="lx-action-group">
                <span
                  class="lx-icon-btn pointer"
                  title="Edit <?= htmlspecialchars($sname, ENT_QUOTES); ?>"
                  data-id="<?= htmlspecialchars($sid, ENT_QUOTES); ?>"
                  data-name="<?= htmlspecialchars($sname, ENT_QUOTES); ?>"
                  data-iface="<?= htmlspecialchars($siface, ENT_QUOTES); ?>"
                  data-prof="<?= htmlspecialchars($sprof, ENT_QUOTES); ?>"
                  data-oneses="<?= $isOneSes ? 'yes' : 'no'; ?>"
                  data-mtu="<?= htmlspecialchars($smtu, ENT_QUOTES); ?>"
                  data-auth="<?= htmlspecialchars($sauth, ENT_QUOTES); ?>"
                  onclick="openEditPSrvModal(this);"
                >
                  <i class="fa fa-pencil"></i>
                </span>
                <?php if ($sdis == "true") { ?>
                  <span
                    class="lx-icon-btn is-locked pointer"
                    title="Aktifkan <?= htmlspecialchars($sname, ENT_QUOTES); ?>"
                    onclick="loadpage('./?ppp=servers&act=enable&sid=<?= urlencode($sid); ?>&session=<?= urlencode($session); ?>');"
                  >
                    <i class="fa fa-lock"></i>
                  </span>
                <?php } else { ?>
                  <span
                    class="lx-icon-btn pointer"
                    title="Nonaktifkan <?= htmlspecialchars($sname, ENT_QUOTES); ?>"
                    onclick="loadpage('./?ppp=servers&act=disable&sid=<?= urlencode($sid); ?>&session=<?= urlencode($session); ?>');"
                  >
                    <i class="fa fa-unlock"></i>
                  </span>
                <?php } ?>
                <span
                  class="lx-icon-btn is-danger pointer"
                  title="Hapus <?= htmlspecialchars($sname, ENT_QUOTES); ?>"
                  data-name="<?= htmlspecialchars($sname . ' (' . $siface . ')', ENT_QUOTES); ?>"
                  data-url="<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>"
                  onclick="openDelPSrvModal(this);"
                >
                  <i class="fa fa-trash-o"></i>
                </span>
              </div>
            </td>
            <td>
              <?php if ($sdis == "true") { ?>
                <span class="lx-pill-status is-disabled">Nonaktif</span>
              <?php } else { ?>
                <span class="lx-pill-status is-active">Aktif</span>
              <?php } ?>
            </td>
            <td><span class="lx-name"><?= htmlspecialchars($sname); ?></span></td>
            <td><span class="lx-badge-iface"><?= htmlspecialchars($siface); ?></span></td>
            <td><span class="lx-mono"><?= htmlspecialchars($sprof != "" ? $sprof : "default"); ?></span></td>
            <td>
              <?php if ($isOneSes) { ?>
                <span class="lx-pill-status is-active">Yes</span>
              <?php } else { ?>
                <span class="lx-mono" style="color:#71717a;">No</span>
              <?php } ?>
            </td>
            <td><span class="lx-mono"><?= htmlspecialchars($smtu != "" ? $smtu : "1480"); ?></span></td>
            <td><span class="lx-mono" style="font-size:11.5px; color:#52525b;"><?= htmlspecialchars($sauth); ?></span></td>
          </tr>
          <?php
        }
        if ($TotalReg == 0) {
          echo "<tr><td colspan='8' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Belum ada PPPoE Server aktif. Klik <strong>Tambah Server</strong> untuk mengaktifkan layanan PPPoE pada port FO/LAN.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <div class="lx-footer">
    <div>Aktifkan <strong>One Session / Host</strong> untuk mencegah 1 akun PPPoE dipakai ganda di jaringan LAN/FO</div>
    <div>RouterOS v6 &amp; v7 Compatible</div>
  </div>
</div>

<!-- Modal Tambah PPPoE Server -->
<div id="lxAddPSrvModal" class="lx-overlay" onclick="if(event.target===this) closePSrvModal('lxAddPSrvModal');">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Tambah PPPoE Server</h4>
      <button type="button" class="lx-dialog-close" onclick="closePSrvModal('lxAddPSrvModal');"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <div class="lx-dialog-body">
        <div class="lx-form-row">
          <div class="lx-field">
            <label>Service Name</label>
            <input type="text" name="service_name" class="lx-input lx-mono-input" placeholder="pppoe-smk-fo" required>
          </div>
          <div class="lx-field">
            <label>Interface Distribusi (FO / LAN)</label>
            <select name="interface" class="lx-select" required>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") {
                  echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '">' . htmlspecialchars($ifName) . '</option>';
                }
              }
              ?>
            </select>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Default Profile</label>
            <select name="default_profile" class="lx-select">
              <?php
              for ($p = 0; $p < $TotalProf; $p++) {
                $pName = $getprofiles[$p]['name'];
                echo '<option value="' . htmlspecialchars($pName, ENT_QUOTES) . '">' . htmlspecialchars($pName) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="lx-field">
            <label>One Session Per Host</label>
            <select name="one_session" class="lx-select">
              <option value="yes" selected>Yes (Direkomendasikan)</option>
              <option value="no">No</option>
            </select>
          </div>
        </div>

        <div class="lx-field">
          <label>Max MTU / MRU <span>(Standar PPPoE: 1480)</span></label>
          <input type="text" name="max_mtu" class="lx-input lx-mono-input" value="1480" placeholder="1480">
        </div>

        <div class="lx-field">
          <label>Authentication</label>
          <div class="lx-auth-grid">
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" value="pap" checked> pap</label>
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" value="chap" checked> chap</label>
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" value="mschap1" checked> mschap1</label>
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" value="mschap2" checked> mschap2</label>
          </div>
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closePSrvModal('lxAddPSrvModal');">Batal</button>
        <button type="submit" name="add_pppoeserver" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Server</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit PPPoE Server -->
<div id="lxEditPSrvModal" class="lx-overlay" onclick="if(event.target===this) closePSrvModal('lxEditPSrvModal');">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Edit PPPoE Server</h4>
      <button type="button" class="lx-dialog-close" onclick="closePSrvModal('lxEditPSrvModal');"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <input type="hidden" name="server_id" id="editPSrvId">
      <div class="lx-dialog-body">
        <div class="lx-form-row">
          <div class="lx-field">
            <label>Service Name</label>
            <input type="text" name="service_name" id="editPSrvName" class="lx-input lx-mono-input" required>
          </div>
          <div class="lx-field">
            <label>Interface Distribusi (FO / LAN)</label>
            <select name="interface" id="editPSrvIface" class="lx-select" required>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") {
                  echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '">' . htmlspecialchars($ifName) . '</option>';
                }
              }
              ?>
            </select>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Default Profile</label>
            <select name="default_profile" id="editPSrvProf" class="lx-select">
              <?php
              for ($p = 0; $p < $TotalProf; $p++) {
                $pName = $getprofiles[$p]['name'];
                echo '<option value="' . htmlspecialchars($pName, ENT_QUOTES) . '">' . htmlspecialchars($pName) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="lx-field">
            <label>One Session Per Host</label>
            <select name="one_session" id="editPSrvOneSes" class="lx-select">
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
          </div>
        </div>

        <div class="lx-field">
          <label>Max MTU / MRU</label>
          <input type="text" name="max_mtu" id="editPSrvMtu" class="lx-input lx-mono-input">
        </div>

        <div class="lx-field">
          <label>Authentication</label>
          <div class="lx-auth-grid">
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" id="editAuthPap" value="pap"> pap</label>
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" id="editAuthChap" value="chap"> chap</label>
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" id="editAuthMschap1" value="mschap1"> mschap1</label>
            <label class="lx-auth-item"><input type="checkbox" name="auth[]" id="editAuthMschap2" value="mschap2"> mschap2</label>
          </div>
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closePSrvModal('lxEditPSrvModal');">Batal</button>
        <button type="submit" name="edit_pppoeserver" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Perubahan</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Konfirmasi Hapus Server -->
<div id="lxDelPSrvModal" class="lx-overlay" onclick="if(event.target===this) closePSrvModal('lxDelPSrvModal');">
  <div class="lx-dialog" style="max-width:400px;">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Hapus PPPoE Server</h4>
      <button type="button" class="lx-dialog-close" onclick="closePSrvModal('lxDelPSrvModal');"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Yakin ingin menghapus PPPoE Server <strong id="delPSrvTarget" style="color:#09090b;"></strong>? Klien di interface tersebut tidak akan bisa terhubung lewat PPPoE.
      </p>
    </div>
    <div class="lx-dialog-foot">
      <button type="button" class="lx-btn-modal" onclick="closePSrvModal('lxDelPSrvModal');">Batal</button>
      <button type="button" id="delPSrvConfirmBtn" class="lx-btn-modal is-danger"><i class="fa fa-trash-o"></i><span>Hapus</span></button>
    </div>
  </div>
</div>

<script>
  function openPSrvModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.add('is-open');
  }
  function closePSrvModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('is-open');
  }
  function openEditPSrvModal(el) {
    document.getElementById('editPSrvId').value = el.getAttribute('data-id') || '';
    document.getElementById('editPSrvName').value = el.getAttribute('data-name') || '';
    document.getElementById('editPSrvIface').value = el.getAttribute('data-iface') || '';
    document.getElementById('editPSrvProf').value = el.getAttribute('data-prof') || 'default';
    document.getElementById('editPSrvOneSes').value = el.getAttribute('data-oneses') || 'yes';
    document.getElementById('editPSrvMtu').value = el.getAttribute('data-mtu') || '1480';

    var authList = (el.getAttribute('data-auth') || '').split(',');
    document.getElementById('editAuthPap').checked = authList.indexOf('pap') !== -1;
    document.getElementById('editAuthChap').checked = authList.indexOf('chap') !== -1;
    document.getElementById('editAuthMschap1').checked = authList.indexOf('mschap1') !== -1;
    document.getElementById('editAuthMschap2').checked = authList.indexOf('mschap2') !== -1;

    openPSrvModal('lxEditPSrvModal');
  }
  function openDelPSrvModal(el) {
    var name = el.getAttribute('data-name') || '';
    var url = el.getAttribute('data-url') || '';
    document.getElementById('delPSrvTarget').textContent = name;
    document.getElementById('delPSrvConfirmBtn').onclick = function() {
      closePSrvModal('lxDelPSrvModal');
      loadpage(url);
    };
    openPSrvModal('lxDelPSrvModal');
  }
</script>