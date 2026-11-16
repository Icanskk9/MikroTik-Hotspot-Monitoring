<?php
/*
 *  Exam / ANBK Mode Controller (1-Click Internet Control)
 *  Uses /ip/firewall/filter/enable & /ip/firewall/filter/disable
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $EXAM_RULE_COMMENT = "EXAM-MODE-BLOCK";
  $EXAM_BYPASS_LIST  = "EXAM-MODE-BYPASS";

  function findExamFilterRule($API, $commentTag) {
    $rules = $API->comm("/ip/firewall/filter/print");
    if (is_array($rules)) {
      foreach ($rules as $r) {
        if (isset($r['comment']) && (strpos($r['comment'], $commentTag) !== false || strpos($r['comment'], "MIKHMON-EXAM-BLOCK") !== false)) {
          return $r;
        }
      }
    }
    return null;
  }

  function ensureAdminBypass($API, $listName) {
    $adminIp = isset($_SERVER['REMOTE_ADDR']) ? trim($_SERVER['REMOTE_ADDR']) : '';
    if ($adminIp == '' || !filter_var($adminIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return;
    }
    $list = $API->comm("/ip/firewall/address-list/print");
    if (is_array($list)) {
      foreach ($list as $item) {
        if (isset($item['list']) && $item['list'] == $listName && isset($item['address']) && $item['address'] == $adminIp) {
          return;
        }
      }
    }
    $API->comm("/ip/firewall/address-list/add", array(
      "list"    => $listName,
      "address" => $adminIp,
      "comment" => "Admin Hotspot Monitor"
    ));
  }

  if (isset($_POST['ajax_exammode'])) {
    while (ob_get_level() > 0) {
      ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['ajax_exammode'];

    // 1. Toggle ON / OFF Mode Ujian
    if ($action == "toggle") {
      $targetState = ($_POST['enable'] == '1');
      $rule = findExamFilterRule($API, $EXAM_RULE_COMMENT);

      if ($targetState) {
        ensureAdminBypass($API, $EXAM_BYPASS_LIST);

        if ($rule == null) {
          $API->comm("/ip/firewall/filter/add", array(
            "chain"            => "forward",
            "src-address-list" => "!" . $EXAM_BYPASS_LIST,
            "dst-address-list" => "!" . $EXAM_BYPASS_LIST,
            "action"           => "drop",
            "comment"          => $EXAM_RULE_COMMENT,
            "place-before"     => "0",
            "disabled"         => "no"
          ));
        } else {
          $API->comm("/ip/firewall/filter/set", array(
            ".id"              => $rule['.id'],
            "src-address-list" => "!" . $EXAM_BYPASS_LIST,
            "dst-address-list" => "!" . $EXAM_BYPASS_LIST,
            "comment"          => $EXAM_RULE_COMMENT
          ));
          $API->comm("/ip/firewall/filter/enable", array(
            ".id" => $rule['.id']
          ));
        }

        echo json_encode(array(
          'status'  => 'success',
          'enabled' => true,
          'message' => 'Mode Ujian aktif. Akses internet siswa diputus sementara.'
        ));
        exit;
      } else {
        if ($rule != null) {
          $API->comm("/ip/firewall/filter/disable", array(
            ".id" => $rule['.id']
          ));
        }

        echo json_encode(array(
          'status'  => 'success',
          'enabled' => false,
          'message' => 'Mode Ujian nonaktif. Koneksi internet siswa kembali normal.'
        ));
        exit;
      }
    }

    // 2. Simpan Target Jaringan Siswa
    if ($action == "save_target") {
      $srcSubnet = trim($_POST['src_address']);
      $inIface   = trim($_POST['in_interface']);

      ensureAdminBypass($API, $EXAM_BYPASS_LIST);
      $rule = findExamFilterRule($API, $EXAM_RULE_COMMENT);

      if ($rule == null) {
        $params = array(
          "chain"            => "forward",
          "src-address-list" => "!" . $EXAM_BYPASS_LIST,
          "dst-address-list" => "!" . $EXAM_BYPASS_LIST,
          "action"           => "drop",
          "comment"          => $EXAM_RULE_COMMENT,
          "place-before"     => "0",
          "disabled"         => "yes"
        );
        if ($srcSubnet != "") $params["src-address"] = $srcSubnet;
        if ($inIface != "")   $params["in-interface"] = $inIface;

        $API->comm("/ip/firewall/filter/add", $params);
      } else {
        $params = array(
          ".id"              => $rule['.id'],
          "src-address-list" => "!" . $EXAM_BYPASS_LIST,
          "dst-address-list" => "!" . $EXAM_BYPASS_LIST,
          "comment"          => $EXAM_RULE_COMMENT
        );
        if ($srcSubnet != "") {
          $params["src-address"] = $srcSubnet;
        } else {
          $params["!src-address"] = "";
        }
        if ($inIface != "") {
          $params["in-interface"] = $inIface;
        } else {
          $params["!in-interface"] = "";
        }
        $API->comm("/ip/firewall/filter/set", $params);
      }

      echo json_encode(array(
        'status'  => 'success',
        'message' => 'Target jaringan pemblokiran disimpan.'
      ));
      exit;
    }

    // 3. Tambah Pengecualian (Ruang Guru / Server Ujian)
    if ($action == "add_bypass") {
      $address = trim($_POST['address']);
      $comment = trim($_POST['comment']);

      if ($address == "") {
        echo json_encode(array('status' => 'error', 'message' => 'Isi IP Address, Subnet, atau Domain terlebih dahulu.'));
        exit;
      }

      $list = $API->comm("/ip/firewall/address-list/print");
      if (is_array($list)) {
        foreach ($list as $item) {
          if (isset($item['list']) && $item['list'] == $EXAM_BYPASS_LIST && isset($item['address']) && strtolower($item['address']) == strtolower($address)) {
            echo json_encode(array('status' => 'error', 'message' => $address . ' sudah terdaftar.'));
            exit;
          }
        }
      }

      $API->comm("/ip/firewall/address-list/add", array(
        "list"    => $EXAM_BYPASS_LIST,
        "address" => $address,
        "comment" => ($comment != "" ? $comment : "Ruang Guru / Server Ujian")
      ));

      echo json_encode(array(
        'status'  => 'success',
        'message' => $address . ' ditambahkan ke pengecualian.'
      ));
      exit;
    }

    // 4. Hapus Pengecualian
    if ($action == "remove_bypass") {
      $idBypass = trim($_POST['id']);
      if ($idBypass != "") {
        $API->comm("/ip/firewall/address-list/remove", array(
          ".id" => $idBypass
        ));
        echo json_encode(array(
          'status'  => 'success',
          'message' => 'Data pengecualian dihapus.'
        ));
      } else {
        echo json_encode(array('status' => 'error', 'message' => 'ID tidak ditemukan.'));
      }
      exit;
    }

    // 5. Ambil Status Terkini
    if ($action == "state") {
      $rule = findExamFilterRule($API, $EXAM_RULE_COMMENT);

      $ruleExists  = ($rule != null);
      $examEnabled = ($ruleExists && (!isset($rule['disabled']) || $rule['disabled'] != "true"));
      $srcAddress  = ($ruleExists && isset($rule['src-address'])) ? $rule['src-address'] : '';
      $inInterface = ($ruleExists && isset($rule['in-interface'])) ? $rule['in-interface'] : '';
      $packets     = ($ruleExists && isset($rule['packets'])) ? $rule['packets'] : '0';

      $bypassItems = array();
      $addrList = $API->comm("/ip/firewall/address-list/print");
      if (is_array($addrList)) {
        foreach ($addrList as $a) {
          if (isset($a['list']) && ($a['list'] == $EXAM_BYPASS_LIST || $a['list'] == "MIKHMON-EXAM-BYPASS")) {
            if (isset($a['dynamic']) && $a['dynamic'] == "true") continue;
            $bypassItems[] = array(
              'id'      => isset($a['.id']) ? $a['.id'] : '',
              'address' => isset($a['address']) ? $a['address'] : '-',
              'comment' => (isset($a['comment']) && $a['comment'] != '') ? $a['comment'] : 'Pengecualian'
            );
          }
        }
      }

      $interfaces = array();
      $getIfaces = $API->comm("/interface/print");
      if (is_array($getIfaces)) {
        foreach ($getIfaces as $if) {
          if (isset($if['name']) && $if['name'] != '') {
            $interfaces[] = array(
              'name' => $if['name'],
              'type' => isset($if['type']) ? $if['type'] : ''
            );
          }
        }
      }

      echo json_encode(array(
        'status'       => 'success',
        'rule_exists'  => $ruleExists,
        'exam_enabled' => $examEnabled,
        'src_address'  => $srcAddress,
        'in_interface' => $inInterface,
        'packets'      => $packets,
        'admin_ip'     => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-',
        'bypass'       => $bypassItems,
        'interfaces'   => $interfaces
      ));
      exit;
    }
  }
}
?>

<style>
  #mhm-exammode {
    color: #18181b;
  }
  #mhm-exammode .lx-shell {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }
  #mhm-exammode .lx-topbar {
    padding: 16px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
  }
  #mhm-exammode .lx-title {
    margin: 0;
    font-size: 15.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-exammode .lx-body {
    padding: 20px 24px;
    background: #fafafa;
  }

  /* Hero Switch Card */
  .exam-hero-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 20px 22px;
    margin-bottom: 18px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    transition: all 0.2s ease;
    border-left: 4px solid #16a34a;
  }
  .exam-hero-card.is-active {
    background: #fffafa;
    border-color: #fecaca;
    border-left-color: #dc2626;
  }
  .exam-hero-left {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
    min-width: 260px;
  }
  .exam-shield-box {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    background: #dcfce7;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
  }
  .exam-hero-card.is-active .exam-shield-box {
    background: #fee2e2;
    color: #dc2626;
  }
  .exam-state-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 999px;
    background: #dcfce7;
    color: #15803d;
    margin-bottom: 5px;
  }
  .exam-hero-card.is-active .exam-state-badge {
    background: #fee2e2;
    color: #b91c1c;
  }
  .exam-state-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #16a34a;
  }
  .exam-hero-card.is-active .exam-state-dot {
    background: #dc2626;
    animation: examPulse 1.4s infinite;
  }
  @keyframes examPulse {
    0%   { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.5); }
    70%  { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
  }

  /* Toggle Switch */
  .exam-switch-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    padding: 10px 16px;
    border-radius: 10px;
  }
  .exam-hero-card.is-active .exam-switch-wrap {
    background: #ffffff;
    border-color: #fecaca;
  }
  .exam-switch {
    position: relative;
    display: inline-block;
    width: 56px;
    height: 30px;
    margin: 0;
    cursor: pointer;
  }
  .exam-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  .exam-slider {
    position: absolute;
    inset: 0;
    background-color: #d4d4d8;
    border-radius: 999px;
    transition: 0.2s ease;
  }
  .exam-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    bottom: 4px;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    transition: 0.2s ease;
  }
  .exam-switch input:checked + .exam-slider {
    background-color: #dc2626;
  }
  .exam-switch input:checked + .exam-slider:before {
    transform: translateX(26px);
  }

  /* Two Column Grid */
  .exam-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1.25fr;
    gap: 18px;
  }
  @media (max-width: 960px) {
    .exam-grid-2 {
      grid-template-columns: 1fr;
    }
  }
  .exam-panel {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 18px 20px;
  }
  .exam-panel-title {
    font-size: 13.5px;
    font-weight: 600;
    color: #09090b;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .exam-panel-sub {
    font-size: 12px;
    color: #71717a;
    margin: 0 0 16px 0;
    line-height: 1.45;
  }
  #mhm-exammode .lx-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #52525b;
    margin-bottom: 6px;
  }
  #mhm-exammode .lx-input,
  #mhm-exammode .lx-select {
    width: 100% !important;
    height: 36px !important;
    padding: 0 12px !important;
    background-color: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 12.5px !important;
    color: #18181b !important;
    margin: 0 !important;
    box-shadow: none !important;
    outline: none !important;
    box-sizing: border-box !important;
    transition: border-color 0.15s ease, background-color 0.15s ease;
  }
  #mhm-exammode .lx-select {
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    padding-right: 32px !important;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 11px center !important;
  }
  #mhm-exammode .lx-input:focus,
  #mhm-exammode .lx-select:focus {
    background-color: #ffffff !important;
    border-color: #18181b !important;
  }
  #mhm-exammode .lx-btn {
    height: 36px;
    padding: 0 14px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  #mhm-exammode .lx-btn:hover { background: #f4f4f5; }
  #mhm-exammode .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }
  #mhm-exammode .lx-btn.is-primary:hover { background: #27272a; }

  /* Bypass List Items */
  .bypass-add-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 8px;
    margin-bottom: 14px;
  }
  @media (max-width: 640px) {
    .bypass-add-row {
      grid-template-columns: 1fr;
    }
  }
  .bypass-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 290px;
    overflow-y: auto;
  }
  .bypass-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 12px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
  }
  .bypass-addr {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 600;
    color: #09090b;
  }
  .bypass-comm {
    font-size: 11.5px;
    color: #71717a;
  }
  .bypass-del {
    background: transparent;
    border: none;
    color: #a1a1aa;
    cursor: pointer;
    padding: 4px 7px;
    border-radius: 5px;
    font-size: 12.5px;
  }
  .bypass-del:hover {
    background: #fee2e2;
    color: #dc2626;
  }

  #mhm-exammode .lx-footer {
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

  /* Clean Toast Notification */
  #exam-toast-container {
    position: fixed;
    top: 66px;
    right: 22px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 8px;
    pointer-events: none;
  }
  .exam-toast {
    pointer-events: auto;
    min-width: 260px;
    max-width: 350px;
    background: #18181b;
    color: #ffffff;
    border-radius: 9px;
    padding: 11px 14px;
    box-shadow: 0 10px 25px -5px rgba(9, 9, 11, 0.25);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
    animation: examSlide 0.18s ease-out;
    transition: opacity 0.18s ease, transform 0.18s ease;
  }
  .exam-toast-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    background: #22c55e;
  }
  .exam-toast.is-danger .exam-toast-dot {
    background: #ef4444;
  }
  @keyframes examSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Clean Confirm Modal */
  #exam-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 9, 11, 0.4);
    backdrop-filter: blur(2px);
    z-index: 99998;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #exam-modal-backdrop.is-open { display: flex; }
  .exam-modal-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    width: 100%;
    max-width: 390px;
    padding: 20px;
    box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.22);
  }
