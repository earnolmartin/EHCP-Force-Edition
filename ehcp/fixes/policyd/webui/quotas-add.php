<?php
# Module: Quotas add
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



if ($_POST['frmaction'] == "add") {
?>
	<p class="pageheader">Add Quota</p>

	<form method="post" action="quotas-add.php">
		<div>
			<input type="hidden" name="frmaction" value="add2" />
		</div>
		<table class="entry">
			<tr>
				<td class="entrytitle">Name</td>
				<td><input type="text" name="quota_name" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Track</td>
				<td>
					<select id="quota_track" name="quota_track"
							onchange="
								var myobj = document.getElementById('quota_track');
								var myobj2 = document.getElementById('quota_trackextra');

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
					<input type="text" id="quota_trackextra" name="quota_trackextra" size="18" value="n/a" disabled="disabled" />
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Period</td>
				<td><input type="text" name="quota_period" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Link to policy</td>
				<td>
					<select name="quota_policyid">
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
				<td class="entrytitle">Verdict</td>
				<td>
					<select name="quota_verdict">
						<option value="HOLD">Hold</option>
						<option value="REJECT" selected="selected">Reject</option>
						<option value="DEFER">Defer (delay)</option>
						<option value="DISCARD">Discard (drop)</option>
						<option value="FILTER">Filter</option>
						<option value="REDIRECT">Redirect</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Data</td>
				<td><input type="text" name="quota_data" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Stop processing here</td>
				<td>
					<select name="quota_lastquota">
						<option value="0">No</option>
						<option value="1">Yes</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Comment</td>
				<td><textarea name="quota_comment" cols="40" rows="5"></textarea></td>
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
	<p class="pageheader">Quota Add Results</p>

<?php
	# Check policy id
	if (empty($_POST['quota_policyid'])) {
?>
		<div class="warning">Policy ID cannot be empty</div>
<?php

	# Check name
	} elseif (empty($_POST['quota_name'])) {
?>
		<div class="warning">Name cannot be empty</div>
<?php

	# Check verdict
	} elseif (empty($_POST['quota_verdict'])) {
?>
		<div class="warning">Verdict cannot be empty</div>
<?php

	# Check last quota
	} elseif (!isset($_POST['quota_lastquota'])) {
?>
		<div class="warning">Stop procesing here field cannot be empty</div>
<?php

	} else {

		if ($_POST['quota_track'] == "SenderIP") {
			$quotaTrack = sprintf('%s:%s',$_POST['quota_track'],$_POST['quota_trackextra']);
		} else {
			$quotaTrack = $_POST['quota_track'];
		}


		$stmt = $db->prepare("
			INSERT INTO ${DB_TABLE_PREFIX}quotas 
				(PolicyID,Name,Track,Period,Verdict,Data,LastQuota,Comment,Disabled)
			VALUES 
				(?,?,?,?,?,?,?,?,1)
		");
		
		$res = $stmt->execute(array(
			$_POST['quota_policyid'],
			$_POST['quota_name'],
			$quotaTrack,
			$_POST['quota_period'],
			$_POST['quota_verdict'],
			$_POST['quota_lastquota'],
			$_POST['quota_data'],
			$_POST['quota_comment']
		));
		
		if ($res) {
?>
			<div class="notice">Quota created</div>
<?php
		} else {
?>
			<div class="warning">Failed to create quota</div>
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
