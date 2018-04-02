<?php
# Module: Policy delete
# Copyright (C) 2009-2012, AllWorldIT
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
			"Back to policies" => "policy-main.php",
		),
));



# Display delete confirm screen
if ($_POST['frmaction'] == "delete") {

	# Check a policy was selected
	if (isset($_POST['policy_id'])) {
?>
		<p class="pageheader">Delete Policy</p>

		<form action="policy-delete.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="delete2" />
				<input type="hidden" name="policy_id" value="<?php echo $_POST['policy_id']; ?>" />
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
		<div class="warning">No policy selected</div>
<?php
	}



# SQL Updates
} elseif ($_POST['frmaction'] == "delete2") {
?>
	<p class="pageheader">Policy Delete Results</p>
<?php
	if (isset($_POST['policy_id'])) {


		if ($_POST['confirm'] == "yes") {

			$db->beginTransaction();

			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}policy_members WHERE PolicyID = ".$db->quote($_POST['policy_id']));
			if ($res === FALSE) {
?>
				<div class="warning">Error clearing policy_members!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}
			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}greylisting WHERE PolicyID = ".$db->quote($_POST['policy_id']));
			if ($res === FALSE) {
?>
				<div class="warning">Error clearing greylisting!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}
			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}access_control WHERE PolicyID = ".$db->quote($_POST['policy_id']));
			if ($res === FALSE) {
?>
				<div class="warning">Error clearing access_control </div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}
			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}checkspf WHERE PolicyID = ".$db->quote($_POST['policy_id']));
			if ($res === FALSE) {
?>
				<div class="warning">Error clearing checkspf!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}
			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}checkhelo WHERE PolicyID = ".$db->quote($_POST['policy_id']));
			if ($res === FALSE) {
?>
				<div class="warning">Error clearing checkhelo!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
			}

			# Grab quotas we need to delete
			$quotas_to_delete = array();
			foreach ($db->query("SELECT ID FROM ${DB_TABLE_PREFIX}quotas WHERE PolicyID = ".$db->quote($_POST['policy_id'])) as $row) {
				array_push($quotas_to_delete, $row['id']);
			}

			# Proceed if we actually have quotas
			if (count($quotas_to_delete) > 0) {
				$quotas_to_delete = implode(",",$quotas_to_delete);

				# Grab limits we need to delete
				$limits_to_delete = array();
				foreach ($db->query("SELECT ID FROM ${DB_TABLE_PREFIX}quotas_limits WHERE QuotasID IN (".$quotas_to_delete.")") as $row) {
					array_push($limits_to_delete, $row['id']);
				}

				# Proceed if we actually have limits
				if (count($limits_to_delete) > 0) {
					$limits_to_delete = implode(",",$limits_to_delete);

					# Do delete of quotas
					$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}quotas_tracking WHERE QuotasLimitsID IN (".$limits_to_delete.")");
					$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}quotas_limits WHERE ID IN (".$limits_to_delete.")");
				}
				$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}quotas WHERE ID IN (".$quotas_to_delete.")");
			}

			# Grab accounting we need to delete
			$accounting_to_delete = array();
			foreach ($db->query("SELECT ID FROM ${DB_TABLE_PREFIX}accounting WHERE PolicyID = ".$db->quote($_POST['policy_id'])) as $row) {
				array_push($accounting_to_delete, $row['id']);
			}

			# Proceed if we actually have accounting
			if (count($accounting_to_delete) > 0) {
				$accounting_to_delete = implode(",",$accounting_to_delete);

				$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}accounting_tracking WHERE AccountingID IN (".$accounting_to_delete.")");
				$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}accounting WHERE ID IN (".$accounting_to_delete.")");
			}

			# Main policy
			$res = $db->exec("DELETE FROM ${DB_TABLE_PREFIX}policies WHERE ID = ".$db->quote($_POST['policy_id']));

			if ($res !== FALSE) {
?>
				<div class="notice">Policy deleted</div>
<?php
				$db->commit();
			} else {
?>
				<div class="warning">Error deleting policy!</div>
				<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
				$db->rollback();
			}
		} else {
?>
			<div class="notice">Policy not deleted, aborted by user</div>
<?php
		}

	# Warn
	} else {
?>
		<div class="warning">Invocation error, no policy ID</div>
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

