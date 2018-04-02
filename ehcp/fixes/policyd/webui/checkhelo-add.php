<?php
# Module: CheckHelo add
# Copyright (C) 2009-2011, AllWorldIT
# Copyright (C) 2008, LinuxRulz
# 
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

include_once("includes/header.php");
include_once("includes/footer.php");
include_once("includes/db.php");
include_once("includes/tooltips.php");



$db = connect_db();



printHeader(array(
		"Tabs" => array(
			"Back to HELO checks" => "checkhelo-main.php"
		),
));



if ($_POST['frmaction'] == "add") {
?>
	<p class="pageheader">Add HELO/EHLO Check</p>

	<form method="post" action="checkhelo-add.php">
		<div>
			<input type="hidden" name="frmaction" value="add2" />
		</div>
		<table class="entry">
			<tr>
				<td class="entrytitle">Name</td>
				<td><input type="text" name="checkhelo_name" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Link to policy</td>
				<td>
					<select name="checkhelo_policyid">
<?php
						$res = $db->query("SELECT ID, Name FROM ${DB_TABLE_PREFIX}policies ORDER BY Name");
						while ($row = $res->fetchObject()) {
?>
							<option value="<?php echo $row->id ?>"><?php echo $row->name ?></option>
<?php
						}
						$res->closeCursor();
?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="textcenter" style="border-bottom: 1px dashed black;">Blacklisting</td>
			</tr>
			<tr>
				<td class="entrytitle">Use Blacklist</td>
				<td>
					<select name="checkhelo_useblacklist">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Blacklist Period
					<?php tooltip('checkhelo_blacklist_period'); ?>
				</td>
				<td><input type="text" name="checkhelo_blacklistperiod" /></td>
			</tr>
			<tr>
				<td colspan="2" class="textcenter" style="border-bottom: 1px dashed black;">Randomization Prevention</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Use HRP
				</td>
				<td>
					<select name="checkhelo_usehrp">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					HRP Period
					<?php tooltip('checkhelo_blacklist_hrpperiod'); ?>
				</td>
				<td><input type="text" name="checkhelo_hrpperiod" /></td>
			</tr>
			<tr>
				<td class="entrytitle">
					HRP Limit
					<?php tooltip('checkhelo_blacklist_hrplimit'); ?>
				</td>
				<td><input type="text" name="checkhelo_hrplimit" /></td>
			</tr>
			<tr>
				<td colspan="2" class="textcenter" style="border-bottom: 1px dashed black;">Reject (RFC non-compliance)</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Reject Invalid
					<?php tooltip('checkhelo_rejectinvalid'); ?>
				</td>
				<td>
					<select name="checkhelo_rejectinvalid">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Reject non-literal IP
					<?php tooltip('checkhelo_rejectip'); ?>
				</td>
				<td>
					<select name="checkhelo_rejectip">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Reject Unresolvable
					<?php tooltip('checkhelo_rejectunresolv'); ?>
				</td>
				<td>
					<select name="checkhelo_rejectunresolvable">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="textcenter" style="border-bottom: 1px dashed black;">&nbsp;</td>
			</tr>
			<tr>
				<td class="entrytitle">Comment</td>
				<td><textarea name="checkhelo_comment" cols="40" rows="5"></textarea></td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="submit" />
				</td>
			</tr>
		</table>
	</form>

<?php

# Check we have all params
} elseif ($_POST['frmaction'] == "add2") {
?>
	<p class="pageheader">HELO/EHLO Check Add Results</p>

<?php
	# Check name
	if (empty($_POST['checkhelo_policyid'])) {
?>
		<div class="warning">Policy ID cannot be empty</div>
<?php

	# Check name
	} elseif (empty($_POST['checkhelo_name'])) {
?>
		<div class="warning">Name cannot be empty</div>
<?php


	} else {

		# Sort out using of blacklist
		switch ($_POST['checkhelo_useblacklist']) {
			case "0":
				$useBlacklist = null;
				break;
			case "1":
				$useBlacklist = 1;
				break;
			case "2":
				$useBlacklist = 0;
				break;
		}
		# Check period
		if (empty($_POST['checkhelo_blacklistperiod'])) {
			$blacklistPeriod = null;
		} else {
			$blacklistPeriod = $_POST['checkhelo_blacklistperiod'];
		}

		# Sort out using of HRP
		switch ($_POST['checkhelo_usehrp']) {
			case "0":
				$useHRP = null;
				break;
			case "1":
				$useHRP = 1;
				break;
			case "2":
				$useHRP = 0;
				break;
		}
		# Check period
		if (empty($_POST['checkhelo_hrpperiod'])) {
			$HRPPeriod = null;
		} else {
			$HRPPeriod = $_POST['checkhelo_hrpperiod'];
		}
		# Check limit
		if (empty($_POST['checkhelo_hrplimit'])) {
			$HRPLimit = null;
		} else {
			$HRPLimit = $_POST['checkhelo_hrplimit'];
		}

		# Sort out checking invalid HELO's
		switch ($_POST['checkhelo_rejectinvalid']) {
			case "0":
				$rejectInvalid = null;
				break;
			case "1":
				$rejectInvalid = 1;
				break;
			case "2":
				$rejectInvalid = 0;
				break;
		}

		# Sort out checking HELO's for IP's
		switch ($_POST['checkhelo_rejectip']) {
			case "0":
				$rejectIP = null;
				break;
			case "1":
				$rejectIP = 1;
				break;
			case "2":
				$rejectIP = 0;
				break;
		}

		# Sort out checking HELO's are resolvable
		switch ($_POST['checkhelo_rejectunresolvable']) {
			case "0":
				$rejectUnresolvable = null;
				break;
			case "1":
				$rejectUnresolvable = 1;
				break;
			case "2":
				$rejectUnresolvable = 0;
				break;
		}

		$stmt = $db->prepare("
			INSERT INTO ${DB_TABLE_PREFIX}checkhelo
					(
						PolicyID,Name,
						UseBlacklist,BlacklistPeriod,
						UseHRP,HRPPeriod,HRPLimit,
						RejectInvalid,RejectIP,RejectUnresolvable,
						Comment,Disabled
					)					
				VALUES 
					(
						?,?,
						?,?,
						?,?,?,
						?,?,?,
						?,1
					)
		");
		
		$res = $stmt->execute(array(
			$_POST['checkhelo_policyid'],
			$_POST['checkhelo_name'],
			$useBlacklist,$blacklistPeriod,
			$useHRP,$HRPPeriod,$HRPLimit,
			$rejectInvalid,$rejectIP,$rejectUnresolvable,
			$_POST['checkhelo_comment']
		));

		if ($res) {
?>
			<div class="notice">HELO/EHLO check created</div>
<?php
		} else {
?>
			<div class="warning">Failed to create HELO/EHLO check</div>
			<div class="warning"><?php print_r($stmt->errorInfo()) ?></div>
<?php
		}

	}


} else {
?>
	<div class="warning">Invalid invocation</div>
<?php
}

printFooter();


# vim: ts=4
?>
