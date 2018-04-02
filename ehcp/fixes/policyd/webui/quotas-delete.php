<?php
# Module: Quotas delete
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
		),
));



# Display delete confirm screen
if ($_POST['frmaction'] == "delete") {

	# Check a quota was selected
	if (isset($_POST['quota_id'])) {
?>
		<p class="pageheader">Delete Quota</p>

		<form action="quotas-delete.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="delete2" />
				<input type="hidden" name="quota_id" value="<?php echo $_POST['quota_id']; ?>" />
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
		<div class="warning">No quota selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "delete2") {
?>
	<p class="pageheader">Quota Delete Results</p>
<?php
	if (isset($_POST['quota_id'])) {

		if ($_POST['confirm'] == "yes") {

			# Grab tracking limits we must delete for
			$res = $db->query("
					SELECT 
						ID
					FROM 
						${DB_TABLE_PREFIX}quotas_limits
					WHERE
						QuotasID = ".$db->quote($_POST['quota_id'])."
			");

			$limitIDs = array();

			if ($res !== FALSE) {
				# Pull in limit ID's
				while ($row = $res->fetchObject()) {
					array_push($limitIDs,$row->id);
				}
				$res->closeCursor();

			} else {
?>
				<div class="warning">Error selecting quota limit IDs!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}


			# Check last query succeeded, if so continue
			if ($res !== FALSE) {
				$db->beginTransaction();

				$stmt = $db->prepare("
					DELETE FROM 
						${DB_TABLE_PREFIX}quotas_tracking 
					WHERE 
						QuotasLimitsID = ?
				");

				# Loop with limit ID's, start off true
				$res = true;
				foreach ($limitIDs as $id) {
					$res = $stmt->execute(array($id));
				}

				if ($res !== FALSE) {
?>
					<div class="notice">Quota tracking info deleted</div>
<?php
				} else {
?>
					<div class="warning">Error deleting quota tracking info!</div>
					<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
					$db->rollBack();
				}
			}


			# Check last query succeeded, if so continue
			if ($res !== FALSE) {
				$stmt = $db->prepare("
						DELETE FROM 
							${DB_TABLE_PREFIX}quotas_limits 
						WHERE 
							QuotasID = ?"
				);
				$res = $stmt->execute(array($_POST['quota_id']));

				if ($res !== FALSE) {
?>
					<div class="notice">Quota limits deleted</div>
<?php
				} else {
?>
					<div class="warning">Error deleting quota limits!</div>
					<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
					$db->rollBack();
				}
			}

			# Check last query succeeded, if so continue
			if ($res !== FALSE) {
				$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}quotas WHERE ID = ".$db->quote($_POST['quota_id']));
				if ($res) {
?>
					<div class="notice">Quota deleted</div>
<?php
				} else {
?>
					<div class="warning">Error deleting quota!</div>
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
			<div class="notice">Quota not deleted, aborted by user</div>
<?php
		}

	# Warn
	} else {
?>
		<div class="warning">Invocation error, no quota ID</div>
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

