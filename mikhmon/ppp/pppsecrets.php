<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

if (!isset($session) || $session === '') {
  $session = isset($_GET['session']) ? $_GET['session'] : '';
}

if (!isset($API) || !is_object($API) || !$API->connected) {
  include_once('./include/config.php');
  include_once('./include/readcfg.php');
  include_once('./lib/routeros_api.class.php');
  $API = new RouterosAPI();
  $API->debug = false;
  $API->connect($iphost, $userhost, decrypt($passwdhost));
}

// Helper: Parse keterangan bersih, tanggal jatuh tempo (JT:YYYY-MM-DD HH:MM), dan [PREV:profile]
function parseSecretComment($rawComment) {
  $raw = (string)$rawComment;
  $dueDate = '';
  $dueTime = '23:59';
  $prevProfile = '';

  if (preg_match('/\[PREV:([^\]]+)\]/i', $raw, $mPrev)) {
    $prevProfile = trim($mPrev[1]);
    $raw = str_replace($mPrev[0], '', $raw);
  }

  if (preg_match('/(?:JT|EXP):(\d{4}-\d{2}-\d{2})(?:\s+(\d{2}:\d{2}))?/i', $raw, $mDue)) {
    $dueDate = $mDue[1];
    if (!empty($mDue[2])) {
      $dueTime = $mDue[2];
    }
    $raw = str_replace($mDue[0], '', $raw);
  }

  $clean = trim(preg_replace('/\s*\|\s*/', ' ', $raw));
  $clean = trim(preg_replace('/\s{2,}/', ' ', $clean));

  return array(
    'text'         => $clean,
    'due_date'     => $dueDate,
    'due_time'     => $dueTime,
    'prev_profile' => $prevProfile,
  );
}

// Helper: Susun kembali comment untuk disimpan ke MikroTik
function buildSecretComment($cleanText, $dueDate, $dueTime, $prevProfile = '') {
  $parts = array();
  $cleanText = trim((string)$cleanText);
  if ($cleanText !== '') {
    $parts[] = $cleanText;
  }
  $dueDate = trim((string)$dueDate);
  $dueTime = trim((string)$dueTime) !== '' ? trim((string)$dueTime) : '23:59';
  if ($dueDate !== '') {
    $parts[] = 'JT:' . $dueDate . ' ' . $dueTime;
  }
  $prevProfile = trim((string)$prevProfile);
  if ($prevProfile !== '') {
    $parts[] = '[PREV:' . $prevProfile . ']';
  }
  return implode(' | ', $parts);
}

// Helper: Putus sesi aktif berdasarkan username
function kickActiveUser($API, $username) {
  if ($username === '') return 0;
  $actives = $API->comm("/ppp/active/print", array("?name" => $username));
  $kicked = 0;
  if (is_array($actives)) {
    foreach ($actives as $act) {
      if (isset($act['.id'])) {
        $API->comm("/ppp/active/remove", array(".id" => $act['.id']));
        $kicked++;
      }
    }
  }
  return $kicked;
}

