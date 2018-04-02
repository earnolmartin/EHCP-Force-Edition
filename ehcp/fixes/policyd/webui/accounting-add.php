<?php
# Module: Accounting add
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
			"Back to accounting" => "accounting-main.php"
		),
));



if ($_POST['frmaction'] == "add") {
?>
	<p class="pageheader">Add Accounting</p>

	<form method="post" action="accounting-add.php">
		<div>
			<input type="hidden" name="frmaction" value="add2" />
		</div>
		<table class="entry">
			<tr>
				<td class="entrytitle">Name</td>
				<td><input type="text" name="accounting_name" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Link to policy</td>
				<td>
					<select name="accounting_policyid">
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
				<td class="entrytitle">Track</td>
				<td>
					<select id="accounting_track" name="accounting_track"
							onchange="
								var myobj = document.getElementById('accounting_track');
								var myobj2 = document.getElementById('accounting_trackextra');

								if (myobj.selectedIndex == 0) {
									myobj2.disabled = false;
									myobj2.value = '/32';
								} else if (myobj.selectedIndex != 0) {
									myobj2.disabled = true;
									myobj2.value = 'n/a';
								}
					">
						<option value="SenderIP">Sender IP</option>
						<option value="Sender:user@domain" selected="selected">Sender:user@domain</option>
						<option value="Sender:@domain">Sender:@domain</option>
						<option value="Sender:user@">Sender:user@</option>
						<option value="Recipient:user@domain">Recipient:user@domain</option>
						<option value="Recipient:@domain">Recipient:@domain</option>
						<option value="Recipient:user@">Recipient:user@</option>
						<option value="SASLUsername">SASLUsername:username</option>
						<option value="Policy">Policy</option>
					</select>
					<input type="text" id="accounting_trackextra" name="accounting_trackextra" size="18" value="n/a" disabled="disabled" />
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Period</td>
				<td>
					<select name="accounting_period">
						<option value="0">Daily</option>
						<option value="1">Weekly</option>
						<option value="2">Monthly</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Message Count Limit</td>
				<td><input type="text" name="accounting_messagecountlimit" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Message Cumulative Size Limit</td>
				<td><input type="text" name="accounting_messagecumulativesizelimit" />Kbyte</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Verdict
				</td>
				<td>
					<select name="accounting_verdict">
						<option value="">None</option>
						<option value="HOLD">Hold</option>
						<option value="REJECT">Reject</option>
						<option value="DISCARD">Discard (drop)</option>
						<option value="FILTER">Filter</option>
						<option value="REDIRECT">Redirect</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Data
				</td>
				<td><input type="text" name="accounting_data" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Stop processing here</td>
				<td>
					<select name="accounting_lastaccounting">
						<option value="0">No</option>
						<option value="1">Yes</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Comment</td>
				<td><textarea name="accounting_comment" cols="40" rows="5"></textarea></td>
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
	<p class="pageheader">Accounting Add Results</p>

<?php
	# Check name
	if (empty($_POST['accounting_policyid'])) {
?>
		<div class="warning">Policy ID cannot be empty</div>
<?php

	# Check name
	} elseif (empty($_POST['accounting_name'])) {
?>
		<div class="warning">Name cannot be empty</div>
<?php

	# Check accounting track
	} elseif (empty($_POST['accounting_track'])) {
?>
		<div class="warning">Track cannot be empty</div>
<?php

	# Check last accounting
	} elseif (!isset($_POST['accounting_lastaccounting'])) {
?>
		<div class="warning">Stop procesing here field cannot be empty</div>
<?php

	} else {

		if ($_POST['accounting_track'] == "SenderIP") {
			$accountingTrack = sprintf('%s:%s',$_POST['accounting_track'],$_POST['accounting_trackextra']);
		} else {
			$accountingTrack = $_POST['accounting_track'];
		}


		$stmt = $db->prepare("
			INSERT INTO ${DB_TABLE_PREFIX}accounting 
				(
					PolicyID, Name, Track, AccountingPeriod,
					MessageCountLimit, MessageCumulativeSizeLimit,
					Verdict, Data,
					LastAccounting,
					Comment, Disabled
				) 
			VALUES 
				(?,?,?,?,?,?,?,?,?,?,1)");
		
		$res = $stmt->execute(array(
			$_POST['accounting_policyid'],
			$_POST['accounting_name'],
			$accountingTrack,
			$_POST['accounting_period'],
			$_POST['accounting_messagecountlimit'],
			$_POST['accounting_messagecumulativesize'],
			$_POST['accounting_verdict'],
			$_POST['accounting_data'],
			$_POST['accounting_lastaccounting'],
			$_POST['accounting_comment']
		));
		
		if ($res) {
?>
			<div class="notice">Accounting created</div>
<?php
		} else {
?>
			<div class="warning">Failed to create accounting</div>
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
