<?php
# Postfix distribution group member add
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



$db = connect_postfix_db();



printHeader(array(
		"Tabs" => array(
			"Back to groups" => "postfix-distgroups-main.php",
			"Back to members" => "postfix-distgroups-member-main.php?postfix_group_id=".$_POST['postfix_group_id'],
		),
));


if ($_POST['frmaction'] == "add")  {
?>
	<p class="pageheader">Add Distribution Group Member</p>
<?php
?>
		<form method="post" action="postfix-distgroups-member-add.php">
			<div>
				<input type="hidden" name="frmaction" value="add2" />
				<input type="hidden" name="postfix_group_id" value="<?php echo $_POST['postfix_group_id'] ?>" />
			</div>
			<table class="entry">
				<tr>
					<td class="entrytitle">Email Address</td>
					<td><input type="text" name="postfix_group_member_goto" /></td>
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
	<p class="pageheader">Distribution Group Member Add Results</p>

<?php

	$stmt = $db->prepare("INSERT INTO ${DB_TABLE_PREFIX}distribution_group_members (DistributionGroupID,Goto,Disabled) VALUES (?,?,0)");
	
	$res = $stmt->execute(array(
		$_POST['postfix_group_id'],
		$_POST['postfix_group_member_goto']
	));


	if ($res) {
?>
		<div class="notice">Distribution group member created</div>
<?php
	} else {
?>
		<div class="warning">Failed to create distribution group member</div>
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
