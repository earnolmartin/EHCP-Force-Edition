<?php
# Postfix mailboxes
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
	<p class="pageheader">Mailboxes</p>

	<form id="main_form" action="postfix-mailboxes-main.php" method="post">

		<div class="textcenter">
			Action
			<select id="main_form_action" name="frmaction" 
					onchange="
						var myform = document.getElementById('main_form');
						var myobj = document.getElementById('main_form_action');

						if (myobj.selectedIndex == 2) {
							myform.action = 'postfix-mailboxes-add.php';
						} else if (myobj.selectedIndex == 4) {
							myform.action = 'postfix-mailboxes-change.php';
						} else if (myobj.selectedIndex == 5) {
							myform.action = 'postfix-mailboxes-delete.php';
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
				<td class="textcenter">Mailbox</td>
				<td class="textcenter">Quota</td>
				<td class="textcenter">Name</td>
				<td class="textcenter">Disabled</td>
			</tr>
<?php
			$sql = "SELECT ID, Mailbox, Quota, Name, Disabled FROM ${DB_TABLE_PREFIX}mailboxes ORDER BY Mailbox";
			$res = $db->query($sql);

			while ($row = $res->fetchObject()) {
?>
				<tr class="resultsitem">
					<td><input type="radio" name="postfix_mailbox_id" value="<?php echo $row->id ?>" /></td>
					<td><?php echo $row->mailbox ?></td>
					<td><?php echo $row->quota ?></td>
					<td><?php echo $row->name ?></td>
					<td class="textcenter"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
				</tr>
<?php
			}
?>
		</table>
	</form>
<?php



printFooter();

# vim: ts=4
?>
