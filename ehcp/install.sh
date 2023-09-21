#!/bin/bash
# EHCP Force Edition Pre-Installer Script
# www.ehcpforce.tk
# by earnolmartin@gmail.com

###################
#### FUNCTIONS ####
###################

function isAptGetInUseBySomething(){
	if [ -e "/var/lib/dpkg/lock" ]; then
		APTGETRunning=$(fuser /var/lib/dpkg/lock)
		if [ ! -z "$APTGETRunning" ]; then
			APTGETProcInfo=$(ps -ef | grep "$APTGETRunning")
			clear
			echo -e "Unable to run EHCP Force installation script!"
			echo ""
			echo -e "A system update process is currently running on your system.\n\n$APTGETProcInfo\n\nClose any update applications listed above and try running the script again.  Most of these update applications finish quickly.  Re-run this installer in a few minutes if you are unsure how to close any update processes."
			exit
		fi
	fi
}

function isValidEmail(){
	# $1 = email address
	if echo "${1}" | grep '^[a-zA-Z0-9]*@[a-zA-Z0-9]*\.[a-zA-Z0-9]*$' >/dev/null; then
        return 0 # 0 = true
    else
        return 1 # 1 = false
    fi
}

function outputInfo(){
	clear
	echo "EHCP Force Edition Pre-Install Script"
	echo "Version 1.0"
	echo -e "By earnolmartin\n\n"
	echo "This pre-installation script allows you to select your EHCP Force installation mode."
	echo -e "For example, you can choose to install EHCP with extra software (Email Anti-Spam, etc).\n"
}

function generateEHCPPreInstallFile(){
  logInfoFile="/root/ehcp_info"
  if [ -e "$logInfoFile" ]; then
	CurDate=$(date +%Y_%m_%d_%s)
	mv "$logInfoFile" "/root/ehcp_info_$CurDate"
  else
	touch "$logInfoFile"
  fi
  
  generatePassword "15"
  MYSQLROOTPASS="$rPass"
  
  generatePassword
  PHPMYADMINPASS="$rPass"
  
  generatePassword
  RCUBEPASS="$rPass"
  
  generatePassword
  EHCPDBPASS="$rPass"
  
  generatePassword "20"
  EHCPADMINPASS="$rPass"
  
  echo -e "<?php\n\$mysql_root_pass=\"$MYSQLROOTPASS\";\n\$php_myadmin_pass=\"$PHPMYADMINPASS\";\n\$rcube_pass=\"$RCUBEPASS\";\n\$ehcp_mysql_pass=\"$EHCPDBPASS\";\n\$ehcp_admin_password=\"$EHCPADMINPASS\";\n?>" > "install_silently.php"
  
  echo -e "\nMySQL root user password = $MYSQLROOTPASS"
  echo "MySQL root user password = $MYSQLROOTPASS" >> "$logInfoFile"
  echo "PHPMyAdmin MySQL user password = $PHPMYADMINPASS"
  echo "PHPMyAdmin MySQL user password = $PHPMYADMINPASS" >> "$logInfoFile"
  echo "Roundcube MySQL user password = $RCUBEPASS"
  echo "Roundcube MySQL user password = $RCUBEPASS" >> "$logInfoFile"
  echo "EHCP MySQL user password = $EHCPDBPASS"
  echo "EHCP MySQL user password = $EHCPDBPASS" >> "$logInfoFile"
  echo "EHCP Admin Login = admin"
  echo "EHCP Admin Login = admin" >> "$logInfoFile"
  echo "EHCP Admin Password = $EHCPADMINPASS"
  echo "EHCP Admin Password = $EHCPADMINPASS" >> "$logInfoFile"
  echo -e "\nThis information has been saved in $logInfoFile for you to reference later!"
  sleep 5
}

function generatePassword(){
  if [ ! -z "$1" ]; then
    PLENGTH="$1"
  else
    PLENGTH="10"
  fi
  
  #rPass=$(date +%s | sha256sum | base64 | head -c "$PLENGTH")
  rPass=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1 | head -c "$PLENGTH")
}

function checkRoot(){
	# Make sure only root can run our script
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
	cmd="apt-get -y --no-remove --allow-unauthenticated install $1"
	
	if [ ! -z "$2" ]; then
		cmd="RUNLEVEL=1 $cmd"
	fi
	
	# Run the command
	sh -c "$cmd"
	
	if [ $? -ne 0 ]; then
		cmd="apt-get -y --allow-unauthenticated install $1"
		if [ ! -z "$2" ]; then
			cmd="RUNLEVEL=1 $cmd"
		fi
		sh -c "$cmd"	
	fi
	
	PackageFailed="$?"

}

