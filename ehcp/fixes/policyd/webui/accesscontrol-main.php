<?php
# Module: AccessControl
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
));

# If we have no action, display list
if (!isset($_POST['frmaction']))
{
?>
	<p class="pageheader">Access Control List</p>

	<form id="main_form" action="accesscontrol-main.php" method="post">

		<div class="textcenter">
			Action
			<select id="main_form_action" name="frmaction" 
					onchange="
						var myform = document.getElementById('main_form');
						var myobj = document.getElementById('main_form_action');

						if (myobj.selectedIndex == 2) {
							myform.action = 'accesscontrol-add.php';
						} else if (myobj.selectedIndex == 4) {
							myform.action = 'accesscontrol-change.php';
						} else if (myobj.selectedIndex == 5) {
							myform.action = 'accesscontrol-delete.php';
						}

						myform.submit();
					">
			 
				<option selected="selected">select action</option>
				<option disabled="disabled"> - - - - - - - - - - - </option>
				<option value="add">Add</option>
				<option disabled="disabled"> - - - - - - - - - - - </option>
				<option value="change">Change</option>
				<option value="delete">Delete</option>
			</select> 
		</div>

		<p />

		<table class="results" style="width: 75%;">
			<tr class="resultstitle">
				<td id="noborder"></td>
				<td class="textcenter">Policy</td>
				<td class="textcenter">Name</td>
				<td class="textcenter">Verdict</td>
				<td class="textcenter">Data</td>
				<td class="textcenter">Disabled</td>
			</tr>
<?php
			$sql = "
					SELECT 
						${DB_TABLE_PREFIX}access_control.ID, ${DB_TABLE_PREFIX}access_control.Name, 
						${DB_TABLE_PREFIX}access_control.Verdict, ${DB_TABLE_PREFIX}access_control.Data, 
						${DB_TABLE_PREFIX}access_control.Disabled,
						${DB_TABLE_PREFIX}policies.Name AS PolicyName

					FROM 
						${DB_TABLE_PREFIX}access_control, ${DB_TABLE_PREFIX}policies

					WHERE
						${DB_TABLE_PREFIX}policies.ID = ${DB_TABLE_PREFIX}access_control.PolicyID

					ORDER BY 
						${DB_TABLE_PREFIX}policies.Name
			";
			$res = $db->query($sql);

			while ($row = $res->fetchObject()) {
?>
				<tr class="resultsitem">
					<td><input type="radio" name="accesscontrol_id" value="<?php echo $row->id ?>" /></td>
					<td><?php echo $row->policyname ?></td>
					<td><?php echo $row->name ?></td>
					<td><?php echo $row->verdict ?></td>
					<td><?php echo $row->data ?></td>
					<td class="textcenter"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
				</tr>
<?php
			}
			$res->closeCursor();
?>
		</table>
	</form>
<?php



}


printFooter();


# vim: ts=4
?>
