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

ini_set('max_execution_time', 300);

if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
} else {
	$qpid = $_GET['qpid'];
	$rem = $_GET['remove'];
	$charup = array(
		"lower" => "abcd",
		"upper" => " ABCD",
		"upplow" => " aBcD",
		"mix" => " 5ab2c34d",
		"mix1" => " 5AB2C34D",
		"mix2" => "5aB2c34D",
	);

	$charvc = array(
		"lower" => " abcd2345",
		"upper" => " ABCD2345",
		"upplow" => " aBcD2345",
		"num" => " 1234",
	);

	if (isset($qpid) && isset($rem)) {
		$API->comm("/system/script/remove", array(
			".id" => "$qpid",
		));
		echo '<script>window.location.reload()</script>';
	}

	// get quick print
	$getquickprint = $API->comm("/system/script/print", array("?.id" => "$qpid"));
	$quickprintdetails = $getquickprint[0];
	$qpid = $quickprintdetails['.id'];
	$quickprintsource = explode("#", $quickprintdetails['source']);
	$package = $quickprintsource[1];
	$server = $quickprintsource[2];
	$usermode = $quickprintsource[3];
	$userlength = $quickprintsource[4];
	$prefix = $quickprintsource[5];
	$char = $quickprintsource[6];
	$profile = $quickprintsource[7];
	$timelimit = $quickprintsource[8];
	$datalimit = $quickprintsource[9];
	$comment = $quickprintsource[10];

	if ($usermode == "up") {
		$tusermode = $_user_pass;
		$tchar = $charup[$char];
	} elseif ($usermode == "vc") {
		$tusermode = $_user_user;
		$tchar = $charvc[$char];
	}

	if (substr(formatBytes2($datalimit, 2), -2) == "MB") {
		$udatalimit = $datalimit / 1048576;
		$xdatalimit = 1048576;
		$MG = "MB";
	} elseif (substr(formatBytes2($datalimit, 2), -2) == "GB") {
		$udatalimit = $datalimit / 1073741824;
		$xdatalimit = 1073741824;
		$MG = "GB";
	} else {
		$udatalimit = "";
		$xdatalimit = 1048576;
		$MG = "MB";
	}

	$srvlist = $API->comm("/ip/hotspot/print");
	$getprofile = $API->comm("/ip/hotspot/user/profile/print");

	if (isset($_POST['name'])) {
		$name = ($_POST['name']);
		$sname = "Quick_Print_" . (preg_replace('/\s+/', '-', $_POST['name']));
		$server = ($_POST['server']);
		$user = ($_POST['user']);
		$userl = ($_POST['userl']);
		$prefix = ($_POST['prefix']);
		$char = ($_POST['char']);
		$profile = ($_POST['profile']);
		$timelimit = ($_POST['timelimit']);
		$datalimit = ($_POST['datalimit']);
		$adcomment = ($_POST['adcomment']);
		$mbgb = ($_POST['mbgb']);
		if ($timelimit == "") {
			$timelimit = "0";
		} else {
			$timelimit = $timelimit;
		}
		if ($datalimit == "") {
			$datalimit = "0";
		} else {
			$datalimit = $datalimit * $mbgb;
		}
		if ($adcomment == "") {
			$adcomment = "";
		} else {
			$adcomment = $adcomment;
		}
		$getprofile = $API->comm("/ip/hotspot/user/profile/print", array("?name" => "$profile"));
		$ponlogin = $getprofile[0]['on-login'];
		$getvalid = explode(",", $ponlogin)[3];
		$getprice = explode(",", $ponlogin)[2];
		$getsprice = explode(",", $ponlogin)[4];
		$getlock = explode(",", $ponlogin)[6];

		$source = '#' . $name . '#' . $server . '#' . $user . '#' . $userl . '#' . $prefix . '#' . $char . '#' . $profile . '#' . $timelimit . '#' . $datalimit . '#' . $adcomment . '#' . $getvalid . '#' . $getprice . '_' . $getsprice . '#' . $getlock;

		if (isset($qpid)) {
			$API->comm("/system/script/set", array(
				".id" => "$qpid",
				"name" => "$sname",
				"source" => "$source",
				"comment" => "QuickPrintMikhmon",
			));
		} else {
			$API->comm("/system/script/add", array(
				"name" => "$sname",
				"source" => "$source",
				"comment" => "QuickPrintMikhmon",
			));
		}

		echo "<script>window.location='./?hotspot=list-quick-print&session=" . $session . "'</script>";
	}

	// Ambil daftar semua paket Quick Print
	$getquickprintList = $API->comm("/system/script/print", array("?comment" => "QuickPrintMikhmon"));
	$TotalReg = count($getquickprintList);

	$cancelUrl = isset($qpid)
		? "./?hotspot=list-quick-print&session=" . $session
		: "./?hotspot=quick-print&session=" . $session;
}
?>