function checkDistro() {		
	# Get distro properly
	if [ -e /etc/issue ]; then
		distro=$( cat /etc/issue | awk '{ print $1 }' )
	fi
		
	if [ ! -z "$distro" ]; then
		# Convert it to lowercase
		distro=$( echo $distro | awk '{print tolower($0)}' )
	fi
		
	
	if [ -z "$distro" ] || [[ "$distro" != "ubuntu" && "$distro" != "debian" ]]; then
		if [ -e /etc/os-release ]; then
			distro=$( cat /etc/os-release | grep -o "^NAME=.*" | grep -o "[^NAME=\"].*[^\"]" )
		fi
	fi
		
	# Assume Ubuntu
	if [ -z "$distro" ]; then
		distro="ubuntu"
	else
		# Convert it to lowercase
		distro=$( echo $distro | awk '{print tolower($0)}' )
	fi 
		
	# Get actual release version information
	version=$( lsb_release -r | awk '{ print $2 }' )
	if [ -z "$version" ]; then
		version=$( cat /etc/issue | awk '{ print $2 }' )
	fi
		
	# Separate year and version
	if [[ "$version" == *.* ]]; then
		yrelease=$( echo "$version" | cut -d. -f1 )
		mrelease=$( echo "$version" | cut -d. -f2 )
	else
		yrelease="$version"
		mrelease="0"
	fi
		
	# Get 64-bit OS or 32-bit OS [used in vsftpd fix]
	if [ $( uname -m ) == 'x86_64' ]; then
		OSBits=64
	else
		OSBits=32
	fi 
		
	# Another way to get the version number
	# version=$(lsb_release -r | awk '{ print $2 }')
		
	echo "Your distro is $distro runnning version $version."
	if [ "$distro" != "debian" ]; then
		echo "Your distros yearly release is $yrelease. Your distros monthly release is $mrelease."
	fi
		
	if [ "$distro" == "debian" ] && [ "$yrelease" -lt "8" ]; then
		echo "Debian 7.x and lower are no longer supported."
		exit
	fi
}

function installInitialPrereqs(){
	if [ "$distro" == "ubuntu" ]; then
		add-apt-repository -y universe
	fi
	
	apt-get update
	
	aptgetInstall software-properties-common
	aptgetInstall wget
	aptgetInstall subversion
	aptgetInstall curl
	aptgetInstall zip
}

function setTimezoneManually(){
	output="1"
	while [ "$output" != "0" ]
	do
		echo -n "Please specify a valid timezone from this list https://raw.githubusercontent.com/leon-do/Timezones/main/timezone.json without trailing and leading quotes: "
		read tzRight
		if [ ! -z "$tzRight" ]; then
			timedatectl set-timezone "$tzRight"
			output="$?"
		fi
	done
	echo -e "Your server's date/time is now currently set to $(date)"
}

function checkServerTime(){
	timezoneFile="/etc/timezone"
	if [ -e "$timezoneFile" ]; then
		currentTimezone=$(cat "$timezoneFile")
	else
		currentTimezone=$(timedatectl show | head -n 1 | cut -d= -f2)
	fi
	if [ ! -z "$currentTimezone" ]; then
		echo -n "Your server's date/time is currently $(date) and its timezone is currently set to ${currentTimezone} - Is this correct? [y/n]: "
		read tzRight
		tzRight=$(echo "$tzRight" | awk '{print tolower($0)}')
		if [ "$tzRight" == "n" ]; then
			# Detect it from IP first...
			autoTZ=$(wget -qO- "https://ehcpforce.tk/timezone.php" | xargs)
			if [ ! -z "$autoTZ" ] && [ "$autoTZ" != "-1" ]; then
				echo -n "Based on your IP address, should your timezone be set to ${autoTZ}? [y/n]: "
				read tzRight
				tzRight=$(echo "$tzRight" | awk '{print tolower($0)}')
				if [ "$tzRight" == "y" ]; then
					timedatectl set-timezone "$autoTZ"
					echo -e "Your server's date/time is now currently set to $(date)"
				else
					setTimezoneManually
				fi
			else
				setTimezoneManually
			fi
		fi
	fi
}

###################
#### Main Code ####
###################
clear

# Check for root
checkRoot

# Check OS
checkDistro

