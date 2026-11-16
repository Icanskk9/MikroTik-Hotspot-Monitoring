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

  $getallqueue = $API->comm("/queue/simple/print", array(
    "?dynamic" => "false",
  ));

  $getpool = $API->comm("/ip/pool/print");

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

    $record = '; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-'.$price.'-|-$address-|-$mac-|-' . $validity . '-|-'.$name.'-|-$comment" owner="$month$year" source="$date" comment="mikhmon"';

    $onlogin = ':put (",'.$expmode.',' . $price . ',' . $validity . ','.$sprice.',,' . $getlock . ',"); {:local comment [ /ip hotspot user get [/ip hotspot user find where name="$user"] comment]; :local ucode [:pic $comment 0 2]; :if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ]; /sys sch add name="$user" disable=no start-date=$date interval="' . $validity . '"; :delay 5s; :local exp [ /sys sch get [ /sys sch find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pic $exp 0 6]; :local t [:pic $exp 7 16]; :local s ("/"); :local exp ("$d$s$year $t"); /ip hotspot user set comment="$exp" [find where name="$user"];}; :if ($getxp = 8) do={ /ip hotspot user set comment="$date $exp" [find where name="$user"];}; :if ($getxp > 15) do={ /ip hotspot user set comment="$exp" [find where name="$user"];};:delay 5s; /sys sch remove [find where name="$user"]';

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

    $bgservice = ':local dateint do={:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );:local days [ :pick $d 4 6 ];:local month [ :pick $d 0 3 ];:local year [ :pick $d 7 11 ];:local monthint ([ :find $montharray $month]);:local month ($monthint + 1);:if ( [len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}}; :local timeint do={ :local hours [ :pick $t 0 2 ]; :local minutes [ :pick $t 3 5 ]; :return ($hours * 60 + $minutes) ; }; :local date [ /system clock get date ]; :local time [ /system clock get time ]; :local today [$dateint d=$date] ; :local curtime [$timeint t=$time] ; :foreach i in [ /ip hotspot user find where profile="'.$name.'" ] do={ :local comment [ /ip hotspot user get $i comment]; :local name [ /ip hotspot user get $i name]; :local gettime [:pic $comment 12 20]; :if ([:pic $comment 3] = "/" and [:pic $comment 6] = "/") do={:local expd [$dateint d=$comment] ; :local expt [$timeint t=$gettime] ; :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ [ /ip hotspot user '.$mode.' $i ]; [ /ip hotspot active remove [find where user=$name] ];}}}';

    $API->comm("/ip/hotspot/user/profile/add", array(
      /*"add-mac-cookie" => "yes",*/
      "name" => "$name",
      "address-pool" => "$addrpool",
      "rate-limit" => "$ratelimit",
      "shared-users" => "$sharedusers",
      "status-autorefresh" => "1m",
      //"transparent-proxy" => "yes",
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

    $getprofile = $API->comm("/ip/hotspot/user/profile/print", array(
      "?name" => "$name",
    ));
    $pid = $getprofile[0]['.id'];
    echo "<script>window.location='./?user-profile=" . $pid . "&session=" . $session . "'</script>";
  }
}
?>

