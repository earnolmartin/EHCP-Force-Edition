<?php
# Database functions
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

require_once('includes/config.php');


# Connect to DB
function connect_db()
{
	global $DB_DSN;
	global $DB_USER;
	global $DB_PASS;

	try {
		$dbh = new PDO($DB_DSN, $DB_USER, $DB_PASS, array(
			PDO::ATTR_PERSISTENT => false
		));

		$dbh->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);

	} catch (PDOException $e) {
		die("Error connecting to Policyd v2 DB: " . $e->getMessage());
	}

	return $dbh;
}


# Connect to postfix DB
function connect_postfix_db()
{
	global $DB_POSTFIX_DSN;
	global $DB_POSTFIX_USER;
	global $DB_POSTFIX_PASS;

	try {
		$dbh = new PDO($DB_POSTFIX_DSN, $DB_POSTFIX_USER, $DB_POSTFIX_PASS, array(
			PDO::ATTR_PERSISTENT => false
		));

		$dbh->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);

	} catch (PDOException $e) {
		die("Error connecting to Postfix DB: " . $e->getMessage());
	}

	return $dbh;
}


# vim: ts=4
?>
