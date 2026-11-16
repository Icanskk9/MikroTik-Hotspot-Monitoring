<?php
session_start();
error_reporting(0);
header('Content-Type: application/json');

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

$action = isset($_GET['action']) ? $_GET['action'] : 'scan';

// Kumpulkan daftar interface WAN & IP Gateway utama agar tidak pernah salah blokir sumber internet
function getWanInfo($API) {
  $wanIfaces = array();
  $gatewayIps = array();

  $dhcpClients = $API->comm("/ip/dhcp-client/print");
  for ($i = 0; $i < count($dhcpClients); $i++) {
    if (!empty($dhcpClients[$i]['interface'])) {
      $wanIfaces[] = $dhcpClients[$i]['interface'];
    }
    if (!empty($dhcpClients[$i]['gateway'])) {
      $gatewayIps[] = $dhcpClients[$i]['gateway'];
    }
  }

  $routes = $API->comm("/ip/route/print");
  for ($r = 0; $r < count($routes); $r++) {
    $dst = isset($routes[$r]['dst-address']) ? $routes[$r]['dst-address'] : "";
    if ($dst === "0.0.0.0/0") {
      if (!empty($routes[$r]['gateway'])) {
        $gw = preg_replace('/%.*/', '', $routes[$r]['gateway']);
        if (filter_var($gw, FILTER_VALIDATE_IP)) {
          $gatewayIps[] = $gw;
        }
      }
      if (!empty($routes[$r]['interface'])) {
        $wanIfaces[] = $routes[$r]['interface'];
      }
    }
  }

  return array(
    "interfaces" => array_unique($wanIfaces),
    "gateways" => array_unique($gatewayIps),
  );
}

