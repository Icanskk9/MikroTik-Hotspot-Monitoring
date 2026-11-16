<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// hide all error
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  date_default_timezone_set($_SESSION['timezone']);

  $getprofile = $API->comm("/ip/hotspot/user/profile/print");
  $srvlist = $API->comm("/ip/hotspot/print");

  if (substr($hotspotuser, 0, 1) == "*") {
    $hotspotuser = $hotspotuser;
  } elseif (substr($hotspotuser, 0, 1) != "") {
    $getuser = $API->comm("/ip/hotspot/user/print", array(
      "?name" => "$hotspotuser",
    ));
    $hotspotuser = $getuser[0]['.id'];
  }

  $getuser = $API->comm("/ip/hotspot/user/print", array(
    "?.id" => "$hotspotuser",
  ));
  $userdetails = $getuser[0];
  $uid = $userdetails['.id'];
  $userver = $userdetails['server'];
  $uname = $userdetails['name'];
  $upass = $userdetails['password'];
  $umac = $userdetails['mac-address'];
  $uprofile = $userdetails['profile'];
  $uuptime = formatDTM($userdetails['uptime']);
  $ueduser = $userdetails['disabled'];
  $utimelimit = $userdetails['limit-uptime'];
  $udatalimit = $userdetails['limit-bytes-total'];
  $ubytesout = $userdetails['bytes-out'];
  $ubytesin = $userdetails['bytes-in'];
  $ucomment = $userdetails['comment'];

  if (substr(formatBytes2($udatalimit, 2), -2) == "MB") {
    $udatalimit = $udatalimit / 1048576;
    $MG = "MB";
  } elseif (substr(formatBytes2($udatalimit, 2), -2) == "GB") {
    $udatalimit = $udatalimit / 1073741824;
    $MG = "GB";
  } elseif ($udatalimit == "") {
    $udatalimit = "";
    $MG = "MB";
  }

  if ($uname == $upass) {
    $usermode = "vc";
  } else {
    $usermode = "up";
  }

  if ($uname == "") {
    echo "<b>User not found redirect to user list...</b>";
    echo "<script>window.location='./?hotspot=users&profile=all&session=" . $session . "'</script>";
  }

  if ((substr($ucomment, 3, 1) == "/" && substr($ucomment, 6, 1) == "/")) {
    $commt = 'disabled';
    $comment2t = 'text';
    $_tcomment = $_expired;
    $_tcomment2 = $_comment;
    $ucomment2 = substr($ucomment, 21, (strlen($ucomment) - 21));
    $ucomment = substr($ucomment, 0, 20);
  } else {
    $comment2t = 'hidden';
    $_tcomment = $_comment;
    $_tcomment2 = "";
    $display = 'style="display:none"';
  }

  $getprofilebyuser = $API->comm("/ip/hotspot/user/profile/print", array(
    "?name" => "$uprofile"
  ));
  $profiledetalis = $getprofilebyuser[0];
  $ponlogin = $profiledetalis['on-login'];
  $getvalid = explode(",", $ponlogin)[3];
  $getprice = explode(",", $ponlogin)[2];
  $getsprice = explode(",", $ponlogin)[4];

  $getsch = $API->comm("/system/scheduler/print", array(
    "?name" => "$uname",
  ));
  $schdetails = $getsch[0];
  $start = $schdetails['start-date'] . " " . $schdetails['start-time'];
  $end = $schdetails['next-run'];

  // share WhatsApp
  if ($getvalid != "") {
    $wavalid = $_validity . " : *" . $getvalid . "* %0A";
  } else {
    $wavalid = "";
  }
  if ($utimelimit != "") {
    $watlimit = $_time_limit . " : *" . $utimelimit . "* %0A";
  } else {
    $watlimit = "";
    $bMB = "";
  }
  if ($udatalimit != "") {
    $wadlimit = $_data_limit . " : *" . $udatalimit . "" . $MG . "* %0A";
    $bMG = $MG;
  } else {
    $wadlimit = "";
  }

  if ($getsprice == "" && $getprice != "") {
    if ($currency == in_array($currency, $cekindo['indo'])) {
      $waprice = $_price . " : *" . $currency . " " . number_format((float)$getprice, 0, ",", ".") . "* %0A";
    } else {
      $waprice = $_price . " : *" . $currency . " " . number_format((float)$getprice) . "* %0A";
    }
    $btprice = $getprice;
  } else if ($getsprice != "") {
    if ($currency == in_array($currency, $cekindo['indo'])) {
      $waprice = $_price . " : *" . $currency . " " . number_format((float)$getsprice, 0, ",", ".") . "* %0A";
    } else {
      $waprice = $_price . " : *" . $currency . " " . number_format((float)$getsprice) . "* %0A";
    }
    $btprice = $getsprice;
  } else if ($getsprice == "") {
    $waprice = "";
    $btprice = "";
  }

  $shareWAUP = "
