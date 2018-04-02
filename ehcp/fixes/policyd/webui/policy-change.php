<?php
# Policy change
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
			"Back to policies" => "policy-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a policy was selected
	if (isset($_POST['policy_id'])) {
		# Prepare statement
		$stmt = $db->prepare("SELECT ID, Name, Priority, Description, Disabled FROM ${DB_TABLE_PREFIX}policies WHERE ID = ?");
?>
		<p class="pageheader">Update Policies</p>

		<form action="policy-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="policy_id" value="<?php echo $_POST['policy_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['policy_id']));

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
					<td><input type="text" name="policy_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Priority</td>
					<td class="oldval"><?php echo $row->priority ?></td>
					<td>
						<input type="text" name="policy_priority" />
						<?php tooltip('policy_priority'); ?>
					</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Description</td>
					<td class="oldval texttop"><?php echo $row->description ?></td>
					<td><textarea name="policy_description" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="policy_disabled">
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
		<div class="warning">No policy selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Policy Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['policy_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['policy_name']));
	}
	if (isset($_POST['policy_priority']) && $_POST['policy_priority'] != "") {
		array_push($updates,"Priority = ".$db->quote($_POST['policy_priority']));
	}
	if (!empty($_POST['policy_description'])) {
		array_push($updates,"Description = ".$db->quote($_POST['policy_description']));
	}
	if (isset($_POST['policy_disabled']) && $_POST['policy_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['policy_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}policies SET $updateStr WHERE ID = ".$db->quote($_POST['policy_id']));
		if ($res) {
?>
			<div class="notice">Policy updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating policy!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to policy</div>
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
