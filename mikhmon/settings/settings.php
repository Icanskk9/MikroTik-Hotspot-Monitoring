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

  // --- HANDLER AJAX BACKUP & DOWNLOAD KONFIGURASI MIKROTIK ---
  if (isset($_POST['ajax_backup_router']) && $_POST['ajax_backup_router'] == "1") {
    while (ob_get_level() > 0) {
      ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $bIp   = isset($_POST['ipmik']) ? trim($_POST['ipmik']) : '';
    $bUser = isset($_POST['usermik']) ? trim($_POST['usermik']) : '';
    $bPass = isset($_POST['passmik']) ? $_POST['passmik'] : '';
    $bSess = isset($_POST['sessname']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['sessname']) : 'Router';

    if ($bIp == "" || $bUser == "") {
      echo json_encode(array('status' => 'error', 'message' => 'IP MikroTik dan Username wajib diisi terlebih dahulu.'));
      exit;
    }

    include_once('./lib/routeros_api.class.php');
    $API = new RouterosAPI();
    $API->debug = false;

    if ($API->connect($bIp, $bUser, $bPass)) {
      date_default_timezone_set('Asia/Jakarta');
      $timestamp = date('Ymd-His');
      $fileName  = "Mikhmon-" . $bSess . "-" . $timestamp;

      // 1. Simpan file binary .backup di internal storage MikroTik
      $API->comm("/system/backup/save", array(
        "name" => $fileName
      ));

      // 2. Simpan file script .rsc di internal storage MikroTik
      $API->comm("/export", array(
        "file" => $fileName
      ));

      // Tunggu sebentar agar MikroTik selesai menulis file .rsc ke disk
      usleep(700000);

      // 3. Ambil isi file .rsc via API untuk langsung didownload browser PC
      $rscFileName = $fileName . ".rsc";
      $fileData = $API->comm("/file/print", array(
        "?name" => $rscFileName
      ));

      $rscContent = "";
      if (is_array($fileData) && isset($fileData[0]['contents'])) {
        $rscContent = $fileData[0]['contents'];
      }

      // 4. Fallback via FTP jika ukuran .rsc melebihi batas baca API (~64KB) dan FTP aktif
      if ($rscContent === "" && function_exists('ftp_connect')) {
        $hostOnly = explode(':', $bIp)[0];
        $ftpConn = @ftp_connect($hostOnly, 21, 5);
        if ($ftpConn && @ftp_login($ftpConn, $bUser, $bPass)) {
          @ftp_pasv($ftpConn, true);
          $tmpStream = fopen('php://temp', 'r+');
          if (@ftp_fget($ftpConn, $tmpStream, $rscFileName, FTP_BINARY)) {
            rewind($tmpStream);
            $rscContent = stream_get_contents($tmpStream);
          }
          fclose($tmpStream);
          @ftp_close($ftpConn);
        }
      }

      $API->disconnect();

      echo json_encode(array(
        'status'      => 'success',
        'message'     => 'Backup berhasil dibuat & siap diunduh!',
        'files'       => array($fileName . '.backup', $rscFileName),
        'rsc_name'    => $rscFileName,
        'rsc_content' => $rscContent
      ));
      exit;
    } else {
      echo json_encode(array(
        'status'  => 'error',
        'message' => 'Gagal terhubung ke MikroTik (' . htmlspecialchars($bIp) . '). Periksa IP, Username, Password, atau port API.'
      ));
      exit;
    }
  }
  // --- SELESAI HANDLER AJAX BACKUP ---

  if ($id == "settings" && explode("-",$router)[0] == "new") {
    $data = '$data';
    $f = fopen('./include/config.php', 'a');
    fwrite($f, "\n'$'data['".$router."'] = array ('1'=>'".$router."!','".$router."@|@','".$router."#|#','".$router."%','".$router."^','".$router."&Rp','".$router."*10','".$router."(1','".$router.")','".$router."=10','".$router."@!@disable');");
    fclose($f);
    $search = "'$'data";
    $replace = (string)"$data";
    $file = file("./include/config.php");
    $content = file_get_contents("./include/config.php");
    $newcontent = str_replace((string)$search, (string)$replace, "$content");
    file_put_contents("./include/config.php", "$newcontent");
    echo "<script>window.location='./admin.php?id=settings&session=" . $router . "'</script>";
  }

  if (isset($_POST['save'])) {

    $siphost = (preg_replace('/\s+/', '', $_POST['ipmik']));
    $suserhost = ($_POST['usermik']);
    $spasswdhost = encrypt($_POST['passmik']);
    $shotspotname = str_replace("'","",$_POST['hotspotname']);
    $sdnsname = trim($_POST['dnsname']);
    $scurrency = ($_POST['currency']);
    $sreload = ($_POST['areload']);
    if ($sreload < 10) {
      $sreload = 10;
    } else {
      $sreload = $sreload;
    }
    $siface = ($_POST['iface']);
    $sinfolp = implode(unpack("H*", $_POST['infolp']));
    $sidleto = ($_POST['idleto']);

    $sesname = (preg_replace('/\s+/', '-', $_POST['sessname']));
    $slivereport = ($_POST['livereport']);

    $search = array('1' => "$session!$iphost", "$session@|@$userhost", "$session#|#$passwdhost", "$session%$hotspotname", "$session^$dnsname", "$session&$currency", "$session*$areload", "$session($iface", "$session)$infolp", "$session=$idleto", "'$session'", "$session@!@$livereport");

    $replace = array('1' => "$sesname!$siphost", "$sesname@|@$suserhost", "$sesname#|#$spasswdhost", "$sesname%$shotspotname", "$sesname^$sdnsname", "$sesname&$scurrency", "$sesname*$sreload", "$sesname($siface", "$sesname)$sinfolp", "$sesname=$sidleto", "'$sesname'", "$sesname@!@$slivereport");

    for ($i = 1; $i < 15; $i++) {
      $file = file("./include/config.php");
      $content = file_get_contents("./include/config.php");
      $newcontent = str_replace((string)$search[$i], (string)$replace[$i], "$content");
      file_put_contents("./include/config.php", "$newcontent");
    }

    // --- SINKRONISASI DNS NAME KE MIKROTIK VIA API ---
    if (!empty($sdnsname) && !empty($siphost) && !empty($suserhost)) {
      include_once('./lib/routeros_api.class.php');
      $API = new RouterosAPI();
      $API->debug = false;

      if ($API->connect($siphost, $suserhost, $_POST['passmik'])) {
        $hsProfiles = $API->comm("/ip/hotspot/profile/print");

        if (is_array($hsProfiles)) {
          foreach ($hsProfiles as $prof) {
            if (isset($prof['.id']) && (!isset($prof['name']) || $prof['name'] !== 'default')) {
              $API->comm("/ip/hotspot/profile/set", array(
                ".id" => $prof['.id'],
                "dns-name" => $sdnsname
              ));
            }
          }
        }
        $API->disconnect();
      }
    }
    // --- SELESAI SINKRONISASI DNS NAME ---

    $_SESSION["connect"] = "";
    echo "<script>window.location='./admin.php?id=settings&session=" . $sesname . "'</script>";
  }
  if ($currency == "") {
    echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
  }
}
?>
<script>
  function PassMk(){
    var x = document.getElementById('passmk');
    var icon = document.getElementById('passmk-icon');
    if (x.type === 'password') {
      x.type = 'text';
      if (icon) { icon.className = 'fa fa-eye-slash'; }
    } else {
      x.type = 'password';
      if (icon) { icon.className = 'fa fa-eye'; }
    }
  }
  function PassAdm(){
    var x = document.getElementById('passadm');
    if (x.type === 'password') {
      x.type = 'text';
    } else {
      x.type = 'password';
    }
  }
