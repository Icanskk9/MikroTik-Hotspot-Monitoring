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

	if ($hotspot == "hostp") {
		$gethosts = $API->comm("/ip/hotspot/host/print", array(
			"?bypassed" => "yes",
		));
		$TotalReg = count($gethosts);

		$counthosts = $API->comm("/ip/hotspot/host/print", array(
			"?bypassed" => "yes",
			"count-only" => "",
		));

	} elseif ($hotspot == "hosta") {
		$gethosts = $API->comm("/ip/hotspot/host/print", array(
			"?authorized" => "yes",
		));
		$TotalReg = count($gethosts);

		$counthosts = $API->comm("/ip/hotspot/host/print", array(
			"?authorized" => "yes",
			"count-only" => "",
		));

	} else {
		$gethosts = $API->comm("/ip/hotspot/host/print");
		$TotalReg = count($gethosts);

		$counthosts = $API->comm("/ip/hotspot/host/print", array(
			"count-only" => "",
		));
	}

	// Ringkasan statistik read-only untuk metric strip
	$authCount = 0;
	$bypassCount = 0;
	$dhcpDynCount = 0;
	for ($j = 0; $j < $TotalReg; $j++) {
		if (isset($gethosts[$j]['authorized']) && $gethosts[$j]['authorized'] == "true") {
			$authCount++;
		}
		if (isset($gethosts[$j]['bypassed']) && $gethosts[$j]['bypassed'] == "true") {
			$bypassCount++;
		}
		if ((isset($gethosts[$j]['DHCP']) && $gethosts[$j]['DHCP'] == "true") || (isset($gethosts[$j]['dynamic']) && $gethosts[$j]['dynamic'] == "true")) {
			$dhcpDynCount++;
		}
	}
}
?>

<style>
	#mhm-hosts {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-hosts .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-hosts .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}
	#mhm-hosts .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-hosts .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Segmented Filter Tabs (All / A / P) */
	#mhm-hosts .lx-seg-group {
		display: inline-flex;
		align-items: center;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		padding: 2px;
		gap: 2px;
	}
	#mhm-hosts .lx-seg-item {
		height: 28px;
		padding: 0 11px;
		border-radius: 6px;
		font-size: 12px;
		font-weight: 500;
		color: #52525b;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		gap: 5px;
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	#mhm-hosts .lx-seg-item:hover {
		color: #09090b;
		background: #f4f4f5;
	}
	#mhm-hosts .lx-seg-item.is-active {
		background: #18181b;
		color: #ffffff;
		font-weight: 600;
	}

	/* Controls */
	#mhm-hosts .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-hosts .lx-search {
		position: relative;
		width: 245px;
		max-width: 100%;
	}
	#mhm-hosts .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-hosts #filterTable {
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
	#mhm-hosts #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-hosts #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-hosts .lx-btn-refresh {
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
	#mhm-hosts .lx-btn-refresh:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-hosts .lx-btn-refresh:active {
		background: #f4f4f5;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-hosts .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 640px) {
		#mhm-hosts .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-hosts .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-hosts .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 640px) {
		#mhm-hosts .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-hosts .lx-metric-item:nth-child(1),
		#mhm-hosts .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-hosts .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-hosts .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-hosts .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
	}

	/* Table */
	#mhm-hosts .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 70vh;
	}
	#mhm-hosts #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-hosts #dataTable thead th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #ffffff !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 500 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.06em !important;
		padding: 12px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
		user-select: none;
	}
	#mhm-hosts #dataTable thead th.pointer {
		cursor: pointer;
		transition: color 0.15s ease;
	}
	#mhm-hosts #dataTable thead th.pointer:hover {
		color: #09090b !important;
	}
	#mhm-hosts #dataTable tbody td {
		padding: 14px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-hosts #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-hosts #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-hosts #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Refined Action, Flag & Cell Typography */
	#mhm-hosts .lx-icon-btn {
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
	#mhm-hosts .lx-icon-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-hosts .lx-flag {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 28px;
		height: 22px;
		padding: 0 7px;
		border-radius: 5px;
		font-size: 11px;
		font-weight: 600;
		letter-spacing: 0.03em;
		border: 1px solid #e4e4e7;
		background: #fafafa;
		color: #18181b;
	}
	#mhm-hosts .lx-flag.is-auth {
		background: #ecfdf5;
		border-color: #a7f3d0;
		color: #047857;
	}
	#mhm-hosts .lx-flag.is-bypass {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}
	#mhm-hosts .lx-mono-primary {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-hosts .lx-mono-muted {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #52525b;
	}
	#mhm-hosts .lx-comment {
		color: #52525b;
		font-size: 12.5px;
	}
	#mhm-hosts .lx-empty {
		color: #d4d4d8;
	}

	/* Footer */
	#mhm-hosts .lx-footer {
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