%0A---------%0A
*" . $hotspotname . "*
%0A%0A
Username : *" . $uname . "* %0A
Password : *" . $upass . "* %0A
" . $wavalid . "
" . $watlimit . "
" . $wadlimit . "
" . $waprice . " %0A
Login : *http://" . $dnsname . "* %0A
---------
";
  $shareWAVC = "
%0A---------%0A
*" . $hotspotname . "*
%0A%0A
Voucher : *" . $uname . "* %0A
" . $wavalid . "
" . $watlimit . "
" . $wadlimit . "
" . $waprice . " %0A
Login : *http://" . $dnsname . "* %0A
---------
";
  if ($uname == $upass) {
    $shareWA = $shareWAVC;
  } else {
    $shareWA = $shareWAUP;
  }

  // quick bt
  include('./include/quickbt.php');

  // Print BT
  $chl = urlencode("http://$dnsname/login?username=$uname&password=$upass");
  $qrcode = 'https://chart.googleapis.com/chart?cht=qr&chs=100x100&chld=L|0&chl=' . $chl . '&choe=utf-8';

  if ($currency == in_array($currency, $cekindo['indo'])) {
    $pricebt = $currency . " " . number_format((float)$btprice, 0, ",", ".");
    if (substr($getvalid, -1) == "d") {
      $validity = substr($getvalid, 0, -1) . "Hari";
    } else if (substr($getvalid, -1) == "h") {
      $validity = substr($getvalid, 0, -1) . "Jam";
    } else if (substr($getvalid, -1) == "m") {
      $validity = substr($getvalid, 0, -1) . "Menit";
    }
    if (substr($utimelimit, -1) == "d" & strlen($utimelimit) > 3) {
      $timelimit = ((substr($utimelimit, 0, -1) * 7) + substr($utimelimit, 2, 1)) . "Hari";
    } else if (substr($utimelimit, -1) == "d") {
      $timelimit = substr($utimelimit, 0, -1) . "Hari";
    } else if (substr($utimelimit, -1) == "h") {
      $timelimit = substr($utimelimit, 0, -1) . "Jam";
    } else if (substr($utimelimit, -1) == "w") {
      $timelimit = (substr($utimelimit, 0, -1) * 7) . "Hari";
    }
  } else {
    $pricebt = $currency . " " . number_format((float)$btprice);
    $timelimit = $utimelimit;
    $validity = $getvalid;
  }

  if (isset($_POST['name'])) {
    $server = ($_POST['server']);
    $name = ($_POST['name']);
    $password = ($_POST['pass']);
    $profile = ($_POST['profile']);
    $disabled = ($_POST['disabled']);
    $timelimit = ($_POST['timelimit']);
    $datalimit = ($_POST['datalimit']);
    $comment = ($_POST['comment']);
    $comment2 = ($_POST['comment2']);
    $hcomment = ($_POST['h_comment']);
    $mbgb = ($_POST['mbgb']);
    if ($timelimit == "") {
      $timelimit = "0";
    } else {
      $timelimit = $timelimit;
    }
    if ($datalimit == "") {
      $datalimit = "0";
    } else {
      $datalimit = $datalimit * $mbgb;
    }
    if ($name == $password) {
      $usermode = "vc-";
    } else {
      $usermode = "up-";
    }

    if ((substr($hcomment, 3, 1) == "/" && substr($hcomment, 6, 1) == "/")) {
      $comment = $hcomment . " " . $comment2;
    } elseif ((substr($comment, 3, 1) == "/" && substr($comment, 6, 1) == "/")) {
      $comment = $comment . " " . $comment2;
    } elseif (substr($comment, 0, 3) == "vc-" || substr($comment, 0, 3) == "up-") {
      $comment = $comment;
    } else {
      $comment = $usermode . $comment;
    }

    $API->comm("/ip/hotspot/user/set", array(
      ".id" => "$uid",
      "server" => "$server",
      "name" => "$name",
      "password" => "$password",
      "profile" => "$profile",
      "disabled" => "$disabled",
      "limit-uptime" => "$timelimit",
      "limit-bytes-total" => "$datalimit",
      "comment" => "$comment",
    ));
    echo "<script>window.location='./?hotspot-user=" . $uid . "&session=" . $session . "'</script>";
  }
}
include('./voucher/printbt.php');