<style>
  #mhm-addprofile-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 350px;
    gap: 18px;
    align-items: start;
    color: #18181b;
  }
  @media (max-width: 992px) {
    #mhm-addprofile-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  .lx-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }

  /* Top Bar */
  .lx-card .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
  }
  .lx-card .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .lx-card .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  .lx-card .lx-pill {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
  }
  .lx-card #loader {
    font-size: 12px;
    color: #71717a;
  }

  /* Buttons */
  .lx-card .lx-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .lx-card .lx-btn {
    height: 34px;
    padding: 0 14px;
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
  .lx-card .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  .lx-card .lx-btn:active {
    background: #f4f4f5;
  }
  .lx-card .lx-btn i {
    font-size: 12px;
    color: #71717a;
  }
  .lx-card .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  .lx-card .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  .lx-card .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }

  /* Form Body */
  .lx-card .lx-form-body {
    padding: 22px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #ffffff;
  }
  .lx-card .lx-field {
    display: grid;
    grid-template-columns: 165px minmax(0, 1fr);
    align-items: center;
    gap: 14px;
    width: 100%;
  }
  @media (max-width: 576px) {
    .lx-card .lx-field {
      grid-template-columns: minmax(0, 1fr);
      gap: 6px;
    }
  }
  .lx-card .lx-label {
    font-size: 13px;
    font-weight: 500;
    color: #3f3f46;
    margin: 0;
  }

  /* Inputs & Selects */
  .lx-card .lx-input,
  .lx-card .lx-select {
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
  .lx-card .lx-input::placeholder {
    color: #a1a1aa !important;
  }
  .lx-card .lx-input:hover,
  .lx-card .lx-select:hover {
    border-color: #d4d4d8 !important;
    background: #ffffff !important;
  }
  .lx-card .lx-input:focus,
  .lx-card .lx-select:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  .lx-card .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12.5px !important;
  }

  /* Currency Prefix Addon */
  .lx-card .lx-prefix-group {
    display: flex;
    align-items: stretch;
    width: 100%;
  }
  .lx-card .lx-prefix-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    min-width: 46px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-right: none;
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #52525b;
    user-select: none;
  }
  .lx-card .lx-prefix-group .lx-input {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    flex: 1;
  }

  /* Bottom Form Action Bar */
  .lx-card .lx-form-bottom {
    margin-top: 4px;
    padding-top: 16px;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
  }

  /* Read Me Sidebar */
  .lx-readme-body {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #ffffff;
  }
  .lx-readme-box {
    padding: 13px 15px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 9px;
    font-size: 12.5px;
    line-height: 1.65;
    color: #3f3f46;
  }
  .lx-readme-box p {
    margin: 0;
  }
  .lx-readme-box ul {
    margin: 6px 0 0;
    padding-left: 18px;
  }
  .lx-readme-box li {
    margin-bottom: 4px;
  }
  .lx-readme-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
    margin-bottom: 6px;
  }

  /* Footer */
  .lx-card .lx-footer {
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

<div id="mhm-addprofile-grid">
  <!-- Left Card: Add User Profile Form -->
  <div class="lx-card">
    <form autocomplete="off" method="post" action="" style="margin:0;">
      <!-- Top Bar -->
      <div class="lx-topbar">
        <div class="lx-title-group">
          <h3 class="lx-title"><?= $_add . ' ' . $_user_profile ?></h3>
          <span class="lx-pill">User Profile</span>
          <small id="loader" style="display: none;">
            <i class="fa fa-circle-o-notch fa-spin"></i> Processing...
          </small>
        </div>

        <div class="lx-actions">
          <a class="lx-btn" href="./?hotspot=user-profiles&session=<?= $session; ?>">
            <i class="fa fa-close"></i>
            <span><?= $_close ?></span>
          </a>
          <button type="submit" name="save" onclick="loader()" class="lx-btn is-primary">
            <i class="fa fa-save"></i>
            <span><?= $_save ?></span>
          </button>
        </div>
      </div>

      <!-- Form Body -->
      <div class="lx-form-body">
        <div class="lx-field">
          <label class="lx-label"><?= $_name ?></label>
          <input
            class="form-control lx-input"
            type="text"
            onchange="remSpace();"
            autocomplete="off"
            name="name"
            placeholder="Contoh: PAKET-1-JAM"
            value=""
            required="1"
            autofocus
          >
        </div>

        <div class="lx-field">
          <label class="lx-label">Address Pool</label>
          <select class="form-control lx-select" name="ppool">
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
            value="1"
            required="1"
          >
        </div>

        <div class="lx-field">
          <label class="lx-label">Rate limit [up/down]</label>
          <input
            class="form-control lx-input lx-mono"
            type="text"
            name="ratelimit"
            autocomplete="off"
            value=""
            placeholder="Example : 512k/1M"
          >
        </div>

        <div class="lx-field">
          <label class="lx-label" for="expmode"><?= $_expired_mode ?></label>
          <select class="form-control lx-select" onchange="RequiredV();" id="expmode" name="expmode" required="1">
            <option value="">Select...</option>
            <option value="0">None</option>
            <option value="rem">Remove</option>
            <option value="ntf">Notice</option>
            <option value="remc">Remove &amp; Record</option>
            <option value="ntfc">Notice &amp; Record</option>
          </select>
        </div>

        <!-- Conditional Validity Field (Toggled by RequiredV) -->
        <div id="validity" style="display:none; width:100%;">
          <div class="lx-field">
            <label class="lx-label" for="validi"><?= $_validity ?></label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              id="validi"
              size="4"
              autocomplete="off"
              name="validity"
              placeholder="Contoh: 1h, 1d, 30d"
              value=""
              required="1"
            >
          </div>
        </div>

        <!-- Conditional Grace Period Field (Toggled by RequiredV) -->
        <div id="graceperiod" style="display:none; width:100%;">
          <div class="lx-field">
            <label class="lx-label" for="gracepi"><?= $_grace_period ?></label>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              id="gracepi"
              size="4"
              autocomplete="off"
              name="graceperiod"
              placeholder="5m"
              value="5m"
              required="1"
            >
          </div>
        </div>

        <div class="lx-field">
          <label class="lx-label"><?= $_price . ' ' . $currency; ?></label>
          <div class="lx-prefix-group">
            <span class="lx-prefix-tag"><?= htmlspecialchars($currency); ?></span>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              size="10"
              min="0"
              name="price"
              placeholder="0"
              value=""
            >
          </div>
        </div>

        <div class="lx-field">
          <label class="lx-label"><?= $_selling_price . ' ' . $currency; ?></label>
          <div class="lx-prefix-group">
            <span class="lx-prefix-tag"><?= htmlspecialchars($currency); ?></span>
            <input
              class="form-control lx-input lx-mono"
              type="text"
              size="10"
              min="0"
              name="sprice"
              placeholder="0"
              value=""
            >
          </div>
        </div>

        <div class="lx-field">
          <label class="lx-label" for="lockunlock"><?= $_lock_user ?></label>
          <select class="form-control lx-select" id="lockunlock" name="lockunlock" required="1">
            <option value="Disable">Disable</option>
            <option value="Enable">Enable</option>
          </select>
        </div>

        <div class="lx-field">
          <label class="lx-label">Parent Queue</label>
          <select class="form-control lx-select" name="parent">
            <option>none</option>
            <?php
            $TotalReg = count($getallqueue);
            for ($i = 0; $i < $TotalReg; $i++) {
              echo "<option>" . htmlspecialchars($getallqueue[$i]['name']) . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="lx-form-bottom">
          <a class="lx-btn" href="./?hotspot=user-profiles&session=<?= $session; ?>">
            <i class="fa fa-close"></i>
            <span><?= $_close ?></span>
          </a>
          <button type="submit" name="save" onclick="loader()" class="lx-btn is-primary">
            <i class="fa fa-save"></i>
            <span><?= $_save ?></span>
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div class="lx-footer">
        <div>Spasi pada nama profil otomatis diubah menjadi tanda hubung (<strong style="color:#18181b;">-</strong>)</div>
        <div>MikroTik Hotspot Profile</div>
      </div>
    </form>
  </div>

  <!-- Right Card: Read Me Guide -->
  <div class="lx-card">
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title"><?= $_readme ?></h3>
      </div>
      <span class="lx-pill">Panduan</span>
    </div>

    <div class="lx-readme-body">
      <div class="lx-readme-box">
        <div class="lx-readme-label">Expired Mode &amp; Lock User</div>
        <p><?= $_details_user_profile ?></p>
      </div>

      <div class="lx-readme-box">
        <div class="lx-readme-label">Format Validity</div>
        <p><?= $_format_validity ?></p>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
function remSpace() {
  var upName = document.getElementsByName("name")[0];
  var newUpName = upName.value.replace(/\s/g, "-");
  //alert("<?php if ($currency == in_array($currency, $cekindo['indo'])) {
            echo "Nama Profile tidak boleh berisi spasi";
          } else {
            echo "Profile name can't containing white space!";
          } ?>");
  upName.value = newUpName;
  upName.focus();
}
</script>