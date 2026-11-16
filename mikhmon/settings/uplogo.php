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

  if (isset($_POST["submit"])) {
    $logo_dir = "./img/";
    $logo_file = $logo_dir . basename($_FILES["UploadLogo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($logo_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["UploadLogo"]["tmp_name"]);
    if ($check !== false) {
      if ($currency == in_array($currency, $cekindo['indo'])) {
        $galat = '<div class="box bg-danger"></i> Alert!<br> File name is : ' . basename($_FILES["UploadLogo"]["name"]) . '. </div>';
      } else {
        $galat = '<div class="box bg-danger"></i> Alert!<br> File name is : ' . basename($_FILES["UploadLogo"]["name"]) . '. </div>';
      }
      $uploadOk = 1;
    } else {
      if ($currency == in_array($currency, $cekindo['indo'])) {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  File bukan gambar. </div>';
      } else {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  File is not an image. </div>';
      }
      $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["UploadLogo"]["size"] > 2000000) {
      if ($currency == in_array($currency, $cekindo['indo'])) {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  Ukuran file terlalu besar. </div>';
      } else {
        $galat = '<div class="box bg-danger"></i> Alert!<br> File is too large. </div>';
      }
      $uploadOk = 0;
    }

    // Allow certain file formats
    if (basename($_FILES["UploadLogo"]["name"] != "logo-" . $session . ".png")) {
      if ($currency == in_array($currency, $cekindo['indo'])) {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  Hanya bisa upload logo-' . $session . '.png. </div>';
      } else {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  Only logo-' . $session . '.png are allowed. </div>';
      }
      $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
      if ($currency == in_array($currency, $cekindo['indo'])) {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  File tidak diupload. Nama file harus logo-' . $session . '.png</div>';
      } else {
        $galat = '<div class="box bg-danger"></i> Alert!<br>  File was not uploaded. File name must be  logo-' . $session . '.png</div>';
      }
    } else {
      // if everything is ok, try to upload file
      if (move_uploaded_file($_FILES["UploadLogo"]["tmp_name"], $logo_file)) {
        if ($currency == in_array($currency, $cekindo['indo'])) {
          $galat = '<div class="box bg-success"></i> Alert!<br>  Success!</h5> File ' . basename($_FILES["UploadLogo"]["name"]) . ' telah diupload. </div>';
        } else {
          $galat = '<div class="box bg-success"></i> Alert!<br>  Success!</h5> The File ' . basename($_FILES["UploadLogo"]["name"]) . ' has been uploaded. </div>';
        }
      } else {
        if ($currency == in_array($currency, $cekindo['indo'])) {
          $galat = '<div class="box bg-danger"></i> Alert!<br>  Terjadi masalah ketika upload file. Nama file harus logo-' . $session . '.png </div>';
        } else {
          $galat = '<div class="box bg-danger"></i> Alert!<br>  There was an error uploading your file. File name must be  logo-' . $session . '.png </div>';
        }
      }
    }
  }

  // Kumpulkan daftar file logo di folder ./img
  $dir = "./img";
  $logoFiles = array();
  if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
      while (($file = readdir($dh)) !== false) {
        if ($file != "." && $file != "..") {
          if (
            substr($file, 0, 5) != "logo-" ||
            substr($file, -5) == ".html" ||
            substr($file, -4) == ".php" ||
            substr($file, -4) == ".jpg" ||
            substr($file, -4) == ".bak"
          ) {
            // skip non-logo files
          } else {
            $logoFiles[] = $file;
          }
        }
      }
      closedir($dh);
    }
  }
  sort($logoFiles);
  $totalLogos = count($logoFiles);
  $expectedLogoName = "logo-" . $session . ".png";
}
?>

