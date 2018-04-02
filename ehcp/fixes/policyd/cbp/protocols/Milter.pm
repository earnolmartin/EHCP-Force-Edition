# Milter protocol support module
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


package cbp::protocols::Milter;


use strict;
use warnings;


use cbp::logging;
use awitpt::db::dblayer;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Milter Protocol Suppot Module",
	init		 	=> \&init,
	priority	 	=> 75,
	protocol_init	=> \&protocol_init,
	protocol_check	=> \&protocol_check,
	protocol_parse	=> \&protocol_parse,
	protocol_response	=> \&protocol_response,
	protocol_getresponse	=> \&protocol_getresponse,
	protocol_validate	=> \&protocol_validate,
};


# Milter constants (from Sendmail-PMilter)
# Protocols
use constant SMFIA_UNKNOWN      => 'U';
use constant SMFIA_UNIX         => 'L';
use constant SMFIA_INET         => '4';
use constant SMFIA_INET6        => '6';
# Commands
use constant SMFIC_ABORT        => 'A';
use constant SMFIC_BODY         => 'B';
use constant SMFIC_CONNECT      => 'C';
use constant SMFIC_MACRO        => 'D';
use constant SMFIC_BODYEOB      => 'E';
use constant SMFIC_HELO         => 'H';
use constant SMFIC_HEADER       => 'L';
use constant SMFIC_MAIL         => 'M';
use constant SMFIC_EOH          => 'N';
use constant SMFIC_OPTNEG       => 'O';
use constant SMFIC_RCPT         => 'R';
use constant SMFIC_QUIT         => 'Q';
use constant SMFIC_DATA         => 'T'; # v4
use constant SMFIC_UNKNOWN      => 'U'; # v3
# Replies
use constant SMFIR_ADDRCPT      => '+';
use constant SMFIR_DELRCPT      => '-';
use constant SMFIR_ACCEPT       => 'a';
use constant SMFIR_REPLBODY     => 'b';
use constant SMFIR_CONTINUE     => 'c';
use constant SMFIR_DISCARD      => 'd';
use constant SMFIR_ADDHEADER    => 'h';
use constant SMFIR_INSHEADER    => 'i'; # v3, or v2 and Sendmail 8.13+
use constant SMFIR_CHGHEADER    => 'm';
use constant SMFIR_PROGRESS     => 'p';
use constant SMFIR_QUARANTINE   => 'q';
use constant SMFIR_REJECT       => 'r';
use constant SMFIR_SETSENDER    => 's';
use constant SMFIR_TEMPFAIL     => 't';
use constant SMFIR_REPLYCODE    => 'y';
# Protocol flags?
use constant SMFIP_NOCONNECT    => 0x01;
use constant SMFIP_NOHELO       => 0x02;
use constant SMFIP_NOMAIL       => 0x04;
use constant SMFIP_NORCPT       => 0x08;
use constant SMFIP_NOBODY       => 0x10;
use constant SMFIP_NOHDRS       => 0x20;
use constant SMFIP_NOEOH        => 0x40;
use constant SMFIP_NONE         => 0x7F;

# Reply constants
use constant SMFIS_CONTINUE     => 100;
use constant SMFIS_REJECT       => 101;
use constant SMFIS_DISCARD      => 102;
use constant SMFIS_ACCEPT       => 103;
use constant SMFIS_TEMPFAIL     => 104;

use constant SMFIF_ADDHDRS      => 0x01;
use constant SMFIF_CHGBODY      => 0x02;
use constant SMFIF_ADDRCPT      => 0x04;
use constant SMFIF_DELRCPT      => 0x08;
use constant SMFIF_CHGHDRS      => 0x10;
use constant SMFIF_MODBODY      => SMFIF_CHGBODY;

use constant SMFI_V1_ACTS       => SMFIF_ADDHDRS|SMFIF_CHGBODY|SMFIF_ADDRCPT|SMFIF_DELRCPT;
use constant SMFI_V2_ACTS       => SMFI_V1_ACTS|SMFIF_CHGHDRS;
use constant SMFI_CURR_ACTS     => SMFI_V2_ACTS;


# Module configuration
my %config;

# Response data
my ($response,$response_data);


# Create a child specific context
sub init
{
	my $server = shift;
	my $inifile = $server->{'inifile'};

	# Defaults
	$config{'enable'} = 1;

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Protocol(Milter): enabled");
		$config{'enable'} = 1;
	}
}



# Initialize per request data...
sub protocol_init
{
	$response = undef;
	$response_data = undef;
}



