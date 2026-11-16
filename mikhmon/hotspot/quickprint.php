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
  // get quick print
  $getquickprint = $API->comm("/system/script/print", array("?comment" => "QuickPrintMikhmon"));
  $TotalReg = count($getquickprint);
  ?>

<style>
  #mhm-quickprint {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-quickprint .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-quickprint .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-quickprint .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-quickprint .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-quickprint .lx-topbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Buttons */
  #mhm-quickprint .lx-btn {
    height: 33px;
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
  #mhm-quickprint .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-quickprint .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-quickprint .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-quickprint .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-quickprint .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-quickprint .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }

  /* Body & Package Grid */
  #mhm-quickprint .lx-body {
    padding: 22px 24px;
    background: #fafafa;
    max-height: 80vh;
    overflow-y: auto;
  }
  #mhm-quickprint .lx-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
  }
  @media (max-width: 1100px) {
    #mhm-quickprint .lx-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 680px) {
    #mhm-quickprint .lx-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  /* Quick Print Package Card */
  #mhm-quickprint .lx-qp-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
  }
  #mhm-quickprint .lx-qp-card:hover {
    border-color: #18181b;
    box-shadow: 0 6px 18px -8px rgba(9, 9, 11, 0.12);
    transform: translateY(-1px);
  }
  #mhm-quickprint .lx-qp-card:active {
    transform: translateY(0);
    background: #fafafa;
  }

  #mhm-quickprint .lx-qp-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  #mhm-quickprint .lx-qp-main {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    flex: 1;
  }
  #mhm-quickprint .lx-qp-icon {
    width: 42px;
    height: 42px;
    border-radius: 9px;
    background: #18181b;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    transition: background 0.15s ease;
  }
  #mhm-quickprint .lx-qp-card:hover .lx-qp-icon {
    background: #27272a;
  }
  #mhm-quickprint .lx-qp-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }
  #mhm-quickprint .lx-qp-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
  }
  #mhm-quickprint .lx-qp-name {
    font-size: 14.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  #mhm-quickprint .lx-print-pill {
    font-size: 11px;
    font-weight: 600;
    color: #18181b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.15s ease;
  }
  #mhm-quickprint .lx-qp-card:hover .lx-print-pill {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }

  /* Specs Grid inside Card */
  #mhm-quickprint .lx-qp-specs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    padding: 10px 12px;
    background: #fafafa;
    border: 1px solid #f4f4f5;
    border-radius: 8px;
  }
  #mhm-quickprint .lx-spec-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
  }
  #mhm-quickprint .lx-spec-key {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #71717a;
  }
  #mhm-quickprint .lx-spec-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
    color: #18181b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
  }

  /* Price Row */
  #mhm-quickprint .lx-qp-prices {
    padding-top: 11px;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 12px;
  }
  #mhm-quickprint .lx-price-pair {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  #mhm-quickprint .lx-price-label {
    color: #71717a;
    font-size: 11.5px;
  }
  #mhm-quickprint .lx-price-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
    color: #09090b;
    font-variant-numeric: tabular-nums;
  }

  /* Empty State */
  #mhm-quickprint .lx-empty-state {
    background: #ffffff;
    border: 1px dashed #d4d4d8;
    border-radius: 10px;
    padding: 44px 24px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
  }
  #mhm-quickprint .lx-empty-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #f4f4f5;
    color: #71717a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 2px;
  }
  #mhm-quickprint .lx-empty-title {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #09090b;
  }
  #mhm-quickprint .lx-empty-desc {
    margin: 0 0 6px;
    font-size: 12.5px;
    color: #71717a;
    max-width: 420px;
  }

  /* Footer */
  #mhm-quickprint .lx-footer {
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
</style>

