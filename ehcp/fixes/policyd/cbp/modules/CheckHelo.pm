# Helo checking module
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


package cbp::modules::CheckHelo;

use strict;
use warnings;


use cbp::logging;
use awitpt::cache;
use awitpt::db::dblayer;
use cbp::protocols;
use cbp::system;

use Net::DNS::Resolver;


# User plugin info
our $pluginInfo = {
	name 			=> "HELO/EHLO Check Plugin",
	priority		=> 80,
	init		 	=> \&init,
	request_process	=> \&check,
	cleanup			=> \&cleanup,
};


# Our config
my %config;


# Create a child specific context
sub init {
	my $server = shift;
	my $inifile = $server->{'inifile'};

	# Defaults
	$config{'enable'} = 0;

	# Parse in config
	if (defined($inifile->{'checkhelo'})) {
		foreach my $key (keys %{$inifile->{'checkhelo'}}) {
			$config{$key} = $inifile->{'checkhelo'}->{$key};
		}
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => CheckHelo: enabled");
		$config{'enable'} = 1;
	} else {
		$server->log(LOG_NOTICE,"  => CheckHelo: disabled");
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
	
	# We need a HELO...
	return CBP_SKIP if (!defined($sessionData->{'Helo'}) || $sessionData->{'Helo'} eq "");
	
	# Check if we have any policies matched, if not just pass
	return CBP_SKIP if (!defined($sessionData->{'Policy'}));

	# Policy we're about to build
	my %policy;

	# Loop with priorities, low to high
	foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}}) {

		# Loop with policies
		foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}}) {

			my $sth = DBSelect('
				SELECT
					UseBlacklist, BlacklistPeriod,
					UseHRP, HRPPeriod, HRPLimit,
					RejectInvalid, RejectIP, RejectUnresolvable
				FROM
					@TP@checkhelo
				WHERE
					PolicyID = ?
					AND Disabled = 0
				',
				$policyID
			);
			if (!$sth) {
				$server->log(LOG_ERR,"[CHECKHELO] Database query failed: ".awitpt::db::dblayer::Error());
				return $server->protocol_response(PROTO_DB_ERROR);
			}
			while (my $row = hashifyLCtoMC($sth->fetchrow_hashref(),
					qw(	UseBlacklist BlacklistPeriod UseHRP HRPPeriod HRPLimit RejectInvalid RejectIP RejectUnresolvable )
			)) {
				$policy{'Identifier'} .= ":$policyID";

				# If defined, its to override
				if (defined($row->{'UseBlacklist'})) {
					$policy{'UseBlacklist'} = $row->{'UseBlacklist'};
				}
				if (defined($row->{'BlacklistPeriod'})) {
					$policy{'BlacklistPeriod'} = $row->{'BlacklistPeriod'};
				}
	
				if (defined($row->{'UseHRP'})) {
					$policy{'UseHRP'} = $row->{'UseHRP'};
				}
				if (defined($row->{'HRPPeriod'})) {
					$policy{'HRPPeriod'} = $row->{'HRPPeriod'};
				}
				if (defined($row->{'HRPLimit'})) {
					$policy{'HRPLimit'} = $row->{'HRPLimit'};
				}
	
				if (defined($row->{'RejectInvalid'})) {
					$policy{'RejectInvalid'} = $row->{'RejectInvalid'};
				}
				if (defined($row->{'RejectIP'})) {
					$policy{'RejectIP'} = $row->{'RejectIP'};
				}
				if (defined($row->{'RejectUnresolvable'})) {
					$policy{'RejectUnresolvable'} = $row->{'RejectUnresolvable'};
				}
			} # while (my $row = $sth->fetchrow_hashref())
		} # foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}})
	} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}})

	# Check if we have a policy
	if (!%policy) {
		return CBP_CONTINUE;
	}

	#
	# Insert/update HELO in database
	#
	my $sth = DBDo({
		'mysql' => ['
					INSERT INTO @TP@checkhelo_tracking 
						(Address,Helo,LastUpdate) 
					VALUES	
						(?,?,?)
					ON DUPLICATE KEY
						UPDATE LastUpdate = ?
				',
				$sessionData->{'ClientAddress'},$sessionData->{'Helo'},$sessionData->{'UnixTimestamp'},
				$sessionData->{'UnixTimestamp'},
			],
		'*' => ['
					UPDATE
						@TP@checkhelo_tracking
					SET
						LastUpdate = ?
					WHERE
						Address = ?
						AND Helo = ?
				',
				$sessionData->{'UnixTimestamp'},$sessionData->{'ClientAddress'},$sessionData->{'Helo'}
			]
	});
	if (!$sth) {
		$server->log(LOG_ERR,"[CHECKHELO] Database update failed: ".awitpt::db::dblayer::Error());
		return $server->protocol_response(PROTO_DB_ERROR);
	}
	# If we didn't update anything, insert
	if ($sth eq "0E0") {
		$sth = DBDo('
				INSERT INTO @TP@checkhelo_tracking 
					(Address,Helo,LastUpdate) 
				VALUES
					(?,?,?)
			',
			$sessionData->{'ClientAddress'},$sessionData->{'Helo'},$sessionData->{'UnixTimestamp'}
		);
		if (!$sth) {
			use Data::Dumper;
			$server->log(LOG_ERR,"[CHECKHELO] Database query failed: ".awitpt::db::dblayer::Error().", data: ".Dumper($sessionData));
			return $server->protocol_response(PROTO_DB_ERROR);
		}
		$server->log(LOG_DEBUG,"[CHECKHELO] Recorded helo '".$sessionData->{'Helo'}."' from address '".$sessionData->{'ClientAddress'}."'") if ($log);
	# And just a bit of debug
	} else {
		$server->log(LOG_DEBUG,"[CHECKHELO] Updated timestamp for helo '".$sessionData->{'Helo'}."' from address '".
				$sessionData->{'ClientAddress'}."'") if ($log);
	}


	#
	# Check if we whitelisted or not...
	#

	# Check cache
	my ($cache_res,$cache) = cacheGetKeyPair('CheckHelo/Whitelist/IP',$sessionData->{'ClientAddress'});
	if ($cache_res) {
		return $server->protocol_response(PROTO_ERROR);
	}
	# Check if we have a cache value and if its a match
	if (defined($cache)) {

		# If cache is positive, whitelist
		if ($cache) {
			$server->maillog("module=CheckHelo, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=whitelisted_cached",
					$sessionData->{'ClientAddress'},
					$sessionData->{'Helo'},
					$sessionData->{'Sender'},
					$sessionData->{'Recipient'});
			return $server->protocol_response(PROTO_PASS);
		}

	} else {
		my $whitelistSources = getWhitelist($server);
		if (!defined($whitelistSources)) {
			return $server->protocol_response(PROTO_DB_ERROR);
		}

		# Loop with whitelist and calculate
		foreach my $source (@{$whitelistSources}) {
			# Check format is SenderIP
			if ((my $raw_waddress = $source) =~ s/^SenderIP://i) {

				# Create our IP object
				my $waddress = new awitpt::netip($raw_waddress);
				if (!defined($waddress)) {
					$server->log(LOG_WARN,"[CHECKHELO] Skipping invalid address '$raw_waddress'.");
					next;
				}
				# Check if IP is whitelisted
				if ($sessionData->{'_ClientAddress'}->is_within($waddress)) {
					# Cache positive result
					my $cache_res = cacheStoreKeyPair('CheckHelo/Whitelist/IP',
							$sessionData->{'ClientAddress'},1);
					if ($cache_res) {
						return $server->protocol_response(PROTO_ERROR);
					}
					# Log...
					$server->maillog("module=CheckHelo, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=whitelisted",
							$sessionData->{'ClientAddress'},
							$sessionData->{'Helo'},
							$sessionData->{'Sender'},
							$sessionData->{'Recipient'});

					return $server->protocol_response(PROTO_PASS);
				}
				# Cache negative result
				my $cache_res = cacheStoreKeyPair('CheckHelo/Whitelist/IP',$sessionData->{'ClientAddress'},0);
				if ($cache_res) {
					return $server->protocol_response(PROTO_ERROR);
				}

			} else {
				$server->log(LOG_ERR,"[CHECKHELO] Whitelist entry '$source' is invalid.");
				return $server->protocol_response(PROTO_DATA_ERROR);
			}
		}
	}


	#
	# Check if we need to reject invalid HELO's
	#
	if (defined($policy{'RejectInvalid'}) && $policy{'RejectInvalid'} eq "1") {

		# Check if helo is an IPv4 or IPv6 address
		if (
			$sessionData->{'Helo'} =~ /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/ ||
			$sessionData->{'Helo'} =~ /^(?:::(:?[a-f\d]{1,4}:){0,6}?[a-f\d]{0,4}|[a-f\d]{1,4}(?::[a-f\d]{1,4}){0,6}?::|[a-f\d]{1,4}(?::[a-f\d]{1,4}){0,6}?::(?:[a-f\d]{1,4}:){0,6}?[a-f\d]{1,4})$/i
		) {

			# Check if we must reject IP address HELO's
			if (defined($policy{'RejectIP'}) && $policy{'RejectIP'} eq "1") {

				$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=ip_not_allowed",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});

				return $server->protocol_response(PROTO_REJECT,
						"Invalid HELO/EHLO; Must be a FQDN or an address literal, not '".$sessionData->{'Helo'}."'");
			}

		# Address literal is valid
		} elsif (
			$sessionData->{'Helo'} =~ /^\[(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\]$/ ||
			$sessionData->{'Helo'} =~ /^\[((?:::(:?[a-f\d]{1,4}:){0,6}?[a-f\d]{0,4}|[a-f\d]{1,4}(?::[a-f\d]{1,4}){0,6}?::|[a-f\d]{1,4}(?::[a-f\d]{1,4}){0,6}?::(?:[a-f\d]{1,4}:){0,6}?[a-f\d]{1,4}))\]$/i

		) {
		# Check if helo is a FQDN - Only valid characters in a domain is alnum and a -
		} elsif ($sessionData->{'Helo'} =~ /^[\w-]+(\.[\w-]+)+$/) {

			# Check if we must reject unresolvable HELO's
			if (defined($policy{'RejectUnresolvable'}) && $policy{'RejectUnresolvable'} eq "1") {
				my $res = Net::DNS::Resolver->new;
				my $query = $res->search($sessionData->{'Helo'});

				# If the query failed
				if ($query) {

					# Look for MX or A records
					my $found = 0;
					foreach my $rr ($query->answer) {
						next unless ($rr->type eq "A" || $rr->type eq "MX");
						$found = 1;
					}

					# Check if we found any valid DNS records
					if (!$found) {

						$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=resolve_notfound",
								$sessionData->{'ClientAddress'},
								$sessionData->{'Helo'},
								$sessionData->{'Sender'},
								$sessionData->{'Recipient'});

						return $server->protocol_response(PROTO_REJECT,
							"Invalid HELO/EHLO; No A or MX records found for '".$sessionData->{'Helo'}."'");
					}

				} else {

					# Check for error
					if ($res->errorstring eq "NXDOMAIN") {

						$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=resolve_nxdomain",
								$sessionData->{'ClientAddress'},
								$sessionData->{'Helo'},
								$sessionData->{'Sender'},
								$sessionData->{'Recipient'});

						return $server->protocol_response(PROTO_REJECT,
							"Invalid HELO/EHLO; Cannot resolve '".$sessionData->{'Helo'}."', no such domain");

					} elsif ($res->errorstring eq "NOERROR") {

						$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=resolve_noerror",
								$sessionData->{'ClientAddress'},
								$sessionData->{'Helo'},
								$sessionData->{'Sender'},
								$sessionData->{'Recipient'});

						return $server->protocol_response(PROTO_REJECT,
							"Invalid HELO/EHLO; Cannot resolve '".$sessionData->{'Helo'}."', no records found");

					} elsif ($res->errorstring eq "SERVFAIL") {

						$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=resolve_servfail",
								$sessionData->{'ClientAddress'},
								$sessionData->{'Helo'},
								$sessionData->{'Sender'},
								$sessionData->{'Recipient'});

						return $server->protocol_response(PROTO_REJECT,
							"Invalid HELO/EHLO; Failure while trying to resolve '".$sessionData->{'Helo'}."'");

					} else {
						$server->log(LOG_ERR,"[CHECKHELO] Unknown error resolving '".$sessionData->{'Helo'}."': ".$res->errorstring);
						return $server->protocol_response(PROTO_ERROR);
					}
				} # if ($query)
			} # if (defined($policy{'RejectUnresolvable'}) && $policy{'RejectUnresolvable'} eq "1") {

		# Reject blatent RFC violation
		} else { # elsif ($sessionData->{'Helo'} =~ /^[\w-]+(\.[\w-]+)+$/)
			return $server->protocol_response(PROTO_REJECT,
					"Invalid HELO/EHLO; Must be a FQDN or an address literal, not '".$sessionData->{'Helo'}."'");
		}
	} # if (defined($policy{'RejectInvalid'}) && $policy{'RejectInvalid'} eq "1")

	# Check if we must use the blacklist or not
	if (defined($policy{'UseBlacklist'}) && $policy{'UseBlacklist'} eq "1") {
		my $start = 0;

		# Check period for blacklisting
		if (defined($policy{'BlacklistPeriod'})) {
			if ($policy{'BlacklistPeriod'} > 0) {
				$start = $policy{'BlacklistPeriod'};
			}
		}
		# Check cache
		my ($cache_res,$cache) = cacheGetKeyPair('CheckHelo/Blacklist/PolicyIdentifier-Blacklisted-IP',
				$policy{'Identifier'}."/".$sessionData->{'ClientAddress'});
		if ($cache_res) {
			return $server->protocol_response(PROTO_ERROR);
		}

		# Check if we have a cache value and if its a match
		if (defined($cache) && $cache) {
			$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=blacklisted_cached",
					$sessionData->{'ClientAddress'},
					$sessionData->{'Helo'},
					$sessionData->{'Sender'},
					$sessionData->{'Recipient'});

			return $server->protocol_response(PROTO_REJECT,"Invalid HELO/EHLO; Blacklisted");
		} else {
			# Get blacklist count
			my $blacklistCount = getBlacklistCount($server,$sessionData->{'ClientAddress'},$start);
			if (!defined($blacklistCount)) {
				return $server->protocol_response(PROTO_DB_ERROR);
			}

			# If count > 0 , then its blacklisted
			if ($blacklistCount > 0) {
				# Cache this
				$cache_res = cacheStoreKeyPair('CheckHelo/Blacklist/PolicyIdentifier-Blacklisted-IP',
				$policy{'Identifier'}."/".$sessionData->{'ClientAddress'},1);
				if ($cache_res) {
					return $server->protocol_response(PROTO_ERROR);
				}

				$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=blacklisted",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});

				return $server->protocol_response(PROTO_REJECT,"Invalid HELO/EHLO; Blacklisted");
			}
		}
	}

	# Check if we must use HRP
	if (defined($policy{'UseHRP'}) && $policy{'UseHRP'} eq "1") {

		# Check if HRPPeriod is defined
		if (defined($policy{'HRPPeriod'})) {

			# Check if HRPPeriod is valid
			if ($policy{'HRPPeriod'} > 0) {

				# Check HRPLimit is defined
				if (defined($policy{'HRPLimit'})) {

					# check HRPLimit is valid
					if ($policy{'HRPLimit'} > 0) {
						my $start = 0;

						# Check period for blacklisting
						if (defined($policy{'HRPPeriod'})) {
							if ($policy{'HRPPeriod'} > 0) {
								$start = $policy{'HRPPeriod'};
							}
						}

						# Check cache
						my ($cache_res,$cache) = cacheGetKeyPair('CheckHelo/HRP/PolicyIdentifier-Blacklisted-IP',
								$policy{'Identifier'}."/".$sessionData->{'ClientAddress'});
						if ($cache_res) {
							return $server->protocol_response(PROTO_ERROR);
						}

						# Check if we have a cache value and if its a match
						if (defined($cache) && $cache) {
							$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=hrp_blacklisted_cached",
									$sessionData->{'ClientAddress'},
									$sessionData->{'Helo'},
									$sessionData->{'Sender'},
									$sessionData->{'Recipient'});

							return $server->protocol_response(PROTO_REJECT,"Invalid HELO/EHLO; HRP limit exceeded");
						} else {
							# Get HRP count
							my $hrpCount = getHRPCount($server,$sessionData->{'ClientAddress'},$start);
							if (!defined($hrpCount)) {
								return $server->protocol_response(PROTO_DB_ERROR);
							}

							# If count > $limit , reject
							if ($hrpCount > $policy{'HRPLimit'}) {
								# Cache this
								$cache_res = cacheStoreKeyPair('CheckHelo/HRP/PolicyIdentifier-Blacklisted-IP',
								$policy{'Identifier'}."/".$sessionData->{'ClientAddress'},1);
								if ($cache_res) {
									return $server->protocol_response(PROTO_ERROR);
								}

								$server->maillog("module=CheckHelo, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=hrp_blacklisted",
										$sessionData->{'ClientAddress'},
										$sessionData->{'Helo'},
										$sessionData->{'Sender'},
										$sessionData->{'Recipient'});

								return $server->protocol_response(PROTO_REJECT,"Invalid HELO/EHLO; HRP limit exceeded");
							}
						}

					} else {
						$server->log(LOG_ERR,"[CHECKHELO] Resolved policy UseHRP is set, HRPPeriod is set but HRPPeriod is invalid");
						return $server->protocol_response(PROTO_DATA_ERROR);
					}


				} else {
					$server->log(LOG_ERR,"[CHECKHELO] Resolved policy UseHRP is set, HRPPeriod is set but HRPLimit is not defined");
					return $server->protocol_response(PROTO_DATA_ERROR);
				}


			} else {
				$server->log(LOG_ERR,"[CHECKHELO] Resolved policy UseHRP is set, but HRPPeriod is invalid");
				return $server->protocol_response(PROTO_DATA_ERROR);
			}


		} else {
			$server->log(LOG_ERR,"[CHECKHELO] Resolved policy UseHRP is set, but HRPPeriod is not defined");
			return $server->protocol_response(PROTO_DATA_ERROR);
		}

	}

	return CBP_CONTINUE;
}


