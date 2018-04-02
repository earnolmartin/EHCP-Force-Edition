# Bizanga protocol support module
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


package cbp::protocols::Bizanga;


use strict;
use warnings;


use POSIX;
use URI::Escape;

use cbp::version;
use cbp::logging;
use awitpt::db::dblayer;
use awitpt::netip;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Bizanga Protocol Support Module",
	init		 	=> \&init,
	priority	 	=> 50,
	protocol_init	=> \&protocol_init,
	protocol_check	=> \&protocol_check,
	protocol_parse	=> \&protocol_parse,
	protocol_response	=> \&protocol_response,
	protocol_getresponse	=> \&protocol_getresponse,
	protocol_validate	=> \&protocol_validate,
};

# Module configuration
my %config;


# Response data
my ($response,$response_data);


# Create a child specific context
sub init {
	my $server = shift;
	my $inifile = $server->{'inifile'};

	# Defaults
	$config{'enable'} = 1;

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Protocol(Bizanga): enabled");
		$config{'enable'} = 1;
	}
}


# Initialize per request data...
sub protocol_init {
	$response = undef;
	$response_data = undef;
}


# Check the buffer to see if this protocol is what we want
sub protocol_check {
	my ($server,$buffer) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});
	

	# If we not enabled, don't do anything
	return undef if (!$config{'enable'});

	# Ignore leading blank lines
	$buffer =~ s/^(?:\015?\012)+//;

	# Check we have at least one line
	return 0 if (!($buffer =~ /\012/));

	# Check for HTTP header
	if ($buffer =~ /^GET [^\s]+ HTTP\/(\d+)\.(\d+)\015?\012/) {
		my ($a,$b) = ($1,$2);

		$server->log(LOG_DEBUG,"[PROTOCOLS/Bizanga] Possible Bizanga (HTTP/$a.$b) protocol") if ($log);

		if ($buffer =~ /\015?\012\015?\012/) {
			$server->log(LOG_INFO,"[Protocols/Bizanga] Identified Bizanga (HTTP/$a.$b) protocol") if ($log);
			return 1;
		}
	}

	return 0;
}


# Process buffer into sessionData
sub protocol_parse {
	my ($server,$buffer) = @_;
	# Get this instance we're working with
	my $serverInstance = $server->{'server'};
	# Are we going to log?
	my $log = defined($server->{'config'}{'logging'}{'bizanga'});

	my %res;

	# remove /?
	$buffer =~ s/^\w+ \/\?//;

	# Loop with each line
	foreach my $item (split /[& ]/, $buffer) {
		# If we don't get a pair, b0rk
		last unless $item =~ s/^([^=]+)=(.*)$//;

		# Clean up strings, and shove into hash
		my ($param,$value) = (uri_unescape($1),uri_unescape($2));
		$res{$param} = $value;
		$server->log(LOG_DEBUG,"[BIZANGA] Request parameter '$param' with value '$value'") if ($log);
	}

	# We need some extra info to make everything else happy...
	$res{'protocol_state'} = "RCPT" if (!defined($res{'protocol_state'}));

	$res{'_protocol_transport'} = "HTTP";

	return \%res;
}


