<?php
# Module: Accounting delete
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
			"Back to accounting" => "accounting-main.php",
		),
));



# Display delete confirm screen
if ($_POST['frmaction'] == "delete") {

	# Check a accounting was selected
	if (isset($_POST['accounting_id'])) {
?>
		<p class="pageheader">Delete Accounting</p>

		<form action="accounting-delete.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="delete2" />
				<input type="hidden" name="accounting_id" value="<?php echo $_POST['accounting_id']; ?>" />
			</div>
			
			<div class="textcenter">
				Are you very sure you want to remove this and its associated records? <br />
				<input type="submit" name="confirm" value="yes" />
				<input type="submit" name="confirm" value="no" />
			</div>
		</form>
<?php
	} else {
?>
		<div class="warning">No accounting selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "delete2") {
?>
	<p class="pageheader">Accounting Delete Results</p>
<?php
	if (isset($_POST['accounting_id'])) {

		if ($_POST['confirm'] == "yes") {	
			
			# Check last query succeeded, if so continue
			$db->beginTransaction();

			$stmt = $db->prepare("
				DELETE FROM 
					${DB_TABLE_PREFIX}accounting_tracking 
				WHERE 
					AccountingID = ?
			");
			$res = $stmt->execute(array($_POST['accounting_id']));

			if ($res !== FALSE) {
?>
				<div class="notice">Accounting tracking info deleted</div>
<?php
			} else {
?>
				<div class="warning">Error deleting accounting tracking info!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
				$db->rollBack();
			}


			if ($res !== FALSE) {
				$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}accounting WHERE ID = ".$db->quote($_POST['accounting_id']));
				if ($res) {
?>
					<div class="notice">Accounting deleted</div>
<?php
				} else {
?>
					<div class="warning">Error deleting accounting!</div>
					<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
					$db->rollBack();
				}
			}
			
			# Commit if last transaction succeeded
			if ($res) {
				$db->commit();
			}
			
		} else {
?>
			<div class="notice">Accounting not deleted, aborted by user</div>
<?php
		}

	# Warn
	} else {
?>
		<div class="warning">Invocation error, no accounting ID</div>
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