// Tentukan URL tombol Close sesuai session navigasi
if ($_SESSION['ubp'] != "") {
  $closeUrl = "./?hotspot=users&profile=" . $_SESSION['ubp'] . "&session=" . $session;
} elseif ($_SESSION['ubc'] != "") {
  $closeUrl = "./?hotspot=users&comment=" . $_SESSION['ubc'] . "&session=" . $session;
} elseif ($_SESSION['hua'] != "") {
  $_SESSION['ubn'] = "";
  $closeUrl = "./?hotspot=active&session=" . $session;
  $_SESSION['hua'] = "";
} elseif ($_SESSION['ubn'] != "") {
  $closeUrl = "./?hotspot=users&profile=all&session=" . $session;
  $_SESSION['ubn'] = "";
} else {
  $closeUrl = "./?hotspot=users&profile=all&session=" . $session;
}

// Format display read-only values
$dispUptime = ($uuptime == 0 || $uuptime == "") ? "0s" : $uuptime;
$dispBytesIn = ($ubytesin == 0 || $ubytesin == "") ? "0 B" : formatBytes($ubytesin, 2);
$dispBytesOut = ($ubytesout == 0 || $ubytesout == "") ? "0 B" : formatBytes($ubytesout, 2);
$dispBytes = $dispBytesIn . " / " . $dispBytesOut;

if ($getprice == 0 || $getprice == "") {
  $dispPrice = "";
  $dispSprice = "";
} else {
  if ($currency == in_array($currency, $cekindo['indo'])) {
    $dispPrice = $currency . " " . number_format((float)$getprice, 0, ",", ".");
    $dispSprice = ($getsprice != "" && $getsprice != "0") ? $currency . " " . number_format((float)$getsprice, 0, ",", ".") : "";
  } else {
    $dispPrice = $currency . " " . number_format((float)$getprice);
    $dispSprice = ($getsprice != "" && $getsprice != "0") ? $currency . " " . number_format((float)$getsprice) : "";
  }
}
?>