# Process response
sub protocol_response 
{
	my ($server,$resp,$data) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});


	# Check protocol responses...
	if ($resp == PROTO_PASS) {
		$response = "200";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_PASS with response '$response':'$response_data'") if ($log);
		return CBP_CONTINUE;

	} elsif ($resp == PROTO_OK) {
		$response = "200";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_OK with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_REJECT) {
		if ($data =~ /^(5[0-9]{2}) (.*)/) {
			$response = "403";
			$response_data = $2;
		} else {
			$response = "403";
			$response_data = $data;
		}
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_REJECT with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_DEFER) {
		if ($data =~ /^(4[0-9]{2}) (.*)/) {
			$response = "401";
			$response_data = $2;
		} else {
			$response = "401";
			$response_data = $data;
		}
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_DEFER with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_HOLD) {
		$server->log(LOG_ERR,"[PROTOCOL/Bizanga] Unsupported return PROTO_HOLD");
		return CBP_STOP;

	} elsif ($resp == PROTO_REDIRECT) {
		$server->log(LOG_ERR,"[PROTOCOL/Bizanga] Unsupported return PROTO_REDIRECT");
		return CBP_STOP;

	} elsif ($resp == PROTO_DISCARD) {
		$server->log(LOG_ERR,"[PROTOCOL/Bizanga] Unsupported return PROTO_DISCARD");
		return CBP_STOP;

	} elsif ($resp == PROTO_FILTER) {
		$server->log(LOG_ERR,"[PROTOCOL/Bizanga] Unsupported return PROTO_FILTER");
		return CBP_STOP;

	} elsif ($resp == PROTO_PREPEND) {
		$server->log(LOG_ERR,"[PROTOCOL/Bizanga] Unsupported return PROTO_PREPEND");
		return CBP_CONTINUE;

	} elsif ($resp == PROTO_ERROR) {
		$response = "503";
		$response_data = defined($data) ? $data : "Unknown error";
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_ERROR with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_DB_ERROR) {
		$response = "504";
		$response_data = defined($data) ? $data : "Database error";
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_DB_ERROR with response '$response':'$response_data'") if ($log);
		return CBP_STOP;
	
	} elsif ($resp == PROTO_DATA_ERROR) {
		$response = "502";
		$response_data = defined($data) ? $data : "Database record error";
		$server->log(LOG_DEBUG,"[PROTOCOL/Bizanga] Received PROTO_DATA_ERROR with response '$response':'$response_data'") if ($log);
		return CBP_STOP;
	
	# Fallthrough
	} else {
		$server->log(LOG_ERR,"[PROTOCOL/Bizanga] Cannot understand response code '$resp'");
		return CBP_ERROR;
	}
}


# Get protocol response
sub protocol_getresponse 
{
	my $resp;


	# If its undefined, set to DUNNO
	if (!defined($response)) {
		$response = "200";
		$response_data = "Pass";
	}

	# Check if we have any additional data
	$response_data = "" if (!defined($response_data));	

	# Get timestamp
	my $timestamp = strftime("%a, %d %b %Y %H:%M:%S %Z",localtime());

	# Construct response
	$resp = "HTTP/1.0 $response $response_data
Date: $timestamp
Content-Length: 0
Content-Type: text/plain
Server: Policyd/".VERSION." (Cluebringer)
Connection: close
";

	return "$resp\n"
}


# Validate protocol data
sub protocol_validate {
	my ($server,$request) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});
	

	# Check params
	if (!awitpt::netip::is_valid($request->{'client_address'})) {
		my $client_address = defined($request->{'client_address'}) ? "'".$request->{'client_address'}."'" : "undef";
		$server->log(LOG_DEBUG,"[PROTOCOLS/Bizanga] Error, parameter 'client_address' cannot be $client_address") if ($log);
		return "Required parameter 'client_address' was not found or invalid format";
	}

	if (!defined($request->{'sender'}) || !($request->{'sender'} =~ /^(?:\S+@\S+|)$/) ) {
		my $sender = defined($request->{'sender'}) ? "'".$request->{'sender'}."'" : "undef";
		$server->log(LOG_DEBUG,"[PROTOCOLS/Bizanga] Error, parameter 'sender' cannot be $sender") if ($log);
		return "Required parameter 'sender' was not found or invalid format";
	}

	if (!defined($request->{'recipient'}) || !($request->{'recipient'} =~ /^\S+@\S+$/) ) {
		my $recipient = defined($request->{'recipient'}) ? "'".$request->{'recipient'}."'" : "undef";
		$server->log(LOG_DEBUG,"[PROTOCOLS/Bizanga] Error, parameter 'recipient' cannot be $recipient") if ($log);
		return "Required parameter 'recipient' was not found or invalid format";
	}
}




1;
# vim: ts=4
