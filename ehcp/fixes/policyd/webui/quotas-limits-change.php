<?php
# Module: Quotas limits change
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
			"Back to quotas" => "quotas-main.php",
			"Back to limits" => "quotas-limits-main.php?quota_id=".$_REQUEST['quota_id'],
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a limit was selected
	if (isset($_POST['quota_limit_id'])) {
		# Prepare statement
		$stmt = $db->prepare("SELECT ID, Type, CounterLimit, Comment, Disabled FROM ${DB_TABLE_PREFIX}quotas_limits WHERE ID = ?");
		$res = $stmt->execute(array($_POST['quota_limit_id']));
		$row = $stmt->fetchObject();
		$stmt->closeCursor();
?>
		<p class="pageheader">Update Quota Limit</p>

		<form action="quotas-limits-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="quota_id" value="<?php echo $_POST['quota_id']; ?>" />
				<input type="hidden" name="quota_limit_id" value="<?php echo $_POST['quota_limit_id']; ?>" />
			</div>
			<table class="entry" style="width: 75%;">
				<tr>
					<td></td>
					<td class="entrytitle textcenter">Old Value</td>
					<td class="entrytitle textcenter">New Value</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Type</td>
					<td class="oldval texttop"><?php echo $row->type ?></td>
					<td>
						<select name="limit_type">
							<option value="">--</option>
							<option value="MessageCount">Message Count</option>
							<option value="MessageCumulativeSize">Message Cumulative Size</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Counter Limit</td>
					<td class="oldval texttop"><?php echo $row->counterlimit ?></td>
					<td><input type="text" name="limit_counterlimit" /></td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="limit_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="limit_disabled" />
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
	<p class="pageheader">Policy Update Results</p>
<?php
	# Check a quota was selected
	if (isset($_POST['quota_limit_id'])) {
		
		$updates = array();

		if (!empty($_POST['limit_type'])) {
			array_push($updates,"Type = ".$db->quote($_POST['limit_type']));
		}
		if (!empty($_POST['limit_counterlimit'])) {
			array_push($updates,"CounterLimit = ".$db->quote($_POST['limit_counterlimit']));
		}
		if (!empty($_POST['limit_comment'])) {
			array_push($updates,"Comment = ".$db->quote($_POST['limit_comment']));
		}
		if (isset($_POST['limit_disabled']) && $_POST['limit_disabled'] != "") {
			array_push($updates ,"Disabled = ".$db->quote($_POST['limit_disabled']));
		}

		# Check if we have updates
		if (sizeof($updates) > 0) {
			$updateStr = implode(', ',$updates);
	
			$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}quotas_limits SET $updateStr WHERE ID = ".$db->quote($_POST['quota_limit_id']));
			if ($res) {
?>
				<div class="notice">Quota limit updated</div>
<?php
			} else {
?>
				<div class="warning">Error updating quota limit!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}

		# Warn
		} else {
?>
			<div class="warning">No quota limit updates</div>
<?php
		}

	# Warn
	} else {
?>
		<div class="error">No quota limit data available</div>
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

