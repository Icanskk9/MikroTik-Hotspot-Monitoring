<?php
/*
 *  Top Bandwidth User Monitor (Real-Time)
 *  Combines /ip/hotspot/active/print & /queue/simple/print + Delta Byte Fallback
 *  Integrated with Mikhmon Clean UI (lx-shell)
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  if (isset($_POST['ajax_topbw'])) {
    while (ob_get_level() > 0) {
      ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['ajax_topbw'];

    // Aksi Kick (Putuskan Sesi Aktif + Opsional Hapus Cookie)
    if ($action == "kick") {
      $activeId   = trim($_POST['id']);
      $username   = trim($_POST['user']);
      $macAddr    = trim($_POST['mac']);
      $clearCook  = (isset($_POST['clear_cookie']) && $_POST['clear_cookie'] == '1');

      if ($activeId == "") {
        echo json_encode(array('status' => 'error', 'message' => 'ID sesi aktif tidak valid.'));
        exit;
      }

      $API->comm("/ip/hotspot/active/remove", array(
        ".id" => $activeId
      ));

      if ($clearCook && ($username != "" || $macAddr != "")) {
        $cookies = $API->comm("/ip/hotspot/cookie/print");
        if (is_array($cookies)) {
          foreach ($cookies as $ck) {
            $matchUser = ($username != "" && isset($ck['user']) && $ck['user'] == $username);
            $matchMac  = ($macAddr != "" && isset($ck['mac-address']) && $ck['mac-address'] == $macAddr);
            if ($matchUser || $matchMac) {
              $API->comm("/ip/hotspot/cookie/remove", array(".id" => $ck['.id']));
            }
          }
        }
      }

      echo json_encode(array(
        'status'  => 'success',
        'message' => 'Sesi user "' . htmlspecialchars($username) . '" berhasil diputuskan.'
      ));
      exit;
    }

    // Aksi List Real-Time Top Bandwidth
    if ($action == "list") {
      $getActive = $API->comm("/ip/hotspot/active/print");
      $getQueues = $API->comm("/queue/simple/print");

      // Petakan Simple Queue berdasarkan target IP dan nama queue (<hotspot-username>)
      $queueByIp   = array();
      $queueByName = array();

      if (is_array($getQueues)) {
        foreach ($getQueues as $q) {
          if (isset($q['disabled']) && $q['disabled'] == "true") continue;

          $rateStr = isset($q['rate']) ? $q['rate'] : '0/0';
          $rateParts = explode('/', $rateStr);
          $upBps   = isset($rateParts[0]) ? (float)$rateParts[0] : 0;
          $downBps = isset($rateParts[1]) ? (float)$rateParts[1] : 0;

          $maxStr = isset($q['max-limit']) ? $q['max-limit'] : '0/0';
          $maxParts = explode('/', $maxStr);
          $maxUpBps   = isset($maxParts[0]) ? (float)$maxParts[0] : 0;
          $maxDownBps = isset($maxParts[1]) ? (float)$maxParts[1] : 0;

          $qInfo = array(
            'up_bps'       => $upBps,
            'down_bps'     => $downBps,
            'max_up_bps'   => $maxUpBps,
            'max_down_bps' => $maxDownBps
          );

          if (isset($q['target']) && $q['target'] != '') {
            $targets = explode(',', $q['target']);
            foreach ($targets as $t) {
              $cleanIp = trim(explode('/', $t)[0]);
              if ($cleanIp != '') {
                $queueByIp[$cleanIp] = $qInfo;
              }
            }
          }

          if (isset($q['name']) && $q['name'] != '') {
            $queueByName[$q['name']] = $qInfo;
          }
        }
      }

      $nowTs = microtime(true);
      if (!isset($_SESSION['topbw_snap']) || !is_array($_SESSION['topbw_snap'])) {
        $_SESSION['topbw_snap'] = array();
      }
      $newSnap = array();

      $users = array();
      $totalDownBps = 0;
      $totalUpBps   = 0;

      if (is_array($getActive)) {
        foreach ($getActive as $act) {
          $actId  = isset($act['.id']) ? $act['.id'] : '';
          $user   = isset($act['user']) ? $act['user'] : '-';
          $ip     = isset($act['address']) ? $act['address'] : '-';
          $mac    = isset($act['mac-address']) ? $act['mac-address'] : '-';
          $uptime = isset($act['uptime']) ? $act['uptime'] : '-';
          $server = isset($act['server']) ? $act['server'] : '-';
          $comm   = isset($act['comment']) ? $act['comment'] : '';

          $snapKey = ($actId != '') ? $actId : ($user . '_' . $ip);

          // Di MikroTik Hotspot Active: bytes-out = Download ke client, bytes-in = Upload dari client
          $bytesOut = isset($act['bytes-out']) ? (float)$act['bytes-out'] : 0;
          $bytesIn  = isset($act['bytes-in']) ? (float)$act['bytes-in'] : 0;

          $upBps      = 0;
          $downBps    = 0;
          $maxDownBps = 0;

          $hsQueueName = "<hotspot-" . $user . ">";
          if (isset($queueByIp[$ip])) {
            $upBps      = $queueByIp[$ip]['up_bps'];
            $downBps    = $queueByIp[$ip]['down_bps'];
            $maxDownBps = $queueByIp[$ip]['max_down_bps'];
          } elseif (isset($queueByName[$hsQueueName])) {
            $upBps      = $queueByName[$hsQueueName]['up_bps'];
            $downBps    = $queueByName[$hsQueueName]['down_bps'];
            $maxDownBps = $queueByName[$hsQueueName]['max_down_bps'];
          }

          // Fallback: hitung kecepatan real-time dari selisih byte jika user tidak punya Simple Queue (misal profil default)
          if ($downBps <= 0 && $upBps <= 0 && isset($_SESSION['topbw_snap'][$snapKey])) {
            $prev = $_SESSION['topbw_snap'][$snapKey];
            $dt = $nowTs - $prev['ts'];
            if ($dt >= 0.5 && $dt <= 60) {
              $diffDown = $bytesOut - $prev['bytes_out'];
              $diffUp   = $bytesIn - $prev['bytes_in'];
              if ($diffDown > 0) {
                $downBps = ($diffDown * 8) / $dt;
              }
              if ($diffUp > 0) {
                $upBps = ($diffUp * 8) / $dt;
              }
            }
          }

          $newSnap[$snapKey] = array(
            'ts'        => $nowTs,
            'bytes_out' => $bytesOut,
            'bytes_in'  => $bytesIn
          );

          $totalDownBps += $downBps;
          $totalUpBps   += $upBps;

          $users[] = array(
            'id'           => $actId,
            'user'         => $user,
            'ip'           => $ip,
            'mac'          => $mac,
            'uptime'       => $uptime,
            'server'       => $server,
            'comment'      => $comm,
            'down_bps'     => $downBps,
            'up_bps'       => $upBps,
            'total_bps'    => ($downBps + $upBps),
            'max_down_bps' => $maxDownBps,
            'bytes_down'   => $bytesOut,
            'bytes_up'     => $bytesIn,
            'total_bytes'  => ($bytesOut + $bytesIn)
          );
        }
      }

      $_SESSION['topbw_snap'] = $newSnap;

      // Urutkan berdasarkan kecepatan real-time tertinggi, lalu total kuota terbesar
      usort($users, function($a, $b) {
        if ($b['total_bps'] != $a['total_bps']) {
          return ($b['total_bps'] > $a['total_bps']) ? 1 : -1;
        }
        if ($b['total_bytes'] != $a['total_bytes']) {
          return ($b['total_bytes'] > $a['total_bytes']) ? 1 : -1;
        }
        return 0;
      });

      echo json_encode(array(
        'status'         => 'success',
        'active_count'   => count($users),
        'total_down_bps' => $totalDownBps,
        'total_up_bps'   => $totalUpBps,
        'users'          => $users
      ));
      exit;
    }
  }
}
?>

<style>
  #mhm-topbw {
    color: #18181b;
  }
  #mhm-topbw .lx-shell {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }
  #mhm-topbw .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-topbw .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-topbw .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-topbw .lx-live-pill {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
    background: #dcfce7;
    color: #15803d;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-topbw .lx-live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #16a34a;
    animation: tbwPulse 1.5s infinite;
  }
  @keyframes tbwPulse {
    0%   { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.5); }
    70%  { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
    100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
  }
  #mhm-topbw .lx-body {
    padding: 20px 24px;
    background: #fafafa;
  }

  /* KPI Summary Cards */
  #mhm-topbw .lx-kpi-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 18px;
  }
  @media (max-width: 820px) {
    #mhm-topbw .lx-kpi-row {
      grid-template-columns: 1fr;
    }
  }
  #mhm-topbw .lx-kpi-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  #mhm-topbw .lx-kpi-label {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-topbw .lx-kpi-val {
    font-size: 20px;
    font-weight: 700;
    color: #09090b;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    letter-spacing: -0.02em;
  }
  #mhm-topbw .lx-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
  }
  .kpi-down { background: #eff6ff; color: #2563eb; }
  .kpi-up   { background: #f0fdf4; color: #16a34a; }
  .kpi-user { background: #f4f4f5; color: #18181b; }

  /* Toolbar Filter */
  #mhm-topbw .lx-toolbar {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-topbw .lx-search-wrap {
    position: relative;
    flex: 1;
    min-width: 220px;
  }
  #mhm-topbw .lx-search-wrap i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #a1a1aa;
    font-size: 13px;
  }
  #mhm-topbw .lx-input,
  #mhm-topbw .lx-select {
    height: 35px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #09090b !important;
    margin: 0 !important;
  }
  #mhm-topbw .lx-input {
    width: 100% !important;
    padding: 0 12px 0 32px !important;
  }
  #mhm-topbw .lx-select {
    padding: 0 10px !important;
    cursor: pointer;
  }
  #mhm-topbw .lx-btn {
    height: 35px;
    padding: 0 13px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  #mhm-topbw .lx-btn:hover { background: #f4f4f5; }

  /* Data Table */
  #mhm-topbw .lx-table-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow-x: auto;
  }
  #mhm-topbw table.lx-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
  }
  #mhm-topbw table.lx-table th {
    text-align: left;
    padding: 12px 14px;
    background: #fafafa;
    border-bottom: 1px solid #e4e4e7;
    color: #52525b;
    font-weight: 600;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
  }
  #mhm-topbw table.lx-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f4f4f5;
    vertical-align: middle;
  }
  #mhm-topbw table.lx-table tr:last-child td {
    border-bottom: none;
  }
  #mhm-topbw table.lx-table tr:hover td {
    background: #fafafa;
  }

  /* Rank Badges */
  .tbw-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 7px;
    font-weight: 700;
    font-size: 11.5px;
    background: #f4f4f5;
    color: #52525b;
  }
  .tbw-rank.r-1 { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
  .tbw-rank.r-2 { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
  .tbw-rank.r-3 { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }

  /* Speed Pills & Progress Bar */
  .tbw-speed-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 5px;
  }
  .tbw-pill-down {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 5px;
    background: #eff6ff;
    color: #1d4ed8;
  }
  .tbw-pill-up {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 5px;
    background: #f0fdf4;
    color: #15803d;
  }
  .tbw-bar-track {
    width: 140px;
    height: 5px;
    background: #f4f4f5;
    border-radius: 99px;
    overflow: hidden;
  }
  .tbw-bar-fill {
    height: 100%;
    border-radius: 99px;
    background: #2563eb;
    transition: width 0.35s ease;
  }
  .tbw-bar-fill.is-heavy {
    background: #dc2626;
  }

  /* Kick Button */
  .tbw-kick-btn {
    height: 30px;
    padding: 0 11px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  .tbw-kick-btn:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff;
  }

  #mhm-topbw .lx-footer {
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

  /* Modern Toast & Modal */
  #tbw-toast-container {
    position: fixed;
    top: 68px;
    right: 22px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
  }
  .tbw-toast {
    pointer-events: auto;
    min-width: 280px;
    max-width: 370px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-left: 4px solid #18181b;
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: 0 12px 28px -8px rgba(9, 9, 11, 0.18);
    display: flex;
    align-items: flex-start;
    gap: 10px;
    animation: tbwSlide 0.2s ease-out;
  }
  .tbw-toast.is-success { border-left-color: #16a34a; }
  .tbw-toast.is-error   { border-left-color: #dc2626; }
  @keyframes tbwSlide {
    from { opacity: 0; transform: translateX(16px); }
    to   { opacity: 1; transform: translateX(0); }
  }
  #tbw-modal-backdrop {
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
  #tbw-modal-backdrop.is-open { display: flex; }
  .tbw-modal-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 14px;
    width: 100%;
    max-width: 410px;
    padding: 22px;
    box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.25);
  }
