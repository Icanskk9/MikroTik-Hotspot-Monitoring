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

  // load config
  include('../include/config.php');
  include('../include/readcfg.php');

  $idbl = $_GET['idbl'];
  $thisM = substr($idbl, 0, 3);
  $thisY = substr($idbl, -4);

  $ms = array(1 => "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec");
  $mn = array_search($thisM, $ms);

  // https://secure.php.net/manual/en/function.cal-days-in-month.php#38666
  function days_in_month($month, $year)
  {
    // calculate number of days in a month
    return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
  }

  if ($mn == date("n")) {
    $totD = (date('d') + 1);
  } else {
    $totD = (days_in_month($mn, $thisY) + 1);
  }

  function resume_per_day($date)
  {
    $evalue = explode($date, $_SESSION['dataresume']);
    $x = count($evalue);
    for ($i = 0; $i < $x; $i++) {
      $result += (int) $evalue[$i];
    }
    return ($x - 1) . '/' . $result;
  }

  $totalvrc = explode("/", $_SESSION['totalresume'])[0];
  $totalincome = explode("/", $_SESSION['totalresume'])[1];

  $isIndo = ($currency == in_array($currency, $cekindo['indo']));
  if ($isIndo) {
    $formattedIncome = $currency . " " . number_format((float)$totalincome, 0, ",", ".");
  } else {
    $formattedIncome = $currency . " " . number_format((float)$totalincome, 2);
  }
  $totalreport = "Total " . $totalvrc . "vcr : " . $formattedIncome;
}
?>

<style>
  .rr-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
  }

  .rr-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
  }

  .rr-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .rr-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #111827;
  }

  .rr-badge {
    padding: 2px 10px;
    background: #f3f4f6;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    color: #4b5563;
  }

  .rr-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: background 0.15s ease;
  }

  .rr-back-btn:hover {
    background: #f9fafb;
  }

  .rr-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-bottom: 1px solid #e5e7eb;
    background: #ffffff;
  }

  .rr-summary-item {
    padding: 16px 24px;
    border-right: 1px solid #e5e7eb;
  }

  .rr-summary-item:last-child {
    border-right: none;
  }

  .rr-summary-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    margin-bottom: 6px;
  }

  .rr-summary-val {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 7px;
  }

  .rr-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
  }

  .rr-body {
    padding: 28px 24px 24px 24px;
    background: #ffffff;
  }

  @media (max-width: 640px) {
    .rr-summary {
      grid-template-columns: 1fr;
    }
    .rr-summary-item {
      border-right: none;
      border-bottom: 1px solid #e5e7eb;
    }
    .rr-summary-item:last-child {
      border-bottom: none;
    }
  }
</style>