<div id="mhm-quickprint">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title"><?= $_quick_print ?></h3>
      <span class="lx-count">
        <?= $TotalReg; ?> <?= ($TotalReg > 1) ? 'packages' : 'package'; ?>
      </span>
    </div>

    <div class="lx-topbar-actions">
      <button
        type="button"
        class="lx-btn is-primary"
        onclick="location.href='./?hotspot=list-quick-print&session=<?= $session ?>';"
        title="Quick Print List"
      >
        <i class="fa fa-list"></i>
        <span>List</span>
      </button>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Reload data">
        <i class="fa fa-refresh"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Body Content -->
  <div class="lx-body">
    <?php if ($TotalReg > 0) { ?>
      <div class="lx-grid">
        <?php
        for ($i = 0; $i < $TotalReg; $i++) {
          $quickprintdetails = $getquickprint[$i];
          $qpname = $quickprintdetails['name'];
          $qpid = $quickprintdetails['.id'];
          $quickprintsource = explode("#", $quickprintdetails['source']);
          $package = $quickprintsource[1];
          $server = $quickprintsource[2];
          $usermode = $quickprintsource[3];
          $userlength = $quickprintsource[4];
          $prefix = $quickprintsource[5];
          $char = $quickprintsource[6];
          $profile = $quickprintsource[7];
          $timelimit = $quickprintsource[8];
          $datalimit = $quickprintsource[9];
          $comment = $quickprintsource[10];
          $validity = $quickprintsource[11];
          $getprice = explode("_", $quickprintsource[12])[0];
          $getsprice = explode("_", $quickprintsource[12])[1];
          $userlock = $quickprintsource[13];

          if ($currency == in_array($currency, $cekindo['indo'])) {
            $price = $currency . " " . number_format((float)$getprice, 0, ",", ".");
            $sprice = $currency . " " . number_format((float)$getsprice, 0, ",", ".");
          } else {
            $price = $currency . " " . number_format((float)$getprice);
            $sprice = $currency . " " . number_format((float)$getsprice);
          }

          $dispTime = ($timelimit != "" && $timelimit != "0") ? htmlspecialchars($timelimit) : "-";
          $dispData = ($datalimit != "" && $datalimit != "0") ? formatBytes($datalimit, 2) : "-";
          $dispValid = ($validity != "") ? htmlspecialchars($validity) : "-";
          ?>
          <div
            id="./hotspot/quickuser.php?quickprint=<?= $qpname ?>&session=<?= $session; ?>"
            class="quick pointer lx-qp-card"
            title="<?= htmlspecialchars($_print . ' ' . $_package . ' ' . $package, ENT_QUOTES); ?>"
          >
            <div class="lx-qp-top">
              <div class="lx-qp-main">
                <span class="lx-qp-icon">
                  <i class="fa fa-print"></i>
                </span>
                <div class="lx-qp-info">
                  <span class="lx-qp-label"><?= $_package ?></span>
                  <div class="lx-qp-name" title="<?= htmlspecialchars($package, ENT_QUOTES); ?>">
                    <?= htmlspecialchars($package); ?>
                  </div>
                </div>
              </div>

              <span class="lx-print-pill">
                <i class="fa fa-bolt" style="font-size:10px;"></i>
                <span>Print</span>
              </span>
            </div>

            <div class="lx-qp-specs">
              <div class="lx-spec-item">
                <span class="lx-spec-key"><?= $_time_limit ?></span>
                <span class="lx-spec-val"><?= $dispTime; ?></span>
              </div>
              <div class="lx-spec-item">
                <span class="lx-spec-key"><?= $_data_limit ?></span>
                <span class="lx-spec-val"><?= $dispData; ?></span>
              </div>
              <div class="lx-spec-item">
                <span class="lx-spec-key"><?= $_validity ?></span>
                <span class="lx-spec-val"><?= $dispValid; ?></span>
              </div>
            </div>

            <div class="lx-qp-prices">
              <div class="lx-price-pair">
                <span class="lx-price-label"><?= $_price ?>:</span>
                <span class="lx-price-val"><?= htmlspecialchars($price); ?></span>
              </div>
              <div class="lx-price-pair">
                <span class="lx-price-label"><?= $_selling_price ?>:</span>
                <span class="lx-price-val"><?= htmlspecialchars($sprice); ?></span>
              </div>
            </div>
          </div>
          <?php
        }
        ?>
      </div>
    <?php } else { ?>
      <div class="lx-empty-state">
        <div class="lx-empty-icon">
          <i class="fa fa-print"></i>
        </div>
        <h4 class="lx-empty-title">Belum ada paket Quick Print</h4>
        <p class="lx-empty-desc">
          Tambahkan template paket voucher terlebih dahulu melalui menu List agar dapat dicetak sekali klik dari halaman ini.
        </p>
        <button
          type="button"
          class="lx-btn is-primary"
          onclick="location.href='./?hotspot=list-quick-print&session=<?= $session ?>';"
        >
          <i class="fa fa-plus"></i>
          <span>Buka List Quick Print</span>
        </button>
      </div>
    <?php } ?>
  </div>

  <!-- Footer -->
  <div class="lx-footer">
    <div>Klik kartu paket untuk generate &amp; cetak voucher secara instan</div>
    <div>Kelola template paket di menu <strong style="color:#18181b;">List</strong></div>
  </div>
</div>
<?php
}
?>

<script>
$(document).ready(function(){
  $(".quick").click(function(){

    loadpage(this.id);
    
  });

});
</script>