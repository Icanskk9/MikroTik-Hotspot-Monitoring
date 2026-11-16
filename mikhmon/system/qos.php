<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *  Custom Module: Manajemen Bandwidth & QoS Ruangan (Simple Queue API)
 */
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
	exit;
}

if (!function_exists('qosGetTrapError')) {
	function qosGetTrapError($res) {
		if (is_array($res) && isset($res['!trap'][0]['message'])) {
			return (string)$res['!trap'][0]['message'];
		}
		return '';
	}
}

if (!function_exists('qosFormatRate')) {
	function qosFormatRate($bps) {
		$v = (float)$bps;
		if ($v <= 0) return '0 bps';
		if ($v >= 1000000000) return round($v / 1000000000, 2) . ' Gbps';
		if ($v >= 1000000)    return round($v / 1000000, 2) . ' Mbps';
		if ($v >= 1000)       return round($v / 1000, 1) . ' Kbps';
		return round($v) . ' bps';
	}
}

if (!function_exists('qosFormatBytes')) {
	function qosFormatBytes($bytes) {
		$v = (float)$bytes;
		if ($v <= 0) return '0 B';
		if ($v >= 1099511627776) return round($v / 1099511627776, 2) . ' TB';
		if ($v >= 1073741824)    return round($v / 1073741824, 2) . ' GB';
		if ($v >= 1048576)       return round($v / 1048576, 1) . ' MB';
		if ($v >= 1024)          return round($v / 1024, 1) . ' KB';
		return round($v) . ' B';
	}
}

if (!function_exists('qosFormatInputUnit')) {
	function qosFormatInputUnit($bps) {
		$v = (float)$bps;
		if ($v <= 0) return '0';
		if ($v >= 1000000000 && fmod($v, 1000000000) == 0) return ($v / 1000000000) . 'G';
		if ($v >= 1000000) {
			$m = round($v / 1000000, 2);
			return rtrim(rtrim((string)$m, '0'), '.') . 'M';
		}
		if ($v >= 1000) {
			$k = round($v / 1000, 1);
			return rtrim(rtrim((string)$k, '0'), '.') . 'K';
		}
		return (string)round($v);
	}
}

if (!function_exists('qosFormatLimitLabel')) {
	function qosFormatLimitLabel($rawLimit) {
		$s = trim((string)$rawLimit);
		if ($s === '' || $s === '0/0') return 'Unlimited';
		$parts = explode('/', $s);
		if (count($parts) === 2) {
			$up   = ($parts[0] === '0' || $parts[0] === '') ? 'Unlim' : qosFormatRate((float)$parts[0]);
			$down = ($parts[1] === '0' || $parts[1] === '') ? 'Unlim' : qosFormatRate((float)$parts[1]);
			return $up . ' / ' . $down;
		}
		return $s;
	}
}

if (!function_exists('qosNormalizeSpeed')) {
	function qosNormalizeSpeed($val) {
		$s = strtoupper(trim((string)$val));
		if ($s === '' || $s === '0') return '0';
		if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $s)) {
			return $s . 'M';
		}
		if (preg_match('/^[0-9]+(\.[0-9]+)?[KMG]$/', $s)) {
			return $s;
		}
		return false;
	}
}

if (!function_exists('qosSanitizeTarget')) {
	function qosSanitizeTarget($raw) {
		$parts = array_filter(array_map('trim', explode(',', (string)$raw)));
		if (empty($parts)) return false;
		$valid = array();
		foreach ($parts as $p) {
			if (preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}(\/[0-9]{1,2})?$/', $p)) {
				$valid[] = $p;
			} elseif (preg_match('/^[a-zA-Z0-9._-]+$/', $p)) {
				$valid[] = $p;
			} else {
				return false;
			}
		}
		return implode(',', $valid);
	}
}

// Pastikan koneksi API aktif
$isConnected = (isset($API) && is_object($API));
if ($isConnected && isset($API->connected) && !$API->connected) {
	$isConnected = $API->connect($iphost, $userhost, decrypt($passwdhost));
}