// Proses Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ppp_action'])) {
  $act = $_POST['ppp_action'];
  $redirectUrl = './?ppp=secrets&session=' . urlencode($session);

  // 1. Tambah PPP Secret Baru
  if ($act === 'add_secret') {
    $name     = trim($_POST['name']);
    $password = trim($_POST['password']);
    $service  = trim($_POST['service']) !== '' ? trim($_POST['service']) : 'pppoe';
    $profile  = trim($_POST['profile']);
    $dueDate  = trim($_POST['due_date']);
    $dueTime  = trim($_POST['due_time']);
    $local    = trim($_POST['local_address']);
    $remote   = trim($_POST['remote_address']);
    $caller   = strtoupper(trim($_POST['caller_id']));
    $desc     = trim($_POST['comment']);

    $comment = buildSecretComment($desc, $dueDate, $dueTime);

    $params = array(
      "name"     => $name,
      "password" => $password,
      "service"  => $service,
      "profile"  => $profile,
      "comment"  => $comment,
    );
    if ($local !== '')  $params["local-address"]  = $local;
    if ($remote !== '') $params["remote-address"] = $remote;
    if ($caller !== '') $params["caller-id"]      = $caller;

    $res = $API->comm("/ppp/secret/add", $params);
    $API->disconnect();

    if (is_array($res) && isset($res['!trap'])) {
      $err = isset($res['!trap'][0]['message']) ? $res['!trap'][0]['message'] : 'Gagal menambahkan akun PPPoE.';
      echo "<script>window.location='" . $redirectUrl . "&error=" . urlencode($err) . "';</script>";
      exit;
    }

    echo "<script>window.location='" . $redirectUrl . "&notice=" . urlencode("Akun PPPoE '" . $name . "' berhasil ditambahkan.") . "';</script>";
    exit;
  }

  // 2. Edit PPP Secret (Aman untuk RouterOS v6/v7 & Tanpa Putus untuk Keterangan / Jadwal / Lock MAC)
  if ($act === 'edit_secret') {
    $id       = trim($_POST['secret_id']);
    $name     = trim($_POST['name']);
    $password = trim($_POST['password']);
    $service  = trim($_POST['service']) !== '' ? trim($_POST['service']) : 'pppoe';
    $profile  = trim($_POST['profile']);
    $dueDate  = trim($_POST['due_date']);
    $dueTime  = trim($_POST['due_time']);
    $local    = trim($_POST['local_address']);
    $remote   = trim($_POST['remote_address']);
    $caller   = strtoupper(trim($_POST['caller_id']));
    $desc     = trim($_POST['comment']);

    $oldList = $API->comm("/ppp/secret/print", array("?.id" => $id));
    $old = isset($oldList[0]) ? $oldList[0] : array();

    $oldName    = isset($old['name']) ? (string)$old['name'] : '';
    $oldPass    = isset($old['password']) ? (string)$old['password'] : '';
    $oldService = isset($old['service']) ? (string)$old['service'] : 'any';
    $oldProfile = isset($old['profile']) ? (string)$old['profile'] : '';
    $oldLocal   = isset($old['local-address']) ? (string)$old['local-address'] : '';
    $oldRemote  = isset($old['remote-address']) ? (string)$old['remote-address'] : '';
    $oldParsed  = parseSecretComment(isset($old['comment']) ? $old['comment'] : '');

    $keepPrev = (strcasecmp($profile, 'ISOLIR') === 0) ? $oldParsed['prev_profile'] : '';
    $comment = buildSecretComment($desc, $dueDate, $dueTime, $keepPrev);

    $setParams = array(
      ".id"       => $id,
      "name"      => $name,
      "password"  => $password,
      "service"   => $service,
      "profile"   => $profile,
      "caller-id" => $caller,
      "comment"   => $comment,
    );
    if ($local !== '') {
      $setParams["local-address"] = $local;
    }
    if ($remote !== '') {
      $setParams["remote-address"] = $remote;
    }

    $res = $API->comm("/ppp/secret/set", $setParams);

    if ($local === '' && $oldLocal !== '') {
      $API->comm("/ppp/secret/unset", array("numbers" => $id, "value-name" => "local-address"));
    }
    if ($remote === '' && $oldRemote !== '') {
      $API->comm("/ppp/secret/unset", array("numbers" => $id, "value-name" => "remote-address"));
    }

    if (is_array($res) && isset($res['!trap'])) {
      $API->disconnect();
      $err = isset($res['!trap'][0]['message']) ? $res['!trap'][0]['message'] : 'Gagal menyimpan perubahan ke MikroTik.';
      echo "<script>window.location='" . $redirectUrl . "&error=" . urlencode($err) . "';</script>";
      exit;
    }

    $needKick = (
      $oldName    !== $name ||
      $oldPass    !== $password ||
      $oldService !== $service ||
      $oldProfile !== $profile ||
      $oldLocal   !== $local ||
      $oldRemote  !== $remote
    );

    if ($needKick) {
      kickActiveUser($API, $oldName);
      $msg = "Perubahan paket/autentikasi '" . $name . "' disimpan & sesi di-refresh.";
    } else {
      $msg = "Perubahan '" . $name . "' berhasil disimpan tanpa memutus koneksi klien.";
    }

    $API->disconnect();
    echo "<script>window.location='" . $redirectUrl . "&notice=" . urlencode($msg) . "';</script>";
    exit;
  }

  // 3. Isolir / Pulihkan 1-Klik
  if ($act === 'toggle_isolir') {
    $id = trim($_POST['secret_id']);
    $targetMode = trim($_POST['mode']);

    $oldList = $API->comm("/ppp/secret/print", array("?.id" => $id));
    if (!empty($oldList[0])) {
      $item = $oldList[0];
      $uname = isset($item['name']) ? $item['name'] : '';
      $curProfile = isset($item['profile']) ? $item['profile'] : 'default';
      $parsed = parseSecretComment(isset($item['comment']) ? $item['comment'] : '');

      if ($targetMode === 'isolir') {
        $newComment = buildSecretComment($parsed['text'], $parsed['due_date'], $parsed['due_time'], $curProfile);
        $API->comm("/ppp/secret/set", array(
          ".id"     => $id,
          "profile" => "ISOLIR",
          "comment" => $newComment,
        ));
        kickActiveUser($API, $uname);
        $msg = "Akun '" . $uname . "' dipindahkan ke profil ISOLIR & sesi diputus.";
      } else {
        $restoreProfile = $parsed['prev_profile'] !== '' ? $parsed['prev_profile'] : trim($_POST['fallback_profile']);
        $newComment = buildSecretComment($parsed['text'], $parsed['due_date'], $parsed['due_time'], '');
        $API->comm("/ppp/secret/set", array(
          ".id"     => $id,
          "profile" => $restoreProfile,
          "comment" => $newComment,
        ));
        kickActiveUser($API, $uname);
        $msg = "Akun '" . $uname . "' dipulihkan ke profil '" . $restoreProfile . "'.";
      }
      $API->disconnect();
      echo "<script>window.location='" . $redirectUrl . "&notice=" . urlencode($msg) . "';</script>";
      exit;
    }
  }

  // 4. Putus Sesi Aktif Manual
  if ($act === 'kick_active') {
    $uname = trim($_POST['username']);
    kickActiveUser($API, $uname);
    $API->disconnect();
    echo "<script>window.location='" . $redirectUrl . "&notice=" . urlencode("Sesi aktif '" . $uname . "' berhasil diputus.") . "';</script>";
    exit;
  }

  // 5. Hapus PPP Secret
  if ($act === 'delete_secret') {
    $id = trim($_POST['secret_id']);
    $uname = trim($_POST['username']);
    $API->comm("/ppp/secret/remove", array(".id" => $id));
    kickActiveUser($API, $uname);
    $API->disconnect();
    echo "<script>window.location='" . $redirectUrl . "&notice=" . urlencode("Akun '" . $uname . "' berhasil dihapus.") . "';</script>";
    exit;
  }

  // 6. Pasang / Update Scheduler Auto-Isolir di MikroTik (Compatible RouterOS v6 & v7)
  if ($act === 'install_scheduler') {
    $schedName = "Mikhmon-PPPoE-AutoIsolir";
    $onEventScript = ':local curDate [/system clock get date]; '
      . ':local curTime [/system clock get time]; '
      . ':local isoDate ""; '
      . ':if ([:len $curDate] = 10) do={ :set isoDate $curDate } else={ '
      . ':local mStr [:pick $curDate 0 3]; '
      . ':local dStr [:pick $curDate 4 6]; '
      . ':local yStr [:pick $curDate 7 11]; '
      . ':local mNum "01"; '
      . ':if ($mStr="feb") do={:set mNum "02"}; :if ($mStr="mar") do={:set mNum "03"}; '
      . ':if ($mStr="apr") do={:set mNum "04"}; :if ($mStr="may") do={:set mNum "05"}; '
      . ':if ($mStr="jun") do={:set mNum "06"}; :if ($mStr="jul") do={:set mNum "07"}; '
      . ':if ($mStr="aug") do={:set mNum "08"}; :if ($mStr="sep") do={:set mNum "09"}; '
      . ':if ($mStr="oct") do={:set mNum "10"}; :if ($mStr="nov") do={:set mNum "11"}; '
      . ':if ($mStr="dec") do={:set mNum "12"}; '
      . ':set isoDate ($yStr . "-" . $mNum . "-" . $dStr); '
      . '}; '
      . ':local curHHMM [:pick $curTime 0 5]; '
      . ':foreach s in=[/ppp secret find where profile!="ISOLIR"] do={ '
      . ':local c [/ppp secret get $s comment]; '
      . ':local pos [:find $c "JT:"]; '
      . ':if ([:typeof $pos] = "num") do={ '
      . ':local expDate [:pick $c ($pos + 3) ($pos + 13)]; '
      . ':local expTime [:pick $c ($pos + 14) ($pos + 19)]; '
      . ':if ([:len $expTime] != 5) do={ :set expTime "23:59" }; '
      . ':if (($isoDate > $expDate) || (($isoDate = $expDate) && ($curHHMM >= $expTime))) do={ '
      . ':local uName [/ppp secret get $s name]; '
      . ':local oldProf [/ppp secret get $s profile]; '
      . '/ppp secret set $s profile="ISOLIR" comment=($c . " | [PREV:" . $oldProf . "]"); '
      . '/ppp active remove [find name=$uName]; '
      . ':log warning ("Auto-Isolir PPPoE: " . $uName . " jatuh tempo (" . $expDate . " " . $expTime . ")"); '
      . '}; '
      . '}; '
      . '};';

    $existing = $API->comm("/system/scheduler/print", array("?name" => $schedName));
    if (!empty($existing[0]['.id'])) {
      $API->comm("/system/scheduler/set", array(
        ".id"        => $existing[0]['.id'],
        "interval"   => "00:05:00",
        "start-time" => "startup",
        "disabled"   => "no",
        "on-event"   => $onEventScript,
        "comment"    => "Auto-Isolir PPPoE Jatuh Tempo (Mikhmon)",
      ));
    } else {
      $API->comm("/system/scheduler/add", array(
        "name"       => $schedName,
        "interval"   => "00:05:00",
        "start-time" => "startup",
        "disabled"   => "no",
        "on-event"   => $onEventScript,
        "comment"    => "Auto-Isolir PPPoE Jatuh Tempo (Mikhmon)",
      ));
    }
    $API->disconnect();
    echo "<script>window.location='" . $redirectUrl . "&notice=" . urlencode("Scheduler Auto-Isolir berhasil diaktifkan (cek otomatis tiap 5 menit).") . "';</script>";
    exit;
  }
}

