# Accounting module
# Copyright (C) 2011, AllWorldIT
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


package cbp::modules::Accounting;

use strict;
use warnings;

use POSIX qw( ceil strftime );

use cbp::logging;
use awitpt::db::dblayer;
use cbp::system;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Accounting Plugin",
	priority		=> 10,
	init		 	=> \&init,
	request_process	=> \&check,
#	cleanup		 	=> \&cleanup,
};


# Module configuration
my %config;


# Create a child specific context
sub init {
	my $server = shift;
	my $inifile = $server->{'inifile'};


	# Defaults
	$config{'enable'} = 0;

	# Parse in config
	if (defined($inifile->{'accounting'})) {
		foreach my $key (keys %{$inifile->{'accounting'}}) {
			$config{$key} = $inifile->{'accounting'}->{$key};
		}
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Accounting: enabled");
		$config{'enable'} = 1;
		# Enable tracking, we need this to recipients for the message in END-OF-DATA
		$server->{'config'}{'track_sessions'} = 1;
	} else {
		$server->log(LOG_NOTICE,"  => Accounting: disabled");
	}
}




# Do our checks
sub check {
	my ($server,$sessionData) = @_;
	

	# If we not enabled, don't do anything
	return CBP_SKIP if (!$config{'enable'});

	# We only valid in the RCPT and EOM state
	return CBP_SKIP if (!defined($sessionData->{'ProtocolState'}));

	# Check valid state & that we have our policy data
	return CBP_SKIP if (!
				(
					($sessionData->{'ProtocolState'} eq "RCPT" && defined($sessionData->{'Policy'})) ||
					($sessionData->{'ProtocolState'} eq "END-OF-MESSAGE" && defined($sessionData->{'_Recipient_To_Policy'}))
				)
			);

	# Our verdict and data
	my ($verdict,$verdict_data);

	my $now = time();


	#
	# RCPT state
	#   If we in this state we increase the RCPT counters for each key we have
	#   we only do this if we not going to reject the message. We also check if
	#   we have exceeded our size limit. The Size limit is updated in the EOM
	#   stage
	#
	if ($sessionData->{'ProtocolState'} eq "RCPT") {

		# Key tracking list, if hasExceeded is not undef, it will contain the msg
		my %newCounters;  # Indexed by AccountingID
		my @trackingList;
		my $hasExceeded;
		my $exceededAtrack;

		# Loop with priorities, low to high
POLICY:		foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}}) {

			# Last if we've exceeded
			last if ($hasExceeded);


			# Loop with each policyID
			foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}}) {

				# Last if we've exceeded
				last if ($hasExceeded);

				# Get accounting records
				my $accountings = getAccountings($server,$policyID);
				# Check if we got a accountings or not
				if (ref($accountings) ne "ARRAY") {
					return $server->protocol_response(PROTO_DB_ERROR);
				}
			
				# Loop with accounting records
				foreach my $accounting (@{$accountings}) {

					# Last if we've exceeded
					last if ($hasExceeded);

					# Grab tracking key
					my $trackKey = getTrackKey($server,$accounting,$sessionData);
					if (!defined($trackKey)) {
						$server->log(LOG_ERR,"[ACCOUNTING] No tracking key found for accounting ID '".$accounting->{'ID'}."'");
						return $server->protocol_response(PROTO_DATA_ERROR);
					}

					# Grab period key
					my $periodKey = getPeriodKey($accounting->{'AccountingPeriod'});
					if (!defined($periodKey)) {
						$server->log(LOG_ERR,"[ACCOUNTING] No period key found for accounting ID '".$accounting->{'ID'}."'");
						return $server->protocol_response(PROTO_DATA_ERROR);
					}

					# Get quota tracking info
					my $atrack = getTrackingInfo($server,$accounting->{'ID'},$trackKey,$periodKey);
					# Check if we got tracking info or not
					if (defined($atrack) && ref($atrack) ne "HASH") {
						return $server->protocol_response(PROTO_DB_ERROR);
					}

					# Check if we have a queue tracking item
					if (defined($atrack)) {
						# Make sure we have initialized the counters
						if (!defined($newCounters{$atrack->{'AccountingID'}})) {
							$newCounters{$atrack->{'AccountingID'}}->{'MessageCount'} = 0;
							$newCounters{$atrack->{'AccountingID'}}->{'MessageCumulativeSize'} = 0;
						}
	
						# Check for violation
						if (defined($accounting->{'MessageCountLimit'}) && $accounting->{'MessageCountLimit'} ne "") {
							# If its a valid message count limit
							if ($accounting->{'MessageCountLimit'} =~ /^[0-9]+$/) {
								# Check if we exceeded
								if ($accounting->{'MessageCountLimit'} > 0 && (
									# Check current counter
									$atrack->{'MessageCount'} >= $accounting->{'MessageCountLimit'}
								)) {
									$hasExceeded = "Policy rejection; Message count exceeded";
								}
							} else {
								$server->log(LOG_ERR,"[ACCOUNTING] The value for MessageCountLimit is invalid for accounting ID  '".$accounting->{'ID'}."'");
							}
						}

						# If we've not exceeded, check the message cumulative size
						if (!$hasExceeded) {
							# Bump up count
							$newCounters{$atrack->{'AccountingID'}}->{'MessageCount'}++;

							# Check for cumulative size violation
							if (defined($accounting->{'MessageCumulativeSizeLimit'}) && 
									$accounting->{'MessageCumulativeSizeLimit'} ne "") {
								# If its a valid message count limit
								if ($accounting->{'MessageCumulativeSizeLimit'} =~ /^[0-9]+$/) {
									if ($accounting->{'MessageCumulativeSizeLimit'} > 0 &&
											$atrack->{'MessageCumulativeSize'} > $accounting->{'MessageCumulativeSizeLimit'}) {
										$hasExceeded = "Policy rejection; Cumulative message size exceeded";
									}
								} else {
									$server->log(LOG_ERR,"[ACCOUNTING] The value for MessageCumulativeSizeLimit is invalid for accounting ID  '".$accounting->{'ID'}."'");
								}
							}
						}

					} else {
						$atrack->{'AccountingID'} = $accounting->{'ID'};
						$atrack->{'TrackKey'} = $trackKey;
						$atrack->{'PeriodKey'} = $periodKey;
						$atrack->{'MessageCount'} = 0;
						$atrack->{'MessageCumulativeSize'} = 0;
					
						# Make sure we have initialized the counters
						if (!defined($newCounters{$atrack->{'AccountingID'}})) {
							$newCounters{$atrack->{'AccountingID'}}->{'MessageCount'} = $atrack->{'MessageCount'};
							$newCounters{$atrack->{'AccountingID'}}->{'MessageCumulativeSize'} = $atrack->{'MessageCumulativeSize'};
						}
							
						# Bump up limit
						$newCounters{$atrack->{'AccountingID'}}->{'MessageCount'}++;
					}
						
					# Setup some stuff we need for logging
					$atrack->{'DBTrackKey'} = $trackKey;
					$atrack->{'DBPeriodKey'} = $periodKey;
					$atrack->{'MessageCountLimit'} = $accounting->{'MessageCountLimit'};
					$atrack->{'MessageCumulativeSizeLimit'} = $accounting->{'MessageCumulativeSizeLimit'};
					$atrack->{'PolicyID'} = $policyID;
					$atrack->{'Verdict'} = $accounting->{'Verdict'};
					$atrack->{'VerdictData'} = $accounting->{'Data'};

					# If we've exceeded setup the atrack which was exceeded
					if ($hasExceeded) {
						$exceededAtrack = $atrack;
					}

					# Save accounting tracking info
					push(@trackingList,$atrack);

					# Check if this is the last accounting
					if (defined($accounting->{'LastAccounting'}) && $accounting->{'LastAccounting'} eq "1") {
						last POLICY;
					}
				} # foreach my $accounting (@{$accountings})
			} # foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}})
		} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}})

		# If we have not exceeded, update
		if (!$hasExceeded) {

			# Loop with tracking ID's and update
			foreach my $atrack (@trackingList) {
				# Percent used
				my $pCountUsage = "/-";
				my $pCumulativeSizeUsage = "/-"; 
				# If we have additional limits, add to the usage string
				if (defined($atrack->{'MessageCountLimit'}) && $atrack->{'MessageCountLimit'} > 0) {
					$pCountUsage = $newCounters{$atrack->{'AccountingID'}}->{'MessageCount'} + $atrack->{'MessageCount'};
					$pCountUsage .= sprintf('/%s (%.1f%%)',
							$atrack->{'MessageCountLimit'},
							( ($newCounters{$atrack->{'AccountingID'}}->{'MessageCount'} + $atrack->{'MessageCount'}) /
									$atrack->{'MessageCountLimit'} ) * 100
					);
				}
				if (defined($atrack->{'MessageCumulativeSizeLimit'}) && $atrack->{'MessageCumulativeSizeLimit'} > 0) {
					$pCumulativeSizeUsage =  $newCounters{$atrack->{'AccountingID'}}->{'MessageCumulativeSize'} + $atrack->{'MessageCumulativeSize'};
					$pCumulativeSizeUsage .= sprintf('/%s (%.1f%%)',
							$atrack->{'MessageCumulativeSizeLimit'},
							( ($newCounters{$atrack->{'AccountingID'}}->{'MessageCumulativeSize'} + $atrack->{'MessageCumulativeSize'}) / 
								$atrack->{'MessageCumulativeSizeLimit'} ) * 100
					);
				}

				# Update database
				my $sth = DBDo('
					UPDATE 
						@TP@accounting_tracking
					SET
						MessageCount = MessageCount + ?,
						MessageCumulativeSize = MessageCumulativeSize + ?,
						LastUpdate = ?
					WHERE
						AccountingID = ?
						AND TrackKey = ?
						AND PeriodKey = ?
					',
					$newCounters{$atrack->{'AccountingID'}}->{'MessageCount'},$newCounters{$atrack->{'AccountingID'}}->{'MessageCumulativeSize'},
					$now,$atrack->{'AccountingID'},$atrack->{'TrackKey'},$atrack->{'PeriodKey'}
				);
				if (!$sth) {
					$server->log(LOG_ERR,"[ACCOUNTING] Failed to update accounting_tracking item: ".awitpt::db::dblayer::Error());
					return $server->protocol_response(PROTO_DB_ERROR);
				}
				
				# If nothing updated, then insert our record
				if ($sth eq "0E0") {
					# Insert into database
					my $sth = DBDo('
						INSERT INTO @TP@accounting_tracking
							(AccountingID,TrackKey,PeriodKey,MessageCount,MessageCumulativeSize,LastUpdate)
						VALUES
							(?,?,?,?,?,?)
						',
						$atrack->{'AccountingID'},$atrack->{'TrackKey'},$atrack->{'PeriodKey'},
						$newCounters{$atrack->{'AccountingID'}}->{'MessageCount'},
						$newCounters{$atrack->{'AccountingID'}}->{'MessageCumulativeSize'},
						$atrack->{'LastUpdate'}
					);
					if (!$sth) {
						$server->log(LOG_ERR,"[ACCOUNTING] Failed to insert accounting_tracking item: ".awitpt::db::dblayer::Error());
						return $server->protocol_response(PROTO_DB_ERROR);
					}
					
					# Log create to mail log
					$server->maillog("module=Accounting, mode=create, host=%s, helo=%s, from=%s, to=%s, reason=accounting_create, policy=%s, accounting=%s, "
								."track=%s, period=%s, count=%s, size=%s",
							$sessionData->{'ClientAddress'},
							$sessionData->{'Helo'},
							$sessionData->{'Sender'},
							$sessionData->{'Recipient'},
							$atrack->{'PolicyID'},
							$atrack->{'AccountingID'},
							$atrack->{'DBTrackKey'},
							$atrack->{'DBPeriodKey'},
							$pCountUsage,
							$pCumulativeSizeUsage);


				# If we updated ...
				} else {
					# Log update to mail log
					$server->maillog("module=Accounting, mode=update, host=%s, helo=%s, from=%s, to=%s, reason=accounting_update, policy=%s, accounting=%s, "
								."track=%s, period=%s, count=%s, size=%s",
							$sessionData->{'ClientAddress'},
							$sessionData->{'Helo'},
							$sessionData->{'Sender'},
							$sessionData->{'Recipient'},
							$atrack->{'PolicyID'},
							$atrack->{'AccountingID'},
							$atrack->{'DBTrackKey'},
							$atrack->{'DBPeriodKey'},
							$pCountUsage,
							$pCumulativeSizeUsage);
				}
					

				# Remove limit
				delete($newCounters{$atrack->{'AccountingID'}});
			}

		# If we have exceeded, set verdict
		} else {
			# Percent used
			my $pCountUsage = $newCounters{$exceededAtrack->{'AccountingID'}}->{'MessageCount'} + $exceededAtrack->{'MessageCount'};
			my $pCumulativeSizeUsage =  $newCounters{$exceededAtrack->{'AccountingID'}}->{'MessageCumulativeSize'} +
				$exceededAtrack->{'MessageCumulativeSize'};
			# If we have additional limits, add to the usage string
			if (defined($exceededAtrack->{'MessageCountLimit'}) && $exceededAtrack->{'MessageCountLimit'} > 0) {
				$pCountUsage .= sprintf('/%s (%.1f%%)',
						$exceededAtrack->{'MessageCountLimit'},
						( ($newCounters{$exceededAtrack->{'AccountingID'}}->{'MessageCount'} +
								$exceededAtrack->{'MessageCount'}) / $exceededAtrack->{'MessageCountLimit'} ) * 100
				);
			} else {
				$pCountUsage .= "/-";
			}
			if (defined($exceededAtrack->{'MessageCumulativeSizeLimit'}) && $exceededAtrack->{'MessageCumulativeSizeLimit'} > 0) {
				$pCumulativeSizeUsage .= sprintf('/%s (%.1f%%)',
						$exceededAtrack->{'MessageCumulativeSizeLimit'},
						( ($newCounters{$exceededAtrack->{'AccountingID'}}->{'MessageCumulativeSize'} +
								$exceededAtrack->{'MessageCumulativeSize'}) / $exceededAtrack->{'MessageCumulativeSizeLimit'} ) * 100
				);
			} else {
				$pCumulativeSizeUsage .= "/-";
			}

			# Log rejection to mail log
			$server->maillog("module=Accounting, action=%s, host=%s, helo=%s, from=%s, to=%s, reason=accounting_match, policy=%s, accounting=%s, "
						."track=%s, period=%s, count=%s, size=%s",
					lc($exceededAtrack->{'Verdict'}),
					$sessionData->{'ClientAddress'},
					$sessionData->{'Helo'},
					$sessionData->{'Sender'},
					$sessionData->{'Recipient'},
					$exceededAtrack->{'PolicyID'},
					$exceededAtrack->{'AccountingID'},
					$exceededAtrack->{'DBTrackKey'},
					$exceededAtrack->{'DBPeriodKey'},
					$pCountUsage,
					$pCumulativeSizeUsage);

			$verdict = $exceededAtrack->{'Verdict'};
			$verdict_data = (defined($exceededAtrack->{'VerdictData'}) && $exceededAtrack->{'VerdictData'} ne "") 
					? $exceededAtrack->{'VerdictData'} : $hasExceeded;
		}

	#
	# END-OF-MESSAGE state
	#   The Size accounting is updated in this state
	#
	} elsif ($sessionData->{'ProtocolState'} eq "END-OF-MESSAGE") {
		
		# Check if we have recipient to policy mappings
		if (!defined($sessionData->{'_Recipient_To_Policy'})) {
			return CBP_SKIP;
		}

		# Loop with email addies
		foreach my $emailAddy (keys %{$sessionData->{'_Recipient_To_Policy'}}) {

			# Loop with priorities, low to high
POLICY:			foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'_Recipient_To_Policy'}{$emailAddy}}) {

				# Loop with each policyID
				foreach my $policyID (@{$sessionData->{'_Recipient_To_Policy'}{$emailAddy}{$priority}}) {

					# Get accounting records
					my $accountings = getAccountings($server,$policyID);
					# Check if we got a accountings or not
					if (ref($accountings) ne "ARRAY") {
						return $server->protocol_response(PROTO_DB_ERROR);
					}
			
					# Loop with accountings
					foreach my $accounting (@{$accountings}) {

						# HACK: Fool getTrackKey into thinking we actually do have a recipient
						$sessionData->{'Recipient'} = $emailAddy;
	
						# Grab tracking keys
						my $trackKey = getTrackKey($server,$accounting,$sessionData);
						if (!defined($trackKey)) {
							$server->log(LOG_WARN,"[ACCOUNTING] No key found for accounting ID '".$accounting->{'ID'}."'");
							return $server->protocol_response(PROTO_DATA_ERROR);
						}
	
						# Grab period key
						my $periodKey = getPeriodKey($accounting->{'AccountingPeriod'});
						if (!defined($periodKey)) {
							$server->log(LOG_ERR,"[ACCOUNTING] No period key found for accounting ID '".$accounting->{'ID'}."'");
							return $server->protocol_response(PROTO_DATA_ERROR);
						}

						# Get account tracking info
						my $atrack = getTrackingInfo($server,$accounting->{'ID'},$trackKey,$periodKey);
						# Check if we got tracking info or not
						if (ref($atrack) ne "HASH") {
							next; # If not just carry on?
						}

						# Update database
						my $sth = DBDo('
							UPDATE 
								@TP@accounting_tracking
							SET
								MessageCumulativeSize = MessageCumulativeSize + ?,
								LastUpdate = ?
							WHERE
								AccountingID = ?
								AND TrackKey = ?
								AND PeriodKey = ?
							',
							$sessionData->{'Size'},$now,$atrack->{'AccountingID'},$atrack->{'TrackKey'},
							$atrack->{'PeriodKey'}
						);
						if (!$sth) {
							$server->log(LOG_ERR,"[ACCOUNTING] Failed to update accounting_tracking item: ".awitpt::db::dblayer::Error());
							return $server->protocol_response(PROTO_DB_ERROR);
						}

						# Percent used
						my $pCountUsage = $atrack->{'MessageCount'};
						my $pCumulativeSizeUsage =  $atrack->{'MessageCumulativeSize'};
						# If we have additional limits, add to the usage string
						if (defined($accounting->{'MessageCountLimit'}) && $accounting->{'MessageCountLimit'} > 0) {
							$pCountUsage .= sprintf('/%s (%.1f%%)',
									$accounting->{'MessageCountLimit'},
									( $atrack->{'MessageCount'} / $accounting->{'MessageCountLimit'} ) * 100
							);
						} else {
							$pCountUsage .= "/-";
						}
						if (defined($accounting->{'MessageCumulativeSizeLimit'}) && $accounting->{'MessageCumulativeSizeLimit'} > 0) {
							$pCumulativeSizeUsage .= sprintf('/%s (%.1f%%)',
									$accounting->{'MessageCumulativeSizeLimit'},
									( $atrack->{'MessageCumulativeSize'} / 
										$accounting->{'MessageCumulativeSizeLimit'} ) * 100
							);
						} else {
							$pCumulativeSizeUsage .= "/-";
						}

						# Log update to mail log
						$server->maillog("module=Accounting, mode=update, host=%s, helo=%s, from=%s, to=%s, reason=accounting_update, policy=%s, accounting=%s, "
									."track=%s, period=%s, count=%s, size=%s",
								$sessionData->{'ClientAddress'},
								$sessionData->{'Helo'},
								$sessionData->{'Sender'},
								$emailAddy,
								$policyID,
								$accounting->{'ID'},
								$trackKey,
								$periodKey,
								$pCountUsage,
								$pCumulativeSizeUsage);
						# Check if this is the last accounting
						if (defined($accounting->{'LastAccounting'}) && $accounting->{'LastAccounting'} eq "1") {
							last POLICY;
						}
					} # foreach my $accounting (@{$accountings})
				} # foreach my $policyID (@{$sessionData->{'_Recipient_To_Policy'}{$emailAddy}{$priority}})
			} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'_Recipient_To_Policy'}{$emailAddy}})
		} # foreach my $emailAddy (keys %{$sessionData->{'_Recipient_To_Policy'}})

			
	}
	
	# Setup result
	if (!defined($verdict)) {
		return CBP_CONTINUE;
 	} elsif ($verdict eq "") {
		$server->maillog("module=Accounting, action=none, host=%s, helo=%s, from=%s, to=%s, reason=no_verdict",
				$sessionData->{'ClientAddress'},
				$sessionData->{'Helo'},
				$sessionData->{'Sender'},
				$sessionData->{'Recipient'});
		return CBP_CONTINUE;
	} elsif ($verdict =~ /^defer$/i) {
		return $server->protocol_response(PROTO_DEFER,$verdict_data);
	} elsif ($verdict =~ /^hold$/i) {
		return $server->protocol_response(PROTO_HOLD,$verdict_data);
	} elsif ($verdict =~ /^reject$/i) {
		return $server->protocol_response(PROTO_REJECT,$verdict_data);
	} elsif ($verdict =~ /^discard$/i) {
		return $server->protocol_response(PROTO_DISCARD,$verdict_data);
	} elsif ($verdict =~ /^filter$/i) {
		return $server->protocol_response(PROTO_FILTER,$verdict_data);
	} elsif ($verdict =~ /^redirect$/i) {
		return $server->protocol_response(PROTO_REDIRECT,$verdict_data);
	} else {
		$server->log(LOG_ERR,"[ACCOUNTING] Unknown Verdict specification in access control '$verdict'");
		$server->maillog("module=Accounting, action=none, host=%s, helo=%s, from=%s, to=%s, reason=invalid_verdict",
				$sessionData->{'ClientAddress'},
				$sessionData->{'Helo'},
				$sessionData->{'Sender'},
				$sessionData->{'Recipient'});
		return $server->protocol_response(PROTO_DATA_ERROR);
	}
}



