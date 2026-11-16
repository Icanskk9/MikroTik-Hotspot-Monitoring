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

$incDir = is_dir('./include') ? './include/' : '../include/';
$sessRaw = isset($session) && $session !== '' ? $session : (isset($_GET['session']) ? $_GET['session'] : 'default');
$safeSession = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$sessRaw);
$hiddenStoreFile = $incDir . 'hlog_hidden_' . $safeSession . '.json';

if (!function_exists('getHiddenHotspotLogs')) {
	function getHiddenHotspotLogs($filePath) {
		if (!file_exists($filePath)) return array();
		$raw = @file_get_contents($filePath);
		$arr = json_decode($raw, true);
		return is_array($arr) ? $arr : array();
	}
}

if (!function_exists('saveHiddenHotspotLogs')) {
	function saveHiddenHotspotLogs($filePath, $map) {
		@file_put_contents($filePath, json_encode($map), LOCK_EX);
	}
}

// Handler AJAX Hapus / Bersihkan Hotspot Log dengan Verifikasi Password Login Mikhmon
if (isset($_POST['ajax_delete_hotspotlog'])) {
	echo "<!--JSON_START-->";

	$inputPass = isset($_POST['app_password']) ? (string)$_POST['app_password'] : '';
	$rawSigs   = isset($_POST['log_sigs']) ? trim((string)$_POST['log_sigs']) : '';

	if ($inputPass === '') {
		echo json_encode(array(
			'status'  => 'error',
			'message' => 'Password login aplikasi wajib diisi!'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	if (!isset($passadm) || !function_exists('decrypt')) {
		ob_start();
		if (file_exists($incDir . 'config.php')) {
			include($incDir . 'config.php');
			include($incDir . 'readcfg.php');
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

	$targetSigs = array_filter(array_map('trim', explode(',', $rawSigs)));
	if (empty($targetSigs)) {
		echo json_encode(array(
			'status'  => 'error',
			'message' => 'Tidak ada entri log yang dipilih untuk dihapus.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	$hiddenMap = getHiddenHotspotLogs($hiddenStoreFile);
	$addedCount = 0;
	foreach ($targetSigs as $sig) {
		if (preg_match('/^[a-f0-9]{32}$/', $sig)) {
			if (!isset($hiddenMap[$sig])) {
				$addedCount++;
			}
			$hiddenMap[$sig] = time();
		}
	}

	saveHiddenHotspotLogs($hiddenStoreFile, $hiddenMap);

	echo json_encode(array(
		'status'  => 'success',
		'deleted' => $addedCount,
		'message' => count($targetSigs) . ' entri Hotspot Log berhasil dihapus dari daftar.'
	));
	echo "<!--JSON_END-->";
	exit;
}

$getlog = $API->comm("/log/print", array(
	"?topics" => "hotspot,info,debug"
));
$log = is_array($getlog) ? array_reverse($getlog) : array();
$TotalReg = count($log);

$hiddenMap = getHiddenHotspotLogs($hiddenStoreFile);
$activeSigMap = array();

$parsedRows = array();
$uniqueUsers = array();
$loginSuccess = 0;
$logoutCount = 0;
$loginFailed = 0;

for ($i = 0; $i < $TotalReg; $i++) {
	$rawMsg = isset($log[$i]['message']) ? (string)$log[$i]['message'] : '';
	if (substr($rawMsg, 0, 2) !== "->") {
		continue;
	}

	$logId = isset($log[$i]['.id']) ? (string)$log[$i]['.id'] : (string)$i;
	$time  = isset($log[$i]['time']) ? (string)$log[$i]['time'] : '';
	$sig   = md5($logId . '|' . $time . '|' . $rawMsg);
	$activeSigMap[$sig] = true;

	// Lewati entri yang sudah dihapus admin
	if (isset($hiddenMap[$sig])) {
		continue;
	}

	$mess = explode(":", $rawMsg);
	if (count($mess) > 6) {
		$userCol = implode(":", array_slice($mess, 1, 6));
		$msgCol  = trim(str_replace("trying to", "", implode(" ", array_slice($mess, 7))));
	} else {
		$userCol = isset($mess[1]) ? $mess[1] : '';
		$msgCol  = trim(str_replace("trying to", "", implode(" ", array_slice($mess, 2))));
	}

	$userTrim = trim($userCol);
	$parenPos = strpos($userTrim, '(');
	if ($parenPos !== false) {
		$uName = trim(substr($userTrim, 0, $parenPos));
		$uIp   = trim(trim(substr($userTrim, $parenPos)), '()');
	} else {
		$uName = $userTrim;
		$uIp   = '';
	}

	if ($uName !== '') {
		$uniqueUsers[$uName] = true;
	}

	$statusKey   = 'other';
	$statusLabel = 'Aktivitas';
	if (stripos($msgCol, 'logged out') !== false) {
		$logoutCount++;
		$statusKey   = 'logout';
		$statusLabel = 'Logout';
	} elseif (stripos($msgCol, 'failed') !== false || stripos($msgCol, 'invalid') !== false) {
		$loginFailed++;
		$statusKey   = 'failed';
		$statusLabel = 'Gagal';
	} elseif (stripos($msgCol, 'log in') !== false || stripos($msgCol, 'logged in') !== false) {
		$loginSuccess++;
		$statusKey   = 'login';
		$statusLabel = 'Login';
	}

	$parsedRows[] = array(
		'sig'          => $sig,
		'time'         => $time,
		'user'         => $uName,
		'ip'           => $uIp,
		'status_key'   => $statusKey,
		'status_label' => $statusLabel,
		'message'      => $msgCol,
	);
}

// Bersihkan otomatis hash lama yang sudah tergeser keluar dari buffer MikroTik agar file tetap ringan
if (!empty($hiddenMap) && $TotalReg > 0) {
	$prunedMap = array();
	foreach ($hiddenMap as $hSig => $ts) {
		if (isset($activeSigMap[$hSig])) {
			$prunedMap[$hSig] = $ts;
		}
	}
	if (count($prunedMap) !== count($hiddenMap)) {
		saveHiddenHotspotLogs($hiddenStoreFile, $prunedMap);
	}
}

$validLogs = count($parsedRows);
$userList = array_keys($uniqueUsers);
sort($userList);
?>

<style>
	#mhm-log {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}
	#mhm-log *,
	#mhm-log *::before,
	#mhm-log *::after {
		box-sizing: border-box;
	}

	/* Top Bar */
	#mhm-log .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		background: #ffffff;
	}
	#mhm-log .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-log .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-log .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #52525b;
		background: #f4f4f5;
		border: 1px solid #e4e4e7;
		padding: 2px 9px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Action Buttons */
	#mhm-log .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-log .lx-btn {
		height: 34px;
		padding: 0 13px;
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
	#mhm-log .lx-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-log .lx-btn.is-danger {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626 !important;
	}
	#mhm-log .lx-btn.is-danger:hover {
		background: #fee2e2;
		border-color: #fca5a5;
		color: #b91c1c !important;
	}
	#mhm-log .lx-btn:disabled {
		opacity: 0.45;
		cursor: not-allowed;
		pointer-events: none;
	}

	/* Metric Strip */
	#mhm-log .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 768px) {
		#mhm-log .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-log .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
		cursor: pointer;
		transition: background-color 0.15s ease;
	}
	#mhm-log .lx-metric-item:hover {
		background: #f4f4f5;
	}
	#mhm-log .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 768px) {
		#mhm-log .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-log .lx-metric-item:nth-child(1),
		#mhm-log .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-log .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-log .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-log .lx-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		display: inline-block;
		flex-shrink: 0;
	}
	#mhm-log .lx-dot.is-green { background: #10b981; }
	#mhm-log .lx-dot.is-zinc { background: #71717a; }
	#mhm-log .lx-dot.is-red { background: #ef4444; }
	#mhm-log .lx-dot.is-blue { background: #0284c7; }

	/* Filter & Search Bar */
	#mhm-log .lx-filterbar {
		padding: 14px 24px;
		background: #ffffff;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
	}
	#mhm-log .lx-filter-group {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 8px;
	}
	#mhm-log .lx-select {
		height: 34px !important;
		padding: 0 11px !important;
		background: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		font-weight: 500 !important;
		color: #18181b !important;
		box-shadow: none !important;
		margin: 0 !important;
		cursor: pointer;
	}
	#mhm-log .lx-select:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		outline: none !important;
	}
	#mhm-log .lx-search {
		position: relative;
		width: 280px;
		max-width: 100%;
	}
	#mhm-log .lx-search i.fa-search {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-log #hlogSearchInput {
		width: 100% !important;
		height: 34px !important;
		padding: 0 30px 0 32px !important;
		background: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		color: #09090b !important;
		box-shadow: none !important;
		margin: 0 !important;
		float: none !important;
	}
	#mhm-log #hlogSearchInput:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-log .lx-search-clear {
		position: absolute;
		right: 8px;
		top: 50%;
		transform: translateY(-50%);
		border: none;
		background: #e4e4e7;
		color: #52525b;
		width: 18px;
		height: 18px;
		border-radius: 50%;
		font-size: 11px;
		line-height: 1;
		display: none;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		padding: 0;
	}
	#mhm-log .lx-search-clear:hover {
		background: #d4d4d8;
		color: #09090b;
	}

	/* Table */
	#mhm-log .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 66vh;
		background: #ffffff;
	}
	#mhm-log table.lx-table {
		display: table !important;
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		border: none !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-log table.lx-table thead th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #fafafa !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 600 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.05em !important;
		padding: 12px 22px !important;
		border-top: none !important;
		border-left: none !important;
		border-right: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		user-select: none;
	}
	#mhm-log table.lx-table tbody td {
		padding: 12px 22px !important;
		border-top: none !important;
		border-left: none !important;
		border-right: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
		background: transparent !important;
	}
	#mhm-log table.lx-table tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-log table.lx-table tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-log table.lx-table tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Cell Badges & Typography */
	#mhm-log .lx-time {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #52525b;
		font-variant-numeric: tabular-nums;
	}
	#mhm-log .lx-user-main {
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-log .lx-ip-pill {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 11.5px;
		color: #0369a1;
		background: #f0f9ff;
		border: 1px solid #bae6fd;
		padding: 1px 7px;
		border-radius: 5px;
		margin-left: 7px;
		cursor: pointer;
		display: inline-block;
	}
	#mhm-log .lx-ip-pill:hover {
		background: #e0f2fe;
	}
	#mhm-log .lx-status-pill {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 2px 9px;
		border-radius: 6px;
		font-size: 11px;
		font-weight: 600;
	}
	#mhm-log .lx-status-pill.is-login {
		background: #f0fdf4;
		color: #15803d;
		border: 1px solid #bbf7d0;
	}
	#mhm-log .lx-status-pill.is-logout {
		background: #f4f4f5;
		color: #52525b;
		border: 1px solid #e4e4e7;
	}
	#mhm-log .lx-status-pill.is-failed {
		background: #fef2f2;
		color: #dc2626;
		border: 1px solid #fecaca;
	}
	#mhm-log .lx-status-pill.is-other {
		background: #f0f9ff;
		color: #0369a1;
		border: 1px solid #bae6fd;
	}
	#mhm-log .lx-msg-text {
		font-size: 13px;
		color: #27272a;
	}
	#mhm-log .lx-msg-text.is-failed {
		color: #b91c1c;
		font-weight: 500;
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
	#mhm-log .lx-empty-state {
		padding: 46px 24px;
		text-align: center;
		color: #71717a;
		font-size: 13px;
	}

	/* Footer */
	#mhm-log .lx-footer {
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
	#mhm-log .lx-legend {
		display: flex;
		align-items: center;
		gap: 14px;
		flex-wrap: wrap;
	}
	#mhm-log .lx-legend-item {
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}

	/* Modal Alert & Verifikasi Password */
	#hlog-modal-backdrop {
		position: fixed;
		inset: 0;
		background: rgba(9, 9, 11, 0.52);
		backdrop-filter: blur(2px);
		z-index: 99998;
		display: none;
		align-items: center;
		justify-content: center;
		padding: 16px;
	}
	#hlog-modal-backdrop.is-open {
		display: flex;
	}
	.hlog-modal-card {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 14px;
		width: 100%;
		max-width: 430px;
		padding: 22px;
		box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.28);
		color: #18181b;
	}
	.hlog-alert-box {
		background: #fef2f2;
		border: 1px solid #fecaca;
		border-left: 4px solid #dc2626;
		border-radius: 8px;
		padding: 10px 12px;
		font-size: 12px;
		color: #991b1b;
		margin-bottom: 12px;
		line-height: 1.5;
	}
	.hlog-pass-wrap {
		position: relative;
		margin-top: 6px;
	}
	.hlog-pass-input {
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
	.hlog-pass-input:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		outline: none !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08) !important;
	}
	.hlog-pass-eye {
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
	#hlog-toast-box {
		position: fixed;
		top: 68px;
		right: 22px;
		z-index: 99999;
		display: flex;
		flex-direction: column;
		gap: 8px;
		pointer-events: none;
	}
	.hlog-toast {
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
	.hlog-toast.is-error {
		border-left-color: #dc2626;
	}
</style>

<div id="hlog-toast-box"></div>

<!-- Modal Alert & Verifikasi Password Hapus Hotspot Log -->
<div id="hlog-modal-backdrop">
	<div class="hlog-modal-card" role="dialog" aria-modal="true">
		<div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
			<div style="width:40px; height:40px; border-radius:10px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0;">
				<i class="fa fa-exclamation-triangle"></i>
			</div>
			<div>
				<h4 style="margin:0; font-size:15px; font-weight:600; color:#09090b;">Konfirmasi Hapus Hotspot Log</h4>
				<div style="font-size:11.5px; color:#71717a;">Otorisasi keamanan aplikasi diperlukan</div>
			</div>
		</div>

		<div class="hlog-alert-box">
			<strong>Peringatan:</strong> Entri Hotspot Log yang dihapus akan dibersihkan dari daftar tampilan secara permanen.
		</div>

		<div id="hlog_target_info" style="background:#fafafa; border:1px solid #e4e4e7; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12.5px; color:#18181b;">
			-
		</div>

		<div style="margin-bottom:14px;">
			<label for="hlog_confirm_password" style="display:block; font-size:12px; font-weight:600; color:#18181b;">
				Masukkan Password Login Aplikasi
			</label>
			<div class="hlog-pass-wrap">
				<input type="password" id="hlog_confirm_password" class="hlog-pass-input" placeholder="Ketik password login Mikhmon..." autocomplete="current-password">
				<button type="button" id="hlog_toggle_pass" class="hlog-pass-eye" title="Tampilkan / Sembunyikan Password">
					<i class="fa fa-eye"></i>
				</button>
			</div>
			<div id="hlog_modal_error" style="display:none; margin-top:6px; font-size:12px; color:#dc2626; font-weight:500;"></div>
		</div>

		<div style="display:flex; justify-content:flex-end; gap:8px;">
			<button type="button" id="btn_hlog_cancel" style="height:36px; padding:0 15px; border-radius:8px; border:1px solid #e4e4e7; background:#ffffff; color:#18181b; font-size:12.5px; font-weight:500; cursor:pointer;">
				Batal
			</button>
			<button type="button" id="btn_hlog_confirm" style="height:36px; padding:0 15px; border-radius:8px; border:1px solid #dc2626; background:#dc2626; color:#ffffff; font-size:12.5px; font-weight:500; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
				<i class="fa fa-trash"></i>
				<span>Ya, Hapus Log</span>
			</button>
		</div>
	</div>
</div>

<div id="mhm-log">
	<!-- Top Bar -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title"><?= $_hotspot_log ?></h3>
			<span class="lx-count" id="hlog_badge_count">
				<?= $validLogs; ?> dari <?= $validLogs; ?> entries
			</span>
		</div>

		<div class="lx-actions">
			<button type="button" onclick="exportHotspotLogCSV();" class="lx-btn" title="Unduh log yang tampil ke file CSV">
				<i class="fa fa-download" style="color:#71717a;"></i>
				<span>CSV</span>
			</button>

			<button type="button" onclick="location.reload();" class="lx-btn" title="Muat ulang data log terbaru">
				<i class="fa fa-refresh" style="color:#71717a;"></i>
				<span>Refresh</span>
			</button>

			<button type="button" id="btn_clear_hlog" onclick="openBulkHlogModal();" class="lx-btn is-danger" <?= ($validLogs == 0) ? 'disabled' : ''; ?> title="Hapus semua log yang tampil saat ini">
				<i class="fa fa-trash"></i>
				<span id="btn_clear_hlog_label">Bersihkan Log (<?= $validLogs; ?>)</span>
			</button>
		</div>
	</div>

	<!-- Metric Strip (klik untuk filter cepat) -->
	<div class="lx-metrics">
		<div class="lx-metric-item" onclick="quickFilterStatus('all');" title="Tampilkan semua aktivitas">
			<div class="lx-metric-label">Total Aktivitas</div>
			<div class="lx-metric-val"><?= $validLogs; ?></div>
		</div>
		<div class="lx-metric-item" onclick="quickFilterStatus('login');" title="Filter hanya Login Berhasil">
			<div class="lx-metric-label">Login Berhasil</div>
			<div class="lx-metric-val">
				<span class="lx-dot is-green"></span>
				<span><?= $loginSuccess; ?></span>
			</div>
		</div>
		<div class="lx-metric-item" onclick="quickFilterStatus('logout');" title="Filter hanya Logout">
			<div class="lx-metric-label">Logout</div>
			<div class="lx-metric-val">
				<span class="lx-dot is-zinc"></span>
				<span><?= $logoutCount; ?></span>
			</div>
		</div>
		<div class="lx-metric-item" onclick="quickFilterStatus('failed');" title="Filter hanya Gagal Autentikasi">
			<div class="lx-metric-label">Gagal Autentikasi</div>
			<div class="lx-metric-val">
				<span class="lx-dot is-red"></span>
				<span><?= $loginFailed; ?></span>
			</div>
		</div>
	</div>

	<!-- Filter & Search Toolbar -->
	<div class="lx-filterbar">
		<div class="lx-filter-group">
			<select id="hlogStatusFilter" class="lx-select" onchange="applyHotspotLogFilter();" title="Filter berdasarkan status aktivitas">
				<option value="all">Semua Status (<?= $validLogs; ?>)</option>
				<option value="login">Login Berhasil (<?= $loginSuccess; ?>)</option>
				<option value="logout">Logout (<?= $logoutCount; ?>)</option>
				<option value="failed">Gagal Autentikasi (<?= $loginFailed; ?>)</option>
			</select>

			<select id="hlogUserFilter" class="lx-select" onchange="applyHotspotLogFilter();" title="Filter berdasarkan username/MAC">
				<option value="all">Semua User (<?= count($userList); ?>)</option>
				<?php foreach ($userList as $usr): ?>
					<option value="<?= htmlspecialchars(strtolower($usr), ENT_QUOTES); ?>"><?= htmlspecialchars($usr); ?></option>
				<?php endforeach; ?>
			</select>

			<button type="button" id="btnResetHlogFilter" class="lx-btn" onclick="resetHotspotLogFilter();" style="display:none; height:34px; padding:0 10px; font-size:12px;">
				<i class="fa fa-times" style="color:#71717a;"></i>
				<span>Reset Filter</span>
			</button>
		</div>

		<div class="lx-search">
			<i class="fa fa-search"></i>
			<input id="hlogSearchInput" type="text" class="form-control" placeholder="Cari waktu, user, IP, atau pesan..." oninput="applyHotspotLogFilter();">
			<button type="button" id="btnClearSearchHlog" class="lx-search-clear" onclick="clearSearchInputHlog();" title="Hapus teks pencarian">&times;</button>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table class="table lx-table" id="hotspotLogTable">
			<thead>
				<tr>
					<th style="width:130px;"><?= $_time ?></th>
					<th style="width:270px;"><?= $_users ?> (IP)</th>
					<th style="width:130px;">Status</th>
					<th><?= $_messages ?></th>
					<th style="width:100px; text-align:right;">Aksi</th>
				</tr>
			</thead>
			<tbody id="hlogTableBody">
				<?php if ($validLogs > 0): ?>
					<?php foreach ($parsedRows as $r): ?>
						<?php
						$pillClass = 'is-other';
						$dotClass  = 'is-blue';
						$msgClass  = '';
						if ($r['status_key'] === 'login') {
							$pillClass = 'is-login';
							$dotClass  = 'is-green';
						} elseif ($r['status_key'] === 'logout') {
							$pillClass = 'is-logout';
							$dotClass  = 'is-zinc';
						} elseif ($r['status_key'] === 'failed') {
							$pillClass = 'is-failed';
							$dotClass  = 'is-red';
							$msgClass  = 'is-failed';
						}
						$searchHay = strtolower($r['time'] . ' ' . $r['user'] . ' ' . $r['ip'] . ' ' . $r['status_label'] . ' ' . $r['message']);
						?>
						<tr class="hlog-row"
							data-sig="<?= htmlspecialchars($r['sig'], ENT_QUOTES); ?>"
							data-time="<?= htmlspecialchars($r['time'], ENT_QUOTES); ?>"
							data-user-raw="<?= htmlspecialchars($r['user'], ENT_QUOTES); ?>"
							data-ip="<?= htmlspecialchars($r['ip'], ENT_QUOTES); ?>"
							data-status="<?= htmlspecialchars($r['status_key'], ENT_QUOTES); ?>"
							data-user="<?= htmlspecialchars(strtolower($r['user']), ENT_QUOTES); ?>"
							data-search="<?= htmlspecialchars($searchHay, ENT_QUOTES); ?>">
							<td><span class="lx-time"><?= htmlspecialchars($r['time']); ?></span></td>
							<td>
								<span class="lx-user-main"><?= htmlspecialchars($r['user']); ?></span>
								<?php if ($r['ip'] !== ''): ?>
									<span class="lx-ip-pill" title="Klik untuk salin IP" onclick="copyHlogIp('<?= htmlspecialchars($r['ip'], ENT_QUOTES); ?>');"><?= htmlspecialchars($r['ip']); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<span class="lx-status-pill <?= $pillClass; ?>">
									<span class="lx-dot <?= $dotClass; ?>"></span>
									<span><?= htmlspecialchars($r['status_label']); ?></span>
								</span>
							</td>
							<td>
								<span class="lx-msg-text <?= $msgClass; ?>"><?= htmlspecialchars($r['message']); ?></span>
							</td>
							<td style="text-align:right;">
								<button type="button" class="lx-row-del-btn" onclick="openSingleHlogModal(this);" title="Hapus baris log ini">
									<i class="fa fa-trash"></i> Hapus
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<div id="hlogEmptyState" class="lx-empty-state" style="<?= ($validLogs > 0) ? 'display:none;' : ''; ?>">
			<?= ($validLogs > 0) ? 'Tidak ada aktivitas Hotspot Log yang cocok dengan filter atau pencarian saat ini.' : 'Belum ada riwayat aktivitas pada Hotspot Log.'; ?>
		</div>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div class="lx-legend">
			<span class="lx-legend-item"><span class="lx-dot is-green"></span> Login</span>
			<span class="lx-legend-item"><span class="lx-dot is-zinc"></span> Logout</span>
			<span class="lx-legend-item"><span class="lx-dot is-red"></span> Gagal</span>
			<span>&bull; Klik IP untuk menyalin cepat</span>
		</div>
		<div>Menampilkan aktivitas terbaru di bagian atas</div>
	</div>
</div>

<script>
	var totalHlogCount = <?= (int)$validLogs; ?>;
	var sessionHlogName = <?= json_encode($session); ?>;
	var pendingHlogSigs = [];

	function showHlogToast(type, title, msg) {
		var box = document.getElementById('hlog-toast-box');
		if (!box) return;
		var isOk = (type === 'success');
		var el = document.createElement('div');
		el.className = 'hlog-toast ' + (isOk ? '' : 'is-error');
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
		}, 3500);
	}

	function copyHlogIp(ip) {
		if (!ip) return;
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(ip);
		} else {
			var ta = document.createElement('textarea');
			ta.value = ip;
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
		}
		showHlogToast('success', 'IP Tersalin', 'Alamat IP ' + ip + ' disalin ke clipboard.');
	}

	function quickFilterStatus(status) {
		var sel = document.getElementById('hlogStatusFilter');
		if (sel) {
			sel.value = status;
			applyHotspotLogFilter();
		}
	}

	function clearSearchInputHlog() {
		var inp = document.getElementById('hlogSearchInput');
		if (inp) {
			inp.value = '';
			applyHotspotLogFilter();
			inp.focus();
		}
	}

	function resetHotspotLogFilter() {
		document.getElementById('hlogStatusFilter').value = 'all';
		document.getElementById('hlogUserFilter').value = 'all';
		document.getElementById('hlogSearchInput').value = '';
		applyHotspotLogFilter();
	}

	function getVisibleHlogRows() {
		var rows = document.querySelectorAll('#hlogTableBody tr.hlog-row');
		var visible = [];
		for (var i = 0; i < rows.length; i++) {
			if (rows[i].style.display !== 'none') {
				visible.push(rows[i]);
			}
		}
		return visible;
	}

	function applyHotspotLogFilter() {
		var statusVal = document.getElementById('hlogStatusFilter').value;
		var userVal   = document.getElementById('hlogUserFilter').value;
		var searchVal = (document.getElementById('hlogSearchInput').value || '').toLowerCase().trim();

		var clearBtn = document.getElementById('btnClearSearchHlog');
		if (clearBtn) {
			clearBtn.style.display = (searchVal.length > 0) ? 'inline-flex' : 'none';
		}

		var resetBtn = document.getElementById('btnResetHlogFilter');
		if (resetBtn) {
			var isFiltered = (statusVal !== 'all' || userVal !== 'all' || searchVal !== '');
			resetBtn.style.display = isFiltered ? 'inline-flex' : 'none';
		}

		var rows = document.querySelectorAll('#hlogTableBody tr.hlog-row');
		var visibleCount = 0;

		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var rStatus = r.getAttribute('data-status') || '';
			var rUser   = r.getAttribute('data-user') || '';
			var rSearch = r.getAttribute('data-search') || '';

			var matchStatus = (statusVal === 'all' || rStatus === statusVal);
			var matchUser   = (userVal === 'all' || rUser === userVal);
			var matchSearch = (searchVal === '' || rSearch.indexOf(searchVal) !== -1);

			if (matchStatus && matchUser && matchSearch) {
				r.style.display = '';
				visibleCount++;
			} else {
				r.style.display = 'none';
			}
		}

		var badge = document.getElementById('hlog_badge_count');
		if (badge) {
			badge.textContent = visibleCount + ' dari ' + totalHlogCount + ' entries';
		}

		var btnClear = document.getElementById('btn_clear_hlog');
		var lblClear = document.getElementById('btn_clear_hlog_label');
		if (lblClear) {
			lblClear.textContent = 'Bersihkan Log (' + visibleCount + ')';
		}
		if (btnClear) {
			btnClear.disabled = (visibleCount === 0);
		}

		var emptyBox = document.getElementById('hlogEmptyState');
		if (emptyBox) {
			emptyBox.style.display = (visibleCount === 0) ? 'block' : 'none';
		}
	}

	function exportHotspotLogCSV() {
		var rows = getVisibleHlogRows();
		var csv = ['"Waktu","Pengguna","IP Address","Status","Aktivitas / Pesan"'];

		for (var i = 0; i < rows.length; i++) {
			var tds = rows[i].querySelectorAll('td');
			var waktu = tds[0] ? tds[0].innerText.trim() : '';
			var userEl = tds[1] ? tds[1].querySelector('.lx-user-main') : null;
			var ipEl   = tds[1] ? tds[1].querySelector('.lx-ip-pill') : null;
			var user   = userEl ? userEl.innerText.trim() : '';
			var ip     = ipEl ? ipEl.innerText.trim() : '-';
			var status = tds[2] ? tds[2].innerText.trim() : '';
			var pesan  = tds[3] ? tds[3].innerText.trim() : '';

			csv.push([waktu, user, ip, status, pesan].map(function(v) {
				return '"' + String(v).replace(/"/g, '""') + '"';
			}).join(','));
		}

		if (rows.length === 0) {
			showHlogToast('error', 'Data Kosong', 'Tidak ada baris log yang tampil untuk diekspor.');
			return;
		}

		var blob = new Blob(["\uFEFF" + csv.join("\r\n")], { type: "text/csv;charset=utf-8;" });
		var a = document.createElement("a");
		a.href = URL.createObjectURL(blob);
		a.download = "hotspot-log-" + sessionHlogName + ".csv";
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
	}

	function openSingleHlogModal(btn) {
		var tr = btn.closest('tr.hlog-row');
		if (!tr) return;
		var sig  = tr.getAttribute('data-sig');
		var time = tr.getAttribute('data-time');
		var user = tr.getAttribute('data-user-raw');
		var ip   = tr.getAttribute('data-ip');

		if (!sig) return;
		pendingHlogSigs = [sig];

		document.getElementById('hlog_target_info').innerHTML =
			'<div style="font-weight:600; color:#09090b; margin-bottom:2px;">1 Entri Log Akan Dihapus:</div>' +
			'<div style="font-family:monospace; font-size:12px; color:#52525b;">User: <strong>' + user + '</strong>' + (ip ? ' (' + ip + ')' : '') + ' &bull; ' + time + '</div>';

		showHlogModal();
	}

	function openBulkHlogModal() {
		var visible = getVisibleHlogRows();
		if (visible.length === 0) return;

		var sigs = [];
		for (var i = 0; i < visible.length; i++) {
			var s = visible[i].getAttribute('data-sig');
			if (s) sigs.push(s);
		}
		if (sigs.length === 0) return;

		pendingHlogSigs = sigs;
		document.getElementById('hlog_target_info').innerHTML =
			'<div style="font-weight:600; color:#dc2626; margin-bottom:2px;">Bersihkan Massal (' + sigs.length + ' Entri Log):</div>' +
			'<div style="font-size:12px; color:#52525b;">Seluruh entri Hotspot Log yang sedang tampil saat ini akan dihapus dari daftar.</div>';

		showHlogModal();
	}

	function showHlogModal() {
		var passInput = document.getElementById('hlog_confirm_password');
		var errBox = document.getElementById('hlog_modal_error');
		passInput.value = '';
		passInput.type = 'password';
		document.getElementById('hlog_toggle_pass').innerHTML = '<i class="fa fa-eye"></i>';
		errBox.style.display = 'none';
		errBox.textContent = '';

		document.getElementById('hlog-modal-backdrop').classList.add('is-open');
		setTimeout(function() {
			passInput.focus();
		}, 80);
	}

	function closeHlogModal() {
		document.getElementById('hlog-modal-backdrop').classList.remove('is-open');
		pendingHlogSigs = [];
	}

	document.getElementById('btn_hlog_cancel').addEventListener('click', closeHlogModal);
	document.getElementById('hlog-modal-backdrop').addEventListener('click', function(e) {
		if (e.target === this) closeHlogModal();
	});

	document.getElementById('hlog_toggle_pass').addEventListener('click', function() {
		var inp = document.getElementById('hlog_confirm_password');
		if (inp.type === 'password') {
			inp.type = 'text';
			this.innerHTML = '<i class="fa fa-eye-slash"></i>';
		} else {
			inp.type = 'password';
			this.innerHTML = '<i class="fa fa-eye"></i>';
		}
	});

	document.getElementById('hlog_confirm_password').addEventListener('keydown', function(e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			document.getElementById('btn_hlog_confirm').click();
		}
	});

	document.getElementById('btn_hlog_confirm').addEventListener('click', function() {
		var passVal = document.getElementById('hlog_confirm_password').value;
		var errBox  = document.getElementById('hlog_modal_error');
		var btn     = this;

		if (!passVal) {
			errBox.textContent = 'Silakan masukkan password login aplikasi terlebih dahulu.';
			errBox.style.display = 'block';
			document.getElementById('hlog_confirm_password').focus();
			return;
		}

		if (pendingHlogSigs.length === 0) return;

		btn.disabled = true;
		btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Menghapus...</span>';
		errBox.style.display = 'none';

		var formData = new URLSearchParams();
		formData.append('ajax_delete_hotspotlog', '1');
		formData.append('app_password', passVal);
		formData.append('log_sigs', pendingHlogSigs.join(','));

		fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData.toString()
		})
		.then(function(res) { return res.text(); })
		.then(function(raw) {
			btn.disabled = false;
			btn.innerHTML = '<i class="fa fa-trash"></i><span>Ya, Hapus Log</span>';

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
				closeHlogModal();
				showHlogToast('success', 'Berhasil Dihapus', data.message);
				setTimeout(function() {
					window.location.reload();
				}, 650);
			} else {
				errBox.textContent = data.message || 'Password salah atau gagal menghapus log.';
				errBox.style.display = 'block';
				document.getElementById('hlog_confirm_password').select();
			}
		})
		.catch(function() {
			btn.disabled = false;
			btn.innerHTML = '<i class="fa fa-trash"></i><span>Ya, Hapus Log</span>';
			errBox.textContent = 'Koneksi ke server terputus.';
			errBox.style.display = 'block';
		});
	});
</script>