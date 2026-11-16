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

	$getcookies = $API->comm("/ip/hotspot/cookie/print");
	$TotalReg = count($getcookies);

	$countcookies = $API->comm("/ip/hotspot/cookie/print", array(
		"count-only" => "",
	));

	// Ringkasan statistik read-only untuk metric strip
	$uniqueUsers = array();
	$uniqueMacs = array();
	$uniqueDomains = array();
	for ($j = 0; $j < $TotalReg; $j++) {
		if (!empty($getcookies[$j]['user'])) {
			$uniqueUsers[$getcookies[$j]['user']] = true;
		}
		if (!empty($getcookies[$j]['mac-address'])) {
			$uniqueMacs[$getcookies[$j]['mac-address']] = true;
		}
		if (!empty($getcookies[$j]['domain'])) {
			$uniqueDomains[$getcookies[$j]['domain']] = true;
		}
	}
}
?>

<style>
	#mhm-cookies {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-cookies .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-cookies .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	#mhm-cookies .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-cookies .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Controls */
	#mhm-cookies .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-cookies .lx-search {
		position: relative;
		width: 250px;
		max-width: 100%;
	}
	#mhm-cookies .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-cookies #filterTable {
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
	#mhm-cookies #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-cookies #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-cookies .lx-btn-refresh {
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
	#mhm-cookies .lx-btn-refresh:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-cookies .lx-btn-refresh:active {
		background: #f4f4f5;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-cookies .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 640px) {
		#mhm-cookies .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-cookies .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-cookies .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 640px) {
		#mhm-cookies .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-cookies .lx-metric-item:nth-child(1),
		#mhm-cookies .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-cookies .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-cookies .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-cookies .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
	}

	/* Table */
	#mhm-cookies .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 70vh;
	}
	#mhm-cookies #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-cookies #dataTable thead th {
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
	#mhm-cookies #dataTable thead th.pointer {
		cursor: pointer;
		transition: color 0.15s ease;
	}
	#mhm-cookies #dataTable thead th.pointer:hover {
		color: #09090b !important;
	}
	#mhm-cookies #dataTable tbody td {
		padding: 14px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-cookies #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-cookies #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-cookies #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Action & Cell Typography */
	#mhm-cookies .lx-icon-btn {
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
	#mhm-cookies .lx-icon-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-cookies .lx-user {
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-cookies .lx-mono {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #3f3f46;
		font-variant-numeric: tabular-nums;
	}
	#mhm-cookies .lx-expiry {
		display: inline-block;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12px;
		font-weight: 500;
		color: #18181b;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 5px;
		font-variant-numeric: tabular-nums;
	}
	#mhm-cookies .lx-empty {
		color: #d4d4d8;
	}

	/* Footer */
	#mhm-cookies .lx-footer {
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

<div id="mhm-cookies">
	<!-- Header & Controls -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title">Hotspot Cookies</h3>
			<span class="lx-count">
				<?php
				if ($countcookies < 2) {
					echo "$countcookies item";
				} elseif ($countcookies > 1) {
					echo "$countcookies items";
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
					placeholder="Cari user, MAC, atau domain..."
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
			<div class="lx-metric-label">Total Cookies</div>
			<div class="lx-metric-val">
				<span class="lx-dot-live"></span>
				<span><?= (int)$countcookies; ?></span>
			</div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">User Unik</div>
			<div class="lx-metric-val"><?= count($uniqueUsers); ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Perangkat (MAC)</div>
			<div class="lx-metric-val"><?= count($uniqueMacs); ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Domain</div>
			<div class="lx-metric-val"><?= count($uniqueDomains); ?></div>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table id="dataTable" class="table">
			<thead>
				<tr>
					<th style="text-align:center; width:72px;">Aksi</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>User</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>MAC Address</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Domain</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Expires In</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $TotalReg; $i++) {
					$cookies = $getcookies[$i];
					$id = $cookies['.id'];
					$user = $cookies['user'];
					$maca = $cookies['mac-address'];
					$domain = $cookies['domain'];
					$exp = formatDTM($cookies['expires-in']);

					$uriprocess = "'./?remove-cookie=" . $id . "&session=" . $session . "'";

					echo "<tr>";
					echo "<td style='text-align:center;'><span class='lx-icon-btn is-danger pointer' title='Remove " . htmlspecialchars($user, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-trash-o'></i></span></td>";
					echo "<td><span class='lx-user'>" . htmlspecialchars($user) . "</span></td>";
					echo "<td>" . ($maca != "" ? "<span class='lx-mono'>" . htmlspecialchars($maca) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($domain != "" ? "<span style='color:#52525b; font-weight:500;'>" . htmlspecialchars($domain) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($exp != "" ? "<span class='lx-expiry'>" . htmlspecialchars($exp) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "</tr>";
				}

				if ($TotalReg == 0) {
					echo "<tr><td colspan='5' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Tidak ada sesi cookie aktif pada Hotspot.</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div>Klik ikon hapus untuk menghapus sesi auto-login perangkat</div>
		<div>Klik header kolom untuk mengurutkan</div>
	</div>
</div>