</script>

<style>
  #mhm-settings {
    color: #18181b;
  }

  /* Main Shell */
  #mhm-settings .lx-shell {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }

  /* Top Bar */
  #mhm-settings .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-settings .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-settings .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-settings .lx-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
    font-weight: 500;
    color: #52525b;
    background: #f4f4f5;
    padding: 2px 9px;
    border-radius: 6px;
  }

  /* Grid Body */
  #mhm-settings .lx-body {
    padding: 22px 24px;
    background: #fafafa;
  }
  #mhm-settings .lx-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
  }
  @media (max-width: 992px) {
    #mhm-settings .lx-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
  #mhm-settings .lx-col-stack {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  /* Sub-cards */
  #mhm-settings .lx-subcard {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow: hidden;
  }
  #mhm-settings .lx-subcard-head {
    padding: 13px 20px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #ffffff;
  }
  #mhm-settings .lx-subcard-title {
    margin: 0;
    font-size: 13.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #mhm-settings .lx-subcard-title i {
    font-size: 12.5px;
    color: #71717a;
  }
  #mhm-settings .lx-subcard-body {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 13px;
  }

  /* Form Rows */
  #mhm-settings .lx-field {
    display: grid;
    grid-template-columns: 155px minmax(0, 1fr);
    align-items: center;
    gap: 12px;
  }
  @media (max-width: 576px) {
    #mhm-settings .lx-field {
      grid-template-columns: minmax(0, 1fr);
      gap: 6px;
    }
  }
  #mhm-settings .lx-label {
    font-size: 12.5px;
    font-weight: 500;
    color: #52525b;
    margin: 0;
  }

  /* Inputs & Selects */
  #mhm-settings .lx-input,
  #mhm-settings .lx-select {
    width: 100% !important;
    height: 36px !important;
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
  #mhm-settings .lx-input:hover,
  #mhm-settings .lx-select:hover {
    border-color: #d4d4d8 !important;
    background: #ffffff !important;
  }
  #mhm-settings .lx-input:focus,
  #mhm-settings .lx-select:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  #mhm-settings .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12.5px !important;
  }

  /* Input with Suffix Addon */
  #mhm-settings .lx-addon-group {
    display: flex;
    align-items: stretch;
    width: 100%;
  }
  #mhm-settings .lx-addon-group .lx-input,
  #mhm-settings .lx-addon-group .lx-select {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
  }
  #mhm-settings .lx-addon-suffix {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    min-width: 48px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: #52525b;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
  }
  #mhm-settings .lx-addon-suffix:hover {
    background: #e4e4e7;
    color: #09090b;
  }

  /* Buttons */
  #mhm-settings .lx-btn-bar {
    padding-top: 6px;
    border-top: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
  }
  #mhm-settings .lx-btn {
    height: 34px;
    padding: 0 14px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #18181b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    white-space: nowrap;
  }
  #mhm-settings .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b;
  }
  #mhm-settings .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-settings .lx-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  #mhm-settings .lx-btn i {
    font-size: 12px;
    color: #71717a;
  }
  #mhm-settings .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff;
  }
  #mhm-settings .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-settings .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }
  #mhm-settings .lx-btn.is-icon {
    width: 34px;
    padding: 0;
  }

  /* Backup Result Alert Card */
  #mhm-settings .lx-backup-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 12.5px;
    display: none;
  }
  #mhm-settings .lx-backup-card.is-success {
    display: block;
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
  }
  #mhm-settings .lx-backup-card.is-error {
    display: block;
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
  }
  #mhm-settings .lx-backup-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    margin-bottom: 6px;
  }
  #mhm-settings .lx-backup-files {
    margin: 6px 0 0 0;
    padding-left: 18px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px;
  }

  /* Ping Result Box Override */
  #mhm-settings #ping .card {
    background: #ffffff !important;
    border: 1px solid #e4e4e7 !important;
    border-radius: 10px !important;
    box-shadow: none !important;
    margin: 0 !important;
  }
  #mhm-settings #ping .card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #f4f4f5 !important;
    padding: 12px 18px !important;
  }
  #mhm-settings #ping .card-body {
    padding: 16px 18px !important;
  }

  /* Footer */
  #mhm-settings .lx-footer {
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

