#!/bin/bash
#@author:  Eric Arnol-Martin (earnolmartin@gmail.com) http://eamster.tk
#Desc:  Bash script that cleans up old PHP session files based on the settings used for EHCP Force Edition (http://ehcpforce.tk)

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

function pruneFiveDays(){
	if [ -e "/var/www/vhosts" ]; then
		# Find phptmpdir folders and delete old session files older than 5 days to keep the system clean
		folderPaths=$(find "/var/www/vhosts" -type d -name "phptmpdir")
		for tmpdirFolder in $folderPaths; do
			echo -e "-----------------------------------------------------------------------------"
			echo -e "Scanning folder \"$tmpdirFolder\" for php session files older than five days!"
			echo -e "-----------------------------------------------------------------------------"
			echo -e "Found the following session files within $tmpdirFolder which are old:"
			find "$tmpdirFolder" -type f -name "sess_*" -mtime +5 -printf "%f\n" -exec rm '{}' \;
			echo -e ""
		done
	fi
}

function pruneLastAccessOverMinutesSpecified(){
	if [ ! -z "$1" ]; then
		if [ -e "/var/www/vhosts" ]; then
			# Find phptmpdir folders and delete old session files to keep the system clean
			folderPaths=$(find "/var/www/vhosts" -type d -name "phptmpdir")
			for tmpdirFolder in $folderPaths; do
				echo -e "-----------------------------------------------------------------------------"
				echo -e "Scanning folder \"$tmpdirFolder\" for php session files last accessed over $1 minutes ago!"
				echo -e "-----------------------------------------------------------------------------"
				echo -e "Found the following session files within $tmpdirFolder which are old:"
				find "$tmpdirFolder" -type f -name "sess_*" -amin +"$1" -printf "%f\n" -exec rm '{}' \;
				echo -e ""
			done
		fi
	fi
}

################
#   MAIN APP   #
################

# Check for root
rootCheck

if [ -z "$1" ]; then
	# Find and remove session files last modified over 5 days ago
	pruneFiveDays
else
	pruneLastAccessOverMinutesSpecified "$1"
fi