<style>
  #mhm-edituser {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-edituser .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-edituser .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-edituser .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-edituser .lx-pill {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-edituser .lx-pill.is-enabled {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
  }
  #mhm-edituser .lx-pill.is-disabled {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
  }
  #mhm-edituser .lx-pill.is-expired {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
  }

  /* Action Buttons */
  #mhm-edituser .lx-actions {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
  }
  #mhm-edituser .lx-btn {
    height: 34px;
    padding: 0 12px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12px;
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
  #mhm-edituser .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-edituser .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-edituser .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-edituser .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-edituser .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-edituser .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }
  #mhm-edituser .lx-btn.is-danger-outline {
    background: #ffffff;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-edituser .lx-btn.is-danger-outline i {
    color: #dc2626;
  }
  #mhm-edituser .lx-btn.is-danger-outline:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #b91c1c !important;
  }
  #mhm-edituser .lx-btn.is-success-outline {
    background: #ffffff;
    border-color: #a7f3d0;
    color: #047857 !important;
  }
  #mhm-edituser .lx-btn.is-success-outline i {
    color: #059669;
  }
  #mhm-edituser .lx-btn.is-success-outline:hover {
    background: #ecfdf5;
    border-color: #6ee7b7;
  }

  /* Read-Only Status Strip */
  #mhm-edituser .lx-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7;
    background: #fafafa;
  }
  @media (max-width: 820px) {
    #mhm-edituser .lx-metrics {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  #mhm-edituser .lx-metric-item {
    padding: 13px 24px;
    border-right: 1px solid #e4e4e7;
  }
  #mhm-edituser .lx-metric-item:last-child {
    border-right: none;
  }
  @media (max-width: 820px) {
    #mhm-edituser .lx-metric-item:nth-child(2) {
      border-right: none;
    }
    #mhm-edituser .lx-metric-item:nth-child(1),
    #mhm-edituser .lx-metric-item:nth-child(2) {
      border-bottom: 1px solid #e4e4e7;
    }
  }
  #mhm-edituser .lx-metric-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
    margin-bottom: 4px;
  }
  #mhm-edituser .lx-metric-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13.5px;
    font-weight: 600;
    color: #09090b;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Form Body */
  #mhm-edituser .lx-body {
    padding: 22px 24px;
    background: #ffffff;
  }
  #mhm-edituser .lx-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px 28px;
  }
  @media (max-width: 960px) {
    #mhm-edituser .lx-form-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-edituser .lx-col-stack {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  #mhm-edituser .lx-field {
    display: grid;
    grid-template-columns: 145px minmax(0, 1fr);
    align-items: center;
    gap: 12px;
  }
  @media (max-width: 576px) {
    #mhm-edituser .lx-field {
      grid-template-columns: minmax(0, 1fr);
      gap: 6px;
    }
  }
  #mhm-edituser .lx-label {
    font-size: 13px;
    font-weight: 500;
    color: #3f3f46;
    margin: 0;
  }

  /* Inputs & Selects */
  #mhm-edituser .lx-input,
  #mhm-edituser .lx-select {
    width: 100% !important;
    height: 38px !important;
    padding: 0 12px !important;
    background: #fafafa !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    color: #09090b !important;
    box-shadow: none !important;
    margin: 0 !important;
    transition: all 0.15s ease !important;
  }
  #mhm-edituser .lx-input::placeholder {
    color: #a1a1aa !important;
  }
  #mhm-edituser .lx-input:hover:not([disabled]):not([readonly]),
  #mhm-edituser .lx-select:hover {
    border-color: #d4d4d8 !important;
    background: #ffffff !important;
  }
  #mhm-edituser .lx-input:focus:not([disabled]):not([readonly]),
  #mhm-edituser .lx-select:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  #mhm-edituser .lx-input[disabled],
  #mhm-edituser .lx-input[readonly] {
    background: #f4f4f5 !important;
    color: #52525b !important;
    border-color: #e4e4e7 !important;
    cursor: default !important;
  }
  #mhm-edituser .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12.5px !important;
  }

  /* Addon Group (Password Toggle & Data Limit MB/GB) */
  #mhm-edituser .lx-addon-group {
    display: flex;
    align-items: stretch;
    width: 100%;
  }
  #mhm-edituser .lx-addon-group .lx-input {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
    flex: 1;
  }
  #mhm-edituser .lx-addon-box {
    width: 46px;
    height: 38px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
  }
  #mhm-edituser .lx-addon-box input[type="checkbox"] {
    cursor: pointer;
    margin: 0;
    width: 14px;
    height: 14px;
    accent-color: #18181b;
  }
  #mhm-edituser .lx-addon-select {
    width: 78px !important;
    height: 38px !important;
    padding: 0 9px !important;
    background-color: #f4f4f5 !important;
    border: 1px solid #e4e4e7 !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #18181b !important;
    cursor: pointer;
    outline: none !important;
    margin: 0 !important;
    transition: all 0.15s ease;
  }
  #mhm-edituser .lx-addon-select:hover {
    background-color: #e4e4e7 !important;
  }

  /* Helper Box */
  #mhm-edituser .lx-help-box {
    margin-top: 18px;
    padding: 12px 16px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 9px;
    font-size: 12px;
    line-height: 1.6;
    color: #52525b;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  #mhm-edituser .lx-help-box p {
    margin: 0;
  }

  /* Footer */
  #mhm-edituser .lx-footer {
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
</style>

