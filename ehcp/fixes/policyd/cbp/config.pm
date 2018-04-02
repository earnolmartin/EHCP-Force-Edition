# Globals for server
# Copyright (C) 2009-2011, AllWorldIT
# Copyright (C) 2008, LinuxRulz
# Copyright (C) 2005-2007 Nigel Kukard  <nkukard@lbsd.net>
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




package cbp::config;

use strict;

# Exporter stuff
require Exporter;
our (@ISA,@EXPORT);
@ISA = qw(Exporter);
@EXPORT = qw(
);

# Our vars
my $config;



# Initialize configuration
sub Init
{
	my $server = shift;


	# Setup configuration
	$config = $server->{'inifile'};

	my $db;
	$db->{'DSN'} = $config->{'database'}{'dsn'};
	$db->{'Username'} = $config->{'database'}{'username'};
	$db->{'Password'} = $config->{'database'}{'password'};
	$db->{'TablePrefix'} = $config->{'database'}{'table_prefix'};

	# Check we have all the config we need
	if (!defined($db->{'DSN'})) {
		$server->log(1,"server/config: No 'DSN' defined in config file for 'database'");
		exit 1;
	}

	$server->{'cbp'}{'database'} = $db;
}


# Return config hash
sub getConfig
{
	return $config;
}


1;
# vim: ts=4
