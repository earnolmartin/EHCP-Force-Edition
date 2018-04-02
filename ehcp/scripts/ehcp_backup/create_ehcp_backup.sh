#!/bin/bash
# Creates a backup of all web contents using EHCP API
# Author:  Eric Arnol-Martin <earnolmartin@gmail.com>

# Parameters
# $1 is the backup filename

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo -e "\nYou must run this script as root!"
	exit
fi

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games"
cd "/var/www/new/ehcp/scripts/ehcp_backup"

if [ ! -e "backup_log.conf" ]; then
	touch backup_log.conf
fi

# Get the last backup that was made and delete it from the system
if [ -e "last_ehcp_backup" ]; then
	oldBackup=$(cat "last_ehcp_backup")
	oldBackupTGZ="/var/backup/$oldBackup.tgz"
	oldBackupENC="/var/backup/$oldBackup.tgz.enc"
	
	if [ -e "$oldBackupTGZ" ]; then
		# Let's delete it
		rm -rf "$oldBackupTGZ"
	fi
	
	if [ -e "$oldBackupENC" ]; then
		# Let's delete it
		rm -rf "$oldBackupENC"
	fi
fi

# Restart EHCP Daemon incase it's sleeping :|
service ehcp restart

# Generate backup file name
DATEOFBACKUP=$(date +"%m_%d_%Y_%H%M%S")
FNAME=${DATEOFBACKUP}_ehcp_backup
if [ ! -z "$1" ]; then
	FNAME="$1_$FNAME"
fi

echo -e "\nStarting EHCP Backup on $DATEOFBACKUP with the filename of $FNAME\n"

# Save backup filename into a file to read later so we know where to FTP it.
echo -e "$FNAME" > last_ehcp_backup

# Make sure the backup file does not yet exit
if [ ! -e "/var/backup/$FNAME" ]; then
	php createBackup.php "$FNAME"
	echo -e "Asked EHCP Daemon to Create Backup File $FNAME!" >> backup_log.conf
fi




