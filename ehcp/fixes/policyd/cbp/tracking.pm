# Message tracking functions
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


package cbp::tracking;

use strict;
use warnings;

# Exporter stuff
require Exporter;
our (@ISA,@EXPORT,@EXPORT_OK);
@ISA = qw(Exporter);
@EXPORT = qw(
	updateSessionData
	getSessionDataFromRequest
	getSessionDataFromQueueID
);


use awitpt::db::dblayer;
use awitpt::netip;
use cbp::logging;
use cbp::policies;
use POSIX qw( ceil);

use Data::Dumper;


# Database handle
my $dbh = undef;


# Get session data from mail_id
sub getSessionDataFromQueueID
{
	my ($server,$queueID,$clientAddress,$sender) = @_;


	$server->log(LOG_DEBUG,"[TRACKING] Retreiving session data for triplet: $queueID/$clientAddress/$sender");
	
	# Pull in session data
	my $sth = DBSelect('
		SELECT
			Instance, QueueID,
			UnixTimestamp,
			ClientAddress, ClientName, ClientReverseName,
			Protocol,
			EncryptionProtocol, EncryptionCipher, EncryptionKeySize,
			SASLMethod, SASLSender, SASLUsername,
			Helo,
			Sender,
			Size,
			RecipientData
		FROM
			@TP@session_tracking
		WHERE
			QueueID = ?
			AND ClientAddress = ?
			AND Sender = ?
		',
		$queueID,$clientAddress,$sender
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[TRACKING] Failed to select session tracking info: ".awitpt::db::dblayer::Error());
		return -1;
	}

	# Fetch row
	my $row = $sth->fetchrow_hashref();
	if (!$row) {
		$server->log(LOG_ERR,"[TRACKING] No session data");
		return -1;
	}

	# Cleanup database record
	my $sessionData = hashifyDBSessionData($row);

	# Pull in decoded policy
	$sessionData->{'_Recipient_To_Policy'} = decodePolicyData($sessionData->{'RecipientData'});

	return $sessionData;
}


