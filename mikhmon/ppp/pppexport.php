<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

// Helper parse tag [EXP:YYYY-MM-DD HH:MM] dan [PROF:nama] dari comment
if (!function_exists('parseSecretMetaExport')) {
  function parseSecretMetaExport($comment) {
    $expDate = "";
    $expTime = "23:59";
    $origProf = "";
    $cleanText = trim((string)$comment);

    if (preg_match('/\[EXP:(\d{4}-\d{2}-\d{2})(?:\s+(\d{2}:\d{2}))?\]/', $cleanText, $m)) {
      $expDate = $m[1];
      if (!empty($m[2])) $expTime = $m[2];
      $cleanText = trim(str_replace($m[0], '', $cleanText));
    }

    if (preg_match('/\[PROF:([^\]]+)\]/', $cleanText, $mp)) {
      $origProf = trim($mp[1]);
      $cleanText = trim(str_replace($mp[0], '', $cleanText));
    }

    return array(
      "exp_date" => $expDate,
      "exp_time" => $expTime,
      "orig_prof" => $origProf,
      "note" => $cleanText,
    );
  }
}

$getsecrets = $API->comm("/ppp/secret/print");
$TotalSecrets = is_array($getsecrets) ? count($getsecrets) : 0;

$getprofiles = $API->comm("/ppp/profile/print");
$TotalProfiles = is_array($getprofiles) ? count($getprofiles) : 0;

$getactive = $API->comm("/ppp/active/print");
$TotalActive = is_array($getactive) ? count($getactive) : 0;

$activeMap = array();
for ($a = 0; $a < $TotalActive; $a++) {
  $aname = isset($getactive[$a]['name']) ? $getactive[$a]['name'] : "";
  if ($aname != "") {
    $activeMap[$aname] = array(
      "address" => isset($getactive[$a]['address']) ? $getactive[$a]['address'] : "",
      "caller"  => isset($getactive[$a]['caller-id']) ? $getactive[$a]['caller-id'] : "",
    );
  }
}

$exportRows = array();
$countHasIp = 0;
$countOnline = 0;
$countIsolir = 0;
$countDisabled = 0;

for ($i = 0; $i < $TotalSecrets; $i++) {
  $sec = $getsecrets[$i];
  $sname = isset($sec['name']) ? $sec['name'] : "";
  $spass = isset($sec['password']) ? $sec['password'] : "";
  $ssrv = isset($sec['service']) ? $sec['service'] : "pppoe";
  $sprof = isset($sec['profile']) ? $sec['profile'] : "default";
  $sladdr = isset($sec['local-address']) ? $sec['local-address'] : "";
  $sraddr = isset($sec['remote-address']) ? $sec['remote-address'] : "";
  $scaller = isset($sec['caller-id']) ? $sec['caller-id'] : "";
  $scommRaw = isset($sec['comment']) ? $sec['comment'] : "";
  $meta = parseSecretMetaExport($scommRaw);
  $sdis = (isset($sec['disabled']) && $sec['disabled'] == "true");

  $isOnline = isset($activeMap[$sname]);
  $activeIp = $isOnline ? $activeMap[$sname]['address'] : "";
  if ($scaller == "" && $isOnline && !empty($activeMap[$sname]['caller'])) {
    $scaller = $activeMap[$sname]['caller'];
  }

  $effectiveIp = ($sraddr != "") ? $sraddr : $activeIp;
  $ipMode = "";
  if ($sraddr != "") {
    $ipMode = "Statis";
  } elseif ($activeIp != "") {
    $ipMode = "Pool";
  }

  $isIsolir = ($sprof == "ISOLIR");

  if ($effectiveIp != "") $countHasIp++;
  if ($isOnline) $countOnline++;
  if ($isIsolir) $countIsolir++;
  if ($sdis) $countDisabled++;

  $statusLabel = "Offline";
  if ($sdis) {
    $statusLabel = "Disabled";
  } elseif ($isIsolir) {
    $statusLabel = "Isolir";
  } elseif ($isOnline) {
    $statusLabel = "Online";
  }

  $normalProf = $isIsolir ? ($meta['orig_prof'] != "" ? $meta['orig_prof'] : "default") : $sprof;
  $expDisplay = ($meta['exp_date'] != "") ? ($meta['exp_date'] . " " . $meta['exp_time']) : "Unlimited";

  $exportRows[] = array(
    "no" => $i + 1,
    "username" => $sname,
    "password" => $spass,
    "service" => $ssrv,
    "profile" => $sprof,
    "normal_profile" => $normalProf,
    "local_address" => $sladdr,
    "remote_address" => $sraddr,
    "active_ip" => $activeIp,
    "effective_ip" => $effectiveIp,
    "ip_mode" => $ipMode,
    "caller_id" => $scaller,
    "status" => $statusLabel,
    "is_online" => $isOnline,
    "is_isolir" => $isIsolir,
    "is_disabled" => $sdis,
    "due_date" => $expDisplay,
    "location" => $meta['note'],
  );
}

