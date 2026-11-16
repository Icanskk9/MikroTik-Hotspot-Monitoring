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
// time zone
date_default_timezone_set($_SESSION['timezone']);

	$genprof = $_GET['genprof'];
	if ($genprof != "") {
		$getprofile = $API->comm("/ip/hotspot/user/profile/print", array(
			"?name" => "$genprof",
		));
		$ponlogin = $getprofile[0]['on-login'];
		$getprice = explode(",", $ponlogin)[2];
		if ($getprice == "0") {
			$getprice = "";
		} else {
			$getprice = $getprice;
		}

		$getvalid = explode(",", $ponlogin)[3];

		$getlocku = explode(",", $ponlogin)[6];
		if ($getlocku == "") {
			$getprice = "Disable";
		} else {
			$getlocku = $getlocku;
		}

		if ($currency == in_array($currency, $cekindo['indo'])) {
			$getprice = $currency . " " . number_format((float)$getprice, 0, ",", ".");
		} else {
			$getprice = $currency . " " . number_format((float)$getprice);
		}
		$ValidPrice = "<b>Validity : " . $getvalid . " | Price : " . $getprice . " | Lock User : " . $getlocku . "</b>";
	} else {
	}

	$srvlist = $API->comm("/ip/hotspot/print");

	if (isset($_POST['qty'])) {
		
		$qty = ($_POST['qty']);
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
		$_SESSION['ubp'] = $profile;
		$commt = $user . "-" . rand(100, 999) . "-" . date("m.d.y") . "-" . $adcomment;
		$gentemp = $commt . "|~" . $profile . "~" . $getvalid . "~" . $getprice . "!".$getsprice."~" . $timelimit . "~" . $datalimit . "~" . $getlock;
		$gen = '<?php $genu="'.encrypt($gentemp).'";?>';
		$temp = './voucher/temp.php';
		$handle = fopen($temp, 'w') or die('Cannot open file:  ' . $temp);
		$data = $gen;
		fwrite($handle, $data);

		$a = array("1" => "", "", 1, 2, 2, 3, 3, 4);

		if ($user == "up") {
			for ($i = 1; $i <= $qty; $i++) {
				if ($char == "lower") {
					$u[$i] = randLC($userl);
				} elseif ($char == "upper") {
					$u[$i] = randUC($userl);
				} elseif ($char == "upplow") {
					$u[$i] = randULC($userl);
				} elseif ($char == "mix") {
					$u[$i] = randNLC($userl);
				} elseif ($char == "mix1") {
					$u[$i] = randNUC($userl);
				} elseif ($char == "mix2") {
					$u[$i] = randNULC($userl);
				}
				if ($userl == 3) {
					$p[$i] = randN(3);
				} elseif ($userl == 4) {
					$p[$i] = randN(4);
				} elseif ($userl == 5) {
					$p[$i] = randN(5);
				} elseif ($userl == 6) {
					$p[$i] = randN(6);
				} elseif ($userl == 7) {
					$p[$i] = randN(7);
				} elseif ($userl == 8) {
					$p[$i] = randN(8);
				}

				$u[$i] = "$prefix$u[$i]";
			}

			for ($i = 1; $i <= $qty; $i++) {
				$API->comm("/ip/hotspot/user/add", array(
					"server" => "$server",
					"name" => "$u[$i]",
					"password" => "$p[$i]",
					"profile" => "$profile",
					"limit-uptime" => "$timelimit",
					"limit-bytes-total" => "$datalimit",
					"comment" => "$commt",
				));
			}
		}

		if ($user == "vc") {
			$shuf = ($userl - $a[$userl]);
			for ($i = 1; $i <= $qty; $i++) {
				if ($char == "lower") {
					$u[$i] = randLC($shuf);
				} elseif ($char == "upper") {
					$u[$i] = randUC($shuf);
				} elseif ($char == "upplow") {
					$u[$i] = randULC($shuf);
				}
				if ($userl == 3) {
					$p[$i] = randN(1);
				} elseif ($userl == 4 || $userl == 5) {
					$p[$i] = randN(2);
				} elseif ($userl == 6 || $userl == 7) {
					$p[$i] = randN(3);
				} elseif ($userl == 8) {
					$p[$i] = randN(4);
				}

				$u[$i] = "$prefix$u[$i]$p[$i]";

				if ($char == "num") {
					if ($userl == 3) {
						$p[$i] = randN(3);
					} elseif ($userl == 4) {
						$p[$i] = randN(4);
					} elseif ($userl == 5) {
						$p[$i] = randN(5);
					} elseif ($userl == 6) {
						$p[$i] = randN(6);
					} elseif ($userl == 7) {
						$p[$i] = randN(7);
					} elseif ($userl == 8) {
						$p[$i] = randN(8);
					}

					$u[$i] = "$prefix$p[$i]";
				}
				if ($char == "mix") {
					$p[$i] = randNLC($userl);


					$u[$i] = "$prefix$p[$i]";
				}
				if ($char == "mix1") {
					$p[$i] = randNUC($userl);


					$u[$i] = "$prefix$p[$i]";
				}
				if ($char == "mix2") {
					$p[$i] = randNULC($userl);


					$u[$i] = "$prefix$p[$i]";
				}

			}
			for ($i = 1; $i <= $qty; $i++) {
				$API->comm("/ip/hotspot/user/add", array(
					"server" => "$server",
					"name" => "$u[$i]",
					"password" => "$u[$i]",
					"profile" => "$profile",
					"limit-uptime" => "$timelimit",
					"limit-bytes-total" => "$datalimit",
					"comment" => "$commt",
				));
			}
		}


		if ($qty < 2) {
			echo "<script>window.location='./?hotspot-user=" . $u[1] . "&session=" . $session . "'</script>";
		} else {
			echo "<script>window.location='./?hotspot-user=generate&session=" . $session . "'</script>";
		}
	}

	$getprofile = $API->comm("/ip/hotspot/user/profile/print");
	include_once('./voucher/temp.php');
	$genuser = explode("-", decrypt($genu));
	$genuser1 = explode("~", decrypt($genu));
	$umode = $genuser[0];
	$ucode = $genuser[1];
	$udate = $genuser[2];
	$uprofile = $genuser1[1];
	$uvalid = $genuser1[2];
	$ucommt = $genuser[3];
	if ($uvalid == "") {
		$uvalid = "-";
	} else {
		$uvalid = $uvalid;
	}
	$uprice = explode("!",$genuser1[3])[0];
	if ($uprice == "0") {
		$uprice = "-";
	} else {
		$uprice = $uprice;
	}
	$suprice = explode("!",$genuser1[3])[1];
	if ($suprice == "0") {
		$suprice = "-";
	} else {
		$suprice = $suprice;
	}
	$utlimit = $genuser1[4];
	if ($utlimit == "0") {
		$utlimit = "-";
	} else {
		$utlimit = $utlimit;
	}
	$udlimit = $genuser1[5];
	if ($udlimit == "0") {
		$udlimit = "-";
	} else {
		$udlimit = formatBytes($udlimit, 2);
	}
	$ulock = $genuser1[6];
	//$urlprint = "$umode-$ucode-$udate-$ucommt";
	$urlprint = explode("|", decrypt($genu))[0];
	if ($currency == in_array($currency, $cekindo['indo'])) {
		$uprice = $currency . " " . number_format((float)$uprice, 0, ",", ".");
		$suprice = $currency . " " . number_format((float)$suprice, 0, ",", ".");
	} else {
		$uprice = $currency . " " . number_format((float)$uprice);
		$suprice = $currency . " " . number_format((float)$suprice);
	}

	if ($_SESSION['ubp'] != "") {
		$closeHref = "./?hotspot=users&profile=" . $_SESSION['ubp'] . "&session=" . $session;
	} elseif ($_SESSION['vcr'] = "active") {
		$closeHref = "./?hotspot=users-by-profile&session=" . $session;
	} else {
		$closeHref = "./?hotspot=users&profile=all&session=" . $session;
	}

	$userListProf = ($_SESSION['ubp'] == "") ? "all" : $uprofile;
}
?>

