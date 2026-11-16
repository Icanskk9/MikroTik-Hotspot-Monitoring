<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  // Kick / Putus Koneksi Aktif
  if (isset($_GET['kick']) && $_GET['kick'] != "") {
    $API->comm("/ppp/active/remove", array(".id" => $_GET['kick']));
    echo "<script>window.location='./?ppp=active&session=" . $session . "'</script>";
    exit;
  }

  $getactive = $API->comm("/ppp/active/print");
  $TotalActive = count($getactive);

  $getsecrets = $API->comm("/ppp/secret/print");
  $TotalSecrets = count($getsecrets);
}
?>

<style>
  #mhm-pppactive {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-pppactive .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-pppactive .lx-title-group { display: flex; align-items: center; gap: 10px; }
  #mhm-pppactive .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-pppactive .lx-count {
    font-size: 11.5px; font-weight: 500; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-pppactive .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-pppactive .lx-search { position: relative; width: 225px; max-width: 100%; }
  #mhm-pppactive .lx-search i {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 12px; color: #a1a1aa; pointer-events: none;
  }
  #mhm-pppactive #filterTable {
    width: 100% !important; height: 34px !important; padding: 0 12px 0 32px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  #mhm-pppactive #filterTable:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  #mhm-pppactive .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
  }
  #mhm-pppactive .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-pppactive .lx-btn.is-live-on {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857 !important;
  }

  /* Metric Strip */
  #mhm-pppactive .lx-metrics {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7; background: #fafafa;
  }
  @media (max-width: 768px) { #mhm-pppactive .lx-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  #mhm-pppactive .lx-metric-item { padding: 14px 24px; border-right: 1px solid #e4e4e7; }
  #mhm-pppactive .lx-metric-item:last-child { border-right: none; }
  #mhm-pppactive .lx-metric-label {
    font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #71717a; margin-bottom: 4px;
  }
  #mhm-pppactive .lx-metric-val {
    font-size: 18px; font-weight: 600; color: #09090b; font-variant-numeric: tabular-nums;
    display: flex; align-items: center; gap: 8px;
  }
  #mhm-pppactive .lx-dot-live {
    width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  }

  /* Table */
  #mhm-pppactive .lx-table-wrap { width: 100%; overflow-x: auto; max-height: 62vh; }
  #mhm-pppactive .lx-table {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-pppactive .lx-table thead th {
    position: sticky; top: 0; z-index: 5; background: #ffffff !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 600 !important; text-transform: uppercase !important;
    letter-spacing: 0.05em !important; padding: 12px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppactive .lx-table tbody td {
    padding: 12px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 13px !important; color: #27272a !important;
  }
  #mhm-pppactive .lx-table tbody tr:hover { background-color: #fafafa !important; }
  #mhm-pppactive .lx-user { font-weight: 600; color: #09090b; display: flex; align-items: center; gap: 8px; }
  #mhm-pppactive .lx-pill-srv {
    display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 5px;
    background: #eff6ff; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 600; color: #1d4ed8;
  }
  #mhm-pppactive .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #3f3f46; }
  #mhm-pppactive .lx-mono-bold { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; font-weight: 600; color: #09090b; }

  /* Traffic Badges */
  #mhm-pppactive .lx-traf-box {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
  }
  #mhm-pppactive .lx-traf-dl {
    color: #047857;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-width: 82px;
  }
  #mhm-pppactive .lx-traf-ul {
    color: #0284c7;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    min-width: 82px;
  }

  /* IP & Ping Action */
  #mhm-pppactive .lx-ip-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-pppactive .lx-ip-link {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 600;
    color: #0284c7 !important;
    text-decoration: none !important;
    padding: 2px 6px;
    border-radius: 5px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
  }
  #mhm-pppactive .lx-ip-link:hover {
    background: #e0f2fe;
    border-color: #7dd3fc;
  }
  #mhm-pppactive .lx-probe-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 24px;
    padding: 0 7px;
    border-radius: 5px;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #52525b;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    gap: 4px;
  }
  #mhm-pppactive .lx-probe-btn:hover {
    background: #fafafa;
    border-color: #18181b;
    color: #09090b;
  }

  #mhm-pppactive .lx-kick-btn {
    display: inline-flex; align-items: center; justify-content: center; height: 28px; padding: 0 10px;
    border-radius: 6px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626;
    font-size: 11.5px; font-weight: 500; cursor: pointer; gap: 5px;
  }
  #mhm-pppactive .lx-kick-btn:hover { background: #dc2626; color: #ffffff; }
  #mhm-pppactive .lx-footer {
    padding: 12px 24px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; font-size: 11.5px; color: #71717a;
  }

  /* Modals */
  .lx-overlay {
    position: fixed; inset: 0; z-index: 9999; background: rgba(9, 9, 11, 0.48);
    backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; padding: 16px;
  }
  .lx-overlay.is-open { display: flex; }
  .lx-dialog {
    width: 100%; max-width: 420px; background: #ffffff; border: 1px solid #e4e4e7;
    border-radius: 12px; box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.25); overflow: hidden; color: #18181b;
  }
  .lx-dialog-head {
    padding: 16px 20px; border-bottom: 1px solid #f4f4f5; display: flex; align-items: center; justify-content: space-between;
  }
  .lx-dialog-title { margin: 0; font-size: 15px; font-weight: 600; color: #09090b; display: flex; align-items: center; gap: 8px; }
  .lx-dialog-close { width: 28px; height: 28px; border-radius: 6px; border: none; background: transparent; color: #71717a; cursor: pointer; }
  .lx-dialog-body { padding: 18px 20px; }
  .lx-dialog-foot {
    padding: 13px 20px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
  }
  .lx-btn-modal {
    height: 34px; padding: 0 14px; border-radius: 8px; border: 1px solid #e4e4e7;
    background: #ffffff; color: #18181b !important; font-size: 12.5px; font-weight: 500; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px; text-decoration: none !important;
  }
  .lx-btn-modal.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; }
  .lx-btn-modal.is-danger { background: #dc2626; border-color: #dc2626; color: #ffffff !important; }

  .lx-probe-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 12.5px;
  }
  .lx-probe-badge-ok {
    padding: 2px 8px; border-radius: 999px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-weight: 600; font-size: 11.5px;
  }
  .lx-probe-badge-err {
    padding: 2px 8px; border-radius: 999px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-weight: 600; font-size: 11.5px;
  }
</style>

<div id="mhm-pppactive">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">PPPoE Active Connections</h3>
      <span class="lx-count" id="activeOnlinePill"><?= $TotalActive; ?> online</span>
    </div>

    <div class="lx-actions">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="filterTable" type="text" class="form-control" placeholder="Cari user, IP, atau MAC...">
      </div>
      <button type="button" id="btnToggleLive" onclick="toggleLiveTraffic();" class="lx-btn is-live-on" title="Jeda atau lanjutkan pembacaan trafik real-time">
        <i class="fa fa-bolt" id="liveIcon"></i>
        <span id="liveLabel">Live Monitor: ON</span>
      </button>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Refresh">
        <i class="fa fa-refresh" style="font-size:11.5px; color:#71717a;"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Metric Strip -->
  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Klien Terhubung</div>
      <div class="lx-metric-val">
        <span class="lx-dot-live"></span>
        <span id="metricOnlineVal"><?= $TotalActive; ?></span>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Download (Rx Klien)</div>
      <div class="lx-metric-val" style="color:#047857;">
        <i class="fa fa-arrow-down" style="font-size:13px;"></i>
        <span id="metricTotalDown">Memuat...</span>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Upload (Tx Klien)</div>
      <div class="lx-metric-val" style="color:#0284c7;">
        <i class="fa fa-arrow-up" style="font-size:13px;"></i>
        <span id="metricTotalUp">Memuat...</span>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Akun Terdaftar</div>
      <div class="lx-metric-val"><?= $TotalSecrets; ?></div>
    </div>
  </div>

  <!-- Data Table -->
  <div class="lx-table-wrap">
    <table id="dataTable" class="table lx-table">
      <thead>
        <tr>
          <th style="text-align:center; width:96px;">Aksi</th>
          <th>Username</th>
          <th>Live Traffic (DL / UL)</th>
          <th>IP Address &amp; Tes ONT</th>
          <th>Service</th>
          <th>Caller ID (MAC)</th>
          <th>Uptime</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($i = 0; $i < $TotalActive; $i++) {
          $act = $getactive[$i];
          $aid = $act['.id'];
          $aname = $act['name'];
          $asrv = $act['service'];
          $acaller = $act['caller-id'];
          $aaddr = $act['address'];
          $auptime = $act['uptime'];
          $kickUrl = "./?ppp=active&kick=" . urlencode($aid) . "&session=" . urlencode($session);
          $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $aname);
          ?>
          <tr data-ppp-user="<?= htmlspecialchars($aname, ENT_QUOTES); ?>">
            <td style="text-align:center;">
              <button type="button" class="lx-kick-btn" data-name="<?= htmlspecialchars($aname, ENT_QUOTES); ?>" data-url="<?= htmlspecialchars($kickUrl, ENT_QUOTES); ?>" onclick="openKickModal(this);">
                <i class="fa fa-power-off"></i>
                <span>Putus</span>
              </button>
            </td>
            <td>
              <div class="lx-user">
                <span class="lx-dot-live"></span>
                <span><?= htmlspecialchars($aname); ?></span>
              </div>
            </td>
            <td>
              <div class="lx-traf-box">
                <span class="lx-traf-dl" id="dl-<?= htmlspecialchars($safeKey, ENT_QUOTES); ?>" title="Download Klien">
                  <i class="fa fa-arrow-down" style="font-size:10px;"></i> <span>0 bps</span>
                </span>
                <span class="lx-traf-ul" id="ul-<?= htmlspecialchars($safeKey, ENT_QUOTES); ?>" title="Upload Klien">
                  <i class="fa fa-arrow-up" style="font-size:10px;"></i> <span>0 bps</span>
                </span>
              </div>
            </td>
            <td>
              <?php if ($aaddr != "") { ?>
                <div class="lx-ip-cell">
                  <a href="http://<?= htmlspecialchars($aaddr, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" onclick="openOntSmart(event, '<?= htmlspecialchars($aaddr, ENT_QUOTES); ?>', '<?= htmlspecialchars($aname, ENT_QUOTES); ?>', this);" class="lx-ip-link" title="Buka Halaman Admin ONT (<?= htmlspecialchars($aaddr, ENT_QUOTES); ?>)">
                    <?= htmlspecialchars($aaddr); ?> <i class="fa fa-external-link" style="font-size:10px; margin-left:3px;"></i>
                  </a>
                  <button type="button" class="lx-probe-btn" onclick="probeOntDevice('<?= htmlspecialchars($aaddr, ENT_QUOTES); ?>', '<?= htmlspecialchars($aname, ENT_QUOTES); ?>');" title="Tes Ping & Cek Port Web ONT">
                    <i class="fa fa-stethoscope"></i>
                    <span>Tes ONT</span>
                  </button>
                </div>
              <?php } else { ?>
                <span style="color:#d4d4d8;">&mdash;</span>
              <?php } ?>
            </td>
            <td><span class="lx-pill-srv"><?= htmlspecialchars($asrv); ?></span></td>
            <td><span class="lx-mono"><?= htmlspecialchars($acaller); ?></span></td>
            <td><span class="lx-mono"><?= htmlspecialchars($auptime); ?></span></td>
          </tr>
          <?php
        }
        ?>
      </tbody>
    </table>
  </div>

  <div class="lx-footer">
    <div>Klik IP Address untuk membuka panel admin ONT &nbsp;&middot;&nbsp; Klik <strong>Tes ONT</strong> untuk cek ping &amp; kesiapan port web (80/8080)</div>
    <div>Live Traffic Interval: 3 Detik</div>
  </div>
