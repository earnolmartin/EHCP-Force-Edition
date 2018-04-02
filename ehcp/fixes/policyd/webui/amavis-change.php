<?php
# Module: Amavis change
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
			"Back to Amavis" => "amavis-main.php"
		),
));

# Process an option
function process_post_option($name,$option) {
	global $db;

	$results = array();

	# Inherit
	if ($option == 0) {
		array_push($results,"$name = NULL");
		array_push($results,"${name}_m = ".$db->quote(0));
	# Explicit yes
	} elseif ($option == 1) {
		array_push($results,"$name = ".$db->quote(1));
		array_push($results,"${name}_m = ".$db->quote(2));
	# Explicit no
	} elseif ($option == 2) {
		array_push($results,"$name = ".$db->quote(0));
		array_push($results,"${name}_m = ".$db->quote(2));
	}

	return $results;
};


# Process a value
function process_post_value($name,$option,$value) {
	global $db;

	$results = array();

	# Inherit
	if ($option == 0) {
		array_push($results,"$name = NULL");
		array_push($results,"${name}_m = ".$db->quote(0));
	# Override
	} elseif ($option == 2) {
		array_push($results,"$name = ".$db->quote($value));
		array_push($results,"${name}_m = ".$db->quote(2));
	}

	return $results;
};


# Process a list of items
function process_post_list($name,$option,$value) {
	global $db;

	$results = array();

	# Inherit
	if ($option == 0) {
		array_push($results,"$name = NULL");
		array_push($results,"${name}_m = ".$db->quote(0));
	# Merge
	} elseif ($option == 1) {
		array_push($results,"$name = ".$db->quote($value));
		array_push($results,"${name}_m = ".$db->quote(1));
	# Override
	} elseif ($option == 2) {
		array_push($results,"$name = ".$db->quote($value));
		array_push($results,"${name}_m = ".$db->quote(2));
	}

	return $results;
};




# Make a pretty db option
function decode_db_option($option,$value)
{
	$ret = "unknown";

	if ($option == "0") {
		$ret = "Inherit";

	# Overwrite
	} elseif ($option == "2") {

		# Check value
		if ($value == "0") {
			$ret = "No";

		} elseif ($value == "1") {
			$ret = "Yes";
		}
	}

	return $ret;
}


# Make a pretty db value
function decode_db_value($option,$value)
{
	$ret = "unknown";

	if ($option == "0") {
		$ret = "Inherit";

	# Merge
	} elseif ($option == "1") {
		$ret = "Merge: $value";

	# Overwrite
	} elseif ($option == "2") {
		$ret = "Overwrite: $value";

	}

	return $ret;
}


