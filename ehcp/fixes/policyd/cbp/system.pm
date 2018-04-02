# System functions
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



package cbp::system;

use strict;
use warnings;

# Exporter stuff
require Exporter;
our (@ISA,@EXPORT);
@ISA = qw(Exporter);
@EXPORT = qw(

	getIPKey

);


# Get key from IP spec
sub getIPKey
{
	my ($spec,$ip) = @_;

	my $key;

	# Check if spec is ok...
	if (defined($spec) && $spec =~ /^\/(\d+)$/) {
		my $mask = $1;

		# Grab IP key, the clever way  ;)
		$key = $ip->copy('cidr' => $mask)->to_network()->to_str();
	}

	return $key;
}


1;
# vim: ts=4
