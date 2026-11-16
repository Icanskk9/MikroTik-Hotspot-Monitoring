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
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  if (substr($userprofile, 0, 1) == "*") {
    $userprofile = $userprofile;
  } elseif (substr($userprofile, 0, 1) != "") {
    $getprofile = $API->comm("/ip/hotspot/user/profile/print", array(
      "?name" => "$userprofile",
    ));
    $userprofile = $getprofile[0]['.id'];
    if ($userprofile == "") {
      echo "<b>User Profile not found</b>";
    }
  }

  $getpool = $API->comm("/ip/pool/print");

  $getprofile = $API->comm("/ip/hotspot/user/profile/print", array(
    "?.id" => "$userprofile"
  ));
  $profiledetalis = $getprofile[0];
  $pid = $profiledetalis['.id'];
  $pname = $profiledetalis['name'];
  $psharedu = $profiledetalis['shared-users'];
  $pratelimit = $profiledetalis['rate-limit'];
  $ponlogin = $profiledetalis['on-login'];
  $ppool = $profiledetalis['address-pool'];
  $sparent = $profiledetalis['parent-queue'];

  if (empty($ppool)) {
    $ppool = "none";
  }
  if (empty($sparent)) {
    $sparent = "none";
  }

  $getexpmode = explode(",", $ponlogin)[1];

  if ($getexpmode == "rem") {
    $getexpmodet = "Remove";
  } elseif ($getexpmode == "ntf") {
    $getexpmodet = "Notice";
  } elseif ($getexpmode == "remc") {
    $getexpmodet = "Remove & Record";
  } elseif ($getexpmode == "ntfc") {
    $getexpmodet = "Notice & Record";
  } else {
    $getexpmode = "0";
    $getexpmodet = "None";
  }

  $getprice = explode(",", $ponlogin)[2];
  if ($getprice == "0") {
    $getprice = "";
  } else {
    $getprice = $getprice;
  }

  $getsprice = explode(",", $ponlogin)[4];
  if ($getsprice == "0") {
    $getsprice = "";
  } else {
    $getsprice = $getsprice;
  }

  $getvalid = explode(",", $ponlogin)[3];
  $getgracep = explode(",", $ponlogin)[4];

  $getlocku = explode(",", $ponlogin)[6];
  if ($getlocku == "") {
    $getlocku = "Disable";
  } else {
    $getlocku = $getlocku;
  }

  $getallqueue = $API->comm("/queue/simple/print", array(
    "?dynamic" => "false",
  ));

  $getmonexpired = $API->comm("/system/scheduler/print", array(
    "?name" => "$pname",
  ));
  $monexpired = $getmonexpired[0];
  $monid = $monexpired['.id'];
  $pmon = $monexpired['name'];
  $chkpmon = $monexpired['disabled'];
  if (empty($pmon) || $chkpmon == "true") {
    $moncolor = "text-orange";
    $monActive = false;
  } else {
    $moncolor = "text-green";
    $monActive = true;
  }

  if (isset($_POST['name'])) {
    $name = (preg_replace('/\s+/', '-', $_POST['name']));
    $sharedusers = ($_POST['sharedusers']);
    $ratelimit = ($_POST['ratelimit']);
    $expmode = ($_POST['expmode']);
    $validity = ($_POST['validity']);
    $graceperiod = ($_POST['graceperiod']);
    $getprice = ($_POST['price']);
    $getsprice = ($_POST['sprice']);
    $addrpool = ($_POST['ppool']);
    if ($getprice == "") {
      $price = "0";
    } else {
      $price = $getprice;
    }
    if ($getsprice == "") {
      $sprice = "0";
    } else {
      $sprice = $getsprice;
    }
    $getlock = ($_POST['lockunlock']);
    if ($getlock == "Enable") {
      $lock = '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]';
    } else {
      $lock = "";
    }

    $randstarttime = "0" . rand(1, 5) . ":" . rand(10, 59) . ":" . rand(10, 59);
    $randinterval = "00:02:" . rand(10, 59);

    $parent = ($_POST['parent']);

    $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-' . $price . '-|-$address-|-$mac-|-' . $validity . '-|-' . $name . '-|-$comment" owner="$month$year" source="$date" comment="mikhmon"';

    $onlogin = ':put (",' . $expmode . ',' . $price . ',' . $validity . ',' . $sprice . ',,' . $getlock . ',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]';

    if ($expmode == "rem") {
      $onlogin = $onlogin . $lock . "}}";
      $mode = "remove";
    } elseif ($expmode == "ntf") {
      $onlogin = $onlogin . $lock . "}}";
      $mode = "set limit-uptime=1s";
    } elseif ($expmode == "remc") {
      $onlogin = $onlogin . $record . $lock . "}}";
      $mode = "remove";
    } elseif ($expmode == "ntfc") {
      $onlogin = $onlogin . $record . $lock . "}}";
      $mode = "set limit-uptime=1s";
    } elseif ($expmode == "0" && $price != "") {
      $onlogin = ':put (",,' . $price . ',,,noexp,' . $getlock . ',")' . $lock;
    } else {
      $onlogin = "";
    }

    $bgservice = ':local dateint do={:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );:local days [ :pick $d 4 6 ];:local month [ :pick $d 0 3 ];:local year [ :pick $d 7 11 ];:local monthint ([ :find $montharray $month]);:local month ($monthint + 1);:if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="' . $name . '" ] do={ :local comment [ /ip hotspot user get $i comment]; :local name [ /ip hotspot user get $i name]; :local gettime [:pic $comment 12 20]; :if ([:pic $comment 3] = "/" and [:pic $comment 6] = "/") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user ' . $mode . ' $i ]; [ /ip hotspot active remove [find where user=$name] ];}}}';

    $API->comm("/ip/hotspot/user/profile/set", array(
      ".id" => "$pid",
      "name" => "$name",
      "address-pool" => "$addrpool",
      "rate-limit" => "$ratelimit",
      "shared-users" => "$sharedusers",
      "status-autorefresh" => "1m",
      "on-login" => "$onlogin",
      "parent-queue" => "$parent",
    ));
    if ($expmode != "0") {
      if (empty($monid)) {
        $API->comm("/system/scheduler/add", array(
          "name" => "$name",
          "start-time" => "$randstarttime",
          "interval" => "$randinterval",
          "on-event" => "$bgservice",
          "disabled" => "no",
          "comment" => "Monitor Profile $name",
        ));
      } else {
        $API->comm("/system/scheduler/set", array(
          ".id" => "$monid",
          "name" => "$name",
          "start-time" => "$randstarttime",
          "interval" => "$randinterval",
          "on-event" => "$bgservice",
          "disabled" => "no",
          "comment" => "Monitor Profile $name",
        ));
      }
    } else {
      $API->comm("/system/scheduler/remove", array(
        ".id" => "$monid"
      ));
    }

    echo "<script>window.location='./?user-profile=" . $pid . "&session=" . $session . "'</script>";
  }
}
?>

