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
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
} else {

	$idhr = $_GET['idhr'];
	$idbl = $_GET['idbl'];
	$idbl2 = explode("/",$idhr)[0].explode("/",$idhr)[2];
	if ($idhr != ""){
		$_SESSION['report'] = "&idhr=".$idhr;
	} elseif ($idbl != ""){
		$_SESSION['report'] = "&idbl=".$idbl;
	} else {
		$_SESSION['report'] = "";
	}
	$_SESSION['idbl'] = $idbl;
	$remdata = ($_POST['remdata']);
	$prefix = $_GET['prefix'];
	

	$gettimezone = $API->comm("/system/clock/print");
	$timezone = $gettimezone[0]['time-zone-name'];
	date_default_timezone_set($timezone);

	if (isset($remdata)) {
		if (strlen($idhr) > "0") {
			if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
				$API->write('/system/script/print', false);
				$API->write('?source=' . $idhr . '', false);
				$API->write('=.proplist=.id');
				$ARREMD = $API->read();
				for ($i = 0; $i < count($ARREMD); $i++) {
					$API->write('/system/script/remove', false);
					$API->write('=.id=' . $ARREMD[$i]['.id']);
					$READ = $API->read();
				}
			}
		} elseif (strlen($idbl) > "0") {
			if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
				$API->write('/system/script/print', false);
				$API->write('?owner=' . $idbl . '', false);
				$API->write('=.proplist=.id');
				$ARREMD = $API->read();
				for ($i = 0; $i < count($ARREMD); $i++) {
					$API->write('/system/script/remove', false);
					$API->write('=.id=' . $ARREMD[$i]['.id']);
					$READ = $API->read();
				}
			}
		}
		echo "<script>window.location='./?report=selling&session=" . $session . "'</script>";
	}

	if ($prefix != "") {
		$fprefix = "-prefix-[" . $prefix . "]";
	} else {
		$fprefix = "";
	}
	if (strlen($idhr) > "0") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?source" => "$idhr",
			));
			$TotalReg = count($getData);
		}
		$filedownload = $idhr;
		$shf = "hidden";
		$shd = "inline-flex";
	} elseif (strlen($idbl) > "0") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?owner" => "$idbl",
			));
			$TotalReg = count($getData);
		}
		$filedownload = $idbl;
		$shf = "hidden";
		$shd = "inline-flex";
	} elseif ($idhr == "" || $idbl == "") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?comment" => "mikhmon",
			));
			$TotalReg = count($getData);
		}
		$filedownload = "all";
		$shf = "text";
		$shd = "none";
	} elseif (strlen($idbl) > "0" ) {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?owner" => "$idbl",
			));
			$TotalReg = count($getData);
		}
		$filedownload = $idbl;
		$shf = "hidden";
		$shd = "inline-flex";
	}

	// Hitung ringkasan statistik untuk metric strip tanpa mengubah alur data utama
	$statCount = 0;
	$statRevenue = 0;
	$statProfiles = array();
	for ($k = 0; $k < $TotalReg; $k++) {
		$pName = explode("-|-", $getData[$k]['name']);
		if ($prefix == "" || substr($pName[2], 0, strlen($prefix)) == $prefix) {
			$statCount++;
			$statRevenue += (float)$pName[3];
			if (!empty($pName[7])) {
				$statProfiles[$pName[7]] = true;
			}
		}
	}
	if ($currency == in_array($currency, $cekindo['indo'])) {
		$formattedRevenue = $currency . " " . number_format($statRevenue, 0, "", ".");
	} else {
		$formattedRevenue = $currency . " " . number_format($statRevenue, 2, ".", ",");
	}
	$periodLabel = trim(ucfirst($idhr) . " " . ucfirst(substr($idbl, 0, 3) . " " . substr($idbl, 3, 5)));
	if ($periodLabel == "") {
		$periodLabel = "Semua Periode";
	}
}
?>
<script>
	function downloadCSV(csv, filename) {
		var csvFile;
		var downloadLink;
		csvFile = new Blob([csv], {type: "text/csv"});
		downloadLink = document.createElement("a");
		downloadLink.download = filename;
		downloadLink.href = window.URL.createObjectURL(csvFile);
		downloadLink.style.display = "none";
		document.body.appendChild(downloadLink);
		downloadLink.click();
	}

	function exportTableToCSV(filename) {
		var csv = [];
		var rows = document.querySelectorAll("#dataTable tr");

		for (var i = 0; i < rows.length; i++) {
			var row = [], cols = rows[i].querySelectorAll("td, th");
			for (var j = 0; j < cols.length; j++)
				row.push(cols[j].innerText);
			csv.push(row.join(","));
		}
		downloadCSV(csv.join("\n"), filename);
	}

	function number_format(number, decimals, dec_point, thousands_sep) {
		number = (number + '')
			.replace(/[^0-9+\-Ee.]/g, '');
		var n = !isFinite(+number) ? 0 : +number,
			prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
			sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
			dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
			s = '',
			toFixedFix = function(n, prec) {
				var k = Math.pow(10, prec);
				return '' + (Math.round(n * k) / k)
					.toFixed(prec);
			};
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
			.split('.');
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || '')
			.length < prec) {
			s[1] = s[1] || '';
			s[1] += new Array(prec - s[1].length + 1)
				.join('0');
		}
		return s.join(dec);
	}

	window.onload = function() {
		var sum = 0;
		var dataTable = document.getElementById("selling");

		// use querySelector to find all sixth table cells
		var cells = document.querySelectorAll("td + td + td + td + td + td");
		for (var i = 0; i < cells.length; i++)
			sum += parseFloat(cells[i].firstChild.data) || 0;

		var th = document.getElementById('total');
		<?php if ($currency == in_array($currency, $cekindo['indo'])) {
			echo 'th.innerHTML = "' . $currency . ' " + number_format(th.innerHTML + (sum),"","",".") ;';
		} else {
			echo 'th.innerHTML = "' . $currency . ' " + number_format(th.innerHTML + (sum),2,".",",") ;';
		} ?>

		var tables = document.getElementsByTagName('tbody');
		var table = tables[tables.length - 1];
		var rows = table.rows;
		for (var i = 0, td; i < rows.length; i++) {
			td = document.createElement('td');
			td.appendChild(document.createTextNode(i + 1));
			rows[i].insertBefore(td, rows[i].firstChild);
		}
	}
