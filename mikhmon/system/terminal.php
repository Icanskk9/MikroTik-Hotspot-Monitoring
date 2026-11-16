<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *  Custom Module: Web Terminal / RouterOS API Console
 */
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
	exit;
}

if (!function_exists('termParseCommand')) {
	function termParseCommand($rawCmd) {
		$raw = trim((string)$rawCmd);
		if ($raw === '') {
			return array('error' => 'Perintah kosong.');
		}

		// Tokenisasi dengan mempertahankan tanda kutip ("..." atau '...')
		preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|(\S+)/', $raw, $matches, PREG_SET_ORDER);
		$tokens = array();
		foreach ($matches as $m) {
			if (isset($m[1]) && $m[1] !== '') {
				$tokens[] = stripcslashes($m[1]);
			} elseif (isset($m[2]) && $m[2] !== '') {
				$tokens[] = stripcslashes($m[2]);
			} elseif (isset($m[3])) {
				$tokens[] = $m[3];
			}
		}

		if (empty($tokens)) {
			return array('error' => 'Format perintah tidak valid.');
		}

		$pathParts = array();
		$paramTokens = array();
		$inParams = false;
		$cliModifiers = array('detail', 'stats', 'terse', 'brief', 'without-paging', 'once', 'count-only');

		foreach ($tokens as $tok) {
			if (!$inParams) {
				$isParam = (
					strpos($tok, '=') !== false ||
					strpos($tok, '?') === 0 ||
					in_array(strtolower($tok), $cliModifiers, true) ||
					filter_var($tok, FILTER_VALIDATE_IP) !== false
				);

				if ($isParam) {
					$inParams = true;
					$paramTokens[] = $tok;
				} else {
					$segments = explode('/', $tok);
					foreach ($segments as $seg) {
						$seg = trim($seg);
						if ($seg !== '') {
							$pathParts[] = $seg;
						}
					}
				}
			} else {
				$paramTokens[] = $tok;
			}
		}

		if (empty($pathParts)) {
			return array('error' => 'Path perintah MikroTik tidak dikenali.');
		}

		$apiPath = '/' . implode('/', $pathParts);
		$apiParams = array();

		// Shorthand khusus untuk /ping dan /tool/traceroute (misal: /ping 8.8.8.8)
		if (($apiPath === '/ping' || $apiPath === '/tool/traceroute') && !empty($paramTokens)) {
			if (strpos($paramTokens[0], '=') === false) {
				$apiParams['address'] = array_shift($paramTokens);
			}
		}

		foreach ($paramTokens as $pt) {
			if (strtolower($pt) === 'without-paging') {
				continue;
			}
			$eqPos = strpos($pt, '=');
			if ($eqPos !== false) {
				$k = substr($pt, 0, $eqPos);
				$v = substr($pt, $eqPos + 1);
				if ($k !== '') {
					$apiParams[$k] = $v;
				}
			} else {
				// Flag tanpa nilai (misal: detail, stats, once)
				$apiParams[$pt] = '';
			}
		}

		// Batasi jumlah ping/traceroute otomatis jika tidak diisi agar request web tidak timeout
		if ($apiPath === '/ping' && !isset($apiParams['count'])) {
			$apiParams['count'] = '4';
		}
		if ($apiPath === '/tool/traceroute' && !isset($apiParams['count'])) {
			$apiParams['count'] = '3';
		}

		return array(
			'path'   => $apiPath,
			'params' => $apiParams
		);
	}
}

// Pastikan koneksi API aktif
$isConnected = (isset($API) && is_object($API));
if ($isConnected && isset($API->connected) && !$API->connected) {
	$isConnected = $API->connect($iphost, $userhost, decrypt($passwdhost));
}

