<?php
/*
 * Copyright (C) 2018 Laksamadi Guko.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

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

  if ($livereport == "disable") {
    $logh = "457px";
    $lreport = "style='display:none;'";
  } else {
    $logh = "350px";
    $lreport = "style='display:block;'";
  }
}
?>

<style>
  /* Paksa box-sizing seluruh elemen dashboard */
  #reloadHome,
  #reloadHome * {
    box-sizing: border-box !important;
  }

  /* Kunci lebar & jarak 20px antara kolom kiri (.col-8) dan kolom kanan (.col-4) */
  #reloadHome > .row:not(#r_1) {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: flex-start !important;
    margin-left: -10px !important;
    margin-right: -10px !important;
    width: auto !important;
  }

  #reloadHome > .row:not(#r_1) > .col-8 {
    width: 66.666667% !important;
    max-width: 66.666667% !important;
    flex: 0 0 66.666667% !important;
    padding-left: 10px !important;
    padding-right: 10px !important;
    min-width: 0 !important;
  }

  #reloadHome > .row:not(#r_1) > .col-4 {
    width: 33.333333% !important;
    max-width: 33.333333% !important;
    flex: 0 0 33.333333% !important;
    padding-left: 10px !important;
    padding-right: 10px !important;
    min-width: 0 !important;
  }

  @media (max-width: 992px) {
    #reloadHome > .row:not(#r_1) > .col-8,
    #reloadHome > .row:not(#r_1) > .col-4 {
      width: 100% !important;
      max-width: 100% !important;
      flex: 0 0 100% !important;
    }
  }

  /* Paksa semua wrapper & card (termasuk hasil AJAX livereport) tetap di dalam kolomnya */
  #reloadHome #r_2,
  #reloadHome #r_3,
  #reloadHome #r_4,
  #reloadHome #r_4 .row,
  #reloadHome .col-8 > .card,
  #reloadHome .col-4 > .card,
  #reloadHome #r_2 .card,
  #reloadHome #r_3 .card,
  #reloadHome #r_4 .stat-card,
  #reloadHome #r_4 .lr-card,
  #reloadHome #r_4 .box {
    width: 100% !important;
    max-width: 100% !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    float: none !important;
  }

  /* Jarak vertikal antar card atas dan bawah */
  #reloadHome #r_2,
  #reloadHome #r_4 {
    margin-bottom: 20px !important;
  }

  #reloadHome #r_2 .card,
  #reloadHome #r_4 .stat-card,
  #reloadHome #r_4 .lr-card,
  #reloadHome #r_4 .box {
    margin-bottom: 0 !important;
    min-height: auto !important;
  }

  /* Kunci 4 kotak Hotspot Overview agar tidak melebar keluar card */
  #reloadHome #r_2 .card-body > .row {
    display: flex !important;
    flex-wrap: wrap !important;
    margin-left: -6px !important;
    margin-right: -6px !important;
  }

  #reloadHome #r_2 .col-3 {
    width: 25% !important;
    max-width: 25% !important;
    flex: 0 0 25% !important;
    padding-left: 6px !important;
    padding-right: 6px !important;
    min-width: 0 !important;
  }

  #reloadHome #r_2 .metric-tile {
    width: 100% !important;
    margin: 0 0 6px 0 !important;
  }

  /* Styling Header Traffic Card di Dashboard */
  #reloadHome .dash-traffic-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 12px !important;
  }

  #reloadHome .dash-traffic-meta {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
  }

  #reloadHome .dash-iface-badge {
    padding: 3px 10px !important;
    border-radius: 999px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #334155 !important;
    font-size: 11.5px !important;
    font-weight: 500 !important;
  }

  #reloadHome .dash-live-badge {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    padding: 3px 9px !important;
    border-radius: 999px !important;
    background: #f0fdf4 !important;
    border: 1px solid #dcfce7 !important;
    color: #15803d !important;
    font-size: 10.5px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.03em !important;
  }

  #reloadHome .dash-live-dot {
    width: 6px !important;
    height: 6px !important;
    border-radius: 50% !important;
    background: #22c55e !important;
    animation: dashPulse 2s infinite !important;
  }

  @keyframes dashPulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
    100% { opacity: 1; transform: scale(1); }
  }
