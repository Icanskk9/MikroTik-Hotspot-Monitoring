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

  // Handler AJAX hapus sesi router langsung dari sessions.php (tanpa perlu edit admin.php)
  if (isset($_POST['ajax_delete_session']) && $_POST['ajax_delete_session'] == "1") {
    while (ob_get_level() > 0) {
      ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $targetSess = isset($_POST['target_session']) ? trim($_POST['target_session']) : '';
    $adminPassInput = isset($_POST['admin_pass']) ? $_POST['admin_pass'] : '';

    if ($targetSess == "" || $targetSess == "mikhmon") {
      echo json_encode(array('status' => 'error', 'message' => 'Nama sesi router tidak valid.'));
      exit;
    }

    if ($adminPassInput === '' || $adminPassInput !== decrypt($passadm)) {
      echo json_encode(array('status' => 'error', 'message' => 'Password login salah!'));
      exit;
    }

    $configPath = "./include/config.php";
    if (!is_writable($configPath)) {
      echo json_encode(array('status' => 'error', 'message' => 'File include/config.php tidak memiliki izin tulis (permission denied).'));
      exit;
    }

    $fc = file($configPath);
    $f = fopen($configPath, "w");
    if (!$f) {
      echo json_encode(array('status' => 'error', 'message' => 'Gagal membuka file include/config.php untuk menulis.'));
      exit;
    }

    $q = "'";
    $rem = '$data[' . $q . $targetSess . $q . ']';
    foreach ($fc as $line) {
      if (!strstr($line, $rem)) {
        fputs($f, $line);
      }
    }
    fclose($f);

    $_SESSION['mhm_toast'] = 'Sesi router "' . $targetSess . '" berhasil dihapus.';
    echo json_encode(array('status' => 'success'));
    exit;
  }

  // array color
  $color = array('1' => 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey', 'bg-light-blue');

  if (isset($_POST['save'])) {

    $suseradm = ($_POST['useradm']);
    $spassadm = encrypt($_POST['passadm']);
    $logobt = ($_POST['logobt']);
    $qrbt = ($_POST['qrbt']);

    $cari = array('1' => "mikhmon<|<$useradm", "mikhmon>|>$passadm");
    $ganti = array('1' => "mikhmon<|<$suseradm", "mikhmon>|>$spassadm");

    for ($i = 1; $i < 3; $i++) {
      $file = file("./include/config.php");
      $content = file_get_contents("./include/config.php");
      $newcontent = str_replace((string)$cari[$i], (string)$ganti[$i], "$content");
      file_put_contents("./include/config.php", "$newcontent");
    }

    $gen = '<?php $qrbt="' . $qrbt . '";?>';
    $key = './include/quickbt.php';
    $handle = fopen($key, 'w') or die('Cannot open file:  ' . $key);
    $data = $gen;
    fwrite($handle, $data);

    $_SESSION['mhm_toast'] = 'Pengaturan akun Admin berhasil disimpan.';
    echo "<script>window.location='./admin.php?id=sessions'</script>";
  }

  // Ambil notifikasi toast jika ada
  $toastSuccess = "";
  $toastError = "";
  if (isset($_SESSION['mhm_toast']) && $_SESSION['mhm_toast'] != "") {
    $toastSuccess = $_SESSION['mhm_toast'];
    unset($_SESSION['mhm_toast']);
  }
  if (isset($_SESSION['mhm_toast_err']) && $_SESSION['mhm_toast_err'] != "") {
    $toastError = $_SESSION['mhm_toast_err'];
    unset($_SESSION['mhm_toast_err']);
  }

  // Kumpulkan daftar sesi router (read-only)
  $routerSessions = array();
  foreach (file('./include/config.php') as $line) {
    $value = explode("'", $line)[1];
    if ($value != "" && $value != "mikhmon") {
      $routerSessions[] = $value;
    }
  }
  $totalRouters = count($routerSessions);
}
?>
<script>
  function Pass(id){
    var x = document.getElementById(id);
    var icon = document.getElementById(id + '-icon');
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
  #mhm-sessions {
    color: #18181b;
  }

  /* Main Shell */
  #mhm-sessions .lx-shell {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
  }

  /* Top Bar */
  #mhm-sessions .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-sessions .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-sessions .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-sessions .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }

  /* Alert Banner */
  #mhm-sessions .lx-banner {
    margin: 18px 24px 0 24px;
    padding: 11px 15px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 12.5px;
    font-weight: 500;
    animation: lxFadeSlide 0.22s ease;
  }
  #mhm-sessions .lx-banner.is-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
  }
  #mhm-sessions .lx-banner.is-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
  }
  #mhm-sessions .lx-banner-left {
    display: flex;
    align-items: center;
    gap: 9px;
  }
  #mhm-sessions .lx-banner-close {
    background: transparent;
    border: none;
    color: inherit;
    opacity: 0.65;
    cursor: pointer;
    font-size: 14px;
    padding: 2px 4px;
    line-height: 1;
  }
  #mhm-sessions .lx-banner-close:hover {
    opacity: 1;
  }

  /* Body & Grid */
  #mhm-sessions .lx-body {
    padding: 22px 24px;
    background: #fafafa;
  }
  #mhm-sessions .lx-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
  }
  @media (max-width: 992px) {
    #mhm-sessions .lx-grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  /* Sub-cards */
  #mhm-sessions .lx-subcard {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow: hidden;
  }
  #mhm-sessions .lx-subcard-head {
    padding: 13px 20px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #ffffff;
  }
  #mhm-sessions .lx-subcard-title {
    margin: 0;
    font-size: 13.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #mhm-sessions .lx-subcard-title i {
    font-size: 12.5px;
    color: #71717a;
  }
  #mhm-sessions .lx-subcard-body {
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  /* Router List Item Card */
  #mhm-sessions .lx-router-card {
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    background: #ffffff;
    padding: 15px 16px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    transition: all 0.15s ease;
  }
  #mhm-sessions .lx-router-card:hover {
    border-color: #d4d4d8;
    background: #fafafa;
  }
  #mhm-sessions .lx-router-main {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    flex: 1;
  }
  #mhm-sessions .lx-router-icon {
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
  #mhm-sessions .lx-router-icon:hover {
    background: #27272a;
  }
  #mhm-sessions .lx-router-meta {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  #mhm-sessions .lx-router-name {
    font-size: 13.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  #mhm-sessions .lx-router-sub {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-sessions .lx-session-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 500;
    color: #3f3f46;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    padding: 2px 8px;
    border-radius: 6px;
  }
  #mhm-sessions .lx-router-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    width: 100%;
    padding-top: 11px;
    border-top: 1px solid #f4f4f5;
  }

  /* Form Rows */
  #mhm-sessions .lx-field {
    display: grid;
    grid-template-columns: 145px minmax(0, 1fr);
    align-items: center;
    gap: 12px;
  }
  @media (max-width: 576px) {
    #mhm-sessions .lx-field {
      grid-template-columns: minmax(0, 1fr);
      gap: 6px;
    }
  }
  #mhm-sessions .lx-label {
    font-size: 12.5px;
    font-weight: 500;
    color: #52525b;
    margin: 0;
  }

  /* Inputs & Selects */
  #mhm-sessions .lx-input,
  #mhm-sessions .lx-select {
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
  #mhm-sessions .lx-input:hover,
  #mhm-sessions .lx-select:hover {
    border-color: #d4d4d8 !important;
    background: #ffffff !important;
  }
  #mhm-sessions .lx-input:focus,
  #mhm-sessions .lx-select:focus {
    background: #ffffff !important;
    border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
    outline: none !important;
  }
  #mhm-sessions .lx-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12.5px !important;
  }

  /* Input Addon */
  #mhm-sessions .lx-addon-group {
    display: flex;
    align-items: stretch;
    width: 100%;
  }
  #mhm-sessions .lx-addon-group .lx-input {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
  }
  #mhm-sessions .lx-addon-suffix {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    min-width: 44px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    font-size: 12px;
    color: #52525b;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
  }
  #mhm-sessions .lx-addon-suffix:hover {
    background: #e4e4e7;
    color: #09090b;
  }

  /* Buttons */
  #mhm-sessions .lx-btn {
    height: 32px;
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
  #mhm-sessions .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-sessions .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-sessions .lx-btn i {
    font-size: 11.5px;
    color: #71717a;
  }
  #mhm-sessions .lx-btn.is-primary {
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-sessions .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-sessions .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }
  #mhm-sessions .lx-btn.is-danger:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-sessions .lx-btn.is-danger:hover i {
    color: #dc2626;
  }
  #mhm-sessions .lx-btn.is-danger-solid {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff !important;
    box-shadow: 0 1px 2px rgba(220, 38, 38, 0.2);
  }
  #mhm-sessions .lx-btn.is-danger-solid i {
    color: #ffffff !important;
  }
  #mhm-sessions .lx-btn.is-danger-solid:hover {
    background: #b91c1c;
    border-color: #b91c1c;
  }
  #mhm-sessions .lx-btn.is-danger-solid:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
  #mhm-sessions .lx-btn.is-icon {
    width: 34px;
    height: 34px;
    padding: 0;
  }

  /* Admin Form Footer & Version Box */
  #mhm-sessions .lx-form-actions {
    padding-top: 6px;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
  }
  #mhm-sessions .lx-version-box {
    margin-top: 4px;
    padding: 11px 14px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 12px;
    color: #52525b;
  }
  #mhm-sessions #loadV {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
    color: #18181b;
  }
  #mhm-sessions #newVer {
    font-size: 11.5px;
    font-weight: 500;
  }

  /* Bottom Shell Footer */
  #mhm-sessions .lx-footer {
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

  /* Custom Clean Delete Modal */
  #lxDeleteModal.lx-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(9, 9, 11, 0.38);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  #lxDeleteModal.lx-modal-backdrop.is-open {
    display: flex;
    animation: lxFadeIn 0.16s ease-out;
  }
  #lxDeleteModal .lx-modal-card {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 16px;
    box-shadow: 0 24px 48px -12px rgba(9, 9, 11, 0.18), 0 0 0 1px rgba(9, 9, 11, 0.03);
    overflow: hidden;
    animation: lxScaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #lxDeleteModal .lx-modal-body {
    padding: 24px 24px 20px 24px;
  }
  #lxDeleteModal .lx-modal-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  #lxDeleteModal .lx-modal-head-left {
    display: flex;
    align-items: flex-start;
    gap: 14px;
  }
  #lxDeleteModal .lx-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    background: #fef2f2;
    border: 1px solid #fee2e2;
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
  #lxDeleteModal .lx-modal-title {
    margin: 0 0 4px 0;
    font-size: 15.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.015em;
  }
  #lxDeleteModal .lx-modal-desc {
    margin: 0;
    font-size: 12.5px;
    line-height: 1.5;
    color: #71717a;
  }
  #lxDeleteModal .lx-modal-close {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    border: 1px solid transparent;
    background: transparent;
    color: #71717a;
    font-size: 16px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s ease;
  }
  #lxDeleteModal .lx-modal-close:hover {
    background: #f4f4f5;
    color: #09090b;
  }

  /* Target Session Card inside Modal */
  #lxDeleteModal .lx-modal-target {
    margin-top: 18px;
    padding: 11px 14px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  #lxDeleteModal .lx-modal-target-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
  }
  #lxDeleteModal .lx-modal-target-badge {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: #18181b;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11.5px;
    flex-shrink: 0;
  }
  #lxDeleteModal .lx-modal-target-name {
    font-size: 13px;
    font-weight: 600;
    color: #18181b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  #lxDeleteModal .lx-modal-target-name.is-muted {
    font-weight: 500;
    color: #71717a;
    font-size: 12.5px;
  }

  /* Unified Seamless Password Field */
  #lxDeleteModal .lx-modal-field {
    margin-top: 18px;
  }
  #lxDeleteModal .lx-modal-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #3f3f46;
    margin-bottom: 7px;
  }
  #lxDeleteModal .lx-modal-label i {
    color: #71717a;
    font-size: 11.5px;
  }
  #lxDeleteModal .lx-pass-box {
    display: flex;
    align-items: center;
    width: 100%;
    height: 40px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.15s ease;
  }
  #lxDeleteModal .lx-pass-box:hover {
    background: #ffffff;
    border-color: #d4d4d8;
  }
  #lxDeleteModal .lx-pass-box:focus-within {
    background: #ffffff;
    border-color: #18181b;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.07);
  }
  #lxDeleteModal .lx-pass-box input {
    flex: 1;
    width: 100% !important;
    height: 100% !important;
    padding: 0 13px !important;
    margin: 0 !important;
    border: none !important;
    outline: none !important;
    background: transparent !important;
    box-shadow: none !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12.5px !important;
    color: #09090b !important;
  }
  #lxDeleteModal .lx-pass-box input::placeholder {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    color: #a1a1aa !important;
    font-size: 12.5px !important;
  }
  #lxDeleteModal .lx-pass-toggle {
    width: 40px;
    height: 100%;
    border: none;
    border-left: 1px solid #f4f4f5;
    background: transparent;
    color: #71717a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s ease;
  }
  #lxDeleteModal .lx-pass-toggle:hover {
    background: #f4f4f5;
    color: #09090b;
  }

  /* Inline Error Pill */
  #lxDeleteModal .lx-modal-error {
    display: none;
    margin-top: 10px;
    padding: 9px 12px;
    border-radius: 9px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 500;
    align-items: center;
    gap: 8px;
  }
  #lxDeleteModal .lx-modal-error.is-visible {
    display: flex;
    animation: lxFadeSlide 0.16s ease;
  }

  /* Modal Footer */
  #lxDeleteModal .lx-modal-footer {
    padding: 14px 24px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
  }
  #lxDeleteModal .lx-modal-footer .lx-btn {
    height: 36px;
    padding: 0 16px;
    border-radius: 9px;
    font-size: 12.5px;
  }

  .lx-shake {
    animation: lxShake 0.32s ease;
  }

  @keyframes lxFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  @keyframes lxScaleUp {
    from { opacity: 0; transform: scale(0.96) translateY(6px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }
  @keyframes lxFadeSlide {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes lxShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-3px); }
  }
