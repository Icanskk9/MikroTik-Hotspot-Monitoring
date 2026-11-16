<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

if (!function_exists('parseSecretNoteOnt')) {
  function parseSecretNoteOnt($comment) {
    $origProf = "";
    $cleanText = trim((string)$comment);

    if (preg_match('/\[EXP:(\d{4}-\d{2}-\d{2})(?:\s+(\d{2}:\d{2}))?\]/', $cleanText, $m)) {
      $cleanText = trim(str_replace($m[0], '', $cleanText));
    }
    if (preg_match('/\[PROF:([^\]]+)\]/', $cleanText, $mp)) {
      $origProf = trim($mp[1]);
      $cleanText = trim(str_replace($mp[0], '', $cleanText));
    }

    return array(
      "orig_prof" => $origProf,
      "note" => $cleanText,
    );
  }
}

$getsecrets  = $API->comm("/ppp/secret/print");
$getactive   = $API->comm("/ppp/active/print");
$getprofiles = $API->comm("/ppp/profile/print");

$activeMap = array();
for ($a = 0; $a < count($getactive); $a++) {
  $aname = isset($getactive[$a]['name']) ? $getactive[$a]['name'] : '';
  if ($aname !== '') {
    $activeMap[$aname] = array(
      "address" => isset($getactive[$a]['address']) ? $getactive[$a]['address'] : '',
      "uptime" => isset($getactive[$a]['uptime']) ? $getactive[$a]['uptime'] : '',
      "caller_id" => isset($getactive[$a]['caller-id']) ? $getactive[$a]['caller-id'] : '',
    );
  }
}

$ontItems = array();
$totalOnline = 0;
$totalOffline = 0;
$totalWithIp = 0;

for ($i = 0; $i < count($getsecrets); $i++) {
  $sec = $getsecrets[$i];
  $sname = isset($sec['name']) ? $sec['name'] : '';
  $sprof = isset($sec['profile']) ? $sec['profile'] : 'default';
  $sraddr = isset($sec['remote-address']) ? trim($sec['remote-address']) : '';
  $scaller = isset($sec['caller-id']) ? trim($sec['caller-id']) : '';
  $sdis = (isset($sec['disabled']) && $sec['disabled'] == 'true');
  $meta = parseSecretNoteOnt(isset($sec['comment']) ? $sec['comment'] : '');

  $isOnline = isset($activeMap[$sname]);
  $isIsolir = ($sprof === 'ISOLIR');

  if ($isOnline) {
    $totalOnline++;
  } else {
    $totalOffline++;
  }

  $activeIp = $isOnline ? $activeMap[$sname]['address'] : '';
  $targetIp = ($activeIp !== '') ? $activeIp : $sraddr;
  $ipMode = ($sraddr !== '') ? 'Statis' : (($activeIp !== '') ? 'Pool' : '');

  if ($targetIp !== '') {
    $totalWithIp++;
  }

  $macAddr = ($isOnline && $activeMap[$sname]['caller_id'] !== '')
    ? $activeMap[$sname]['caller_id']
    : $scaller;

  $ontItems[] = array(
    "username" => $sname,
    "location" => $meta['note'] !== '' ? $meta['note'] : $sname,
    "has_custom_loc" => ($meta['note'] !== ''),
    "profile" => $sprof,
    "orig_prof" => $meta['orig_prof'],
    "is_online" => $isOnline,
    "is_isolir" => $isIsolir,
    "is_disabled" => $sdis,
    "uptime" => $isOnline ? $activeMap[$sname]['uptime'] : '',
    "target_ip" => $targetIp,
    "ip_mode" => $ipMode,
    "mac" => $macAddr,
  );
}
?>