</style>

<div id="tbw-toast-container"></div>

<!-- Modal Konfirmasi Kick User -->
<div id="tbw-modal-backdrop">
  <div class="tbw-modal-card" role="dialog" aria-modal="true">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
      <div style="width:38px; height:38px; border-radius:10px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:16px;">
        <i class="fa fa-ban"></i>
      </div>
      <h4 style="margin:0; font-size:15px; font-weight:600; color:#09090b;">Putuskan Koneksi User?</h4>
    </div>
    <p style="font-size:13px; color:#52525b; margin:0 0 12px 0;">User berikut akan langsung dikeluarkan dari jaringan Hotspot:</p>
    <div style="background:#fafafa; border:1px solid #e4e4e7; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12.5px;">
      <div style="font-weight:600; color:#09090b;" id="kick_user_label">-</div>
      <div style="font-family:monospace; color:#71717a; font-size:12px; margin-top:2px;" id="kick_ip_label">-</div>
    </div>
    <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#3f3f46; margin-bottom:18px; cursor:pointer;">
      <input type="checkbox" id="kick_clear_cookie" checked style="margin:0;">
      <span>Hapus cookie login juga (mencegah perangkat auto-login ulang)</span>
    </label>
    <div style="display:flex; justify-content:flex-end; gap:8px;">
      <button type="button" id="btn_kick_cancel" style="height:36px; padding:0 15px; border-radius:8px; border:1px solid #e4e4e7; background:#fff; font-size:12.5px; cursor:pointer;">Batal</button>
      <button type="button" id="btn_kick_confirm" style="height:36px; padding:0 15px; border-radius:8px; border:1px solid #dc2626; background:#dc2626; color:#fff; font-size:12.5px; font-weight:500; cursor:pointer;">Ya, Kick User</button>
    </div>
  </div>
