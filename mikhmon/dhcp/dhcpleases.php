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

	$getlease = $API->comm("/ip/dhcp-server/lease/print");
	$TotalReg = count($getlease);

	$countlease = $API->comm("/ip/dhcp-server/lease/print", array(
		"count-only" => "",
	));

	$boundCount = 0;
	$dynamicCount = 0;
	$staticCount = 0;
	for ($j = 0; $j < $TotalReg; $j++) {
		if (isset($getlease[$j]['status']) && $getlease[$j]['status'] == "bound") {
			$boundCount++;
		}
		if (isset($getlease[$j]['dynamic']) && $getlease[$j]['dynamic'] == "true") {
			$dynamicCount++;
		} else {
			$staticCount++;
		}
	}
}
?>

<style>
	#mhm-dhcp {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-dhcp .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-dhcp .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	#mhm-dhcp .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-dhcp .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Controls */
	#mhm-dhcp .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-dhcp .lx-search {
		position: relative;
		width: 250px;
		max-width: 100%;
	}
	#mhm-dhcp .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-dhcp #filterTable {
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
	#mhm-dhcp #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-dhcp #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-dhcp .lx-btn-refresh {
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
	#mhm-dhcp .lx-btn-refresh:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-dhcp .lx-btn-refresh:active {
		background: #f4f4f5;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-dhcp .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 640px) {
		#mhm-dhcp .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-dhcp .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-dhcp .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 640px) {
		#mhm-dhcp .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-dhcp .lx-metric-item:nth-child(1),
		#mhm-dhcp .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-dhcp .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-dhcp .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-dhcp .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
	}

	/* Table */
	#mhm-dhcp .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 70vh;
	}
	#mhm-dhcp #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-dhcp #dataTable thead th {
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
	#mhm-dhcp #dataTable thead th.pointer {
		cursor: pointer;
		transition: color 0.15s ease;
	}
	#mhm-dhcp #dataTable thead th.pointer:hover {
		color: #09090b !important;
	}
	#mhm-dhcp #dataTable tbody td {
		padding: 14px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-dhcp #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-dhcp #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-dhcp #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Refined Cell Typography */
	#mhm-dhcp .lx-flag {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 22px;
		height: 22px;
		border-radius: 5px;
		font-size: 11px;
		font-weight: 600;
		border: 1px solid #e4e4e7;
		background: #fafafa;
		color: #3f3f46;
	}
	#mhm-dhcp .lx-flag.is-static {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}
	#mhm-dhcp .lx-mono-primary {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-dhcp .lx-mono-muted {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #71717a;
	}
	#mhm-dhcp .lx-host {
		font-weight: 500;
		color: #09090b;
	}
	#mhm-dhcp .lx-status {
		display: inline-flex;
		align-items: center;
		gap: 7px;
		font-size: 12.5px;
		font-weight: 500;
		color: #18181b;
	}
	#mhm-dhcp .lx-status-dot {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
	}
	#mhm-dhcp .lx-status-dot.is-wait {
		background: #f59e0b;
	}
	#mhm-dhcp .lx-empty {
		color: #d4d4d8;
	}

	/* Footer */
	#mhm-dhcp .lx-footer {
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

<div id="mhm-dhcp">
	<!-- Header & Controls -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title">DHCP Leases</h3>
			<span class="lx-count">
				<?php
				if ($countlease < 2) {
					echo "$countlease item";
				} elseif ($countlease > 1) {
					echo "$countlease items";
				}
				?>
			</span>
		</div>

		<div class="lx-actions">
			<div class="lx-search">
				<i class="fa fa-search"></i>
				<input
					id="filterTable"
					type="text"
					class="form-control"
					placeholder="Cari IP, MAC, atau hostname..."
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
			<div class="lx-metric-label">Total Leases</div>
			<div class="lx-metric-val"><?= (int)$countlease; ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Bound</div>
			<div class="lx-metric-val">
				<span class="lx-dot-live"></span>
				<span><?= $boundCount; ?></span>
			</div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Dynamic (D)</div>
			<div class="lx-metric-val"><?= $dynamicCount; ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Static (S)</div>
			<div class="lx-metric-val"><?= $staticCount; ?></div>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table id="dataTable" class="table">
			<thead>
				<tr>
					<th style="text-align:center; width:68px;">Type</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>MAC Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Server</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Active Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Active MAC Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Active Host Name</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $TotalReg; $i++) {
					$lease = $getlease[$i];
					$id = $lease['.id'];

					$addr = $lease['address'];
					$maca = $lease['mac-address'];
					$server = $lease['server'];
					$aaddr = $lease['active-address'];
					$amaca = $lease['active-mac-address'];
					$ahostname = $lease['host-name'];
					$status = $lease['status'];

					echo "<tr>";

					// Type D / S
					echo "<td style='text-align:center;'>";
					if ($lease['dynamic'] == "true") {
						echo "<span title='D - dynamic' class='lx-flag'>D</span>";
					} else {
						echo "<span title='S - static' class='lx-flag is-static'>S</span>";
					}
					echo "</td>";

					// Address
					echo "<td>" . ($addr != "" ? "<span class='lx-mono-primary'>" . htmlspecialchars($addr) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";

					// MAC Address
					echo "<td>" . ($maca != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($maca) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";

					// Server
					echo "<td style='color:#52525b; font-weight:500;'>" . ($server != "" ? htmlspecialchars($server) : "<span class='lx-empty'>&mdash;</span>") . "</td>";

					// Active Address
					echo "<td>" . ($aaddr != "" ? "<span class='lx-mono-primary' style='font-weight:500;'>" . htmlspecialchars($aaddr) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";

					// Active MAC Address
					echo "<td>" . ($amaca != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($amaca) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";

					// Active Host Name
					echo "<td>" . ($ahostname != "" ? "<span class='lx-host'>" . htmlspecialchars($ahostname) . "</span>" : "<span style='color:#a1a1aa; font-style:italic;'>Unknown</span>") . "</td>";

					// Status
					echo "<td>";
					if ($status == "bound") {
						echo "<span class='lx-status'><span class='lx-status-dot'></span>" . htmlspecialchars($status) . "</span>";
					} else {
						echo "<span class='lx-status' style='color:#71717a;'><span class='lx-status-dot is-wait'></span>" . ($status != "" ? htmlspecialchars($status) : "waiting") . "</span>";
					}
					echo "</td>";

					echo "</tr>";
				}

				if ($TotalReg == 0) {
					echo "<tr><td colspan='8' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Tidak ada data DHCP Lease aktif.</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div>
			<span style="font-weight:600; color:#3f3f46;">D</span> Dynamic &nbsp;&middot;&nbsp;
			<span style="font-weight:600; color:#3f3f46;">S</span> Static
		</div>
		<div>Klik header kolom untuk mengurutkan</div>
	</div>
</div>