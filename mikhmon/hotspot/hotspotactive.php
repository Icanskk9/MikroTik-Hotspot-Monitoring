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

// load session MikroTik
	$session = $_GET['session'];
	$serveractive = $_GET['server'];

// load config
	include('../include/config.php');
	include('../include/readcfg.php');
	
// lang
	include('../include/lang.php');
	include('../lang/'.$langid.'.php');

// routeros api
	include_once('../lib/routeros_api.class.php');
	include_once('../lib/formatbytesbites.php');
	$API = new RouterosAPI();
	$API->debug = false;
	$API->connect($iphost, $userhost, decrypt($passwdhost));

	if ($serveractive != "") {
		$gethotspotactive = $API->comm("/ip/hotspot/active/print", array("?server" => "" . $serveractive . ""));
		$TotalReg = count($gethotspotactive);

		$counthotspotactive = $API->comm("/ip/hotspot/active/print", array(
			"count-only" => "", "?server" => "" . $serveractive . ""
		));

	} else {
		$gethotspotactive = $API->comm("/ip/hotspot/active/print");
		$TotalReg = count($gethotspotactive);

		$counthotspotactive = $API->comm("/ip/hotspot/active/print", array(
			"count-only" => "",
		));
	}

	// Ringkasan statistik read-only untuk metric strip
	$totalBytesIn = 0;
	$totalBytesOut = 0;
	$uniqueServers = array();
	for ($j = 0; $j < $TotalReg; $j++) {
		$totalBytesIn += (float)$gethotspotactive[$j]['bytes-in'];
		$totalBytesOut += (float)$gethotspotactive[$j]['bytes-out'];
		if (!empty($gethotspotactive[$j]['server'])) {
			$uniqueServers[$gethotspotactive[$j]['server']] = true;
		}
	}
	$formattedTotalIn = $totalBytesIn > 0 ? formatBytes($totalBytesIn, 2) : "0 B";
	$formattedTotalOut = $totalBytesOut > 0 ? formatBytes($totalBytesOut, 2) : "0 B";
}
?>

<style>
	#mhm-active {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-active .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-active .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-active .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-active .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}
	#mhm-active .lx-filter-chip {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 11.5px;
		font-weight: 500;
		color: #18181b;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		padding: 2px 9px;
		border-radius: 6px;
	}
	#mhm-active .lx-showall-btn {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		height: 26px;
		padding: 0 10px;
		border-radius: 6px;
		background: #18181b;
		color: #ffffff !important;
		font-size: 11.5px;
		font-weight: 500;
		text-decoration: none;
		transition: background 0.15s ease;
	}
	#mhm-active .lx-showall-btn:hover {
		background: #27272a;
	}

	/* Controls */
	#mhm-active .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-active .lx-search {
		position: relative;
		width: 250px;
		max-width: 100%;
	}
	#mhm-active .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-active #filterTable {
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
		transition: all 0.15s ease !important;
	}
	#mhm-active #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-active #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-active .lx-btn-refresh {
		height: 34px;
		padding: 0 13px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		font-size: 12.5px;
		font-weight: 500;
		color: #18181b;
		display: inline-flex;
		align-items: center;
		gap: 6px;
		cursor: pointer;
		transition: all 0.15s ease;
	}
	#mhm-active .lx-btn-refresh:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-active .lx-btn-refresh:active {
		background: #f4f4f5;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-active .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 768px) {
		#mhm-active .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-active .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-active .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 768px) {
		#mhm-active .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-active .lx-metric-item:nth-child(1),
		#mhm-active .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-active .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-active .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-active .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
		flex-shrink: 0;
	}

	/* Table */
	#mhm-active .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 70vh;
	}
	#mhm-active #tFilter {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-active #tFilter thead th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #ffffff !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 500 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.06em !important;
		padding: 12px 18px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		user-select: none;
	}
	#mhm-active #tFilter tbody td {
		padding: 13px 18px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-active #tFilter tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-active #tFilter tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-active #tFilter tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Refined Cell Links, Badges & Typography */
	#mhm-active .lx-icon-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 28px;
		height: 28px;
		border-radius: 6px;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #52525b;
		font-size: 12px;
		cursor: pointer;
		transition: all 0.15s ease;
	}
	#mhm-active .lx-icon-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-active .lx-server-link {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 3px 9px;
		border-radius: 6px;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		color: #3f3f46 !important;
		font-size: 12px;
		font-weight: 500;
		text-decoration: none;
		transition: all 0.15s ease;
	}
	#mhm-active .lx-server-link:hover {
		background: #f4f4f5;
		border-color: #d4d4d8;
		color: #09090b !important;
	}
	#mhm-active .lx-user-link {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-weight: 600;
		color: #09090b !important;
		text-decoration: none;
		letter-spacing: -0.01em;
		transition: opacity 0.15s ease;
	}
	#mhm-active .lx-user-link i {
		font-size: 11.5px;
		color: #71717a;
	}
	#mhm-active .lx-user-link:hover {
		text-decoration: underline;
	}
	#mhm-active .lx-mono-primary {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		font-weight: 600;
		color: #09090b;
		font-variant-numeric: tabular-nums;
	}
	#mhm-active .lx-mono-muted {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #52525b;
		font-variant-numeric: tabular-nums;
	}
	#mhm-active .lx-badge {
		display: inline-block;
		font-size: 11.5px;
		font-weight: 500;
		color: #3f3f46;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 5px;
	}
	#mhm-active .lx-empty {
		color: #d4d4d8;
	}

	/* Footer */
	#mhm-active .lx-footer {
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
</style>

