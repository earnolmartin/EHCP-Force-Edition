<?php
# Postfix mailbox add
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
			"Back to Mailboxes" => "postfix-mailboxes-main.php",
		),
));


if ($_POST['frmaction'] == "add")  {
?>
	<p class="pageheader">Add Mailbox</p>
<?php
?>
		<form method="post" action="postfix-mailboxes-add.php">
			<div>
				<input type="hidden" name="frmaction" value="add2" />
			</div>
			<table class="entry">
				<tr>
					<td class="entrytitle">Email Address</td>
					<td>
						<input type="text" size="8" name="postfix_mailbox_address" /> @
						<select name="postfix_transport_id">
<?php
							$sql = "SELECT ID, DomainName FROM ${DB_TABLE_PREFIX}transports WHERE Disabled = 0 ORDER BY DomainName";
							$res = $db->query($sql);

							while ($row = $res->fetchObject()) {
?>
								<option value="<?php echo $row->id ?>">
									<?php echo $row->domainname ?>
								</option>
<?php
							}
							$res->closeCursor();
?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">Name</td>
					<td><input type="text" name="postfix_mailbox_name" id="postfix_mailbox_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Password</td>
					<td><input type="text" name="postfix_mailbox_password" id="postfix_mailbox_password" /></td>
				</tr>
				<tr>
					<td class="entrytitle">
						Quota (in Mbyte)
						<?php tooltip('postfix_mailbox_quota'); ?>
					</td>
					<td><input type="text" name="postfix_mailbox_quota" size="5" id="postfix_mailbox_quota" /> (0 = unlimited)</td>
				</tr>
				<tr>
					<td class="entrytitle">
						BCC
						<?php tooltip('postfix_mailbox_bcc'); ?>
					</td>
					<td><input type="text" name="postfix_mailbox_bcc" id="postfix_mailbox_bcc" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Comment</td>
					<td><textarea name="postfix_mailbox_comment" cols="40" rows="5"></textarea></td>
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
	<p class="pageheader">Mailbox Add Results</p>

<?php
	# Prepare statement
	$stmt = $db->prepare("SELECT ID, DomainName, Type, Transport, Disabled FROM ${DB_TABLE_PREFIX}transports WHERE ID = ?");
	$res = $stmt->execute(array($_POST['postfix_transport_id']));
	$row = $stmt->fetchObject();

	$mailbox = $_POST['postfix_mailbox_address'] . '@' . $row->domainname;

	$stmt = $db->prepare("
		INSERT INTO ${DB_TABLE_PREFIX}mailboxes 
			(TransportID,Address,Mailbox,Password,Quota,Name,BCC,Comment,Disabled) 
		VALUES 
			(?,?,?,?,?,?,?,?,0)
	");

	# Encrypt password
	$password = "{MD5}".base64_encode(pack("H*", md5($_POST['postfix_mailbox_password'])));

	$res = $stmt->execute(array(
		$_POST['postfix_transport_id'],
		$_POST['postfix_mailbox_address'],
		$mailbox,
		$password,
		$_POST['postfix_mailbox_quota'],
		$_POST['postfix_mailbox_name'],
		$_POST['postfix_mailbox_bcc'],
		$_POST['postfix_mailbox_comment'],
	));


	if ($res) {
?>
		<div class="notice">Mailbox created</div>
<?php
	} else {
?>
		<div class="warning">Failed to create mailbox</div>
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