# Check for Apt-get availability
isAptGetInUseBySomething

# Install some pre-reqs
installInitialPrereqs

# Get parameters
for varCheck in "$@"
do
    if [ "$varCheck" == "unattended" ]; then
		preUnattended=1
    elif [ "$varCheck" == "debug" ]; then
		debug="debug"
    elif [ "$varCheck" == "extra" ]; then
		installmode="extra"
    elif [ "$varCheck" == "normal" ]; then
		installmode="normal"
    fi
done

if [ ! -z "$preUnattended" ]; then
	## They really want this install to be unattended, so run the installer and use default passwords of 1234
	
	if [ ! -z "$installmode" ] && [ "$installmode" == "extra" ]; then
	
		# Handle policyd
		insPolicyD="ins_policyd.cfg"
		if [ ! -e "$insPolicyD" ]; then
			echo -e "insPolicyD=true" > "$insPolicyD"
		fi
		
		# Set amavis fully qualified domain name
		if [ ! -e "fqdn_amavis.cfg" ]; then
			FQDNCFG="fqdn_amavis.cfg"
			FQDNName="ehcpforce.tk"
			echo -e "FQDNName=\"$FQDNName\"" > "$FQDNCFG"
		fi
		
		# Handle preset timezone
		serverTZ="server_tz.cfg"
		if [ -e "$serverTZ" ]; then
			serverTZ=$(cat "$serverTZ")
			timedatectl set-timezone "$serverTZ"
		fi
		
	fi
	
	## Run the main installer passing the parameters we received
	bash install_main.sh "$@"
else

	# Echo Info
	outputInfo

	# Ready to start?
	echo -n "Install EHCP Force Edition in unattended mode (installs all software without prompts and generates passwords)? [y/n]: "
	read unattended
	unattended=$(echo "$unattended" | awk '{print tolower($0)}')
	
	if [ "$unattended" != "n" ]; then
		generateEHCPPreInstallFile
		unattendedMode="unattended"
	fi
	
	
	echo ""
	echo -n "Please enter a valid global email address for the panel to use: "
	read emailAddr
	emailAddr=$(echo "$emailAddr" | awk '{print tolower($0)}')
	if ! isValidEmail "$emailAddr"; then
		emailAddr="info@ehcpforce.tk"
	fi
	adminEmailCFG="admin_email.php"
	echo "<?php \$adminEmail = \"${emailAddr}\"; ?>" > "$adminEmailCFG"
	
	echo ""
	echo -n "Install extra software in addition to EHCP Force Edition (such as Amavis, SpamAssassin, ClamAV, ModSecurity, ModEvasive, Fail2Ban)? [y/n]: "
	read insMode
	insMode=$(echo "$insMode" | awk '{print tolower($0)}')
	
	if [ "$insMode" != "n" ]; then
		extra="extra"
		FQDNCFG="fqdn_amavis.cfg"
		if [ ! -e "$FQDNCFG" ]; then
			# Prompt for FQDN for mail server
			echo ""
			echo -n "Please enter your Fully Qualified Domain Name (FQDN) for this mail server (used for Amavis): "
			read FQDNName
			FQDNName=$(echo "$FQDNName" | awk '{print tolower($0)}')
			if [ -z "$FQDNName" ]; then
				# Just replace it with ehcpforce.tk
				FQDNName="ehcpforce.tk"
			fi
			echo -e "FQDNName=\"$FQDNName\"" > "$FQDNCFG"
		fi
		
		insPolicyD="ins_policyd.cfg"
		if [ ! -e "$insPolicyD" ]; then
			# Prompt for PolicyD Installation
			echo ""
			echo -n "ADVANCED: Would you like to install PolicyD (Ubuntu 14.04 & Debian 8 - And Up ONLY)? [y/n]: "
			read policyDI
			policyDI=$(echo "$policyDI" | awk '{print tolower($0)}')
			if [ "$policyDI" == "y" ]; then
				# Just replace it with ehcpforce.tk
				echo -e "insPolicyD=true" > "$insPolicyD"
			fi
		fi
	else
		extra="normal"
	fi
	
	echo -e ""
	echo -e "Performing server date/timezone check..."
	echo -e ""
	checkServerTime
	echo -e ""
	echo -e ""
	
	echo -e "Running the main installer now..."
	
	# Run the main installer
	echo "bash install_main.sh $unattendedMode $extra $debug"
	bash install_main.sh $unattendedMode $extra $debug
	
fi
