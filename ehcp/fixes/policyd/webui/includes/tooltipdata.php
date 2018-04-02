<?php
# Tooltips
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

# Tooltip data
$tooltips['policy_priority'] = "Priority. Range 0-100. 0: highest, 100: lowest. Priorities are processed in an ascending fashion, any rule linked to a policy has the potential to inherit, overwrite or merge the previous one.";

$tooltips['policy_member_source'] = "Source: 'any' - Any source, '@domain' - From domain, 'user@domain' - From email address, 'a.b.c.d' - From IP address, 'a.b.c.d/e' - From a CIDR range, '%group' - From a group, '\$sasl_username' - From a SASL username, '\$*' would depict all SASL users.";
$tooltips['policy_member_destination'] = "Destination. 'any' - Any destination, '@domain' - To domain, 'user@domain' - To to full email address, '%group' - To a group.";

$tooltips['policy_group_member'] = "'@domain' - Domain, 'user@domain' - Full email address, 'a.b.c.d' - IP address, 'a.b.c.d/e' - CIDR range, '\$sasl_username' - From a SASL username, '\$*' would depict all SASL users.";

$tooltips['accesscontrol_verdict'] = "HOLD - Quarantine message. REJECT - Reject message. DISCARD - Drop message. FILTER - Filter message through mechanism. REDIRECT - Redirect message to another email address.";
$tooltips['accesscontrol_data'] = "Extra data to verdict the message with. For instance if the verdict is 'REJECT' you can set the data to 'Access Denied'.";

$tooltips['checkhelo_blacklist_period'] = "Period in seconds to keep blacklisted server for.";
$tooltips['checkhelo_blacklist_hrpperiod'] = "Period to look back to see how many HELO/EHLO's have been used, this in seconds.";
$tooltips['checkhelo_blacklist_hrplimit'] = "Maxmim HELO/EHLO's to receive from host before we blacklist them.";
$tooltips['checkhelo_rejectinvalid'] = "Reject HELO/EHLO's which are not standards compliant.";
$tooltips['checkhelo_rejectip'] = "Reject IP's if they are used as the HELO/EHLO. The RFC requirement is FQDN.";
$tooltips['checkhelo_rejectunresolv'] = "Reject unresolvable HELO/EHLO's, its an RFC requirement that the HELO/EHLO be a FQDN and resolvable.";

$tooltips['checkhelo_blacklist_helo'] = "HELO/EHLO to blacklist.";

$tooltips['checkhelo_whitelist_source'] = "Currently only 'SenderIP' is supported, the input box this can be a CIDR (a.b.c.d/e) range or IP address (a.b.c.d).";

$tooltips['checkspf_rejectfailed'] = "Reject message if it fails SPF check.";
$tooltips['checkspf_addheader'] = "If message is not rejected, add a header with the SPF information (if any).";

$tooltips['greylisting_period'] = "Period in seconds to greylist for. A sane value is 240. Blank means inherit.";
$tooltips['greylisting_track'] = "How to track the greylisting, this can currently only be a network mask in CIDR format. A sane value is /24.";
$tooltips['greylisting_auth_validity'] = "Period in seconds to keep authenticated entries for. This is based on a rolling window. Blank means inherit. A sane value is 604800 (7 days).";
$tooltips['greylisting_unauth_validity'] = "Period in seconds to keep unauthenticated entries for. This is based on a rolling window. Blank means inherit. A sane value is 86400 (1 day).";
$tooltips['greylisting_unauth_validity'] = "Period in seconds to keep unauthenticated entries for. This is based on a rolling window. Blank means inherit. A sane value is 86400 (1 day).";

$tooltips['greylisting_awl_period'] = "Period in seconds to automatically whitelist the sending server for. This is updated on in a rolling window fashion. Blank means inherit. A sane value is 604800.";
$tooltips['greylisting_awl_count'] = "How many successful entries should it take before we whitelist. Blank means inherit, 0 means disable. A sane value for this is 500.";
$tooltips['greylisting_awl_percentage'] = "This changes the function of the AWL Count, only autowhitelist after Count entries are recieved and Percentage of them are authenticated. Blank means inherit, 0 means disable. A sane set of values are 100 and 95 respectively.";

