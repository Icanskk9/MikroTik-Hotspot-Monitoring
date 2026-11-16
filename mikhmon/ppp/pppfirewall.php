<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $statusMsg = "";
  $errMsg = "";

  // Helper konversi IP/Prefix ke alamat Network CIDR (misal 192.168.10.254/24 -> 192.168.10.0/24)
  function cidrToNetwork($cidr) {
    $parts = explode('/', trim($cidr));
    if (count($parts) !== 2) return "";
    $ipLong = ip2long($parts[0]);
    $prefix = (int)$parts[1];
    if ($ipLong === false || $prefix < 0 || $prefix > 32) return "";
    $mask = ($prefix === 0) ? 0 : (~0 << (32 - $prefix));
    $netLong = $ipLong & $mask;
    return long2ip($netLong) . "/" . $prefix;
  }

  // Helper cek apakah sebuah IP berada dalam suatu subnet CIDR
  function ipInCidr($ip, $cidr) {
    $parts = explode('/', trim($cidr));
    if (count($parts) !== 2) return false;
    $ipLong = ip2long($ip);
    $netLong = ip2long($parts[0]);
    $prefix = (int)$parts[1];
    if ($ipLong === false || $netLong === false || $prefix < 0 || $prefix > 32) return false;
    $mask = ($prefix === 0) ? 0 : (~0 << (32 - $prefix));
    return (($ipLong & $mask) === ($netLong & $mask));
  }

  // Helper cari ID rule spesifik berdasarkan tipe & alamat subnet agar mendukung multi-profile PPPoE
  function findMatchingRule($rows, $ruleKey, $adminSub, $pppoeSub) {
    for ($i = 0; $i < count($rows); $i++) {
      $r = $rows[$i];
      $c = isset($r['comment']) ? $r['comment'] : "";
      $ch = isset($r['chain']) ? $r['chain'] : "";
      $ac = isset($r['action']) ? $r['action'] : "";
      $src = isset($r['src-address']) ? $r['src-address'] : "";
      $dst = isset($r['dst-address']) ? $r['dst-address'] : "";
      $proto = isset($r['protocol']) ? $r['protocol'] : "";
      $outIf = isset($r['out-interface']) ? $r['out-interface'] : "";

      if ($ruleKey === "allow_remote") {
        $isMatchType = (strpos($c, "Allow Management Remote Access") !== false || strpos($c, "Allow Admin Remote ONT") !== false || ($ch === "forward" && $ac === "accept" && $proto === "" && $outIf === ""));
        if ($isMatchType && $src === $adminSub && $dst === $pppoeSub) {
          return $r;
        }
      } elseif ($ruleKey === "masq_remote") {
        $isMatchType = (stripos($c, "Masquerade Remote") !== false || ($ch === "srcnat" && $ac === "masquerade" && $outIf === ""));
        if ($isMatchType && $src === $adminSub && $dst === $pppoeSub) {
          return $r;
        }
      } elseif ($ruleKey === "isolate_ont") {
        $isMatchType = (strpos($c, "Isolate Client") !== false || strpos($c, "Block Siswa & Antar ONT") !== false || ($ch === "forward" && $ac === "drop" && $src === "" && $proto === ""));
        if ($isMatchType && $dst === $pppoeSub) {
          return $r;
        }
      } elseif ($ruleKey === "block_router") {
        $isMatchType = (strpos($c, "Block Client Access to Router") !== false || strpos($c, "Block ONT Akses Router") !== false || ($ch === "input" && $ac === "drop"));
        if ($isMatchType && $src === $pppoeSub) {
          return $r;
        }
      }
    }
    return null;
  }

  // Aksi cepat: Enable / Disable / Remove Rule Filter atau NAT
  if (isset($_GET['act']) && isset($_GET['ftype']) && isset($_GET['fid']) && $_GET['fid'] != "") {
    $act = $_GET['act'];
    $ftype = $_GET['ftype'];
    $fid = $_GET['fid'];

    if (($ftype == "filter" || $ftype == "nat") && ($act == "enable" || $act == "disable" || $act == "remove")) {
      $API->comm("/ip/firewall/" . $ftype . "/" . $act, array(".id" => $fid));
    }
    echo "<script>window.location='./?ppp=firewall&session=" . $session . "'</script>";
    exit;
  }

  // Kontrol Cepat 1-Klik per Rule (Aktifkan / Nonaktifkan / Pasang Baru untuk Profile/Subnet PPPoE)
  if (isset($_POST['toggle_quick_rule'])) {
    $q_key = trim($_POST['toggle_quick_rule']);
    $q_act = trim($_POST['quick_action']);
    $q_admin = trim($_POST['admin_subnet']);
    $q_pppoe = trim($_POST['pppoe_subnet']);

    if ($q_admin != "" && $q_pppoe != "" && in_array($q_key, array("allow_remote", "masq_remote", "isolate_ont", "block_router"))) {
      $isNat = ($q_key === "masq_remote");
      $tblPath = $isNat ? "/ip/firewall/nat" : "/ip/firewall/filter";
      $rows = $API->comm($tblPath . "/print");
      $matched = findMatchingRule($rows, $q_key, $q_admin, $q_pppoe);

      if ($q_act === "disable") {
        if ($matched && isset($matched['.id'])) {
          $API->comm($tblPath . "/disable", array(".id" => $matched['.id']));
        }
        echo "<script>window.location='./?ppp=firewall&saved=5&session=" . $session . "'</script>";
        exit;
      } else {
        if ($matched && isset($matched['.id'])) {
          $API->comm($tblPath . "/enable", array(".id" => $matched['.id']));
        } else {
          $topFilterId = (!$isNat && isset($rows[0]['.id'])) ? $rows[0]['.id'] : "";
          $addParams = array("disabled" => "no");

          if ($q_key === "allow_remote") {
            $addParams["chain"] = "forward";
            $addParams["src-address"] = $q_admin;
            $addParams["dst-address"] = $q_pppoe;
            $addParams["action"] = "accept";
            $addParams["comment"] = "Mikhmon-PPPoE: Allow Management Remote Access (" . $q_pppoe . ")";
            if ($topFilterId != "") $addParams["place-before"] = $topFilterId;
          } elseif ($q_key === "masq_remote") {
            $addParams["chain"] = "srcnat";
            $addParams["src-address"] = $q_admin;
            $addParams["dst-address"] = $q_pppoe;
            $addParams["action"] = "masquerade";
            $addParams["comment"] = "Mikhmon-PPPoE: Masquerade Remote Access (" . $q_pppoe . ")";
          } elseif ($q_key === "isolate_ont") {
            $addParams["chain"] = "forward";
            $addParams["dst-address"] = $q_pppoe;
            $addParams["connection-state"] = "new";
            $addParams["action"] = "drop";
            $addParams["comment"] = "Mikhmon-PPPoE: Isolate Client & Guest Access (" . $q_pppoe . ")";
            if ($topFilterId != "") $addParams["place-before"] = $topFilterId;
          } elseif ($q_key === "block_router") {
            $addParams["chain"] = "input";
            $addParams["src-address"] = $q_pppoe;
            $addParams["protocol"] = "tcp";
            $addParams["dst-port"] = "80,443,8291,22,23";
            $addParams["action"] = "drop";
            $addParams["comment"] = "Mikhmon-PPPoE: Block Client Access to Router (" . $q_pppoe . ")";
            if ($topFilterId != "") $addParams["place-before"] = $topFilterId;
          }

          $API->comm($tblPath . "/add", $addParams);
        }
        echo "<script>window.location='./?ppp=firewall&saved=4&session=" . $session . "'</script>";
        exit;
      }
    } else {
      $errMsg = "Jaringan Pengelola / IT dan Jaringan Klien PPPoE wajib diisi.";
    }
  }

  // Tambah Rule Manual (Filter / NAT)
  if (isset($_POST['add_manual_fw'])) {
    $m_type = trim($_POST['rule_type']);
    $m_chain = trim($_POST['chain']);
    $m_action = trim($_POST['action']);
    $m_src = trim($_POST['src_address']);
    $m_dst = trim($_POST['dst_address']);
    $m_proto = trim($_POST['protocol']);
    $m_dport = trim($_POST['dst_port']);
    $m_inif = trim($_POST['in_interface']);
    $m_outif = trim($_POST['out_interface']);
    $m_toaddr = trim($_POST['to_addresses']);
    $m_toport = trim($_POST['to_ports']);
    $m_comm = trim($_POST['comment']);

    if (($m_type == "filter" || $m_type == "nat") && $m_chain != "" && $m_action != "") {
      $params = array(
        "chain" => $m_chain,
        "action" => $m_action,
        "disabled" => "no",
      );
      if ($m_src != "") $params["src-address"] = $m_src;
      if ($m_dst != "") $params["dst-address"] = $m_dst;
      if ($m_proto != "") $params["protocol"] = $m_proto;
      if ($m_dport != "" && $m_proto != "") $params["dst-port"] = $m_dport;
      if ($m_inif != "") $params["in-interface"] = $m_inif;
      if ($m_outif != "") $params["out-interface"] = $m_outif;
      if ($m_type == "nat" && $m_toaddr != "") $params["to-addresses"] = $m_toaddr;
      if ($m_type == "nat" && $m_toport != "") $params["to-ports"] = $m_toport;
      if ($m_comm != "") $params["comment"] = $m_comm;

      $res = $API->comm("/ip/firewall/" . $m_type . "/add", $params);
      if (isset($res['!trap'])) {
        $errMsg = isset($res['!trap'][0]['message']) ? $res['!trap'][0]['message'] : "Gagal menambahkan rule firewall ke MikroTik.";
      } else {
        echo "<script>window.location='./?ppp=firewall&saved=2&session=" . $session . "'</script>";
        exit;
      }
    } else {
      $errMsg = "Tipe rule, Chain, dan Action wajib diisi.";
    }
  }

  // Edit Rule Manual (Filter / NAT)
  if (isset($_POST['edit_manual_fw'])) {
    $e_type = trim($_POST['rule_type']);
    $e_id = trim($_POST['rule_id']);
    $e_chain = trim($_POST['chain']);
    $e_action = trim($_POST['action']);
    $e_src = trim($_POST['src_address']);
    $e_dst = trim($_POST['dst_address']);
    $e_proto = trim($_POST['protocol']);
    $e_dport = trim($_POST['dst_port']);
    $e_inif = trim($_POST['in_interface']);
    $e_outif = trim($_POST['out_interface']);
    $e_toaddr = trim($_POST['to_addresses']);
    $e_toport = trim($_POST['to_ports']);
    $e_comm = trim($_POST['comment']);

    if (($e_type == "filter" || $e_type == "nat") && $e_id != "" && $e_chain != "" && $e_action != "") {
      $setParams = array(
        ".id" => $e_id,
        "chain" => $e_chain,
        "action" => $e_action,
      );
      $unsetFields = array();

      $optionalMap = array(
        "src-address" => $e_src,
        "dst-address" => $e_dst,
        "dst-port" => ($e_proto != "" ? $e_dport : ""),
        "protocol" => $e_proto,
        "in-interface" => $e_inif,
        "out-interface" => $e_outif,
        "comment" => $e_comm,
      );
      if ($e_type == "nat") {
        $optionalMap["to-ports"] = $e_toport;
        $optionalMap["to-addresses"] = $e_toaddr;
      }

      foreach ($optionalMap as $key => $val) {
        if ($val !== "") {
          $setParams[$key] = $val;
        } else {
          $unsetFields[] = $key;
        }
      }

      $res = $API->comm("/ip/firewall/" . $e_type . "/set", $setParams);
      if (isset($res['!trap'])) {
        $errMsg = isset($res['!trap'][0]['message']) ? $res['!trap'][0]['message'] : "Gagal memperbarui rule firewall.";
      } else {
        foreach ($unsetFields as $uField) {
          $API->comm("/ip/firewall/" . $e_type . "/unset", array(
            ".id" => $e_id,
            "value-name" => $uField,
          ));
        }
        echo "<script>window.location='./?ppp=firewall&saved=3&session=" . $session . "'</script>";
        exit;
      }
    }
  }

  // 1-Klik Pasang / Update Aturan Otomatis Halaman Isolir (Redirect ke error.html via Web Proxy)
  if (isset($_POST['auto_setup_isolir_fw'])) {
    $isolir_sub = trim($_POST['isolir_subnet']);
    $proxy_port = trim($_POST['proxy_port']);
    $setup_proxy = isset($_POST['setup_web_proxy']) ? trim($_POST['setup_web_proxy']) : "1";

    if ($proxy_port == "" || !is_numeric($proxy_port)) {
      $proxy_port = "8080";
    }

    if ($isolir_sub != "") {
      $existingFilters = $API->comm("/ip/firewall/filter/print");
      $topFilterId = isset($existingFilters[0]['.id']) ? $existingFilters[0]['.id'] : "";

      // Urutan 7 Filter Rules sesuai skema isolir (QUIC -> HTTPS -> DNS -> Proxy 8080 -> Drop Internet)
      $isolirFilterSpecs = array(
        array(
          "comment" => "Reject QUIC Isolir",
          "match"   => array("chain" => "forward", "action" => "reject", "protocol" => "udp", "dst-port" => "443"),
          "params"  => array(
            "chain"       => "forward",
            "src-address" => $isolir_sub,
            "protocol"    => "udp",
            "dst-port"    => "443",
            "action"      => "reject",
            "reject-with" => "icmp-network-unreachable",
            "disabled"    => "no",
          ),
        ),
        array(
          "comment" => "Reject HTTPS Isolir",
          "match"   => array("chain" => "forward", "action" => "reject", "protocol" => "tcp", "dst-port" => "443"),
          "params"  => array(
            "chain"       => "forward",
            "src-address" => $isolir_sub,
            "protocol"    => "tcp",
            "dst-port"    => "443",
            "action"      => "reject",
            "reject-with" => "icmp-network-unreachable",
            "disabled"    => "no",
          ),
        ),
        array(
          "comment" => "Allow TCP DNS Isolir",
          "match"   => array("chain" => "forward", "action" => "accept", "protocol" => "tcp", "dst-port" => "53"),
          "params"  => array(
            "chain"       => "forward",
            "src-address" => $isolir_sub,
            "protocol"    => "tcp",
            "dst-port"    => "53",
            "action"      => "accept",
            "disabled"    => "no",
          ),
        ),
        array(
          "comment" => "Allow DNS User Isolir",
          "match"   => array("chain" => "forward", "action" => "accept", "protocol" => "udp", "dst-port" => "53"),
          "params"  => array(
            "chain"       => "forward",
            "src-address" => $isolir_sub,
            "protocol"    => "udp",
            "dst-port"    => "53",
            "action"      => "accept",
            "disabled"    => "no",
          ),
        ),
        array(
          "comment" => "Allow Local DNS Isolir",
          "match"   => array("chain" => "input", "action" => "accept", "protocol" => "udp", "dst-port" => "53"),
          "params"  => array(
            "chain"       => "input",
            "src-address" => $isolir_sub,
            "protocol"    => "udp",
            "dst-port"    => "53",
            "action"      => "accept",
            "disabled"    => "no",
          ),
        ),
        array(
          "comment" => "Allow Halaman Isolir Proxy",
          "match"   => array("chain" => "input", "action" => "accept", "protocol" => "tcp", "dst-port" => $proxy_port),
          "params"  => array(
            "chain"       => "input",
            "src-address" => $isolir_sub,
            "protocol"    => "tcp",
            "dst-port"    => $proxy_port,
            "action"      => "accept",
            "disabled"    => "no",
          ),
        ),
        array(
          "comment" => "Drop Internet User Isolir",
          "match"   => array("chain" => "forward", "action" => "drop", "protocol" => "", "dst-port" => ""),
          "params"  => array(
            "chain"       => "forward",
            "src-address" => $isolir_sub,
            "action"      => "drop",
            "disabled"    => "no",
          ),
        ),
      );

      foreach ($isolirFilterSpecs as $spec) {
        $found = null;
        for ($i = 0; $i < count($existingFilters); $i++) {
          $r = $existingFilters[$i];
          $rc = isset($r['comment']) ? $r['comment'] : "";
          $rsrc = isset($r['src-address']) ? $r['src-address'] : "";
          $rch = isset($r['chain']) ? $r['chain'] : "";
          $rac = isset($r['action']) ? $r['action'] : "";
          $rproto = isset($r['protocol']) ? $r['protocol'] : "";
          $rdport = isset($r['dst-port']) ? $r['dst-port'] : "";

          if ($rsrc === $isolir_sub) {
            if ($rc === $spec["comment"] || ($rch === $spec["match"]["chain"] && $rac === $spec["match"]["action"] && $rproto === $spec["match"]["protocol"] && $rdport === $spec["match"]["dst-port"])) {
              $found = $r;
              break;
            }
          }
        }

        if ($found && isset($found['.id'])) {
          $setParams = $spec["params"];
          $setParams[".id"] = $found['.id'];
          $setParams["comment"] = $spec["comment"];
          $API->comm("/ip/firewall/filter/set", $setParams);
        } else {
          $addParams = $spec["params"];
          $addParams["comment"] = $spec["comment"];
          if ($topFilterId != "") {
            $addParams["place-before"] = $topFilterId;
          }
          $API->comm("/ip/firewall/filter/add", $addParams);
        }
      }

      // Pasang / Update NAT Redirect Isolir Local (TCP 80 -> Proxy Port) di urutan paling atas NAT
      $existingNats = $API->comm("/ip/firewall/nat/print");
      $topNatId = isset($existingNats[0]['.id']) ? $existingNats[0]['.id'] : "";
      $foundRedirectNat = null;

      for ($n = 0; $n < count($existingNats); $n++) {
        $nr = $existingNats[$n];
        $nc = isset($nr['comment']) ? $nr['comment'] : "";
        $nsrc = isset($nr['src-address']) ? $nr['src-address'] : "";
        $nch = isset($nr['chain']) ? $nr['chain'] : "";
        $nact = isset($nr['action']) ? $nr['action'] : "";
        $nproto = isset($nr['protocol']) ? $nr['protocol'] : "";
        $ndport = isset($nr['dst-port']) ? $nr['dst-port'] : "";

        if ($nsrc === $isolir_sub && ($nc === "Redirect Isolir Local" || ($nch === "dstnat" && $nact === "redirect" && $nproto === "tcp" && $ndport === "80"))) {
          $foundRedirectNat = $nr;
          break;
        }
      }

      $redirectNatParams = array(
        "chain"       => "dstnat",
        "src-address" => $isolir_sub,
        "protocol"    => "tcp",
        "dst-port"    => "80",
        "action"      => "redirect",
        "to-ports"    => $proxy_port,
        "comment"     => "Redirect Isolir Local",
        "disabled"    => "no",
      );

      if ($foundRedirectNat && isset($foundRedirectNat['.id'])) {
        $redirectNatParams[".id"] = $foundRedirectNat['.id'];
        $API->comm("/ip/firewall/nat/set", $redirectNatParams);
      } else {
        if ($topNatId != "") {
          $redirectNatParams["place-before"] = $topNatId;
        }
        $API->comm("/ip/firewall/nat/add", $redirectNatParams);
      }

      // Opsional: Aktifkan Web Proxy & blokir akses proxy dari subnet isolir agar menampilkan error.html
      if ($setup_proxy === "1") {
        $API->comm("/ip/proxy/set", array(
          "enabled"       => "yes",
          "port"          => $proxy_port,
          "cache-on-disk" => "no",
        ));

        $proxyAccess = $API->comm("/ip/proxy/access/print");
        $hasProxyDeny = false;
        for ($pa = 0; $pa < count($proxyAccess); $pa++) {
          $pasrc = isset($proxyAccess[$pa]['src-address']) ? $proxyAccess[$pa]['src-address'] : "";
          $paact = isset($proxyAccess[$pa]['action']) ? $proxyAccess[$pa]['action'] : "";
          if ($pasrc === $isolir_sub && $paact === "deny") {
            $hasProxyDeny = true;
            $API->comm("/ip/proxy/access/set", array(
              ".id"      => $proxyAccess[$pa]['.id'],
              "disabled" => "no",
            ));
            break;
          }
        }
        if (!$hasProxyDeny) {
          $addProxyAccess = array(
            "src-address" => $isolir_sub,
            "action"      => "deny",
            "comment"     => "Mikhmon-Isolir: Show error.html (" . $isolir_sub . ")",
            "disabled"    => "no",
          );
          if (isset($proxyAccess[0]['.id']) && $proxyAccess[0]['.id'] != "") {
            $addProxyAccess["place-before"] = $proxyAccess[0]['.id'];
          }
          $API->comm("/ip/proxy/access/add", $addProxyAccess);
        }
      }

      echo "<script>window.location='./?ppp=firewall&saved=6&session=" . $session . "'</script>";
      exit;
    } else {
      $errMsg = "Subnet IP Isolir wajib diisi (contoh: 10.254.254.0/24).";
    }
  }

  // 1-Klik Pasang / Update Aturan Otomatis Firewall & NAT PPPoE
  if (isset($_POST['auto_setup_pppoe_fw'])) {
    $admin_sub = trim($_POST['admin_subnet']);
    $pppoe_sub = trim($_POST['pppoe_subnet']);
    $wan_iface = trim($_POST['wan_interface']);

    if ($admin_sub != "" && $pppoe_sub != "" && $wan_iface != "") {
      $existingFilters = $API->comm("/ip/firewall/filter/print");
      $topFilterId = isset($existingFilters[0]['.id']) ? $existingFilters[0]['.id'] : "";

      $filterSpecs = array(
        array(
          "key"     => "allow_remote",
          "comment" => "Mikhmon-PPPoE: Allow Management Remote Access (" . $pppoe_sub . ")",
          "params"  => array(
            "chain" => "forward",
            "src-address" => $admin_sub,
            "dst-address" => $pppoe_sub,
            "action" => "accept",
            "disabled" => "no",
          ),
        ),
        array(
          "key"     => "isolate_ont",
          "comment" => "Mikhmon-PPPoE: Isolate Client & Guest Access (" . $pppoe_sub . ")",
          "params"  => array(
            "chain" => "forward",
            "dst-address" => $pppoe_sub,
            "connection-state" => "new",
            "action" => "drop",
            "disabled" => "no",
          ),
        ),
        array(
          "key"     => "block_router",
          "comment" => "Mikhmon-PPPoE: Block Client Access to Router (" . $pppoe_sub . ")",
          "params"  => array(
            "chain" => "input",
            "src-address" => $pppoe_sub,
            "protocol" => "tcp",
            "dst-port" => "80,443,8291,22,23",
            "action" => "drop",
            "disabled" => "no",
          ),
        ),
      );

      foreach ($filterSpecs as $spec) {
        $found = findMatchingRule($existingFilters, $spec["key"], $admin_sub, $pppoe_sub);
        if ($found && isset($found['.id'])) {
          $setParams = $spec["params"];
          $setParams[".id"] = $found['.id'];
          $setParams["comment"] = $spec["comment"];
          $API->comm("/ip/firewall/filter/set", $setParams);
          if ($spec["key"] === "isolate_ont" && !empty($found['src-address'])) {
            $API->comm("/ip/firewall/filter/unset", array(
              ".id" => $found['.id'],
              "value-name" => "src-address",
            ));
          }
        } else {
          $addParams = $spec["params"];
          $addParams["comment"] = $spec["comment"];
          if ($topFilterId != "") {
            $addParams["place-before"] = $topFilterId;
          }
          $API->comm("/ip/firewall/filter/add", $addParams);
        }
      }

      // Pasang / Update NAT Masquerade Remote ONT (Anti-403)
      $existingNats = $API->comm("/ip/firewall/nat/print");
      $foundMasqRemote = findMatchingRule($existingNats, "masq_remote", $admin_sub, $pppoe_sub);
      $masqRemoteParams = array(
        "chain" => "srcnat",
        "src-address" => $admin_sub,
        "dst-address" => $pppoe_sub,
        "action" => "masquerade",
        "comment" => "Mikhmon-PPPoE: Masquerade Remote Access (" . $pppoe_sub . ")",
        "disabled" => "no",
      );
      if ($foundMasqRemote && isset($foundMasqRemote['.id'])) {
        $masqRemoteParams[".id"] = $foundMasqRemote['.id'];
        $API->comm("/ip/firewall/nat/set", $masqRemoteParams);
      } else {
        $API->comm("/ip/firewall/nat/add", $masqRemoteParams);
      }

      // Pasang / Update NAT Masquerade Internet PPPoE
      $foundNatNet = null;
      for ($n = 0; $n < count($existingNats); $n++) {
        $nr = $existingNats[$n];
        $nc = isset($nr['comment']) ? $nr['comment'] : "";
        $nsrc = isset($nr['src-address']) ? $nr['src-address'] : "";
        $nout = isset($nr['out-interface']) ? $nr['out-interface'] : "";
        if ((strpos($nc, "NAT Internet") !== false || ($nr['chain'] === "srcnat" && $nr['action'] === "masquerade" && $nout !== "")) && $nsrc === $pppoe_sub) {
          $foundNatNet = $nr;
          break;
        }
      }

      $natParams = array(
        "chain" => "srcnat",
        "src-address" => $pppoe_sub,
        "out-interface" => $wan_iface,
        "action" => "masquerade",
        "comment" => "Mikhmon-PPPoE: NAT Internet Access (" . $pppoe_sub . ")",
        "disabled" => "no",
      );

      if ($foundNatNet && isset($foundNatNet['.id'])) {
        $natParams[".id"] = $foundNatNet['.id'];
        $API->comm("/ip/firewall/nat/set", $natParams);
      } else {
        $API->comm("/ip/firewall/nat/add", $natParams);
      }

      echo "<script>window.location='./?ppp=firewall&saved=1&session=" . $session . "'</script>";
      exit;
    } else {
      $errMsg = "Jaringan Pengelola, Jaringan Klien PPPoE, dan Interface WAN wajib diisi.";
    }
  }

  if (isset($_GET['saved'])) {
    if ($_GET['saved'] == "1") {
      $statusMsg = "Seluruh aturan akses remote perangkat, isolasi antar-ONT, dan jalur internet PPPoE berhasil diterapkan ke MikroTik!";
    } elseif ($_GET['saved'] == "2") {
      $statusMsg = "Rule firewall baru berhasil ditambahkan ke MikroTik!";
    } elseif ($_GET['saved'] == "3") {
      $statusMsg = "Perubahan rule firewall berhasil disimpan!";
    } elseif ($_GET['saved'] == "4") {
      $statusMsg = "Rule pilihan berhasil diaktifkan pada MikroTik!";
    } elseif ($_GET['saved'] == "5") {
      $statusMsg = "Rule pilihan berhasil dinonaktifkan sementara pada MikroTik!";
    } elseif ($_GET['saved'] == "6") {
      $statusMsg = "Seluruh rule isolir (Reject QUIC/HTTPS, DNS, Proxy 8080, Drop Internet, & NAT Redirect error.html) berhasil dipasang ke MikroTik!";
    }
  }

  $getfilters = $API->comm("/ip/firewall/filter/print");
  $TotalFilter = count($getfilters);

  $getnats = $API->comm("/ip/firewall/nat/print");
  $TotalNat = count($getnats);

  $getifaces = $API->comm("/interface/print");
  $TotalIface = count($getifaces);

  $getaddrs = $API->comm("/ip/address/print");
  $getpools = $API->comm("/ip/pool/print");

  // Kumpulkan seluruh subnet yang terdeteksi di router untuk pilihan otomatis
  $detectedSubnets = array();
  $clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
  $autoAdminSub = "";
  $autoPppoeSub = "";

  for ($a = 0; $a < count($getaddrs); $a++) {
    $rawAddr = isset($getaddrs[$a]['address']) ? $getaddrs[$a]['address'] : "";
    $ifLabel = isset($getaddrs[$a]['interface']) ? $getaddrs[$a]['interface'] : "";
    $netCidr = cidrToNetwork($rawAddr);
    if ($netCidr != "") {
      $detectedSubnets[$netCidr] = "Interface: " . $ifLabel;
      if ($clientIp != "" && ipInCidr($clientIp, $netCidr)) {
        $autoAdminSub = $netCidr;
      }
      if ($autoAdminSub == "" && $ifLabel != "ether1") {
        $autoAdminSub = $netCidr;
      }
    }
  }

  for ($p = 0; $p < count($getpools); $p++) {
    $poolName = isset($getpools[$p]['name']) ? $getpools[$p]['name'] : "";
    $poolRange = isset($getpools[$p]['ranges']) ? $getpools[$p]['ranges'] : "";
    $firstIp = trim(preg_split('/[-,]/', $poolRange)[0]);
    $poolCidr = cidrToNetwork($firstIp . "/24");
    if ($poolCidr != "") {
      if (!isset($detectedSubnets[$poolCidr])) {
        $detectedSubnets[$poolCidr] = "IP Pool: " . $poolName;
      }
      if (stripos($poolName, "pppoe") !== false || stripos($poolName, "pool-") === 0) {
        $autoPppoeSub = $poolCidr;
      }
    }
  }

  if ($autoAdminSub == "") $autoAdminSub = "192.168.100.0/24";
  if ($autoPppoeSub == "") $autoPppoeSub = "10.30.30.0/24";

  // Deteksi status aturan yang sudah terpasang & bangun peta status untuk Kontrol Cepat 1-Klik
  $remoteReady = false;
  $natReady = false;
  $isolirReady = false;
  $mikhmonRulesCount = 0;
  $savedAdminSub = $autoAdminSub;
  $savedPppoeSub = $autoPppoeSub;
  $savedIsolirSub = "10.254.254.0/24";
  $savedProxyPort = "8080";
  $savedWanIface = "ether1";
  $quickStatusMap = array();

  for ($i = 0; $i < $TotalFilter; $i++) {
    $f = $getfilters[$i];
    $c = isset($f['comment']) ? $f['comment'] : "";
    $dis = isset($f['disabled']) && $f['disabled'] == "true";
    $ch = isset($f['chain']) ? $f['chain'] : "";
    $ac = isset($f['action']) ? $f['action'] : "";
    $src = isset($f['src-address']) ? $f['src-address'] : "";
    $dst = isset($f['dst-address']) ? $f['dst-address'] : "";
    $proto = isset($f['protocol']) ? $f['protocol'] : "";
    $dport = isset($f['dst-port']) ? $f['dst-port'] : "";

    if (strpos($c, "Mikhmon-PPPoE:") !== false || stripos($c, "Isolir") !== false) {
      $mikhmonRulesCount++;
    }

    if ((strpos($c, "Allow Halaman Isolir Proxy") !== false || ($ch === "input" && $ac === "accept" && $proto === "tcp" && $dport !== "" && $src !== "")) && !$dis) {
      $isolirReady = true;
      if ($src != "") $savedIsolirSub = $src;
      if ($dport != "") $savedProxyPort = $dport;
    }

    if ((strpos($c, "Allow Management Remote Access") !== false || strpos($c, "Allow Admin Remote ONT") !== false || ($ch === "forward" && $ac === "accept" && $proto === "" && $src !== "" && $dst !== ""))) {
      $k = $src . "|" . $dst;
      if (!isset($quickStatusMap[$k])) $quickStatusMap[$k] = array();
      $quickStatusMap[$k]["allow_remote"] = $dis ? "disabled" : "active";
      if (!$dis) {
        $remoteReady = true;
        if ($src != "") $savedAdminSub = $src;
        if ($dst != "") $savedPppoeSub = $dst;
      }
    }

    if ((strpos($c, "Isolate Client") !== false || strpos($c, "Block Siswa & Antar ONT") !== false || ($ch === "forward" && $ac === "drop" && $src === "" && $dst !== "" && $proto === ""))) {
      $k = "*|" . $dst;
      if (!isset($quickStatusMap[$k])) $quickStatusMap[$k] = array();
      $quickStatusMap[$k]["isolate_ont"] = $dis ? "disabled" : "active";
    }

    if ((strpos($c, "Block Client Access to Router") !== false || strpos($c, "Block ONT Akses Router") !== false || ($ch === "input" && $ac === "drop" && $src !== ""))) {
      $k = "*|" . $src;
      if (!isset($quickStatusMap[$k])) $quickStatusMap[$k] = array();
      $quickStatusMap[$k]["block_router"] = $dis ? "disabled" : "active";
    }
  }

  for ($i = 0; $i < $TotalNat; $i++) {
    $n = $getnats[$i];
    $c = isset($n['comment']) ? $n['comment'] : "";
    $dis = isset($n['disabled']) && $n['disabled'] == "true";
    $ch = isset($n['chain']) ? $n['chain'] : "";
    $ac = isset($n['action']) ? $n['action'] : "";
    $src = isset($n['src-address']) ? $n['src-address'] : "";
    $dst = isset($n['dst-address']) ? $n['dst-address'] : "";
    $outIf = isset($n['out-interface']) ? $n['out-interface'] : "";
    $toport = isset($n['to-ports']) ? $n['to-ports'] : "";

    if (strpos($c, "Mikhmon-PPPoE:") !== false || stripos($c, "Masquerade Remote") !== false || stripos($c, "Isolir") !== false) {
      $mikhmonRulesCount++;
    }

    if ((strpos($c, "Redirect Isolir Local") !== false || ($ch === "dstnat" && $ac === "redirect" && $src !== "")) && !$dis) {
      $isolirReady = true;
      if ($src != "") $savedIsolirSub = $src;
      if ($toport != "") $savedProxyPort = $toport;
    }

    if ((stripos($c, "Masquerade Remote") !== false || ($ch === "srcnat" && $ac === "masquerade" && $src !== "" && $dst !== "" && $outIf === ""))) {
      $k = $src . "|" . $dst;
      if (!isset($quickStatusMap[$k])) $quickStatusMap[$k] = array();
      $quickStatusMap[$k]["masq_remote"] = $dis ? "disabled" : "active";
    }

    if ((strpos($c, "NAT Internet") !== false || ($ch === "srcnat" && $ac === "masquerade" && $outIf !== "")) && !$dis) {
      $natReady = true;
      if ($outIf != "") $savedWanIface = $outIf;
    }
  }
}
?>

