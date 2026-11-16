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
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
	exit;
}

// Handler AJAX Hapus User Log dengan Validasi Password Login Mikhmon
if (isset($_POST['ajax_delete_userlog'])) {
	echo "<!--JSON_START-->";

	$inputPass = isset($_POST['app_password']) ? (string)$_POST['app_password'] : '';
	$rawIds    = isset($_POST['script_ids']) ? trim((string)$_POST['script_ids']) : '';

	if ($inputPass === '') {
		echo json_encode(array(
			'status'  => 'error',
			'message' => 'Password login aplikasi wajib diisi!'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	// Pastikan $passadm dan fungsi decrypt() termuat persis seperti di admin.php
	if (!isset($passadm) || !function_exists('decrypt')) {
		ob_start();
		if (file_exists('./include/config.php')) {
			include('./include/config.php');
			include('./include/readcfg.php');
		} elseif (file_exists('../include/config.php')) {
			include('../include/config.php');
			include('../include/readcfg.php');
		}
		ob_end_clean();
	}

	$realAdminPass = (isset($passadm) && function_exists('decrypt')) ? (string)decrypt($passadm) : '';

	if ($realAdminPass === '' || $inputPass !== $realAdminPass) {
		echo json_encode(array(
			'status'  => 'error',
			'message' => 'Password login aplikasi salah! Penghapusan dibatalkan.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	$targetIds = array_filter(array_map('trim', explode(',', $rawIds)));
	if (empty($targetIds)) {
		echo json_encode(array(
			'status'  => 'error',
			'message' => 'Tidak ada data log yang dipilih untuk dihapus.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
		// Filter hanya script dengan comment=mikhmon agar script sistem lain tetap aman
		$API->write('/system/script/print', false);
		$API->write('?=comment=mikhmon');
		$validScripts = $API->read();

		$allowedIdMap = array();
		if (is_array($validScripts)) {
			foreach ($validScripts as $sc) {
				if (isset($sc['.id'])) {
					$allowedIdMap[$sc['.id']] = true;
				}
			}
		}

		$deletedCount = 0;
		foreach ($targetIds as $sid) {
			if (isset($allowedIdMap[$sid])) {
				$API->comm('/system/script/remove', array(
					'.id' => $sid
				));
				$deletedCount++;
			}
		}

		$API->disconnect();

		echo json_encode(array(
			'status'  => 'success',
			'deleted' => $deletedCount,
			'message' => $deletedCount . ' data User Log berhasil dihapus permanen dari MikroTik.'
		));
	} else {
		echo json_encode(array(
			'status'  => 'error',
			'message' => 'Gagal terhubung ke MikroTik API.'
		));
	}

	echo "<!--JSON_END-->";
	exit;
}

$idhr = isset($_GET['idhr']) ? $_GET['idhr'] : '';
$idbl = isset($_GET['idbl']) ? $_GET['idbl'] : '';

if (strlen($idhr) > 0) {
	if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
		$API->write('/system/script/print', false);
		$API->write('?=source=' . $idhr . '');
		$ARRAY = $API->read();
		$API->disconnect();
	}
	$filedownload = $idhr;
} elseif (strlen($idbl) > 0) {
	if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
		$API->write('/system/script/print', false);
		$API->write('?=owner=' . $idbl . '');
		$ARRAY = $API->read();
		$API->disconnect();
	}
	$filedownload = $idbl;
} else {
	if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
		$API->write('/system/script/print', false);
		$API->write('?=comment=mikhmon');
		$ARRAY = $API->read();
		$API->disconnect();
	}
	$filedownload = "all";
}

// Hitung ringkasan statistik untuk metric strip
$TotalReg = is_array($ARRAY) ? count($ARRAY) : 0;
$uniqueUsers = array();
$uniqueMacs = array();
for ($k = 0; $k < $TotalReg; $k++) {
	$pName = explode("-|-", isset($ARRAY[$k]['name']) ? $ARRAY[$k]['name'] : '');
	if (!empty($pName[2])) {
		$uniqueUsers[$pName[2]] = true;
	}
	if (!empty($pName[5])) {
		$uniqueMacs[$pName[5]] = true;
	}
}

$periodLabel = trim(ucfirst($idhr) . " " . ucfirst(substr($idbl, 0, 3) . " " . substr($idbl, 3, 5)));
if ($periodLabel == "") {
	$periodLabel = "Semua Periode";
}
?>

<style>
	#mhm-userlog {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-userlog .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		background: #ffffff;
	}
	#mhm-userlog .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-userlog .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-userlog .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Action Buttons */
	#mhm-userlog .lx-actions {
		display: flex;
		align-items: center;
		gap: 7px;
		flex-wrap: wrap;
	}
	#mhm-userlog .lx-btn {
		height: 33px;
		padding: 0 12px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		font-size: 12px;
		font-weight: 500;
		color: #18181b;
		display: inline-flex;
		align-items: center;
		gap: 6px;
		cursor: pointer;
		text-decoration: none;
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	#mhm-userlog .lx-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b;
	}
	#mhm-userlog .lx-btn:active {
		background: #f4f4f5;
	}
	#mhm-userlog .lx-btn i {
		font-size: 11.5px;
		color: #71717a;
	}
	#mhm-userlog .lx-btn.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}
	#mhm-userlog .lx-btn.is-primary i {
		color: #d4d4d8;
	}
	#mhm-userlog .lx-btn.is-primary:hover {
		background: #27272a;
		border-color: #27272a;
	}
	#mhm-userlog .lx-btn.is-danger {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-userlog .lx-btn.is-danger i {
		color: #dc2626;
	}
	#mhm-userlog .lx-btn.is-danger:hover {
		background: #fee2e2;
		border-color: #fca5a5;
		color: #b91c1c;
	}
	#mhm-userlog .lx-btn:disabled {
		opacity: 0.45;
		cursor: not-allowed;
		pointer-events: none;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-userlog .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 768px) {
		#mhm-userlog .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-userlog .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-userlog .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 768px) {
		#mhm-userlog .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-userlog .lx-metric-item:nth-child(1),
		#mhm-userlog .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-userlog .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-userlog .lx-metric-val {
		font-size: 17px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-userlog .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
		flex-shrink: 0;
	}

	/* Filter Toolbar */
	#mhm-userlog .lx-filterbar {
		padding: 14px 24px;
		background: #ffffff;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
	}
	#mhm-userlog .lx-search {
		position: relative;
		width: 250px;
		max-width: 100%;
	}
	#mhm-userlog .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-userlog #filterTable {
		width: 100% !important;
		height: 34px !important;
		padding: 0 12px 0 32px !important;
		background: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		color: #09090b !important;
		box-shadow: none !important;
		margin: 0 !important;
		float: none !important;
		max-width: none !important;
		transition: all 0.15s ease !important;
	}
	#mhm-userlog #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-userlog #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}

	#mhm-userlog .lx-date-group {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-userlog .lx-select {
		height: 34px !important;
		padding: 0 28px 0 11px !important;
		background-color: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		font-weight: 500 !important;
		color: #18181b !important;
		cursor: pointer;
		outline: none !important;
		transition: all 0.15s ease;
	}
	#mhm-userlog .lx-select:hover {
		border-color: #d4d4d8 !important;
		background-color: #ffffff !important;
	}
	#mhm-userlog .lx-select:focus {
		border-color: #18181b !important;
		background-color: #ffffff !important;
	}

	/* Table */
	#mhm-userlog .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 68vh;
	}
	#mhm-userlog #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-userlog #dataTable thead tr:first-child th {
		background: #fafafa !important;
		color: #18181b !important;
		font-size: 12px !important;
		font-weight: 600 !important;
		padding: 11px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		letter-spacing: -0.01em;
	}
	#mhm-userlog #dataTable thead tr:nth-child(2) th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #ffffff !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 500 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.06em !important;
		padding: 11px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		user-select: none;
	}
	#mhm-userlog #dataTable tbody td {
		padding: 13px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-userlog #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-userlog #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-userlog #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}
	/* Kolom Date & Time */
	#mhm-userlog #dataTable tbody td:nth-child(1),
	#mhm-userlog #dataTable tbody td:nth-child(2) {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px !important;
		color: #52525b !important;
		font-variant-numeric: tabular-nums;
	}
	/* Kolom Username */
	#mhm-userlog #dataTable tbody td:nth-child(3) {
		font-weight: 600 !important;
		color: #09090b !important;
	}
	/* Kolom Address & MAC Address */
	#mhm-userlog #dataTable tbody td:nth-child(4),
	#mhm-userlog #dataTable tbody td:nth-child(5) {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px !important;
		color: #3f3f46 !important;
		font-variant-numeric: tabular-nums;
	}
	/* Kolom Validity */
	#mhm-userlog #dataTable tbody td:nth-child(6) {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12px !important;
		font-weight: 500 !important;
		color: #18181b !important;
	}

	/* Row Delete Button */
	.lx-row-del-btn {
		height: 28px;
		padding: 0 9px;
		border-radius: 6px;
		border: 1px solid #fecaca;
		background: #fef2f2;
		color: #dc2626;
		font-size: 11.5px;
		font-weight: 500;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 4px;
		transition: all 0.15s ease;
	}
	.lx-row-del-btn:hover {
		background: #dc2626;
		border-color: #dc2626;
		color: #ffffff;
	}

	/* Empty State */
	#mhm-userlog .lx-empty-state {
		padding: 48px 24px;
		text-align: center;
		color: #a1a1aa;
		font-size: 12.5px;
		border-top: 1px solid #f4f4f5;
	}

	/* Footer */
	#mhm-userlog .lx-footer {
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

	/* Security Confirmation Modal & Toast */
	#ulog-modal-backdrop {
		position: fixed;
		inset: 0;
		background: rgba(9, 9, 11, 0.5);
		backdrop-filter: blur(2px);
		z-index: 99998;
		display: none;
		align-items: center;
		justify-content: center;
		padding: 16px;
	}
	#ulog-modal-backdrop.is-open {
		display: flex;
	}
	.ulog-modal-card {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 14px;
		width: 100%;
		max-width: 430px;
		padding: 22px;
		box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.28);
		color: #18181b;
	}
	.ulog-alert-box {
		background: #fef2f2;
		border: 1px solid #fecaca;
		border-left: 4px solid #dc2626;
		border-radius: 8px;
		padding: 10px 12px;
		font-size: 12px;
		color: #991b1b;
		margin-bottom: 12px;
		line-height: 1.45;
	}
	.ulog-pass-wrap {
		position: relative;
		margin-top: 6px;
	}
	.ulog-pass-input {
		width: 100% !important;
		height: 38px !important;
		padding: 0 38px 0 12px !important;
		border: 1px solid #d4d4d8 !important;
		border-radius: 8px !important;
		font-size: 13px !important;
		color: #09090b !important;
		background: #fafafa !important;
		box-sizing: border-box !important;
		margin: 0 !important;
	}
	.ulog-pass-input:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08) !important;
	}
	.ulog-pass-eye {
		position: absolute;
		right: 10px;
		top: 50%;
		transform: translateY(-50%);
		background: none;
		border: none;
		color: #71717a;
		cursor: pointer;
		padding: 4px;
		font-size: 13px;
	}
	#ulog-toast-box {
		position: fixed;
		top: 68px;
		right: 22px;
		z-index: 99999;
		display: flex;
		flex-direction: column;
		gap: 8px;
		pointer-events: none;
	}
	.ulog-toast {
		pointer-events: auto;
		min-width: 280px;
		max-width: 370px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-left: 4px solid #16a34a;
		border-radius: 10px;
		padding: 12px 14px;
		box-shadow: 0 12px 28px -8px rgba(9, 9, 11, 0.18);
		display: flex;
		align-items: flex-start;
		gap: 10px;
	}
	.ulog-toast.is-error {
		border-left-color: #dc2626;
	}