# Get key from spec and SASL username
sub getSASLUsernameKey
{
	my ($spec,$username) = @_;

	my $key;

	# Very basic, for blank SASL username, just use '-'
	if (!defined($username)) {
		$key = "-";
	} else {
		$key = $username;
	}

	return $key;
}



# Get key from spec and email addy
sub getEmailKey
{
	my ($spec,$addy) = @_;

	my $key;


	# Short-circuit <>
	if ($addy eq '') {
		return "@";
	}

	# We need to track the sender
	if ($spec eq 'user@domain') {
		$key = $addy;

	} elsif ($spec eq 'user@') {
		($key) = ( $addy =~ /^([^@]+@)/ );

	} elsif ($spec eq '@domain') {
		($key) = ( $addy =~ /^(?:[^@]+)(@.*)/ );
	}

	return $key;
}



# Get accounting records from policyID
sub getAccountings
{
	my ($server,$policyID) = @_;


	my @res;

	# Grab quota data
	my $sth = DBSelect('
		SELECT
			ID,
			Track,
			AccountingPeriod, 
			MessageCountLimit,
			MessageCumulativeSizeLimit,
			Verdict,
			Data,
			LastAccounting
		FROM
			@TP@accounting
		WHERE
			PolicyID = ?
			AND Disabled = 0
		',
		$policyID
	);
	if (!$sth) {
		$server->log(LOG_ERR,"Failed to get accounting data: ".awitpt::db::dblayer::Error());
		return -1;
	}
	while (my $quota = hashifyLCtoMC($sth->fetchrow_hashref(),
			qw( ID Track AccountingPeriod MessageCountLimit MessageCumulativeSizeLimit Verdict Data LastAccounting )
	)) {

		push(@res, $quota);
	}

	return \@res;
}