// MODE 1: SCAN ANCAMAN LOKAL (ROGUE DHCP & ARP SPOOFING / NETCUT)
if ($action === 'scan') {
  $wanInfo = getWanInfo($API);
  $wanIfaces = $wanInfo['interfaces'];
  $gatewayIps = $wanInfo['gateways'];

  // Ambil IP lokal milik MikroTik sendiri (untuk deteksi jika ada yang memalsukan IP gateway sekolah)
  $routerLocalIps = array();
  $ipAddrs = $API->comm("/ip/address/print");
  for ($ia = 0; $ia < count($ipAddrs); $ia++) {
    $ifn = isset($ipAddrs[$ia]['interface']) ? $ipAddrs[$ia]['interface'] : "";
    if (!in_array($ifn, $wanIfaces) && !empty($ipAddrs[$ia]['address'])) {
      $ipOnly = explode('/', $ipAddrs[$ia]['address'])[0];
      $routerLocalIps[$ipOnly] = $ifn;
    }
  }

  // Ambil daftar interface yang menjalankan DHCP Server lokal
  $dhcpServers = $API->comm("/ip/dhcp-server/print");
  $dhcpIfaces = array();
  for ($ds = 0; $ds < count($dhcpServers); $ds++) {
    $dif = isset($dhcpServers[$ds]['interface']) ? $dhcpServers[$ds]['interface'] : "";
    if ($dif != "" && !in_array($dif, $wanIfaces)) {
      $dhcpIfaces[] = $dif;
    }
  }

  // Ambil DHCP Server Alerts (/ip/dhcp-server/alert)
  $alerts = $API->comm("/ip/dhcp-server/alert/print");
  $sensorActive = (count($alerts) > 0);

  // Ambil DHCP Leases resmi
  $leases = $API->comm("/ip/dhcp-server/lease/print");
  $officialMacs = array();
  for ($l = 0; $l < count($leases); $l++) {
    if (!empty($leases[$l]['mac-address'])) {
      $officialMacs[strtoupper($leases[$l]['mac-address'])] = true;
    }
  }

  // Ambil daftar MAC yang sedang dikarantina
  $quarantinedMacs = array();
  $filters = $API->comm("/ip/firewall/filter/print");
  for ($f = 0; $f < count($filters); $f++) {
    $comm = isset($filters[$f]['comment']) ? $filters[$f]['comment'] : "";
    if (strpos($comm, "Mikhmon-Quarantine: ") === 0) {
      $qMac = strtoupper(trim(str_replace("Mikhmon-Quarantine: ", "", $comm)));
      if ($qMac != "") {
        $quarantinedMacs[$qMac] = true;
      }
    }
  }

  // Analisis Tabel ARP khusus jaringan lokal (Non-WAN)
  $arps = $API->comm("/ip/arp/print");
  $macIpMap = array();
  $lanArps = array();

  for ($a = 0; $a < count($arps); $a++) {
    $arp = $arps[$a];
    $mac = isset($arp['mac-address']) ? strtoupper(trim($arp['mac-address'])) : "";
    $ip = isset($arp['address']) ? trim($arp['address']) : "";
    $iface = isset($arp['interface']) ? trim($arp['interface']) : "";
    $isDynamic = (!isset($arp['dynamic']) || $arp['dynamic'] === "true");

    // Abaikan port WAN (sumber internet), gateway ISP, ARP statis admin, dan PPPoE virtual
    if ($mac === "" || $ip === "") continue;
    if (in_array($iface, $wanIfaces)) continue;
    if (in_array($ip, $gatewayIps)) continue;
    if (strpos($iface, "<pppoe-") === 0) continue;
    if (!$isDynamic) continue;

    $lanArps[] = $arp;
    if (!isset($macIpMap[$mac])) $macIpMap[$mac] = array();
    $macIpMap[$mac][] = array("ip" => $ip, "interface" => $iface);
  }

  $threats = array();
  $flaggedMacs = array();

  // 1. Masukkan Rogue DHCP Server yang tertangkap sensor RouterOS
  for ($al = 0; $al < count($alerts); $al++) {
    $aItem = $alerts[$al];
    $unkMac = isset($aItem['unknown-server']) ? strtoupper(trim($aItem['unknown-server'])) : "";
    $aIface = isset($aItem['interface']) ? $aItem['interface'] : "-";
    if ($unkMac != "" && !in_array($aIface, $wanIfaces)) {
      $flaggedMacs[$unkMac] = true;
      $threats[] = array(
        "mac" => $unkMac,
        "ip" => "DHCP Server Palsu",
        "interface" => $aIface,
        "severity" => "high",
        "reason" => "Rogue DHCP Terdeteksi (Router/AP Pribadi Salah Colok)",
        "quarantined" => isset($quarantinedMacs[$unkMac]),
      );
    }
  }

  // 2. Deteksi ARP Spoofing / NetCut (1 MAC memalsukan IP Gateway atau mengkloning banyak IP)
  foreach ($macIpMap as $mac => $entries) {
    if (isset($flaggedMacs[$mac])) continue;

    $claimedGateway = false;
    $sampleIp = $entries[0]['ip'];
    $sampleIface = $entries[0]['interface'];

    foreach ($entries as $ent) {
      if (isset($routerLocalIps[$ent['ip']])) {
        $claimedGateway = true;
        $sampleIp = $ent['ip'];
        $sampleIface = $ent['interface'];
        break;
      }
    }

    if ($claimedGateway) {
      $flaggedMacs[$mac] = true;
      $threats[] = array(
        "mac" => $mac,
        "ip" => $sampleIp,
        "interface" => $sampleIface,
        "severity" => "high",
        "reason" => "ARP Spoofing / NetCut (Memalsukan IP Gateway Sekolah)",
        "quarantined" => isset($quarantinedMacs[$mac]),
      );
    } elseif (count($entries) >= 3) {
      $flaggedMacs[$mac] = true;
      $threats[] = array(
        "mac" => $mac,
        "ip" => $sampleIp . " (+" . (count($entries) - 1) . " IP)",
        "interface" => $sampleIface,
        "severity" => "high",
        "reason" => "Indikasi NetCut / ARP Poisoning (1 MAC Klaim Banyak IP)",
        "quarantined" => isset($quarantinedMacs[$mac]),
      );
    } elseif (!isset($officialMacs[$mac]) && in_array($sampleIface, $dhcpIfaces)) {
      $flaggedMacs[$mac] = true;
      $threats[] = array(
        "mac" => $mac,
        "ip" => $sampleIp,
        "interface" => $sampleIface,
        "severity" => "warn",
        "reason" => "IP Statis Liar di Jalur DHCP (Tidak Terdaftar di Lease)",
        "quarantined" => isset($quarantinedMacs[$mac]),
      );
    }
  }

  $API->disconnect();
  echo json_encode(array(
    "ok" => true,
    "wan_excluded" => $wanIfaces,
    "sensor_active" => $sensorActive,
    "threats" => $threats,
    "quarantined_list" => array_keys($quarantinedMacs),
  ));
  exit;
}

