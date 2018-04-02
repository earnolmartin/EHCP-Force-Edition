<?php
# Main index file
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

include_once("includes/header.php");
include_once("includes/footer.php");


printHeader();

?>
	<p class="pageheader">Features Supported</p>
	<ul>
		<li>Protocols
			<ul>
				<li>Bizanga 
					<a title="Help on Bizanga protocol" href="http://www.policyd.org/tiki-index.php?page=Bizanga&structure=Documentation" class="help">
						<img src="images/help.gif" alt="Help" />
					</a>
				</li>
				<li>Postfix
					<a title="Help on Postfix protocol" href="http://www.policyd.org/tiki-index.php?page=Postfix&structure=Documentation" class="help">
						<img src="images/help.gif" alt="Help" />
					</a>
				</li>
			</ul>
		</li>

		<li>Policies &amp; Policy Groups
			<a title="Help on policies and groups" href="http://www.policyd.org/tiki-index.php?page=Policies%20%26%20Groups&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Define policy groups made up of various combinations of tags.</li>
				<li>Define and manage policies comprising of ACL's which can include groups.</li>
			</ul>
		</li>

		<li>Access Control
			<a title="Help on access control" href="http://www.policyd.org/tiki-index.php?page=AccessControl&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Control access based on policy. eg. Rejecting mail matching a specific policy.</li>
			</ul>
		</li>

		<li>Amavis Integration
			<a title="Help on Amavis integration" href="http://www.policyd.org/tiki-index.php?page=Amavis&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Anti-virus checks.</li>
				<li>Anti-spam checks.</li>
				<li>Banned filename checks.</li>
				<li>Email header checks.</li>
				<li>Message size limits.</li>
				<li>Blacklist/whitelist senders.</li>
				<li>Email interception (BCC).</li>
			</ul>
		</li>

		<li>Greylisting
			<a title="Help on greylisting" href="http://www.policyd.org/tiki-index.php?page=Greylisting&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Support for greylisting and masking sender IP addresses.</li>
				<li>Support for auto-whitelisting and auto-greylisting based on count or count+percentage.</li>
			</ul>
		</li>

		<li>HELO/EHLO Checks
			<a title="Help on HELO/EHLO checks" href="http://www.policyd.org/tiki-index.php?page=CheckHelo&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>HELO/EHLO randomization prevention</li>
				<li>Blacklisting of HELO/EHLO's ... those used by your own servers</li>
				<li>Whitelisting of CIDR's which are known to be braindead</li>
				<li>Check sending server HELO/EHLO for validity and RFC compliance.</li>
			</ul>
		</li>

		<li>SPF Checks
			<a title="Help on SPF checks" href="http://www.policyd.org/tiki-index.php?page=CheckSPF&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Check the SPF records of a domain and see if the inbound email is allowed or prohibited.</li>
			</ul>
		</li>

		<li>Quotas
			<a title="Help on quotas" href="http://www.policyd.org/tiki-index.php?page=Quotas&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Define message count quotas for policies.</li>
				<li>Define cumulative size quotas for policies.</li>
				<li>Track these quotas based on various methods, including sender IP block, sender user/domain/email address.</li>
			</ul>
		</li>

		<li>Accounting
			<a title="Help on accounting" href="http://www.policyd.org/tiki-index.php?page=Accounting&structure=Documentation" class="help">
				<img src="images/help.gif" alt="Help" />
			</a>
			<ul>
				<li>Message count and cumulative size accounting.</li>
				<li>Message count and cumulative size limits per accounting period.</li>
				<li>Daily, weekly and monthly accounting periods.</li>
			</ul>
		</li>

		<li>Postfix Integration
			<ul>
				<li>Setup and create transports.</li>
				<li>Create mailboxes.</li>
				<li>Create mailbox aliases.</li>
				<li>Manage distribution groups.</li>
			</ul>
		</li>

	</ul>
<?php

printFooter();

# vim: ts=4
?>
