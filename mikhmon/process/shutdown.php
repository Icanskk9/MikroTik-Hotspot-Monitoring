<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

if (isset($_POST['shutdown'])) {
  $API = new RouterosAPI();
  $API->debug = false;
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $API->write('/system/shutdown');
    $API->read();
    $API->disconnect();
  }
  $_SESSION['connect'] = "";
  echo "<script>window.location='./admin.php?id=sessions'</script>";
  exit;
}

$routerLabel = !empty($hotspotname) ? $hotspotname : $session;
?>

<style>
  #mhm-shutdown-wrap {
    max-width: 460px;
    margin: 48px auto;
    padding: 0 12px;
  }
  #mhm-shutdown-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(9, 9, 11, 0.04), 0 14px 32px -16px rgba(9, 9, 11, 0.12);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-shutdown-card .sd-header {
    padding: 16px 22px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fafafa;
  }
  #mhm-shutdown-card .sd-badge-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }
  #mhm-shutdown-card .sd-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
  }
  #mhm-shutdown-card .sd-body {
    padding: 22px;
  }
  #mhm-shutdown-card .sd-question {
    font-size: 14px;
    font-weight: 500;
    color: #18181b;
    margin: 0 0 12px 0;
    line-height: 1.5;
  }
  #mhm-shutdown-card .sd-router-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 9px;
    padding: 10px 14px;
    margin-bottom: 14px;
    font-size: 12.5px;
  }
  #mhm-shutdown-card .sd-warning {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-left: 4px solid #dc2626;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12px;
    color: #991b1b;
    line-height: 1.5;
    margin-bottom: 20px;
  }
  #mhm-shutdown-card .sd-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
  }
  #mhm-shutdown-card .sd-btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    cursor: pointer;
    text-decoration: none !important;
    transition: all 0.15s ease;
    border: 1px solid transparent;
  }
  #mhm-shutdown-card .sd-btn-cancel {
    background: #ffffff;
    border-color: #e4e4e7;
    color: #18181b !important;
  }
  #mhm-shutdown-card .sd-btn-cancel:hover {
    background: #f4f4f5;
    border-color: #d4d4d8;
  }
  #mhm-shutdown-card .sd-btn-confirm {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff !important;
    font-weight: 600;
  }
  #mhm-shutdown-card .sd-btn-confirm:hover {
    background: #b91c1c;
    border-color: #b91c1c;
  }
  #mhm-shutdown-card .sd-btn-confirm:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
</style>

<div id="mhm-shutdown-wrap">
  <div id="mhm-shutdown-card">
    <div class="sd-header">
      <div class="sd-badge-icon">
        <i class="fa fa-power-off"></i>
      </div>
      <h3 class="sd-title">Konfirmasi Shutdown MikroTik</h3>
    </div>

    <div class="sd-body">
      <p class="sd-question">
        Apakah Anda yakin ingin mematikan daya router <strong><?= htmlspecialchars($session); ?></strong>?
      </p>

      <div class="sd-router-box">
        <span style="color:#71717a;">Target Router</span>
        <span style="font-weight:600; color:#09090b; font-family:monospace;"><?= htmlspecialchars($routerLabel); ?> (<?= htmlspecialchars($iphost); ?>)</span>
      </div>

      <div class="sd-warning">
        <strong>Peringatan Keras:</strong> Router akan mati total dan seluruh layanan jaringan berhenti. Perangkat harus dinyalakan kembali secara manual (cabut-pasang adaptor/power) untuk aktif lagi.
      </div>

      <form method="post" action="" id="formShutdownMikrotik" class="sd-actions">
        <a href="./?session=<?= urlencode($session); ?>" class="sd-btn sd-btn-cancel">
          <i class="fa fa-times"></i>
          <span>Batal</span>
        </a>
        <button type="submit" name="shutdown" id="btnSubmitShutdown" class="sd-btn sd-btn-confirm">
          <i class="fa fa-power-off"></i>
          <span>Ya, Matikan Router</span>
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  document.getElementById('formShutdownMikrotik').addEventListener('submit', function() {
    var btn = document.getElementById('btnSubmitShutdown');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Mematikan Router...</span>';
    }
  });
</script>