<div class="row">
<div id="reloadHotspotActive" style="width:100%;">
<div class="col-12">
	<div id="mhm-active">
		<!-- Header & Controls -->
		<div class="lx-topbar">
			<div class="lx-title-group">
				<h3 class="lx-title"><?= $_hotspot_active ?></h3>
				<span class="lx-count">
					<?php
					if ($counthotspotactive < 2) {
						echo "$counthotspotactive item";
					} elseif ($counthotspotactive > 1) {
						echo "$counthotspotactive items";
					}
					?>
				</span>

				<?php if ($serveractive != ""): ?>
					<span class="lx-filter-chip">
						<i class="fa fa-server" style="font-size:11px; color:#71717a;"></i>
						<span><?= htmlspecialchars($serveractive); ?></span>
					</span>
					<a class="lx-showall-btn" href="./?hotspot=active&session=<?= $session; ?>">
						<i class="fa fa-times" style="font-size:10.5px;"></i>
						<span>Show all</span>
					</a>
				<?php endif; ?>
			</div>

			<div class="lx-actions">
				<div class="lx-search">
					<i class="fa fa-search"></i>
					<input
						id="filterTable"
						type="text"
						class="form-control"
						placeholder="Cari user, IP, MAC, server..."
						oninput="filterActiveTable(this.value)"
					>
				</div>
				<button
					type="button"
					onclick="location.reload();"
					class="lx-btn-refresh"
					title="Reload data"
				>
					<i class="fa fa-refresh" style="font-size:11.5px; color:#71717a;"></i>
					<span>Refresh</span>
				</button>
			</div>
		</div>

		<!-- Integrated Metric Strip -->
		<div class="lx-metrics">
			<div class="lx-metric-item">
				<div class="lx-metric-label">User Aktif</div>
				<div class="lx-metric-val">
					<span class="lx-dot-live"></span>
					<span><?= (int)$counthotspotactive; ?></span>
				</div>
			</div>
			<div class="lx-metric-item">
				<div class="lx-metric-label">Total Download</div>
				<div class="lx-metric-val"><?= htmlspecialchars($formattedTotalIn); ?></div>
			</div>
			<div class="lx-metric-item">
				<div class="lx-metric-label">Total Upload</div>
				<div class="lx-metric-val"><?= htmlspecialchars($formattedTotalOut); ?></div>
			</div>
			<div class="lx-metric-item">
				<div class="lx-metric-label">Server Aktif</div>
				<div class="lx-metric-val">
					<?= $serveractive != "" ? htmlspecialchars($serveractive) : count($uniqueServers); ?>
				</div>
			</div>
		</div>

		<!-- Table -->
		<div class="lx-table-wrap">
			<table id="tFilter" class="table">
				<thead>
					<tr>
						<th style="text-align:center; width:64px;">Aksi</th>
						<th>Server</th>
						<th>User</th>
						<th>Address</th>
						<th>Mac Address</th>
						<th style="text-align:right;">Uptime</th>
						<th style="text-align:right;">Bytes In</th>
						<th style="text-align:right;">Bytes Out</th>
						<th style="text-align:right;">Time Left</th>
						<th>Login By</th>
						<th><?= $_comment ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $TotalReg; $i++) {
						$hotspotactive = $gethotspotactive[$i];
						$id = $hotspotactive['.id'];
						$server = $hotspotactive['server'];
						$user = $hotspotactive['user'];
						$address = $hotspotactive['address'];
						$mac = $hotspotactive['mac-address'];
						$uptime = formatDTM($hotspotactive['uptime']);
						$usesstime = formatDTM($hotspotactive['session-time-left']);
						$bytesi = formatBytes($hotspotactive['bytes-in'], 2);
						$byteso = formatBytes($hotspotactive['bytes-out'], 2);
						$loginby = $hotspotactive['login-by'];
						$comment = $hotspotactive['comment'];
						$uriprocess = "'./?remove-user-active=" . $id . "&session=" . $session . "'";

						echo "<tr>";
						echo "<td style='text-align:center;'><span class='lx-icon-btn is-danger pointer' title='Remove " . htmlspecialchars($user, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-trash-o'></i></span></td>";
						echo "<td><a class='lx-server-link' title='filter " . htmlspecialchars($server, ENT_QUOTES) . "' href='./?hotspot=active&server=" . $server . "&session=" . $session . "'><i class='fa fa-server' style='font-size:11px; color:#71717a;'></i><span>" . htmlspecialchars($server) . "</span></a></td>";
						echo "<td><a class='lx-user-link' title='Open User " . htmlspecialchars($user, ENT_QUOTES) . "' href='./?hotspot-user=" . $user . "&session=" . $session . "'><i class='fa fa-edit'></i><span>" . htmlspecialchars($user) . "</span></a></td>";
						echo "<td>" . ($address != "" ? "<span class='lx-mono-primary'>" . htmlspecialchars($address) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td>" . ($mac != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($mac) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td style='text-align:right;'>" . ($uptime != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($uptime) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td style='text-align:right;'>" . ($bytesi != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($bytesi) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td style='text-align:right;'>" . ($byteso != "" ? "<span class='lx-mono-primary' style='font-weight:500;'>" . htmlspecialchars($byteso) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td style='text-align:right;'>" . ($usesstime != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($usesstime) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td>" . ($loginby != "" ? "<span class='lx-badge'>" . htmlspecialchars($loginby) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td style='color:#52525b; font-size:12.5px;'>" . ($comment != "" ? htmlspecialchars($comment) : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "</tr>";
					}

					if ($TotalReg == 0) {
						echo "<tr><td colspan='11' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Tidak ada pengguna Hotspot yang sedang aktif saat ini.</td></tr>";
					}
					?>
				</tbody>
			</table>
		</div>

		<!-- Footer -->
		<div class="lx-footer">
			<div>Klik nama server untuk filter &nbsp;&middot;&nbsp; Klik username untuk kelola akun</div>
			<div>Menampilkan sesi Hotspot aktif secara real-time</div>
		</div>
	</div>
</div>
</div>
</div>

<script>
	function filterActiveTable(val) {
		window._mhmActiveSearch = val;
		var q = (val || '').toLowerCase();
		var rows = document.querySelectorAll('#tFilter tbody tr');
		for (var i = 0; i < rows.length; i++) {
			var text = rows[i].textContent || rows[i].innerText || '';
			rows[i].style.display = text.toLowerCase().indexOf(q) > -1 ? '' : 'none';
		}
	}
	(function() {
		if (window._mhmActiveSearch) {
			var inp = document.getElementById('filterTable');
			if (inp) {
				inp.value = window._mhmActiveSearch;
				filterActiveTable(window._mhmActiveSearch);
			}
		}
	})();
</script>