<style>
  #mhm-uplogo {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }

  /* Top Bar */
  #mhm-uplogo .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
  }
  #mhm-uplogo .lx-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-uplogo .lx-topbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #mhm-uplogo .lx-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.02em;
  }
  #mhm-uplogo .lx-pill {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
  }

  /* Buttons */
  #mhm-uplogo .lx-btn {
    height: 35px;
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
  #mhm-uplogo .lx-btn:hover {
    background: #fafafa;
    border-color: #d4d4d8;
    color: #09090b !important;
  }
  #mhm-uplogo .lx-btn:active {
    background: #f4f4f5;
  }
  #mhm-uplogo .lx-btn i {
    font-size: 12px;
    color: #71717a;
  }
  #mhm-uplogo .lx-btn.is-primary {
    height: 38px;
    padding: 0 18px;
    background: #18181b;
    border-color: #18181b;
    color: #ffffff !important;
  }
  #mhm-uplogo .lx-btn.is-primary i {
    color: #d4d4d8;
  }
  #mhm-uplogo .lx-btn.is-primary:hover {
    background: #27272a;
    border-color: #27272a;
  }
  #mhm-uplogo .lx-btn.is-danger-outline {
    height: 31px;
    padding: 0 11px;
    font-size: 12px;
    background: #ffffff;
    border-color: #fecaca;
    color: #dc2626 !important;
  }
  #mhm-uplogo .lx-btn.is-danger-outline i {
    color: #dc2626;
  }
  #mhm-uplogo .lx-btn.is-danger-outline:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #b91c1c !important;
  }
  #mhm-uplogo .lx-btn.is-sm {
    height: 31px;
    padding: 0 11px;
    font-size: 12px;
  }

  /* Alert Styling for $galat */
  #mhm-uplogo .lx-alert-wrap:empty {
    display: none;
  }
  #mhm-uplogo .lx-alert-wrap {
    padding: 16px 24px 0;
    background: #fafafa;
  }
  #mhm-uplogo .lx-alert-wrap .box {
    margin: 0 !important;
    padding: 12px 16px !important;
    border-radius: 9px !important;
    font-size: 12.5px !important;
    line-height: 1.55 !important;
    box-shadow: none !important;
  }
  #mhm-uplogo .lx-alert-wrap .box.bg-danger {
    background: #fef2f2 !important;
    border: 1px solid #fecaca !important;
    color: #b91c1c !important;
  }
  #mhm-uplogo .lx-alert-wrap .box.bg-success {
    background: #ecfdf5 !important;
    border: 1px solid #a7f3d0 !important;
    color: #047857 !important;
  }

  /* Upload Section */
  #mhm-uplogo .lx-upload-zone {
    padding: 20px 24px;
    background: #fafafa;
    border-bottom: 1px solid #e4e4e7;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  #mhm-uplogo .lx-format-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 12.5px;
    color: #52525b;
  }
  #mhm-uplogo .lx-code-badge {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
    color: #09090b;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    padding: 2px 8px;
    border-radius: 6px;
  }
  #mhm-uplogo .lx-upload-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  #mhm-uplogo .lx-file-box {
    flex: 1;
    min-width: 240px;
    height: 38px;
    padding: 0 12px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: border-color 0.15s ease;
  }
  #mhm-uplogo .lx-file-box:hover {
    border-color: #d4d4d8;
  }
  #mhm-uplogo .lx-file-input {
    width: 100%;
    font-size: 12.5px;
    color: #3f3f46;
    cursor: pointer;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  #mhm-uplogo .lx-file-input::file-selector-button {
    height: 26px;
    padding: 0 10px;
    margin-right: 10px;
    background: #f4f4f5;
    border: 1px solid #e4e4e7;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #18181b;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  #mhm-uplogo .lx-file-input::file-selector-button:hover {
    background: #e4e4e7;
  }
  #mhm-uplogo .lx-file-hint {
    font-size: 12px;
    color: #71717a;
  }
  #mhm-uplogo .lx-file-hint.is-match {
    color: #047857;
    font-weight: 500;
  }
  #mhm-uplogo .lx-file-hint.is-mismatch {
    color: #b45309;
    font-weight: 500;
  }

  /* Table */
  #mhm-uplogo .lx-table-wrap {
    width: 100%;
    overflow-x: auto;
  }
  #mhm-uplogo .lx-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    background: #ffffff !important;
    text-align: left;
  }
  #mhm-uplogo .lx-table thead th {
    background: #ffffff !important;
    color: #71717a !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    padding: 12px 24px !important;
    border-top: none !important;
    border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-uplogo .lx-table tbody td {
    padding: 14px 24px !important;
    border-top: none !important;
    border-bottom: 1px solid #f4f4f5 !important;
    font-size: 13px !important;
    color: #27272a !important;
    vertical-align: middle !important;
  }
  #mhm-uplogo .lx-table tbody tr:last-child td {
    border-bottom: none !important;
  }
  #mhm-uplogo .lx-table tbody tr:hover td {
    background-color: #fafafa !important;
  }

  /* Logo Item Cell */
  #mhm-uplogo .lx-logo-item {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  #mhm-uplogo .lx-logo-thumb {
    width: 48px;
    height: 48px;
    border-radius: 9px;
    border: 1px solid #e4e4e7;
    background-color: #f8fafc;
    background-image:
      linear-gradient(45deg, #e4e4e7 25%, transparent 25%),
      linear-gradient(-45deg, #e4e4e7 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, #e4e4e7 75%),
      linear-gradient(-45deg, transparent 75%, #e4e4e7 75%);
    background-size: 10px 10px;
    background-position: 0 0, 0 5px, 5px -5px, -5px 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px;
    flex-shrink: 0;
    overflow: hidden;
    transition: border-color 0.15s ease;
  }
  #mhm-uplogo .lx-logo-thumb:hover {
    border-color: #18181b;
  }
  #mhm-uplogo .lx-logo-thumb img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
  }
  #mhm-uplogo .lx-logo-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
  }
  #mhm-uplogo .lx-logo-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  #mhm-uplogo .lx-logo-name {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
    font-weight: 600;
    color: #09090b;
    text-decoration: none;
  }
  #mhm-uplogo .lx-logo-name:hover {
    text-decoration: underline;
  }
  #mhm-uplogo .lx-active-badge {
    font-size: 10.5px;
    font-weight: 600;
    color: #047857;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 1px 7px;
    border-radius: 5px;
  }
  #mhm-uplogo .lx-logo-sub {
    font-size: 11.5px;
    color: #71717a;
    font-variant-numeric: tabular-nums;
  }

  /* Row Actions */
  #mhm-uplogo .lx-row-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 7px;
  }

  /* Empty State */
  #mhm-uplogo .lx-empty-cell {
    padding: 44px 24px !important;
    text-align: center !important;
    color: #71717a !important;
  }

  /* Footer */
  #mhm-uplogo .lx-footer {
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

  /* Help Modal (Clean & Editorial) */
  #uplogo-help-modal {
    position: fixed;
    inset: 0;
    background: rgba(9, 9, 11, 0.45);
    backdrop-filter: blur(2px);
    z-index: 99998;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  #uplogo-help-modal.is-open {
    display: flex;
  }
  #uplogo-help-modal .uh-card {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    width: 100%;
    max-width: 430px;
    box-shadow: 0 20px 38px -15px rgba(9, 9, 11, 0.22);
    color: #18181b;
    overflow: hidden;
  }
  #uplogo-help-modal .uh-head {
    padding: 16px 20px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  #uplogo-help-modal .uh-title {
    margin: 0;
    font-size: 14.5px;
    font-weight: 600;
    color: #09090b;
    letter-spacing: -0.01em;
  }
  #uplogo-help-modal .uh-close {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid transparent;
    background: transparent;
    color: #71717a;
    font-size: 16px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  #uplogo-help-modal .uh-close:hover {
    background: #f4f4f5;
    color: #09090b;
  }
  #uplogo-help-modal .uh-body {
    padding: 18px 20px;
    font-size: 13px;
    line-height: 1.6;
    color: #3f3f46;
  }
  #uplogo-help-modal .uh-body p {
    margin: 0 0 12px 0;
  }
  #uplogo-help-modal .uh-copy-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #fafafa;
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    padding: 8px 10px 8px 12px;
    margin-bottom: 14px;
  }
  #uplogo-help-modal .uh-copy-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 600;
    color: #09090b;
    word-break: break-all;
  }
  #uplogo-help-modal .uh-copy-btn {
    height: 28px;
    padding: 0 10px;
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: #18181b;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.15s ease;
  }
  #uplogo-help-modal .uh-copy-btn:hover {
    background: #f4f4f5;
    border-color: #d4d4d8;
  }
  #uplogo-help-modal .uh-notes {
    margin: 0;
    padding-left: 18px;
    font-size: 12px;
    color: #71717a;
    line-height: 1.65;
  }
  #uplogo-help-modal .uh-foot {
    padding: 12px 20px;
    background: #fafafa;
    border-top: 1px solid #f4f4f5;
    display: flex;
    justify-content: flex-end;
  }
  #uplogo-help-modal .uh-done-btn {
    height: 33px;
    padding: 0 16px;
    background: #18181b;
    border: 1px solid #18181b;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 500;
    color: #ffffff;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  #uplogo-help-modal .uh-done-btn:hover {
    background: #27272a;
  }
