<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
}
?>

<style>
  #mhm-dhcpalert {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-dhcpalert .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-dhcpalert .lx-title-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  #mhm-dhcpalert .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-dhcpalert .lx-count {
    font-size: 11.5px; font-weight: 500; color: #71717a; background: #f4f4f5; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-dhcpalert .lx-wan-pill {
    font-size: 11px; font-weight: 500; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-dhcpalert .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-dhcpalert .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
    transition: all 0.15s ease;
  }
  #mhm-dhcpalert .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-dhcpalert .lx-btn.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; }
  #mhm-dhcpalert .lx-btn.is-primary:hover { background: #27272a; }
  #mhm-dhcpalert .lx-btn.is-sensor-off { background: #fffbeb; border-color: #fde68a; color: #b45309 !important; }
  #mhm-dhcpalert .lx-btn.is-sensor-on { background: #ecfdf5; border-color: #a7f3d0; color: #047857 !important; }

  /* Table */
  #mhm-dhcpalert .lx-table-wrap { width: 100%; overflow-x: auto; max-height: 62vh; }
  #mhm-dhcpalert .lx-table {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-dhcpalert .lx-table thead th {
    position: sticky; top: 0; z-index: 5; background: #ffffff !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 600 !important; text-transform: uppercase !important;
    letter-spacing: 0.05em !important; padding: 12px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-dhcpalert .lx-table tbody td {
    padding: 12px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 13px !important; color: #27272a !important;
  }
  #mhm-dhcpalert .lx-table tbody tr:hover { background-color: #fafafa !important; }

  #mhm-dhcpalert .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #3f3f46; }
  #mhm-dhcpalert .lx-quarantine-btn {
    display: inline-flex; align-items: center; height: 27px; padding: 0 10px; border-radius: 6px;
    font-size: 11.5px; font-weight: 600; cursor: pointer; gap: 5px; border: 1px solid #fecaca;
    background: #fef2f2; color: #dc2626; transition: all 0.15s ease;
  }
  #mhm-dhcpalert .lx-quarantine-btn:hover { background: #dc2626; color: #ffffff; }
  #mhm-dhcpalert .lx-unquarantine-btn {
    display: inline-flex; align-items: center; height: 27px; padding: 0 10px; border-radius: 6px;
    font-size: 11.5px; font-weight: 600; cursor: pointer; gap: 5px; border: 1px solid #a7f3d0;
    background: #ecfdf5; color: #047857; transition: all 0.15s ease;
  }
  #mhm-dhcpalert .lx-unquarantine-btn:hover { background: #047857; color: #ffffff; }

  #mhm-dhcpalert .lx-badge-high {
    display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 5px;
    background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-size: 11.5px; font-weight: 600;
  }
  #mhm-dhcpalert .lx-badge-warn {
    display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 5px;
    background: #fffbeb; border: 1px solid #fde68a; color: #b45309; font-size: 11.5px; font-weight: 500;
  }

  #mhm-dhcpalert .lx-quarantined-bar {
    padding: 12px 24px;
    background: #fef2f2;
    border-top: 1px solid #fecaca;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #991b1b;
  }
  #mhm-dhcpalert .lx-q-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px;
    background: #ffffff; border: 1px solid #fca5a5; border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11.5px; color: #dc2626;
  }
  #mhm-dhcpalert .lx-q-chip button {
    border: none; background: transparent; color: #71717a; cursor: pointer; padding: 0 2px; font-size: 12px;
  }
  #mhm-dhcpalert .lx-q-chip button:hover { color: #09090b; }

  #mhm-dhcpalert .lx-footer {
    padding: 12px 24px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; font-size: 11.5px; color: #71717a;
  }
</style>

