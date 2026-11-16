<?php
session_start();
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION["mikhmon"])) {
  echo json_encode(array("ok" => false, "error" => "Unauthorized"));
  exit;
}

$session = isset($_GET['session']) ?$_GET['session'] : '';
if ($session == "") {
  echo json_encode(array("ok" => false, "error" => "Session missing"));
  exit;
}

include_once('../include/config.php');
include_once('../include/readcfg.php');
include_once('../lib/routeros_api.class.php');

$API = new RouterosAPI();$API->debug = false;

if (!$API->connect($iphost, $userhost, decrypt($passwdhost))) {
  echo json_encode(array("ok" => false, "error" => "Gagal terhubung ke MikroTik API"));
  exit;
}

function formatBps($bits) {
  $bits = (float)$bits;
  if ($bits <= 0) return "0 bps";
  $units = array("bps", "Kbps", "Mbps", "Gbps");
  $i = 0;
  while ($bits >= 1000 &&$i < count($units) - 1) {$bits /= 1000;
    $i++;
  }
  return round($bits, ($i >= 2 ? 2 : 1)) . " " . $units[$i];
}

$mode = isset($_GET['mode']) ?$_GET['mode'] : 'traffic';

// MODE 1: Live Traffic Monitor semua klien aktif
if ($mode === 'traffic') {
  $actives =$API->comm("/ppp/active/print");
  $data = array();$totalDownBps = 0;
  $totalUpBps = 0;

  for ($i = 0; $i < count($actives); $i++) {$uname = isset($actives[$i]['name']) ? $actives[$i]['name'] : "";
    if ($uname == "") continue;

    $ifaceName = "<pppoe-" . $uname . ">";
    $mon =$API->comm("/interface/monitor-traffic", array(
      "interface" => $ifaceName,
      "once" => "",
    ));

    // Pada interface PPPoE di sisi server MikroTik:
    // tx-bits-per-second = trafik keluar ke klien (Download klien)
    // rx-bits-per-second = trafik masuk dari klien (Upload klien)
    $downBps = isset($mon[0]['tx-bits-per-second']) ? (float)$mon[0]['tx-bits-per-second'] : 0;
    $upBps   = isset($mon[0]['rx-bits-per-second']) ? (float)$mon[0]['rx-bits-per-second'] : 0;

    $totalDownBps +=$downBps;
    $totalUpBps   +=$upBps;

    $data[$uname] = array(
      "down_bps"  => $downBps,
      "up_bps"    => $upBps,
      "down_text" => formatBps($downBps),
      "up_text"   => formatBps($upBps),
    );
  }

  $API->disconnect();
  echo json_encode(array(
    "ok" => true,
    "total_active" => count($actives),
    "total_down_text" => formatBps($totalDownBps),
    "total_up_text"   => formatBps($totalUpBps),
    "users" => $data,
  ));
  exit;
}

// MODE 2: Tes Ping dari MikroTik + Cek Port Web ONT (80 & 8080)
if ($mode === 'probe') {$targetIp = isset($_GET['ip']) ? trim($_GET['ip']) : "";
  if (!filter_var($targetIp, FILTER_VALIDATE_IP)) {$API->disconnect();
    echo json_encode(array("ok" => false, "error" => "Alamat IP tidak valid"));
    exit;
  }

  // Ping 3 paket dari MikroTik ke IP ONT
  $pingRes =$API->comm("/ping", array(
    "address" => $targetIp,
    "count" => "3",
    "interval" => "200ms",
  ));
  $API->disconnect();

  $sent = count($pingRes);$received = 0;
  $lastTime = "-";
  for ($p = 0; $p < $sent; $p++) {
    if (isset($pingRes[$p]['received']) && (int)$pingRes[$p]['received'] > 0) {$received++;
      if (isset($pingRes[$p]['time']))$lastTime = $pingRes[$p]['time'];
    } elseif (isset($pingRes[$p]['time']) && $pingRes[$p]['time'] != "") {
      $received++;$lastTime = $pingRes[$p]['time'];
    }
  }

  // Cek kesiapan port HTTP 80 & 8080 di sisi ONT dari server Mikhmon
  $port80Open = false;
  $port8080Open = false;

  $fp80 = @fsockopen($targetIp, 80, $errno80,$errstr80, 1.2);
  if ($fp80) {$port80Open = true;
    fclose($fp80);
  }

  $fp8080 = @fsockopen($targetIp, 8080, $errno8080,$errstr8080, 1.2);
  if ($fp8080) {$port8080Open = true;
    fclose($fp8080);
  }

  echo json_encode(array(
    "ok" => true,
    "ip" => $targetIp,
    "ping_ok" => ($received > 0),
    "ping_sent" => ($sent > 0 ?$sent : 3),
    "ping_recv" => $received,
    "ping_time" => $lastTime,
    "port_80" => $port80Open,
    "port_8080" => $port8080Open,
  ));
  exit;
}

$API->disconnect();
echo json_encode(array("ok" => false, "error" => "Unknown mode"));