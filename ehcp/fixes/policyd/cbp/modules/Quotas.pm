# Quotas module
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


package cbp::modules::Quotas;

use strict;
use warnings;


use cbp::logging;
use awitpt::db::dblayer;
use cbp::system;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Quotas Plugin",
	priority		=> 50,
	init		 	=> \&init,
	request_process	=> \&check,
	cleanup		 	=> \&cleanup,
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
	if (defined($inifile->{'quotas'})) {
		foreach my $key (keys %{$inifile->{'quotas'}}) {
			$config{$key} = $inifile->{'quotas'}->{$key};
		}
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Quotas: enabled");
		$config{'enable'} = 1;
		# Enable tracking, we need this to recipients for the message in END-OF-DATA
		$server->{'config'}{'track_sessions'} = 1;
	} else {
		$server->log(LOG_NOTICE,"  => Quotas: disabled");
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
	#   we have exceeded our size quota. The Size quota is updated in the EOM
	#   stage
	#
	if ($sessionData->{'ProtocolState'} eq "RCPT") {

		# Key tracking list, if quotaExceeded is not undef, it will contain the msg
		my %newCounters;  # Indexed by QuotaLimitsID
		my @trackingList;
		my $hasExceeded;
		my $exceededQtrack;

		# Loop with priorities, low to high
POLICY:		foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}}) {

			# Last if we've exceeded
			last if ($hasExceeded);


			# Loop with each policyID
			foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}}) {

				# Last if we've exceeded
				last if ($hasExceeded);

				# Get quota object
				my $quotas = getQuotas($server,$policyID);
				# Check if we got a quota or not
				if (ref $quotas ne "ARRAY") {
					return $server->protocol_response(PROTO_DB_ERROR);
				}
			
				# Loop with quotas
				foreach my $quota (@{$quotas}) {
					# Exceeded limit
					my $exceededLimit;

					# Last if we've exceeded
					last if ($hasExceeded);

					# Grab tracking keys
					my $key = getKey($server,$quota,$sessionData);
					if (!defined($key)) {
						$server->log(LOG_ERR,"[QUOTAS] No key found for quota ID '".$quota->{'ID'}."'");
						return $server->protocol_response(PROTO_DATA_ERROR);
					}
	
					# Get limits
					my $limits = getLimits($server,$quota->{'ID'});
					# Check if we got limits or err
					if (ref $limits ne "ARRAY") {
						return $server->protocol_response(PROTO_DB_ERROR);
					}
	
					# Loop with limits
					foreach my $limit (@{$limits}) {
	
						# Get quota tracking info
						my $qtrack = getTrackingInfo($server,$limit->{'ID'},$key);
	
						# Check if we got tracking info or not
						if (defined($qtrack) && ref $qtrack ne "HASH") {
							return $server->protocol_response(PROTO_DB_ERROR);
						}
						# Check if we have a queue tracking item
						if (defined($qtrack)) {
							my $elapsedTime = defined($qtrack->{'LastUpdate'}) ? ( $now - $qtrack->{'LastUpdate'} ) : $quota->{'Period'};
							# If elapsed time is less than zero, its time diff between servers, meaning no time has elapsed
							$elapsedTime = 0 if ($elapsedTime < 0);
							
							# Check if elapsedTime is longer than period, or negative (time diff between servers?)
							my $currentCounter;
							if ($elapsedTime > $quota->{'Period'}) {
								$currentCounter = 0;
	
							# Calculate the % of the period we have, and multiply it with the counter ... this should give us a reasonably
							# accurate counting
							} else {
								$currentCounter = ( 1 - ($elapsedTime / $quota->{'Period'}) ) * $qtrack->{'Counter'};
							}

							# Work out the difference to the DB value, we ONLY DO THIS ONCE!!! so if its defined, leave it alone!
							if (!defined($newCounters{$qtrack->{'QuotasLimitsID'}})) {
								$newCounters{$qtrack->{'QuotasLimitsID'}} = $currentCounter - $qtrack->{'Counter'};
							}

							# Limit type
							my $limitType = lc($limit->{'Type'});

							# Make sure its the MessageCount counter
							if ($limitType eq "messagecount") {
								# Check for violation
								if ($currentCounter > $limit->{'CounterLimit'}) {
									$hasExceeded = "Policy rejection; Message count quota exceeded";
								}
								# Bump up limit
								$newCounters{$qtrack->{'QuotasLimitsID'}}++;
	
							# Check for cumulative size violation
							} elsif ($limitType eq "messagecumulativesize") {
								# Check for violation
								if ($currentCounter > $limit->{'CounterLimit'}) {
									$hasExceeded = "Policy rejection; Cumulative message size quota exceeded";
								}
							}
	
						} else {
							$qtrack->{'QuotasLimitsID'} = $limit->{'ID'};
							$qtrack->{'TrackKey'} = $key;
							$qtrack->{'Counter'} = 0;
							$qtrack->{'LastUpdate'} = $now;
								
							# Work out the difference to the DB value, we ONLY DO THIS ONCE!!! so if its defined, leave it alone!
							if (!defined($newCounters{$qtrack->{'QuotasLimitsID'}})) {
								$newCounters{$qtrack->{'QuotasLimitsID'}} = $qtrack->{'Counter'};
							}
							
							# Check if this is a message counter
							if (lc($limit->{'Type'}) eq "messagecount") {
								# Bump up limit
								$newCounters{$qtrack->{'QuotasLimitsID'}}++;
							}
						}
						
						# Setup some stuff we need for logging
						$qtrack->{'DBKey'} = $key;
						$qtrack->{'CounterLimit'} = $limit->{'CounterLimit'};
						$qtrack->{'LimitType'} = $limit->{'Type'};
						$qtrack->{'PolicyID'} = $policyID;
						$qtrack->{'QuotaID'} = $quota->{'ID'};
						$qtrack->{'LimitID'} = $limit->{'ID'};
						$qtrack->{'Verdict'} = $quota->{'Verdict'};
						$qtrack->{'VerdictData'} = $quota->{'Data'};

						# If we've exceeded setup the qtrack which was exceeded
						if ($hasExceeded) {
							$exceededQtrack = $qtrack;
						}

						# Save quota tracking info
						push(@trackingList,$qtrack);
	
					}  # foreach my $limit (@{$limits})

					# Check if this is the last quota
					if (defined($quota->{'LastQuota'}) && $quota->{'LastQuota'} eq "1") {
						last POLICY;
					}
				} # foreach my $quota (@{$quotas})
			} # foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}})
		} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}})

		# If we have not exceeded, update
		if (!$hasExceeded) {

			# Loop with tracking ID's and update
			foreach my $qtrack (@trackingList) {
					
				# Percent used
				my $pused =  sprintf('%.1f', ( ($newCounters{$qtrack->{'QuotasLimitsID'}} + $qtrack->{'Counter'}) / $qtrack->{'CounterLimit'} ) * 100);

				# Update database
				my $sth = DBDo('
					UPDATE 
						@TP@quotas_tracking
					SET
						Counter = Counter + ?,
						LastUpdate = ?
					WHERE
						QuotasLimitsID = ?
						AND TrackKey = ?
					',
					$newCounters{$qtrack->{'QuotasLimitsID'}},$now,$qtrack->{'QuotasLimitsID'},$qtrack->{'TrackKey'}
				);
				if (!$sth) {
					$server->log(LOG_ERR,"[QUOTAS] Failed to update quota_tracking item: ".awitpt::db::dblayer::Error());
					return $server->protocol_response(PROTO_DB_ERROR);
				}
				
				# If nothing updated, then insert our record
				if ($sth eq "0E0") {
					# Insert into database
					my $sth = DBDo('
						INSERT INTO @TP@quotas_tracking
							(QuotasLimitsID,TrackKey,LastUpdate,Counter)
						VALUES
							(?,?,?,?)
						',
						$qtrack->{'QuotasLimitsID'},$qtrack->{'TrackKey'},$qtrack->{'LastUpdate'},$newCounters{$qtrack->{'QuotasLimitsID'}}
					);
					if (!$sth) {
						$server->log(LOG_ERR,"[QUOTAS] Failed to insert quota_tracking item: ".awitpt::db::dblayer::Error());
						return $server->protocol_response(PROTO_DB_ERROR);
					}
					
					# Log create to mail log
					$server->maillog("module=Quotas, mode=create, host=%s, helo=%s, from=%s, to=%s, reason=quota_create, policy=%s, quota=%s, limit=%s, "
								."track=%s, counter=%s, quota=%s/%s (%s%%)",
							$sessionData->{'ClientAddress'},
							$sessionData->{'Helo'},
							$sessionData->{'Sender'},
							$sessionData->{'Recipient'},
							$qtrack->{'PolicyID'},
							$qtrack->{'QuotaID'},
							$qtrack->{'LimitID'},
							$qtrack->{'DBKey'},
							$qtrack->{'LimitType'},
							sprintf('%.2f',$newCounters{$qtrack->{'QuotasLimitsID'}} + $qtrack->{'Counter'}),
							$qtrack->{'CounterLimit'},
							$pused);


				# If we updated ...
				} else {
					# Log update to mail log
					$server->maillog("module=Quotas, mode=update, host=%s, helo=%s, from=%s, to=%s, reason=quota_update, policy=%s, quota=%s, limit=%s, "
								."track=%s, counter=%s, quota=%s/%s (%s%%)",
							$sessionData->{'ClientAddress'},
							$sessionData->{'Helo'},
							$sessionData->{'Sender'},
							$sessionData->{'Recipient'},
							$qtrack->{'PolicyID'},
							$qtrack->{'QuotaID'},
							$qtrack->{'LimitID'},
							$qtrack->{'DBKey'},
							$qtrack->{'LimitType'},
							sprintf('%.2f',$newCounters{$qtrack->{'QuotasLimitsID'}} + $qtrack->{'Counter'}),
							$qtrack->{'CounterLimit'},
							$pused);

				}
					

				# Remove limit
				delete($newCounters{$qtrack->{'QuotasLimitsID'}});
			}

		# If we have exceeded, set verdict
		} else {
			# Percent used
			my $pused =  sprintf('%.1f', ( ($newCounters{$exceededQtrack->{'QuotasLimitsID'}} + $exceededQtrack->{'Counter'}) / $exceededQtrack->{'CounterLimit'} ) * 100);

			# Log rejection to mail log
			$server->maillog("module=Quotas, action=%s, host=%s, helo=%s, from=%s, to=%s, reason=quota_match, policy=%s, quota=%s, limit=%s, track=%s, "
						."counter=%s, quota=%s/%s (%s%%)",
					lc($exceededQtrack->{'Verdict'}),
					$sessionData->{'ClientAddress'},
					$sessionData->{'Helo'},
					$sessionData->{'Sender'},
					$sessionData->{'Recipient'},
					$exceededQtrack->{'PolicyID'},
					$exceededQtrack->{'QuotaID'},
					$exceededQtrack->{'LimitID'},
					$exceededQtrack->{'DBKey'},
					$exceededQtrack->{'LimitType'},
					sprintf('%.2f',$newCounters{$exceededQtrack->{'QuotasLimitsID'}} + $exceededQtrack->{'Counter'}),
					$exceededQtrack->{'CounterLimit'},
					$pused);

			$verdict = $exceededQtrack->{'Verdict'};
			$verdict_data = (defined($exceededQtrack->{'VerdictData'}) && $exceededQtrack->{'VerdictData'} ne "") 
					? $exceededQtrack->{'VerdictData'} : $hasExceeded;
		}

	#
	# END-OF-MESSAGE state
	#   The Size quota is updated in this state
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

					# Check if we got a quota or not
					my $quotas = getQuotas($server,$policyID);
					if (ref $quotas ne "ARRAY") {
						return $server->protocol_response(PROTO_DB_ERROR);
					}

					# Loop with quotas
					foreach my $quota (@{$quotas}) {

						# HACK: Fool getKey into thinking we actually do have a recipient
						$sessionData->{'Recipient'} = $emailAddy;
	
						# Grab tracking keys
						my $key = getKey($server,$quota,$sessionData);
						if (!defined($key)) {
							$server->log(LOG_WARN,"[QUOTAS] No key found for quota ID '".$quota->{'ID'}."'");
							return $server->protocol_response(PROTO_DATA_ERROR);
						}
	
						# Get limits
						my $limits = getLimits($server,$quota->{'ID'});
						# Check if we got limits or err
						if (ref $limits ne "ARRAY") {
							return $server->protocol_response(PROTO_DB_ERROR);
						}
	
						# Loop with limits
						foreach my $limit (@{$limits}) {
	
							# Get quota tracking info
							my $qtrack = getTrackingInfo($server,$limit->{'ID'},$key);
							# Check if we got tracking info or not
							if (ref $qtrack ne "HASH") {
								next; # If not just carry on?
							}

							# Check if we're working with cumulative sizes
							if (lc($limit->{'Type'}) eq "messagecumulativesize") {
								# Bump up counter
								my $currentCounter = $qtrack->{'Counter'} + $sessionData->{'Size'};
								
								# Update database
								my $sth = DBDo('
									UPDATE 
										@TP@quotas_tracking
									SET
										Counter = Counter + ?,
										LastUpdate = ?
									WHERE
										QuotasLimitsID = ?
										AND TrackKey = ?
									',
									$sessionData->{'Size'},$now,$qtrack->{'QuotasLimitsID'},$qtrack->{'TrackKey'}
								);
								if (!$sth) {
									$server->log(LOG_ERR,"[QUOTAS] Failed to update quota_tracking item: ".awitpt::db::dblayer::Error());
									return $server->protocol_response(PROTO_DB_ERROR);
								}

								# Percent used
								my $pused =  sprintf('%.1f', ( $currentCounter / $limit->{'CounterLimit'} ) * 100);

								# Log update to mail log
								$server->maillog("module=Quotas, mode=update, host=%s, helo=%s, from=%s, to=%s, reason=quota_update, policy=%s, "
											."quota=%s, limit=%s, track=%s, counter=%s, quota=%s/%s (%s%%)",
										$sessionData->{'ClientAddress'},
										$sessionData->{'Helo'},
										$sessionData->{'Sender'},
										$emailAddy,
										$policyID,
										$quota->{'ID'},
										$limit->{'ID'},
										$key,
										$limit->{'Type'},
										sprintf('%.2f',$currentCounter),
										$limit->{'CounterLimit'},
										$pused);
							} # if (lc($limit->{'Type'}) eq "messagecumulativesize")
						} # foreach my $limit (@{$limits})

						# Check if this is the last quota
						if (defined($quota->{'LastQuota'}) && $quota->{'LastQuota'} eq "1") {
							last POLICY;
						}
					} # foreach my $quota (@{$quotas})
				} # foreach my $policyID (@{$sessionData->{'_Recipient_To_Policy'}{$emailAddy}{$priority}})
			} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'_Recipient_To_Policy'}{$emailAddy}})
		} # foreach my $emailAddy (keys %{$sessionData->{'_Recipient_To_Policy'}})

			
	}
	
	# Setup result
	if (!defined($verdict)) {
		return CBP_CONTINUE;
	} elsif ($verdict eq "") {
		$server->maillog("module=Quotas, action=none, host=%s, helo=%s, from=%s, to=%s, reason=no_verdict",
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
		$server->log(LOG_ERR,"[QUOTAS] Unknown Verdict specification in access control '$verdict'");
		$server->maillog("module=Quotas, action=none, host=%s, helo=%s, from=%s, to=%s, reason=invalid_verdict",
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


# Get quota from policyID
sub getQuotas
{
	my ($server,$policyID) = @_;


	my @res;

	# Grab quota data
	my $sth = DBSelect('
		SELECT
			ID,
			Period, Track, Verdict,	Data, LastQuota
		FROM
			@TP@quotas
		WHERE
			PolicyID = ?
			AND Disabled = 0
		',
		$policyID
	);
	if (!$sth) {
		$server->log(LOG_ERR,"Failed to get quota data: ".awitpt::db::dblayer::Error());
		return -1;
	}
	while (my $quota = hashifyLCtoMC($sth->fetchrow_hashref(),
			qw( ID Period Track Verdict Data LastQuota )
	)) {

		push(@res, $quota);
	}

	return \@res;
}


# Get key from session
sub getKey
{
	my ($server,$quota,$sessionData) = @_;


	my $res;


	# Split off method and splec
	my ($method,$spec) = ($quota->{'Track'} =~ /^([^:]+)(?::(\S+))?/);
	
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
			$server->log(LOG_WARN,"[QUOTAS] Unknown key specification in TrackSenderIP");
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
			$server->log(LOG_WARN,"[QUOTAS] Unknown key specification in TrackSender");
		}


	# Check TrackSASLUsername
	} elsif ($method eq "saslusername") {
		my $key = getSASLUsernameKey($spec,$sessionData->{'SASLUsername'});
	
		# Check for no key
		if (defined($key)) {
			$res = "SASLUsername:$key";
		} else {
			$server->log(LOG_WARN,"[QUOTAS] Unknown key specification in TrackSASLUsername");
		}

	# Check TrackRecipient
	} elsif ($method eq "recipient") {
		my $key = getEmailKey($spec,$sessionData->{'Recipient'});
	
		# Check for no key
		if (defined($key)) {
			$res = "Recipient:$key";
		} else {
			$server->log(LOG_WARN,"[QUOTAS] Unknown key specification in TrackRecipient");
		}
	
	# Fall-through to catch invalid specs
	} else {
		$server->log(LOG_WARN,"[QUOTAS] Invalid tracking specification '".$quota->{'Track'}."'");
	}


	return $res;
}


# Get tracking info
sub getTrackingInfo
{
	my ($server,$quotaID,$key) = @_;
	
	
	# Query quota info
	my $sth = DBSelect('
		SELECT 
			QuotasLimitsID,
			TrackKey, Counter, LastUpdate
		FROM
			@TP@quotas_tracking
		WHERE
			QuotasLimitsID = ?
			AND TrackKey = ?
		',
		$quotaID,$key
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[QUOTAS] Failed to query quotas_tracking: ".awitpt::db::dblayer::Error());
		return -1;
	}
	my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( QuotasLimitsID TrackKey Counter LastUpdate )); 
	DBFreeRes($sth);

	return $row;
}


