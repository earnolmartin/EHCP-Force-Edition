# Postfix SMTP Access delegation protocol support module
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


package cbp::protocols::Postfix;


use strict;
use warnings;


use cbp::logging;
use awitpt::db::dblayer;
use awitpt::netip;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Postfix SMTP Access Delegation Protocol Suppot Module",
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
		$server->log(LOG_NOTICE,"  => Protocol(Postfix): enabled");
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

	# Check we have at least one line
	return 0 if (!($buffer =~ /\012/));

	# Check for policy protocol
	if ($buffer =~ /^\w+=[^\012]+\015?\012/) {

		$server->log(LOG_DEBUG,"[PROTOCOLS/Postfix] Possible Postfix protocol") if ($log);

		if ($buffer =~ /\015?\012\015?\012/) {
			$server->log(LOG_INFO,"[PROTOCOLS/Postfix] Identified Postfix protocol") if ($log);
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

	my %res;

	# Loop with each line
	foreach my $line (split /\015?\012/, $buffer) {
		# If we don't get a pair, b0rk
		last unless $line =~ s/^([^=]+)=(.*)$//;
		$res{$1} = $2;
	}

	$res{'_protocol_transport'} = "Postfix";

	return \%res;
}


# Process response
sub protocol_response 
{
	my ($server,$resp,$data) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});


	# Check protocol responses...
	if ($resp == PROTO_PASS) {
		$response = "DUNNO";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_PASS with response '$response':'$response_data'") if ($log);
		return CBP_CONTINUE;

	} elsif ($resp == PROTO_OK) {
		$response = "OK";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_OK with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_REJECT) {
		if ($data =~ /^(5[0-9]{2}) (.*)/) {
			$response = $1;
			$response_data = $2;
		} else {
			$response = "REJECT";
			$response_data = $data;
		}
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_REJECT with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_DEFER) {
		if ($data =~ /^(4[0-9]{2}) (.*)/) {
			$response = $1;
			$response_data = $2;
		} else {
			$response = "DEFER";
			$response_data = $data;
		}
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_DEFER with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_HOLD) {
		$response = "HOLD";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_HOLD with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_REDIRECT) {
		$response = "REDIRECT";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_REDIRECT with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_DISCARD) {
		$response = "DISCARD";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_DISCARD with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_FILTER) {
		$response = "FILTER";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_FILTER with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_PREPEND) {
		$response = "PREPEND";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_PREPEND with response '$response':'$response_data'") if ($log);
		return CBP_CONTINUE;

	} elsif ($resp == PROTO_ERROR) {
		$response = "DEFER";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_ERROR with response '$response':'$response_data'") if ($log);
		return CBP_STOP;

	} elsif ($resp == PROTO_DB_ERROR) {
		$response = "DEFER";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_DB_ERROR with response '$response':'$response_data'") if ($log);
		return CBP_STOP;
	
	} elsif ($resp == PROTO_DATA_ERROR) {
		$response = "DEFER";
		$response_data = $data;
		$server->log(LOG_DEBUG,"[PROTOCOL/Postfix] Received PROTO_DATA_ERROR with response '$response':'$response_data'") if ($log);
		return CBP_STOP;
	
	# Fallthrough
	} else {
		$server->log(LOG_ERR,"[PROTOCOL/Postfix] Cannot understand response code '$resp'");
		return CBP_ERROR;
	}
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
sub protocol_validate {
	my ($server,$request) = @_;
	my $log = defined($server->{'config'}{'logging'}{'protocols'});
	

	# Check params
	if (!defined($request->{'protocol_state'})) {
		$server->log(LOG_ERR,"[PROTOCOLS/Postfix] Error, parameter 'protocol_state' must be defined") if ($log);
		return "required parameter 'protocol_state' was not found";
	}

	if (!awitpt::netip::is_valid($request->{'client_address'})) {
		my $client_address = defined($request->{'client_address'}) ? "'".$request->{'client_address'}."'" : "undef";
		$server->log(LOG_ERR,"[PROTOCOLS/Postfix] Error, parameter 'client_address' cannot be $client_address") if ($log);
		return "required parameter 'client_address' was not found or invalid format";
	}

	if (!defined($request->{'sender'}) || !($request->{'sender'} =~ /^(?:\S+@\S+|)$/) ) {
		my $sender = defined($request->{'sender'}) ? "'".$request->{'sender'}."'" : "undef";
		$server->log(LOG_ERR,"[PROTOCOLS/Postfix] Error, parameter 'sender' cannot be $sender") if ($log);
		return "required parameter 'sender' was not found or invalid format";
	}

	if ($request->{'protocol_state'} eq "RCPT") {
		if (!defined($request->{'recipient'}) || !($request->{'recipient'} =~ /^\S+@\S+$/) ) {
			my $recipient = defined($request->{'recipient'}) ? "'".$request->{'recipient'}."'" : "undef"; 
			$server->log(LOG_ERR,"[PROTOCOLS/Postfix] Error, parameter 'recipient' cannot be $recipient") if ($log);
			return "required parameter 'recipient' was not found or invalid format";
		}
	}

	if (!defined($request->{'instance'}) || $request->{'instance'} eq "") {
		my $instance = defined($request->{'instance'}) ? "'".$request->{'instance'}."'" : "undef";
		$server->log(LOG_ERR,"[PROTOCOLS/Postfix] Error, parameter 'instance' cannot be $instance") if ($log);
		return "required parameter 'instance' was not found or invalid format";
	}
}





1;
# vim: ts=4
