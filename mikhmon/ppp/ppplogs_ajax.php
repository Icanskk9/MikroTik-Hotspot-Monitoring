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

$API = new RouterosAPI();
$API->debug = false;

if (!$API->connect($iphost, $userhost, decrypt($passwdhost))) {
  echo json_encode(array("ok" => false, "error" => "Gagal terhubung ke MikroTik API"));
  exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Mode Clear: Kosongkan buffer log memori MikroTik lalu kembalikan ke 1000 baris
if ($action === 'clear') {
  $actions = $API->comm("/system/logging/action/print", array("?name" => "memory"));
  if (!empty($actions[0]['.id'])) {
    $memId = $actions[0]['.id'];
    $API->comm("/system/logging/action/set", array(".id" => $memId, "memory-lines" => "1"));
    $API->comm("/system/logging/action/set", array(".id" => $memId, "memory-lines" => "1000"));
  }
  $API->disconnect();
  echo json_encode(array("ok" => true));
  exit;
}

// Mode List: Ambil riwayat log PPPoE dari MikroTik
$rawLogs = $API->comm("/log/print");
$API->disconnect();

$filtered = array();
if (is_array($rawLogs)) {
  foreach ($rawLogs as $entry) {
    $topics = isset($entry['topics']) ? (string)$entry['topics'] : '';
    $message = isset($entry['message']) ? (string)$entry['message'] : '';
    $time = isset($entry['time']) ? (string)$entry['time'] : '';

    if (stripos($topics, 'pppoe') !== false || stripos($topics, 'ppp') !== false) {
      $filtered[] = array(
        "time" => $time,
        "topics" => $topics,
        "message" => $message,
      );
    }
  }
}

// Ambil maksimal 250 aktivitas terbaru dan urutkan dari yang paling baru
$recentLogs = array_reverse(array_slice($filtered, -250));

$logsOutput = array();
$countConnect = 0;
$countDisconnect = 0;
$countError = 0;

$disconnectCounter = array();
$lastDisconnectTime = array();

foreach ($recentLogs as $row) {
  $topics = $row['topics'];
  $msg = $row['message'];
  $time = $row['time'];

  // Ekstrak username dari pesan log RouterOS
  $username = '';
  if (preg_match('/<pppoe-([^>]+)>/', $msg, $m)) {
    $username = trim($m[1]);
  } elseif (preg_match('/^([^\s:]+)\s+logged\s+(?:in|out)/i', $msg, $m)) {
    $username = trim($m[1]);
  } elseif (preg_match('/user\s+([^\s]+)\s+authentication\s+failed/i', $msg, $m)) {
    $username = trim($m[1]);
  }

  // Klasifikasi jenis aktivitas log
  $type = 'info';
  if (
    stripos($topics, 'error') !== false ||
    stripos($msg, 'authentication failed') !== false ||
    stripos($msg, 'failed') !== false ||
    stripos($msg, 'rejected') !== false
  ) {
    $type = 'error';
    $countError++;
  } elseif (
    stripos($msg, 'logged in') !== false ||
    stripos($msg, 'authenticated') !== false ||
    stripos($msg, ': connected') !== false
  ) {
    $type = 'connect';
    $countConnect++;
  } elseif (
    stripos($msg, 'logged out') !== false ||
    stripos($msg, ': disconnected') !== false
  ) {
    $type = 'disconnect';
    $countDisconnect++;

    if ($username !== '' && stripos($msg, ': disconnected') !== false) {
      if (!isset($disconnectCounter[$username])) {
        $disconnectCounter[$username] = 0;
        $lastDisconnectTime[$username] = $time;
      }
      $disconnectCounter[$username]++;
    }
  }

  $logsOutput[] = array(
    "time" => $time,
    "type" => $type,
    "username" => $username,
    "topics" => $topics,
    "message" => $msg,
  );
}

// Deteksi akun yang mengalami putus-nyambung berulang (>= 3x putus)
$flappingList = array();
arsort($disconnectCounter);
foreach ($disconnectCounter as $uname => $cnt) {
  if ($cnt >= 3) {
    $flappingList[] = array(
      "username" => $uname,
      "count" => $cnt,
      "hint" => "Putus-nyambung berulang (cek kabel & ONT)",
      "last_time" => isset($lastDisconnectTime[$uname]) ? $lastDisconnectTime[$uname] : '-',
    );
  }
}

echo json_encode(array(
  "ok" => true,
  "stats" => array(
    "total" => count($logsOutput),
    "connect" => $countConnect,
    "disconnect" => $countDisconnect,
    "error" => $countError,
  ),
  "flapping" => $flappingList,
  "logs" => $logsOutput,
));
exit;