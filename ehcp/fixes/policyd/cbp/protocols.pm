# Protocols, common stuff
# Copyright (C) 2009-2011, AllWorldIT
# Copyright (C) 2008, LinuxRulz
# Copyright (C) 2007, Nigel Kukard  <nkukard@lbsd.net>
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


package cbp::protocols;

use strict;
use warnings;


# Exporter stuff
require Exporter;
our (@ISA,@EXPORT,@EXPORT_OK);
@ISA = qw(Exporter);
@EXPORT = qw(
	CBP_CONTINUE
	CBP_ERROR
	CBP_STOP
	CBP_SKIP

	PROTO_PASS
	PROTO_OK
	PROTO_REJECT
	PROTO_DEFER
	PROTO_HOLD
	PROTO_REDIRECT
	PROTO_DISCARD
	PROTO_FILTER
	PROTO_PREPEND

	PROTO_ERROR
	PROTO_DB_ERROR
	PROTO_DATA_ERROR
);
@EXPORT_OK = qw(
);


use constant {
	CBP_CONTINUE => 0,
	CBP_ERROR => -1,
	CBP_STOP => 1,
	CBP_SKIP => 2,

	PROTO_PASS => 1,
	PROTO_OK => 2,
	PROTO_REJECT => 3,
	PROTO_DEFER => 4,
	PROTO_HOLD => 5,
	PROTO_REDIRECT => 6,
	PROTO_DISCARD => 7,
	PROTO_FILTER => 8,
	PROTO_PREPEND => 9,
	
	# Errors
	PROTO_ERROR => -1001,
	PROTO_DB_ERROR => -2001,
	PROTO_DATA_ERROR => -2101,
};


1;
# vim: ts=4
