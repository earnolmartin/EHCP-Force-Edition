# Greylisting module
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


package cbp::modules::Greylisting;

use strict;
use warnings;


use cbp::logging;
use awitpt::cache;
use awitpt::db::dblayer;
use awitpt::netip;
use cbp::system;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Greylisting Plugin",
	priority		=> 60,
	init		 	=> \&init,
	request_process	=> \&check,
	cleanup		 	=> \&cleanup,
};


# Our config
my %config;


# Create a child specific context
sub init {
	my $server = shift;
	my $inifile = $server->{'inifile'};

	# Defaults
	$config{'enable'} = 0;
	$config{'training_mode'} = 0;
	$config{'defer_message'} = "Greylisting in effect, please come back later";
	$config{'blacklist_message'} = "Greylisting in effect, sending server blacklisted";

	my $moreInfo = "";

	# Parse in config
	if (defined($inifile->{'greylisting'})) {
		foreach my $key (keys %{$inifile->{'greylisting'}}) {
			$config{$key} = $inifile->{'greylisting'}->{$key};
		}
	}

	# Check if training
	if ($config{'training_mode'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Greylisting training mode: enabled");
		$config{'training_mode'} = 1;
		$moreInfo .= " (TRAINING)";
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Greylisting: enabled$moreInfo");
		$config{'enable'} = 1;
	} else {
		$server->log(LOG_NOTICE,"  => Greylisting: disabled");
	}

}


# Do our check
sub check {
	my ($server,$sessionData) = @_;
	my $log = defined($server->{'config'}{'logging'}{'modules'});


	# If we not enabled, don't do anything
	return CBP_SKIP if (!$config{'enable'});

	# We only valid in the RCPT state
	return CBP_SKIP if (!defined($sessionData->{'ProtocolState'}) || $sessionData->{'ProtocolState'} ne "RCPT");
	
	# Check if we have any policies matched, if not just pass
	return CBP_SKIP if (!defined($sessionData->{'Policy'}));

	# Policy we're about to build
	my %policy;

	# Loop with priorities, low to high
	foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}}) {

		# Loop with policies
		foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}}) {

			# Grab greylisting info
			my $sth = DBSelect('
				SELECT
					UseGreylisting, GreylistPeriod,
					Track,
					GreylistAuthValidity, GreylistUnAuthValidity,

					UseAutoWhitelist, AutoWhitelistPeriod, AutoWhitelistCount, AutoWhitelistPercentage,
					UseAutoBlacklist, AutoBlacklistPeriod, AutoBlacklistCount, AutoBlacklistPercentage

				FROM
					@TP@greylisting

				WHERE
					PolicyID = ?
					AND Disabled = 0
				',
				$policyID
			);
			if (!$sth) {
				$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
				return $server->protocol_response(PROTO_DB_ERROR);
			}
			# Loop with rows and build end policy
			while (my $row = hashifyLCtoMC($sth->fetchrow_hashref(),
					qw(
						UseGreylisting GreylistPeriod Track	GreylistAuthValidity 
						GreylistUnAuthValidity	UseAutoWhitelist AutoWhitelistPeriod 
						AutoWhitelistCount AutoWhitelistPercentage	UseAutoBlacklist 
						AutoBlacklistPeriod AutoBlacklistCount AutoBlacklistPercentage 
					)
			)) {

				$policy{'Identifier'} .= ":$policyID";

				# If defined, its to override
				if (defined($row->{'UseGreylisting'})) {
					$policy{'UseGreylisting'} = $row->{'UseGreylisting'};
				}
				if (defined($row->{'GreylistPeriod'})) {
					$policy{'GreylistPeriod'} = $row->{'GreylistPeriod'};
				}
				if (defined($row->{'Track'})) {
					$policy{'Track'} = $row->{'Track'};
				}
				if (defined($row->{'GreylistAuthValidity'})) {
					$policy{'GreylistAuthValidity'} = $row->{'GreylistAuthValidity'};
				}
				if (defined($row->{'GreylistUnAuthValidity'})) {
					$policy{'GreylistUnAuthValidity'} = $row->{'GreylistUnAuthValidity'};
				}
	
				if (defined($row->{'UseAutoWhitelist'})) {
					$policy{'UseAutoWhitelist'} = $row->{'UseAutoWhitelist'};
				}
				if (defined($row->{'AutoWhitelistPeriod'})) {
					$policy{'AutoWhitelistPeriod'} = $row->{'AutoWhitelistPeriod'};
				}
				if (defined($row->{'AutoWhitelistCount'})) {
					$policy{'AutoWhitelistCount'} = $row->{'AutoWhitelistCount'};
				}
				if (defined($row->{'AutoWhitelistPercentage'})) {
					$policy{'AutoWhitelistPercentage'} = $row->{'AutoWhitelistPercentage'};
				}
	
				if (defined($row->{'UseAutoBlacklist'})) {
					$policy{'UseAutoBlacklist'} = $row->{'UseAutoBlacklist'};
				}
				if (defined($row->{'AutoBlacklistPeriod'})) {
					$policy{'AutoBlacklistPeriod'} = $row->{'AutoBlacklistPeriod'};
				}
				if (defined($row->{'AutoBlacklistCount'})) {
					$policy{'AutoBlacklistCount'} = $row->{'AutoBlacklistCount'};
				}
				if (defined($row->{'AutoBlacklistPercentage'})) {
					$policy{'AutoBlacklistPercentage'} = $row->{'AutoBlacklistPercentage'};
				}
	
			} # while (my $row = $sth->fetchrow_hashref())
		} # foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}})
	} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}})

	# Check if we have a policy
	if (!%policy) {
		return CBP_CONTINUE;
	}

	# 
	# Check if we must use greylisting
	#
	if (defined($policy{'UseGreylisting'}) && $policy{'UseGreylisting'} ne "1") {
		return CBP_SKIP;
	}

	#
	# Check if we're whitelisted
	#
	my $sth = DBSelect('
		SELECT
			Source
		FROM
			@TP@greylisting_whitelist
		WHERE
			Disabled = 0
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
		return $server->protocol_response(PROTO_DB_ERROR);
	}
	# Loop with whitelist and calculate
	while (my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Source ))) {
		
		# Check format is SenderIP
		if ((my $raw_waddress = $row->{'Source'}) =~ s/^SenderIP://i) {

			# Create our IP object
			my $waddress = new awitpt::netip($raw_waddress);
			if (!defined($waddress)) {
				$server->log(LOG_WARN,"[GREYLISTING] Skipping invalid address '$raw_waddress'.");
				next;
			}
			# Check if IP is whitelisted
			if ($sessionData->{'_ClientAddress'}->is_within($waddress)) {
				$server->maillog("module=Greylisting, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=whitelisted",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				DBFreeRes($sth);
				return $server->protocol_response(PROTO_PASS);
			}

		} else {
			$server->log(LOG_WARN,"[GREYLISTING] Skipping invalid whitelist entry '".$row->{'Source'}."'.");
		}
	}
	DBFreeRes($sth);


	#
	# Get tracking key used below
	#
	my $key = getKey($server,$policy{'Track'},$sessionData);
	if (!defined($key)) {
		$server->log(LOG_ERR,"[GREYLISTING] Failed to get key from tracking spec '".$policy{'Track'}."'");
		return $server->protocol_response(PROTO_DATA_ERROR);
	}

	# Do we have entries that we can use?
	my $currentAutoBlacklistEntry;
	my $currentAutoWhitelistEntry;

	#
	# Check if we we must use auto-whitelisting and if we're auto-whitelisted
	#
	if (defined($policy{'UseAutoWhitelist'}) && $policy{'UseAutoWhitelist'} eq "1") {

		# Sanity check, no use doing the query to find out we don't have a period
		if (defined($policy{'AutoWhitelistPeriod'}) && $policy{'AutoWhitelistPeriod'} > 0) {
			my $sth = DBSelect('
				SELECT
					ID, LastSeen
				FROM
					@TP@greylisting_autowhitelist
				WHERE
					TrackKey = ?
				',
				$key
			);
			if (!$sth) {
				$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
				return $server->protocol_response(PROTO_DB_ERROR);
			}
			my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( ID LastSeen ));

			# Pull off first row
			if ($row) {

				# Check if we're within the auto-whitelisting period
				if ($sessionData->{'UnixTimestamp'} - $row->{'LastSeen'} <= $policy{'AutoWhitelistPeriod'}) {

					my $sth = DBDo('
						UPDATE
							@TP@greylisting_autowhitelist
						SET
							LastSeen = ?
						WHERE
							TrackKey = ?
						',
						$sessionData->{'UnixTimestamp'},$key
					);
					if (!$sth) {
						$server->log(LOG_ERR,"[GREYLISTING] Database update failed: ".awitpt::db::dblayer::Error());
						return $server->protocol_response(PROTO_DB_ERROR);
					}

					$server->maillog("module=Greylisting, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=auto-whitelisted",
							$sessionData->{'ClientAddress'},
							$sessionData->{'Helo'},
							$sessionData->{'Sender'},
							$sessionData->{'Recipient'});

					return $server->protocol_response(PROTO_PASS);
				# We already have a auto-whitelist entry, but its old
				} else {
					$currentAutoWhitelistEntry = $row->{'ID'};
				}
			} # if ($row)

		} else {  # if (defined($policy{'AutoWhitelistPeriod'}) && $policy{'AutoWhitelistPeriod'} > 0)
			$server->log(LOG_ERR,"[GREYLISTING] Resolved policy UseAutoWhitelist is set, but AutoWhitelistPeriod is not set or invalid");
			return $server->protocol_response(PROTO_DATA_ERROR);
		}
	} # if (defined($policy{'UseAutoWhitelist'}) && $policy{'UseAutoWhitelist'} eq "1")


	#
	# Check if we we must use auto-blacklisting and check if we're blacklisted
	#
	if (!$config{'training_mode'} && defined($policy{'UseAutoBlacklist'}) && $policy{'UseAutoBlacklist'} eq "1") {

		# Sanity check, no use doing the query to find out we don't have a period
		if (defined($policy{'AutoBlacklistPeriod'}) && $policy{'AutoBlacklistPeriod'} > 0) {
			# Check cache
			my ($cache_res,$cache) = cacheGetKeyPair('Greylisting/Auto-Blacklist/PolicyIdentifier-Blacklisted-IP',
					$policy{'Identifier'}."/".$sessionData->{'ClientAddress'});
			if ($cache_res) {
				return $server->protocol_response(PROTO_ERROR);
			}

			# Check if we have a cache value and if its a match
			if (defined($cache) && $cache) {
				$server->maillog("module=Greylisting, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=auto-blacklisted_cached",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});

				return $server->protocol_response(PROTO_REJECT,$config{'blacklist_message'});

			# Else lets do a query...
			} else {
				my $sth = DBSelect('
					SELECT
						ID, Added
					FROM
						@TP@greylisting_autoblacklist
					WHERE
						TrackKey = ?
					',
					$key
				);
				if (!$sth) {
					$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
					return $server->protocol_response(PROTO_DB_ERROR);
				}
				my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( ID Added ));
	
				# Pull off first row
				if ($row) {
	
					# Check if we're within the auto-blacklisting period
					if ($sessionData->{'UnixTimestamp'} - $row->{'Added'} <= $policy{'AutoBlacklistPeriod'}) {
						# Cache positive result
						my $cache_res = cacheStoreKeyPair(
								'Greylisting/Auto-Blacklist/PolicyIdentifier-Blacklisted-IP',
								$policy{'Identifier'}."/".$sessionData->{'ClientAddress'},1);
						if ($cache_res) {
							return $server->protocol_response(PROTO_ERROR);
						}
	
						$server->maillog("module=Greylisting, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=auto-blacklisted",
								$sessionData->{'ClientAddress'},
								$sessionData->{'Helo'},
								$sessionData->{'Sender'},
								$sessionData->{'Recipient'});
	
						return $server->protocol_response(PROTO_REJECT,$config{'blacklist_message'});
					# We already have a auto-blacklist entry, but its old
					} else {
						$currentAutoBlacklistEntry = $row->{'ID'};
					}
				}

			}

		} else {  # if (defined($policy{'AutoBlacklistPeriod'}) && $policy{'AutoBlacklistPeriod'} > 0)
			$server->log(LOG_ERR,"[GREYLISTING] Resolved policy UseAutoBlacklist is set, but AutoBlacklistPeriod is not set or invalid");
			return $server->protocol_response(PROTO_DATA_ERROR);
		}
	} # if (defined($policy{'UseAutoBlacklist'}) && $policy{'UseAutoBlacklist'} eq "1")


	#
	# Update/Insert record into database
	#

	# Insert/update triplet in database
	$sth = DBDo('
		UPDATE 
			@TP@greylisting_tracking
		SET
			LastUpdate = ?
		WHERE
			TrackKey = ?
			AND Sender = ?
			AND Recipient = ?
		',
		$sessionData->{'UnixTimestamp'},$key,$sessionData->{'Sender'},$sessionData->{'Recipient'}
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Database update failed: ".awitpt::db::dblayer::Error());
		return $server->protocol_response(PROTO_DB_ERROR);
	}
	# If we didn't update anything, insert
	if ($sth eq "0E0") {
		#
		# Check if we must blacklist the host for abuse ...
		#
		if (!$config{'training_mode'} && defined($policy{'UseAutoBlacklist'}) && $policy{'UseAutoBlacklist'} eq "1") {

			# Only proceed if we have a period
			if (defined($policy{'AutoBlacklistPeriod'}) && $policy{'AutoBlacklistPeriod'} > 0) {

				# Check if we have a count
				if (defined($policy{'AutoBlacklistCount'}) && $policy{'AutoBlacklistCount'} > 0) {

					# Work out time to check from...
					my $addedTime = $sessionData->{'UnixTimestamp'} - $policy{'AutoBlacklistPeriod'};
	
					my $sth = DBSelect('
						SELECT
							Count(*) AS TotalCount
						FROM
							@TP@greylisting_tracking
						WHERE
							TrackKey = ?
							AND FirstSeen >= ?
						',
						$key,
						$addedTime
					);
					if (!$sth) {
						$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
						return $server->protocol_response(PROTO_DB_ERROR);
					}
					my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( TotalCount ));
					my $totalCount = defined($row->{'TotalCount'}) ? $row->{'TotalCount'} : 0;
	
	
					# If count exceeds or equals blacklist count, nail the server
					if ($totalCount > 0 && $totalCount >= $policy{'AutoBlacklistCount'}) {
						# Start off as undef
						my $blacklist;
	
						$sth = DBSelect('
							SELECT
								Count(*) AS FailCount
							FROM
								@TP@greylisting_tracking
							WHERE
								TrackKey = ?
								AND FirstSeen >= ?
								AND Count = 0
							',
							$key,$addedTime
						);
						if (!$sth) {
							$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
							return $server->protocol_response(PROTO_DB_ERROR);
						}
						$row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( FailCount ));
						my $failCount = defined($row->{'FailCount'}) ? $row->{'FailCount'} : 0;
	
						# Check if we should blacklist this host
						if (defined($policy{'AutoBlacklistPercentage'}) && $policy{'AutoBlacklistPercentage'} > 0) {
					
							my $percentage = ( $failCount / $totalCount ) * 100;
	
							# If we meet the percentage of unauthenticated triplets, blacklist
							if ($percentage >= $policy{'AutoBlacklistPercentage'} ) {
								$blacklist = sprintf("Auto-blacklisted: TotalCount/Required = %s/%s, Percentage/Threshold = %s/%s",
										$totalCount, $policy{'AutoBlacklistCount'},
										$percentage, $policy{'AutoBlacklistPercentage'});
							}
						# This is not a percentage check
						} else {
							# Check if we exceed
							if ($failCount >= $policy{'AutoBlacklistCount'}) {
								$blacklist = sprintf("Auto-blacklisted: Count/Required = %s/%s", $failCount, 
										$policy{'AutoBlacklistCount'});
							}
						}
					
						# If we are to be listed, this is our reason
						if ($blacklist) {
							# Check if we already have an expired autoblacklist entry, this happens if 
							# the cleanup has not run yet
							if (defined($currentAutoBlacklistEntry)) {
								# Update blacklisting to the new details
								$sth = DBDo('
									UPDATE 
										@TP@greylisting_autoblacklist
									SET
										TrackKey = ?,
										Added = ?,
										Comment = ?
									WHERE
										ID = ?
									',
									$key,$sessionData->{'UnixTimestamp'},$blacklist,$currentAutoBlacklistEntry
								);
								if (!$sth) {
									$server->log(LOG_ERR,"[GREYLISTING] Database update failed: ".
											awitpt::db::dblayer::Error());
									return $server->protocol_response(PROTO_DB_ERROR);
								}
							# If we don't have an entry we can use, create one
							} else {
								# Record blacklisting
								$sth = DBDo('
									INSERT INTO @TP@greylisting_autoblacklist
										(TrackKey,Added,Comment)
									VALUES
										(?,?,?)
									',
									$key,$sessionData->{'UnixTimestamp'},$blacklist
								);
								if (!$sth) {
									$server->log(LOG_ERR,"[GREYLISTING] Database insert failed: ".
											awitpt::db::dblayer::Error());
									return $server->protocol_response(PROTO_DB_ERROR);
								}
							}
	
							# Cache positive result
							my $cache_res = cacheStoreKeyPair(
									'Greylisting/Auto-Blacklist/PolicyIdentifier-Blacklisted-IP',
									$policy{'Identifier'}."/".$sessionData->{'ClientAddress'},1);
							if ($cache_res) {
								return $server->protocol_response(PROTO_ERROR);
							}
	
							$server->maillog("module=Greylisting, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=auto-blacklisted",
									$sessionData->{'ClientAddress'},
									$sessionData->{'Helo'},
									$sessionData->{'Sender'},
									$sessionData->{'Recipient'});
	
							return $server->protocol_response(PROTO_REJECT,$config{'blacklist_message'});
						}
					} # if ($totalCount > 0 && $totalCount >= $policy{'AutoBlacklistCount'})
				} # if (defined($policy{'AutoBlacklistCount'}) && $policy{'AutoBlacklistCount'} > 0)

			} else { # if (defined($policy{'AutoBlacklistPeriod'}) && $policy{'AutoBlacklistPeriod'} > 0)
				$server->log(LOG_ERR,"[GREYLISTING] Resolved policy UseAutoWBlacklist is set, but AutoBlacklistPeriod is not set or invalid");
				return $server->protocol_response(PROTO_DATA_ERROR);
			}
		}

		# Record triplet
		$sth = DBDo('
			INSERT INTO @TP@greylisting_tracking
				(TrackKey,Sender,Recipient,FirstSeen,LastUpdate,Tries,Count)
			VALUES
				(?,?,?,?,?,1,0)
			',
			$key,$sessionData->{'Sender'},$sessionData->{'Recipient'},$sessionData->{'UnixTimestamp'},$sessionData->{'UnixTimestamp'}
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Database insert failed: ".awitpt::db::dblayer::Error());
			return $server->protocol_response(PROTO_DB_ERROR);
		}

		# Make sure we're not in training mode
		if (!$config{'training_mode'}) {
			$server->maillog("module=Greylisting, action=defer, host=%s, helo=%s, from=%s, to=%s, reason=greylisted",
					$sessionData->{'ClientAddress'},
					$sessionData->{'Helo'},
					$sessionData->{'Sender'},
					$sessionData->{'Recipient'});

			# Skip to rejection, if we using greylisting 0 seconds is highly unlikely to be a greylisitng period
			return $server->protocol_response(PROTO_DEFER,"451 4.7.1 ".$config{'defer_message'});
		}

	# And just a bit of debug
	} else {
		$server->log(LOG_DEBUG,"[GREYLISTING] Updated greylisting triplet ('$key','".$sessionData->{'Sender'}."','".
				$sessionData->{'Recipient'}."') @ ".$sessionData->{'UnixTimestamp'}."") if ($log);
	}


	#
	# Retrieve record from database and check time elapsed
	#

	# Pull triplet and check
	$sth = DBSelect('
		SELECT
			FirstSeen,
			LastUpdate,
			Tries

		FROM
			@TP@greylisting_tracking

		WHERE
			TrackKey = ?
			AND Sender = ?
			AND Recipient = ?
		',
		$key,$sessionData->{'Sender'},$sessionData->{'Recipient'}
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
		return $server->protocol_response(PROTO_DB_ERROR);
	}
	my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( FirstSeen LastUpdate Tries ));
	if (!$row) {
		$server->log(LOG_ERR,"[GREYLISTING] Failed to find triplet in database");
		return $server->protocol_response(PROTO_DB_ERROR);
	}

	# Check if we should greylist, or not
	my $timeElapsed = $row->{'LastUpdate'} - $row->{'FirstSeen'};
	if (!$config{'training_mode'} && $timeElapsed < $policy{'GreylistPeriod'}) {
		# Get time left, debug and return
		my $timeLeft = $policy{'GreylistPeriod'} - $timeElapsed;
		$server->maillog("module=Greylisting, action=defer, host=%s, helo=%s, from=%s, to=%s, reason=greylisted, tries=%s",
				$sessionData->{'ClientAddress'},
				$sessionData->{'Helo'},
				$sessionData->{'Sender'},
				$sessionData->{'Recipient'},
				$row->{'Tries'} + 1);

		# Update stats
		my $sth = DBDo('
			UPDATE 
				@TP@greylisting_tracking
			SET
				Tries = Tries + 1
			WHERE
				TrackKey = ?
				AND Sender = ?
				AND Recipient = ?
			',
			$key,$sessionData->{'Sender'},$sessionData->{'Recipient'}
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Database update failed: ".awitpt::db::dblayer::Error());
			return $server->protocol_response(PROTO_DB_ERROR);
		}

		return $server->protocol_response(PROTO_DEFER,"451 4.7.1 ".$config{'defer_message'});

	} else {
		# Insert/update triplet in database
		my $sth = DBDo('
			UPDATE 
				@TP@greylisting_tracking
			SET
				Count = Count + 1
			WHERE
				TrackKey = ?
				AND Sender = ?
				AND Recipient = ?
			',
			$key,$sessionData->{'Sender'},$sessionData->{'Recipient'}
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Database update failed: ".awitpt::db::dblayer::Error());
			return $server->protocol_response(PROTO_DB_ERROR);
		}

		#
		# Check if we must whitelist the host for being good
		#
		if (defined($policy{'UseAutoWhitelist'}) && $policy{'UseAutoWhitelist'} eq "1") {

			# Only proceed if we have a period
			if (defined($policy{'AutoWhitelistPeriod'}) && $policy{'AutoWhitelistPeriod'} > 0) {

				# Check if we have a count
				if (defined($policy{'AutoWhitelistCount'}) && $policy{'AutoWhitelistCount'} > 0) {
					my $addedTime = $sessionData->{'UnixTimestamp'} - $policy{'AutoWhitelistPeriod'};

					my $sth = DBSelect('
						SELECT
							Count(*) AS TotalCount
						FROM
							@TP@greylisting_tracking
						WHERE
							TrackKey = ?
							AND FirstSeen >= ?
						',
						$key,$addedTime
					);
					if (!$sth) {
						$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
						return $server->protocol_response(PROTO_DB_ERROR);
					}
					my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( TotalCount ));
					my $totalCount = defined($row->{'TotalCount'}) ? $row->{'TotalCount'} : 0;

					# If count exceeds or equals whitelist count, nail the server
					if ($totalCount >= $policy{'AutoWhitelistCount'}) {
						my $whitelist;

						$sth = DBSelect('
							SELECT
								Count(*) AS PassCount
							FROM
								@TP@greylisting_tracking
							WHERE
								TrackKey = ?
								AND FirstSeen >= ?
								AND Count != 0
							',
							$key,$addedTime
						);
						if (!$sth) {
							$server->log(LOG_ERR,"[GREYLISTING] Database query failed: ".awitpt::db::dblayer::Error());
							return $server->protocol_response(PROTO_DB_ERROR);
						}
						$row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( PassCount ));
						my $passCount = defined($row->{'PassCount'}) ? $row->{'PassCount'} : 0;
				
						# Check if we should whitelist this host
						if (defined($policy{'AutoWhitelistPercentage'}) && $policy{'AutoWhitelistPercentage'} > 0) {
							# Cannot divide by zero
							my $percentage = ( $passCount / $totalCount ) * 100;
							# If we meet the percentage of unauthenticated triplets, whitelist
							if ($percentage >= $policy{'AutoWhitelistPercentage'} ) {
								$whitelist = sprintf("Auto-whitelisted: TotalCount/Required = %s/%s, Percentage/Threshold/Required = %s/%s",
										$totalCount, $policy{'AutoWhitelistCount'},
										$percentage, $policy{'AutoWhitelistPercentage'});
							}
	
						} else {
							# Check if we exceed
							if ($passCount >= $policy{'AutoWhitelistCount'}) {
								$whitelist = sprintf("Auto-whitelisted: Count/Required = %s/%s", $passCount, $policy{'AutoWhitelistCount'});
							}
						}
	
						# If we are to be listed, this is our reason
						if ($whitelist) {
							# Check if we already have an expired autowhitelist entry, this happens if the cleanup has not run yet
							if (defined($currentAutoWhitelistEntry)) {
								# Update whitelisting to the new details
								$sth = DBDo('
									UPDATE 
										@TP@greylisting_autowhitelist
									SET
										TrackKey = ?,
										Added = ?,
										LastSeen = ?,
										Comment = ?
									WHERE
										ID = ?
									',
									$key,$sessionData->{'UnixTimestamp'},$sessionData->{'UnixTimestamp'},$whitelist,$currentAutoWhitelistEntry
								);
								if (!$sth) {
									$server->log(LOG_ERR,"[GREYLISTING] Database update failed: ".awitpt::db::dblayer::Error());
									return $server->protocol_response(PROTO_DB_ERROR);
								}
							} else {
								# Update whitelisting to the new details
								$sth = DBDo('
									INSERT INTO @TP@greylisting_autowhitelist
										(TrackKey,Added,LastSeen,Comment)
									VALUES
										(?,?,?,?)
									',
									$key,$sessionData->{'UnixTimestamp'},$sessionData->{'UnixTimestamp'},$whitelist
								);
								if (!$sth) {
									$server->log(LOG_ERR,"[GREYLISTING] Database insert failed: ".awitpt::db::dblayer::Error());
									return $server->protocol_response(PROTO_DB_ERROR);
								}
							}

							$server->maillog("module=Greylisting, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=auto-whitelisted",
									$sessionData->{'ClientAddress'},
									$sessionData->{'Helo'},
									$sessionData->{'Sender'},
									$sessionData->{'Recipient'});

							return $server->protocol_response(PROTO_PASS);
						}
					} # if ($row->{'RCount'} >= $policy{'AutoWhitelistCount'})
				} # if (defined($policy{'AutoWhitelistCount'}) && $policy{'AutoWhitelistCount'} > 0) 

			} else { # if (defined($policy{'AutoWhitelistPeriod'}) && $policy{'AutoWhitelistPeriod'} > 0)
				$server->log(LOG_ERR,"[GREYLISTING] Resolved policy UseAutoWWhitelist is set, but AutoWhitelistPeriod is not set or invalid");
				return $server->protocol_response(PROTO_DATA_ERROR);
			}
		}

		# Depending on if we training or not, set the reason
		my $reason;
		if ($config{'training_mode'}) {
			$reason = "training";
		} else {
			$reason = "authenticated";
		}
		
		$server->maillog("module=Greylisting, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=$reason",
				$sessionData->{'ClientAddress'},
				$sessionData->{'Helo'},
				$sessionData->{'Sender'},
				$sessionData->{'Recipient'});
				
		return $server->protocol_response(PROTO_PASS);
	}

	# We should never get here
	return CBP_ERROR;
}