<div id="mhm-dhcpalert">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">Anti-NetCut &amp; Rogue DHCP Detector</h3>
      <span class="lx-count" id="threatCountBadge">Memindai...</span>
      <span class="lx-wan-pill" id="wanExcludedBadge" style="display:none;"></span>
    </div>

    <div class="lx-actions">
      <button type="button" id="btnEnableSensor" onclick="enableDhcpSensor();" class="lx-btn is-sensor-off" title="Aktifkan sensor DHCP Alert pada interface LAN lokal">
        <i class="fa fa-bell-o"></i>
        <span id="sensorBtnLabel">Aktifkan Sensor DHCP</span>
      </button>
      <button type="button" onclick="scanNetworkThreats();" class="lx-btn is-primary" title="Scan Jaringan">
        <i class="fa fa-refresh" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Scan Ulang</span>
      </button>
    </div>
  </div>

  <!-- Loading State -->
  <div id="threatLoadingState" style="padding:48px; text-align:center; color:#71717a; font-size:13px;">
    <i class="fa fa-spinner fa-spin" style="font-size:20px; margin-bottom:8px; display:block;"></i>
    Memindai anomali jaringan lokal (mengecualikan jalur WAN/ISP)...
  </div>

  <!-- Table Threats -->
  <div class="lx-table-wrap" id="threatTableWrap" style="display:none;">
    <table class="table lx-table">
      <thead>
        <tr>
          <th style="text-align:center; width:125px;">Aksi</th>
          <th>MAC Address</th>
          <th>IP Address</th>
          <th>Interface Lokal</th>
          <th>Status / Indikasi</th>
        </tr>
      </thead>
      <tbody id="threatTableBody"></tbody>
    </table>
  </div>

  <!-- Bar Daftar Perangkat yang Sedang Dikarantina -->
  <div id="quarantinedListBar" class="lx-quarantined-bar" style="display:none;">
    <strong><i class="fa fa-ban"></i> Diblokir / Karantina Aktif:</strong>
    <div id="quarantinedChips" style="display:inline-flex; flex-wrap:wrap; gap:6px;"></div>
  </div>

  <div class="lx-footer">
    <div>Jalur WAN / gateway internet otomatis dikecualikan dari pemindaian agar tidak terblokir</div>
    <div>RouterOS v6 &amp; v7 Compatible</div>
  </div>
</div>

<!-- Modal Konfirmasi Karantina -->
<div id="lxQuarantineModal" class="lx-overlay" onclick="if(event.target===this) closeQuarantineModal();" style="position:fixed; inset:0; z-index:9999; background:rgba(9, 9, 11, 0.48); display:none; align-items:center; justify-content:center; padding:16px;">
  <div class="lx-dialog" style="width:100%; max-width:400px; background:#ffffff; border:1px solid #e4e4e7; border-radius:12px; overflow:hidden;">
    <div class="lx-dialog-head" style="padding:16px 20px; border-bottom:1px solid #f4f4f5; display:flex; align-items:center; justify-content:space-between;">
      <h4 class="lx-dialog-title" style="margin:0; font-size:15px; font-weight:600; color:#09090b;">Karantina Perangkat</h4>
      <button type="button" class="lx-dialog-close" onclick="closeQuarantineModal();" style="border:none; background:transparent; font-size:14px; cursor:pointer;"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body" style="padding:18px 20px;">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Blokir akses jaringan lokal untuk MAC Address <strong id="quarantineTargetMac" style="color:#09090b;" class="lx-mono"></strong> sekarang?
      </p>
    </div>
    <div class="lx-dialog-foot" style="padding:13px 20px; background:#fafafa; border-top:1px solid #f4f4f5; display:flex; justify-content:flex-end; gap:8px;">
      <button type="button" class="lx-btn" onclick="closeQuarantineModal();">Batal</button>
      <button type="button" id="quarantineConfirmBtn" class="lx-btn" style="background:#dc2626; color:#ffffff; border-color:#dc2626;"><i class="fa fa-ban"></i><span>Karantina</span></button>
    </div>
  </div>
</div>

