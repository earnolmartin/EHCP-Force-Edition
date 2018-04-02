# Cluebringer policy support for amavisd-new
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


package Amavis::Custom;
use strict;

use lib('/usr/local/lib/cbpolicyd-2.1');


#my $DB_dsn = "DBI:SQLite:dbname=/tmp/cluebringer.sqlite";
my $DB_dsn = "DBI:mysql:database=policyd;host=localhost";
my $DB_user = "{ehcpusername}";
my $DB_pass = "{ehcppass}";
my $DB_prefix = "";


# This is the amavis rule options we can use
my %ruleOptions = (
	'boolean' =>  [ qw(
			bypass_virus_checks
			bypass_banned_checks
			bypass_spam_checks
			bypass_header_checks
			
			spam_modifies_subject
	) ],

	'float' => [ qw(
			spam_tag_level
			spam_tag2_level
			spam_tag3_level
			spam_kill_level
			spam_dsn_cutoff_level
			spam_quarantine_cutoff_level
	) ],

	'text' => [ qw(
			spam_tag_subject
			spam_tag2_subject
			spam_tag3_subject
			
			quarantine_virus
			quarantine_banned_file
			quarantine_bad_header
			quarantine_spam
			
			bcc_to
	) ],

	'integer' => [ qw(
			max_message_size
	) ],

	'textlist' => [ qw(
			banned_files

			sender_whitelist
			sender_blacklist

			notify_admin_newvirus
			notify_admin_virus
			notify_admin_spam
			notify_admin_banned_file
			notify_admin_bad_header
	) ],
);




BEGIN {
	import Amavis::Util qw(do_log);
	import Amavis::rfc2821_2822_Tools qw(parse_message_id);
	import Amavis::Conf qw(
		D_REJECT
		D_BOUNCE
		D_DISCARD
		D_PASS
	);

	# Use cluebringer modules
	use cbp::config;
	use awitpt::db::dblayer;
	use cbp::tracking;
	use cbp::policies;
	use cbp::logging;
}



sub new {
	my($class,$conn,$msginfo) = @_;
	my($self) = bless {}, $class;

	# Forge configuration
	$self->{'inifile'}{'database'}{'dsn'} = $DB_dsn;
	$self->{'inifile'}{'database'}{'username'} = $DB_user;
	$self->{'inifile'}{'database'}{'password'} = $DB_pass;
	$self->{'inifile'}{'database'}{'table_prefix'} = $DB_prefix;
	cbp::config::Init($self);
	
	# Init system stuff
	$self->{'dbh'} = awitpt::db::dbilayer::Init($self,'cbp');
	if (!defined($self->{'dbh'})) {
		$self->log(LOG_WARN,"Failed to Initialize: ".awitpt::db::dbilayer::internalError()." ($$)");
		die;
	}
	if ($self->{'dbh'}->connect()) {
		$self->log(LOG_WARN,"Failed to connect to database: ".$self->{'dbh'}->Error()." ($$)");
		die;
	}

	# Setup database handle
	awitpt::db::dblayer::setHandle($self->{'dbh'});

	return $self;
}