</style>

<div id="ulog-toast-box"></div>

<!-- Modal Alert & Verifikasi Password Hapus Log -->
<div id="ulog-modal-backdrop">
	<div class="ulog-modal-card" role="dialog" aria-modal="true">
		<div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
			<div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0;">
				<i class="fa fa-exclamation-triangle"></i>
			</div>
			<div>
				<h4 style="margin:0; font-size:15px; font-weight:600; color:#09090b;">Konfirmasi Hapus User Log</h4>
				<div style="font-size:11.5px; color:#71717a;">Otorisasi keamanan aplikasi diperlukan</div>
			</div>
		</div>

		<div class="ulog-alert-box">
			<strong>Peringatan:</strong> Data riwayat login yang dihapus akan hilang permanen dari <code>/system/script</code> MikroTik dan tidak dapat dipulihkan.
		</div>

		<div id="ulog_target_info" style="background:#fafafa; border:1px solid #e4e4e7; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12.5px; color:#18181b;">
			-
		</div>

		<div style="margin-bottom:14px;">
			<label for="ulog_confirm_password" style="display:block; font-size:12px; font-weight:600; color:#18181b;">
				Masukkan Password Login Aplikasi
			</label>
			<div class="ulog-pass-wrap">
				<input type="password" id="ulog_confirm_password" class="ulog-pass-input" placeholder="Ketik password login Mikhmon..." autocomplete="current-password">
				<button type="button" id="ulog_toggle_pass" class="ulog-pass-eye" title="Tampilkan / Sembunyikan Password">
					<i class="fa fa-eye"></i>
				</button>
			</div>
			<div id="ulog_modal_error" style="display:none; margin-top:6px; font-size:12px; color:#dc2626; font-weight:500;"></div>
		</div>

		<div style="display:flex; justify-content:flex-end; gap:8px;">
			<button type="button" id="btn_ulog_cancel" style="height:36px; padding:0 15px; border-radius:8px; border:1px solid #e4e4e7; background:#ffffff; color:#18181b; font-size:12.5px; font-weight:500; cursor:pointer;">
				Batal
			</button>
			<button type="button" id="btn_ulog_confirm" style="height:36px; padding:0 15px; border-radius:8px; border:1px solid #dc2626; background:#dc2626; color:#ffffff; font-size:12.5px; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
				<i class="fa fa-trash"></i>
				<span>Ya, Hapus Permanen</span>
			</button>
		</div>
	</div>