</style>
    
<div id="reloadHome">

  <!-- Row 1: Router System Status -->
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

  <!-- Main Content Grid -->
  <div class="row">
    <!-- Left Column (8 cols) -->
    <div class="col-8">
      <div id="r_2">
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

      <!-- Traffic Monitor Card -->
      <?php 
      $getinterface = $API->comm("/interface/print");
      $interface = $getinterface[$iface - 1]['name']; 
      ?>
      <div class="card">
        <div class="card-header dash-traffic-header">
          <h3><i class="fa fa-area-chart"></i> <?= $_traffic ?></h3>
          <div class="dash-traffic-meta">
            <span class="dash-iface-badge"><?= htmlspecialchars($interface); ?></span>
            <span class="dash-live-badge"><span class="dash-live-dot"></span> Live</span>
          </div>
        </div>
        <div class="card-body">
          <script type="text/javascript"> 
            var chart;
            var sessiondata = "<?= $session ?>";
            var interface = "<?= $interface ?>";
            var n = 3000;

            function formatDashBits(bytes) {
              var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
              if (!bytes || bytes <= 0) return '0 bps';
              var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
              if (i >= sizes.length) i = sizes.length - 1;
              return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function requestDatta(session, iface) {
              $.ajax({
                url: './traffic/traffic.php?session=' + session + '&iface=' + iface,
                datatype: "json",
                success: function(data) {
                  var midata = JSON.parse(data);
                  if (midata.length > 0) {
                    var TX = parseInt(midata[0].data);
                    var RX = parseInt(midata[1].data);
                    var x = (new Date()).getTime(); 
                    var shift = chart.series[0].data.length > 19;
                    chart.series[0].addPoint([x, TX], true, shift);
                    chart.series[1].addPoint([x, RX], true, shift);
                  }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) { 
                  console.error("Status: " + textStatus + " request: " + XMLHttpRequest);
                  console.error("Error: " + errorThrown); 
                }       
              });
            } 

            $(document).ready(function() {
              Highcharts.setOptions({
                global: {
                  useUTC: false
                },
                chart: {
                  height: 310,
                  style: {
                    fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
                  }
                }
              });

              Highcharts.addEvent(Highcharts.Series, 'afterInit', function () {
                this.symbolUnicode = {
                  circle: '●',
                  diamond: '♦',
                  square: '■',
                  triangle: '▲',
                  'triangle-down': '▼'
                }[this.symbol] || '●';
              });

              chart = new Highcharts.Chart({
                chart: {
                  renderTo: 'trafficMonitor',
                  animation: Highcharts.svg,
                  type: 'areaspline',
                  backgroundColor: 'transparent',
                  plotBorderWidth: 0,
                  spacingTop: 10,
                  spacingBottom: 6,
                  spacingLeft: 0,
                  spacingRight: 8,
                  events: {
                    load: function () {
                      setInterval(function () {
                        requestDatta(sessiondata, interface);
                      }, 8000);
                    }       
                  }
                },
                credits: {
                  enabled: false
                },
                title: {
                  text: '<?= $_interface ?> ' + interface,
                  align: 'left',
                  margin: 18,
                  style: {
                    color: '#334155',
                    fontSize: '13px',
                    fontWeight: '600'
                  }
                },
                xAxis: {
                  type: 'datetime',
                  tickPixelInterval: 150,
                  maxZoom: 20 * 1000,
                  lineColor: '#e2e8f0',
                  tickColor: '#e2e8f0',
                  gridLineWidth: 0,
                  labels: {
                    style: {
                      color: '#64748b',
                      fontSize: '11px'
                    }
                  }
                },
                yAxis: {
                  minPadding: 0.2,
                  maxPadding: 0.2,
                  gridLineColor: '#f1f5f9',
                  gridLineDashStyle: 'Dash',
                  title: {
                    text: null
                  },
                  labels: {
                    style: {
                      color: '#64748b',
                      fontSize: '11px'
                    },
                    formatter: function () {      
                      return formatDashBits(this.value);
                    }
                  }       
                },
                legend: {
                  align: 'center',
                  verticalAlign: 'bottom',
                  itemStyle: {
                    color: '#334155',
                    fontWeight: '500',
                    fontSize: '12px'
                  },
                  itemHoverStyle: {
                    color: '#0f172a'
                  },
                  symbolRadius: 6
                },
                plotOptions: {
                  areaspline: {
                    lineWidth: 2.2,
                    states: {
                      hover: {
                        lineWidth: 2.8
                      }
                    },
                    marker: {
                      enabled: true,
                      radius: 3.5,
                      lineWidth: 1.5,
                      lineColor: '#ffffff'
                    }
                  }
                },
                series: [{
                  name: 'Tx',
                  color: '#2563eb',
                  fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                      [0, 'rgba(37, 99, 235, 0.14)'],
                      [1, 'rgba(37, 99, 235, 0.0)']
                    ]
                  },
                  data: [],
                  marker: {
                    symbol: 'circle'
                  }
                }, {
                  name: 'Rx',
                  color: '#f43f5e',
                  fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                      [0, 'rgba(244, 63, 94, 0.14)'],
                      [1, 'rgba(244, 63, 94, 0.0)']
                    ]
                  },
                  data: [],
                  marker: {
                    symbol: 'circle'
                  }
                }],
                tooltip: {
                  useHTML: true,
                  backgroundColor: 'rgba(255, 255, 255, 0.96)',
                  borderColor: '#e2e8f0',
                  borderRadius: 10,
                  borderWidth: 1,
                  shadow: {
                    color: 'rgba(15, 23, 42, 0.08)',
                    offsetX: 0,
                    offsetY: 4,
                    opacity: 0.15,
                    width: 10
                  },
                  style: {
                    color: '#1e293b',
                    fontSize: '12px'
                  },
                  formatter: function () { 
                    var s = [];
                    $.each(this.points, function(i, point) {
                      s.push(
                        '<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:4px;">' +
                          '<span style="display:inline-flex;align-items:center;gap:6px;color:#475569;">' +
                            '<span style="color:' + point.series.color + ';font-size:13px;">' + point.series.symbolUnicode + '</span>' +
                            '<b>' + point.series.name + '</b>' +
                          '</span>' +
                          '<span style="font-weight:600;color:#0f172a;">' + formatDashBits(point.y) + '</span>' +
                        '</div>'
                      );
                    });
                    return (
                      '<div style="padding:3px 5px;min-width:165px;">' +
                        '<div style="font-weight:600;color:#0f172a;margin-bottom:2px;">Packet Traffic Monitor</div>' +
                        '<div style="font-size:11px;color:#64748b;padding-bottom:5px;border-bottom:1px solid #f1f5f9;">Time: ' +
                          Highcharts.dateFormat('%H:%M:%S', new Date(this.x)) +
                        '</div>' +
                        s.join('') +
                      '</div>'
                    );
                  },
                  shared: true                                                      
                }
              });
            });
          </script>
          <div id="trafficMonitor"></div>
        </div> 
      </div>
    </div>  

    <!-- Right Column (4 cols) -->
    <div class="col-4">
      <!-- Income Summary -->
      <div id="r_4">
        <div <?= $lreport; ?> class="box stat-card" style="width: 100%;">
          <div class="stat-icon" style="background: var(--success-soft); color: var(--success);">
            <i class="fa fa-money"></i>
          </div>
          <div class="stat-content">
            <div class="stat-label"><?= $_income ?></div>
            <div class="stat-value">
              <div id="reloadLreport">
                <?php 
                if ($_SESSION[$session.'sdate'] == $_SESSION[$session.'idhr']){
                  echo $_today . " (" . $_SESSION[$session.'totalHr'] . " vcr): <strong>" . $currency . " " . $_SESSION[$session.'dincome'] . "</strong><br/>";
                  echo $_this_month . " (" . $_SESSION[$session.'totalBl'] . " vcr): <strong>" . $currency . " " . $_SESSION[$session.'mincome'] . "</strong>"; 
                }else{
                  echo "<div id='loader'><i class='fa fa-circle-o-notch fa-spin'></i> " . $_processing . "</div>";
                }
                ?>                       
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hotspot Log -->
      <div id="r_3">
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
                  <tr>
                    <td colspan="3" class="text-center" style="padding: 24px !important;">
                      <div id="loader"><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?></div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>