sub process_policy {
	my($self,$conn,$msginfo,$pbn) = @_;

	do_log(5,"policyd/process_policy: Starting");
	
	# Get message ID
	my (undef,undef,$lastReceived) = $msginfo->get_header_field('received',0);
	if (!($lastReceived =~ /with E?SMTPS?A? id ([0-9A-Z]+)/)) {
		do_log(-1,"policyd/process_policy: Failed to parse in queue id from received line '$lastReceived'");
		return $pbn;
	}
	my $queueID = $1;

	#
	# Pull session data
	#
	# We pull in this information so we can get the sasl details
	# once we have hte sasl details we can generate a policy.
	# We do all this because the email addy may of been changed
	# due to an alias or distribution list.
	do_log(5,"policyd/process_policy: Getting session data from queue ID '$queueID'");
	my $sessionData = getSessionDataFromQueueID($self,$queueID,$msginfo->client_addr,$msginfo->sender);
	if (ref $sessionData ne "HASH") {
		do_log(-1,"policyd/process_policy: No session data found");
		return $pbn;
	}

	# Loop with recipients
	my %recip_to_policy;
	foreach my $r (@{$msginfo->per_recip_data}) {
		my $emailAddy = $r->recip_addr;

		# If this recipient isn't part of the stored policy, get the policy ourselves
		# This means that the recipients addy changed, or there is no policy for them??
		if (!defined($sessionData->{'_Recipient_To_Policy'}{$emailAddy})) {
			# Override recipient
			$sessionData->{'Recipient'} = $emailAddy;
			# Now pull in policy
			my $policy = getPolicy($self,$sessionData);
			if (!$policy) {
				next;
			}

			$recip_to_policy{$emailAddy} = $policy;

		# Else just load
		} else {
			$recip_to_policy{$emailAddy} = $sessionData->{'_Recipient_To_Policy'}{$emailAddy};
		}
	}

	# Loop with email addies
	foreach my $emailAddy (keys %recip_to_policy) {

		 # Start with a blank config
		my %amavisConfig = ();

		# Loop with priorities, low to high
		foreach my $priority (sort {$a <=> $b} keys %{$recip_to_policy{$emailAddy}}) {

			# Loop with each policyID
			foreach my $policyID (@{$recip_to_policy{$emailAddy}->{$priority}}) {

				# Grab amavis policyID
				my $amavisRule = $self->getAmavisRule($policyID);
				# If no amavis policyID, next...
				if (!$amavisRule) {
					next;
				}

				# Loop with variable types
				foreach my $vartype (keys %ruleOptions) {

					# Start with checking booleans
					if ($vartype eq "boolean") {

						# Loop with variables
						foreach my $varname (@{$ruleOptions{$vartype}}) {

							# We ignore state 0, which is ignore/inherit
							if ($amavisRule->{$varname."_m"} eq "0") {

							# Mode 2 is overwrite
							} elsif ($amavisRule->{$varname."_m"} eq "2") {
								$amavisConfig{$varname} = $amavisRule->{$varname};

							# All other modes including mode 1 (merge) is invalid
							} else {
								do_log(-1,"policyd/process_policy: Mode '%s' for amavis policy '%s' variable '%s'  is invalid as its a boolean",
										$amavisRule->{$varname."_m"},$policyID,$varname);
							}
						}

					# Floats
					} elsif ($vartype eq "float") {
						# Loop with variables
						foreach my $varname (@{$ruleOptions{$vartype}}) {

							# We ignore state 0, which is ignore/inherit
							if ($amavisRule->{$varname."_m"} eq "0") {

							# Mode 2 is overwrite
							} elsif ($amavisRule->{$varname."_m"} eq "2") {
								$amavisConfig{$varname} = $amavisRule->{$varname};

							# All other modes including mode 1 (merge) is invalid
							} else {
								do_log(-1,"policyd/process_policy: Mode '%s' for amavis policy '%s' variable '%s'  is invalid as its a float",
										$amavisRule->{$varname."_m"},$policyID,$varname);
							}
						}

					# Text
					} elsif ($vartype eq "text") {
						# Loop with variables
						foreach my $varname (@{$ruleOptions{$vartype}}) {

							# We ignore state 0, which is ignore/inherit
							if ($amavisRule->{$varname."_m"} eq "0") {

							# Mode 2 is overwrite
							} elsif ($amavisRule->{$varname."_m"} eq "2") {
								$amavisConfig{$varname} = $amavisRule->{$varname};

							# All other modes including mode 1 (merge) is invalid
							} else {
								do_log(-1,"policyd/process_policy: Mode '%s' for amavis policy '%s' variable '%s'  is invalid as its a text",
										$amavisRule->{$varname."_m"},$policyID,$varname);
							}
						}

					# Integers
					} elsif ($vartype eq "integer") {
						# Loop with variables
						foreach my $varname (@{$ruleOptions{$vartype}}) {

							# We ignore state 0, which is ignore/inherit
							if ($amavisRule->{$varname."_m"} eq "0") {

							# Mode 2 is overwrite
							} elsif ($amavisRule->{$varname."_m"} eq "2") {
								$amavisConfig{$varname} = $amavisRule->{$varname};

							# All other modes including mode 1 (merge) is invalid
							} else {
								do_log(-1,"policyd/process_policy: Mode '%s' for amavis policy '%s' variable '%s'  is invalid as its a integer",
										$amavisRule->{$varname."_m"},$policyID,$varname);
							}
						}

					# Text list (array)
					} elsif ($vartype eq "textlist") {
						# Loop with variables
						foreach my $varname (@{$ruleOptions{$vartype}}) {
							# We ignore state 0, which is ignore/inherit
							if ($amavisRule->{$varname."_m"} eq "0") {

							# Mode 1 is merge
							} elsif ($amavisRule->{$varname."_m"} eq "1") {
								my @items = split /[,;\s+]/, $amavisRule->{$varname};

								# If we already have a list, add to end of it
								if (defined($amavisConfig{$varname})) {
									push(@items,@{$amavisConfig{$varname}});
								}

								# Loop and get unique
								my %uniqItems = ();
								foreach my $item (@items) {
									$uniqItems{$item} = 1;
								}

								my @items = keys %uniqItems;

								# Only store the key list we have
								$amavisConfig{$varname} = \@items;

							# Mode 2 is overwrite
							} elsif ($amavisRule->{$varname."_m"} eq "2") {
								my @items = split /[,;\s+]/, $amavisRule->{$varname};
								# Wipe and add
								$amavisConfig{$varname} = \@items;

							# All other modes including mode 1 (merge) is invalid
							} else {
								do_log(-1,"policyd/process_policy: Mode '%s' for amavis policy '%s' variable '%s'  is invalid as its a text list",
										$amavisRule->{$varname."_m"},$policyID,$varname);
							}
						}
					}
				} # foreach my $vartype (keys %ruleOptions)
			} # foreach my $policyID (@{$recip_to_policy{$emailAddy}{$priority}})
		} # foreach my $priority (sort {$a <=> $b} keys %{$recip_to_policy{$emailAddy}})

		# Check bypass
		#
		# Bypass will bypass the check if no other recip needs to be checked, lover means we will
		# send to the recip regardless of the result

		# Check for virus bypass
		if (defined($amavisConfig{'bypass_virus_checks'})) {
			push(@{$pbn->{'bypass_virus_checks_maps'}},\{
					$emailAddy => 1
			});
			push(@{$pbn->{'virus_lovers_maps'}},\{
					$emailAddy	=> 1
			});
		}
		# Check for banned file/filetype bypass
		if (defined($amavisConfig{'bypass_banned_checks'})) {
			push(@{$pbn->{'bypass_banned_checks_maps'}},\{
					$emailAddy	=> 1
			});
			push(@{$pbn->{'banned_files_lovers_maps'}},\{
					$emailAddy	=> 1
			});
		}
		# Check for spam bypass
		if (defined($amavisConfig{'bypass_spam_checks'})) {
			push(@{$pbn->{'bypass_spam_checks_maps'}},\{
					$emailAddy	=> 1
			});
			push(@{$pbn->{'spam_lovers_maps'}},\{
					$emailAddy	=> 1
			});
		}
		# Check for header bypass
		if (defined($amavisConfig{'bypass_header_checks'})) {
			push(@{$pbn->{'bypass_header_checks_maps'}},\{
					$emailAddy	=> 1
			});
			push(@{$pbn->{'bad_header_lovers_maps'}},\{
					$emailAddy	=> 1
			});
		}

		# Spam levels

		# Check if we have a tag level
		if (defined($amavisConfig{'spam_tag_level'})) {
			push(@{$pbn->{'spam_tag_level_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_tag_level'}
			});
		}

		# Check if we have a tag2 level
		if (defined($amavisConfig{'spam_tag2_level'})) {
			push(@{$pbn->{'spam_tag2_level_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_tag2_level'}
			});
		}

		# Check if we have a tag3 level
		if (defined($amavisConfig{'spam_tag3_level'})) {
			push(@{$pbn->{'spam_tag3_level_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_tag3_level'}
			});
		}

		# Check if we have a kill level
		if (defined($amavisConfig{'spam_kill_level'})) {
			push(@{$pbn->{'spam_kill_level_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_kill_level'}
			});
		}

		# Check if we have a dsn_cutoff level
		if (defined($amavisConfig{'spam_dsn_cutoff_level'})) {
			push(@{$pbn->{'spam_dsn_cutoff_level_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_dsn_cutoff_level'}
			});
		}

		# Check if we have a quarantine_cutoff level
		if (defined($amavisConfig{'spam_quarantine_cutoff_level'})) {
			push(@{$pbn->{'spam_quarantine_cutoff_level_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_quarantine_cutoff_level'}
			});
		}


		# Spam subject stuff

		# Check for spam modifies subject
		if (defined($amavisConfig{'spam_modifies_subject'})) {
			push(@{$pbn->{'spam_modifies_subj_maps'}},\{
					$emailAddy	=> 1
			});
		}

		# Check for spam tag subject
		if (defined($amavisConfig{'spam_tag_subject'})) {
			push(@{$pbn->{'spam_subject_tag_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_tag_subject'}
			});
		}

		# Check for spam tag2 subject
		if (defined($amavisConfig{'spam_tag2_subject'})) {
			push(@{$pbn->{'spam_subject_tag2_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_tag2_subject'}
			});
		}

		# Check for spam tag3 subject
		if (defined($amavisConfig{'spam_tag3_subject'})) {
			push(@{$pbn->{'spam_subject_tag3_maps'}},\{
					$emailAddy	=> $amavisConfig{'spam_tag3_subject'}
			});
		}

		# General checks

		# Check if we have a message size limit, if so push it in
		if (defined($amavisConfig{'max_message_size'})) {
			push(@{$pbn->{'message_size_limit_maps'}},\{
					$emailAddy	=> ( $amavisConfig{'max_message_size'} * 1024 )
			});
		}

		# Check if we have a list of banned files
		if (defined($amavisConfig{'banned_files'})) {
			my @banned_ext;
			my @banned_type;
			foreach my $bf (@{$amavisConfig{'banned_files'}}) {
				# Check for file extension
				if ($bf =~ /^\./) {
					$bf =~ s/^\.//;
					push(@banned_ext,$bf);
				# Check for content type
				} elsif ($bf =~ /^\S+\//) {
					# Fix *
					$bf =~ s/\*$/.*/;
					push(@banned_type,$bf);
				}
			}

			# Build half the regex
			my $banned_ext_re = join('|',@banned_ext);
			my $banned_type_re = join('|',@banned_type);

			my @re_list;

			# Check vars we just created
			if ($banned_ext_re ne "") {
				$banned_ext_re = "^\\.($banned_ext_re)\$";
				$banned_ext_re = qr"$banned_ext_re"i;
				push(@re_list,$banned_ext_re);
			}
			if ($banned_type_re ne "") {
				$banned_type_re = "^($banned_type_re)\$";
				$banned_type_re = qr"$banned_type_re"i;
				push(@re_list,$banned_type_re);
			}

			push(@{$pbn->{'banned_filename_maps'}},\{
					$emailAddy	=>  [ Amavis::Lookup::RE->new(@re_list) ]
			});
		}


		# Whitelist & blacklist
		
		# Check if we have a list of sender whitelists
		if (defined($amavisConfig{'sender_whitelist'})) {
			# If the lookup tables isn't a hash ref, make one
			if (ref $pbn->{'per_recip_whitelist_sender_lookup_tables'} ne "HASH") {
				$pbn->{'per_recip_whitelist_sender_lookup_tables'} = { };
			}

			# Get list of vals to add
			my @vals = @{$amavisConfig{'sender_whitelist'}};
			# Check if we can add old vals
			if (defined($pbn->{'per_recip_whitelist_sender_lookup_tables'}{$emailAddy})) {
				push(@vals,@{$pbn->{'per_recip_whitelist_sender_lookup_tables'}{$emailAddy}});
			}
			# Build hahs to get unique
			my %tmphash = ();
			foreach my $item (@vals) {
				$tmphash{$item} = 1;
			}
			# Create array
			@vals = keys %tmphash;
			# Save...
			$pbn->{'per_recip_whitelist_sender_lookup_tables'}{$emailAddy} = \@vals;
		}
		
		# Check if we have a list of sender blacklists
		if (defined($amavisConfig{'sender_blacklist'})) {
			# If the lookup tables isn't a hash ref, make one
			if (ref $pbn->{'per_recip_blacklist_sender_lookup_tables'} ne "HASH") {
				$pbn->{'per_recip_blacklist_sender_lookup_tables'} = { };
			}

			# Get list of vals to add
			my @vals = @{$amavisConfig{'sender_blacklist'}};
			# Check if we can add old vals
			if (defined($pbn->{'per_recip_blacklist_sender_lookup_tables'}{$emailAddy})) {
				push(@vals,@{$pbn->{'per_recip_blacklist_sender_lookup_tables'}{$emailAddy}});
			}
			# Build hahs to get unique
			my %tmphash = ();
			foreach my $item (@vals) {
				$tmphash{$item} = 1;
			}
			# Create array
			@vals = keys %tmphash;
			# Save...
			$pbn->{'per_recip_blacklist_sender_lookup_tables'}{$emailAddy} = \@vals;
		}


		# Admin notifications
		
		# Check if we have a list of new virus admins
		if (defined($amavisConfig{'notify_admin_newvirus'})) {
			push(@{$pbn->{'newvirus_admin_maps'}},\{
					$emailAddy	=> $amavisConfig{'notify_admin_newvirus'}
			});
		}
		
		# Check if we have a list of virus admins
		if (defined($amavisConfig{'notify_admin_virus'})) {
			push(@{$pbn->{'virus_admin_maps'}},\{
					$emailAddy	=> $amavisConfig{'notify_admin_virus'}
			});
		}
		
		# Check if we have a list of spam admins
		if (defined($amavisConfig{'notify_admin_spam'})) {
			push(@{$pbn->{'spam_admin_maps'}},\{
					$emailAddy	=> $amavisConfig{'notify_admin_spam'}
			});
		}
		
		# Check if we have a list of banned file admins
		if (defined($amavisConfig{'notify_admin_banned_file'})) {
			push(@{$pbn->{'banned_admin_maps'}},\{
					$emailAddy	=> $amavisConfig{'notify_admin_banned_file'}
			});
		}
		
		# Check if we have a list of bad header admins
		if (defined($amavisConfig{'notify_admin_bad_header'})) {
			push(@{$pbn->{'bad_header_admin_maps'}},\{
					$emailAddy	=> $amavisConfig{'notify_admin_bad_header'}
			});
		}


		# Quarantine options
		
		# Check if we must quarantine a virus
		if (defined($amavisConfig{'quarantine_virus'})) {
			push(@{$pbn->{'virus_quarantine_to_maps'}},\{
					$emailAddy	=> $amavisConfig{'quarantine_virus'}
			});
		}

		# Check if we must quarantine a banned file
		if (defined($amavisConfig{'quarantine_banned_file'})) {
			push(@{$pbn->{'banned_quarantine_to_maps'}},\{
					$emailAddy	=> $amavisConfig{'quarantine_banned_file'}
			});
		}

		# Check if we must quarantine a banned header
		if (defined($amavisConfig{'quarantine_bad_header'})) {
			push(@{$pbn->{'bad_header_quarantine_to_maps'}},\{
					$emailAddy	=> $amavisConfig{'quarantine_bad_header'}
			});
		}

		# Check if we must quarantine spam
		if (defined($amavisConfig{'quarantine_spam'})) {
			push(@{$pbn->{'spam_quarantine_to_maps'}},\{
					$emailAddy	=> $amavisConfig{'quarantine_spam'}
			});
		}

		# Interception
		
		# Email addy to BCC to
		if (defined($amavisConfig{'bcc_to'})) {
			if (!defined($pbn->{'always_bcc'}) || $pbn->{'always_bcc'} eq "") {
				$pbn->{'always_bcc'} = $amavisConfig{'bcc_to'}
			} else {
				$pbn->{'always_bcc'} .= "," . $amavisConfig{'bcc_to'}
			}
		}
	} # foreach my $emailAddy (keys %{$sessionData->{'_Recipient_To_Policy'}})

	return $pbn;
};



