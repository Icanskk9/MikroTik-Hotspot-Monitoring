<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

if (isset($_POST['reboot'])) {
  $API = new RouterosAPI();
  $API->debug = false;
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $API->write('/system/reboot');
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
  #mhm-reboot-wrap {
    max-width: 460px;
    margin: 48px auto;
    padding: 0 12px;
  }
  #mhm-reboot-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(9, 9, 11, 0.04), 0 14px 32px -16px rgba(9, 9, 11, 0.12);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-reboot-card .rb-header {
    padding: 16px 22px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fafafa;
  }
  #mhm-reboot-card .rb-badge-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }
  #mhm-reboot-card .rb-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
  }
  #mhm-reboot-card .rb-body {
    padding: 22px;
  }
  #mhm-reboot-card .rb-question {
    font-size: 14px;
    font-weight: 500;
    color: #18181b;
    margin: 0 0 12px 0;
    line-height: 1.5;
  }
  #mhm-reboot-card .rb-router-box {
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
  #mhm-reboot-card .rb-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12px;
    color: #92400e;
    line-height: 1.5;
    margin-bottom: 20px;
  }
  #mhm-reboot-card .rb-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
  }
  #mhm-reboot-card .rb-btn {
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
  #mhm-reboot-card .rb-btn-cancel {
    background: #ffffff;
    border-color: #e4e4e7;
    color: #18181b !important;
  }
  #mhm-reboot-card .rb-btn-cancel:hover {
    background: #f4f4f5;
    border-color: #d4d4d8;
  }
  #mhm-reboot-card .rb-btn-confirm {
    background: #d97706;
    border-color: #d97706;
    color: #ffffff !important;
    font-weight: 600;
  }
  #mhm-reboot-card .rb-btn-confirm:hover {
    background: #b45309;
    border-color: #b45309;
  }
  #mhm-reboot-card .rb-btn-confirm:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
</style>

<div id="mhm-reboot-wrap">
  <div id="mhm-reboot-card">
    <div class="rb-header">
      <div class="rb-badge-icon">
        <i class="fa fa-power-off"></i>
      </div>
      <h3 class="rb-title">Konfirmasi Reboot MikroTik</h3>
    </div>

    <div class="rb-body">
      <p class="rb-question">
        Apakah Anda yakin ingin me-restart router <strong><?= htmlspecialchars($session); ?></strong>?
      </p>

      <div class="rb-router-box">
        <span style="color:#71717a;">Target Router</span>
        <span style="font-weight:600; color:#09090b; font-family:monospace;"><?= htmlspecialchars($routerLabel); ?> (<?= htmlspecialchars($iphost); ?>)</span>
      </div>

      <div class="rb-warning">
        <strong>Perhatian:</strong> Seluruh koneksi klien Hotspot dan PPPoE akan terputus sementara selama proses reboot berlangsung (&plusmn; 1&ndash;2 menit).
      </div>

      <form method="post" action="" id="formRebootMikrotik" class="rb-actions">
        <a href="./?session=<?= urlencode($session); ?>" class="rb-btn rb-btn-cancel">
          <i class="fa fa-times"></i>
          <span>Batal</span>
        </a>
        <button type="submit" name="reboot" id="btnSubmitReboot" class="rb-btn rb-btn-confirm">
          <i class="fa fa-refresh"></i>
          <span>Ya, Reboot Sekarang</span>
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  document.getElementById('formRebootMikrotik').addEventListener('submit', function() {
    var btn = document.getElementById('btnSubmitReboot');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Merestart Router...</span>';
    }
  });
</script>