<div id="mhm-hosts">
	<!-- Header & Controls -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title">Hosts</h3>
			<span class="lx-count">
				<?php
				if ($counthosts < 2) {
					echo "$counthosts item";
				} elseif ($counthosts > 1) {
					echo "$counthosts items";
				}
				?>
			</span>

			<!-- Segmented Filter (All / A / P) -->
			<div class="lx-seg-group">
				<a class="lx-seg-item <?= ($hotspot != 'hosta' && $hotspot != 'hostp') ? 'is-active' : ''; ?>" title="All" href="./?hotspot=hosts&session=<?= $session; ?>">
					All
				</a>
				<a class="lx-seg-item <?= ($hotspot == 'hosta') ? 'is-active' : ''; ?>" title="Authorized" href="./?hotspot=hosta&session=<?= $session; ?>">
					A &middot; Authorized
				</a>
				<a class="lx-seg-item <?= ($hotspot == 'hostp') ? 'is-active' : ''; ?>" title="Bypassed" href="./?hotspot=hostp&session=<?= $session; ?>">
					P &middot; Bypassed
				</a>
			</div>
		</div>

		<div class="lx-actions">
			<div class="lx-search">
				<i class="fa fa-search"></i>
				<input
					id="filterTable"
					type="text"
					class="form-control"
					placeholder="Cari MAC, IP, server, atau comment..."
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
			<div class="lx-metric-label">Total Hosts</div>
			<div class="lx-metric-val"><?= (int)$counthosts; ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Authorized (A)</div>
			<div class="lx-metric-val">
				<span class="lx-dot-live"></span>
				<span><?= $authCount; ?></span>
			</div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Bypassed (P)</div>
			<div class="lx-metric-val"><?= $bypassCount; ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">DHCP / Dynamic</div>
			<div class="lx-metric-val"><?= $dhcpDynCount; ?></div>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table id="dataTable" class="table">
			<thead>
				<tr>
					<th style="text-align:center; width:68px;">Aksi</th>
					<th style="text-align:center; width:76px;">Flag</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>MAC Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>To Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Server</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i><?= $_comment ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $TotalReg; $i++) {
					$hosts = $gethosts[$i];
					$id = $hosts['.id'];

					$maca = $hosts['mac-address'];
					$addr = $hosts['address'];
					$toaddr = $hosts['to-address'];
					$server = $hosts['server'];
					$commt = $hosts['comment'];

					$uriprocess = "'./?remove-host=" . $id . "&session=" . $session . "'";

					echo "<tr>";
					echo "<td style='text-align:center;'><span class='lx-icon-btn is-danger pointer' title='Remove " . htmlspecialchars($maca, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-trash-o'></i></span></td>";
					echo "<td style='text-align:center;'>";
					if ($hosts['authorized'] == "true" && $hosts['DHCP'] == "true") {
						echo "<span class='lx-flag is-auth' title='A - authorized, H - DHCP'>A H</span>";
					} elseif ($hosts['authorized'] == "true" && $hosts['dynamic'] == "true") {
						echo "<span class='lx-flag is-auth' title='A - Authorized, D - dynamic'>A D</span>";
					} elseif ($hosts['authorized'] == "true") {
						echo "<span class='lx-flag is-auth' title='A - authorized'>A</span>";
					} elseif ($hosts['DHCP'] == "true") {
						echo "<span class='lx-flag' title='H - DHCP'>H</span>";
					} elseif ($hosts['dynamic'] == "true") {
						echo "<span class='lx-flag' title='D - dynamic'>D</span>";
					} elseif ($hosts['bypassed'] == "true") {
						echo "<span class='lx-flag is-bypass' title='P - Bypassed'>P</span>";
					} else {
						echo "<span class='lx-empty'>&mdash;</span>";
					}
					echo "</td>";
					echo "<td>" . ($maca != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($maca) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($addr != "" ? "<span class='lx-mono-primary'>" . htmlspecialchars($addr) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($toaddr != "" ? "<span class='lx-mono-primary' style='font-weight:500;'>" . htmlspecialchars($toaddr) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td style='color:#52525b; font-weight:500;'>" . ($server != "" ? htmlspecialchars($server) : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($commt != "" ? "<span class='lx-comment'>" . htmlspecialchars($commt) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "</tr>";
				}

				if ($TotalReg == 0) {
					echo "<tr><td colspan='7' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Tidak ada perangkat aktif pada daftar Hotspot Hosts.</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div>
			<span style="font-weight:600; color:#047857;">A</span> Authorized &nbsp;&middot;&nbsp;
			<span style="font-weight:600; color:#18181b;">P</span> Bypassed &nbsp;&middot;&nbsp;
			<span style="font-weight:600; color:#3f3f46;">H</span> DHCP &nbsp;&middot;&nbsp;
			<span style="font-weight:600; color:#3f3f46;">D</span> Dynamic
		</div>
		<div>Klik header kolom untuk mengurutkan</div>
	</div>
</div>