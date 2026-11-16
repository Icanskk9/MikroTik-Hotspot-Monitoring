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

  $getprofile = $API->comm("/ip/hotspot/user/profile/print");
  $srvlist = $API->comm("/ip/hotspot/print");

  if (isset($_POST['name'])) {
    $server = ($_POST['server']);
    $name = ($_POST['name']);
    $password = ($_POST['pass']);
    $profile = ($_POST['profile']);
    $disabled = ($_POST['disabled']);
    $timelimit = ($_POST['timelimit']);
    $datalimit = ($_POST['datalimit']);
    $comment = ($_POST['comment']);
    $chkvalid = ($_POST['valid']);
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
    
    $comment = $usermode . $comment;
    
    $API->comm("/ip/hotspot/user/add", array(
      "server" => "$server",
      "name" => "$name",
      "password" => "$password",
      "profile" => "$profile",
      "disabled" => "no",
      "limit-uptime" => "$timelimit",
      "limit-bytes-total" => "$datalimit",
      "comment" => "$comment",
    ));
    $getuser = $API->comm("/ip/hotspot/user/print", array(
      "?name" => "$name",
    ));
    $uid = $getuser[0]['.id'];
    echo "<script>window.location='./?hotspot-user=" . $uid . "&session=" . $session . "'</script>";
  }

  $closeUrl = ($_SESSION['ubp'] != "")
    ? "./?hotspot=users&profile=" . $_SESSION['ubp'] . "&session=" . $session
    : "./?hotspot=users&profile=all&session=" . $session;
}
?>
<script>
  function PassUser(){
    var x = document.getElementById('passUser');
    var icon = document.getElementById('passUser-icon');
    if (x.type === 'password') {
      x.type = 'text';
      if (icon) { icon.className = 'fa fa-eye-slash'; }
    } else {
      x.type = 'password';
      if (icon) { icon.className = 'fa fa-eye'; }
    }
  }
</script>