# Cleanup function
sub cleanup
{
	my ($server) = @_;

	# Get now
	my $now = time();

	#
	# Tracking table cleanup
	#
	
	# Get maximum periods
	my $sth = DBSelect('
		SELECT 
			MAX(BlacklistPeriod) AS BlacklistPeriod, MAX(HRPPeriod) AS HRPPeriod
		FROM 
			@TP@checkhelo
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[CHECKHELO] Failed to query maximum periods: ".awitpt::db::dblayer::Error());
		return -1;
	}
	my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( BlacklistPeriod HRPPeriod ));

	# Check we have results
	return if (!defined($row->{'BlacklistPeriod'}) && !defined($row->{'HRPPeriod'}));

	# Work out which one is largest
	my $period;
	if (defined($row->{'BlacklistPeriod'}) && defined($row->{'HRPPeriod'})) {
		$period = $row->{'BlacklistPeriod'} > $row->{'HRPPeriod'} ? $row->{'BlacklistPeriod'} : $row->{'HRPPeriod'};
	} elsif (defined($row->{'BlacklistPeriod'})) {
		$period = $row->{'BlacklistPeriod'};
	} else {
		$period = $row->{'HRPPeriod'};
	}

	# Bork if we didn't find anything of interest
	return if (!($period > 0));

	# Get start time
	$period = $now - $period;

	# Remove old tracking entries from database
	$sth = DBDo('
		DELETE FROM 
			@TP@checkhelo_tracking
		WHERE
			LastUpdate < ?
		',
		$period
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[CHECKHELO] Failed to remove old helo records: ".awitpt::db::dblayer::Error());
		return -1;
	}

	$server->log(LOG_INFO,"[CHECKHELO] Removed ".( $sth ne "0E0" ? $sth : 0)." records from tracking table");
}