# Check the buffer to see if this protocol is what we want
sub protocol_check
{
	my ($server,$buffer) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});
	

	# If we not enabled, don't do anything
	return undef if (!$config{'enable'});

	# Check that we have at least 5 bytes , len = 4 , cmd = 1
	return 0 if (length($buffer) < 5);


	# Pull off length & command
	my $rawLen = substr($buffer,0,4);
	my $rawCmd = substr($buffer,4,1);

	# Pull out len and see if its valid
	my $len = unpack('N',$rawLen);
	if ($len < 1 || $len > 131072) {
		return 0;
	}

	# Pull in raw data
	my $rawData = substr($buffer,5, $len - 1);

	# Check if this is a protocol negotiation, if it is ... WE FOUND IT!
	if ($rawCmd eq SMFIC_OPTNEG) {
		# Packet MUST be 12 bytes long
		if (length($rawData) == 12) {	
			# Process protocol
			my ($ver, $sActions, $sProtocols) = unpack('NNN', $rawData);

			# Check version
			if ($ver == 2) {
				$server->log(LOG_INFO,"[PROTOCOLS/Milter] Identified Milter protocol version '$ver'") if ($log);

				my $protocols = SMFIP_NONE;
				$protocols &= ~SMFIP_NOCONNECT; # CONNECT callback
				$protocols &= ~SMFIP_NOMAIL; # MAIL FROM callback
				$protocols &= ~SMFIP_NOHELO; # HELO callback
				$protocols &= ~SMFIP_NORCPT; # RCPT TO callback
				$protocols &= ~SMFIP_NOBODY; # BODY callback
				$protocols &= ~SMFIP_NOHDRS; # HEADER callback
				$protocols &= ~SMFIP_NOEOH; # EOH callback

				my $callback_flags = SMFI_V2_ACTS;

				# Reply...
				send_packet(SMFIC_OPTNEG, 
						pack("NNN", $ver, $callback_flags & $sActions, $protocols & $sProtocols)
				);

				return 1;
			} else {
				$server->log(LOG_INFO,"[PROTOCOLS/Milter] Unknown Milter protocol version '$ver'") if ($log);
			}

		} else {
			$server->log(LOG_INFO,"[PROTOCOLS/Milter] Milter SMFIC_OPTNEG packet wrong size") if ($log);
		}

	}


	return 0;
}



# Process buffer into sessionData
sub protocol_parse
{
	my ($server,$oldbuffer) = @_;

	my %res;


	# Buffer
	my $buffer = "";

	# Create an FDSET for use in select()
	my $fdset = "";
	vec($fdset, fileno(STDIN), 1) = 1;
	# Loop
	while (1) {
		$server->log(LOG_DEBUG,"NK DEBUG: LOOP");

		# Check for timeout....
		my $n = select($fdset,undef,undef,$server->{'server'}->{'timeout'});
		if (!$n) {
			$server->log(LOG_WARN,"[PROTOCOLS/Milter] Timeout from => Peer: ".$server->{'server'}->{'peeraddr'}.":".
					$server->{'server'}->{'peerport'}.", Local: ".$server->{'server'}->{'sockaddr'}.":".$server->{'server'}->{'sockport'});
			return;
		}

				
		# Read in 8kb
		$n = sysread(STDIN,$buffer,8192,length($buffer));
		if (!$n) {
			my $reason = defined($n) ? "Client closed connection" : "sysread[$!]";
			$server->log(LOG_WARN,"[CBPOLICYD/Milter] $reason => Peer: ".$server->{'server'}->{'peeraddr'}.":".$server->{'server'}->{'peerport'}.
					", Local: ".$server->{'server'}->{'sockaddr'}.":".$server->{'server'}->{'sockport'});
			return;
		}

		$server->log(LOG_DEBUG,"NK DEBUG: ".unpack("H*",$buffer));

#######################
		# Check that we have at least 5 bytes , len = 4 , cmd = 1
		next if (length($buffer) < 5);

		# Pull off length & command
		my $rawLen = substr($buffer,0,4);
		my $rawCmd = substr($buffer,4,1);

		# Pull out len and see if its valid
		my $len = unpack('N',$rawLen);
		if ($len < 1 || $len > 131072) {
			return 0;
		}

		# Pull in raw data
		my $rawData = substr($buffer,5, $len - 1);
		
		$server->log(LOG_DEBUG,"NK DEBUG: Len = $len, Command = $rawCmd: $rawData");

		use Data::Dumper;

		# Check packet type
		if ($rawCmd eq SMFIC_MACRO) {
			$server->log(LOG_DEBUG,"NK DEBUG: SMFIC_MACRO: ".Dumper(split_buffer($rawData)));
		}
	}


	$res{'_protocol_transport'} = "Milter";

	return \%res;
}



# Process response
sub protocol_response 
{
	my ($server,$resp,$data) = @_;

	# Not yet implemented
	return CBP_CONTINUE;
}



# Get protocol response
sub protocol_getresponse 
{
	my $resp;


	# If its undefined, set to DUNNO
	if (!defined($response)) {
		$response = "DUNNO";
	}

	# Build string we need
	$resp = "action=$response" . ( defined($response_data) ? " $response_data" : "" );

	return "$resp\n\n"
}


# Validate protocol data
sub protocol_validate
{
	my ($server,$request) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});
	

	return "protocol 'Milter' not supported at present";
}



# Function to build and send a packet
sub send_packet
{
	my ($code,$data) = @_;

	# Blank data if its undefined
	$data = '' unless defined($data);

	# Encode lengh in network byte order
	my $len = pack('N', length($data) + 1);

	# Write
	syswrite(STDOUT,$len);
	syswrite(STDOUT,$code);
	syswrite(STDOUT,$data);
}



# Function to split up buffer
sub split_buffer
{
	my $buffer = shift;

	# Remove trailing NUL
	$buffer =~ s/\0$//; 
	# Split on NULL
	my @results = split(/\0/, $buffer);

	return @results;
};



1;
# vim: ts=4
