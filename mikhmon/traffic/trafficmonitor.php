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
}
?>

<style>
  /* Clean White Space & Luxury Minimalist Styling */
  .tm-card {
    background: #ffffff;
    border: 1px solid #eef2f6;
    border-radius: 16px;
    box-shadow: 0 4px 24px -4px rgba(15, 23, 42, 0.04);
    margin-bottom: 24px;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  .tm-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 28px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
  }

  .tm-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .tm-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0f172a;
    font-size: 15px;
  }

  .tm-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .tm-live-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    color: #15803d;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
  }

  .tm-live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #22c55e;
    animation: tmPulse 2s infinite;
  }

  @keyframes tmPulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
    100% { opacity: 1; transform: scale(1); }
  }

  .tm-control-group {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .tm-select-wrapper {
    position: relative;
    display: inline-block;
  }

  .tm-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 9px 38px 9px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    min-width: 210px;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
  }

  .tm-select:hover {
    background-color: #ffffff;
    border-color: #cbd5e1;
  }

  .tm-select:focus {
    background-color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .tm-body {
    padding: 28px 28px 24px 28px;
    background: #ffffff;
  }

  #trafficMonitor {
    width: 100%;
    min-height: 460px;
  }
</style>

<script>
  $(function() {
    var sessionEl = document.getElementById("MikhmonSession");
    var sesKey = sessionEl ? "Interface_" + sessionEl.value : null;

    // Sinkronkan pilihan dropdown dengan interface yang sedang aktif di sessionStorage
    if (sesKey && typeof(Storage) !== "undefined") {
      var savedIface = sessionStorage.getItem(sesKey);
      if (savedIface) {
        $("#d_interface").val(savedIface);
      }
    }

    $("#d_interface").on("change", function() {
      var selectedIface = $(this).val();
      var currentKey = "Interface_" + document.getElementById("MikhmonSession").value;
      if (selectedIface) {
        if (typeof(Storage) !== "undefined") {
          sessionStorage.setItem(currentKey, selectedIface);
        } else {
          alert("Please use Google Chrome");
        }
        window.location.reload();
      }
      return false;
    });
  });
</script>

<div class="card tm-card">
  <div class="tm-header">
    <div class="tm-title-group">
      <div class="tm-icon-box">
        <i class="fa fa-area-chart"></i>
      </div>
      <div>
        <h3 class="tm-title">
          <?= $_traffic_monitor ?>
          <span class="tm-live-badge">
            <span class="tm-live-dot"></span> Live
          </span>
        </h3>
      </div>
    </div>

    <?php
      $getinterface = $API->comm("/interface/print");
      $interface = $getinterface[$iface - 1]['name'];
      $TotalReg = count($getinterface);
    ?>
    <div class="tm-control-group">
      <div class="tm-select-wrapper">
        <select id="d_interface" class="tm-select">
          <option value=""><?= $_select_interface ?></option>
          <?php
            for ($i = 0; $i < $TotalReg; $i++) {
              echo '<option value="' . $getinterface[$i]['name'] . '">[' . ($i + 1) . '] ' . $getinterface[$i]['name'] . '</option>';
            }
          ?>
        </select>
      </div>
    </div>
  </div>

  <div class="tm-body">
    <div class="row">
      <script type="text/javascript">
        var chart;
        var sessiondata = "<?= $session ?>";

        function formatBits(bytes) {
          var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
          if (!bytes || bytes <= 0) return '0 bps';
          var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
          if (i >= sizes.length) i = sizes.length - 1;
          return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function requestDatta(session, iface) {
          if (!iface) return;
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
              height: 460,
              style: {
                fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
              }
            }
          });

          Highcharts.addEvent(Highcharts.Series, 'afterInit', function() {
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
              spacingTop: 16,
              spacingBottom: 12,
              spacingLeft: 4,
              spacingRight: 12,
              events: {
                load: function() {
                  setInterval(function() {
                    var sesIface = "Interface_" + document.getElementById("MikhmonSession").value;
                    var iface = sessionStorage.getItem(sesIface);
                    requestDatta(sessiondata, iface);
                    if (iface) {
                      chart.setTitle({ text: '<?= $_interface ?> ' + iface });
                    }
                  }, 3000);
                }
              }
            },
            credits: {
              enabled: false
            },
            title: {
              text: '<?= $_loading_interface ?>...',
              align: 'left',
              margin: 24,
              style: {
                color: '#334155',
                fontSize: '14px',
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
                formatter: function() {
                  return formatBits(this.value);
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
                lineWidth: 2.5,
                states: {
                  hover: {
                    lineWidth: 3
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
              borderRadius: 12,
              borderWidth: 1,
              shadow: {
                color: 'rgba(15, 23, 42, 0.08)',
                offsetX: 0,
                offsetY: 4,
                opacity: 0.15,
                width: 12
              },
              style: {
                color: '#1e293b',
                fontSize: '12px'
              },
              formatter: function() {
                var s = [];
                $.each(this.points, function(i, point) {
                  s.push(
                    '<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:4px;">' +
                      '<span style="display:inline-flex;align-items:center;gap:6px;color:#475569;">' +
                        '<span style="color:' + point.series.color + ';font-size:14px;">' + point.series.symbolUnicode + '</span>' +
                        '<b>' + point.series.name + '</b>' +
                      '</span>' +
                      '<span style="font-weight:600;color:#0f172a;">' + formatBits(point.y) + '</span>' +
                    '</div>'
                  );
                });
                return (
                  '<div style="padding:4px 6px;min-width:170px;">' +
                    '<div style="font-weight:600;color:#0f172a;margin-bottom:2px;">Mikhmon Traffic Monitor</div>' +
                    '<div style="font-size:11px;color:#64748b;padding-bottom:6px;border-bottom:1px solid #f1f5f9;">Time: ' +
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
      <div class="col-12" id="trafficMonitor"></div>
    </div>
  </div>
</div>