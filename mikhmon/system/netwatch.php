<?php
/*
 *  AP & Device Monitor (MikroTik Netwatch API)
 *  Integrated with Mikhmon Clean UI (lx-shell) + Modern Toast & Modal Alerts
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  // Handler AJAX: Ambil data Netwatch / Tambah AP / Hapus AP
  if (isset($_POST['ajax_netwatch'])) {
    while (ob_get_level() > 0) {
      ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['ajax_netwatch'];

    if ($action == "add") {
      $host    = trim($_POST['host']);
      $comment = trim($_POST['comment']);
      $interv  = trim($_POST['interval']);
      if ($interv == "") $interv = "00:00:30";

      if ($host == "") {
        echo json_encode(array('status' => 'error', 'message' => 'IP Address perangkat wajib diisi!'));
        exit;
      }

      if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        echo json_encode(array('status' => 'error', 'message' => 'Format IPv4 tidak valid (contoh: 192.168.100.11).'));
        exit;
      }

      // Cek duplikat IP di Netwatch MikroTik
      $existing = $API->comm("/tool/netwatch/print", array("?host" => $host));
      if (is_array($existing) && count($existing) > 0) {
        echo json_encode(array('status' => 'error', 'message' => 'IP ' . $host . ' sudah terdaftar di daftar pemantauan!'));
        exit;
      }

      $labelName = ($comment != "" ? $comment : "Access Point");

      $API->comm("/tool/netwatch/add", array(
        "host"     => $host,
        "interval" => $interv,
        "timeout"  => "1000ms",
        "comment"  => $labelName
      ));

      echo json_encode(array(
        'status'  => 'success',
        'message' => $labelName . ' (' . $host . ') berhasil ditambahkan ke pemantauan.'
      ));
      exit;
    }

    if ($action == "remove") {
      $idNw = trim($_POST['id']);
      if ($idNw != "") {
        $API->comm("/tool/netwatch/remove", array(
          ".id" => $idNw
        ));
        echo json_encode(array(
          'status'  => 'success',
          'message' => 'Perangkat berhasil dihapus dari daftar Netwatch.'
        ));
      } else {
        echo json_encode(array('status' => 'error', 'message' => 'ID Netwatch tidak valid.'));
      }
      exit;
    }

    if ($action == "list") {
      $getNetwatch = $API->comm("/tool/netwatch/print");
      $items = array();
      $upCount = 0;
      $downCount = 0;

      if (is_array($getNetwatch)) {
        foreach ($getNetwatch as $nw) {
          if (isset($nw['disabled']) && $nw['disabled'] == "true") {
            continue;
          }
          $status = isset($nw['status']) ? strtolower($nw['status']) : 'unknown';
          if ($status == 'up') $upCount++;
          elseif ($status == 'down') $downCount++;

          $items[] = array(
            'id'       => isset($nw['.id']) ? $nw['.id'] : '',
            'host'     => isset($nw['host']) ? $nw['host'] : '-',
            'status'   => $status,
            'since'    => isset($nw['since']) ? $nw['since'] : '-',
            'interval' => isset($nw['interval']) ? $nw['interval'] : '30s',
            'comment'  => (isset($nw['comment']) && $nw['comment'] != '') ? $nw['comment'] : 'Access Point / Device'
          );
        }
      }

      echo json_encode(array(
        'status' => 'success',
        'up'     => $upCount,
        'down'   => $downCount,
        'total'  => count($items),
        'items'  => $items
      ));
      exit;
    }
  }
}
?>

<style>
  #mhm-netwatch {
    color: #18181b;
  }
  #mhm-netwatch .lx-shell {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }
  #mhm-netwatch .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-netwatch .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-netwatch .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-netwatch .lx-badge {
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  #mhm-netwatch .lx-badge.is-up {
    background: #dcfce7;
    color: #15803d;
  }
  #mhm-netwatch .lx-badge.is-down {
    background: #fee2e2;
    color: #b91c1c;
  }
  #mhm-netwatch .lx-body {
    padding: 22px 24px;
    background: #fafafa;
  }

  /* Add AP Form Bar */
  #mhm-netwatch .lx-add-bar {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 18px;
    display: grid;
    grid-template-columns: 1.3fr 1fr 0.7fr auto;
    gap: 12px;
    align-items: end;
  }
  @media (max-width: 900px) {
    #mhm-netwatch .lx-add-bar {
      grid-template-columns: 1fr;
    }
  }
  #mhm-netwatch .lx-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #52525b;
    margin-bottom: 5px;
  }
  #mhm-netwatch .lx-input {
    width: 100% !important;
    height: 36px !important;
    padding: 0 12px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    color: #09090b !important;
    margin: 0 !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  #mhm-netwatch .lx-input:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    outline: none !important;
  }
  #mhm-netwatch .lx-input.is-invalid {
    border-color: #dc2626 !important;
    background: #fef2f2 !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12) !important;
  }
  #mhm-netwatch .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
  }

  /* Buttons */
  #mhm-netwatch .lx-btn {
    height: 36px;
    padding: 0 14px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  #mhm-netwatch .lx-btn:hover {
    background: #f4f4f5;
  }
  #mhm-netwatch .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }
  #mhm-netwatch .lx-btn.is-primary:hover {
    background: #27272a;
  }

  /* Offline Warning Banner */
  #nw_offline_banner {
    display: none;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-left: 4px solid #dc2626;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 18px;
    align-items: center;
    gap: 12px;
    color: #991b1b;
    font-size: 12.5px;
  }
  #nw_offline_banner .nw-banner-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #fee2e2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }

  /* AP Cards Grid */
  #mhm-netwatch .lx-ap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(275px, 1fr));
    gap: 16px;
  }
  #mhm-netwatch .lx-ap-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    border-left: 4px solid #a1a1aa;
  }
  #mhm-netwatch .lx-ap-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -10px rgba(0, 0, 0, 0.08);
  }
  #mhm-netwatch .lx-ap-card.is-up {
    border-left-color: #16a34a;
  }
  #mhm-netwatch .lx-ap-card.is-down {
    border-left-color: #dc2626;
    background: #fffafa;
  }
  #mhm-netwatch .lx-ap-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-netwatch .lx-ap-name {
    font-size: 14px;
    font-weight: 600;
    color: #09090b;
    margin: 0 0 3px 0;
  }
  #mhm-netwatch .lx-ap-ip {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    color: #52525b;
    background: #f4f4f5;
    padding: 2px 7px;
    border-radius: 5px;
    display: inline-block;
  }
  #mhm-netwatch .lx-status-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  #mhm-netwatch .lx-status-pill.is-up {
    background: #dcfce7;
    color: #15803d;
  }
  #mhm-netwatch .lx-status-pill.is-down {
    background: #fee2e2;
    color: #b91c1c;
  }
  #mhm-netwatch .lx-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
  }
  #mhm-netwatch .is-up .lx-dot {
    background: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
  }
  #mhm-netwatch .is-down .lx-dot {
    background: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
  }
  #mhm-netwatch .lx-ap-foot {
    padding-top: 10px;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-netwatch .lx-del-btn {
    background: transparent;
    border: none;
    color: #a1a1aa;
    cursor: pointer;
    padding: 3px 7px;
    border-radius: 5px;
    font-size: 12px;
    transition: all 0.15s ease;
  }
  #mhm-netwatch .lx-del-btn:hover {
    background: #fee2e2;
    color: #dc2626;
  }
  #mhm-netwatch .lx-footer {
    padding: 12px 24px;
    background: #ffffff;
    border-top: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11.5px;
    color: #71717a;
  }

  /* Modern Toast Notification */
  #nw-toast-container {
    position: fixed;
    top: 68px;
    right: 22px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
  }
  .nw-toast {
    pointer-events: auto;
    min-width: 290px;
    max-width: 380px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-left: 4px solid #18181b;
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: 0 12px 28px -8px rgba(9, 9, 11, 0.18), 0 4px 10px -4px rgba(9, 9, 11, 0.08);
    display: flex;
    align-items: flex-start;
    gap: 11px;
    animation: nwSlideIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .nw-toast.is-success { border-left-color: #16a34a; }
  .nw-toast.is-error   { border-left-color: #dc2626; }
  .nw-toast.is-warning { border-left-color: #d97706; }
  .nw-toast-icon {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 1px;
  }
  .nw-toast.is-success .nw-toast-icon { background: #dcfce7; color: #16a34a; }
  .nw-toast.is-error   .nw-toast-icon { background: #fee2e2; color: #dc2626; }
  .nw-toast.is-warning .nw-toast-icon { background: #fef3c7; color: #d97706; }
  .nw-toast-content {
    flex: 1;
    min-width: 0;
  }
  .nw-toast-title {
    font-size: 12.5px;
    font-weight: 600;
    color: #09090b;
    margin-bottom: 2px;
  }
  .nw-toast-msg {
    font-size: 12px;
    color: #52525b;
    line-height: 1.4;
  }
  .nw-toast-close {
    background: transparent;
    border: none;
    color: #a1a1aa;
    cursor: pointer;
    padding: 0 2px;
    font-size: 14px;
    line-height: 1;
  }
  .nw-toast-close:hover { color: #18181b; }
  @keyframes nwSlideIn {
    from { opacity: 0; transform: translateX(18px); }
    to   { opacity: 1; transform: translateX(0); }
  }

  /* Custom Confirm Modal */
  #nw-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 9, 11, 0.45);
    backdrop-filter: blur(2px);
    z-index: 99998;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #nw-modal-backdrop.is-open {
    display: flex;
  }
  .nw-modal-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 14px;
    width: 100%;
    max-width: 400px;
    padding: 22px;
    box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.25);
    animation: nwModalPop 0.18s ease-out;
  }
  @keyframes nwModalPop {
    from { opacity: 0; transform: scale(0.95); }
    to   { opacity: 1; transform: scale(1); }
  }
  .nw-modal-head {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
  }
  .nw-modal-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #fee2e2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
  .nw-modal-title {
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
    margin: 0;
  }
  .nw-modal-desc {
    font-size: 13px;
    color: #52525b;
    line-height: 1.5;
    margin: 0 0 18px 0;
  }
  .nw-modal-device {
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 18px;
    font-size: 12.5px;
  }
  .nw-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
  }
  .nw-mbtn {
    height: 36px;
    padding: 0 15px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #18181b;
    transition: all 0.15s ease;
  }
  .nw-mbtn:hover { background: #f4f4f5; }
  .nw-mbtn.is-danger {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff;
  }
  .nw-mbtn.is-danger:hover { background: #b91c1c; }
</style>

<!-- Toast Container -->
<div id="nw-toast-container"></div>

<!-- Custom Delete Confirmation Modal -->
<div id="nw-modal-backdrop">
  <div class="nw-modal-card" role="dialog" aria-modal="true">
    <div class="nw-modal-head">
      <div class="nw-modal-icon"><i class="fa fa-trash-o"></i></div>
      <div>
        <h4 class="nw-modal-title">Hapus Perangkat dari Netwatch?</h4>
      </div>
    </div>
    <p class="nw-modal-desc">Perangkat ini akan dihapus dari daftar pemantauan MikroTik Netwatch:</p>
    <div class="nw-modal-device">
      <div style="font-weight:600; color:#09090b;" id="nw_del_name">-</div>
      <div style="font-family:monospace; color:#71717a; font-size:12px; margin-top:2px;" id="nw_del_ip">-</div>
    </div>
    <div class="nw-modal-actions">
      <button type="button" class="nw-mbtn" id="btn_nw_cancel_del">Batal</button>
      <button type="button" class="nw-mbtn is-danger" id="btn_nw_confirm_del">Ya, Hapus</button>
    </div>
  </div>
</div>

<div id="mhm-netwatch">
  <div class="lx-shell">
    <!-- Top Bar -->
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title">Access Point & Device Monitor</h3>
        <span class="lx-badge is-up"><i class="fa fa-check-circle"></i> <span id="nw_up_count">0</span> Online</span>
        <span class="lx-badge is-down"><i class="fa fa-times-circle"></i> <span id="nw_down_count">0</span> Offline</span>
      </div>

      <div style="display:flex; align-items:center; gap:8px;">
        <span id="nw_last_sync" style="font-size:11.5px; color:#71717a;">Memuat...</span>
        <button type="button" id="btn_nw_refresh" class="lx-btn">
          <i class="fa fa-refresh"></i>
          <span>Cek Sekarang</span>
        </button>
      </div>
    </div>

    <!-- Body -->
    <div class="lx-body">
      <!-- Form Tambah AP -->
      <div class="lx-add-bar">
        <div>
          <label class="lx-label">Nama Lokasi / Access Point</label>
          <input type="text" id="nw_comment" class="lx-input" placeholder="Contoh: AP Ruang Guru / AP Lab TKJ">
        </div>
        <div>
          <label class="lx-label">IP Address Perangkat</label>
          <input type="text" id="nw_host" class="lx-input lx-mono" placeholder="192.168.100.11">
        </div>
        <div>
          <label class="lx-label">Interval Cek</label>
          <input type="text" id="nw_interval" class="lx-input lx-mono" value="00:00:30" title="Format Jam:Menit:Detik">
        </div>
        <div>
          <button type="button" id="btn_nw_add" class="lx-btn is-primary" style="width:100%;">
            <i class="fa fa-plus"></i>
            <span>Tambah AP</span>
          </button>
        </div>
      </div>

      <!-- Alert Banner jika ada AP Offline -->
      <div id="nw_offline_banner">
        <div class="nw-banner-icon"><i class="fa fa-exclamation-triangle"></i></div>
        <div>
          <strong style="display:block; margin-bottom:2px;">Peringatan: Ada Access Point Tidak Terjangkau (Offline)</strong>
          <span id="nw_offline_list">-</span>
        </div>
      </div>

      <!-- Grid Kartu Status AP -->
      <div id="nw_grid" class="lx-ap-grid">
        <div style="grid-column: 1/-1; text-align:center; padding:40px; color:#71717a;">
          <i class="fa fa-circle-o-notch fa-spin"></i> Mengambil status Netwatch dari MikroTik...
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="lx-footer">
      <div>Data sinkron langsung dengan <strong>Tools &gt; Netwatch</strong> MikroTik</div>
      <div>Auto-refresh setiap <strong>15 detik</strong></div>
    </div>
  </div>
</div>

<script>
(function() {
  var sessionName = "<?= htmlspecialchars($session); ?>";
  var ajaxUrl = "./?system=netwatch&session=" + encodeURIComponent(sessionName);
  var pendingDeleteId = "";

  // Modern Toast Alert Helper
  function showToast(type, title, message) {
    var container = document.getElementById('nw-toast-container');
    if (!container) return;

    var iconClass = 'fa-info-circle';
    if (type === 'success') iconClass = 'fa-check';
    else if (type === 'error') iconClass = 'fa-times';
    else if (type === 'warning') iconClass = 'fa-exclamation';

    var toast = document.createElement('div');
    toast.className = 'nw-toast is-' + (type || 'success');
    toast.innerHTML =
      '<div class="nw-toast-icon"><i class="fa ' + iconClass + '"></i></div>' +
      '<div class="nw-toast-content">' +
        '<div class="nw-toast-title">' + escapeHtml(title) + '</div>' +
        '<div class="nw-toast-msg">' + escapeHtml(message) + '</div>' +
      '</div>' +
      '<button type="button" class="nw-toast-close" aria-label="Tutup">&times;</button>';

    var closeBtn = toast.querySelector('.nw-toast-close');
    function dismiss() {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(14px)';
      setTimeout(function() {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 200);
    }
    closeBtn.addEventListener('click', dismiss);

    container.appendChild(toast);
    setTimeout(dismiss, 4200);
  }

  function parseJsonSafe(raw) {
    try {
      var clean = (raw || '').trim();
      var s = clean.indexOf('{');
      var e = clean.lastIndexOf('}');
      if (s !== -1 && e !== -1) return JSON.parse(clean.substring(s, e + 1));
    } catch (err) {}
    return null;
  }

  function loadNetwatch(manualClick) {
    var btnRef = document.getElementById('btn_nw_refresh');
    if (btnRef) btnRef.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Memeriksa...</span>';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { ajax_netwatch: 'list' },
      dataType: 'text',
      success: function(raw) {
        if (btnRef) btnRef.innerHTML = '<i class="fa fa-refresh"></i><span>Cek Sekarang</span>';

        var res = parseJsonSafe(raw);
        if (!res || res.status !== 'success') {
          if (manualClick) showToast('error', 'Gagal Memuat', 'Tidak dapat mengambil data Netwatch dari router.');
          return;
        }

        if (manualClick) {
          showToast('success', 'Status Diperbarui', 'Berhasil menyinkronkan ' + res.total + ' perangkat dari MikroTik.');
        }

        document.getElementById('nw_up_count').textContent = res.up;
        document.getElementById('nw_down_count').textContent = res.down;

        var now = new Date();
        document.getElementById('nw_last_sync').textContent = 'Update: ' + now.toLocaleTimeString('id-ID');

        var banner = document.getElementById('nw_offline_banner');
        var offlineNames = [];

        var grid = document.getElementById('nw_grid');
        if (!res.items || res.items.length === 0) {
          banner.style.display = 'none';
          grid.innerHTML =
            '<div style="grid-column:1/-1; text-align:center; padding:40px 20px; background:#fff; border:1px dashed #e4e4e7; border-radius:10px; color:#71717a;">' +
              'Belum ada perangkat/Access Point yang dipantau. Tambahkan IP AP di kolom atas.' +
            '</div>';
          return;
        }

        var html = '';
        for (var i = 0; i < res.items.length; i++) {
          var item = res.items[i];
          var isUp = (item.status === 'up');
          if (!isUp) {
            offlineNames.push(item.comment + ' (' + item.host + ')');
          }
          var stateClass = isUp ? 'is-up' : 'is-down';
          var stateLabel = isUp ? 'Online' : 'Offline';

          html +=
            '<div class="lx-ap-card ' + stateClass + '">' +
              '<div class="lx-ap-top">' +
                '<div>' +
                  '<div class="lx-ap-name">' + escapeHtml(item.comment) + '</div>' +
                  '<span class="lx-ap-ip">' + escapeHtml(item.host) + '</span>' +
                '</div>' +
                '<span class="lx-status-pill ' + stateClass + '">' +
                  '<span class="lx-dot"></span> ' + stateLabel +
                '</span>' +
              '</div>' +
              '<div class="lx-ap-foot">' +
                '<span><i class="fa fa-clock-o"></i> Sejak: ' + escapeHtml(item.since) + '</span>' +
                '<button type="button" class="lx-del-btn" data-id="' + escapeHtml(item.id) + '" data-name="' + escapeHtml(item.comment) + '" data-ip="' + escapeHtml(item.host) + '" title="Hapus dari pemantauan">' +
                  '<i class="fa fa-trash-o"></i> Hapus' +
                '</button>' +
              '</div>' +
            '</div>';
        }
        grid.innerHTML = html;

        if (offlineNames.length > 0) {
          document.getElementById('nw_offline_list').textContent = offlineNames.join(', ');
          banner.style.display = 'flex';
        } else {
          banner.style.display = 'none';
        }
      },
      error: function() {
        if (btnRef) btnRef.innerHTML = '<i class="fa fa-refresh"></i><span>Cek Sekarang</span>';
        if (manualClick) showToast('error', 'Koneksi Bermasalah', 'Gagal terhubung ke server Mikhmon.');
      }
    });
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function(m) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];
    });
  }

  // Event Delegation untuk Tombol Hapus -> Buka Custom Modal
  document.getElementById('nw_grid').addEventListener('click', function(e) {
    var btn = e.target.closest('.lx-del-btn');
    if (!btn) return;
    pendingDeleteId = btn.getAttribute('data-id');
    document.getElementById('nw_del_name').textContent = btn.getAttribute('data-name');
    document.getElementById('nw_del_ip').textContent = btn.getAttribute('data-ip');
    document.getElementById('nw-modal-backdrop').classList.add('is-open');
  });

  function closeDeleteModal() {
    pendingDeleteId = "";
    document.getElementById('nw-modal-backdrop').classList.remove('is-open');
  }

  document.getElementById('btn_nw_cancel_del').addEventListener('click', closeDeleteModal);
  document.getElementById('nw-modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDeleteModal();
  });

  document.getElementById('btn_nw_confirm_del').addEventListener('click', function() {
    if (!pendingDeleteId) return;
    var btnConfirm = this;
    var targetId = pendingDeleteId;
    btnConfirm.disabled = true;
    btnConfirm.textContent = 'Menghapus...';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { ajax_netwatch: 'remove', id: targetId },
      dataType: 'text',
      success: function(raw) {
        btnConfirm.disabled = false;
        btnConfirm.textContent = 'Ya, Hapus';
        closeDeleteModal();

        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          showToast('success', 'Perangkat Dihapus', res.message || 'Perangkat dihapus dari Netwatch.');
        } else {
          showToast('error', 'Gagal Menghapus', (res && res.message) ? res.message : 'Terjadi kesalahan saat menghapus.');
        }
        loadNetwatch(false);
      },
      error: function() {
        btnConfirm.disabled = false;
        btnConfirm.textContent = 'Ya, Hapus';
        closeDeleteModal();
        showToast('error', 'Gagal Menghapus', 'Koneksi terputus saat menghapus perangkat.');
      }
    });
  });

  // Hapus tanda merah saat user mengetik ulang IP
  var hostInput = document.getElementById('nw_host');
  hostInput.addEventListener('input', function() {
    this.classList.remove('is-invalid');
  });

  // Tambah AP dengan Validasi & Toast Modern
  document.getElementById('btn_nw_add').addEventListener('click', function() {
    var host = hostInput.value.trim();
    var comment = document.getElementById('nw_comment').value.trim();
    var interval = document.getElementById('nw_interval').value.trim();

    if (!host) {
      hostInput.classList.add('is-invalid');
      hostInput.focus();
      showToast('warning', 'IP Address Kosong', 'Masukkan IP Address Access Point yang ingin dipantau.');
      return;
    }

    var ipv4Pattern = /^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/;
    if (!ipv4Pattern.test(host)) {
      hostInput.classList.add('is-invalid');
      hostInput.focus();
      showToast('error', 'Format IP Tidak Valid', 'Gunakan format IPv4 yang benar, misalnya 192.168.100.11');
      return;
    }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Menambahkan...</span>';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: {
        ajax_netwatch: 'add',
        host: host,
        comment: comment,
        interval: interval
      },
      dataType: 'text',
      success: function(raw) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-plus"></i><span>Tambah AP</span>';

        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          hostInput.value = '';
          document.getElementById('nw_comment').value = '';
          showToast('success', 'AP Berhasil Ditambahkan', res.message);
          loadNetwatch(false);
        } else {
          hostInput.classList.add('is-invalid');
          hostInput.focus();
          showToast('error', 'Gagal Menambahkan', (res && res.message) ? res.message : 'Tidak dapat menambahkan perangkat.');
        }
      },
      error: function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-plus"></i><span>Tambah AP</span>';
        showToast('error', 'Koneksi Gagal', 'Gagal mengirim perintah ke router MikroTik.');
      }
    });
  });

  document.getElementById('btn_nw_refresh').addEventListener('click', function() {
    loadNetwatch(true);
  });

  // Load awal & auto-refresh tiap 15 detik
  loadNetwatch(false);
  setInterval(function() { loadNetwatch(false); }, 15000);
})();
</script>