# Get key from session
sub getKey
{
	my ($server,$track,$sessionData) = @_;


	my $res;


	# Split off method and splec
	my ($method,$spec) = ($track =~ /^([^:]+)(?::(\S+))?/);
	
	# Lowercase method & spec
	$method = lc($method);
	$spec = lc($spec) if (defined($spec));

	# Check TrackSenderIP
	if ($method eq "senderip") {
		my $key = getIPKey($spec,$sessionData->{'_ClientAddress'});

		# Check for no key
		if (defined($key)) {
			$res = "SenderIP:$key";
		} else {
			$server->log(LOG_WARN,"[GREYLISTING] Unknown key specification in TrackSenderIP");
		}

	# Fall-through to catch invalid specs
	} else {
		$server->log(LOG_WARN,"[GREYLISTING] Invalid tracking specification '$track'");
	}


	return $res;
}


# Cleanup function
sub cleanup
{
	my ($server) = @_;

	# Get now
	my $now = time();

	#
	# Autowhitelist cleanups
	#
	
	# Get maximum AutoWhitelistPeriod
	my $sth = DBSelect('
		SELECT 
			MAX(AutoWhitelistPeriod) AS Period
		FROM 
			@TP@greylisting
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Failed to query AutoWhitelistPeriod: ".awitpt::db::dblayer::Error());
		return -1;
	}
	my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Period ));

	# Check if we have something...
	my $AWLPeriod;
	if (($AWLPeriod = $row->{'Period'}) && $AWLPeriod > 0) {
		# Get start time
		$AWLPeriod = $now - $AWLPeriod;

		# Remove old whitelistings from database
		$sth = DBDo('
			DELETE FROM 
				@TP@greylisting_autowhitelist
			WHERE
				LastSeen < ?
			',
			$AWLPeriod
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Failed to remove old autowhitelist records: ".awitpt::db::dblayer::Error());
			return -1;
		}
		$server->log(LOG_INFO,"[GREYLISTING] Removed ".( $sth ne "0E0" ? $sth : 0)." records from autowhitelist table");
	}


	#
	# Autoblacklist cleanups
	#
	
	# Get maximum AutoBlacklistPeriod
	$sth = DBSelect('
		SELECT 
			MAX(AutoBlacklistPeriod) AS Period
		FROM 
			@TP@greylisting
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Failed to query AutoBlacklistPeriod: ".awitpt::db::dblayer::Error());
		return -1;
	}
	$row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Period ));

	# Check if we have something...
	my $ABLPeriod;
	if (($ABLPeriod = $row->{'Period'}) && $ABLPeriod > 0) {
		# Get start time
		$ABLPeriod = $now - $ABLPeriod;
	
		# Remove blacklistings from database
		$sth = DBDo('
			DELETE FROM 
				@TP@greylisting_autoblacklist
			WHERE
				Added < ?
			',
			$ABLPeriod
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Failed to remove old autoblacklist records: ".awitpt::db::dblayer::Error());
			return -1;
		}
		$server->log(LOG_INFO,"[GREYLISTING] Removed ".( $sth ne "0E0" ? $sth : 0)." records from autoblacklist table");
	}
	
	#
	# Authenticated record cleanups
	#
	
	# Get maximum GreylistAuthValidity
	$sth = DBSelect('
		SELECT 
			MAX(GreylistAuthValidity) AS Period
		FROM 
			@TP@greylisting
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Failed to query GreylistAuthValidity: ".awitpt::db::dblayer::Error());
		return -1;
	}
	$row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Period ));
	
	# Check if we have something...
	my $AuthPeriod;
	if (($AuthPeriod = $row->{'Period'}) && $AuthPeriod > 0) {
		# Get start time
		$AuthPeriod = $now - $AuthPeriod;
	
		# Remove old authenticated records from database
		$sth = DBDo('
			DELETE FROM 
				@TP@greylisting_tracking
			WHERE
				LastUpdate < ?
				AND Count != 0
			',
			$AuthPeriod
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Failed to remove old authenticated records: ".awitpt::db::dblayer::Error());
			return -1;
		}
		$server->log(LOG_INFO,"[GREYLISTING] Removed ".( $sth ne "0E0" ? $sth : 0)." authenticated records from greylist tracking table");
	}

	#
	# UnAuthenticated record cleanups
	#
	
	# Get maximum GreylistUnAuthValidity
	$sth = DBSelect('
		SELECT 
			MAX(GreylistUnAuthValidity) AS Period
		FROM 
			@TP@greylisting
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[GREYLISTING] Failed to query GreylistUnAuthValidity: ".awitpt::db::dblayer::Error());
		return -1;
	}
	$row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Period ));

	# Check if we have something...
	my $UnAuthPeriod;
	if (($UnAuthPeriod = $row->{'Period'}) && $UnAuthPeriod > 0) {
		# Get start time
		$UnAuthPeriod = $now - $UnAuthPeriod;
	
		# Remove old un-authenticated records info from database
		$sth = DBDo('
			DELETE FROM 
				@TP@greylisting_tracking
			WHERE
				LastUpdate < ?
				AND Count = 0
			',
			$UnAuthPeriod
		);
		if (!$sth) {
			$server->log(LOG_ERR,"[GREYLISTING] Failed to remove old un-authenticated records: ".awitpt::db::dblayer::Error());
			return -1;
		}
		$server->log(LOG_INFO,"[GREYLISTING] Removed ".( $sth ne "0E0" ? $sth : 0)." unauthenticated records from greylist tracking table");
	}
}



1;
# vim: ts=4
