<?php
# Postfix transport change
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
			"Back to Transports" => "postfix-transports-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a transport was selected
	if (isset($_POST['postfix_transport_id'])) {
		# Prepare statement
		$stmt = $db->prepare("SELECT ID, DomainName, Type, Transport, Disabled FROM ${DB_TABLE_PREFIX}transports WHERE ID = ?");
?>
		<p class="pageheader">Update Postfix Transport</p>

		<form action="postfix-transports-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="postfix_transport_id" value="<?php echo $_POST['postfix_transport_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['postfix_transport_id']));

			$row = $stmt->fetchObject();

?>
			<table class="entry" style="width: 75%;">
				<tr>
					<td></td>
					<td class="entrytitle textcenter">Old Value</td>
					<td class="entrytitle textcenter">New Value</td>
				</tr>
				<tr>
					<td class="entrytitle">Domain Name</td>
					<td class="oldval"><?php echo $row->domainname ?></td>
					<td></td>
				</tr>
				<tr>
					<td class="entrytitle">
						Type
						<?php tooltip('postfix_transport_type'); ?>
					</td>
					<td class="oldval"><?php
		   				# Translate type	
						if ($row->type == "0") {
							echo "Virtual";
						} elseif ($row->type == "1") {
							echo "SMTP";
						}
					?></td>
					<td></td>
				</tr>
				<tr>
					<td class="entrytitle">Data</td>
					<td class="oldval"><?php echo $row->transport ?></td>
<?php
					if ($row->type == "1") {
?>
						<td><input type="text" name="postfix_transport_data" /></td>
<?php
					} else {
?>
						<td></td>
<?php
					}
?>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="postfix_transport_disabled" />
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
	<p class="pageheader">Policy Group Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['postfix_transport_type'])) {
		array_push($updates,"Type = ".$db->quote($_POST['postfix_transport_type']));
	}

	if (!empty($_POST['postfix_transport_data'])) {
		# smtp
		$transport = $_POST['postfix_transport_data'];
		$ptransport = "smtp:$transport";
		array_push($updates ,"Transport = ".$db->quote($transport));
		array_push($updates ,"PTransport = ".$db->quote($ptransport));
	}

	if (isset($_POST['postfix_transport_disabled']) && $_POST['postfix_transport_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['postfix_transport_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}transports SET $updateStr WHERE ID = ".$db->quote($_POST['postfix_transport_id']));
		if ($res) {
?>
			<div class="notice">Postfix transport updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating Postfix transport!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to Postfix transport</div>
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
