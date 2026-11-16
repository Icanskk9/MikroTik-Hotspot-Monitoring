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
  $countall = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));
  $getprofile = $API->comm("/ip/hotspot/user/profile/print");
  $TotalReg = count($getprofile);
  ?>

<style>
  #mhm-vouchers {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-vouchers .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-vouchers .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-vouchers .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-vouchers .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }
  #mhm-vouchers .lx-topbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  /* Body & Profile Grid */
  #mhm-vouchers .lx-body {
    padding: 22px 24px;
    background: #fafafa;
    max-height: 80vh;
    overflow-y: auto;
  }
  #mhm-vouchers .lx-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
  }
  @media (max-width: 1100px) {
    #mhm-vouchers .lx-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  @media (max-width: 680px) {
    #mhm-vouchers .lx-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  /* Profile Card */
  #mhm-vouchers .lx-prof-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
    transition: all 0.15s ease;
  }
  #mhm-vouchers .lx-prof-card:hover {
    border-color: #d4d4d8;
    box-shadow: 0 4px 14px -6px rgba(9, 9, 11, 0.08);
    transform: translateY(-1px);
  }
  #mhm-vouchers .lx-prof-card.is-all {
    background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
    border-color: #d4d4d8;
  }

  #mhm-vouchers .lx-prof-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  #mhm-vouchers .lx-prof-main {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    flex: 1;
  }
  #mhm-vouchers .lx-prof-icon {
    width: 42px;
    height: 42px;
    border-radius: 9px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    color: #18181b !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    text-decoration: none !important;
    transition: all 0.15s ease;
  }
  #mhm-vouchers .lx-prof-card.is-all .lx-prof-icon {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-vouchers .lx-prof-icon:hover {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-vouchers .lx-prof-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }
  #mhm-vouchers .lx-prof-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
  }
  #mhm-vouchers .lx-prof-name {
    font-size: 14.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Item Count Badge */
  #mhm-vouchers .lx-item-badge {
    font-size: 12px;
    font-weight: 600;
    color: #18181b;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 3px 9px;
    border-radius: 6px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
  }
  #mhm-vouchers .lx-prof-card.is-all .lx-item-badge {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }

  /* Optional Profile Metadata Pills */
  #mhm-vouchers .lx-prof-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  #mhm-vouchers .lx-meta-tag {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 500;
    color: #52525b;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    padding: 2px 7px;
    border-radius: 5px;
  }

  /* Card Actions */
  #mhm-vouchers .lx-prof-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f4f4f5;
  }
  #mhm-vouchers .lx-btn {
    height: 33px;
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
  #mhm-vouchers .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-vouchers .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-vouchers .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-vouchers .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-vouchers .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-vouchers .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }

  /* Footer */
  #mhm-vouchers .lx-footer {
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

