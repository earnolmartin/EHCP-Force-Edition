<?php
# Module: Greylisting (whitelist) add
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
			"Back to whitelist" => "greylisting-whitelist-main.php"
		),
));



if ($_POST['frmaction'] == "add") {
?>
	<p class="pageheader">Add Greylisting Whitelist</p>

	<form method="post" action="greylisting-whitelist-add.php">
		<div>
			<input type="hidden" name="frmaction" value="add2" />
		</div>
		<table class="entry">
			<tr>
				<td class="entrytitle">
					Source
					<?php tooltip('greylisting_whitelist_source'); ?>
				</td>
				<td>
					<select id="whitelist_type" name="whitelist_type">
						<option value="SenderIP">Sender IP</option>
					</select>
					<input type="text" name="whitelist_source" size="40" />
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Comment</td>
				<td><textarea name="whitelist_comment" cols="40" rows="5"></textarea></td>
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
	<p class="pageheader">Greylisting Whitelist Add Results</p>

<?php
	# Check name
	if (empty($_POST['whitelist_source'])) {
?>
		<div class="warning">Source cannot be empty</div>
<?php

	} else {
		$stmt = $db->prepare("INSERT INTO ${DB_TABLE_PREFIX}greylisting_whitelist (Source,Comment,Disabled) VALUES (?,?,1)");
		
		$res = $stmt->execute(array(
			$_POST['whitelist_type'] . ":" . $_POST['whitelist_source'],
			$_POST['whitelist_comment']
		));
		
		if ($res) {
?>
			<div class="notice">Greylisting whitelist created</div>
<?php
		} else {
?>
			<div class="warning">Failed to create Greylisting whitelisting</div>
			<div class="warning"><?php print_r($stmt->errorInfo()) ?></div>
<?php
		}

	}


} else {
?>
	<div class="warning">Invalid invocation</div>
<?php
}

printFooter();


# vim: ts=4
?>
