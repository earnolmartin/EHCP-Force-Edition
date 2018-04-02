# Database independent layer module
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




## @class awit::db::dbilayer
# Database independant layer module. This module encapsulates the DBI
# module and provides us with some tweaked functionality
package awitpt::db::dbilayer;

use strict;
use warnings;


use DBI;



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

## @fn internalError
# Return current module error message
#
# @return Last module error message
sub internalError
{
	my $err = $error;

	# Reset error
	$error = "";

	# Return error
	return $err;
}


## @member Error
# Return current object error message
#
# @return Current object error message
sub Error
{
	my $self = shift;

	my $err = $self->{_error};

	# Reset error
	$self->{_error} = "";

	# Return error
	return $err;
}



## @fn Init($server,$server_name)
# Initialize class and return a fully connected object
#
# @param server Server object
# @param server_name Name of server
#
# @return dbilayer object, undef on error
sub Init
{
	my $server = shift;
	my $server_name = shift;


	if (!defined($server)) {
		setError("Server object undefined");
		return undef;
	}
	if (!defined($server_name)) {
		setError("Server name undefined");
		return undef;
	}


	my $dbconfig = $server->{$server_name}->{'database'};


	# Check if we created
	my $dbh = awitpt::db::dbilayer->new($dbconfig->{'DSN'},$dbconfig->{'Username'},$dbconfig->{'Password'},
			$dbconfig->{'TablePrefix'});
	return undef if (!defined($dbh));


	return $dbh;
}


## @member new($dsn,$username,$password)
# Class constructor
#
# @param dsn Data source name
# @param username Username to use
# @param password Password to use
#
# @return Constructed object, undef on error
sub new
{
	my ($class,$dsn,$username,$password,$table_prefix) = @_;

	# Iternals
	my $self = {
		_type => undef,

		_dbh => undef,
		_error => undef,

		_dsn => undef,
		_username => undef,
		_password => undef,

		_table_prefix => "",

		_in_transaction => undef,
	};

	# Set database parameters
	if (defined($dsn)) {
		$self->{_dsn} = $dsn;
		$self->{_username} = $username;
		$self->{_password} = $password;
		$self->{_table_prefix} = $table_prefix if (defined($table_prefix) && $table_prefix ne "");
	} else {
		setError("Invalid DSN '$dsn' given");
		return undef;
	}

	# Try grab database type
	$self->{_dsn} =~ /^DBI:([^:]+):/i;
	$self->{_type} = (defined($1) && $1 ne "") ? lc($1) : "unknown";

	# Create...
	bless $self, $class;
	return $self;
}



## @member connect(@params)
# Return connection to database
#
# @param params DBI parameters
#
# @return 0 on success, < 0 on error
sub connect
{
	my $self = shift;


	$self->{_dbh} = DBI->connect($self->{_dsn}, $self->{_username}, $self->{_password}, {
			'AutoCommit' => 1,
			'PrintError' => 0,
			'FetchHashKeyName' => 'NAME_lc'
	});

	# Connect to database if we have to, check if we ok
	if (!$self->{_dbh}) {
		$self->{_error} = "Error connecting to database: $DBI::errstr";
		return -1;
	}

	# Apon connect we are not in a transaction
	$self->{_in_transaction} = 0;

	return 0;
}


## @member type
# Return database type
#
# @return Database type string
sub type
{
	my $self = shift;

	return $self->{_type};
}


## @member _check
# Check database connection and reconnect if we lost the connection
sub _check
{
	my $self = shift;


	# If we not in a transaction try connect
	if ($self->{_in_transaction} == 0) {
		# Try ping
		if (!$self->{_dbh}->ping()) {
			# Disconnect & reconnect
			$self->{_dbh}->disconnect();
			$self->connect();
		}
	}
}


## @member select($query)
# Return database selection results...
#
# @param query SQL query
#
# @return DBI statement handle object, undef on error
sub select
{
	my ($self,$query,@params) = @_;


	$self->_check();

#	# Build single query instead of using binding of params
#	# not all databases support binding, and not all support all
#	# the places we use ?
#	$query =~ s/\?/%s/g;
#	# Map each element in params to the quoted value
#	$query = sprintf($query,
#		map { $self->quote($_) } @params
#	);
#use Data::Dumper; print STDERR Dumper($query);
	# Prepare query
	my $sth;
	if (!($sth = $self->{_dbh}->prepare($query))) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	# Check for execution error
#	if (!$sth->execute()) {
	if (!$sth->execute(@params)) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	return $sth;
}


## @member do($command)
# Perform a command
#
# @param command Command to execute
#
# @return DBI statement handle object, undef on error
sub do
{
	my ($self,$command,@params) = @_;


	$self->_check();

#	# Build single command instead of using binding of params
#	# not all databases support binding, and not all support all
#	# the places we use ?
#	$command =~ s/\?/%s/g;
#	# Map each element in params to the quoted value
#	$command = sprintf($command,
#		map { $self->quote($_) } @params
#	);
#use Data::Dumper; print STDERR Dumper($command);

	# Prepare query
	my $sth;
#	if (!($sth = $self->{_dbh}->do($command))) {
	if (!($sth = $self->{_dbh}->do($command,undef,@params))) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	return $sth;
}


## @method lastInsertID($table,$column)
# Function to get last insert id
#
# @param table Table last entry was inserted into
# @param column Column we want the last value for
#
# @return Last inserted ID, undef on error
sub lastInsertID
{
	my ($self,$table,$column) = @_;


	# Get last insert id
	my $res;
	if (!($res = $self->{_dbh}->last_insert_id(undef,undef,$table,$column))) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	return $res;
}


## @method begin
# Function to begin a transaction
#
# @return 1 on success, undef on error
sub begin
{
	my $self = shift;


	$self->_check();

	$self->{_in_transaction}++;

	# Don't really start transaction if we more than 1 deep
	if ($self->{_in_transaction} > 1) {
		return 1;
	}

	# Begin
	my $res;
	if (!($res = $self->{_dbh}->begin_work())) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	return $res;
}


## @method commit
# Function to commit a transaction
#
# @return DBI layer result, or 1 on deep transaction commit
sub commit
{
	my $self = shift;


	# Reduce level
	$self->{_in_transaction}--;

	# If we not at top level, return success
	if ($self->{_in_transaction} > 0) {
		return 1;
	}

	# Reset transaction depth to 0
	$self->{_in_transaction} = 0;

	# Commit
	my $res;
	if (!($res = $self->{_dbh}->commit())) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	return $res;
}


## @method rollback
# Function to rollback a transaction
#
# @return DBI layer result or 1 on deep transaction
sub rollback
{
	my $self = shift;


	# If we at top level, return success
	if ($self->{_in_transaction} < 1) {
		return 1;
	}

	$self->{_in_transaction} = 0;

	# Rollback
	my $res;
	if (!($res = $self->{_dbh}->rollback())) {
		$self->{_error} = $self->{_dbh}->errstr;
		return undef;
	}

	return $res;
}


## @method quote($variable)
# Function to quote a database variable
#
# @param variable Variable to quote
#
# @return Quoted variable
sub quote
{
	my ($self,$variable) = @_;

	return $self->{_dbh}->quote($variable);
}


## @method free($sth)
# Function to cleanup DB query
#
# @param sth DBI statement handle
sub free
{
	my ($self,$sth) = @_;


	if ($sth) {
		$sth->finish();
	}
}


# Function to return the table prefix
sub table_prefix
{
	my $self = shift;

	return $self->{_table_prefix};
}




1;
# vim: ts=4