# Mail logging
sub amail_done
{
	my($self,$conn,$msginfo) = @_;
  
	my($mail_id) = $msginfo->mail_id;
	my($spam_level) = $msginfo->spam_level;
	my($sid) = $msginfo->sender_maddr_id;
	my($m_id) = $msginfo->orig_header_fields->{'message-id'};
		do_log(-2,"CUSTOM: m_id1: $m_id");
	my($m_id) = parse_message_id($m_id) if $m_id ne ''; # strip CFWS, take #1
		do_log(-2,"CUSTOM: m_id2: $m_id");
	my($subj) = $msginfo->orig_header_fields->{'subject'};
	my($from) = $msginfo->orig_header_fields->{'from'};  # raw full field
	my($rfc2822_from)   = $msginfo->rfc2822_from;  # undef, scalar or listref
	my($rfc2822_sender) = $msginfo->rfc2822_sender;  # undef or scalar
	$rfc2822_from = join(', ',@$rfc2822_from)  if ref $rfc2822_from;
	my($os_fp) = $msginfo->client_os_fingerprint;
	my $size = $msginfo->msg_size;

	# insert per-recipient records into table msgrcpt
	for my $r (@{$msginfo->per_recip_data}) {
		my($rid) = $r->recip_maddr_id;
		my($dest,$resp) = ($r->recip_destiny, $r->recip_smtp_response);
		my $blacklist_sender = $r->recip_blacklisted_sender ? 'Y' : 'N';
		my $blacklist_recipient = $r->recip_whitelisted_sender ? 'Y' : 'N';
		my $score = $spam_level+$r->recip_score_boost;
		do_log(-2,"CUSTOM: mail_id: $mail_id, rid: $rid, dest: $dest, ".
				"resp: $resp, black_sender: $blacklist_sender, black_recip: ".
				"$blacklist_recipient, spam_level: $score, sid: $sid, mm_id: ".
				"$m_id, subj: $subj, from: $from ($rfc2822_from), to: ".
				$r->recip_addr.", os_fp: $os_fp");
		do_log(-2,"CUSTOM DBLOG: mail_id: $mail_id, from: $from, to: ".$r->recip_addr.", subject: $subj, size: $size, status: $resp");
	}
}



