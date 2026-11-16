<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $flashErr = "";
  $flashOk = "";

  if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "saved") {
      $flashOk = "IP Address berhasil ditambahkan ke router.";
    } elseif ($_GET['msg'] == "updated") {
      $flashOk = "Perubahan IP Address berhasil disimpan.";
    } elseif ($_GET['msg'] == "deleted") {
      $flashOk = "IP Address berhasil dihapus.";
    } elseif ($_GET['msg'] == "toggled") {
      $flashOk = "Status IP Address berhasil diperbarui.";
    }
  }

  // Aksi cepat: Enable / Disable / Remove IP Address
  if (isset($_GET['act']) && isset($_GET['aid']) && $_GET['aid'] != "") {
    $act = $_GET['act'];
    $aid = $_GET['aid'];
    if ($act == "enable") {
      $API->comm("/ip/address/enable", array(".id" => $aid));
    } elseif ($act == "disable") {
      $API->comm("/ip/address/disable", array(".id" => $aid));
    } elseif ($act == "remove") {
      $API->comm("/ip/address/remove", array(".id" => $aid));
    }
    echo "<script>window.location='./?ip=addresses&msg=" . ($act == "remove" ? "deleted" : "toggled") . "&session=" . urlencode($session) . "'</script>";
    exit;
  }

  // Tambah IP Address baru
  if (isset($_POST['add_ip_address'])) {
    $addr = trim($_POST['address']);
    $net = trim($_POST['network']);
    $iface = trim($_POST['interface']);
    $comm = trim($_POST['comment']);
    $gotoHotspot = isset($_POST['goto_hotspot']) && $_POST['goto_hotspot'] == "1";

    if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}\/([1-9]|[12]\d|3[0-2])$/', $addr)) {
      $flashErr = "Format IP Address tidak valid. Gunakan format CIDR, contoh: 192.168.20.1/24";
    } elseif ($iface == "") {
      $flashErr = "Interface wajib dipilih.";
    } else {
      $params = array(
        "address" => $addr,
        "interface" => $iface,
        "disabled" => "no",
      );
      if ($net != "") {
        $params["network"] = $net;
      }
      if ($comm != "") {
        $params["comment"] = $comm;
      }

      $res = $API->comm("/ip/address/add", $params);
      if (is_array($res) && isset($res['!trap'][0]['message'])) {
        $flashErr = "RouterOS Error: " . $res['!trap'][0]['message'];
      } else {
        if ($gotoHotspot) {
          echo "<script>window.location='./?ip=hotspot-setup&iface=" . urlencode($iface) . "&msg=ip_added&session=" . urlencode($session) . "'</script>";
        } else {
          echo "<script>window.location='./?ip=addresses&msg=saved&session=" . urlencode($session) . "'</script>";
        }
        exit;
      }
    }
  }

  // Edit IP Address
  if (isset($_POST['edit_ip_address'])) {
    $aid = trim($_POST['addr_id']);
    $addr = trim($_POST['address']);
    $net = trim($_POST['network']);
    $iface = trim($_POST['interface']);
    $comm = trim($_POST['comment']);

    if ($aid == "" || !preg_match('/^(\d{1,3}\.){3}\d{1,3}\/([1-9]|[12]\d|3[0-2])$/', $addr) || $iface == "") {
      $flashErr = "Data edit IP Address tidak valid. Pastikan format seperti 192.168.20.1/24.";
    } else {
      $params = array(
        ".id" => $aid,
        "address" => $addr,
        "interface" => $iface,
        "comment" => $comm,
      );
      if ($net != "") {
        $params["network"] = $net;
      }
      $res = $API->comm("/ip/address/set", $params);
      if (is_array($res) && isset($res['!trap'][0]['message'])) {
        $flashErr = "RouterOS Error: " . $res['!trap'][0]['message'];
      } else {
        echo "<script>window.location='./?ip=addresses&msg=updated&session=" . urlencode($session) . "'</script>";
        exit;
      }
    }
  }

  $getipaddr = $API->comm("/ip/address/print");
  $TotalIP = is_array($getipaddr) ? count($getipaddr) : 0;

  $getifaces = $API->comm("/interface/print");
  $TotalIface = is_array($getifaces) ? count($getifaces) : 0;

  $gethotspots = $API->comm("/ip/hotspot/print");
  $TotalHotspot = is_array($gethotspots) ? count($gethotspots) : 0;

  // Petakan interface -> daftar hotspot server & IP
  $hsByIface = array();
  for ($h = 0; $h < $TotalHotspot; $h++) {
    $hIf = $gethotspots[$h]['interface'];
    if ($hIf != "") {
      $hsByIface[$hIf] = $gethotspots[$h]['name'];
    }
  }

  $ipByIface = array();
  $activeIP = 0;
  for ($j = 0; $j < $TotalIP; $j++) {
    $row = $getipaddr[$j];
    if (!isset($row['disabled']) || $row['disabled'] != "true") {
      $activeIP++;
    }
    $rIf = $row['interface'];
    if ($rIf != "") {
      if (!isset($ipByIface[$rIf])) $ipByIface[$rIf] = array();
      $ipByIface[$rIf][] = $row['address'];
    }
  }

  $runningIface = 0;
  for ($k = 0; $k < $TotalIface; $k++) {
    if (isset($getifaces[$k]['running']) && $getifaces[$k]['running'] == "true") {
      $runningIface++;
    }
  }
}
?>