</div>

<div id="mhm-topbw">
  <div class="lx-shell">
    <!-- Top Bar -->
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title">Top Bandwidth User (Real-Time)</h3>
        <span class="lx-live-pill"><span class="lx-live-dot"></span> Live Monitoring</span>
      </div>

      <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <span id="tbw_last_sync" style="font-size:11.5px; color:#71717a;">Memuat...</span>
        <select id="tbw_interval" class="lx-select" title="Interval Auto-Refresh">
          <option value="3000">Auto: 3 Detik</option>
          <option value="5000" selected>Auto: 5 Detik</option>
          <option value="10000">Auto: 10 Detik</option>
          <option value="0">Pause</option>
        </select>
        <button type="button" id="btn_tbw_refresh" class="lx-btn">
          <i class="fa fa-refresh"></i>
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Body -->
    <div class="lx-body">
      <!-- KPI Summary -->
      <div class="lx-kpi-row">
        <div class="lx-kpi-card">
          <div>
            <div class="lx-kpi-label">Total Download Real-Time</div>
            <div class="lx-kpi-val" id="kpi_total_down">0 bps</div>
          </div>
          <div class="lx-kpi-icon kpi-down"><i class="fa fa-arrow-down"></i></div>
        </div>
        <div class="lx-kpi-card">
          <div>
            <div class="lx-kpi-label">Total Upload Real-Time</div>
            <div class="lx-kpi-val" id="kpi_total_up">0 bps</div>
          </div>
          <div class="lx-kpi-icon kpi-up"><i class="fa fa-arrow-up"></i></div>
        </div>
        <div class="lx-kpi-card">
          <div>
            <div class="lx-kpi-label">Perangkat Hotspot Aktif</div>
            <div class="lx-kpi-val" id="kpi_active_users">0</div>
          </div>
          <div class="lx-kpi-icon kpi-user"><i class="fa fa-users"></i></div>
        </div>
      </div>

      <!-- Filter & Search -->
      <div class="lx-toolbar">
        <div class="lx-search-wrap">
          <i class="fa fa-search"></i>
          <input type="text" id="tbw_search" class="lx-input" placeholder="Cari username, IP address, MAC address, atau komentar...">
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
          <select id="tbw_filter_mode" class="lx-select">
            <option value="all">Semua User Aktif</option>
            <option value="streaming">Sedang Menyedot Traffic (&gt; 0 bps)</option>
            <option value="heavy_quota">Kuota Terpakai &gt; 500 MB</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="lx-table-card">
        <table class="lx-table">
          <thead>
            <tr>
              <th style="width:54px;">Rank</th>
              <th>User &amp; Identitas</th>
              <th>IP &amp; MAC Address</th>
              <th>Kecepatan Real-Time</th>
              <th>Kuota Terpakai</th>
              <th>Durasi Aktif</th>
              <th style="text-align:right;">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbw_tbody">
            <tr>
              <td colspan="7" style="text-align:center; padding:36px; color:#71717a;">
                <i class="fa fa-circle-o-notch fa-spin"></i> Mengambil data traffic user dari MikroTik...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Footer -->
    <div class="lx-footer">
      <div>Sumber data: <strong>/ip/hotspot/active</strong> &amp; <strong>/queue/simple</strong></div>
      <div>Diurutkan otomatis dari pemakai bandwidth terbesar</div>
    </div>
  </div>