<script>
  function PassUser() {
    var x = document.getElementById('passUser');
    if (x.type === 'password') {
      x.type = 'text';
    } else {
      x.type = 'password';
    }
  }
  var _0x7baa = ["\x63\x6C\x69\x63\x6B", "\x2E\x70\x72\x69\x6E\x74\x42\x54", "\x72\x65\x61\x64\x79"];
  $(document)[_0x7baa[2]](function() {
    $(_0x7baa[1])[_0x7baa[0]](function() {
      printBT()
    })
  });
</script>

<div id="mhm-edituser">
  <form autocomplete="new-password" method="post" action="" style="margin:0;">
    <!-- Top Bar -->
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title">
          <?= $_edit_user ?> <span style="font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;"><?= htmlspecialchars($uname); ?></span>
        </h3>
        <?php if ($ueduser == "true"): ?>
          <span class="lx-pill is-disabled">Disabled</span>
        <?php else: ?>
          <span class="lx-pill is-enabled">Enabled</span>
        <?php endif; ?>
        <?php if ($utimelimit == "1s"): ?>
          <span class="lx-pill is-expired"><?= $_expired ?></span>
        <?php endif; ?>
        <span class="lx-pill"><?= ($usermode == "vc") ? "Voucher" : "User & Pass"; ?></span>
      </div>

      <div class="lx-actions">
        <a class="lx-btn" href="<?= $closeUrl; ?>">
          <i class="fa fa-close"></i>
          <span><?= $_close ?></span>
        </a>

        <button type="submit" name="save" class="lx-btn is-primary">
          <i class="fa fa-save"></i>
          <span><?= $_save ?></span>
        </button>

        <div
          class="lx-btn is-danger-outline"
          onclick="if(confirm('Are you sure to delete username (<?= htmlspecialchars($uname, ENT_QUOTES); ?>)?')){loadpage('./?remove-hotspot-user=<?= $uid; ?>&session=<?= $session; ?>')}else{}"
          title="Remove <?= htmlspecialchars($uname, ENT_QUOTES); ?>"
        >
          <i class="fa fa-minus-square"></i>
          <span><?= $_remove ?></span>
        </div>

        <a
          class="lx-btn"
          title="Print"
          href="javascript:window.open('./voucher/print.php?user=<?= $usermode . "-" . $uname; ?>&qr=no&session=<?= $session; ?>','_blank','width=310,height=450').print();"
        >
          <i class="fa fa-print"></i>
          <span><?= $_print ?></span>
        </a>

        <a
          class="lx-btn"
          title="Print QR"
          href="javascript:window.open('./voucher/print.php?user=<?= $usermode . "-" . $uname; ?>&qr=yes&session=<?= $session; ?>','_blank','width=310,height=450').print();"
        >
          <i class="fa fa-qrcode"></i>
          <span><?= $_print_qr ?></span>
        </a>

        <?php if ($utimelimit == "1s"): ?>
          <a class="lx-btn" href="./?reset-hotspot-user=<?= $uid; ?>&session=<?= $session; ?>">
            <i class="fa fa-retweet"></i>
            <span>Reset</span>
          </a>
        <?php endif; ?>

        <a id="shareWA" class="lx-btn is-success-outline" title="Share WhatsApp" href="whatsapp://send?text=<?= $shareWA; ?>">
          <i class="fa fa-whatsapp"></i>
          <span><?= $_share ?></span>
        </a>

        <div class="lx-btn" onclick="sendToQuickPrinterChrome()" title="Print Bluetooth">
          <i class="fa fa-bluetooth"></i>
          <span><?= $_print ?> BT</span>
        </div>
      </div>
    </div>

    <!-- Read-Only Telemetry Strip -->
    <div class="lx-metrics">
      <div class="lx-metric-item">
        <div class="lx-metric-label">Uptime</div>
        <div class="lx-metric-val"><?= htmlspecialchars($dispUptime); ?></div>
      </div>
      <div class="lx-metric-item">
        <div class="lx-metric-label">Bytes In / Out</div>
        <div class="lx-metric-val"><?= htmlspecialchars($dispBytes); ?></div>
      </div>
      <div class="lx-metric-item">
        <div class="lx-metric-label"><?= $_price ?></div>
        <div class="lx-metric-val"><?= ($dispPrice != "") ? htmlspecialchars($dispPrice) : "&mdash;"; ?></div>
      </div>
      <div class="lx-metric-item">
        <div class="lx-metric-label"><?= $_selling_price ?></div>
        <div class="lx-metric-val"><?= ($dispSprice != "") ? htmlspecialchars($dispSprice) : "&mdash;"; ?></div>
      </div>
    </div>

    <!-- Form Body -->
    <div class="lx-body">
      <div class="lx-form-grid">
        <!-- Left Column -->
        <div class="lx-col-stack">
          <div class="lx-field">
            <label class="lx-label">Enabled</label>
            <select class="form-control lx-select" name="disabled" required="1">
              <option value="<?= htmlspecialchars($ueduser); ?>"><?php if ($ueduser == "true") { echo "No"; } else { echo "Yes"; } ?></option>
              <option value="no">Yes</option>
              <option value="yes">No</option>
            </select>
          </div>

          <div class="lx-field">
            <label class="lx-label">Server</label>
            <select class="form-control lx-select" name="server" required="1">
              <option><?php if ($userver == "") { echo "all"; } else { echo htmlspecialchars($userver); } ?></option>
              <option>all</option>
              <?php
              $TotalReg = count($srvlist);
              for ($i = 0; $i < $TotalReg; $i++) {
                echo "<option>" . htmlspecialchars($srvlist[$i]['name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div class="lx-field">
            <label class="lx-label" for="name"><?= $_name ?></label>
            <input
              id="name"
              class="form-control lx-input lx-mono"
              type="text"
              autocomplete="off"
              name="name"
              value="<?= htmlspecialchars($uname); ?>"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label" for="passUser"><?= $_password ?></label>
            <div class="lx-addon-group">
              <input
                class="form-control lx-input lx-mono"
                id="passUser"
                type="password"
                name="pass"
                autocomplete="new-password"
                value="<?= htmlspecialchars($upass); ?>"
              >
              <label class="lx-addon-box" title="Show/Hide Password">
                <input type="checkbox" onclick="PassUser()">
              </label>
            </div>
          </div>

          <div class="lx-field">
            <label class="lx-label"><?= $_profile ?></label>
            <select class="form-control lx-select" name="profile" required="1">
              <option><?= htmlspecialchars($uprofile); ?></option>
              <?php
              $TotalReg = count($getprofile);
              for ($i = 0; $i < $TotalReg; $i++) {
                echo "<option>" . htmlspecialchars($getprofile[$i]['name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div class="lx-field">
            <label class="lx-label">Mac Address</label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              value="<?= htmlspecialchars($umac); ?>"
              readonly
            >
          </div>
        </div>

        <!-- Right Column -->
        <div class="lx-col-stack">
          <div class="lx-field">
            <label class="lx-label" for="timelimit"><?= $_time_limit ?></label>
            <input
              id="timelimit"
              class="form-control lx-input lx-mono"
              type="text"
              size="4"
              autocomplete="off"
              name="timelimit"
              placeholder="Contoh: 1h, 12h, 1d"
              value="<?php if ($utimelimit == "1s") { echo ""; } else { echo htmlspecialchars($utimelimit); } ?>"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label" for="datalimit"><?= $_data_limit ?></label>
            <div class="lx-addon-group">
              <input
                class="form-control lx-input lx-mono"
                type="number"
                min="0"
                max="9999"
                id="datalimit"
                name="datalimit"
                placeholder="0 (Unlimited)"
                value="<?= htmlspecialchars($udatalimit); ?>"
              >
              <select class="lx-addon-select" id="mbgb" name="mbgb" required="1">
                <option value="<?php if ($MG == "MB") { echo "1048576"; } elseif ($MG == "GB") { echo "1073741824"; } ?>"><?= $MG; ?></option>
                <option value="1048576">MB</option>
                <option value="1073741824">GB</option>
              </select>
            </div>
          </div>

          <div class="lx-field">
            <label class="lx-label" for="comment"><?= $_tcomment ?></label>
            <div style="width:100%;">
              <input
                class="form-control lx-input"
                type="text"
                id="comment"
                autocomplete="off"
                name="comment"
                title="No special characters"
                value="<?= htmlspecialchars($ucomment); ?>"
                <?= $commt ?>
              >
              <input type="hidden" name="h_comment" value="<?= htmlspecialchars($ucomment); ?>">
            </div>
          </div>

          <div class="lx-field" <?= $display ?>>
            <label class="lx-label" for="comment2"><?= $_tcomment2 ?></label>
            <input
              class="form-control lx-input"
              type="<?= $comment2t; ?>"
              id="comment2"
              autocomplete="off"
              name="comment2"
              title="No special characters"
              value="<?= htmlspecialchars($ucomment2); ?>"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label">Uptime</label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              value="<?php if ($uuptime == 0) {} else { echo htmlspecialchars($uuptime); } ?>"
              disabled
            >
          </div>

          <div class="lx-field">
            <label class="lx-label">Bytes In / Out</label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              value="<?php if ($ubytesout == 0) {} else { echo htmlspecialchars(formatBytes($ubytesin, 2)); } ?> / <?php if ($ubytesout == 0) {} else { echo htmlspecialchars(formatBytes($ubytesout, 2)); } ?>"
              disabled
            >
          </div>

          <div class="lx-field">
            <label class="lx-label"><?= $_price ?> / <?= $_selling_price ?></label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              value="<?= htmlspecialchars(($dispPrice != "" ? $dispPrice : "-") . " / " . ($dispSprice != "" ? $dispSprice : "-")); ?>"
              disabled
            >
          </div>

          <?php if ($getvalid != ""): ?>
            <div class="lx-field">
              <label class="lx-label" for="validity"><?= $_validity ?></label>
              <input
                class="form-control lx-input lx-mono"
                type="text"
                id="validity"
                value="<?= htmlspecialchars($getvalid); ?>"
                disabled
              >
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Format Help Box -->
      <div class="lx-help-box">
        <p><?= $_format_time_limit ?></p>
        <?php if ($end != ""): ?>
          <span class="lx-pill">Next Run: <?= htmlspecialchars($end); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Footer -->
    <div class="lx-footer">
      <div>ID MikroTik: <strong style="color:#18181b; font-family:monospace;"><?= htmlspecialchars($uid); ?></strong></div>
      <div>Klik <strong style="color:#18181b;">Save</strong> setelah mengubah konfigurasi user</div>
    </div>
  </form>
</div>