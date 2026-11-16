<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *  Custom Module: Web Firewall Site Blocker via MikroTik API
 *  (Firewall Filter Rules: Content, Layer 7 Protocols, & TLS Host)
 */
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
	exit;
}

// Daftar Paket Blokir Otomatis (menggunakan Layer 7 Protocol + Firewall Filter Drop)
$BLOCK_PRESETS = array(
	'judi' => array(
		'label'   => 'Judi Online',
		'desc'    => 'Blokir akses slot, gacor, maxwin, judol, togel, sbobet, pragmatic, scatter, zeus88',
		'pattern' => 'slot|gacor|maxwin|judol|togel|sbobet|pragmatic|scatter|zeus88|mahjongways'
	),
	'game' => array(
		'label'   => 'Game Online',
		'desc'    => 'Mobile Legends, Moonton, Free Fire, Garena, PUBG Mobile, Roblox, Steam, Valorant, Genshin',
		'pattern' => 'mobilelegends|moonton|youngjoygame|freefire|garenanow|pubgmobile|proximabeta|roblox|rbxcdn|steampowered|steamcommunity|riotgames|hoyoverse'
	),
	'sosmed' => array(
		'label'   => 'Sosial Media',
		'desc'    => 'Blokir akses TikTok, Instagram, Facebook, dan YouTube',
		'pattern' => 'tiktok|tiktokcdn|tiktokv|instagram|cdninstagram|facebook|fbcdn|youtube|googlevideo|youtu\.be'
	)
);

if (!function_exists('sbGetTrapError')) {
	function sbGetTrapError($res) {
		if (is_array($res) && isset($res['!trap'][0]['message'])) {
			return (string)$res['!trap'][0]['message'];
		}
		return '';
	}
}

if (!function_exists('sbFormatBytes')) {
	function sbFormatBytes($bytes) {
		$b = (float)$bytes;
		if ($b <= 0) return '0 B';
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$i = (int)floor(log($b, 1024));
		if ($i >= count($units)) $i = count($units) - 1;
		return round($b / pow(1024, $i), 1) . ' ' . $units[$i];
	}
}

if (!function_exists('sbFindFirstStaticFilterId')) {
	function sbFindFirstStaticFilterId($filters) {
		if (!is_array($filters)) return '';
		foreach ($filters as $f) {
			$isDyn = (isset($f['dynamic']) && ($f['dynamic'] === 'true' || $f['dynamic'] === true));
			if (!$isDyn && isset($f['.id'])) {
				return (string)$f['.id'];
			}
		}
		return '';
	}
}

if (!function_exists('sbSanitizeTarget')) {
	function sbSanitizeTarget($raw) {
		$clean = strtolower(trim((string)$raw));
		$clean = preg_replace('#^https?://#', '', $clean);
		$clean = preg_replace('#/.*$#', '', $clean);
		$clean = trim($clean, " \t\n\r\0\x0B*");
		return $clean;
	}
}

if (!function_exists('sbSanitizeSrcAddr')) {
	function sbSanitizeSrcAddr($raw) {
		$s = trim((string)$raw);
		if ($s === '') return '';
		if (!preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}(\/[0-9]{1,2}|-[0-9]{1,3}(\.[0-9]{1,3}){3})?$/', $s)) {
			return false;
		}
		return $s;
	}
}

// Pastikan koneksi API aktif
$isConnected = (isset($API) && is_object($API));
if ($isConnected && isset($API->connected) && !$API->connected) {
	$isConnected = $API->connect($iphost, $userhost, decrypt($passwdhost));
}