</style>

<!-- Modal Help Upload Logo -->
<div id="uplogo-help-modal" onclick="if(event.target===this) closeUplogoHelp();">
  <div class="uh-card" role="dialog" aria-modal="true" aria-labelledby="uhModalTitle">
    <div class="uh-head">
      <h4 class="uh-title" id="uhModalTitle">Panduan Format Upload Logo</h4>
      <button type="button" class="uh-close" onclick="closeUplogoHelp();" aria-label="Tutup">&times;</button>
    </div>

    <div class="uh-body">
      <p>
        Sebelum mengunggah gambar, pastikan Anda sudah mengubah nama (<strong>rename</strong>) file logo di perangkat Anda agar sesuai persis dengan format <strong>Format file name</strong> di atas kolom <strong>Choose File</strong>:
      </p>

      <div class="uh-copy-box">
        <span class="uh-copy-code"><?= htmlspecialchars($expectedLogoName); ?></span>
        <button type="button" class="uh-copy-btn" id="btnCopyLogoName" onclick="copyExpectedLogoName();">
          <i class="fa fa-copy"></i>
          <span>Salin</span>
        </button>
      </div>

      <ul class="uh-notes">
        <li>Ekstensi file wajib menggunakan format <strong>.png</strong>.</li>
        <li>Ukuran file maksimal <strong>2 MB</strong>.</li>
        <li>Penulisan huruf besar dan kecil harus sama persis agar logo otomatis terbaca pada template voucher.</li>
      </ul>
    </div>

    <div class="uh-foot">
      <button type="button" class="uh-done-btn" onclick="closeUplogoHelp();">Mengerti</button>
    </div>
  </div>
