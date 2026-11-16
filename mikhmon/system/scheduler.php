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

	$getsch = $API->comm("/system/scheduler/print");
	$TotalReg = count($getsch);

	$countsch = $API->comm("/system/scheduler/print", array(
		"count-only" => "",
	));

	$enabledCount = 0;
	$disabledCount = 0;
	$totalRunCount = 0;
	for ($j = 0; $j < $TotalReg; $j++) {
		if (isset($getsch[$j]['disabled']) && $getsch[$j]['disabled'] == "true") {
			$disabledCount++;
		} else {
			$enabledCount++;
		}
		if (isset($getsch[$j]['run-count'])) {
			$totalRunCount += (int)$getsch[$j]['run-count'];
		}
	}
}
?>

<style>
	#mhm-scheduler {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-scheduler .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-scheduler .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	#mhm-scheduler .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-scheduler .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Controls */
	#mhm-scheduler .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-scheduler .lx-search {
		position: relative;
		width: 250px;
		max-width: 100%;
	}
	#mhm-scheduler .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-scheduler #filterTable {
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
	#mhm-scheduler #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-scheduler #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-scheduler .lx-btn-refresh {
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
	#mhm-scheduler .lx-btn-refresh:hover {
		background: #fafafa;
		border-color: #d4d4d8;
	}
	#mhm-scheduler .lx-btn-refresh:active {
		background: #f4f4f5;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-scheduler .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 640px) {
		#mhm-scheduler .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-scheduler .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-scheduler .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 640px) {
		#mhm-scheduler .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-scheduler .lx-metric-item:nth-child(1),
		#mhm-scheduler .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-scheduler .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-scheduler .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-scheduler .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
	}

	/* Table */
	#mhm-scheduler .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 70vh;
	}
	#mhm-scheduler #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-scheduler #dataTable thead th {
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
	#mhm-scheduler #dataTable thead th.pointer {
		cursor: pointer;
		transition: color 0.15s ease;
	}
	#mhm-scheduler #dataTable thead th.pointer:hover {
		color: #09090b !important;
	}
	#mhm-scheduler #dataTable tbody td {
		padding: 14px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-scheduler #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-scheduler #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-scheduler #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Refined Action Buttons & Cell Typography */
	#mhm-scheduler .lx-action-group {
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	#mhm-scheduler .lx-icon-btn {
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
	#mhm-scheduler .lx-icon-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b;
	}
	#mhm-scheduler .lx-icon-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-scheduler .lx-icon-btn.is-locked {
		background: #fffbeb;
		border-color: #fde68a;
		color: #b45309;
	}
	#mhm-scheduler .lx-icon-btn.is-locked:hover {
		background: #fef3c7;
		border-color: #fcd34d;
	}
	#mhm-scheduler .lx-name {
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-scheduler .lx-mono {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #3f3f46;
		font-variant-numeric: tabular-nums;
	}
	#mhm-scheduler .lx-mono-muted {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #71717a;
		font-variant-numeric: tabular-nums;
	}
	#mhm-scheduler .lx-interval {
		display: inline-block;
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12px;
		font-weight: 500;
		color: #18181b;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 5px;
	}
	#mhm-scheduler .lx-comment {
		color: #52525b;
		font-size: 12.5px;
	}
	#mhm-scheduler .lx-empty {
		color: #d4d4d8;
	}

	/* Footer */
	#mhm-scheduler .lx-footer {
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

<div id="mhm-scheduler">
	<!-- Header & Controls -->
	<div class="lx-topbar">
		<div class="lx-title-group">
			<h3 class="lx-title">System Scheduler</h3>
			<span class="lx-count">
				<?php
				if ($countsch < 2) {
					echo "$countsch item";
				} elseif ($countsch > 1) {
					echo "$countsch items";
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
					placeholder="Cari nama scheduler, comment..."
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
			<div class="lx-metric-label">Total Scheduler</div>
			<div class="lx-metric-val"><?= (int)$countsch; ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Aktif</div>
			<div class="lx-metric-val">
				<span class="lx-dot-live"></span>
				<span><?= $enabledCount; ?></span>
			</div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Nonaktif</div>
			<div class="lx-metric-val"><?= $disabledCount; ?></div>
		</div>
		<div class="lx-metric-item">
			<div class="lx-metric-label">Total Eksekusi</div>
			<div class="lx-metric-val"><?= number_format($totalRunCount); ?></div>
		</div>
	</div>

	<!-- Table -->
	<div class="lx-table-wrap">
		<table id="dataTable" class="table">
			<thead>
				<tr>
					<th style="text-align:center; width:96px;">Aksi</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Name</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Start Date</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Start Time</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Interval</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Next Run</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Run Count</th>
					<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Comment</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $TotalReg; $i++) {
					$sch = $getsch[$i];
					$id = $sch['.id'];
					$name = $sch['name'];
					$startd = $sch['start-date'];
					$startt = $sch['start-time'];
					$interval = formatInterval($sch['interval']);
					$nextrun = $sch['next-run'];
					$runcount = $sch['run-count'];
					$comment = $sch['comment'];
					$disabled = $sch['disabled'];

					echo "<tr>";
					?>
					<td style="text-align:center;">
						<div class="lx-action-group">
							<span
								class="lx-icon-btn is-danger pointer"
								onclick="if(confirm('Are you sure to delete scheduler (<?= htmlspecialchars($name, ENT_QUOTES); ?>)?')){loadpage('./?remove-scheduler=<?= $id; ?>&session=<?= $session; ?>')}else{}"
								title="Remove <?= htmlspecialchars($name, ENT_QUOTES); ?>"
							>
								<i class="fa fa-trash-o"></i>
							</span>
							<?php if ($disabled == "true") {
								$uriprocess = "'./?enable-scheduler=" . $id . "&session=" . $session . "'";
								echo "<span class='lx-icon-btn is-locked pointer' title='Enable Scheduler " . htmlspecialchars($name, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-lock'></i></span>";
							} else {
								$uriprocess = "'./?disable-scheduler=" . $id . "&session=" . $session . "'";
								echo "<span class='lx-icon-btn pointer' title='Disable Scheduler " . htmlspecialchars($name, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-unlock'></i></span>";
							} ?>
						</div>
					</td>
					<?php
					echo "<td><span class='lx-name'>" . htmlspecialchars($name) . "</span></td>";
					echo "<td>" . ($startd != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($startd) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($startt != "" ? "<span class='lx-mono'>" . htmlspecialchars($startt) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($interval != "" ? "<span class='lx-interval'>" . htmlspecialchars($interval) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td>" . ($nextrun != "" ? "<span class='lx-mono'>" . htmlspecialchars($nextrun) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "<td><span class='lx-mono' style='font-weight:600; color:#09090b;'>" . htmlspecialchars($runcount) . "</span></td>";
					echo "<td>" . ($comment != "" ? "<span class='lx-comment'>" . htmlspecialchars($comment) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
					echo "</tr>";
				}

				if ($TotalReg == 0) {
					echo "<tr><td colspan='8' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Tidak ada jadwal aktif pada System Scheduler.</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>

	<!-- Footer -->
	<div class="lx-footer">
		<div>
			<span style="font-weight:600; color:#3f3f46;"><i class="fa fa-unlock" style="margin-right:4px;"></i>Aktif</span> &nbsp;&middot;&nbsp;
			<span style="font-weight:600; color:#b45309;"><i class="fa fa-lock" style="margin-right:4px;"></i>Nonaktif</span>
		</div>
		<div>Klik header kolom untuk mengurutkan</div>
	</div>
</div>