# Get key from session
sub getTrackKey
{
	my ($server,$accounting,$sessionData) = @_;


	my $res;

	# Split off method and splec
	my ($method,$spec) = ($accounting->{'Track'} =~ /^([^:]+)(?::(\S+))?/);
	
	# Lowercase method & spec
	$method = lc($method);
	$spec = lc($spec) if (defined($spec));

	# Track entire policy
	if ($method eq "policy") {
		$res = "Policy";

	# Check TrackSenderIP
	} elsif ($method eq "senderip") {
		my $key = getIPKey($spec,$sessionData->{'_ClientAddress'});

		# Check for no key
		if (defined($key)) {
			$res = "SenderIP:$key";
		} else {
			$server->log(LOG_WARN,"[ACCOUNTING] Unknown key specification in TrackSenderIP");
		}


	# Check TrackSender
	} elsif ($method eq "sender") {
		# Check if the sender is blank (<>), it makes no sense at present to work out how its tracked, <> is <>
		my $key;
		if ($sessionData->{'Sender'} ne "") {
			$key = getEmailKey($spec,$sessionData->{'Sender'});
		} else {
			$key = "<>";
		}
	
		# Check for no key
		if (defined($key)) {
			$res = "Sender:$key";
		} else {
			$server->log(LOG_WARN,"[ACCOUNTING] Unknown key specification in TrackSender");
		}


	# Check TrackSASLUsername
	} elsif ($method eq "saslusername") {
		my $key = getSASLUsernameKey($spec,$sessionData->{'SASLUsername'});
	
		# Check for no key
		if (defined($key)) {
			$res = "SASLUsername:$key";
		} else {
			$server->log(LOG_WARN,"[ACCOUNTING] Unknown key specification in TrackSASLUsername");
		}

	# Check TrackRecipient
	} elsif ($method eq "recipient") {
		my $key = getEmailKey($spec,$sessionData->{'Recipient'});
	
		# Check for no key
		if (defined($key)) {
			$res = "Recipient:$key";
		} else {
			$server->log(LOG_WARN,"[ACCOUNTING] Unknown key specification in TrackRecipient");
		}
	
	# Fall-through to catch invalid specs
	} else {
		$server->log(LOG_WARN,"[ACCOUNTING] Invalid tracking specification '".$accounting->{'Track'}."'");
	}


	return $res;
}