</div>

<!-- Modal Hasil Tes Ping & Port ONT -->
<div id="lxProbeModal" class="lx-overlay" onclick="if(event.target===this) closeProbeModal();">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">
        <i class="fa fa-stethoscope" style="color:#0284c7;"></i>
        <span>Diagnostik Koneksi ONT</span>
      </h4>
      <button type="button" class="lx-dialog-close" onclick="closeProbeModal();"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <div style="margin-bottom:12px; font-size:12.5px; color:#52525b;">
        Target Perangkat: <strong id="probeUserLabel" style="color:#09090b;"></strong> (<span id="probeIpLabel" class="lx-mono-bold"></span>)
      </div>

      <div id="probeLoading" style="padding:18px; text-align:center; color:#71717a; font-size:12.5px;">
        <i class="fa fa-spinner fa-spin" style="font-size:18px; margin-bottom:6px; display:block;"></i>
        Mengirim paket Ping dari MikroTik &amp; memeriksa port web ONT...
      </div>

      <div id="probeResultBox" style="display:none;">
        <div class="lx-probe-row">
          <span>Respon Ping (MikroTik &rarr; ONT)</span>
          <span id="probePingStatus"></span>
        </div>
        <div class="lx-probe-row">
          <span>Web Admin HTTP (Port 80)</span>
          <span id="probePort80Status"></span>
        </div>
        <div class="lx-probe-row" style="margin-bottom:0;">
          <span>Web Admin Alternatif (Port 8080)</span>
          <span id="probePort8080Status"></span>
        </div>
      </div>
    </div>
    <div class="lx-dialog-foot">
      <a href="#" id="probeOpen80Btn" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" class="lx-btn-modal is-primary" style="display:none;">
        <i class="fa fa-external-link"></i><span>Buka Web ONT (:80)</span>
      </a>
      <a href="#" id="probeOpen8080Btn" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" class="lx-btn-modal is-primary" style="display:none;">
        <i class="fa fa-external-link"></i><span>Buka Web ONT (:8080)</span>
      </a>
      <button type="button" class="lx-btn-modal" onclick="closeProbeModal();">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Kick -->