<style>
  #mhm-editprofile {
    display: grid;
    grid-template-columns: minmax(0, 1.85fr) minmax(290px, 1fr);
    gap: 18px;
    align-items: start;
    color: #18181b;
  }
  @media (max-width: 992px) {
    #mhm-editprofile {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  #mhm-editprofile .lx-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }

  /* Top Bar */
  #mhm-editprofile .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
  }
  #mhm-editprofile .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-editprofile .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-editprofile .lx-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: #52525b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 9px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-editprofile .lx-pill.is-active {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
  }
  #mhm-editprofile .lx-pill.is-idle {
    background: #fffbeb;
    border-color: #fde68a;
    color: #b45309;
  }
  #mhm-editprofile .lx-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
  }
  #mhm-editprofile .lx-dot.is-green {
    background: #10b981;
  }
  #mhm-editprofile .lx-dot.is-orange {
    background: #f59e0b;
  }

  /* Buttons */
  #mhm-editprofile .lx-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  #mhm-editprofile .lx-btn {
    height: 34px;
    padding: 0 13px;
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
  #mhm-editprofile .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-editprofile .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-editprofile .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-editprofile .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-editprofile .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-editprofile .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }

  /* Form Body */
  #mhm-editprofile .lx-body {
    padding: 22px 24px;
    background: #ffffff;
  }
  #mhm-editprofile .lx-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px 24px;
  }
  @media (max-width: 720px) {
    #mhm-editprofile .lx-form-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-editprofile .lx-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  #mhm-editprofile .lx-field.is-full {
    grid-column: 1 / -1;
  }
  #mhm-editprofile .lx-label {
    font-size: 12.5px;
    font-weight: 500;
    color: #3f3f46;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  #mhm-editprofile .lx-label-hint {
    font-size: 11px;
    font-weight: 400;
    color: #71717a;
  }

  #mhm-editprofile .lx-input,
  #mhm-editprofile .lx-select {
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
  #mhm-editprofile .lx-input::placeholder {
    color: #a1a1aa !important;
  }
  #mhm-editprofile .lx-input:hover,
  #mhm-editprofile .lx-select:hover {
    border-color: #d4d4d8 !important;
    background: #ffffff !important;
  }
  #mhm-editprofile .lx-input:focus,
  #mhm-editprofile .lx-select:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  #mhm-editprofile .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12.5px !important;
  }

  /* Read Me Guide Card */
  #mhm-editprofile .lx-guide-body {
    padding: 18px 22px;
    background: #fafafa;
    font-size: 12.5px;
    line-height: 1.65;
    color: #3f3f46;
  }
  #mhm-editprofile .lx-guide-box {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 9px;
    padding: 14px 16px;
    margin-bottom: 12px;
  }
  #mhm-editprofile .lx-guide-box:last-child {
    margin-bottom: 0;
  }
  #mhm-editprofile .lx-guide-box p {
    margin: 0;
  }
  #mhm-editprofile .lx-guide-box ul {
    margin: 8px 0;
    padding-left: 18px;
  }
  #mhm-editprofile .lx-guide-box li {
    margin-bottom: 4px;
  }

  /* Footer */
  #mhm-editprofile .lx-footer {
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

