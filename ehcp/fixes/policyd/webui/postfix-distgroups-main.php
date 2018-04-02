<?php
# Postfix distribution groups main screen
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
));

?>
	<p class="pageheader">Distribution Groups</p>

	<form id="main_form" action="postfix-distgroups-main.php" method="post">

		<div class="textcenter">
			Action
			<select id="main_form_action" name="frmaction" 
					onchange="
						var myform = document.getElementById('main_form');
						var myobj = document.getElementById('main_form_action');

						if (myobj.selectedIndex == 2) {
							myform.action = 'postfix-distgroups-add.php';
						} else if (myobj.selectedIndex == 4) {
							myform.action = 'postfix-distgroups-change.php';
						} else if (myobj.selectedIndex == 5) {
							myform.action = 'postfix-distgroups-delete.php';
						} else if (myobj.selectedIndex == 6) {
							myform.action = 'postfix-distgroups-member-main.php';
						}

						myform.submit();
					">
			 
				<option selected="selected">select action</option>
				<option disabled="disabled"> - - - - - - - - - - - </option>
				<option value="add">Add</option>
				<option disabled="disabled"> - - - - - - - - - - - </option>
				<option value="change">Change</option>
				<option value="delete">Delete</option>
				<option value="members">Members</option>
			</select> 
		</div>

		<p />

		<table class="results" style="width: 75%;">
			<tr class="resultstitle">
				<td id="noborder"></td>
				<td class="textcenter">Mail Address</td>
				<td class="textcenter">Disabled</td>
			</tr>
<?php
			$sql = "SELECT ID, MailAddress, Disabled FROM ${DB_TABLE_PREFIX}distribution_groups ORDER BY MailAddress";
			$res = $db->query($sql);
			
			while ($row = $res->fetchObject()) {
?>
				<tr class="resultsitem">
					<td><input type="radio" name="postfix_group_id" value="<?php echo $row->id ?>" /></td>
					<td><?php echo $row->mailaddress ?></td>
					<td class="textcenter"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
				</tr>
<?php
			}
			$res->closeCursor();
?>
		</table>
	</form>
<?php



printFooter();

# vim: ts=4
?>
