<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  // Helper CIDR calculation
  function mhm_parse_cidr($cidr) {
    if (!preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/([1-9]|[12]\d|30)$/', trim($cidr), $m)) {
      return false;
    }
    $ipLong = ip2long($m[1]);
    if ($ipLong === false) return false;
    $prefix = intval($m[2]);
    $maskLong = -1 << (32 - $prefix);
    $netLong = $ipLong & $maskLong;
    $bcastLong = $netLong | (~$maskLong);

    $gateway = $m[1];
    $network = long2ip($netLong);
    $netCidr = $network . "/" . $prefix;

    // Range pool menghindari gateway IP
    $firstHost = $netLong + 1;
    $lastHost = $bcastLong - 1;
    if ($ipLong == $firstHost) {
      $poolStart = long2ip($firstHost + 1);
      $poolEnd = long2ip($lastHost);
    } elseif ($ipLong == $lastHost) {
      $poolStart = long2ip($firstHost);
      $poolEnd = long2ip($lastHost - 1);
    } else {
      $poolStart = long2ip($firstHost);
      $poolEnd = long2ip($lastHost);
    }

    return array(
      "gateway" => $gateway,
      "prefix" => $prefix,
      "network" => $network,
      "net_cidr" => $netCidr,
      "pool_range" => $poolStart . "-" . $poolEnd
    );
  }

  $setupErr = "";
  $setupSteps = array();
  $infoBanner = "";

  if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "ip_added") {
      $infoBanner = "IP Address baru berhasil disimpan! Lanjutkan klik Jalankan Hotspot Setup di bawah ini.";
    } elseif ($_GET['msg'] == "hs_toggled") {
      $infoBanner = "Status Hotspot Server berhasil diperbarui.";
    } elseif ($_GET['msg'] == "hs_deleted") {
      $infoBanner = "Hotspot Server berhasil dihapus.";
    }
  }

  // Aksi cepat pada daftar Hotspot Server: Enable / Disable / Remove
  if (isset($_GET['act']) && isset($_GET['hsid']) && $_GET['hsid'] != "") {
    $act = $_GET['act'];
    $hsid = $_GET['hsid'];
    if ($act == "enable") {
      $API->comm("/ip/hotspot/enable", array(".id" => $hsid));
    } elseif ($act == "disable") {
      $API->comm("/ip/hotspot/disable", array(".id" => $hsid));
    } elseif ($act == "remove") {
      $API->comm("/ip/hotspot/remove", array(".id" => $hsid));
    }
    echo "<script>window.location='./?ip=hotspot-setup&msg=" . ($act == "remove" ? "hs_deleted" : "hs_toggled") . "&session=" . urlencode($session) . "'</script>";
    exit;
  }

  // Ambil data terkini dari RouterOS
  $getifaces = $API->comm("/interface/print");
  $TotalIface = is_array($getifaces) ? count($getifaces) : 0;

  $getipaddr = $API->comm("/ip/address/print");
  $TotalIP = is_array($getipaddr) ? count($getipaddr) : 0;

  $gethotspots = $API->comm("/ip/hotspot/print");
  $TotalHotspot = is_array($gethotspots) ? count($gethotspots) : 0;

  $gethsprofiles = $API->comm("/ip/hotspot/profile/print");
  $TotalHsProf = is_array($gethsprofiles) ? count($gethsprofiles) : 0;

  $getuserprofiles = $API->comm("/ip/hotspot/user/profile/print");
  $TotalUserProf = is_array($getuserprofiles) ? count($getuserprofiles) : 0;

  $getdns = $API->comm("/ip/dns/print");
  $currentDnsServers = (is_array($getdns) && isset($getdns[0]['servers']) && $getdns[0]['servers'] != "") ? $getdns[0]['servers'] : "8.8.8.8,1.1.1.1";

  // Deteksi html-directory default pada router (hotspot atau flash/hotspot)
  $defaultHtmlDir = "hotspot";
  for ($hp = 0; $hp < $TotalHsProf; $hp++) {
    if (isset($gethsprofiles[$hp]['html-directory']) && $gethsprofiles[$hp]['html-directory'] != "") {
      $defaultHtmlDir = $gethsprofiles[$hp]['html-directory'];
      break;
    }
  }

  // Eksekusi Wizard Hotspot Setup
  if (isset($_POST['run_hotspot_setup'])) {
    $iface = trim($_POST['hs_interface']);
    $localCidr = trim($_POST['local_address']);
    $poolRange = trim($_POST['pool_range']);
    $leaseTime = trim($_POST['lease_time']) != "" ? trim($_POST['lease_time']) : "01:00:00";
    $dnsServers = trim($_POST['dns_servers']) != "" ? trim($_POST['dns_servers']) : "8.8.8.8,1.1.1.1";
    $dnsName = trim($_POST['dns_name']);
    $hsName = trim($_POST['hotspot_name']);
    $htmlDir = trim($_POST['html_directory']) != "" ? trim($_POST['html_directory']) : $defaultHtmlDir;
    $enableNat = (isset($_POST['enable_nat']) && $_POST['enable_nat'] == "1");
    $initUser = trim($_POST['init_user']);
    $initPass = trim($_POST['init_pass']);
    $initProfile = trim($_POST['init_profile']) != "" ? trim($_POST['init_profile']) : "default";

    $parsed = mhm_parse_cidr($localCidr);

    if ($iface == "") {
      $setupErr = "Hotspot Interface wajib dipilih.";
    } elseif ($parsed === false) {
      $setupErr = "Format Local Address tidak valid. Gunakan format IP/Prefix seperti 192.168.20.1/24.";
    } elseif ($poolRange == "" || strpos($poolRange, "-") === false) {
      $setupErr = "Format Address Pool tidak valid. Contoh: 192.168.20.2-192.168.20.254";
    } elseif ($dnsName == "" || !preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $dnsName)) {
      $setupErr = "DNS Name portal hotspot harus berupa domain valid (mengandung titik), contoh: hotspot.sekolah.local";
    } else {
      $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $iface);
      if ($hsName == "") $hsName = "hotspot-" . $safeSlug;
      $poolName = "hs-pool-" . $safeSlug;
      $hsProfName = "hsprof-" . $safeSlug;
      $dhcpName = "dhcp-" . $safeSlug;

      // 1. Pastikan IP Address terpasang di interface
      $ipExists = false;
      for ($a = 0; $a < $TotalIP; $a++) {
        if ($getipaddr[$a]['interface'] == $iface && $getipaddr[$a]['address'] == $localCidr) {
          $ipExists = true;
          break;
        }
      }
      if (!$ipExists) {
        $resIp = $API->comm("/ip/address/add", array(
          "address" => $localCidr,
          "interface" => $iface,
          "comment" => "Hotspot " . $iface,
          "disabled" => "no"
        ));
        if (is_array($resIp) && isset($resIp['!trap'][0]['message'])) {
          $setupErr = "Gagal menambahkan IP Address pada " . $iface . ": " . $resIp['!trap'][0]['message'];
        } else {
          $setupSteps[] = "IP Address <strong>" . htmlspecialchars($localCidr) . "</strong> ditambahkan ke interface <strong>" . htmlspecialchars($iface) . "</strong>";
        }
      } else {
        $setupSteps[] = "IP Address <strong>" . htmlspecialchars($localCidr) . "</strong> pada <strong>" . htmlspecialchars($iface) . "</strong> sudah terpasang (digunakan)";
      }

      // 2. Buat atau perbarui IP Pool
      if ($setupErr == "") {
        $getpools = $API->comm("/ip/pool/print");
        $poolId = "";
        if (is_array($getpools)) {
          foreach ($getpools as $p) {
            if (isset($p['name']) && $p['name'] == $poolName) {
              $poolId = $p['.id'];
              break;
            }
          }
        }
        if ($poolId != "") {
          $API->comm("/ip/pool/set", array(".id" => $poolId, "ranges" => $poolRange));
          $setupSteps[] = "IP Pool <strong>" . htmlspecialchars($poolName) . "</strong> diperbarui (" . htmlspecialchars($poolRange) . ")";
        } else {
          $API->comm("/ip/pool/add", array("name" => $poolName, "ranges" => $poolRange));
          $setupSteps[] = "IP Pool <strong>" . htmlspecialchars($poolName) . "</strong> dibuat (" . htmlspecialchars($poolRange) . ")";
        }
      }

      // 3. Buat atau perbarui DHCP Network & DHCP Server
      if ($setupErr == "") {
        $getdhcpnets = $API->comm("/ip/dhcp-server/network/print");
        $netId = "";
        if (is_array($getdhcpnets)) {
          foreach ($getdhcpnets as $dn) {
            if (isset($dn['address']) && $dn['address'] == $parsed['net_cidr']) {
              $netId = $dn['.id'];
              break;
            }
          }
        }
        if ($netId != "") {
          $API->comm("/ip/dhcp-server/network/set", array(
            ".id" => $netId,
            "gateway" => $parsed['gateway'],
            "dns-server" => $dnsServers
          ));
        } else {
          $API->comm("/ip/dhcp-server/network/add", array(
            "address" => $parsed['net_cidr'],
            "gateway" => $parsed['gateway'],
            "dns-server" => $dnsServers,
            "comment" => "Hotspot " . $iface
          ));
        }

        $getdhcps = $API->comm("/ip/dhcp-server/print");
        $dhcpId = "";
        if (is_array($getdhcps)) {
          foreach ($getdhcps as $ds) {
            if ((isset($ds['interface']) && $ds['interface'] == $iface) || (isset($ds['name']) && $ds['name'] == $dhcpName)) {
              $dhcpId = $ds['.id'];
              break;
            }
          }
        }
        if ($dhcpId != "") {
          $API->comm("/ip/dhcp-server/set", array(
            ".id" => $dhcpId,
            "address-pool" => $poolName,
            "lease-time" => $leaseTime,
            "disabled" => "no"
          ));
          $setupSteps[] = "DHCP Server pada <strong>" . htmlspecialchars($iface) . "</strong> diperbarui &amp; diaktifkan";
        } else {
          $API->comm("/ip/dhcp-server/add", array(
            "name" => $dhcpName,
            "interface" => $iface,
            "address-pool" => $poolName,
            "lease-time" => $leaseTime,
            "disabled" => "no"
          ));
          $setupSteps[] = "DHCP Server <strong>" . htmlspecialchars($dhcpName) . "</strong> dibuat pada interface <strong>" . htmlspecialchars($iface) . "</strong>";
        }
      }

      // 4. DNS Remote Requests & NAT Masquerade
      if ($setupErr == "") {
        $dnsSetParams = array("allow-remote-requests" => "yes");
        if (!is_array($getdns) || !isset($getdns[0]['servers']) || trim($getdns[0]['servers']) == "") {
          $dnsSetParams["servers"] = $dnsServers;
        }
        $API->comm("/ip/dns/set", $dnsSetParams);

        if ($enableNat) {
          $getnat = $API->comm("/ip/firewall/nat/print");
          $natExists = false;
          if (is_array($getnat)) {
            foreach ($getnat as $nr) {
              if (isset($nr['chain']) && $nr['chain'] == "srcnat" && isset($nr['action']) && $nr['action'] == "masquerade" && isset($nr['src-address']) && $nr['src-address'] == $parsed['net_cidr']) {
                $natExists = true;
                break;
              }
            }
          }
          if (!$natExists) {
            $API->comm("/ip/firewall/nat/add", array(
              "chain" => "srcnat",
              "action" => "masquerade",
              "src-address" => $parsed['net_cidr'],
              "comment" => "masquerade hotspot " . $iface
            ));
            $setupSteps[] = "Firewall NAT Masquerade ditambahkan untuk segmen <strong>" . htmlspecialchars($parsed['net_cidr']) . "</strong>";
          } else {
            $setupSteps[] = "Firewall NAT Masquerade untuk <strong>" . htmlspecialchars($parsed['net_cidr']) . "</strong> sudah aktif";
          }
        }
      }

      // 5. Hotspot Server Profile
      if ($setupErr == "") {
        $profId = "";
        for ($hp = 0; $hp < $TotalHsProf; $hp++) {
          if (isset($gethsprofiles[$hp]['name']) && $gethsprofiles[$hp]['name'] == $hsProfName) {
            $profId = $gethsprofiles[$hp]['.id'];
            break;
          }
        }
        $profParams = array(
          "hotspot-address" => $parsed['gateway'],
          "dns-name" => $dnsName,
          "html-directory" => $htmlDir,
          "login-by" => "http-chap,http-pap,cookie"
        );
        if ($profId != "") {
          $profParams[".id"] = $profId;
          $API->comm("/ip/hotspot/profile/set", $profParams);
          $setupSteps[] = "Hotspot Server Profile <strong>" . htmlspecialchars($hsProfName) . "</strong> diperbarui (DNS: <strong>" . htmlspecialchars($dnsName) . "</strong>)";
        } else {
          $profParams["name"] = $hsProfName;
          $API->comm("/ip/hotspot/profile/add", $profParams);
          $setupSteps[] = "Hotspot Server Profile <strong>" . htmlspecialchars($hsProfName) . "</strong> dibuat (DNS: <strong>" . htmlspecialchars($dnsName) . "</strong>)";
        }
      }

      // 6. Hotspot Server
      if ($setupErr == "") {
        $existingHsId = "";
        for ($hs = 0; $hs < $TotalHotspot; $hs++) {
          if ((isset($gethotspots[$hs]['interface']) && $gethotspots[$hs]['interface'] == $iface) || (isset($gethotspots[$hs]['name']) && $gethotspots[$hs]['name'] == $hsName)) {
            $existingHsId = $gethotspots[$hs]['.id'];
            break;
          }
        }
        $hsParams = array(
          "interface" => $iface,
          "address-pool" => $poolName,
          "profile" => $hsProfName,
          "addresses-per-mac" => "2",
          "idle-timeout" => "00:05:00",
          "keepalive-timeout" => "none",
          "disabled" => "no"
        );
        if ($existingHsId != "") {
          $hsParams[".id"] = $existingHsId;
          $API->comm("/ip/hotspot/set", $hsParams);
          $setupSteps[] = "Hotspot Server <strong>" . htmlspecialchars($hsName) . "</strong> pada <strong>" . htmlspecialchars($iface) . "</strong> diperbarui &amp; diaktifkan";
        } else {
          $hsParams["name"] = $hsName;
          $resHs = $API->comm("/ip/hotspot/add", $hsParams);
          if (is_array($resHs) && isset($resHs['!trap'][0]['message'])) {
            $setupErr = "Gagal membuat Hotspot Server: " . $resHs['!trap'][0]['message'];
          } else {
            $setupSteps[] = "Hotspot Server <strong>" . htmlspecialchars($hsName) . "</strong> aktif pada interface <strong>" . htmlspecialchars($iface) . "</strong>!";
          }
        }
      }

      // 7. User Hotspot Awal (Opsional)
      if ($setupErr == "" && $initUser != "") {
        $chkUser = $API->comm("/ip/hotspot/user/print", array("?name" => $initUser));
        if (!is_array($chkUser) || count($chkUser) == 0) {
          $API->comm("/ip/hotspot/user/add", array(
            "name" => $initUser,
            "password" => $initPass,
            "profile" => $initProfile,
            "comment" => "Setup Wizard " . $iface
          ));
          $setupSteps[] = "Akun Hotspot awal <strong>" . htmlspecialchars($initUser) . "</strong> berhasil dibuat";
        }
      }

      // Refresh daftar hotspot setelah wizard selesai
      $gethotspots = $API->comm("/ip/hotspot/print");
      $TotalHotspot = is_array($gethotspots) ? count($gethotspots) : 0;
      $gethsprofiles = $API->comm("/ip/hotspot/profile/print");
      $TotalHsProf = is_array($gethsprofiles) ? count($gethsprofiles) : 0;
      $getipaddr = $API->comm("/ip/address/print");
      $TotalIP = is_array($getipaddr) ? count($getipaddr) : 0;
    }
  }

  // Petakan setiap interface -> IP statis yang sudah terpasang
  $ifaceIpMap = array();
  for ($j = 0; $j < $TotalIP; $j++) {
    $rIf = $getipaddr[$j]['interface'];
    $rAddr = $getipaddr[$j]['address'];
    $isDyn = (isset($getipaddr[$j]['dynamic']) && $getipaddr[$j]['dynamic'] == "true");
    if ($rIf != "" && $rAddr != "") {
      if (!isset($ifaceIpMap[$rIf]) || !$isDyn) {
        $ifaceIpMap[$rIf] = $rAddr;
      }
    }
  }

  // Petakan profile name -> dns-name
  $profDnsMap = array();
  for ($hp = 0; $hp < $TotalHsProf; $hp++) {
    $profDnsMap[$gethsprofiles[$hp]['name']] = isset($gethsprofiles[$hp]['dns-name']) ? $gethsprofiles[$hp]['dns-name'] : '';
  }

  // Tentukan default interface terpilih di form
  $reqIface = isset($_GET['iface']) ? trim($_GET['iface']) : (isset($_POST['hs_interface']) ? trim($_POST['hs_interface']) : "");
  $selectedIface = "";
  if ($reqIface != "") {
    $selectedIface = $reqIface;
  } else {
    for ($f = 0; $f < $TotalIface; $f++) {
      if ($getifaces[$f]['name'] == "ether5") {
        $selectedIface = "ether5";
        break;
      }
    }
    if ($selectedIface == "" && $TotalIface > 0) {
      $selectedIface = $getifaces[0]['name'];
    }
  }

  $initialCidr = isset($ifaceIpMap[$selectedIface]) ? $ifaceIpMap[$selectedIface] : "192.168.20.1/24";
  $initialParsed = mhm_parse_cidr($initialCidr);
  $initialPool = $initialParsed ? $initialParsed['pool_range'] : "192.168.20.2-192.168.20.254";
  $initialHsName = "hotspot-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $selectedIface);
}
?>