# Display change screen
if ($_POST['frmaction'] == "change") {

	# Check a amavis rule was selected
	if (isset($_POST['amavis_id'])) {
		# Prepare statement
		$stmt = $db->prepare("
			SELECT 
				${DB_TABLE_PREFIX}amavis_rules.ID, ${DB_TABLE_PREFIX}amavis_rules.PolicyID, ${DB_TABLE_PREFIX}amavis_rules.Name, 
			
				${DB_TABLE_PREFIX}amavis_rules.bypass_virus_checks, ${DB_TABLE_PREFIX}amavis_rules.bypass_virus_checks_m,
				${DB_TABLE_PREFIX}amavis_rules.bypass_banned_checks, ${DB_TABLE_PREFIX}amavis_rules.bypass_banned_checks_m,
				${DB_TABLE_PREFIX}amavis_rules.bypass_spam_checks, ${DB_TABLE_PREFIX}amavis_rules.bypass_spam_checks_m,
				${DB_TABLE_PREFIX}amavis_rules.bypass_header_checks, ${DB_TABLE_PREFIX}amavis_rules.bypass_header_checks_m,

				${DB_TABLE_PREFIX}amavis_rules.spam_tag_level, ${DB_TABLE_PREFIX}amavis_rules.spam_tag_level_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_tag2_level, ${DB_TABLE_PREFIX}amavis_rules.spam_tag2_level_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_tag3_level, ${DB_TABLE_PREFIX}amavis_rules.spam_tag3_level_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_kill_level, ${DB_TABLE_PREFIX}amavis_rules.spam_kill_level_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_dsn_cutoff_level, ${DB_TABLE_PREFIX}amavis_rules.spam_dsn_cutoff_level_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_quarantine_cutoff_level, ${DB_TABLE_PREFIX}amavis_rules.spam_quarantine_cutoff_level_m,

				${DB_TABLE_PREFIX}amavis_rules.spam_modifies_subject, ${DB_TABLE_PREFIX}amavis_rules.spam_modifies_subject_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_tag_subject, ${DB_TABLE_PREFIX}amavis_rules.spam_tag_subject_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_tag2_subject, ${DB_TABLE_PREFIX}amavis_rules.spam_tag2_subject_m,
				${DB_TABLE_PREFIX}amavis_rules.spam_tag3_subject, ${DB_TABLE_PREFIX}amavis_rules.spam_tag3_subject_m,

				${DB_TABLE_PREFIX}amavis_rules.max_message_size, ${DB_TABLE_PREFIX}amavis_rules.max_message_size_m,
				${DB_TABLE_PREFIX}amavis_rules.banned_files, ${DB_TABLE_PREFIX}amavis_rules.banned_files_m,

				${DB_TABLE_PREFIX}amavis_rules.sender_whitelist, ${DB_TABLE_PREFIX}amavis_rules.sender_whitelist_m,
				${DB_TABLE_PREFIX}amavis_rules.sender_blacklist, ${DB_TABLE_PREFIX}amavis_rules.sender_blacklist_m,

				${DB_TABLE_PREFIX}amavis_rules.notify_admin_newvirus, ${DB_TABLE_PREFIX}amavis_rules.notify_admin_newvirus_m,
				${DB_TABLE_PREFIX}amavis_rules.notify_admin_virus, ${DB_TABLE_PREFIX}amavis_rules.notify_admin_virus_m,
				${DB_TABLE_PREFIX}amavis_rules.notify_admin_spam, ${DB_TABLE_PREFIX}amavis_rules.notify_admin_spam_m,
				${DB_TABLE_PREFIX}amavis_rules.notify_admin_banned_file, ${DB_TABLE_PREFIX}amavis_rules.notify_admin_banned_file_m,
				${DB_TABLE_PREFIX}amavis_rules.notify_admin_bad_header, ${DB_TABLE_PREFIX}amavis_rules.notify_admin_bad_header_m,

				${DB_TABLE_PREFIX}amavis_rules.quarantine_virus, ${DB_TABLE_PREFIX}amavis_rules.quarantine_virus_m,
				${DB_TABLE_PREFIX}amavis_rules.quarantine_spam, ${DB_TABLE_PREFIX}amavis_rules.quarantine_spam_m,
				${DB_TABLE_PREFIX}amavis_rules.quarantine_banned_file, ${DB_TABLE_PREFIX}amavis_rules.quarantine_banned_file_m,
				${DB_TABLE_PREFIX}amavis_rules.quarantine_bad_header, ${DB_TABLE_PREFIX}amavis_rules.quarantine_bad_header_m,
				
				${DB_TABLE_PREFIX}amavis_rules.bcc_to, ${DB_TABLE_PREFIX}amavis_rules.bcc_to_m,

				${DB_TABLE_PREFIX}amavis_rules.Comment, 
				${DB_TABLE_PREFIX}amavis_rules.Disabled,
				
				${DB_TABLE_PREFIX}policies.Name AS PolicyName
				
			FROM 
				${DB_TABLE_PREFIX}amavis_rules, ${DB_TABLE_PREFIX}policies 

			WHERE 
				${DB_TABLE_PREFIX}amavis_rules.ID = ?
				AND ${DB_TABLE_PREFIX}policies.ID = ${DB_TABLE_PREFIX}amavis_rules.PolicyID
			");
?>
		<p class="pageheader">Update Amavis Rule</p>

		<form action="amavis-change.php" method="post">
			<div>
				<input type="hidden" name="frmaction" value="change2" />
				<input type="hidden" name="amavis_id" value="<?php echo $_POST['amavis_id']; ?>" />
			</div>
<?php

			$res = $stmt->execute(array($_POST['amavis_id']));

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
					<td><input type="text" name="amavis_name" /></td>
				</tr>
				<tr>
					<td class="entrytitle">Link to policy</td>
					<td class="oldval"><?php echo $row->policyname ?></td>
					<td>
						<select name="amavis_policyid">
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
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Bypass Checks</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Virus Checks
						<?php tooltip('amavis_bypass_virus_checks'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_option($row->bypass_virus_checks_m,$row->bypass_virus_checks) ?></td>
					<td>
						<select name="amavis_bypass_virus_checks">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Yes</option>
							<option value="2">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Banned File Checks
						<?php tooltip('amavis_bypass_banned_checks'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_option($row->bypass_banned_checks_m,$row->bypass_banned_checks) ?></td>
					<td>
						<select name="amavis_bypass_banned_checks">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Yes</option>
							<option value="2">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Spam Checks
						<?php tooltip('amavis_bypass_spam_checks'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_option($row->bypass_spam_checks_m,$row->bypass_spam_checks) ?></td>
					<td>
						<select name="amavis_bypass_spam_checks">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Yes</option>
							<option value="2">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Header Checks
						<?php tooltip('amavis_bypass_header_checks'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_option($row->bypass_header_checks_m,$row->bypass_header_checks) ?></td>
					<td>
						<select name="amavis_bypass_header_checks">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Yes</option>
							<option value="2">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Anti-spam Settings</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Tag Level
						<?php tooltip('amavis_spam_tag_level'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_tag_level_m,$row->spam_tag_level) ?></td>
					<td>
						<select name="amavis_spam_tag_level_mode" id="amavis_spam_tag_level_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_tag_level_mode');
								var myobji = document.getElementById('amavis_spam_tag_level');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '0.0';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_tag_level" size="6" id="amavis_spam_tag_level" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Tag2 Level
						<?php tooltip('amavis_spam_tag2_level'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_tag2_level_m,$row->spam_tag2_level) ?></td>
					<td>
						<select name="amavis_spam_tag2_level_mode" id="amavis_spam_tag2_level_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_tag2_level_mode');
								var myobji = document.getElementById('amavis_spam_tag2_level');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '5.0';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_tag2_level" size="6" id="amavis_spam_tag2_level" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Tag3 Level
						<?php tooltip('amavis_spam_tag3_level'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_tag3_level_m,$row->spam_tag3_level) ?></td>
					<td>
						<select name="amavis_spam_tag3_level_mode" id="amavis_spam_tag3_level_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_tag3_level_mode');
								var myobji = document.getElementById('amavis_spam_tag3_level');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '7.5';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_tag3_level" size="6" id="amavis_spam_tag3_level" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Kill Level
						<?php tooltip('amavis_spam_kill_level'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_kill_level_m,$row->spam_kill_level) ?></td>
					<td>
						<select name="amavis_spam_kill_level_mode" id="amavis_spam_kill_level_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_kill_level_mode');
								var myobji = document.getElementById('amavis_spam_kill_level');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '7.5';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_kill_level" size="6" id="amavis_spam_kill_level" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						DSN Cutoff Level
						<?php tooltip('amavis_spam_dsn_cutoff_level'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_dsn_cutoff_level_m,$row->spam_dsn_cutoff_level) ?></td>
					<td>
						<select name="amavis_spam_dsn_cutoff_level_mode" id="amavis_spam_dsn_cutoff_level_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_dsn_cutoff_level_mode');
								var myobji = document.getElementById('amavis_spam_dsn_cutoff_level');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '7.5';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_dsn_cutoff_level" size="6" id="amavis_spam_dsn_cutoff_level" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Quarantine Cutoff Level
						<?php tooltip('amavis_spam_quarantine_cutoff_level'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_quarantine_cutoff_level_m,$row->spam_quarantine_cutoff_level) ?></td>
					<td>
						<select name="amavis_spam_quarantine_cutoff_level_mode" id="amavis_spam_quarantine_cutoff_level_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_quarantine_cutoff_level_mode');
								var myobji = document.getElementById('amavis_spam_quarantine_cutoff_level');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '15.0';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_quarantine_cutoff_level" size="6" id="amavis_spam_quarantine_cutoff_level" 
								disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Modify Subject
						<?php tooltip('amavis_spam_modifies_subject'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_option($row->spam_modifies_subject_m,$row->spam_modifies_subject) ?></td>
					<td>
						<select name="amavis_spam_modifies_subject">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Yes</option>
							<option value="2">No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Tag Subject
						<?php tooltip('amavis_spam_tag_subject'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_tag_subject_m,$row->spam_tag_subject) ?></td>
					<td>
						<select name="amavis_spam_tag_subject_mode" id="amavis_spam_tag_subject_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_tag_subject_mode');
								var myobji = document.getElementById('amavis_spam_tag_subject');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_tag_subject" id="amavis_spam_tag_subject" 
								disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Tag2 Subject
						<?php tooltip('amavis_spam_tag2_subject'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_tag2_subject_m,$row->spam_tag2_subject) ?></td>
					<td>
						<select name="amavis_spam_tag2_subject_mode" id="amavis_spam_tag2_subject_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_tag2_subject_mode');
								var myobji = document.getElementById('amavis_spam_tag2_subject');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_tag2_subject" id="amavis_spam_tag2_subject" 
								disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Tag3 Subject
						<?php tooltip('amavis_spam_tag3_subject'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->spam_tag3_subject_m,$row->spam_tag3_subject) ?></td>
					<td>
						<select name="amavis_spam_tag3_subject_mode" id="amavis_spam_tag3_subject_mode"
							onchange="
								var myobjs = document.getElementById('amavis_spam_tag3_subject_mode');
								var myobji = document.getElementById('amavis_spam_tag3_subject');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_spam_tag3_subject" id="amavis_spam_tag3_subject" 
								disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">General Checks</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Max Message Size (Kbyte)
						<?php tooltip('amavis_max_message_size'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->max_message_size_m,$row->max_message_size) ?></td>
					<td>
						<select name="amavis_max_message_size_mode" id="amavis_max_message_size_mode"
							onchange="
								var myobjs = document.getElementById('amavis_max_message_size_mode');
								var myobji = document.getElementById('amavis_max_message_size');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_max_message_size" id="amavis_max_message_size" 
								disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">
						Banned Files	
						<?php tooltip('amavis_banned_files'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->banned_files_m,$row->banned_files) ?></td>
					<td>
						<select name="amavis_banned_files_mode" id="amavis_banned_files_mode"
							onchange="
								var myobjs = document.getElementById('amavis_banned_files_mode');
								var myobji = document.getElementById('amavis_banned_files');

								if (myobjs.selectedIndex < 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex >= 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Merge</option>
							<option value="2">Override</option>
						</select>
						<br />
						<textarea name="amavis_banned_files" id="amavis_banned_files" disabled="disabled" cols="40" rows="5">n/a</textarea>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Whitelist &amp; Blacklist</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">
						Sender Whitelist
						<?php tooltip('amavis_sender_whitelist'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->sender_whitelist_m,$row->sender_whitelist) ?></td>
					<td class="texttop">
						<select name="amavis_sender_whitelist_mode" id="amavis_sender_whitelist_mode"
							onchange="
								var myobjs = document.getElementById('amavis_sender_whitelist_mode');
								var myobji = document.getElementById('amavis_sender_whitelist');

								if (myobjs.selectedIndex < 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex >= 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Merge</option>
							<option value="2">Override</option>
						</select>
						<br />
						<textarea name="amavis_sender_whitelist" id="amavis_sender_whitelist" disabled="disabled" cols="40" rows="5">n/a</textarea>
					</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">
						Sender Blacklist
						<?php tooltip('amavis_sender_blacklist'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->sender_blacklist_m,$row->sender_blacklist) ?></td>
					<td class="texttop">
						<select name="amavis_sender_blacklist_mode" id="amavis_sender_blacklist_mode"
							onchange="
								var myobjs = document.getElementById('amavis_sender_blacklist_mode');
								var myobji = document.getElementById('amavis_sender_blacklist');

								if (myobjs.selectedIndex < 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex >= 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="1">Merge</option>
							<option value="2">Override</option>
						</select>
						<br />
						<textarea name="amavis_sender_blacklist" id="amavis_sender_blacklist" disabled="disabled" cols="40" rows="5">n/a</textarea>
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Admin Notifications</td>
				</tr>
				<tr>
					<td class="entrytitle">
						New Virus
						<?php tooltip('amavis_notify_admin_newvirus'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->notify_admin_newvirus_m,$row->notify_admin_newvirus) ?></td>
					<td>
						<select name="amavis_notify_admin_newvirus_mode" id="amavis_notify_admin_newvirus_mode"
							onchange="
								var myobjs = document.getElementById('amavis_notify_admin_newvirus_mode');
								var myobji = document.getElementById('amavis_notify_admin_newvirus');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_notify_admin_newvirus" id="amavis_notify_admin_newvirus" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Virus
						<?php tooltip('amavis_notify_admin_virus'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->notify_admin_virus_m,$row->notify_admin_virus) ?></td>
					<td>
						<select name="amavis_notify_admin_virus_mode" id="amavis_notify_admin_virus_mode"
							onchange="
								var myobjs = document.getElementById('amavis_notify_admin_virus_mode');
								var myobji = document.getElementById('amavis_notify_admin_virus');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_notify_admin_virus" id="amavis_notify_admin_virus" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Spam
						<?php tooltip('amavis_notify_admin_spam'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->notify_admin_spam_m,$row->notify_admin_spam) ?></td>
					<td>
						<select name="amavis_notify_admin_spam_mode" id="amavis_notify_admin_spam_mode"
							onchange="
								var myobjs = document.getElementById('amavis_notify_admin_spam_mode');
								var myobji = document.getElementById('amavis_notify_admin_spam');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_notify_admin_spam" id="amavis_notify_admin_spam" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Banned File
						<?php tooltip('amavis_notify_admin_banned_file'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->notify_admin_banned_file_m,$row->notify_admin_banned_file) ?></td>
					<td>
						<select name="amavis_notify_admin_banned_file_mode" id="amavis_notify_admin_banned_file_mode"
							onchange="
								var myobjs = document.getElementById('amavis_notify_admin_banned_file_mode');
								var myobji = document.getElementById('amavis_notify_admin_banned_file');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_notify_admin_banned_file" id="amavis_notify_admin_banned_file" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Bad Header
						<?php tooltip('amavis_notify_admin_bad_header'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->notify_admin_bad_header_m,$row->notify_admin_bad_header) ?></td>
					<td>
						<select name="amavis_notify_admin_bad_header_mode" id="amavis_notify_admin_bad_header_mode"
							onchange="
								var myobjs = document.getElementById('amavis_notify_admin_bad_header_mode');
								var myobji = document.getElementById('amavis_notify_admin_bad_header');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_notify_admin_bad_header" id="amavis_notify_admin_bad_header" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Quarantine</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Virus
						<?php tooltip('amavis_quarantine_virus'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->quarantine_virus_m,$row->quarantine_virus) ?></td>
					<td>
						<select name="amavis_quarantine_virus_mode" id="amavis_quarantine_virus_mode"
							onchange="
								var myobjs = document.getElementById('amavis_quarantine_virus_mode');
								var myobji = document.getElementById('amavis_quarantine_virus');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_quarantine_virus" id="amavis_quarantine_virus" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Spam
						<?php tooltip('amavis_quarantine_spam'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->quarantine_spam_m,$row->quarantine_spam) ?></td>
					<td>
						<select name="amavis_quarantine_spam_mode" id="amavis_quarantine_spam_mode"
							onchange="
								var myobjs = document.getElementById('amavis_quarantine_spam_mode');
								var myobji = document.getElementById('amavis_quarantine_spam');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_quarantine_spam" id="amavis_quarantine_spam" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Banned File
						<?php tooltip('amavis_quarantine_banned_file'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->quarantine_banned_file_m,$row->quarantine_banned_file) ?></td>
					<td>
						<select name="amavis_quarantine_banned_file_mode" id="amavis_quarantine_banned_file_mode"
							onchange="
								var myobjs = document.getElementById('amavis_quarantine_banned_file_mode');
								var myobji = document.getElementById('amavis_quarantine_banned_file');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_quarantine_banned_file" id="amavis_quarantine_banned_file" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td class="entrytitle">
						Bad Header
						<?php tooltip('amavis_quarantine_bad_header'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->quarantine_bad_header_m,$row->quarantine_bad_header) ?></td>
					<td>
						<select name="amavis_quarantine_bad_header_mode" id="amavis_quarantine_bad_header_mode"
							onchange="
								var myobjs = document.getElementById('amavis_quarantine_bad_header_mode');
								var myobji = document.getElementById('amavis_quarantine_bad_header');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_quarantine_bad_header" id="amavis_quarantine_bad_header" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">Interception</td>
				</tr>
				<tr>
					<td class="entrytitle">
						BCC To
						<?php tooltip('amavis_bcc_to'); ?>
					</td>
					<td class="oldval"><?php echo decode_db_value($row->bcc_to_m,$row->bcc_to) ?></td>
					<td>
						<select name="amavis_bcc_to_mode" id="amavis_bcc_to_mode"
							onchange="
								var myobjs = document.getElementById('amavis_bcc_to_mode');
								var myobji = document.getElementById('amavis_bcc_to');
	
								if (myobjs.selectedIndex != 2) {
									myobji.disabled = true;
									myobji.value = 'n/a';
								} else if (myobjs.selectedIndex == 2) {
									myobji.disabled = false;
									myobji.value = '';
								}
						">
							<option value="">--</option>
							<option value="0">Inherit</option>
							<option value="2">Override</option>
						</select>
						<input type="text" name="amavis_bcc_to" id="amavis_bcc_to" disabled="disabled" value="n/a" />
					</td>
				</tr>
				<tr>
					<td colspan="3" class="textcenter" style="border-bottom: 1px dashed black;">&nbsp;</td>
				</tr>
				<tr>
					<td class="entrytitle texttop">Comment</td>
					<td class="oldval texttop"><?php echo $row->comment ?></td>
					<td><textarea name="amavis_comment" cols="40" rows="5"></textarea></td>
				</tr>
				<tr>
					<td class="entrytitle">Disabled</td>
					<td class="oldval"><?php echo $row->disabled ? 'yes' : 'no' ?></td>
					<td>
						<select name="amavis_disabled">
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
		<div class="warning">No Amavis rule selected</div>
<?php
	}
	
	
	
# SQL Updates
} elseif ($_POST['frmaction'] == "change2") {
?>
	<p class="pageheader">Amavis Rule Update Results</p>
<?php
	$updates = array();

	if (!empty($_POST['amavis_policyid'])) {
		array_push($updates,"PolicyID = ".$db->quote($_POST['amavis_policyid']));
	}
	if (!empty($_POST['amavis_name'])) {
		array_push($updates,"Name = ".$db->quote($_POST['amavis_name']));
	}

	# Bypass options
	if (isset($_POST['amavis_bypass_virus_checks']) && $_POST['amavis_bypass_virus_checks'] != "") {
		$res = process_post_option('bypass_virus_checks',$_POST['amavis_bypass_virus_checks']);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_bypass_banned_checks']) && $_POST['amavis_bypass_banned_checks'] != "") {
		$res = process_post_option('bypass_banned_checks',$_POST['amavis_bypass_banned_checks']);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_bypass_spam_checks']) && $_POST['amavis_bypass_spam_checks'] != "") {
		$res = process_post_option('bypass_spam_checks',$_POST['amavis_bypass_spam_checks']);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_bypass_header_checks']) && $_POST['amavis_bypass_header_checks'] != "") {
		$res = process_post_option('bypass_header_checks',$_POST['amavis_bypass_header_checks']);
		$updates = array_merge($updates,$res);
	}

	# Antispam level
	if (isset($_POST['amavis_spam_tag_level_mode']) && $_POST['amavis_spam_tag_level_mode'] != "") {
		$res = process_post_value('spam_tag_level',$_POST['amavis_spam_tag_level_mode'],
				isset($_POST['amavis_spam_tag_level']) ? $_POST['amavis_spam_tag_level'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_tag2_level_mode']) && $_POST['amavis_spam_tag2_level_mode'] != "") {
		$res = process_post_value('spam_tag2_level',$_POST['amavis_spam_tag2_level_mode'],
				isset($_POST['amavis_spam_tag2_level']) ? $_POST['amavis_spam_tag2_level'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_tag3_level_mode']) && $_POST['amavis_spam_tag3_level_mode'] != "") {
		$res = process_post_value('spam_tag3_level',$_POST['amavis_spam_tag3_level_mode'],
				isset($_POST['amavis_spam_tag3_level']) ? $_POST['amavis_spam_tag3_level'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_kill_level_mode']) && $_POST['amavis_spam_kill_level_mode'] != "") {
		$res = process_post_value('spam_kill_level',$_POST['amavis_spam_kill_level_mode'],
				isset($_POST['amavis_spam_kill_level']) ? $_POST['amavis_spam_kill_level'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_dsn_cutoff_level_mode']) && $_POST['amavis_spam_dsn_cutoff_level_mode'] != "") {
		$res = process_post_value('spam_dsn_cutoff_level',$_POST['amavis_spam_dsn_cutoff_level_mode'],
				isset($_POST['amavis_spam_dsn_cutoff_level']) ? $_POST['amavis_spam_dsn_cutoff_level'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_quarantine_cutoff_level_mode']) && $_POST['amavis_spam_quarantine_cutoff_level_mode'] != "") {
		$res = process_post_value('spam_quarantine_cutoff_level',$_POST['amavis_spam_quarantine_cutoff_level_mode'],
				isset($_POST['amavis_spam_quarantine_cutoff_level']) ? $_POST['amavis_spam_quarantine_cutoff_level'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	if (isset($_POST['amavis_spam_modifies_subject']) && $_POST['amavis_spam_modifies_subject'] != "") {
		$res = process_post_option('spam_modifies_subject',$_POST['amavis_spam_modifies_subject']);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_tag_subject_mode']) && $_POST['amavis_spam_tag_subject_mode'] != "") {
		$res = process_post_value('spam_tag_subject',$_POST['amavis_spam_tag_subject_mode'],
				isset($_POST['amavis_spam_tag_subject']) ? $_POST['amavis_spam_tag_subject'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_tag2_subject_mode']) && $_POST['amavis_spam_tag2_subject_mode'] != "") {
		$res = process_post_value('spam_tag2_subject',$_POST['amavis_spam_tag2_subject_mode'],
				isset($_POST['amavis_spam_tag2_subject']) ? $_POST['amavis_spam_tag2_subject'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_spam_tag3_subject_mode']) && $_POST['amavis_spam_tag3_subject_mode'] != "") {
		$res = process_post_value('spam_tag3_subject',$_POST['amavis_spam_tag3_subject_mode'],
				isset($_POST['amavis_spam_tag3_subject']) ? $_POST['amavis_spam_tag3_subject'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	# General
	if (isset($_POST['amavis_max_message_size_mode']) && $_POST['amavis_max_message_size_mode'] != "") {
		$res = process_post_value('max_message_size',$_POST['amavis_max_message_size_mode'],
				isset($_POST['amavis_max_message_size']) ? $_POST['amavis_max_message_size'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_banned_files_mode']) && $_POST['amavis_banned_files_mode'] != "") {
		$res = process_post_list('banned_files',$_POST['amavis_banned_files_mode'],
				isset($_POST['amavis_banned_files']) ? $_POST['amavis_banned_files'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	# Whitelist & blacklist
	if (isset($_POST['amavis_sender_whitelist_mode']) && $_POST['amavis_sender_whitelist_mode'] != "") {
		$res = process_post_list('sender_whitelist',$_POST['amavis_sender_whitelist_mode'],
				isset($_POST['amavis_sender_whitelist']) ? $_POST['amavis_sender_whitelist'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_sender_blacklist_mode']) && $_POST['amavis_sender_blacklist_mode'] != "") {
		$res = process_post_list('sender_blacklist',$_POST['amavis_sender_blacklist_mode'],
				isset($_POST['amavis_sender_blacklist']) ? $_POST['amavis_sender_blacklist'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	# Notifications
	if (isset($_POST['amavis_notify_admin_newvirus_mode']) && $_POST['amavis_notify_admin_newvirus_mode'] != "") {
		$res = process_post_value('notify_admin_newvirus',$_POST['amavis_notify_admin_newvirus_mode'],
				isset($_POST['amavis_notify_admin_newvirus']) ? $_POST['amavis_notify_admin_newvirus'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_notify_admin_virus_mode']) && $_POST['amavis_notify_admin_virus_mode'] != "") {
		$res = process_post_value('notify_admin_virus',$_POST['amavis_notify_admin_virus_mode'],
				isset($_POST['amavis_notify_admin_virus']) ? $_POST['amavis_notify_admin_virus'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_notify_admin_spam_mode']) && $_POST['amavis_notify_admin_spam_mode'] != "") {
		$res = process_post_value('notify_admin_spam',$_POST['amavis_notify_admin_spam_mode'],
				isset($_POST['amavis_notify_admin_spam']) ? $_POST['amavis_notify_admin_spam'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_notify_admin_banned_file_mode']) && $_POST['amavis_notify_admin_banned_file_mode'] != "") {
		$res = process_post_value('notify_admin_banned_file',$_POST['amavis_notify_admin_banned_file_mode'],
				isset($_POST['amavis_notify_admin_banned_file']) ? $_POST['amavis_notify_admin_banned_file'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_notify_admin_bad_header_mode']) && $_POST['amavis_notify_admin_bad_header_mode'] != "") {
		$res = process_post_value('notify_admin_bad_header',$_POST['amavis_notify_admin_bad_header_mode'],
				isset($_POST['amavis_notify_admin_bad_header']) ? $_POST['amavis_notify_admin_bad_header'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	# Quarantine
	if (isset($_POST['amavis_quarantine_virus_mode']) && $_POST['amavis_quarantine_virus_mode'] != "") {
		$res = process_post_value('quarantine_virus',$_POST['amavis_quarantine_virus_mode'],
				isset($_POST['amavis_quarantine_virus']) ? $_POST['amavis_quarantine_virus'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_quarantine_spam_mode']) && $_POST['amavis_quarantine_spam_mode'] != "") {
		$res = process_post_value('quarantine_spam',$_POST['amavis_quarantine_spam_mode'],
				isset($_POST['amavis_quarantine_spam']) ? $_POST['amavis_quarantine_spam'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_quarantine_banned_file_mode']) && $_POST['amavis_quarantine_banned_file_mode'] != "") {
		$res = process_post_value('quarantine_banned_file',$_POST['amavis_quarantine_banned_file_mode'],
				isset($_POST['amavis_quarantine_banned_file']) ? $_POST['amavis_quarantine_banned_file'] : ''
		);
		$updates = array_merge($updates,$res);
	}
	if (isset($_POST['amavis_quarantine_bad_header_mode']) && $_POST['amavis_quarantine_bad_header_mode'] != "") {
		$res = process_post_value('quarantine_bad_header',$_POST['amavis_quarantine_bad_header_mode'],
				isset($_POST['amavis_quarantine_bad_header']) ? $_POST['amavis_quarantine_bad_header'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	# Interception
	if (isset($_POST['amavis_bcc_to_mode']) && $_POST['amavis_bcc_to_mode'] != "") {
		$res = process_post_value('bcc_to',$_POST['amavis_bcc_to_mode'],
				isset($_POST['amavis_bcc_to']) ? $_POST['amavis_bcc_to'] : ''
		);
		$updates = array_merge($updates,$res);
	}

	# Whatever is left over
	if (!empty($_POST['amavis_comment'])) {
		array_push($updates,"Comment = ".$db->quote($_POST['amavis_comment']));
	}
	if (isset($_POST['amavis_disabled']) && $_POST['amavis_disabled'] != "") {
		array_push($updates ,"Disabled = ".$db->quote($_POST['amavis_disabled']));
	}

	# Check if we have updates
	if (sizeof($updates) > 0) {
		$updateStr = implode(', ',$updates);

		$res = $db->exec("UPDATE ${DB_TABLE_PREFIX}amavis_rules SET $updateStr WHERE ID = ".$db->quote($_POST['amavis_id']));
		if ($res) {
?>
			<div class="notice">Amavis rule updated</div>
<?php
		} else {
?>
			<div class="warning">Error updating Amavis rule!</div>
			<div class="warning"><?php print_r($db->errorInfo()) ?></div>
<?php
		}

	} else {
?>
		<div class="warning">No changes made to Amavis rule</div>
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