# Get period key
sub getPeriodKey
{
	my $period = shift;


	my $key;

	# Pull timestamp
	my @timestamp = localtime();

	# Track per day
	if ($period eq "0") {
		$key = strftime("%Y-%m-%d",@timestamp);

	# Track per week
	} elsif ($period eq "1") {
		$key = strftime("%Y/%V",@timestamp);
	
	# Track per month
	} elsif ($period eq "2") {
		$key = strftime("%Y-%m",@timestamp);
	}

	return $key;
}



# Get tracking info
sub getTrackingInfo
{
	my ($server,$accountID,$trackKey,$periodKey) = @_;
	
	
	# Query accounting info
	my $sth = DBSelect('
		SELECT 
			AccountingID,
			TrackKey, PeriodKey,
			MessageCount, MessageCumulativeSize
		FROM
			@TP@accounting_tracking
		WHERE
			AccountingID = ?
			AND TrackKey = ?
			AND PeriodKey = ?
		',
		$accountID,$trackKey,$periodKey
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[ACCOUNTING] Failed to query accounting_tracking: ".awitpt::db::dblayer::Error());
		return -1;
	}
	my $row = hashifyLCtoMC($sth->fetchrow_hashref(),
			qw( AccountingID TrackKey PeriodKey MessageCount MessageCumulativeSize )
	);
	DBFreeRes($sth);

	return $row;
}



## Cleanup function
#sub cleanup
#{
#	my ($server) = @_;
#
#	# Get 30-days ago time
#	my $lastMonth = time() - 2592000;
#
#	# Remove old tracking info from database
#	my $sth = DBDo('
#		DELETE FROM 
#			@TP@accounting_tracking
#		WHERE
#			LastUpdate < ?
#		',
#		$lastMonth
#	);
#	if (!$sth) {
#		$server->log(LOG_ERR,"[ACCOUNTING] Failed to remove old accounting tracking records: ".awitpt::db::dblayer::Error());
#	}
#	$server->log(LOG_INFO,"[ACCOUNTING] Removed ".( $sth ne "0E0" ? $sth : 0).." records from tracking table");
#}



1;
# vim: ts=4