// MODE 2: AKTIFKAN SENSOR ROGUE DHCP ALERT DI INTERFACE LOKAL (LAN/BRIDGE)
if ($action === 'enable_alert') {
  $wanInfo = getWanInfo($API);
  $wanIfaces = $wanInfo['interfaces'];

  $dhcpServers = $API->comm("/ip/dhcp-server/print");
  $existingAlerts = $API->comm("/ip/dhcp-server/alert/print");
  $alertIfaces = array();
  for ($ea = 0; $ea < count($existingAlerts); $ea++) {
    if (!empty($existingAlerts[$ea]['interface'])) {
      $alertIfaces[$existingAlerts[$ea]['interface']] = true;
    }
  }

  $addedCount = 0;
  for ($ds = 0; $ds < count($dhcpServers); $ds++) {
    $dif = isset($dhcpServers[$ds]['interface']) ? $dhcpServers[$ds]['interface'] : "";
    if ($dif != "" && !in_array($dif, $wanIfaces) && !isset($alertIfaces[$dif])) {
      $ifaceInfo = $API->comm("/interface/print", array("?name" => $dif));
      $validMac = isset($ifaceInfo[0]['mac-address']) ? $ifaceInfo[0]['mac-address'] : "";

      $params = array(
        "interface" => $dif,
        "alert-timeout" => "00:10:00",
        "disabled" => "no",
        "comment" => "Mikhmon: Rogue DHCP Sensor",
      );
      if ($validMac != "") {
        $params["valid-server"] = $validMac;
      }
      $API->comm("/ip/dhcp-server/alert/add", $params);
      $addedCount++;
    }
  }

  $API->disconnect();
  echo json_encode(array("ok" => true, "added" => $addedCount));
  exit;
}

// MODE 3: KARANTINA MAC ADDRESS (BLOKIR VIA FIREWALL FILTER)
if ($action === 'quarantine') {
  $targetMac = isset($_GET['mac']) ? strtoupper(trim($_GET['mac'])) : "";
  if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $targetMac)) {
    $API->disconnect();
    echo json_encode(array("ok" => false, "error" => "Format MAC Address tidak valid"));
    exit;
  }

  $commFwd = "Mikhmon-Quarantine: " . $targetMac;
  $commIn  = "Mikhmon-Quarantine-In: " . $targetMac;

  $chk = $API->comm("/ip/firewall/filter/print", array("?comment" => $commFwd));
  if (!isset($chk[0]['.id'])) {
    $filters = $API->comm("/ip/firewall/filter/print");
    $topId = isset($filters[0]['.id']) ? $filters[0]['.id'] : "";

    $pFwd = array(
      "chain" => "forward",
      "src-mac-address" => $targetMac,
      "action" => "drop",
      "comment" => $commFwd,
      "disabled" => "no",
    );
    $pIn = array(
      "chain" => "input",
      "src-mac-address" => $targetMac,
      "action" => "drop",
      "comment" => $commIn,
      "disabled" => "no",
    );
    if ($topId != "") {
      $pFwd["place-before"] = $topId;
      $pIn["place-before"] = $topId;
    }
    $API->comm("/ip/firewall/filter/add", $pFwd);
    $API->comm("/ip/firewall/filter/add", $pIn);
  }

  $API->disconnect();
  echo json_encode(array("ok" => true));
  exit;
}

// MODE 4: LEPAS KARANTINA MAC ADDRESS
if ($action === 'unquarantine') {
  $targetMac = isset($_GET['mac']) ? strtoupper(trim($_GET['mac'])) : "";
  $commFwd = "Mikhmon-Quarantine: " . $targetMac;
  $commIn  = "Mikhmon-Quarantine-In: " . $targetMac;

  $r1 = $API->comm("/ip/firewall/filter/print", array("?comment" => $commFwd));
  for ($i = 0; $i < count($r1); $i++) {
    if (isset($r1[$i]['.id'])) $API->comm("/ip/firewall/filter/remove", array(".id" => $r1[$i]['.id']));
  }

  $r2 = $API->comm("/ip/firewall/filter/print", array("?comment" => $commIn));
  for ($i = 0; $i < count($r2); $i++) {
    if (isset($r2[$i]['.id'])) $API->comm("/ip/firewall/filter/remove", array(".id" => $r2[$i]['.id']));
  }

  $API->disconnect();
  echo json_encode(array("ok" => true));
  exit;
}

$API->disconnect();
echo json_encode(array("ok" => false, "error" => "Unknown action"));