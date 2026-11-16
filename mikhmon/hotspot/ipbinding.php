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

	// Proses tambah IP Binding baru ke MikroTik
	if (isset($_POST['add_ipbinding'])) {
		$b_mac = trim($_POST['mac_address']);
		$b_addr = trim($_POST['address']);
		$b_toaddr = trim($_POST['to_address']);
		$b_server = trim($_POST['server']);
		$b_type = trim($_POST['type']);
		$b_comment = trim($_POST['comment']);

		if ($b_mac != "" || $b_addr != "") {
			$params = array(
				"type" => ($b_type != "" ? $b_type : "bypassed"),
			);
			if ($b_mac != "") {
				$params["mac-address"] = strtoupper($b_mac);
			}
			if ($b_addr != "") {
				$params["address"] = $b_addr;
			}
			if ($b_toaddr != "") {
				$params["to-address"] = $b_toaddr;
			}
			if ($b_server != "" && $b_server != "all") {
				$params["server"] = $b_server;
			}
			if ($b_comment != "") {
				$params["comment"] = $b_comment;
			}

			$API->comm("/ip/hotspot/ip-binding/add", $params);
			echo "<script>window.location='./?hotspot=ip-binding&session=" . $session . "'</script>";
			exit;
		}
	}

	$getbinding = $API->comm("/ip/hotspot/ip-binding/print");
	$TotalReg = count($getbinding);

	$countbinding = $API->comm("/ip/hotspot/ip-binding/print", array(
		"count-only" => "",
	));

	$gethsserver = $API->comm("/ip/hotspot/print");
	$TotalServer = count($gethsserver);

	// Ringkasan statistik read-only untuk metric strip
	$bypassedCount = 0;
	$enabledCount = 0;
	$disabledCount = 0;
	for ($j = 0; $j < $TotalReg; $j++) {
		if (isset($getbinding[$j]['bypassed']) && $getbinding[$j]['bypassed'] == "true") {
			$bypassedCount++;
		}
		if (isset($getbinding[$j]['disabled']) && $getbinding[$j]['disabled'] == "true") {
			$disabledCount++;
		} else {
			$enabledCount++;
		}
	}
}
?>

