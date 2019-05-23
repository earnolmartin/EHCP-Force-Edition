#!/bin/bash
# Installs and configures DKIM for all domains hosted on the server
# Main domain is specified to act as the signee / configure the public key DNS setting
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

function aptgetInstall(){
	# Parameter $1 is a list of programs to install
	# Parameter $2 is used to specify runlevel 1 in front of the command to prevent daemons from automatically starting (needed for amavisd-new)

	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get install for:$1"
		return
	fi

	# first, try to install without any prompt, then if anything goes wrong, normal install..
	cmd="apt-get -qq -y --no-remove --allow-unauthenticated install $1"
	
	if [ ! -z "$2" ]; then
		cmd="RUNLEVEL=1 $cmd"
	fi
	
	# Run the command
	sh -c "DEBIAN_FRONTEND=noninteractive ${cmd} < /dev/null > /dev/null" > /dev/null 2>&1 
	
	if [ $? -ne 0 ]; then
		cmd="apt-get -qq -y --allow-unauthenticated install $1"
		if [ ! -z "$2" ]; then
			cmd="RUNLEVEL=1 $cmd"
		fi
		sh -c "DEBIAN_FRONTEND=noninteractive ${cmd} < /dev/null > /dev/null" > /dev/null 2>&1 	
	fi
	
	PackageFailed="$?"

}

function setupDKIMConfig(){
	echo "AutoRestart             Yes
AutoRestartRate         10/1h
UMask                   002
Syslog                  yes
SyslogSuccess           Yes
LogWhy                  Yes

Canonicalization        relaxed/simple

ExternalIgnoreList      refile:/etc/opendkim/TrustedHosts
InternalHosts           refile:/etc/opendkim/TrustedHosts
KeyTable                refile:/etc/opendkim/KeyTable
SigningTable            refile:/etc/opendkim/SigningTable

Mode                    sv
PidFile                 /var/run/opendkim/opendkim.pid
SignatureAlgorithm      rsa-sha256

UserID                  opendkim:opendkim

Socket                  inet:12301@localhost" >> "/etc/opendkim.conf"

	if [ -e "/etc/default/opendkim" ]; then
		hasSocketLine=$(cat "/etc/default/opendkim" | grep -o "^SOCKET=")
		if [ -z "$hasSocketLine" ]; then
			echo -e 'SOCKET="inet:12301@localhost"' >> "/etc/default/opendkim"
		else
			sed -i "s#^SOCKET=.*#SOCKET=\"inet:12301@localhost\"#g" "/etc/default/opendkim"
		fi
	fi
	
	##########################
	# Adjust postfix settings#
	##########################
	# Make a backup first
	cp "/etc/postfix/main.cf" "/etc/postfix/main_before_dkim_addition.cf_${CurDate}"
				
	hasMilterLine=$(cat "/etc/postfix/main.cf" | grep -o "^milter_protocol")
	if [ -z "$hasMilterLine" ]; then
		echo -e 'milter_protocol = 2' >> "/etc/postfix/main.cf"
	else
		sed -i "s#^milter_protocol.*#milter_protocol = 2#g" "/etc/postfix/main.cf"
	fi
		
	hasMilterDefaultActionLine=$(cat "/etc/postfix/main.cf" | grep -o "^milter_default_action")
	if [ -z "$hasMilterDefaultActionLine" ]; then
		echo -e 'milter_default_action = accept' >> "/etc/postfix/main.cf"
	else
		sed -i "s#^milter_default_action.*#milter_default_action = accept#g" "/etc/postfix/main.cf"
	fi
		
	hasMilterSMTPD=$(cat "/etc/postfix/main.cf" | grep -o "^smtpd_milters")
	if [ -z "$hasMilterSMTPD" ]; then
		echo -e 'smtpd_milters = inet:localhost:12301' >> "/etc/postfix/main.cf"
	else
		hasINETInIt=$(cat "/etc/postfix/main.cf" | grep -o "smtpd_milters.*" | grep -o "inet:localhost:12301")
		if [ -z "$hasINETInIt" ]; then
			sed -i "s#^smtpd_milters.*#smtpd_milters = inet:localhost:12301#g" "/etc/postfix/main.cf"
		fi
	fi
		
	hasMilterNonSMTPD=$(cat "/etc/postfix/main.cf" | grep -o "^non_smtpd_milters")
	if [ -z "$hasMilterNonSMTPD" ]; then
		echo -e 'non_smtpd_milters = inet:localhost:12301' >> "/etc/postfix/main.cf"
	else
		hasINETInIt=$(cat "/etc/postfix/main.cf" | grep -o "non_smtpd_milters.*" | grep -o "inet:localhost:12301")
		if [ -z "$hasINETInIt" ]; then
			sed -i "s#^non_smtpd_milters.*#non_smtpd_milters = inet:localhost:12301#g" "/etc/postfix/main.cf"
		fi
	fi
	
	# Update the host name it's being sent from
	hasMyHostName=$(cat "/etc/postfix/main.cf" | grep -o "^myhostname")
	if [ -z "$hasMyHostName" ]; then
		echo -e "myhostname = ${DOMAIN}" >> "/etc/postfix/main.cf"
	else
		sed -i "s#^myhostname.*#myhostname = ${DOMAIN}#g" "/etc/postfix/main.cf"
	fi
	
	
	# Generate the keys and signing table
	mkdir -p /etc/opendkim
	mkdir -p /etc/opendkim/keys
	
	> /etc/opendkim/KeyTable
	> /etc/opendkim/SigningTable
	> /etc/opendkim/TrustedHosts
	echo -e "mail._domainkey.${DOMAIN} ${DOMAIN}:mail:/etc/opendkim/keys/${DOMAIN}/mail.private" > /etc/opendkim/KeyTable
	echo -e "* mail._domainkey.${DOMAIN}" > /etc/opendkim/SigningTable
	echo -e "127.0.0.1
localhost
*.${DOMAIN}" > /etc/opendkim/TrustedHosts
	
	mkdir -p "/etc/opendkim/keys/${DOMAIN}"
	cd "/etc/opendkim/keys/${DOMAIN}"
	opendkim-genkey -s mail -d "${DOMAIN}"
	chown opendkim:opendkim mail.private
	
	service postfix restart > /dev/null 2>&1 
	service opendkim restart > /dev/null 2>&1 
	
	update-rc.d opendkim defaults > /dev/null 2>&1 
	systemctl daemon-reload > /dev/null 2>&1 
	systemctl enable opendkim.service > /dev/null 2>&1 
	
	# Return the public key
	pubKey=$(cat mail.txt | grep -o "p=.*" | cut -f1 -d'"' | grep -o "[^(p=)+].*")
	if [ ! -z "$pubKey" ]; then
		echo -n "$pubKey"
	fi
}