</script>

<script>
	$(document).ready(function(){
		$("#openResume").click(function(){
			notify("Calculating data");
			window.location = "./?report=resume-report&idbl=<?= $idbl;?>&session=<?= $session;?>";
		});
	});
</script>

<style>
	#mhm-selling {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.06);
		overflow: hidden;
		color: #18181b;
		margin-bottom: 24px;
	}

	/* Top Bar */
	#mhm-selling .lx-topbar {
		padding: 22px 28px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-selling .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-selling .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-selling .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 9px;
		border-radius: 999px;
		font-variant-numeric: tabular-nums;
	}

	/* Action Buttons */
	#mhm-selling .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-selling .lx-btn,
	.lx-modal-btn {
		height: 34px;
		padding: 0 13px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		font-size: 12px;
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
	#mhm-selling .lx-btn:hover,
	.lx-modal-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b;
	}
	#mhm-selling .lx-btn:active,
	.lx-modal-btn:active {
		background: #f4f4f5;
	}
	#mhm-selling .lx-btn i {
		font-size: 11.5px;
		color: #71717a;
	}
	#mhm-selling .lx-btn.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}
	#mhm-selling .lx-btn.is-primary i {
		color: #d4d4d8;
	}
	#mhm-selling .lx-btn.is-primary:hover {
		background: #27272a;
		border-color: #27272a;
	}
	#mhm-selling .lx-btn.is-danger,
	.lx-modal-btn.is-danger {
		background: #ffffff;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-selling .lx-btn.is-danger i {
		color: #dc2626;
	}
	#mhm-selling .lx-btn.is-danger:hover,
	.lx-modal-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fca5a5;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-selling .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 768px) {
		#mhm-selling .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-selling .lx-metric-item {
		padding: 18px 28px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-selling .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 768px) {
		#mhm-selling .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-selling .lx-metric-item:nth-child(1),
		#mhm-selling .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-selling .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 6px;
	}
	#mhm-selling .lx-metric-val {
		font-size: 17px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	#mhm-selling .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
		flex-shrink: 0;
	}

	/* Filter Toolbar */
	#mhm-selling .lx-filterbar {
		padding: 16px 28px;
		background: #ffffff;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 14px;
	}
	#mhm-selling .lx-search {
		position: relative;
		width: 260px;
		max-width: 100%;
	}
	#mhm-selling .lx-search i {
		position: absolute;
		left: 12px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-selling #filterTable {
		width: 100% !important;
		height: 35px !important;
		padding: 0 12px 0 34px !important;
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
	#mhm-selling #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-selling #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}

	#mhm-selling .lx-date-group {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-selling .lx-select {
		appearance: none !important;
		-webkit-appearance: none !important;
		-moz-appearance: none !important;
		height: 35px !important;
		padding: 0 30px 0 12px !important;
		background-color: #fafafa !important;
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
		background-repeat: no-repeat !important;
		background-position: right 10px center !important;
		background-size: 13px !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		font-weight: 500 !important;
		color: #18181b !important;
		cursor: pointer;
		outline: none !important;
		transition: all 0.15s ease;
	}
	#mhm-selling .lx-select:hover {
		border-color: #d4d4d8 !important;
		background-color: #ffffff !important;
	}
	#mhm-selling .lx-select:focus {
		border-color: #18181b !important;
		background-color: #ffffff !important;
	}

	/* Table */
	#mhm-selling .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 68vh;
	}
	#mhm-selling #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-selling #dataTable thead tr:first-child th {
		background: #fafafa !important;
		color: #18181b !important;
		font-size: 11.5px !important;
		font-weight: 600 !important;
		letter-spacing: 0.02em;
		padding: 12px 28px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		font-variant-numeric: tabular-nums;
	}
	#mhm-selling #dataTable thead tr:first-child th#total {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 13px !important;
		font-weight: 700 !important;
		color: #09090b !important;
	}
	#mhm-selling #dataTable thead tr:nth-child(2) th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #ffffff !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 500 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.06em !important;
		padding: 12px 28px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		user-select: none;
	}
	#mhm-selling #dataTable tbody td {
		padding: 14px 28px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-selling #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-selling #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-selling #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}
	/* Kolom No (inserted by window.onload as 1st child) */
	#mhm-selling #dataTable tbody td:nth-child(1) {
		color: #a1a1aa !important;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12px !important;
		width: 56px;
	}
	/* Kolom Date & Time */
	#mhm-selling #dataTable tbody td:nth-child(2),
	#mhm-selling #dataTable tbody td:nth-child(3) {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px !important;
		color: #52525b !important;
		font-variant-numeric: tabular-nums;
	}
	/* Kolom Username */
	#mhm-selling #dataTable tbody td:nth-child(4) {
		font-weight: 600 !important;
		color: #09090b !important;
	}
	/* Kolom Price (7th child after row number inserted) */
	#mhm-selling #dataTable tbody td:nth-child(7) {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px !important;
		font-weight: 600 !important;
		color: #09090b !important;
		font-variant-numeric: tabular-nums;
	}

	/* Empty State */
	#mhm-selling .lx-empty-state {
		padding: 56px 28px;
		text-align: center;
		color: #a1a1aa;
		font-size: 12.5px;
		border-top: 1px solid #f4f4f5;
	}

	/* Footer */
	#mhm-selling .lx-footer {
		padding: 14px 28px;
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

	/* Clean Modal Styling */
	.modal-window > div {
		border-radius: 12px !important;
		border: 1px solid #e4e4e7 !important;
		box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.16) !important;
		padding: 24px 26px !important;
		background: #ffffff !important;
		color: #18181b !important;
	}
	.lx-modal-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding-bottom: 14px;
		margin-bottom: 14px;
		border-bottom: 1px solid #f4f4f5;
	}
	.lx-modal-head h1 {
		font-size: 15px !important;
		font-weight: 600 !important;
		color: #09090b !important;
		margin: 0 !important;
	}
	.lx-modal-x {
		width: 28px;
		height: 28px;
		border-radius: 7px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		color: #71717a !important;
		text-decoration: none !important;
		font-size: 16px;
		line-height: 1;
		transition: background 0.15s ease, color 0.15s ease;
	}
	.lx-modal-x:hover {
		background: #f4f4f5;
		color: #09090b !important;
	}
	.modal-window p {
		font-size: 13px !important;
		color: #52525b !important;
		line-height: 1.65 !important;
		margin: 0 !important;
	}
	.lx-modal-foot {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
		margin-top: 20px;
		padding-top: 14px;
		border-top: 1px solid #f4f4f5;
	}