$routerTitle = !empty($identity) ? $identity : (!empty($hotspotname) ? $hotspotname : $session);
?>

<style>
  #mhm-pppexport {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-pppexport *,
  #mhm-pppexport *::before,
  #mhm-pppexport *::after {
    box-sizing: border-box;
  }

  /* Toggle kolom password tanpa memicu [class*="col-"] Mikhmon */
  #mhm-pppexport.hide-pass .lx-pass-cell {
    display: none !important;
  }

  /* Top Bar */
  #mhm-pppexport .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-pppexport .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-pppexport .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-pppexport .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #52525b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 9px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-pppexport .lx-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Buttons */
  #mhm-pppexport .lx-btn {
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
  #mhm-pppexport .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
  }
  #mhm-pppexport .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-pppexport .lx-btn.is-primary:hover {
    background: #27272a;
  }
  #mhm-pppexport .lx-btn.is-excel {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d !important;
  }
  #mhm-pppexport .lx-btn.is-excel:hover {
    background: #dcfce7;
    border-color: #86efac;
  }

  /* Metric Strip */
  #mhm-pppexport .lx-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7;
    background: #fafafa;
  }
  @media (max-width: 768px) {
    #mhm-pppexport .lx-metrics {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  #mhm-pppexport .lx-metric-item {
    padding: 14px 24px;
    border-right: 1px solid #e4e4e7;
  }
  #mhm-pppexport .lx-metric-item:last-child {
    border-right: none;
  }
  #mhm-pppexport .lx-metric-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-pppexport .lx-metric-val {
    font-size: 19px;
    font-weight: 600;
    color: #09090b;
    font-variant-numeric: tabular-nums;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* Filter Toolbar */
  #mhm-pppexport .lx-filter-bar {
    padding: 14px 24px;
    border-bottom: 1px solid #f4f4f5;
    background: #ffffff;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-pppexport .lx-filter-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
  }
  #mhm-pppexport .lx-select {
    height: 34px !important;
    padding: 0 11px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #18181b !important;
    box-shadow: none !important;
    margin: 0 !important;
    cursor: pointer;
  }
  #mhm-pppexport .lx-select:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    outline: none !important;
  }
  #mhm-pppexport .lx-search {
    position: relative;
    width: 240px;
    max-width: 100%;
  }
  #mhm-pppexport .lx-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #a1a1aa;
    pointer-events: none;
  }
  #mhm-pppexport #exportSearchInput {
    width: 100% !important;
    height: 34px !important;
    padding: 0 12px 0 32px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #09090b !important;
    box-shadow: none !important;
    margin: 0 !important;
  }
  #mhm-pppexport #exportSearchInput:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }

  /* Toggle Switch Password */
  #mhm-pppexport .lx-check-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 34px;
    padding: 0 12px;
    border-radius: 8px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    font-size: 12px;
    font-weight: 500;
    color: #3f3f46;
    cursor: pointer;
    user-select: none;
    margin: 0;
  }
  #mhm-pppexport .lx-check-pill:hover {
    background: #f4f4f5;
  }
  #mhm-pppexport .lx-check-pill input {
    margin: 0;
    cursor: pointer;
    accent-color: #18181b;
  }

  /* Table (Force override penuh terhadap CSS global Mikhmon) */
  #mhm-pppexport .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
    max-height: 60vh;
    background: #ffffff;
  }
  #mhm-pppexport table.lx-table {
    display: table !important;
    width: 100% !important;
    table-layout: auto !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    margin: 0 !important;
    border: none !important;
    white-space: nowrap;
    text-align: left;
    background: #ffffff !important;
  }
  #mhm-pppexport table.lx-table thead {
    display: table-header-group !important;
    background: #fafafa !important;
  }
  #mhm-pppexport table.lx-table tbody {
    display: table-row-group !important;
  }
  #mhm-pppexport table.lx-table tr {
    display: table-row !important;
    float: none !important;
  }
  #mhm-pppexport table.lx-table th,
  #mhm-pppexport table.lx-table td {
    display: table-cell !important;
    float: none !important;
    width: auto !important;
    margin: 0 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
    box-shadow: none !important;
    vertical-align: middle !important;
  }
  #mhm-pppexport table.lx-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #fafafa !important;
    color: #71717a !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 12px 18px !important;
    border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppexport table.lx-table tbody tr {
    background: #ffffff !important;
    transition: background-color 0.12s ease;
  }
  #mhm-pppexport table.lx-table tbody td {
    padding: 12px 18px !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 12.5px !important;
    color: #27272a !important;
    background: transparent !important;
  }
  #mhm-pppexport table.lx-table tbody tr:hover {
    background-color: #fafafa !important;
  }

  #mhm-pppexport .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    color: #3f3f46;
    font-variant-numeric: tabular-nums;
  }
  #mhm-pppexport .lx-copyable {
    cursor: pointer;
    border-radius: 5px;
    padding: 2px 6px;
    margin-left: -6px;
    transition: background-color 0.15s ease;
  }
  #mhm-pppexport .lx-copyable:hover {
    background: #f4f4f5;
    color: #09090b;
  }
  #mhm-pppexport .lx-ip-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    color: #0369a1;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
  }
  #mhm-pppexport .lx-ip-pill.is-pool {
    color: #4338ca;
    background: #eef2ff;
    border-color: #c7d2fe;
  }
  #mhm-pppexport .lx-ip-tag {
    font-family: inherit;
    font-size: 9.5px;
    font-weight: 600;
    text-transform: uppercase;
    opacity: 0.75;
  }
  #mhm-pppexport .lx-prof-pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 5px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    font-size: 11.5px;
    font-weight: 500;
    color: #3f3f46;
  }
  #mhm-pppexport .lx-prof-pill.is-isolir {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
    font-weight: 600;
  }
  #mhm-pppexport .lx-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
  }
  #mhm-pppexport .lx-status-pill.is-online {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }
  #mhm-pppexport .lx-status-pill.is-offline {
    background: #f4f4f5;
    color: #52525b;
    border: 1px solid #e4e4e7;
  }
  #mhm-pppexport .lx-status-pill.is-isolir {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
  }
  #mhm-pppexport .lx-status-pill.is-disabled {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
  }
  #mhm-pppexport .lx-empty {
    color: #a1a1aa;
  }

  /* Footer */
  #mhm-pppexport .lx-footer {
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
  #lx-copy-toast {
    position: fixed;
    bottom: 22px;
    right: 22px;
    z-index: 99999;
    background: #18181b;
    color: #ffffff;
    font-size: 12px;
    font-weight: 500;
    padding: 8px 14px;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
    opacity: 0;
    transform: translateY(6px);
    pointer-events: none;
    transition: all 0.18s ease;
  }
  #lx-copy-toast.is-show {
    opacity: 1;
    transform: translateY(0);
  }