# Get session data
# Params:
# 	server, request
sub getSessionDataFromRequest
{
	my ($server,$request) = @_;
	my $log = defined($server->{'config'}{'logging'}{'tracking'});


	# We must have protocol transport
	if (!defined($request->{'_protocol_transport'})) {
		$server->log(LOG_ERR,"[TRACKING] No protocol transport specified");
		return -1;
	}

	# Change size to kbyte, we don't want to use bytes
	my $requestSize;
	if (defined($request->{'size'})) {
		$requestSize = ceil($request->{'size'} / 1024);
	}

	my $sessionData;

	# Requesting server address, we need this before the policy call. Only do this for TCP
	if ($server->{'server'}->{'peer_type'} eq "TCP") {
		$sessionData->{'PeerAddress'} = $request->{'_peer_address'};
		$sessionData->{'_PeerAddress'} = new awitpt::netip($sessionData->{'PeerAddress'});
		if (!defined($sessionData->{'_PeerAddress'})) {
			$server->log(LOG_ERR,"[TRACKING] Failed to understand PeerAddress: ".awitpt::netip::Error());
			return -1;
		}
	}

	# Check protocol
	if ($request->{'_protocol_transport'} eq "Postfix") {
		my $initSessionData = 0;

		# Check if we need to track the sessions...
		if ($server->{'config'}->{'track_sessions'}) {

			# Pull in session data
			my $sth = DBSelect('
				SELECT
					Instance, QueueID,
					UnixTimestamp,
					ClientAddress, ClientName, ClientReverseName,
					Protocol,
					EncryptionProtocol, EncryptionCipher, EncryptionKeySize,
					SASLMethod, SASLSender, SASLUsername,
					Helo,
					Sender,
					Size,
					RecipientData
				FROM
					@TP@session_tracking
				WHERE
					Instance = ?
				',
				$request->{'instance'}
			);
			if (!$sth) {
				$server->log(LOG_ERR,"[TRACKING] Failed to select session tracking info: ".awitpt::db::dblayer::Error());
				return -1;
			}
			
			my $row = $sth->fetchrow_hashref();
				
			# If no state information, create everything we need
			if (!($sessionData = hashifyDBSessionData($row))) {

				$server->log(LOG_DEBUG,"[TRACKING] No session tracking data exists for request: ".Dumper($request)) if ($log);

				# Should only track sessions from RCPT
				if ($request->{'protocol_state'} eq "RCPT") {
					DBBegin();
	
					# Record tracking info
					$sth = DBDo('
						INSERT INTO @TP@session_tracking 
							(
								Instance,QueueID,
								UnixTimestamp,
								ClientAddress, ClientName, ClientReverseName,
								Protocol,
								EncryptionProtocol,EncryptionCipher,EncryptionKeySize,
								SASLMethod,SASLSender,SASLUsername,
								Helo,
								Sender,
								Size
							)
						VALUES
							(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
						',
						$request->{'instance'},$request->{'queue_id'},$request->{'_timestamp'},$request->{'client_address'},
						$request->{'client_name'},$request->{'reverse_client_name'},$request->{'protocol_name'},
						$request->{'encryption_protocol'},$request->{'encryption_cipher'},$request->{'encryption_keysize'},
						$request->{'sasl_method'},$request->{'sasl_sender'},$request->{'sasl_username'},$request->{'helo_name'},
						$request->{'sender'},$requestSize
					);
					if (!$sth) {
						$server->log(LOG_ERR,"[TRACKING] Failed to record session tracking info: ".awitpt::db::dblayer::Error());
						DBRollback();
						return -1;
					}
					$server->log(LOG_DEBUG,"[TRACKING] Added session tracking information for: ".Dumper($request)) if ($log);
	
					DBCommit();
				}
				# Initialize session data later on, we didn't get anything from the DB
				$initSessionData = 1;
			}
		# If we have no interest in tracking sessions, we must initialize the session data
		} else {
			$initSessionData = 1;
		}

		# Check if we must initialize the session data from the request
		if ($initSessionData) {	
			$sessionData->{'Instance'} = $request->{'instance'};
			$sessionData->{'QueueID'} = $request->{'queue_id'};
			$sessionData->{'ClientAddress'} = $request->{'client_address'};
			$sessionData->{'ClientName'} = $request->{'client_name'};
			$sessionData->{'ClientReverseName'} = $request->{'reverse_client_name'};
			$sessionData->{'Protocol'} = $request->{'protocol_name'};
			$sessionData->{'EncryptionProtocol'} = $request->{'encryption_protocol'};
			$sessionData->{'EncryptionCipher'} = $request->{'encryption_cipher'};
			$sessionData->{'EncryptionKeySize'} = $request->{'encryption_keysize'};
			$sessionData->{'SASLMethod'} = $request->{'sasl_method'};
			$sessionData->{'SASLSender'} = $request->{'sasl_sender'};
			$sessionData->{'SASLUsername'} = $request->{'sasl_username'};
			$sessionData->{'Helo'} = $request->{'helo_name'};
			$sessionData->{'Sender'} = $request->{'sender'};
			$sessionData->{'Size'} = $requestSize;
			$sessionData->{'RecipientData'} = "";
		}
		
		# Set client address..
		$sessionData->{'_ClientAddress'} = new awitpt::netip($sessionData->{'ClientAddress'});
		if (!defined($sessionData->{'_ClientAddress'})) {
			$server->log(LOG_ERR,"[TRACKING] Failed to understand ClientAddress: ".awitpt::netip::Error());
			return -1;
		}

		# If we in rcpt, caclulate and save policy
		if ($request->{'protocol_state'} eq 'RCPT') {
			$server->log(LOG_DEBUG,"[TRACKING] Protocol state is 'RCPT', resolving policy...") if ($log);

			$sessionData->{'Recipient'} = $request->{'recipient'};

			# Get policy
			my $policy = getPolicy($server,$sessionData);
			if (ref $policy ne "HASH") {
				return -1;
			}
			
			$server->log(LOG_DEBUG,"[TRACKING] Policy resolved into: ".Dumper($policy)) if ($log);
	
			$sessionData->{'Policy'} = $policy;
	
		# If we in end of message, load policy from data
		} elsif ($request->{'protocol_state'} eq 'END-OF-MESSAGE') {
			$server->log(LOG_DEBUG,"[TRACKING] Protocol state is 'END-OF-MESSAGE', decoding policy...") if ($log);
			# Decode... only if we actually have session data from the DB, which means initSessionData is 0
			if (!$initSessionData) {
				$sessionData->{'_Recipient_To_Policy'} = decodePolicyData($sessionData->{'RecipientData'});
			}
			
			$server->log(LOG_DEBUG,"[TRACKING] Decoded into: ".Dumper($sessionData->{'_Recipient_To_Policy'})) if ($log);

			# This must be updated here ... we may of got actual size
			$sessionData->{'Size'} = $requestSize;
			# Only get a queue id once we have gotten the message
			$sessionData->{'QueueID'} = $request->{'queue_id'};
		}

	# Check for HTTP protocol transport
	} elsif ($request->{'_protocol_transport'} eq "HTTP") {

		# Set client address..
		$sessionData->{'ClientAddress'} = $request->{'client_address'};
		$sessionData->{'_ClientAddress'} = new awitpt::netip($sessionData->{'ClientAddress'});
		if (!defined($sessionData->{'_ClientAddress'})) {
			$server->log(LOG_ERR,"[TRACKING] Failed to understand ClientAddress: ".awitpt::netip::Error());
			return -1;
		}

		$sessionData->{'ClientReverseName'} = $request->{'client_reverse_name'} if (defined($request->{'client_reverse_name'}));
		$sessionData->{'Helo'} = $request->{'helo_name'} if (defined($request->{'helo_name'}));
		$sessionData->{'Sender'} = $request->{'sender'};

		# If we in RCPT state, set recipient
		if ($request->{'protocol_state'} eq "RCPT") {
			$server->log(LOG_DEBUG,"[TRACKING] Protocol state is 'RCPT', resolving policy...") if ($log);

			# Get policy
			my $policy = getPolicy($server,$request->{'client_address'},$request->{'sender'},$request->{'recipient'},$request->{'sasl_username'});
			if (ref $policy ne "HASH") {
				return -1;
			}
			
			$server->log(LOG_DEBUG,"[TRACKING] Policy resolved into: ".Dumper($policy)) if ($log);
	
			$sessionData->{'Policy'} = $policy;
			$sessionData->{'Recipient'} = $request->{'recipient'};
		}
	}

	# Shove in various thing not stored in DB
	$sessionData->{'ProtocolTransport'} = $request->{'_protocol_transport'};
	$sessionData->{'ProtocolState'} = $request->{'protocol_state'};
	$sessionData->{'UnixTimestamp'} = $request->{'_timestamp'};
	# Make sure HELO is clean...
	$sessionData->{'Helo'} = defined($sessionData->{'Helo'}) ? $sessionData->{'Helo'} : '';

	$server->log(LOG_DEBUG,"[TRACKING] Request translated into session data: ".Dumper($sessionData)) if ($log);

	return $sessionData;
}


# Record session data
# Args:
# 	$server, $sessiondata
sub updateSessionData
{
	my ($server,$sessionData) = @_;


	# Check the protocol transport
	if ($sessionData->{'ProtocolTransport'} eq "Postfix") {

		# Return if we're not in RCPT state, in this case we shouldn't update the data
		if ($sessionData->{'ProtocolState'} eq 'RCPT') {
	
			# Get encoded policy data
			my $policyData = encodePolicyData($sessionData->{'Recipient'},$sessionData->{'Policy'});
			# Make sure recipient data is set
			my $recipientData = defined($sessionData->{'RecipientData'}) ? $sessionData->{'RecipientData'} : "";
			# Generate recipient data, make sure we don't use a undefined value either!
			$recipientData .= "/$policyData";
	
			# Record tracking info
			my $sth = DBDo('
				UPDATE 
					@TP@session_tracking 
				SET
					RecipientData = ?
				WHERE
					Instance = ?
				',
				$recipientData,$sessionData->{'Instance'}
			);
			if (!$sth) {
				$server->log(LOG_ERR,"[TRACKING] Failed to update recipient data in session tracking info: ".awitpt::db::dblayer::Error());
				return -1;
			}
		
		# If we at END-OF-MESSAGE, update size
		} elsif ($sessionData->{'ProtocolState'} eq 'END-OF-MESSAGE') {
			# Record tracking info
			my $sth = DBDo('
				UPDATE 
					@TP@session_tracking 
				SET
					QueueID = ?,
					Size = ?
				WHERE
					Instance = ?
				',
				$sessionData->{'QueueID'},$sessionData->{'Size'},$sessionData->{'Instance'}
			);
			if (!$sth) {
				$server->log(LOG_ERR,"[TRACKING] Failed to update size in session tracking info: ".awitpt::db::dblayer::Error());
				return -1;
			}
		}
	}

	return 0;
}


# Build a hash without all LC names
sub hashifyDBSessionData
{
	my $record = shift;


	return hashifyLCtoMC($record, qw(
			Instance QueueID
			UnixTimestamp
			ClientAddress ClientName ClientReverseName
			Protocol
			EncryptionProtocol EncryptionCipher EncryptionKeySize
			SASLMethod SASLSender SASLUsername
			Helo
			Sender
			Size
			RecipientData
	));
}


1;
# vim: ts=4
