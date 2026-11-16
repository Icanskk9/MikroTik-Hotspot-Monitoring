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
?>
<?php
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
} else {
	// load session MikroTik
	$session = $_GET['session'];

	// load config
	include('../include/config.php');
	include('../include/readcfg.php');

	$url = $_SERVER['REQUEST_URI'];
	$telplate = $_GET['template'];
	if ($telplate == "default" || $telplate == "rdefault") {
		$telplatet = "template";
		$popup = "javascript:window.open('./voucher/vpreview.php?usermode=up&qr=no&session=" . $session . "','_blank','width=310,height=310')";
		$popupQR = "javascript:window.open('./voucher/vpreview.php?usermode=up&qr=yes&session=" . $session . "','_blank','width=310,height=310')";
	} elseif ($telplate == "thermal" || $telplate == "rthermal") {
		$telplatet = "template-thermal";
		$popup = "javascript:window.open('./voucher/vpreview.php?usermode=up&user=m&qr=no&session=" . $session . "','_blank','width=310,height=310')";
		$popupQR = "javascript:window.open('./voucher/vpreview.php?usermode=up&user=m&qr=yes&session=" . $session . "','_blank','width=310,height=310')";
	} elseif ($telplate == "small" || $telplate == "rsmall") {
		$telplatet = "template-small";
		$popup = "javascript:window.open('./voucher/vpreview.php?usermode=up&small=yes&qr=no&session=" . $session . "','_blank','width=310,height=310')";
		$popupQR = "javascript:window.open('./voucher/vpreview.php?usermode=up&small=yes&qr=yes&session=" . $session . "','_blank','width=310,height=310')";
	}

	$isSaved = false;
	if (isset($_POST['save'])) {
		$template = './voucher/' . $telplatet . '.php';
		$handle = fopen($template, 'w') or die('Cannot open file:  ' . $template);

		$data = ($_POST['editor']);

		fwrite($handle, $data);
		$isSaved = true;
	}

	// Parse variable.php untuk tampilan klik-salin yang rapi (read-only)
	$rawVars = @file_get_contents('./voucher/variable.php');
	$varItems = array();
	if ($rawVars !== false && trim($rawVars) !== '') {
		$blocks = preg_split("/\r?\n\s*\r?\n/", trim($rawVars));
		foreach ($blocks as $block) {
			$lines = preg_split("/\r?\n/", trim($block), 2);
			$label = rtrim(trim($lines[0]), " :");
			$code = isset($lines[1]) ? trim($lines[1]) : '';
			if ($label !== '') {
				$varItems[] = array('label' => $label, 'code' => $code);
			}
		}
	}
}
?>
<!-- Create a simple CodeMirror instance -->
<link rel="stylesheet" href="./css/editor.min.css">
<script src="./js/editor.min.js"></script>