</style>

<div id="mhm-sessions">
  <div class="lx-shell">
    <!-- Top Bar -->
    <div class="lx-topbar">
      <div class="lx-title-group">
        <h3 class="lx-title"><?= $_admin_settings ?></h3>
        <span class="lx-count">
          <?= $totalRouters; ?> <?= ($totalRouters > 1) ? 'routers' : 'router'; ?>
        </span>
      </div>

      <button type="button" onclick="location.reload();" class="lx-btn" style="height:34px; padding:0 13px;" title="Reload data">
        <i class="fa fa-refresh"></i>
        <span>Refresh</span>
      </button>
    </div>

    <?php if ($toastSuccess != "") { ?>
      <div class="lx-banner is-success" id="lxAlertBanner">
        <div class="lx-banner-left">
          <i class="fa fa-check-circle"></i>
          <span><?= htmlspecialchars($toastSuccess); ?></span>
        </div>
        <button type="button" class="lx-banner-close" onclick="this.parentElement.style.display='none';">&times;</button>
      </div>
    <?php } ?>

    <?php if ($toastError != "") { ?>
      <div class="lx-banner is-error" id="lxAlertBanner">
        <div class="lx-banner-left">
          <i class="fa fa-exclamation-circle"></i>
          <span><?= htmlspecialchars($toastError); ?></span>
        </div>
        <button type="button" class="lx-banner-close" onclick="this.parentElement.style.display='none';">&times;</button>
      </div>
    <?php } ?>

    <!-- Main 2-Column Content -->
    <div class="lx-body">
      <div class="lx-grid">
        <!-- Left Column: Router List -->
        <div class="lx-subcard">
          <div class="lx-subcard-head">
            <h4 class="lx-subcard-title">
              <i class="fa fa-server"></i>
              <span><?= $_router_list ?></span>
            </h4>
            <span class="lx-count"><?= $totalRouters; ?> terdaftar</span>
          </div>

          <div class="lx-subcard-body">
            <?php
            foreach ($routerSessions as $value) {
              $hsName = explode('%', $data[$value][4])[1];
              $displayTitle = ($hsName != "") ? $hsName : $value;
            ?>
              <div class="lx-router-card">
                <div class="lx-router-main">
                  <span class="lx-router-icon connect pointer" id="<?= $value; ?>" title="Open <?= htmlspecialchars($displayTitle, ENT_QUOTES); ?>">
                    <i class="fa fa-server"></i>
                  </span>
                  <div class="lx-router-meta">
                    <div class="lx-router-name" title="<?= htmlspecialchars($displayTitle, ENT_QUOTES); ?>">
                      <?= htmlspecialchars($displayTitle); ?>
                    </div>
                    <div class="lx-router-sub">
                      <span><?= $_session_name ?>:</span>
                      <span class="lx-session-pill"><?= htmlspecialchars($value); ?></span>
                    </div>
                  </div>
                </div>

                <div class="lx-router-actions">
                  <span class="lx-btn is-primary connect pointer" id="<?= $value; ?>">
                    <i class="fa fa-external-link"></i>
                    <span><?= $_open ?></span>
                  </span>

                  <a class="lx-btn" href="./admin.php?id=settings&session=<?= $value; ?>">
                    <i class="fa fa-edit"></i>
                    <span><?= $_edit ?></span>
                  </a>

                  <button
                    type="button"
                    class="lx-btn is-danger js-delete-session"
                    data-session="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                    data-title="<?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8'); ?>"
                  >
                    <i class="fa fa-trash-o"></i>
                    <span><?= $_delete ?></span>
                  </button>
                </div>
              </div>
            <?php
            }

            if ($totalRouters == 0) {
            ?>
              <div style="padding:36px 16px; text-align:center; color:#a1a1aa; font-size:12.5px; border:1px dashed #e4e4e7; border-radius:9px;">
                Belum ada sesi router yang dikonfigurasi.
              </div>
            <?php
            }
            ?>
          </div>
        </div>

        <!-- Right Column: Admin Account Settings -->
        <form autocomplete="off" method="post" action="" style="margin:0;">
          <div class="lx-subcard">
            <div class="lx-subcard-head">
              <h4 class="lx-subcard-title">
                <i class="fa fa-user-circle"></i>
                <span><?= $_admin ?></span>
              </h4>
            </div>

            <div class="lx-subcard-body">
              <div class="lx-field">
                <label class="lx-label" for="useradm"><?= $_user_name ?></label>
                <input
                  class="form-control lx-input"
                  id="useradm"
                  type="text"
                  size="10"
                  name="useradm"
                  title="User Admin"
                  value="<?= htmlspecialchars($useradm); ?>"
                  required="1"
                />
              </div>

              <div class="lx-field">
                <label class="lx-label" for="passadm"><?= $_password ?></label>
                <div class="lx-addon-group">
                  <input
                    class="lx-input lx-mono"
                    id="passadm"
                    type="password"
                    size="10"
                    name="passadm"
                    title="Password Admin"
                    value="<?= htmlspecialchars(decrypt($passadm)); ?>"
                    required="1"
                  />
                  <span class="lx-addon-suffix" onclick="Pass('passadm')" title="Show/Hide Password">
                    <i id="passadm-icon" class="fa fa-eye"></i>
                  </span>
                </div>
              </div>

              <div class="lx-field">
                <label class="lx-label"><?= $_quick_print ?> QR</label>
                <select class="form-control lx-select" name="qrbt">
                  <option value="<?= htmlspecialchars($qrbt); ?>"><?= htmlspecialchars($qrbt); ?></option>
                  <option value="enable">enable</option>
                  <option value="disable">disable</option>
                </select>
              </div>

              <div class="lx-form-actions">
                <button type="button" class="lx-btn is-icon" onclick="location.reload();" title="Reload Data">
                  <i class="fa fa-refresh"></i>
                </button>
                <button type="submit" name="save" value="<?= $_save ?>" class="lx-btn is-primary" style="height:34px; padding:0 16px; cursor:pointer;">
                  <i class="fa fa-save"></i>
                  <span><?= $_save ?></span>
                </button>
              </div>

              <div class="lx-version-box">
                <div id="loadV">v<?= $_SESSION['v']; ?> </div>
                <div><b id="newVer" class="text-green"></b></div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Footer -->
    <div class="lx-footer">
      <div>Klik <strong style="color:#18181b;">Open</strong> untuk masuk ke dashboard manajemen router</div>
      <div>Kredensial admin tersimpan di <strong style="color:#18181b;">./include/config.php</strong></div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus Router dengan Verifikasi Password Login -->
  <div id="lxDeleteModal" class="lx-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="lxModalTitle">
    <div class="lx-modal-card">
      <div class="lx-modal-body">
        <div class="lx-modal-top">
          <div class="lx-modal-head-left">
            <div class="lx-modal-icon">
              <i class="fa fa-trash-o"></i>
            </div>
            <div>
              <h4 class="lx-modal-title" id="lxModalTitle">Hapus Sesi Router</h4>
              <p class="lx-modal-desc">Konfigurasi koneksi router ini akan dihapus permanen dari daftar.</p>
            </div>
          </div>
          <button type="button" class="lx-modal-close" id="lxModalCloseX" title="Tutup">&times;</button>
        </div>

        <div class="lx-modal-target">
          <div class="lx-modal-target-left">
            <span class="lx-modal-target-badge"><i class="fa fa-server"></i></span>
            <span class="lx-modal-target-name" id="lxModalRouterTitle">-</span>
          </div>
          <span class="lx-session-pill" id="lxModalSessionPill">-</span>
        </div>

        <div class="lx-modal-field" id="lxModalInputWrap">
          <label class="lx-modal-label" for="delAdminPass">
            <i class="fa fa-lock"></i>
            <span>Password Login</span>
          </label>
          <div class="lx-pass-box">
            <input
              type="password"
              id="delAdminPass"
              placeholder="Masukkan password login admin..."
              autocomplete="new-password"
            />
            <button type="button" class="lx-pass-toggle" onclick="Pass('delAdminPass')" title="Lihat / Sembunyikan Password">
              <i id="delAdminPass-icon" class="fa fa-eye"></i>
            </button>
          </div>

          <div class="lx-modal-error" id="lxModalError">
            <i class="fa fa-exclamation-circle"></i>
            <span id="lxModalErrorText">Password login salah!</span>
          </div>
        </div>
      </div>

      <div class="lx-modal-footer">
        <button type="button" class="lx-btn" id="lxModalCancel">
          Batal
        </button>
        <button type="button" class="lx-btn is-danger-solid" id="lxModalConfirm">
          <i class="fa fa-trash-o"></i>
          <span>Hapus Router</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    var targetSession = '';
    var isDeleting = false;

    var $modal = $('#lxDeleteModal');
    var $passInput = $('#delAdminPass');
    var $inputWrap = $('#lxModalInputWrap');
    var $errorBox = $('#lxModalError');
    var $errorText = $('#lxModalErrorText');
    var $confirmBtn = $('#lxModalConfirm');
    var $cancelBtn = $('#lxModalCancel');
    var $closeXBtn = $('#lxModalCloseX');

    function openDeleteModal(session, title) {
      targetSession = session;
      isDeleting = false;

      var $titleEl = $('#lxModalRouterTitle');
      if (!title || title === session) {
        $titleEl.text('Session Router').addClass('is-muted');
      } else {
        $titleEl.text(title).removeClass('is-muted');
      }
      $('#lxModalSessionPill').text(session);

      $passInput.val('').attr('type', 'password');
      $('#delAdminPass-icon').attr('class', 'fa fa-eye');
      $errorBox.removeClass('is-visible');
      $confirmBtn.prop('disabled', false).html('<i class="fa fa-trash-o"></i><span>Hapus Router</span>');

      $modal.addClass('is-open');
      setTimeout(function() {
        $passInput.focus();
      }, 90);
    }

    function closeDeleteModal() {
      if (isDeleting) return;
      $modal.removeClass('is-open');
      targetSession = '';
    }

    function showModalError(msg) {
      $errorText.text(msg);
      $errorBox.addClass('is-visible');
      $inputWrap.removeClass('lx-shake');
      void $inputWrap[0].offsetWidth;
      $inputWrap.addClass('lx-shake');
      $passInput.focus().select();
    }

    function submitDeleteSession() {
      if (isDeleting || !targetSession) return;
      var passVal = $passInput.val();

      if (!passVal) {
        showModalError('Silakan masukkan password login terlebih dahulu.');
        return;
      }

      isDeleting = true;
      $errorBox.removeClass('is-visible');
      $confirmBtn.prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin"></i><span>Memverifikasi...</span>');

      $.ajax({
        type: 'POST',
        url: './admin.php?id=sessions',
        data: {
          ajax_delete_session: '1',
          target_session: targetSession,
          admin_pass: passVal
        },
        dataType: 'text',
        success: function(raw) {
          var res = null;
          try {
            var clean = (raw || '').trim();
            var startIdx = clean.indexOf('{');
            var endIdx = clean.lastIndexOf('}');
            if (startIdx !== -1 && endIdx !== -1) {
              res = JSON.parse(clean.substring(startIdx, endIdx + 1));
            }
          } catch (e) {
            res = null;
          }

          if (res && res.status === 'success') {
            $confirmBtn.html('<i class="fa fa-check"></i><span>Terhapus</span>');
            window.location.href = './admin.php?id=sessions';
          } else if (res && res.message) {
            isDeleting = false;
            $confirmBtn.prop('disabled', false).html('<i class="fa fa-trash-o"></i><span>Hapus Router</span>');
            showModalError(res.message);
          } else {
            isDeleting = false;
            $confirmBtn.prop('disabled', false).html('<i class="fa fa-trash-o"></i><span>Hapus Router</span>');
            showModalError('Gagal memproses penghapusan. Coba muat ulang halaman.');
          }
        },
        error: function() {
          isDeleting = false;
          $confirmBtn.prop('disabled', false).html('<i class="fa fa-trash-o"></i><span>Hapus Router</span>');
          showModalError('Koneksi ke server terputus. Coba muat ulang halaman.');
        }
      });
    }

    $(document).on('click', '.js-delete-session', function() {
      var sess = $(this).data('session');
      var title = $(this).data('title');
      openDeleteModal(sess, title);
    });

    $cancelBtn.on('click', closeDeleteModal);
    $closeXBtn.on('click', closeDeleteModal);
    $confirmBtn.on('click', submitDeleteSession);

    $passInput.on('keydown', function(e) {
      if (e.key === 'Enter' || e.which === 13) {
        e.preventDefault();
        submitDeleteSession();
      }
    });

    $modal.on('click', function(e) {
      if (e.target === this) {
        closeDeleteModal();
      }
    });

    $(document).on('keydown', function(e) {
      if ((e.key === 'Escape' || e.which === 27) && $modal.hasClass('is-open')) {
        closeDeleteModal();
      }
    });

    // Auto-hide banner notifikasi setelah 4.5 detik
    setTimeout(function() {
      $('#lxAlertBanner').fadeOut(250);
    }, 4500);
  })();
