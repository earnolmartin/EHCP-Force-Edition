#!/bin/bash
# Uses certbot to update certificates and reloads nginx / apache2 config
# By Eric Arnol-Martin <earnolmartin@gmail.com>

###############
#  FUNCTIONS  #
###############

function rootCheck(){
	# Check to make sure the script is running as root
	if [ "$(id -u)" != "0" ]; then
		echo "This script must be run as root" 1>&2
		exit 1
	fi
}

function detectRunningWebServer(){
	nginxRunning=$(ps -ef | grep -v grep | grep nginx)
	apache2Running=$(ps -ef | grep -v grep | grep apache2)
}


################
#   MAIN APP   #
################

# Check for root
rootCheck

# Update certificates
if [ -e "/usr/local/bin/certbot" ]; then
	/usr/local/bin/certbot renew --quiet

	# Reload appropriate service to update any certificates that may have been updated
	if [ ! -z "$nginxRunning" ]; then
		service nginx reload
	fi

	if [ ! -z "$apache2Running" ]; then
		service apache2 reload
	fi
fi

exit