// Ambil Data Terbaru dari MikroTik
$profilesRaw = $API->comm("/ppp/profile/print");
$secretsRaw  = $API->comm("/ppp/secret/print");
$activesRaw  = $API->comm("/ppp/active/print");
$schedRaw    = $API->comm("/system/scheduler/print", array("?name" => "Mikhmon-PPPoE-AutoIsolir"));

$schedulerActive = (!empty($schedRaw[0]['.id']) && (!isset($schedRaw[0]['disabled']) || $schedRaw[0]['disabled'] !== 'true'));

$profilesList = array();
$defaultRestoreProfile = 'default';
if (is_array($profilesRaw)) {
  foreach ($profilesRaw as $pr) {
    if (!empty($pr['name'])) {
      $profilesList[] = $pr['name'];
      if (strcasecmp($pr['name'], 'ISOLIR') !== 0 && $defaultRestoreProfile === 'default' && $pr['name'] !== 'default' && $pr['name'] !== 'default-encryption') {
        $defaultRestoreProfile = $pr['name'];
      }
    }
  }
}

// Map sesi online berdasarkan username
$activeMap = array();
if (is_array($activesRaw)) {
  foreach ($activesRaw as $ac) {
    if (!empty($ac['name'])) {
      $activeMap[$ac['name']] = array(
        'address'   => isset($ac['address']) ? $ac['address'] : '',
        'caller_id' => isset($ac['caller-id']) ? $ac['caller-id'] : '',
        'uptime'    => isset($ac['uptime']) ? $ac['uptime'] : '',
      );
    }
  }
}

// Olah statistik & baris akun
$secretsData = array();
$totalSecrets = 0;
$totalOnline = 0;
$totalIsolir = 0;
$totalScheduled = 0;

if (is_array($secretsRaw)) {
  foreach ($secretsRaw as $sc) {
    $uname = isset($sc['name']) ? $sc['name'] : '';
    if ($uname === '') continue;

    $totalSecrets++;
    $prof = isset($sc['profile']) ? $sc['profile'] : 'default';
    $isIsolir = (strcasecmp($prof, 'ISOLIR') === 0);
    $isOnline = isset($activeMap[$uname]);
    $parsed = parseSecretComment(isset($sc['comment']) ? $sc['comment'] : '');

    if ($isOnline) $totalOnline++;
    if ($isIsolir) $totalIsolir++;
    if ($parsed['due_date'] !== '') $totalScheduled++;

    $secretsData[] = array(
      'id'             => isset($sc['.id']) ? $sc['.id'] : '',
      'name'           => $uname,
      'password'       => isset($sc['password']) ? $sc['password'] : '',
      'service'        => isset($sc['service']) ? $sc['service'] : 'pppoe',
      'profile'        => $prof,
      'local_address'  => isset($sc['local-address']) ? $sc['local-address'] : '',
      'remote_address' => isset($sc['remote-address']) ? $sc['remote-address'] : '',
      'caller_id'      => isset($sc['caller-id']) ? $sc['caller-id'] : '',
      'disabled'       => (isset($sc['disabled']) && $sc['disabled'] === 'true'),
      'is_isolir'      => $isIsolir,
      'is_online'      => $isOnline,
      'active_info'    => $isOnline ? $activeMap[$uname] : null,
      'clean_comment'  => $parsed['text'],
      'due_date'       => $parsed['due_date'],
      'due_time'       => $parsed['due_time'],
      'prev_profile'   => $parsed['prev_profile'],
    );
  }
}

$noticeMsg = isset($_GET['notice']) ? trim($_GET['notice']) : '';
$errorMsg  = isset($_GET['error']) ? trim($_GET['error']) : '';
?>

