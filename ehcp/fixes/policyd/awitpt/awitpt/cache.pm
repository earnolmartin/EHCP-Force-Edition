# Caching engine
# Copyright (C) 2009-2011, AllWorldIT
# Copyright (C) 2008, LinuxRulz
# Copyright (C) 2007 Nigel Kukard  <nkukard@lbsd.net>
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


package awitpt::cache;

use strict;
use warnings;


require Exporter;
our (@ISA,@EXPORT,@EXPORT_OK);
@ISA = qw(Exporter);
@EXPORT = qw(
	cacheStoreKeyPair
	cacheStoreComplexKeyPair
	cacheGetKeyPair
	cacheGetComplexKeyPair
);
@EXPORT_OK = qw(
	getCacheHits
	getCacheMisses
);

use Cache::FastMmap;
use Storable;

# Cache stuff
my $cache_type = "FastMmap";
my $cache;


# Our current error message
my $error = "";

## @internal
# @fn setError($err)
# This function is used to set the last error for this class
#
# @param err Error message
sub setError
{
	my $err = shift;
	my ($package,$filename,$line) = caller;
	my (undef,undef,undef,$subroutine) = caller(1);

	# Set error
	$error = "$subroutine($line): $err";
}


## @internal
# @fn Error
# Return current error message
#
# @return Last error message
sub Error
{
	my $err = $error;

	# Reset error
	$error = "";

	# Return error
	return $err;
}





## @fn Init($server)
# Initialize cache
#
# @param server Server object
# @param params Parameters for the cache
# @li cache_file Filename of the cache file
# @li cache_file_user Owner of the cache file
# @li cache_file_group Group of the cache file
sub Init
{
	my ($server,$params) = @_;
	my $ch;


	# We going to pass these options to new()
	my %opt = (
		'page_size' => 2048,
		'num_pages' => 1000,
		'expire_time' => 300,
		'raw_values' => 1,
		'unlink_on_exit' => 1,
	);

	# Check if we have the optional $params
	if (defined($params)) {
		# If we have a special cache file, use it and init it too
		if (defined($params->{'cache_file'})) {
			$opt{'share_file'} = $params->{'cache_file'};
			$opt{'init_file'} = 1;
		}
	}

	# Create Cache
	$ch = Cache::FastMmap->new(%opt);

	# Check if we have the optional $params
	if (defined($params)) {
		# If we have an explicit owner set, use it
		if (defined($params->{'cache_file_user'})) {
			# Check all is ok...
			my ($chown_user,$chown_group);
			if (!($chown_user = getpwnam($params->{'cache_file_user'}))) {
				setError("User '$chown_user' appears to be invalid: $?");
				return(-1);
			}
			if (!($chown_group = getgrnam($params->{'cache_file_group'}))) {
				setError("Group '$chown_group' appears to be invalid: $?");
				return(-1);
			}
			# Go in and chown it
			if (!chown($chown_user,$chown_group,$opt{'share_file'})) {
				setError("Failed to chown cache file '".$opt{'share_file'}."': $!");
				return(-1);
			}
		}
	}

	# Stats
	$ch->set('Cache/Stats/Hit',0);
	$ch->set('Cache/Stats/Miss',0);

	# Set server vars
	$server->{'cache_engine'}{'handle'} = $ch;
};


## @fn Destroy()
# Destroy cache
#
# @param server Server object
sub Destroy
{
};


## @fn connect($server)
# Connect server with the cache
#
# @param server Server object
sub connect
{
	my $server = shift;

	$cache = $server->{'cache_engine'}{'handle'};
}


## @fn disconnect()
# Disconnect cache from server
#
# @param server Server object
sub disconnect
{
}


## @fn cacheStoreKeyPair($cacheName,$key,$value)
# Store keypair in cache
#
# @param cacheName Cache name to use
# @param key Item key
# @param value Item value
sub cacheStoreKeyPair
{
	my ($cacheName,$key,$value) = @_;


	if (!defined($cacheName)) {
		setError("Cache name not defined in store");
		return -1;
	}

	if ($cacheName eq "") {
		setError("Cache name not set in store");
		return -1;
	}

	if (!defined($key)) {
		setError("Key not defined for cache '$cacheName' store");
		return -1;
	}

	if (!defined($value)) {
		setError("Value not defined for cache '$cacheName' key '$key' store");
		return -1;
	}

	# If we're not caching just return
	return 0 if ($cache_type eq 'none');

	# Store
	$cache->set("$cacheName/$key",$value);

	return 0;
}


## @fn cacheGetKeyPair($cacheName,$key)
# Get value from cache
#
# @param cacheName Cache name to use
# @param key Item key
sub cacheGetKeyPair
{
	my ($cacheName,$key) = @_;


	if (!defined($cacheName)) {
		setError("Cache name not defined in get");
		return (-1);
	}

	if ($cacheName eq "") {
		setError("Cache name not set in get");
		return (-1);
	}

	if (!defined($key)) {
		setError("Key not defined for cache '$cacheName' get");
		return (-1);
	}

	# If we're not caching just return
	if ($cache_type eq 'none') {
		return (0,undef);
	}

	# Check and count
	my $res = $cache->get("$cacheName/$key");
	if ($res) {
		$cache->get_and_set('Cache/Stats/Hit',sub { return ++$_[1]; });
	} else {
		$cache->get_and_set('Cache/Stats/Miss',sub { return ++$_[1]; });
	}

	return (0,$res);
}


## @fn cacheStoreComplexKeyPair($cacheName,$key,$value)
# Store a complex keypair in cache, this would be an object and
# not a number or text
#
# @param cacheName Cache name to use
# @param key Item key
# @param value Item value
sub cacheStoreComplexKeyPair
{
	my ($cacheName,$key,$value) = @_;


	my $rawValue = Storable::freeze($value);
	if (!defined($rawValue)) {
		setError("Unable to freeze cache value in '$cacheName'");
		return -1;
	}

	return cacheStoreKeyPair($cacheName,$key,$rawValue);
}



## @fn cacheGetComplexKeyPair($cacheName,$key)
# Get value from cache
#
# @param cacheName Cache name to use
# @param key Item key
sub cacheGetComplexKeyPair
{
	my ($cacheName,$key) = @_;


	my ($res,$rawValue) = cacheGetKeyPair($cacheName,$key);
	# Thaw out item, if there is no error and we are defined
	if (!$res && defined($rawValue)) {
		$rawValue = Storable::thaw($rawValue);
	}

	return ($res,$rawValue);
}



## @fn getCacheHits
# Return cache hits
#
# @return Cache hits
sub getCacheHits
{
	my $res;


	# Get counter
	$res = defined($cache->get('Cache/Stats/Hit')) ? $cache->get('Cache/Stats/Hit') : 0;

	return $res;
}


## @fn getCacheMisses
# Return cache misses
#
# @return Cache misses
sub getCacheMisses
{
	my $res;


	# Get counter
	$res = defined($cache->get('Cache/Stats/Miss')) ? $cache->get('Cache/Stats/Miss') : 0;

	return $res;
}


1;
# vim: ts=4