<style>
  #mhm-ipaddresses {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-ipaddresses .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-ipaddresses .lx-title-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  #mhm-ipaddresses .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-ipaddresses .lx-count {
    font-size: 11.5px; font-weight: 500; color: #71717a; background: #f4f4f5; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-ipaddresses .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-ipaddresses .lx-search { position: relative; width: 220px; max-width: 100%; }
  #mhm-ipaddresses .lx-search i {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 12px; color: #a1a1aa; pointer-events: none;
  }
  #mhm-ipaddresses #filterTable {
    width: 100% !important; height: 34px !important; padding: 0 12px 0 32px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  #mhm-ipaddresses #filterTable:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  #mhm-ipaddresses .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
    transition: all 0.15s ease;
  }
  #mhm-ipaddresses .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-ipaddresses .lx-btn.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; }
  #mhm-ipaddresses .lx-btn.is-primary:hover { background: #27272a; }
  #mhm-ipaddresses .lx-btn.is-accent { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8 !important; }
  #mhm-ipaddresses .lx-btn.is-accent:hover { background: #dbeafe; }

  #mhm-ipaddresses .lx-alert {
    margin: 14px 24px 0; padding: 11px 14px; border-radius: 9px; font-size: 12.5px;
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
  }
  #mhm-ipaddresses .lx-alert.is-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-ipaddresses .lx-alert.is-err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

  #mhm-ipaddresses .lx-metrics {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7; background: #fafafa; margin-top: 14px;
  }
  #mhm-ipaddresses .lx-metrics.no-top-gap { margin-top: 0; }
  @media (max-width: 720px) { #mhm-ipaddresses .lx-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  #mhm-ipaddresses .lx-metric-item { padding: 14px 24px; border-right: 1px solid #e4e4e7; }
  #mhm-ipaddresses .lx-metric-item:last-child { border-right: none; }
  #mhm-ipaddresses .lx-metric-label {
    font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #71717a; margin-bottom: 4px;
  }
  #mhm-ipaddresses .lx-metric-val {
    font-size: 18px; font-weight: 600; color: #09090b; font-variant-numeric: tabular-nums;
    display: flex; align-items: center; gap: 7px;
  }
  #mhm-ipaddresses .lx-dot-live { width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block; }

  /* Interface Strip */
  #mhm-ipaddresses .lx-iface-strip {
    padding: 14px 24px;
    border-bottom: 1px solid #e4e4e7;
    background: #ffffff;
  }
  #mhm-ipaddresses .lx-iface-strip-title {
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
    color: #71717a; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
  }
  #mhm-ipaddresses .lx-iface-grid {
    display: flex; flex-wrap: wrap; gap: 8px;
  }
  #mhm-ipaddresses .lx-iface-chip {
    display: inline-flex; align-items: center; gap: 8px; padding: 6px 11px;
    border-radius: 8px; border: 1px solid #e4e4e7; background: #fafafa;
    font-size: 12px; color: #18181b; cursor: pointer; transition: all 0.15s ease;
  }
  #mhm-ipaddresses .lx-iface-chip:hover {
    border-color: #18181b; background: #ffffff;
  }
  #mhm-ipaddresses .lx-iface-chip .dot-on { width: 7px; height: 7px; border-radius: 50%; background: #10b981; }
  #mhm-ipaddresses .lx-iface-chip .dot-off { width: 7px; height: 7px; border-radius: 50%; background: #a1a1aa; }
  #mhm-ipaddresses .lx-iface-chip .chip-ip {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px; color: #52525b; background: #ffffff; border: 1px solid #e4e4e7;
    padding: 1px 6px; border-radius: 5px;
  }
  #mhm-ipaddresses .lx-iface-chip .chip-hs {
    font-size: 10px; font-weight: 600; color: #047857; background: #ecfdf5;
    border: 1px solid #a7f3d0; padding: 1px 5px; border-radius: 4px;
  }

  /* Table */
  #mhm-ipaddresses .lx-table-wrap { width: 100%; overflow-x: auto; max-height: 68vh; }
  #mhm-ipaddresses #dataTable {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-ipaddresses #dataTable thead th {
    position: sticky; top: 0; z-index: 5; background: #ffffff !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 500 !important; text-transform: uppercase !important;
    letter-spacing: 0.06em !important; padding: 12px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-ipaddresses #dataTable thead th.pointer { cursor: pointer; }
  #mhm-ipaddresses #dataTable tbody td {
    padding: 13px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 13px !important; color: #27272a !important;
  }
  #mhm-ipaddresses #dataTable tbody tr:hover { background-color: #fafafa !important; }

  #mhm-ipaddresses .lx-action-group { display: inline-flex; align-items: center; gap: 5px; }
  #mhm-ipaddresses .lx-icon-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
    border-radius: 6px; border: 1px solid #e4e4e7; background: #ffffff; color: #52525b; font-size: 12px; cursor: pointer;
    text-decoration: none !important;
  }
  #mhm-ipaddresses .lx-icon-btn:hover { background: #fafafa; border-color: #d4d4d8; color: #09090b; }
  #mhm-ipaddresses .lx-icon-btn.is-danger:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
  #mhm-ipaddresses .lx-icon-btn.is-locked { background: #fffbeb; border-color: #fde68a; color: #b45309; }
  #mhm-ipaddresses .lx-icon-btn.is-wizard {
    width: auto; padding: 0 9px; gap: 5px; font-size: 11.5px; font-weight: 500;
    background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8;
  }
  #mhm-ipaddresses .lx-icon-btn.is-wizard:hover { background: #dbeafe; }

  #mhm-ipaddresses .lx-pill-status {
    display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;
  }
  #mhm-ipaddresses .lx-pill-status.is-active { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-ipaddresses .lx-pill-status.is-disabled { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }
  #mhm-ipaddresses .lx-pill-status.is-dynamic { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
  #mhm-ipaddresses .lx-pill-status.is-invalid { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

  #mhm-ipaddresses .lx-badge-iface {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px; font-weight: 600; color: #18181b; background: #f4f4f5; border: 1px solid #e4e4e7;
    padding: 2px 8px; border-radius: 5px;
  }
  #mhm-ipaddresses .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #09090b; font-weight: 600; }
  #mhm-ipaddresses .lx-mono-sub { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #52525b; }
  #mhm-ipaddresses .lx-footer {
    padding: 12px 24px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; font-size: 11.5px; color: #71717a;
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
  .lx-preset-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 2px; }
  .lx-preset-pill {
    font-size: 11px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    padding: 3px 8px; border-radius: 6px; border: 1px solid #e4e4e7; background: #fafafa; color: #3f3f46;
    cursor: pointer;
  }
  .lx-preset-pill:hover { background: #18181b; color: #ffffff; border-color: #18181b; }
  .lx-check-box {
    display: flex; align-items: center; gap: 8px; padding: 9px 11px;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; color: #1e293b; cursor: pointer;
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

<div id="mhm-ipaddresses">
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">IP &rsaquo; Addresses &amp; Interface</h3>
      <span class="lx-count"><?= $TotalIP; ?> IP terpasang</span>
    </div>

    <div class="lx-actions">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="filterTable" type="text" class="form-control" placeholder="Cari IP, network, interface...">
      </div>
      <button type="button" onclick="openAddIPModal('');" class="lx-btn is-primary">
        <i class="fa fa-plus" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Tambah IP Address</span>
      </button>
      <a href="./?ip=hotspot-setup&session=<?= urlencode($session); ?>" class="lx-btn is-accent">
        <i class="fa fa-magic" style="font-size:11.5px;"></i>
        <span>Hotspot Setup</span>
      </a>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Refresh">
        <i class="fa fa-refresh" style="font-size:11.5px; color:#71717a;"></i>
      </button>
    </div>
  </div>

  <?php if ($flashOk != "") { ?>
    <div class="lx-alert is-ok">
      <span><i class="fa fa-check-circle" style="margin-right:6px;"></i><?= htmlspecialchars($flashOk); ?></span>
      <a href="./?ip=hotspot-setup&session=<?= urlencode($session); ?>" style="font-weight:600; color:#047857; text-decoration:underline;">Lanjut ke Hotspot Setup &rarr;</a>
    </div>
  <?php } ?>
  <?php if ($flashErr != "") { ?>
    <div class="lx-alert is-err">
      <span><i class="fa fa-exclamation-triangle" style="margin-right:6px;"></i><?= htmlspecialchars($flashErr); ?></span>
    </div>
  <?php } ?>

  <div class="lx-metrics <?= ($flashOk == "" && $flashErr == "") ? 'no-top-gap' : ''; ?>">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total IP Address</div>
      <div class="lx-metric-val"><?= $TotalIP; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">IP Aktif</div>
      <div class="lx-metric-val"><span class="lx-dot-live"></span><span><?= $activeIP; ?></span></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Interface Running</div>
      <div class="lx-metric-val"><?= $runningIface; ?> <span style="font-size:12px; font-weight:400; color:#71717a;">/ <?= $TotalIface; ?> port</span></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Hotspot Server Aktif</div>
      <div class="lx-metric-val"><?= $TotalHotspot; ?></div>
    </div>
  </div>

  <!-- Interface Overview Strip -->
  <div class="lx-iface-strip">
    <div class="lx-iface-strip-title">
      <span>Status Port / Interface Router (Klik port untuk tambah IP)</span>
      <span style="font-weight:400; color:#a1a1aa;">Hijau = Link Terhubung (Running)</span>
    </div>
    <div class="lx-iface-grid">
      <?php
      for ($f = 0; $f < $TotalIface; $f++) {
        $ifRow = $getifaces[$f];
        $ifName = $ifRow['name'];
        if ($ifName == "") continue;
        $isRun = (isset($ifRow['running']) && $ifRow['running'] == "true");
        $ifIps = isset($ipByIface[$ifName]) ? implode(", ", $ipByIface[$ifName]) : "Belum ada IP";
        $hasHs = isset($hsByIface[$ifName]);
        ?>
        <div class="lx-iface-chip" onclick="openAddIPModal('<?= htmlspecialchars($ifName, ENT_QUOTES); ?>');" title="Klik untuk tambah IP Address pada <?= htmlspecialchars($ifName, ENT_QUOTES); ?>">
          <span class="<?= $isRun ? 'dot-on' : 'dot-off'; ?>"></span>
          <strong><?= htmlspecialchars($ifName); ?></strong>
          <span class="chip-ip"><?= htmlspecialchars($ifIps); ?></span>
          <?php if ($hasHs) { ?>
            <span class="chip-hs">Hotspot</span>
          <?php } ?>
        </div>
        <?php
      }
      ?>
    </div>
  </div>

  <!-- Table IP Addresses -->
  <div class="lx-table-wrap">
    <table id="dataTable" class="table">
      <thead>
        <tr>
          <th style="text-align:center; width:190px;">Aksi</th>
          <th style="width:105px;">Status</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Address (IP / Prefix)</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Network</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Interface</th>
          <th>Layanan Terpasang</th>
          <th>Komentar</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($i = 0; $i < $TotalIP; $i++) {
          $item = $getipaddr[$i];
          $aid = $item['.id'];
          $address = $item['address'];
          $network = $item['network'];
          $iface = $item['interface'];
          $comment = isset($item['comment']) ? $item['comment'] : '';
          $isDis = (isset($item['disabled']) && $item['disabled'] == "true");
          $isDyn = (isset($item['dynamic']) && $item['dynamic'] == "true");
          $isInv = (isset($item['invalid']) && $item['invalid'] == "true");
          $delUrl = "./?ip=addresses&act=remove&aid=" . urlencode($aid) . "&session=" . urlencode($session);
          $setupUrl = "./?ip=hotspot-setup&iface=" . urlencode($iface) . "&session=" . urlencode($session);
          ?>
          <tr>
            <td style="text-align:center;">
              <div class="lx-action-group">
                <?php if (!$isDyn) { ?>
                  <span
                    class="lx-icon-btn pointer"
                    title="Edit <?= htmlspecialchars($address, ENT_QUOTES); ?>"
                    data-id="<?= htmlspecialchars($aid, ENT_QUOTES); ?>"
                    data-address="<?= htmlspecialchars($address, ENT_QUOTES); ?>"
                    data-network="<?= htmlspecialchars($network, ENT_QUOTES); ?>"
                    data-iface="<?= htmlspecialchars($iface, ENT_QUOTES); ?>"
                    data-comment="<?= htmlspecialchars($comment, ENT_QUOTES); ?>"
                    onclick="openEditIPModal(this);"
                  >
                    <i class="fa fa-pencil"></i>
                  </span>
                  <?php if ($isDis) { ?>
                    <span
                      class="lx-icon-btn is-locked pointer"
                      title="Aktifkan <?= htmlspecialchars($address, ENT_QUOTES); ?>"
                      onclick="loadpage('./?ip=addresses&act=enable&aid=<?= urlencode($aid); ?>&session=<?= urlencode($session); ?>');"
                    >
                      <i class="fa fa-lock"></i>
                    </span>
                  <?php } else { ?>
                    <span
                      class="lx-icon-btn pointer"
                      title="Nonaktifkan <?= htmlspecialchars($address, ENT_QUOTES); ?>"
                      onclick="loadpage('./?ip=addresses&act=disable&aid=<?= urlencode($aid); ?>&session=<?= urlencode($session); ?>');"
                    >
                      <i class="fa fa-unlock"></i>
                    </span>
                  <?php } ?>
                  <span
                    class="lx-icon-btn is-danger pointer"
                    title="Hapus <?= htmlspecialchars($address, ENT_QUOTES); ?>"
                    data-name="<?= htmlspecialchars($address . ' (' . $iface . ')', ENT_QUOTES); ?>"
                    data-url="<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>"
                    onclick="openDelIPModal(this);"
                  >
                    <i class="fa fa-trash-o"></i>
                  </span>
                <?php } else { ?>
                  <span class="lx-mono-sub" style="font-size:11px; color:#a1a1aa; padding:0 6px;">Auto DHCP</span>
                <?php } ?>
                <a href="<?= htmlspecialchars($setupUrl, ENT_QUOTES); ?>" class="lx-icon-btn is-wizard" title="Jalankan Hotspot Setup pada <?= htmlspecialchars($iface, ENT_QUOTES); ?>">
                  <i class="fa fa-magic"></i><span>Hotspot</span>
                </a>
              </div>
            </td>
            <td>
              <?php if ($isInv) { ?>
                <span class="lx-pill-status is-invalid">Invalid</span>
              <?php } elseif ($isDis) { ?>
                <span class="lx-pill-status is-disabled">Nonaktif</span>
              <?php } elseif ($isDyn) { ?>
                <span class="lx-pill-status is-dynamic">Dynamic</span>
              <?php } else { ?>
                <span class="lx-pill-status is-active">Aktif</span>
              <?php } ?>
            </td>
            <td><span class="lx-mono"><?= htmlspecialchars($address); ?></span></td>
            <td><span class="lx-mono-sub"><?= htmlspecialchars($network); ?></span></td>
            <td><span class="lx-badge-iface"><?= htmlspecialchars($iface); ?></span></td>
            <td>
              <?php if (isset($hsByIface[$iface])) { ?>
                <span class="lx-pill-status is-active"><i class="fa fa-wifi"></i> <?= htmlspecialchars($hsByIface[$iface]); ?></span>
              <?php } else { ?>
                <span style="font-size:12px; color:#71717a;">Non-Hotspot (Admin / LAN / WAN)</span>
              <?php } ?>
            </td>
            <td><span style="font-size:12.5px; color:#52525b;"><?= htmlspecialchars($comment != "" ? $comment : "-"); ?></span></td>
          </tr>
          <?php
        }
        if ($TotalIP == 0) {
          echo "<tr><td colspan='7' style='padding:44px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Belum ada IP Address terpasang. Klik <strong>Tambah IP Address</strong> untuk menambahkan segmen IP baru.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <div class="lx-footer">
    <div><strong>Opsi B (Hotspot Terpisah):</strong> Tambahkan IP baru (mis. <code style="background:#f4f4f5; padding:1px 5px; border-radius:4px;">192.168.20.1/24</code>) pada <code style="background:#f4f4f5; padding:1px 5px; border-radius:4px;">ether5</code>, lalu klik tombol <strong>Hotspot</strong> untuk menjalankan Hotspot Setup Wizard.</div>
    <div>RouterOS IP &rsaquo; Addresses</div>
  </div>
</div>

<!-- Modal Tambah IP Address -->
<div id="lxAddIPModal" class="lx-overlay" onclick="if(event.target===this) closeIPModal('lxAddIPModal');">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Tambah IP Address (IP &rsaquo; Addresses)</h4>
      <button type="button" class="lx-dialog-close" onclick="closeIPModal('lxAddIPModal');"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <div class="lx-dialog-body">
        <div class="lx-field">
          <label>Address (IP / Prefix CIDR) <span>— Wajib pakai /prefix</span></label>
          <input type="text" name="address" id="addIPAddress" class="lx-input lx-mono-input" placeholder="192.168.20.1/24" oninput="autoCalcNetwork('addIPAddress','addIPNetwork')" required>
          <div class="lx-preset-row">
            <span class="lx-preset-pill" onclick="applyIPPreset('192.168.20.1/24', 'ether5', 'Hotspot Terpisah ether5')">192.168.20.1/24 (Opsi B)</span>
            <span class="lx-preset-pill" onclick="applyIPPreset('192.168.30.1/24', '', 'Segmen Hotspot Baru')">192.168.30.1/24</span>
            <span class="lx-preset-pill" onclick="applyIPPreset('10.20.30.1/24', '', 'Segmen Hotspot VLAN/AP')">10.20.30.1/24</span>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Network <span>(Otomatis)</span></label>
            <input type="text" name="network" id="addIPNetwork" class="lx-input lx-mono-input" placeholder="192.168.20.0">
          </div>
          <div class="lx-field">
            <label>Interface</label>
            <select name="interface" id="addIPIface" class="lx-select" required>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") {
                  $sel = ($ifName == "ether5") ? "selected" : "";
                  echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '" ' . $sel . '>' . htmlspecialchars($ifName) . '</option>';
                }
              }
              ?>
            </select>
          </div>
        </div>

        <div class="lx-field">
          <label>Comment <span>(Opsional)</span></label>
          <input type="text" name="comment" id="addIPComment" class="lx-input" placeholder="Contoh: Jalur AP Hotspot Terpisah (Opsi B)">
        </div>

        <label class="lx-check-box">
          <input type="checkbox" name="goto_hotspot" value="1" checked>
          <span>Setelah simpan, langsung buka <strong>Hotspot Setup Wizard</strong> untuk interface ini</span>
        </label>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closeIPModal('lxAddIPModal');">Batal</button>
        <button type="submit" name="add_ip_address" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan IP Address</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit IP Address -->
<div id="lxEditIPModal" class="lx-overlay" onclick="if(event.target===this) closeIPModal('lxEditIPModal');">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Edit IP Address</h4>
      <button type="button" class="lx-dialog-close" onclick="closeIPModal('lxEditIPModal');"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <input type="hidden" name="addr_id" id="editIPId">
      <div class="lx-dialog-body">
        <div class="lx-field">
          <label>Address (IP / Prefix CIDR)</label>
          <input type="text" name="address" id="editIPAddress" class="lx-input lx-mono-input" oninput="autoCalcNetwork('editIPAddress','editIPNetwork')" required>
        </div>
        <div class="lx-form-row">
          <div class="lx-field">
            <label>Network</label>
            <input type="text" name="network" id="editIPNetwork" class="lx-input lx-mono-input">
          </div>
          <div class="lx-field">
            <label>Interface</label>
            <select name="interface" id="editIPIface" class="lx-select" required>
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
        <div class="lx-field">
          <label>Comment</label>
          <input type="text" name="comment" id="editIPComment" class="lx-input">
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closeIPModal('lxEditIPModal');">Batal</button>
        <button type="submit" name="edit_ip_address" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Perubahan</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Konfirmasi Hapus IP -->
<div id="lxDelIPModal" class="lx-overlay" onclick="if(event.target===this) closeIPModal('lxDelIPModal');">
  <div class="lx-dialog" style="max-width:400px;">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Hapus IP Address</h4>
      <button type="button" class="lx-dialog-close" onclick="closeIPModal('lxDelIPModal');"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Yakin ingin menghapus IP Address <strong id="delIPTarget" style="color:#09090b;"></strong> dari router? Pastikan Anda tidak menghapus IP yang sedang dipakai untuk mengakses Mikhmon/WinBox.
      </p>
    </div>
    <div class="lx-dialog-foot">
      <button type="button" class="lx-btn-modal" onclick="closeIPModal('lxDelIPModal');">Batal</button>
      <button type="button" id="delIPConfirmBtn" class="lx-btn-modal is-danger"><i class="fa fa-trash-o"></i><span>Hapus IP</span></button>
    </div>
  </div>
</div>

<script>
  function openIPModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.add('is-open');
  }
  function closeIPModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('is-open');
  }
  function autoCalcNetwork(addrInputId, netInputId) {
    var val = (document.getElementById(addrInputId).value || '').trim();
    var m = val.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/);
    if (!m) return;
    var octets = [parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10), parseInt(m[4], 10)];
    var prefix = parseInt(m[5], 10);
    if (prefix < 1 || prefix > 32 || octets.some(function(o){ return o < 0 || o > 255; })) return;
    var ipInt = ((octets[0] << 24) | (octets[1] << 16) | (octets[2] << 8) | octets[3]) >>> 0;
    var mask = prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - prefix)) >>> 0);
    var netInt = (ipInt & mask) >>> 0;
    var netStr = [(netInt >>> 24) & 255, (netInt >>> 16) & 255, (netInt >>> 8) & 255, netInt & 255].join('.');
    document.getElementById(netInputId).value = netStr;
  }
  function openAddIPModal(preIface) {
    if (preIface) {
      var sel = document.getElementById('addIPIface');
      if (sel) sel.value = preIface;
      if (preIface === 'ether5' && !document.getElementById('addIPAddress').value) {
        document.getElementById('addIPAddress').value = '192.168.20.1/24';
        autoCalcNetwork('addIPAddress', 'addIPNetwork');
      }
    }
    openIPModal('lxAddIPModal');
  }
  function applyIPPreset(cidr, iface, comment) {
    document.getElementById('addIPAddress').value = cidr;
    autoCalcNetwork('addIPAddress', 'addIPNetwork');
    if (iface) {
      var sel = document.getElementById('addIPIface');
      for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === iface) {
          sel.value = iface;
          break;
        }
      }
    }
    if (comment) {
      document.getElementById('addIPComment').value = comment;
    }
  }
  function openEditIPModal(el) {
    document.getElementById('editIPId').value = el.getAttribute('data-id') || '';
    document.getElementById('editIPAddress').value = el.getAttribute('data-address') || '';
    document.getElementById('editIPNetwork').value = el.getAttribute('data-network') || '';
    document.getElementById('editIPIface').value = el.getAttribute('data-iface') || '';
    document.getElementById('editIPComment').value = el.getAttribute('data-comment') || '';
    openIPModal('lxEditIPModal');
  }
  function openDelIPModal(el) {
    var name = el.getAttribute('data-name') || '';
    var url = el.getAttribute('data-url') || '';
    document.getElementById('delIPTarget').textContent = name;
    document.getElementById('delIPConfirmBtn').onclick = function() {
      closeIPModal('lxDelIPModal');
      loadpage(url);
    };
    openIPModal('lxDelIPModal');
  }
</script>