<style>
	#mhm-genuser-grid {
		display: grid;
		grid-template-columns: minmax(0, 1fr) 360px;
		gap: 18px;
		align-items: start;
		color: #18181b;
	}
	@media (max-width: 992px) {
		#mhm-genuser-grid {
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
		padding: 18px 24px;
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
	.lx-card #loader {
		font-size: 12px;
		color: #71717a;
	}

	/* Sub-toolbar (Quick Print Last Batch) */
	.lx-card .lx-subbar {
		padding: 11px 24px;
		background: #fafafa;
		border-bottom: 1px solid #e4e4e7;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 10px;
	}
	.lx-card .lx-subbar-label {
		font-size: 12px;
		color: #52525b;
		display: flex;
		align-items: center;
		gap: 7px;
	}
	.lx-card .lx-code-badge {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 11.5px;
		font-weight: 600;
		color: #09090b;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		padding: 1px 7px;
		border-radius: 5px;
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
	.lx-card .lx-btn.is-sm {
		height: 29px;
		padding: 0 11px;
		font-size: 11.5px;
	}

	/* Form Body */
	.lx-card .lx-form-body {
		padding: 22px 24px;
		display: flex;
		flex-direction: column;
		gap: 14px;
		background: #ffffff;
	}
	.lx-card .lx-field {
		display: grid;
		grid-template-columns: 165px minmax(0, 1fr);
		align-items: center;
		gap: 14px;
	}
	@media (max-width: 576px) {
		.lx-card .lx-field {
			grid-template-columns: minmax(0, 1fr);
			gap: 6px;
		}
	}
	.lx-card .lx-label {
		font-size: 13px;
		font-weight: 500;
		color: #3f3f46;
		margin: 0;
	}

	/* Inputs & Selects */
	.lx-card .lx-input,
	.lx-card .lx-select {
		width: 100% !important;
		height: 38px !important;
		padding: 0 12px !important;
		background: #fafafa !important;
		border: 1px solid #e4e4e7 !important;
		border-radius: 8px !important;
		font-size: 13px !important;
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
		font-size: 12.5px !important;
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
		width: 86px !important;
		height: 38px !important;
		padding: 0 10px !important;
		background-color: #f4f4f5 !important;
		border: 1px solid #e4e4e7 !important;
		border-top-left-radius: 0 !important;
		border-bottom-left-radius: 0 !important;
		border-top-right-radius: 8px !important;
		border-bottom-right-radius: 8px !important;
		font-size: 12.5px !important;
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
		margin-left: 179px;
		padding: 9px 13px;
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
	@media (max-width: 576px) {
		.lx-card #GetValidPrice {
			margin-left: 0;
		}
	}

	/* Bottom Form Action Bar */
	.lx-card .lx-form-bottom {
		margin-top: 4px;
		padding-top: 16px;
		border-top: 1px solid #f4f4f5;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 8px;
	}

	/* Right Sidebar: Last Generate Summary */
	.lx-summary-list {
		display: flex;
		flex-direction: column;
		background: #ffffff;
	}
	.lx-summary-row {
		padding: 10px 20px;
		border-bottom: 1px solid #f4f4f5;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		font-size: 12.5px;
	}
	.lx-summary-row:last-child {
		border-bottom: none;
	}
	.lx-summary-key {
		color: #71717a;
		font-weight: 500;
	}
	.lx-summary-val {
		color: #09090b;
		font-weight: 600;
		text-align: right;
		font-variant-numeric: tabular-nums;
	}
	.lx-summary-val.is-mono {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		font-size: 12px;
	}

	/* Sidebar Guide Box */
	.lx-guide-wrap {
		padding: 14px 20px 18px;
		background: #fafafa;
		border-top: 1px solid #e4e4e7;
		display: flex;
		flex-direction: column;
		gap: 10px;
	}
	.lx-guide-box {
		padding: 11px 13px;
		background: #ffffff;
		border: 1px solid #e4e4e7;
		border-radius: 8px;
		font-size: 12px;
		line-height: 1.6;
		color: #3f3f46;
	}
	.lx-guide-box p {
		margin: 0;
	}
	.lx-guide-label {
		font-size: 10px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #71717a;
		margin-bottom: 4px;
	}

	/* Footer */
	.lx-card .lx-footer {
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

<div id="mhm-genuser-grid">
	<!-- Left Card: Generate User Form -->
	<div class="lx-card">
		<form autocomplete="off" method="post" action="" style="margin:0;">
			<!-- Top Bar -->
			<div class="lx-topbar">
				<div class="lx-title-group">
					<h3 class="lx-title"><?= $_generate_user ?></h3>
					<span class="lx-pill">Batch Generator</span>
					<small id="loader" style="display: none;">
						<i class="fa fa-circle-o-notch fa-spin"></i> <?= $_processing ?>
					</small>
				</div>

				<div class="lx-actions">
					<a class="lx-btn" href="<?= $closeHref; ?>">
						<i class="fa fa-close"></i>
						<span><?= $_close ?></span>
					</a>
					<a class="lx-btn" title="Open User List by Profile <?= htmlspecialchars($userListProf, ENT_QUOTES); ?>" href="./?hotspot=users&profile=<?= $userListProf; ?>&session=<?= $session; ?>">
						<i class="fa fa-users"></i>
						<span><?= $_user_list ?></span>
					</a>
					<button type="submit" name="save" onclick="loader()" class="lx-btn is-primary" title="Generate User">
						<i class="fa fa-save"></i>
						<span><?= $_generate ?></span>
					</button>
				</div>
			</div>

			<!-- Quick Print Bar for Last Generated Batch -->
			<div class="lx-subbar">
				<div class="lx-subbar-label">
					<span>Cetak Batch Terakhir:</span>
					<span class="lx-code-badge">#<?= htmlspecialchars($ucode != "" ? $ucode : "-"); ?></span>
				</div>
				<div class="lx-actions">
					<a class="lx-btn is-sm" title="Print Default" href="./voucher/print.php?id=<?= $urlprint; ?>&qr=no&session=<?= $session; ?>" target="_blank">
						<i class="fa fa-print"></i>
						<span><?= $_print ?></span>
					</a>
					<a class="lx-btn is-sm" title="Print QR" href="./voucher/print.php?id=<?= $urlprint; ?>&qr=yes&session=<?= $session; ?>" target="_blank">
						<i class="fa fa-qrcode"></i>
						<span><?= $_print_qr ?></span>
					</a>
					<a class="lx-btn is-sm" title="Print Small" href="./voucher/print.php?id=<?= $urlprint; ?>&small=yes&session=<?= $session; ?>" target="_blank">
						<i class="fa fa-print"></i>
						<span><?= $_print_small ?></span>
					</a>
				</div>
			</div>

			<!-- Form Fields -->
			<div class="lx-form-body">
				<div class="lx-field">
					<label class="lx-label"><?= $_qty ?></label>
					<input
						class="form-control lx-input lx-mono"
						type="number"
						name="qty"
						min="1"
						max="500"
						value="1"
						required="1"
					>
				</div>

				<div class="lx-field">
					<label class="lx-label">Server</label>
					<select class="form-control lx-select" name="server" required="1">
						<option>all</option>
						<?php
						$TotalReg = count($srvlist);
						for ($i = 0; $i < $TotalReg; $i++) {
							echo "<option>" . htmlspecialchars($srvlist[$i]['name']) . "</option>";
						}
						?>
					</select>
				</div>

				<div class="lx-field">
					<label class="lx-label" for="user"><?= $_user_mode ?></label>
					<select class="form-control lx-select" onchange="defUserl();" id="user" name="user" required="1">
						<option value="up"><?= $_user_pass ?></option>
						<option value="vc"><?= $_user_user ?></option>
					</select>
				</div>

				<div class="lx-field">
					<label class="lx-label" for="userl"><?= $_user_length ?></label>
					<select class="form-control lx-select lx-mono" id="userl" name="userl" required="1">
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
						size="6"
						maxlength="6"
						autocomplete="off"
						name="prefix"
						placeholder="Opsional (maks. 6 karakter)"
						value=""
					>
				</div>

				<div class="lx-field">
					<label class="lx-label"><?= $_character ?></label>
					<select class="form-control lx-select" name="char" required="1">
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
						if ($genprof != "") {
							echo "<option>" . htmlspecialchars($genprof) . "</option>";
						}
						$TotalReg = count($getprofile);
						for ($i = 0; $i < $TotalReg; $i++) {
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
						placeholder="Contoh: 1h, 12h, 1d, 30d"
						value=""
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
							value="<?= $udatalimit; ?>"
						>
						<select class="lx-addon-select" name="mbgb" required="1">
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
						placeholder="Catatan tambahan batch (tanpa karakter khusus)"
						value=""
					>
				</div>

				<div class="lx-form-bottom">
					<a class="lx-btn" href="<?= $closeHref; ?>">
						<i class="fa fa-close"></i>
						<span><?= $_close ?></span>
					</a>
					<button type="submit" name="save" onclick="loader()" class="lx-btn is-primary" title="Generate User">
						<i class="fa fa-save"></i>
						<span><?= $_generate ?></span>
					</button>
				</div>
			</div>

			<!-- Footer -->
			<div class="lx-footer">
				<div>Maksimal pembuatan sekaligus <strong style="color:#18181b;">500 akun</strong> per batch</div>
				<div>MikroTik Hotspot Generator</div>
			</div>
		</form>
	</div>

	<!-- Right Card: Last Generate & Guide -->
	<div class="lx-card">
		<div class="lx-topbar">
			<div class="lx-title-group">
				<h3 class="lx-title"><?= $_last_generate ?></h3>
			</div>
			<span class="lx-pill">#<?= htmlspecialchars($ucode != "" ? $ucode : "-"); ?></span>
		</div>

		<div class="lx-summary-list">
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_generate_code ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($ucode); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_date ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($udate); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_profile ?></span>
				<span class="lx-summary-val"><?= htmlspecialchars($uprofile); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_validity ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($uvalid); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_time_limit ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($utlimit); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_data_limit ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($udlimit); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_price ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($uprice); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_selling_price ?></span>
				<span class="lx-summary-val is-mono"><?= htmlspecialchars($suprice); ?></span>
			</div>
			<div class="lx-summary-row">
				<span class="lx-summary-key"><?= $_lock_user ?></span>
				<span class="lx-summary-val"><?= htmlspecialchars($ulock); ?></span>
			</div>
		</div>

		<div class="lx-guide-wrap">
			<div class="lx-guide-box">
				<div class="lx-guide-label">Format Time Limit</div>
				<p><?= $_format_time_limit ?></p>
			</div>

			<div class="lx-guide-box">
				<div class="lx-guide-label">Ketentuan Batas Waktu</div>
				<p><?= $_details_add_user ?></p>
			</div>
		</div>
	</div>
</div>

<script>
// get valid $ price
function GetVP(){
  var prof = document.getElementById('uprof').value;
  $("#GetValidPrice").load("./process/getvalidprice.php?name="+prof+"&session=<?= $session; ?> #getdata");
}
</script>