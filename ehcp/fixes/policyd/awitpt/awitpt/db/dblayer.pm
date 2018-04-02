# Common database layer module
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



## @class awitpt::db::dblayer
# Database layer module which makes life a bit esier
package awitpt::db::dblayer;

use strict;
use warnings;


# Exporter stuff
require Exporter;
our (@ISA,@EXPORT);
@ISA = qw(Exporter);
@EXPORT = qw(
	DBConnect
	DBSelect
	DBDo
	DBLastInsertID
	DBBegin
	DBCommit
	DBRollback
	DBQuote
	DBFreeRes

	DBSelectNumResults
	DBSelectSearch

	hashifyLCtoMC
);

use POSIX qw( strftime );
use Date::Parse;

use awitpt::db::dbilayer;


# Database handle
my $dbh = undef;

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



## @fn setHandle($handle)
# Initialize database handle
#
# @param handle Set internal database handle we use
sub setHandle
{
		my $handle = shift;

		$dbh = $handle;
}


## @fn DBSelect($query,@params)
# Return database selection results...
#
# @param query Query to run
#
# @return DBI statement handle, undef on error
sub DBSelect
{
	my ($query,@params) = @_;

	my $table_prefix = $dbh->table_prefix();

	# Replace table prefix macro
	$query =~ s/\@TP\@/$table_prefix/g;

	# Prepare query
	my $sth;
	if (!($sth = $dbh->select($query,@params))) {
		setError("Error executing select: ".$dbh->Error());
		return undef;
	}

	return $sth;
}


## @fn DBDo($command)
# Perform a command
#
# @param command Command to run
#
# @return DBI statement handle, undef on error
sub DBDo
{
	my (@params) = @_;


	my $table_prefix = $dbh->table_prefix();

	# Check type of params we have
	if (ref($params[0]) eq 'HASH') {
		my $queryHash = $params[0];
		my $dbType = $dbh->type();

		# Check DB type is defined, if not use *
		if (defined($queryHash->{$dbType})) {
			@params = @{$queryHash->{$dbType}};
		} elsif (defined($queryHash->{'*'})) {
			@params = @{$queryHash->{'*'}};
		} else {
			setError("Error executing, database type in query not fund and no '*' query found");
			return undef;
		}
	}

	# Grab command and data
	my $command = shift(@params);
	my @data = @params;

	# Replace table prefix macro
	$command =~ s/\@TP\@/$table_prefix/g;

	# Prepare query
	my $sth;
	if (!($sth = $dbh->do($command,@data))) {
		setError("Error executing command '$command': ".$dbh->Error());
		return undef;
	}

	return $sth;
}


## @fn DBLastInsertID($table,$column)
# Function to get last insert id
#
# @param table Table to check
# @param column Column to get last insert on
#
# @return Last insert ID or undef on error
sub DBLastInsertID
{
	my ($table,$column) = @_;


	my $res;
	if (!($res = $dbh->lastInsertID(undef,undef,$table,$column))) {
		setError("Error getting last inserted id: ".$dbh->Error());
		return undef;
	}

	return $res;
}


## @fn DBBegin
# Function to begin a transaction
#
# @return 1 on success, undef on error
sub DBBegin
{
	my $res;
	if (!($res = $dbh->begin())) {
		setError("Error beginning transaction: ".$dbh->Error());
		return undef;
	}

	return $res;
}


## @fn DBCommit
# Function to commit a transaction
#
# @return 1 on success, undef on error
sub DBCommit
{
	my $res;
	if (!($res = $dbh->commit())) {
		setError("Error committing transaction: ".$dbh->Error());
		return undef;
	}

	return $res;
}


## @fn DBRollback
# Function to rollback a transaction
#
# @return 1 on success, undef on error
sub DBRollback
{
	my $res;
	if (!($res = $dbh->rollback())) {
		setError("Error rolling back transaction: ".$dbh->Error());
		return undef;
	}

	return $res;
}


## @fn DBQuote($variable)
# Function to quote a database variable
#
# @param variable Variable to quote
#
# @return Quoted variable
sub DBQuote
{
	my $variable = shift;


	return $dbh->quote($variable);
}


## @fn DBFreeRes($sth)
# Function to cleanup DB query
#
# @param sth Statement handle to cleanup
sub DBFreeRes
{
	my $sth = shift;


	if ($sth) {
		$sth->finish();
	}
}


# Function to get table prefix
sub DBTablePrefix
{
	return $dbh->table_prefix();
}


#
# Value Added Functions
#