# Get amavis rule
sub getAmavisRule
{
	my ($self,$policyID) = @_;

	
	# Query amavis rules table
	my $sth = DBSelect('
		SELECT 
			ID,

			bypass_virus_checks, bypass_banned_checks, bypass_spam_checks, bypass_header_checks,
			bypass_virus_checks_m, bypass_banned_checks_m, bypass_spam_checks_m, bypass_header_checks_m,


			spam_tag_level, spam_tag2_level, spam_tag3_level, spam_kill_level, spam_dsn_cutoff_level, spam_quarantine_cutoff_level,
			spam_tag_level_m, spam_tag2_level_m, spam_tag3_level_m, spam_kill_level_m, spam_dsn_cutoff_level_m, spam_quarantine_cutoff_level_m,
			spam_modifies_subject, spam_tag_subject, spam_tag2_subject, spam_tag3_subject,
			spam_modifies_subject_m, spam_tag_subject_m, spam_tag2_subject_m, spam_tag3_subject_m,


			max_message_size, banned_files,
			max_message_size_m, banned_files_m,


			sender_whitelist, sender_blacklist,
			sender_whitelist_m, sender_blacklist_m,


			notify_admin_newvirus, notify_admin_virus, notify_admin_spam, notify_admin_banned_file, notify_admin_bad_header,
			notify_admin_newvirus_m, notify_admin_virus_m, notify_admin_spam_m, notify_admin_banned_file_m, notify_admin_bad_header_m,
		

			quarantine_virus, quarantine_banned_file, quarantine_bad_header, quarantine_spam,
			quarantine_virus_m, quarantine_banned_file_m, quarantine_bad_header_m, quarantine_spam_m,
			
			bcc_to,
			bcc_to_m

		FROM
			@TP@amavis_rules

		WHERE
			PolicyID = ?
			AND Disabled = 0
		',
		$policyID
	);
	if (!$sth) {
		do_log(-2,"policyd/process_policyd: Failed to query amavis: ".awitpt::db::dblayer::Error());
		return;
	}

	my $row = $sth->fetchrow_hashref();
	DBFreeRes($sth);

	# Database compatibility, quick and dirty
	if ($row) {
		$row->{'ID'} = $row->{'id'};
	}

	return $row;
}


# Logging...
sub log
{
	my ($self,$level,$msg,@args) = @_;

	# Check log level and set text
	my $logtxt = "UNKNOWN"; 
	my $loglvl = 1;
	# Check levels...
	if ($level == LOG_DEBUG) {
		$logtxt = "DEBUG";
		$loglvl = 2;
	} elsif ($level == LOG_INFO) {
		$logtxt = "INFO";
		$loglvl = 1;
	} elsif ($level == LOG_NOTICE) {
		$logtxt = "NOTICE";
		$loglvl = 0;
	} elsif ($level == LOG_WARN) {
		$logtxt = "WARNING";
		$loglvl = -1;
	} elsif ($level == LOG_ERR) {
		$logtxt = "ERROR";
		$loglvl = -2;
	} 

	# Parse message nicely
	if ($msg =~ /^(\[[^\]]+\]) (.*)/s) {
		$msg = "$1 $logtxt: $2";
	} else {
		$msg = "[CORE] $logtxt: $msg";
	}

	do_log($loglvl,"$msg".join('',@args));
}


# vim: ts=4
1;