<style>
  #mhm-hotspotsetup {
    display: flex;
    flex-direction: column;
    gap: 18px;
    color: #18181b;
  }
  #mhm-hotspotsetup .lx-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }
  #mhm-hotspotsetup .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-hotspotsetup .lx-title-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  #mhm-hotspotsetup .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-hotspotsetup .lx-count {
    font-size: 11.5px; font-weight: 500; color: #71717a; background: #f4f4f5; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-hotspotsetup .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-hotspotsetup .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
  }
  #mhm-hotspotsetup .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-hotspotsetup .lx-btn.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; height: 38px; padding: 0 18px; font-weight: 600; }
  #mhm-hotspotsetup .lx-btn.is-primary:hover { background: #27272a; }

  #mhm-hotspotsetup .lx-alert {
    margin: 16px 24px 0; padding: 12px 16px; border-radius: 9px; font-size: 12.5px; line-height: 1.55;
  }
  #mhm-hotspotsetup .lx-alert.is-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
  #mhm-hotspotsetup .lx-alert.is-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-hotspotsetup .lx-alert.is-err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
  #mhm-hotspotsetup .lx-alert ul { margin: 6px 0 0 18px; padding: 0; }

  /* Wizard Grid */
  #mhm-hotspotsetup .lx-wizard-body {
    padding: 20px 24px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }
  @media (max-width: 820px) {
    #mhm-hotspotsetup .lx-wizard-body { grid-template-columns: minmax(0, 1fr); }
  }
  #mhm-hotspotsetup .lx-step-box {
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    background: #fafafa;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  #mhm-hotspotsetup .lx-step-head {
    display: flex; align-items: center; gap: 9px;
    padding-bottom: 10px; border-bottom: 1px solid #e4e4e7;
  }
  #mhm-hotspotsetup .lx-step-num {
    width: 22px; height: 22px; border-radius: 6px; background: #18181b; color: #ffffff;
    font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center;
  }
  #mhm-hotspotsetup .lx-step-title {
    font-size: 13px; font-weight: 600; color: #09090b; margin: 0;
  }
  #mhm-hotspotsetup .lx-form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  @media (max-width: 480px) { #mhm-hotspotsetup .lx-form-row { grid-template-columns: minmax(0, 1fr); } }
  #mhm-hotspotsetup .lx-field { display: flex; flex-direction: column; gap: 5px; }
  #mhm-hotspotsetup .lx-field label { font-size: 11.5px; font-weight: 600; color: #3f3f46; margin: 0; }
  #mhm-hotspotsetup .lx-field label span { font-weight: 400; color: #71717a; }
  #mhm-hotspotsetup .lx-input, #mhm-hotspotsetup .lx-select {
    width: 100% !important; height: 35px !important; padding: 0 11px !important;
    background: #ffffff !important; border: 1px solid #d4d4d8 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  #mhm-hotspotsetup .lx-input:focus, #mhm-hotspotsetup .lx-select:focus {
    border-color: #18181b !important; box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  #mhm-hotspotsetup .lx-mono-input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
  #mhm-hotspotsetup .lx-ip-hint {
    font-size: 11.5px; padding: 6px 10px; border-radius: 7px; display: flex; align-items: center; gap: 6px;
  }
  #mhm-hotspotsetup .lx-ip-hint.is-found { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-hotspotsetup .lx-ip-hint.is-missing { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }
  #mhm-hotspotsetup .lx-check-box {
    display: flex; align-items: center; gap: 8px; padding: 8px 10px;
    background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px; font-size: 12px; color: #27272a; cursor: pointer;
  }
  #mhm-hotspotsetup .lx-wizard-foot {
    padding: 14px 24px; background: #fafafa; border-top: 1px solid #e4e4e7;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
  }

  /* Active Hotspot Table */
  #mhm-hotspotsetup .lx-table-wrap { width: 100%; overflow-x: auto; }
  #mhm-hotspotsetup #dataTable {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-hotspotsetup #dataTable thead th {
    background: #fafafa !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 600 !important; text-transform: uppercase !important;
    letter-spacing: 0.05em !important; padding: 11px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-hotspotsetup #dataTable tbody td {
    padding: 12px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 13px !important; color: #27272a !important;
  }
  #mhm-hotspotsetup .lx-pill-status {
    display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;
  }
  #mhm-hotspotsetup .lx-pill-status.is-active { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-hotspotsetup .lx-pill-status.is-disabled { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }
  #mhm-hotspotsetup .lx-badge-iface {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px; font-weight: 600; color: #18181b; background: #f4f4f5; border: 1px solid #e4e4e7;
    padding: 2px 8px; border-radius: 5px;
  }
  #mhm-hotspotsetup .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #3f3f46; }
  #mhm-hotspotsetup .lx-icon-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
    border-radius: 6px; border: 1px solid #e4e4e7; background: #ffffff; color: #52525b; font-size: 12px; cursor: pointer;
  }
  #mhm-hotspotsetup .lx-icon-btn:hover { background: #fafafa; border-color: #d4d4d8; color: #09090b; }
  #mhm-hotspotsetup .lx-icon-btn.is-danger:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
  #mhm-hotspotsetup .lx-icon-btn.is-locked { background: #fffbeb; border-color: #fde68a; color: #b45309; }