</div>

<script>
(function() {
  var sessionName = "<?= htmlspecialchars($session); ?>";
  var ajaxUrl = "./?hotspot=top-bandwidth&session=" + encodeURIComponent(sessionName);
  var allUsers = [];
  var isFetching = false;
  var initialSampleDone = false;
  var refreshTimer = null;
  var kickTarget = { id: '', user: '', mac: '' };

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function(m) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];
    });
  }

  function formatBps(bps) {
    bps = Number(bps) || 0;
    if (bps <= 0) return '0 bps';
    if (bps < 1000) return bps.toFixed(0) + ' bps';
    if (bps < 1000000) return (bps / 1000).toFixed(1) + ' Kbps';
    if (bps < 1000000000) return (bps / 1000000).toFixed(2) + ' Mbps';
    return (bps / 1000000000).toFixed(2) + ' Gbps';
  }

  function formatBytes(bytes) {
    bytes = Number(bytes) || 0;
    if (bytes <= 0) return '0 B';
    var units = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(1024));
    if (i >= units.length) i = units.length - 1;
    return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 2) + ' ' + units[i];
  }

  function showToast(type, title, message) {
    var container = document.getElementById('tbw-toast-container');
    if (!container) return;
    var isSuccess = (type === 'success');
    var toast = document.createElement('div');
    toast.className = 'tbw-toast ' + (isSuccess ? 'is-success' : 'is-error');
    toast.innerHTML =
      '<div style="width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;background:' + (isSuccess ? '#dcfce7;color:#16a34a' : '#fee2e2;color:#dc2626') + ';">' +
        '<i class="fa ' + (isSuccess ? 'fa-check' : 'fa-times') + '"></i>' +
      '</div>' +
      '<div style="flex:1;">' +
        '<div style="font-size:12.5px;font-weight:600;color:#09090b;margin-bottom:2px;">' + escapeHtml(title) + '</div>' +
        '<div style="font-size:12px;color:#52525b;">' + escapeHtml(message) + '</div>' +
      '</div>';
    container.appendChild(toast);
    setTimeout(function() {
      toast.style.opacity = '0';
      setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 200);
    }, 3800);
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

  function renderTable() {
    var keyword = document.getElementById('tbw_search').value.toLowerCase().trim();
    var mode = document.getElementById('tbw_filter_mode').value;
    var tbody = document.getElementById('tbw_tbody');

    var filtered = allUsers.filter(function(u) {
      if (mode === 'streaming' && u.total_bps <= 0) return false;
      if (mode === 'heavy_quota' && u.total_bytes < 524288000) return false;
      if (keyword !== '') {
        var hay = (u.user + ' ' + u.ip + ' ' + u.mac + ' ' + u.comment + ' ' + u.server).toLowerCase();
        if (hay.indexOf(keyword) === -1) return false;
      }
      return true;
    });

    if (filtered.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="7" style="text-align:center; padding:36px; color:#71717a;">' +
          'Tidak ada user aktif yang sesuai dengan filter saat ini.' +
        '</td></tr>';
      return;
    }

    var topSpeed = filtered[0].total_bps > 0 ? filtered[0].total_bps : 1;
    var html = '';

    for (var i = 0; i < filtered.length; i++) {
      var u = filtered[i];
      var rankNum = i + 1;
      var rankClass = (rankNum <= 3) ? ('r-' + rankNum) : '';

      var pct = 0;
      if (u.max_down_bps > 0) {
        pct = Math.min(100, Math.round((u.down_bps / u.max_down_bps) * 100));
      } else if (u.total_bps > 0) {
        pct = Math.min(100, Math.round((u.total_bps / topSpeed) * 100));
      }
      var isHeavy = (pct >= 80 && u.total_bps > 0);

      html +=
        '<tr>' +
          '<td><span class="tbw-rank ' + rankClass + '">#' + rankNum + '</span></td>' +
          '<td>' +
            '<div style="font-weight:600; color:#09090b;">' + escapeHtml(u.user) + '</div>' +
            (u.comment ? '<div style="font-size:11.5px; color:#71717a;">' + escapeHtml(u.comment) + '</div>' : '<div style="font-size:11px; color:#a1a1aa;">Server: ' + escapeHtml(u.server) + '</div>') +
          '</td>' +
          '<td>' +
            '<div style="font-family:monospace; font-size:12px; color:#18181b;">' + escapeHtml(u.ip) + '</div>' +
            '<div style="font-family:monospace; font-size:11px; color:#71717a;">' + escapeHtml(u.mac) + '</div>' +
          '</td>' +
          '<td>' +
            '<div class="tbw-speed-row">' +
              '<span class="tbw-pill-down" title="Download Speed">&darr; ' + formatBps(u.down_bps) + '</span>' +
              '<span class="tbw-pill-up" title="Upload Speed">&uarr; ' + formatBps(u.up_bps) + '</span>' +
            '</div>' +
            '<div class="tbw-bar-track"><div class="tbw-bar-fill ' + (isHeavy ? 'is-heavy' : '') + '" style="width:' + pct + '%;"></div></div>' +
          '</td>' +
          '<td>' +
            '<div style="font-weight:600; color:#18181b;">' + formatBytes(u.total_bytes) + '</div>' +
            '<div style="font-size:11px; color:#71717a;">&darr; ' + formatBytes(u.bytes_down) + ' &bull; &uarr; ' + formatBytes(u.bytes_up) + '</div>' +
          '</td>' +
          '<td style="font-family:monospace; font-size:12px; color:#52525b;">' + escapeHtml(u.uptime) + '</td>' +
          '<td style="text-align:right;">' +
            '<button type="button" class="tbw-kick-btn" data-id="' + escapeHtml(u.id) + '" data-user="' + escapeHtml(u.user) + '" data-ip="' + escapeHtml(u.ip) + '" data-mac="' + escapeHtml(u.mac) + '">' +
              '<i class="fa fa-ban"></i> Kick' +
            '</button>' +
          '</td>' +
        '</tr>';
    }

    tbody.innerHTML = html;
  }

  function fetchTopBandwidth(manual) {
    if (isFetching) return;
    isFetching = true;

    var btnRef = document.getElementById('btn_tbw_refresh');
    if (manual && btnRef) {
      btnRef.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Memuat...</span>';
    }

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { ajax_topbw: 'list' },
      dataType: 'text',
      success: function(raw) {
        isFetching = false;
        if (btnRef) btnRef.innerHTML = '<i class="fa fa-refresh"></i><span>Refresh</span>';

        var res = parseJsonSafe(raw);
        if (!res || res.status !== 'success') return;

        allUsers = res.users || [];
        document.getElementById('kpi_total_down').textContent = formatBps(res.total_down_bps);
        document.getElementById('kpi_total_up').textContent = formatBps(res.total_up_bps);
        document.getElementById('kpi_active_users').textContent = res.active_count + ' User';

        var now = new Date();
        document.getElementById('tbw_last_sync').textContent = 'Update: ' + now.toLocaleTimeString('id-ID');

        renderTable();

        if (!initialSampleDone && res.active_count > 0 && res.total_down_bps === 0 && res.total_up_bps === 0) {
          initialSampleDone = true;
          setTimeout(function() {
            fetchTopBandwidth(false);
          }, 1200);
        } else {
          initialSampleDone = true;
        }
      },
      error: function() {
        isFetching = false;
        if (btnRef) btnRef.innerHTML = '<i class="fa fa-refresh"></i><span>Refresh</span>';
      }
    });
  }

  function resetIntervalTimer() {
    if (refreshTimer) clearInterval(refreshTimer);
    var ms = parseInt(document.getElementById('tbw_interval').value, 10);
    if (ms > 0) {
      refreshTimer = setInterval(function() {
        fetchTopBandwidth(false);
      }, ms);
    }
  }

  // Event Kick Modal
  document.getElementById('tbw_tbody').addEventListener('click', function(e) {
    var btn = e.target.closest('.tbw-kick-btn');
    if (!btn) return;
    kickTarget.id   = btn.getAttribute('data-id');
    kickTarget.user = btn.getAttribute('data-user');
    kickTarget.ip   = btn.getAttribute('data-ip');
    kickTarget.mac  = btn.getAttribute('data-mac');

    document.getElementById('kick_user_label').textContent = kickTarget.user;
    document.getElementById('kick_ip_label').textContent = kickTarget.ip + ' (' + kickTarget.mac + ')';
    document.getElementById('tbw-modal-backdrop').classList.add('is-open');
  });

  function closeKickModal() {
    document.getElementById('tbw-modal-backdrop').classList.remove('is-open');
  }

  document.getElementById('btn_kick_cancel').addEventListener('click', closeKickModal);
  document.getElementById('tbw-modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) closeKickModal();
  });

  document.getElementById('btn_kick_confirm').addEventListener('click', function() {
    if (!kickTarget.id) return;
    var btn = this;
    var clearCook = document.getElementById('kick_clear_cookie').checked ? '1' : '0';
    btn.disabled = true;
    btn.textContent = 'Memutuskan...';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: {
        ajax_topbw: 'kick',
        id: kickTarget.id,
        user: kickTarget.user,
        mac: kickTarget.mac,
        clear_cookie: clearCook
      },
      dataType: 'text',
      success: function(raw) {
        btn.disabled = false;
        btn.textContent = 'Ya, Kick User';
        closeKickModal();

        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          showToast('success', 'Sesi Diputuskan', res.message);
        } else {
          showToast('error', 'Gagal Memutuskan', (res && res.message) ? res.message : 'Terjadi kesalahan.');
        }
        fetchTopBandwidth(false);
      },
      error: function() {
        btn.disabled = false;
        btn.textContent = 'Ya, Kick User';
        closeKickModal();
        showToast('error', 'Koneksi Bermasalah', 'Gagal mengirim perintah kick ke MikroTik.');
      }
    });
  });

  document.getElementById('tbw_search').addEventListener('input', renderTable);
  document.getElementById('tbw_filter_mode').addEventListener('change', renderTable);
  document.getElementById('tbw_interval').addEventListener('change', resetIntervalTimer);
  document.getElementById('btn_tbw_refresh').addEventListener('click', function() {
    fetchTopBandwidth(true);
  });

  fetchTopBandwidth(false);
  resetIntervalTimer();
})();
</script>