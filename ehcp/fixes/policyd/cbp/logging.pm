# Logging constants
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


package cbp::logging;

use strict;
use warnings;


# Exporter stuff
require Exporter;
our (@ISA,@EXPORT,@EXPORT_OK);
@ISA = qw(Exporter);
@EXPORT = qw(
	LOG_ERR
	LOG_WARN
	LOG_NOTICE
	LOG_INFO
	LOG_DEBUG
);
@EXPORT_OK = qw(
);


use constant {
	LOG_ERR		=> 0,
	LOG_WARN	=> 1,
	LOG_NOTICE	=> 2,
	LOG_INFO	=> 3,
	LOG_DEBUG	=> 4
};


1;
# vim: ts=4