<style>
  #mhm-pppont {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-pppont .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-pppont .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-pppont .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-pppont .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #52525b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 9px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-pppont .lx-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Buttons */
  #mhm-pppont .lx-btn {
    height: 34px;
    padding: 0 13px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12.5px;
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
  #mhm-pppont .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
  }
  #mhm-pppont .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-pppont .lx-btn.is-primary:hover {
    background: #27272a;
  }
  #mhm-pppont .lx-btn.is-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-pppont .lx-btn.is-live {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d !important;
  }
  #mhm-pppont .lx-live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #a1a1aa;
    display: inline-block;
  }
  #mhm-pppont .lx-btn.is-live .lx-live-dot {
    background: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
    animation: ontPulse 1.5s infinite;
  }

  /* Summary KPI Strip */
  #mhm-pppont .lx-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    padding: 18px 24px 0 24px;
    background: #fafafa;
  }
  @media (max-width: 900px) {
    #mhm-pppont .lx-stats-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 480px) {
    #mhm-pppont .lx-stats-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-pppont .lx-stat-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-pppont .lx-stat-label {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-pppont .lx-stat-val {
    font-size: 20px;
    font-weight: 700;
    color: #09090b;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
  }
  #mhm-pppont .lx-stat-sub {
    font-size: 11px;
    color: #71717a;
    margin-top: 3px;
  }
  #mhm-pppont .lx-stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }

  /* Main Body */
  #mhm-pppont .lx-body {
    padding: 16px 24px 22px 24px;
    background: #fafafa;
  }

  /* Mass Ping Progress Banner */
  #mhm-pppont .lx-progress-box {
    margin-bottom: 14px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 12px 16px;
  }
  #mhm-pppont .lx-progress-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 500;
    color: #18181b;
    margin-bottom: 8px;
  }
  #mhm-pppont .lx-progress-track {
    width: 100%;
    height: 7px;
    background: #f4f4f5;
    border-radius: 99px;
    overflow: hidden;
  }
  #mhm-pppont .lx-progress-fill {
    height: 100%;
    width: 0%;
    background: #18181b;
    border-radius: 99px;
    transition: width 0.2s ease;
  }

  /* Filter & View Toolbar */
  #mhm-pppont .lx-toolbar {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 11px 14px;
    margin-bottom: 16px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-pppont .lx-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f4f4f5;
    padding: 3px;
    border-radius: 8px;
    flex-wrap: wrap;
  }
  #mhm-pppont .lx-tab {
    border: none;
    background: transparent;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #52525b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
  }
  #mhm-pppont .lx-tab:hover {
    color: #09090b;
  }
  #mhm-pppont .lx-tab.is-active {
    background: #ffffff;
    color: #09090b;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.06);
  }
  #mhm-pppont .lx-tab-num {
    font-size: 10.5px;
    padding: 0 5px;
    border-radius: 4px;
    background: #e4e4e7;
    color: #3f3f46;
    font-variant-numeric: tabular-nums;
  }
  #mhm-pppont .lx-tab.is-active .lx-tab-num {
    background: #18181b;
    color: #ffffff;
  }

  #mhm-pppont .lx-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  #mhm-pppont .lx-select {
    height: 33px !important;
    padding: 0 10px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    color: #18181b !important;
    box-shadow: none !important;
    margin: 0 !important;
    cursor: pointer;
  }
  #mhm-pppont .lx-search {
    position: relative;
    width: 220px;
    max-width: 100%;
  }
  #mhm-pppont .lx-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #a1a1aa;
    pointer-events: none;
  }
  #mhm-pppont #ontSearchInput {
    width: 100% !important;
    height: 33px !important;
    padding: 0 12px 0 32px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #09090b !important;
    box-shadow: none !important;
    margin: 0 !important;
  }
  #mhm-pppont #ontSearchInput:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }

  /* View Mode Switcher */
  #mhm-pppont .lx-view-switch {
    display: inline-flex;
    background: #f4f4f5;
    padding: 3px;
    border-radius: 8px;
  }
  #mhm-pppont .lx-view-btn {
    width: 28px;
    height: 27px;
    border: none;
    background: transparent;
    border-radius: 6px;
    color: #71717a;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
  }
  #mhm-pppont .lx-view-btn.is-active {
    background: #ffffff;
    color: #09090b;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.06);
  }

  /* ONT Cards Grid */
  #mhm-pppont .lx-ont-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 14px;
  }
  #mhm-pppont .lx-ont-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 15px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  #mhm-pppont .lx-ont-card:hover {
    border-color: #d4d4d8;
    box-shadow: 0 4px 12px -4px rgba(9, 9, 11, 0.06);
  }
  #mhm-pppont .lx-ont-card.is-online {
    border-top: 3px solid #10b981;
  }
  #mhm-pppont .lx-ont-card.is-offline {
    border-top: 3px solid #e4e4e7;
  }
  #mhm-pppont .lx-ont-card.is-isolir {
    border-top: 3px solid #ef4444;
    background: #fffafa;
  }

  #mhm-pppont .lx-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
  }
  #mhm-pppont .lx-card-loc {
    font-size: 14px;
    font-weight: 600;
    color: #09090b;
    line-height: 1.3;
    word-break: break-word;
  }
  #mhm-pppont .lx-card-user {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    color: #71717a;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  #mhm-pppont .lx-state-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
  }
  #mhm-pppont .lx-state-badge.is-online {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }
  #mhm-pppont .lx-state-badge.is-offline {
    background: #f4f4f5;
    color: #71717a;
    border: 1px solid #e4e4e7;
  }
  #mhm-pppont .lx-state-badge.is-isolir {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
  }
  #mhm-pppont .lx-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }

  #mhm-pppont .lx-card-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding: 9px 10px;
    background: #fafafa;
    border: 1px solid #f4f4f5;
    border-radius: 8px;
    font-size: 11.5px;
  }
  #mhm-pppont .lx-meta-lbl {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #71717a;
    margin-bottom: 2px;
  }
  #mhm-pppont .lx-meta-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
    color: #18181b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Ping Result Strip inside Card */
  #mhm-pppont .lx-ping-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-top: 2px;
  }
  #mhm-pppont .lx-ping-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
  }
  #mhm-pppont .lx-ping-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    width: fit-content;
  }
  #mhm-pppont .lx-ping-pill.is-idle {
    background: #f4f4f5;
    color: #71717a;
    border: 1px solid #e4e4e7;
    font-family: inherit;
    font-weight: 500;
  }
  #mhm-pppont .lx-ping-pill.is-good {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }
  #mhm-pppont .lx-ping-pill.is-warn {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
  }
  #mhm-pppont .lx-ping-pill.is-loss,
  #mhm-pppont .lx-ping-pill.is-rto {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
  }
  #mhm-pppont .lx-ping-note {
    font-size: 10.5px;
    color: #71717a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  #mhm-pppont .lx-ping-btn {
    height: 30px;
    padding: 0 11px;
    border-radius: 7px;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #18181b;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
  }
  #mhm-pppont .lx-ping-btn:hover:not(:disabled) {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }
  #mhm-pppont .lx-ping-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Compact Table Mode */
  #mhm-pppont .lx-table-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow: hidden;
  }
  #mhm-pppont .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
    max-height: 60vh;
  }
  #mhm-pppont .lx-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    white-space: nowrap;
    text-align: left;
    background: #ffffff !important;
  }
  #mhm-pppont .lx-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #fafafa !important;
    color: #71717a !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 11px 16px !important;
    border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppont .lx-table tbody td {
    padding: 10px 16px !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 12.5px !important;
    color: #27272a !important;
    vertical-align: middle;
  }
  #mhm-pppont .lx-table tbody tr:hover {
    background-color: #fafafa !important;
  }

  /* Footer */
  #mhm-pppont .lx-footer {
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

  @keyframes ontPulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
  }