// Handler AJAX Eksekusi Perintah Terminal
if (isset($_POST['ajax_terminal'])) {
	echo "<!--JSON_START-->";

	if (!$isConnected) {
		echo json_encode(array('status' => 'error', 'message' => 'Koneksi ke MikroTik API terputus.'));
		echo "<!--JSON_END-->";
		exit;
	}

	$rawCmd = isset($_POST['cmd']) ? (string)$_POST['cmd'] : '';
	$parsed = termParseCommand($rawCmd);

	if (isset($parsed['error'])) {
		echo json_encode(array('status' => 'error', 'message' => $parsed['error']));
		echo "<!--JSON_END-->";
		exit;
	}

	$tStart = microtime(true);
	$res = $API->comm($parsed['path'], $parsed['params']);
	$elapsed = round((microtime(true) - $tStart) * 1000, 1);

	if (is_array($res) && isset($res['!trap'][0]['message'])) {
		echo json_encode(array(
			'status'   => 'trap',
			'api_path' => $parsed['path'],
			'params'   => $parsed['params'],
			'message'  => (string)$res['!trap'][0]['message'],
			'ms'       => $elapsed
		));
		echo "<!--JSON_END-->";
		exit;
	}

	echo json_encode(array(
		'status'   => 'success',
		'api_path' => $parsed['path'],
		'params'   => $parsed['params'],
		'data'     => $res,
		'ms'       => $elapsed
	));
	echo "<!--JSON_END-->";
	exit;
}

// Ambil Identity & Info Singkat Router untuk Prompt Terminal
$routerIdentity = 'MikroTik';
$routerVersion  = '-';
if ($isConnected) {
	$idRes = $API->comm('/system/identity/print');
	if (is_array($idRes) && isset($idRes[0]['name'])) {
		$routerIdentity = (string)$idRes[0]['name'];
	}
	$sysRes = $API->comm('/system/resource/print');
	if (is_array($sysRes) && isset($sysRes[0]['version'])) {
		$routerVersion = (string)$sysRes[0]['version'];
	}
}
$promptUser = isset($userhost) && $userhost !== '' ? $userhost : 'admin';
?>

<style>
	#mhm-terminal {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}
	#mhm-terminal *,
	#mhm-terminal *::before,
	#mhm-terminal *::after {
		box-sizing: border-box;
	}

	/* Top Bar */
	#mhm-terminal .tm-topbar {
		padding: 16px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		background: #ffffff;
	}
	#mhm-terminal .tm-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-terminal .tm-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-terminal .tm-pill {
		font-size: 11.5px;
		font-weight: 500;
		color: #15803d;
		background: #f0fdf4;
		border: 1px solid #bbf7d0;
		padding: 2px 9px;
		border-radius: 6px;
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	#mhm-terminal .tm-pill.is-off {
		color: #dc2626;
		background: #fef2f2;
		border-color: #fecaca;
	}
	#mhm-terminal .tm-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-terminal .tm-btn {
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
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	#mhm-terminal .tm-btn:hover {
		background: #f4f4f5;
		border-color: #d4d4d8;
	}

	/* Quick Snippets Bar */
	#mhm-terminal .tm-snippets {
		padding: 11px 24px;
		background: #fafafa;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		align-items: center;
		gap: 7px;
		flex-wrap: wrap;
	}
	#mhm-terminal .tm-snip-label {
		font-size: 11px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: #71717a;
		margin-right: 4px;
	}
	#mhm-terminal .tm-chip {
		height: 27px;
		padding: 0 10px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 6px;
		font-size: 11.5px;
		font-weight: 500;
		color: #27272a;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 5px;
		transition: all 0.15s ease;
	}
	#mhm-terminal .tm-chip:hover {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}

	/* Console Window */
	#mhm-terminal .tm-window {
		background: #09090b;
		color: #e4e4e7;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
		font-size: 12.5px;
		line-height: 1.55;
		display: flex;
		flex-direction: column;
	}
	#mhm-terminal .tm-win-bar {
		padding: 9px 18px;
		background: #121215;
		border-bottom: 1px solid #27272a;
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 11.5px;
		color: #a1a1aa;
	}
	#mhm-terminal .tm-dots {
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	#mhm-terminal .tm-dots span {
		width: 10px;
		height: 10px;
		border-radius: 50%;
		display: inline-block;
	}
	#mhm-terminal .tm-output {
		height: 460px;
		overflow-y: auto;
		padding: 16px 20px;
		scroll-behavior: smooth;
	}
	#mhm-terminal .tm-entry {
		margin-bottom: 16px;
		border-bottom: 1px dashed #27272a;
		padding-bottom: 14px;
	}
	#mhm-terminal .tm-entry:last-child {
		border-bottom: none;
		margin-bottom: 0;
		padding-bottom: 4px;
	}
	#mhm-terminal .tm-cmd-line {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
		margin-bottom: 6px;
	}
	#mhm-terminal .tm-prompt-tag {
		color: #34d399;
		font-weight: 600;
	}
	#mhm-terminal .tm-cmd-text {
		color: #f8fafc;
		font-weight: 600;
	}
	#mhm-terminal .tm-meta {
		font-size: 11px;
		color: #71717a;
		white-space: nowrap;
	}
	#mhm-terminal pre.tm-pre {
		margin: 0;
		padding: 10px 12px;
		background: #121215;
		border: 1px solid #27272a;
		border-radius: 7px;
		color: #d4d4d8;
		font-family: inherit;
		font-size: 12px;
		line-height: 1.5;
		overflow-x: auto;
		white-space: pre;
	}
	#mhm-terminal pre.tm-pre.is-error {
		border-color: #7f1d1d;
		background: #1c1012;
		color: #fca5a5;
	}
	#mhm-terminal pre.tm-pre.is-ok {
		border-color: #14532d;
		background: #0d1912;
		color: #86efac;
	}

	/* Input Bar */
	#mhm-terminal .tm-input-wrap {
		padding: 12px 18px;
		background: #121215;
		border-top: 1px solid #27272a;
		display: flex;
		align-items: center;
		gap: 10px;
	}
	#mhm-terminal .tm-input-prompt {
		color: #34d399;
		font-weight: 600;
		white-space: nowrap;
		user-select: none;
	}
	#mhm-terminal #tmCmdInput {
		flex: 1;
		background: transparent !important;
		border: none !important;
		outline: none !important;
		box-shadow: none !important;
		color: #ffffff !important;
		font-family: inherit !important;
		font-size: 13px !important;
		height: 30px !important;
		padding: 0 !important;
		margin: 0 !important;
	}
	#mhm-terminal .tm-run-btn {
		height: 30px;
		padding: 0 13px;
		background: #10b981;
		border: none;
		border-radius: 6px;
		color: #052e16;
		font-family: inherit;
		font-size: 12px;
		font-weight: 700;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 5px;
		transition: background 0.15s ease;
	}
	#mhm-terminal .tm-run-btn:hover {
		background: #34d399;
	}
	#mhm-terminal .tm-run-btn:disabled {
		opacity: 0.55;
		cursor: not-allowed;
	}

	/* Footer */
	#mhm-terminal .tm-footer {
		padding: 11px 24px;
		background: #fafafa;
		border-top: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		font-size: 11.5px;
		color: #71717a;
	}