<div id="lxKickModal" class="lx-overlay" onclick="if(event.target===this) closeKickModal();">
  <div class="lx-dialog" style="max-width:400px;">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Putus Sesi PPPoE</h4>
      <button type="button" class="lx-dialog-close" onclick="closeKickModal();"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Putuskan koneksi aktif untuk akun <strong id="kickTargetName" style="color:#09090b;"></strong> sekarang? (Perangkat klien akan melakukan dial ulang otomatis).
      </p>
    </div>
    <div class="lx-dialog-foot">
      <button type="button" class="lx-btn-modal" onclick="closeKickModal();">Batal</button>
      <button type="button" id="kickConfirmBtn" class="lx-btn-modal is-danger"><i class="fa fa-power-off"></i><span>Putuskan</span></button>
    </div>
  </div>
</div>

<script>
  var pppSession = <?= json_encode($session); ?>;
  var liveEnabled = true;
  var liveTimer = null;
  var ontPortCache = {};

  try {
    ontPortCache = JSON.parse(sessionStorage.getItem('mhm_ont_ports') || '{}') || {};
  } catch (e) {
    ontPortCache = {};
  }

  function saveOntPort(ip, port) {
    ontPortCache[ip] = port;
    try {
      sessionStorage.setItem('mhm_ont_ports', JSON.stringify(ontPortCache));
    } catch (e) {}
  }

  function safeUserKey(name) {
    return String(name).replace(/[^a-zA-Z0-9_-]/g, '_');
  }

  function fetchLiveTraffic() {
    if (!liveEnabled) return;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ppp/pppactive_ajax.php?mode=traffic&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res && res.ok) {
            document.getElementById('metricTotalDown').textContent = res.total_down_text;
            document.getElementById('metricTotalUp').textContent = res.total_up_text;
            document.getElementById('metricOnlineVal').textContent = res.total_active;
            document.getElementById('activeOnlinePill').textContent = res.total_active + ' online';

            var users = res.users || {};
            for (var uname in users) {
              if (Object.prototype.hasOwnProperty.call(users, uname)) {
                var key = safeUserKey(uname);
                var dlEl = document.getElementById('dl-' + key);
                var ulEl = document.getElementById('ul-' + key);
                if (dlEl) dlEl.querySelector('span').textContent = users[uname].down_text;
                if (ulEl) ulEl.querySelector('span').textContent = users[uname].up_text;
              }
            }
          }
        } catch (e) {}
      }
    };
    xhr.send();
  }

  function toggleLiveTraffic() {
    liveEnabled = !liveEnabled;
    var btn = document.getElementById('btnToggleLive');
    var lbl = document.getElementById('liveLabel');
    if (liveEnabled) {
      btn.classList.add('is-live-on');
      lbl.textContent = 'Live Monitor: ON';
      fetchLiveTraffic();
    } else {
      btn.classList.remove('is-live-on');
      lbl.textContent = 'Live Monitor: OFF';
    }
  }

  function redirectWinNoReferrer(win, targetUrl) {
    if (!win || win.closed) {
      window.open(targetUrl, '_blank', 'noopener,noreferrer');
      return;
    }
    var safeUrlAttr = targetUrl.replace(/"/g, '&quot;');
    var safeUrlJs = JSON.stringify(targetUrl);
    try {
      win.opener = null;
      win.document.open();
      win.document.write(
        '<!DOCTYPE html><html><head>' +
        '<meta charset="utf-8">' +
        '<meta name="referrer" content="no-referrer">' +
        '<meta http-equiv="refresh" content="0;url=' + safeUrlAttr + '">' +
        '<title>Membuka ' + safeUrlAttr + '</title>' +
        '</head><body style="font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;color:#3f3f46;background:#fafafa;gap:12px;">' +
        '<p style="margin:0;font-size:14px;">Membuka panel admin <strong>' + safeUrlAttr + '</strong>...</p>' +
        '<a id="goOntLink" href="' + safeUrlAttr + '" rel="noreferrer noopener" referrerpolicy="no-referrer" style="padding:8px 14px;background:#18181b;color:#fff;text-decoration:none;border-radius:6px;font-size:13px;">Buka Halaman Admin ONT</a>' +
        '<script>setTimeout(function(){var a=document.getElementById("goOntLink");if(a){a.click();}else{location.replace(' + safeUrlJs + ');}},50);<\/script>' +
        '</body></html>'
      );
      win.document.close();
    } catch (e) {
      win.location.replace(targetUrl);
    }
  }

  function openOntSmart(ev, ip, uname, linkEl) {
    if (ev) ev.preventDefault();
    var safeIp = String(ip).replace(/[^0-9a-fA-F.:]/g, '');

    if (ontPortCache[safeIp]) {
      var cachedUrl = (ontPortCache[safeIp] === 8080) ? ('http://' + safeIp + ':8080') : ('http://' + safeIp);
      var fastWin = window.open('about:blank', '_blank');
      redirectWinNoReferrer(fastWin, cachedUrl);
      return;
    }

    var origHtml = linkEl ? linkEl.innerHTML : '';
    if (linkEl) {
      linkEl.style.pointerEvents = 'none';
      linkEl.innerHTML = safeIp + ' <i class="fa fa-spinner fa-spin" style="font-size:10px; margin-left:3px;"></i>';
    }

    var win = window.open('about:blank', '_blank');
    if (win) {
      try {
        win.opener = null;
        win.document.open();
        win.document.write('<!DOCTYPE html><html><head><meta name="referrer" content="no-referrer"><title>Menghubungkan ke ' + safeIp + '...</title></head><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#52525b;background:#fafafa;"><p>Mendeteksi port web aktif perangkat <strong>' + safeIp + '</strong>...</p></body></html>');
        win.document.close();
      } catch (e) {}
    }

    var done = false;
    function finishRedirect(url) {
      if (done) return;
      done = true;
      if (linkEl) {
        linkEl.style.pointerEvents = '';
        linkEl.innerHTML = origHtml;
      }
      redirectWinNoReferrer(win, url);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ppp/pppactive_ajax.php?mode=probe&ip=' + encodeURIComponent(safeIp) + '&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.timeout = 5000;
    xhr.ontimeout = function() {
      finishRedirect('http://' + safeIp + ':8080');
    };
    xhr.onerror = function() {
      finishRedirect('http://' + safeIp + ':8080');
    };
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && !done) {
        var targetUrl = '';
        try {
          var res = JSON.parse(xhr.responseText);
          if (res && res.ok) {
            if (res.port_8080 && !res.port_80) {
              saveOntPort(safeIp, 8080);
              targetUrl = 'http://' + safeIp + ':8080';
            } else if (res.port_80) {
              saveOntPort(safeIp, 80);
              targetUrl = 'http://' + safeIp;
            } else if (res.port_8080) {
              saveOntPort(safeIp, 8080);
              targetUrl = 'http://' + safeIp + ':8080';
            } else {
              done = true;
              if (linkEl) {
                linkEl.style.pointerEvents = '';
                linkEl.innerHTML = origHtml;
              }
              if (win && !win.closed) win.close();
              probeOntDevice(safeIp, uname);
              return;
            }
          }
        } catch (e) {}

        if (targetUrl === '') {
          targetUrl = 'http://' + safeIp + ':8080';
        }
        finishRedirect(targetUrl);
      }
    };
    xhr.send();
  }

  function probeOntDevice(ip, uname) {
    document.getElementById('probeUserLabel').textContent = uname;
    document.getElementById('probeIpLabel').textContent = ip;
    document.getElementById('probeLoading').style.display = '';
    document.getElementById('probeResultBox').style.display = 'none';
    document.getElementById('probeOpen80Btn').style.display = 'none';
    document.getElementById('probeOpen8080Btn').style.display = 'none';
    document.getElementById('lxProbeModal').classList.add('is-open');

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ppp/pppactive_ajax.php?mode=probe&ip=' + encodeURIComponent(ip) + '&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        document.getElementById('probeLoading').style.display = 'none';
        document.getElementById('probeResultBox').style.display = '';

        var pingEl = document.getElementById('probePingStatus');
        var p80El = document.getElementById('probePort80Status');
        var p8080El = document.getElementById('probePort8080Status');

        try {
          var res = JSON.parse(xhr.responseText);
          if (res && res.ok) {
            if (res.ping_ok) {
              pingEl.innerHTML = '<span class="lx-probe-badge-ok">Online (' + res.ping_recv + '/' + res.ping_sent + ' &middot; ' + res.ping_time + ')</span>';
            } else {
              pingEl.innerHTML = '<span class="lx-probe-badge-err">Timeout (0/' + res.ping_sent + ')</span>';
            }

            if (res.port_80) {
              saveOntPort(ip, 80);
              p80El.innerHTML = '<span class="lx-probe-badge-ok">Terbuka (Siap)</span>';
              var btn80 = document.getElementById('probeOpen80Btn');
              btn80.href = 'http://' + ip;
              btn80.style.display = 'inline-flex';
            } else {
              p80El.innerHTML = '<span class="lx-probe-badge-err">Tertutup</span>';
            }

            if (res.port_8080) {
              saveOntPort(ip, 8080);
              p8080El.innerHTML = '<span class="lx-probe-badge-ok">Terbuka (Siap)</span>';
              var btn8080 = document.getElementById('probeOpen8080Btn');
              btn8080.href = 'http://' + ip + ':8080';
              btn8080.style.display = 'inline-flex';
            } else {
              p8080El.innerHTML = '<span class="lx-probe-badge-err">Tertutup</span>';
            }
          } else {
            pingEl.innerHTML = '<span class="lx-probe-badge-err">Gagal memeriksa</span>';
          }
        } catch (e) {
          pingEl.innerHTML = '<span class="lx-probe-badge-err">Error koneksi API</span>';
        }
      }
    };
    xhr.send();
  }

  function closeProbeModal() {
    document.getElementById('lxProbeModal').classList.remove('is-open');
  }

  function openKickModal(el) {
    document.getElementById('kickTargetName').textContent = el.getAttribute('data-name') || '';
    var url = el.getAttribute('data-url') || '';
    document.getElementById('kickConfirmBtn').onclick = function() {
      closeKickModal();
      loadpage(url);
    };
    document.getElementById('lxKickModal').classList.add('is-open');
  }
  function closeKickModal() {
    document.getElementById('lxKickModal').classList.remove('is-open');
  }

  // Mulai pembacaan trafik real-time saat halaman dimuat
  fetchLiveTraffic();
  if (window.pppLiveInterval) clearInterval(window.pppLiveInterval);
  window.pppLiveInterval = setInterval(fetchLiveTraffic, 3000);
</script>