</div>

<div id="mhm-uplogo">
  <!-- Top Bar -->
  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title"><?= $_upload_logo ?></h3>
      <span class="lx-pill"><?= $totalLogos; ?> <?= ($totalLogos > 1) ? 'files' : 'file'; ?></span>
      <span class="lx-pill">PNG · Maks. 2 MB</span>
    </div>

    <div class="lx-topbar-actions">
      <button type="button" onclick="openUplogoHelp();" class="lx-btn is-sm" title="Panduan format nama file logo">
        <i class="fa fa-question-circle"></i>
        <span>Help</span>
      </button>

      <button type="button" onclick="location.reload();" class="lx-btn is-sm" title="Reload data">
        <i class="fa fa-refresh"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Server Upload Alert (if any) -->
  <div class="lx-alert-wrap"><?= $galat; ?></div>

  <!-- Upload Form Zone -->
  <div class="lx-upload-zone">
    <div class="lx-format-bar">
      <div>
        <span><?= $_format_file_name ?> :</span>
        <span class="lx-code-badge"><?= htmlspecialchars($expectedLogoName); ?></span>
      </div>
      <span id="lx-file-status" class="lx-file-hint">Pilih file gambar PNG sesuai nama sesi aktif</span>
    </div>

    <form action="" method="post" enctype="multipart/form-data" style="margin:0;">
      <div class="lx-upload-row">
        <div class="lx-file-box">
          <input
            id="lx-logo-input"
            class="lx-file-input"
            type="file"
            name="UploadLogo"
            accept=".png,image/png"
            onchange="checkLogoFileName(this)"
          >
        </div>

        <button type="submit" name="submit" value="<?= $_upload ?>" class="lx-btn is-primary" title="Upload logo">
          <i class="fa fa-upload"></i>
          <span><?= $_upload ?></span>
        </button>
      </div>
    </form>
  </div>

  <!-- Logo List Table -->
  <div class="lx-table-wrap">
    <table class="lx-table">
      <thead>
        <tr>
          <th><?= $_list_logo ?></th>
          <th style="text-align:right;"><?= $_action ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($totalLogos > 0) {
          foreach ($logoFiles as $file) {
            $filePath = "./img/" . $file;
            $fileSizeKb = file_exists($filePath) ? round(filesize($filePath) / 1024, 1) . " KB" : "PNG";
            $isCurrentSessionLogo = ($file === $expectedLogoName);
            ?>
            <tr>
              <td>
                <div class="lx-logo-item">
                  <a
                    class="lx-logo-thumb"
                    href="javascript:window.open('./img/<?= htmlspecialchars($file, ENT_QUOTES); ?>','_blank','width=300,height=300')"
                    title="Open <?= htmlspecialchars($file, ENT_QUOTES); ?>"
                  >
                    <img src="./img/<?= htmlspecialchars($file, ENT_QUOTES); ?>" alt="<?= htmlspecialchars($file, ENT_QUOTES); ?>">
                  </a>

                  <div class="lx-logo-meta">
                    <div class="lx-logo-name-row">
                      <a
                        class="lx-logo-name"
                        href="javascript:window.open('./img/<?= htmlspecialchars($file, ENT_QUOTES); ?>','_blank','width=300,height=300')"
                        title="Open <?= htmlspecialchars($file, ENT_QUOTES); ?>"
                      >
                        <?= htmlspecialchars($file); ?>
                      </a>
                      <?php if ($isCurrentSessionLogo): ?>
                        <span class="lx-active-badge">Sesi Aktif</span>
                      <?php endif; ?>
                    </div>
                    <span class="lx-logo-sub">./img/<?= htmlspecialchars($file); ?> &middot; <?= $fileSizeKb; ?></span>
                  </div>
                </div>
              </td>

              <td style="text-align:right;">
                <div class="lx-row-actions">
                  <a
                    class="lx-btn is-sm"
                    href="javascript:window.open('./img/<?= htmlspecialchars($file, ENT_QUOTES); ?>','_blank','width=300,height=300')"
                    title="Preview <?= htmlspecialchars($file, ENT_QUOTES); ?>"
                  >
                    <i class="fa fa-external-link"></i>
                    <span>Preview</span>
                  </a>

                  <a
                    class="lx-btn is-danger-outline"
                    href="javascript:void(0)"
                    onclick="if(confirm('Sure to delete <?= htmlspecialchars($file, ENT_QUOTES); ?> ?')){window.location='./admin.php?id=remove-logo&logo=<?= htmlspecialchars($file, ENT_QUOTES); ?>&session=<?= $session ?>'}else{}"
                  >
                    <i class="fa fa-trash"></i>
                    <span><?= $_delete ?></span>
                  </a>
                </div>
              </td>
            </tr>
            <?php
          }
        } else {
          ?>
          <tr>
            <td colspan="2" class="lx-empty-cell">
              Belum ada file logo tersimpan di direktori <strong style="color:#18181b;">./img/</strong>.
            </td>
          </tr>
          <?php
        }
        ?>
      </tbody>
    </table>
  </div>

  <!-- Footer -->
  <div class="lx-footer">
    <div>Logo dengan format <strong style="color:#18181b;"><?= htmlspecialchars($expectedLogoName); ?></strong> otomatis tampil pada template cetak voucher</div>
    <div>Klik gambar atau nama file untuk membuka pratinjau ukuran penuh</div>
  </div>