</style>

<div id="lx-copy-toast">Tersalin ke clipboard</div>

<div id="mhm-pppexport">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">Rekap Pelanggan &amp; Daftar IP ONT</h3>
      <span class="lx-count" id="filteredCountBadge"><?= $TotalSecrets; ?> dari <?= $TotalSecrets; ?> akun</span>
    </div>

    <div class="lx-actions">
      <a href="./?ppp=secrets&session=<?= urlencode($session); ?>" class="lx-btn" title="Kembali ke PPP Secrets">
        <i class="fa fa-arrow-left" style="color:#71717a;"></i>
        <span>PPP Secrets</span>
      </a>

      <button type="button" onclick="downloadOntCsv();" class="lx-btn is-excel" title="Unduh file CSV siap buka di Microsoft Excel">
        <i class="fa fa-file-excel-o"></i>
        <span>Download CSV (Excel)</span>
      </button>

      <button type="button" onclick="openPrintArchive();" class="lx-btn is-primary" title="Buka tampilan siap cetak untuk arsip dokumentasi jaringan">
        <i class="fa fa-print" style="color:#d4d4d8;"></i>
        <span>Cetak / Print Arsip</span>
      </button>
    </div>
  </div>

  <!-- Metric Strip -->
  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Pelanggan / ONT</div>
      <div class="lx-metric-val"><?= $TotalSecrets; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Memiliki IP ONT (Statis / Pool)</div>
      <div class="lx-metric-val" style="color:#0369a1;"><?= $countHasIp; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Sedang Online</div>
      <div class="lx-metric-val" style="color:#15803d;"><?= $countOnline; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Terisolir / Disabled</div>
      <div class="lx-metric-val" style="color:<?= ($countIsolir + $countDisabled) > 0 ? '#dc2626' : '#09090b'; ?>;">
        <?= ($countIsolir + $countDisabled); ?>
      </div>
    </div>
  </div>

  <!-- Filter & Options Toolbar -->
  <div class="lx-filter-bar">
    <div class="lx-filter-group">
      <select id="filterProfile" class="lx-select" onchange="renderExportTable();" title="Filter berdasarkan Profil Paket">
        <option value="all">Semua Profil Paket</option>
        <?php
        for ($p = 0; $p < $TotalProfiles; $p++) {
          $pname = $getprofiles[$p]['name'];
          echo '<option value="' . htmlspecialchars($pname, ENT_QUOTES) . '">' . htmlspecialchars($pname) . '</option>';
        }
        ?>
      </select>

      <select id="filterStatus" class="lx-select" onchange="renderExportTable();" title="Filter berdasarkan Status Koneksi">
        <option value="all">Semua Status</option>
        <option value="Online">Online</option>
        <option value="Offline">Offline</option>
        <option value="Isolir">Isolir</option>
        <option value="Disabled">Disabled</option>
      </select>

      <select id="filterIpType" class="lx-select" onchange="renderExportTable();" title="Filter berdasarkan IP ONT">
        <option value="all">Semua IP ONT</option>
        <option value="static_ip">IP Statis ONT</option>
        <option value="has_ip">Punya IP (Statis / Pool)</option>
        <option value="no_ip">Belum Ada IP</option>
      </select>

      <label class="lx-check-pill" title="Sertakan kolom Password saat melihat, mengunduh CSV, atau mencetak">
        <input type="checkbox" id="toggleShowPassword" checked onchange="renderExportTable();">
        <span>Tampilkan Password</span>
      </label>
    </div>

    <div class="lx-search">
      <i class="fa fa-search"></i>
      <input id="exportSearchInput" type="text" class="form-control" placeholder="Cari username, IP ONT, ruangan..." onkeyup="renderExportTable();">
    </div>
  </div>

  <!-- Table Preview -->
  <div class="lx-table-wrap">
    <table class="table lx-table" id="exportPreviewTable">
      <thead>
        <tr>
          <th style="width:52px; text-align:center;">No</th>
          <th>Username</th>
          <th class="lx-pass-cell">Password</th>
          <th>Profil Paket</th>
          <th>IP ONT</th>
          <th>MAC / Caller-ID</th>
          <th>Status</th>
          <th>Jatuh Tempo</th>
          <th>Keterangan Lokasi / Ruangan</th>
        </tr>
      </thead>
      <tbody id="exportTableBody"></tbody>
    </table>

    <div id="exportEmptyState" style="display:none; padding:40px 20px; text-align:center; color:#71717a; font-size:13px;">
      Tidak ada data akun PPP Secrets yang cocok dengan filter saat ini.
    </div>
  </div>

  <div class="lx-footer">
    <div>Klik pada <strong>Password</strong> atau <strong>IP ONT</strong> untuk menyalin cepat &bull; File CSV menggunakan UTF-8 BOM</div>
    <div>Router: <strong style="color:#18181b;"><?= htmlspecialchars($routerTitle); ?></strong></div>
  </div>