<style>
	#mhm-ipbinding {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
		color: #18181b;
	}

	/* Top Bar */
	#mhm-ipbinding .lx-topbar {
		padding: 18px 24px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		background: #ffffff;
	}
	#mhm-ipbinding .lx-title-group {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	#mhm-ipbinding .lx-title {
		margin: 0;
		font-size: 16px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
	}
	#mhm-ipbinding .lx-count {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}

	/* Controls */
	#mhm-ipbinding .lx-actions {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
	}
	#mhm-ipbinding .lx-search {
		position: relative;
		width: 240px;
		max-width: 100%;
	}
	#mhm-ipbinding .lx-search i {
		position: absolute;
		left: 11px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 12px;
		color: #a1a1aa;
		pointer-events: none;
	}
	#mhm-ipbinding #filterTable {
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
	#mhm-ipbinding #filterTable::placeholder {
		color: #a1a1aa !important;
	}
	#mhm-ipbinding #filterTable:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	#mhm-ipbinding .lx-btn {
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
		gap: 6px;
		cursor: pointer;
		text-decoration: none !important;
		transition: all 0.15s ease;
		white-space: nowrap;
	}
	#mhm-ipbinding .lx-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b !important;
	}
	#mhm-ipbinding .lx-btn:active {
		background: #f4f4f5;
	}
	#mhm-ipbinding .lx-btn.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff !important;
	}
	#mhm-ipbinding .lx-btn.is-primary:hover {
		background: #27272a;
		border-color: #27272a;
	}

	/* Integrated Minimalist Metric Strip */
	#mhm-ipbinding .lx-metrics {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		border-bottom: 1px solid #e4e4e7;
		background: #fafafa;
	}
	@media (max-width: 640px) {
		#mhm-ipbinding .lx-metrics {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
	#mhm-ipbinding .lx-metric-item {
		padding: 14px 24px;
		border-right: 1px solid #e4e4e7;
	}
	#mhm-ipbinding .lx-metric-item:last-child {
		border-right: none;
	}
	@media (max-width: 640px) {
		#mhm-ipbinding .lx-metric-item:nth-child(2) {
			border-right: none;
		}
		#mhm-ipbinding .lx-metric-item:nth-child(1),
		#mhm-ipbinding .lx-metric-item:nth-child(2) {
			border-bottom: 1px solid #e4e4e7;
		}
	}
	#mhm-ipbinding .lx-metric-label {
		font-size: 10.5px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}
	#mhm-ipbinding .lx-metric-val {
		font-size: 18px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.02em;
		font-variant-numeric: tabular-nums;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	#mhm-ipbinding .lx-dot-live {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #10b981;
		display: inline-block;
	}

	/* Table */
	#mhm-ipbinding .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 70vh;
	}
	#mhm-ipbinding #dataTable {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	#mhm-ipbinding #dataTable thead th {
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
	#mhm-ipbinding #dataTable thead th.pointer {
		cursor: pointer;
		transition: color 0.15s ease;
	}
	#mhm-ipbinding #dataTable thead th.pointer:hover {
		color: #09090b !important;
	}
	#mhm-ipbinding #dataTable tbody td {
		padding: 14px 20px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 13px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	#mhm-ipbinding #dataTable tbody tr:last-child td {
		border-bottom: none !important;
	}
	#mhm-ipbinding #dataTable tbody tr {
		transition: background-color 0.12s ease;
	}
	#mhm-ipbinding #dataTable tbody tr:hover {
		background-color: #fafafa !important;
	}

	/* Refined Action Buttons & Cell Typography */
	#mhm-ipbinding .lx-action-group {
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	#mhm-ipbinding .lx-icon-btn {
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
	#mhm-ipbinding .lx-icon-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b;
	}
	#mhm-ipbinding .lx-icon-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626;
	}
	#mhm-ipbinding .lx-icon-btn.is-locked {
		background: #fffbeb;
		border-color: #fde68a;
		color: #b45309;
	}
	#mhm-ipbinding .lx-icon-btn.is-locked:hover {
		background: #fef3c7;
		border-color: #fcd34d;
	}
	#mhm-ipbinding .lx-flag {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 22px;
		height: 22px;
		border-radius: 5px;
		font-size: 11px;
		font-weight: 600;
		background: #18181b;
		border: 1px solid #18181b;
		color: #ffffff;
	}
	#mhm-ipbinding .lx-flag.is-blocked {
		background: #dc2626;
		border-color: #dc2626;
	}
	#mhm-ipbinding .lx-name {
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-ipbinding .lx-mono-primary {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		font-weight: 600;
		color: #09090b;
		letter-spacing: -0.01em;
	}
	#mhm-ipbinding .lx-mono-muted {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12.5px;
		color: #52525b;
	}
	#mhm-ipbinding .lx-empty {
		color: #d4d4d8;
	}

	/* Footer */
	#mhm-ipbinding .lx-footer {
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

	/* Custom Dialog & Add Modal */
	.lx-overlay {
		position: fixed;
		inset: 0;
		z-index: 9999;
		background: rgba(9, 9, 11, 0.45);
		backdrop-filter: blur(2px);
		display: none;
		align-items: center;
		justify-content: center;
		padding: 16px;
	}
	.lx-overlay.is-open {
		display: flex;
	}
	.lx-dialog {
		width: 100%;
		max-width: 460px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.22);
		overflow: hidden;
		color: #18181b;
		animation: lxPop 0.14s ease-out;
	}
	@keyframes lxPop {
		from { opacity: 0; transform: scale(0.97) translateY(4px); }
		to { opacity: 1; transform: scale(1) translateY(0); }
	}
	.lx-dialog-head {
		padding: 16px 20px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.lx-dialog-title {
		margin: 0;
		font-size: 15px;
		font-weight: 600;
		color: #09090b;
	}
	.lx-dialog-close {
		width: 28px;
		height: 28px;
		border-radius: 6px;
		border: 1px solid transparent;
		background: transparent;
		color: #71717a;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		justify-content: center;
	}
	.lx-dialog-close:hover {
		background: #f4f4f5;
		color: #09090b;
	}
	.lx-dialog-body {
		padding: 18px 20px;
		display: flex;
		flex-direction: column;
		gap: 13px;
	}
	.lx-form-row {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 12px;
	}
	@media (max-width: 480px) {
		.lx-form-row {
			grid-template-columns: minmax(0, 1fr);
		}
	}
	.lx-field {
		display: flex;
		flex-direction: column;
		gap: 5px;
	}
	.lx-field label {
		font-size: 12px;
		font-weight: 500;
		color: #3f3f46;
		margin: 0;
	}
	.lx-field label span {
		font-weight: 400;
		color: #a1a1aa;
	}
	.lx-input, .lx-select {
		width: 100% !important;
		height: 36px !important;
		padding: 0 11px !important;
		background: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 12.5px !important;
		color: #09090b !important;
		box-shadow: none !important;
		margin: 0 !important;
	}
	.lx-input:focus, .lx-select:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	.lx-mono-input {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
	}
	.lx-dialog-foot {
		padding: 13px 20px;
		background: #fafafa;
		border-top: 1px solid #f4f4f5;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
	}
	.lx-btn-modal {
		height: 34px;
		padding: 0 14px;
		border-radius: 8px;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #18181b;
		font-size: 12.5px;
		font-weight: 500;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	.lx-btn-modal:hover {
		background: #f4f4f5;
	}
	.lx-btn-modal.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff;
	}
	.lx-btn-modal.is-primary:hover {
		background: #27272a;
	}
	.lx-btn-modal.is-danger {
		background: #dc2626;
		border-color: #dc2626;
		color: #ffffff;
	}
	.lx-btn-modal.is-danger:hover {
		background: #b91c1c;
	}

	/* Clean Help Modal Styling */
	.modal-window > div {
		border-radius: 12px !important;
		border: 1px solid #e4e4e7 !important;
		box-shadow: 0 20px 40px -15px rgba(9, 9, 11, 0.18) !important;
		padding: 24px !important;
		background: #ffffff !important;
		color: #18181b !important;
	}
	.modal-window header h1 {
		font-size: 16px !important;
		font-weight: 600 !important;
		color: #09090b !important;
		margin: 0 0 10px 0 !important;
	}
	.modal-window ul {
		font-size: 13px !important;
		color: #52525b !important;
		line-height: 1.7 !important;
		padding-left: 18px !important;
		margin: 0 !important;
	}
</style>

<div id="reloadbinding">
	<div id="mhm-ipbinding">
		<!-- Header & Controls -->
		<div class="lx-topbar">
			<div class="lx-title-group">
				<h3 class="lx-title"><?= $_ip_bindings ?></h3>
				<span class="lx-count">
					<?php
					if ($countbinding < 2) {
						echo "$countbinding item";
					} elseif ($countbinding > 1) {
						echo "$countbinding items";
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
						placeholder="Cari nama, MAC, atau IP..."
					>
				</div>
				<button
					type="button"
					onclick="openAddBindingModal();"
					class="lx-btn is-primary"
					title="Tambah perangkat ke IP Bindings"
				>
					<i class="fa fa-plus" style="font-size:11.5px; color:#d4d4d8;"></i>
					<span>Tambah Binding</span>
				</button>
				<a href="#help" class="lx-btn" title="Help">
					<i class="fa fa-question-circle" style="font-size:12px; color:#71717a;"></i>
					<span>Help</span>
				</a>
				<button
					type="button"
					onclick="location.reload();"
					class="lx-btn"
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
				<div class="lx-metric-label">Total Bindings</div>
				<div class="lx-metric-val"><?= (int)$countbinding; ?></div>
			</div>
			<div class="lx-metric-item">
				<div class="lx-metric-label">Bypassed (P)</div>
				<div class="lx-metric-val"><?= $bypassedCount; ?></div>
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
		</div>

		<!-- Table -->
		<div class="lx-table-wrap">
			<table id="dataTable" class="table">
				<thead>
					<tr>
						<th style="text-align:center; width:96px;">Aksi</th>
						<th style="text-align:center; width:64px;">Type</th>
						<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i><?= $_name ?></th>
						<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>MAC Address</th>
						<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Address</th>
						<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>To Address</th>
						<th class="pointer" title="Click to sort"><i class="fa fa-sort" style="color:#d4d4d8; margin-right:6px;"></i>Server</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $TotalReg; $i++) {
						$binding = $getbinding[$i];
						$id = $binding['.id'];

						$maca = $binding['mac-address'];
						$addr = $binding['address'];
						$toaddr = $binding['to-address'];
						$server = $binding['server'];
						$commt = $binding['comment'];
						$bdisabled = $binding['disabled'];
						$delLabel = $commt != "" ? $commt . " (" . $maca . ")" : ($maca != "" ? $maca : $addr);
						$delUrl = "./?remove-ip-binding=" . $id . "&mac=" . $maca . "&addr=" . $addr . "&session=" . $session;

						echo "<tr>";
						?>
						<td style="text-align:center;">
							<div class="lx-action-group">
								<span
									class="lx-icon-btn is-danger pointer"
									onclick="confirmRemoveBinding('<?= htmlspecialchars($delLabel, ENT_QUOTES); ?>', '<?= htmlspecialchars($delUrl, ENT_QUOTES); ?>');"
									title="Remove <?= htmlspecialchars($delLabel, ENT_QUOTES); ?>"
								>
									<i class="fa fa-trash-o"></i>
								</span>
								<?php if ($bdisabled == "true") {
									$uriprocess = "'./?enable-ip-binding=" . $id . "&session=" . $session . "'";
									echo "<span class='lx-icon-btn is-locked btnsmall pointer' title='Enable Binding " . htmlspecialchars($delLabel, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-lock'></i></span>";
								} else {
									$uriprocess = "'./?disable-ip-binding=" . $id . "&session=" . $session . "'";
									echo "<span class='lx-icon-btn btnsmall pointer' title='Disable Binding " . htmlspecialchars($delLabel, ENT_QUOTES) . "' onclick=\"loadpage(" . $uriprocess . ")\"><i class='fa fa-unlock'></i></span>";
								} ?>
							</div>
						</td>
						<?php
						echo "<td style='text-align:center;'>";
						if ($binding['bypassed'] == "true") {
							echo "<span title='P - bypassed' class='lx-flag'>P</span>";
						} elseif ($binding['blocked'] == "true") {
							echo "<span title='B - blocked' class='lx-flag is-blocked'>B</span>";
						} else {
							echo "<span class='lx-empty'>&mdash;</span>";
						}
						echo "</td>";
						echo "<td>" . ($commt != "" ? "<span class='lx-name'>" . htmlspecialchars($commt) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td>" . ($maca != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($maca) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td>" . ($addr != "" ? "<span class='lx-mono-primary'>" . htmlspecialchars($addr) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td>" . ($toaddr != "" ? "<span class='lx-mono-muted'>" . htmlspecialchars($toaddr) . "</span>" : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "<td style='color:#52525b; font-weight:500;'>" . ($server != "" ? htmlspecialchars($server) : "<span class='lx-empty'>&mdash;</span>") . "</td>";
						echo "</tr>";
					}

					if ($TotalReg == 0) {
						echo "<tr><td colspan='7' style='padding:48px 20px; text-align:center; color:#a1a1aa; font-size:12.5px;'>Belum ada perangkat yang terdaftar pada IP Bindings.</td></tr>";
					}
					?>
				</tbody>
			</table>
		</div>

		<!-- Footer -->
		<div class="lx-footer">
			<div>
				<span style="font-weight:600; color:#3f3f46;">P</span> Bypassed &nbsp;&middot;&nbsp;
				<span style="font-weight:600; color:#3f3f46;"><i class="fa fa-unlock" style="margin-right:4px;"></i>Aktif</span> &nbsp;&middot;&nbsp;
				<span style="font-weight:600; color:#b45309;"><i class="fa fa-lock" style="margin-right:4px;"></i>Nonaktif</span>
			</div>
			<div>Klik header kolom untuk mengurutkan</div>
		</div>
	</div>

	<!-- Modal Tambah IP Binding -->
	<div id="lxAddBindingModal" class="lx-overlay" onclick="if(event.target===this) closeAddBindingModal();">
		<div class="lx-dialog">
			<div class="lx-dialog-head">
				<h4 class="lx-dialog-title">Tambah IP Binding</h4>
				<button type="button" class="lx-dialog-close" onclick="closeAddBindingModal();">
					<i class="fa fa-times"></i>
				</button>
			</div>
			<form method="post" action="" autocomplete="off" style="margin:0;">
				<div class="lx-dialog-body">
					<div class="lx-field">
						<label>Nama Perangkat / Keterangan <span>(Comment)</span></label>
						<input type="text" name="comment" class="lx-input" placeholder="Contoh: PC-Guru-Lab1 atau Printer-Lab" required>
					</div>

					<div class="lx-field">
						<label>MAC Address <span>(Wajib jika IP kosong)</span></label>
						<input type="text" name="mac_address" id="lxMacInput" class="lx-input lx-mono-input" placeholder="AA:BB:CC:DD:EE:FF" maxlength="17">
					</div>

					<div class="lx-form-row">
						<div class="lx-field">
							<label>Address <span>(Opsional)</span></label>
							<input type="text" name="address" class="lx-input lx-mono-input" placeholder="10.50.50.10">
						</div>
						<div class="lx-field">
							<label>To Address <span>(Opsional)</span></label>
							<input type="text" name="to_address" class="lx-input lx-mono-input" placeholder="10.50.50.10">
						</div>
					</div>

					<div class="lx-form-row">
						<div class="lx-field">
							<label>Server Hotspot</label>
							<select name="server" class="lx-select">
								<option value="all">all</option>
								<?php
								for ($s = 0; $s < $TotalServer; $s++) {
									$srvName = $gethsserver[$s]['name'];
									if ($srvName != "") {
										echo "<option value=\"" . htmlspecialchars($srvName, ENT_QUOTES) . "\">" . htmlspecialchars($srvName) . "</option>";
									}
								}
								?>
							</select>
						</div>
						<div class="lx-field">
							<label>Type</label>
							<select name="type" class="lx-select">
								<option value="bypassed">bypassed (Bebas Login)</option>
								<option value="regular">regular (Normal)</option>
								<option value="blocked">blocked (Blokir)</option>
							</select>
						</div>
					</div>
				</div>
				<div class="lx-dialog-foot">
					<button type="button" class="lx-btn-modal" onclick="closeAddBindingModal();">Batal</button>
					<button type="submit" name="add_ipbinding" class="lx-btn-modal is-primary">
						<i class="fa fa-save"></i>
						<span>Simpan Binding</span>
					</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Modal Konfirmasi Hapus -->
	<div id="lxDeleteBindingModal" class="lx-overlay" onclick="if(event.target===this) closeDeleteBindingModal();">
		<div class="lx-dialog" style="max-width:400px;">
			<div class="lx-dialog-head">
				<h4 class="lx-dialog-title">Hapus IP Binding</h4>
				<button type="button" class="lx-dialog-close" onclick="closeDeleteBindingModal();">
					<i class="fa fa-times"></i>
				</button>
			</div>
			<div class="lx-dialog-body">
				<p style="margin:0; font-size:13px; color:#3f3f46; line-height:1.6;">
					Yakin ingin menghapus <strong id="lxDelTargetName" style="color:#09090b;"></strong> dari daftar IP Bindings? Perangkat tersebut akan kembali diminta login voucher.
				</p>
			</div>
			<div class="lx-dialog-foot">
				<button type="button" class="lx-btn-modal" onclick="closeDeleteBindingModal();">Batal</button>
				<button type="button" id="lxConfirmDelBtn" class="lx-btn-modal is-danger">
					<i class="fa fa-trash-o"></i>
					<span>Hapus</span>
				</button>
			</div>
		</div>
	</div>

	<!-- Modal Help -->
	<div class="modal-window" id="help" aria-hidden="true">
		<div>
			<header><h1>Help</h1></header>
			<a style="font-weight:bold;" href="#" title="Close" class="modal-close">X</a>
			<p>
				<ul>
					<li>Klik tombol <strong>Tambah Binding</strong> untuk mendaftarkan MAC Address PC Guru, Server, atau Printer agar bebas akses tanpa login voucher.</li>
					<li>Pilih tipe <strong>bypassed</strong> agar perangkat langsung terhubung ke internet tanpa halaman login Hotspot.</li>
					<li>Anda juga bisa mengklik IP Address di menu <strong>Hosts</strong> untuk melakukan binding otomatis.</li>
				</ul>
			</p>
		</div>
	</div>
</div>

<script>
	function openAddBindingModal() {
		var modal = document.getElementById('lxAddBindingModal');
		if (modal) {
			modal.classList.add('is-open');
			var macInput = document.getElementById('lxMacInput');
			if (macInput) setTimeout(function() { macInput.focus(); }, 60);
		}
	}
	function closeAddBindingModal() {
		var modal = document.getElementById('lxAddBindingModal');
		if (modal) modal.classList.remove('is-open');
	}
	function confirmRemoveBinding(label, url) {
		var modal = document.getElementById('lxDeleteBindingModal');
		var targetEl = document.getElementById('lxDelTargetName');
		var btn = document.getElementById('lxConfirmDelBtn');
		if (modal && targetEl && btn) {
			targetEl.textContent = label;
			btn.onclick = function() {
				closeDeleteBindingModal();
				loadpage(url);
			};
			modal.classList.add('is-open');
		}
	}
	function closeDeleteBindingModal() {
		var modal = document.getElementById('lxDeleteBindingModal');
		if (modal) modal.classList.remove('is-open');
	}
</script>