</style>

<div id="mhm-selling">
	<!-- Top Bar -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title">
				<?= $_selling_report ?> <?= ucfirst($idhr) . ucfirst(substr($idbl,0,3).' '.substr($idbl,3,5)); if ($prefix != "") {echo " prefix [" . $prefix . "]";} ?>
			</h3>
			<span class="lx-count">
				<?= $statCount; ?> <?= ($statCount > 1) ? 'items' : 'item'; ?>
			</span>
			<small id="loader" style="display: none; font-size: 12px; color: #71717a;">
				<i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?></i>
			</small>
		</div>

		<!-- Action Buttons -->
		<div class="lx-actions">
			<button type="button" name="help" class="lx-btn" onclick="location.href='#help';" title="Help">
				<i class="fa fa-question-circle"></i>
				<span><?= $_help ?></span>
			</button>

			<button type="button" class="lx-btn" onclick="exportTableToCSV('report-mikhmon-<?= $filedownload . $fprefix; ?>.csv')" title="Download selling report">
				<i class="fa fa-download"></i>
				<span>CSV</span>
			</button>

			<button type="button" class="lx-btn" onclick="location.href='./?report=selling&session=<?= $session; ?>';" title="Reload all data">
				<i class="fa fa-list-ul"></i>
				<span><?= $_all ?></span>
			</button>

			<?php if (!empty($idbl)) {
				echo '<button type="button" name="resume" id="openResume" class="lx-btn" title="Resume Report"><i class="fa fa-area-chart"></i><span>' . $_resume . '</span></button>';
			} else {
				echo '<a class="lx-btn" href="./?report=selling&idbl=' . $idbl2 . '&session=' . $session . '" title="Show ' . ucfirst(substr($idbl2,0,3) . ' ' . substr($idbl2,3,5)) . '"><i class="fa fa-calendar"></i><span>' . ucfirst(substr($idbl2,0,3) . ' ' . substr($idbl2,3,5)) . '</span></a>';
			} ?>

			<button type="button" name="print" class="lx-btn is-primary" onclick="window.open('./report/print.php?<?= explode("?report=selling&",$url)[1] ?>','_blank');" title="Print">
				<i class="fa fa-print"></i>
				<span><?= $_print ?></span>
			</button>

			<button type="button" style="display: <?= $shd; ?>;" name="remdata" class="lx-btn is-danger" onclick="location.href='#remdata';" title="Delete Data <?= $filedownload; ?>">
				<i class="fa fa-trash-o"></i>
				<span><?= $_delete_data . ' ' . $filedownload; ?></span>
			</button>

			<button type="button" id="remSelected" style="display: none;" class="lx-btn is-danger" onclick="MikhmonRemoveReportSelected()">
				<i class="fa fa-trash-o"></i>
				<span><span id="selected"></span> <?= $_selected ?></span>
			</button>
		</div>
	</div>

	<!-- Integrated Metric Strip -->
	<div class="lx-metrics">
		<div class="lx-metric-item">
			<div class="lx-metric-label">Periode Laporan</div>
			<div class="lx-metric-val"><?= htmlspecialchars($periodLabel); ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Voucher Terjual</div>
			<div class="lx-metric-val">
				<span class="lx-dot-live"></span>
				<span><?= number_format($statCount); ?></span>
			</div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Estimasi Total</div>
			<div class="lx-metric-val"><?= htmlspecialchars($formattedRevenue); ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Variasi Profil</div>
			<div class="lx-metric-val"><?= count($statProfiles); ?></div>
		</div>
	</div>

	<!-- Filter Bar -->
	<div class="lx-filterbar">
		<div class="lx-search">
			<i class="fa fa-search"></i>
			<input id="filterTable" type="text" class="form-control" placeholder="<?= $_search ?> / Prefix...">
		</div>

		<div class="lx-date-group">
			<select class="lx-select" title="<?= $_days ?>" id="D">
				<?php
				$day = explode("/", $idhr)[1];
				if ($day != "") {
					echo "<option value='" . $day . "'>" . $day . "</option>";
				}
				echo "<option value=''>Day</option>";

				for ($x = 1; $x <= 31; $x++) {
					if (strlen($x) == 1) {
						$x = "0" . $x;
					} else {
						$x = $x;
					}
					echo "<option value='" . $x . "'>" . $x . "</option>";
				}
				?>
			</select>

			<select class="lx-select" title="Month" id="M">
				<?php
				$idbls = array(1 => "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec");
				$idblf = array(1 => "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
				$month = explode("/", $idhr)[0];
				$month1 = substr($idbl, 0, 3);

				if ($month != "") {
					$fm = array_search($month, $idbls);
					echo "<option value='" . $month . "'>" . $idblf[$fm] . "</option>";
				} elseif ($month1 != "") {
					$fm = array_search($month1, $idbls);
					echo "<option value=" . $month1 . ">" . $idblf[$fm] . "</option>";
				} else {
					echo "<option value=" . $idbls[date("n")] . ">" . $idblf[date("n")] . "</option>";
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
					echo "<option>" . $year . "</option>";
				} elseif ($year1 != "") {
					echo "<option>" . $year1 . "</option>";
				}
				echo "<option>" . date("Y") . "</option>";

				for ($Y = 2018; $Y <= date("Y"); $Y++) {
					if ($Y == date("Y")) {
					} else {
						echo "<option value='" . $Y . "'>" . $Y . "</option>";
					}
				}
				?>
			</select>

			<button type="button" class="lx-btn is-primary" onclick="filterR(); loader();">
				<i class="fa fa-filter"></i>
				<span>Filter</span>
			</button>

			<script type="text/javascript">
				function filterR(){
					var D = document.getElementById('D').value;
					var M = document.getElementById('M').value;
					var Y = document.getElementById('Y').value;
					var X = document.getElementById('filterTable').value;

					if(D !== ""){
						window.location='./?report=selling&idhr='+M+'/'+D+'/'+Y+'&prefix='+X+'&session=<?= $session; ?>';
					}else if(D === ""){
						window.location='./?report=selling&idbl='+M+Y+'&prefix='+X+'&session=<?= $session; ?>';
					}
				}
			</script>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table id="dataTable" class="table">
			<thead>
				<tr>
					<th colspan="5"><?= $_selling_report ?> <?= $filedownload . $fprefix; ?><b style="font-size:0;">,,,,</b></th>
					<th style="text-align:right;"><?= $_total ?></th>
					<th style="text-align:right;" id="total"></th>
				</tr>
				<tr>
					<th>&#8470;</th>
					<th><?= $_date ?></th>
					<th><?= $_time ?></th>
					<th><?= $_user_name ?></th>
					<th><?= $_profile ?></th>
					<th><?= $_comment ?></th>
					<th style="text-align:right;"><?= $_price ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ($prefix != "") {
					for ($i = 0; $i < $TotalReg; $i++) {
						$getname = explode("-|-", $getData[$i]['name']);
						if (substr($getname[2], 0, strlen($prefix)) == $prefix) {
							echo "<tr>";
							echo "<td>";
							$tgl = $getname[0];
							echo $tgl;
							echo "</td>";
							echo "<td>";
							$ltime = $getname[1];
							echo $ltime;
							echo "</td>";
							echo "<td>";
							$username = $getname[2];
							echo $username;
							echo "</td>";
							echo "<td>";
							$profile = $getname[7];
							echo $profile;
							echo "</td>";
							echo "<td>";
							$comment = $getname[8];
							echo $comment;
							echo "</td>";
							echo "<td style='text-align:right;'>";
							$price = $getname[3];
							echo $price;
							echo "</td>";
							echo "</tr>";
						}
					}
				} else {
					for ($i = 0; $i < $TotalReg; $i++) {
						$getname = explode("-|-", $getData[$i]['name']);
						echo "<tr>";
						echo "<td>";
						$tgl = $getname[0];
						echo $tgl;
						echo "</td>";
						echo "<td>";
						$ltime = $getname[1];
						echo $ltime;
						echo "</td>";
						echo "<td>";
						$username = $getname[2];
						echo $username;
						echo "</td>";
						echo "<td>";
						$profile = $getname[7];
						echo $profile;
						echo "</td>";
						echo "<td>";
						$comment = $getname[8];
						echo $comment;
						echo "</td>";
						echo "<td style='text-align:right;'>";
						$price = $getname[3];
						echo $price;
						echo "</td>";
						echo "</tr>";

						$dataresume .= $getname[0].$getname[3];
						$totalresume += $getname[3];
						$_SESSION['dataresume'] = $dataresume;
						$_SESSION['totalresume'] = $TotalReg.'/'.$totalresume;
					}
				}
				?>
			</tbody>
		</table>

		<?php if ($statCount == 0): ?>
			<div class="lx-empty-state">
				Belum ada data transaksi penjualan pada periode ini.
			</div>
		<?php endif; ?>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div>Mata Uang: <strong style="color:#18181b;"><?= htmlspecialchars($currency); ?></strong></div>
		<div>Pilih Day untuk filter harian atau kosongkan untuk rekap bulanan</div>
	</div>
</div>

<!-- Modal -->
<div class="modal-window" id="remdata" aria-hidden="true">
	<div>
		<div class="lx-modal-head">
			<h1><?= $_confirm ?></h1>
			<a href="#" title="Close" class="lx-modal-x">&times;</a>
		</div>
		<p><?= $_delete_report ?></p>
		<form autocomplete="off" method="post" action="">
			<div class="lx-modal-foot">
				<a class="lx-modal-btn" href="#" title="Close">No</a>
				<button type="submit" name="remdata" title="Yes" class="lx-modal-btn is-danger">Yes, Delete</button>
			</div>
		</form>
	</div>
</div>
<div class="modal-window" id="help" aria-hidden="true">
	<div>
		<div class="lx-modal-head">
			<h1><?= $_help ?></h1>
			<a href="#" title="Close" class="lx-modal-x">&times;</a>
		</div>
		<p><?= $_help_report ?></p>
		<div class="lx-modal-foot">
			<a class="lx-modal-btn" href="#" title="Close">Tutup</a>
		</div>
	</div>
</div>