</div>

<script>
function checkLogoFileName(input) {
  var statusEl = document.getElementById('lx-file-status');
  var expected = <?= json_encode($expectedLogoName); ?>;
  if (!statusEl) return;

  if (!input.files || input.files.length === 0) {
    statusEl.className = 'lx-file-hint';
    statusEl.textContent = 'Pilih file gambar PNG sesuai nama sesi aktif';
    return;
  }

  var pickedName = input.files[0].name;
  if (pickedName === expected) {
    statusEl.className = 'lx-file-hint is-match';
    statusEl.textContent = 'Nama file sesuai (' + pickedName + ') — siap diupload';
  } else {
    statusEl.className = 'lx-file-hint is-mismatch';
    statusEl.textContent = 'Peringatan: nama file "' + pickedName + '" berbeda dari "' + expected + '"';
  }
}

function openUplogoHelp() {
  var modal = document.getElementById('uplogo-help-modal');
  if (modal) modal.classList.add('is-open');
}

function closeUplogoHelp() {
  var modal = document.getElementById('uplogo-help-modal');
  if (modal) modal.classList.remove('is-open');
}

function copyExpectedLogoName() {
  var text = <?= json_encode($expectedLogoName); ?>;
  var btn = document.getElementById('btnCopyLogoName');
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text);
  } else {
    var ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  }
  if (btn) {
    btn.innerHTML = '<i class="fa fa-check" style="color:#047857;"></i><span>Tersalin</span>';
    setTimeout(function() {
      btn.innerHTML = '<i class="fa fa-copy"></i><span>Salin</span>';
    }, 1800);
  }
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeUplogoHelp();
  }
});
</script>