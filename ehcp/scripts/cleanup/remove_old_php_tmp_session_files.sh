#!/bin/bash
#@author:  Eric Arnol-Martin (earnolmartin@gmail.com) http://eamster.tk
#Desc:  Bash script that cleans up old PHP session files based on the settings used for EHCP Force Edition (https://ehcpforce.ezpz.cc)

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

function deleteOldSessionFiles(){
	# $1 is the path
	# $2 is the type
	# $3 is the time
	if [ ! -z "$1" ] && [ ! -z "$2" ] && [ ! -z "$3" ]; then
		if [ "$2" == "days" ];  then
			echo -e "-----------------------------------------------------------------------------"
			echo -e "Scanning folder \"${1}\" for php session files older than ${3} days!"
			echo -e "-----------------------------------------------------------------------------"
			echo -e "Found the following session files within ${1} which are old:"
			find "${1}" -type f -name "sess_*" -mtime +"${3}" -printf "%f\n" -exec rm '{}' \;
			echo -e ""	
		elif [ "$2" == "minutes" ]; then
			echo -e "-----------------------------------------------------------------------------"
			echo -e "Scanning folder \"${1}\" for php session files last accessed over ${3} minutes ago!"
			echo -e "-----------------------------------------------------------------------------"
			echo -e "Found the following session files within ${1} which are old:"
			find "${1}" -type f -name "sess_*" -amin +"${3}" -printf "%f\n" -exec rm '{}' \;
			echo -e ""
		fi
		
	fi
}

function pruneFiveDays(){
	if [ -e "/var/www/vhosts" ]; then
		# Find phptmpdir folders and delete old session files older than 5 days to keep the system clean
		folderPaths=$(find "/var/www/vhosts" -type d -name "phptmpdir")
		
		for tmpdirFolder in $folderPaths; do
			deleteOldSessionFiles "$tmpdirFolder" "days" "5"
		done
		
		for tmpdirFolder in $additionalFolderPaths; do
			deleteOldSessionFiles "$tmpdirFolder" "days" "5"
		done
	fi
}

function pruneLastAccessOverMinutesSpecified(){
	if [ ! -z "$1" ]; then
		if [ -e "/var/www/vhosts" ]; then
			# Find phptmpdir folders and delete old session files to keep the system clean
			folderPaths=$(find "/var/www/vhosts" -type d -name "phptmpdir")
			
			for tmpdirFolder in $folderPaths; do
				deleteOldSessionFiles "$tmpdirFolder" "minutes" "${1}"
			done
			
			for tmpdirFolder in $additionalFolderPaths; do
				deleteOldSessionFiles "$tmpdirFolder" "minutes" "${1}"
			done
		fi
	fi
}

################
#   MAIN APP   #
################

# Check for root
rootCheck

additionalFolderPaths=()
additionalFolderPaths+=('/var/www/php_sessions')

if [ -z "$1" ]; then
	# Find and remove session files last modified over 5 days ago
	pruneFiveDays
else
	pruneLastAccessOverMinutesSpecified "$1"
fi
