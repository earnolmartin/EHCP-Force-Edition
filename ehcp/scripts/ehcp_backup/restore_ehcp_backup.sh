#!/bin/bash
# Restores an EHCP backup of all web contents using the EHCP API
# Author:  Eric Arnol-Martin <earnolmartin@gmail.com>

# RESAVE THIS FILE WITH ANOTHER NAME (and use that copy of the script) SO THAT EHCP UPDATES DON'T LOSE YOUR SETTINGS #
# RESAVE THIS FILE WITH ANOTHER NAME (and use that copy of the script) SO THAT EHCP UPDATES DON'T LOSE YOUR SETTINGS #
# RESAVE THIS FILE WITH ANOTHER NAME (and use that copy of the script) SO THAT EHCP UPDATES DON'T LOSE YOUR SETTINGS #
# RESAVE THIS FILE WITH ANOTHER NAME (and use that copy of the script) SO THAT EHCP UPDATES DON'T LOSE YOUR SETTINGS #
# RESAVE THIS FILE WITH ANOTHER NAME (and use that copy of the script) SO THAT EHCP UPDATES DON'T LOSE YOUR SETTINGS #
# RESAVE THIS FILE WITH ANOTHER NAME (and use that copy of the script) SO THAT EHCP UPDATES DON'T LOSE YOUR SETTINGS #

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo -e "\nYou must run this script as root!"
	exit
fi

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games"

##################
# Variables      #
##################

# Configurable vars (ADJUST THESE VARIABLES HERE)
backupEncFilesDir="/var/backup" # Use the directory where your uploaded backup files exist on the local server you're restoring a backup on
backupWildCardName="*testvirtualbox1604*.enc" # Replace testvirtualbox1604 with your backup name - leave the * for pattern matching
encryptionKey='testy!!!!TY!'; # enter your real encryption key here... I don't actually use this key :)

# Fixed vars
DATEOFBACKUP=$(date +"%m_%d_%Y_%H%M%S")

##################
# Functions      #
##################

function getLatestBackupToRestore(){
	if [ ! -z "$backupEncFilesDir" ] && [ -e "$backupEncFilesDir" ] && [ ! -z "$backupWildCardName" ]; then

		# Get latest enc file (https://superuser.com/questions/294161/unix-linux-find-and-sort-by-date-modified)
		fullBkEncPath=$(find "$backupEncFilesDir" -name "${backupWildCardName}*" -printf "%T@ %Tc %p\n" | sort -rn | head -1 | awk '{print $9}')
		
		if [ ! -z "$fullBkEncPath" ]; then
			echo -e "\nStarting EHCP Restore on $DATEOFBACKUP using the encrytped backup file of $fullBkEncPath\n"
			decryptBackupFile "$fullBkEncPath"
		fi
	fi
}

function decryptBackupFile(){
	if [ ! -z "$1" ] && [ -e "$1" ]; then
		backupFileName=$(echo "$1" | grep -o "[^${backupEncFilesDir}].*" | grep -o "[^/].*")
		backupFileNameWithTGZ=$(echo ${backupFileName: : -4})
		if [ ! -z "$backupFileNameWithTGZ" ]; then
			openssl enc -aes-256-cbc -d -in "$1" -out "/var/backup/${backupFileNameWithTGZ}" -k "${encryptionKey}"
			doEHCPRestore "${backupFileNameWithTGZ}"
		fi
	fi
}

function doEHCPRestore(){
	if [ ! -z "$1" ] && [ -e "$1" ]; then
		echo -e "Asked EHCP Daemon to Restore Backup File ${1} on ${DATEOFBACKUP}!" >> restore_log.conf
		php restoreBackup.php "$1"
	fi
}

##################
# Code           #
##################
cd "/var/www/new/ehcp/scripts/ehcp_backup"
if [ ! -e "restore_log.conf" ]; then
	touch restore_log.conf
fi

# Find the backup file to restore, unecrypt it, and then tell the EHCP daemon to restore it based on variable settings at the beginning of the file
getLatestBackupToRestore

