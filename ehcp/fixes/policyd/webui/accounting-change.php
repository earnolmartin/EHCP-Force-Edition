<?php
# Module: Accounting change
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



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a accounting was selected
	if (isset($_POST['accounting_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
			SELECT 
				${DB_TABLE_PREFIX}accounting.ID, ${DB_TABLE_PREFIX}accounting.PolicyID, ${DB_TABLE_PREFIX}accounting.Name, 
				${DB_TABLE_PREFIX}accounting.Track, ${DB_TABLE_PREFIX}accounting.AccountingPeriod, 
				${DB_TABLE_PREFIX}accounting.MessageCountLimit, ${DB_TABLE_PREFIX}accounting.MessageCumulativeSizeLimit,
				${DB_TABLE_PREFIX}accounting.Verdict, ${DB_TABLE_PREFIX}accounting.Data, 
				${DB_TABLE_PREFIX}accounting.LastAccounting, 
				${DB_TABLE_PREFIX}accounting.Comment, 
				${DB_TABLE_PREFIX}accounting.Disabled,
				
				${DB_TABLE_PREFIX}policies.Name AS PolicyName
				
			FROM 
				${DB_TABLE_PREFIX}accounting, ${DB_TABLE_PREFIX}policies 

			WHERE 
				${DB_TABLE_PREFIX}accounting.ID = ?
				AND ${DB_TABLE_PREFIX}policies.ID = ${DB_TABLE_PREFIX}accounting.PolicyID
			");
?>
		<p class="pageheader">Update Accounting</p>

		<form action="accounting-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="accounting_id" value="<?php echo $_POST['accounting_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['accounting_id']));

			$row = $stmt->fetchObject();
			$stmt->closeCursor();
?>
			<table class="entry" style="width: 75%;">
				<tr>
					<td></td>
					<td class="entrytitle textcenter">Old Value</td>
					<td class="entrytitle textcenter">New Value</td>
				</tr>
				<tr>
					<td class="entrytitle">Name</td>
					<td class="oldval"><?php echo $row->name ?></td>
					<td><input type="text" name="accounting_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Link to policy</td>
					<td class="oldval"><?php echo $row->policyname ?></td>
					<td>
						<select name="accounting_policyid">
							<option value="">--</option>
<?php
							$res = $db->query("SELECT ID, Name FROM ${DB_TABLE_PREFIX}policies ORDER BY Name");
							while ($row2 = $res->fetchObject()) {
?>
								<option value="<?php echo $row2->id ?>" ><?php echo $row2->name ?></option>
<?php
							}
							$res->closeCursor();
?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">Track</td>
					<td class="oldval"><?php echo $row->track ?></td>
					<td>
						<select id="accounting_track" name="accounting_track"
								onChange="
									var myobj = document.getElementById('accounting_track');
									var myobj2 = document.getElementById('accounting_trackextra');

									if (myobj.selectedIndex == 1) {
										myobj2.disabled = false;
										myobj2.value = '/32';
									} else if (myobj.selectedIndex != 1) {
										myobj2.disabled = true;
										myobj2.value = 'n/a';
									}
							">
							<option value="">--</option>
							<option value="SenderIP">Sender IP</option>
							<option value="Sender:user@domain">Sender:user@domain</option>
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
<?php
					# Get human readable accounting period
					if ($row->accountingperiod == "0") {
						$accountingperiod = "Daily";
					} elseif ($row->accountingperiod == "1") {
						$accountingperiod = "Weekly";
					} elseif ($row->accountingperiod == "2") {
						$accountingperiod = "Monthly";
					}
?>
					<td class="entrytitle">Period</td>
					<td class="oldval"><?php echo $accountingperiod ?></td>
					<td>
						<select id="accounting_period" name="accounting_period">
							<option value="">--</option>
							<option value="0">Daily</option>
							<option value="1">Weekly</option>
							<option value="2">Monthly</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">Message Count Limit</td>
					<td class="oldval"><?php echo $row->messagecountlimit ? $row->messagecountlimit : '-none-' ?></td>
					<td><input type="text" name="accounting_messagecountlimit" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Message Cumulative Size Limit</td>
					<td class="oldval"><?php echo $row->messagecumulativesizelimit ? $row->messagecumulativesizelimit : '-none-' ?></td>
					<td><input type="text" name="accounting_messagecumulativesizelimit" />Kbyte</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Verdict
					</td>
					<td class="oldval"><?php echo $row->verdict ?></td>
					<td>
						<select name="accounting_verdict">
							<option value="">--</option>
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
					<td class="oldval"><?php echo $row->data ?></td>
					<td><input type="text" name="accounting_data" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Stop processing here</td>
					<td class="oldval"><?php echo $row->lastaccounting ? 'yes' : 'no' ?></td>
					<td>
						<select name="accounting_lastaccounting">
							<option value="">--</option>
							<option value="0">No</option>
							<option value="1">Yes</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="accounting_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="accounting_disabled">
							<option value="">--</option>
							<option value="0">No</option>
							<option value="1">Yes</option>
						</select>		
					</td>
				</tr>
			</table>
	
			<p />
			<div class="textcenter">
				<input type="submit" />
			</div>
		</form>
<?php
	} else {
?>
		<div class="warning">No accounting selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Accounting Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['accounting_policyid'])) {
		array_push($updates,"PolicyID = ".$db->quote($_POST['accounting_policyid']));
	}
	if (!empty($_POST['accounting_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['accounting_name']));
	}
	if (!empty($_POST['accounting_track'])) {
		if ($_POST['accounting_track'] == "SenderIP") {
			$accountingTrack = sprintf('%s:%s',$_POST['accounting_track'],$_POST['accounting_trackextra']);
		} else {
			$accountingTrack = $_POST['accounting_track'];
		}
		array_push($updates,"Track = ".$db->quote($accountingTrack));
	}
	if (isset($_POST['accounting_period']) && $_POST['accounting_period'] != "") {
		array_push($updates,"AccountingPeriod = ".$db->quote($_POST['accounting_period']));
	}
	if (!empty($_POST['accounting_messagecountlimit'])) {
		array_push($updates,"MessageCountLimit = ".$db->quote($_POST['accounting_messagecountlimit']));
	}
	if (!empty($_POST['accounting_messagecumulativesizelimit'])) {
		array_push($updates,"MessageCumulativeSizeLimit = ".$db->quote($_POST['accounting_messagecumulativesizelimit']));
	}
	if (!empty($_POST['accounting_verdict'])) {
		array_push($updates,"Verdict = ".$db->quote($_POST['accounting_verdict']));
	}
	if (!empty($_POST['accounting_data'])) {
		array_push($updates,"Data = ".$db->quote($_POST['accounting_data']));
	}
	if (!empty($_POST['accounting_lastaccounting'])) {
		array_push($updates,"LastAccounting = ".$db->quote($_POST['accounting_lastaccounting']));
	}
	if (!empty($_POST['accounting_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['accounting_comment']));
	}
	if (isset($_POST['accounting_disabled']) && $_POST['accounting_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['accounting_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}accounting SET $updateStr WHERE ID = ".$db->quote($_POST['accounting_id']));
		if ($res) {
?>
			<div class="notice">Accounting updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating accounting!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to accounting</div>
<?php
	}

} else {
?>
	<div class="warning">Invalid invocation</div>
<?php
}


printFooter();


# vim: ts=4
?>
