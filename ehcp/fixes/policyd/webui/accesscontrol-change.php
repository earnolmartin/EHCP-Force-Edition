<?php
# Module: AccessControl change
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
			"Back to access cntrl" => "accesscontrol-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a access control was selected
	if (isset($_POST['accesscontrol_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
			SELECT 
				${DB_TABLE_PREFIX}access_control.ID, ${DB_TABLE_PREFIX}access_control.PolicyID, ${DB_TABLE_PREFIX}access_control.Name, 
				${DB_TABLE_PREFIX}access_control.Verdict, ${DB_TABLE_PREFIX}access_control.Data, 
				${DB_TABLE_PREFIX}access_control.Comment, ${DB_TABLE_PREFIX}access_control.Disabled,
				
				${DB_TABLE_PREFIX}policies.Name AS PolicyName
				
			FROM 
				${DB_TABLE_PREFIX}access_control, ${DB_TABLE_PREFIX}policies 

			WHERE 
				${DB_TABLE_PREFIX}access_control.ID = ?
				AND ${DB_TABLE_PREFIX}policies.ID = ${DB_TABLE_PREFIX}access_control.PolicyID
			");
?>
		<p class="pageheader">Update Access Control</p>

		<form action="accesscontrol-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="accesscontrol_id" value="<?php echo $_POST['accesscontrol_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['accesscontrol_id']));

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
					<td><input type="text" name="accesscontrol_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Link to policy</td>
					<td class="oldval"><?php echo $row->policyname ?></td>
					<td>
						<select name="accesscontrol_policyid">
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
					<td class="entrytitle">
						Verdict
						<?php tooltip('accesscontrol_verdict'); ?>
					</td>
					<td class="oldval"><?php echo $row->verdict ?></td>
					<td>
						<select name="accesscontrol_verdict">
							<option value="">--</option>
							<option value="HOLD">Hold</option>
							<option value="REJECT">Reject</option>
							<option value="DISCARD">Discard (drop)</option>
							<option value="FILTER">Filter</option>
							<option value="REDIRECT">Redirect</option>
							<option value="OK">Ok</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Data
						<?php tooltip('accesscontrol_data'); ?>
					</td>
					<td class="oldval"><?php echo $row->data ?></td>
					<td><input type="text" name="accesscontrol_data" /></td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="accesscontrol_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="accesscontrol_disabled">
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
		<div class="warning">No access control selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Access Control Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['accesscontrol_policyid'])) {
		array_push($updates,"PolicyID = ".$db->quote($_POST['accesscontrol_policyid']));
	}
	if (!empty($_POST['accesscontrol_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['accesscontrol_name']));
	}
	if (!empty($_POST['accesscontrol_verdict'])) {
		array_push($updates,"Verdict = ".$db->quote($_POST['accesscontrol_verdict']));
	}
	if (!empty($_POST['accesscontrol_data'])) {
		array_push($updates,"Data = ".$db->quote($_POST['accesscontrol_data']));
	}
	if (!empty($_POST['accesscontrol_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['accesscontrol_comment']));
	}
	if (isset($_POST['accesscontrol_disabled']) && $_POST['accesscontrol_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['accesscontrol_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}access_control SET $updateStr WHERE ID = ".$db->quote($_POST['accesscontrol_id']));
		if ($res) {
?>
			<div class="notice">Access control updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating access control!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to access control</div>
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
