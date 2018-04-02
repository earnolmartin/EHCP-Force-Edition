<?php
# Module: AccessControl delete
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
			"Back to access cntrl" => "accesscontrol-main.php",
		),
));



# Display delete confirm screen
if ($_POST['frmaction'] == "delete") {

	# Check a access control was selected
	if (isset($_POST['accesscontrol_id'])) {
?>
		<p class="pageheader">Delete Access Control</p>

		<form action="accesscontrol-delete.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="delete2" />
				<input type="hidden" name="accesscontrol_id" value="<?php echo $_POST['accesscontrol_id']; ?>" />
			</div>
			
			<div class="textcenter">
				Are you very sure? <br />
				<input type="submit" name="confirm" value="yes" />
				<input type="submit" name="confirm" value="no" />
			</div>
		</form>
<?php
	} else {
?>
		<div class="warning">No access control selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "delete2") {
?>
	<p class="pageheader">Access Control Delete Results</p>
<?php
	if (isset($_POST['accesscontrol_id'])) {

		if ($_POST['confirm'] == "yes") {	
			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}access_control WHERE ID = ".$db->quote($_POST['accesscontrol_id']));
			if ($res) {
?>
				<div class="notice">Access control deleted</div>
<?php
			} else {
?>
				<div class="warning">Error deleting access control!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}
		} else {
?>
			<div class="notice">Access control not deleted, aborted by user</div>
<?php
		}

	# Warn
	} else {
?>
		<div class="warning">Invocation error, no access control ID</div>
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