function clearDKIMPostfix(){
	cp "/etc/postfix/main.cf" "/etc/postfix/main_before_dkim_resest.cf_${CurDate}"
	service postfix stop
	service opendkim stop
	
	if [ -e "/etc/opendkim" ]; then
		> /etc/opendkim/KeyTable
		> /etc/opendkim/SigningTable
		> /etc/opendkim/TrustedHosts
		
		rm -rf /etc/opendkim/keys/*
	fi
	
	sed -i "s#^milter_protocol.*##g" "/etc/postfix/main.cf"
	sed -i "s#^milter_default_action.*##g" "/etc/postfix/main.cf"
	sed -i "s/^smtpd_milters.*/#smtpd_milters.*/g" "/etc/postfix/main.cf"
	sed -i "s/^non_smtpd_milters.*/#non_smtpd_milters.*/g" "/etc/postfix/main.cf"
	
	update-rc.d opendkim disable
	systemctl daemon-reload
	systemctl disable opendkim.service
	
	service postfix restart
}

################
#   MAIN APP   #
################
# $1 is the main domain we're configuring dkim with
# $2 is action add or remove dkim
rootCheck
CurDate=$(date +%Y_%m_%d_%s)

if [ -e "/etc/postfix/main.cf" ]; then
	if [ ! -z "$1" ] && [ "$2" == "add" ]; then
		DOMAIN="$1"
		# Check for root
		aptgetInstall "opendkim opendkim-tools"
		setupDKIMConfig
	elif [ "$2" == "remove" ]; then
		clearDKIMPostfix
	else
		echo "Domain and action (add / remove) must be specified."
	fi
else
	echo "Postfix must be installed..."
fi

exit