</style>

<div id="mhm-terminal">
	<!-- Top Bar -->
	<div class="tm-topbar">
		<div class="tm-title-group">
			<h3 class="tm-title">MikroTik Web Terminal (RouterOS API)</h3>
			<span class="tm-pill <?= $isConnected ? '' : 'is-off'; ?>">
				<span style="width:7px;height:7px;border-radius:50%;background:<?= $isConnected ? '#16a34a' : '#dc2626'; ?>;"></span>
				<?= $isConnected ? 'Terhubung &bull; RouterOS v' . htmlspecialchars($routerVersion) : 'Terputus'; ?>
			</span>
		</div>

		<div class="tm-actions">
			<button type="button" class="tm-btn" onclick="copyLastOutput();">
				<i class="fa fa-copy"></i>
				<span>Salin Output Terakhir</span>
			</button>
			<button type="button" class="tm-btn" onclick="clearTerminalOutput();">
				<i class="fa fa-eraser"></i>
				<span>Clear (Ctrl+L)</span>
			</button>
		</div>
	</div>

	<!-- Quick Commands -->
	<div class="tm-snippets">
		<span class="tm-snip-label">Cepat:</span>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/system resource print');">Resource CPU/RAM</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/ip address print');">IP Address</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/interface print');">Interface</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/ip route print');">Routing Table</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/ip hotspot active print');">Hotspot Active</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/ip dhcp-server lease print');">DHCP Leases</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/ip firewall filter print');">Firewall Filter</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/log print');">Log Router</button>
		<button type="button" class="tm-chip" onclick="runQuickCmd('/ping 8.8.8.8 count=4');">Ping 8.8.8.8</button>
	</div>

	<!-- Terminal Window -->
	<div class="tm-window" onclick="document.getElementById('tmCmdInput').focus();">
		<div class="tm-win-bar">
			<div class="tm-dots">
				<span style="background:#ef4444;"></span>
				<span style="background:#f59e0b;"></span>
				<span style="background:#10b981;"></span>
				<span style="margin-left:8px; color:#d4d4d8; font-weight:600;"><?= htmlspecialchars($promptUser . '@' . $routerIdentity); ?></span>
			</div>
			<div>Panah <strong style="color:#e4e4e7;">&uarr; / &darr;</strong> riwayat perintah &bull; <strong style="color:#e4e4e7;">Enter</strong> eksekusi</div>
		</div>

		<div class="tm-output" id="tmOutputBox">
			<div class="tm-entry">
				<pre class="tm-pre" style="border-color:#27272a; color:#a1a1aa;">MikroTik RouterOS Web Console &mdash; <?= htmlspecialchars($routerIdentity); ?> (v<?= htmlspecialchars($routerVersion); ?>)