</div>

<div id="mhm-userlog">
	<!-- Top Bar -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title">
				User Log <?= htmlspecialchars($idhr . $idbl); ?>
			</h3>
			<span class="lx-count" id="ulog_badge_count">
				<?= $TotalReg; ?> <?= ($TotalReg > 1) ? 'entries' : 'entry'; ?>
			</span>
		</div>

		<!-- Action Buttons -->
		<div class="lx-actions">
			<button type="button" class="lx-btn" onclick="exportTableToCSV('user-log-mikhmon-<?= htmlspecialchars($filedownload); ?>.csv')" title="Download user log">
				<i class="fa fa-download"></i>
				<span>CSV</span>
			</button>

			<button type="button" class="lx-btn" onclick="location.href='./?report=userlog&session=<?= urlencode($session); ?>';" title="Reload all data">
				<i class="fa fa-list-ul"></i>
				<span><?= $_all ?></span>
			</button>

			<button type="button" id="btn_delete_all_filtered" class="lx-btn is-danger" onclick="openBulkDeleteModal();" <?= ($TotalReg == 0) ? 'disabled' : ''; ?> title="Hapus semua log pada tampilan/periode ini">
				<i class="fa fa-trash"></i>
				<span id="btn_delete_all_label">Hapus Log (<?= $TotalReg; ?>)</span>
			</button>
		</div>
	</div>

	<!-- Integrated Metric Strip -->
	<div class="lx-metrics">
		<div class="lx-metric-item">
			<div class="lx-metric-label">Periode Log</div>
			<div class="lx-metric-val"><?= htmlspecialchars($periodLabel); ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Total Aktivitas</div>
			<div class="lx-metric-val">
				<span class="lx-dot-live"></span>
				<span><?= number_format($TotalReg); ?></span>
			</div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">User Unik</div>
			<div class="lx-metric-val"><?= number_format(count($uniqueUsers)); ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Perangkat (MAC)</div>
			<div class="lx-metric-val"><?= number_format(count($uniqueMacs)); ?></div>
		</div>
	</div>

	<!-- Filter Bar -->
	<div class="lx-filterbar">
		<div class="lx-search">
			<i class="fa fa-search"></i>
			<input id="filterTable" type="text" class="form-control" placeholder="Cari tanggal, user, IP, atau MAC...">
		</div>

		<div class="lx-date-group">
			<select class="lx-select" title="Day" id="D">
				<?php
				$day = explode("/", $idhr)[1];
				if ($day != "") {
					echo "<option value='" . htmlspecialchars($day) . "'>" . htmlspecialchars($day) . "</option>";
				}
				echo "<option value=''>Day</option>";

				for ($x = 1; $x <= 31; $x++) {
					$dx = (strlen($x) == 1) ? ("0" . $x) : (string)$x;
					echo "<option value='" . $dx . "'>" . $dx . "</option>";
				}
				?>
			</select>

			<select class="lx-select" title="Month" id="M">
				<?php
				$idbls = array(1 => "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "okt", "nov", "dec");
				$idblf = array(1 => "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
				$month = explode("/", $idhr)[0];
				$month1 = substr($idbl, 0, 3);

				if ($month != "") {
					$fm = array_search($month, $idbls);
					echo "<option value='" . htmlspecialchars($month) . "'>" . $idblf[$fm] . "</option>";
				} elseif ($month1 != "") {
					$fm = array_search($month1, $idbls);
					echo "<option value='" . htmlspecialchars($month1) . "'>" . $idblf[$fm] . "</option>";
				} else {
					echo "<option value='" . $idbls[date("n")] . "'>" . $idblf[date("n")] . "</option>";
				}
				for ($x = 1; $x <= 12; $x++) {
					echo "<option value='" . $idbls[$x] . "'>" . $idblf[$x] . "</option>";
				}
				?>
			</select>

			<select class="lx-select" title="Year" id="Y">
				<?php
				$year = explode("/", $idhr)[2];
				$year1 = substr($idbl, 3, 4);

				if ($year != "") {
					echo "<option>" . htmlspecialchars($year) . "</option>";
				} elseif ($year1 != "") {
					echo "<option>" . htmlspecialchars($year1) . "</option>";
				} else {
					echo "<option>" . date("Y") . "</option>";
				}
				for ($Y = 2018; $Y <= date("Y"); $Y++) {
					if ($Y != date("Y")) {
						echo "<option value='" . $Y . "'>" . $Y . "</option>";
					}
				}
				?>
			</select>

			<button type="button" class="lx-btn is-primary" onclick="filterR();">
				<i class="fa fa-filter"></i>
				<span>Filter</span>
			</button>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table id="dataTable" class="table">
			<thead>
				<tr>
					<th colspan="7">User Log <?= htmlspecialchars($filedownload); ?></th>
				</tr>
				<tr>
					<th><?= $_date ?></th>
					<th><?= $_time ?></th>
					<th><?= $_user_name ?></th>
					<th>address</th>
					<th>Mac Address</th>
					<th><?= $_validity ?></th>
					<th style="text-align:right;">Aksi</th>
				</tr>
			</thead>
			<tbody id="ulog_tbody">
				<?php
				for ($i = 0; $i < $TotalReg; $i++) {
					$regtable = $ARRAY[$i];
					$scriptId = isset($regtable['.id']) ? $regtable['.id'] : '';
					$getname  = explode("-|-", isset($regtable['name']) ? $regtable['name'] : '');
					$tgl      = isset($getname[0]) ? $getname[0] : '';
					$ltime    = isset($getname[1]) ? $getname[1] : '';
					$username = isset($getname[2]) ? $getname[2] : '';
					$addr     = isset($getname[4]) ? $getname[4] : '';
					$mac      = isset($getname[5]) ? $getname[5] : '';
					$val      = isset($getname[6]) ? $getname[6] : '';

					echo '<tr class="ulog-row" data-id="' . htmlspecialchars($scriptId, ENT_QUOTES) . '" data-user="' . htmlspecialchars($username, ENT_QUOTES) . '" data-date="' . htmlspecialchars($tgl . ' ' . $ltime, ENT_QUOTES) . '" data-ip="' . htmlspecialchars($addr, ENT_QUOTES) . '">';
					echo '<td>' . htmlspecialchars($tgl) . '</td>';
					echo '<td>' . htmlspecialchars($ltime) . '</td>';
					echo '<td>' . htmlspecialchars($username) . '</td>';
					echo '<td>' . htmlspecialchars($addr) . '</td>';
					echo '<td>' . htmlspecialchars($mac) . '</td>';
					echo '<td>' . htmlspecialchars($val) . '</td>';
					echo '<td style="text-align:right;">';
					echo '<button type="button" class="lx-row-del-btn" onclick="openSingleDeleteModal(this);" title="Hapus baris log ini">';
					echo '<i class="fa fa-trash"></i> Hapus';
					echo '</button>';
					echo '</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>

		<?php if ($TotalReg == 0): ?>
			<div class="lx-empty-state">
				Belum ada catatan riwayat login user pada periode ini.
			</div>
		<?php endif; ?>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div>Format Ekspor: <strong style="color:#18181b;">CSV</strong></div>
		<div>Pilih Day untuk filter harian atau kosongkan untuk rekap bulanan</div>
	</div>
</div>

<script>
	var pendingDeleteIds = [];
	var periodText = <?= json_encode($periodLabel); ?>;

	function filterR() {
		var D = document.getElementById('D').value;
		var M = document.getElementById('M').value;
		var Y = document.getElementById('Y').value;

		if (D !== "") {
			window.location = './?report=userlog&idhr=' + M + '/' + D + '/' + Y + '&session=<?= urlencode($session); ?>';
		} else {
			window.location = './?report=userlog&idbl=' + M + Y + '&session=<?= urlencode($session); ?>';
		}
	}

	function downloadCSV(csv, filename) {
		var csvFile = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8;" });
		var downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
		document.body.removeChild(downloadLink);
	}

	function exportTableToCSV(filename) {
		var csv = [];
		var rows = document.querySelectorAll("#dataTable tr");

		for (var i = 0; i < rows.length; i++) {
			if (rows[i].style.display === 'none') continue;
			var row = [];
			var cols = rows[i].querySelectorAll("td, th");
			var limit = (cols.length > 1) ? (cols.length - 1) : cols.length;
			for (var j = 0; j < limit; j++) {
				var txt = cols[j].innerText.replace(/"/g, '""');
				row.push('"' + txt + '"');
			}
			csv.push(row.join(","));
		}
		downloadCSV(csv.join("\r\n"), filename);
	}

	function showUlogToast(type, title, msg) {
		var box = document.getElementById('ulog-toast-box');
		if (!box) return;
		var isOk = (type === 'success');
		var el = document.createElement('div');
		el.className = 'ulog-toast ' + (isOk ? '' : 'is-error');
		el.innerHTML =
			'<div style="width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;background:' + (isOk ? '#dcfce7;color:#16a34a' : '#fee2e2;color:#dc2626') + ';">' +
				'<i class="fa ' + (isOk ? 'fa-check' : 'fa-times') + '"></i>' +
			'</div>' +
			'<div style="flex:1;">' +
				'<div style="font-size:12.5px;font-weight:600;color:#09090b;margin-bottom:2px;">' + title + '</div>' +
				'<div style="font-size:12px;color:#52525b;">' + msg + '</div>' +
			'</div>';
		box.appendChild(el);
		setTimeout(function() {
			el.style.opacity = '0';
			setTimeout(function() {
				if (el.parentNode) el.parentNode.removeChild(el);
			}, 200);
		}, 3800);
	}

	function getVisibleRows() {
		var rows = document.querySelectorAll('#ulog_tbody tr.ulog-row');
		var visible = [];
		for (var i = 0; i < rows.length; i++) {
			if (rows[i].style.display !== 'none') {
				visible.push(rows[i]);
			}
		}
		return visible;
	}

	function updateVisibleCounter() {
		var visible = getVisibleRows();
		var count = visible.length;
		var badge = document.getElementById('ulog_badge_count');
		var btnDelAll = document.getElementById('btn_delete_all_filtered');
		var lblDelAll = document.getElementById('btn_delete_all_label');

		if (badge) {
			badge.textContent = count + (count > 1 ? ' entries' : ' entry');
		}
		if (lblDelAll) {
			lblDelAll.textContent = 'Hapus Log (' + count + ')';
		}
		if (btnDelAll) {
			btnDelAll.disabled = (count === 0);
		}
	}

	// Pencarian real-time
	var filterInput = document.getElementById('filterTable');
	if (filterInput) {
		filterInput.addEventListener('input', function() {
			var q = this.value.toLowerCase().trim();
			var rows = document.querySelectorAll('#ulog_tbody tr.ulog-row');
			for (var i = 0; i < rows.length; i++) {
				var text = rows[i].innerText.toLowerCase();
				rows[i].style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
			}
			updateVisibleCounter();
		});
	}

	function openSingleDeleteModal(btn) {
		var tr = btn.closest('tr.ulog-row');
		if (!tr) return;
		var id   = tr.getAttribute('data-id');
		var user = tr.getAttribute('data-user');
		var date = tr.getAttribute('data-date');
		var ip   = tr.getAttribute('data-ip');

		if (!id) {
			alert('ID script MikroTik tidak ditemukan pada baris ini.');
			return;
		}

		pendingDeleteIds = [id];
		document.getElementById('ulog_target_info').innerHTML =
			'<div style="font-weight:600; color:#09090b; margin-bottom:2px;">1 Entri Log Akan Dihapus:</div>' +
			'<div style="font-family:monospace; font-size:12px; color:#52525b;">User: <strong>' + user + '</strong> &bull; ' + date + ' &bull; ' + ip + '</div>';

		showDeleteModal();
	}

	function openBulkDeleteModal() {
		var visible = getVisibleRows();
		if (visible.length === 0) return;

		var ids = [];
		for (var i = 0; i < visible.length; i++) {
			var sid = visible[i].getAttribute('data-id');
			if (sid) ids.push(sid);
		}
		if (ids.length === 0) return;

		pendingDeleteIds = ids;
		document.getElementById('ulog_target_info').innerHTML =
			'<div style="font-weight:600; color:#dc2626; margin-bottom:2px;">Hapus Massal (' + ids.length + ' Entri Log):</div>' +
			'<div style="font-size:12px; color:#52525b;">Seluruh log yang sedang tampil pada periode <strong>' + periodText + '</strong> akan dihapus dari MikroTik.</div>';

		showDeleteModal();
	}

	function showDeleteModal() {
		var passInput = document.getElementById('ulog_confirm_password');
		var errBox = document.getElementById('ulog_modal_error');
		passInput.value = '';
		passInput.type = 'password';
		document.getElementById('ulog_toggle_pass').innerHTML = '<i class="fa fa-eye"></i>';
		errBox.style.display = 'none';
		errBox.textContent = '';

		document.getElementById('ulog-modal-backdrop').classList.add('is-open');
		setTimeout(function() {
			passInput.focus();
		}, 80);
	}

	function closeDeleteModal() {
		document.getElementById('ulog-modal-backdrop').classList.remove('is-open');
		pendingDeleteIds = [];
	}

	document.getElementById('btn_ulog_cancel').addEventListener('click', closeDeleteModal);
	document.getElementById('ulog-modal-backdrop').addEventListener('click', function(e) {
		if (e.target === this) closeDeleteModal();
	});

	document.getElementById('ulog_toggle_pass').addEventListener('click', function() {
		var inp = document.getElementById('ulog_confirm_password');
		if (inp.type === 'password') {
			inp.type = 'text';
			this.innerHTML = '<i class="fa fa-eye-slash"></i>';
		} else {
			inp.type = 'password';
			this.innerHTML = '<i class="fa fa-eye"></i>';
		}
	});

	document.getElementById('ulog_confirm_password').addEventListener('keydown', function(e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			document.getElementById('btn_ulog_confirm').click();
		}
	});

	document.getElementById('btn_ulog_confirm').addEventListener('click', function() {
		var passVal = document.getElementById('ulog_confirm_password').value;
		var errBox  = document.getElementById('ulog_modal_error');
		var btn     = this;

		if (!passVal) {
			errBox.textContent = 'Silakan masukkan password login aplikasi terlebih dahulu.';
			errBox.style.display = 'block';
			document.getElementById('ulog_confirm_password').focus();
			return;
		}

		if (pendingDeleteIds.length === 0) return;

		btn.disabled = true;
		btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Menghapus...</span>';
		errBox.style.display = 'none';

		var formData = new URLSearchParams();
		formData.append('ajax_delete_userlog', '1');
		formData.append('app_password', passVal);
		formData.append('script_ids', pendingDeleteIds.join(','));

		fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData.toString()
		})
		.then(function(res) { return res.text(); })
		.then(function(raw) {
			btn.disabled = false;
			btn.innerHTML = '<i class="fa fa-trash"></i><span>Ya, Hapus Permanen</span>';

			var s = raw.indexOf('<!--JSON_START-->');
			var e = raw.indexOf('<!--JSON_END-->');
			var data = null;
			if (s !== -1 && e !== -1) {
				try {
					data = JSON.parse(raw.substring(s + 17, e));
				} catch (err) {}
			}

			if (!data) {
				errBox.textContent = 'Respon server tidak valid. Coba muat ulang halaman.';
				errBox.style.display = 'block';
				return;
			}

			if (data.status === 'success') {
				closeDeleteModal();
				showUlogToast('success', 'Berhasil Dihapus', data.message);
				setTimeout(function() {
					window.location.reload();
				}, 700);
			} else {
				errBox.textContent = data.message || 'Password salah atau gagal menghapus log.';
				errBox.style.display = 'block';
				document.getElementById('ulog_confirm_password').select();
			}
		})
		.catch(function() {
			btn.disabled = false;
			btn.innerHTML = '<i class="fa fa-trash"></i><span>Ya, Hapus Permanen</span>';
			errBox.textContent = 'Koneksi ke server terputus.';
			errBox.style.display = 'block';
		});
	});
</script>