<style>
  #mhm-pppsecrets {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Floating Toast Alert */
  #mhm-pppsecrets .lx-toast {
    position: fixed;
    top: 66px;
    right: 22px;
    z-index: 10050;
    min-width: 280px;
    max-width: 390px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-left: 4px solid #16a34a;
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: 0 14px 32px -8px rgba(9, 9, 11, 0.18);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    animation: lxToastSlide 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    transition: opacity 0.22s ease, transform 0.22s ease;
  }
  #mhm-pppsecrets .lx-toast.is-error {
    border-left-color: #dc2626;
  }
  #mhm-pppsecrets .lx-toast-icon {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: #f0fdf4;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
  }
  #mhm-pppsecrets .lx-toast.is-error .lx-toast-icon {
    background: #fef2f2;
    color: #dc2626;
  }
  #mhm-pppsecrets .lx-toast-text {
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b;
    line-height: 1.4;
    flex: 1;
  }
  #mhm-pppsecrets .lx-toast-close {
    border: none;
    background: transparent;
    color: #a1a1aa;
    font-size: 15px;
    cursor: pointer;
    padding: 2px 4px;
    line-height: 1;
  }
  #mhm-pppsecrets .lx-toast-close:hover {
    color: #18181b;
  }
  @keyframes lxToastSlide {
    from { opacity: 0; transform: translateX(16px); }
    to   { opacity: 1; transform: translateX(0); }
  }

  /* Top Bar */
  #mhm-pppsecrets .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-pppsecrets .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-pppsecrets .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-pppsecrets .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #52525b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 9px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-pppsecrets .lx-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Search Input */
  #mhm-pppsecrets .lx-search {
    position: relative;
    width: 235px;
    max-width: 100%;
  }
  #mhm-pppsecrets .lx-search i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #a1a1aa;
    pointer-events: none;
  }
  #mhm-pppsecrets #secretSearchInput {
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
  #mhm-pppsecrets #secretSearchInput:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }

  /* Buttons */
  #mhm-pppsecrets .lx-btn {
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
  #mhm-pppsecrets .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
  }
  #mhm-pppsecrets .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-pppsecrets .lx-btn.is-primary:hover {
    background: #27272a;
  }
  #mhm-pppsecrets .lx-btn.is-danger {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff !important;
  }
  #mhm-pppsecrets .lx-btn.is-danger:hover {
    background: #b91c1c;
    border-color: #b91c1c;
  }
  #mhm-pppsecrets .lx-btn.is-warning {
    background: #d97706;
    border-color: #d97706;
    color: #ffffff !important;
  }
  #mhm-pppsecrets .lx-btn.is-warning:hover {
    background: #b45309;
    border-color: #b45309;
  }
  #mhm-pppsecrets .lx-btn.is-restore-solid {
    background: #16a34a;
    border-color: #16a34a;
    color: #ffffff !important;
  }
  #mhm-pppsecrets .lx-btn.is-restore-solid:hover {
    background: #15803d;
    border-color: #15803d;
  }
  #mhm-pppsecrets .lx-btn.is-amber {
    background: #fffbeb;
    border-color: #fde68a;
    color: #b45309 !important;
  }
  #mhm-pppsecrets .lx-btn.is-amber:hover {
    background: #fef3c7;
  }
  #mhm-pppsecrets .lx-btn.is-green {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d !important;
  }

  /* KPI Summary Strip */
  #mhm-pppsecrets .lx-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    padding: 18px 24px 0 24px;
    background: #fafafa;
  }
  @media (max-width: 900px) {
    #mhm-pppsecrets .lx-stats-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 480px) {
    #mhm-pppsecrets .lx-stats-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-pppsecrets .lx-stat-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-pppsecrets .lx-stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #71717a;
    margin-bottom: 5px;
  }
  #mhm-pppsecrets .lx-stat-val {
    font-size: 20px;
    font-weight: 700;
    color: #09090b;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  #mhm-pppsecrets .lx-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
  }

  /* Body & Info Bar */
  #mhm-pppsecrets .lx-body {
    padding: 14px 24px 22px 24px;
    background: #fafafa;
  }
  #mhm-pppsecrets .lx-hint-bar {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 9px;
    padding: 10px 14px;
    font-size: 12px;
    color: #52525b;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
  }

  /* Table Card */
  #mhm-pppsecrets .lx-table-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow: hidden;
  }
  #mhm-pppsecrets .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
  }
  #mhm-pppsecrets .lx-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    white-space: nowrap;
    text-align: left;
    background: #ffffff !important;
  }
  #mhm-pppsecrets .lx-table thead th {
    background: #fafafa !important;
    color: #71717a !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 11px 14px !important;
    border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppsecrets .lx-table tbody td {
    padding: 11px 14px !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 12.5px !important;
    color: #27272a !important;
    vertical-align: middle;
  }
  #mhm-pppsecrets .lx-table tbody tr:hover {
    background-color: #fafafa !important;
  }

  /* Row Action Buttons */
  #mhm-pppsecrets .lx-row-actions {
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  #mhm-pppsecrets .lx-act-pill {
    height: 26px;
    padding: 0 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
  }
  #mhm-pppsecrets .lx-act-pill.is-isolir {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
  }
  #mhm-pppsecrets .lx-act-pill.is-isolir:hover {
    background: #dc2626;
    color: #ffffff;
  }
  #mhm-pppsecrets .lx-act-pill.is-restore {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
  }
  #mhm-pppsecrets .lx-act-pill.is-restore:hover {
    background: #16a34a;
    color: #ffffff;
  }
  #mhm-pppsecrets .lx-icon-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #52525b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11.5px;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  #mhm-pppsecrets .lx-icon-btn:hover {
    background: #f4f4f5;
    color: #09090b;
    border-color: #d4d4d8;
  }
  #mhm-pppsecrets .lx-icon-btn.is-del:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
  }

  /* Badges & Mono */
  #mhm-pppsecrets .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    color: #3f3f46;
  }
  #mhm-pppsecrets .lx-prof-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #f4f4f5;
    color: #18181b;
    border: 1px solid #e4e4e7;
  }
  #mhm-pppsecrets .lx-prof-badge.is-isolir {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
  }

  /* Modal Styling */
  #mhm-pppsecrets .lx-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 9, 11, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #mhm-pppsecrets .lx-modal-backdrop.is-open {
    display: flex;
  }
  #mhm-pppsecrets .lx-modal-card {
    width: 100%;
    max-width: 560px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(9, 9, 11, 0.22);
    overflow: hidden;
    animation: lxModalIn 0.16s ease-out;
  }
  #mhm-pppsecrets .lx-modal-card.is-confirm {
    max-width: 410px;
    z-index: 10010;
  }
  #mhm-pppsecrets .lx-confirm-body {
    padding: 22px 24px 18px 24px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
  }
  #mhm-pppsecrets .lx-confirm-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
  }
  #mhm-pppsecrets .lx-confirm-icon.is-warning {
    background: #fffbeb;
    border-color: #fde68a;
    color: #d97706;
  }
  #mhm-pppsecrets .lx-confirm-icon.is-restore {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #16a34a;
  }
  #mhm-pppsecrets .lx-confirm-title {
    margin: 0 0 6px 0;
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.015em;
  }
  #mhm-pppsecrets .lx-confirm-desc {
    margin: 0;
    font-size: 12.5px;
    color: #52525b;
    line-height: 1.55;
  }
  #mhm-pppsecrets .lx-confirm-user-tag {
    display: inline-block;
    margin-top: 9px;
    padding: 3px 9px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
    color: #09090b;
  }
  #mhm-pppsecrets .lx-confirm-foot {
    padding: 13px 22px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
  }

  #mhm-pppsecrets .lx-modal-head {
    padding: 16px 22px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  #mhm-pppsecrets .lx-modal-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #09090b;
  }
  #mhm-pppsecrets .lx-modal-close {
    border: none;
    background: transparent;
    color: #71717a;
    font-size: 16px;
    cursor: pointer;
    padding: 4px;
  }
  #mhm-pppsecrets .lx-modal-body {
    padding: 18px 22px;
  }
  #mhm-pppsecrets .lx-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px 16px;
  }
  @media (max-width: 560px) {
    #mhm-pppsecrets .lx-form-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-pppsecrets .lx-field label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #27272a;
    margin-bottom: 5px;
  }
  #mhm-pppsecrets .lx-field label span {
    color: #a1a1aa;
    font-weight: 400;
  }
  #mhm-pppsecrets .lx-input {
    width: 100% !important;
    height: 36px !important;
    padding: 0 11px !important;
    background: #ffffff !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #09090b !important;
    box-shadow: none !important;
    margin: 0 !important;
  }
  #mhm-pppsecrets .lx-input:focus {
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  #mhm-pppsecrets .lx-mac-chip {
    display: none;
    float: right;
    font-size: 10.5px;
    font-weight: 600;
    color: #15803d;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    padding: 1px 7px;
    border-radius: 5px;
    text-decoration: none !important;
    cursor: pointer;
  }
  #mhm-pppsecrets .lx-mac-chip:hover {
    background: #dcfce7;
  }
  #mhm-pppsecrets .lx-modal-foot {
    padding: 14px 22px;
    border-top: 1px solid #f4f4f5;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-pppsecrets .lx-smart-note {
    font-size: 11px;
    color: #71717a;
    line-height: 1.4;
  }
  #mhm-pppsecrets .lx-footer {
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

  @keyframes lxModalIn {
    from { opacity: 0; transform: translateY(6px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }
</style>

<div id="mhm-pppsecrets">
  <?php if ($noticeMsg !== ''): ?>
    <div class="lx-toast" id="lxToastBanner">
      <div class="lx-toast-icon"><i class="fa fa-check"></i></div>
      <div class="lx-toast-text"><?= htmlspecialchars($noticeMsg); ?></div>
      <button type="button" class="lx-toast-close" onclick="dismissToastBanner();">&times;</button>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg !== ''): ?>
    <div class="lx-toast is-error" id="lxToastBanner">
      <div class="lx-toast-icon"><i class="fa fa-exclamation"></i></div>
      <div class="lx-toast-text"><?= htmlspecialchars($errorMsg); ?></div>
      <button type="button" class="lx-toast-close" onclick="dismissToastBanner();">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">PPP Secrets &amp; Isolir</h3>
      <span class="lx-count"><?= $totalSecrets; ?> akun</span>
    </div>

    <div class="lx-actions">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="secretSearchInput" type="text" class="form-control" placeholder="Cari username, IP, atau keterangan..." onkeyup="filterSecretsTable();">
      </div>

      <form method="post" style="margin:0; display:inline;">
        <input type="hidden" name="ppp_action" value="install_scheduler">
        <button type="submit" class="lx-btn <?= $schedulerActive ? 'is-green' : 'is-amber'; ?>" title="Pasang atau perbarui script scheduler otomatis di MikroTik">
          <i class="fa <?= $schedulerActive ? 'fa-check-circle' : 'fa-clock-o'; ?>"></i>
          <span><?= $schedulerActive ? 'Scheduler Isolir: Aktif' : 'Aktifkan Scheduler Isolir'; ?></span>
        </button>
      </form>

      <button type="button" onclick="exportSecretsCsv();" class="lx-btn" title="Unduh daftar akun PPPoE ke CSV">
        <i class="fa fa-download" style="color:#71717a;"></i>
        <span>Export / Print</span>
      </button>

      <button type="button" onclick="openAddSecretModal();" class="lx-btn is-primary">
        <i class="fa fa-plus" style="font-size:11px;"></i>
        <span>Tambah Secret</span>
      </button>
    </div>
  </div>

  <!-- 4 Summary Metric Cards -->
  <div class="lx-stats-grid">
    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Total Akun</div>
        <div class="lx-stat-val"><?= $totalSecrets; ?></div>
      </div>
      <i class="fa fa-users" style="color:#a1a1aa; font-size:15px;"></i>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Online Sekarang</div>
        <div class="lx-stat-val" style="color:#15803d;">
          <span class="lx-dot" style="background:#16a34a;"></span>
          <span><?= $totalOnline; ?></span>
        </div>
      </div>
      <i class="fa fa-plug" style="color:#16a34a; font-size:15px;"></i>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Status Isolir</div>
        <div class="lx-stat-val" style="color:<?= $totalIsolir > 0 ? '#dc2626' : '#09090b'; ?>;"><?= $totalIsolir; ?></div>
      </div>
      <i class="fa fa-ban" style="color:#dc2626; font-size:15px;"></i>
    </div>

    <div class="lx-stat-card">
      <div>
        <div class="lx-stat-label">Berjadwal Jatuh Tempo</div>
        <div class="lx-stat-val"><?= $totalScheduled; ?></div>
      </div>
      <i class="fa fa-calendar-check-o" style="color:#71717a; font-size:15px;"></i>
    </div>
  </div>

  <div class="lx-body">
    <div class="lx-hint-bar">
      <span>
        Tombol <strong>Isolir</strong> memindahkan akun ke profil <code>ISOLIR</code> dan langsung memutus sesi aktif. Edit <strong>Keterangan</strong>, <strong>Jatuh Tempo</strong>, atau <strong>Lock MAC</strong> tidak memutus koneksi aktif klien.
      </span>
      <span style="font-size:11px; color:#71717a;">RouterOS v6 &amp; v7 Compatible</span>
    </div>

    <div class="lx-table-card">
      <div class="lx-table-wrap">
        <table class="table lx-table" id="pppSecretsTable">
          <thead>
            <tr>
              <th style="width:165px;">Aksi &amp; Isolir</th>
              <th>Username</th>
              <th>Password</th>
              <th>Profile</th>
              <th>Jatuh Tempo</th>
              <th>Local / Remote Address</th>
              <th>Caller-ID (MAC)</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($secretsData)): ?>
              <tr>
                <td colspan="8" style="text-align:center; padding:34px 16px !important; color:#71717a;">
                  Belum ada akun PPP Secret terdaftar. Klik <strong>+ Tambah Secret</strong> untuk membuat akun baru.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($secretsData as $row): ?>
                <tr>
                  <td>
                    <div class="lx-row-actions">
                      <?php if ($row['is_isolir']): ?>
                        <form method="post" style="margin:0; display:inline;"
                          data-confirm-type="restore"
                          data-confirm-title="Pulihkan Koneksi Akun?"
                          data-confirm-user="<?= htmlspecialchars($row['name']); ?>"
                          data-confirm-desc="Akun akan dikembalikan ke paket normal dan sesi PPPoE disegarkan."
                          data-confirm-btn="Ya, Pulihkan"
                          onsubmit="return openConfirmModal(event, this);">
                          <input type="hidden" name="ppp_action" value="toggle_isolir">
                          <input type="hidden" name="secret_id" value="<?= htmlspecialchars($row['id']); ?>">
                          <input type="hidden" name="mode" value="restore">
                          <input type="hidden" name="fallback_profile" value="<?= htmlspecialchars($defaultRestoreProfile); ?>">
                          <button type="submit" class="lx-act-pill is-restore" title="Pulihkan paket normal">
                            <i class="fa fa-unlock"></i> Pulihkan
                          </button>
                        </form>
                      <?php else: ?>
                        <form method="post" style="margin:0; display:inline;"
                          data-confirm-type="isolir"
                          data-confirm-title="Isolir Akun PPPoE?"
                          data-confirm-user="<?= htmlspecialchars($row['name']); ?>"
                          data-confirm-desc="Akun akan dipindahkan ke profil ISOLIR dan sesi aktifnya langsung diputus."
                          data-confirm-btn="Ya, Isolir Akun"
                          onsubmit="return openConfirmModal(event, this);">
                          <input type="hidden" name="ppp_action" value="toggle_isolir">
                          <input type="hidden" name="secret_id" value="<?= htmlspecialchars($row['id']); ?>">
                          <input type="hidden" name="mode" value="isolir">
                          <button type="submit" class="lx-act-pill is-isolir" title="Pindahkan ke profil ISOLIR">
                            <i class="fa fa-ban"></i> Isolir
                          </button>
                        </form>
                      <?php endif; ?>

                      <button type="button" class="lx-icon-btn" title="Edit Secret"
                        onclick='openEditSecretModal(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>);'>
                        <i class="fa fa-pencil"></i>
                      </button>

                      <?php if ($row['is_online']): ?>
                        <form method="post" style="margin:0; display:inline;"
                          data-confirm-type="kick"
                          data-confirm-title="Putus Sesi Aktif?"
                          data-confirm-user="<?= htmlspecialchars($row['name']); ?>"
                          data-confirm-desc="Koneksi PPPoE perangkat ini akan diputus dan melakukan dial-up ulang."
                          data-confirm-btn="Putus Sesi"
                          onsubmit="return openConfirmModal(event, this);">
                          <input type="hidden" name="ppp_action" value="kick_active">
                          <input type="hidden" name="username" value="<?= htmlspecialchars($row['name']); ?>">
                          <button type="submit" class="lx-icon-btn" title="Putus Sesi Aktif (Kick)">
                            <i class="fa fa-power-off" style="color:#d97706;"></i>
                          </button>
                        </form>
                      <?php endif; ?>

                      <form method="post" style="margin:0; display:inline;"
                        data-confirm-type="delete"
                        data-confirm-title="Hapus Akun Permanen?"
                        data-confirm-user="<?= htmlspecialchars($row['name']); ?>"
                        data-confirm-desc="Data PPP Secret akan dihapus permanen dari router dan sesi aktifnya diputus."
                        data-confirm-btn="Hapus Akun"
                        onsubmit="return openConfirmModal(event, this);">
                        <input type="hidden" name="ppp_action" value="delete_secret">
                        <input type="hidden" name="secret_id" value="<?= htmlspecialchars($row['id']); ?>">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($row['name']); ?>">
                        <button type="submit" class="lx-icon-btn is-del" title="Hapus Akun">
                          <i class="fa fa-trash-o"></i>
                        </button>
                      </form>
                    </div>
                  </td>

                  <td>
                    <div style="display:flex; align-items:center; gap:7px;">
                      <span class="lx-dot" style="background:<?= $row['is_online'] ? '#16a34a' : '#d4d4d8'; ?>;" title="<?= $row['is_online'] ? 'Online (' . htmlspecialchars($row['active_info']['address']) . ')' : 'Offline'; ?>"></span>
                      <strong style="color:#09090b;"><?= htmlspecialchars($row['name']); ?></strong>
                    </div>
                  </td>

                  <td>
                    <span class="lx-mono"><?= htmlspecialchars($row['password']); ?></span>
                  </td>

                  <td>
                    <span class="lx-prof-badge <?= $row['is_isolir'] ? 'is-isolir' : ''; ?>">
                      <?= htmlspecialchars($row['profile']); ?>
                    </span>
                  </td>

                  <td>
                    <?php if ($row['due_date'] !== ''): ?>
                      <span class="lx-mono"><?= htmlspecialchars($row['due_date'] . ' ' . $row['due_time']); ?></span>
                    <?php else: ?>
                      <span style="color:#a1a1aa;">—</span>
                    <?php endif; ?>
                  </td>

                  <td>
                    <?php
                      $ipDisplay = array();
                      if ($row['local_address'] !== '') $ipDisplay[] = $row['local_address'];
                      if ($row['remote_address'] !== '') $ipDisplay[] = $row['remote_address'];
                      elseif ($row['is_online'] && !empty($row['active_info']['address'])) $ipDisplay[] = $row['active_info']['address'] . ' (Pool)';
                    ?>
                    <?php if (!empty($ipDisplay)): ?>
                      <span class="lx-mono"><?= htmlspecialchars(implode(' → ', $ipDisplay)); ?></span>
                    <?php else: ?>
                      <span style="color:#a1a1aa;">Otomatis (Pool)</span>
                    <?php endif; ?>
                  </td>

                  <td>
                    <?php if ($row['caller_id'] !== ''): ?>
                      <span class="lx-mono"><?= htmlspecialchars($row['caller_id']); ?></span>
                    <?php else: ?>
                      <span style="color:#a1a1aa;">—</span>
                    <?php endif; ?>
                  </td>

                  <td>
                    <?= $row['clean_comment'] !== '' ? htmlspecialchars($row['clean_comment']) : '<span style="color:#a1a1aa;">—</span>'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="lx-footer">
    <div>Smart Kick Aktif &bull; Edit keterangan, jadwal, atau Lock MAC tanpa memutus koneksi ONT</div>
    <div>Total: <?= $totalSecrets; ?> PPP Secrets</div>
  </div>

  <!-- Modal Konfirmasi Elegan (Pengganti Browser Alert) -->
  <div class="lx-modal-backdrop" id="modalConfirmAction" onclick="if(event.target===this) closeConfirmModal();">
    <div class="lx-modal-card is-confirm" role="dialog" aria-modal="true">
      <div class="lx-confirm-body">
        <div class="lx-confirm-icon" id="confirmIconBox">
          <i class="fa fa-ban" id="confirmIconGlyph"></i>
        </div>
        <div>
          <h4 class="lx-confirm-title" id="confirmTitleText">Konfirmasi Tindakan</h4>
          <p class="lx-confirm-desc" id="confirmDescText">Lanjutkan proses ini?</p>
          <span class="lx-confirm-user-tag" id="confirmUserBadge">-</span>
        </div>
      </div>
      <div class="lx-confirm-foot">
        <button type="button" class="lx-btn" id="btnConfirmCancel" onclick="closeConfirmModal();">Batal</button>
        <button type="button" class="lx-btn is-danger" id="btnConfirmProceed" onclick="submitConfirmedForm();">Ya, Lanjutkan</button>
      </div>
    </div>
  </div>

  <!-- Modal Tambah PPP Secret -->
  <div class="lx-modal-backdrop" id="modalAddSecret" onclick="if(event.target===this) closeModal('modalAddSecret');">
    <div class="lx-modal-card">
      <div class="lx-modal-head">
        <h4 class="lx-modal-title">Tambah PPP Secret</h4>
        <button type="button" class="lx-modal-close" onclick="closeModal('modalAddSecret');">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="ppp_action" value="add_secret">
        <div class="lx-modal-body">
          <div class="lx-form-grid">
            <div class="lx-field">
              <label>Username</label>
              <input type="text" name="name" class="lx-input" required placeholder="Contoh: ont-lab-tkj1">
            </div>
            <div class="lx-field">
              <label>Password</label>
              <input type="text" name="password" class="lx-input" required placeholder="Password PPPoE">
            </div>

            <div class="lx-field">
              <label>Service</label>
              <select name="service" class="lx-input">
                <option value="pppoe" selected>pppoe</option>
                <option value="any">any</option>
                <option value="l2tp">l2tp</option>
                <option value="pptp">pptp</option>
                <option value="ovpn">ovpn</option>
                <option value="sstp">sstp</option>
              </select>
            </div>
            <div class="lx-field">
              <label>Profile Paket</label>
              <select name="profile" class="lx-input">
                <?php foreach ($profilesList as $pName): ?>
                  <option value="<?= htmlspecialchars($pName); ?>"><?= htmlspecialchars($pName); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="lx-field">
              <label>Tanggal Jatuh Tempo <span>(Opsional)</span></label>
              <input type="date" name="due_date" class="lx-input">
            </div>
            <div class="lx-field">
              <label>Jam Jatuh Tempo</label>
              <input type="time" name="due_time" class="lx-input" value="23:59">
            </div>

            <div class="lx-field">
              <label>Local Address <span>(Opsional)</span></label>
              <input type="text" name="local_address" class="lx-input" placeholder="Kosongkan untuk pakai Profile">
            </div>
            <div class="lx-field">
              <label>Remote Address <span>(IP Statis ONT)</span></label>
              <input type="text" name="remote_address" class="lx-input" placeholder="Kosongkan untuk pakai Pool">
            </div>

            <div class="lx-field">
              <label>Caller-ID / Lock MAC <span>(Opsional)</span></label>
              <input type="text" name="caller_id" class="lx-input" placeholder="AA:BB:CC:DD:EE:FF">
            </div>
            <div class="lx-field">
              <label>Keterangan <span>(Lokasi / Ruangan)</span></label>
              <input type="text" name="comment" class="lx-input" placeholder="Contoh: ONT Ruang Guru">
            </div>
          </div>
        </div>
        <div class="lx-modal-foot">
          <div class="lx-smart-note">Akun baru langsung aktif dan siap di-dial oleh ONT.</div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="lx-btn" onclick="closeModal('modalAddSecret');">Batal</button>
            <button type="submit" class="lx-btn is-primary"><i class="fa fa-save"></i> Simpan Secret</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit PPP Secret -->
  <div class="lx-modal-backdrop" id="modalEditSecret" onclick="if(event.target===this) closeModal('modalEditSecret');">
    <div class="lx-modal-card">
      <div class="lx-modal-head">
        <h4 class="lx-modal-title">Edit PPP Secret</h4>
        <button type="button" class="lx-modal-close" onclick="closeModal('modalEditSecret');">&times;</button>
      </div>
      <form method="post">
        <input type="hidden" name="ppp_action" value="edit_secret">
        <input type="hidden" name="secret_id" id="editSecretId">
        <div class="lx-modal-body">
          <div class="lx-form-grid">
            <div class="lx-field">
              <label>Username</label>
              <input type="text" name="name" id="editSecretName" class="lx-input" required>
            </div>
            <div class="lx-field">
              <label>Password</label>
              <input type="text" name="password" id="editSecretPass" class="lx-input" required>
            </div>

            <div class="lx-field">
              <label>Service</label>
              <select name="service" id="editSecretService" class="lx-input">
                <option value="pppoe">pppoe</option>
                <option value="any">any</option>
                <option value="l2tp">l2tp</option>
                <option value="pptp">pptp</option>
                <option value="ovpn">ovpn</option>
                <option value="sstp">sstp</option>
              </select>
            </div>
            <div class="lx-field">
              <label>Profile Paket</label>
              <select name="profile" id="editSecretProfile" class="lx-input">
                <?php foreach ($profilesList as $pName): ?>
                  <option value="<?= htmlspecialchars($pName); ?>"><?= htmlspecialchars($pName); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="lx-field">
              <label>Tanggal Jatuh Tempo <span>(Opsional)</span></label>
              <input type="date" name="due_date" id="editSecretDueDate" class="lx-input">
            </div>
            <div class="lx-field">
              <label>Jam Jatuh Tempo</label>
              <input type="time" name="due_time" id="editSecretDueTime" class="lx-input" value="23:59">
            </div>

            <div class="lx-field">
              <label>Local Address <span>(Opsional)</span></label>
              <input type="text" name="local_address" id="editSecretLocal" class="lx-input" placeholder="Kosongkan untuk pakai Profile">
            </div>
            <div class="lx-field">
              <label>Remote Address <span>(IP Statis ONT)</span></label>
              <input type="text" name="remote_address" id="editSecretRemote" class="lx-input" placeholder="Kosongkan untuk pakai Pool">
            </div>

            <div class="lx-field">
              <label>
                Caller-ID / Lock MAC <span>(Opsional)</span>
                <a href="javascript:void(0);" id="btnUseActiveMac" onclick="applyDetectedMac();" class="lx-mac-chip">+ Pakai MAC Online</a>
              </label>
              <input type="text" name="caller_id" id="editSecretCaller" class="lx-input" placeholder="AA:BB:CC:DD:EE:FF">
            </div>
            <div class="lx-field">
              <label>Keterangan <span>(Lokasi / Ruangan)</span></label>
              <input type="text" name="comment" id="editSecretComment" class="lx-input">
            </div>
          </div>
        </div>
        <div class="lx-modal-foot">
          <div class="lx-smart-note">Edit keterangan, jadwal, atau Lock MAC tidak memutus koneksi aktif.</div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="lx-btn" onclick="closeModal('modalEditSecret');">Batal</button>
            <button type="submit" class="lx-btn is-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  var detectedOnlineMac = '';
  var pendingConfirmForm = null;

  function openConfirmModal(e, formEl) {
    if (e) e.preventDefault();
    pendingConfirmForm = formEl;

    var type  = formEl.getAttribute('data-confirm-type') || 'isolir';
    var title = formEl.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
    var user  = formEl.getAttribute('data-confirm-user') || '-';
    var desc  = formEl.getAttribute('data-confirm-desc') || '';
    var btnTx = formEl.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';

    var iconBox   = document.getElementById('confirmIconBox');
    var iconGlyph = document.getElementById('confirmIconGlyph');
    var btnOk     = document.getElementById('btnConfirmProceed');

    iconBox.className = 'lx-confirm-icon';
    btnOk.className   = 'lx-btn';
    btnOk.disabled    = false;

    if (type === 'kick') {
      iconBox.classList.add('is-warning');
      iconGlyph.className = 'fa fa-power-off';
      btnOk.classList.add('is-warning');
    } else if (type === 'restore') {
      iconBox.classList.add('is-restore');
      iconGlyph.className = 'fa fa-unlock';
      btnOk.classList.add('is-restore-solid');
    } else if (type === 'delete') {
      iconGlyph.className = 'fa fa-trash-o';
      btnOk.classList.add('is-danger');
    } else {
      iconGlyph.className = 'fa fa-ban';
      btnOk.classList.add('is-danger');
    }

    document.getElementById('confirmTitleText').textContent = title;
    document.getElementById('confirmDescText').textContent  = desc;
    document.getElementById('confirmUserBadge').textContent = user;
    btnOk.textContent = btnTx;

    document.getElementById('modalConfirmAction').classList.add('is-open');
    return false;
  }

  function closeConfirmModal() {
    document.getElementById('modalConfirmAction').classList.remove('is-open');
    pendingConfirmForm = null;
  }

  function submitConfirmedForm() {
    if (!pendingConfirmForm) return;
    var btnOk = document.getElementById('btnConfirmProceed');
    btnOk.disabled = true;
    btnOk.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i> Memproses...';
    pendingConfirmForm.submit();
  }

  function dismissToastBanner() {
    var toast = document.getElementById('lxToastBanner');
    if (!toast) return;
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(12px)';
    setTimeout(function() {
      if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
    }, 220);
  }

  if (document.getElementById('lxToastBanner')) {
    setTimeout(dismissToastBanner, 4200);
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeConfirmModal();
      closeModal('modalAddSecret');
      closeModal('modalEditSecret');
    }
  });

  function openAddSecretModal() {
    document.getElementById('modalAddSecret').classList.add('is-open');
  }

  function openEditSecretModal(data) {
    document.getElementById('editSecretId').value      = data.id || '';
    document.getElementById('editSecretName').value    = data.name || '';
    document.getElementById('editSecretPass').value    = data.password || '';
    document.getElementById('editSecretService').value = data.service || 'pppoe';
    document.getElementById('editSecretProfile').value = data.profile || 'default';
    document.getElementById('editSecretDueDate').value = data.due_date || '';
    document.getElementById('editSecretDueTime').value = data.due_time || '23:59';
    document.getElementById('editSecretLocal').value   = data.local_address || '';
    document.getElementById('editSecretRemote').value  = data.remote_address || '';
    document.getElementById('editSecretCaller').value  = data.caller_id || '';
    document.getElementById('editSecretComment').value = data.clean_comment || '';

    var btnMac = document.getElementById('btnUseActiveMac');
    if (data.is_online && data.active_info && data.active_info.caller_id) {
      detectedOnlineMac = data.active_info.caller_id;
      btnMac.style.display = 'inline-block';
      btnMac.title = 'Isi otomatis MAC aktif: ' + detectedOnlineMac;
    } else {
      detectedOnlineMac = '';
      btnMac.style.display = 'none';
    }

    document.getElementById('modalEditSecret').classList.add('is-open');
  }

  function applyDetectedMac() {
    if (detectedOnlineMac !== '') {
      document.getElementById('editSecretCaller').value = detectedOnlineMac;
    }
  }

  function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('is-open');
  }

  function filterSecretsTable() {
    var q = (document.getElementById('secretSearchInput').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#pppSecretsTable tbody tr');
    for (var i = 0; i < rows.length; i++) {
      var txt = rows[i].textContent.toLowerCase();
      rows[i].style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
    }
  }

  function exportSecretsCsv() {
    var table = document.getElementById('pppSecretsTable');
    if (!table) return;
    var csv = [];
    var rows = table.querySelectorAll('tr');
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].style.display === 'none') continue;
      var cols = rows[i].querySelectorAll('th, td');
      var rowData = [];
      for (var j = 1; j < cols.length; j++) {
        var val = cols[j].innerText.replace(/\s+/g, ' ').trim();
        rowData.push('"' + val.replace(/"/g, '""') + '"');
      }
      csv.push(rowData.join(','));
    }
    var csvUri = 'data:text/csv;charset=utf-8,' + encodeURI(csv.join('\n'));
    var a = document.createElement('a');
    a.href = csvUri;
    a.download = 'ppp-secrets-<?= htmlspecialchars($session); ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }
</script>