Mendukung sintaks CLI maupun API langsung. Contoh perintah:
  &bull; /system resource print
  &bull; /ip address print
  &bull; /ping 8.8.8.8 count=4
  &bull; /ip firewall filter print ?disabled=no
  &bull; /ip hotspot user add name=uji1 password=123 profile=default</pre>
			</div>
		</div>

		<form class="tm-input-wrap" onsubmit="submitTerminalCmd(event);">
			<span class="tm-input-prompt">[<?= htmlspecialchars($promptUser . '@' . $routerIdentity); ?>] &gt;</span>
			<input
				type="text"
				id="tmCmdInput"
				autocomplete="off"
				spellcheck="false"
				placeholder="Ketik perintah MikroTik lalu tekan Enter..."
				autofocus
			>
			<button type="submit" id="tmRunBtn" class="tm-run-btn">
				<i class="fa fa-play"></i>
				<span>Run</span>
			</button>
		</form>
	</div>

	<!-- Footer -->
	<div class="tm-footer">
		<div>Protokol: <strong style="color:#18181b;">MikroTik RouterOS API Command Bridge</strong></div>
		<div>Gunakan parameter <code style="color:#18181b;">?key=val</code> untuk filter hasil (contoh: <code style="color:#18181b;">/interface print ?running=true</code>)</div>
	</div>
</div>

