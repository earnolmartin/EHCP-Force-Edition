this modify done by sandu@lungu.info into ehcp.thanks.


AFFECTED STUFF
==============

Patched files*:
	etc/apache2/default
	webmail/config/config.php
	classapp.php
	install_lib.php

New files:
	.htaccess
	config/.htaccess
	etc/.htaccess
	languages/.htaccess
	multicms/.htaccess
	
New directories:
	z7/*
	templates/z7/*
	misc/templates/z7/*
	
Updated 3rd party apps:
	webmail (SquirrelMail - updated to last version)
	sysinfo (phpSysInfo - pathed, cleaned up)
	net2ftp (cleaned up)
	
Patched lines were marked with "z7 mod"	comments

SUMMARY
=======

1. Modified the xp5 theme and made it more consistent. The new z7 theme is now W3C valid, table-less CSS based. I also played with the links in the side-panel and I think it's now less messy.

2. Added a phpinfo() page - I use this function all the time :).

3. Hidden multilangual features - these are not ready yet, anyway :( Same with the banner - it's ugly and not very welcome on a production site (to devels. - you should consider context and text ads and you should use the same fonts/colors so that they do not drive so much attention).

4. Improved security by activating support of and adding .htaccess files. Helps to preventing access and execution of EHCP core files. This is also necessary for proper mod_rewrite operation.

5. Changed Apache webroot from /var/www to /var/www/vhosts as the default htdocs, 'cause I see no reason for 'passivedomains' folder and anything else in there to be available for everyone.

6. Added some redirects (index.php files).

7. Made a few minor bug fixes. Added login checks to the php files being called directly.

8. Updated SquirrelMail to the latest version.

9. Delete skin and language files I don't need.