</script>

<script>
  var _0x7470=["\x68\x6F\x73\x74\x6E\x61\x6D\x65","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x2E","\x73\x70\x6C\x69\x74","\x6D\x69\x6B\x68\x6D\x6F\x6E\x2E\x6F\x6E\x6C\x69\x6E\x65","\x78\x62\x61\x6E\x2E\x78\x79\x7A","\x6C\x6F\x67\x61\x6D\x2E\x69\x64","\x6D\x69\x6E\x69\x73\x2E\x69\x64","\x69\x6E\x64\x65\x78\x4F\x66","\x3C\x73\x70\x61\x6E\x20\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x77\x68\x69\x74\x65\x20\x66\x61\x20\x66\x61\x2D\x69\x6E\x66\x6F\x2D\x63\x69\x72\x63\x6C\x65\x22\x3E\x3C\x2F\x69\x3E\x20\x3C\x61\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x62\x6C\x75\x65\x22\x20\x68\x72\x65\x66\x3D\x22\x2E\x2F\x61\x64\x6D\x69\x6E\x2E\x70\x68\x70\x3F\x69\x64\x3D\x61\x62\x6F\x75\x74\x22\x3E\x43\x68\x65\x63\x6B\x20\x55\x70\x64\x61\x74\x65\x3C\x2F\x61\x3E\x3C\x2F\x73\x70\x61\x6E\x3E","\x68\x74\x6D\x6C","\x23\x6E\x65\x77\x56\x65\x72","\x68\x74\x74\x70\x73\x3A\x2F\x2F\x72\x61\x77\x2E\x67\x69\x74\x68\x75\x62\x75\x73\x65\x72\x63\x6F\x6E\x74\x65\x6E\x74\x2E\x63\x6F\x6D\x2F\x6C\x61\x6B\x73\x61\x31\x39\x2F\x6D\x69\x6B\x68\x6D\x6F\x6E\x76\x33\x2F\x6D\x61\x73\x74\x65\x72\x2F\x76\x65\x72\x73\x6F\x6E\x2E\x74\x78\x74\x3F\x74\x3D","\x72\x61\x6E\x64\x6F\x6D","\x66\x6C\x6F\x6F\x72","\x76","\x76\x65\x72\x73\x69\x6F\x6E","","\x72\x65\x70\x6C\x61\x63\x65","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x6C\x6F\x61\x64\x56","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x20","\x75\x70\x64\x61\x74\x65\x64","\x2D","\x4E\x65\x77\x20\x56\x65\x72\x73\x69\x6F\x6E\x20","\x3C\x62\x72\x3E\x3C\x73\x70\x61\x6E\x20\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x77\x68\x69\x74\x65\x20\x66\x61\x20\x66\x61\x2D\x69\x6E\x66\x6F\x2D\x63\x69\x72\x63\x6C\x65\x22\x3E\x3C\x2F\x69\x3E\x20\x3C\x61\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x62\x6C\x75\x65\x22\x20\x68\x72\x65\x66\x3D\x22\x2E\x2F\x61\x64\x6D\x69\x6E\x2E\x70\x68\x70\x3F\x69\x64\x3D\x61\x62\x6F\x75\x74\x22\x3E\x43\x68\x65\x63\x6B\x20\x55\x70\x64\x61\x74\x65\x3C\x2F\x61\x3E\x3C\x2F\x73\x70\x61\x6E\x3E","\x67\x65\x74\x4A\x53\x4F\x4E"];var hname=window[_0x7470[1]][_0x7470[0]];var dom=hname[_0x7470[3]](_0x7470[2])[1]+ _0x7470[2]+ hname[_0x7470[3]](_0x7470[2])[2];var domArray=[_0x7470[4],_0x7470[5],_0x7470[6],_0x7470[7]];var a=domArray[_0x7470[8]](hname);var b=domArray[_0x7470[8]](dom);if(dom== _0x7470[4]){$(_0x7470[11])[_0x7470[10]](_0x7470[9])}else {if(a> 0|| b> 0){}else {$[_0x7470[27]](_0x7470[12]+ (Math[_0x7470[14]]((Math[_0x7470[13]]()* 999999999)+ 1))* 128,function(_0xc1b4x6){getNewVer= (_0xc1b4x6[_0x7470[16]])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4x7=parseInt(getNewVer[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4x8=document[_0x7470[21]](_0x7470[20])[_0x7470[19]];var _0xc1b4x9=(_0xc1b4x8[_0x7470[3]](_0x7470[22])[0])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4xa=parseInt(_0xc1b4x9[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4xb=(_0xc1b4x7- _0xc1b4xa);getNewVer= (_0xc1b4x6[_0x7470[16]])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4x7=parseInt(getNewVer[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4x8=document[_0x7470[21]](_0x7470[20])[_0x7470[19]];var _0xc1b4x9=(_0xc1b4x8[_0x7470[3]](_0x7470[22])[0])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4xa=parseInt(_0xc1b4x9[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4xb=(_0xc1b4x7- _0xc1b4xa);getNewD= (_0xc1b4x6[_0x7470[23]])[_0x7470[3]](_0x7470[22])[0];newD= parseInt((getNewD)[_0x7470[3]](_0x7470[24])[2]+ (getNewD)[_0x7470[3]](_0x7470[24])[0]+ (getNewD)[_0x7470[3]](_0x7470[24])[1]);var _0xc1b4xc=parseInt((_0xc1b4x8[_0x7470[3]](_0x7470[22])[1])[_0x7470[3]](_0x7470[24])[2]+ (_0xc1b4x8[_0x7470[3]](_0x7470[22])[1])[_0x7470[3]](_0x7470[24])[0]+ (_0xc1b4x8[_0x7470[3]](_0x7470[22])[1][_0x7470[3]](_0x7470[24]))[1]);var _0xc1b4xd=(newD- _0xc1b4xc);if(_0xc1b4xb> 0|| _0xc1b4xd> 0){$(_0x7470[11])[_0x7470[10]](_0x7470[25]+ _0xc1b4x6[_0x7470[16]]+ _0x7470[22]+ _0xc1b4x6[_0x7470[23]]+ _0x7470[26])}})}}
</script>