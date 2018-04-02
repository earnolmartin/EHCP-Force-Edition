<?php
# Module: Quotas change
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



$db = connect_db();



printHeader(array(
		"Tabs" => array(
			"Back to quotas" => "quotas-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a quota was selected
	if (isset($_POST['quota_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
			SELECT 
				${DB_TABLE_PREFIX}quotas.ID, ${DB_TABLE_PREFIX}quotas.PolicyID, ${DB_TABLE_PREFIX}quotas.Name, 
				${DB_TABLE_PREFIX}quotas.Track, ${DB_TABLE_PREFIX}quotas.Period, 
				${DB_TABLE_PREFIX}quotas.Verdict, ${DB_TABLE_PREFIX}quotas.Data, 
				${DB_TABLE_PREFIX}quotas.LastQuota,
				${DB_TABLE_PREFIX}quotas.Comment, ${DB_TABLE_PREFIX}quotas.Disabled,
				
				${DB_TABLE_PREFIX}policies.Name AS PolicyName
				
			FROM 
				${DB_TABLE_PREFIX}quotas, ${DB_TABLE_PREFIX}policies 

			WHERE 
				${DB_TABLE_PREFIX}quotas.ID = ?
				AND ${DB_TABLE_PREFIX}policies.ID = ${DB_TABLE_PREFIX}quotas.PolicyID
			");
?>
		<p class="pageheader">Update Quota</p>

		<form action="quotas-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="quota_id" value="<?php echo $_POST['quota_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['quota_id']));

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
					<td><input type="text" name="quota_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Link to policy</td>
					<td class="oldval"><?php echo $row->policyname ?></td>
					<td>
						<select name="quota_policyid">
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
						<select id="quota_track" name="quota_track"
								onChange="
									var myobj = document.getElementById('quota_track');
									var myobj2 = document.getElementById('quota_trackextra');

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
						<input type="text" id="quota_trackextra" name="quota_trackextra" size="18" value="n/a" disabled="disabled" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">Period</td>
					<td class="oldval"><?php echo $row->period ?></td>
					<td><input type="text" name="quota_period" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Verdict</td>
					<td class="oldval"><?php echo $row->verdict ?></td>
					<td>
						<select name="quota_verdict">
							<option value="">--</option>
							<option value="HOLD">Hold</option>
							<option value="REJECT">Reject</option>
							<option value="DEFER">Defer (delay)</option>
							<option value="DISCARD">Discard (drop)</option>
							<option value="FILTER">Filter</option>
							<option value="REDIRECT">Redirect</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">Data</td>
					<td class="oldval"><?php echo $row->data ?></td>
					<td><input type="text" name="quota_data" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Stop processing here</td>
					<td class="oldval"><?php echo $row->lastquota ? 'yes' : 'no' ?></td>
					<td>
						<select name="quota_lastquota">
							<option value="">--</option>
							<option value="0">No</option>
							<option value="1">Yes</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="quota_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="quota_disabled">
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
		<div class="warning">No quota selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Access Control Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['quota_policyid'])) {
		array_push($updates,"PolicyID = ".$db->quote($_POST['quota_policyid']));
	}
	if (!empty($_POST['quota_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['quota_name']));
	}
	if (!empty($_POST['quota_track'])) {
		if ($_POST['quota_track'] == "SenderIP") {
			$quotaTrack = sprintf('%s:%s',$_POST['quota_track'],$_POST['quota_trackextra']);
		} else {
			$quotaTrack = $_POST['quota_track'];
		}

		array_push($updates,"Track = ".$db->quote($quotaTrack));
	}
	if (!empty($_POST['quota_period'])) {
		array_push($updates,"Period = ".$db->quote($_POST['quota_period']));
	}
	if (!empty($_POST['quota_verdict'])) {
		array_push($updates,"Verdict = ".$db->quote($_POST['quota_verdict']));
	}
	if (!empty($_POST['quota_data'])) {
		array_push($updates,"Data = ".$db->quote($_POST['quota_data']));
	}
	if (isset($_POST['quota_lastquota']) && $_POST['quota_lastquota'] != "") {
		array_push($updates,"LastQuota = ".$db->quote($_POST['quota_lastquota']));
	}
	if (!empty($_POST['quota_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['quota_comment']));
	}
	if (isset($_POST['quota_disabled']) && $_POST['quota_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['quota_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}quotas SET $updateStr WHERE ID = ".$db->quote($_POST['quota_id']));
		if ($res) {
?>
			<div class="notice">Quota updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating quota!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to quota</div>
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