$tooltips['greylisting_abl_period'] = "Period in seconds to automatically blacklist the sending server for. Blank means inherit. A sane value is 604800 (7 days).";
$tooltips['greylisting_abl_count'] = "How many failed entries should it take before we blacklist. Blank means inherit, 0 means disable. A sane value for this is 500.";
$tooltips['greylisting_abl_percentage'] = "This changes the function of the ABL Count, only autoblacklist after Count entries are recieved and Percentage of them are unauthenticated. Blank means inherit, 0 means disable. A sane set of values are 100 and 100 respectively.";

$tooltips['greylisting_whitelist_source'] = "Currently only 'SenderIP' is supported, the input box this can be a CIDR (a.b.c.d/e) range or IP address (a.b.c.d).";

$tooltips['amavis_bypass_virus_checks'] = "Bypass anti-virus checks.";
$tooltips['amavis_bypass_banned_checks'] = "Bypass banned file checks.";
$tooltips['amavis_bypass_spam_checks'] = "Bypass spam related checks.";
$tooltips['amavis_bypass_header_checks'] = "Bypass malformed header checks.";
$tooltips['amavis_spam_tag_level'] = "Begin tagging email at this score as clean.";
$tooltips['amavis_spam_tag2_level'] = "Begin tagging email as SPAMMY at this score.";
$tooltips['amavis_spam_tag3_level'] = "Tag email as SPAMMY at this score, but using you can specify a different subjet line. ie. 'Spam Tag3 Subject.'";
$tooltips['amavis_spam_kill_level'] = "Score to trigger evasive action. ie. Reject or Quarantine.";
$tooltips['amavis_spam_dsn_cutoff_level'] = "Spam score at which not to generate delivery status notifications.";
$tooltips['amavis_spam_quarantine_cutoff_level'] = "Spam score at which not to quarantine.";
$tooltips['amavis_spam_modifies_subject'] = "Modify email subject for this policy if spam is detected.";
$tooltips['amavis_spam_tag_subject'] = "Subject to prepend to the email message when message exceeds spam tag level. Macros available are _REQD_ and _SCORE_ which are replaced with the required and actual score respectively.";
$tooltips['amavis_spam_tag2_subject'] = "Subject to prepend to the email message when message exceeds spam tag2 level. Macros available are _REQD_ and _SCORE_ which are replaced with the required and actual score respectively.";
$tooltips['amavis_spam_tag3_subject'] = "Subject to prepend to the email message when message exceeds spam tag3 level. Macros available are _REQD_ and _SCORE_ which are replaced with the required and actual score respectively.";
$tooltips['amavis_max_message_size'] = "Maximum message size allowed. In Kbyte.";
$tooltips['amavis_banned_files'] = "List of filename extensions and/or types to ban. Separated by , or ; or newline or whitespace. For example .exe,.bat,.dll,audio/*,video/*.";
$tooltips['amavis_sender_whitelist'] = "Whitelist these senders, this allows them to always bypass the various checks. Email addresses are separated by a comma. Remeber however sender addresses can be forged!";
$tooltips['amavis_sender_blacklist'] = "Blacklist these senders, this will automatically declare the email message as being spam. Email addresses should be separated by a comma.";
$tooltips['amavis_notify_admin_newvirus'] = "Send newly encountered virus notifications to this address. This is new viruses encountered since last software start.";
$tooltips['amavis_notify_admin_virus'] = "Send virus notifications to this address.";
$tooltips['amavis_notify_admin_spam'] = "Send spam notifications to this address.";
$tooltips['amavis_notify_admin_banned_file'] = "Send banned file notifications to this address.";
$tooltips['amavis_notify_admin_bad_header'] = "Send bad header notifications to this address.";
$tooltips['amavis_quarantine_virus'] = "Email address or facility to quanrantine emails containing viruses to.";
$tooltips['amavis_quarantine_spam'] = "Email address or facility to quanrantine emails containing spam to.";
$tooltips['amavis_quarantine_banned_file'] = "Email address or facility to quanrantine emails containing banned files to.";
$tooltips['amavis_quarantine_bad_header'] = "Email address or facility to quanrantine emails containing bad headers to.";
$tooltips['amavis_bcc_to'] = "Interception: BCC all email to this email address.";

$tooltips['postfix_transport_type'] = "Transport for this domain, either SMTP to another server or Virtual for local.";

$tooltips['postfix_mailbox_quota'] = "Mailbox size in Mbyte.";
$tooltips['postfix_mailbox_bcc'] = "This is the Postfix BCC mapping, this occurs BEFORE Amavis integration.";

$tooltips['postfix_alias_goto'] = "The destination email address.";


?>
