<?php
# Module: CheckHelo (blacklist) change
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
			"Back to blacklist" => "checkhelo-blacklist-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a checkhelo blacklist was selected
	if (isset($_POST['blacklist_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
			SELECT 
				ID, Helo, Comment, 
				Disabled
				
			FROM 
				${DB_TABLE_PREFIX}checkhelo_blacklist

			WHERE 
				ID = ?
			");
?>
		<p class="pageheader">Update HELO/EHLO Blacklist</p>

		<form action="checkhelo-blacklist-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="blacklist_id" value="<?php echo $_POST['blacklist_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['blacklist_id']));

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
					<td class="entrytitle">
						Helo
						<?php tooltip('checkhelo_blacklist_helo'); ?>
					</td>
					<td class="oldval"><?php echo $row->helo ?></td>
					<td><input type="text" name="blacklist_helo" /></td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="blacklist_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="blacklist_disabled">
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
		<div class="warning">No blacklisting selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">HELO/EHLO Blacklisting Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['blacklist_helo'])) {
		array_push($updates,"Helo = ".$db->quote($_POST['blacklist_helo']));
	}
	if (!empty($_POST['blacklist_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['blacklist_comment']));
	}
	if (isset($_POST['blacklist_disabled']) && $_POST['blacklist_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['blacklist_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}checkhelo_blacklist SET $updateStr WHERE ID = ".$db->quote($_POST['blacklist_id']));
		if ($res) {
?>
			<div class="notice">HELO/EHLO blacklisting updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating HELO/EHLO blacklisting!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to HELO/EHLO blacklisting</div>
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