<form autocomplete="off" method="post" action="" name="settings" id="mhm-settings">
  <div class="lx-shell">
    <!-- Top Bar -->
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title"><?= $_session_settings ?></h3>
        <?php if (explode("-", $session)[0] != "new" && $session != ""): ?>
          <span class="lx-pill"><?= htmlspecialchars($session); ?></span>
        <?php endif; ?>
      </div>

      <div style="display:flex; align-items:center; gap:8px;">
        <button type="button" onclick="location.reload();" class="lx-btn" title="Reload data">
          <i class="fa fa-refresh"></i>
          <span>Refresh</span>
        </button>
        <button type="submit" name="save" value="Save" class="lx-btn is-primary">
          <i class="fa fa-save"></i>
          <span>Save</span>
        </button>
      </div>
    </div>

    <!-- Main 2-Column Content -->
    <div class="lx-body">
      <div class="lx-grid">
        <!-- Left Column: Session & MikroTik -->
        <div class="lx-col-stack">
          <!-- Session Card -->
          <div class="lx-subcard">
            <div class="lx-subcard-head">
              <h4 class="lx-subcard-title">
                <i class="fa fa-tag"></i>
                <span><?= $_session ?></span>
              </h4>
            </div>
            <div class="lx-subcard-body">
              <div class="lx-field">
                <label class="lx-label" for="sessname"><?= $_session_name ?></label>
                <input
                  class="form-control lx-input lx-mono"
                  id="sessname"
                  type="text"
                  name="sessname"
                  title="Session Name"
                  placeholder="Contoh: SMKN1-HOTSPOT"
                  value="<?php if (explode("-",$session)[0] == "new") {
                    echo "";
                  } else {
                    echo htmlspecialchars($session);
                  } ?>"
                  required="1"
                />
              </div>
            </div>
          </div>

          <!-- MikroTik Connection Card -->
          <div class="lx-subcard">
            <div class="lx-subcard-head">
              <h4 class="lx-subcard-title">
                <i class="fa fa-server"></i>
                <span>MikroTik <?= $_SESSION["connect"]; ?></span>
              </h4>
            </div>
            <div class="lx-subcard-body">
              <div class="lx-field">
                <label class="lx-label">IP MikroTik</label>
                <input
                  class="form-control lx-input lx-mono"
                  id="ipmik"
                  type="text"
                  size="15"
                  name="ipmik"
                  title="IP MikroTik / IP Cloud MikroTik"
                  placeholder="192.168.88.1"
                  value="<?= htmlspecialchars($iphost); ?>"
                  required="1"
                />
              </div>

              <div class="lx-field">
                <label class="lx-label" for="usermk">Username</label>
                <input
                  class="form-control lx-input"
                  id="usermk"
                  type="text"
                  size="10"
                  name="usermik"
                  title="User MikroTik"
                  value="<?= htmlspecialchars($userhost); ?>"
                  required="1"
                />
              </div>

              <div class="lx-field">
                <label class="lx-label" for="passmk">Password</label>
                <div class="lx-addon-group">
                  <input
                    class="lx-input lx-mono"
                    id="passmk"
                    type="password"
                    name="passmik"
                    title="Password MikroTik"
                    value="<?= htmlspecialchars(decrypt($passwdhost)); ?>"
                    required="1"
                  />
                  <span class="lx-addon-suffix" onclick="PassMk()" title="Show/Hide Password">
                    <i id="passmk-icon" class="fa fa-eye"></i>
                  </span>
                </div>
              </div>

              <div class="lx-btn-bar">
                <button type="submit" name="save" value="Save" class="lx-btn is-primary" style="cursor:pointer;">
                  <i class="fa fa-save"></i>
                  <span>Save</span>
                </button>
                <span class="connect pointer lx-btn" id="<?= $session; ?>&c=settings">
                  <i class="fa fa-plug"></i>
                  <span>Connect</span>
                </span>
                <span class="pointer lx-btn" id="ping_test">
                  <i class="fa fa-exchange"></i>
                  <span>Ping</span>
                </span>
                <button type="button" class="lx-btn" id="btn_backup_router" title="Backup ke router dan langsung download file .rsc ke PC">
                  <i class="fa fa-download"></i>
                  <span>Backup Router</span>
                </button>
                <button type="button" class="lx-btn is-icon" onclick="location.reload();" title="Reload Data">
                  <i class="fa fa-refresh"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Backup Output Container -->
          <div id="backup_result" class="lx-backup-card"></div>

          <!-- Ping Output Container -->
          <div id="ping"></div>
        </div>

        <!-- Right Column: Mikhmon Data -->
        <div class="lx-col-stack">
          <div class="lx-subcard">
            <div class="lx-subcard-head">
              <h4 class="lx-subcard-title">
                <i class="fa fa-sliders"></i>
                <span>MikroTik Hotspot Monitoring Data</span>
              </h4>
            </div>
            <div class="lx-subcard-body">
              <div class="lx-field">
                <label class="lx-label"><?= $_hotspot_name ?></label>
                <input
                  class="form-control lx-input"
                  type="text"
                  size="15"
                  maxlength="50"
                  name="hotspotname"
                  title="Hotspot Name"
                  value="<?= htmlspecialchars($hotspotname); ?>"
                  required="1"
                />
              </div>

              <div class="lx-field">
                <label class="lx-label"><?= $_dns_name ?></label>
                <input
                  class="form-control lx-input lx-mono"
                  type="text"
                  size="15"
                  maxlength="500"
                  name="dnsname"
                  title="DNS Name [IP->Hotspot->Server Profiles->DNS Name]"
                  value="<?= htmlspecialchars($dnsname); ?>"
                  required="1"
                />
              </div>

              <div class="lx-field">
                <label class="lx-label"><?= $_currency ?></label>
                <input
                  class="form-control lx-input"
                  type="text"
                  size="3"
                  maxlength="4"
                  name="currency"
                  title="currency"
                  value="<?= htmlspecialchars($currency); ?>"
                  required="1"
                />
              </div>

              <div class="lx-field">
                <label class="lx-label"><?= $_auto_reload ?></label>
                <div class="lx-addon-group">
                  <input
                    class="lx-input lx-mono"
                    type="number"
                    min="10"
                    max="3600"
                    name="areload"
                    title="Auto Reload in sec [min 10]"
                    value="<?= htmlspecialchars($areload); ?>"
                    required="1"
                  />
                  <span class="lx-addon-suffix" style="cursor:default;"><?= $_sec ?></span>
                </div>
              </div>

              <div class="lx-field">
                <label class="lx-label"><?= $_idle_timeout ?></label>
                <div class="lx-addon-group">
                  <select class="lx-select" name="idleto" required="1">
                    <option value="<?= htmlspecialchars($idleto); ?>"><?= htmlspecialchars($idleto); ?></option>
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="disable">disable</option>
                  </select>
                  <span class="lx-addon-suffix" style="cursor:default;"><?= $_min ?></span>
                </div>
              </div>

              <div class="lx-field">
                <label class="lx-label"><?= $_traffic_interface ?></label>
                <input
                  class="form-control lx-input lx-mono"
                  type="number"
                  min="1"
                  max="99"
                  name="iface"
                  title="Traffic Interface"
                  value="<?= htmlspecialchars($iface); ?>"
                  required="1"
                />
              </div>

              <?php if (empty($livereport)) {
              } else { ?>
              <div class="lx-field">
                <label class="lx-label"><?= $_live_report ?></label>
                <select class="form-control lx-select" name="livereport">
                  <option value="<?= htmlspecialchars($livereport); ?>"><?= ucfirst($livereport); ?></option>
                  <option value="enable">Enable</option>
                  <option value="disable">Disable</option>
                </select>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="lx-footer">
      <div>Klik <strong style="color:#18181b;">Save</strong> sebelum menekan <strong style="color:#18181b;">Connect</strong> jika mengubah kredensial router</div>
      <div>Konfigurasi tersimpan di <strong style="color:#18181b;">./include/config.php</strong></div>
    </div>
  </div>
</form>

<script type="text/javascript">
// Helper download file teks (.rsc) langsung ke PC lewat browser
function triggerRscDownload(filename, content) {
  var blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
  var url = window.URL.createObjectURL(blob);
  var a = document.createElement('a');
  a.style.display = 'none';
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  setTimeout(function() {
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
  }, 150);
}

// AJAX Handler untuk Tombol Backup Router & Auto Download ke PC
(function() {
  var btnBackup = document.getElementById('btn_backup_router');
  var boxResult = document.getElementById('backup_result');
  var lastRscName = '';
  var lastRscContent = '';

  if (btnBackup) {
    btnBackup.addEventListener('click', function() {
      var ipVal   = document.getElementById('ipmik').value;
      var userVal = document.getElementById('usermk').value;
      var passVal = document.getElementById('passmk').value;
      var sessVal = document.getElementById('sessname').value;

      btnBackup.disabled = true;
      btnBackup.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Membackup...</span>';
      boxResult.className = 'lx-backup-card';
      boxResult.innerHTML = '';

      $.ajax({
        type: 'POST',
        url: window.location.href,
        data: {
          ajax_backup_router: '1',
          ipmik: ipVal,
          usermik: userVal,
          passmik: passVal,
          sessname: sessVal
        },
        dataType: 'text',
        success: function(raw) {
          btnBackup.disabled = false;
          btnBackup.innerHTML = '<i class="fa fa-download"></i><span>Backup Router</span>';

          var res = null;
          try {
            var clean = (raw || '').trim();
            var sIdx = clean.indexOf('{');
            var eIdx = clean.lastIndexOf('}');
            if (sIdx !== -1 && eIdx !== -1) {
              res = JSON.parse(clean.substring(sIdx, eIdx + 1));
            }
          } catch (e) {
            res = null;
          }

          if (res && res.status === 'success') {
            var filesHtml = '';
            if (res.files && res.files.length > 0) {
              filesHtml = '<ul class="lx-backup-files">';
              for (var i = 0; i < res.files.length; i++) {
                filesHtml += '<li>' + res.files[i] + '</li>';
              }
              filesHtml += '</ul>';
            }

            var downloadActionHtml = '';
            if (res.rsc_content && res.rsc_content.length > 0) {
              lastRscName = res.rsc_name || 'backup.rsc';
              lastRscContent = res.rsc_content;

              // Langsung trigger download otomatis ke browser PC!
              triggerRscDownload(lastRscName, lastRscContent);

              downloadActionHtml =
                '<div style="margin-top:10px;padding-top:8px;border-top:1px solid #bbf7d0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">' +
                  '<span><i class="fa fa-check"></i> File <strong>' + lastRscName + '</strong> otomatis diunduh ke PC.</span>' +
                  '<button type="button" id="btn_redownload_rsc" class="lx-btn" style="height:28px;padding:0 10px;font-size:11.5px;">' +
                    '<i class="fa fa-download"></i><span>Unduh Ulang .rsc</span>' +
                  '</button>' +
                '</div>';
            } else {
              downloadActionHtml =
                '<div style="margin-top:8px;font-size:11.5px;opacity:0.85;">' +
                  '<em>File tersimpan di MikroTik. (Auto-download ke PC membutuhkan ukuran skrip &lt;64KB atau layanan FTP aktif di IP &gt; Services).</em>' +
                '</div>';
            }

            boxResult.className = 'lx-backup-card is-success';
            boxResult.innerHTML =
              '<div class="lx-backup-head">' +
                '<span><i class="fa fa-check-circle"></i> ' + res.message + '</span>' +
                '<span style="cursor:pointer;opacity:0.7;" onclick="this.parentElement.parentElement.style.display=\'none\'">&times;</span>' +
              '</div>' +
              '<div>Tersimpan di menu <strong>Files</strong> Winbox MikroTik:</div>' +
              filesHtml +
              downloadActionHtml;

            var btnRedownload = document.getElementById('btn_redownload_rsc');
            if (btnRedownload) {
              btnRedownload.addEventListener('click', function() {
                triggerRscDownload(lastRscName, lastRscContent);
              });
            }
          } else {
            var errMsg = (res && res.message) ? res.message : 'Gagal menjalankan backup ke router.';
            boxResult.className = 'lx-backup-card is-error';
            boxResult.innerHTML =
              '<div class="lx-backup-head">' +
                '<span><i class="fa fa-exclamation-circle"></i> ' + errMsg + '</span>' +
                '<span style="cursor:pointer;opacity:0.7;" onclick="this.parentElement.parentElement.style.display=\'none\'">&times;</span>' +
              '</div>';
          }
        },
        error: function() {
          btnBackup.disabled = false;
          btnBackup.innerHTML = '<i class="fa fa-download"></i><span>Backup Router</span>';
          boxResult.className = 'lx-backup-card is-error';
          boxResult.innerHTML =
            '<div class="lx-backup-head">' +
              '<span><i class="fa fa-exclamation-circle"></i> Koneksi ke server Mikhmon terputus.</span>' +
              '<span style="cursor:pointer;opacity:0.7;" onclick="this.parentElement.parentElement.style.display=\'none\'">&times;</span>' +
            '</div>';
        }
      });
    });
  }
})();

var _0x1d39=["\x68\x6F\x73\x74\x6E\x61\x6D\x65","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x2E","\x73\x70\x6C\x69\x74","","\x78\x62\x61\x6E\x2E\x78\x79\x7A","\x6C\x6F\x67\x61\x6D\x2E\x69\x64","\x6D\x69\x6E\x69\x73\x2E\x69\x64","\x69\x6E\x64\x65\x78\x4F\x66","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x70\x69\x6E\x67","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x3C\x64\x69\x76\x20\x69\x64\x3D\x22\x70\x69\x6E\x67\x58\x22\x20\x63\x6C\x61\x73\x73\x3D\x22\x63\x6F\x6C\x2D\x31\x32\x22\x3E\x3C\x64\x69\x76\x20\x63\x6C\x61\x73\x73\x3D\x22\x63\x61\x72\x64\x22\x3E\x3C\x64\x69\x76\x20\x63\x6C\x61\x73\x73\x3D\x22\x63\x61\x72\x64\x2D\x68\x65\x61\x64\x65\x72\x22\x3E\x3C\x68\x33\x20\x63\x6C\x61\x73\x73\x3D\x22\x63\x61\x72\x64\x2D\x74\x69\x74\x6C\x65\x22\x3E\x50\x69\x6E\x67\x20\x54\x65\x73\x74\x20\x3C\x2F\x68\x33\x3E\x09\x3C\x2F\x64\x69\x76\x3E\x09\x3C\x64\x69\x76\x20\x63\x6C\x61\x73\x73\x3D\x22\x63\x61\x72\x64\x2D\x62\x6F\x64\x79\x22\x3E\x3C\x68\x33\x3E\x46\x69\x74\x75\x72\x20\x74\x69\x64\x61\x6B\x20\x73\x75\x70\x70\x6F\x72\x74\x2E\x3C\x2F\x68\x33\x3E\x3C\x73\x70\x61\x6E\x20\x63\x6C\x61\x73\x73\x3D\x22\x70\x6F\x69\x6E\x74\x65\x72\x20\x62\x74\x6E\x22\x20\x6F\x6E\x63\x6C\x69\x63\x6B\x3D\x22\x63\x6C\x6F\x73\x65\x58\x28\x29\x22\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x66\x61\x20\x66\x61\x2D\x63\x6C\x6F\x73\x65\x20\x74\x65\x78\x74\x2D\x72\x65\x64\x20\x22\x3E\x3C\x2F\x69\x3E\x20\x43\x6C\x6F\x73\x65\x3C\x2F\x73\x70\x61\x6E\x3E\x3C\x2F\x64\x69\x76\x3E\x3C\x2F\x64\x69\x76\x3E\x3C\x2F\x64\x69\x76\x3E","\x6F\x6E\x63\x6C\x69\x63\x6B","\x70\x69\x6E\x67\x5F\x74\x65\x73\x74","\x2E\x2F\x73\x74\x61\x74\x75\x73\x2F\x70\x69\x6E\x67\x2D\x74\x65\x73\x74\x2E\x70\x68\x70\x3F\x70\x69\x6E\x67\x26\x73\x65\x73\x73\x69\x6F\x6E\x3D","\x6C\x6F\x61\x64","\x23\x70\x69\x6E\x67","\x76\x61\x6C\x75\x65","\x73\x65\x73\x73\x6E\x61\x6D\x65","\x68\x69\x64\x65","\x23\x70\x69\x6E\x67\x58"];var _0x8202=["\x62\x72\x61\x6E\x64","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x4D\x49\x4B\x48\x4D\x4F\x4E","\x64\x69\x73\x70\x6C\x61\x79","\x73\x74\x79\x6C\x65","\x6E\x6F\x6E\x65","\x62\x6F\x64\x79","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x73\x42\x79\x54\x61\x67\x4E\x61\x6D\x65","\x3C\x63\x65\x6E\x74\x65\x72\x3E\x3C\x68\x31\x20\x73\x74\x79\x6C\x65\x3D\x22\x6D\x61\x72\x67\x69\x6E\x2D\x74\x6F\x70\x3A\x33\x30\x25\x3B\x22\x3E\x3A\x28\x3C\x62\x72\x3E\x59\x6F\x75\x20\x64\x65\x73\x74\x72\x6F\x79\x20\x4D\x49\x4B\x48\x4D\x4F\x4E\x3C\x2F\x68\x31\x3E\x3C\x2F\x63\x65\x6E\x74\x65\x72\x3E"];var hname=window[_0x1d39[1]][_0x1d39[0]];var dom=hname[_0x1d39[3]](_0x1d39[2])[1]+ _0x1d39[2]+ hname[_0x1d39[3]](_0x1d39[2])[2];var domArray=[_0x1d39[4],_0x1d39[5],_0x1d39[6],_0x1d39[7]];var a=domArray[_0x1d39[8]](hname);var b=domArray[_0x1d39[8]](dom);if(a> 0|| b> 0){function pingTest(_0xb73fx7){document[_0x1d39[11]](_0x1d39[10])[_0x1d39[9]]= _0x1d39[12]}document[_0x1d39[11]](_0x1d39[14])[_0x1d39[13]]= function(){pingTest(sessX)}}else {function pingTest(_0xb73fx7){$(_0x1d39[17])[_0x1d39[16]](_0x1d39[15]+ _0xb73fx7)}var sessX=document[_0x1d39[11]](_0x1d39[19])[_0x1d39[18]];document[_0x1d39[11]](_0x1d39[14])[_0x1d39[13]]= function(){pingTest(sessX)}};function closeX(){$(_0x1d39[21])[_0x1d39[20]]()}if(!(document[_0x8202[1]](_0x8202[0]))|| document[_0x8202[1]](_0x8202[0])[_0x8202[2]]!= _0x8202[3] || document[_0x8202[1]](_0x8202[0])[_0x8202[5]][_0x8202[4]]== _0x8202[6]){document[_0x8202[8]](_0x8202[7])[0][_0x8202[2]]= (_0x8202[9])}else {document[_0x8202[1]](_0x8202[0])[_0x8202[2]]= _0x8202[3]} var _0xdf1e=["\x73\x65\x73\x73\x6E\x61\x6D\x65","\x73\x65\x74\x74\x69\x6E\x67\x73","\x76\x61\x6C\x75\x65","\x6D\x69\x6B\x68\x6D\x6F\x6E","\x4D\x49\x4B\x48\x4D\x4F\x4E","\x4D\x69\x6B\x68\x6D\x6F\x6E","\x59\x6F\x75\x20\x63\x61\x6E\x6E\x6F\x74\x20\x75\x73\x65\x20","\x20\x61\x73\x20\x61\x20\x73\x65\x73\x73\x69\x6F\x6E\x20\x6E\x61\x6D\x65\x2E","","\x72\x65\x6C\x6F\x61\x64","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x6F\x6E\x6B\x65\x79\x75\x70","\x6F\x6E\x63\x68\x61\x6E\x67\x65"];var sesname=document[_0xdf1e[1]][_0xdf1e[0]];function chksname(){if(sesname[_0xdf1e[2]]== _0xdf1e[3]|| sesname[_0xdf1e[2]]== _0xdf1e[4]|| sesname[_0xdf1e[2]]== _0xdf1e[5]){message= _0xdf1e[6]+ sesname[_0xdf1e[2]]+ _0xdf1e[7];alert(message);sesname[_0xdf1e[2]]= _0xdf1e[8];window[_0xdf1e[10]][_0xdf1e[9]]()}}sesname[_0xdf1e[11]]= chksname;sesname[_0xdf1e[12]]= chksname
</script>