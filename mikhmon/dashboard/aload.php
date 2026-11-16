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
  // load session MikroTik
  $session = $_GET['session'];
  $load = $_GET['load'];

  // lang
  include('../include/lang.php');
  include('../lang/' . $langid . '.php');

  // load config
  include('../include/config.php');
  include('../include/readcfg.php');

  // routeros api
  include_once('../lib/routeros_api.class.php');
  include_once('../lib/formatbytesbites.php');
  $API = new RouterosAPI();
  $API->debug = false;

  if ($load == "sysresource") {

    $API->connect($iphost, $userhost, decrypt($passwdhost));

    // get MikroTik system clock
    $getclock = $API->comm("/system/clock/print");
    $clock = $getclock[0];
    $timezone = $getclock[0]['time-zone-name'];
    $_SESSION['timezone'] = $timezone;
    date_default_timezone_set($timezone);

    // get system resource MikroTik
    $getresource = $API->comm("/system/resource/print");
    $resource = $getresource[0];

    // get routeboard info
    $getrouterboard = $API->comm("/system/routerboard/print");
    $routerboard = $getrouterboard[0];

    // get health info
    $gethealth = $API->comm("/system/health/print");
    $voltage = "";
    $temperature = "";
    if (is_array($gethealth) && count($gethealth) > 0) {
      if (isset($gethealth[0]['voltage']) || isset($gethealth[0]['temperature']) || isset($gethealth[0]['cpu-temperature'])) {
        $voltage = isset($gethealth[0]['voltage']) ? $gethealth[0]['voltage'] : "";
        $temperature = isset($gethealth[0]['temperature']) ? $gethealth[0]['temperature'] : (isset($gethealth[0]['cpu-temperature']) ? $gethealth[0]['cpu-temperature'] : "");
      } else {
        foreach ($gethealth as $h) {
          if (isset($h['name']) && isset($h['value'])) {
            if ($h['name'] == 'voltage' && $voltage == "") {
              $voltage = $h['value'];
            } elseif (($h['name'] == 'temperature' || $h['name'] == 'cpu-temperature' || $h['name'] == 'board-temperature1') && $temperature == "") {
              $temperature = $h['value'];
            }
          }
        }
      }
    }
    ?>
    
    <div id="r_1" class="row">
      <div class="col-4">
        <div class="box stat-card">
          <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
          <div class="stat-content">
            <div class="stat-label"><?= $_system_date_time ?></div>
            <div class="stat-value">
              <?php 
              echo ucfirst($clock['date']) . " &bull; " . $clock['time'] . "<br>";
              echo $_uptime . ": <strong>" . formatDTM($resource['uptime']) . "</strong>";
              $_SESSION[$session.'sdate'] = $clock['date'];
              ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-4">
        <div class="box stat-card">
          <div class="stat-icon"><i class="fa fa-microchip"></i></div>
          <div class="stat-content">
            <div class="stat-label">RouterBoard &amp; OS</div>
            <div class="stat-value">
              <?php
              echo $_board_name . ": <strong>" . $resource['board-name'] . "</strong> (" . $routerboard['model'] . ")<br/>";
              echo "RouterOS: <strong>" . $resource['version'] . "</strong>";
              if ($voltage != "" || $temperature != "") {
                $healthItems = array();
                if ($voltage != "") {
                  $healthItems[] = "<strong><i class='fa fa-bolt'></i> " . rtrim($voltage, "Vv") . "V</strong>";
                }
                if ($temperature != "") {
                  $healthItems[] = "<strong><i class='fa fa-thermometer-half'></i> " . rtrim($temperature, "Cc°") . "&deg;C</strong>";
                }
                echo "<br/>Health: " . implode(" &bull; ", $healthItems);
              }
              ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-4">
        <div class="box stat-card">
          <div class="stat-icon"><i class="fa fa-server"></i></div>
          <div class="stat-content">
            <div class="stat-label">Resource Load</div>
            <div class="stat-value">
              <?php
              echo $_cpu_load . ": <strong>" . $resource['cpu-load'] . "%</strong> &bull; ";
              echo $_free_memory . ": <strong>" . formatBytes($resource['free-memory'], 2) . "</strong><br/>";
              echo $_free_hdd . ": <strong>" . formatBytes($resource['free-hdd-space'], 2) . "</strong>";
              ?>
            </div>
          </div>
        </div>
      </div> 
    </div>

  <?php 
  } else if ($load == "hotspot") {

    $API->connect($iphost, $userhost, decrypt($passwdhost));
    // get & counting hotspot users
    $countallusers = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));
    if ($countallusers < 2) {
      $uunit = "item";
    } elseif ($countallusers > 1) {
      $uunit = "items";
    }

    // get & counting hotspot active
    $counthotspotactive = $API->comm("/ip/hotspot/active/print", array("count-only" => ""));
    if ($counthotspotactive < 2) {
      $hunit = "item";
    } elseif ($counthotspotactive > 1) {
      $hunit = "items";
    }
    ?>
    
    <div id="r_2" class="row">
      <div class="card" style="width: 100%;">
        <div class="card-header">
          <h3><i class="fa fa-wifi"></i> Hotspot Overview</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <!-- Metric 1: Active Users -->
            <div class="col-3 col-box-6">
              <div class="box metric-tile">
                <a onclick="cancelPage()" href="./?hotspot=active&session=<?= $session; ?>">
                  <div class="metric-tile-top">
                    <span class="metric-tile-title"><?= $_hotspot_active ?></span>
                    <span class="metric-badge badge-blue"><i class="fa fa-wifi"></i></span>
                  </div>
                  <h1 class="metric-tile-num"><?= $counthotspotactive; ?> <span><?= $hunit; ?></span></h1>
                </a>
              </div>
            </div>

            <!-- Metric 2: Total Users -->
            <div class="col-3 col-box-6">
              <div class="box metric-tile">
                <a onclick="cancelPage()" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
                  <div class="metric-tile-top">
                    <span class="metric-tile-title"><?= $_hotspot_users ?></span>
                    <span class="metric-badge badge-green"><i class="fa fa-users"></i></span>
                  </div>
                  <h1 class="metric-tile-num"><?= $countallusers; ?> <span><?= $uunit; ?></span></h1>
                </a>
              </div>
            </div>

            <!-- Quick Action 1: Add User -->
            <div class="col-3 col-box-6">
              <div class="box metric-tile action-tile">
                <a onclick="cancelPage()" href="./?hotspot-user=add&session=<?= $session; ?>">
                  <div class="metric-tile-top">
                    <span class="metric-tile-title"><?= $_hotspot_users ?></span>
                    <span class="metric-badge badge-amber"><i class="fa fa-user-plus"></i></span>
                  </div>
                  <h1 class="metric-tile-num" style="font-size: 16px !important; padding-top: 4px;"><?= $_add ?></h1>
                </a>
              </div>
            </div>

            <!-- Quick Action 2: Generate Voucher -->
            <div class="col-3 col-box-6">
              <div class="box metric-tile action-tile">
                <a onclick="cancelPage()" href="./?hotspot-user=generate&session=<?= $session; ?>">
                  <div class="metric-tile-top">
                    <span class="metric-tile-title">Voucher</span>
                    <span class="metric-badge badge-slate"><i class="fa fa-ticket"></i></span>
                  </div>
                  <h1 class="metric-tile-num" style="font-size: 16px !important; padding-top: 4px;"><?= $_generate ?></h1>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  <?php 
  } else if ($load == "logs") {

    $API->connect($iphost, $userhost, decrypt($passwdhost));

    // move hotspot log to disk
    $getlogging = $API->comm("/system/logging/print", array("?prefix" => "->"));
    $logging = $getlogging[0];
    if ($logging['prefix'] == "->") {
    } else {
      $API->comm("/system/logging/add", array("action" => "disk", "prefix" => "->", "topics" => "hotspot,info,debug"));
    }
    
    // get hotspot log
    $getlog = $API->comm("/log/print", array("?topics" => "hotspot,info,debug"));
    $log = array_reverse($getlog);

    if ($livereport == "disable") {
      $logh = "457px";
      $lreport = "style='display:none;'";
    } else {
      $logh = "350px";
      $lreport = "style='display:block;'";
    }
    ?>
  
    <div id="r_3" class="row">
      <div class="card" style="width: 100%;">
        <div class="card-header">
          <h3><a onclick="cancelPage()" href="./?hotspot=log&session=<?= $session; ?>" title="Open Hotspot Log"><i class="fa fa-align-justify"></i> <?= $_hotspot_log ?></a></h3>
        </div>
        <div class="card-body" style="padding: 0 !important;">
          <div style="height: <?= $logh; ?>;" class="overflow">
            <table class="table table-sm table-hover" style="font-size: 12px; margin: 0;">
              <thead>
                <tr>
                  <th><?= $_time ?></th>
                  <th><?= $_users ?> (IP)</th>
                  <th><?= $_messages ?></th>
                </tr>
              </thead>
              <tbody>
                <?php
                for ($i = 0; $i < 20; $i++) {
                  $mess = explode(":", $log[$i]['message']);
                  $time = $log[$i]['time'];
                  echo "<tr>";
                  if (substr($log[$i]['message'], 0, 2) == "->") {
                    echo "<td>" . $time . "</td>";
                    echo "<td>";
                    if (count($mess) > 6) {
                      echo $mess[1] . ":" . $mess[2] . ":" . $mess[3] . ":" . $mess[4] . ":" . $mess[5] . ":" . $mess[6];
                    } else {
                      echo $mess[1];
                    }
                    echo "</td>";
                    echo "<td>";
                    if (count($mess) > 6) {
                      echo str_replace("trying to", "", $mess[7] . " " . $mess[8] . " " . $mess[9] . " " . $mess[10]);
                    } else {
                      echo str_replace("trying to", "", $mess[2] . " " . $mess[3] . " " . $mess[4] . " " . $mess[5]);
                    }
                    echo "</td>";
                  }
                  echo "</tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <?php 
  }
}
?>