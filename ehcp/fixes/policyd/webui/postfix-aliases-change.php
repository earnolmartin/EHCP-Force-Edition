<?php
# Postfix alias change
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



$db = connect_postfix_db();



printHeader(array(
		"Tabs" => array(
			"Back to Aliases" => "postfix-aliases-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a alias was selected
	if (isset($_POST['postfix_alias_id'])) {
		# Prepare statement
		$stmt = $db->prepare("SELECT ID, MailAddress, Goto, Disabled FROM ${DB_TABLE_PREFIX}aliases WHERE ID = ?");
?>
		<p class="pageheader">Update Postfix Alias</p>

		<form action="postfix-aliases-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="postfix_alias_id" value="<?php echo $_POST['postfix_alias_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['postfix_alias_id']));

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
					<td class="entrytitle">Alias Address</td>
					<td class="oldval"><?php echo $row->mailaddress ?></td>
					<td></td>
				</tr>
				<tr>
					<td class="entrytitle">
						Goto
						<?php tooltip('postfix_alias_goto'); ?>
					</td>
					<td class="oldval"><?php echo $row->goto ?></td>
					<td><input type="text" name="postfix_alias_goto" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="postfix_alias_disabled" />
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
		<div class="warning">No alias selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Policy Group Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['postfix_alias_goto'])) {
		array_push($updates ,"Goto = ".$db->quote($_POST['postfix_alias_goto']));
	}
	if (isset($_POST['postfix_alias_disabled']) && $_POST['postfix_alias_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['postfix_alias_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}aliases SET $updateStr WHERE ID = ".$db->quote($_POST['postfix_alias_id']));
		if ($res) {
?>
			<div class="notice">Postfix alias updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating Postfix alias!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to Postfix alias</div>
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