<style>
  #mhm-pppfirewall {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-pppfirewall .lx-alert-ok {
    margin: 16px 24px 0;
    padding: 12px 16px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
    color: #047857;
    font-size: 12.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #mhm-pppfirewall .lx-alert-err {
    margin: 16px 24px 0;
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #b91c1c;
    font-size: 12.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #mhm-pppfirewall .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-pppfirewall .lx-title-group { display: flex; align-items: center; gap: 10px; }
  #mhm-pppfirewall .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-pppfirewall .lx-count {
    font-size: 11.5px; font-weight: 500; color: #71717a; background: #f4f4f5; padding: 2px 8px; border-radius: 6px;
  }
  #mhm-pppfirewall .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-pppfirewall .lx-search { position: relative; width: 225px; max-width: 100%; }
  #mhm-pppfirewall .lx-search i {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 12px; color: #a1a1aa; pointer-events: none;
  }
  #mhm-pppfirewall #fwSearchInput {
    width: 100% !important; height: 34px !important; padding: 0 12px 0 32px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  #mhm-pppfirewall #fwSearchInput:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  #mhm-pppfirewall .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
    transition: all 0.15s ease;
  }
  #mhm-pppfirewall .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-pppfirewall .lx-btn.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; }
  #mhm-pppfirewall .lx-btn.is-primary:hover { background: #27272a; }
  #mhm-pppfirewall .lx-btn.is-help {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a !important;
  }
  #mhm-pppfirewall .lx-btn.is-help:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
  }

  /* Metric Strip */
  #mhm-pppfirewall .lx-metrics {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7; background: #fafafa;
  }
  @media (max-width: 768px) { #mhm-pppfirewall .lx-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  #mhm-pppfirewall .lx-metric-item { padding: 14px 24px; border-right: 1px solid #e4e4e7; }
  #mhm-pppfirewall .lx-metric-item:last-child { border-right: none; }
  #mhm-pppfirewall .lx-metric-label {
    font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #71717a; margin-bottom: 6px;
  }
  #mhm-pppfirewall .lx-metric-val {
    font-size: 17px; font-weight: 600; color: #09090b; font-variant-numeric: tabular-nums;
    display: flex; align-items: center; gap: 7px;
  }
  #mhm-pppfirewall .lx-status-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px;
    font-size: 12px; font-weight: 600;
  }
  #mhm-pppfirewall .lx-status-badge.is-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-pppfirewall .lx-status-badge.is-warn { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }
  #mhm-pppfirewall .lx-dot-live { width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block; }
  #mhm-pppfirewall .lx-dot-warn { width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; display: inline-block; }

  /* 50% Left / 50% Right Split Layout */
  #mhm-pppfirewall .lx-split-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    padding: 20px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e4e4e7;
    align-items: stretch;
  }
  @media (max-width: 1024px) {
    #mhm-pppfirewall .lx-split-grid { grid-template-columns: minmax(0, 1fr); }
  }
  #mhm-pppfirewall .lx-split-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
  }

  /* Quick 1-Click Rule Toggle Panel (Left 50%) */
  #mhm-pppfirewall .lx-quick-inner {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 18px;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  #mhm-pppfirewall .lx-quick-head {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 12px;
  }
  #mhm-pppfirewall .lx-quick-title-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  #mhm-pppfirewall .lx-quick-sub {
    margin: 0;
    font-size: 11px;
    color: #71717a;
    line-height: 1.45;
  }
  #mhm-pppfirewall .lx-quick-target-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 600;
    color: #0f172a;
    width: fit-content;
  }
  #mhm-pppfirewall .lx-quick-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 9px;
    flex: 1;
  }
  #mhm-pppfirewall .lx-qcard {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    padding: 10px 13px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 7px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  #mhm-pppfirewall .lx-qcard.is-active {
    border-color: #a7f3d0;
    background: #fcfefd;
  }
  #mhm-pppfirewall .lx-qcard-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
  }
  #mhm-pppfirewall .lx-qcard-title {
    margin: 0 0 2px 0;
    font-size: 12.5px;
    font-weight: 600;
    color: #09090b;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-pppfirewall .lx-qcard-desc {
    margin: 0;
    font-size: 11.5px;
    color: #52525b;
    line-height: 1.4;
  }
  #mhm-pppfirewall .lx-qcard-foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-top: 7px;
    border-top: 1px solid #f4f4f5;
  }
  #mhm-pppfirewall .lx-qcard-spec {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 10.5px;
    color: #71717a;
  }
  #mhm-pppfirewall .lx-qstate {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 600;
    white-space: nowrap;
  }
  #mhm-pppfirewall .lx-qstate.is-on {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
  }
  #mhm-pppfirewall .lx-qstate.is-off {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
  }
  #mhm-pppfirewall .lx-qstate.is-none {
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    color: #52525b;
  }
  #mhm-pppfirewall .lx-qbtn {
    height: 27px;
    padding: 0 11px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1px solid #18181b;
    background: #18181b;
    color: #ffffff;
    transition: all 0.15s ease;
  }
  #mhm-pppfirewall .lx-qbtn:hover { background: #27272a; }
  #mhm-pppfirewall .lx-qbtn.is-stop {
    background: #ffffff;
    border-color: #e4e4e7;
    color: #dc2626;
  }
  #mhm-pppfirewall .lx-qbtn.is-stop:hover {
    background: #fef2f2;
    border-color: #fecaca;
  }

  /* Quick Auto-Setup Panels (Right 50%) */
  #mhm-pppfirewall .lx-setup-inner {
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex: 1;
  }
  #mhm-pppfirewall .lx-setup-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 14px;
  }
  #mhm-pppfirewall .lx-setup-title {
    font-size: 13.5px;
    font-weight: 600;
    color: #09090b;
    margin: 0 0 4px 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
  }
  #mhm-pppfirewall .lx-setup-desc {
    font-size: 11.5px;
    color: #71717a;
    margin: 0;
    line-height: 1.5;
  }
  #mhm-pppfirewall .lx-setup-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
    align-items: end;
  }
  @media (max-width: 540px) {
    #mhm-pppfirewall .lx-setup-grid { grid-template-columns: minmax(0, 1fr); }
  }
  #mhm-pppfirewall .lx-field { display: flex; flex-direction: column; gap: 5px; }
  #mhm-pppfirewall .lx-field label { font-size: 11.5px; font-weight: 600; color: #3f3f46; margin: 0; }
  #mhm-pppfirewall .lx-field label span { font-weight: 400; color: #71717a; }
  #mhm-pppfirewall .lx-input, #mhm-pppfirewall .lx-select {
    width: 100% !important; height: 36px !important; padding: 0 11px !important;
    background: #ffffff !important; border: 1px solid #d4d4d8 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
  }
  #mhm-pppfirewall .lx-input:focus, #mhm-pppfirewall .lx-select:focus {
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }

  /* Filter Toolbar & Tabs */
  #mhm-pppfirewall .lx-toolbar-tabs {
    padding: 12px 24px;
    background: #fafafa;
    border-bottom: 1px solid #e4e4e7;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  #mhm-pppfirewall .lx-tab-group {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  #mhm-pppfirewall .lx-tab {
    height: 30px;
    padding: 0 12px;
    border-radius: 7px;
    border: 1px solid #e4e4e7;
    background: #ffffff;
    color: #52525b;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-pppfirewall .lx-tab:hover { background: #f4f4f5; color: #09090b; }
  #mhm-pppfirewall .lx-tab.is-active {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }
  #mhm-pppfirewall .lx-tab-badge {
    font-size: 10.5px;
    padding: 1px 6px;
    border-radius: 999px;
    background: rgba(113, 113, 122, 0.14);
  }
  #mhm-pppfirewall .lx-tab.is-active .lx-tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
  }
  #mhm-pppfirewall .lx-dyn-toggle {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 500;
    color: #3f3f46;
    cursor: pointer;
    margin: 0;
    user-select: none;
  }

  /* Section Divider */
  #mhm-pppfirewall .lx-sec-bar {
    padding: 10px 24px;
    background: #f4f4f5;
    border-bottom: 1px solid #e4e4e7;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #52525b;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  /* Table */
  #mhm-pppfirewall .lx-table-wrap { width: 100%; overflow-x: auto; max-height: 46vh; }
  #mhm-pppfirewall .lx-table {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-pppfirewall .lx-table thead th {
    position: sticky; top: 0; z-index: 5; background: #ffffff !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 600 !important; text-transform: uppercase !important;
    letter-spacing: 0.05em !important; padding: 11px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppfirewall .lx-table tbody td {
    padding: 11px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 12.5px !important; color: #27272a !important;
  }
  #mhm-pppfirewall .lx-table tbody tr:hover { background-color: #fafafa !important; }
  #mhm-pppfirewall .lx-row-highlight { background-color: #f0fdf4 !important; }
  #mhm-pppfirewall .lx-row-highlight:hover { background-color: #dcfce7 !important; }
  #mhm-pppfirewall .lx-row-disabled td { opacity: 0.55; }

  #mhm-pppfirewall .lx-action-group { display: inline-flex; align-items: center; gap: 5px; }
  #mhm-pppfirewall .lx-icon-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 27px; height: 27px;
    border-radius: 6px; border: 1px solid #e4e4e7; background: #ffffff; color: #52525b; font-size: 11.5px; cursor: pointer;
  }
  #mhm-pppfirewall .lx-icon-btn:hover { background: #fafafa; border-color: #d4d4d8; color: #09090b; }
  #mhm-pppfirewall .lx-icon-btn.is-danger:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
  #mhm-pppfirewall .lx-icon-btn.is-locked { background: #fffbeb; border-color: #fde68a; color: #b45309; }

  #mhm-pppfirewall .lx-pill {
    display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px; font-weight: 600;
  }
  #mhm-pppfirewall .lx-pill.is-accept { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
  #mhm-pppfirewall .lx-pill.is-drop { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
  #mhm-pppfirewall .lx-pill.is-masq { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
  #mhm-pppfirewall .lx-pill.is-other { background: #f4f4f5; border: 1px solid #e4e4e7; color: #3f3f46; }
  #mhm-pppfirewall .lx-dyn-tag {
    display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 5px;
    font-size: 10.5px; font-weight: 600; color: #71717a; background: #f4f4f5; border: 1px solid #e4e4e7;
  }

  #mhm-pppfirewall .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; color: #3f3f46; }
  #mhm-pppfirewall .lx-empty { color: #d4d4d8; }
  #mhm-pppfirewall .lx-footer {
    padding: 12px 24px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; font-size: 11.5px; color: #71717a;
  }

  /* Modals */
  .lx-overlay {
    position: fixed; inset: 0; z-index: 9999; background: rgba(9, 9, 11, 0.5);
    backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; padding: 16px;
  }
  .lx-overlay.is-open { display: flex; }
  .lx-dialog {
    width: 100%; max-width: 480px; background: #ffffff; border: 1px solid #e4e4e7;
    border-radius: 12px; box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.25); overflow: hidden; color: #18181b;
  }
  .lx-dialog.is-wide { max-width: 680px; }
  .lx-dialog-head {
    padding: 16px 20px; border-bottom: 1px solid #f4f4f5; display: flex; align-items: center; justify-content: space-between;
    background: #ffffff;
  }
  .lx-dialog-title {
    margin: 0; font-size: 15px; font-weight: 600; color: #09090b;
    display: flex; align-items: center; gap: 8px;
  }
  .lx-dialog-close {
    width: 28px; height: 28px; border-radius: 6px; border: none; background: transparent;
    color: #71717a; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
  }
  .lx-dialog-close:hover { background: #f4f4f5; color: #09090b; }
  .lx-dialog-body {
    padding: 18px 20px;
    max-height: 75vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .lx-form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  @media (max-width: 480px) { .lx-form-row { grid-template-columns: minmax(0, 1fr); } }
  .lx-modal-field { display: flex; flex-direction: column; gap: 5px; }
  .lx-modal-field label { font-size: 12px; font-weight: 500; color: #3f3f46; margin: 0; }
  .lx-modal-field label span { font-weight: 400; color: #a1a1aa; }
  .lx-modal-input, .lx-modal-select {
    width: 100% !important; height: 36px !important; padding: 0 11px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  .lx-modal-input:focus, .lx-modal-select:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  .lx-mono-input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
  .lx-dialog-foot {
    padding: 13px 20px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
  }
  .lx-btn-modal {
    height: 34px; padding: 0 14px; border-radius: 8px; border: 1px solid #e4e4e7;
    background: #ffffff; color: #18181b; font-size: 12.5px; font-weight: 500; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .lx-btn-modal.is-primary { background: #18181b; border-color: #18181b; color: #ffffff; }
  .lx-btn-modal.is-danger { background: #dc2626; border-color: #dc2626; color: #ffffff; }

  /* Help Modal Content Styling */
  .lx-help-card {
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    padding: 12px 14px;
  }
  .lx-help-card h5 {
    margin: 0 0 6px 0;
    font-size: 13px;
    font-weight: 600;
    color: #09090b;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .lx-help-card p, .lx-help-card li {
    margin: 0;
    font-size: 12.5px;
    color: #3f3f46;
    line-height: 1.6;
  }
  .lx-help-card ul {
    margin: 6px 0 0 0;
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .lx-help-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    padding: 1px 5px;
    border-radius: 4px;
    color: #09090b;
  }
</style>

<div id="mhm-pppfirewall">
  <?php if ($statusMsg != "") { ?>
    <div class="lx-alert-ok">
      <i class="fa fa-check-circle"></i>
      <span><?= htmlspecialchars($statusMsg); ?></span>
    </div>
  <?php } ?>

  <?php if ($errMsg != "") { ?>
    <div class="lx-alert-err">
      <i class="fa fa-exclamation-circle"></i>
      <span><?= htmlspecialchars($errMsg); ?></span>
    </div>
  <?php } ?>

  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">Firewall &amp; NAT (PPPoE &amp; Remote Perangkat)</h3>
      <span class="lx-count"><?= ($TotalFilter + $TotalNat); ?> rules</span>
    </div>

    <div class="lx-actions">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="fwSearchInput" type="text" class="form-control" placeholder="Cari IP, chain, atau keterangan..." onkeyup="applyFwFilters();">
      </div>
      <button type="button" onclick="openAddFwModal();" class="lx-btn is-primary">
        <i class="fa fa-plus" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Tambah Rule</span>
      </button>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Refresh Data">
        <i class="fa fa-refresh" style="font-size:11.5px; color:#71717a;"></i>
        <span>Refresh</span>
      </button>
      <button type="button" onclick="openHelpFwModal();" class="lx-btn is-help" title="Panduan Akses Remote & Setting Sisi ONT">
        <i class="fa fa-question-circle" style="font-size:13px; color:#0284c7;"></i>
        <span>Help</span>
      </button>
    </div>
  </div>

  <!-- Status Strip -->
  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Akses Remote Perangkat</div>
      <div class="lx-metric-val">
        <?php if ($remoteReady) { ?>
          <span class="lx-status-badge is-ok"><span class="lx-dot-live"></span>Aktif (Terkunci)</span>
        <?php } else { ?>
          <span class="lx-status-badge is-warn"><span class="lx-dot-warn"></span>Belum Dipasang</span>
        <?php } ?>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">NAT Internet PPPoE</div>
      <div class="lx-metric-val">
        <?php if ($natReady) { ?>
          <span class="lx-status-badge is-ok"><span class="lx-dot-live"></span>Aktif</span>
        <?php } else { ?>
          <span class="lx-status-badge is-warn"><span class="lx-dot-warn"></span>Belum Dipasang</span>
        <?php } ?>
      </div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Filter Rules</div>
      <div class="lx-metric-val"><?= $TotalFilter; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">NAT Rules</div>
      <div class="lx-metric-val"><?= $TotalNat; ?></div>
    </div>
  </div>

  <!-- Datalist Subnet Terdeteksi dari MikroTik -->
  <datalist id="lxDetectedSubnets">
    <?php
    foreach ($detectedSubnets as $subCidr => $subInfo) {
      echo '<option value="' . htmlspecialchars($subCidr, ENT_QUOTES) . '">' . htmlspecialchars($subInfo) . '</option>';
    }
    ?>
  </datalist>

  <!-- Form Tersembunyi untuk Submit Toggle 1-Klik -->
  <form id="lxQuickRuleForm" method="post" action="" style="display:none; margin:0;">
    <input type="hidden" name="toggle_quick_rule" id="qFormKey">
    <input type="hidden" name="quick_action" id="qFormAction">
    <input type="hidden" name="admin_subnet" id="qFormAdmin">
    <input type="hidden" name="pppoe_subnet" id="qFormPppoe">
  </form>

  <!-- Grid 50% Kiri (Kontrol Cepat) & 50% Kanan (Konfigurasi Otomatis) -->
  <div class="lx-split-grid">
    <!-- KOLOM KIRI 50%: Kontrol Cepat Rule Akses Remote & Isolasi PPPoE (1-Klik) -->
    <div class="lx-split-col">
      <div class="lx-quick-inner">
        <div class="lx-quick-head">
          <div class="lx-quick-title-row">
            <h4 class="lx-setup-title" style="margin:0;">Kontrol Cepat Rule Akses Remote &amp; Isolasi PPPoE (1-Klik)</h4>
            <div class="lx-quick-target-pill" title="Target subnet yang sedang dipilih">
              <i class="fa fa-exchange" style="color:#0284c7;"></i>
              <span id="qBadgeAdmin"><?= htmlspecialchars($savedAdminSub); ?></span>
              <span>&rarr;</span>
              <span id="qBadgePppoe"><?= htmlspecialchars($savedPppoeSub); ?></span>
            </div>
          </div>
          <p class="lx-quick-sub">
            Aktifkan atau nonaktifkan masing-masing aturan secara praktis.<br>
            Jika membuat <strong>PPP Profile / Subnet baru</strong>, pilih subnet-nya di sebelah kanan lalu tekan tombol aktifkan pada kartu rule yang dibutuhkan.
          </p>
        </div>

        <div class="lx-quick-grid">
          <!-- Kartu 1: Buka Akses Remote ONT dari Sisi IT -->
          <div class="lx-qcard" id="qcard-allow_remote">
            <div class="lx-qcard-top">
              <div>
                <h5 class="lx-qcard-title"><i class="fa fa-unlock-alt" style="color:#047857;"></i> 1. Buka Akses Remote ONT (Dari Sisi IT)</h5>
                <p class="lx-qcard-desc">Mengizinkan komputer dari jaringan IT/Pengelola membuka halaman web admin seluruh ONT &amp; router PPPoE.</p>
              </div>
              <span class="lx-qstate is-none" id="qstate-allow_remote">Memeriksa...</span>
            </div>
            <div class="lx-qcard-foot">
              <span class="lx-qcard-spec">Filter forward &middot; accept</span>
              <button type="button" class="lx-qbtn" id="qbtn-allow_remote" onclick="submitQuickRule('allow_remote');">Aktifkan Rule</button>
            </div>
          </div>

          <!-- Kartu 2: Masquerade Remote ONT (Anti-403) -->
          <div class="lx-qcard" id="qcard-masq_remote">
            <div class="lx-qcard-top">
              <div>
                <h5 class="lx-qcard-title"><i class="fa fa-random" style="color:#1d4ed8;"></i> 2. Masquerade Remote ONT (Anti-Blokir 403)</h5>
                <p class="lx-qcard-desc">Menyamarkan IP IT agar router seperti TP-Link, ZTE, atau Huawei menerima koneksi login remote tanpa pesan <em>HTTP 403</em>.</p>
              </div>
              <span class="lx-qstate is-none" id="qstate-masq_remote">Memeriksa...</span>
            </div>
            <div class="lx-qcard-foot">
              <span class="lx-qcard-spec">NAT srcnat &middot; masquerade</span>
              <button type="button" class="lx-qbtn" id="qbtn-masq_remote" onclick="submitQuickRule('masq_remote');">Aktifkan Rule</button>
            </div>
          </div>

          <!-- Kartu 3: Isolasi Antar-ONT & WiFi Tamu -->
          <div class="lx-qcard" id="qcard-isolate_ont">
            <div class="lx-qcard-top">
              <div>
                <h5 class="lx-qcard-title"><i class="fa fa-ban" style="color:#dc2626;"></i> 3. Isolasi Antar-ONT &amp; Jaringan Tamu</h5>
                <p class="lx-qcard-desc">Memblokir semua klien PPPoE maupun jaringan WiFi tamu/publik agar tidak bisa mengakses halaman admin ONT milik klien lain.</p>
              </div>
              <span class="lx-qstate is-none" id="qstate-isolate_ont">Memeriksa...</span>
            </div>
            <div class="lx-qcard-foot">
              <span class="lx-qcard-spec">Filter forward &middot; drop (conn: new)</span>
              <button type="button" class="lx-qbtn" id="qbtn-isolate_ont" onclick="submitQuickRule('isolate_ont');">Aktifkan Rule</button>
            </div>
          </div>

          <!-- Kartu 4: Kunci Akses ONT ke MikroTik -->
          <div class="lx-qcard" id="qcard-block_router">
            <div class="lx-qcard-top">
              <div>
                <h5 class="lx-qcard-title"><i class="fa fa-lock" style="color:#b45309;"></i> 4. Kunci Akses Klien PPPoE ke MikroTik</h5>
                <p class="lx-qcard-desc">Mencegah perangkat dari jalur PPPoE membuka halaman login WebFig, Winbox, maupun SSH milik router MikroTik utama.</p>
              </div>
              <span class="lx-qstate is-none" id="qstate-block_router">Memeriksa...</span>
            </div>
            <div class="lx-qcard-foot">
              <span class="lx-qcard-spec">Filter input &middot; tcp 80,443,8291,22,23</span>
              <button type="button" class="lx-qbtn" id="qbtn-block_router" onclick="submitQuickRule('block_router');">Aktifkan Rule</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KOLOM KANAN 50%: Konfigurasi Otomatis Akses Remote & Halaman Isolir -->
    <div class="lx-split-col">
      <!-- Card Kanan Atas: Konfigurasi Otomatis Akses Remote Perangkat & NAT Internet -->
      <div class="lx-setup-inner">
        <div class="lx-setup-head">
          <div>
            <h4 class="lx-setup-title">Konfigurasi Otomatis Akses Remote Perangkat &amp; NAT Internet</h4>
            <p class="lx-setup-desc">Membatasi akses halaman admin router/ONT klien hanya dari jaringan tim pengelola (IT/Operator), mengisolasi jaringan publik/tamu, dan mengaktifkan jalur internet PPPoE sekaligus.</p>
          </div>
        </div>
        <form method="post" action="" autocomplete="off" style="margin:0;">
          <div class="lx-setup-grid">
            <div class="lx-field">
              <label>Jaringan Pengelola / IT <span>(Management)</span></label>
              <input type="text" id="inputAdminSubnet" name="admin_subnet" list="lxDetectedSubnets" class="lx-input" value="<?= htmlspecialchars($savedAdminSub, ENT_QUOTES); ?>" placeholder="Pilih atau ketik subnet IT" oninput="refreshQuickRuleCards();" onchange="refreshQuickRuleCards();" required>
            </div>
            <div class="lx-field">
              <label>Jaringan Klien PPPoE <span>(Target ONT)</span></label>
              <input type="text" id="inputPppoeSubnet" name="pppoe_subnet" list="lxDetectedSubnets" class="lx-input" value="<?= htmlspecialchars($savedPppoeSub, ENT_QUOTES); ?>" placeholder="Pilih atau ketik subnet PPPoE" oninput="refreshQuickRuleCards();" onchange="refreshQuickRuleCards();" required>
            </div>
            <div class="lx-field">
              <label>Interface Internet <span>(WAN)</span></label>
              <select name="wan_interface" class="lx-select" required>
                <?php
                for ($f = 0; $f < $TotalIface; $f++) {
                  $ifName = $getifaces[$f]['name'];
                  if ($ifName != "") {
                    $sel = ($ifName == $savedWanIface) ? "selected" : "";
                    echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '" ' . $sel . '>' . htmlspecialchars($ifName) . '</option>';
                  }
                }
                ?>
              </select>
            </div>
            <div class="lx-field">
              <button type="submit" name="auto_setup_pppoe_fw" class="lx-btn is-primary" style="width:100%; justify-content:center; height:36px;">
                <i class="fa fa-sliders" style="font-size:12px; color:#d4d4d8;"></i>
                <span>Pasang / Update Aturan</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Card Kanan Bawah: Konfigurasi Otomatis Halaman Isolir (Jatuh Tempo -> error.html) -->
      <div class="lx-setup-inner">
        <div class="lx-setup-head">
          <div>
            <h4 class="lx-setup-title">
              <span>Konfigurasi Otomatis Halaman Isolir (Jatuh Tempo &rarr; error.html)</span>
              <?php if ($isolirReady) { ?>
                <span class="lx-status-badge is-ok" style="font-size:11px; padding:2px 8px;"><span class="lx-dot-live"></span>Rule Isolir Aktif</span>
              <?php } else { ?>
                <span class="lx-status-badge is-warn" style="font-size:11px; padding:2px 8px;"><span class="lx-dot-warn"></span>Belum Dipasang</span>
              <?php } ?>
            </h4>
            <p class="lx-setup-desc">Memasang otomatis <strong>7 Filter Rules</strong> (Reject QUIC/HTTPS 443, Allow DNS 53, Allow Proxy 8080, Drop Internet) + <strong>1 NAT Redirect</strong> (TCP 80 &rarr; 8080) untuk pengalihan ke halaman <strong>error.html</strong>.</p>
          </div>
        </div>
        <form method="post" action="" autocomplete="off" style="margin:0;">
          <div class="lx-setup-grid">
            <div class="lx-field">
              <label>Subnet IP Isolir <span>(Jatuh Tempo)</span></label>
              <input type="text" name="isolir_subnet" list="lxDetectedSubnets" class="lx-input" value="<?= htmlspecialchars($savedIsolirSub, ENT_QUOTES); ?>" placeholder="10.254.254.0/24" required>
            </div>
            <div class="lx-field">
              <label>Port Web Proxy <span>(Halaman error.html)</span></label>
              <input type="number" name="proxy_port" class="lx-input" value="<?= htmlspecialchars($savedProxyPort, ENT_QUOTES); ?>" placeholder="8080" required>
            </div>
            <div class="lx-field">
              <label>Konfigurasi Web Proxy</label>
              <select name="setup_web_proxy" class="lx-select" style="font-family:inherit !important;">
                <option value="1">Aktifkan Web Proxy &amp; Deny Isolir</option>
                <option value="0">Hanya Rule Firewall &amp; NAT</option>
              </select>
            </div>
            <div class="lx-field">
              <button type="submit" name="auto_setup_isolir_fw" class="lx-btn is-primary" style="width:100%; justify-content:center; height:36px;">
                <i class="fa fa-ban" style="font-size:12px; color:#d4d4d8;"></i>
                <span>Pasang Rule Isolir</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Filter Tabs & Dynamic Toggle -->
  <div class="lx-toolbar-tabs">
    <div class="lx-tab-group">
      <button type="button" class="lx-tab is-active" onclick="setFwTab('all', this);">
        <span>Semua Rules</span>
        <span class="lx-tab-badge"><?= ($TotalFilter + $TotalNat); ?></span>
      </button>
      <button type="button" class="lx-tab" onclick="setFwTab('mikhmon', this);">
        <span>Khusus PPPoE</span>
        <span class="lx-tab-badge"><?= $mikhmonRulesCount; ?></span>
      </button>
      <button type="button" class="lx-tab" onclick="setFwTab('filter', this);">
        <span>Filter Rules</span>
        <span class="lx-tab-badge"><?= $TotalFilter; ?></span>
      </button>
      <button type="button" class="lx-tab" onclick="setFwTab('nat', this);">
        <span>NAT Rules</span>
        <span class="lx-tab-badge"><?= $TotalNat; ?></span>
      </button>
    </div>

    <label class="lx-dyn-toggle">
      <input type="checkbox" id="hideDynamicCheck" onchange="applyFwFilters();">
      <span>Sembunyikan Rule Dynamic</span>
    </label>
  </div>

  <!-- Tabel Filter Rules -->
  <div id="secFilterBlock">
    <div class="lx-sec-bar">
      <span>Firewall Filter Rules (Kontrol Akses Remote &amp; Isolasi Jaringan)</span>
      <span><?= $TotalFilter; ?> rules</span>
    </div>
    <div class="lx-table-wrap">
      <table class="table lx-table fw-data-table">
        <thead>
          <tr>
            <th style="text-align:center; width:115px;">Aksi</th>
            <th style="width:105px;">Action</th>
            <th>Chain</th>
            <th>Src. Address</th>
            <th>Dst. Address</th>
            <th>Protocol / Port</th>
            <th>In / Out Interface</th>
            <th>Keterangan (Comment)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($i = 0; $i < $TotalFilter; $i++) {
            $flt = $getfilters[$i];
            $fid = $flt['.id'];
            $fact = $flt['action'];
            $fchain = $flt['chain'];
            $fsrc = $flt['src-address'];
            $fdst = $flt['dst-address'];
            $fproto = $flt['protocol'];
            $fdport = $flt['dst-port'];
            $finif = $flt['in-interface'];
            $foutif = $flt['out-interface'];
            $fcomm = $flt['comment'];
            $fdis = isset($flt['disabled']) && $flt['disabled'] == "true";
            $fdyn = isset($flt['dynamic']) && $flt['dynamic'] == "true";
            $isMikhmonRule = (strpos($fcomm, "Mikhmon-PPPoE:") !== false || stripos($fcomm, "Isolir") !== false);
            $delUrl = "./?ppp=firewall&act=remove&ftype=filter&fid=" . urlencode($fid) . "&session=" . urlencode($session);
            $pillClass = ($fact == "accept") ? "is-accept" : (($fact == "drop" || $fact == "reject") ? "is-drop" : "is-other");
            $ifDisp = trim(($finif != "" ? "in:" . $finif : "") . " " . ($foutif != "" ? "out:" . $foutif : ""));
            ?>
            <tr
              class="fw-row <?= $isMikhmonRule ? 'lx-row-highlight' : ''; ?> <?= $fdis ? 'lx-row-disabled' : ''; ?>"
              data-group="filter"
              data-mikhmon="<?= $isMikhmonRule ? '1' : '0'; ?>"
              data-dynamic="<?= $fdyn ? '1' : '0'; ?>"
            >
              <td style="text-align:center;">
                <?php if ($fdyn) { ?>
                  <span class="lx-dyn-tag" title="Rule otomatis bawaan RouterOS">Dynamic</span>
                <?php } else { ?>
                  <div class="lx-action-group">
                    <span
                      class="lx-icon-btn"
                      title="Edit Rule"
                      data-type="filter"
                      data-id="<?= htmlspecialchars($fid, ENT_QUOTES); ?>"
                      data-chain="<?= htmlspecialchars($fchain, ENT_QUOTES); ?>"
                      data-action="<?= htmlspecialchars($fact, ENT_QUOTES); ?>"
                      data-src="<?= htmlspecialchars($fsrc, ENT_QUOTES); ?>"
                      data-dst="<?= htmlspecialchars($fdst, ENT_QUOTES); ?>"
                      data-proto="<?= htmlspecialchars($fproto, ENT_QUOTES); ?>"
                      data-dport="<?= htmlspecialchars($fdport, ENT_QUOTES); ?>"
                      data-inif="<?= htmlspecialchars($finif, ENT_QUOTES); ?>"
                      data-outif="<?= htmlspecialchars($foutif, ENT_QUOTES); ?>"
                      data-toaddr=""
                      data-toport=""
                      data-comment="<?= htmlspecialchars($fcomm, ENT_QUOTES); ?>"
                      onclick="openEditFwModal(this);"
                    >
                      <i class="fa fa-pencil"></i>
                    </span>
                    <?php if ($fdis) { ?>
                      <span class="lx-icon-btn is-locked" title="Aktifkan Rule" onclick="loadpage('./?ppp=firewall&act=enable&ftype=filter&fid=<?= urlencode($fid); ?>&session=<?= urlencode($session); ?>');"><i class="fa fa-lock"></i></span>
                    <?php } else { ?>
                      <span class="lx-icon-btn" title="Nonaktifkan Rule" onclick="loadpage('./?ppp=firewall&act=disable&ftype=filter&fid=<?= urlencode($fid); ?>&session=<?= urlencode($session); ?>');"><i class="fa fa-unlock"></i></span>
                    <?php } ?>
                    <span class="lx-icon-btn is-danger" title="Hapus Rule" data-name="<?= htmlspecialchars($fcomm != "" ? $fcomm : $fact, ENT_QUOTES); ?>" data-url="<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>" onclick="openDelFwModal(this);"><i class="fa fa-trash-o"></i></span>
                  </div>
                <?php } ?>
              </td>
              <td><span class="lx-pill <?= $pillClass; ?>"><?= htmlspecialchars($fact); ?></span></td>
              <td><span class="lx-mono"><?= htmlspecialchars($fchain); ?></span></td>
              <td><?= $fsrc != "" ? '<span class="lx-mono">' . htmlspecialchars($fsrc) . '</span>' : '<span class="lx-empty">any</span>'; ?></td>
              <td><?= $fdst != "" ? '<span class="lx-mono">' . htmlspecialchars($fdst) . '</span>' : '<span class="lx-empty">any</span>'; ?></td>
              <td>
                <?php if ($fproto != "" || $fdport != "") { ?>
                  <span class="lx-mono"><?= htmlspecialchars(trim($fproto . " " . $fdport)); ?></span>
                <?php } else { ?>
                  <span class="lx-empty">&mdash;</span>
                <?php } ?>
              </td>
              <td><?= $ifDisp != "" ? '<span class="lx-mono">' . htmlspecialchars($ifDisp) . '</span>' : '<span class="lx-empty">&mdash;</span>'; ?></td>
              <td style="font-weight:<?= $isMikhmonRule ? '600' : '400'; ?>;"><?= $fcomm != "" ? htmlspecialchars($fcomm) : '<span class="lx-empty">&mdash;</span>'; ?></td>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel NAT Rules -->
  <div id="secNatBlock">
    <div class="lx-sec-bar" style="border-top:1px solid #e4e4e7;">
      <span>Firewall NAT Rules (Masquerade &amp; Port Forwarding)</span>
      <span><?= $TotalNat; ?> rules</span>
    </div>
    <div class="lx-table-wrap">
      <table class="table lx-table fw-data-table">
        <thead>
          <tr>
            <th style="text-align:center; width:115px;">Aksi</th>
            <th style="width:105px;">Action</th>
            <th>Chain</th>
            <th>Src. Address</th>
            <th>Dst. Address</th>
            <th>Protocol / Port</th>
            <th>In / Out Interface</th>
            <th>To Address / Port</th>
            <th>Keterangan (Comment)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($i = 0; $i < $TotalNat; $i++) {
            $nat = $getnats[$i];
            $nid = $nat['.id'];
            $nact = $nat['action'];
            $nchain = $nat['chain'];
            $nsrc = $nat['src-address'];
            $ndst = $nat['dst-address'];
            $nproto = $nat['protocol'];
            $ndport = $nat['dst-port'];
            $ninif = $nat['in-interface'];
            $noutif = $nat['out-interface'];
            $ntoaddr = $nat['to-addresses'];
            $ntoport = $nat['to-ports'];
            $ncomm = $nat['comment'];
            $ndis = isset($nat['disabled']) && $nat['disabled'] == "true";
            $ndyn = isset($nat['dynamic']) && $nat['dynamic'] == "true";
            $isMikhmonRule = (strpos($ncomm, "Mikhmon-PPPoE:") !== false || stripos($ncomm, "Masquerade Remote") !== false || stripos($ncomm, "Isolir") !== false);
            $delUrl = "./?ppp=firewall&act=remove&ftype=nat&fid=" . urlencode($nid) . "&session=" . urlencode($session);
            $ifDisp = trim(($ninif != "" ? "in:" . $ninif : "") . " " . ($noutif != "" ? "out:" . $noutif : ""));
            $toDisp = trim($ntoaddr . ($ntoport != "" ? ":" . $ntoport : ""));
            ?>
            <tr
              class="fw-row <?= $isMikhmonRule ? 'lx-row-highlight' : ''; ?> <?= $ndis ? 'lx-row-disabled' : ''; ?>"
              data-group="nat"
              data-mikhmon="<?= $isMikhmonRule ? '1' : '0'; ?>"
              data-dynamic="<?= $ndyn ? '1' : '0'; ?>"
            >
              <td style="text-align:center;">
                <?php if ($ndyn) { ?>
                  <span class="lx-dyn-tag" title="Rule otomatis bawaan RouterOS">Dynamic</span>
                <?php } else { ?>
                  <div class="lx-action-group">
                    <span
                      class="lx-icon-btn"
                      title="Edit Rule"
                      data-type="nat"
                      data-id="<?= htmlspecialchars($nid, ENT_QUOTES); ?>"
                      data-chain="<?= htmlspecialchars($nchain, ENT_QUOTES); ?>"
                      data-action="<?= htmlspecialchars($nact, ENT_QUOTES); ?>"
                      data-src="<?= htmlspecialchars($nsrc, ENT_QUOTES); ?>"
                      data-dst="<?= htmlspecialchars($ndst, ENT_QUOTES); ?>"
                      data-proto="<?= htmlspecialchars($nproto, ENT_QUOTES); ?>"
                      data-dport="<?= htmlspecialchars($ndport, ENT_QUOTES); ?>"
                      data-inif="<?= htmlspecialchars($ninif, ENT_QUOTES); ?>"
                      data-outif="<?= htmlspecialchars($noutif, ENT_QUOTES); ?>"
                      data-toaddr="<?= htmlspecialchars($ntoaddr, ENT_QUOTES); ?>"
                      data-toport="<?= htmlspecialchars($ntoport, ENT_QUOTES); ?>"
                      data-comment="<?= htmlspecialchars($ncomm, ENT_QUOTES); ?>"
                      onclick="openEditFwModal(this);"
                    >
                      <i class="fa fa-pencil"></i>
                    </span>
                    <?php if ($ndis) { ?>
                      <span class="lx-icon-btn is-locked" title="Aktifkan Rule" onclick="loadpage('./?ppp=firewall&act=enable&ftype=nat&fid=<?= urlencode($nid); ?>&session=<?= urlencode($session); ?>');"><i class="fa fa-lock"></i></span>
                    <?php } else { ?>
                      <span class="lx-icon-btn" title="Nonaktifkan Rule" onclick="loadpage('./?ppp=firewall&act=disable&ftype=nat&fid=<?= urlencode($nid); ?>&session=<?= urlencode($session); ?>');"><i class="fa fa-unlock"></i></span>
                    <?php } ?>
                    <span class="lx-icon-btn is-danger" title="Hapus Rule" data-name="<?= htmlspecialchars($ncomm != "" ? $ncomm : $nact, ENT_QUOTES); ?>" data-url="<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>" onclick="openDelFwModal(this);"><i class="fa fa-trash-o"></i></span>
                  </div>
                <?php } ?>
              </td>
              <td>
                <span class="lx-pill <?= $nact == 'masquerade' ? 'is-masq' : 'is-other'; ?>"><?= htmlspecialchars($nact); ?></span>
              </td>
              <td><span class="lx-mono"><?= htmlspecialchars($nchain); ?></span></td>
              <td><?= $nsrc != "" ? '<span class="lx-mono">' . htmlspecialchars($nsrc) . '</span>' : '<span class="lx-empty">all</span>'; ?></td>
              <td><?= $ndst != "" ? '<span class="lx-mono">' . htmlspecialchars($ndst) . '</span>' : '<span class="lx-empty">any</span>'; ?></td>
              <td>
                <?php if ($nproto != "" || $ndport != "") { ?>
                  <span class="lx-mono"><?= htmlspecialchars(trim($nproto . " " . $ndport)); ?></span>
                <?php } else { ?>
                  <span class="lx-empty">&mdash;</span>
                <?php } ?>
              </td>
              <td><?= $ifDisp != "" ? '<span class="lx-mono">' . htmlspecialchars($ifDisp) . '</span>' : '<span class="lx-empty">&mdash;</span>'; ?></td>
              <td><?= $toDisp != "" ? '<span class="lx-mono">' . htmlspecialchars($toDisp) . '</span>' : '<span class="lx-empty">&mdash;</span>'; ?></td>
              <td style="font-weight:<?= $isMikhmonRule ? '600' : '400'; ?>;"><?= $ncomm != "" ? htmlspecialchars($ncomm) : '<span class="lx-empty">&mdash;</span>'; ?></td>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="lx-footer">
    <div>Baris hijau lembut menandakan aturan aktif <strong>Mikhmon-PPPoE &amp; Isolir</strong> &nbsp;&middot;&nbsp; Klik <strong>Help (?)</strong> di kanan atas untuk panduan setting sisi ONT</div>
    <div>RouterOS v6 &amp; v7 Compatible</div>
  </div>
</div>

<!-- Modal Tambah Rule Manual -->
<div id="lxAddFwModal" class="lx-overlay" onclick="if(event.target===this) closeAddFwModal();">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Tambah Rule Firewall / NAT</h4>
      <button type="button" class="lx-dialog-close" onclick="closeAddFwModal();"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <div class="lx-dialog-body">
        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Tabel Firewall</label>
            <select name="rule_type" id="addFwType" class="lx-modal-select" onchange="toggleManualFwFields();">
              <option value="filter">Firewall Filter</option>
              <option value="nat">Firewall NAT</option>
            </select>
          </div>
          <div class="lx-modal-field">
            <label>Chain</label>
            <input type="text" name="chain" id="addFwChain" list="lxFwChainList" class="lx-modal-input lx-mono-input" value="forward" required>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Action</label>
            <input type="text" name="action" id="addFwAction" list="lxFwActionList" class="lx-modal-input lx-mono-input" value="accept" required>
          </div>
          <div class="lx-modal-field">
            <label>Protocol <span>(Opsional)</span></label>
            <select name="protocol" class="lx-modal-select lx-mono-input">
              <option value="">(Semua / Any)</option>
              <option value="tcp">tcp</option>
              <option value="udp">udp</option>
              <option value="icmp">icmp</option>
            </select>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Src. Address <span>(Opsional)</span></label>
            <input type="text" name="src_address" list="lxDetectedSubnets" class="lx-modal-input lx-mono-input" placeholder="192.168.100.0/24">
          </div>
          <div class="lx-modal-field">
            <label>Dst. Address <span>(Opsional)</span></label>
            <input type="text" name="dst_address" list="lxDetectedSubnets" class="lx-modal-input lx-mono-input" placeholder="10.30.30.0/24">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Dst. Port <span>(Jika TCP/UDP)</span></label>
            <input type="text" name="dst_port" class="lx-modal-input lx-mono-input" placeholder="80,443">
          </div>
          <div class="lx-modal-field">
            <label>In. Interface <span>(Opsional)</span></label>
            <select name="in_interface" class="lx-modal-select lx-mono-input">
              <option value="">(Semua / Any)</option>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '">' . htmlspecialchars($ifName) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Out. Interface <span>(Opsional)</span></label>
            <select name="out_interface" class="lx-modal-select lx-mono-input">
              <option value="">(Semua / Any)</option>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '">' . htmlspecialchars($ifName) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="lx-modal-field" id="addNatToWrap" style="display:none;">
            <label>To Addresses <span>(Khusus NAT)</span></label>
            <input type="text" name="to_addresses" class="lx-modal-input lx-mono-input" placeholder="10.30.30.101">
          </div>
        </div>

        <div class="lx-form-row" id="addNatPortRow" style="display:none;">
          <div class="lx-modal-field">
            <label>To Ports <span>(Khusus NAT)</span></label>
            <input type="text" name="to_ports" class="lx-modal-input lx-mono-input" placeholder="80">
          </div>
          <div></div>
        </div>

        <div class="lx-modal-field">
          <label>Keterangan (Comment)</label>
          <input type="text" name="comment" class="lx-modal-input" placeholder="Catatan aturan firewall">
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closeAddFwModal();">Batal</button>
        <button type="submit" name="add_manual_fw" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Rule</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Rule Manual -->
<div id="lxEditFwModal" class="lx-overlay" onclick="if(event.target===this) closeEditFwModal();">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Edit Rule Firewall / NAT</h4>
      <button type="button" class="lx-dialog-close" onclick="closeEditFwModal();"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <input type="hidden" name="rule_type" id="editFwType">
      <input type="hidden" name="rule_id" id="editFwId">
      <div class="lx-dialog-body">
        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Chain</label>
            <input type="text" name="chain" id="editFwChain" list="lxFwChainList" class="lx-modal-input lx-mono-input" required>
          </div>
          <div class="lx-modal-field">
            <label>Action</label>
            <input type="text" name="action" id="editFwAction" list="lxFwActionList" class="lx-modal-input lx-mono-input" required>
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Src. Address <span>(Opsional)</span></label>
            <input type="text" name="src_address" id="editFwSrc" list="lxDetectedSubnets" class="lx-modal-input lx-mono-input">
          </div>
          <div class="lx-modal-field">
            <label>Dst. Address <span>(Opsional)</span></label>
            <input type="text" name="dst_address" id="editFwDst" list="lxDetectedSubnets" class="lx-modal-input lx-mono-input">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>Protocol <span>(Opsional)</span></label>
            <select name="protocol" id="editFwProto" class="lx-modal-select lx-mono-input">
              <option value="">(Semua / Any)</option>
              <option value="tcp">tcp</option>
              <option value="udp">udp</option>
              <option value="icmp">icmp</option>
            </select>
          </div>
          <div class="lx-modal-field">
            <label>Dst. Port <span>(Jika TCP/UDP)</span></label>
            <input type="text" name="dst_port" id="editFwDport" class="lx-modal-input lx-mono-input">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-modal-field">
            <label>In. Interface <span>(Opsional)</span></label>
            <select name="in_interface" id="editFwInIf" class="lx-modal-select lx-mono-input">
              <option value="">(Semua / Any)</option>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '">' . htmlspecialchars($ifName) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="lx-modal-field">
            <label>Out. Interface <span>(Opsional)</span></label>
            <select name="out_interface" id="editFwOutIf" class="lx-modal-select lx-mono-input">
              <option value="">(Semua / Any)</option>
              <?php
              for ($f = 0; $f < $TotalIface; $f++) {
                $ifName = $getifaces[$f]['name'];
                if ($ifName != "") echo '<option value="' . htmlspecialchars($ifName, ENT_QUOTES) . '">' . htmlspecialchars($ifName) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>

        <div class="lx-form-row" id="editNatToRow" style="display:none;">
          <div class="lx-modal-field">
            <label>To Addresses <span>(Khusus NAT)</span></label>
            <input type="text" name="to_addresses" id="editFwToAddr" class="lx-modal-input lx-mono-input">
          </div>
          <div class="lx-modal-field">
            <label>To Ports <span>(Khusus NAT)</span></label>
            <input type="text" name="to_ports" id="editFwToPort" class="lx-modal-input lx-mono-input">
          </div>
        </div>

        <div class="lx-modal-field">
          <label>Keterangan (Comment)</label>
          <input type="text" name="comment" id="editFwComment" class="lx-modal-input">
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closeEditFwModal();">Batal</button>
        <button type="submit" name="edit_manual_fw" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Perubahan</span></button>
      </div>
    </form>
  </div>
</div>

<datalist id="lxFwChainList">
  <option value="forward">
  <option value="input">
  <option value="output">
  <option value="srcnat">
  <option value="dstnat">
</datalist>

<datalist id="lxFwActionList">
  <option value="accept">
  <option value="drop">
  <option value="reject">
  <option value="masquerade">
  <option value="dst-nat">
  <option value="src-nat">
  <option value="redirect">
  <option value="passthrough">
</datalist>

<!-- Modal Penjelasan / Help Firewall Remote Perangkat -->
<div id="lxHelpFwModal" class="lx-overlay" onclick="if(event.target===this) closeHelpFwModal();">
  <div class="lx-dialog is-wide">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">
        <i class="fa fa-question-circle" style="color:#0284c7;"></i>
        <span>Panduan Akses Remote Perangkat &amp; Setting Sisi ONT</span>
      </h4>
      <button type="button" class="lx-dialog-close" onclick="closeHelpFwModal();"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <div class="lx-help-card">
        <h5><i class="fa fa-building-o" style="color:#0284c7;"></i> 1. Analogi &amp; Cara Kerja di Kantor / Gedung</h5>
        <p>
          Bayangkan setiap modem/ONT atau router WiFi di tiap divisi, lantai, atau ruangan kantor memiliki panel kontrol (halaman login pengaturan). Tanpa pembatasan firewall, siapa saja yang terhubung ke WiFi tamu maupun dari ruangan lain bisa mencoba mengakses panel kontrol perangkat divisi sebelah.
        </p>
        <p style="margin-top:6px;">
          Aturan pada halaman ini bekerja seperti <strong>kartu akses khusus tim IT</strong>: hanya komputer dari <strong>Jaringan Pengelola / IT</strong> yang diizinkan membuka halaman admin seluruh perangkat PPPoE dari jarak jauh (untuk ganti sandi WiFi atau <em>reboot</em>), sementara pengguna publik/tamu dan antar-divisi tetap terisolasi.
        </p>
      </div>

      <div class="lx-help-card">
        <h5><i class="fa fa-sitemap" style="color:#7c3aed;"></i> 2. Menyesuaikan Segmen IP Tiap Lokasi</h5>
        <p>
          Setiap kantor atau instansi memiliki skema IP lokal yang berbeda (misalnya <span class="lx-help-code">192.168.1.0/24</span>, <span class="lx-help-code">192.168.100.0/24</span>, atau <span class="lx-help-code">172.16.0.0/24</span>). Kolom input pada halaman ini sudah membaca daftar subnet yang aktif di MikroTik secara otomatis—cukup klik kolom input untuk memilih segmen jaringan tempat komputer pengelola/IT Anda terhubung.
        </p>
      </div>

      <div class="lx-help-card">
        <h5><i class="fa fa-shield" style="color:#047857;"></i> 3. Ringkasan Aturan Otomatis di MikroTik</h5>
        <ul>
          <li><strong>Allow Management Remote Access (Accept):</strong> Membuka jalur akses dari <em>Jaringan Pengelola / IT</em> menuju seluruh IP <em>Jaringan Klien PPPoE</em>.</li>
          <li><strong>Masquerade Remote Access (Masquerade):</strong> Menyamarkan IP IT agar router/ONT (seperti TP-Link/ZTE) menerima akses login remote tanpa pesan <em>HTTP 403</em>.</li>
          <li><strong>Isolate Client &amp; Guest Access (Drop):</strong> Memblokir akses dari WiFi tamu/publik maupun antar-klien PPPoE agar tidak bisa membuka halaman admin perangkat lain.</li>
          <li><strong>Block Client Access to Router (Drop):</strong> Mencegah perangkat klien PPPoE mengakses port manajemen MikroTik (<span class="lx-help-code">port 80, 443, 8291, 22, 23</span>).</li>
          <li><strong>NAT Internet Access (Masquerade):</strong> Menghubungkan segmen IP klien PPPoE ke interface internet utama (<span class="lx-help-code">WAN</span>) agar bisa mengakses internet.</li>
        </ul>
      </div>

      <div class="lx-help-card">
        <h5><i class="fa fa-wifi" style="color:#0284c7;"></i> 4. Panduan Setting Wajib di Sisi ONT / Router Klien (Per Merek)</h5>
        <p style="margin-bottom:6px;">
          Secara bawaan pabrik, ONT memblokir akses halaman login dari jalur WAN/PPPoE. Sebelum ONT dipasang ke ruangan, buka halaman admin ONT dan aktifkan akses WAN sesuai mereknya:
        </p>
        <ul>
          <li>
            <strong>ONT Huawei (HG8245H / HG8145V5 / EG8141A5):</strong><br>
            Buka menu <strong>Security &rarr; ONT Access Control Configuration</strong> (atau <em>Device Access Control</em>), centang <strong>Enable WAN-side PC to access the ONT via HTTP</strong>, lalu klik <strong>Apply</strong>.
          </li>
          <li>
            <strong>ONT ZTE (F609 / F660 / F670L):</strong><br>
            Buka menu <strong>Security &rarr; Remote Management</strong> (atau <em>Administration &rarr; Service Control</em>), pada baris <strong>HTTP</strong> ubah antarmuka <strong>WAN</strong> menjadi <strong>Permit / Enable</strong>, lalu klik <strong>Submit</strong>.
          </li>
          <li>
            <strong>ONT Fiberhome (HG6145D2 / AN5506):</strong><br>
            Buka menu <strong>Security &rarr; ACL</strong> (<em>Access Control List</em>) atau <em>Remote Management</em>, tambahkan rule baru: <strong>Direction = WAN</strong>, <strong>Service = HTTP</strong>, <strong>Action = Permit</strong>, lalu simpan.
          </li>
          <li>
            <strong>Router WiFi Tambahan (TP-Link / Tenda / Ruijie / Totolink):</strong><br>
            Buka menu <strong>Security / System Tools &rarr; Remote Management</strong>, aktifkan <strong>Remote Web Management</strong> (port <span class="lx-help-code">80</span> atau <span class="lx-help-code">8080</span>) dan isi IP izin dengan <span class="lx-help-code">255.255.255.255</span> (aman karena sudah dikunci ketat oleh Firewall MikroTik).
          </li>
          <li>
            <strong>Ganti Password Admin Bawaan:</strong> Ubah password login halaman admin ONT dari bawaan pabrik agar tidak bisa diakses oleh pengguna di ruangan tersebut.
          </li>
        </ul>
      </div>

      <div class="lx-help-card" style="margin-bottom:0;">
        <h5><i class="fa fa-check-square-o" style="color:#b45309;"></i> 5. Urutan Eksekusi dari Awal Sampai Siap Remote</h5>
        <ul>
          <li><strong>Langkah 1 (Firewall &amp; NAT):</strong> Pastikan <em>Jaringan Pengelola / IT</em>, <em>Jaringan Klien PPPoE</em>, dan <em>Interface WAN</em> sudah sesuai, lalu klik <strong>Pasang / Update Aturan</strong> (atau aktifkan kartu rule di sebelah kiri) hingga indikator menyala hijau <strong>Aktif</strong>.</li>
          <li><strong>Langkah 2 (Kunci IP di PPP Secrets):</strong> Pada menu <strong>PPP Secrets</strong>, isi kolom <strong>Remote Address</strong> dengan IP statis per perangkat (contoh: <span class="lx-help-code">10.30.30.3</span> untuk ruangan pertama, <span class="lx-help-code">10.30.30.4</span> untuk berikutnya).</li>
          <li><strong>Langkah 3 (Setting ONT):</strong> Aktifkan fitur <em>Remote WAN / HTTP</em> pada ONT sesuai panduan poin 4 di atas, lalu pasang ONT ke jalur FO ruangan.</li>
          <li><strong>Langkah 4 (Buka Remote):</strong> Buka menu <strong>PPP Active</strong> di web ini, lalu klik langsung alamat IP perangkat yang sedang online untuk membuka panel admin ONT di tab baru.</li>
        </ul>
      </div>
    </div>
    <div class="lx-dialog-foot">
      <button type="button" class="lx-btn-modal is-primary" onclick="closeHelpFwModal();">Mengerti</button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Hapus Rule -->
<div id="lxDelFwModal" class="lx-overlay" onclick="if(event.target===this) closeDelFwModal();">
  <div class="lx-dialog" style="max-width:400px;">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Hapus Rule Firewall</h4>
      <button type="button" class="lx-dialog-close" onclick="closeDelFwModal();"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Yakin ingin menghapus aturan <strong id="delFwTarget" style="color:#09090b;"></strong> dari MikroTik?
      </p>
    </div>
    <div class="lx-dialog-foot">
      <button type="button" class="lx-btn-modal" onclick="closeDelFwModal();">Batal</button>
      <button type="button" id="delFwConfirmBtn" class="lx-btn-modal is-danger"><i class="fa fa-trash-o"></i><span>Hapus</span></button>
    </div>
  </div>
</div>

<script>
  var currentFwTab = 'all';
  var quickStatusMap = <?= json_encode($quickStatusMap); ?> || {};
  var quickCurrentStates = {};

  function refreshQuickRuleCards() {
    var adminSub = (document.getElementById('inputAdminSubnet').value || '').trim();
    var pppoeSub = (document.getElementById('inputPppoeSubnet').value || '').trim();

    document.getElementById('qBadgeAdmin').textContent = adminSub || '-';
    document.getElementById('qBadgePppoe').textContent = pppoeSub || '-';

    var pairBucket = quickStatusMap[adminSub + '|' + pppoeSub] || {};
    var pppoeBucket = quickStatusMap['*|' + pppoeSub] || {};

    var states = {
      allow_remote: pairBucket.allow_remote || 'none',
      masq_remote: pairBucket.masq_remote || 'none',
      isolate_ont: pppoeBucket.isolate_ont || 'none',
      block_router: pppoeBucket.block_router || 'none'
    };
    quickCurrentStates = states;

    var keys = ['allow_remote', 'masq_remote', 'isolate_ont', 'block_router'];
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var st = states[k];
      var card = document.getElementById('qcard-' + k);
      var badge = document.getElementById('qstate-' + k);
      var btn = document.getElementById('qbtn-' + k);

      if (!card || !badge || !btn) continue;

      if (st === 'active') {
        card.classList.add('is-active');
        badge.className = 'lx-qstate is-on';
        badge.innerHTML = '<span class="lx-dot-live"></span> Aktif';
        btn.className = 'lx-qbtn is-stop';
        btn.innerHTML = '<i class="fa fa-pause-circle"></i> Nonaktifkan';
      } else if (st === 'disabled') {
        card.classList.remove('is-active');
        badge.className = 'lx-qstate is-off';
        badge.innerHTML = '<span class="lx-dot-warn"></span> Nonaktif';
        btn.className = 'lx-qbtn';
        btn.innerHTML = '<i class="fa fa-play-circle"></i> Aktifkan Kembali';
      } else {
        card.classList.remove('is-active');
        badge.className = 'lx-qstate is-none';
        badge.textContent = 'Belum Dipasang';
        btn.className = 'lx-qbtn';
        btn.innerHTML = '<i class="fa fa-check-circle"></i> Aktifkan Rule';
      }
    }
  }

  function submitQuickRule(ruleKey) {
    var adminSub = (document.getElementById('inputAdminSubnet').value || '').trim();
    var pppoeSub = (document.getElementById('inputPppoeSubnet').value || '').trim();
    if (!adminSub || !pppoeSub) {
      alert('Isi Jaringan Pengelola / IT dan Jaringan Klien PPPoE terlebih dahulu.');
      return;
    }
    var curState = quickCurrentStates[ruleKey] || 'none';
    document.getElementById('qFormKey').value = ruleKey;
    document.getElementById('qFormAction').value = (curState === 'active') ? 'disable' : 'enable';
    document.getElementById('qFormAdmin').value = adminSub;
    document.getElementById('qFormPppoe').value = pppoeSub;
    document.getElementById('lxQuickRuleForm').submit();
  }

  function openAddFwModal() {
    document.getElementById('lxAddFwModal').classList.add('is-open');
  }
  function closeAddFwModal() {
    document.getElementById('lxAddFwModal').classList.remove('is-open');
  }

  function toggleManualFwFields() {
    var rtype = document.getElementById('addFwType').value;
    var chainEl = document.getElementById('addFwChain');
    var actEl = document.getElementById('addFwAction');
    var natToWrap = document.getElementById('addNatToWrap');
    var natPortRow = document.getElementById('addNatPortRow');

    if (rtype === 'nat') {
      chainEl.value = 'srcnat';
      actEl.value = 'masquerade';
      natToWrap.style.display = '';
      natPortRow.style.display = '';
    } else {
      chainEl.value = 'forward';
      actEl.value = 'accept';
      natToWrap.style.display = 'none';
      natPortRow.style.display = 'none';
    }
  }

  function openEditFwModal(el) {
    var rtype = el.getAttribute('data-type') || 'filter';
    document.getElementById('editFwType').value = rtype;
    document.getElementById('editFwId').value = el.getAttribute('data-id') || '';
    document.getElementById('editFwChain').value = el.getAttribute('data-chain') || '';
    document.getElementById('editFwAction').value = el.getAttribute('data-action') || '';
    document.getElementById('editFwSrc').value = el.getAttribute('data-src') || '';
    document.getElementById('editFwDst').value = el.getAttribute('data-dst') || '';
    document.getElementById('editFwProto').value = el.getAttribute('data-proto') || '';
    document.getElementById('editFwDport').value = el.getAttribute('data-dport') || '';
    document.getElementById('editFwInIf').value = el.getAttribute('data-inif') || '';
    document.getElementById('editFwOutIf').value = el.getAttribute('data-outif') || '';
    document.getElementById('editFwToAddr').value = el.getAttribute('data-toaddr') || '';
    document.getElementById('editFwToPort').value = el.getAttribute('data-toport') || '';
    document.getElementById('editFwComment').value = el.getAttribute('data-comment') || '';

    var natToRow = document.getElementById('editNatToRow');
    natToRow.style.display = (rtype === 'nat') ? '' : 'none';

    document.getElementById('lxEditFwModal').classList.add('is-open');
  }
  function closeEditFwModal() {
    document.getElementById('lxEditFwModal').classList.remove('is-open');
  }

  function openHelpFwModal() {
    document.getElementById('lxHelpFwModal').classList.add('is-open');
  }
  function closeHelpFwModal() {
    document.getElementById('lxHelpFwModal').classList.remove('is-open');
  }

  function openDelFwModal(el) {
    document.getElementById('delFwTarget').textContent = el.getAttribute('data-name') || '';
    var url = el.getAttribute('data-url') || '';
    document.getElementById('delFwConfirmBtn').onclick = function() {
      closeDelFwModal();
      loadpage(url);
    };
    document.getElementById('lxDelFwModal').classList.add('is-open');
  }
  function closeDelFwModal() {
    document.getElementById('lxDelFwModal').classList.remove('is-open');
  }

  function setFwTab(tabName, btnEl) {
    currentFwTab = tabName;
    var tabs = document.querySelectorAll('#mhm-pppfirewall .lx-tab');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].classList.remove('is-active');
    }
    if (btnEl) btnEl.classList.add('is-active');

    var filterBlock = document.getElementById('secFilterBlock');
    var natBlock = document.getElementById('secNatBlock');
    if (tabName === 'filter') {
      filterBlock.style.display = '';
      natBlock.style.display = 'none';
    } else if (tabName === 'nat') {
      filterBlock.style.display = 'none';
      natBlock.style.display = '';
    } else {
      filterBlock.style.display = '';
      natBlock.style.display = '';
    }
    applyFwFilters();
  }

  function applyFwFilters() {
    var query = (document.getElementById('fwSearchInput').value || '').toLowerCase().trim();
    var hideDyn = document.getElementById('hideDynamicCheck').checked;
    var rows = document.querySelectorAll('#mhm-pppfirewall .fw-row');

    for (var i = 0; i < rows.length; i++) {
      var r = rows[i];
      var isDyn = r.getAttribute('data-dynamic') === '1';
      var isMikhmon = r.getAttribute('data-mikhmon') === '1';
      var text = r.textContent.toLowerCase();

      var visible = true;
      if (hideDyn && isDyn) visible = false;
      if (currentFwTab === 'mikhmon' && !isMikhmon) visible = false;
      if (query !== '' && text.indexOf(query) === -1) visible = false;

      r.style.display = visible ? '' : 'none';
    }
  }

  refreshQuickRuleCards();
  applyFwFilters();
</script>