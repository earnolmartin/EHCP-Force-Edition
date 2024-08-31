#!/bin/bash
#@author:  Eric Arnol-Martin (earnolmartin@gmail.com) http://eamster.tk
#Desc:  Bash script that configures anonymous ftp access or removes anonymous ftp access (https://ehcpforce.ezpz.cc)

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
# $1 should be the directory to configure anonymous ftp access
# If $1 is not passed in, we should remove anonymous ftp access
VSFTPDConf="/etc/vsftpd.conf"

# Check for root
rootCheck

if [ ! -z "$1" ]; then
	# Configure anonymous access
	
	# Set up the directory with proper perms
	DIRForAnonAccess="$1"
	if [ ! -e "$DIRForAnonAccess" ]; then
		mkdir -p "$DIRForAnonAccess"
	fi
	chown root:root "$DIRForAnonAccess"
	
	# Setup the vsftpd.conf config
	if [ -e "$VSFTPDConf" ]; then
		anonEnableLineExists=$(cat "$VSFTPDConf" | grep "anonymous_enable")
		anonRootLineExists=$(cat "$VSFTPDConf" | grep "anon_root")
		anonMKDIRLineExists=$(cat "$VSFTPDConf" | grep "anon_mkdir_write_enable")
		anonUploadEnableLineExists=$(cat "$VSFTPDConf" | grep "anon_upload_enable")
		
		if [ ! -z "$anonEnableLineExists" ]; then
			sed -i "s/anonymous_enable=.*/anonymous_enable=YES/g" "$VSFTPDConf"
		else
			echo -e "anonymous_enable=YES" >> "$VSFTPDConf"
		fi
		if [ ! -z "$anonRootLineExists" ]; then
			sed -i "s#anon_root=.*#anon_root=$DIRForAnonAccess#g" "$VSFTPDConf"
		else
			echo -e "anon_root=$DIRForAnonAccess" >> "$VSFTPDConf"
		fi
		if [ ! -z "$anonMKDIRLineExists" ]; then
			sed -i "s/anon_mkdir_write_enable=.*/anon_mkdir_write_enable=NO/g" "$VSFTPDConf"
		else
			echo -e "anon_mkdir_write_enable=NO" >> "$VSFTPDConf"
		fi		
		if [ ! -z "$anonUploadEnableLineExists" ]; then
			sed -i "s/anon_upload_enable=.*/anon_upload_enable=NO/g" "$VSFTPDConf"
		else
			echo -e "anon_upload_enable=NO" >> "$VSFTPDConf"
		fi
	fi
else
	# Disable anonymous access
	anonEnableLineExists=$(cat "$VSFTPDConf" | grep "anonymous_enable")
	if [ ! -z "$anonEnableLineExists" ]; then
		sed -i "s/anonymous_enable=.*/anonymous_enable=NO/g" "$VSFTPDConf"
	else
		echo -e "anonymous_enable=NO" >> "$VSFTPDConf"
	fi
fi
service vsftpd restart
