#!/bin/bash
# Script that uploads the EHCP backup once completed via FTP or SCP
# Author:  Eric Arnol-Martin <earnolmartin@gmail.com>

###############
# Parameters  #
###############
# $1 = transfer method [ftp or scp]
# $2 = transfer method login
# $3 = tranfer method password
# $4 = transfer method host
# $5 = transfer method port
# $6 = openssl encryption password

if [ "$#" -ne 6 ]; then
    echo "Illegal number of parameters received: $#"
    exit
else
	if [ ! -z "$1" ]; then
		BACKMETHOD="$1"
	fi

	if [ ! -z "$2" ]; then
		LOGIN="$2"
	fi

	if [ ! -z "$3" ]; then
		PASS="$3"
	fi

	if [ ! -z "$4" ]; then
		HOST="$4"
	fi

	if [ ! -z "$5" ]; then
		PORT="$5"
	fi
	
	if [ ! -z "$6" ]; then
		ENCRYPT_PASS="$6"
	fi
fi


###############
# Functions   #
###############

function ftpCopy () {
	if [ ! -z "$1" ]; then
		UPLOADFILE="$1"
		FTPLOGFILE="FTP_UPLOAD_LOG"
		if [ -e /var/backup ]; then
			cd "/var/backup"
			if [ -e "$UPLOADFILE" ]; then
				Server="$HOST"
				LoginAs="$LOGIN"
				Password="$PASS"
				Port="$PORT"
				
				echo "Trying to upload backup file $UPLOADFILE to $HOST:$PORT via FTP!"
				
				ftp -inv -p "$Server" "$Port" > "$FTPLOGFILE" 2>&1 <<End-Of-Session
				user "$LoginAs" "$Password"
				binary
				put "$UPLOADFILE"
				bye	
End-Of-Session
				# Get upload status
				SUCCESSFTPUPLOAD=$(cat "$FTPLOGFILE" | grep "226 Transfer OK")
				if [ -z "$SUCCESSFTPUPLOAD" ]; then
					echo -e "Failed to upload and backup $UPLOADFILE to FTP SERVER $HOST! Check the FTP log file /var/backup/$FTPLOGFILE for details!\n"
					echo -e "Failed to upload and backup $UPLOADFILE to FTP SERVER $HOST! Check the FTP log file /var/backup/$FTPLOGFILE for details!" >> backup_log.conf
					logText=$(tail -n 20 "backup_log.conf")
					ftpLog=$(tail -n 20 "/var/backup/${FTPLOGFILE}")
					php /var/www/new/ehcp/scripts/ehcp_backup/sendTransferEmail.php "Backup Transfer Failed!" "<p>Failed to upload and backup ${UPLOADFILE} to FTP SERVER ${HOST}!</p><h3>Log Text</h3><pre>${logText}</pre><h3>FTP Log</h3><pre>${ftpLog}</pre>"
				else
					echo -e "Successfully uploaded and backed up $UPLOADFILE to FTP SERVER $HOST!\n"
					echo -e "Successfully uploaded and backed up $UPLOADFILE to FTP SERVER $HOST!" >> backup_log.conf
					php /var/www/new/ehcp/scripts/ehcp_backup/sendTransferEmail.php "Backup Transfer Succeeded!" "<p>Successfully transfered backup ${UPLOADFILE} to FTP SERVER ${HOST}!</p>"
				fi
			fi
		fi
	fi
}