<div class="card rr-card">
  <div class="rr-header">
    <div class="rr-title-wrap">
      <h3 class="rr-title">Resume Report</h3>
      <span class="rr-badge"><?= ucfirst($thisM) . " " . $thisY; ?></span>
    </div>
    <button type="button" class="rr-back-btn" onclick="history.back()">
      <i class="fa fa-arrow-left"></i> Kembali
    </button>
  </div>

  <div class="rr-summary">
    <div class="rr-summary-item">
      <div class="rr-summary-label">Periode Laporan</div>
      <div class="rr-summary-val"><?= ucfirst($thisM) . " " . $thisY; ?></div>
    </div>
    <div class="rr-summary-item">
      <div class="rr-summary-label">Voucher Terjual</div>
      <div class="rr-summary-val"><span class="rr-dot"></span> <?= $totalvrc ?: 0; ?> vcr</div>
    </div>
    <div class="rr-summary-item">
      <div class="rr-summary-label">Total Pendapatan</div>
      <div class="rr-summary-val"><?= $formattedIncome; ?></div>
    </div>
  </div>

  <div class="card-body rr-body">
    <div class="row">
      <script src="./js/highcharts/highcharts.js"></script>
      <script src="./js/highcharts/themes/hc.<?= $theme; ?>.js"></script>

      <div class="col-12" id="container"></div>

      <script type="text/javascript">
        var currencySymbol = "<?= $currency; ?>";
        var isIndoCurrency = <?= $isIndo ? 'true' : 'false'; ?>;

        function formatNominal(val) {
          var num = parseFloat(val) || 0;
          if (isIndoCurrency) {
            return currencySymbol + ' ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
          }
          return currencySymbol + ' ' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        Highcharts.chart('container', {
          chart: {
            height: 440,
            type: 'areaspline',
            backgroundColor: '#ffffff',
            plotBorderWidth: 0,
            spacingTop: 12,
            spacingBottom: 8
          },
          credits: {
            enabled: false
          },
          title: {
            text: 'Selling Report <?= ucfirst($thisM) . " " . $thisY; ?>',
            align: 'left',
            style: {
              color: '#111827',
              fontSize: '14px',
              fontWeight: '600'
            }
          },
          subtitle: {
            text: '<?= $totalreport; ?>',
            align: 'left',
            style: {
              color: '#6b7280',
              fontSize: '12px'
            }
          },
          xAxis: {
            tickInterval: 1,
            gridLineWidth: 0,
            lineColor: '#e5e7eb',
            tickColor: '#e5e7eb',
            labels: {
              style: {
                color: '#6b7280',
                fontSize: '11px'
              }
            }
          },
          yAxis: {
            gridLineColor: '#f3f4f6',
            gridLineDashStyle: 'Dash',
            title: {
              text: null
            },
            labels: {
              style: {
                color: '#6b7280',
                fontSize: '11px'
              },
              formatter: function() {
                return formatNominal(this.value);
              }
            }
          },
          legend: {
            layout: 'horizontal',
            align: 'center',
            verticalAlign: 'bottom',
            itemStyle: {
              color: '#374151',
              fontWeight: '500',
              fontSize: '12px'
            }
          },
          plotOptions: {
            areaspline: {
              color: '#111827',
              lineWidth: 2,
              fillColor: {
                linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                stops: [
                  [0, 'rgba(17, 24, 39, 0.08)'],
                  [1, 'rgba(17, 24, 39, 0.0)']
                ]
              },
              marker: {
                enabled: true,
                radius: 3.5,
                fillColor: '#111827',
                lineWidth: 1.5,
                lineColor: '#ffffff'
              }
            },
            series: {
              label: {
                connectorAllowed: false
              },
              pointStart: 1
            }
          },
          series: [{
            name: 'Report',
            data: [
              <?php
              for ($i = 1; $i < $totD; $i++) {
                if (strlen($i) == "1") {
                  $thisD = "0" . $i;
                } else {
                  $thisD = $i;
                }
                $idhr = strtolower($thisM . '/' . $thisD . '/' . $thisY);
                if (explode("/", resume_per_day($idhr))[1] == "") {
                  $r = 0;
                } else {
                  $r = explode("/", resume_per_day($idhr))[1];
                }
                echo "['<b>" . $thisD . " " . ucfirst($thisM) . " " . explode("/", resume_per_day($idhr))[0] . "vcr</b>'," . $r . "],";
              }
              ?>
            ]
          }],
          tooltip: {
            backgroundColor: '#ffffff',
            borderColor: '#e5e7eb',
            borderRadius: 8,
            shadow: {
              color: 'rgba(0, 0, 0, 0.06)',
              offsetX: 0,
              offsetY: 2,
              width: 8
            },
            style: {
              color: '#111827',
              fontSize: '12px'
            },
            formatter: function() {
              return this.point.name + '<br/>Total sales: <b>' + formatNominal(this.y) + '</b>';
            }
          },
          responsive: {
            rules: [{
              condition: {
                maxWidth: 500
              },
              chartOptions: {
                legend: {
                  layout: 'horizontal',
                  align: 'center',
                  verticalAlign: 'bottom'
                }
              }
            }]
          }
        });
      </script>
    </div>
  </div>
</div>