<style>
	#mhm-listqp-grid {
		display: grid;
		grid-template-columns: 390px minmax(0, 1fr);
		gap: 18px;
		align-items: start;
		color: #18181b;
	}
	@media (max-width: 1120px) {
		#mhm-listqp-grid {
			grid-template-columns: minmax(0, 1fr);
		}
	}

	.lx-card {
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 12px;
		box-shadow: 0 1px 2px rgba(9, 9, 11, 0.03), 0 10px 30px -15px rgba(9, 9, 11, 0.07);
		overflow: hidden;
	}

	/* Top Bar */
	.lx-card .lx-topbar {
		padding: 18px 22px;
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
	.lx-card .lx-pill {
		font-size: 11.5px;
		font-weight: 500;
		color: #71717a;
		background: #f4f4f5;
		padding: 2px 8px;
		border-radius: 6px;
		font-variant-numeric: tabular-nums;
	}
	.lx-card .lx-pill.is-edit {
		background: #fffbeb;
		border: 1px solid #fde68a;
		color: #b45309;
	}
	.lx-card #loader {
		font-size: 12px;
		color: #71717a;
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
	.lx-card .lx-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b !important;
	}
	.lx-card .lx-btn:active {
		background: #f4f4f5;
	}
	.lx-card .lx-btn i {
		font-size: 11.5px;
		color: #71717a;
	}
	.lx-card .lx-btn.is-primary {
		background: #18181b;
		border-color: #18181b;
		color: #ffffff !important;
	}
	.lx-card .lx-btn.is-primary i {
		color: #d4d4d8;
	}
	.lx-card .lx-btn.is-primary:hover {
		background: #27272a;
		border-color: #27272a;
	}

	/* Form Body */
	.lx-card .lx-form-body {
		padding: 20px 22px;
		display: flex;
		flex-direction: column;
		gap: 13px;
		background: #ffffff;
	}
	.lx-card .lx-field {
		display: grid;
		grid-template-columns: 122px minmax(0, 1fr);
		align-items: center;
		gap: 12px;
	}
	@media (max-width: 576px) {
		.lx-card .lx-field {
			grid-template-columns: minmax(0, 1fr);
			gap: 5px;
		}
	}
	.lx-card .lx-label {
		font-size: 12.5px;
		font-weight: 500;
		color: #3f3f46;
		margin: 0;
	}

	/* Inputs & Selects */
	.lx-card .lx-input,
	.lx-card .lx-select {
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
		transition: all 0.15s ease !important;
	}
	.lx-card .lx-input::placeholder {
		color: #a1a1aa !important;
	}
	.lx-card .lx-input:hover,
	.lx-card .lx-select:hover {
		border-color: #d4d4d8 !important;
		background: #ffffff !important;
	}
	.lx-card .lx-input:focus,
	.lx-card .lx-select:focus {
		background: #ffffff !important;
		border-color: #18181b !important;
		box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.06) !important;
		outline: none !important;
	}
	.lx-card .lx-mono {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
		font-size: 12px !important;
	}

	/* Addon Group (Data Limit MB/GB) */
	.lx-card .lx-addon-group {
		display: flex;
		align-items: stretch;
		width: 100%;
	}
	.lx-card .lx-addon-group .lx-input {
		border-top-right-radius: 0 !important;
		border-bottom-right-radius: 0 !important;
		border-right: none !important;
		flex: 1;
	}
	.lx-card .lx-addon-select {
		width: 78px !important;
		height: 36px !important;
		padding: 0 9px !important;
		background-color: #f4f4f5 !important;
		border: 1px solid #e4e4e7 !important;
		border-top-left-radius: 0 !important;
		border-bottom-left-radius: 0 !important;
		border-top-right-radius: 8px !important;
		border-bottom-right-radius: 8px !important;
		font-size: 12px !important;
		font-weight: 600 !important;
		color: #18181b !important;
		cursor: pointer;
		outline: none !important;
		margin: 0 !important;
		transition: all 0.15s ease;
	}
	.lx-card .lx-addon-select:hover {
		background-color: #e4e4e7 !important;
	}

	/* Dynamic Profile Price/Validity Strip */
	.lx-card #GetValidPrice {
		padding: 9px 12px;
		background: #fafafa;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		font-size: 12px;
		font-weight: 500;
		color: #18181b;
	}
	.lx-card #GetValidPrice:empty {
		display: none;
	}

	/* Bottom Form Action Bar */
	.lx-card .lx-form-bottom {
		margin-top: 4px;
		padding-top: 14px;
		border-top: 1px solid #f4f4f5;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
	}

	/* Table */
	.lx-card .lx-table-wrap {
		width: 100%;
		overflow-x: auto;
		overflow-y: auto;
		max-height: 74vh;
	}
	.lx-card .lx-table {
		width: 100% !important;
		border-collapse: collapse !important;
		margin: 0 !important;
		white-space: nowrap;
		text-align: left;
		background: #ffffff !important;
	}
	.lx-card .lx-table thead th {
		position: sticky;
		top: 0;
		z-index: 5;
		background: #ffffff !important;
		color: #71717a !important;
		font-size: 11px !important;
		font-weight: 500 !important;
		text-transform: uppercase !important;
		letter-spacing: 0.06em !important;
		padding: 12px 16px !important;
		border-top: none !important;
		border-bottom: 1px solid #e4e4e7 !important;
	}
	.lx-card .lx-table tbody td {
		padding: 12px 16px !important;
		border-top: none !important;
		border-bottom: 1px solid #f4f4f5 !important;
		font-size: 12.5px !important;
		color: #27272a !important;
		vertical-align: middle !important;
	}
	.lx-card .lx-table tbody tr:last-child td {
		border-bottom: none !important;
	}
	.lx-card .lx-table tbody tr:hover td {
		background-color: #fafafa !important;
	}
	.lx-card .lx-table tbody tr.is-editing td {
		background-color: #fffbeb !important;
	}

	/* Table Cell Elements */
	.lx-action-group {
		display: inline-flex;
		align-items: center;
		gap: 5px;
	}
	.lx-icon-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 27px;
		height: 27px;
		border-radius: 6px;
		border: 1px solid #e4e4e7;
		background: #ffffff;
		color: #52525b !important;
		font-size: 11.5px;
		cursor: pointer;
		text-decoration: none !important;
		transition: all 0.15s ease;
	}
	.lx-icon-btn:hover {
		background: #fafafa;
		border-color: #d4d4d8;
		color: #09090b !important;
	}
	.lx-icon-btn.is-danger:hover {
		background: #fef2f2;
		border-color: #fecaca;
		color: #dc2626 !important;
	}

	.lx-pkg-link {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-weight: 600;
		color: #09090b !important;
		text-decoration: none;
		letter-spacing: -0.01em;
	}
	.lx-pkg-link i {
		font-size: 11px;
		color: #71717a;
	}
	.lx-pkg-link:hover {
		text-decoration: underline;
	}
	.lx-badge {
		display: inline-block;
		font-size: 11px;
		font-weight: 500;
		color: #18181b;
		background: #f4f4f5;
		padding: 2px 7px;
		border-radius: 5px;
	}
	.lx-cell-mono {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12px;
		color: #3f3f46;
		font-variant-numeric: tabular-nums;
	}
	.lx-empty-cell {
		color: #d4d4d8;
	}

	/* Empty Table State */
	.lx-empty-table {
		padding: 48px 20px !important;
		text-align: center !important;
		color: #71717a !important;
	}
	.lx-empty-table-icon {
		width: 40px;
		height: 40px;
		border-radius: 9px;
		background: #f4f4f5;
		color: #71717a;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 16px;
		margin-bottom: 8px;
	}
	.lx-empty-table-title {
		font-size: 13.5px;
		font-weight: 600;
		color: #09090b;
		margin-bottom: 3px;
	}
	.lx-empty-table-desc {
		font-size: 12px;
		color: #71717a;
	}

	/* Footer */
	.lx-card .lx-footer {
		padding: 12px 22px;
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

<div id="mhm-listqp-grid">
	<!-- Left Card: Add / Edit Quick Print Form -->
	<div class="lx-card">
		<form autocomplete="off" method="post" action="" style="margin:0;">
			<!-- Top Bar -->
			<div class="lx-topbar">
				<div class="lx-title-group">
					<h3 class="lx-title">
						<?php if (isset($qpid)) { echo $_edit; } else { echo $_add; } echo ' ' . $_quick_print; ?>
					</h3>
					<?php if (isset($qpid)): ?>
						<span class="lx-pill is-edit">Mode Edit</span>
					<?php else: ?>
						<span class="lx-pill">Template Baru</span>
					<?php endif; ?>
					<small id="loader" style="display: none;">
						<i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?>
					</small>
				</div>

				<div class="lx-actions">
					<a class="lx-btn" href="<?= $cancelUrl; ?>">
						<i class="fa fa-close"></i>
						<span><?= isset($qpid) ? $_cancel : $_close; ?></span>
					</a>
					<button type="submit" name="save" onclick="loader()" class="lx-btn is-primary" title="Save Quick Print">
						<i class="fa fa-save"></i>
						<span><?= $_save ?></span>
					</button>
				</div>
			</div>

			<!-- Form Body -->
			<div class="lx-form-body">
				<div class="lx-field">
					<label class="lx-label"><?= $_name ?></label>
					<input
						class="form-control lx-input"
						type="text"
						name="name"
						placeholder="Contoh: Paket 1 Jam"
						value="<?= htmlspecialchars($package); ?>"
						required="1"
					>
				</div>

				<div class="lx-field">
					<label class="lx-label">Server</label>
					<select class="form-control lx-select" name="server" required="1">
						<?php
						if (isset($qpid) && $server != "") {
							echo '<option>' . htmlspecialchars($server) . '</option>';
							if ($server != "all") {
								echo '<option>all</option>';
							}
						} else {
							echo '<option>all</option>';
						}
						$TotalSrv = count($srvlist);
						for ($i = 0; $i < $TotalSrv; $i++) {
							echo "<option>" . htmlspecialchars($srvlist[$i]['name']) . "</option>";
						}
						?>
					</select>
				</div>

				<div class="lx-field">
					<label class="lx-label" for="user"><?= $_user_mode ?></label>
					<select class="form-control lx-select" onchange="defUserl();" id="user" name="user" required="1">
						<?php if (isset($qpid)) { echo '<option value="' . htmlspecialchars($usermode) . '">' . htmlspecialchars($tusermode) . '</option>'; } ?>
						<option value="up"><?= $_user_pass ?></option>
						<option value="vc"><?= $_user_user ?></option>
					</select>
				</div>

				<div class="lx-field">
					<label class="lx-label" for="userl"><?= $_user_length ?></label>
					<select class="form-control lx-select lx-mono" id="userl" name="userl" required="1">
						<?php if (isset($qpid)) { echo '<option>' . htmlspecialchars($userlength) . '</option>'; } ?>
						<option>4</option>
						<option>3</option>
						<option>4</option>
						<option>5</option>
						<option>6</option>
						<option>7</option>
						<option>8</option>
					</select>
				</div>

				<div class="lx-field">
					<label class="lx-label"><?= $_prefix ?></label>
					<input
						class="form-control lx-input lx-mono"
						type="text"
						size="4"
						maxlength="4"
						autocomplete="off"
						name="prefix"
						placeholder="Opsional (maks. 4)"
						value="<?= htmlspecialchars($prefix); ?>"
					>
				</div>

				<div class="lx-field">
					<label class="lx-label"><?= $_character ?></label>
					<select class="form-control lx-select" name="char" required="1">
						<?php if (isset($qpid)) { echo '<option value="' . htmlspecialchars($char) . '">' . $_random . ' ' . htmlspecialchars($tchar) . '</option>'; } ?>
						<option id="lower" style="display:block;" value="lower"><?= $_random ?> abcd</option>
						<option id="upper" style="display:block;" value="upper"><?= $_random ?> ABCD</option>
						<option id="upplow" style="display:block;" value="upplow"><?= $_random ?> aBcD</option>
						<option id="lower1" style="display:none;" value="lower"><?= $_random ?> abcd2345</option>
						<option id="upper1" style="display:none;" value="upper"><?= $_random ?> ABCD2345</option>
						<option id="upplow1" style="display:none;" value="upplow"><?= $_random ?> aBcD2345</option>
						<option id="mix" style="display:block;" value="mix"><?= $_random ?> 5ab2c34d</option>
						<option id="mix1" style="display:block;" value="mix1"><?= $_random ?> 5AB2C34D</option>
						<option id="mix2" style="display:block;" value="mix2"><?= $_random ?> 5aB2c34D</option>
						<option id="num" style="display:none;" value="num"><?= $_random ?> 1234</option>
					</select>
				</div>

				<div class="lx-field">
					<label class="lx-label" for="uprof"><?= $_profile ?></label>
					<select class="form-control lx-select" onchange="GetVP();" id="uprof" name="profile" required="1">
						<?php
						if (isset($qpid)) {
							echo "<option>" . htmlspecialchars($profile) . "</option>";
						}
						$TotalProf = count($getprofile);
						for ($i = 0; $i < $TotalProf; $i++) {
							echo "<option>" . htmlspecialchars($getprofile[$i]['name']) . "</option>";
						}
						?>
					</select>
				</div>

				<!-- Dynamic Profile Validity & Price Output -->
				<div id="GetValidPrice"><?php if ($genprof != "") { echo $ValidPrice; } ?></div>

				<div class="lx-field">
					<label class="lx-label"><?= $_time_limit ?></label>
					<input
						class="form-control lx-input lx-mono"
						type="text"
						size="4"
						autocomplete="off"
						name="timelimit"
						placeholder="Contoh: 1h, 12h, 1d"
						value="<?= htmlspecialchars($timelimit); ?>"
					>
				</div>

				<div class="lx-field">
					<label class="lx-label"><?= $_data_limit ?></label>
					<div class="lx-addon-group">
						<input
							class="form-control lx-input lx-mono"
							type="number"
							min="0"
							max="9999"
							name="datalimit"
							placeholder="0 (Unlimited)"
							value="<?= htmlspecialchars($udatalimit); ?>"
						>
						<select class="lx-addon-select" name="mbgb" required="1">
							<?php if (isset($qpid)) { echo '<option value="' . $xdatalimit . '">' . $MG . '</option>'; } ?>
							<option value="1048576">MB</option>
							<option value="1073741824">GB</option>
						</select>
					</div>
				</div>

				<div class="lx-field">
					<label class="lx-label" for="comment"><?= $_comment ?></label>
					<input
						class="form-control lx-input"
						type="text"
						title="No special characters"
						id="comment"
						autocomplete="off"
						name="adcomment"
						placeholder="Catatan (tanpa karakter khusus)"
						value="<?= htmlspecialchars($comment); ?>"
					>
				</div>

				<div class="lx-form-bottom">
					<a class="lx-btn" href="<?= $cancelUrl; ?>">
						<i class="fa fa-close"></i>
						<span><?= isset($qpid) ? $_cancel : $_close; ?></span>
					</a>
					<button type="submit" name="save" onclick="loader()" class="lx-btn is-primary" title="Save Quick Print">
						<i class="fa fa-save"></i>
						<span><?= $_save ?></span>
					</button>
				</div>
			</div>

			<!-- Footer -->
			<div class="lx-footer">
				<div>Disimpan ke <strong style="color:#18181b;">/system/script</strong> MikroTik</div>
				<div>Tag: <strong style="color:#18181b;">QuickPrintMikhmon</strong></div>
			</div>
		</form>
	</div>

	<!-- Right Card: Package Quick Print Table -->
	<div class="lx-card">
		<div class="lx-topbar">
			<div class="lx-title-group">
				<h3 class="lx-title"><?= $_package . ' ' . $_quick_print ?></h3>
				<span class="lx-pill">
					<?= $TotalReg; ?> <?= ($TotalReg > 1) ? 'packages' : 'package'; ?>
				</span>
			</div>

			<div class="lx-actions">
				<a class="lx-btn" href="./?hotspot=quick-print&session=<?= $session; ?>" title="Open Quick Print">
					<i class="fa fa-print"></i>
					<span><?= $_quick_print ?></span>
				</a>
				<button type="button" onclick="location.reload();" class="lx-btn" title="Reload data">
					<i class="fa fa-refresh"></i>
					<span>Refresh</span>
				</button>
			</div>
		</div>

		<div class="lx-table-wrap">
			<table class="lx-table">
				<thead>
					<tr>
						<th style="text-align:center; width:76px;">Aksi</th>
						<th><?= $_package ?></th>
						<th>Server</th>
						<th><?= $_user_mode ?></th>
						<th style="text-align:center;"><?= $_user_length ?></th>
						<th><?= $_prefix ?></th>
						<th><?= $_profile ?></th>
						<th><?= $_time_limit ?></th>
						<th><?= $_data_limit ?></th>
						<th><?= $_validity ?></th>
						<th style="text-align:right;"><?= $_price ?></th>
						<th style="text-align:right;"><?= $_selling_price ?></th>
						<th><?= $_lock_user ?></th>
						<th><?= $_comment ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ($TotalReg > 0) {
						for ($i = 0; $i < $TotalReg; $i++) {
							$quickprintdetails = $getquickprintList[$i];
							$rowQpid = $quickprintdetails['.id'];
							$quickprintsource = explode("#", $quickprintdetails['source']);
							$rowPackage = $quickprintsource[1];
							$rowServer = $quickprintsource[2];
							$rowUsermode = $quickprintsource[3];
							$rowUserlength = $quickprintsource[4];
							$rowPrefix = $quickprintsource[5];
							$rowChar = $quickprintsource[6];
							$rowProfile = $quickprintsource[7];
							$rowTimelimit = $quickprintsource[8];
							$rowDatalimit = $quickprintsource[9];
							$rowComment = $quickprintsource[10];
							$rowValidity = $quickprintsource[11];
							$getprice = explode("_", $quickprintsource[12])[0];
							$getsprice = explode("_", $quickprintsource[12])[1];
							$rowUserlock = $quickprintsource[13];

							if ($currency == in_array($currency, $cekindo['indo'])) {
								$price = $currency . " " . number_format((float)$getprice, 0, ",", ".");
								$sprice = $currency . " " . number_format((float)$getsprice, 0, ",", ".");
							} else {
								$price = $currency . " " . number_format((float)$getprice);
								$sprice = $currency . " " . number_format((float)$getsprice);
							}

							$isEditingRow = (isset($qpid) && $qpid == $rowQpid);
							?>
							<tr class="<?= $isEditingRow ? 'is-editing' : ''; ?>">
								<td style="text-align:center;">
									<div class="lx-action-group">
										<span
											class="lx-icon-btn is-danger pointer"
											onclick="if(confirm('Are you sure to delete (<?= htmlspecialchars($rowPackage, ENT_QUOTES); ?>)?')){loadpage('./?hotspot=list-quick-print&remove&qpid=<?= $rowQpid; ?>&session=<?= $session; ?>')}else{}"
											title="Remove <?= htmlspecialchars($rowPackage, ENT_QUOTES); ?>"
										>
											<i class="fa fa-trash-o"></i>
										</span>
										<a
											class="lx-icon-btn"
											title="Edit <?= htmlspecialchars($_package . ' ' . $rowPackage, ENT_QUOTES); ?>"
											href="./?hotspot=list-quick-print&qpid=<?= $rowQpid; ?>&session=<?= $session; ?>"
										>
											<i class="fa fa-edit"></i>
										</a>
									</div>
								</td>
								<td>
									<a class="lx-pkg-link" title="Edit <?= htmlspecialchars($_package . ' ' . $rowPackage, ENT_QUOTES); ?>" href="./?hotspot=list-quick-print&qpid=<?= $rowQpid; ?>&session=<?= $session; ?>">
										<span><?= htmlspecialchars($rowPackage); ?></span>
									</a>
								</td>
								<td style="color:#52525b; font-weight:500;"><?= htmlspecialchars($rowServer); ?></td>
								<td><span class="lx-badge"><?= htmlspecialchars($rowUsermode); ?></span></td>
								<td style="text-align:center;"><span class="lx-cell-mono"><?= htmlspecialchars($rowUserlength); ?></span></td>
								<td><?= ($rowPrefix != "") ? "<span class='lx-cell-mono'>" . htmlspecialchars($rowPrefix) . "</span>" : "<span class='lx-empty-cell'>&mdash;</span>"; ?></td>
								<td><span class="lx-badge"><?= htmlspecialchars($rowProfile); ?></span></td>
								<td><?= ($rowTimelimit != "" && $rowTimelimit != "0") ? "<span class='lx-cell-mono'>" . htmlspecialchars($rowTimelimit) . "</span>" : "<span class='lx-empty-cell'>&mdash;</span>"; ?></td>
								<td><span class="lx-cell-mono"><?= ($rowDatalimit != "" && $rowDatalimit != "0") ? formatBytes($rowDatalimit, 2) : "&mdash;"; ?></span></td>
								<td><?= ($rowValidity != "") ? "<span class='lx-cell-mono'>" . htmlspecialchars($rowValidity) . "</span>" : "<span class='lx-empty-cell'>&mdash;</span>"; ?></td>
								<td style="text-align:right;"><span class="lx-cell-mono"><?= htmlspecialchars($price); ?></span></td>
								<td style="text-align:right;"><span class="lx-cell-mono" style="font-weight:600; color:#09090b;"><?= htmlspecialchars($sprice); ?></span></td>
								<td><?= ($rowUserlock != "") ? "<span class='lx-badge'>" . htmlspecialchars($rowUserlock) . "</span>" : "<span class='lx-empty-cell'>&mdash;</span>"; ?></td>
								<td><?= ($rowComment != "") ? "<span style='color:#52525b;'>" . htmlspecialchars($rowComment) . "</span>" : "<span class='lx-empty-cell'>&mdash;</span>"; ?></td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td colspan="14" class="lx-empty-table">
								<div class="lx-empty-table-icon">
									<i class="fa fa-ticket"></i>
								</div>
								<div class="lx-empty-table-title">Belum ada paket Quick Print tersimpan</div>
								<div class="lx-empty-table-desc">Isi formulir di sebelah kiri lalu klik Save untuk menambahkan template paket pertama.</div>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>

		<!-- Footer -->
		<div class="lx-footer">
			<div>Klik nama paket atau ikon edit untuk mengubah konfigurasi template</div>
			<div>Geser tabel ke samping untuk melihat seluruh parameter paket</div>
		</div>
	</div>
</div>

<script>
// get valid $ price
function GetVP(){
  var prof = document.getElementById('uprof').value;
  var url = "./process/getvalidprice.php?name=";
  var session = "&session=<?= $session; ?>"
  var getvalidprice = url+prof+session
  $("#GetValidPrice").load(getvalidprice);
}
</script>