<div id="mhm-editprofile">
  <!-- Main Form Card -->
  <div class="lx-card">
    <form autocomplete="off" method="post" action="" style="margin:0;">
      <!-- Top Bar -->
      <div class="lx-topbar">
        <div class="lx-title-group">
          <h3 class="lx-title"><?= $_edit . " " . $_user_profile ?></h3>
          <?php if ($monActive): ?>
            <span class="lx-pill is-active" title="Scheduler Monitor Aktif">
              <span class="lx-dot is-green"></span>
              <span>Monitor Aktif</span>
            </span>
          <?php else: ?>
            <span class="lx-pill is-idle" title="Scheduler Monitor Tidak Aktif">
              <span class="lx-dot is-orange"></span>
              <span>Monitor Nonaktif</span>
            </span>
          <?php endif; ?>
        </div>

        <div class="lx-actions">
          <a class="lx-btn" href="./?hotspot=user-profiles&session=<?= $session; ?>">
            <i class="fa fa-close"></i>
            <span><?= $_close ?></span>
          </a>
          <button type="submit" name="save" class="lx-btn is-primary">
            <i class="fa fa-save"></i>
            <span><?= $_save ?></span>
          </button>
        </div>
      </div>

      <!-- Form Grid -->
      <div class="lx-body">
        <div class="lx-form-grid">
          <div class="lx-field is-full">
            <label class="lx-label">
              <span><?= $_name ?></span>
              <span class="lx-label-hint">Spasi otomatis diubah menjadi tanda hubung (-)</span>
            </label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              onchange="remSpace();"
              autocomplete="off"
              name="name"
              value="<?= htmlspecialchars($pname); ?>"
              required="1"
              autofocus
            >
          </div>

          <div class="lx-field">
            <label class="lx-label">Address Pool</label>
            <select class="form-control lx-select" name="ppool">
              <option><?= htmlspecialchars($ppool); ?></option>
              <option>none</option>
              <?php
              $TotalReg = count($getpool);
              for ($i = 0; $i < $TotalReg; $i++) {
                echo "<option>" . htmlspecialchars($getpool[$i]['name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div class="lx-field">
            <label class="lx-label">Shared Users</label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              size="4"
              autocomplete="off"
              name="sharedusers"
              value="<?= htmlspecialchars($psharedu); ?>"
              required="1"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label">
              <span>Rate limit [up/down]</span>
              <span class="lx-label-hint">Opsional</span>
            </label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              name="ratelimit"
              autocomplete="off"
              value="<?= htmlspecialchars($pratelimit); ?>"
              placeholder="Example : 512k/1M"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label">Parent Queue</label>
            <select class="form-control lx-select" name="parent">
              <option><?= htmlspecialchars($sparent); ?></option>
              <option>none</option>
              <?php
              $TotalReg = count($getallqueue);
              for ($i = 0; $i < $TotalReg; $i++) {
                echo "<option>" . htmlspecialchars($getallqueue[$i]['name']) . "</option>";
              }
              ?>
            </select>
          </div>

          <div class="lx-field">
            <label class="lx-label"><?= $_expired_mode ?></label>
            <select class="form-control lx-select" onchange="RequiredV();" id="expmode" name="expmode" required="1">
              <option value="<?= htmlspecialchars($getexpmode); ?>"><?= htmlspecialchars($getexpmodet); ?></option>
              <option value="0">None</option>
              <option value="rem">Remove</option>
              <option value="ntf">Notice</option>
              <option value="remc">Remove & Record</option>
              <option value="ntfc">Notice & Record</option>
            </select>
          </div>

          <div class="lx-field" id="validity" <?php if ($getexpmodet == "None") { echo 'style="display:none;"'; } ?>>
            <label class="lx-label"><?= $_validity ?></label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              id="validi"
              size="4"
              autocomplete="off"
              name="validity"
              value="<?= htmlspecialchars($getvalid); ?>"
              required="1"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label"><?= $_price . " " . $currency; ?></label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              min="0"
              name="price"
              placeholder="0"
              value="<?= htmlspecialchars($getprice); ?>"
            >
          </div>

          <div class="lx-field">
            <label class="lx-label"><?= $_selling_price . ' ' . $currency; ?></label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              size="10"
              min="0"
              name="sprice"
              placeholder="0"
              value="<?= htmlspecialchars($getsprice); ?>"
            >
          </div>

          <div class="lx-field is-full">
            <label class="lx-label">
              <span><?= $_lock_user ?></span>
              <span class="lx-label-hint">Kunci akun ke MAC Address perangkat pertama</span>
            </label>
            <select class="form-control lx-select" id="lockunlock" name="lockunlock" required="1">
              <option value="<?= htmlspecialchars($getlocku); ?>"><?= htmlspecialchars($getlocku); ?></option>
              <option value="Enable">Enable</option>
              <option value="Disable">Disable</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="lx-footer">
        <div>ID Profile: <strong style="color:#18181b; font-family:monospace;"><?= htmlspecialchars($pid); ?></strong></div>
        <div>Klik <strong style="color:#18181b;">Save</strong> untuk memperbarui profil &amp; scheduler</div>
      </div>
    </form>
  </div>

  <!-- Read Me Card -->
  <div class="lx-card">
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title"><i class="fa fa-book" style="color:#71717a; margin-right:6px;"></i><?= $_readme ?></h3>
      </div>
    </div>

    <div class="lx-guide-body">
      <div class="lx-guide-box">
        <p><?= $_details_user_profile ?></p>
      </div>
      <div class="lx-guide-box">
        <p><?= $_format_validity ?></p>
      </div>
    </div>
  </div>
</div>