</style>

<div id="mhm-hotspotsetup">
  <!-- Card 1: Hotspot Setup Wizard -->
  <div class="lx-card">
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title">IP &rsaquo; Hotspot Setup Wizard (Interface Terpisah)</h3>
        <span class="lx-count">Otomatisasi IP Pool, DHCP &amp; Hotspot</span>
      </div>
      <div class="lx-actions">
        <a href="./?ip=addresses&session=<?= urlencode($session); ?>" class="lx-btn">
          <i class="fa fa-arrow-left" style="font-size:11.5px; color:#71717a;"></i>
          <span>Kelola IP Addresses</span>
        </a>
      </div>
    </div>

    <?php if ($infoBanner != "") { ?>
      <div class="lx-alert is-info">
        <i class="fa fa-info-circle" style="margin-right:6px;"></i><?= htmlspecialchars($infoBanner); ?>
      </div>
    <?php } ?>

    <?php if ($setupErr != "") { ?>
      <div class="lx-alert is-err">
        <i class="fa fa-exclamation-triangle" style="margin-right:6px;"></i><?= htmlspecialchars($setupErr); ?>
      </div>
    <?php } ?>

    <?php if (count($setupSteps) > 0 && $setupErr == "") { ?>
      <div class="lx-alert is-ok">
        <strong><i class="fa fa-check-circle" style="margin-right:6px;"></i>Hotspot Setup Selesai Dikonfigurasi!</strong>
        <ul>
          <?php foreach ($setupSteps as $st) { ?>
            <li><?= $st; ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>

    <form method="post" action="" autocomplete="off" style="margin:0;">
      <div class="lx-wizard-body">
        <!-- Step 1 -->
        <div class="lx-step-box">
          <div class="lx-step-head">
            <span class="lx-step-num">1</span>
            <h4 class="lx-step-title">Hotspot Interface &amp; Segmen IP Lokal</h4>
          </div>
          <div class="lx-field">
            <label>Pilih Hotspot Interface <span>(Misal: ether5 khusus AP Hotspot)</span></label>
            <select name="hs_interface" id="wizIface" class="lx-select" onchange="onWizardIfaceChange()" required>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName == "") continue;
                $sel = ($ifName == $selectedIface) ? "selected" : "";
                $ipHint = isset($ifaceIpMap[$ifName]) ? " — IP: " . $ifaceIpMap[$ifName] : " — Belum ada IP";
                echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '" ' . $sel . '>' . htmlspecialchars($ifName . $ipHint) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="lx-field">
            <label>Local Address of Network (Gateway / Prefix)</label>
            <input type="text" name="local_address" id="wizLocalAddr" class="lx-input lx-mono-input" value="<?= htmlspecialchars($initialCidr, ENT_QUOTES); ?>" oninput="onWizardCidrChange()" required>
          </div>
          <div id="wizIpStatusBanner" class="lx-ip-hint <?= isset($ifaceIpMap[$selectedIface]) ? 'is-found' : 'is-missing'; ?>">
            <i class="fa <?= isset($ifaceIpMap[$selectedIface]) ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span id="wizIpStatusText">
              <?= isset($ifaceIpMap[$selectedIface])
                ? "IP " . htmlspecialchars($ifaceIpMap[$selectedIface]) . " sudah terpasang di IP > Addresses."
                : "Interface ini belum punya IP. Wizard akan otomatis menambahkannya ke IP > Addresses."; ?>
            </span>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="lx-step-box">
          <div class="lx-step-head">
            <span class="lx-step-num">2</span>
            <h4 class="lx-step-title">Address Pool &amp; DHCP Server</h4>
          </div>
          <div class="lx-field">
            <label>Address Pool of Network <span>(Rentang IP Klien Hotspot)</span></label>
            <input type="text" name="pool_range" id="wizPoolRange" class="lx-input lx-mono-input" value="<?= htmlspecialchars($initialPool, ENT_QUOTES); ?>" required>
          </div>
          <div class="lx-form-row">
            <div class="lx-field">
              <label>DHCP Lease Time</label>
              <input type="text" name="lease_time" class="lx-input lx-mono-input" value="01:00:00">
            </div>
            <div class="lx-field">
              <label>Nama Hotspot Server</label>
              <input type="text" name="hotspot_name" id="wizHsName" class="lx-input lx-mono-input" value="<?= htmlspecialchars($initialHsName, ENT_QUOTES); ?>" required>
            </div>
          </div>
          <label class="lx-check-box">
            <input type="checkbox" name="enable_nat" value="1" checked>
            <span>Aktifkan <strong>Masquerade Network (NAT)</strong> agar klien segmen ini bisa akses internet</span>
          </label>
        </div>

        <!-- Step 3 -->
        <div class="lx-step-box">
          <div class="lx-step-head">
            <span class="lx-step-num">3</span>
            <h4 class="lx-step-title">DNS &amp; Portal Login Hotspot</h4>
          </div>
          <div class="lx-form-row">
            <div class="lx-field">
              <label>DNS Servers</label>
              <input type="text" name="dns_servers" class="lx-input lx-mono-input" value="<?= htmlspecialchars($currentDnsServers, ENT_QUOTES); ?>" required>
            </div>
            <div class="lx-field">
              <label>DNS Name <span>(Alamat Login Portal)</span></label>
              <input type="text" name="dns_name" class="lx-input lx-mono-input" value="hotspot.sekolah.local" placeholder="hotspot.sekolah.local" required>
            </div>
          </div>
          <div class="lx-field">
            <label>HTML Directory <span>(Sesuai penyimpanan router)</span></label>
            <select name="html_directory" class="lx-select">
              <option value="hotspot" <?= $defaultHtmlDir == "hotspot" ? "selected" : ""; ?>>hotspot (Standard Storage)</option>
              <option value="flash/hotspot" <?= $defaultHtmlDir == "flash/hotspot" ? "selected" : ""; ?>>flash/hotspot (16MB Flash Router)</option>
            </select>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="lx-step-box">
          <div class="lx-step-head">
            <span class="lx-step-num">4</span>
            <h4 class="lx-step-title">Akun Hotspot Lokal Awal (Opsional)</h4>
          </div>
          <div class="lx-form-row">
            <div class="lx-field">
              <label>Username Awal <span>(Kosongkan jika tidak perlu)</span></label>
              <input type="text" name="init_user" class="lx-input lx-mono-input" placeholder="admin-hs">
            </div>
            <div class="lx-field">
              <label>Password</label>
              <input type="text" name="init_pass" class="lx-input lx-mono-input" placeholder="123456">
            </div>
          </div>
          <div class="lx-field">
            <label>User Profile</label>
            <select name="init_profile" class="lx-select">
              <?php
              if ($TotalUserProf > 0) {
                for ($up = 0; $up < $TotalUserProf; $up++) {
                  $upName = $getuserprofiles[$up]['name'];
                  echo '<option value="' . htmlspecialchars($upName, ENT_QUOTES) . '">' . htmlspecialchars($upName) . '</option>';
                }
              } else {
                echo '<option value="default">default</option>';
              }
              ?>
            </select>
          </div>
        </div>
      </div>

      <div class="lx-wizard-foot">
        <div style="font-size:12px; color:#52525b;">
          <i class="fa fa-shield" style="color:#047857; margin-right:5px;"></i>
          Jalur admin/server (misal <code style="background:#ffffff; border:1px solid #e4e4e7; padding:1px 5px; border-radius:4px;">ether2</code>) tetap terpisah dan tidak terpengaruh halaman login hotspot.
        </div>
        <button type="submit" name="run_hotspot_setup" class="lx-btn is-primary">
          <i class="fa fa-magic"></i>
          <span>Jalankan Hotspot Setup</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Card 2: Daftar Hotspot Server Terpasang -->
  <div class="lx-card">
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title">Daftar Hotspot Server Terpasang (IP &rsaquo; Hotspot &rsaquo; Servers)</h3>
        <span class="lx-count"><?= $TotalHotspot; ?> server</span>
      </div>
    </div>

    <div class="lx-table-wrap">
      <table id="dataTable" class="table">
        <thead>
          <tr>
            <th style="text-align:center; width:100px;">Aksi</th>
            <th style="width:95px;">Status</th>
            <th>Nama Server</th>
            <th>Interface</th>
            <th>Address Pool</th>
            <th>Server Profile</th>
            <th>DNS Name Portal</th>
            <th>Idle Timeout</th>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($h = 0; $h < $TotalHotspot; $h++) {
            $hs = $gethotspots[$h];
            $hsid = $hs['.id'];
            $hsName = $hs['name'];
            $hsIface = $hs['interface'];
            $hsPool = $hs['address-pool'];
            $hsProf = $hs['profile'];
            $hsIdle = isset($hs['idle-timeout']) ? $hs['idle-timeout'] : 'none';
            $hsDis = (isset($hs['disabled']) && $hs['disabled'] == "true");
            $hsDns = isset($profDnsMap[$hsProf]) && $profDnsMap[$hsProf] != "" ? $profDnsMap[$hsProf] : "-";
            ?>
            <tr>
              <td style="text-align:center;">
                <div style="display:inline-flex; gap:5px;">
                  <?php if ($hsDis) { ?>
                    <span class="lx-icon-btn is-locked" title="Aktifkan <?= htmlspecialchars($hsName, ENT_QUOTES); ?>" onclick="loadpage('./?ip=hotspot-setup&act=enable&hsid=<?= urlencode($hsid); ?>&session=<?= urlencode($session); ?>');">
                      <i class="fa fa-lock"></i>
                    </span>
                  <?php } else { ?>
                    <span class="lx-icon-btn" title="Nonaktifkan <?= htmlspecialchars($hsName, ENT_QUOTES); ?>" onclick="loadpage('./?ip=hotspot-setup&act=disable&hsid=<?= urlencode($hsid); ?>&session=<?= urlencode($session); ?>');">
                      <i class="fa fa-unlock"></i>
                    </span>
                  <?php } ?>
                  <span class="lx-icon-btn is-danger" title="Hapus <?= htmlspecialchars($hsName, ENT_QUOTES); ?>" onclick="if(confirm('Hapus Hotspot Server <?= htmlspecialchars($hsName, ENT_QUOTES); ?>?')) loadpage('./?ip=hotspot-setup&act=remove&hsid=<?= urlencode($hsid); ?>&session=<?= urlencode($session); ?>');">
                    <i class="fa fa-trash-o"></i>
                  </span>
                </div>
              </td>
              <td>
                <?php if ($hsDis) { ?>
                  <span class="lx-pill-status is-disabled">Nonaktif</span>
                <?php } else { ?>
                  <span class="lx-pill-status is-active">Aktif</span>
                <?php } ?>
              </td>
              <td><strong><?= htmlspecialchars($hsName); ?></strong></td>
              <td><span class="lx-badge-iface"><?= htmlspecialchars($hsIface); ?></span></td>
              <td><span class="lx-mono"><?= htmlspecialchars($hsPool); ?></span></td>
              <td><span class="lx-mono"><?= htmlspecialchars($hsProf); ?></span></td>
              <td><span class="lx-mono"><?= htmlspecialchars($hsDns); ?></span></td>
              <td><span class="lx-mono"><?= htmlspecialchars($hsIdle); ?></span></td>
            </tr>
            <?php
          }
          if ($TotalHotspot == 0) {
            echo "<tr><td colspan='8' style='padding:36px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Belum ada Hotspot Server aktif. Gunakan wizard di atas untuk membuat Hotspot baru.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  var mhmIfaceIpMap = <?= json_encode($ifaceIpMap); ?>;

  function intToIp(num) {
    return [(num >>> 24) & 255, (num >>> 16) & 255, (num >>> 8) & 255, num & 255].join('.');
  }

  function onWizardCidrChange() {
    var val = (document.getElementById('wizLocalAddr').value || '').trim();
    var m = val.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/);
    if (!m) return;
    var oct = [parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10), parseInt(m[4], 10)];
    var prefix = parseInt(m[5], 10);
    if (prefix < 1 || prefix > 30 || oct.some(function(o){ return o < 0 || o > 255; })) return;

    var ipInt = ((oct[0] << 24) | (oct[1] << 16) | (oct[2] << 8) | oct[3]) >>> 0;
    var mask = (0xFFFFFFFF << (32 - prefix)) >>> 0;
    var netInt = (ipInt & mask) >>> 0;
    var bcastInt = (netInt | (~mask)) >>> 0;

    var firstHost = (netInt + 1) >>> 0;
    var lastHost = (bcastInt - 1) >>> 0;
    var pStart = (ipInt === firstHost) ? ((firstHost + 1) >>> 0) : firstHost;
    var pEnd = (ipInt === lastHost) ? ((lastHost - 1) >>> 0) : lastHost;

    document.getElementById('wizPoolRange').value = intToIp(pStart) + '-' + intToIp(pEnd);
  }

  function onWizardIfaceChange() {
    var iface = document.getElementById('wizIface').value || '';
    var slug = iface.replace(/[^a-zA-Z0-9_-]/g, '');
    document.getElementById('wizHsName').value = 'hotspot-' + slug;

    var banner = document.getElementById('wizIpStatusBanner');
    var txt = document.getElementById('wizIpStatusText');
    var icon = banner.querySelector('i');

    if (mhmIfaceIpMap && mhmIfaceIpMap[iface]) {
      document.getElementById('wizLocalAddr').value = mhmIfaceIpMap[iface];
      banner.className = 'lx-ip-hint is-found';
      icon.className = 'fa fa-check-circle';
      txt.textContent = 'IP ' + mhmIfaceIpMap[iface] + ' sudah terpasang di IP > Addresses.';
    } else {
      document.getElementById('wizLocalAddr').value = '192.168.20.1/24';
      banner.className = 'lx-ip-hint is-missing';
      icon.className = 'fa fa-exclamation-circle';
      txt.textContent = 'Interface ini belum punya IP. Wizard akan otomatis menambahkannya ke IP > Addresses.';
    }
    onWizardCidrChange();
  }
</script>