</style>

<div id="exam-toast-container"></div>

<!-- Modal Konfirmasi -->
<div id="exam-modal-backdrop">
  <div class="exam-modal-card" role="dialog" aria-modal="true">
    <h4 style="margin:0 0 8px 0; font-size:15px; font-weight:600; color:#09090b;">Aktifkan Mode Ujian?</h4>
    <p style="font-size:12.5px; color:#52525b; line-height:1.5; margin:0 0 18px 0;">
      Akses internet jaringan siswa akan diputus sementara. Perangkat di daftar pengecualian tetap terhubung normal.
    </p>
    <div style="display:flex; justify-content:flex-end; gap:8px;">
      <button type="button" id="btn_exam_cancel" style="height:34px; padding:0 14px; border-radius:7px; border:1px solid #e4e4e7; background:#fff; font-size:12.5px; cursor:pointer;">Batal</button>
      <button type="button" id="btn_exam_confirm" style="height:34px; padding:0 14px; border-radius:7px; border:1px solid #dc2626; background:#dc2626; color:#fff; font-size:12.5px; font-weight:500; cursor:pointer;">Aktifkan</button>
    </div>
  </div>
</div>

<div id="mhm-exammode">
  <div class="lx-shell">
    <!-- Top Bar -->
    <div class="lx-topbar">
      <h3 class="lx-title">Kontrol Akses Internet Ujian / ANBK</h3>

      <div style="display:flex; align-items:center; gap:10px;">
        <span style="font-size:11.5px; color:#71717a;">IP Admin: <strong id="exam_admin_ip" style="font-family:monospace; color:#18181b;">-</strong></span>
        <button type="button" id="btn_exam_refresh" class="lx-btn">
          <i class="fa fa-refresh"></i>
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Body -->
    <div class="lx-body">
      <!-- Hero Saklar Utama -->
      <div id="exam_hero_card" class="exam-hero-card">
        <div class="exam-hero-left">
          <div class="exam-shield-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1.5" y="19.5" width="21" height="2.4" rx="1.2" fill="currentColor" stroke="none"/>
              <path d="M8 9H5a1.5 1.5 0 0 0-1.5 1.5v9h17v-6.5"/>
              <rect x="10.2" y="1.2" width="3.6" height="2" rx="0.5" fill="currentColor" stroke="none"/>
              <line x1="12" y1="3.2" x2="12" y2="4.4"/>
              <line x1="8.3" y1="4.8" x2="9.3" y2="5.7"/>
              <line x1="15.7" y1="4.8" x2="14.7" y2="5.7"/>
              <circle cx="12" cy="8.2" r="4"/>
              <polyline points="12 6.3 12 8.3 13.5 9.2"/>
              <polyline points="6 12.2 7 13.2 8.8 11.4"/>
              <line x1="10.5" y1="12.4" x2="12" y2="12.4"/>
              <polyline points="6 14.9 7 15.9 8.8 14.1"/>
              <line x1="10.5" y1="15.1" x2="14" y2="15.1"/>
              <line x1="6.3" y1="17" x2="8.1" y2="18.8"/>
              <line x1="8.1" y1="17" x2="6.3" y2="18.8"/>
              <line x1="10.5" y1="17.9" x2="17.2" y2="17.9"/>
              <path d="M19.8 8.2a1.2 1.2 0 0 1 1.7 1.7l-5.3 5.3-2.3.6.6-2.3 5.3-5.3z" fill="currentColor"/>
            </svg>
          </div>
          <div>
            <div class="exam-state-badge">
              <span class="exam-state-dot"></span>
              <span id="exam_badge_text">Normal &bull; Internet Aktif</span>
            </div>
            <h4 id="exam_heading_text" style="margin:0 0 3px 0; font-size:15px; font-weight:600; color:#09090b;">
              Akses Internet Siswa Berjalan Normal
            </h4>
            <p id="exam_desc_text" style="margin:0; font-size:12px; color:#52525b; line-height:1.45;">
              Aktifkan saklar saat ujian dimulai untuk memutus sementara internet siswa tanpa mengganggu Ruang Guru dan Server Ujian.
            </p>
            <div id="exam_stats_row" style="display:none; margin-top:8px; font-size:11.5px; color:#b91c1c; font-weight:500;">
              <i class="fa fa-ban"></i> Paket diblokir: <strong id="exam_blocked_packets" style="font-family:monospace;">0</strong>
            </div>
          </div>
        </div>

        <div class="exam-switch-wrap">
          <div style="text-align:right;">
            <div style="font-size:10.5px; font-weight:600; color:#71717a; text-transform:uppercase;">Mode Ujian</div>
            <div id="exam_switch_label" style="font-size:12.5px; font-weight:700; color:#16a34a;">OFF</div>
          </div>
          <label class="exam-switch" title="Ubah status Mode Ujian">
            <input type="checkbox" id="exam_toggle_input">
            <span class="exam-slider"></span>
          </label>
        </div>
      </div>

      <!-- 2 Kolom Pengaturan -->
      <div class="exam-grid-2">
        <!-- Kolom Kiri: Target Pemblokiran -->
        <div class="exam-panel">
          <h4 class="exam-panel-title"><i class="fa fa-crosshairs"></i> Target Jaringan Siswa</h4>
          <p class="exam-panel-sub">
            Tentukan segmen jaringan yang dibatasi saat Mode Ujian aktif. Kosongkan keduanya untuk membatasi semua jaringan di luar pengecualian.
          </p>

          <div style="margin-bottom:14px;">
            <label class="lx-label">IP Subnet Siswa (Opsional)</label>
            <input type="text" id="exam_src_address" class="lx-input" style="font-family:monospace !important;" placeholder="Contoh: 192.168.100.0/24">
          </div>

          <div style="margin-bottom:16px;">
            <label class="lx-label">Interface Hotspot / Siswa (Opsional)</label>
            <select id="exam_in_interface" class="lx-select">
              <option value="">Semua Interface</option>
            </select>
          </div>

          <button type="button" id="btn_save_target" class="lx-btn is-primary" style="width:100%;">
            <i class="fa fa-save"></i>
            <span>Simpan Target</span>
          </button>
        </div>

        <!-- Kolom Kanan: Pengecualian Ruang Guru & Server Ujian -->
        <div class="exam-panel">
          <h4 class="exam-panel-title"><i class="fa fa-check-square-o"></i> Pengecualian (Ruang Guru &amp; Server Ujian)</h4>
          <p class="exam-panel-sub">
            Daftar IP Address, Subnet, atau Domain di bawah ini tetap mendapat akses internet penuh saat Mode Ujian aktif.
          </p>

          <div class="bypass-add-row">
            <input type="text" id="bypass_address" class="lx-input" style="font-family:monospace !important;" placeholder="IP / Subnet / Domain">
            <input type="text" id="bypass_comment" class="lx-input" placeholder="Keterangan (misal Ruang Guru)">
            <button type="button" id="btn_add_bypass" class="lx-btn is-primary">
              <i class="fa fa-plus"></i>
              <span>Tambah</span>
            </button>
          </div>

          <div id="bypass_list_container" class="bypass-list">
            <div style="text-align:center; padding:24px; color:#71717a; font-size:12px;">
              Memuat data...
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="lx-footer">
      <div>Sinkron dengan <strong>IP &gt; Firewall &gt; Filter Rules</strong></div>
      <div>Address List: <code style="font-size:11px;">EXAM-MODE-BYPASS</code></div>
    </div>
  </div>