# Get HRP count for a specific client address
sub getHRPCount
{
	my ($server,$clientAddress,$start) = @_;

	my $sth = DBSelect('
		SELECT
			Count(*) AS Count

		FROM
			@TP@checkhelo_tracking

		WHERE
			Address = ?
			AND LastUpdate >= ?
		',
		$clientAddress,$start
	);
	if (!$sth) {
		$server->log(LOG_ERR,"Database query failed: ".awitpt::db::dblayer::Error());
		return;
	}

	my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Count ));

	return $row->{'Count'};
}


# Check if we've used a blacklisted HELO
sub getBlacklistCount
{
	my ($server,$clientAddress,$start) = @_;


	# Check cache
	my ($cache_res,$cache) = cacheGetKeyPair('CheckHelo/Blacklist',$clientAddress);
	if ($cache_res) {
		$server->log(LOG_ERR,"[CHECKHELO] Blacklist cache get failed: ".awitpt::cache::Error());
		return;
	}
	return $cache if ($cache);

	# Select and compare the number of tracking HELO's in the past time with the blacklisted ones
	my $sth = DBSelect('
		SELECT
			Count(*) AS Count

		FROM
			@TP@checkhelo_tracking, @TP@checkhelo_blacklist

		WHERE
			@TP@checkhelo_tracking.LastUpdate >= ?
			AND @TP@checkhelo_tracking.Address = ?
			AND @TP@checkhelo_tracking.Helo = @TP@checkhelo_blacklist.Helo
			AND @TP@checkhelo_blacklist.Disabled = 0
		',
		$start,$clientAddress
	);
	if (!$sth) {
		$server->log(LOG_ERR,"Database query failed: ".awitpt::db::dblayer::Error());
		return $server->protocol_response(PROTO_DB_ERROR);
	}
	my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Count ));
	
	# Cache this
	$cache_res = cacheStoreKeyPair('CheckHelo/Blacklist',$clientAddress,$row->{'Count'});
	if ($cache_res) {
		$server->log(LOG_ERR,"[CHECKHELO] Blacklist cache store failed: ".awitpt::cache::Error());
		return;
	}

	return $row->{'Count'};
}



# Return checkhelo whitelist
sub getWhitelist
{
	my $server = shift;

	# Check cache
	my ($cache_res,$cache) = cacheGetComplexKeyPair('CheckHelo/Whitelist','Sources');
	if ($cache_res) {
		$server->log(LOG_ERR,"[CHECKHELO] Whitelist cache get failed: ".awitpt::cache::Error());
		return;
	}
	return $cache if ($cache);

	# Check if we whitelisted or not...
	my $sth = DBSelect('
		SELECT
			Source

		FROM
			@TP@checkhelo_whitelist

		WHERE
			Disabled = 0
	');
	if (!$sth) {
		$server->log(LOG_ERR,"[CHECKHELO] Database query failed: ".awitpt::db::dblayer::Error());
		return;
	}
	# Loop with whitelist and calculate
	my @sources;
	while (my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Source ))) {
			push(@sources,$row->{'Source'});
	}

	# Cache this
	$cache_res = cacheStoreComplexKeyPair('CheckHelo/Whitelist','Sources',\@sources);
	if ($cache_res) {
		$server->log(LOG_ERR,"[CHECKHELO] Whitelist cache store failed: ".awitpt::cache::Error());
		return;
	}

	return \@sources;
}


1;
# vim: ts=4