<script>
	var tmHistory = [];
	var tmHistIdx = -1;
	var tmPromptStr = <?= json_encode('[' . $promptUser . '@' . $routerIdentity . '] >'); ?>;
	var tmLastText = '';

	function escapeHtmlTerm(str) {
		return String(str || '').replace(/[&<>"']/g, function(m) {
			return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];
		});
	}

	function formatRouterData(apiPath, data) {
		if (!Array.isArray(data)) {
			if (typeof data === 'string' && data.trim() !== '') {
				return 'OK (ID: ' + data + ')';
			}
			return 'Perintah berhasil dijalankan.';
		}
		if (data.length === 0) {
			return 'Perintah selesai (0 baris data dikembalikan).';
		}

		// Format khusus untuk hasil /ping
		if (apiPath === '/ping') {
			var lines = ['SEQ   HOST              SIZE   TTL   TIME       STATUS'];
			for (var i = 0; i < data.length; i++) {
				var r = data[i];
				var seq    = String(r['seq'] !== undefined ? r['seq'] : i).padEnd(6);
				var host   = String(r['host'] || '-').padEnd(18);
				var size   = String(r['size'] || '-').padEnd(7);
				var ttl    = String(r['ttl'] || '-').padEnd(6);
				var time   = String(r['time'] || '-').padEnd(11);
				var status = String(r['status'] || (r['time'] ? 'echo reply' : '-'));
				lines.push(seq + host + size + ttl + time + status);
			}
			return lines.join('\n');
		}

		// Kumpulkan semua kolom unik
		var keys = [];
		var keySet = {};
		for (var rIdx = 0; rIdx < data.length; rIdx++) {
			var row = data[rIdx];
			if (typeof row !== 'object' || row === null) continue;
			for (var k in row) {
				if (Object.prototype.hasOwnProperty.call(row, k) && k !== '.id' && !keySet[k]) {
					keySet[k] = true;
					keys.push(k);
				}
			}
		}

		// Jika hanya 1 baris data dengan banyak kolom (misal: /system resource print), tampilkan vertikal rapi
		if (data.length === 1 && keys.length > 6) {
			var maxKeyLen = 0;
			for (var mk = 0; mk < keys.length; mk++) {
				if (keys[mk].length > maxKeyLen) maxKeyLen = keys[mk].length;
			}
			var singleLines = [];
			var item0 = data[0];
			for (var sk = 0; sk < keys.length; sk++) {
				var kName = keys[sk];
				singleLines.push(kName.padStart(maxKeyLen, ' ') + ': ' + String(item0[kName]));
			}
			return singleLines.join('\n');
		}

		// Jika kolom <= 6, tampilkan sebagai tabel ASCII sejajar
		if (keys.length > 0 && keys.length <= 6) {
			var widths = { '#': Math.max(3, String(data.length).length + 1) };
			for (var c = 0; c < keys.length; c++) {
				var col = keys[c];
				widths[col] = col.length;
				for (var j = 0; j < data.length; j++) {
					var valStr = String(data[j][col] !== undefined ? data[j][col] : '');
					if (valStr.length > 48) valStr = valStr.substring(0, 45) + '...';
					if (valStr.length > widths[col]) widths[col] = valStr.length;
				}
			}

			var header = '#'.padEnd(widths['#'] + 2);
			var sep    = '-'.repeat(widths['#']) + '  ';
			for (var c2 = 0; c2 < keys.length; c2++) {
				var col2 = keys[c2];
				header += col2.toUpperCase().padEnd(widths[col2] + 3);
				sep    += '-'.repeat(widths[col2]) + '   ';
			}

			var outRows = [header, sep];
			for (var j2 = 0; j2 < data.length; j2++) {
				var line = String(j2).padEnd(widths['#'] + 2);
				for (var c3 = 0; c3 < keys.length; c3++) {
					var col3 = keys[c3];
					var v3 = String(data[j2][col3] !== undefined ? data[j2][col3] : '');
					if (v3.length > 48) v3 = v3.substring(0, 45) + '...';
					line += v3.padEnd(widths[col3] + 3);
				}
				outRows.push(line);
			}
			return outRows.join('\n');
		}

		// Jika baris banyak dengan kolom > 6, tampilkan gaya key=value per baris seperti MikroTik CLI
		var kvBlocks = [];
		for (var idx = 0; idx < data.length; idx++) {
			var item = data[idx];
			var pairs = [];
			for (var key in item) {
				if (Object.prototype.hasOwnProperty.call(item, key) && key !== '.id') {
					var v = String(item[key]);
					if (v.indexOf(' ') !== -1) v = '"' + v + '"';
					pairs.push(key + '=' + v);
				}
			}
			kvBlocks.push(String(idx).padStart(2, ' ') + '  ' + pairs.join(' '));
		}
		return kvBlocks.join('\n');
	}

	function appendTerminalEntry(cmd, apiPath, outputText, statusType, elapsedMs) {
		var box = document.getElementById('tmOutputBox');
		var entry = document.createElement('div');
		entry.className = 'tm-entry';

		var preClass = 'tm-pre';
		if (statusType === 'error') preClass += ' is-error';
		else if (statusType === 'ok') preClass += ' is-ok';

		var metaInfo = apiPath ? (escapeHtmlTerm(apiPath) + (elapsedMs !== null ? ' &bull; ' + elapsedMs + ' ms' : '')) : '';

		entry.innerHTML =
			'<div class="tm-cmd-line">' +
				'<div><span class="tm-prompt-tag">' + escapeHtmlTerm(tmPromptStr) + '</span> <span class="tm-cmd-text">' + escapeHtmlTerm(cmd) + '</span></div>' +
				'<div class="tm-meta">' + metaInfo + '</div>' +
			'</div>' +
			'<pre class="' + preClass + '">' + escapeHtmlTerm(outputText) + '</pre>';

		box.appendChild(entry);
		box.scrollTop = box.scrollHeight;
		tmLastText = outputText;
	}

	function executeTerminalCommand(cmd) {
		var cleanCmd = (cmd || '').trim();
		if (!cleanCmd) return;

		if (cleanCmd.toLowerCase() === 'clear' || cleanCmd.toLowerCase() === 'cls') {
			clearTerminalOutput();
			return;
		}

		// Pengaman untuk perintah reboot / shutdown / reset
		if (/\b(reboot|shutdown|reset-configuration)\b/i.test(cleanCmd)) {
			if (!confirm('Perintah "' + cleanCmd + '" akan memengaruhi operasional router. Lanjutkan eksekusi?')) {
				return;
			}
		}

		if (tmHistory.length === 0 || tmHistory[tmHistory.length - 1] !== cleanCmd) {
			tmHistory.push(cleanCmd);
		}
		tmHistIdx = tmHistory.length;

		var input = document.getElementById('tmCmdInput');
		var btn   = document.getElementById('tmRunBtn');
		input.disabled = true;
		btn.disabled   = true;
		btn.innerHTML  = '<i class="fa fa-circle-o-notch fa-spin"></i><span>Run</span>';

		var formData = new URLSearchParams();
		formData.append('ajax_terminal', '1');
		formData.append('cmd', cleanCmd);

		fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData.toString()
		})
		.then(function(res) { return res.text(); })
		.then(function(raw) {
			input.disabled = false;
			btn.disabled   = false;
			btn.innerHTML  = '<i class="fa fa-play"></i><span>Run</span>';
			input.focus();

			var s = raw.indexOf('<!--JSON_START-->');
			var e = raw.indexOf('<!--JSON_END-->');
			var res = null;
			if (s !== -1 && e !== -1) {
				try { res = JSON.parse(raw.substring(s + 17, e)); } catch (err) {}
			}

			if (!res) {
				appendTerminalEntry(cleanCmd, '', 'Gagal membaca respon server Mikhmon.', 'error', null);
				return;
			}

			if (res.status === 'success') {
				var formatted = formatRouterData(res.api_path, res.data);
				var isSimpleOk = (!Array.isArray(res.data) || res.data.length === 0);
				appendTerminalEntry(cleanCmd, res.api_path, formatted, isSimpleOk ? 'ok' : 'normal', res.ms);
			} else if (res.status === 'trap') {
				appendTerminalEntry(cleanCmd, res.api_path, 'RouterOS Error: ' + res.message, 'error', res.ms);
			} else {
				appendTerminalEntry(cleanCmd, '', 'Error: ' + (res.message || 'Perintah gagal dijalankan.'), 'error', null);
			}
		})
		.catch(function() {
			input.disabled = false;
			btn.disabled   = false;
			btn.innerHTML  = '<i class="fa fa-play"></i><span>Run</span>';
			appendTerminalEntry(cleanCmd, '', 'Koneksi ke server terputus.', 'error', null);
			input.focus();
		});
	}

	function submitTerminalCmd(e) {
		e.preventDefault();
		var input = document.getElementById('tmCmdInput');
		var val = input.value;
		input.value = '';
		executeTerminalCommand(val);
	}

	function runQuickCmd(cmd) {
		var input = document.getElementById('tmCmdInput');
		input.value = '';
		executeTerminalCommand(cmd);
	}

	function clearTerminalOutput() {
		document.getElementById('tmOutputBox').innerHTML = '';
		document.getElementById('tmCmdInput').focus();
	}

	function copyLastOutput() {
		if (!tmLastText) return;
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(tmLastText);
		}
	}

	document.getElementById('tmCmdInput').addEventListener('keydown', function(e) {
		if (e.key === 'ArrowUp') {
			e.preventDefault();
			if (tmHistory.length > 0 && tmHistIdx > 0) {
				tmHistIdx--;
				this.value = tmHistory[tmHistIdx];
			}
		} else if (e.key === 'ArrowDown') {
			e.preventDefault();
			if (tmHistIdx < tmHistory.length - 1) {
				tmHistIdx++;
				this.value = tmHistory[tmHistIdx];
			} else {
				tmHistIdx = tmHistory.length;
				this.value = '';
			}
		} else if (e.ctrlKey && e.key.toLowerCase() === 'l') {
			e.preventDefault();
			clearTerminalOutput();
		}
	});
</script>