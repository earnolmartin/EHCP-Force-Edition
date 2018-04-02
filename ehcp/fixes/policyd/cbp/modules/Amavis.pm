# Amavis module
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


package cbp::modules::Amavis;

use strict;
use warnings;


use cbp::logging;
use awitpt::db::dblayer;
use cbp::system;
use cbp::protocols;


# User plugin info
our $pluginInfo = {
	name 			=> "Amavis Plugin",
	priority		=> 50,
	init		 	=> \&init
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
	if (defined($inifile->{'amavis'})) {
		foreach my $key (keys %{$inifile->{'amavis'}}) {
			$config{$key} = $inifile->{'amavis'}->{$key};
		}
	}

	# Check if enabled
	if ($config{'enable'} =~ /^\s*(y|yes|1|on)\s*$/i) {
		$server->log(LOG_NOTICE,"  => Amavis: enabled");
		$config{'enable'} = 1;
		# Enable tracking, we need this to pass data to the amavis plugin
		$server->{'config'}{'track_sessions'} = 1;
	} else {
		$server->log(LOG_NOTICE,"  => Amavis: disabled");
	}
}


1;
# vim: ts=4
