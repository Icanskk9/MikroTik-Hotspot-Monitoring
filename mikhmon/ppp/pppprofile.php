<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  $errMsg = "";

  // Helper: jika Remote Address berisi rentang IP (mengandung '-'), otomatis buatkan IP Pool di MikroTik
  function resolveRemotePool($API, $profName, $remoteInput) {
    $remoteInput = trim($remoteInput);
    if ($remoteInput == "") return "";
    if (strpos($remoteInput, '-') !== false) {
      $poolName = "pool-" . preg_replace('/[^a-zA-Z0-9_-]/', '-', $profName);
      $existPool = $API->comm("/ip/pool/print", array("?name" => $poolName));
      if (isset($existPool[0]['.id'])) {
        $API->comm("/ip/pool/set", array(
          ".id" => $existPool[0]['.id'],
          "ranges" => $remoteInput,
        ));
      } else {
        $API->comm("/ip/pool/add", array(
          "name" => $poolName,
          "ranges" => $remoteInput,
        ));
      }
      return $poolName;
    }
    return $remoteInput;
  }

  // Tambah PPP Profile baru
  if (isset($_POST['add_pppprofile'])) {
    $p_name = trim($_POST['name']);
    $p_local = trim($_POST['local_address']);
    $p_remote = trim($_POST['remote_address']);
    $p_rate = trim($_POST['rate_limit']);
    $p_onlyone = trim($_POST['only_one']);
    $p_dns = trim($_POST['dns_server']);
    $p_comment = trim($_POST['comment']);

    if ($p_name != "") {
      $params = array(
        "name" => $p_name,
        "only-one" => ($p_onlyone != "" ? $p_onlyone : "default"),
      );
      if ($p_local != "") $params["local-address"] = $p_local;
      if ($p_remote != "") {
        $params["remote-address"] = resolveRemotePool($API, $p_name, $p_remote);
      }
      if ($p_rate != "") $params["rate-limit"] = $p_rate;
      if ($p_dns != "") $params["dns-server"] = $p_dns;
      if ($p_comment != "") $params["comment"] = $p_comment;

      $res = $API->comm("/ppp/profile/add", $params);
      if (isset($res['!trap'])) {
        $errMsg = isset($res['!trap'][0]['message']) ? $res['!trap'][0]['message'] : "Gagal menambahkan PPP Profile ke MikroTik.";
      } else {
        echo "<script>window.location='./?ppp=profiles&session=" . $session . "'</script>";
        exit;
      }
    }
  }

  // Edit PPP Profile
  if (isset($_POST['edit_pppprofile'])) {
    $ep_id = trim($_POST['profile_id']);
    $ep_name = trim($_POST['name']);
    $ep_local = trim($_POST['local_address']);
    $ep_remote = trim($_POST['remote_address']);
    $ep_rate = trim($_POST['rate_limit']);
    $ep_onlyone = trim($_POST['only_one']);
    $ep_dns = trim($_POST['dns_server']);
    $ep_comment = trim($_POST['comment']);

    if ($ep_id != "" && $ep_name != "") {
      $resolvedRemote = ($ep_remote != "") ? resolveRemotePool($API, $ep_name, $ep_remote) : "";
      $params = array(
        ".id" => $ep_id,
        "name" => $ep_name,
        "local-address" => $ep_local,
        "remote-address" => $resolvedRemote,
        "rate-limit" => $ep_rate,
        "only-one" => ($ep_onlyone != "" ? $ep_onlyone : "default"),
        "dns-server" => $ep_dns,
        "comment" => $ep_comment,
      );

      $res = $API->comm("/ppp/profile/set", $params);
      if (isset($res['!trap'])) {
        $errMsg = isset($res['!trap'][0]['message']) ? $res['!trap'][0]['message'] : "Gagal memperbarui PPP Profile.";
      } else {
        echo "<script>window.location='./?ppp=profiles&session=" . $session . "'</script>";
        exit;
      }
    }
  }

  $getprofiles = $API->comm("/ppp/profile/print");
  $TotalReg = count($getprofiles);

  $getpools = $API->comm("/ip/pool/print");
  $TotalPool = count($getpools);

  $limitedProf = 0;
  $pooledProf = 0;
  $onlyOneProf = 0;
  for ($j = 0; $j < $TotalReg; $j++) {
    if (!empty($getprofiles[$j]['rate-limit'])) $limitedProf++;
    if (!empty($getprofiles[$j]['remote-address'])) $pooledProf++;
    if (isset($getprofiles[$j]['only-one']) && $getprofiles[$j]['only-one'] == "yes") $onlyOneProf++;
  }
}
?>