<div id="mhm-vouchers">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title"><?= $_vouchers ?></h3>
      <span class="lx-count">
        <?= $TotalReg; ?> <?= ($TotalReg > 1) ? 'profiles' : 'profile'; ?>
      </span>
      <span class="lx-count">
        <?= (int)$countall; ?> <?= ($countall < 2) ? 'item' : 'items'; ?>
      </span>
    </div>

    <div class="lx-topbar-actions">
      <a class="lx-btn is-primary" href="./?hotspot-user=generate&session=<?= $session; ?>" title="Generate Voucher">
        <i class="fa fa-plus"></i>
        <span><?= $_generate ?></span>
      </a>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Reload data">
        <i class="fa fa-refresh"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Profile Cards Grid -->
  <div class="lx-body">
    <div class="lx-grid">
      <!-- All Profiles Summary Card -->
      <div class="lx-prof-card is-all">
        <div class="lx-prof-top">
          <div class="lx-prof-main">
            <a class="lx-prof-icon" title="Open User by profile all" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
              <i class="fa fa-ticket"></i>
            </a>
            <div class="lx-prof-info">
              <span class="lx-prof-label">Profile</span>
              <div class="lx-prof-name" title="all">all</div>
            </div>
          </div>

          <span class="lx-item-badge">
            <?= ($countall < 2) ? $countall . " Item" : $countall . " Items"; ?>
          </span>
        </div>

        <div class="lx-prof-meta">
          <span class="lx-meta-tag">Semua Paket Hotspot</span>
        </div>

        <div class="lx-prof-actions">
          <a class="lx-btn is-primary" title="Open User by profile all" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
            <i class="fa fa-external-link"></i>
            <span><?= $_open ?></span>
          </a>
          <a class="lx-btn" title="Generate User by profile all" href="./?hotspot-user=generate&session=<?= $session; ?>">
            <i class="fa fa-users"></i>
            <span><?= $_generate ?></span>
          </a>
        </div>
      </div>

      <!-- Individual Hotspot User Profiles -->
      <?php
      for ($i = 0; $i < $TotalReg; $i++) {
        $profiledetalis = $getprofile[$i];
        $pname = $profiledetalis['name'];
        $countuser = $API->comm("/ip/hotspot/user/print", array("count-only" => "", "?profile" => "$pname"));

        // Ekstrak info tambahan read-only dari konfigurasi profil jika tersedia
        $ponlogin = isset($profiledetalis['on-login']) ? $profiledetalis['on-login'] : "";
        $pvalid = ($ponlogin != "") ? explode(",", $ponlogin)[3] : "";
        $psprice = ($ponlogin != "") ? explode(",", $ponlogin)[4] : "";
        if ($psprice == "" || $psprice == "0") {
          $psprice = ($ponlogin != "") ? explode(",", $ponlogin)[2] : "";
        }
        $pratelimit = isset($profiledetalis['rate-limit']) ? $profiledetalis['rate-limit'] : "";
        ?>
        <div class="lx-prof-card">
          <div class="lx-prof-top">
            <div class="lx-prof-main">
              <a class="lx-prof-icon" title="Open User by profile <?= htmlspecialchars($pname, ENT_QUOTES); ?>" href="./?hotspot=users&profile=<?= $pname; ?>&session=<?= $session; ?>">
                <i class="fa fa-ticket"></i>
              </a>
              <div class="lx-prof-info">
                <span class="lx-prof-label">Profile</span>
                <div class="lx-prof-name" title="<?= htmlspecialchars($pname, ENT_QUOTES); ?>">
                  <?= htmlspecialchars($pname); ?>
                </div>
              </div>
            </div>

            <span class="lx-item-badge">
              <?= ($countuser < 2) ? $countuser . " Item" : $countuser . " Items"; ?>
            </span>
          </div>

          <div class="lx-prof-meta">
            <?php if ($pvalid != ""): ?>
              <span class="lx-meta-tag" title="Validity"><?= htmlspecialchars($pvalid); ?></span>
            <?php endif; ?>
            <?php if ($psprice != "" && $psprice != "0"): ?>
              <span class="lx-meta-tag" title="Price">
                <?php
                if ($currency == in_array($currency, $cekindo['indo'])) {
                  echo htmlspecialchars($currency . " " . number_format((float)$psprice, 0, ",", "."));
                } else {
                  echo htmlspecialchars($currency . " " . number_format((float)$psprice));
                }
                ?>
              </span>
            <?php endif; ?>
            <?php if ($pratelimit != ""): ?>
              <span class="lx-meta-tag" title="Rate Limit"><?= htmlspecialchars($pratelimit); ?></span>
            <?php endif; ?>
            <?php if ($pvalid == "" && ($psprice == "" || $psprice == "0") && $pratelimit == ""): ?>
              <span class="lx-meta-tag">Default MikroTik</span>
            <?php endif; ?>
          </div>

          <div class="lx-prof-actions">
            <a class="lx-btn is-primary" title="Open User by profile <?= htmlspecialchars($pname, ENT_QUOTES); ?>" href="./?hotspot=users&profile=<?= $pname; ?>&session=<?= $session; ?>">
              <i class="fa fa-external-link"></i>
              <span><?= $_open ?></span>
            </a>
            <a class="lx-btn" title="Generate User by profile <?= htmlspecialchars($pname, ENT_QUOTES); ?>" href="./?hotspot-user=generate&genprof=<?= $pname; ?>&session=<?= $session; ?>">
              <i class="fa fa-users"></i>
              <span><?= $_generate ?></span>
            </a>
          </div>
        </div>
        <?php
      }
      ?>
    </div>
  </div>

  <!-- Footer -->
  <div class="lx-footer">
    <div>Klik <strong style="color:#18181b;">Open</strong> untuk melihat daftar voucher berdasarkan profil</div>
    <div>Klik <strong style="color:#18181b;">Generate</strong> untuk membuat batch voucher baru</div>
  </div>
</div>
<?php
}
?>  