</style>

<div id="mhm-pppont">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">ONT Status &amp; Ping Latency Monitor</h3>
      <span class="lx-count" id="ontTotalBadge"><?= count($ontItems); ?> titik ONT</span>
    </div>

    <div class="lx-actions">
      <button type="button" id="btnAutoOnt" onclick="toggleOntAutoRefresh();" class="lx-btn" title="Refresh otomatis status Online/Offline setiap 15 detik">
        <span class="lx-live-dot"></span>
        <span id="autoOntLabel">Live: Off</span>
      </button>

      <button type="button" id="btnMassPing" onclick="startMassPing();" class="lx-btn is-primary" title="Uji ping ke seluruh ONT yang memiliki alamat IP">
        <i class="fa fa-signal" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Ping Semua ONT</span>
      </button>

      <button type="button" id="btnStopMassPing" onclick="stopMassPing();" class="lx-btn is-danger" style="display:none;">
        <i class="fa fa-stop" style="font-size:11px;"></i>
        <span>Hentikan</span>
      </button>

      <button type="button" onclick="refreshOntStatus(false);" class="lx-btn" title="Muat Ulang Status ONT">
        <i class="fa fa-refresh" id="ontRefreshIcon" style="font-size:11.5px; color:#71717a;"></i>
      </button>
    </div>
  </div>

  <!-- 4 Summary Metric Cards -->
  <div class="lx-stats-grid">
    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Total Titik / ONT</div>
        <div class="lx-stat-val" id="statOntTotal"><?= count($ontItems); ?></div>
        <div class="lx-stat-sub" id="statOntWithIp"><?= $totalWithIp; ?> memiliki IP aktif/statis</div>
      </div>
      <div class="lx-stat-icon" style="background:#f4f4f5; color:#18181b;">
        <i class="fa fa-sitemap"></i>
      </div>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">ONT Online (PPPoE Aktif)</div>
        <div class="lx-stat-val" id="statOntOnline" style="color:#15803d;"><?= $totalOnline; ?></div>
        <div class="lx-stat-sub">Terhubung ke server PPPoE</div>
      </div>
      <div class="lx-stat-icon" style="background:#f0fdf4; color:#16a34a;">
        <i class="fa fa-check-circle"></i>
      </div>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">ONT Offline</div>
        <div class="lx-stat-val" id="statOntOffline" style="color:<?= $totalOffline > 0 ? '#dc2626' : '#09090b'; ?>;"><?= $totalOffline; ?></div>
        <div class="lx-stat-sub">Tidak ada sesi aktif</div>
      </div>
      <div class="lx-stat-icon" style="background:#fef2f2; color:#dc2626;">
        <i class="fa fa-power-off"></i>
      </div>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Hasil Uji Ping ONT</div>
        <div class="lx-stat-val" id="statPingSummary" style="font-size:16px; padding-top:3px;">Belum diuji</div>
        <div class="lx-stat-sub" id="statPingSub">Klik Ping satuan atau massal</div>
      </div>
      <div class="lx-stat-icon" style="background:#f0f9ff; color:#0284c7;">
        <i class="fa fa-heartbeat"></i>
      </div>
    </div>
  </div>

  <div class="lx-body">
    <!-- Mass Ping Progress Bar -->
    <div id="massPingProgressBox" class="lx-progress-box" style="display:none;">
      <div class="lx-progress-top">
        <span id="massPingStatusText"><i class="fa fa-circle-o-notch fa-spin"></i> Menguji konektivitas ONT...</span>
        <span id="massPingCounterText" style="font-family:monospace;">0 / 0</span>
      </div>
      <div class="lx-progress-track">
        <div id="massPingProgressFill" class="lx-progress-fill"></div>
      </div>
    </div>

    <!-- Filter & View Toolbar -->
    <div class="lx-toolbar">
      <div class="lx-tabs">
        <button type="button" class="lx-tab is-active" onclick="setOntFilter('all', this);">
          <span>Semua</span>
          <span class="lx-tab-num" id="tabOntAll"><?= count($ontItems); ?></span>
        </button>
        <button type="button" class="lx-tab" onclick="setOntFilter('online', this);">
          <span>Online</span>
          <span class="lx-tab-num" id="tabOntOnline"><?= $totalOnline; ?></span>
        </button>
        <button type="button" class="lx-tab" onclick="setOntFilter('offline', this);">
          <span>Offline</span>
          <span class="lx-tab-num" id="tabOntOffline"><?= $totalOffline; ?></span>
        </button>
        <button type="button" class="lx-tab" onclick="setOntFilter('issue', this);">
          <span>Latency / RTO</span>
          <span class="lx-tab-num" id="tabOntIssue">0</span>
        </button>
      </div>

      <div class="lx-toolbar-right">
        <select id="ontProfileFilter" class="lx-select" onchange="renderOntView();">
          <option value="all">Semua Profil</option>
          <?php
          for ($p = 0; $p < count($getprofiles); $p++) {
            $pname = $getprofiles[$p]['name'];
            echo '<option value="' . htmlspecialchars($pname, ENT_QUOTES) . '">' . htmlspecialchars($pname) . '</option>';
          }
          ?>
        </select>

        <div class="lx-search">
          <i class="fa fa-search"></i>
          <input id="ontSearchInput" type="text" class="form-control" placeholder="Cari ruangan, user, IP ONT..." onkeyup="renderOntView();">
        </div>

        <div class="lx-view-switch" title="Ganti Tampilan Kartu / Tabel">
          <button type="button" id="btnViewCard" class="lx-view-btn is-active" onclick="setOntViewMode('card');" title="Tampilan Kartu Ruangan">
            <i class="fa fa-th-large"></i>
          </button>
          <button type="button" id="btnViewTable" class="lx-view-btn" onclick="setOntViewMode('table');" title="Tampilan Tabel Ringkas">
            <i class="fa fa-list"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Card Grid Container -->
    <div id="ontCardGrid" class="lx-ont-grid"></div>

    <!-- Table View Container -->
    <div id="ontTableWrap" class="lx-table-card" style="display:none;">
      <div class="lx-table-wrap">
        <table class="table lx-table">
          <thead>
            <tr>
              <th>Ruangan / Lokasi</th>
              <th>Username</th>
              <th>Status</th>
              <th>Uptime</th>
              <th>IP ONT</th>
              <th>MAC / Caller-ID</th>
              <th>Hasil Ping &amp; Latency</th>
              <th style="text-align:center; width:90px;">Uji</th>
            </tr>
          </thead>
          <tbody id="ontTableBody"></tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div id="ontEmptyState" style="display:none; background:#ffffff; border:1px solid #e4e4e7; border-radius:10px; padding:40px 20px; text-align:center; color:#71717a; font-size:13px;">
      Tidak ada titik ONT atau pelanggan yang sesuai dengan filter saat ini.
    </div>
  </div>

  <div class="lx-footer">
    <div>Panduan Latency FTTH Lokal: <strong>&le; 15 ms</strong> (Normal) &bull; <strong>&gt; 15 ms</strong> (Cek Redaman / Trafik PON) &bull; <strong>RTO / Loss</strong> (Cek Konektor / Adaptor ONT)</div>
    <div id="ontLastSyncLabel">RouterOS v6 &amp; v7 Compatible</div>
  </div>
