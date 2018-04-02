<?php
# Module: CheckHelo change
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
			"Back to HELO checks" => "checkhelo-main.php"
		),
));



# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a helo check was selected
	if (isset($_POST['checkhelo_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
			SELECT 
				${DB_TABLE_PREFIX}checkhelo.ID, ${DB_TABLE_PREFIX}checkhelo.PolicyID, ${DB_TABLE_PREFIX}checkhelo.Name, 
			
				${DB_TABLE_PREFIX}checkhelo.UseBlacklist, ${DB_TABLE_PREFIX}checkhelo.BlacklistPeriod, 

				${DB_TABLE_PREFIX}checkhelo.UseHRP, ${DB_TABLE_PREFIX}checkhelo.HRPPeriod, ${DB_TABLE_PREFIX}checkhelo.HRPLimit,
				
				${DB_TABLE_PREFIX}checkhelo.RejectInvalid, ${DB_TABLE_PREFIX}checkhelo.RejectIP, ${DB_TABLE_PREFIX}checkhelo.RejectUnresolvable,

				${DB_TABLE_PREFIX}checkhelo.Comment, 
				${DB_TABLE_PREFIX}checkhelo.Disabled,
				
				${DB_TABLE_PREFIX}policies.Name AS PolicyName
				
			FROM 
				${DB_TABLE_PREFIX}checkhelo, ${DB_TABLE_PREFIX}policies 

			WHERE 
				${DB_TABLE_PREFIX}checkhelo.ID = ?
				AND ${DB_TABLE_PREFIX}policies.ID = ${DB_TABLE_PREFIX}checkhelo.PolicyID
			");
?>
		<p class="pageheader">Update HELO/EHLO Check</p>

		<form action="checkhelo-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="checkhelo_id" value="<?php echo $_POST['checkhelo_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['checkhelo_id']));

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
					<td class="entrytitle">Name</td>
					<td class="oldval"><?php echo $row->name ?></td>
					<td><input type="text" name="checkhelo_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Link to policy</td>
					<td class="oldval"><?php echo $row->policyname ?></td>
					<td>
						<select name="checkhelo_policyid">
							<option value="">--</option>
<?php
							$res = $db->query("SELECT ID, Name FROM ${DB_TABLE_PREFIX}policies ORDER BY Name");
							while ($row2 = $res->fetchObject()) {
?>
								<option value="<?php echo $row2->id ?>" ><?php echo $row2->name ?></option>
<?php
							}
							$res->closeCursor();
?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Blacklisting</td>
				</tr>
				<tr>
					<td class="entrytitle">Use Blacklist</td>
					<td class="oldval"><?php 
							switch ($row->useblacklist) {
								case null:
									echo "Inherit";
									break;
								case 1:
									echo "Yes";
									break;
								case 0:
									echo "No";
									break;
								default:
									echo "UNKNOWN";
									break;
							}
					?></td>
					<td>
						<select name="checkhelo_useblacklist">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Yes</option>
							<option value="3">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Blacklist Period
						<?php tooltip('checkhelo_blacklist_period'); ?>
					</td>
					<td class="oldval"><?php echo is_null($row->blacklistperiod) ? '*inherited*' : $row->blacklistperiod ?></td>
					<td>
						<input type="text" name="checkhelo_blacklistperiod" />
						<select name="checkhelo_blacklistperiod_m">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Overwrite</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Randomization Prevention</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Use HRP
					</td>
					<td class="oldval"><?php 
							switch ($row->usehrp) {
								case null:
									echo "Inherit";
									break;
								case 1:
									echo "Yes";
									break;
								case 0:
									echo "No";
									break;
								default:
									echo "UNKNOWN";
									break;
							}
					?></td>
					<td>
						<select name="checkhelo_usehrp">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Yes</option>
							<option value="3">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						HRP Period
						<?php tooltip('checkhelo_blacklist_hrpperiod'); ?>
					</td>
					<td class="oldval"><?php echo is_null($row->hrpperiod) ? '*inherited*' : $row->hrpperiod ?></td>
					<td>
						<input type="text" name="checkhelo_hrpperiod" />
						<select name="checkhelo_hrpperiod_m">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Overwrite</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						HRP Limit
						<?php tooltip('checkhelo_blacklist_hrplimit'); ?>
					</td>
					<td class="oldval"><?php echo is_null($row->hrplimit) ? '*inherited*' : $row->hrplimit ?></td>
					<td>
						<input type="text" name="checkhelo_hrplimit" />
						<select name="checkhelo_hrplimit_m">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Overwrite</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Reject (RFC non-compliance)</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Reject Invalid
						<?php tooltip('checkhelo_rejectinvalid'); ?>
					</td>
					<td class="oldval"><?php 
							switch ($row->rejectinvalid) {
								case null:
									echo "Inherit";
									break;
								case 1:
									echo "Yes";
									break;
								case 0:
									echo "No";
									break;
								default:
									echo "UNKNOWN";
									break;
							}
					?></td>
					<td>
						<select name="checkhelo_rejectinvalid">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Yes</option>
							<option value="3">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Reject non-literal IP
						<?php tooltip('checkhelo_rejectip'); ?>
					</td>
					<td class="oldval"><?php 
							switch ($row->rejectip) {
								case null:
									echo "Inherit";
									break;
								case 1:
									echo "Yes";
									break;
								case 0:
									echo "No";
									break;
								default:
									echo "UNKNOWN";
									break;
							}
					?></td>
					<td>
						<select name="checkhelo_rejectip">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Yes</option>
							<option value="3">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Reject Unresolvable
						<?php tooltip('checkhelo_rejectunresolv'); ?>
					</td>
					<td class="oldval"><?php 
							switch ($row->rejectunresolvable) {
								case null:
									echo "Inherit";
									break;
								case 1:
									echo "Yes";
									break;
								case 0:
									echo "No";
									break;
								default:
									echo "UNKNOWN";
									break;
							}
					?></td>
					<td>
						<select name="checkhelo_rejectunresolvable">
							<option value="">--</option>
							<option value="1">Inherit</option>
							<option value="2">Yes</option>
							<option value="3">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">&nbsp;</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="checkhelo_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="checkhelo_disabled">
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
		<div class="warning">No HELO/EHLO check selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">HELO/EHLO Update Results</p>
<?php
	$updates = array();

	# Process all our options below
	if (!empty($_POST['checkhelo_policyid'])) {
		array_push($updates,"PolicyID = ".$db->quote($_POST['checkhelo_policyid']));
	}

	if (!empty($_POST['checkhelo_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['checkhelo_name']));
	}

	if (!empty($_POST['checkhelo_useblacklist'])) {
		if ($_POST['checkhelo_useblacklist'] == "1") {
			$useblacklist = null;
		} elseif ($_POST['checkhelo_useblacklist'] == "2") {
			$useblacklist = 1;
		} elseif ($_POST['checkhelo_useblacklist'] == "3") {
			$useblacklist = 0;
		}
		array_push($updates,"UseBlacklist = ".$db->quote($useblacklist));
	}
	if (!empty($_POST['checkhelo_blacklistperiod_m'])) {
		if ($_POST['checkhelo_blacklistperiod_m'] == "1") {
			$blacklistperiod = null;
		} elseif ($_POST['checkhelo_blacklistperiod_m'] == "2") {
			$blacklistperiod = $_POST['checkhelo_blacklistperiod'];
		}
		array_push($updates,"BlacklistPeriod = ".$db->quote($blacklistperiod));
	}

	if (!empty($_POST['checkhelo_usehrp'])) {
		if ($_POST['checkhelo_usehrp'] == "1") {
			$usehrp = null;
		} elseif ($_POST['checkhelo_usehrp'] == "2") {
			$usehrp = 1;
		} elseif ($_POST['checkhelo_usehrp'] == "3") {
			$usehrp = 0;
		}
		array_push($updates,"UseHRP = ".$db->quote($usehrp));
	}
	if (!empty($_POST['checkhelo_hrpperiod_m'])) {
		if ($_POST['checkhelo_hrpperiod_m'] == "1") {
			$hrpperiod = null;
		} elseif ($_POST['checkhelo_hrpperiod_m'] == "2") {
			$hrpperiod = $_POST['checkhelo_hrpperiod'];
		}
		array_push($updates,"HRPPeriod = ".$db->quote($hrpperiod));
	}
	if (!empty($_POST['checkhelo_hrplimit_m'])) {
		if ($_POST['checkhelo_hrplimit_m'] == "1") {
			$hrplimit = null;
		} elseif ($_POST['checkhelo_hrplimit_m'] == "2") {
			$hrplimit = $_POST['checkhelo_hrplimit'];
		}
		array_push($updates,"HRPLimit = ".$db->quote($hrplimit));
	}

	if (!empty($_POST['checkhelo_rejectinvalid'])) {
		if ($_POST['checkhelo_rejectinvalid'] == "1") {
			$rejectinvalid = null;
		} elseif ($_POST['checkhelo_rejectinvalid'] == "2") {
			$rejectinvalid = 1;
		} elseif ($_POST['checkhelo_rejectinvalid'] == "3") {
			$rejectinvalid = 0;
		}
		array_push($updates,"RejectInvalid = ".$db->quote($rejectinvalid));
	}

	if (!empty($_POST['checkhelo_rejectip'])) {
		if ($_POST['checkhelo_rejectip'] == "1") {
			$rejectip = null;
		} elseif ($_POST['checkhelo_rejectip'] == "2") {
			$rejectip = 1;
		} elseif ($_POST['checkhelo_rejectip'] == "3") {
			$rejectip = 0;
		}
		array_push($updates,"RejectIP = ".$db->quote($rejectip));
	}

	if (!empty($_POST['checkhelo_rejectunresolvable'])) {
		if ($_POST['checkhelo_rejectunresolvable'] == "1") {
			$rejectunresolvable = null;
		} elseif ($_POST['checkhelo_rejectunresolvable'] == "2") {
			$rejectunresolvable = 1;
		} elseif ($_POST['checkhelo_rejectunresolvable'] == "3") {
			$rejectunresolvable = 0;
		}
		array_push($updates,"RejectUnresolvable = ".$db->quote($rejectunresolvable));
	}

	if (!empty($_POST['checkhelo_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['checkhelo_comment']));
	}
	if (isset($_POST['checkhelo_disabled']) && $_POST['checkhelo_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['checkhelo_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}checkhelo SET $updateStr WHERE ID = ".$db->quote($_POST['checkhelo_id']));
		if ($res) {
?>
			<div class="notice">HELO/EHLO check updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating HELO/EHLO check!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to HELO/EHLO check</div>
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
