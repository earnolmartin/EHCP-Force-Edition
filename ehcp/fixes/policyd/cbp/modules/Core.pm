# Core module
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


package cbp::modules::Core;

use strict;
use warnings;


use cbp::logging;
use awitpt::db::dblayer;
use cbp::system;


# User plugin info
our $pluginInfo = {
	name 			=> "Core",
	priority		=> 100,
	cleanup		 	=> \&cleanup,
};


# Cleanup function
sub cleanup
{
	my ($server) = @_;

	# Get yesterday's time
	my $yesterday = time() - 86400;

	# Remove old tracking info from database
	my $sth = DBDo('
		DELETE FROM 
			@TP@session_tracking
		WHERE
			UnixTimestamp < ?
		',
		$yesterday
	);
	if (!$sth) {
		$server->log(LOG_ERR,"[CORE] Failed to remove old session tracking records: ".awitpt::db::dblayer::Error());
		return -1;
	}
	$server->log(LOG_INFO,"[CORE] Removed ".( $sth ne "0E0" ? $sth : 0)." records from session tracking table");
}


1;
# vim: ts=4