## @fn DBSelectNumResults($query)
# Return how many results came up from the specific SELECT query
#
# @param query Query to perform, minus "SELECT COUNT(*) AS num_results"
#
# @return Number of results, undef on error
sub DBSelectNumResults
{
	my $query = shift;


	# Prepare query
	my $sth;
	if (!($sth = $dbh->select("SELECT COUNT(*) AS num_results $query"))) {
		setError("Error executing select: ".$dbh->Error());
		return undef;
	}

	# Grab row
	my $row = $sth->fetchrow_hashref();
	if (!defined($row)) {
		setError("Failed to get results from a select: ".$dbh->Error());
		return undef;
	}

	# Pull number
	my $num_results = $row->{'num_results'};
	$sth->finish();

	return $num_results;
}


## @fn DBSelectSearch($query,$search,$filters,$sorts)
# Select results from database and return the total number aswell
#
# @param query Base query
#
# @param search Search hash ref
# @li Filter - Filter based on this...
# [filter] => Array (
#	[0] => Array (
#		[field] => Name
#		[data] => Array (
#			[type] => string
#			[value] => hi there
#		)
#	)
# )
# { 'data' => { 'comparison' => 'gt', 'value' => '5', 'type' => 'numeric' }, 'field' => 'ID' }
# @li Start - Start item number, indexed from 0 onwards
# @li Limit - Limit number of results
# @li Sort - Sort by this item
# @li SortDirection - Sort in this direction, either ASC or DESC
#
# @param filters Filter hash ref
# Hash:  'Column' -> 'Table.DBColumn'
#
# @param sorts Hash ref of valid sort criteria, indexed by what we get, pointing to the DB column in the query
# Hash:  'Column' -> 'Table.DBColumn'
#
# @return Number of results, undef on error
sub DBSelectSearch
{
	my ($query,$search,$filters,$sorts) = @_;


	# Stuff we need to add to the SQL query
	my @where; # Where clauses
	my $sqlWhere = "";
	my $sqlLimit = "";
	my $sqlOffset = "";
	my $sqlOrderBy = "";
	my $sqlOrderByDirection = "";

	# Check if we're searching
	if (defined($search) && ref($search)) {
		# Check it is a hash
		if (ref($search) ne "HASH") {
			setError("Parameter 'search' is not a hashtable");
			return (undef,-1);
		}
		# Check if we need to filter
		if (defined($search->{'Filter'})) {
			# We need filters in order to use filtering
			if (!defined($filters)) {
				setError("Parameter 'search' element 'Filter' requires 'filters' to be defined");
				return (undef,-1);
			}

			# Check type of Filter
			if (ref($search->{'Filter'}) ne "ARRAY") {
				setError("Parameter 'search' element 'Filter' is of invalid type, it must be an ARRAY'");
				return (undef,-1);
			}

			# Loop with filters
			foreach my $item (@{$search->{'Filter'}}) {
				my $data = $item->{'data'};  # value, type, comparison
				my $field = $item->{'field'};
				my $column = $filters->{$field};

				# Check if field is in our allowed filters
				if (!defined($filters->{$field})) {
					setError("Parameter 'search' element 'Filter' has invalid field item '$field' according to 'filters'");
					return (undef,-1);
				}
				# Check data
				if (!defined($data->{'type'})) {
					setError("Parameter 'search' element 'Filter' requires field data element 'type' for field '$field'");
					return (undef,-1);
				}
				if (!defined($data->{'value'})) {
					setError("Parameter 'search' element 'Filter' requires field data element 'value' for field '$field'");
					return (undef,-1);
				}

				# match =, LIKE, IN (
				# matchEnd '' or )
				my $match;
				my $matchEnd = "";
				# value is the DBQuote()'d value
				my $value;

				# Check what type of comparison
				if ($data->{'type'} eq "boolean") {
					$match = '=';
					$value = DBQuote($data->{'value'});


				} elsif ($data->{'type'} eq "date") {

					# The comparison type must be defined
					if (!defined($data->{'comparison'})) {
						setError("Parameter 'search' element 'Filter' requires field data element 'comparison' for date field '$field'");
						return (undef,-1);
					}

					# Check comparison type
					if ($data->{'comparison'} eq "gt") {
						$match = ">";

					} elsif ($data->{'comparison'} eq "lt") {
						$match = "<";

					} elsif ($data->{'comparison'} eq "eq") {
						$match = "=";

					} elsif ($data->{'comparison'} eq "ge") {
						$match = ">=";

					} elsif ($data->{'comparison'} eq "le") {
						$match = "<=";
					}
					# Convert to ISO format
					my $unixtime = str2time($data->{'value'});
					my @d = localtime($unixtime);
					$value = DBQuote(sprintf("%4d-%02d-%02d", $d[5]+1900, $d[4]+1, $d[3]));


				} elsif ($data->{'type'} eq "list") {
					# Quote all values
					my @valueList;
					foreach my $i (split(/,/,$data->{'value'})) {
						push(@valueList,DBQuote($i));
					}

					$match = "IN (";
					# Join up 'xx','yy','zz'
					$value = join(',',@valueList);
					$matchEnd = ")";


				} elsif ($data->{'type'} eq "numeric") {

					# The comparison type must be defined
					if (!defined($data->{'comparison'})) {
						setError("Parameter 'search' element 'Filter' requires field data element 'comparison' for numeric field '$field'");
						return (undef,-1);
					}

					# Check comparison type
					if ($data->{'comparison'} eq "gt") {
						$match = ">";

					} elsif ($data->{'comparison'} eq "lt") {
						$match = "<";

					} elsif ($data->{'comparison'} eq "eq") {
						$match = "=";

					} elsif ($data->{'comparison'} eq "ge") {
						$match = ">=";

					} elsif ($data->{'comparison'} eq "le") {
						$match = "<=";
					}

					$value = DBQuote($data->{'value'});


				} elsif ($data->{'type'} eq "string") {
					$match = "LIKE";
					$value = DBQuote("%".$data->{'value'}."%");

				}

				# Add to list
				push(@where,"$column $match $value $matchEnd");
			}

			# Check if we have any WHERE clauses to add ...
			if (@where > 0) {
				# Check if we have WHERE clauses in the query
				if ($query =~ /\sWHERE\s/i) {
					# If so start off with AND
					$sqlWhere .= "AND ";
				} else {
					$sqlWhere = "WHERE ";
				}
				$sqlWhere .= join(" AND ",@where);
			}
		}

		# Check if we starting at an OFFSET
		if (defined($search->{'Start'})) {
			# Check if Start is valid
			if ($search->{'Start'} < 0) {
				setError("Parameter 'search' element 'Start' invalid value '".$search->{'Start'}."'");
				return (undef,-1);
			}

			$sqlOffset = sprintf("OFFSET %i",$search->{'Start'});
		}

		# Check if results will be LIMIT'd
		if (defined($search->{'Limit'})) {
			# Check if Limit is valid
			if ($search->{'Limit'} < 1) {
				setError("Parameter 'search' element 'Limit' invalid value '".$search->{'Limit'}."'");
				return (undef,-1);
			}

			$sqlLimit = sprintf("LIMIT %i",$search->{'Limit'});
		}

		# Check if we going to be sorting
		if (defined($search->{'Sort'})) {
			# We need sorts in order to use sorting
			if (!defined($sorts)) {
				setError("Parameter 'search' element 'Filter' requires 'filters' to be defined");
				return (undef,-1);
			}

			# Check if sort is defined
			if (!defined($sorts->{$search->{'Sort'}})) {
				setError("Parameter 'search' element 'Sort' invalid item '".$search->{'Sort'}."' according to 'sorts'");
				return (undef,-1);
			}

			# Build ORDER By
			$sqlOrderBy = "ORDER BY ".$sorts->{$search->{'Sort'}};

			# Check for sort ORDER
			if (defined($search->{'SortDirection'})) {

				# Check for valid directions
				if (lc($search->{'SortDirection'}) eq "asc") {
					$sqlOrderByDirection = "ASC";

				} elsif (lc($search->{'SortDirection'}) eq "desc") {
					$sqlOrderByDirection = "DESC";

				} else {
					setError("Parameter 'search' element 'SortDirection' invalid value '".$search->{'SortDirection'}."'");
					return (undef,-1);
				}
			}
		}
	}

	# Select row count, pull out   "SELECT .... "  as we replace this in the NumResults query
	(my $queryCount = $query) =~ s/^\s*SELECT\s.*\sFROM/FROM/is;
	my $numResults = DBSelectNumResults("$queryCount $sqlWhere");
	if (!defined($numResults)) {
		return undef;
	}

	# Add Start, Limit, Sort, Direction
	my $sth = DBSelect("$query $sqlWhere $sqlOrderBy $sqlOrderByDirection $sqlLimit $sqlOffset");
	if (!defined($sth)) {
		return undef;
	}

	return ($sth,$numResults);
}


# Convert a lower case array to mixed case
sub hashifyLCtoMC
{
	my ($record,@entries) = @_;


	# If we undefined, return
	return undef if (!defined($record));

	my $res;

	# Loop with each item, assign from lowecase database record to our result
	foreach my $entry (@entries) {
		$res->{$entry} = $record->{lc($entry)};
	}

	return $res;
}




1;
# vim: ts=4