<style>
  #mhm-adduser-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 18px;
    align-items: start;
    color: #18181b;
  }
  @media (max-width: 992px) {
    #mhm-adduser-grid {
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
    gap: 15px;
    background: #ffffff;
  }
  .lx-card .lx-field {
    display: grid;
    grid-template-columns: 155px minmax(0, 1fr);
    align-items: center;
    gap: 14px;
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

  /* Addon Group (Password Eye & Data Limit MB/GB) */
  .lx-card .lx-addon-group {
    display: flex;
    align-items: stretch;
    width: 100%;
  }
  .lx-card .lx-addon-group .lx-input {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
    flex: 1;
  }
  .lx-card .lx-addon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 13px;
    min-width: 44px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    font-size: 12.5px;
    color: #52525b;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
  }
  .lx-card .lx-addon-btn:hover {
    background: #e4e4e7;
    color: #09090b;
  }
  .lx-card .lx-addon-select {
    width: 86px !important;
    height: 38px !important;
    padding: 0 10px !important;
    background-color: #f4f4f5 !important;
    border: 1px solid #e4e4e7 !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    color: #18181b !important;
    cursor: pointer;
    outline: none !important;
    margin: 0 !important;
    transition: all 0.15s ease;
  }
  .lx-card .lx-addon-select:hover {
    background-color: #e4e4e7 !important;
  }

  /* Dynamic Profile Price/Validity Strip */
  .lx-card #GetValidPrice {
    margin-left: 169px;
    padding: 9px 13px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: #18181b;
  }
  .lx-card #GetValidPrice:empty {
    display: none;
  }
  @media (max-width: 576px) {
    .lx-card #GetValidPrice {
      margin-left: 0;
    }
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
    padding: 12px 14px;
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
  .lx-readme-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #71717a;
    margin-bottom: 5px;
  }
  .lx-code-tag {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 600;
    color: #18181b;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    padding: 1px 6px;
    border-radius: 4px;
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

<div id="mhm-adduser-grid">
  <!-- Left Card: Add User Form -->
  <div class="lx-card">
    <form autocomplete="off" method="post" action="" style="margin:0;">
      <!-- Top Bar -->
      <div class="lx-topbar">
        <div class="lx-title-group">
          <h3 class="lx-title"><?= $_add_user ?></h3>
          <span class="lx-pill">Hotspot User</span>
          <small id="loader" style="display: none;">
            <i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?>
          </small>
        </div>

        <div class="lx-actions">
          <a class="lx-btn" href="<?= $closeUrl; ?>">
            <i class="fa fa-close"></i>
            <span><?= $_close ?></span>
          </a>
          <button type="submit" onclick="loader()" class="lx-btn is-primary" name="save">
            <i class="fa fa-save"></i>
            <span><?= $_save ?></span>
          </button>
        </div>
      </div>

      <!-- Form Fields -->
      <div class="lx-form-body">
        <div class="lx-field">
          <label class="lx-label">Server</label>
          <select class="form-control lx-select" name="server" required="1">
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
          <label class="lx-label"><?= $_name ?></label>
          <input
            class="form-control lx-input"
            type="text"
            autocomplete="off"
            name="name"
            placeholder="Masukkan username atau kode voucher"
            value=""
            required="1"
            autofocus
          >
        </div>

        <div class="lx-field">
          <label class="lx-label" for="passUser"><?= $_password ?></label>
          <div class="lx-addon-group">
            <input
              class="lx-input lx-mono"
              id="passUser"
              type="password"
              name="pass"
              autocomplete="new-password"
              placeholder="Samakan dengan Name untuk mode voucher"
              value=""
              required="1"
            >
            <span class="lx-addon-btn" onclick="PassUser()" title="Show/Hide Password">
              <i id="passUser-icon" class="fa fa-eye"></i>
            </span>
          </div>
        </div>

        <div class="lx-field">
          <label class="lx-label" for="uprof"><?= $_profile ?></label>
          <select class="form-control lx-select" onchange="GetVP();" id="uprof" name="profile" required="1">
            <?php
            $TotalReg = count($getprofile);
            for ($i = 0; $i < $TotalReg; $i++) {
              echo "<option>" . htmlspecialchars($getprofile[$i]['name']) . "</option>";
            }
            ?>
          </select>
        </div>

        <!-- Dynamic Profile Validity & Price Output -->
        <div id="GetValidPrice"></div>

        <div class="lx-field">
          <label class="lx-label"><?= $_time_limit ?></label>
          <input
            class="form-control lx-input lx-mono"
            type="text"
            autocomplete="off"
            name="timelimit"
            placeholder="Contoh: 1h, 12h, 1d, 30d"
            value=""
          >
        </div>

        <div class="lx-field">
          <label class="lx-label"><?= $_data_limit ?></label>
          <div class="lx-addon-group">
            <input
              class="lx-input lx-mono"
              type="number"
              min="0"
              max="9999"
              name="datalimit"
              placeholder="0 (Unlimited)"
              value="<?= $udatalimit; ?>"
            >
            <select class="lx-addon-select" name="mbgb" required="1">
              <option value="1048576">MB</option>
              <option value="1073741824">GB</option>
            </select>
          </div>
        </div>

        <div class="lx-field">
          <label class="lx-label" for="comment"><?= $_comment ?></label>
          <input
            class="form-control lx-input"
            type="text"
            title="No special characters"
            id="comment"
            autocomplete="off"
            name="comment"
            placeholder="Catatan atau nama batch (tanpa karakter khusus)"
            value=""
          >
        </div>

        <div class="lx-form-bottom">
          <a class="lx-btn" href="<?= $closeUrl; ?>">
            <i class="fa fa-close"></i>
            <span><?= $_close ?></span>
          </a>
          <button type="submit" onclick="loader()" class="lx-btn is-primary" name="save">
            <i class="fa fa-save"></i>
            <span><?= $_save ?></span>
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div class="lx-footer">
        <div>Prefix <strong style="color:#18181b;">vc-</strong> atau <strong style="color:#18181b;">up-</strong> ditambahkan otomatis pada comment</div>
        <div>MikroTik Hotspot User</div>
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
        <div class="lx-readme-label">Format Time Limit</div>
        <p><?= $_format_time_limit ?></p>
      </div>

      <div class="lx-readme-box">
        <div class="lx-readme-label">Ketentuan Batas Waktu</div>
        <p><?= $_details_add_user ?></p>
      </div>

      <div class="lx-readme-box">
        <div class="lx-readme-label">Mode Akun Otomatis</div>
        <p>
          Jika <span class="lx-code-tag">Name = Password</span>, sistem otomatis menandai comment dengan prefix <span class="lx-code-tag">vc-</span> (Voucher). Jika berbeda, ditandai dengan <span class="lx-code-tag">up-</span> (User/Pass).
        </p>
      </div>
    </div>
  </div>
</div>

<script>
// get valid $ price
function GetVP(){
  var prof = document.getElementById('uprof').value;
  $("#GetValidPrice").load("./process/getvalidprice.php?name="+prof+"&session=<?= $session; ?> #getdata");
}
</script>