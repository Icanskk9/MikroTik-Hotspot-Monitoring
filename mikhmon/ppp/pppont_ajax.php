<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["mikhmon"])) {
  echo json_encode(array("ok" => false, "error" => "Unauthorized"));
  exit;
}

$session = isset($_GET['session']) ? $_GET['session'] : '';
if ($session == "") {
  echo json_encode(array("ok" => false, "error" => "Session missing"));
  exit;
}

include_once('../include/config.php');
include_once('../include/readcfg.php');
include_once('../lib/routeros_api.class.php');

function parseSecretNoteAjax($comment) {
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

function parseRouterOsRttMs($timeStr) {
  $str = trim((string)$timeStr);
  if ($str === '') return null;

  // Format MikroTik v7: "2ms145us" atau "1s200ms"
  if (preg_match('/^(?:(\d+)s)?(?:(\d+)ms)?(?:(\d+)us)?$/i', $str, $m)) {
    if ($m[1] !== '' || $m[2] !== '' || $m[3] !== '') {
      $sec = ($m[1] !== '') ? (float)$m[1] * 1000.0 : 0.0;
      $ms  = ($m[2] !== '') ? (float)$m[2] : 0.0;
      $us  = ($m[3] !== '') ? (float)$m[3] / 1000.0 : 0.0;
      return $sec + $ms + $us;
    }
  }

  // Format waktu "00:00:00.003"
  if (preg_match('/(\d+):(\d+):(\d+(?:\.\d+)?)/', $str, $mt)) {
    return ((float)$mt[1] * 3600 + (float)$mt[2] * 60 + (float)$mt[3]) * 1000.0;
  }

  // Fallback angka biasa
  if (is_numeric($str)) {
    return (float)$str;
  }

  return null;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'status';

$API = new RouterosAPI();
$API->debug = false;

if (!$API->connect($iphost, $userhost, decrypt($passwdhost))) {
  echo json_encode(array("ok" => false, "error" => "Gagal terhubung ke MikroTik API"));
  exit;
}

// MODE 1: UJI PING KE IP ONT
if ($action === 'ping') {
  $ip = isset($_GET['ip']) ? trim($_GET['ip']) : '';
  $count = isset($_GET['count']) ? intval($_GET['count']) : 3;
  if ($count < 1) $count = 1;
  if ($count > 5) $count = 5;

  if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $API->disconnect();
    echo json_encode(array("ok" => false, "error" => "Alamat IPv4 ONT tidak valid"));
    exit;
  }

  $pingRes = $API->comm("/ping", array(
    "address" => $ip,
    "count" => (string)$count,
    "interval" => "250ms",
  ));
  $API->disconnect();

  $sent = 0;
  $received = 0;
  $rtts = array();
  $lastErr = "Request timed out";

  if (is_array($pingRes)) {
    foreach ($pingRes as $pkt) {
      if (!isset($pkt['seq']) && !isset($pkt['host']) && !isset($pkt['status']) && !isset($pkt['time'])) {
        continue;
      }
      $sent++;
      $status = isset($pkt['status']) ? strtolower(trim($pkt['status'])) : '';
      $timeRaw = isset($pkt['time']) ? trim($pkt['time']) : '';

      if ($timeRaw !== '' && $status === '') {
        $received++;
        $ms = parseRouterOsRttMs($timeRaw);
        if ($ms !== null) {
          $rtts[] = $ms;
        }
      } elseif ($status !== '') {
        $lastErr = ucfirst($status);
      }
    }
  }

  if ($sent === 0) {
    $sent = $count;
  }

  $loss = ($sent > 0) ? (int)round((($sent - $received) / $sent) * 100) : 100;
  $avgMs = (count($rtts) > 0) ? round(array_sum($rtts) / count($rtts), 1) : null;
  $minMs = (count($rtts) > 0) ? round(min($rtts), 1) : null;
  $maxMs = (count($rtts) > 0) ? round(max($rtts), 1) : null;

  $health = 'good';
  $note = 'Respon normal & stabil';

  if ($received === 0) {
    $health = 'rto';
    $note = $lastErr . ' (ONT mati / putus jalur FO)';
  } elseif ($loss > 0) {
    $health = 'loss';
    $note = 'Packet loss ' . $loss . '% (cek redaman FO / konektor)';
  } elseif ($avgMs !== null && $avgMs > 30.0) {
    $health = 'warn';
    $note = 'Latency tinggi >30ms (cek trafik / redaman PON)';
  } elseif ($avgMs !== null && $avgMs > 15.0) {
    $health = 'warn';
    $note = 'Latency agak tinggi >15ms';
  }

  echo json_encode(array(
    "ok" => true,
    "ip" => $ip,
    "sent" => $sent,
    "received" => $received,
    "loss" => $loss,
    "avg_ms" => $avgMs,
    "min_ms" => $minMs,
    "max_ms" => $maxMs,
    "health" => $health,
    "note" => $note,
  ));
  exit;
}

// MODE 2: REFRESH DAFTAR STATUS ONT (SECRETS + ACTIVE)
$getsecrets = $API->comm("/ppp/secret/print");
$getactive  = $API->comm("/ppp/active/print");
$API->disconnect();

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

$items = array();
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
  $meta = parseSecretNoteAjax(isset($sec['comment']) ? $sec['comment'] : '');

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

  $items[] = array(
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

echo json_encode(array(
  "ok" => true,
  "stats" => array(
    "total" => count($items),
    "online" => $totalOnline,
    "offline" => $totalOffline,
    "with_ip" => $totalWithIp,
  ),
  "items" => $items,
));
exit;