// Handler AJAX API MikroTik
if (isset($_POST['ajax_siteblock'])) {
	echo "<!--JSON_START-->";

	if (!$isConnected) {
		echo json_encode(array('status' => 'error', 'message' => 'Gagal terhubung ke MikroTik API.'));
		echo "<!--JSON_END-->";
		exit;
	}

	$act = isset($_POST['act']) ? (string)$_POST['act'] : '';

	// 1. Tambah Aturan Blokir Baru (Layer 7 / Firewall Content / TLS Host)
	if ($act === 'add_single') {
		$rawDomain = isset($_POST['domain']) ? $_POST['domain'] : '';
		$method    = isset($_POST['method']) ? (string)$_POST['method'] : 'layer7';
		$cat       = isset($_POST['category']) ? (string)$_POST['category'] : 'custom';
		$rawSrc    = isset($_POST['src_address']) ? $_POST['src_address'] : '';

		if (!in_array($cat, array('judi', 'game', 'sosmed', 'custom'))) {
			$cat = 'custom';
		}
		if (!in_array($method, array('layer7', 'content', 'tlshost'))) {
			$method = 'layer7';
		}

		$target = sbSanitizeTarget($rawDomain);
		if ($target === '') {
			echo json_encode(array('status' => 'error', 'message' => 'Nama domain atau kata kunci wajib diisi.'));
			echo "<!--JSON_END-->";
			exit;
		}

		$srcAddr = sbSanitizeSrcAddr($rawSrc);
		if ($srcAddr === false) {
			echo json_encode(array('status' => 'error', 'message' => 'Format IP / Subnet target tidak valid (contoh: 192.168.100.50 atau 192.168.100.0/24).'));
			echo "<!--JSON_END-->";
			exit;
		}

		$existingFilters = $API->comm("/ip/firewall/filter/print");
		$topFilterId     = sbFindFirstStaticFilterId($existingFilters);
		$commentTag      = "mikhmon-block:" . $cat;

		if ($method === 'layer7') {
			$slug   = preg_replace('/[^a-z0-9]+/', '_', $target);
			$slug   = trim(substr($slug, 0, 22), '_');
			if ($slug === '') $slug = 'site';
			$l7Name = 'mhm_l7_' . $slug . '_' . substr(md5($target . microtime(true)), 0, 4);
			$rx     = '^.+(' . preg_quote($target, '/') . ').*$';

			$resL7 = $API->comm("/ip/firewall/layer7-protocol/add", array(
				"name"    => $l7Name,
				"regexp"  => $rx,
				"comment" => $commentTag
			));
			$errL7 = sbGetTrapError($resL7);
			if ($errL7 !== '') {
				echo json_encode(array('status' => 'error', 'message' => 'Gagal membuat Layer 7 Protocol: ' . $errL7));
				echo "<!--JSON_END-->";
				exit;
			}

			$fParams = array(
				"chain"           => "forward",
				"layer7-protocol" => $l7Name,
				"action"          => "drop",
				"comment"         => $commentTag
			);
			if ($srcAddr !== '') $fParams["src-address"] = $srcAddr;
			if ($topFilterId !== '') $fParams["place-before"] = $topFilterId;

			$resF = $API->comm("/ip/firewall/filter/add", $fParams);
			$errF = sbGetTrapError($resF);
			if ($errF !== '') {
				$l7List = $API->comm("/ip/firewall/layer7-protocol/print", array("?name" => $l7Name));
				if (is_array($l7List) && isset($l7List[0]['.id'])) {
					$API->comm("/ip/firewall/layer7-protocol/remove", array(".id" => $l7List[0]['.id']));
				}
				echo json_encode(array('status' => 'error', 'message' => 'Gagal menambah Firewall Filter: ' . $errF));
				echo "<!--JSON_END-->";
				exit;
			}
		} elseif ($method === 'content') {
			$fParams = array(
				"chain"   => "forward",
				"content" => $target,
				"action"  => "drop",
				"comment" => $commentTag
			);
			if ($srcAddr !== '') $fParams["src-address"] = $srcAddr;
			if ($topFilterId !== '') $fParams["place-before"] = $topFilterId;

			$resF = $API->comm("/ip/firewall/filter/add", $fParams);
			$errF = sbGetTrapError($resF);
			if ($errF !== '') {
				echo json_encode(array('status' => 'error', 'message' => 'Gagal menambah Firewall Content: ' . $errF));
				echo "<!--JSON_END-->";
				exit;
			}
		} elseif ($method === 'tlshost') {
			$fParams = array(
				"chain"    => "forward",
				"protocol" => "tcp",
				"dst-port" => "443",
				"tls-host" => "*" . $target . "*",
				"action"   => "drop",
				"comment"  => $commentTag
			);
			if ($srcAddr !== '') $fParams["src-address"] = $srcAddr;
			if ($topFilterId !== '') $fParams["place-before"] = $topFilterId;

			$resF = $API->comm("/ip/firewall/filter/add", $fParams);
			$errF = sbGetTrapError($resF);
			if ($errF !== '') {
				echo json_encode(array('status' => 'error', 'message' => 'Gagal menambah TLS Host Filter: ' . $errF));
				echo "<!--JSON_END-->";
				exit;
			}
		}

		echo json_encode(array('status' => 'success', 'message' => 'Aturan Firewall blokir "' . $target . '" berhasil diterapkan ke MikroTik.'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 2. Terapkan / Lepas Paket Blokir 1-Klik (Layer 7 Protocol + Firewall Filter)
	if ($act === 'apply_preset') {
		$presetKey = isset($_POST['preset']) ? (string)$_POST['preset'] : '';
		$op        = isset($_POST['op']) ? (string)$_POST['op'] : 'enable';
		$rawSrc    = isset($_POST['src_address']) ? $_POST['src_address'] : '';

		if (!isset($BLOCK_PRESETS[$presetKey])) {
			echo json_encode(array('status' => 'error', 'message' => 'Paket blokir tidak ditemukan.'));
			echo "<!--JSON_END-->";
			exit;
		}

		$srcAddr = sbSanitizeSrcAddr($rawSrc);
		if ($srcAddr === false) {
			echo json_encode(array('status' => 'error', 'message' => 'Format IP / Subnet target tidak valid.'));
			echo "<!--JSON_END-->";
			exit;
		}

		$l7PresetName = 'mhm_preset_' . $presetKey;
		$commentTag   = 'mikhmon-block:' . $presetKey;
		$rxPattern    = '^.+(' . $BLOCK_PRESETS[$presetKey]['pattern'] . ').*$';

		$existingFilters = $API->comm("/ip/firewall/filter/print");
		$existingL7      = $API->comm("/ip/firewall/layer7-protocol/print");

		if ($op === 'remove') {
			if (is_array($existingFilters)) {
				foreach ($existingFilters as $f) {
					if (isset($f['layer7-protocol']) && $f['layer7-protocol'] === $l7PresetName && isset($f['.id'])) {
						$API->comm("/ip/firewall/filter/remove", array(".id" => $f['.id']));
					}
				}
			}
			if (is_array($existingL7)) {
				foreach ($existingL7 as $l) {
					if (isset($l['name']) && $l['name'] === $l7PresetName && isset($l['.id'])) {
						$API->comm("/ip/firewall/layer7-protocol/remove", array(".id" => $l['.id']));
					}
				}
			}
			echo json_encode(array('status' => 'success', 'message' => 'Paket blokir ' . $BLOCK_PRESETS[$presetKey]['label'] . ' berhasil dihapus dari Firewall.'));
			echo "<!--JSON_END-->";
			exit;
		}

		// Pastikan Layer 7 Protocol untuk preset ada & ter-update
		$foundL7Id = '';
		if (is_array($existingL7)) {
			foreach ($existingL7 as $l) {
				if (isset($l['name']) && $l['name'] === $l7PresetName && isset($l['.id'])) {
					$foundL7Id = (string)$l['.id'];
					break;
				}
			}
		}

		if ($foundL7Id !== '') {
			$API->comm("/ip/firewall/layer7-protocol/set", array(
				".id"     => $foundL7Id,
				"regexp"  => $rxPattern,
				"comment" => $commentTag
			));
		} else {
			$resL7 = $API->comm("/ip/firewall/layer7-protocol/add", array(
				"name"    => $l7PresetName,
				"regexp"  => $rxPattern,
				"comment" => $commentTag
			));
			$errL7 = sbGetTrapError($resL7);
			if ($errL7 !== '') {
				echo json_encode(array('status' => 'error', 'message' => 'MikroTik menolak Layer 7 Protocol: ' . $errL7));
				echo "<!--JSON_END-->";
				exit;
			}
		}

		// Cek apakah Filter Rule untuk preset sudah ada
		$foundFilterId = '';
		if (is_array($existingFilters)) {
			foreach ($existingFilters as $f) {
				if (isset($f['layer7-protocol']) && $f['layer7-protocol'] === $l7PresetName && isset($f['.id'])) {
					$foundFilterId = (string)$f['.id'];
					break;
				}
			}
		}

		if ($foundFilterId !== '') {
			$setParams = array(
				".id"      => $foundFilterId,
				"disabled" => "no"
			);
			if ($srcAddr !== '') {
				$setParams["src-address"] = $srcAddr;
			} else {
				$API->comm("/ip/firewall/filter/unset", array(
					".id"        => $foundFilterId,
					"value-name" => "src-address"
				));
			}
			$API->comm("/ip/firewall/filter/set", $setParams);
		} else {
			$topFilterId = sbFindFirstStaticFilterId($existingFilters);
			$fParams = array(
				"chain"           => "forward",
				"layer7-protocol" => $l7PresetName,
				"action"          => "drop",
				"comment"         => $commentTag
			);
			if ($srcAddr !== '') $fParams["src-address"] = $srcAddr;
			if ($topFilterId !== '') $fParams["place-before"] = $topFilterId;

			$resF = $API->comm("/ip/firewall/filter/add", $fParams);
			$errF = sbGetTrapError($resF);
			if ($errF !== '') {
				echo json_encode(array('status' => 'error', 'message' => 'MikroTik menolak Firewall Filter: ' . $errF));
				echo "<!--JSON_END-->";
				exit;
			}
		}

		echo json_encode(array('status' => 'success', 'message' => 'Paket blokir ' . $BLOCK_PRESETS[$presetKey]['label'] . ' aktif di Firewall Router!'));
		echo "<!--JSON_END-->";
		exit;
	}

	// 3. Toggle Enable / Disable 1 Aturan Firewall
	if ($act === 'toggle_rule') {
		$id     = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
		$target = isset($_POST['target']) ? (string)$_POST['target'] : 'disable';
		if ($id === '') {
			echo json_encode(array('status' => 'error', 'message' => 'ID aturan tidak valid.'));
			echo "<!--JSON_END-->";
			exit;
		}
		if ($target === 'enable') {
			$API->comm("/ip/firewall/filter/enable", array(".id" => $id));
		} else {
			$API->comm("/ip/firewall/filter/disable", array(".id" => $id));
		}

		echo json_encode(array(
			'status'  => 'success',
			'message' => ($target === 'enable') ? 'Aturan Firewall blokir diaktifkan.' : 'Aturan Firewall dijeda sementara.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	// 4. Hapus 1 atau Banyak Aturan Firewall (+ bersihkan Layer 7 terkait)
	if ($act === 'delete_rules') {
		$rawIds = isset($_POST['ids']) ? trim((string)$_POST['ids']) : '';
		$ids = array_filter(array_map('trim', explode(',', $rawIds)));
		if (empty($ids)) {
			echo json_encode(array('status' => 'error', 'message' => 'Tidak ada aturan yang dipilih.'));
			echo "<!--JSON_END-->";
			exit;
		}

		$existingFilters = $API->comm("/ip/firewall/filter/print");
		$l7ToDelete = array();
		if (is_array($existingFilters)) {
			foreach ($existingFilters as $f) {
				if (isset($f['.id']) && in_array($f['.id'], $ids)) {
					if (!empty($f['layer7-protocol'])) {
						$l7ToDelete[$f['layer7-protocol']] = true;
					}
				}
			}
		}

		foreach ($ids as $sid) {
			$API->comm("/ip/firewall/filter/remove", array(".id" => $sid));
		}

		if (!empty($l7ToDelete)) {
			$existingL7 = $API->comm("/ip/firewall/layer7-protocol/print");
			if (is_array($existingL7)) {
				foreach ($existingL7 as $l) {
					if (isset($l['name']) && isset($l7ToDelete[$l['name']]) && isset($l['.id'])) {
						$API->comm("/ip/firewall/layer7-protocol/remove", array(".id" => $l['.id']));
					}
				}
			}
		}

		echo json_encode(array(
			'status'  => 'success',
			'message' => count($ids) . ' aturan blokir berhasil dihapus dari Firewall MikroTik.'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	// 5. Reset Hit Counter Firewall
	if ($act === 'reset_counters') {
		$existingFilters = $API->comm("/ip/firewall/filter/print");
		$resetCount = 0;
		if (is_array($existingFilters)) {
			foreach ($existingFilters as $f) {
				$cmt = isset($f['comment']) ? (string)$f['comment'] : '';
				if (strpos($cmt, 'mikhmon-block:') === 0 && isset($f['.id'])) {
					$API->comm("/ip/firewall/filter/reset-counters", array(".id" => $f['.id']));
					$resetCount++;
				}
			}
		}
		echo json_encode(array(
			'status'  => 'success',
			'message' => 'Counter paket terblokir di-reset ke 0 (' . $resetCount . ' aturan).'
		));
		echo "<!--JSON_END-->";
		exit;
	}

	echo json_encode(array('status' => 'error', 'message' => 'Perintah tidak dikenali.'));
	echo "<!--JSON_END-->";
	exit;
}

// Ambil data Live Firewall Filter & Layer 7 dari MikroTik API
$allFilters = $isConnected ? $API->comm("/ip/firewall/filter/print") : array();
$allL7      = $isConnected ? $API->comm("/ip/firewall/layer7-protocol/print") : array();

$l7Map = array();
if (is_array($allL7)) {
	foreach ($allL7 as $l) {
		if (isset($l['name'])) {
			$l7Map[$l['name']] = isset($l['regexp']) ? (string)$l['regexp'] : '';
		}
	}
}

$blockRows = array();
$presetActiveMap = array(
	'judi'   => false,
	'game'   => false,
	'sosmed' => false
);
$catCounts = array(
	'judi'   => 0,
	'game'   => 0,
	'sosmed' => 0,
	'custom' => 0
);
$activeRulesCount = 0;
$totalBlockedPackets = 0;
$totalBlockedBytes   = 0;

if (is_array($allFilters)) {
	foreach ($allFilters as $f) {
		$cmt = isset($f['comment']) ? (string)$f['comment'] : '';
		if (strpos($cmt, 'mikhmon-block:') !== 0) {
			continue;
		}
		$cat = substr($cmt, strlen('mikhmon-block:'));
		if (!isset($catCounts[$cat])) {
			$cat = 'custom';
		}

		$isDisabled = (isset($f['disabled']) && ($f['disabled'] === 'true' || $f['disabled'] === true));
		if (!$isDisabled) {
			$activeRulesCount++;
		}
		$catCounts[$cat]++;

		$pkts  = isset($f['packets']) ? (int)$f['packets'] : 0;
		$bytes = isset($f['bytes']) ? (float)$f['bytes'] : 0;
		$totalBlockedPackets += $pkts;
		$totalBlockedBytes   += $bytes;

		$l7Name  = isset($f['layer7-protocol']) ? (string)$f['layer7-protocol'] : '';
		$content = isset($f['content']) ? (string)$f['content'] : '';
		$tlsHost = isset($f['tls-host']) ? (string)$f['tls-host'] : '';
		$srcAddr = isset($f['src-address']) ? (string)$f['src-address'] : '';

		if ($l7Name === 'mhm_preset_judi')   $presetActiveMap['judi']   = true;
		if ($l7Name === 'mhm_preset_game')   $presetActiveMap['game']   = true;
		if ($l7Name === 'mhm_preset_sosmed') $presetActiveMap['sosmed'] = true;

		if ($l7Name !== '') {
			$rx = isset($l7Map[$l7Name]) ? $l7Map[$l7Name] : $l7Name;
			$cleanDisp = preg_replace('/^\^\.\+\((.+)\)\.\*\$$/', '$1', $rx);
			$cleanDisp = str_replace('\\.', '.', $cleanDisp);
			$cleanDisp = str_replace('|', ', ', $cleanDisp);
			$methodLabel = 'Layer 7 Protocol';
			$rawDetail   = $l7Name . ' → ' . $rx;
		} elseif ($tlsHost !== '') {
			$cleanDisp   = $tlsHost;
			$methodLabel = 'TLS Host (HTTPS)';
			$rawDetail   = 'tcp/443 tls-host=' . $tlsHost;
		} else {
			$cleanDisp   = $content;
			$methodLabel = 'Firewall Content';
			$rawDetail   = 'forward content="' . $content . '"';
		}

		$blockRows[] = array(
			'id'          => isset($f['.id']) ? (string)$f['.id'] : '',
			'display'     => $cleanDisp,
			'method'      => $methodLabel,
			'detail'      => $rawDetail,
			'src_address' => $srcAddr,
			'packets'     => $pkts,
			'bytes_fmt'   => sbFormatBytes($bytes),
			'category'    => $cat,
			'disabled'    => $isDisabled
		);
	}
}

$totalRules = count($blockRows);

$catLabels = array(
	'judi'   => array('name' => 'Judi Online',  'cls' => 'is-red'),
	'game'   => array('name' => 'Game Online',  'cls' => 'is-amber'),
	'sosmed' => array('name' => 'Sosial Media', 'cls' => 'is-blue'),
	'custom' => array('name' => 'Custom',       'cls' => 'is-zinc')
);
?>

<style>
	#mhm-siteblock {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}
	#mhm-siteblock *,
	#mhm-siteblock *::before,
	#mhm-siteblock *::after {
		box-sizing: border-box;
	}

	/* Topbar */
	#mhm-siteblock .sb-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		background: #ffffff;
	}
	#mhm-siteblock .sb-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-siteblock .sb-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-siteblock .sb-pill {
		font-size: 11.5px;
		font-weight: 500;
		color: #52525b;
		background: #f4f4f5;
		border: 1px solid #e4e4e7;
		padding: 2px 9px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}
	#mhm-siteblock .sb-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}

	/* Buttons */
	#mhm-siteblock .sb-btn {
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
	#mhm-siteblock .sb-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-siteblock .sb-btn.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff !important;
	}
	#mhm-siteblock .sb-btn.is-primary:hover {
		background: #27272a;
		border-color: #27272a;
	}
	#mhm-siteblock .sb-btn.is-danger {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626 !important;
	}
	#mhm-siteblock .sb-btn.is-danger:hover {
		background: #fee2e2;
		border-color: #fca5a5;
	}
	#mhm-siteblock .sb-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
		pointer-events: none;
	}

	/* Metric Strip */
	#mhm-siteblock .sb-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 768px) {
		#mhm-siteblock .sb-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-siteblock .sb-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
		cursor: pointer;
		transition: background-color 0.15s ease;
	}
	#mhm-siteblock .sb-metric-item:hover {
		background: #f4f4f5;
	}
	#mhm-siteblock .sb-metric-item:last-child {
		border-right: none;
	}
	#mhm-siteblock .sb-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-siteblock .sb-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-siteblock .sb-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		display: inline-block;
		flex-shrink: 0;
	}
	#mhm-siteblock .sb-dot.is-green { background: #10b981; }
	#mhm-siteblock .sb-dot.is-red { background: #ef4444; }
	#mhm-siteblock .sb-dot.is-amber { background: #f59e0b; }
	#mhm-siteblock .sb-dot.is-blue { background: #0284c7; }
	#mhm-siteblock .sb-dot.is-zinc { background: #71717a; }

	/* Preset Grid (3 Kolom Simetris) */
	#mhm-siteblock .sb-presets-wrap {
		padding: 18px 24px;
		border-bottom: 1px solid #e4e4e7;
		background: #ffffff;
	}
	#mhm-siteblock .sb-section-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 12px;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-siteblock .sb-section-title {
		font-size: 12px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: #52525b;
	}
	#mhm-siteblock .sb-preset-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 14px;
	}
	@media (max-width: 820px) {
		#mhm-siteblock .sb-preset-grid {
			grid-template-columns: 1fr;
		}
	}
	#mhm-siteblock .sb-preset-card {
		border: 1px solid #e4e4e7;
		border-radius: 10px;
		padding: 15px;
		background: #fafafa;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		gap: 12px;
	}
	#mhm-siteblock .sb-preset-card.is-active {
		background: #ffffff;
		border-color: #bbf7d0;
		box-shadow: 0 1px 3px rgba(9, 9, 11, 0.04);
	}
	#mhm-siteblock .sb-preset-top {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		margin-bottom: 4px;
	}
	#mhm-siteblock .sb-preset-name {
		font-size: 13.5px;
		font-weight: 600;
		color: #09090b;
	}
	#mhm-siteblock .sb-preset-desc {
		font-size: 11.5px;
		color: #71717a;
		line-height: 1.45;
	}
	#mhm-siteblock .sb-preset-foot {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	#mhm-siteblock .sb-preset-btn {
		flex: 1;
		height: 31px;
		padding: 0 10px;
		border-radius: 7px;
		font-size: 11.5px;
		font-weight: 500;
		cursor: pointer;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #18181b;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 5px;
		transition: all 0.15s ease;
	}
	#mhm-siteblock .sb-preset-btn:hover {
		background: #f4f4f5;
	}
	#mhm-siteblock .sb-preset-btn.is-apply {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}
	#mhm-siteblock .sb-preset-btn.is-apply:hover {
		background: #27272a;
	}
	#mhm-siteblock .sb-preset-btn:disabled {
		opacity: 0.6;
		cursor: not-allowed;
		pointer-events: none;
	}

	/* Add Custom Form Bar */
	#mhm-siteblock .sb-add-bar {
		padding: 16px 24px;
		background: #fafafa;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 9px;
	}
	#mhm-siteblock .sb-input {
		flex: 2;
		min-width: 200px;
		height: 36px !important;
		padding: 0 12px !important;
		background: #ffffff !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		color: #09090b !important;
		margin: 0 !important;
	}
	#mhm-siteblock .sb-input.is-ip {
		flex: 1;
		min-width: 175px;
	}
	#mhm-siteblock .sb-input:focus {
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-siteblock .sb-select {
		height: 36px !important;
		padding: 0 11px !important;
		background: #ffffff !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		font-weight: 500 !important;
		color: #18181b !important;
		margin: 0 !important;
		cursor: pointer;
	}

	/* Filter Toolbar */
	#mhm-siteblock .sb-filterbar {
		padding: 13px 24px;
		background: #ffffff;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
	}
	#mhm-siteblock .sb-search {
		position: relative;
		width: 260px;
		max-width: 100%;
	}
	#mhm-siteblock .sb-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-siteblock .sb-search input {
		width: 100% !important;
		height: 34px !important;
		padding: 0 12px 0 32px !important;
		background: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		color: #09090b !important;
		margin: 0 !important;
	}

	/* Table */
	#mhm-siteblock .sb-table-wrap {
		width: 100%;
		overflow-x: auto;
		max-height: 60vh;
	}
	#mhm-siteblock table.sb-table {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		text-align: left;
	}
	#mhm-siteblock table.sb-table thead th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #fafafa !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 600 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.05em !important;
		padding: 11px 20px !important;
		border-bottom: 1px solid #e4e4e7 !important;
		white-space: nowrap;
	}
	#mhm-siteblock table.sb-table tbody td {
		padding: 12px 20px !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-siteblock table.sb-table tbody tr:hover {
		background: #fafafa !important;
	}

	/* Badges */
	.sb-badge {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 2px 9px;
		border-radius: 6px;
		font-size: 11px;
		font-weight: 600;
		white-space: nowrap;
	}
	.sb-badge.is-red { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
	.sb-badge.is-amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
	.sb-badge.is-blue { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
	.sb-badge.is-zinc { background: #f4f4f5; color: #52525b; border: 1px solid #e4e4e7; }
	.sb-badge.is-on { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
	.sb-badge.is-off { background: #f4f4f5; color: #71717a; border: 1px solid #e4e4e7; }

	.sb-row-btn {
		height: 28px;
		padding: 0 9px;
		border-radius: 6px;
		font-size: 11.5px;
		font-weight: 500;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 4px;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #18181b;
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	.sb-row-btn:hover { background: #f4f4f5; }
	.sb-row-btn.is-del {
		border-color: #fecaca;
		background: #fef2f2;
		color: #dc2626;
	}
	.sb-row-btn.is-del:hover {
		background: #dc2626;
		border-color: #dc2626;
		color: #ffffff;
	}

	/* Footer */
	#mhm-siteblock .sb-footer {
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

	/* Custom Confirm Modal */
	@keyframes sbModalFade {
		from { opacity: 0; }
		to   { opacity: 1; }
	}
	@keyframes sbModalPop {
		from { opacity: 0; transform: translateY(8px) scale(0.96); }
		to   { opacity: 1; transform: translateY(0) scale(1); }
	}
	#sb-confirm-overlay {
		position: fixed;
		inset: 0;
		z-index: 99998;
		background: rgba(9, 9, 11, 0.45);
		backdrop-filter: blur(3px);
		-webkit-backdrop-filter: blur(3px);
		display: none;
		align-items: center;
		justify-content: center;
		padding: 16px;
		animation: sbModalFade 0.16s ease-out;
	}
	#sb-confirm-overlay.is-open {
		display: flex;
	}
	.sb-modal-box {
		width: 100%;
		max-width: 410px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 14px;
		box-shadow: 0 24px 48px -12px rgba(9, 9, 11, 0.25);
		overflow: hidden;
		animation: sbModalPop 0.18s cubic-bezier(0.16, 1, 0.3, 1);
	}
	.sb-modal-body {
		padding: 22px 22px 18px 22px;
		display: flex;
		align-items: flex-start;
		gap: 14px;
	}
	.sb-modal-icon {
		width: 40px;
		height: 40px;
		border-radius: 10px;
		background: #fef2f2;
		border: 1px solid #fecaca;
		color: #dc2626;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 16px;
		flex-shrink: 0;
	}
	.sb-modal-title {
		margin: 0 0 5px 0;
		font-size: 15px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	.sb-modal-desc {
		margin: 0;
		font-size: 13px;
		color: #52525b;
		line-height: 1.5;
	}
	.sb-modal-target {
		margin-top: 10px;
		padding: 7px 10px;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		border-radius: 7px;
		font-size: 12px;
		font-weight: 500;
		color: #18181b;
		font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
		word-break: break-word;
	}
	.sb-modal-foot {
		padding: 12px 22px;
		background: #fafafa;
		border-top: 1px solid #f4f4f5;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
	}
	.sb-modal-btn {
		height: 35px;
		padding: 0 15px;
		border-radius: 8px;
		font-size: 12.5px;
		font-weight: 500;
		cursor: pointer;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #18181b;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		transition: all 0.15s ease;
	}
	.sb-modal-btn:hover {
		background: #f4f4f5;
		border-color: #d4d4d8;
	}
	.sb-modal-btn.is-danger {
		background: #dc2626;
		border-color: #dc2626;
		color: #ffffff;
	}
	.sb-modal-btn.is-danger:hover {
		background: #b91c1c;
		border-color: #b91c1c;
	}

	/* Toast Notification */
	@keyframes sbToastSlide {
		from { opacity: 0; transform: translateX(24px); }
		to   { opacity: 1; transform: translateX(0); }
	}
	@keyframes sbToastTimer {
		from { width: 100%; }
		to   { width: 0%; }
	}
	#sb-toast-box {
		position: fixed;
		top: 68px;
		right: 22px;
		z-index: 99999;
		display: flex;
		flex-direction: column;
		gap: 9px;
		pointer-events: none;
	}
	.sb-toast {
		pointer-events: auto;
		position: relative;
		overflow: hidden;
		min-width: 295px;
		max-width: 410px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 11px;
		padding: 13px 14px 15px 14px;
		box-shadow: 0 14px 30px -8px rgba(9, 9, 11, 0.2);
		display: flex;
		align-items: flex-start;
		gap: 11px;
		animation: sbToastSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		transition: opacity 0.2s ease, transform 0.2s ease;
	}
	.sb-toast-bar {
		position: absolute;
		left: 0;
		bottom: 0;
		height: 3px;
		background: #16a34a;
		animation: sbToastTimer 3.5s linear forwards;
	}
	.sb-toast.is-error .sb-toast-bar {
		background: #dc2626;
		animation-duration: 5.5s;
	}
	.sb-toast-close {
		background: transparent;
		border: none;
		color: #a1a1aa;
		cursor: pointer;
		padding: 0 2px;
		font-size: 14px;
		line-height: 1;
	}
	.sb-toast-close:hover {
		color: #18181b;
	}
</style>

<!-- Toast Container -->
<div id="sb-toast-box"></div>

<!-- Custom Confirmation Modal -->
<div id="sb-confirm-overlay" onclick="if(event.target === this) closeSbConfirm();">
	<div class="sb-modal-box" role="dialog" aria-modal="true" aria-labelledby="sbModalTitle">
		<div class="sb-modal-body">
			<div class="sb-modal-icon">
				<i class="fa fa-trash-o"></i>
			</div>
			<div style="flex:1; min-width:0;">
				<h4 class="sb-modal-title" id="sbModalTitle">Konfirmasi Hapus</h4>
				<p class="sb-modal-desc" id="sbModalDesc">Apakah Anda yakin ingin menghapus aturan ini?</p>
				<div class="sb-modal-target" id="sbModalTarget" style="display:none;"></div>
			</div>
		</div>
		<div class="sb-modal-foot">
			<button type="button" class="sb-modal-btn" id="sbModalCancelBtn" onclick="closeSbConfirm();">Batal</button>
			<button type="button" class="sb-modal-btn is-danger" id="sbModalConfirmBtn">
				<i class="fa fa-trash"></i>
				<span id="sbModalConfirmLabel">Ya, Hapus</span>
			</button>
		</div>
	</div>
</div>

<div id="mhm-siteblock">
	<!-- Top Bar -->
	<div class="sb-topbar">
		<div class="sb-title-group">
			<h3 class="sb-title">Blokir Situs &amp; Konten (Firewall API)</h3>
			<span class="sb-pill" id="sb_badge_count"><?= $activeRulesCount; ?> aktif dari <?= $totalRules; ?> aturan</span>
		</div>

		<div class="sb-actions">
			<button type="button" onclick="resetFirewallCounters(this);" class="sb-btn" <?= ($totalRules == 0) ? 'disabled' : ''; ?> title="Reset angka hit paket terblokir menjadi 0">
				<i class="fa fa-history" style="color:#71717a;"></i>
				<span>Reset Counter</span>
			</button>

			<button type="button" onclick="location.reload();" class="sb-btn" title="Muat ulang &amp; cek counter paket terbaru">
				<i class="fa fa-refresh" style="color:#71717a;"></i>
				<span>Refresh</span>
			</button>
		</div>
	</div>

	<!-- Metric Strip -->
	<div class="sb-metrics">
		<div class="sb-metric-item" onclick="quickFilterCat('all');">
			<div class="sb-metric-label">Aturan Firewall Aktif</div>
			<div class="sb-metric-val">
				<span class="sb-dot is-green"></span>
				<span><?= $activeRulesCount; ?> <span style="font-size:12px; font-weight:400; color:#71717a;">/ <?= $totalRules; ?></span></span>
			</div>
		</div>
		<div class="sb-metric-item" onclick="quickFilterCat('judi');">
			<div class="sb-metric-label">Judi Online</div>
			<div class="sb-metric-val">
				<span class="sb-dot is-red"></span>
				<span><?= $catCounts['judi']; ?></span>
			</div>
		</div>
		<div class="sb-metric-item" onclick="quickFilterCat('game');">
			<div class="sb-metric-label">Game Online</div>
			<div class="sb-metric-val">
				<span class="sb-dot is-amber"></span>
				<span><?= $catCounts['game']; ?></span>
			</div>
		</div>
		<div class="sb-metric-item" onclick="quickFilterCat('all');" title="Total paket &amp; byte koneksi yang berhasil dijatuhkan (drop) oleh router">
			<div class="sb-metric-label">Koneksi Diblokir (Live)</div>
			<div class="sb-metric-val">
				<span class="sb-dot is-blue"></span>
				<span><?= number_format($totalBlockedPackets); ?> <span style="font-size:12px; font-weight:400; color:#71717a;">paket (<?= sbFormatBytes($totalBlockedBytes); ?>)</span></span>
			</div>
		</div>
	</div>

	<!-- Paket Blokir Cepat 1-Klik -->
	<div class="sb-presets-wrap">
		<div class="sb-section-head">
			<span class="sb-section-title">Paket Blokir Otomatis (Layer 7 Protocol + Firewall Drop)</span>
			<span style="font-size:11.5px; color:#71717a;">Langsung membuat aturan di IP &rarr; Firewall &rarr; Layer 7 &amp; Filter Rules via API</span>
		</div>

		<div class="sb-preset-grid">
			<?php foreach ($BLOCK_PRESETS as $pKey => $pInfo): ?>
				<?php $isPresetOn = $presetActiveMap[$pKey]; ?>
				<div class="sb-preset-card <?= $isPresetOn ? 'is-active' : ''; ?>">
					<div>
						<div class="sb-preset-top">
							<span class="sb-preset-name"><?= htmlspecialchars($pInfo['label']); ?></span>
							<span class="sb-badge <?= $isPresetOn ? 'is-on' : 'is-off'; ?>">
								<?= $isPresetOn ? 'Aktif' : 'Belum aktif'; ?>
							</span>
						</div>
						<div class="sb-preset-desc"><?= htmlspecialchars($pInfo['desc']); ?></div>
					</div>

					<div class="sb-preset-foot">
						<button type="button" class="sb-preset-btn is-apply" onclick="applyPreset(this, '<?= $pKey; ?>', 'enable', '<?= htmlspecialchars($pInfo['label'], ENT_QUOTES); ?>');">
							<i class="fa fa-ban"></i>
							<span><?= $isPresetOn ? 'Perbarui' : 'Aktifkan Blokir'; ?></span>
						</button>
						<?php if ($isPresetOn): ?>
							<button type="button" class="sb-preset-btn" onclick="applyPreset(this, '<?= $pKey; ?>', 'remove', '<?= htmlspecialchars($pInfo['label'], ENT_QUOTES); ?>');" title="Hapus aturan paket ini dari Firewall">
								<i class="fa fa-trash" style="color:#dc2626;"></i>
							</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Form Tambah Custom Domain / Kata Kunci -->
	<form class="sb-add-bar" onsubmit="addCustomBlock(event);">
		<input
			type="text"
			id="sbDomainInput"
			class="sb-input"
			placeholder="Domain / kata kunci (misal: youtube.com, roblox, slot)..."
			required
		>

		<select id="sbMethodSelect" class="sb-select" title="Metode Firewall MikroTik">
			<option value="layer7">Metode: Layer 7 Protocol (Spesifik)</option>
			<option value="content">Metode: Firewall Content (Cepat)</option>
			<option value="tlshost">Metode: TLS Host (Khusus HTTPS)</option>
		</select>

		<input
			type="text"
			id="sbSrcAddrInput"
			class="sb-input is-ip"
			placeholder="Target IP/Subnet (kosong = Semua)"
			title="Kosongkan untuk semua perangkat, atau isi IP/Subnet (misal: 192.168.100.0/24)"
		>

		<select id="sbCatSelect" class="sb-select" title="Kategori blokir">
			<option value="judi">Kategori: Judi Online</option>
			<option value="game">Kategori: Game Online</option>
			<option value="sosmed">Kategori: Sosial Media</option>
			<option value="custom" selected>Kategori: Custom</option>
		</select>

		<button type="submit" id="btnAddBlock" class="sb-btn is-primary" style="height:36px; padding:0 16px;">
			<i class="fa fa-plus"></i>
			<span>Tambah Blokir</span>
		</button>
	</form>

	<!-- Filter & Search Bar -->
	<div class="sb-filterbar">
		<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
			<select id="sbFilterCat" class="sb-select" style="height:34px !important;" onchange="filterBlockTable();">
				<option value="all">Semua Kategori (<?= $totalRules; ?>)</option>
				<option value="judi">Judi Online (<?= $catCounts['judi']; ?>)</option>
				<option value="game">Game Online (<?= $catCounts['game']; ?>)</option>
				<option value="sosmed">Sosial Media (<?= $catCounts['sosmed']; ?>)</option>
				<option value="custom">Custom (<?= $catCounts['custom']; ?>)</option>
			</select>

			<select id="sbFilterStatus" class="sb-select" style="height:34px !important;" onchange="filterBlockTable();">
				<option value="all">Semua Status</option>
				<option value="active">Aktif</option>
				<option value="disabled">Nonaktif</option>
			</select>

			<button type="button" id="btnDeleteVisibleBlocks" class="sb-btn is-danger" onclick="deleteVisibleRules(this);" <?= ($totalRules == 0) ? 'disabled' : ''; ?>>
				<i class="fa fa-trash"></i>
				<span id="lblDeleteVisibleBlocks">Hapus Tampil (<?= $totalRules; ?>)</span>
			</button>
		</div>

		<div class="sb-search">
			<i class="fa fa-search"></i>
			<input type="text" id="sbSearchInput" placeholder="Cari domain, kata kunci, IP, atau metode..." oninput="filterBlockTable();">
		</div>
	</div>

	<!-- Tabel Daftar Aturan Firewall Aktif -->
	<div class="sb-table-wrap">
		<table class="table sb-table">
			<thead>
				<tr>
					<th>Target Situs / Kata Kunci</th>
					<th style="width:140px;">Kategori</th>
					<th style="width:155px;">Target Klien</th>
					<th style="width:165px;">Trafik Diblokir</th>
					<th style="width:110px;">Status</th>
					<th style="width:150px; text-align:right;">Aksi</th>
				</tr>
			</thead>
			<tbody id="sbTableBody">
				<?php foreach ($blockRows as $r): ?>
					<?php
					$cInfo = isset($catLabels[$r['category']]) ? $catLabels[$r['category']] : $catLabels['custom'];
					$stKey = $r['disabled'] ? 'disabled' : 'active';
					$hay   = strtolower($r['display'] . ' ' . $r['detail'] . ' ' . $r['src_address'] . ' ' . $cInfo['name']);
					?>
					<tr class="sb-row"
						data-id="<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>"
						data-label="<?= htmlspecialchars($r['display'], ENT_QUOTES); ?>"
						data-cat="<?= htmlspecialchars($r['category'], ENT_QUOTES); ?>"
						data-status="<?= $stKey; ?>"
						data-search="<?= htmlspecialchars($hay, ENT_QUOTES); ?>">
						<td style="max-width:340px;">
							<div style="font-weight:600; color:#09090b; font-size:12.5px; word-break:break-word;">
								<?= htmlspecialchars($r['display']); ?>
							</div>
							<div style="font-size:11px; color:#71717a; margin-top:2px; font-family:ui-monospace, SFMono-Regular, Menlo, monospace;">
								<?= htmlspecialchars($r['method']); ?>
							</div>
						</td>
						<td>
							<span class="sb-badge <?= $cInfo['cls']; ?>"><?= htmlspecialchars($cInfo['name']); ?></span>
						</td>
						<td>
							<?php if ($r['src_address'] !== ''): ?>
								<span style="font-family:monospace; font-size:12px; background:#f4f4f5; border:1px solid #e4e4e7; padding:2px 7px; border-radius:5px; color:#18181b;">
									<?= htmlspecialchars($r['src_address']); ?>
								</span>
							<?php else: ?>
								<span style="font-size:12px; color:#52525b;">Semua Perangkat</span>
							<?php endif; ?>
						</td>
						<td>
							<div style="font-weight:600; font-size:12.5px; color:<?= ($r['packets'] > 0) ? '#dc2626' : '#71717a'; ?>; font-variant-numeric:tabular-nums;">
								<?= number_format($r['packets']); ?> paket
							</div>
							<div style="font-size:11px; color:#71717a; font-variant-numeric:tabular-nums;">
								<?= $r['bytes_fmt']; ?>
							</div>
						</td>
						<td>
							<?php if ($r['disabled']): ?>
								<span class="sb-badge is-off"><span class="sb-dot is-zinc"></span> Nonaktif</span>
							<?php else: ?>
								<span class="sb-badge is-on"><span class="sb-dot is-green"></span> Aktif</span>
							<?php endif; ?>
						</td>
						<td style="text-align:right;">
							<div style="display:inline-flex; gap:6px;">
								<?php if ($r['disabled']): ?>
									<button type="button" class="sb-row-btn" onclick="toggleSingleRule(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', 'enable');" title="Aktifkan kembali">
										<i class="fa fa-play" style="color:#16a34a;"></i> Aktifkan
									</button>
								<?php else: ?>
									<button type="button" class="sb-row-btn" onclick="toggleSingleRule(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>', 'disable');" title="Nonaktifkan sementara">
										<i class="fa fa-pause" style="color:#d97706;"></i> Jeda
									</button>
								<?php endif; ?>

								<button type="button" class="sb-row-btn is-del" onclick="deleteSingleRule(this, '<?= htmlspecialchars($r['id'], ENT_QUOTES); ?>');" title="Hapus aturan">
									<i class="fa fa-trash"></i>
								</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div id="sbEmptyState" style="padding:44px 24px; text-align:center; color:#71717a; font-size:13px; <?= ($totalRules > 0) ? 'display:none;' : ''; ?>">
			<?= ($totalRules > 0) ? 'Tidak ada aturan blokir yang sesuai filter pencarian.' : 'Belum ada aturan Firewall blokir aktif. Klik Aktifkan Blokir pada paket di atas atau tambahkan situs secara manual.'; ?>
		</div>
	</div>

	<!-- Footer -->
	<div class="sb-footer">
		<div>Engine: <strong style="color:#18181b;">MikroTik Firewall Filter (Chain: Forward &rarr; Action: Drop)</strong></div>
		<div>Klik <strong style="color:#18181b;">Refresh</strong> untuk memantau pertambahan paket koneksi yang berhasil diblokir</div>
	</div>
</div>

<script>
	var sbConfirmAction = null;

	function openSbConfirm(opts) {
		var overlay   = document.getElementById('sb-confirm-overlay');
		var titleEl   = document.getElementById('sbModalTitle');
		var descEl    = document.getElementById('sbModalDesc');
		var targetEl  = document.getElementById('sbModalTarget');
		var okLabelEl = document.getElementById('sbModalConfirmLabel');
		var okBtn     = document.getElementById('sbModalConfirmBtn');

		titleEl.textContent   = opts.title || 'Konfirmasi Hapus';
		descEl.textContent    = opts.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
		okLabelEl.textContent = opts.confirmText || 'Ya, Hapus';

		if (opts.target) {
			targetEl.textContent = opts.target;
			targetEl.style.display = 'block';
		} else {
			targetEl.style.display = 'none';
		}

		sbConfirmAction = opts.onConfirm || null;
		overlay.classList.add('is-open');
		setTimeout(function() { okBtn.focus(); }, 30);
	}

	function closeSbConfirm() {
		var overlay = document.getElementById('sb-confirm-overlay');
		overlay.classList.remove('is-open');
		sbConfirmAction = null;
	}

	document.getElementById('sbModalConfirmBtn').addEventListener('click', function() {
		var fn = sbConfirmAction;
		closeSbConfirm();
		if (typeof fn === 'function') fn();
	});

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			var overlay = document.getElementById('sb-confirm-overlay');
			if (overlay && overlay.classList.contains('is-open')) {
				closeSbConfirm();
			}
		}
	});

	function showSbToast(type, title, msg) {
		var box = document.getElementById('sb-toast-box');
		if (!box) return;
		var isOk = (type === 'success');
		var el = document.createElement('div');
		el.className = 'sb-toast ' + (isOk ? '' : 'is-error');
		el.innerHTML =
			'<div style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;background:' + (isOk ? '#dcfce7;color:#16a34a' : '#fee2e2;color:#dc2626') + ';">' +
				'<i class="fa ' + (isOk ? 'fa-check' : 'fa-exclamation-triangle') + '"></i>' +
			'</div>' +
			'<div style="flex:1;min-width:0;">' +
				'<div style="font-size:12.5px;font-weight:600;color:#09090b;margin-bottom:2px;">' + title + '</div>' +
				'<div style="font-size:12px;color:#52525b;line-height:1.4;">' + msg + '</div>' +
			'</div>' +
			'<button type="button" class="sb-toast-close" onclick="this.parentNode.remove();">&times;</button>' +
			'<div class="sb-toast-bar"></div>';
		box.appendChild(el);
		setTimeout(function() {
			el.style.opacity = '0';
			el.style.transform = 'translateX(16px)';
			setTimeout(function() {
				if (el.parentNode) el.parentNode.removeChild(el);
			}, 220);
		}, isOk ? 3500 : 5500);
	}

	function setBtnLoading(btn, isLoading, loadingText) {
		if (!btn) return;
		if (isLoading) {
			btn.setAttribute('data-orig-html', btn.innerHTML);
			btn.disabled = true;
			btn.innerHTML = '<i class="fa fa-circle-o-notch fa-spin"></i>' + (loadingText ? '<span>' + loadingText + '</span>' : '');
		} else {
			btn.disabled = false;
			var orig = btn.getAttribute('data-orig-html');
			if (orig) btn.innerHTML = orig;
		}
	}

	function postSiteblockAction(params, btnEl, loadingLabel) {
		setBtnLoading(btnEl, true, loadingLabel);

		var formData = new URLSearchParams();
		formData.append('ajax_siteblock', '1');
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
			if (!data) {
				setBtnLoading(btnEl, false);
				showSbToast('error', 'Gagal', 'Respon server tidak valid. Pastikan koneksi router aktif.');
				return;
			}
			if (data.status === 'success') {
				showSbToast('success', 'Berhasil', data.message);
				setTimeout(function() { window.location.reload(); }, 750);
			} else {
				setBtnLoading(btnEl, false);
				showSbToast('error', 'Gagal', data.message || 'Terjadi kesalahan.');
			}
		})
		.catch(function() {
			setBtnLoading(btnEl, false);
			showSbToast('error', 'Koneksi Terputus', 'Tidak dapat menghubungi server Mikhmon.');
		});
	}

	function addCustomBlock(e) {
		e.preventDefault();
		var dom = document.getElementById('sbDomainInput').value.trim();
		var mth = document.getElementById('sbMethodSelect').value;
		var src = document.getElementById('sbSrcAddrInput').value.trim();
		var cat = document.getElementById('sbCatSelect').value;
		if (!dom) return;

		var btn = document.getElementById('btnAddBlock');
		postSiteblockAction({
			act: 'add_single',
			domain: dom,
			method: mth,
			src_address: src,
			category: cat
		}, btn, 'Menerapkan...');
	}

	function applyPreset(btn, presetKey, op, labelName) {
		var src = document.getElementById('sbSrcAddrInput') ? document.getElementById('sbSrcAddrInput').value.trim() : '';
		if (op === 'remove') {
			openSbConfirm({
				title: 'Hapus Paket Blokir?',
				message: 'Aturan Firewall & Layer 7 Protocol untuk paket ini akan dihapus dari router MikroTik.',
				target: 'Paket: ' + (labelName || presetKey),
				confirmText: 'Ya, Hapus Paket',
				onConfirm: function() {
					postSiteblockAction({
						act: 'apply_preset',
						preset: presetKey,
						op: 'remove',
						src_address: src
					}, btn, '');
				}
			});
			return;
		}
		postSiteblockAction({
			act: 'apply_preset',
			preset: presetKey,
			op: op,
			src_address: src
		}, btn, 'Mengaktifkan...');
	}

	function toggleSingleRule(btn, id, target) {
		postSiteblockAction({
			act: 'toggle_rule',
			id: id,
			target: target
		}, btn, 'Proses...');
	}

	function deleteSingleRule(btn, id) {
		var row = btn.closest('tr');
		var lbl = row ? row.getAttribute('data-label') : '';
		openSbConfirm({
			title: 'Hapus Aturan Blokir?',
			message: 'Akses ke situs/kata kunci ini akan kembali dibuka untuk klien.',
			target: lbl,
			confirmText: 'Ya, Hapus',
			onConfirm: function() {
				postSiteblockAction({
					act: 'delete_rules',
					ids: id
				}, btn, '');
			}
		});
	}

	function deleteVisibleRules(btn) {
		var rows = document.querySelectorAll('#sbTableBody tr.sb-row');
		var ids = [];
		for (var i = 0; i < rows.length; i++) {
			if (rows[i].style.display !== 'none') {
				var id = rows[i].getAttribute('data-id');
				if (id) ids.push(id);
			}
		}
		if (ids.length === 0) return;

		openSbConfirm({
			title: 'Hapus Aturan Tampil?',
			message: 'Seluruh aturan blokir yang sedang ditampilkan di tabel akan dihapus permanen dari Firewall MikroTik.',
			target: ids.length + ' aturan blokir terpilih',
			confirmText: 'Hapus Semua (' + ids.length + ')',
			onConfirm: function() {
				postSiteblockAction({
					act: 'delete_rules',
					ids: ids.join(',')
				}, btn, 'Menghapus...');
			}
		});
	}

	function resetFirewallCounters(btn) {
		postSiteblockAction({ act: 'reset_counters' }, btn, 'Mereset...');
	}

	function quickFilterCat(cat) {
		document.getElementById('sbFilterCat').value = cat;
		filterBlockTable();
	}

	function filterBlockTable() {
		var catVal = document.getElementById('sbFilterCat').value;
		var stVal  = document.getElementById('sbFilterStatus').value;
		var qVal   = (document.getElementById('sbSearchInput').value || '').toLowerCase().trim();

		var rows = document.querySelectorAll('#sbTableBody tr.sb-row');
		var vis = 0;
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var mCat = (catVal === 'all' || r.getAttribute('data-cat') === catVal);
			var mSt  = (stVal === 'all' || r.getAttribute('data-status') === stVal);
			var mQ   = (qVal === '' || (r.getAttribute('data-search') || '').indexOf(qVal) !== -1);

			if (mCat && mSt && mQ) {
				r.style.display = '';
				vis++;
			} else {
				r.style.display = 'none';
			}
		}

		var lblDel = document.getElementById('lblDeleteVisibleBlocks');
		var btnDel = document.getElementById('btnDeleteVisibleBlocks');
		if (lblDel) lblDel.textContent = 'Hapus Tampil (' + vis + ')';
		if (btnDel) btnDel.disabled = (vis === 0);

		var empty = document.getElementById('sbEmptyState');
		if (empty) empty.style.display = (vis === 0) ? 'block' : 'none';
	}
</script>