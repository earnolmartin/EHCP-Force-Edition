<?php
# Module: CheckHelo (blacklisting)
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
	<p class="pageheader">HELO/EHLO Blacklistings</p>

	<form id="main_form" action="checkhelo-main.php" method="post">

		<div class="textcenter">
			Action
			<select id="main_form_action" name="frmaction" 
					onchange="
						var myform = document.getElementById('main_form');
						var myobj = document.getElementById('main_form_action');

						if (myobj.selectedIndex == 2) {
							myform.action = 'checkhelo-blacklist-add.php';
						} else if (myobj.selectedIndex == 4) {
							myform.action = 'checkhelo-blacklist-change.php';
						} else if (myobj.selectedIndex == 5) {
							myform.action = 'checkhelo-blacklist-delete.php';
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
				<td class="textcenter">HELO/EHLO</td>
				<td class="textcenter">Disabled</td>
			</tr>
<?php
			$sql = "
					SELECT 
						ID, Helo, Disabled

					FROM 
						${DB_TABLE_PREFIX}checkhelo_blacklist

					ORDER BY 
						Helo
			";
			$res = $db->query($sql);
			while ($row = $res->fetchObject()) {
?>
				<tr class="resultsitem">
					<td><input type="radio" name="blacklist_id" value="<?php echo $row->id ?>" /></td>
					<td><?php echo $row->helo ?></td>
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