</div>

<script>
  var ontSession = <?= json_encode($session); ?>;
  var ontList = <?= json_encode($ontItems); ?>;
  var pingCache = {};      // key: username -> { loading, ok, avg_ms, min_ms, max_ms, loss, health, note }
  var activeOntFilter = 'all';
  var activeViewMode = 'card';
  var autoOntTimer = null;
  var massPingAbort = false;
  var massPingRunning = false;

  function getFilteredOnts() {
    var profFilter = document.getElementById('ontProfileFilter').value;
    var q = (document.getElementById('ontSearchInput').value || '').toLowerCase().trim();
    var filtered = [];

    for (var i = 0; i < ontList.length; i++) {
      var item = ontList[i];
      var pData = pingCache[item.username];
      var hasIssue = (pData && !pData.loading && (pData.health === 'warn' || pData.health === 'loss' || pData.health === 'rto'));

      if (activeOntFilter === 'online' && !item.is_online) continue;
      if (activeOntFilter === 'offline' && item.is_online) continue;
      if (activeOntFilter === 'issue' && !hasIssue) continue;
      if (profFilter !== 'all' && item.profile !== profFilter) continue;

      if (q !== '') {
        var hay = (item.location + ' ' + item.username + ' ' + item.profile + ' ' + item.target_ip + ' ' + item.mac).toLowerCase();
        if (hay.indexOf(q) === -1) continue;
      }

      filtered.push(item);
    }
    return filtered;
  }

  function updateSummaryCounters() {
    var total = ontList.length;
    var online = 0;
    var offline = 0;
    var withIp = 0;
    var pingGood = 0;
    var pingIssue = 0;

    for (var i = 0; i < ontList.length; i++) {
      var it = ontList[i];
      if (it.is_online) online++; else offline++;
      if (it.target_ip) withIp++;

      var p = pingCache[it.username];
      if (p && !p.loading && p.ok) {
        if (p.health === 'good') pingGood++;
        else pingIssue++;
      }
    }

    document.getElementById('ontTotalBadge').textContent = total + ' titik ONT';
    document.getElementById('statOntTotal').textContent = total;
    document.getElementById('statOntWithIp').textContent = withIp + ' memiliki IP aktif/statis';
    document.getElementById('statOntOnline').textContent = online;
    document.getElementById('statOntOffline').textContent = offline;
    document.getElementById('statOntOffline').style.color = offline > 0 ? '#dc2626' : '#09090b';

    document.getElementById('tabOntAll').textContent = total;
    document.getElementById('tabOntOnline').textContent = online;
    document.getElementById('tabOntOffline').textContent = offline;
    document.getElementById('tabOntIssue').textContent = pingIssue;

    var tested = pingGood + pingIssue;
    if (tested === 0) {
      document.getElementById('statPingSummary').textContent = 'Belum diuji';
      document.getElementById('statPingSub').textContent = 'Klik Ping satuan atau massal';
    } else {
      document.getElementById('statPingSummary').innerHTML =
        '<span style="color:#15803d;">' + pingGood + ' Sehat</span>' +
        (pingIssue > 0 ? ' &bull; <span style="color:#dc2626;">' + pingIssue + ' Kendala</span>' : '');
      document.getElementById('statPingSub').textContent = tested + ' dari ' + withIp + ' ONT ber-IP telah diuji';
    }
  }

  function formatPingBadgeHtml(item) {
    if (!item.target_ip) {
      return {
        pill: '<span class="lx-ping-pill is-idle">Tanpa IP Statis</span>',
        note: 'Set Remote Address di PPP Secrets untuk ping saat offline'
      };
    }

    var p = pingCache[item.username];
    if (!p) {
      return {
        pill: '<span class="lx-ping-pill is-idle">Belum diuji ping</span>',
        note: 'Siap uji ping ke ' + escapeHtml(item.target_ip)
      };
    }

    if (p.loading) {
      return {
        pill: '<span class="lx-ping-pill is-idle"><i class="fa fa-spinner fa-spin"></i> Menguji ' + escapeHtml(item.target_ip) + '...</span>',
        note: 'Mengirim paket ICMP via RouterOS...'
      };
    }

    if (!p.ok) {
      return {
        pill: '<span class="lx-ping-pill is-rto">Error API</span>',
        note: escapeHtml(p.error || 'Gagal menjalankan ping')
      };
    }

    if (p.health === 'rto') {
      return {
        pill: '<span class="lx-ping-pill is-rto"><i class="fa fa-times-circle"></i> RTO (100% Loss)</span>',
        note: escapeHtml(p.note)
      };
    }

    var cls = 'is-good';
    var icon = 'fa-check-circle';
    if (p.health === 'warn') {
      cls = 'is-warn';
      icon = 'fa-exclamation-triangle';
    } else if (p.health === 'loss') {
      cls = 'is-loss';
      icon = 'fa-exclamation-circle';
    }

    var rttText = (p.avg_ms !== null) ? (p.avg_ms + ' ms') : '-';
    var detailText = 'Loss ' + p.loss + '%';
    if (p.min_ms !== null && p.max_ms !== null) {
      detailText += ' (min ' + p.min_ms + ' / max ' + p.max_ms + ' ms)';
    }

    return {
      pill: '<span class="lx-ping-pill ' + cls + '"><i class="fa ' + icon + '"></i> ' + rttText + ' &bull; Loss ' + p.loss + '%</span>',
      note: escapeHtml(detailText + ' — ' + p.note)
    };
  }

  function renderOntView() {
    updateSummaryCounters();
    var list = getFilteredOnts();

    var cardGrid = document.getElementById('ontCardGrid');
    var tableWrap = document.getElementById('ontTableWrap');
    var emptyBox = document.getElementById('ontEmptyState');

    if (list.length === 0) {
      cardGrid.style.display = 'none';
      tableWrap.style.display = 'none';
      emptyBox.style.display = '';
      return;
    }

    emptyBox.style.display = 'none';

    if (activeViewMode === 'card') {
      tableWrap.style.display = 'none';
      cardGrid.style.display = 'grid';

      var cardsHtml = '';
      for (var i = 0; i < list.length; i++) {
        var it = list[i];
        var cardClass = it.is_isolir ? 'is-isolir' : (it.is_online ? 'is-online' : 'is-offline');
        var badgeClass = it.is_isolir ? 'is-isolir' : (it.is_online ? 'is-online' : 'is-offline');
        var stateLabel = it.is_isolir ? 'ISOLIR' : (it.is_online ? 'ONLINE' : (it.is_disabled ? 'DISABLED' : 'OFFLINE'));

        var pingUi = formatPingBadgeHtml(it);
        var pState = pingCache[it.username];
        var isPinging = (pState && pState.loading);
        var canPing = (it.target_ip !== '' && !isPinging);

        var ipLabel = it.target_ip
          ? (escapeHtml(it.target_ip) + (it.ip_mode ? ' <span style="font-weight:400; color:#71717a;">(' + it.ip_mode + ')</span>' : ''))
          : '<span style="color:#a1a1aa; font-weight:400;">Belum diatur</span>';

        var rightMetaLabel = it.is_online ? 'Uptime Aktif' : 'MAC / Caller-ID';
        var rightMetaVal = it.is_online
          ? escapeHtml(it.uptime || 'Baru terhubung')
          : (it.mac ? escapeHtml(it.mac) : '<span style="color:#a1a1aa; font-weight:400;">&mdash;</span>');

        cardsHtml += '<div class="lx-ont-card ' + cardClass + '">' +
          '<div>' +
            '<div class="lx-card-top">' +
              '<div>' +
                '<div class="lx-card-loc">' + escapeHtml(it.location) + '</div>' +
                '<div class="lx-card-user">' +
                  '<span><i class="fa fa-user"></i> ' + escapeHtml(it.username) + '</span>' +
                  '<span>&bull;</span>' +
                  '<span>' + escapeHtml(it.profile) + '</span>' +
                '</div>' +
              '</div>' +
              '<span class="lx-state-badge ' + badgeClass + '"><span class="lx-dot"></span> ' + stateLabel + '</span>' +
            '</div>' +
          '</div>' +

          '<div class="lx-card-meta">' +
            '<div>' +
              '<div class="lx-meta-lbl">Alamat IP ONT</div>' +
              '<div class="lx-meta-val">' + ipLabel + '</div>' +
            '</div>' +
            '<div>' +
              '<div class="lx-meta-lbl">' + rightMetaLabel + '</div>' +
              '<div class="lx-meta-val">' + rightMetaVal + '</div>' +
            '</div>' +
          '</div>' +

          '<div class="lx-ping-bar">' +
            '<div class="lx-ping-info">' +
              pingUi.pill +
              '<div class="lx-ping-note" title="' + pingUi.note + '">' + pingUi.note + '</div>' +
            '</div>' +
            '<button type="button" class="lx-ping-btn" ' + (canPing ? '' : 'disabled') + ' onclick="pingSingleOnt(\'' + escapeAttr(it.username) + '\');">' +
              '<i class="fa ' + (isPinging ? 'fa-spinner fa-spin' : 'fa-exchange') + '"></i>' +
              '<span>Ping</span>' +
            '</button>' +
          '</div>' +
        '</div>';
      }
      cardGrid.innerHTML = cardsHtml;
    } else {
      cardGrid.style.display = 'none';
      tableWrap.style.display = '';

      var rowsHtml = '';
      for (var j = 0; j < list.length; j++) {
        var r = list[j];
        var bClass = r.is_isolir ? 'is-isolir' : (r.is_online ? 'is-online' : 'is-offline');
        var sLabel = r.is_isolir ? 'ISOLIR' : (r.is_online ? 'ONLINE' : (r.is_disabled ? 'DISABLED' : 'OFFLINE'));
        var pUi = formatPingBadgeHtml(r);
        var pSt = pingCache[r.username];
        var pBusy = (pSt && pSt.loading);
        var pAllowed = (r.target_ip !== '' && !pBusy);

        rowsHtml += '<tr>' +
          '<td><strong>' + escapeHtml(r.location) + '</strong></td>' +
          '<td style="font-family:monospace;">' + escapeHtml(r.username) + '</td>' +
          '<td><span class="lx-state-badge ' + bClass + '"><span class="lx-dot"></span> ' + sLabel + '</span></td>' +
          '<td style="font-family:monospace;">' + (r.is_online ? escapeHtml(r.uptime || '-') : '<span style="color:#a1a1aa;">&mdash;</span>') + '</td>' +
          '<td style="font-family:monospace; font-weight:600;">' + (r.target_ip ? escapeHtml(r.target_ip) : '<span style="color:#a1a1aa; font-weight:400;">&mdash;</span>') + '</td>' +
          '<td style="font-family:monospace;">' + (r.mac ? escapeHtml(r.mac) : '<span style="color:#a1a1aa;">&mdash;</span>') + '</td>' +
          '<td>' + pUi.pill + '</td>' +
          '<td style="text-align:center;">' +
            '<button type="button" class="lx-ping-btn" ' + (pAllowed ? '' : 'disabled') + ' onclick="pingSingleOnt(\'' + escapeAttr(r.username) + '\');">' +
              '<i class="fa ' + (pBusy ? 'fa-spinner fa-spin' : 'fa-exchange') + '"></i>' +
              '<span>Ping</span>' +
            '</button>' +
          '</td>' +
        '</tr>';
      }
      document.getElementById('ontTableBody').innerHTML = rowsHtml;
    }
  }

  function findOntByUsername(uname) {
    for (var i = 0; i < ontList.length; i++) {
      if (ontList[i].username === uname) return ontList[i];
    }
    return null;
  }

  function pingSingleOnt(uname, callback) {
    var item = findOntByUsername(uname);
    if (!item || !item.target_ip) {
      if (callback) callback();
      return;
    }

    pingCache[uname] = { loading: true };
    renderOntView();

    var xhr = new XMLHttpRequest();
    var url = './ppp/pppont_ajax.php?action=ping&session=' + encodeURIComponent(ontSession) +
              '&ip=' + encodeURIComponent(item.target_ip) + '&count=3&_t=' + Date.now();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            res.loading = false;
            pingCache[uname] = res;
          } catch (e) {
            pingCache[uname] = { loading: false, ok: false, error: 'Respons JSON tidak valid' };
          }
        } else {
          pingCache[uname] = { loading: false, ok: false, error: 'HTTP Error ' + xhr.status };
        }
        renderOntView();
        if (callback) callback();
      }
    };
    xhr.send();
  }

  function startMassPing() {
    if (massPingRunning) return;

    var targets = [];
    var currentVisible = getFilteredOnts();
    for (var i = 0; i < currentVisible.length; i++) {
      if (currentVisible[i].target_ip) {
        targets.push(currentVisible[i].username);
      }
    }

    if (targets.length === 0) {
      alert('Tidak ada ONT dengan alamat IP valid pada filter saat ini.');
      return;
    }

    massPingRunning = true;
    massPingAbort = false;

    document.getElementById('btnMassPing').style.display = 'none';
    document.getElementById('btnStopMassPing').style.display = '';
    document.getElementById('massPingProgressBox').style.display = '';

    var total = targets.length;
    var completed = 0;
    var cursor = 0;
    var concurrency = 3; // Jalankan 3 worker paralel agar ringan di CPU MikroTik

    function updateProgressUi(currentUname) {
      var pct = Math.round((completed / total) * 100);
      document.getElementById('massPingProgressFill').style.width = pct + '%';
      document.getElementById('massPingCounterText').textContent = completed + ' / ' + total + ' (' + pct + '%)';
      if (currentUname) {
        document.getElementById('massPingStatusText').innerHTML =
          '<i class="fa fa-circle-o-notch fa-spin"></i> Menguji ONT: <strong>' + escapeHtml(currentUname) + '</strong>';
      }
    }

    function finishMassPing() {
      massPingRunning = false;
      document.getElementById('btnMassPing').style.display = '';
      document.getElementById('btnStopMassPing').style.display = 'none';
      document.getElementById('massPingStatusText').innerHTML = massPingAbort
        ? '<i class="fa fa-info-circle"></i> Uji ping massal dihentikan.'
        : '<i class="fa fa-check-circle" style="color:#16a34a;"></i> Uji ping massal selesai (' + completed + ' ONT).';
      setTimeout(function() {
        if (!massPingRunning) {
          document.getElementById('massPingProgressBox').style.display = 'none';
        }
      }, 3500);
    }

    updateProgressUi(targets[0]);

    function nextWorker() {
      if (massPingAbort || cursor >= total) {
        if (completed >= total || massPingAbort) {
          finishMassPing();
        }
        return;
      }

      var uname = targets[cursor++];
      updateProgressUi(uname);
      pingSingleOnt(uname, function() {
        completed++;
        updateProgressUi('');
        if (completed >= total || massPingAbort) {
          finishMassPing();
        } else {
          nextWorker();
        }
      });
    }

    var workersToStart = Math.min(concurrency, total);
    for (var w = 0; w < workersToStart; w++) {
      nextWorker();
    }
  }

  function stopMassPing() {
    massPingAbort = true;
  }

  function refreshOntStatus(silent) {
    var icon = document.getElementById('ontRefreshIcon');
    if (icon) icon.classList.add('fa-spin');

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './ppp/pppont_ajax.php?action=status&session=' + encodeURIComponent(ontSession) + '&_t=' + Date.now(), true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (icon) icon.classList.remove('fa-spin');
        if (xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (res && res.ok && Array.isArray(res.items)) {
              ontList = res.items;
              renderOntView();
              var now = new Date();
              var hh = ('0' + now.getHours()).slice(-2);
              var mm = ('0' + now.getMinutes()).slice(-2);
              var ss = ('0' + now.getSeconds()).slice(-2);
              document.getElementById('ontLastSyncLabel').textContent = 'Sinkron pukul ' + hh + ':' + mm + ':' + ss;
            }
          } catch (e) {}
        }
      }
    };
    xhr.send();
  }

  function toggleOntAutoRefresh() {
    var btn = document.getElementById('btnAutoOnt');
    var lbl = document.getElementById('autoOntLabel');
    if (autoOntTimer) {
      clearInterval(autoOntTimer);
      autoOntTimer = null;
      btn.classList.remove('is-live');
      lbl.textContent = 'Live: Off';
    } else {
      btn.classList.add('is-live');
      lbl.textContent = 'Live: 15d';
      autoOntTimer = setInterval(function() {
        if (!document.getElementById('mhm-pppont')) {
          clearInterval(autoOntTimer);
          autoOntTimer = null;
          return;
        }
        refreshOntStatus(true);
      }, 15000);
    }
  }

  function setOntFilter(filterType, btnEl) {
    activeOntFilter = filterType;
    var tabs = document.querySelectorAll('#mhm-pppont .lx-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.remove('is-active');
    }
    if (btnEl) btnEl.classList.add('is-active');
    renderOntView();
  }

  function setOntViewMode(mode) {
    activeViewMode = mode;
    document.getElementById('btnViewCard').classList.toggle('is-active', mode === 'card');
    document.getElementById('btnViewTable').classList.toggle('is-active', mode === 'table');
    renderOntView();
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escapeAttr(str) {
    return escapeHtml(str).replace(/'/g, '&#39;');
  }

  // Render tampilan awal
  renderOntView();
</script>