function scpCopy () {
	if [ ! -z "$1" ]; then
		UPLOADFILE="$1"
		if [ -e /var/backup ]; then
			cd "/var/backup"
			if [ -e "$UPLOADFILE" ]; then
				Server="$HOST"
				LoginAs="$LOGIN"
				Password="$PASS"
				Port="$PORT"
				
				echo "Trying to scp backup file $UPLOADFILE to $HOST!"
				
				SSHPASSINSTALLED=$(which sshpass)
				if [ ! -z "$SSHPASSINSTALLED" ]; then
					sshpass -p "$Password" scp -o StrictHostKeyChecking=no -P "$Port" "$UPLOADFILE" "$LoginAs@$HOST:/home/$LoginAs/"
					if [ $? -ne 0 ]; then
						echo -e "Failed to upload and backup $UPLOADFILE to SCP SERVER $HOST! Error code returned from SSHPASS: $?\n"
						echo -e "Failed to upload and backup $UPLOADFILE to SCP SERVER $HOST! Error code returned from SSHPASS: $?" >> backup_log.conf
						logText=$(tail -n 20 "backup_log.conf")
						php /var/www/new/ehcp/scripts/ehcp_backup/sendTransferEmail.php "Backup Transfer Failed!" "<p>Failed to upload and backup ${UPLOADFILE} to SCP SERVER ${HOST}!</p><h3>Log Text</h3><pre>${logText}</pre>"
					else
						echo -e "Successfully uploaded and backed up $UPLOADFILE to SCP SERVER $HOST!\n"
						echo -e "Successfully uploaded and backed up $UPLOADFILE to SCP SERVER $HOST!" >> backup_log.conf
						php /var/www/new/ehcp/scripts/ehcp_backup/sendTransferEmail.php "Backup Transfer Succeeded!" "<p>Successfully transfered backup ${UPLOADFILE} to SCP SERVER ${HOST}!</p>"
					fi
				fi
			fi
		fi
	fi
}

function encryptFile(){
	# Encrypted file path
	ENCRYPTED_FILE_PATH="$lastBackupFilePath.enc"
	lastBackupFileRelPath="$lastBackupFileRelPath.enc"
	
	# Remove the encrypted file if it already exists
	if [ -e "$ENCRYPTED_FILE_PATH" ]; then
		rm -f "$ENCRYPTED_FILE_PATH"
	fi
	
	# Encrypt the file
	openssl enc -aes-256-cbc -salt -in "$lastBackupFilePath" -out "$ENCRYPTED_FILE_PATH" -k "$ENCRYPT_PASS"
}


###############
# ACTUAL CODE #
###############

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo -e "\nYou must run this script as root!"
	exit
fi

# Last backup information files
lastBKFilePath="/var/www/new/ehcp/scripts/ehcp_backup/last_ehcp_backup"

# Get the backup files
if [ -e "$lastBKFilePath" ]; then
	lastBackupFile=$(cat "$lastBKFilePath")
	lastBackupFilePath="/var/backup/${lastBackupFile}.tgz"
	lastBackupFileStatus="/var/backup/${lastBackupFile}_STATUS"
	lastBackupFileEncryptionStatus="/var/backup/${lastBackupFile}_STATUS_ENC"
	lastBackupFileRelPath="${lastBackupFile}.tgz"

	export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games"
	cd "/var/www/new/ehcp/scripts/ehcp_backup"
	
	echo -e "Checking to see if the EHCP daemon has finished backing up files!"

	# Is the backup job finished?
	EHCPDAEMONFINISHEDBackup=0
	while [ "$EHCPDAEMONFINISHEDBackup" -eq "0" ]
	do
		if [ -e "$lastBackupFileStatus" ]; then
			DONEBK=$(cat "$lastBackupFileStatus")
			if [ "$DONEBK" -eq "1" ]; then
				EHCPDAEMONFINISHEDBackup=1
			fi
		fi
		if [ "$EHCPDAEMONFINISHEDBackup" -eq "0" ]; then
			# Sleep if it's still 0
			sleep 2m
		fi
	done
	
	# Encrypt the backup file
	encryptFile

	if [ -e "$ENCRYPTED_FILE_PATH" ]; then
		if [ "$BACKMETHOD" == "ftp" ]; then
			ftpCopy "$lastBackupFileRelPath";
		elif [ "$BACKMETHOD" == "scp" ]; then
			scpCopy "$ENCRYPTED_FILE_PATH"
		fi
	else
		echo -e "Backup file does not exist!"	
	fi

else
	echo -e "\nNo backup has yet to be created!"
fi

echo -e "\nBackup operations have been completed!"