<style>
	#mhm-editor-grid {
		display: grid;
		grid-template-columns: minmax(0, 1fr) 310px;
		gap: 18px;
		align-items: start;
	}
	@media (max-width: 992px) {
		#mhm-editor-grid {
			grid-template-columns: minmax(0, 1fr);
		}
	}

	.lx-card {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	.lx-card .lx-topbar {
		padding: 16px 22px;
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
	.lx-card .lx-file-pill {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 11.5px;
		font-weight: 500;
		color: #52525b;
		background: #f4f4f5;
		padding: 2px 9px;
		border-radius: 6px;
	}
	.lx-card .lx-saved-pill {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 11.5px;
		font-weight: 500;
		color: #047857;
		background: #ecfdf5;
		border: 1px solid #a7f3d0;
		padding: 2px 9px;
		border-radius: 6px;
	}
	.lx-card .lx-saved-dot {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
	}

	/* Buttons */
	.lx-card .lx-actions {
		display: flex;
		align-items: center;
		gap: 7px;
		flex-wrap: wrap;
	}
	.lx-card .lx-btn {
		height: 33px;
		padding: 0 13px;
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
	.lx-card .lx-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b;
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
		color: #ffffff;
	}
	.lx-card .lx-btn.is-primary i {
		color: #d4d4d8;
	}
	.lx-card .lx-btn.is-primary:hover {
		background: #27272a;
		border-color: #27272a;
	}

	/* Sub-toolbar (Template Switcher & Reset) */
	.lx-card .lx-subbar {
		padding: 12px 22px;
		background: #fafafa;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
	}
	.lx-card .lx-control-group {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	.lx-card .lx-control-label {
		font-size: 11px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
	}
	.lx-card .lx-seg-group {
		display: inline-flex;
		align-items: center;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		padding: 2px;
		gap: 2px;
	}
	.lx-card .lx-seg-item {
		height: 27px;
		padding: 0 11px;
		border-radius: 6px;
		font-size: 12px;
		font-weight: 500;
		color: #52525b;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		transition: all 0.15s ease;
	}
	.lx-card .lx-seg-item:hover {
		color: #09090b;
		background: #f4f4f5;
	}
	.lx-card .lx-seg-item.is-active {
		background: #18181b;
		color: #ffffff;
		font-weight: 600;
	}
	.lx-card .lx-select {
		height: 32px !important;
		padding: 0 26px 0 10px !important;
		background-color: #ffffff !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12px !important;
		font-weight: 500 !important;
		color: #18181b !important;
		cursor: pointer;
		outline: none !important;
		transition: all 0.15s ease;
	}
	.lx-card .lx-select:hover {
		border-color: #d4d4d8 !important;
	}
	.lx-card .lx-select:focus {
		border-color: #18181b !important;
	}

	/* CodeMirror Frame */
	.lx-editor-shell {
		background: #263238;
		position: relative;
	}
	.lx-editor-shell .CodeMirror {
		border: none !important;
		height: 545px !important;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
		font-size: 12.5px !important;
		line-height: 1.6 !important;
	}
	.lx-editor-shell textarea {
		font-size: 12.5px;
		border: none;
		width: 100%;
		height: 545px;
	}

	/* Variable Sidebar */
	.lx-var-list {
		max-height: 598px;
		overflow-y: auto;
		padding: 12px 16px;
		display: flex;
		flex-direction: column;
		gap: 8px;
		background: #ffffff;
	}
	.lx-var-item {
		padding: 9px 11px;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		cursor: pointer;
		transition: all 0.15s ease;
		position: relative;
	}
	.lx-var-item:hover {
		background: #ffffff;
		border-color: #18181b;
		box-shadow: 0 2px 6px rgba(9, 9, 11, 0.04);
	}
	.lx-var-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 4px;
	}
	.lx-var-name {
		font-size: 11px;
		font-weight: 600;
		color: #3f3f46;
	}
	.lx-var-copy-badge {
		font-size: 10px;
		font-weight: 500;
		color: #a1a1aa;
		transition: color 0.15s ease;
	}
	.lx-var-item:hover .lx-var-copy-badge {
		color: #18181b;
	}
	.lx-var-item.is-copied {
		border-color: #10b981;
		background: #ecfdf5;
	}
	.lx-var-item.is-copied .lx-var-copy-badge {
		color: #047857;
		font-weight: 600;
	}
	.lx-var-code {
		display: block;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 11.5px;
		color: #18181b;
		word-break: break-all;
		line-height: 1.45;
	}

	/* Footer */
	.lx-card .lx-footer {
		padding: 11px 22px;
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
	.lx-kbd {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 11px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 4px;
		padding: 1px 5px;
		color: #18181b;
	}
</style>

<div id="mhm-editor-grid">
	<!-- Left Panel: Code Editor -->
	<div class="lx-card">
		<form autocomplete="off" method="post" action="" style="margin:0;">
			<!-- Top Bar -->
			<div class="lx-topbar">
				<div class="lx-title-group">
					<h3 class="lx-title"><?= $_template_editor ?></h3>
					<span class="lx-file-pill"><?= htmlspecialchars($telplatet); ?>.php</span>
					<?php if ($isSaved): ?>
						<span class="lx-saved-pill">
							<span class="lx-saved-dot"></span>
							<span>Tersimpan</span>
						</span>
					<?php endif; ?>
				</div>

				<div class="lx-actions">
					<a class="lx-btn" href="<?= $popup ?>" title="View voucher with Logo">
						<i class="fa fa-image"></i>
						<span>Preview Logo</span>
					</a>
					<a class="lx-btn" href="<?= $popupQR ?>" title="View voucher with QR">
						<i class="fa fa-qrcode"></i>
						<span>Preview QR</span>
					</a>
					<button type="submit" title="Save template" class="lx-btn is-primary" name="save">
						<i class="fa fa-save"></i>
						<span><?= $_save ?></span>
					</button>
				</div>
			</div>

			<!-- Sub-toolbar: Template & Reset Switcher -->
			<div class="lx-subbar">
				<div class="lx-control-group">
					<span class="lx-control-label">Template</span>
					<div class="lx-seg-group">
						<a class="lx-seg-item <?= ($telplate == 'default' || $telplate == 'rdefault') ? 'is-active' : ''; ?>" href="./admin.php?id=editor&template=default&session=<?= $session; ?>">Default</a>
						<a class="lx-seg-item <?= ($telplate == 'thermal' || $telplate == 'rthermal') ? 'is-active' : ''; ?>" href="./admin.php?id=editor&template=thermal&session=<?= $session; ?>">Thermal</a>
						<a class="lx-seg-item <?= ($telplate == 'small' || $telplate == 'rsmall') ? 'is-active' : ''; ?>" href="./admin.php?id=editor&template=small&session=<?= $session; ?>">Small</a>
					</div>
				</div>

				<div class="lx-control-group">
					<span class="lx-control-label">Reset</span>
					<select class="lx-select" onchange="if(this.value) window.location.href=this.value+'&session=<?= $session; ?>';">
						<option value=""><?= ucfirst($telplate); ?></option>
						<option value="./admin.php?id=editor&template=rdefault">Default</option>
						<option value="./admin.php?id=editor&template=rthermal">Thermal</option>
						<option value="./admin.php?id=editor&template=rsmall">Small</option>
					</select>
				</div>
			</div>

			<!-- CodeMirror Canvas -->
			<div class="lx-editor-shell">
				<textarea class="bg-dark" id="editorMikhmon" name="editor"><?php
					if ($telplate == "default") {
						echo htmlspecialchars(file_get_contents('./voucher/template.php'));
					} elseif ($telplate == "thermal") {
						echo htmlspecialchars(file_get_contents('./voucher/template-thermal.php'));
					} elseif ($telplate == "small") {
						echo htmlspecialchars(file_get_contents('./voucher/template-small.php'));
					} elseif ($telplate == "rdefault") {
						echo htmlspecialchars(file_get_contents('./voucher/default.php'));
					} elseif ($telplate == "rthermal") {
						echo htmlspecialchars(file_get_contents('./voucher/default-thermal.php'));
					} elseif ($telplate == "rsmall") {
						echo htmlspecialchars(file_get_contents('./voucher/default-small.php'));
					}
				?></textarea>
			</div>

			<!-- Footer -->
			<div class="lx-footer">
				<div>Tekan <span class="lx-kbd">Ctrl</span> + <span class="lx-kbd">J</span> untuk mencocokkan tag HTML</div>
				<div>Path: <strong style="color:#18181b;">./voucher/<?= htmlspecialchars($telplatet); ?>.php</strong></div>
			</div>
		</form>
	</div>

	<!-- Right Panel: Variable Reference -->
	<div class="lx-card">
		<div class="lx-topbar">
			<div class="lx-title-group">
				<h3 class="lx-title">Variable</h3>
			</div>
			<span class="lx-file-pill">Klik untuk salin</span>
		</div>

		<?php if (!empty($varItems)): ?>
			<div class="lx-var-list">
				<?php foreach ($varItems as $item): ?>
					<div class="lx-var-item" onclick="copyVarSnippet(this)" data-snippet="<?= htmlspecialchars($item['code'], ENT_QUOTES); ?>">
						<div class="lx-var-head">
							<span class="lx-var-name"><?= htmlspecialchars($item['label']); ?></span>
							<span class="lx-var-copy-badge">Copy</span>
						</div>
						<?php if ($item['code'] !== ''): ?>
							<code class="lx-var-code"><?= htmlspecialchars($item['code']); ?></code>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<textarea id="var" style="display:none;" readonly disabled><?= htmlspecialchars($rawVars); ?></textarea>
		<?php else: ?>
			<div style="padding:14px;">
				<textarea id="var" class="bg-dark" readonly rows="34" style="width:100%; border-radius:8px; padding:10px; font-family:monospace; font-size:12px;" disabled><?= htmlspecialchars($rawVars); ?></textarea>
			</div>
		<?php endif; ?>

		<div class="lx-footer">
			<span>Gunakan variabel di atas pada kode voucher</span>
		</div>
	</div>
</div>

<script>
	function copyVarSnippet(el) {
		var code = el.getAttribute('data-snippet') || '';
		if (!code) return;

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(code);
		} else {
			var tmp = document.createElement('textarea');
			tmp.value = code;
			document.body.appendChild(tmp);
			tmp.select();
			document.execCommand('copy');
			document.body.removeChild(tmp);
		}

		var badge = el.querySelector('.lx-var-copy-badge');
		el.classList.add('is-copied');
		if (badge) badge.textContent = 'Copied!';
		setTimeout(function() {
			el.classList.remove('is-copied');
			if (badge) badge.textContent = 'Copy';
		}, 1200);
	}
</script>

<script>
var _0x5b73=["\x75\x6E\x64\x65\x66\x69\x6E\x65\x64","\x4D\x69\x6B\x68\x6D\x6F\x6E\x53\x65\x73\x73\x69\x6F\x6E","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x73\x65\x74\x49\x74\x65\x6D","\x50\x6C\x65\x61\x73\x65\x20\x75\x73\x65\x20\x47\x6F\x6F\x67\x6C\x65\x20\x43\x68\x72\x6F\x6D\x65","\x67\x65\x74\x49\x74\x65\x6D","\x6E\x75\x6C\x6C","","\x4D\x69\x6B\x68\x6D\x6F\x6E\x20\x62\x61\x6A\x61\x6B\x61\x6E\x21\x20\x3A\x29","\x65\x64\x69\x74\x6F\x72\x4D\x69\x6B\x68\x6D\x6F\x6E","\x61\x70\x70\x6C\x69\x63\x61\x74\x69\x6F\x6E\x2F\x78\x2D\x68\x74\x74\x70\x64\x2D\x70\x68\x70","\x74\x6F\x4D\x61\x74\x63\x68\x69\x6E\x67\x54\x61\x67","\x66\x72\x6F\x6D\x54\x65\x78\x74\x41\x72\x65\x61","\x74\x68\x65\x6D\x65","\x6D\x61\x74\x65\x72\x69\x61\x6C","\x73\x65\x74\x4F\x70\x74\x69\x6F\x6E"];if( typeof (Storage)!== _0x5b73[0]){sessionStorage[_0x5b73[4]](_0x5b73[1],document[_0x5b73[3]](_0x5b73[1])[_0x5b73[2]])}else {alert(_0x5b73[5])};var session=sessionStorage[_0x5b73[6]](_0x5b73[1]);if(session=== _0x5b73[7]|| session=== _0x5b73[8]){alert(_0x5b73[9])};var editor=CodeMirror[_0x5b73[13]](document[_0x5b73[3]](_0x5b73[10]),{lineNumbers:true,matchBrackets:true,mode:_0x5b73[11],indentUnit:4,indentWithTabs:true,lineWrapping:true,viewportMargin:Infinity,matchTags:{bothTags:true},extraKeys:{"\x43\x74\x72\x6C\x2D\x4A":_0x5b73[12]}});editor[_0x5b73[16]](_0x5b73[14],_0x5b73[15])
</script>