<?php
# Module: CheckSPF add
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
			"Back to SPF checks" => "checkspf-main.php"
		),
));



if ($_POST['frmaction'] == "add") {
?>
	<p class="pageheader">Add SPF Check</p>

	<form method="post" action="checkspf-add.php">
		<div>
			<input type="hidden" name="frmaction" value="add2" />
		</div>
		<table class="entry">
			<tr>
				<td class="entrytitle">Name</td>
				<td><input type="text" name="checkspf_name" /></td>
			</tr>
			<tr>
				<td class="entrytitle">Link to policy</td>
				<td>
					<select name="checkspf_policyid">
<?php
						$res = $db->query("SELECT ID, Name FROM ${DB_TABLE_PREFIX}policies ORDER BY Name");
						while ($row = $res->fetchObject()) {
?>
							<option value="<?php echo $row->id ?>"><?php echo $row->name ?></option>
<?php
						}
?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Use SPF</td>
				<td>
					<select name="checkspf_usespf">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Reject Failed SPF
					<?php tooltip('checkspf_rejectfailed'); ?>
				</td>
				<td>
					<select name="checkspf_rejectfailed">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">
					Add SPF Header
					<?php tooltip('checkspf_addheader'); ?>
				</td>
				<td>
					<select name="checkspf_addheader">
						<option value="0" selected="selected">Inherit</option>
						<option value="1">Yes</option>
						<option value="2">No</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="entrytitle">Comment</td>
				<td><textarea name="checkspf_comment" cols="40" rows="5"></textarea></td>
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
	<p class="pageheader">SPF Check Add Results</p>

<?php
	# Check name
	if (empty($_POST['checkspf_policyid'])) {
?>
		<div class="warning">Policy ID cannot be empty</div>
<?php

	# Check name
	} elseif (empty($_POST['checkspf_name'])) {
?>
		<div class="warning">Name cannot be empty</div>
<?php

	} else {
		# Sort out if we going to use SPF or not
		switch ($_POST['checkspf_usespf']) {
			case "0":
				$useSPF = null;
				break;
			case "1":
				$useSPF = 1;
				break;
			case "2":
				$useSPF = 2;
				break;
		}

		# And if we reject on failed
		switch ($_POST['checkspf_rejectfailed']) {
			case "0":
				$rejectFailed = null;
				break;
			case "1":
				$rejectFailed = 1;
				break;
			case "2":
				$rejectFailed = 2;
				break;
		}

		# And if we add the spf header
		switch ($_POST['checkspf_addheader']) {
			case "0":
				$addHeader = null;
				break;
			case "1":
				$addHeader = 1;
				break;
			case "2":
				$addHeader = 2;
				break;
		}

		$stmt = $db->prepare("
			INSERT INTO ${DB_TABLE_PREFIX}checkspf 
				(PolicyID,Name,UseSPF,RejectFailedSPF,AddSPFHeader,Comment,Disabled) 
			VALUES 
				(?,?,?,?,?,?,1)
		");
		
		$res = $stmt->execute(array(
			$_POST['checkspf_policyid'],
			$_POST['checkspf_name'],
			$useSPF,
			$rejectFailed,
			$addHeader,
			$_POST['checkspf_comment']
		));
		
		if ($res) {
?>
			<div class="notice">SPF check created</div>
<?php
		} else {
?>
			<div class="warning">Failed to create SPF check</div>
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