// Handler AJAX API MikroTik
if (isset($_POST['ajax_qos'])) {
	echo "<!--JSON_START-->";

	if (!$isConnected) {
		echo json_encode(array('status' => 'error', 'message' => 'Gagal terhubung ke MikroTik API.'));
		echo "<!--JSON_END-->";
		exit;
	}

	$act = isset($_POST['act']) ? (string)$_POST['act'] : '';

	// 1. Live Polling Trafik & Utilisasi Simple Queue
	if ($act === 'poll_live') {
		$queues = $API->comm("/queue/simple/print");
		$items = array();
		$totalUp = 0;
		$totalDown = 0;

		if (is_array($queues)) {
			foreach ($queues as $q) {
				$id = isset($q['.id']) ? (string)$q['.id'] : '';
				$rateRaw = isset($q['rate']) ? (string)$q['rate'] : '0/0';
				$rParts = explode('/', $rateRaw);
				$upBps   = isset($rParts[0]) ? (float)$rParts[0] : 0;
				$downBps = isset($rParts[1]) ? (float)$rParts[1] : 0;

				$mlRaw   = isset($q['max-limit']) ? (string)$q['max-limit'] : '0/0';
				$mlParts = explode('/', $mlRaw);
				$maxUp   = isset($mlParts[0]) ? (float)$mlParts[0] : 0;
				$maxDown = isset($mlParts[1]) ? (float)$mlParts[1] : 0;

				$upPct   = ($maxUp > 0) ? min(100, round(($upBps / $maxUp) * 100)) : ($upBps > 0 ? 12 : 0);
				$downPct = ($maxDown > 0) ? min(100, round(($downBps / $maxDown) * 100)) : ($downBps > 0 ? 12 : 0);

				$bytesRaw = isset($q['bytes']) ? (string)$q['bytes'] : '0/0';
				$bParts   = explode('/', $bytesRaw);
				$upBytes  = isset($bParts[0]) ? (float)$bParts[0] : 0;
				$dnBytes  = isset($bParts[1]) ? (float)$bParts[1] : 0;

				$isDisabled = (isset($q['disabled']) && ($q['disabled'] === 'true' || $q['disabled'] === true));
				if (!$isDisabled) {
					$totalUp   += $upBps;
					$totalDown += $downBps;
				}

				$items[$id] = array(
					'up_bps'       => $upBps,
					'down_bps'     => $downBps,
					'up_fmt'       => qosFormatRate($upBps),
					'down_fmt'     => qosFormatRate($downBps),
					'up_pct'       => $upPct,
					'down_pct'     => $downPct,
					'up_bytes_fmt' => qosFormatBytes($upBytes),
					'dn_bytes_fmt' => qosFormatBytes($dnBytes),
					'disabled'     => $isDisabled
				);
			}
		}

		echo json_encode(array(
			'status'         => 'success',
			'total_up_fmt'   => qosFormatRate($totalUp),
			'total_down_fmt' => qosFormatRate($totalDown),
			'queues'         => $items
		));
		echo "<!--JSON_END-->";
		exit;
	}

	// 2. Tambah Simple Queue Ruangan / Subnet Baru
	if ($act === 'add_queue') {
		$name      = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
		$target    = qosSanitizeTarget(isset($_POST['target']) ? $_POST['target'] : '');
		$upRaw     = qosNormalizeSpeed(isset($_POST['max_up']) ? $_POST['max_up'] : '0');
		$downRaw   = qosNormalizeSpeed(isset($_POST['max_down']) ? $_POST['max_down'] : '0');
		$burstUp   = qosNormalizeSpeed(isset($_POST['burst_up']) ? $_POST['burst_up'] : '0');
		$burstDown = qosNormalizeSpeed(isset($_POST['burst_down']) ? $_POST['burst_down'] : '0');
		$burstTime = trim((string)(isset($_POST['burst_time']) ? $_POST['burst_time'] : '8s'));
		$priority  = isset($_POST['priority']) ? (int)$_POST['priority'] : 8;
		$qType     = trim((string)(isset($_POST['queue_type']) ? $_POST['queue_type'] : 'default-small'));
		$roomTag   = isset($_POST['room_tag']) ? trim((string)$_POST['room_tag']) : 'ruangan';

		if ($name === '') {
			echo json_encode(array('status' => 'error', 'message' => 'Nama aturan / ruangan wajib diisi.'));
			echo "<!--JSON_END-->";
			exit;
		}
		if ($target === false) {
			echo json_encode(array('status' => 'error', 'message' => 'Target IP / Subnet / Interface tidak valid (contoh: 192.168.10.0/24).'));
			echo "<!--JSON_END-->";
			exit;
		}
		if ($upRaw === false || $downRaw === false || $burstUp === false || $burstDown === false) {
			echo json_encode(array('status' => 'error', 'message' => 'Format kecepatan tidak valid. Gunakan angka atau akhiran K/M/G (contoh: 20M atau 512K).'));
			echo "<!--JSON_END-->";
			exit;
		}
		if ($priority < 1 || $priority > 8) {
			$priority = 8;
		}

		$maxLimit = $upRaw . '/' . $downRaw;
		$prioStr  = $priority . '/' . $priority;
		$comment  = 'mikhmon-qos:' . preg_replace('/[^a-z0-9_-]/i', '', $roomTag);

		$params = array(
			'name'      => $name,
			'target'    => $target,
			'max-limit' => $maxLimit,
			'priority'  => $prioStr,
			'comment'   => $comment
		);

		if ($qType === 'pcq') {
			$params['queue'] = 'pcq-upload-default/pcq-download-default';
		} elseif ($qType !== '' && $qType !== 'default-small') {
			$params['queue'] = $qType . '/' . $qType;
		}

		if ($burstUp !== '0' || $burstDown !== '0') {
			if (!preg_match('/^[0-9]+s?$/i', $burstTime)) {
				$burstTime = '8s';
			}
			if (substr(strtolower($burstTime), -1) !== 's') {
				$burstTime .= 's';
			}
			$params['burst-limit']     = $burstUp . '/' . $burstDown;
			$params['burst-threshold'] = $maxLimit;
			$params['burst-time']      = $burstTime . '/' . $burstTime;
		}

		$res = $API->comm('/queue/simple/add', $params);
		$err = qosGetTrapError($res);
		if ($err !== '') {
			echo json_encode(array('status' => 'error', 'message' => 'MikroTik menolak Simple Queue: ' . $err));
			echo "<!--JSON_END-->";
			exit;
		}

		echo json_encode(array('status' => 'success', 'message' => 'Limit bandwidth "' . $name . '" berhasil ditambahkan!'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 3. Edit Limit & Prioritas Queue yang Sudah Ada
	if ($act === 'edit_queue') {
		$id        = trim((string)(isset($_POST['id']) ? $_POST['id'] : ''));
		$name      = trim((string)(isset($_POST['name']) ? $_POST['name'] : ''));
		$target    = qosSanitizeTarget(isset($_POST['target']) ? $_POST['target'] : '');
		$upRaw     = qosNormalizeSpeed(isset($_POST['max_up']) ? $_POST['max_up'] : '0');
		$downRaw   = qosNormalizeSpeed(isset($_POST['max_down']) ? $_POST['max_down'] : '0');
		$burstUp   = qosNormalizeSpeed(isset($_POST['burst_up']) ? $_POST['burst_up'] : '0');
		$burstDown = qosNormalizeSpeed(isset($_POST['burst_down']) ? $_POST['burst_down'] : '0');
		$priority  = isset($_POST['priority']) ? (int)$_POST['priority'] : 8;
		$qType     = trim((string)(isset($_POST['queue_type']) ? $_POST['queue_type'] : 'keep'));

		if ($id === '' || $name === '' || $target === false || $upRaw === false || $downRaw === false || $burstUp === false || $burstDown === false) {
			echo json_encode(array('status' => 'error', 'message' => 'Data perubahan tidak lengkap atau format salah.'));
			echo "<!--JSON_END-->";
			exit;
		}
		if ($priority < 1 || $priority > 8) $priority = 8;

		$maxLimit = $upRaw . '/' . $downRaw;
		$setParams = array(
			'.id'       => $id,
			'name'      => $name,
			'target'    => $target,
			'max-limit' => $maxLimit,
			'priority'  => $priority . '/' . $priority
		);

		if ($burstUp !== '0' || $burstDown !== '0') {
			$setParams['burst-limit']     = $burstUp . '/' . $burstDown;
			$setParams['burst-threshold'] = $maxLimit;
			$setParams['burst-time']      = '8s/8s';
		} else {
			$setParams['burst-limit']     = '0/0';
			$setParams['burst-threshold'] = '0/0';
			$setParams['burst-time']      = '0s/0s';
		}

		if ($qType === 'pcq') {
			$setParams['queue'] = 'pcq-upload-default/pcq-download-default';
		} elseif ($qType === 'default-small') {
			$setParams['queue'] = 'default-small/default-small';
		}

		$res = $API->comm('/queue/simple/set', $setParams);
		$err = qosGetTrapError($res);
		if ($err !== '') {
			echo json_encode(array('status' => 'error', 'message' => 'Gagal menyimpan perubahan: ' . $err));
			echo "<!--JSON_END-->";
			exit;
		}

		echo json_encode(array('status' => 'success', 'message' => 'Konfigurasi bandwidth "' . $name . '" berhasil diperbarui.'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 4. Ubah Urutan Rule Simple Queue (Move Up / Down)
	if ($act === 'move_queue') {
		$id   = trim((string)(isset($_POST['id']) ? $_POST['id'] : ''));
		$dest = trim((string)(isset($_POST['dest']) ? $_POST['dest'] : ''));
		if ($id === '' || $dest === '') {
			echo json_encode(array('status' => 'error', 'message' => 'Parameter urutan queue tidak valid.'));
			echo "<!--JSON_END-->";
			exit;
		}
		$res = $API->comm('/queue/simple/move', array(
			'numbers'     => $id,
			'destination' => $dest
		));
		$err = qosGetTrapError($res);
		if ($err !== '') {
			echo json_encode(array('status' => 'error', 'message' => 'Gagal memindahkan urutan: ' . $err));
			echo "<!--JSON_END-->";
			exit;
		}
		echo json_encode(array('status' => 'success', 'message' => 'Urutan prioritas Simple Queue diperbarui.'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 5. Toggle Enable / Disable Queue
	if ($act === 'toggle_queue') {
		$id     = trim((string)(isset($_POST['id']) ? $_POST['id'] : ''));
		$target = isset($_POST['target']) ? (string)$_POST['target'] : 'disable';
		if ($id === '') {
			echo json_encode(array('status' => 'error', 'message' => 'ID queue tidak valid.'));
			echo "<!--JSON_END-->";
			exit;
		}
		if ($target === 'enable') {
			$API->comm('/queue/simple/enable', array('.id' => $id));
		} else {
			$API->comm('/queue/simple/disable', array('.id' => $id));
		}
		echo json_encode(array(
			'status'  => 'success',
			'message' => ($target === 'enable') ? 'Limit bandwidth diaktifkan.' : 'Limit bandwidth dijeda sementara.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	// 6. Hapus Simple Queue
	if ($act === 'delete_queue') {
		$id = trim((string)(isset($_POST['id']) ? $_POST['id'] : ''));
		if ($id === '') {
			echo json_encode(array('status' => 'error', 'message' => 'ID queue tidak ditemukan.'));
			echo "<!--JSON_END-->";
			exit;
		}
		$API->comm('/queue/simple/remove', array('.id' => $id));
		echo json_encode(array('status' => 'success', 'message' => 'Aturan bandwidth berhasil dihapus dari router.'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 7. Reset Counter Queue
	if ($act === 'reset_counters') {
		$API->comm('/queue/simple/reset-counters-all');
		echo json_encode(array('status' => 'success', 'message' => 'Seluruh counter trafik & kuota Simple Queue di-reset ke 0.'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 8. Nonaktifkan FastTrack Firewall (Opsional 1-Klik)
	if ($act === 'disable_fasttrack') {
		$fwRules = $API->comm('/ip/firewall/filter/print', array('?action' => 'fasttrack-connection'));
		$disabledFt = 0;
		if (is_array($fwRules)) {
			foreach ($fwRules as $fw) {
				if (isset($fw['.id']) && (!isset($fw['disabled']) || $fw['disabled'] !== 'true')) {
					$API->comm('/ip/firewall/filter/disable', array('.id' => $fw['.id']));
					$disabledFt++;
				}
			}
		}
		echo json_encode(array(
			'status'  => 'success',
			'message' => $disabledFt . ' aturan FastTrack dinonaktifkan agar limit Simple Queue berjalan penuh.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	echo json_encode(array('status' => 'error', 'message' => 'Perintah tidak dikenali.'));
	echo "<!--JSON_END-->";
	exit;
}

// Ambil Data Simple Queue, Interface, dan Cek FastTrack
$allQueues = $isConnected ? $API->comm('/queue/simple/print') : array();
$allIfaces = $isConnected ? $API->comm('/interface/print') : array();
$fastTrackActiveCount = 0;

if ($isConnected) {
	$fwCheck = $API->comm('/ip/firewall/filter/print', array('?action' => 'fasttrack-connection'));
	if (is_array($fwCheck)) {
		foreach ($fwCheck as $fw) {
			if (!isset($fw['disabled']) || ($fw['disabled'] !== 'true' && $fw['disabled'] !== true)) {
				$fastTrackActiveCount++;
			}
		}
	}
}

$queueRows = array();
$activeCount = 0;
$staticCount = 0;
$dynamicCount = 0;
$totalUpBps = 0;
$totalDownBps = 0;

if (is_array($allQueues)) {
	foreach ($allQueues as $idx => $q) {
		$isDisabled = (isset($q['disabled']) && ($q['disabled'] === 'true' || $q['disabled'] === true));
		$isDynamic  = (isset($q['dynamic']) && ($q['dynamic'] === 'true' || $q['dynamic'] === true));

		if (!$isDisabled) $activeCount++;
		if ($isDynamic) $dynamicCount++; else $staticCount++;

		$rateRaw = isset($q['rate']) ? (string)$q['rate'] : '0/0';
		$rParts  = explode('/', $rateRaw);
		$upBps   = isset($rParts[0]) ? (float)$rParts[0] : 0;
		$downBps = isset($rParts[1]) ? (float)$rParts[1] : 0;

		if (!$isDisabled) {
			$totalUpBps   += $upBps;
			$totalDownBps += $downBps;
		}

		$maxLimitRaw = isset($q['max-limit']) ? (string)$q['max-limit'] : '0/0';
		$mlParts     = explode('/', $maxLimitRaw);
		$maxUpBps    = isset($mlParts[0]) ? (float)$mlParts[0] : 0;
		$maxDownBps  = isset($mlParts[1]) ? (float)$mlParts[1] : 0;

		$upEditVal   = qosFormatInputUnit($maxUpBps);
		$downEditVal = qosFormatInputUnit($maxDownBps);

		$upPct   = ($maxUpBps > 0) ? min(100, round(($upBps / $maxUpBps) * 100)) : ($upBps > 0 ? 12 : 0);
		$downPct = ($maxDownBps > 0) ? min(100, round(($downBps / $maxDownBps) * 100)) : ($downBps > 0 ? 12 : 0);

		$burstRaw   = isset($q['burst-limit']) ? (string)$q['burst-limit'] : '0/0';
		$brParts    = explode('/', $burstRaw);
		$burstUpVal = isset($brParts[0]) ? qosFormatInputUnit((float)$brParts[0]) : '0';
		$burstDnVal = isset($brParts[1]) ? qosFormatInputUnit((float)$brParts[1]) : '0';

		$bytesRaw = isset($q['bytes']) ? (string)$q['bytes'] : '0/0';
		$bParts   = explode('/', $bytesRaw);
		$upBytes  = isset($bParts[0]) ? (float)$bParts[0] : 0;
		$dnBytes  = isset($bParts[1]) ? (float)$bParts[1] : 0;

		$prioRaw = isset($q['priority']) ? (string)$q['priority'] : '8/8';
		$prioNum = (int)explode('/', $prioRaw)[0];
		if ($prioNum < 1 || $prioNum > 8) $prioNum = 8;

		$queueField = isset($q['queue']) ? (string)$q['queue'] : 'default-small/default-small';
		$isPcq = (stripos($queueField, 'pcq') !== false);

		$queueRows[] = array(
			'index'        => $idx,
			'id'           => isset($q['.id']) ? (string)$q['.id'] : '',
			'name'         => isset($q['name']) ? (string)$q['name'] : '-',
			'target'       => isset($q['target']) ? (string)$q['target'] : '-',
			'max_limit'    => qosFormatLimitLabel($maxLimitRaw),
			'burst_label'  => ($burstUpVal !== '0' || $burstDnVal !== '0') ? ($burstUpVal . '/' . $burstDnVal) : '',
			'up_edit'      => $upEditVal,
			'down_edit'    => $downEditVal,
			'burst_up'     => $burstUpVal,
			'burst_down'   => $burstDnVal,
			'priority'     => $prioNum,
			'is_pcq'       => $isPcq,
			'up_fmt'       => qosFormatRate($upBps),
			'down_fmt'     => qosFormatRate($downBps),
			'up_pct'       => $upPct,
			'down_pct'     => $downPct,
			'up_bytes_fmt' => qosFormatBytes($upBytes),
			'dn_bytes_fmt' => qosFormatBytes($dnBytes),
			'comment'      => isset($q['comment']) ? (string)$q['comment'] : '',
			'disabled'     => $isDisabled,
			'dynamic'      => $isDynamic
		);
	}
}
$totalQueues = count($queueRows);
?>

<style>
	#mhm-qos {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}
	#mhm-qos *,
	#mhm-qos *::before,
	#mhm-qos *::after {
		box-sizing: border-box;
	}

	/* Top Bar */
	#mhm-qos .qs-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		background: #ffffff;
	}
	#mhm-qos .qs-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-qos .qs-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-qos .qs-pill {
		font-size: 11.5px;
		font-weight: 500;
		color: #52525b;
		background: #f4f4f5;
		border: 1px solid #e4e4e7;
		padding: 2px 9px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}
	#mhm-qos .qs-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-qos .qs-btn {
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
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	#mhm-qos .qs-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-qos .qs-btn.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff !important;
	}
	#mhm-qos .qs-btn.is-primary:hover {
		background: #27272a;
	}
	#mhm-qos .qs-btn.is-help {
		border-color: #bae6fd;
		background: #f0f9ff;
		color: #0369a1 !important;
	}
	#mhm-qos .qs-btn.is-help:hover {
		background: #e0f2fe;
		border-color: #7dd3fc;
	}
	#mhm-qos .qs-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
		pointer-events: none;
	}

	/* FastTrack Warning Strip */
	#mhm-qos .qs-alert-strip {
		padding: 11px 24px;
		background: #fffbeb;
		border-bottom: 1px solid #fde68a;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		flex-wrap: wrap;
		font-size: 12.5px;
		color: #92400e;
	}

	/* Metric Strip */
	#mhm-qos .qs-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 768px) {
		#mhm-qos .qs-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-qos .qs-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-qos .qs-metric-item:last-child {
		border-right: none;
	}
	#mhm-qos .qs-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-qos .qs-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	.qs-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		display: inline-block;
		flex-shrink: 0;
	}
	@keyframes qsPulse {
		0%, 100% { opacity: 1; transform: scale(1); }
		50% { opacity: 0.45; transform: scale(0.85); }
	}
	.qs-dot.is-live {
		animation: qsPulse 1.8s infinite ease-in-out;
	}

	/* Add Form Bar */
	#mhm-qos .qs-add-section {
		padding: 16px 24px;
		background: #ffffff;
		border-bottom: 1px solid #e4e4e7;
	}
	#mhm-qos .qs-quick-chips {
		display: flex;
		align-items: center;
		gap: 6px;
		flex-wrap: wrap;
		margin-bottom: 10px;
	}
	#mhm-qos .qs-chip {
		font-size: 11px;
		font-weight: 500;
		color: #3f3f46;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		border-radius: 99px;
		padding: 3px 10px;
		cursor: pointer;
		transition: all 0.12s ease;
	}
	#mhm-qos .qs-chip:hover {
		border-color: #18181b;
		background: #ffffff;
		color: #09090b;
	}
	#mhm-qos .qs-add-bar {
		display: grid;
		grid-template-columns: 1.25fr 1.25fr 0.8fr 0.8fr 0.95fr 0.9fr auto;
		gap: 8px;
		align-items: center;
	}
	@media (max-width: 1160px) {
		#mhm-qos .qs-add-bar {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-qos .qs-burst-row {
		margin-top: 9px;
		padding-top: 9px;
		border-top: 1px dashed #e4e4e7;
		display: none;
		grid-template-columns: repeat(3, minmax(0, 220px));
		gap: 8px;
		align-items: center;
	}
	#mhm-qos .qs-burst-row.is-open {
		display: grid;
	}
	#mhm-qos .qs-input,
	#mhm-qos .qs-select {
		width: 100% !important;
		height: 36px !important;
		padding: 0 11px !important;
		background: #ffffff !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		color: #09090b !important;
		margin: 0 !important;
	}
	#mhm-qos .qs-input:focus,
	#mhm-qos .qs-select:focus {
		border-color: #18181b !important;
		outline: none !important;
	}

	/* Filter & Search Toolbar */
	#mhm-qos .qs-filter-bar {
		padding: 12px 24px;
		background: #fafafa;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		flex-wrap: wrap;
	}
	#mhm-qos .qs-tabs {
		display: inline-flex;
		background: #e4e4e7;
		padding: 3px;
		border-radius: 8px;
		gap: 2px;
	}
	#mhm-qos .qs-tab {
		border: none;
		background: transparent;
		padding: 5px 11px;
		border-radius: 6px;
		font-size: 12px;
		font-weight: 500;
		color: #52525b;
		cursor: pointer;
		transition: all 0.15s ease;
	}
	#mhm-qos .qs-tab.is-active {
		background: #ffffff;
		color: #09090b;
		box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
	}

	/* Table */
	#mhm-qos .qs-table-wrap {
		width: 100%;
		overflow-x: auto;
		max-height: 62vh;
	}
	#mhm-qos table.qs-table {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		text-align: left;
	}
	#mhm-qos table.qs-table thead th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #fafafa !important;
		color: #71717a !important;
		font-size: 10.5px !important;
		font-weight: 600 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.05em !important;
		padding: 11px 16px !important;
		border-bottom: 1px solid #e4e4e7 !important;
		white-space: nowrap;
	}
	#mhm-qos table.qs-table tbody td {
		padding: 12px 16px !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-qos table.qs-table tbody tr:hover {
		background: #fafafa !important;
	}
	#mhm-qos table.qs-table tbody tr.is-dimmed {
		opacity: 0.58;
	}

	/* Live Progress Bars */
	.qs-rate-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		font-size: 11.5px;
		font-weight: 600;
		margin-bottom: 3px;
	}
	.qs-bar-track {
		width: 135px;
		height: 5px;
		background: #f4f4f5;
		border-radius: 99px;
		overflow: hidden;
	}
	.qs-bar-fill {
		height: 100%;
		border-radius: 99px;
		transition: width 0.35s ease, background-color 0.25s ease;
	}

	.qs-badge {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 2px 8px;
		border-radius: 6px;
		font-size: 11px;
		font-weight: 600;
		white-space: nowrap;
	}
	.qs-badge.is-high { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
	.qs-badge.is-med  { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
	.qs-badge.is-low  { background: #f4f4f5; color: #52525b; border: 1px solid #e4e4e7; }
	.qs-badge.is-on   { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
	.qs-badge.is-off  { background: #f4f4f5; color: #71717a; border: 1px solid #e4e4e7; }
	.qs-badge.is-pcq  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

	.qs-row-btn {
		height: 28px;
		padding: 0 8px;
		border-radius: 6px;
		font-size: 11.5px;
		font-weight: 500;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 4px;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #18181b;
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	.qs-row-btn:hover { background: #f4f4f5; }
	.qs-row-btn:disabled { opacity: 0.35; cursor: not-allowed; }
	.qs-row-btn.is-del {
		border-color: #fecaca;
		background: #fef2f2;
		color: #dc2626;
	}
	.qs-row-btn.is-del:hover {
		background: #dc2626;
		border-color: #dc2626;
		color: #ffffff;
	}

	#mhm-qos .qs-footer {
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

	/* Custom Modals (Edit, Confirm, Help) */
	.qs-overlay {
		position: fixed;
		inset: 0;
		z-index: 99998;
		background: rgba(9, 9, 11, 0.45);
		backdrop-filter: blur(3px);
		display: none;
		align-items: center;
		justify-content: center;
		padding: 16px;
	}
	.qs-overlay.is-open { display: flex; }
	.qs-modal-card {
		width: 100%;
		max-width: 450px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 14px;
		box-shadow: 0 24px 48px -12px rgba(9, 9, 11, 0.25);
		overflow: hidden;
	}
	.qs-modal-head {
		padding: 16px 20px;
		border-bottom: 1px solid #f4f4f5;
		font-size: 15px;
		font-weight: 600;
		color: #09090b;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
	}
	.qs-modal-body {
		padding: 18px 20px;
	}
	.qs-modal-foot {
		padding: 12px 20px;
		background: #fafafa;
		border-top: 1px solid #f4f4f5;
		display: flex;
		justify-content: flex-end;
		gap: 8px;
	}
	.qs-field-label {
		display: block;
		font-size: 11.5px;
		font-weight: 600;
		color: #52525b;
		margin-bottom: 5px;
	}

	/* Help Modal Specifics */
	.qs-help-scroll {
		max-height: 72vh;
		overflow-y: auto;
		padding: 20px 22px;
		display: flex;
		flex-direction: column;
		gap: 14px;
	}
	.qs-help-item {
		border: 1px solid #e4e4e7;
		border-radius: 10px;
		padding: 14px 16px;
		background: #fafafa;
	}
	.qs-help-item h4 {
		margin: 0 0 6px 0;
		font-size: 13.5px;
		font-weight: 600;
		color: #09090b;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.qs-help-item p,
	.qs-help-item ul {
		margin: 0;
		font-size: 12.5px;
		color: #3f3f46;
		line-height: 1.55;
	}
	.qs-help-item ul {
		padding-left: 18px;
		margin-top: 5px;
	}
	.qs-help-item li {
		margin-bottom: 4px;
	}
	.qs-code-tag {
		font-family: monospace;
		font-size: 11.5px;
		background: #ffffff;
		border: 1px solid #d4d4d8;
		padding: 1px 6px;
		border-radius: 5px;
		color: #09090b;
	}

	/* Toast Alert */
	#qs-toast-box {
		position: fixed;
		top: 68px;
		right: 22px;
		z-index: 99999;
		display: flex;
		flex-direction: column;
		gap: 9px;
		pointer-events: none;
	}
	.qs-toast {
		pointer-events: auto;
		min-width: 290px;
		max-width: 400px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 11px;
		padding: 13px 14px;
		box-shadow: 0 14px 30px -8px rgba(9, 9, 11, 0.2);
		display: flex;
		align-items: flex-start;
		gap: 11px;
	}
</style>

<div id="qs-toast-box"></div>

<!-- Modal Help / Panduan Lengkap -->
<div id="qsHelpOverlay" class="qs-overlay" onclick="if(event.target === this) closeHelpModal();">
	<div class="qs-modal-card" style="max-width:740px;">
		<div class="qs-modal-head">
			<div style="display:flex; align-items:center; gap:9px;">
				<span style="width:28px; height:28px; border-radius:7px; background:#e0f2fe; color:#0284c7; display:inline-flex; align-items:center; justify-content:center; font-size:14px;">
					<i class="fa fa-book"></i>
				</span>
				<span>Panduan Lengkap Manajemen Bandwidth &amp; QoS (Simple Queue)</span>
			</div>
			<button type="button" class="qs-row-btn" onclick="closeHelpModal();" title="Tutup Panduan">
				<i class="fa fa-times"></i>
			</button>
		</div>

		<div class="qs-help-scroll">
			<div class="qs-help-item">
				<h4><i class="fa fa-sort-numeric-asc" style="color:#0284c7;"></i> 1. Urutan Eksekusi Rule (#0, #1, #2...) Sangat Menentukan</h4>
				<p>
					MikroTik membaca daftar <strong>Simple Queue</strong> secara berurutan dari atas (indeks <span class="qs-code-tag">#0</span>) ke bawah. Begitu paket data cocok dengan satu aturan di atas, aturan di bawahnya tidak akan diproses lagi untuk trafik tersebut.
				</p>
				<ul>
					<li><strong>Aturan Emas:</strong> Letakkan target yang lebih spesifik (misal IP tunggal <span class="qs-code-tag">192.168.10.2</span> Server Ujian atau subnet Ruang Guru) di urutan paling atas menggunakan tombol panah <strong>&uarr; / &darr;</strong>.</li>
					<li>Letakkan subnet umum atau jaringan luas (seperti Hotspot Siswa atau seluruh bridge) di urutan paling bawah sebagai penampung sisa trafik.</li>
				</ul>
			</div>

			<div class="qs-help-item">
				<h4><i class="fa fa-crosshairs" style="color:#16a34a;"></i> 2. Format Input Target &amp; Batas Kecepatan (Max Up / Max Down)</h4>
				<ul>
					<li><strong>Target IP / Subnet / Interface:</strong> Dapat diisi IP perangkat tunggal (<span class="qs-code-tag">192.168.20.15</span>), satu blok jaringan CIDR (<span class="qs-code-tag">192.168.20.0/24</span>), beberapa IP sekaligus dipisah koma (<span class="qs-code-tag">192.168.20.5,192.168.20.6</span>), atau nama interface router (<span class="qs-code-tag">ether3</span> / <span class="qs-code-tag">vlan-lab</span>).</li>
					<li><strong>Max Upload &amp; Max Download:</strong> Cukup ketik angka saja (misal <span class="qs-code-tag">20</span>) maka sistem otomatis membacanya sebagai <strong>20 Mbps (20M)</strong>. Anda juga bisa memakai akhiran <span class="qs-code-tag">K</span> (Kbps, contoh <span class="qs-code-tag">512K</span>), <span class="qs-code-tag">M</span> (Mbps), atau <span class="qs-code-tag">G</span> (Gbps).</li>
					<li><strong>Unlimited:</strong> Isi angka <span class="qs-code-tag">0</span> jika ingin membebaskan batas kecepatan pada arah Upload maupun Download.</li>
				</ul>
			</div>

			<div class="qs-help-item">
				<h4><i class="fa fa-users" style="color:#2563eb;"></i> 3. Perbedaan Metode Antrean: PCQ vs Default-Small</h4>
				<ul>
					<li><strong>PCQ (Per Connection Queue):</strong> Sangat direkomendasikan untuk <strong>Ruang Guru, Lab Komputer, atau Hotspot Siswa</strong>. Jika limit diset <span class="qs-code-tag">30M</span> dengan PCQ, MikroTik akan membagi kecepatan secara adil dan merata ke setiap perangkat yang aktif (misal 10 komputer aktif masing-masing mendapat bagian seimbang ~3 Mbps), sehingga satu PC yang mengunduh file besar tidak membuat PC lain putus koneksi.</li>
					<li><strong>Default-Small:</strong> Antrean standar FIFO untuk satu perangkat tunggal seperti <strong>Server Dapodik, Server ANBK, atau router cabang</strong> yang memang membutuhkan seluruh kapasitas tanpa dibagi-bagi.</li>
				</ul>
			</div>

			<div class="qs-help-item">
				<h4><i class="fa fa-signal" style="color:#dc2626;"></i> 4. Sistem Prioritas Trafik (Priority 1 s/d 8)</h4>
				<p>
					Nilai <strong>Prioritas 1</strong> adalah yang tertinggi, sedangkan <strong>Prioritas 8</strong> adalah yang terendah. Prioritas bekerja saat total bandwidth internet utama sedang penuh (jenuh): router akan mendahulukan paket dari ruangan berprioritas tinggi (seperti Server Ujian/Guru) sebelum melayani antrean berprioritas rendah (Hotspot Siswa).
				</p>
			</div>

			<div class="qs-help-item">
				<h4><i class="fa fa-bolt" style="color:#d97706;"></i> 5. Fungsi Opsi Burst Limit &amp; Burst Time</h4>
				<p>
					<strong>Burst Limit</strong> mengizinkan pengguna melampaui batas normal (<em>Max Limit</em>) untuk sesaat (misalnya saat membuka halaman web ujian atau memuat gambar) agar koneksi terasa sangat responsif. Jika unduhan berlangsung terus-menerus melebihi rata-rata selama durasi <strong>Burst Time</strong> (default <span class="qs-code-tag">8s</span>), kecepatan otomatis diturunkan kembali ke <em>Max Limit</em> normal.
				</p>
			</div>

			<div class="qs-help-item">
				<h4><i class="fa fa-shield" style="color:#b45309;"></i> 6. Peringatan Firewall FastTrack</h4>
				<p>
					Di MikroTik, fitur <strong>FastTrack</strong> mempercepat *routing* dengan cara melewati (<em>bypass</em>) proses Simple Queue. Jika rule FastTrack aktif di <span class="qs-code-tag">/ip/firewall/filter</span>, limit bandwidth ruangan tidak akan berfungsi. Halaman ini otomatis mendeteksinya dan menyediakan tombol 1-klik untuk menonaktifkan FastTrack bila diperlukan.
				</p>
			</div>

			<div class="qs-help-item">
				<h4><i class="fa fa-tachometer" style="color:#52525b;"></i> 7. Arti Indikator Bar Live &amp; Kontrol Tabel</h4>
				<ul>
					<li><strong>Bar Persentase Live:</strong> Menampilkan pemakaian kecepatan saat ini dibandingkan dengan <em>Max Limit</em>. Warna berubah menjadi <strong>Kuning</strong> saat pemakaian mencapai &ge; 75%, dan <strong>Merah</strong> saat mencapai &ge; 90% (mendekati batas maksimal).</li>
					<li><strong>Jeda / Lanjut Telemetry:</strong> Menghentikan sementara pembaruan angka trafik otomatis setiap 3 detik tanpa memuat ulang halaman.</li>
					<li><strong>Reset Counter:</strong> Mengembalikan perhitungan akumulasi <em>Kuota Terpakai</em> (Upload/Download) seluruh queue menjadi 0 B.</li>
					<li><strong>Ekspor CSV:</strong> Mengunduh daftar aturan bandwidth beserta trafik dan kuota terpakai sesuai filter yang sedang tampil ke format spreadsheet (<span class="qs-code-tag">.csv</span>).</li>
				</ul>
			</div>
		</div>

		<div class="qs-modal-foot">
			<button type="button" class="qs-btn is-primary" onclick="closeHelpModal();">Mengerti, Tutup Panduan</button>
		</div>
	</div>
</div>

<!-- Modal Edit Queue -->
<div id="qsEditOverlay" class="qs-overlay" onclick="if(event.target === this) closeEditModal();">
	<div class="qs-modal-card">
		<div class="qs-modal-head">
			<span>Edit Limit Bandwidth Ruangan</span>
			<button type="button" class="qs-row-btn" onclick="closeEditModal();"><i class="fa fa-times"></i></button>
		</div>
		<form onsubmit="submitEditQueue(event);">
			<div class="qs-modal-body">
				<input type="hidden" id="editQueueId">
				<div style="margin-bottom:12px;">
					<label class="qs-field-label">Nama Ruangan / Aturan</label>
					<input type="text" id="editQueueName" class="qs-input" required>
				</div>
				<div style="margin-bottom:12px;">
					<label class="qs-field-label">Target IP / Subnet / Interface</label>
					<input type="text" id="editQueueTarget" class="qs-input" style="font-family:monospace !important;" required>
				</div>
				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
					<div>
						<label class="qs-field-label">Max Upload (misal 20M / 0)</label>
						<input type="text" id="editQueueUp" class="qs-input" required>
					</div>
					<div>
						<label class="qs-field-label">Max Download (misal 50M / 0)</label>
						<input type="text" id="editQueueDown" class="qs-input" required>
					</div>
				</div>
				<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
					<div>
						<label class="qs-field-label">Burst Upload (0 = Nonaktif)</label>
						<input type="text" id="editQueueBurstUp" class="qs-input" placeholder="0">
					</div>
					<div>
						<label class="qs-field-label">Burst Download (0 = Nonaktif)</label>
						<input type="text" id="editQueueBurstDown" class="qs-input" placeholder="0">
					</div>
				</div>
				<div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:10px;">
					<div>
						<label class="qs-field-label">Prioritas Trafik</label>
						<select id="editQueuePrio" class="qs-select">
							<option value="1">1 - Utama (Server / Dapodik)</option>
							<option value="2">2 - Sangat Tinggi (Guru / TU)</option>
							<option value="4">4 - Normal / Standar</option>
							<option value="5">5 - Menengah (Lab Komputer)</option>
							<option value="8">8 - Rendah (Hotspot Siswa)</option>
						</select>
					</div>
					<div>
						<label class="qs-field-label">Metode Antrean</label>
						<select id="editQueueType" class="qs-select">
							<option value="keep">Tetap</option>
							<option value="pcq">PCQ (Adil/User)</option>
							<option value="default-small">Default-Small</option>
						</select>
					</div>
				</div>
			</div>
			<div class="qs-modal-foot">
				<button type="button" class="qs-btn" onclick="closeEditModal();">Batal</button>
				<button type="submit" id="btnSaveEditQueue" class="qs-btn is-primary">Simpan Perubahan</button>
			</div>
		</form>
	</div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="qsConfirmOverlay" class="qs-overlay" onclick="if(event.target === this) closeQsConfirm();">
	<div class="qs-modal-card" style="max-width:400px;">
		<div class="qs-modal-body" style="display:flex; gap:14px; align-items:flex-start;">
			<div style="width:38px; height:38px; border-radius:9px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
				<i class="fa fa-trash-o"></i>
			</div>
			<div>
				<h4 style="margin:0 0 5px 0; font-size:15px; font-weight:600; color:#09090b;">Hapus Limit Bandwidth?</h4>
				<p style="margin:0; font-size:12.5px; color:#52525b; line-height:1.5;">
					Aturan Simple Queue <strong id="qsDelName" style="color:#09090b;">-</strong> akan dihapus permanen dari MikroTik.
				</p>
			</div>
		</div>
		<div class="qs-modal-foot">
			<button type="button" class="qs-btn" onclick="closeQsConfirm();">Batal</button>
			<button type="button" id="btnConfirmDelQueue" class="qs-btn" style="background:#dc2626; border-color:#dc2626; color:#ffffff !important;">Ya, Hapus</button>
		</div>
	</div>
</div>

<div id="mhm-qos">
	<!-- Top Bar -->
	<div class="qs-topbar">
		<div class="qs-title-group">
			<h3 class="qs-title">Manajemen Bandwidth &amp; QoS Ruangan</h3>
			<span class="qs-pill"><?= $activeCount; ?> aktif dari <?= $totalQueues; ?> queue</span>
		</div>

		<div class="qs-actions">
			<button type="button" class="qs-btn" onclick="exportQosCsv();" <?= ($totalQueues === 0) ? 'disabled' : ''; ?>>
				<i class="fa fa-download" style="color:#71717a;"></i>
				<span>Ekspor CSV</span>
			</button>
			<button type="button" class="qs-btn is-help" onclick="openHelpModal();">
				<i class="fa fa-question-circle"></i>
				<span>Help</span>
			</button>
			<button type="button" class="qs-btn" onclick="resetQosCounters(this);" <?= ($totalQueues === 0) ? 'disabled' : ''; ?>>
				<i class="fa fa-history" style="color:#71717a;"></i>
				<span>Reset Counter</span>
			</button>
			<button type="button" class="qs-btn" onclick="location.reload();">
				<i class="fa fa-refresh" style="color:#71717a;"></i>
				<span>Refresh</span>
			</button>
		</div>
	</div>

	<!-- Peringatan Otomatis jika Firewall FastTrack Aktif -->
	<?php if ($fastTrackActiveCount > 0): ?>
		<div class="qs-alert-strip">
			<div style="display:flex; align-items:center; gap:9px;">
				<i class="fa fa-exclamation-triangle" style="color:#d97706;"></i>
				<span><strong>FastTrack Firewall Terdeteksi (<?= $fastTrackActiveCount; ?> rule aktif):</strong> Paket FastTrack melewati Simple Queue sehingga limit bandwidth ruangan bisa terabaikan.</span>
			</div>
			<button type="button" class="qs-btn" style="height:28px; font-size:11.5px; border-color:#fcd34d; background:#ffffff;" onclick="disableFastTrackRules(this);">
				<i class="fa fa-shield" style="color:#d97706;"></i> Nonaktifkan FastTrack
			</button>
		</div>
	<?php endif; ?>

	<!-- Metric Strip -->
	<div class="qs-metrics">
		<div class="qs-metric-item">
			<div class="qs-metric-label">Aturan Ruangan / Dinamis</div>
			<div class="qs-metric-val">
				<span class="qs-dot" style="background:#10b981;"></span>
				<span><?= $staticCount; ?> <span style="font-size:12px; font-weight:400; color:#71717a;">statis / <?= $dynamicCount; ?> dinamis</span></span>
			</div>
		</div>
		<div class="qs-metric-item">
			<div class="qs-metric-label">Total Upload (Live)</div>
			<div class="qs-metric-val">
				<span class="qs-dot" style="background:#f59e0b;"></span>
				<span id="qsTotalUpLive"><?= qosFormatRate($totalUpBps); ?></span>
			</div>
		</div>
		<div class="qs-metric-item">
			<div class="qs-metric-label">Total Download (Live)</div>
			<div class="qs-metric-val">
				<span class="qs-dot" style="background:#0284c7;"></span>
				<span id="qsTotalDownLive"><?= qosFormatRate($totalDownBps); ?></span>
			</div>
		</div>
		<div class="qs-metric-item">
			<div class="qs-metric-label">Live Telemetry</div>
			<div class="qs-metric-val" style="justify-content:space-between;">
				<span style="display:inline-flex; align-items:center; gap:7px; font-size:13.5px;">
					<span id="qsLiveDot" class="qs-dot is-live" style="background:#16a34a;"></span>
					<span id="qsLiveText">Sync 3 Detik</span>
				</span>
				<button type="button" id="btnTogglePoll" class="qs-row-btn" style="height:24px; font-size:11px;" onclick="toggleLivePoll();">Jeda</button>
			</div>
		</div>
	</div>

	<!-- Form Tambah Ruangan / Subnet Baru -->
	<div class="qs-add-section">
		<div class="qs-quick-chips">
			<span style="font-size:11px; font-weight:600; color:#71717a; margin-right:2px;">Template Cepat:</span>
			<button type="button" class="qs-chip" onclick="fillRoomTemplate('Server Dapodik & Ujian', '192.168.10.0/24', '0', '0', '1', 'default-small');">+ Server Ujian</button>
			<button type="button" class="qs-chip" onclick="fillRoomTemplate('Ruang Guru & TU', '192.168.20.0/24', '20M', '50M', '2', 'pcq');">+ Ruang Guru</button>
			<button type="button" class="qs-chip" onclick="fillRoomTemplate('Lab Komputer TJKT', '192.168.30.0/24', '15M', '30M', '5', 'pcq');">+ Lab Komputer</button>
			<button type="button" class="qs-chip" onclick="fillRoomTemplate('Hotspot Siswa', '192.168.40.0/24', '10M', '20M', '8', 'pcq');">+ Hotspot Siswa</button>
			<button type="button" class="qs-chip" style="margin-left:auto; border-style:dashed;" onclick="toggleBurstFields();">
				<i class="fa fa-bolt" style="color:#f59e0b;"></i> Opsi Burst Limit
			</button>
		</div>

		<form onsubmit="addNewQueue(event);">
			<div class="qs-add-bar">
				<input type="text" id="qsNameInput" class="qs-input" placeholder="Nama Ruangan (misal: Ruang Guru)" required>
				<input type="text" id="qsTargetInput" class="qs-input" style="font-family:monospace !important;" placeholder="Target IP / Subnet (192.168.10.0/24)" list="qsTargetSuggest" required>
				<datalist id="qsTargetSuggest">
					<?php if (is_array($allIfaces)): ?>
						<?php foreach ($allIfaces as $if): ?>
							<?php if (!empty($if['name'])): ?>
								<option value="<?= htmlspecialchars($if['name']); ?>">Interface: <?= htmlspecialchars($if['name']); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</datalist>
				<input type="text" id="qsUpInput" class="qs-input" placeholder="Max Up (20M)" title="Isi angka saja (otomatis Mbps) atau akhiran K/M/G. Isi 0 untuk Unlimited." required>
				<input type="text" id="qsDownInput" class="qs-input" placeholder="Max Down (50M)" title="Isi angka saja (otomatis Mbps) atau akhiran K/M/G. Isi 0 untuk Unlimited." required>
				<select id="qsPrioInput" class="qs-select" title="Prioritas antrean trafik">
					<option value="1">Prio 1 (Server)</option>
					<option value="2">Prio 2 (Guru/TU)</option>
					<option value="5" selected>Prio 5 (Lab)</option>
					<option value="8">Prio 8 (Siswa)</option>
				</select>
				<select id="qsTypeInput" class="qs-select" title="Jenis pembagian antrean">
					<option value="pcq" selected>PCQ (Adil/User)</option>
					<option value="default-small">Default-Small</option>
				</select>
				<button type="submit" id="btnAddQueue" class="qs-btn is-primary" style="height:36px; padding:0 16px;">
					<i class="fa fa-plus"></i>
					<span>Tambah</span>
				</button>
			</div>

			<div id="qsBurstFields" class="qs-burst-row">
				<input type="text" id="qsBurstUpInput" class="qs-input" placeholder="Burst Up (opsional, misal 30M)">
				<input type="text" id="qsBurstDownInput" class="qs-input" placeholder="Burst Down (opsional, misal 70M)">
				<input type="text" id="qsBurstTimeInput" class="qs-input" placeholder="Burst Time (default: 8s)" value="8s">
			</div>
		</form>
	</div>

	<!-- Filter & Search Bar -->
	<div class="qs-filter-bar">
		<div class="qs-tabs" id="qsFilterTabs">
			<button type="button" class="qs-tab is-active" onclick="setQosFilter('all', this);">Semua (<?= $totalQueues; ?>)</button>
			<button type="button" class="qs-tab" onclick="setQosFilter('static', this);">Ruangan Statis (<?= $staticCount; ?>)</button>
			<button type="button" class="qs-tab" onclick="setQosFilter('active', this);">Aktif (<?= $activeCount; ?>)</button>
			<button type="button" class="qs-tab" onclick="setQosFilter('disabled', this);">Dijeda (<?= $totalQueues - $activeCount; ?>)</button>
		</div>

		<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
			<input type="text" id="qsSearchInput" class="qs-input" style="width:230px !important; height:32px !important;" placeholder="Cari ruangan atau IP target..." onkeyup="applyTableFilter();">
		</div>
	</div>

	<!-- Tabel Simple Queue -->
	<div class="qs-table-wrap">
		<table class="table qs-table" id="qsMainTable">
			<thead>
				<tr>
					<th style="width:46px;">#</th>
					<th>Nama Ruangan / Queue</th>
					<th>Target IP / Interface</th>
					<th>Limit &amp; Metode</th>
					<th>Trafik &amp; Utilisasi Live</th>
					<th>Kuota Terpakai</th>
					<th>Prioritas</th>
					<th>Status</th>
					<th style="text-align:right;">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($queueRows as $r): ?>
					<?php
					$prioCls = ($r['priority'] <= 2) ? 'is-high' : (($r['priority'] <= 5) ? 'is-med' : 'is-low');
					$upBarColor = ($r['up_pct'] >= 90) ? '#dc2626' : (($r['up_pct'] >= 75) ? '#f59e0b' : '#d97706');
					$dnBarColor = ($r['down_pct'] >= 90) ? '#dc2626' : (($r['down_pct'] >= 75) ? '#f59e0b' : '#0284c7');
					$qJson   = htmlspecialchars(json_encode(array(
						'id'         => $r['id'],
						'name'       => $r['name'],
						'target'     => $r['target'],
						'up'         => $r['up_edit'],
						'down'       => $r['down_edit'],
						'burst_up'   => $r['burst_up'],
						'burst_down' => $r['burst_down'],
						'priority'   => $r['priority']
					)), ENT_QUOTES);
					?>
					<tr class="<?= $r['disabled'] ? 'is-dimmed' : ''; ?>"
						data-qid="<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>"
						data-static="<?= $r['dynamic'] ? '0' : '1'; ?>"
						data-active="<?= $r['disabled'] ? '0' : '1'; ?>"
						data-search="<?= htmlspecialchars(strtolower($r['name'] . ' ' . $r['target'] . ' ' . $r['comment']), ENT_QUOTES); ?>">
						<td style="font-family:monospace; font-size:11.5px; color:#71717a;">
							#<?= $r['index']; ?>
						</td>
						<td>
							<div style="font-weight:600; color:#09090b; font-size:13px;"><?= htmlspecialchars($r['name']); ?></div>
							<?php if ($r['dynamic']): ?>
								<span style="font-size:10.5px; color:#71717a;">Dynamic (Hotspot/PPPoE)</span>
							<?php elseif ($r['comment'] !== ''): ?>
								<span style="font-size:11px; color:#71717a;"><?= htmlspecialchars($r['comment']); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<span style="font-family:monospace; font-size:12px; background:#f4f4f5; border:1px solid #e4e4e7; padding:2px 7px; border-radius:5px; color:#18181b;">
								<?= htmlspecialchars($r['target']); ?>
							</span>
						</td>
						<td style="font-variant-numeric:tabular-nums;">
							<div style="font-weight:600; font-size:12.5px; color:#09090b;"><?= htmlspecialchars($r['max_limit']); ?></div>
							<div style="display:flex; gap:4px; margin-top:3px; flex-wrap:wrap;">
								<?php if ($r['is_pcq']): ?>
									<span class="qs-badge is-pcq" style="font-size:10px; padding:1px 6px;">PCQ</span>
								<?php endif; ?>
								<?php if ($r['burst_label'] !== ''): ?>
									<span class="qs-badge is-med" style="font-size:10px; padding:1px 6px;">Burst <?= htmlspecialchars($r['burst_label']); ?></span>
								<?php endif; ?>
							</div>
						</td>
						<td style="font-variant-numeric:tabular-nums;">
							<div style="margin-bottom:5px;">
								<div class="qs-rate-row" style="color:#d97706;">
									<span>&uarr; <span class="qs-live-up"><?= htmlspecialchars($r['up_fmt']); ?></span></span>
									<span class="qs-live-up-pct" style="font-size:10.5px; color:#71717a;"><?= $r['up_pct']; ?>%</span>
								</div>
								<div class="qs-bar-track">
									<div class="qs-bar-fill qs-bar-up" style="width:<?= $r['up_pct']; ?>%; background:<?= $upBarColor; ?>;"></div>
								</div>
							</div>
							<div>
								<div class="qs-rate-row" style="color:#0284c7;">
									<span>&darr; <span class="qs-live-down"><?= htmlspecialchars($r['down_fmt']); ?></span></span>
									<span class="qs-live-dn-pct" style="font-size:10.5px; color:#71717a;"><?= $r['down_pct']; ?>%</span>
								</div>
								<div class="qs-bar-track">
									<div class="qs-bar-fill qs-bar-dn" style="width:<?= $r['down_pct']; ?>%; background:<?= $dnBarColor; ?>;"></div>
								</div>
							</div>
						</td>
						<td style="font-variant-numeric:tabular-nums; font-size:11.5px; color:#52525b;">
							<div>&uarr; <span class="qs-bytes-up"><?= htmlspecialchars($r['up_bytes_fmt']); ?></span></div>
							<div>&darr; <span class="qs-bytes-dn"><?= htmlspecialchars($r['dn_bytes_fmt']); ?></span></div>
						</td>
						<td>
							<span class="qs-badge <?= $prioCls; ?>">Prio <?= $r['priority']; ?></span>
						</td>
						<td>
							<?php if ($r['disabled']): ?>
								<span class="qs-badge is-off">Dijeda</span>
							<?php else: ?>
								<span class="qs-badge is-on">Aktif</span>
							<?php endif; ?>
						</td>
						<td style="text-align:right;">
							<div style="display:inline-flex; gap:4px; align-items:center;">
								<?php if (!$r['dynamic']): ?>
									<button type="button" class="qs-row-btn" onclick="moveQueueOrder(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', '<?= max(0, $r['index'] - 1); ?>');" <?= ($r['index'] === 0) ? 'disabled' : ''; ?> title="Naikkan urutan rule">
										<i class="fa fa-arrow-up"></i>
									</button>
									<button type="button" class="qs-row-btn" onclick="moveQueueOrder(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', '<?= $r['index'] + 1; ?>');" <?= ($r['index'] >= $totalQueues - 1) ? 'disabled' : ''; ?> title="Turunkan urutan rule">
										<i class="fa fa-arrow-down"></i>
									</button>
									<button type="button" class="qs-row-btn" onclick="openEditModal(<?= $qJson; ?>);" title="Edit limit bandwidth">
										<i class="fa fa-pencil"></i>
									</button>
									<?php if ($r['disabled']): ?>
										<button type="button" class="qs-row-btn" onclick="toggleQueue(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', 'enable');" title="Aktifkan">
											<i class="fa fa-play" style="color:#16a34a;"></i>
										</button>
									<?php else: ?>
										<button type="button" class="qs-row-btn" onclick="toggleQueue(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', 'disable');" title="Jeda sementara">
											<i class="fa fa-pause" style="color:#d97706;"></i>
										</button>
									<?php endif; ?>
									<button type="button" class="qs-row-btn is-del" onclick="confirmDeleteQueue('<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', '<?= htmlspecialchars($r['name'], ENT_QUOTES); ?>');" title="Hapus">
										<i class="fa fa-trash"></i>
									</button>
								<?php else: ?>
									<span style="font-size:11px; color:#a1a1aa;">Otomatis</span>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ($totalQueues === 0): ?>
			<div style="padding:42px 24px; text-align:center; color:#71717a; font-size:13px;">
				Belum ada aturan Simple Queue di MikroTik. Gunakan <strong>Template Cepat</strong> atau form di atas untuk menambahkan subnet ruangan.
			</div>
		<?php endif; ?>
	</div>

	<!-- Footer -->
	<div class="qs-footer">
		<div>Engine: <strong style="color:#18181b;">MikroTik Simple Queue (/queue/simple)</strong> &bull; Eksekusi berurutan dari indeks #0</div>
		<div>Klik tombol <strong>Help</strong> di kanan atas untuk penjelasan rinci setiap parameter QoS</div>
	</div>
</div>

<script>
	var qsPendingDeleteId = '';
	var qsCurrentFilter = 'all';
	var qsLivePollingActive = true;

	function showQsToast(type, title, msg) {
		var box = document.getElementById('qs-toast-box');
		if (!box) return;
		var isOk = (type === 'success');
		var el = document.createElement('div');
		el.className = 'qs-toast';
		el.innerHTML =
			'<div style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;background:' + (isOk ? '#dcfce7;color:#16a34a' : '#fee2e2;color:#dc2626') + ';">' +
				'<i class="fa ' + (isOk ? 'fa-check' : 'fa-exclamation-triangle') + '"></i>' +
			'</div>' +
			'<div style="flex:1;min-width:0;">' +
				'<div style="font-size:12.5px;font-weight:600;color:#09090b;margin-bottom:2px;">' + title + '</div>' +
				'<div style="font-size:12px;color:#52525b;line-height:1.4;">' + msg + '</div>' +
			'</div>';
		box.appendChild(el);
		setTimeout(function() {
			if (el.parentNode) el.parentNode.removeChild(el);
		}, isOk ? 3500 : 5500);
	}

	function openHelpModal() {
		document.getElementById('qsHelpOverlay').classList.add('is-open');
	}

	function closeHelpModal() {
		document.getElementById('qsHelpOverlay').classList.remove('is-open');
	}

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			closeHelpModal();
			closeEditModal();
			closeQsConfirm();
		}
	});

	function postQosAction(params, btnEl, loadingLabel, onDone) {
		var origHtml = btnEl ? btnEl.innerHTML : '';
		if (btnEl) {
			btnEl.disabled = true;
			btnEl.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i>' + (loadingLabel ? ' ' + loadingLabel : '');
		}

		var formData = new URLSearchParams();
		formData.append('ajax_qos', '1');
		for (var k in params) {
			if (Object.prototype.hasOwnProperty.call(params, k)) {
				formData.append(k, params[k]);
			}
		}

		fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData.toString()
		})
		.then(function(res) { return res.text(); })
		.then(function(raw) {
			var s = raw.indexOf('<!--JSON_START-->');
			var e = raw.indexOf('<!--JSON_END-->');
			var data = null;
			if (s !== -1 && e !== -1) {
				try { data = JSON.parse(raw.substring(s + 17, e)); } catch (err) {}
			}
			if (onDone) onDone();
			if (!data) {
				if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = origHtml; }
				showQsToast('error', 'Gagal', 'Respon server tidak valid.');
				return;
			}
			if (data.status === 'success') {
				showQsToast('success', 'Berhasil', data.message);
				setTimeout(function() { window.location.reload(); }, 650);
			} else {
				if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = origHtml; }
				showQsToast('error', 'Gagal', data.message || 'Terjadi kesalahan.');
			}
		})
		.catch(function() {
			if (onDone) onDone();
			if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = origHtml; }
			showQsToast('error', 'Koneksi Terputus', 'Tidak dapat menghubungi server Mikhmon.');
		});
	}

	function fillRoomTemplate(name, target, up, down, prio, qtype) {
		document.getElementById('qsNameInput').value   = name;
		document.getElementById('qsTargetInput').value = target;
		document.getElementById('qsUpInput').value     = up;
		document.getElementById('qsDownInput').value   = down;
		document.getElementById('qsPrioInput').value   = prio;
		document.getElementById('qsTypeInput').value   = qtype;
		document.getElementById('qsTargetInput').focus();
	}

	function toggleBurstFields() {
		var el = document.getElementById('qsBurstFields');
		if (el) el.classList.toggle('is-open');
	}

	function addNewQueue(e) {
		e.preventDefault();
		var btn = document.getElementById('btnAddQueue');
		postQosAction({
			act: 'add_queue',
			name: document.getElementById('qsNameInput').value.trim(),
			target: document.getElementById('qsTargetInput').value.trim(),
			max_up: document.getElementById('qsUpInput').value.trim(),
			max_down: document.getElementById('qsDownInput').value.trim(),
			burst_up: document.getElementById('qsBurstUpInput').value.trim() || '0',
			burst_down: document.getElementById('qsBurstDownInput').value.trim() || '0',
			burst_time: document.getElementById('qsBurstTimeInput').value.trim() || '8s',
			priority: document.getElementById('qsPrioInput').value,
			queue_type: document.getElementById('qsTypeInput').value
		}, btn, 'Menyimpan...');
	}

	function openEditModal(item) {
		document.getElementById('editQueueId').value        = item.id;
		document.getElementById('editQueueName').value      = item.name;
		document.getElementById('editQueueTarget').value    = item.target;
		document.getElementById('editQueueUp').value        = item.up;
		document.getElementById('editQueueDown').value      = item.down;
		document.getElementById('editQueueBurstUp').value   = item.burst_up || '0';
		document.getElementById('editQueueBurstDown').value = item.burst_down || '0';
		document.getElementById('editQueuePrio').value      = String(item.priority || 8);
		document.getElementById('editQueueType').value      = 'keep';
		document.getElementById('qsEditOverlay').classList.add('is-open');
	}

	function closeEditModal() {
		document.getElementById('qsEditOverlay').classList.remove('is-open');
	}

	function submitEditQueue(e) {
		e.preventDefault();
		var btn = document.getElementById('btnSaveEditQueue');
		postQosAction({
			act: 'edit_queue',
			id: document.getElementById('editQueueId').value,
			name: document.getElementById('editQueueName').value.trim(),
			target: document.getElementById('editQueueTarget').value.trim(),
			max_up: document.getElementById('editQueueUp').value.trim(),
			max_down: document.getElementById('editQueueDown').value.trim(),
			burst_up: document.getElementById('editQueueBurstUp').value.trim() || '0',
			burst_down: document.getElementById('editQueueBurstDown').value.trim() || '0',
			priority: document.getElementById('editQueuePrio').value,
			queue_type: document.getElementById('editQueueType').value
		}, btn, 'Menyimpan...');
	}

	function confirmDeleteQueue(id, name) {
		qsPendingDeleteId = id;
		document.getElementById('qsDelName').textContent = name;
		document.getElementById('qsConfirmOverlay').classList.add('is-open');
	}

	function closeQsConfirm() {
		document.getElementById('qsConfirmOverlay').classList.remove('is-open');
		qsPendingDeleteId = '';
	}

	document.getElementById('btnConfirmDelQueue').addEventListener('click', function() {
		if (!qsPendingDeleteId) return;
		var id = qsPendingDeleteId;
		postQosAction({ act: 'delete_queue', id: id }, this, 'Menghapus...', function() {
			closeQsConfirm();
		});
	});

	function moveQueueOrder(btn, id, dest) {
		postQosAction({ act: 'move_queue', id: id, dest: dest }, btn, '');
	}

	function toggleQueue(btn, id, target) {
		postQosAction({ act: 'toggle_queue', id: id, target: target }, btn, '');
	}

	function resetQosCounters(btn) {
		postQosAction({ act: 'reset_counters' }, btn, 'Mereset...');
	}

	function disableFastTrackRules(btn) {
		postQosAction({ act: 'disable_fasttrack' }, btn, 'Memproses...');
	}

	function setQosFilter(filterType, tabEl) {
		qsCurrentFilter = filterType;
		var tabs = document.querySelectorAll('#qsFilterTabs .qs-tab');
		for (var i = 0; i < tabs.length; i++) {
			tabs[i].classList.remove('is-active');
		}
		if (tabEl) tabEl.classList.add('is-active');
		applyTableFilter();
	}

	function applyTableFilter() {
		var query = (document.getElementById('qsSearchInput').value || '').toLowerCase().trim();
		var rows = document.querySelectorAll('#qsMainTable tbody tr[data-qid]');
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var isStatic = r.getAttribute('data-static') === '1';
			var isActive = r.getAttribute('data-active') === '1';
			var hay = r.getAttribute('data-search') || '';

			var matchTab = true;
			if (qsCurrentFilter === 'static' && !isStatic) matchTab = false;
			if (qsCurrentFilter === 'active' && !isActive) matchTab = false;
			if (qsCurrentFilter === 'disabled' && isActive) matchTab = false;

			var matchSearch = (query === '' || hay.indexOf(query) !== -1);
			r.style.display = (matchTab && matchSearch) ? '' : 'none';
		}
	}

	function toggleLivePoll() {
		qsLivePollingActive = !qsLivePollingActive;
		var dot = document.getElementById('qsLiveDot');
		var txt = document.getElementById('qsLiveText');
		var btn = document.getElementById('btnTogglePoll');
		if (qsLivePollingActive) {
			dot.style.background = '#16a34a';
			dot.classList.add('is-live');
			txt.textContent = 'Sync 3 Detik';
			btn.textContent = 'Jeda';
		} else {
			dot.style.background = '#a1a1aa';
			dot.classList.remove('is-live');
			txt.textContent = 'Dijeda';
			btn.textContent = 'Lanjut';
		}
	}

	function exportQosCsv() {
		var rows = document.querySelectorAll('#qsMainTable tbody tr[data-qid]');
		var csv = ['No,Nama Ruangan,Target IP/Interface,Batas Kecepatan,Trafik Upload,Trafik Download,Kuota Upload,Kuota Download,Prioritas,Status'];
		for (var i = 0; i < rows.length; i++) {
			if (rows[i].style.display === 'none') continue;
			var cells = rows[i].querySelectorAll('td');
			var no     = cells[0].innerText.trim();
			var nama   = '"' + cells[1].querySelector('div').innerText.replace(/"/g, '""') + '"';
			var target = '"' + cells[2].innerText.trim().replace(/"/g, '""') + '"';
			var limit  = '"' + cells[3].querySelector('div').innerText.trim().replace(/"/g, '""') + '"';
			var upRate = '"' + cells[4].querySelector('.qs-live-up').innerText.trim() + '"';
			var dnRate = '"' + cells[4].querySelector('.qs-live-down').innerText.trim() + '"';
			var upByte = '"' + cells[5].querySelector('.qs-bytes-up').innerText.trim() + '"';
			var dnByte = '"' + cells[5].querySelector('.qs-bytes-dn').innerText.trim() + '"';
			var prio   = '"' + cells[6].innerText.trim() + '"';
			var status = '"' + cells[7].innerText.trim() + '"';
			csv.push([no, nama, target, limit, upRate, dnRate, upByte, dnByte, prio, status].join(','));
		}
		var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
		var link = document.createElement('a');
		link.href = URL.createObjectURL(blob);
		link.download = 'Laporan-Bandwidth-QoS-Ruangan.csv';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
	}

	// Live Polling Kecepatan, Utilisasi & Bytes setiap 3 detik
	setInterval(function() {
		if (!qsLivePollingActive) return;

		var formData = new URLSearchParams();
		formData.append('ajax_qos', '1');
		formData.append('act', 'poll_live');

		fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData.toString()
		})
		.then(function(res) { return res.text(); })
		.then(function(raw) {
			var s = raw.indexOf('<!--JSON_START-->');
			var e = raw.indexOf('<!--JSON_END-->');
			if (s === -1 || e === -1) return;
			var data = JSON.parse(raw.substring(s + 17, e));
			if (!data || data.status !== 'success') return;

			var totUp = document.getElementById('qsTotalUpLive');
			var totDn = document.getElementById('qsTotalDownLive');
			if (totUp) totUp.textContent = data.total_up_fmt;
			if (totDn) totDn.textContent = data.total_down_fmt;

			var rows = document.querySelectorAll('#qsMainTable tbody tr[data-qid]');
			for (var i = 0; i < rows.length; i++) {
				var qid = rows[i].getAttribute('data-qid');
				if (data.queues && data.queues[qid]) {
					var info = data.queues[qid];
					var upEl = rows[i].querySelector('.qs-live-up');
					var dnEl = rows[i].querySelector('.qs-live-down');
					var upPctEl = rows[i].querySelector('.qs-live-up-pct');
					var dnPctEl = rows[i].querySelector('.qs-live-dn-pct');
					var upBar = rows[i].querySelector('.qs-bar-up');
					var dnBar = rows[i].querySelector('.qs-bar-dn');
					var upByt = rows[i].querySelector('.qs-bytes-up');
					var dnByt = rows[i].querySelector('.qs-bytes-dn');

					if (upEl) upEl.textContent = info.up_fmt;
					if (dnEl) dnEl.textContent = info.down_fmt;
					if (upPctEl) upPctEl.textContent = info.up_pct + '%';
					if (dnPctEl) dnPctEl.textContent = info.down_pct + '%';
					if (upByt) upByt.textContent = info.up_bytes_fmt;
					if (dnByt) dnByt.textContent = info.dn_bytes_fmt;

					if (upBar) {
						upBar.style.width = info.up_pct + '%';
						upBar.style.backgroundColor = (info.up_pct >= 90) ? '#dc2626' : ((info.up_pct >= 75) ? '#f59e0b' : '#d97706');
					}
					if (dnBar) {
						dnBar.style.width = info.down_pct + '%';
						dnBar.style.backgroundColor = (info.down_pct >= 90) ? '#dc2626' : ((info.down_pct >= 75) ? '#f59e0b' : '#0284c7');
					}
				}
			}
		})
		.catch(function() {});
	}, 3000);
</script>