<?php
# Postfix transport add
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
			"Back to Transports" => "postfix-transports-main.php",
		),
));


if ($_POST['frmaction'] == "add")  {
?>
	<p class="pageheader">Add Postfix Transport</p>
<?php
?>
		<form method="post" action="postfix-transports-add.php">
			<div>
				<input type="hidden" name="frmaction" value="add2" />
			</div>
			<table class="entry">
				<tr>
					<td class="entrytitle">Domain Name</td>
					<td><input type="text" name="postfix_transport_domainname" /></td>
				</tr>
				<tr>
					<td class="entrytitle">
						Type
						<?php tooltip('postfix_transport_type'); ?>
					</td>
					<td>
						<select name="postfix_transport_type" id="postfix_transport_type"
								onchange="
									var myobjs = document.getElementById('postfix_transport_type');
									var myobji = document.getElementById('postfix_transport_data');

									if (myobjs.selectedIndex == 0) {
										myobji.disabled = true;
										myobji.value = 'n/a';
									} else if (myobjs.selectedIndex != 0) {
										myobji.disabled = false;
										myobji.value = 'server hostname here';
									}
						">
							<option value="0">Virtual</option>
							<option value="1">SMTP</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">Data</td>
					<td><input type="text" name="postfix_transport_data" id="postfix_transport_data" disabled="disabled" value="n/a" /></td>
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
	<p class="pageheader">Postfix Transport Add Results</p>

<?php

	$stmt = $db->prepare("INSERT INTO ${DB_TABLE_PREFIX}transports (DomainName,Type,Transport,PTransport,Disabled) VALUES (?,?,?,?,0)");

	# virtual
	if ($_POST['postfix_transport_type'] == "0") {
		$transport = $_POST['postfix_transport_domainname'];
		$ptransport = "virtual:$transport";
	
	# smtp
	} elseif ($_POST['postfix_transport_type'] == "1") {
		$transport = $_POST['postfix_transport_data'];
		$ptransport = "smtp:$transport";
	}

	$res = $stmt->execute(array(
		$_POST['postfix_transport_domainname'],
		$_POST['postfix_transport_type'],
		$transport,
		$ptransport
	));
	if ($res) {
?>
		<div class="notice">Postfix transport created</div>
<?php
	} else {
?>
		<div class="warning">Failed to create Postfix transport</div>
		<div class="warning"><?php print_r($stmt->errorInfo()) ?></div>
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
