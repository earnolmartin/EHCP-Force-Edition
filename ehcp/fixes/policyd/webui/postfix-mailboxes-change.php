<?php
# Postfix mailbox change
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
			"Back to Mailboxes" => "postfix-mailboxes-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a mailbox was selected
	if (isset($_POST['postfix_mailbox_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
				SELECT 
					ID, 
					Mailbox, 
					Quota, 
					Name, 
					BCC, 
					Comment, 
					Disabled 
				FROM 
					${DB_TABLE_PREFIX}mailboxes
				WHERE 
					ID = ?
		");
?>
		<p class="pageheader">Update Mailbox</p>

		<form action="postfix-mailboxes-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="postfix_mailbox_id" value="<?php echo $_POST['postfix_mailbox_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['postfix_mailbox_id']));

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
					<td class="entrytitle">Mailbox</td>
					<td class="oldval"><?php echo $row->mailbox ?></td>
					<td></td>
				</tr>
				<tr>
					<td class="entrytitle">Name</td>
					<td class="oldval"><?php echo $row->name ?></td>
					<td><input type="text" name="postfix_mailbox_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Password</td>
					<td class="oldval">*encrypted*</td>
					<td><input type="text" name="postfix_mailbox_password" /></td>
				</tr>
				<tr>
					<td class="entrytitle">
						Quota (in Mbyte)
						<?php tooltip('postfix_mailbox_quota'); ?>
					</td>
					<td class="oldval"><?php echo $row->quota ?></td>
					<td><input type="text" name="postfix_mailbox_quota" /> (0 = unlimited)</td>
				</tr>
				<tr>
					<td class="entrytitle">
						BCC
						<?php tooltip('postfix_mailbox_bcc'); ?>
					</td>
					<td class="oldval"><?php echo $row->bcc ?></td>
					<td><input type="text" name="postfix_mailbox_bcc" /></td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="postfix_mailbox_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="postfix_mailbox_disabled" />
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
		<div class="warning">No mailbox selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Mailbox Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['postfix_mailbox_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['postfix_mailbox_name']));
	}

	if (!empty($_POST['postfix_mailbox_password'])) {
		# Encrypt password
		$password = "{MD5}".base64_encode(pack("H*", md5($_POST['postfix_mailbox_password'])));

		array_push($updates,"Password = ".$db->quote($password));
	}

	if (isset($_POST['postfix_mailbox_quota'])) {
		if (!empty($_POST['postfix_mailbox_quota'])) {
			$quota = $db->quote($_POST['postfix_mailbox_quota']);
			array_push($updates,"Quota = ".$quota);
		}
	}

	if (!empty($_POST['postfix_mailbox_bcc'])) {
		array_push($updates,"BCC = ".$db->quote($_POST['postfix_mailbox_bcc']));
	}

	if (!empty($_POST['postfix_mailbox_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['postfix_mailbox_comment']));
	}

	if (isset($_POST['postfix_mailbox_disabled']) && $_POST['postfix_mailbox_disabled'] != "") {
		array_push($updates,"Disabled = ".$db->quote($_POST['postfix_mailbox_disabled']));
	}


	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}mailboxes SET $updateStr WHERE ID = ".$db->quote($_POST['postfix_mailbox_id']));
		if ($res) {
?>
			<div class="notice">Mailbox updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating mailbox!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to Postfix mailbox</div>
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