</div>

<script>
  var rawSecretList = <?= json_encode($exportRows); ?>;
  var routerIdentity = <?= json_encode($routerTitle); ?>;
  var sessionName = <?= json_encode($session); ?>;
  var copyToastTimer = null;

  function copyQuickText(text, label) {
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    }
    var toast = document.getElementById('lx-copy-toast');
    if (toast) {
      toast.textContent = (label || 'Teks') + ' tersalin: ' + text;
      toast.classList.add('is-show');
      if (copyToastTimer) clearTimeout(copyToastTimer);
      copyToastTimer = setTimeout(function() {
        toast.classList.remove('is-show');
      }, 2200);
    }
  }

  function getFilteredSecrets() {
    var profVal = document.getElementById('filterProfile').value;
    var statVal = document.getElementById('filterStatus').value;
    var ipVal = document.getElementById('filterIpType').value;
    var q = (document.getElementById('exportSearchInput').value || '').toLowerCase().trim();

    var result = [];
    for (var i = 0; i < rawSecretList.length; i++) {
      var item = rawSecretList[i];

      if (profVal !== 'all') {
        var matchProf = (item.profile === profVal) || (item.is_isolir && item.normal_profile === profVal);
        if (!matchProf) continue;
      }
      if (statVal !== 'all' && item.status !== statVal) continue;
      if (ipVal === 'static_ip' && !item.remote_address) continue;
      if (ipVal === 'has_ip' && !item.effective_ip) continue;
      if (ipVal === 'no_ip' && item.effective_ip) continue;

      if (q !== '') {
        var hay = (
          item.username + ' ' +
          item.profile + ' ' +
          item.normal_profile + ' ' +
          item.remote_address + ' ' +
          item.active_ip + ' ' +
          item.caller_id + ' ' +
          item.location + ' ' +
          item.status
        ).toLowerCase();
        if (hay.indexOf(q) === -1) continue;
      }

      result.push(item);
    }
    return result;
  }

  function renderExportTable() {
    var list = getFilteredSecrets();
    var showPass = document.getElementById('toggleShowPassword').checked;
    var rootBox = document.getElementById('mhm-pppexport');

    if (rootBox) {
      if (showPass) {
        rootBox.classList.remove('hide-pass');
      } else {
        rootBox.classList.add('hide-pass');
      }
    }

    document.getElementById('filteredCountBadge').textContent = list.length + ' dari ' + rawSecretList.length + ' akun';

    var tbody = document.getElementById('exportTableBody');
    var emptyBox = document.getElementById('exportEmptyState');

    if (list.length === 0) {
      tbody.innerHTML = '';
      emptyBox.style.display = '';
      return;
    }

    emptyBox.style.display = 'none';
    var html = '';

    for (var i = 0; i < list.length; i++) {
      var row = list[i];
      var profClass = row.is_isolir ? 'lx-prof-pill is-isolir' : 'lx-prof-pill';
      var profExtra = (row.is_isolir && row.normal_profile)
        ? ' <span style="font-size:11px; color:#71717a;">(asal: ' + escapeHtml(row.normal_profile) + ')</span>'
        : '';

      var statusClass = 'is-offline';
      if (row.status === 'Online') statusClass = 'is-online';
      else if (row.status === 'Isolir') statusClass = 'is-isolir';
      else if (row.status === 'Disabled') statusClass = 'is-disabled';

      var ipHtml = '<span class="lx-empty">&mdash;</span>';
      if (row.effective_ip) {
        var isPool = (row.ip_mode === 'Pool');
        ipHtml = '<span class="lx-ip-pill ' + (isPool ? 'is-pool' : '') + '" title="Klik untuk salin IP ONT" onclick="copyQuickText(\'' + escapeHtml(row.effective_ip) + '\', \'IP ONT\');">' +
          escapeHtml(row.effective_ip) +
          ' <span class="lx-ip-tag">' + escapeHtml(row.ip_mode) + '</span>' +
        '</span>';
      }

      var callerHtml = row.caller_id
        ? '<span class="lx-mono">' + escapeHtml(row.caller_id) + '</span>'
        : '<span class="lx-empty">&mdash;</span>';

      var locHtml = row.location
        ? '<strong>' + escapeHtml(row.location) + '</strong>'
        : '<span class="lx-empty">&mdash;</span>';

      html += '<tr>' +
        '<td style="text-align:center;"><span class="lx-mono">' + (i + 1) + '</span></td>' +
        '<td><strong>' + escapeHtml(row.username) + '</strong></td>' +
        '<td class="lx-pass-cell"><span class="lx-mono lx-copyable" title="Klik untuk salin password" onclick="copyQuickText(\'' + escapeHtml(row.password) + '\', \'Password\');">' + escapeHtml(row.password) + '</span></td>' +
        '<td><span class="' + profClass + '">' + escapeHtml(row.profile) + '</span>' + profExtra + '</td>' +
        '<td>' + ipHtml + '</td>' +
        '<td>' + callerHtml + '</td>' +
        '<td><span class="lx-status-pill ' + statusClass + '">' + escapeHtml(row.status) + '</span></td>' +
        '<td><span class="lx-mono">' + escapeHtml(row.due_date) + '</span></td>' +
        '<td>' + locHtml + '</td>' +
      '</tr>';
    }

    tbody.innerHTML = html;
  }

  function downloadOntCsv() {
    var list = getFilteredSecrets();
    if (list.length === 0) {
      alert('Tidak ada data yang dapat diekspor pada filter saat ini.');
      return;
    }

    var showPass = document.getElementById('toggleShowPassword').checked;
    var headers = ['No', 'Username'];
    if (showPass) headers.push('Password');
    headers.push('Profil Paket', 'IP ONT', 'Tipe IP', 'Local Address', 'MAC / Caller-ID', 'Status', 'Jatuh Tempo', 'Keterangan Lokasi / Ruangan');

    var csvLines = [];
    csvLines.push(headers.map(csvEscape).join(','));

    for (var i = 0; i < list.length; i++) {
      var r = list[i];
      var profText = r.is_isolir ? (r.profile + ' (Asal: ' + r.normal_profile + ')') : r.profile;
      var line = [
        i + 1,
        r.username
      ];
      if (showPass) line.push(r.password);
      line.push(
        profText,
        r.effective_ip || '-',
        r.ip_mode || '-',
        r.local_address || '-',
        r.caller_id || '-',
        r.status,
        r.due_date,
        r.location || '-'
      );
      csvLines.push(line.map(csvEscape).join(','));
    }

    var bom = '\uFEFF';
    var blob = new Blob([bom + csvLines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);

    var now = new Date();
    var y = now.getFullYear();
    var m = ('0' + (now.getMonth() + 1)).slice(-2);
    var d = ('0' + now.getDate()).slice(-2);
    var filename = 'Daftar-PPPoE-ONT-' + sessionName + '-' + y + '-' + m + '-' + d + '.csv';

    var a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function openPrintArchive() {
    var list = getFilteredSecrets();
    if (list.length === 0) {
      alert('Tidak ada data yang dapat dicetak pada filter saat ini.');
      return;
    }

    var showPass = document.getElementById('toggleShowPassword').checked;
    var now = new Date();
    var tglCetak = ('0' + now.getDate()).slice(-2) + '/' + ('0' + (now.getMonth() + 1)).slice(-2) + '/' + now.getFullYear() + ' ' + ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);

    var rowsHtml = '';
    var ipCount = 0;
    for (var i = 0; i < list.length; i++) {
      var r = list[i];
      if (r.effective_ip) ipCount++;
      var profText = r.is_isolir ? (r.profile + ' (' + r.normal_profile + ')') : r.profile;
      var ipLabel = r.effective_ip ? (r.effective_ip + ' (' + r.ip_mode + ')') : '-';

      rowsHtml += '<tr>' +
        '<td style="text-align:center;">' + (i + 1) + '</td>' +
        '<td style="font-weight:600;">' + escapeHtml(r.username) + '</td>' +
        (showPass ? '<td class="mono">' + escapeHtml(r.password) + '</td>' : '') +
        '<td>' + escapeHtml(profText) + '</td>' +
        '<td class="mono" style="font-weight:600;">' + escapeHtml(ipLabel) + '</td>' +
        '<td class="mono">' + escapeHtml(r.caller_id || '-') + '</td>' +
        '<td>' + escapeHtml(r.status) + '</td>' +
        '<td>' + escapeHtml(r.location || '-') + '</td>' +
      '</tr>';
    }

    var printWin = window.open('', '_blank', 'width=1050,height=740');
    if (!printWin) {
      alert('Pop-up diblokir browser. Izinkan pop-up untuk membuka halaman cetak.');
      return;
    }

    var docHtml = '<!DOCTYPE html>' +
      '<html lang="id"><head><meta charset="UTF-8">' +
      '<title>Arsip Daftar Pelanggan PPPoE & IP ONT - ' + escapeHtml(routerIdentity) + '</title>' +
      '<style>' +
        '@page { size: A4 landscape; margin: 14mm; }' +
        'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #111827; margin: 0; padding: 16px; font-size: 11.5px; }' +
        '.doc-head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #18181b; padding-bottom: 10px; margin-bottom: 14px; }' +
        '.doc-title { font-size: 17px; font-weight: 700; margin: 0 0 3px 0; letter-spacing: -0.02em; }' +
        '.doc-sub { font-size: 11.5px; color: #4b5563; margin: 0; }' +
        '.doc-meta { text-align: right; font-size: 11px; color: #374151; line-height: 1.5; }' +
        '.summary-bar { display: flex; gap: 18px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; font-size: 11px; }' +
        'table { width: 100%; border-collapse: collapse; }' +
        'th { background: #18181b; color: #ffffff; text-align: left; padding: 7px 9px; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.04em; border: 1px solid #18181b; }' +
        'td { padding: 6px 9px; border: 1px solid #d1d5db; vertical-align: middle; }' +
        'tr:nth-child(even) td { background: #f9fafb; }' +
        '.mono { font-family: Consolas, "Courier New", monospace; font-size: 11px; }' +
        '.sign-area { margin-top: 24px; display: flex; justify-content: flex-end; }' +
        '.sign-box { width: 220px; text-align: center; font-size: 11px; }' +
        '.sign-space { height: 54px; }' +
        '@media print { body { padding: 0; } .no-print { display: none; } }' +
      '</style></head><body>' +
      '<div class="doc-head">' +
        '<div>' +
          '<h1 class="doc-title">ARSIP DATA PELANGGAN PPPoE &amp; DAFTAR IP ONT</h1>' +
          '<p class="doc-sub">Dokumentasi Topologi &amp; Alokasi IP Perangkat Jaringan &mdash; ' + escapeHtml(routerIdentity) + '</p>' +
        '</div>' +
        '<div class="doc-meta">' +
          '<div><strong>Session:</strong> ' + escapeHtml(sessionName) + '</div>' +
          '<div><strong>Tanggal Cetak:</strong> ' + escapeHtml(tglCetak) + '</div>' +
        '</div>' +
      '</div>' +
      '<div class="summary-bar">' +
        '<div><strong>Total Data Tercetak:</strong> ' + list.length + ' Akun</div>' +
        '<div><strong>Memiliki IP ONT:</strong> ' + ipCount + ' Perangkat</div>' +
        '<div><strong>Mode Password:</strong> ' + (showPass ? 'Ditampilkan' : 'Disembunyikan (Arsip Publik)') + '</div>' +
      '</div>' +
      '<table><thead><tr>' +
        '<th style="width:34px; text-align:center;">No</th>' +
        '<th>Username</th>' +
        (showPass ? '<th>Password</th>' : '') +
        '<th>Profil Paket</th>' +
        '<th>IP ONT</th>' +
        '<th>MAC / Caller-ID</th>' +
        '<th>Status</th>' +
        '<th>Keterangan Lokasi / Ruangan</th>' +
      '</tr></thead><tbody>' + rowsHtml + '</tbody></table>' +
      '<div class="sign-area"><div class="sign-box">' +
        '<div>Penanggung Jawab Jaringan,</div>' +
        '<div class="sign-space"></div>' +
        '<div style="border-top:1px solid #374151; padding-top:4px; font-weight:600;">Teknisi / Admin Jaringan</div>' +
      '</div></div>' +
      '<script>window.onload = function() { setTimeout(function() { window.print(); }, 250); };<\/script>' +
      '</body></html>';

    printWin.document.open();
    printWin.document.write(docHtml);
    printWin.document.close();
  }

  function csvEscape(val) {
    var s = String(val === null || val === undefined ? '' : val);
    return '"' + s.replace(/"/g, '""') + '"';
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  renderExportTable();
</script>