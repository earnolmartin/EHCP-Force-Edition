# Policy handling functions
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


package cbp::policies;

use strict;
use warnings;

# Exporter stuff
require Exporter;
our (@ISA,@EXPORT);
@ISA = qw(Exporter);
@EXPORT = qw(
	getPolicy
	encodePolicyData
	decodePolicyData
);


use cbp::logging;
use awitpt::cache;
use awitpt::db::dblayer;
use awitpt::netip;
use cbp::system;

use Data::Dumper;


# Database handle
my $dbh = undef;

# Our current error message
my $error = "";

# Set current error message
# Args: error_message
sub setError
{
	my $err = shift;
	my ($package,$filename,$line) = caller;
	my (undef,undef,undef,$subroutine) = caller(1);

	# Set error
	$error = "$subroutine($line): $err";
}

# Return current error message
# Args: none
sub Error
{
	my $err = $error;

	# Reset error
	$error = "";

	# Return error
	return $err;
}


# Return a hash of policies matches
# Returns:
# 	Hash - indexed by policy priority, the value is an array of policy ID's
sub getPolicy
{
	my ($server,$sessionData) = @_;
	my $log = defined($server->{'config'}{'logging'}{'policies'});


	$server->log(LOG_DEBUG,"[POLICIES] Going to resolve session data into policy: ".Dumper($sessionData)) if ($log);

	# Start with blank policy list
	my %matchedPolicies = ();


	# Grab policy members from database
	my $policyMembers = getPolicyMembers($server,$log);
	if (ref($policyMembers) ne "ARRAY") {
		$server->log(LOG_DEBUG,"[POLICIES] Error while retriving policy members: $policyMembers");
		return \%matchedPolicies;
	}

	# Process the Members
	foreach my $policyMember (@{$policyMembers}) {
		# Make debugging a bit easier
		my $debugTxt = sprintf('[ID:%s/Name:%s]',$policyMember->{'ID'},$policyMember->{'Name'});

		#
		# Source Test
		#
		my $sourceMatch = 0;

		# No source or "any"
		if (!defined($policyMember->{'Source'}) || lc($policyMember->{'Source'}) eq "any") {
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: Source not defined or 'any', explicit match: matched=1") if ($log);
			$sourceMatch = 1;

		} else {
			# Split off sources
			my @rawSources = split(/,/,$policyMember->{'Source'});
			
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: Main policy sources '".join(',',@rawSources)."'") if ($log);

			# Default to no match
			my $history = {};  # Used to track depth & loops
			foreach my $item (@rawSources) {
				# Process item
				my $res = policySourceItemMatches($server,$debugTxt,$history,$item,$sessionData);
				# Check for error
				if ($res < 0) {
					$server->log(LOG_WARN,"[POLICIES] $debugTxt: Error while processing source item '$item', skipping...");
					$sourceMatch = 0;
					last;
				# Check for success
				} elsif ($res == 1) {
					$sourceMatch = 1;
				# Check for failure, 0 and anything else
				} else {
					$sourceMatch = 0;
					last;
				}
			}
		}
		
		$server->log(LOG_INFO,"[POLICIES] $debugTxt: Source matching result: matched=$sourceMatch") if($log);
		# Check if we passed the tests
		next if (!$sourceMatch);

		#
		# Destination Test
		#
		my $destinationMatch = 0;

		# No destination or "any"
		if (!defined($policyMember->{'Destination'}) || lc($policyMember->{'Destination'}) eq "any") {
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: Destination not defined or 'any', explicit match: matched=1") if ($log);
			$destinationMatch = 1;
		
		} else {
			# Split off destinations
			my @rawDestinations = split(/,/,$policyMember->{'Destination'});
				
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: Main policy destinations '".join(',',@rawDestinations)."'") if ($log);

			# Parse in group data
			my $history = {};  # Used to track depth & loops
			foreach my $item (@rawDestinations) {
				# Process item
				my $res = policyDestinationItemMatches($server,$debugTxt,$history,$item,$sessionData);
				# Check for error
				if ($res < 0) {
					$server->log(LOG_WARN,"[POLICIES] $debugTxt: Error while processing destination item '$item', skipping...");
					$destinationMatch = 0;
					last;
				# Check for success
				} elsif ($res == 1) {
					$destinationMatch = 1;
				# Check for failure, 0 and anything else
				} else {
					$destinationMatch = 0;
					last;
				}
			}
		}
		$server->log(LOG_INFO,"[POLICIES] $debugTxt: Destination matching result: matched=$destinationMatch") if ($log);
		# Check if we passed the tests
		next if (!$destinationMatch);

		push(@{$matchedPolicies{$policyMember->{'Priority'}}},$policyMember->{'PolicyID'});
	}

	# If we logging, display a list
	if ($log) {
		foreach my $prio (sort keys %matchedPolicies) {
			$server->log(LOG_DEBUG,"[POLICIES] END RESULT: prio=$prio => policy ids: ".join(',',@{$matchedPolicies{$prio}}));
		}
	}

	return \%matchedPolicies;
}