</div>

<script>
(function() {
  var sessionName = "<?= htmlspecialchars($session); ?>";
  var ajaxUrl = "./?system=exam-mode&session=" + encodeURIComponent(sessionName);
  var currentEnabled = false;

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function(m) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];
    });
  }

  function showToast(type, message) {
    var container = document.getElementById('exam-toast-container');
    if (!container) return;
    var isDanger = (type === 'danger' || type === 'error');
    var toast = document.createElement('div');
    toast.className = 'exam-toast' + (isDanger ? ' is-danger' : '');
    toast.innerHTML =
      '<span class="exam-toast-dot"></span>' +
      '<span>' + escapeHtml(message) + '</span>';
    container.appendChild(toast);
    setTimeout(function() {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-6px)';
      setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 180);
    }, 3200);
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

  function updateHeroUI(enabled, packets) {
    currentEnabled = !!enabled;
    var card = document.getElementById('exam_hero_card');
    var toggle = document.getElementById('exam_toggle_input');
    var badgeText = document.getElementById('exam_badge_text');
    var heading = document.getElementById('exam_heading_text');
    var desc = document.getElementById('exam_desc_text');
    var swLabel = document.getElementById('exam_switch_label');
    var statsRow = document.getElementById('exam_stats_row');

    toggle.checked = currentEnabled;

    if (currentEnabled) {
      card.classList.add('is-active');
      badgeText.textContent = 'Ujian Aktif \u2022 Internet Siswa Dibatasi';
      heading.textContent = 'Akses Internet Siswa Sedang Diputus';
      desc.textContent = 'Jaringan siswa diblokir sementara. Ruang Guru, Server Ujian, dan daftar pengecualian tetap terhubung.';
      swLabel.textContent = 'ON';
      swLabel.style.color = '#dc2626';
      statsRow.style.display = 'block';
      document.getElementById('exam_blocked_packets').textContent = Number(packets || 0).toLocaleString('id-ID');
    } else {
      card.classList.remove('is-active');
      badgeText.textContent = 'Normal \u2022 Internet Aktif';
      heading.textContent = 'Akses Internet Siswa Berjalan Normal';
      desc.textContent = 'Aktifkan saklar saat ujian dimulai untuk memutus sementara internet siswa tanpa mengganggu Ruang Guru dan Server Ujian.';
      swLabel.textContent = 'OFF';
      swLabel.style.color = '#16a34a';
      statsRow.style.display = 'none';
    }
  }

  function loadExamState(manual) {
    var btnRef = document.getElementById('btn_exam_refresh');
    if (manual && btnRef) btnRef.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Memuat...</span>';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { ajax_exammode: 'state' },
      dataType: 'text',
      success: function(raw) {
        if (btnRef) btnRef.innerHTML = '<i class="fa fa-refresh"></i><span>Refresh</span>';
        var res = parseJsonSafe(raw);
        if (!res || res.status !== 'success') return;

        document.getElementById('exam_admin_ip').textContent = res.admin_ip || '-';
        document.getElementById('exam_src_address').value = res.src_address || '';

        var selIf = document.getElementById('exam_in_interface');
        var ifHtml = '<option value="">Semua Interface</option>';
        if (res.interfaces && res.interfaces.length > 0) {
          for (var i = 0; i < res.interfaces.length; i++) {
            var ifName = res.interfaces[i].name;
            var selected = (ifName === res.in_interface) ? ' selected' : '';
            ifHtml += '<option value="' + escapeHtml(ifName) + '"' + selected + '>' + escapeHtml(ifName) + '</option>';
          }
        }
        selIf.innerHTML = ifHtml;

        var listBox = document.getElementById('bypass_list_container');
        if (!res.bypass || res.bypass.length === 0) {
          listBox.innerHTML =
            '<div style="text-align:center; padding:24px; background:#fafafa; border:1px dashed #e4e4e7; border-radius:8px; color:#71717a; font-size:12px;">' +
              'Belum ada pengecualian.' +
            '</div>';
        } else {
          var bHtml = '';
          for (var j = 0; j < res.bypass.length; j++) {
            var b = res.bypass[j];
            bHtml +=
              '<div class="bypass-item">' +
                '<div>' +
                  '<div class="bypass-addr">' + escapeHtml(b.address) + '</div>' +
                  '<div class="bypass-comm">' + escapeHtml(b.comment) + '</div>' +
                '</div>' +
                '<button type="button" class="bypass-del" data-id="' + escapeHtml(b.id) + '" title="Hapus">' +
                  '<i class="fa fa-trash-o"></i>' +
                '</button>' +
              '</div>';
          }
          listBox.innerHTML = bHtml;
        }

        updateHeroUI(res.exam_enabled, res.packets);
        if (manual) {
          showToast('success', 'Status diperbarui.');
        }
      },
      error: function() {
        if (btnRef) btnRef.innerHTML = '<i class="fa fa-refresh"></i><span>Refresh</span>';
      }
    });
  }

  function executeToggle(enableState) {
    var toggle = document.getElementById('exam_toggle_input');
    toggle.disabled = true;

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { ajax_exammode: 'toggle', enable: enableState ? '1' : '0' },
      dataType: 'text',
      success: function(raw) {
        toggle.disabled = false;
        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          showToast(res.enabled ? 'danger' : 'success', res.message);
        }
        loadExamState(false);
      },
      error: function() {
        toggle.disabled = false;
        toggle.checked = currentEnabled;
        showToast('error', 'Gagal terhubung ke router.');
      }
    });
  }

  document.getElementById('exam_toggle_input').addEventListener('change', function() {
    var wantEnable = this.checked;
    if (wantEnable) {
      this.checked = false;
      document.getElementById('exam-modal-backdrop').classList.add('is-open');
    } else {
      executeToggle(false);
    }
  });

  document.getElementById('btn_exam_cancel').addEventListener('click', function() {
    document.getElementById('exam-modal-backdrop').classList.remove('is-open');
  });
  document.getElementById('exam-modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('is-open');
  });
  document.getElementById('btn_exam_confirm').addEventListener('click', function() {
    document.getElementById('exam-modal-backdrop').classList.remove('is-open');
    executeToggle(true);
  });

  document.getElementById('btn_save_target').addEventListener('click', function() {
    var btn = this;
    var srcAddr = document.getElementById('exam_src_address').value.trim();
    var inIface = document.getElementById('exam_in_interface').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Menyimpan...</span>';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: {
        ajax_exammode: 'save_target',
        src_address: srcAddr,
        in_interface: inIface
      },
      dataType: 'text',
      success: function(raw) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save"></i><span>Simpan Target</span>';
        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          showToast('success', res.message);
          loadExamState(false);
        }
      },
      error: function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save"></i><span>Simpan Target</span>';
      }
    });
  });

  document.getElementById('btn_add_bypass').addEventListener('click', function() {
    var addrInput = document.getElementById('bypass_address');
    var commInput = document.getElementById('bypass_comment');
    var address = addrInput.value.trim();
    var comment = commInput.value.trim();

    if (!address) {
      addrInput.focus();
      showToast('error', 'Masukkan IP Address, Subnet, atau Domain.');
      return;
    }

    var btn = this;
    btn.disabled = true;

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: {
        ajax_exammode: 'add_bypass',
        address: address,
        comment: comment
      },
      dataType: 'text',
      success: function(raw) {
        btn.disabled = false;
        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          addrInput.value = '';
          commInput.value = '';
          showToast('success', res.message);
          loadExamState(false);
        } else {
          showToast('error', (res && res.message) ? res.message : 'Gagal menambahkan.');
        }
      },
      error: function() {
        btn.disabled = false;
      }
    });
  });

  document.getElementById('bypass_list_container').addEventListener('click', function(e) {
    var btn = e.target.closest('.bypass-del');
    if (!btn) return;
    var id = btn.getAttribute('data-id');
    btn.disabled = true;

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { ajax_exammode: 'remove_bypass', id: id },
      dataType: 'text',
      success: function(raw) {
        var res = parseJsonSafe(raw);
        if (res && res.status === 'success') {
          showToast('success', res.message);
        }
        loadExamState(false);
      }
    });
  });

  document.getElementById('btn_exam_refresh').addEventListener('click', function() {
    loadExamState(true);
  });

  loadExamState(false);
})();
</script>