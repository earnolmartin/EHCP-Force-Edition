#!/bin/bash
#@author:  Eric Arnol-Martin (earnolmartin@gmail.com) http://eamster.tk
#Desc:  Rewrites error_page.html used by nginx to EHCP Force Default due to bugs in ehcp found around 2012 that caused this file to become corrupt with nonsense data (http://ehcpforce.tk)
#This script should only be run if you discover 404 error pages with garbage data from when the file was first created for the domain since this file is only added once when the domain is created... 
#if you've been using EHCP that long.

####################
#    FUNCTIONS     #
####################
function rootCheck(){
	# Check to make sure the script is running as root
	if [ "$(id -u)" != "0" ]; then
		echo "This script must be run as root" 1>&2
		exit 1
	fi
}

################
#   MAIN APP   #
################

# Check for root
rootCheck

if [ -e "/var/www/vhosts" ]; then
	# Find phptmpdir folders and delete old session files older than 5 days to keep the system clean
	folderPaths=$(find "/var/www/vhosts" -type d -name "httpdocs")
	for findFolder in $folderPaths; do
		if [ -e "$findFolder/error_page.html" ]; then
			echo -e "$findFolder/error_page.html exists!"
			hasEHCPForce=$(cat "$findFolder/error_page.html" | grep "ehcpforce.tk")
			if [ -z "$hasEHCPForce" ]; then
				if [ -e "/var/www/new/ehcp/error_page.html" ]; then
					echo -e "Did not detect ehcpforce.tk in $findFolder/error_page.html! Replacing with updated copy...\n"
					cp "/var/www/new/ehcp/error_page.html" "$findFolder/error_page.html"
				fi
			fi
		fi
	done
fi