# Return an array of the policy members from the database
# Returns:
#	Array - array of policy members
sub getPolicyMembers
{
	my ($server,$log) = @_;


	# Check cache
#	my ($cache_res,$cache) = cacheGetComplexKeyPair('Policies','Members');
#	if ($cache_res) {
#		return awitpt::cache::Error();
#	}
#	return $cache if (defined($cache));

	# Grab all the policy members
	my $sth = DBSelect('
		SELECT 
			@TP@policies.Name, @TP@policies.Priority, @TP@policies.Disabled AS PolicyDisabled,
			@TP@policy_members.ID, @TP@policy_members.PolicyID, @TP@policy_members.Source, 
			@TP@policy_members.Destination, @TP@policy_members.Disabled AS MemberDisabled
		FROM
			@TP@policies, @TP@policy_members
		WHERE
			@TP@policies.Disabled = 0
			AND @TP@policy_members.Disabled = 0
			AND @TP@policy_members.PolicyID = @TP@policies.ID
	');
	if (!$sth) {
		$server->log(LOG_DEBUG,"[POLICIES] Error while selecing policy members from database: ".
				awitpt::db::dblayer::Error());
		return undef;
	}

	# Loop with results
	my @policyMembers;
	while (my $row = hashifyLCtoMC($sth->fetchrow_hashref(),
			qw( Name Priority PolicyDisabled ID PolicyID Source Destination MemberDisabled )
	)) {

		# Log what we see
		if ($row->{'PolicyDisabled'} eq "1") {
			$server->log(LOG_DEBUG,"[POLICIES] Policy '".$row->{'Name'}."' is disabled") if ($log);
		} elsif ($row->{'MemberDisabled'} eq "1") {
			$server->log(LOG_DEBUG,"[POLICIES] Policy member item with ID '".$row->{'ID'}."' is disabled") if ($log);
		} else {
			$server->log(LOG_DEBUG,"[POLICIES] Found policy member with ID '".$row->{'ID'}."' in policy '".$row->{'Name'}."'") if ($log);
			push(@policyMembers, $row);
		}
	}
	
	# Cache this
#	$cache_res = cacheStoreComplexKeyPair('Policies','Members',\@policyMembers);
#	if ($cache_res) {
#		return awitpt::cache::Error();
#	}

	return \@policyMembers;
}




# Get group members from group name
sub getGroupMembers
{
	my $group = shift;


	# Check cache
	my ($cache_res,$cache) = cacheGetKeyPair('Policies/Groups/Name-to-Members',$group);
	if ($cache_res) {
		return awitpt::cache::Error();
	}
	if (defined($cache)) {
		my @groupMembers = split(/,/,$cache);
		return \@groupMembers;
	}

	# Grab group members
	my $sth = DBSelect('
		SELECT 
			@TP@policy_group_members.Member
		FROM
			@TP@policy_groups, @TP@policy_group_members
		WHERE
			@TP@policy_groups.Name = ?
			AND @TP@policy_groups.ID = @TP@policy_group_members.PolicyGroupID
			AND @TP@policy_groups.Disabled = 0
			AND @TP@policy_group_members.Disabled = 0
		',
		$group
	);
	if (!$sth) {
		return awitpt::db::dblayer::Error();
	}
	# Pull in groups
	my @groupMembers;
	while (my $row = hashifyLCtoMC($sth->fetchrow_hashref(), qw( Member ))) {
		push(@groupMembers,$row->{'Member'});
	}

	# Cache this
	$cache_res = cacheStoreKeyPair('Policies/Groups/Name-to-Members',$group,join(',',@groupMembers));
	if ($cache_res) {
		return awitpt::cache::Error();
	}

	return \@groupMembers;
}


# Check if this source item matches, this function automagically resolves groups aswell
sub policySourceItemMatches
{
	my ($server,$debugTxt,$history,$rawItem,$sessionData) = @_;
	my $log = defined($server->{'config'}{'logging'}{'policies'});


	# Rip out negate if we have it, and clean the item
	my ($negate,$tmpItem) = ($rawItem =~ /^(!)?(.*)/);
	# See if we match %, if we do its a group
	my ($isGroup,$item) = ($tmpItem =~ /^(%)?(.*)/);
	# IPv6 match components
	my $v6c = '[a-f\d]{1,4}';
	my $v6cg = "(?:$v6c:){0,6}";
	my $v6c1 = "$v6cg?:?:?$v6cg?(?:$v6c)?";
	my $v6m = '(?:\/\d{1,3})';
	my $v6 = "$v6c1$v6m?";
	
	# Check if this is a group
	my $match = 0;
	if ($isGroup) {
		# Make sure we're not looping
		if (defined($history->{$item})) {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: Source policy group '$item' appears to be used more than once, possible loop, aborting!");
			return -1;
		}
		
		# We going deeper, record the depth
		$history->{$item} = keys(%{$history});
		# Check if we not tooo deep
		if ($history->{$item} > 5) {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: This source policy is recursing too deep, aborting!");
			return -1;
		}

		# Get group members
		my $groupMembers = getGroupMembers($item);
		if (ref $groupMembers ne "ARRAY") {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: Error '$groupMembers' while retrieving group members for source group '$item'");
			return -1;
		}
		$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: Group '$item' has ".@{$groupMembers}." source(s) => ".join(',',@{$groupMembers})) if ($log);
		# Check if actually have any
		if (@{$groupMembers} > 0) {
			foreach my $gmember (@{$groupMembers}) {
				# Process this group member
				my $res = policySourceItemMatches($server,"$debugTxt=>(group:$item)",$history,$gmember,$sessionData);
				# Check for hard error
				if ($res < 0) {
					return $res;
				# Check for match
				} elsif ($res) {
					$match = 1;
					last;
				}
			}
		} else {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: No group members for source group '$item'");
		}
		$server->log(LOG_DEBUG,"[POLICIES] $debugTxt=>(group:$item): Source group result: matched=$match") if ($log);

	# Normal member
	} else {
		my $res = 0;

		# Match IPv4 or IPv6
		if (
			$item =~ /^(?:\d{1,3})(?:\.(?:\d{1,3})(?:\.(?:\d{1,3})(?:\.(?:\d{1,3}))?)?)?(?:\/(\d{1,2}))?$/ ||
			$item =~ /^$v6$/i
		) {
			# See if we get an object from 
			my $matchRange = new awitpt::netip($item);
			if (!defined($matchRange)) {
				$server->log(LOG_WARN,"[POLICIES] $debugTxt: - Resolved source '$item' to a IP/CIDR specification, but its INVALID: ".awitpt::netip::Error());
				return -1;
			}
			# Check if IP is within the range
			$res = $sessionData->{'_ClientAddress'}->is_within($matchRange);
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved source '$item' to a IP/CIDR specification, match = $res") if ($log);

		# Match peer IPv4 or IPv6 (the server requesting the policy)
		} elsif (
			$item =~ /^\[((?:\d{1,3})(?:\.(?:\d{1,3})(?:\.(?:\d{1,3})(?:\.(?:\d{1,3}))?)?)?(?:\/(\d{1,2}))?)\]$/ ||
			$item =~ /^\[($v6)\]$/i
		) {
			# We don't want the [ and ]
			my $cleanItem = $1;

			# See if we get an object from 
			my $matchRange = new awitpt::netip($cleanItem);
			if (!defined($matchRange)) {
				$server->log(LOG_WARN,"[POLICIES] $debugTxt: - Resolved source '$item' to a PEER IP/CIDR specification, but its INVALID: ".awitpt::netip::Error());
				return -1;
			}
			if ($server->{'server'}->{'peer_type'} eq "TCP") {
				# Check if IP is within the range
				$res = $sessionData->{'_PeerAddress'}->is_within($matchRange);
				$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved source '$item' to a PEER IP/CIDR specification, match = $res") if ($log);
			} else {
				$server->log(LOG_WARN,"[POLICIES] $debugTxt: - Trying to match source '$item' to a PEER IP/CIDR specification when peer type is '".$server->{'server'}->{'peer_type'}."'") if ($log);
				return -1;
			}


		# Match SASL user, must be above email addy to match SASL usernames in the same format as email addies
		} elsif ($item =~ /^\$\S+$/) {
			$res = saslUsernameMatches($sessionData->{'SASLUsername'},$item);
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved source '$item' to a SASL user specification, match = $res") if ($log);

		# Match blank email addy
		} elsif ($item eq "@") {
			$res = 1 if ($sessionData->{'Sender'} eq "");
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved source '$item' to a email blank address specification, match = $res") if ($log);

		# Match email addy
		} elsif ($item =~ /^\S*@\S+$/) {
			$res = emailAddressMatches($sessionData->{'Sender'},$item);
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved source '$item' to a email address specification, match = $res") if ($log);

		# Match domain name (for reverse dns)
		} elsif ($item =~ /^\.?(?:[a-z0-9\-_\*]+\.)+[a-z0-9]+$/i) {
			$res = reverseDNSMatches($sessionData->{'ClientReverseName'},$item);
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved source '$item' to a reverse dns specification, match = $res") if ($log);

		# Not valid
		} else {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: - Source '".$item."' is not a valid specification");
		}
		
		$match = 1 if ($res);
	}

	# Check the result, if its undefined or 0, return 0, if its 1 return 1
	# !1 == undef
	return ($negate ? !$match : $match) ? 1 : 0;
}



# Check if this destination item matches, this function automagically resolves groups aswell
sub policyDestinationItemMatches
{
	my ($server,$debugTxt,$history,$rawItem,$sessionData) = @_;
	my $log = defined($server->{'config'}{'logging'}{'policies'});


	# Rip out negate if we have it, and clean the item
	my ($negate,$tmpItem) = ($rawItem =~ /^(!)?(.*)/);
	# See if we match %, if we do its a group
	my ($isGroup,$item) = ($tmpItem =~ /^(%)?(.*)/);
	
	# Check if this is a group
	my $match = 0;
	if ($isGroup) {
		# Make sure we're not looping
		if (defined($history->{$item})) {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: Destination policy group '$item' appears to be used more than once, possible loop, aborting!");
			return -1;
		}
		
		# We going deeper, record the depth
		$history->{$item} = keys(%{$history});
		# Check if we not tooo deep
		if ($history->{$item} > 5) {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: This destination policy is recursing too deep, aborting!");
			return -1;
		}

		# Get group members
		my $groupMembers = getGroupMembers($item);
		if (ref $groupMembers ne "ARRAY") {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: Error '$groupMembers' while retrieving group members for destination group '$item'");
			return -1;
		}
		$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: Group '$item' has ".@{$groupMembers}." destination(s) => ".join(',',@{$groupMembers})) if ($log);
		# Check if actually have any
		if (@{$groupMembers} > 0) {
			foreach my $gmember (@{$groupMembers}) {
				# Process this group member
				my $res = policyDestinationItemMatches($server,"$debugTxt=>(group:$item)",$history,$gmember,$sessionData);
				# Check for hard error
				if ($res < 0) {
					return $res;
				# Check for match
				} elsif ($res) {
					$match = 1;
					last;
				}
			}
		} else {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: No group members for destination group '$item'");
		}
		$server->log(LOG_DEBUG,"[POLICIES] $debugTxt=>(group:$item): Destination group result: matched=$match") if ($log);

	# Normal member
	} else {
		my $res = 0;

		# Match email addy
		if ($item =~ /^!?\S*@\S+$/) {
			$res = emailAddressMatches($sessionData->{'Recipient'},$item);
			$server->log(LOG_DEBUG,"[POLICIES] $debugTxt: - Resolved destination '$item' to a email address specification, match = $res") if ($log);

		} else {
			$server->log(LOG_WARN,"[POLICIES] $debugTxt: - Destination '$item' is not a valid specification");
		}
		
		$match = 1 if ($res);
	}

	# Check the result, if its undefined or 0, return 0, if its 1 return 1
	# !1 == undef
	return ($negate ? !$match : $match) ? 1 : 0;
}



# Check if first arg lies within the scope of second arg email/domain
sub emailAddressMatches
{
	my ($email,$template) = @_;


	# Sender may be blank, in the case of <>
	return 0 if ($email eq "");

	my $match = 0;

	# Strip email addy
	my ($email_user,$email_domain) = ($email =~ /^(\S+)@(\S+)$/);
	my ($template_user,$template_domain) = ($template =~ /^(\S*)@(\S+)$/);

	# Make sure its all lowercase
	$template_user = lc($template_user);
	$template_domain = lc($template_domain);

	# Replace all .'s with \.'s
	$template_user =~ s/\./\\./g;
	$template_domain =~ s/\./\\./g;

	# Change *'s into a proper regex expression
	$template_user =~ s/\*/\\S*/g;
	$template_domain =~ s/\*/\\S*/g;

	# Check if we have a match
	if ($email_domain =~ /^$template_domain$/) {
		if (($email_user =~ /^$template_user$/) || $template_user eq "") {
			$match = 1;
		}
	}

	return $match;
}


# Check if first arg lies within the scope of second arg sasl specification
sub saslUsernameMatches
{
	my ($saslUsername,$template) = @_;

	my $match = 0;

	# Decipher template
	my ($template_user) = ($template =~ /^\$(\S+)$/);

	# If there is no SASL username
	if (!defined($saslUsername) || $saslUsername eq "") {
		# $- is a special case which allows matching against no SASL username
		if ($template_user eq '-') {
			$match = 1;
		}
	# Else regex it
	} else {
		# Make sure its all lowercase
		$template_user = lc($template_user);
		# Replace all .'s with \.'s
		$template_user =~ s/\./\\./g;
		# Change *'s into a proper regex expression
		$template_user =~ s/\*/\\S*/g;

		if ($saslUsername =~ /^$template_user$/) {
			$match = 1;
		}
	}

	return $match;
}


# Check if first arg lies within the scope of second arg reverse dns specification
sub reverseDNSMatches
{
	my ($reverseDNSMatches,$template) = @_;

	my $match = 0;
	my $partial = 0;

	# Check if we have a . at the beginning of the line to match partials
	if ($template =~ /^\./) {
		$partial = 1;
	}

	# Replace all .'s with \.'s
	$template =~ s/\./\\./g;
	# Change *'s into a proper regex expression
	$template =~ s/\*/[a-z0-9\-_\.]*/g;

	# Check for partial match
	if ($partial) {
		if ($reverseDNSMatches =~ /$template$/i) {
			$match = 1;
		}
	# Check for exact match
	} else {
		if ($reverseDNSMatches =~ /^$template$/i) {
			$match = 1;
		}
	}
	
	return $match;
}


# Encode policy data into session recipient data
sub encodePolicyData
{
	my ($email,$policy) = @_;

	# Generate...    <recipient@domain>#priority=policy_id,policy_id,policy_id;priority2=policy_id2,policy_id2/recipient2@...
	my $ret = "<$email>#";
	foreach my $priority (keys %{$policy}) {
		$ret .= sprintf('%s=%s;',$priority,join(',',@{$policy->{$priority}}));
	}

	return $ret;
}


# Decode recipient data into policy data
sub decodePolicyData
{
	my $recipientData = shift;


	my %recipientToPolicy = ();
	# Build policy str list and recipients list
	foreach my $item (split(/\//,$recipientData)) {
		# Skip over first /
		next if ($item eq "");

		my ($email,$rawPolicy) = ($item =~ /<([^>]*)>#(.*)/);

		# Make sure that the recipient data in the DB is not null, ie. it may 
		# of been killed by the admin before it updated it	
		if (defined($email) && defined($rawPolicy)) {
			# Loop with raw policies
			foreach my $policy (split(/;/,$rawPolicy)) {
				# Strip off priority and policy IDs
				my ($prio,$policyIDs) = ( $policy =~ /(\d+)=(.*)/ );
				# Pull off policyID's from string
				foreach my $pid (split(/,/,$policyIDs)) {
					push(@{$recipientToPolicy{$email}{$prio}},$pid);
				}
			}
		}
	}

	return \%recipientToPolicy;
}


1;
# vim: ts=4