<script>
  var pppSession = <?= json_encode($session); ?>;

  function scanNetworkThreats() {
    document.getElementById('threatLoadingState').style.display = '';
    document.getElementById('threatTableWrap').style.display = 'none';

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ip/dhcp_alert_ajax.php?action=scan&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        document.getElementById('threatLoadingState').style.display = 'none';
        try {
          var res = JSON.parse(xhr.responseText);
          if (res && res.ok) {
            var threats = res.threats || [];
            var qList = res.quarantined_list || [];
            var wans = res.wan_excluded || [];

            document.getElementById('threatCountBadge').textContent = threats.length + ' anomali lokal';

            if (wans.length > 0) {
              var wanEl = document.getElementById('wanExcludedBadge');
              wanEl.textContent = 'WAN Aman: ' + wans.join(', ');
              wanEl.style.display = '';
            }

            var sensorBtn = document.getElementById('btnEnableSensor');
            var sensorLbl = document.getElementById('sensorBtnLabel');
            if (res.sensor_active) {
              sensorBtn.className = 'lx-btn is-sensor-on';
              sensorLbl.textContent = 'Sensor DHCP: Aktif';
            } else {
              sensorBtn.className = 'lx-btn is-sensor-off';
              sensorLbl.textContent = 'Aktifkan Sensor DHCP';
            }

            var tbodyHtml = '';
            for (var i = 0; i < threats.length; i++) {
              var t = threats[i];
              var mac = t.mac || '-';
              var ip = t.ip || '-';
              var iface = t.interface || '-';
              var reason = t.reason || '-';
              var badgeCls = (t.severity === 'high') ? 'lx-badge-high' : 'lx-badge-warn';

              var actionBtn = '';
              if (t.quarantined) {
                actionBtn = '<button type="button" class="lx-unquarantine-btn" onclick="unquarantineMac(\'' + escapeHtml(mac) + '\');"><i class="fa fa-unlock"></i><span>Lepas</span></button>';
              } else {
                var qUrl = './ip/dhcp_alert_ajax.php?action=quarantine&mac=' + encodeURIComponent(mac) + '&session=' + encodeURIComponent(pppSession);
                actionBtn = '<button type="button" class="lx-quarantine-btn" data-mac="' + escapeHtml(mac) + '" data-url="' + escapeHtml(qUrl) + '" onclick="openQuarantineModal(this);"><i class="fa fa-ban"></i><span>Karantina</span></button>';
              }

              tbodyHtml += '<tr>' +
                '<td style="text-align:center;">' + actionBtn + '</td>' +
                '<td><span class="lx-mono" style="font-weight:600; color:' + (t.severity === 'high' ? '#dc2626' : '#09090b') + ';">' + escapeHtml(mac) + '</span></td>' +
                '<td><span class="lx-mono">' + escapeHtml(ip) + '</span></td>' +
                '<td><span class="lx-mono">' + escapeHtml(iface) + '</span></td>' +
                '<td><span class="' + badgeCls + '">' + escapeHtml(reason) + '</span></td>' +
                '</tr>';
            }

            if (threats.length === 0) {
              tbodyHtml = '<tr><td colspan="5" style="text-align:center; padding:32px; color:#047857; font-weight:600;"><i class="fa fa-check-circle" style="margin-right:6px;"></i> Jaringan Lokal Aman — Tidak ada Rogue DHCP atau ARP Spoofing terdeteksi.</td></tr>';
            }

            document.getElementById('threatTableBody').innerHTML = tbodyHtml;
            document.getElementById('threatTableWrap').style.display = '';

            // Render bar perangkat yang sedang diblokir
            var qBar = document.getElementById('quarantinedListBar');
            var qChips = document.getElementById('quarantinedChips');
            if (qList.length > 0) {
              var chipsHtml = '';
              for (var q = 0; q < qList.length; q++) {
                chipsHtml += '<span class="lx-q-chip">' + escapeHtml(qList[q]) +
                  ' <button type="button" title="Lepas Karantina" onclick="unquarantineMac(\'' + escapeHtml(qList[q]) + '\');">&times;</button></span>';
              }
              qChips.innerHTML = chipsHtml;
              qBar.style.display = 'flex';
            } else {
              qBar.style.display = 'none';
            }
          }
        } catch (e) {}
      }
    };
    xhr.send();
  }

  function enableDhcpSensor() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ip/dhcp_alert_ajax.php?action=enable_alert&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        scanNetworkThreats();
      }
    };
    xhr.send();
  }

  function unquarantineMac(mac) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ip/dhcp_alert_ajax.php?action=unquarantine&mac=' + encodeURIComponent(mac) + '&session=' + encodeURIComponent(pppSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        scanNetworkThreats();
      }
    };
    xhr.send();
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function openQuarantineModal(el) {
    var mac = el.getAttribute('data-mac') || '';
    var url = el.getAttribute('data-url') || '';
    document.getElementById('quarantineTargetMac').textContent = mac;
    document.getElementById('quarantineConfirmBtn').onclick = function() {
      closeQuarantineModal();
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url + '&_t=' + Date.now(), true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          scanNetworkThreats();
        }
      };
      xhr.send();
    };
    document.getElementById('lxQuarantineModal').style.display = 'flex';
  }

  function closeQuarantineModal() {
    document.getElementById('lxQuarantineModal').style.display = 'none';
  }

  scanNetworkThreats();
</script>