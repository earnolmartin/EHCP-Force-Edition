# Access control module
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


package cbp::modules::AccessControl;

use strict;
use warnings;


use cbp::logging;
use awitpt::db::dblayer;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Access Control Plugin",
	priority		=> 90,
	init		 	=> \&init,
	request_process	=> \&check,
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
	if (defined($inifile->{'accesscontrol'})) {
		foreach my $key (keys %{$inifile->{'accesscontrol'}}) {
			$config{$key} = $inifile->{'accesscontrol'}->{$key};
		}
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => AccessControl: enabled");
		$config{'enable'} = 1;
	} else {
		$server->log(LOG_NOTICE,"  => AccessControl: disabled");
	}
}


# Check the request
sub check {
	my ($server,$sessionData) = @_;
	

	# If we not enabled, don't do anything
	return CBP_SKIP if (!$config{'enable'});

	# We only valid in the RCPT state
	return CBP_SKIP if (!defined($sessionData->{'ProtocolState'}) || $sessionData->{'ProtocolState'} ne "RCPT");

	# Check if we have any policies matched, if not just pass
	return CBP_SKIP if (!defined($sessionData->{'Policy'}));

	# Result
	my $res;

	# Loop with priorities, low to high
	foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'Policy'}}) {

		# Loop with policies
		foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}}) {

			my $sth = DBSelect('
				SELECT
					Verdict, Data
				FROM
					@TP@access_control
				WHERE
					PolicyID = ?
					AND Disabled = 0
				',
				$policyID
			);
			if (!$sth) {
				$server->log(LOG_ERR,"Database query failed: ".awitpt::db::dblayer::Error());
				return $server->protocol_response(PROTO_DB_ERROR);
			}
			my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Verdict Data ));
			DBFreeRes($sth);

			# If no result, next
			next if (!$row);

			# Setup result
			if (!defined($row->{'Verdict'})) {
				$server->maillog("module=AccessControl, action=none, host=%s, helo=%s, from=%s, to=%s, reason=no_verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				next; # No verdict

			} elsif ($row->{'Verdict'} =~ /^hold$/i) {
				$server->maillog("module=AccessControl, action=hold, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_HOLD,$row->{'Data'});

			} elsif ($row->{'Verdict'} =~ /^reject$/i) {
				$server->maillog("module=AccessControl, action=reject, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_REJECT,$row->{'Data'});

			} elsif ($row->{'Verdict'} =~ /^discard$/i) {
				$server->maillog("module=AccessControl, action=discard, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_DISCARD,$row->{'Data'});

			} elsif ($row->{'Verdict'} =~ /^filter$/i) {
				$server->maillog("module=AccessControl, action=filter, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_FILTER,$row->{'Data'});

			} elsif ($row->{'Verdict'} =~ /^redirect$/i) {
				$server->maillog("module=AccessControl, action=redirect, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_REDIRECT,$row->{'Data'});

			} elsif ($row->{'Verdict'} =~ /^ok$/i) {
				$server->maillog("module=AccessControl, action=ok, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_OK,$row->{'Data'});

			} elsif ($row->{'Verdict'} =~ /^pass$/i) {
				$server->maillog("module=AccessControl, action=pass, host=%s, helo=%s, from=%s, to=%s, reason=verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_PASS,$row->{'Data'});

			} else {
				$server->log(LOG_ERR,"[ACCESSCONTROL] Unknown Verdict specification in access control '".$row->{'Verdict'}."'");
				$server->maillog("module=AccessControl, action=none, host=%s, helo=%s, from=%s, to=%s, reason=invalid_verdict",
						$sessionData->{'ClientAddress'},
						$sessionData->{'Helo'},
						$sessionData->{'Sender'},
						$sessionData->{'Recipient'});
				return $server->protocol_response(PROTO_DATA_ERROR);
			}

		} # foreach my $policyID (@{$sessionData->{'Policy'}->{$priority}})

	} # foreach my $priority (sort {$a <=> $b} keys %{$sessionData->{'_policy'}})

	return CBP_CONTINUE;
}



1;
# vim: ts=4