# Get limits
sub getLimits
{
	my ($server,$quotasID) = @_;

	# Query quota info
	my $sth = DBSelect('
		SELECT 
			ID,
			Type, CounterLimit
		FROM
			@TP@quotas_limits
		WHERE
			QuotasID = ?
			AND Disabled = 0
		',
		$quotasID
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[QUOTAS] Failed to query quotas_limits: ".awitpt::db::dblayer::Error());
		return -1;
	}
	my $list = [];
	while (my $qtrack = hashifyLCtoMC($sth->fetchrow_hashref(), qw( ID Type CounterLimit ))) {
		push(@{$list}, $qtrack);
	}

	return $list;
}


# Cleanup function
sub cleanup
{
	my ($server) = @_;

	# Get 30-days ago time
	my $lastMonth = time() - 2592000;

	# Remove old tracking info from database
	my $sth = DBDo('
		DELETE FROM 
			@TP@quotas_tracking
		WHERE
			LastUpdate < ?
		',
		$lastMonth
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[QUOTAS] Failed to remove old quota tracking records: ".awitpt::db::dblayer::Error());
		return -1;
	}
	$server->log(LOG_INFO,"[QUOTAS] Removed ".( $sth ne "0E0" ? $sth : 0).." records from tracking table");
}



1;
# vim: ts=4