<style>
  #mhm-pppprofile {
    background: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
    overflow: hidden;
    color: #18181b;
  }
  #mhm-pppprofile .lx-alert-error {
    margin: 16px 24px 0;
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    color: #b91c1c;
    font-size: 12.5px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #mhm-pppprofile .lx-topbar {
    padding: 18px 24px;
    border-bottom: 1px solid #f4f4f5;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
  }
  #mhm-pppprofile .lx-title-group { display: flex; align-items: center; gap: 10px; }
  #mhm-pppprofile .lx-title { margin: 0; font-size: 16px; font-weight: 600; color: #09090b; letter-spacing: -0.02em; }
  #mhm-pppprofile .lx-count {
    font-size: 11.5px;
    font-weight: 500;
    color: #71717a;
    background: #f4f4f5;
    padding: 2px 8px;
    border-radius: 6px;
  }
  #mhm-pppprofile .lx-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  #mhm-pppprofile .lx-search { position: relative; width: 230px; max-width: 100%; }
  #mhm-pppprofile .lx-search i {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 12px; color: #a1a1aa; pointer-events: none;
  }
  #mhm-pppprofile #filterTable {
    width: 100% !important; height: 34px !important; padding: 0 12px 0 32px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  #mhm-pppprofile #filterTable:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  #mhm-pppprofile .lx-btn {
    height: 34px; padding: 0 13px; background: #ffffff; border: 1px solid #e4e4e7; border-radius: 8px;
    font-size: 12.5px; font-weight: 500; color: #18181b !important;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none !important;
  }
  #mhm-pppprofile .lx-btn:hover { background: #fafafa; border-color: #d4d4d8; }
  #mhm-pppprofile .lx-btn.is-primary { background: #18181b; border-color: #18181b; color: #ffffff !important; }
  #mhm-pppprofile .lx-btn.is-primary:hover { background: #27272a; }

  #mhm-pppprofile .lx-metrics {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e4e4e7; background: #fafafa;
  }
  @media (max-width: 640px) { #mhm-pppprofile .lx-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  #mhm-pppprofile .lx-metric-item { padding: 14px 24px; border-right: 1px solid #e4e4e7; }
  #mhm-pppprofile .lx-metric-item:last-child { border-right: none; }
  #mhm-pppprofile .lx-metric-label {
    font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #71717a; margin-bottom: 4px;
  }
  #mhm-pppprofile .lx-metric-val { font-size: 18px; font-weight: 600; color: #09090b; font-variant-numeric: tabular-nums; }

  #mhm-pppprofile .lx-table-wrap { width: 100%; overflow-x: auto; max-height: 70vh; }
  #mhm-pppprofile #dataTable {
    width: 100% !important; border-collapse: collapse !important; margin: 0 !important;
    white-space: nowrap; text-align: left; background: #ffffff !important;
  }
  #mhm-pppprofile #dataTable thead th {
    position: sticky; top: 0; z-index: 5; background: #ffffff !important; color: #71717a !important;
    font-size: 11px !important; font-weight: 500 !important; text-transform: uppercase !important;
    letter-spacing: 0.06em !important; padding: 12px 18px !important; border-bottom: 1px solid #e4e4e7 !important;
  }
  #mhm-pppprofile #dataTable thead th.pointer { cursor: pointer; }
  #mhm-pppprofile #dataTable tbody td {
    padding: 13px 18px !important; border-bottom: 1px solid #f4f4f5 !important; font-size: 13px !important; color: #27272a !important;
  }
  #mhm-pppprofile #dataTable tbody tr:hover { background-color: #fafafa !important; }

  #mhm-pppprofile .lx-action-group { display: inline-flex; align-items: center; gap: 5px; }
  #mhm-pppprofile .lx-icon-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
    border-radius: 6px; border: 1px solid #e4e4e7; background: #ffffff; color: #52525b; font-size: 12px; cursor: pointer;
  }
  #mhm-pppprofile .lx-icon-btn:hover { background: #fafafa; border-color: #d4d4d8; color: #09090b; }
  #mhm-pppprofile .lx-icon-btn.is-danger:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
  #mhm-pppprofile .lx-badge-rate {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11.5px; font-weight: 600; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0;
    padding: 2px 8px; border-radius: 5px;
  }
  #mhm-pppprofile .lx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12.5px; color: #3f3f46; }
  #mhm-pppprofile .lx-name { font-weight: 600; color: #09090b; }
  #mhm-pppprofile .lx-empty { color: #d4d4d8; }
  #mhm-pppprofile .lx-footer {
    padding: 12px 24px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; font-size: 11.5px; color: #71717a;
  }

  .lx-overlay {
    position: fixed; inset: 0; z-index: 9999; background: rgba(9, 9, 11, 0.45);
    backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; padding: 16px;
  }
  .lx-overlay.is-open { display: flex; }
  .lx-dialog {
    width: 100%; max-width: 490px; background: #ffffff; border: 1px solid #e4e4e7;
    border-radius: 12px; box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.22); overflow: hidden; color: #18181b;
  }
  .lx-dialog-head {
    padding: 16px 20px; border-bottom: 1px solid #f4f4f5; display: flex; align-items: center; justify-content: space-between;
  }
  .lx-dialog-title { margin: 0; font-size: 15px; font-weight: 600; color: #09090b; }
  .lx-dialog-close { width: 28px; height: 28px; border-radius: 6px; border: none; background: transparent; color: #71717a; cursor: pointer; }
  .lx-dialog-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
  .lx-form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  @media (max-width: 480px) { .lx-form-row { grid-template-columns: minmax(0, 1fr); } }
  .lx-field { display: flex; flex-direction: column; gap: 5px; }
  .lx-field label { font-size: 12px; font-weight: 500; color: #3f3f46; margin: 0; }
  .lx-field label span { font-weight: 400; color: #a1a1aa; }
  .lx-input, .lx-select {
    width: 100% !important; height: 36px !important; padding: 0 11px !important;
    background: #fafafa !important; border: 1px solid #e4e4e7 !important; border-radius: 8px !important;
    font-size: 12.5px !important; color: #09090b !important; box-shadow: none !important; margin: 0 !important;
  }
  .lx-input:focus, .lx-select:focus {
    background: #ffffff !important; border-color: #18181b !important;
    box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important; outline: none !important;
  }
  .lx-mono-input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
  .lx-dialog-foot {
    padding: 13px 20px; background: #fafafa; border-top: 1px solid #f4f4f5;
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
  }
  .lx-btn-modal {
    height: 34px; padding: 0 14px; border-radius: 8px; border: 1px solid #e4e4e7;
    background: #ffffff; color: #18181b; font-size: 12.5px; font-weight: 500; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .lx-btn-modal.is-primary { background: #18181b; border-color: #18181b; color: #ffffff; }
  .lx-btn-modal.is-danger { background: #dc2626; border-color: #dc2626; color: #ffffff; }
</style>

<div id="mhm-pppprofile">
  <?php if ($errMsg != "") { ?>
    <div class="lx-alert-error">
      <i class="fa fa-exclamation-circle"></i>
      <span><strong>MikroTik Error:</strong> <?= htmlspecialchars($errMsg); ?></span>
    </div>
  <?php } ?>

  <div class="lx-topbar">
    <div class="lx-title-group">
      <h3 class="lx-title">PPP Profiles (Paket Kecepatan)</h3>
      <span class="lx-count"><?= $TotalReg; ?> profiles</span>
    </div>

    <div class="lx-actions">
      <div class="lx-search">
        <i class="fa fa-search"></i>
        <input id="filterTable" type="text" class="form-control" placeholder="Cari nama paket, pool, limit...">
      </div>
      <button type="button" onclick="openPProfModal('lxAddPProfModal');" class="lx-btn is-primary">
        <i class="fa fa-plus" style="font-size:11.5px; color:#d4d4d8;"></i>
        <span>Tambah Profile</span>
      </button>
      <button type="button" onclick="location.reload();" class="lx-btn" title="Refresh">
        <i class="fa fa-refresh" style="font-size:11.5px; color:#71717a;"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <div class="lx-metrics">
    <div class="lx-metric-item">
      <div class="lx-metric-label">Total Profiles</div>
      <div class="lx-metric-val"><?= $TotalReg; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Limit Bandwidth</div>
      <div class="lx-metric-val"><?= $limitedProf; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Pakai IP Pool</div>
      <div class="lx-metric-val"><?= $pooledProf; ?></div>
    </div>
    <div class="lx-metric-item">
      <div class="lx-metric-label">Only One (1 Sesi)</div>
      <div class="lx-metric-val"><?= $onlyOneProf; ?></div>
    </div>
  </div>

  <div class="lx-table-wrap">
    <table id="dataTable" class="table">
      <thead>
        <tr>
          <th style="text-align:center; width:88px;">Aksi</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Nama Profile</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Local Address</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Remote Address (Pool)</th>
          <th class="pointer"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Rate Limit [Rx/Tx]</th>
          <th>Only One</th>
          <th>DNS Server</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($i = 0; $i < $TotalReg; $i++) {
          $prof = $getprofiles[$i];
          $pid = $prof['.id'];
          $pname = $prof['name'];
          $plocal = $prof['local-address'];
          $premote = $prof['remote-address'];
          $prate = $prof['rate-limit'];
          $ponly = $prof['only-one'];
          $pdns = $prof['dns-server'];
          $pcomm = $prof['comment'];
          $isDefault = (isset($prof['default']) && $prof['default'] == "true");
          $delUrl = "./?remove-pprofile=" . urlencode($pid) . "&session=" . urlencode($session);
          ?>
          <tr>
            <td style="text-align:center;">
              <div class="lx-action-group">
                <span
                  class="lx-icon-btn pointer"
                  title="Edit <?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                  data-id="<?= htmlspecialchars($pid, ENT_QUOTES); ?>"
                  data-name="<?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                  data-local="<?= htmlspecialchars($plocal, ENT_QUOTES); ?>"
                  data-remote="<?= htmlspecialchars($premote, ENT_QUOTES); ?>"
                  data-rate="<?= htmlspecialchars($prate, ENT_QUOTES); ?>"
                  data-onlyone="<?= htmlspecialchars($ponly != "" ? $ponly : "default", ENT_QUOTES); ?>"
                  data-dns="<?= htmlspecialchars($pdns, ENT_QUOTES); ?>"
                  data-comment="<?= htmlspecialchars($pcomm, ENT_QUOTES); ?>"
                  onclick="openEditPProfModal(this);"
                >
                  <i class="fa fa-pencil"></i>
                </span>
                <?php if (!$isDefault) { ?>
                  <span
                    class="lx-icon-btn is-danger pointer"
                    title="Hapus <?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                    data-name="<?= htmlspecialchars($pname, ENT_QUOTES); ?>"
                    data-url="<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>"
                    onclick="openDelPProfModal(this);"
                  >
                    <i class="fa fa-trash-o"></i>
                  </span>
                <?php } ?>
              </div>
            </td>
            <td><span class="lx-name"><?= htmlspecialchars($pname); ?></span></td>
            <td><?= $plocal != "" ? '<span class="lx-mono">' . htmlspecialchars($plocal) . '</span>' : '<span class="lx-empty">&mdash;</span>'; ?></td>
            <td><?= $premote != "" ? '<span class="lx-mono">' . htmlspecialchars($premote) . '</span>' : '<span class="lx-empty">&mdash;</span>'; ?></td>
            <td><?= $prate != "" ? '<span class="lx-badge-rate">' . htmlspecialchars($prate) . '</span>' : '<span class="lx-empty">Unlimited</span>'; ?></td>
            <td><span class="lx-mono"><?= htmlspecialchars($ponly != "" ? $ponly : "default"); ?></span></td>
            <td><?= $pdns != "" ? '<span class="lx-mono">' . htmlspecialchars($pdns) . '</span>' : '<span class="lx-empty">&mdash;</span>'; ?></td>
          </tr>
          <?php
        }
        ?>
      </tbody>
    </table>
  </div>

  <div class="lx-footer">
    <div>Format Rate Limit: <strong style="color:#18181b;">Upload/Download</strong> (contoh: <code style="font-size:11px;">10M/20M</code>) &nbsp;&middot;&nbsp; Rentang IP otomatis dibuatkan IP Pool</div>
    <div>RouterOS v6 &amp; v7 Compatible</div>
  </div>
</div>

<!-- Datalist IP Pool MikroTik -->
<datalist id="lxIpPoolList">
  <?php
  for ($k = 0; $k < $TotalPool; $k++) {
    $poolName = $getpools[$k]['name'];
    if ($poolName != "") {
      echo '<option value="' . htmlspecialchars($poolName, ENT_QUOTES) . '">';
    }
  }
  ?>
</datalist>

<!-- Modal Tambah PPP Profile -->
<div id="lxAddPProfModal" class="lx-overlay" onclick="if(event.target===this) closePProfModal('lxAddPProfModal');">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Tambah PPP Profile</h4>
      <button type="button" class="lx-dialog-close" onclick="closePProfModal('lxAddPProfModal');"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <div class="lx-dialog-body">
        <div class="lx-form-row">
          <div class="lx-field">
            <label>Nama Profile Paket</label>
            <input type="text" name="name" class="lx-input lx-mono-input" placeholder="PAKET-FO-20MBPS" required>
          </div>
          <div class="lx-field">
            <label>Rate Limit [Up/Down]</label>
            <input type="text" name="rate_limit" class="lx-input lx-mono-input" placeholder="10M/20M">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Local Address <span>(Gateway Router)</span></label>
            <input type="text" name="local_address" class="lx-input lx-mono-input" placeholder="10.20.20.1">
          </div>
          <div class="lx-field">
            <label>Remote Address <span>(Pool / Range IP)</span></label>
            <input type="text" name="remote_address" list="lxIpPoolList" class="lx-input lx-mono-input" placeholder="10.20.20.2-10.20.20.254">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Only One <span>(1 Akun 1 Koneksi)</span></label>
            <select name="only_one" class="lx-select">
              <option value="yes" selected>yes (Direkomendasikan)</option>
              <option value="default">default</option>
              <option value="no">no</option>
            </select>
          </div>
          <div class="lx-field">
            <label>DNS Server <span>(Opsional)</span></label>
            <input type="text" name="dns_server" class="lx-input lx-mono-input" placeholder="8.8.8.8,1.1.1.1">
          </div>
        </div>

        <div class="lx-field">
          <label>Keterangan <span>(Opsional)</span></label>
          <input type="text" name="comment" class="lx-input" placeholder="Paket Fiber Optik Lab & Guru">
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closePProfModal('lxAddPProfModal');">Batal</button>
        <button type="submit" name="add_pppprofile" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Profile</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit PPP Profile -->
<div id="lxEditPProfModal" class="lx-overlay" onclick="if(event.target===this) closePProfModal('lxEditPProfModal');">
  <div class="lx-dialog">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Edit PPP Profile</h4>
      <button type="button" class="lx-dialog-close" onclick="closePProfModal('lxEditPProfModal');"><i class="fa fa-times"></i></button>
    </div>
    <form method="post" action="" autocomplete="off" style="margin:0;">
      <input type="hidden" name="profile_id" id="editPProfId">
      <div class="lx-dialog-body">
        <div class="lx-form-row">
          <div class="lx-field">
            <label>Nama Profile Paket</label>
            <input type="text" name="name" id="editPProfName" class="lx-input lx-mono-input" required>
          </div>
          <div class="lx-field">
            <label>Rate Limit [Up/Down]</label>
            <input type="text" name="rate_limit" id="editPProfRate" class="lx-input lx-mono-input" placeholder="10M/20M">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Local Address <span>(Gateway Router)</span></label>
            <input type="text" name="local_address" id="editPProfLocal" class="lx-input lx-mono-input">
          </div>
          <div class="lx-field">
            <label>Remote Address <span>(Pool / Range IP)</span></label>
            <input type="text" name="remote_address" id="editPProfRemote" list="lxIpPoolList" class="lx-input lx-mono-input">
          </div>
        </div>

        <div class="lx-form-row">
          <div class="lx-field">
            <label>Only One</label>
            <select name="only_one" id="editPProfOnly" class="lx-select">
              <option value="yes">yes</option>
              <option value="default">default</option>
              <option value="no">no</option>
            </select>
          </div>
          <div class="lx-field">
            <label>DNS Server <span>(Opsional)</span></label>
            <input type="text" name="dns_server" id="editPProfDns" class="lx-input lx-mono-input">
          </div>
        </div>

        <div class="lx-field">
          <label>Keterangan</label>
          <input type="text" name="comment" id="editPProfComment" class="lx-input">
        </div>
      </div>
      <div class="lx-dialog-foot">
        <button type="button" class="lx-btn-modal" onclick="closePProfModal('lxEditPProfModal');">Batal</button>
        <button type="submit" name="edit_pppprofile" class="lx-btn-modal is-primary"><i class="fa fa-save"></i><span>Simpan Perubahan</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Konfirmasi Hapus Profile -->
<div id="lxDelPProfModal" class="lx-overlay" onclick="if(event.target===this) closePProfModal('lxDelPProfModal');">
  <div class="lx-dialog" style="max-width:400px;">
    <div class="lx-dialog-head">
      <h4 class="lx-dialog-title">Hapus PPP Profile</h4>
      <button type="button" class="lx-dialog-close" onclick="closePProfModal('lxDelPProfModal');"><i class="fa fa-times"></i></button>
    </div>
    <div class="lx-dialog-body">
      <p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
        Yakin ingin menghapus profile paket <strong id="delPProfTarget" style="color:#09090b;"></strong>?
      </p>
    </div>
    <div class="lx-dialog-foot">
      <button type="button" class="lx-btn-modal" onclick="closePProfModal('lxDelPProfModal');">Batal</button>
      <button type="button" id="delPProfConfirmBtn" class="lx-btn-modal is-danger"><i class="fa fa-trash-o"></i><span>Hapus</span></button>
    </div>
  </div>
</div>

<script>
  function openPProfModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.add('is-open');
  }
  function closePProfModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('is-open');
  }
  function openEditPProfModal(el) {
    document.getElementById('editPProfId').value = el.getAttribute('data-id') || '';
    document.getElementById('editPProfName').value = el.getAttribute('data-name') || '';
    document.getElementById('editPProfLocal').value = el.getAttribute('data-local') || '';
    document.getElementById('editPProfRemote').value = el.getAttribute('data-remote') || '';
    document.getElementById('editPProfRate').value = el.getAttribute('data-rate') || '';
    document.getElementById('editPProfOnly').value = el.getAttribute('data-onlyone') || 'default';
    document.getElementById('editPProfDns').value = el.getAttribute('data-dns') || '';
    document.getElementById('editPProfComment').value = el.getAttribute('data-comment') || '';
    openPProfModal('lxEditPProfModal');
  }
  function openDelPProfModal(el) {
    var name = el.getAttribute('data-name') || '';
    var url = el.getAttribute('data-url') || '';
    document.getElementById('delPProfTarget').textContent = name;
    document.getElementById('delPProfConfirmBtn').onclick = function() {
      closePProfModal('lxDelPProfModal');
      loadpage(url);
    };
    openPProfModal('lxDelPProfModal');
  }
</script>