#!/bin/bash
# EHCP Force Edition - Easy Hosting Control Panel Installation Script
# http://ehcpforce.tk
# by earnolmartin@gmail.com 

########################
# FORK INFORMATION     #
########################
# Fork created by earnolmartin@gmail.com
# http://ehcpforce.tk
# This forked version of EHCP offers new functionality and features (custom FTP accounts, proper web user permissions so that PHP scripts work as intended, and more) 
# that were not previously available in the main EHCP release.
# Also, this forked version is updated on a regular basis when a new version of Ubuntu comes out.
# You should run this fork on Ubuntu.  It will work perfectly on all supported Ubuntu versions (The ones Ubuntu officially supports such as the LTS release and latested versions).
# Debian should work as well, but if it doesn't, please let me know. 
# Based on Yeni EHCP Release
# Source code available via SVN so that multiple people can develop and track changes!
# Does not add a EHCP reseller account by default without your knowledge ensuring security
# Main Web Panel GUI theme is Ep-Ic V2 (theme I custom developed which links to all of the new functionality)
# Other themes may not have the latest and greatest operations available

ehcpversion="1.0.1"

echo
echo 
#echo "Beginning EHCP FoRcE Version Installation"
echo
echo 
chmod -Rf a+r *

# Get parameters
for varCheck in "$@"
do
    if [ "$varCheck" == "unattended" ]; then
		unattended="unattended"
    elif [ "$varCheck" == "light" ]; then
		installmode="light"
	elif [ "$varCheck" == "extra" ]; then
		installmode="extra"
	elif [ "$varCheck" == "noapt" ]; then
		noapt="noapt"
	elif [ "$varCheck" == "skipupdates" ] || [ "$varCheck" == "devmode" ]; then
		skip_updates="yes"
	elif [ "$varCheck" == "debug" ]; then
		debug="debug"
    fi
done

if [ -z "$installmode" ]; then
	installmode="normal"
fi

################################################################################################
# Function Definitions																		 #
################################################################################################

# Stub function for apt-get

function setGlobalVars(){
	FIXDIR="/var/www/new/ehcp/fixes"
	serviceNameTempFile="/root/ehcp_service_name_search_temp_file"
	logInfoFile="/root/ehcp_info"
	patchDir="/root/Downloads"
	if [ ! -e $patchDir ]; then
		mkdir $patchDir
	fi
	FQDNCFG="fqdn_amavis.cfg"
	if [ -e "$FQDNCFG" ]; then
		source "$FQDNCFG"
	fi
	INSPDCFG="ins_policyd.cfg"
	if [ -e "$INSPDCFG" ]; then
		source "$INSPDCFG"
	fi
	installerDir=$(pwd)
	initProcessStr=$(ps -p 1 | awk '{print $4}' | tail -n 1)
	if [ "$initProcessStr" == "systemd" ]; then
		systemdPresent=1
	fi
}

function installaptget () {
	echo "now let's try to install apt-get on your system."
	echo "Not yet implemented"
	exit
}

# Stub function fot yum

function installyum () {
	echo "now let's try to install yum on your system."
	echo "Not yet implemented"
}

# Initial Welcome Screen

function ehcpHeader() {
	echo 
	echo
	echo "STAGE 1"
	echo "====================================================================="
	echo
	echo "--------------------EHCP PRE-INSTALLER $ehcpversion -------------------------"
	echo "-----Easy Hosting Control Panel for Ubuntu, Debian, and Alikes-------"
	echo "---------------------- FoRcE Edition (a fork) -----------------------"
	echo "---------------------- http://www.ehcpforce.tk ----------------------"
	echo "-------------------------Non-Fork Version:  www.ehcp.net-------------"
	echo "---------------------------------------------------------------------"
	echo
	echo 
	echo "Now, ehcp pre-installer begins, a series of operations will be performed and main installer will be invoked. "
	echo "if any problem occurs, refer to www.ehcpforce.tk forum section, or contact me, mail/msn: info@ehcpforce.tk"
	
	echo "Please be patient, press enter to continue"
	if [ "$unattended" == "" ] ; then
		read
	fi

	echo
	echo "Note that ehcp can only be installed automatically on Debian based Linux OS'es or Linux'es with apt-get enabled..(Ubuntu, Kubuntu, debian and so on) Do not try to install ehcp with this installer on redhat, centos and non-debian Linux's... To use ehcp on no-debian systems, you need to manually install.. "
	echo "this installer is for installing onto a clean, newly installed Ubuntu/Debian. If you install it on existing system, some existing packages will be removed after prompting, if they conflict with packages that are used in ehcp, so, be careful to answer yes/no when using in non-new system"
	echo "Actually, I dont like saying like, 'No warranty, I cannot be responsible for any damage.... ', But, this is just a utility.. use at your own."
	echo "ehcp also sends some usage data to developer for statistical purposes"
	echo "press enter to continue"
	if [ "$unattended" == "" ] ; then
		read
	fi
}

# Check for yum

function checkyum () {
	which yum > /dev/null 2>&1
	if [ "$?" == "0"  ]; then
		echo "yum is available"
		return 0
	else
		# This should never happen
		echo "Please install yum"
		installyum
	fi
}

# Check for apt-get

function checkAptget(){

	sayi=`which apt-get | wc -w`
	if [ $sayi -eq 0 ] ; then
		ehco "apt-get is not found."
		installaptget
	fi

	echo "apt-get seems to be installed on your system."
	aptIsInstalled=1

	sayi=`grep -v "#" /etc/apt/sources.list | wc -l`

	if [ $sayi -lt 10 ] ; then
		echo
		echo "WARNING ! Your /etc/apt/sources.list  file contains very few sources, This may cause problems installing some packages.. see http://www.ehcp.net/?q=node/389 for an example file"
		echo "This may be normal for some versions of debian"
		echo "press enter to continue or Ctrl-C to cancel and fix that file"
		if [ "$unattended" == "" ] ; then
			read
		fi
	fi

}

# Function to be called when installing packages, by Marcel <marcelbutucea@gmail.com>

function installPack(){
	
	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get install for:$1"
		return
	fi
	
	if [ $distro == "ubuntu" ] || [ $distro == "debian" ];then
		# first, try to install without any prompt, then if anything goes wrong, normal install..
		apt-get -y --no-remove --allow-unauthenticated install $1
		if [ $? -ne 0 ]; then
				apt-get -y --allow-unauthenticated install $1
		fi
	else
		# Yum is nice, you don't get prompted :)
		yum -y -t install $1
	fi
}

function logToFile(){
	logfile="ehcp-apt-get-install.log"
	echo "$1" >> $logfile
}

function logToInstallLogFile(){
	# Only log if debug is enabled
	if [ ! -z "$debug" ]; then
		loginstallfile="install_log.txt"
		echo "$1" >> "$installerDir/$loginstallfile"
		echo -e "\n$1"
	fi
}

function aptget_Update(){
	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get update"
		return
	fi

	apt-get update
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

function aptPackageInstallOrManualGet(){
	cd "$patchDir"
	aptgetInstall "$1"
	AptExitCode="$PackageFailed"
	if [ "$AptExitCode" -eq "100" ]; then
		PackageExists=$(echo "$AptExitCode")
	else
		PackageExists=
	fi
	
	if [ ! -z "$PackageExists" ]; then
		echo -e "\nPackage $1 was not found in the main Ubuntu repository.  However, it is needed in EHCP.  Checking EHCP fix directory for local fix.\n"
		# See if the package exists locally
		if [ $OSBits -eq "64" ]; then
			localPackageName="$1_x64.deb"
		else
			localPackageName="$1.deb"
		fi
		localSearchResults=$(find "$FIXDIR" -name "$localPackageName")
		if [ ! -z "$localSearchResults" ]; then
			cp "$localSearchResults" "$1.deb"
		fi
		
		# Clear the flag as we have found it locally...
		if [ -e "$1.deb" ]; then
			PackageExists=
		fi
	fi
	
	# If the package was not found locally, get it from the web
	baseDLURL="http://www.dinofly.com/files/linux/"
	if [ ! -z "$PackageExists" ]; then
		echo -e "\nPackage $1 was not found in the main Ubuntu repository.  However, it is needed in EHCP.  Checking developer's server for appropriate package.\n"
	
		# Get proper version of package from 
		if [ $OSBits -eq "64" ]; then
			# Check to see if package exists
			fExists=$(wget --spider -v "$baseDLURL$1_x64.deb")
			fExistsCheck=$(echo "$fExists" | grep "404")
			if [ -z "$fExistsCheck" ]; then
				echo -e "Package $1 was found on developer server.  Downloading and installing now!"
				wget -N -O "$1.deb" "$baseDLURL$1_x64.deb"
			fi
		else
			fExists=$(wget --spider -v "$baseDLURL$1.deb")
			fExistsCheck=$(echo "$fExists" | grep "404")
			if [ -z "$fExistsCheck" ]; then
				echo -e "Package $1 was found on developer server.  Downloading and installing now!"
				wget -N -O "$1.deb" "$baseDLURL$1.deb"
			fi
		fi
	fi
	
	# Install the missing package if it exists within our repository
	if [ -e "$1.deb" ]; then
		gdebi --n "$1.deb"
	fi
}

function aptgetRemove(){
	if [ -n "$noapt" ] ; then  # skip uninstall
		echo "skipping apt-get remove for:$1"
		return
	fi 
	
	# first, try to uninstall without any prompt, then if anything goes wrong, normal uninstall..
	cmd="apt-get -y remove $1"
	logToFile "$cmd"
	
	# Run the command
	sh -c "$cmd"
	
	if [ $? -ne 0 ]; then
		cmd="apt-get remove $1"
		logToFile "$cmd"
		
		# Run the command
		sh -c "$cmd"	
	fi 
}

# Get distro name , by Marcel <marcelbutucea@gmail.com>, thanks to marcel for fixing whole code syntax
# No longer works in Ubuntu 13.04
# Fixed by Eric Martin <earnolmartin@gmail.com>
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
		fi
		
		# Get 64-bit OS or 32-bit OS [used in vsftpd fix]
		if [ $( uname -m ) == 'x86_64' ]; then
			OSBits=64
		else
			OSBits=32
		fi 
		
		# Another way to get the version number
		# version=$(lsb_release -r | awk '{ print $2 }')
		
		echo "Your distro is $distro runnning version $version"
		
		if [ "$distro" == "debian" ] && [ "$yrelease" -lt "8" ]; then
			echo "Debian 7.x and lower are no longer supported."
			exit
		fi
}

# Check if the running user is root, if not restart with sudo
function checkUser() {
		if [ `whoami` != "root" ];then
				echo "you are $who, you have to be root to use ehcp installation program.  switching to root mode, please enter password  or re-run install.sh as root"
				sudo $0 # restart this with superuser-root privileges				
				exit
		fi
}

# Function to kill any running ehcp / php daemons
function killallEhcp() {
		for i in `ps aux | grep ehcpdaemon.sh | grep -v grep | awk -F " " '{ print $2 }'`;do
				kill -9 $i
		done

		for i in `ps aux | grep 'php index.php' | grep -v grep | awk -F " " '{ print $2 }'`;do
				kill -9 $i
		done
}


function checkPhp(){
	which php
	if [ $? -eq 0 ] ; then
		echo "php seems installed. This is good.."
	else
		echo "PHP IS STILL NOT INSTALLED. THIS IS A SERIOUS PROBLEM.  MOST PROBABLY, YOU WILL NOT BE ABLE TO CONTINUE. TRY TO INSTLL PHP yourself."
		echo "if rest of install is successfull, then, this is a false alarm, just ignore"
	fi
}

function launchPanel(){
	# NEVER LAUNCH FIREFOX AS THE ROOT USER.  IT WILL MESS IT UP FOR THE NORMAL USER
	if [ -z "$unattended" ] || [ "$unattended" == "" ] ; then
		if [ ! -z "$SUDO_USER" ] && [ "$SUDO_USER" != "root" ]; then
			echo 
			echo "The EHCP panel is now accessible!"
			echo "Your panel administrative login is: admin"
			echo "Attempting to load the control panel via web browser from the local machine."
			sudo -u "$SUDO_USER" xdg-open http://localhost/
		fi
	fi
}

# Thanks a lot to  earnolmartin@gmail.com for fail2ban integration & vsftpd fixes.

function slaveDNSApparmorFix(){ # by earnolmartin@gmail.com
	if [ -e /etc/apparmor.d/usr.sbin.named ]; then
				echo -e "\nChanging bind apparmor rule to allow master DNS synchronization for slave setups.\n"
				sed -i 's#/etc/bind/\*\* r,#/etc/bind/\*\* rw,#g' /etc/apparmor.d/usr.sbin.named
				manageService "apparmor" "restart"
	fi
}

function libldapFix(){ # by earnolmartin@gmail.com
	# install libldap, for vsftpd fix, without prompts
	#Remove originally installed libpam-ldap if it exists
	origDir=$(pwd)	
	aptgetRemove libpam-ldap
	DEBIAN_FRONTEND=noninteractive apt-get -y install libpam-ldap
	cd "$patchDir"
	mkdir lib32gccfix
	cd lib32gccfix
	cp "$FIXDIR/vsftpd/x64/ldap_conf_64bit_vsftpd.tar.gz" "ldap_conf.tar.gz"
	tar -zxvf ldap_conf.tar.gz
	cp ldap.conf /etc/
	cd $origDir
}  

function fixVSFTPConfig(){ # by earnolmartin@gmail.com
	# VSFTPD config file
	VSFTPDCONF="/etc/vsftpd.conf"	
	
	# Check to see if the chroot_local_user setting is present
	chrootLocalCheck=$(cat "$VSFTPDCONF" | grep "chroot_local_user")
	if [ -z "$chrootLocalCheck" ]; then
		sh -c "echo 'chroot_local_user=YES' >> $VSFTPDCONF"
	else
		sed -i 's/chroot_local_user=.*/chroot_local_user=YES/g' "$VSFTPDCONF"
	fi
		
	# Check to see if allow writeable chroot is present
	allowWriteValue=$(cat "$VSFTPDCONF" | grep "allow_writeable_chroot")
	if [ -z "$allowWriteValue" ]; then
		sh -c "echo 'allow_writeable_chroot=YES' >> $VSFTPDCONF"
	else
		sed -i 's/allow_writeable_chroot=.*/allow_writeable_chroot=YES/g' "$VSFTPDCONF"
	fi

	if [ $OSBits -eq "64" ]; then 
		#aptgetInstall libpam-ldap # this is required in buggy vsftpd installs.. ubuntu 12.04,12.10, 13.04, now... 
		libldapFix
		aptgetInstall libgcc1
		# 64-bit 500 OOPS: priv_sock_get_cmd Fix
		# seccomp_sandbox=NO
		allowSandBox=$(cat "$VSFTPDCONF" | grep "seccomp_sandbox")
		if [ -z "$allowSandBox" ]; then
			if [[ "$distro" == "ubuntu" && "$yrelease" -ge "13" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
				sh -c "echo 'seccomp_sandbox=NO' >> $VSFTPDCONF"
			fi
		else
			sed -i 's/seccomp_sandbox=.*/seccomp_sandbox=NO/g' "$VSFTPDCONF"
		fi		
	fi
	
	# Restart the VSFTPD service
	manageService "vsftpd" "restart"
}

function remove_vsftpd(){
	# Remove the hold if any:
	echo "vsftpd install" | dpkg --set-selections
	# Remove originally installed vsftpd
	aptgetRemove vsftpd
	# Just incase it's been installed already or another version has been installed using dpgk, let's remove it
	dpkg --remove vsftpd
}

function fixApacheDefault(){
	ApacheFile="/etc/apache2/apache2.conf"
	confStr="IncludeOptional sites-enabled/\*.conf"
	correctConfStr="Include sites-enabled/"
	if [ -e "$ApacheFile" ]; then
		ConfCheck=$( cat "$ApacheFile" | grep -o "$confStr" )
		if [ ! -z "$ConfCheck" ]; then 
			sed -i "s#$confStr#$correctConfStr#g" "$ApacheFile"
			manageService "apache2" "restart"
		fi
	fi
}

function removeNameVirtualHost(){
	ApacheFile="/etc/apache2/ports.conf"
	confStr="NameVirtualHost.*"
	
	if [ -e "$ApacheFile" ]; then
		ConfCheck=$( cat "$ApacheFile" | grep -o "$confStr" )
		if [ ! -z "$ConfCheck" ]; then 
			sed -i "s#$confStr##g" "$ApacheFile"
			manageService "apache2" "restart"
		fi
	fi
}

function genUbuntuFixes(){
	# Ubuntu packages keep coming with new features that mess things up
	# Thanks Ubuntu for the unneccessary headaches!
	# Includes debian too... jesus christ
	if [ ! -z "$yrelease" ]; then
		if [[ "$distro" == "ubuntu" && "$yrelease" == "13" && "$mrelease" == "10" ]] || [[ "$distro" == "ubuntu" && "$yrelease" -ge "14" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
			fixApacheDefault
			removeNameVirtualHost
			addConfDFolder
			getRidOfExtraPHPMyAdminAlias
			fixApache2ConfigInvalidLockFileLine
			fixMariaDBSkippingInnoDB
		fi
	fi
}

function fixApache2ConfigInvalidLockFileLine(){
	sed -i "s/^LockFile/#LockFile/g" "/etc/apache2/apache2.conf"
}

function addConfDFolder(){
	# If the conf.d folder doesn't exist, we must create it!
	if [ ! -e "/etc/apache2/conf.d" ]; then
		mkdir -p "/etc/apache2/conf.d"
	fi
	
	# Include it in apache2 config
	if [ -e "/etc/apache2/apache2.conf" ]; then
		APACHECONFCONTENTS=$(cat "/etc/apache2/apache2.conf" | grep "conf.d")
		if [ -z "$APACHECONFCONTENTS" ]; then
			echo "Include conf.d/" >> "/etc/apache2/apache2.conf"
		fi
	fi
}

function ubuntuVSFTPDFix(){ # by earnolmartin@gmail.com
	# Get currently working directory
	origDir=$( pwd )
	
	# Install vsftpd if for some reason it wasn't installed earlier
	aptgetInstall vsftpd
	
	# Ubuntu VSFTPD Fixes
	if [ ! -z "$yrelease" ]; then
		if [ "$distro" == "ubuntu" ]; then
			if [ "$yrelease" == "12" ] ; then
				 if [ "$mrelease" == "04" ]; then
					# Run 12.04 Fix
					remove_vsftpd
					echo -e "\nRunning VSFTPD fix for Ubuntu 12.04\n"
					add-apt-repository -y ppa:thefrontiergroup/vsftpd
					aptget_Update
					aptgetInstall vsftpd

				 elif [ "$mrelease" == "10" ]; then
					# Run 12.10 Fix
					remove_vsftpd
					echo -e "\nRunning VSFTPD fix for Ubuntu 12.10\n"
					#get the code
					cd "$patchDir"
					if [ ! -e vsftpd_2.3.5-3ubuntu1.deb ]; then
						if [ $OSBits -eq "32" ]; then
							cp "$FIXDIR/vsftpd/ubuntu/12.10/vsftpd_2.3.5-3ubuntu1_i386.deb" "vsftpd_2.3.5-3ubuntu1.deb"
						else
							cp "$FIXDIR/vsftpd/ubuntu/12.10/vsftpd_2.3.5-3.jme_amd64.deb" "vsftpd_2.3.5-3ubuntu1.deb"
						fi
					fi
					#install
					dpkg -i vsftpd_2.3.5-3ubuntu1.deb
					echo "vsftpd hold" | dpkg --set-selections # Official updates to the VSFTPD package will break functionality since the package is broken OOTB
					cd $origDir
				 fi
			elif [ "$yrelease" == "13" ]; then
				# Ubuntu 13.04
				if [ "$mrelease" == "04" ]; then
					remove_vsftpd
					echo -e "\nRunning VSFTPD fix for Ubuntu 13.04\n"
					cd "$patchDir"
					if [ ! -e vsftpd_3.0.2-patched_ubuntu.deb ]; then
						if [ $OSBits -eq "32" ]; then 
							cp "$FIXDIR/vsftpd/ubuntu/13.04/vsftpd_3.0.2-patched_ubuntu_13.04_x86.deb" "vsftpd_3.0.2-patched_ubuntu.deb"
						else
							cp "$FIXDIR/vsftpd/ubuntu/13.04/vsftpd_3.0.2-1ubuntu1_amd64_patched.deb" "vsftpd_3.0.2-patched_ubuntu.deb"
						fi
					fi
					dpkg -i vsftpd_3.0.2-patched_ubuntu.deb
					echo "vsftpd hold" | dpkg --set-selections # Official updates to the VSFTPD package will break functionality since the package is broken OOTB
					cd $origDir
				fi
				
				# Ubuntu 13.10
				if [ "$mrelease" == "10" ]; then
					echo -e "\nRunning VSFTPD fix for Ubuntu 13.10\n"
				fi
			elif [ "$yrelease" == "14" ]; then
				# Ubuntu 14.04
				if [ "$mrelease" == "04" ]; then
					echo -e "\nRunning VSFTPD fix for Ubuntu 14.04\n"
					if [ $OSBits -eq "64" ]; then 
						remove_vsftpd
						cp "$FIXDIR/vsftpd/ubuntu/14.04/vsftpd_3.0.2-1ubuntu2_amd64.deb" "vsftpd_3.0.2-1ubuntu2.deb"
						dpkg -i vsftpd_3.0.2-1ubuntu2.deb
						echo "vsftpd hold" | dpkg --set-selections # Official updates to the VSFTPD package will break functionality since the package is broken OOTB
					fi
				fi
			
				# Ubuntu 14.10
				if [ "$mrelease" == "10" ]; then
					echo -e "\nRunning VSFTPD fix for Ubuntu 14.10\n"
					if [ $OSBits -eq "64" ]; then 
						remove_vsftpd
						cp "$FIXDIR/vsftpd/ubuntu/14.10/vsftpd_3.0.2-14ubuntu1_amd64.deb" "vsftpd_3.0.2-14ubuntu1.deb"
						dpkg -i vsftpd_3.0.2-14ubuntu1.deb
						echo "vsftpd hold" | dpkg --set-selections # Official updates to the VSFTPD package will break functionality since the package is broken OOTB
					fi
				fi			
			fi
		fi  
	fi
	
	# Run our fixes to the vsftpd config file
	fixVSFTPConfig
}

function logDirFix(){ # by earnolmartin@gmail.com
	chmod 755 /var/www/new/ehcp/log
	chmod 744 /var/www/new/ehcp/log/ehcp_failed_authentication.log
	chown ${VSFTPDUser}:www-data /var/www/new/ehcp/log/ehcp_failed_authentication.log
}

function fixBINDPerms(){ # by earnolmartin@gmail.com
	chmod -R 774 /etc/bind
	
	# Set correct owner on the bind directory:
	if [ -e "/etc/bind" ]; then
		BINDUser=$(ls -alhi /etc/bind | awk '{print $4}' | head -n2 | tail -n1)
		if [ "$BINDUser" == "root" ]; then
			BINDUser=$(ls -alhi /etc/bind | awk '{print $5}' | head -n2 | tail -n1)
		fi
		
		if [ ! -z "$BINDUser" ] && [ "$BINDUser" != "root" ]; then
			chown "$BINDUser":root -R /etc/bind
		else
			BINDUser=$(cat /etc/passwd | grep -o "^bind.*")
			if [ ! -z "$BINDUser" ]; then
				chown bind:root -R /etc/bind
			else
				BINDUser=$(cat /etc/passwd | grep -o "^named.*")
				if [ ! -z "$BINDUser" ]; then
					chown named:root -R /etc/bind
				fi
			fi
		fi
	fi
}

function fixEHCPPerms(){ # by earnolmartin@gmail.com
	# Make backup dir for install log file
	genEHCPBackupDIR="/root/Backup/ehcpforce"
	if [ ! -e "$genEHCPBackupDIR" ]; then
		mkdir -p "$genEHCPBackupDIR"
	fi

	# Secure ehcp files
	chown -R root:root /var/www/new/ehcp
	chmod -R 755 /var/www/new/ehcp/
	
	# Correct net2ftp permissions
	chown -R ${VSFTPDUser}:www-data /var/www/new/ehcp/net2ftp
	chmod -R 755 /var/www/new/ehcp/net2ftp
	
	# Correct permissions on test folder - used for testing some things like SSL certs
	# Delete old security risk files from the directory first
	if [ -e "/var/www/new/ehcp/test" ]; then
		rm -r "/var/www/new/ehcp/test"
	fi
	mkdir -p /var/www/new/ehcp/test
	chown -R ${VSFTPDUser}:www-data /var/www/new/ehcp/test
	chmod -R 755 /var/www/new/ehcp/test
	
	# Correct extplorer permissions
	if [ -e "/var/www/new/ehcp/extplorer" ]; then
		chown -R ${VSFTPDUser}:www-data /var/www/new/ehcp/extplorer
		chmod -R 755 /var/www/new/ehcp/extplorer
	fi

	# Make default index readable
	chmod 755 /var/www/new/index.html
	
	# Set proper permissions on vhosts
	chown ${VSFTPDUser}:www-data -R /var/www/vhosts/
	chmod 0755 -R /var/www/vhosts/
	
	# Secure webmail
	chown root:www-data -R /var/www/new/ehcp/webmail2
	chmod 754 -R /var/www/new/ehcp/webmail2
	chmod -R 774 /var/www/new/ehcp/webmail2/data
	
	# Fix EHCP security
	if [ -e "/var/www/new/ehcp/ehcpbackup.php" ]; then
		rm "/var/www/new/ehcp/ehcpbackup.php"
	fi
	
	if [ -e "/var/www/new/ehcp/phpadmin.php" ]; then
		rm "/var/www/new/ehcp/phpadmin.php"
	fi
	
	if [ -e "/var/www/new/ehcp/install_log.txt" ]; then
		rm "/var/www/new/ehcp/install_log.txt"
	fi
	
	chmod 700 "/var/www/new/ehcp/install_1.php"
	chmod 700 "/var/www/new/ehcp/install_2.php"
	chmod 700 "/var/www/new/ehcp/install_lib.php"
	chmod 700 "/var/www/new/ehcp/install2.1.php"
	chmod 700 "/var/www/new/ehcp/ehcp_fix_apache.php"
}

function fixPHPConfig(){ # by earnolmartin@gmail.com
	PHPConfFile="$PHPCONFDir/cli/php.ini"
	if [ -e $PHPConfFile ]; then
		PHPConfCheck=$( cat $PHPConfFile | grep -o ";extension=mysql.so" )
		if [ -z "$PHPConfCheck" ]; then 
			sed -i "s/extension=mysql.so/;extension=mysql.so/g" $PHPConfFile
			manageService "apache2" "restart"
		fi
	fi
}

function copyNginxConf(){ # by earnolmartin@gmail.com
	# Make sure to copy our modified nginx.conf over to /etc/nginx
	cp -f "/var/www/new/ehcp/etc/nginx/nginx.conf" "/etc/nginx"
}

function fixNginxSessions(){
	nginxFPMPHPConfig="$PHPCONFDir/fpm/php.ini"
	if [ -e "$nginxFPMPHPConfig" ]; then
		SESSIONPathConfigured=$(cat "$nginxFPMPHPConfig" | grep -o "^session.save_path.*")
		if [ -z "$SESSIONPathConfigured" ]; then
			echo -e "session.save_path = \"/tmp\"" >> "$nginxFPMPHPConfig"
		fi
		managePHPFPMService "restart"
	fi
}

function changeApacheUser(){ # by earnolmartin@gmail.com
	# Apache should run as the vsftpd account so that FTP connections own the file and php scripts can own the file
	# Without this fix, files uploaded by ftp could not be changed by PHP scripts... AND
	# Files uploaded / created by PHP scripts could not be modified (chmod) via FTP clients
	manageService "apache2" "stop"
	if [ -e "/etc/apache2/envvars" ]; then
		sed -i "s/export APACHE_RUN_USER=.*/export APACHE_RUN_USER=${VSFTPDUser}/g" "/etc/apache2/envvars"
		if [ -e "/var/lock/apache2" ]; then
			chown ${VSFTPDUser}:www-data "/var/lock/apache2"
		else
			mkdir "/var/lock/apache2"
			chown ${VSFTPDUser}:www-data "/var/lock/apache2"
		fi
		manageService "apache2" "restart"
	fi
	
	# Also change nginx user
	changeNginxUser
	
	# Also change php-fpm user
	if [ -e "$PHPCONFDir/fpm/pool.d/www.conf" ]; then
		sed -i "s/user = .*/user = ${VSFTPDUser}/g" "$PHPCONFDir/fpm/pool.d/www.conf"
		sed -i "s/group = .*/group = www-data/g" "$PHPCONFDir/fpm/pool.d/www.conf"
	fi
	
	# Create EHCP Pool and Secure WWW Pool
	# VHOSTs can't run protected functions :)
	createEHCPPool
}

function createEHCPPool(){
	# Create EHCP Pool
	if [ ! -e "$PHPCONFDir/fpm/pool.d/ehcp.conf" ]; then
		cp "$PHPCONFDir/fpm/pool.d/www.conf" "$PHPCONFDir/fpm/pool.d/ehcp.conf"
		sed -i "s/^\[www\]/\[ehcp\]/g" "$PHPCONFDir/fpm/pool.d/ehcp.conf"
		sed -i "s/^listen.*/listen = 9001/g" "$PHPCONFDir/fpm/pool.d/ehcp.conf"
	fi
	
	hasDisableFunctions=$(cat "$PHPCONFDir/fpm/pool.d/www.conf" | grep -o "php_admin_value\[disable_functions\].*")
	if [ -z "$hasDisableFunctions" ]; then
		echo "php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen" >> "$PHPCONFDir/fpm/pool.d/www.conf"
	fi
	
	# Remove it from ehcp if it's there
	hasDisableFunctionsTwo=$(cat "$PHPCONFDir/fpm/pool.d/ehcp.conf" | grep -o "php_admin_value\[disable_functions\].*")
	if [ ! -z "$hasDisableFunctionsTwo" ]; then
		sed -i "s/^php_admin_value\[disable_functions\]/;php_admin_value\[disable_functions\]/g" "$PHPCONFDir/fpm/pool.d/ehcp.conf"
	fi
}

function apacheUseFPM(){
	a2enmod proxy_fcgi
	a2dismod php5
	a2dismod php7.0
	a2dismod php7.1
	a2dismod php7.2
	
	# We need a newer version of Apache for this to work properly!
	add-apt-repository -y ppa:ondrej/apache2
	aptget_Update
	
	# Harder to use PPAs from Ubuntu on Debian, but still possible :)
	if [ "$distro" == "debian" ] && [ "$yrelease" -eq "8" ]; then
		sed -i "s/jessie/trusty/g" "/etc/apt/sources.list.d/ondrej-apache2-jessie.list"
		sed -i "s/jessie/trusty/g" "/etc/apt/sources.list.d/ondrej-apache2-jessie.list.save"
	fi
	if [ "$distro" == "debian" ] && [ "$yrelease" -eq "9" ]; then
		sed -i "s/cosmic/xenial/g" "/etc/apt/sources.list.d/ondrej-ubuntu-apache2-cosmic.list"
		sed -i "s/cosmic/xenial/g" "/etc/apt/sources.list.d/ondrej-ubuntu-apache2-cosmic.list.save"
	fi
	
	aptget_Update
	apt-get install -y --allow-unauthenticated -o Dpkg::Options::="--force-confold" apache2
}

function changeNginxUser(){
	# Change nginx user
	if [ -e "/etc/nginx/nginx.conf" ]; then
		sed -i "s/^user .*/user ${VSFTPDUser} www-data;/g" "/etc/nginx/nginx.conf"
	fi
}

function nginxRateLimit(){
	if [ -e "/etc/nginx/nginx.conf" ]; then
		NGINXHASRATELIMIT=$(cat "/etc/nginx/nginx.conf" | grep "limit_req_zone")
		if [ -z "$NGINXHASRATELIMIT" ]; then
			sed -i '/http {/a limit_req_zone $binary_remote_addr zone=one:10m rate=10r/s;' "/etc/nginx/nginx.conf"
		fi
	fi
}

function updateBeforeInstall(){ # by earnolmartin@gmail.com
	# Update packages before installing to avoid errors
	
	if [ -z "$skip_updates" ]; then
		if [ "$aptIsInstalled" -eq "1" ] ; then
			echo "Updating package information and downloading package updates before installation."
			
			# Make sure the system will update and upgrade
			if [ -e "/var/lib/apt/lists/lock" ]; then
				rm "/var/lib/apt/lists/lock"
			fi
			
			# Make sure the system will update and upgrade
			if [ -e "/var/cache/apt/archives/lock" ]; then
				rm "/var/cache/apt/archives/lock"
			fi
			
			# Run update commands
			apt-key update
			apt-get update -y --allow-unauthenticated
			apt-get upgrade -y --allow-unauthenticated
		fi
	fi
}

function secureApache(){
	APACHE2Conf="/etc/apache2/apache2.conf"
	if [ -e "$APACHE2Conf" ]; then
		containsDef=$(cat "$APACHE2Conf" | grep "<Directory /var/www/>")
		if [ ! -z "$containsDef" ]; then
			sed -i "s/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/g" "$APACHE2Conf"
		else
			containsCorrectIndexs=$(cat "$APACHE2Conf" | grep "Options -Indexes +FollowSymLinks")
			if [ -z "$containsCorrectIndexs" ]; then
				echo "Options -Indexes +FollowSymLinks" >> "$APACHE2Conf"
			fi
		fi
	fi
}

function allowHTACCESSOverrides(){
	APACHE2Conf="/etc/apache2/apache2.conf"
	if [ -e "$APACHE2Conf" ]; then
		# This should only be done for Apache versions in latest versions of Ubuntu where they define specifically allow override perms
		containsDef=$(cat "$APACHE2Conf" | grep "<Directory /var/www/>")
		if [ ! -z "$containsDef" ]; then
			containsDef=$(cat "$APACHE2Conf" | grep "<Directory /var/www/vhosts>")
			if [ -z "$containsDef" ]; then
				echo -e "<Directory /var/www/vhosts>\n\tOptions -Indexes +FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>" >> "$APACHE2Conf"
			fi
		fi
	fi
}

function restartDaemons(){ # by earnolmartin@gmail.com	
	# Enable php-fpm
	enablePHPFPMService
	
	# Restart MySQL Service
	killAllMySQLAndRestart
	
	# Restart php-fpm
	managePHPFPMService
	
	# Restart apache2 daemon
	manageService "apache2" "restart"
	
	# Restart the EHCP daemon after installation is completed
	manageService "ehcp" "restart"
	
	# Restart PolicyD if available
	if [ "$insPolicyD" = true ]; then
		manageService "policyd_agent" "stop"
		manageService "policyd_agent" "start"
	fi
	
	# Make sure courier daemon is enabled (mainly for Ubuntu 16.04 and up)
	update-rc.d courier-authdaemon enable
	update-rc.d courier-authdaemon defaults
	manageService "courier-authdaemon" "restart"
	
	# Restart postfix
	manageService "postfix" "restart"
}

# Secures BIND and prevents UDP Recursion Attacks:
# https://www.team-cymru.org/Services/Resolvers/instructions.html
# Good explanation FROM MS Forums (LOL):  http://social.technet.microsoft.com/Forums/windowsserver/en-US/24ea1094-0ae4-47b5-9b74-2f77884cce15/dns-recursion?forum=winserverNIS
function disableRecursiveBIND(){ # by earnolmartin@gmail.com
	# Get Resolv.conf and do not run this code if nameserver is set to 127.0.0.1
	RESOLVCOUNT=$(cat "/etc/resolv.conf" | grep -c "nameserver")
	RESOLVLOCAL=$(cat "/etc/resolv.conf" | grep "nameserver 127.0.0.1")
	
	if [ "$RESOLVCOUNT" == "1" ] && [ ! -z "$RESOLVLOCAL" ]; then
		echo -e "Skipping Bind Recursion Settings Due to 127.0.0.1 Nameserver"
	else
		bindOptionsFile="/etc/bind/named.conf.options"
		bindBckFile="/etc/bind/named.conf.options_backup"
		if [ -e "$bindOptionsFile" ]; then
			
			# Create a backup of the original
			if [ ! -e "$bindBckFile" ]; then
				cp "$bindOptionsFile" "$bindBckFile"
			fi
			
			# Remove all blank lines at the end of the file:
			# BINDNoEmptyLines=$(sed '/^ *$/d' "$bindOptionsFile")
			# Better code here to strip out ending lines of empty text:   http://stackoverflow.com/questions/7359527/removing-trailing-starting-newlines-with-sed-awk-tr-and-friends
			# Can also do this for leading and trailing empty lines:  sed -e :a -e '/./,$!d;/^\n*$/{$d;N;};/\n$/ba' file
			BINDNoEmptyLines=$(sed -e :a -e '/^\n*$/{$d;N;};/\n$/ba' "$bindOptionsFile")
			BINDNoEmptyLines=$(trim "$BINDNoEmptyLines")
			echo "$BINDNoEmptyLines" > "$bindOptionsFile"
		
			# Add recursion no
			RecursiveSettingCheck=$( cat "$bindOptionsFile" | grep -o "^recursion .*" | grep -o " .*$" | grep -o "[^ ].*" )
			if [ -z "$RecursiveSettingCheck" ]; then
				# Put it one line before close pattern
				sed -i '$i \recursion no;' "$bindOptionsFile"
			else
				sed -i 's/^recursion .*/recursion no;/g' "$bindOptionsFile"
			fi
			
			# Add additional-from-cache no
			RecursiveCacheCheck=$( cat "$bindOptionsFile" | grep -o "^additional-from-cache .*" | grep -o " .*$" | grep -o "[^ ].*" )
			if [ -z "$RecursiveCacheCheck" ]; then
				sed -i '$i \additional-from-cache no;' "$bindOptionsFile"
			else
				sed -i 's/^additional-from-cache .*/additional-from-cache no;/g' "$bindOptionsFile"
			fi
		fi
		
		# Extra optional step
		#if [ -e "/etc/default/bind9" ]; then
		#	sed -i 's/^RESOLVCONF=.*/RESOLVCONF=no/g' "/etc/default/bind9"
		#fi
		
		manageService "bind9" "restart"
	fi
}

function killAllMySQLAndRestart(){
	# Stop service
	manageService "mysql" "stop"
		
	# Get each PID of mysqld and kill it --- random bug occurs sometimes after install
	ps -ef | grep mysqld | while read mysqlProcess ; do kill -9  $(echo $mysqlProcess | awk '{ print $2 }') ; done
		
	# Restart the service
	manageService "mysql" "start"
}

function removeInstallSilently(){
	if [ -e "/var/www/new/ehcp/install_silently.php" ]; then
		rm "/var/www/new/ehcp/install_silently.php"
	fi
	
	if [ -e "/var/www/new/ehcp/admin_email.php" ]; then
		rm "/var/www/new/ehcp/admin_email.php"
	fi
}

function installExtras(){
	
	if [ "$installmode" == "extra" ]; then
		if [ -z "$unattended" ]; then
			# Attended install, so ask questions
			# Spam Assassin
			echo ""
			echo -n "Install Amavis, SpamAssassin, and ClamAV? [y/n]: "
			read insMode
			
			insMode=$(echo "$insMode" | awk '{print tolower($0)}')
			
			if [ "$insMode" != "n" ]; then
				installAntiSpam
			fi
			
			echo ""
			echo -n "Install fail2ban? [y/n]: "
			read insMode
			
			insMode=$(echo "$insMode" | awk '{print tolower($0)}')
			
			if [ "$insMode" != "n" ]; then
				echo -e "Installing Fail2Ban\n"
				# Install Fail2Ban
				fail2ban
			fi
			
			echo ""
			echo -n "Install Apache2 security modules (mod-security and mod-evasive)? [y/n]: "
			read insMode
			
			insMode=$(echo "$insMode" | awk '{print tolower($0)}')
			
			if [ "$insMode" != "n" ]; then
				echo -e "Installing Apache2 Security Modules\n"
				# Install Apache2 Security Modules
				apacheSecurity
			fi
		else
			# Unattended mode... install them all by default
			installAntiSpam
			fail2ban
			apacheSecurity
		fi
	fi
}

function apacheSecurity(){
	aptgetInstall libapache2-mod-evasive
	aptgetInstall libapache-mod-security
	aptgetInstall libapache2-modsecurity
	aptgetInstall libapache2-mod-security2
	
	# Make sure the conf.d directory exists
	addConfDFolder

	# Shouldn't be running, but attempt to stop it anyways just in case
	manageService "apache2" "stop"
	
	# Ensures that we always get the latest security rules and apply only the latest
	if [ -e "/etc/apache2/mod_security_rules" ]; then
		rm -R "/etc/apache2/mod_security_rules"
	fi
	
	mkdir /etc/apache2/mod_security_rules
	cd "$patchDir"
	
	if [ -e "mod_security_rules_latest" ]; then
		rm -R "mod_security_rules_latest"
	fi

	mkdir "mod_security_rules_latest"
	
	# Different rules based on different versions
	if [[ "$distro" == "ubuntu" && "$yrelease" == "13" && "$mrelease" == "10" ]] || [[ "$yrelease" -ge "14" && "$distro" == "ubuntu" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
		cp "$FIXDIR/apache2/modsecurity/mod_security_rules_13.10.tar.gz" "mod_security_rules.tar.gz"
	else
		cp "$FIXDIR/apache2/modsecurity/mod_security_base_rules.tar.gz" "mod_security_rules.tar.gz"
	fi
	
	tar -zxvf "mod_security_rules.tar.gz" -C "mod_security_rules_latest"
	mv mod_security_rules_latest/* /etc/apache2/mod_security_rules
	chown -R root:root /etc/apache2/mod_security_rules

	# Config file names
	ModSecureNewConfFile="security2.conf"
	ModSecureNewLoadFile="security2.load"
	ModEvasiveNewConfFile="evasive.conf"
	ModEvasiveNewLoadFile="evasive.load"
	
	ModSecureOldConfFile="mod-security.conf"
	ModSecureOldLoadFile="mod-security.load"
	ModEvasiveOldConfFile="mod-evasive.conf"
	ModEvasiveOldLoadFile="mod-evasive.load"
	
	
	# Cleanup mod security
	if [ -e "/etc/apache2/mods-available/${ModSecureNewConfFile}" ]; then
		# Overwrite official config in use
		cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-available/${ModSecureNewConfFile}"
		
		# If it's enabled file also exists, copy that too
		if [ -e "/etc/apache2/mods-enabled/${ModSecureNewConfFile}" ]; then
			cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-enabled/${ModSecureNewConfFile}"
		fi
		
		# Cleanup old config it exists from an OS upgrade
		if [ -e "/etc/apache2/mods-available/${ModSecureOldConfFile}" ]; then
			rm "/etc/apache2/mods-available/${ModSecureOldConfFile}"
		fi
		
		rm "/etc/apache2/mods-enabled/${ModSecureOldConfFile}"
		
		# Remove old load file as well
		if [ -e "/etc/apache2/mods-available/${ModSecureOldLoadFile}" ]; then
			rm "/etc/apache2/mods-available/${ModSecureOldLoadFile}"
		fi
		
		rm "/etc/apache2/mods-enabled/${ModSecureOldLoadFile}"
		
		# Cleanup our old version which is no longer needed since we'll use the official configuration
		if [ -e "/etc/apache2/conf.d/modsecure" ]; then
			rm "/etc/apache2/conf.d/modsecure"
		fi
	elif [ -e "/etc/apache2/mods-available/${ModSecureOldConfFile}" ]; then
		# Overwrite official config in use
		cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-available/${ModSecureOldConfFile}"
		
		# If it's enabled file also exists, copy that too
		if [ -e "/etc/apache2/mods-enabled/${ModSecureOldConfFile}" ]; then
			cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-enabled/${ModSecureOldConfFile}"
		fi
		
		# Remove incorrect config file if it exists
		if [ -e "/etc/apache2/mods-available/${ModSecureNewConfFile}" ]; then
			rm "/etc/apache2/mods-available/${ModSecureNewConfFile}"
		fi
		
		if [ -e "/etc/apache2/mods-available/${ModSecureNewLoadFile}" ]; then
			rm "/etc/apache2/mods-available/${ModSecureNewLoadFile}"
		fi
		
		rm "/etc/apache2/mods-enabled/${ModSecureNewConfFile}"
		rm "/etc/apache2/mods-enabled/${ModSecureNewLoadFile}"
		
		# Cleanup our old version which is no longer needed since we'll use the official configuration
		if [ -e "/etc/apache2/conf.d/modsecure" ]; then
			rm "/etc/apache2/conf.d/modsecure"
		fi
	else
		# None exist
		if [[ "$distro" == "ubuntu" && "$yrelease" -gt "13" ]] || [[ "$distro" == "ubuntu" && "$yrelease" == "13" && "$mrelease" == "10" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
			cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-available/${ModSecureNewConfFile}"
			cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-enabled/${ModSecureNewConfFile}"
			rm "/etc/apache2/mods-available/${ModSecureOldConfFile}"
			rm "/etc/apache2/mods-enabled/${ModSecureOldConfFile}"
			rm "/etc/apache2/mods-available/${ModSecureOldLoadFile}"
			rm "/etc/apache2/mods-enabled/${ModSecureOldLoadFile}"			
		else
			cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-available/${ModSecureOldConfFile}"
			cp "/var/www/new/ehcp/mod_secure/modsecure" "/etc/apache2/mods-enabled/${ModSecureOldConfFile}"
			rm "/etc/apache2/mods-available/${ModSecureNewConfFile}"
			rm "/etc/apache2/mods-enabled/${ModSecureNewConfFile}"
			rm "/etc/apache2/mods-available/${ModSecureNewLoadFile}"
			rm "/etc/apache2/mods-enabled/${ModSecureNewLoadFile}"	
		fi
		
		# Cleanup our old version which is no longer needed since we'll use the official configuration
		if [ -e "/etc/apache2/conf.d/modsecure" ]; then
			rm "/etc/apache2/conf.d/modsecure"
		fi
	fi
	
	# Cleanup mod evasive
	if [ -e "/etc/apache2/mods-available/${ModEvasiveNewConfFile}" ]; then
		# Overwrite official config in use
		cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-available/${ModEvasiveNewConfFile}"
		
		# If it's enabled file also exists, copy that too
		if [ -e "/etc/apache2/mods-enabled/${ModEvasiveNewConfFile}" ]; then
			cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-enabled/${ModEvasiveNewConfFile}"
		fi
		
		# Cleanup old config it exists from an OS upgrade
		if [ -e "/etc/apache2/mods-available/${ModEvasiveOldConfFile}" ]; then
			rm "/etc/apache2/mods-available/${ModEvasiveOldConfFile}"
		fi
		
		rm "/etc/apache2/mods-enabled/${ModEvasiveOldConfFile}"
		
		# Remove old load file as well
		if [ -e "/etc/apache2/mods-available/${ModEvasiveOldLoadFile}" ]; then
			rm "/etc/apache2/mods-available/${ModEvasiveOldLoadFile}"
		fi
		
		rm "/etc/apache2/mods-enabled/${ModEvasiveOldLoadFile}"
		
		# Cleanup our old version which is no longer needed since we'll use the official configuration
		if [ -e "/etc/apache2/conf.d/modevasive" ]; then
			rm "/etc/apache2/conf.d/modevasive"
		fi
	elif [ -e "/etc/apache2/mods-available/${ModEvasiveOldConfFile}" ]; then
		# Overwrite official config in use
		cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-available/${ModEvasiveOldConfFile}"
		
		# If it's enabled file also exists, copy that too
		if [ -e "/etc/apache2/mods-enabled/${ModEvasiveOldConfFile}" ]; then
			cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-enabled/${ModEvasiveOldConfFile}"
		fi
		
		# Remove incorrect config file if it exists
		if [ -e "/etc/apache2/mods-available/${ModEvasiveNewConfFile}" ]; then
			rm "/etc/apache2/mods-available/${ModEvasiveNewConfFile}"
		fi
		
		if [ -e "/etc/apache2/mods-available/${ModEvasiveNewLoadFile}" ]; then
			rm "/etc/apache2/mods-available/${ModEvasiveNewLoadFile}"
		fi
		
		rm "/etc/apache2/mods-enabled/${ModEvasiveNewConfFile}"
		rm "/etc/apache2/mods-enabled/${ModEvasiveNewLoadFile}"
		
		# Cleanup our old version which is no longer needed since we'll use the official configuration
		if [ -e "/etc/apache2/conf.d/modevasive" ]; then
			rm "/etc/apache2/conf.d/modevasive"
		fi
	else
		# None exist
		if [[ "$distro" == "ubuntu" && "$yrelease" -gt "13" ]] || [[ "$distro" == "ubuntu" && "$yrelease" == "13" && "$mrelease" == "10" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
			cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-available/${ModEvasiveNewConfFile}"
			cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-enabled/${ModEvasiveNewConfFile}"
			rm "/etc/apache2/mods-enabled/${ModEvasiveOldConfFile}"
			rm "/etc/apache2/mods-enabled/${ModEvasiveOldLoadFile}"
			rm "/etc/apache2/mods-available/${ModEvasiveOldConfFile}"
			rm "/etc/apache2/mods-available/${ModEvasiveOldLoadFile}"
		else
			cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-available/${ModEvasiveOldConfFile}"
			cp "/var/www/new/ehcp/mod_secure/modevasive" "/etc/apache2/mods-enabled/${ModEvasiveOldConfFile}"
			rm "/etc/apache2/mods-enabled/${ModEvasiveNewConfFile}"
			rm "/etc/apache2/mods-enabled/${ModEvasiveNewLoadFile}"
			rm "/etc/apache2/mods-available/${ModEvasiveNewConfFile}"
			rm "/etc/apache2/mods-available/${ModEvasiveNewLoadFile}"
		fi
		
		# Cleanup our old version which is no longer needed since we'll use the official configuration
		if [ -e "/etc/apache2/conf.d/modevasive" ]; then
			rm "/etc/apache2/conf.d/modevasive"
		fi
	fi

	if [[ "$distro" == "ubuntu" && "$yrelease" -gt "13" ]] || [[ "$distro" == "ubuntu" && "$yrelease" == "13" && "$mrelease" == "10" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
		a2enmod evasive
		a2enmod security2
	else
		a2enmod mod-evasive
		a2enmod mod-security
	fi
}

function fail2ban(){
	aptgetInstall fail2ban
	manageService "fail2ban" "stop"

	cp "/var/www/new/ehcp/fail2ban/apache-dos.conf" "/etc/fail2ban/filter.d/apache-dos.conf"
	cp "/var/www/new/ehcp/fail2ban/ehcp.conf" "/etc/fail2ban/filter.d/ehcp.conf"
	cp "/var/www/new/ehcp/fail2ban/postfix-sasl.conf" "/etc/fail2ban/filter.d/postfix-sasl.conf"

	if [ ! -e "/etc/fail2ban/jail.local" ]; then
		cp "/var/www/new/ehcp/fail2ban/jail.local" "/etc/fail2ban/jail.local"
	fi

	EHCPINF2BAN=$(cat /etc/fail2ban/jail.local | grep "\[ehcp\]")
	APACHEDOSINF2BAN=$(cat /etc/fail2ban/jail.local | grep "\[apache-dos\]")
	POSTFIXSASLINF2BAN=$(cat /etc/fail2ban/jail.local | grep "\[sasl\]")

	if [ -z "$EHCPINF2BAN" ]; then
	   echo "
[ehcp]
# fail2ban section for Easy Hosting Control Panel, ehcp.net
enabled = true
port = http,https
filter = ehcp
logpath = /var/www/new/ehcp/log/ehcp_failed_authentication.log
maxretry = 10" >> "/etc/fail2ban/jail.local"
	fi

	if [ -z "$APACHEDOSINF2BAN" ]; then
	   echo "
[apache-dos]
# Apache Anti-DDoS Security Based Log Entries from Mod Evasive Apache Module
enabled = true
port = http,https
filter = apache-dos
logpath = /var/log/apache*/*error.log
maxretry = 5" >> "/etc/fail2ban/jail.local"
	fi
	
	if [ -z "$POSTFIXSASLINF2BAN" ]; then
		echo "
[sasl]
enabled  = true
port	 = smtp,ssmtp,imap2,imap3,imaps,pop3,pop3s
filter   = postfix-sasl
# You might consider monitoring /var/log/warn.log instead
# if you are running postfix. See http://bugs.debian.org/507990
logpath  = /var/log/mail.log
maxretry = 4" >> "/etc/fail2ban/jail.local"
	fi

	manageService "fail2ban" "restart"
}

function installAntiSpam(){
	# Postfix must be installed
	CURDIR=$(pwd)	
	ANTISPAMINSTALLED=$(which "spamassassin")
	POSTFIXInstalled=$(which "postfix")
	postFixUserExists=$(grep postfix /etc/passwd)
	if [ ! -z "$POSTFIXInstalled" ] && [ ! -z "$postFixUserExists" ] && [ "$installmode" == "extra" ]; then
		# SpamAssassin is not installed / configured
		# Lets roll
		# Set variables
		SPConfig="/etc/default/spamassassin"
		PHeadChecks="/etc/postfix/header_checks"
		PostFixConf="/etc/postfix/main.cf"
		PostFixMaster="/etc/postfix/master.cf"
		CONTENTFILTER="/etc/amavis/conf.d/15-content_filter_mode"
		SPAMASSASSCONF="/etc/spamassassin/local.cf"
		AMAVISHOST="/etc/amavis/conf.d/05-node_id"
		AMAVISDEBDEFAULTSCONF="/etc/amavis/conf.d/20-debian_defaults"
		AMAVISDOMAINIDCONF="/etc/amavis/conf.d/05-domain_id"
		CLAMAVCONFFILE="/etc/clamav/clamd.conf"
		
		# Install Anti-Spam Software
		aptgetInstall "amavisd-new" "runlevel=1" # amavisd-new install should not start the daemon immediately after installation since we haven't configured our fully qualified domain name of the server yet
		
		# Use FQDN for mail server (used by Amavis) as entered by user earlier
		if [ -z "$FQDNName" ]; then
			# Just replace it with ehcpforce.tk
			sed -i "s/^#\$myhostname.*/\$myhostname = \"ehcpforce.tk\";/g" "$AMAVISHOST"
			sed -i "s#^\$myhostname.*#\$myhostname = \"ehcpforce.tk\";#g" "$AMAVISHOST"
		else
			sed -i "s/^#\$myhostname.*/\$myhostname = \"$FQDNName\";/g" "$AMAVISHOST"
			sed -i "s#^\$myhostname.*#\$myhostname = \"$FQDNName\";#g" "$AMAVISHOST"
		fi
		
		# Install SpamAssassin and Clamav-Daemon
		aptgetInstall "spamassassin clamav-daemon"
		
		# Install individually incase some packages are not found
		aptgetInstall libnet-dns-perl
		aptgetInstall pyzor
		aptgetInstall razor
		aptgetInstall arj
		aptgetInstall bzip2
		aptgetInstall cabextract
		aptgetInstall cpio
		aptgetInstall file
		aptgetInstall gzip
		aptgetInstall lha
		aptgetInstall nomarch
		aptgetInstall pax
		aptgetInstall rar
		aptgetInstall unrar
		aptgetInstall unzip
		aptgetInstall zip
		aptgetInstall zoo
		aptgetInstall unzoo
		aptgetInstall libdbi-perl
		aptgetInstall opendkim
		aptgetInstall postfix-policyd-spf-python
		
		# Only keep going if we have the basic packages installed
		AMAVISINS=$(which amavisd-new)
		SPAMASSASSINS=$(which spamassassin)
				
		if [ ! -z "$AMAVISINS" ] && [ ! -z "$SPAMASSASSINS" ]; then
		
			# Add Users
			adduser clamav amavis
			adduser amavis clamav
			
			# Enable SpamAssassin
			if [ -e "$SPConfig" ]; then
				sed -i "s#ENABLED=.*#ENABLED=1#g" "$SPConfig"
				sed -i "s#CRON=.*#CRON=1#g" "$SPConfig"
				
				# More settings
				if [ -e "$SPAMASSASSCONF" ]; then
					# Rewrite the header
					sed -i "s/#rewrite_header.*/rewrite_header Subject \*\*\*\*\*SPAM\*\*\*\*\*/g" "$SPAMASSASSCONF"
					sed -i "s/# rewrite_header.*/rewrite_header Subject \*\*\*\*\*SPAM\*\*\*\*\*/g" "$SPAMASSASSCONF"
					sed -i "s#rewrite_header.*#rewrite_header Subject \*\*\*\*\*SPAM\*\*\*\*\*#g" "$SPAMASSASSCONF"
					
					# Set the spam score
					sed -i "s/#required_score.*/required_score 12.0/g" "$SPAMASSASSCONF"
					sed -i "s/# required_score.*/required_score 12.0/g" "$SPAMASSASSCONF"
					sed -i "s#required_score.*#required_score 12.0#g" "$SPAMASSASSCONF"
						
					# use bayes 1
					sed -i "s/#use_bayes.*/use_bayes 1/g" "$SPAMASSASSCONF"
					sed -i "s/# use_bayes.*/use_bayes 1/g" "$SPAMASSASSCONF"
					sed -i "s#use_bayes.*#use_bayes 1#g" "$SPAMASSASSCONF"
						
					# use bayes auto learn
					sed -i "s/#bayes_auto_learn.*/bayes_auto_learn 1/g" "$SPAMASSASSCONF"
					sed -i "s/# bayes_auto_learn.*/bayes_auto_learn 1/g" "$SPAMASSASSCONF"
					sed -i "s#bayes_auto_learn.*#bayes_auto_learn 1#g" "$SPAMASSASSCONF"
						
				fi
					
				manageService "spamassassin" "restart"
			fi
				
			# Integrate into postfix
			postconf -e "content_filter = smtp-amavis:[127.0.0.1]:10024"
				
			echo "use strict;

# You can modify this file to re-enable SPAM checking through spamassassin
# and to re-enable antivirus checking.

#
# Default antivirus checking mode
# Uncomment the two lines below to enable it
#

@bypass_virus_checks_maps = (
	\%bypass_virus_checks, \@bypass_virus_checks_acl, \$bypass_virus_checks_re);


#
# Default SPAM checking mode
# Uncomment the two lines below to enable it
#

@bypass_spam_checks_maps = (
	\%bypass_spam_checks, \@bypass_spam_checks_acl, \$bypass_spam_checks_re);

1;  # insure a defined return" > "$CONTENTFILTER"
			if [ -e "$PostFixMaster" ]; then
				POSTFIXMASCHECK1=$(cat "$PostFixMaster" | grep "smtp-amavis")
				if [ -z "$POSTFIXMASCHECK1" ]; then
						echo "
smtp-amavis     unix    -       -       -       -       2       smtp
		-o smtp_data_done_timeout=1200
		-o smtp_send_xforward_command=yes
		-o disable_dns_lookups=yes
		-o max_use=20" >> "$PostFixMaster"
				fi
					
				POSTFIXMASCHECK2=$(cat "$PostFixMaster" | grep "127.0.0.1:10025")
				if [ -z "$POSTFIXMASCHECK2" ]; then
					echo "
127.0.0.1:10025 inet    n       -       -       -       -       smtpd
		-o content_filter=
		-o local_recipient_maps=
		-o relay_recipient_maps=
		-o smtpd_restriction_classes=
		-o smtpd_delay_reject=no
		-o smtpd_client_restrictions=permit_mynetworks,reject
		-o smtpd_helo_restrictions=
		-o smtpd_sender_restrictions=
		-o smtpd_recipient_restrictions=permit_mynetworks,reject
		-o smtpd_data_restrictions=reject_unauth_pipelining
		-o smtpd_end_of_data_restrictions=
		-o mynetworks=127.0.0.0/8
		-o smtpd_error_sleep_time=0
		-o smtpd_soft_error_limit=1001
		-o smtpd_hard_error_limit=1000
		-o smtpd_client_connection_count_limit=0
		-o smtpd_client_connection_rate_limit=0
		-o receive_override_options=no_header_body_checks,no_unknown_recipient_checks" >> "$PostFixMaster"
				fi
		
			fi
		
			#http://stackoverflow.com/questions/11694980/using-sed-insert-a-line-below-or-above-the-pattern
			POSTFIXMASCHECK3=$(cat "$PostFixMaster" | grep -A2 "pickup" | grep -v "pickup" | grep -o "\-o receive_override_options=no_header_body_checks$")
			if [ -z "$POSTFIXMASCHECK3" ]; then
				sed -i "/pickup.*/a\\\t-o receive_override_options=no_header_body_checks" "$PostFixMaster"
			fi
				
			POSTFIXMASCHECK4=$(cat "$PostFixMaster" | grep -A2 'pickup' | grep -v "pickup" | grep -o "\-o content_filter=$")
			if [ -z "$POSTFIXMASCHECK4" ]; then
				sed -i "/pickup.*/a\\\t-o content_filter=" "$PostFixMaster"
			fi
			
			# Change settings for amavis deb defaults
			if [ -e "$AMAVISDEBDEFAULTSCONF" ]; then
				# Set spam scores higher for a few levels
				
				# Change $sa_kill_level_deflt to 8.01
				sed -i "s/^#\$sa_kill_level_deflt.*/\$sa_kill_level_deflt = 8.01; # triggers spam evasive actions/g" "$AMAVISDEBDEFAULTSCONF"
				sed -i "s/^# \$sa_kill_level_deflt.*/\$sa_kill_level_deflt = 8.01; # triggers spam evasive actions/g" "$AMAVISDEBDEFAULTSCONF"
				sed -i "s/^\$sa_kill_level_deflt.*/\$sa_kill_level_deflt = 8.01; # triggers spam evasive actions/g" "$AMAVISDEBDEFAULTSCONF"
				
				# Change $sa_dsn_cutoff_level to 11
				sed -i "s/^#\$sa_dsn_cutoff_level.*/\$sa_dsn_cutoff_level = 11; # spam level beyond which a DSN is not sent/g" "$AMAVISDEBDEFAULTSCONF"
				sed -i "s/^# \$sa_dsn_cutoff_level.*/\$sa_dsn_cutoff_level = 11; # spam level beyond which a DSN is not sent/g" "$AMAVISDEBDEFAULTSCONF"
				sed -i "s/^\$sa_dsn_cutoff_level.*/\$sa_dsn_cutoff_level = 11; # spam level beyond which a DSN is not sent/g" "$AMAVISDEBDEFAULTSCONF"
				
				# Check to see if we have the rewrite subject line in the configuration file
				amavisHasSubjRewrite=$(cat "$AMAVISDEBDEFAULTSCONF" | grep '$sa_spam_modifies_subj')
				if [ -z "$amavisHasSubjRewrite" ]; then
					sed -i "/^\$sa_spam_subject_tag.*/a\$sa_spam_modifies_subj = 1;" "$AMAVISDEBDEFAULTSCONF"
				else
					sed -i "s/^\$sa_spam_modifies_subj.*/\$sa_spam_modifies_subj = 1;/g" "$AMAVISDEBDEFAULTSCONF"
				fi
			fi
			
			# Allow for any domain configuration
			if [ -e "$AMAVISDOMAINIDCONF" ]; then
				# Check to see if we have the rewrite subject line in the configuration file
				amavisHasLocalDomainMaps=$(cat "$AMAVISDOMAINIDCONF" | grep '@local_domains_maps')
				if [ -z "$amavisHasLocalDomainMaps" ]; then
					sed -i "/^@local_domains_acl.*/a@local_domains_maps = \( \[\"\.\"\] \);" "$AMAVISDOMAINIDCONF"
				else
					sed -i "s/^@local_domains_maps.*/@local_domains_maps = \( \[\"\.\"\] \);/g" "$AMAVISDOMAINIDCONF"
				fi
			fi
			
			# Needed in Ubuntu 16.04
			# Allow supplementary groups to fix permissions
			if [ -e "$CLAMAVCONFFILE" ]; then
				sed -i "s/^AllowSupplementaryGroups.*/AllowSupplementaryGroups true/g" "$CLAMAVCONFFILE"
			fi
			
			# Install and configure policyd
			if [ "$insPolicyD" = true ]; then
				installPolicyD
			fi
			
			# Should be good to go?
				
			# Restart Amavis
			manageService "amavis" "restart"
				
			# Restart services
			manageService "postfix" "restart"
			
			# Get lastest clamav definitions
			freshclam
			
			# Restart clamav-daemon
			manageService "clamav-daemon" "restart"	
					
		fi
	fi
}

function fixPHPFPMListen(){
	FPMConfFile="$PHPCONFDir/fpm/pool.d/www.conf"
	if [ -e "$FPMConfFile" ]; then
		sed -i "s#^listen =.*#listen = 9000#g" "$FPMConfFile"
	fi
}

function removeApacheUmask(){
	APACHE2ENVVARS="/etc/apache2/envvars"
	if [ -e "$APACHE2ENVVARS" ]; then
		sed -i "s/^umask/#umask/g" "$APACHE2ENVVARS"
	fi
}

function securePHPMyAdminConfiguration(){
	# Variables
	PHPMYADMINMAINCONFFILE="/etc/phpmyadmin/config.inc.php"
	PHPMYADMINTMPDIR="/var/lib/phpmyadmin/tmp"
	PHPMYADMINCONFFILE="/usr/share/phpmyadmin/config.inc.php"
	PHPMYADMINROOTWL="/usr/share/phpmyadmin/rootip_whitelist.php"
	PHPMYADMINROOTWLFunctions="/usr/share/phpmyadmin/rootip_whitelist_functions.php"
	PHPMYADMINTEMPUPDIR="/usr/share/phpmyadmin/upload"
	WHITELISTEMPLATEFILE="/var/www/new/ehcp/etc/phpmyadmin/rootip_whitelist.php"
	WHITELISTEMPLATEFILEFunctions="/var/www/new/ehcp/etc/phpmyadmin/rootip_whitelist_functions.php"
	DEFAULTPHPMYADMINCONFTEMPLATE="/var/www/new/ehcp/etc/phpmyadmin/phpmyadmin_default_conf"
	DEFAULTPHPMYADMINMAINCONFTEMPLATE="/var/www/new/ehcp/etc/phpmyadmin/phpmyadmin_default_main_conf"
	
	# Fix perms on temporary directory that may be used for file uploads (importing exported sql files for example):
	if [ -e "$PHPMYADMINTMPDIR" ]; then
		chown -R ${VSFTPDUser}:www-data "$PHPMYADMINTMPDIR"
	fi
	
	# See if PHPMyAdmin config.inc.php exists and secure its configuration if it does
	if [ -e "$PHPMYADMINMAINCONFFILE" ]; then
		phpMyAdminConfigurationTweaks "$PHPMYADMINMAINCONFFILE"
	else
		# Copy template over to have a valid phpmyadmin config
		cp "$DEFAULTPHPMYADMINMAINCONFTEMPLATE" "$PHPMYADMINMAINCONFFILE"
	fi
	
	if [ -e "$PHPMYADMINCONFFILE" ]; then
		phpMyAdminConfigurationTweaks "$PHPMYADMINCONFFILE"	
	else
		# Copy template over to have a valid phpmyadmin config
		cp "$DEFAULTPHPMYADMINCONFTEMPLATE" "$PHPMYADMINCONFFILE"
	fi
	
	# Copy root ip address whitelist over to phpmyadmin directory if it doesn't exist
	if [ ! -e "$PHPMYADMINROOTWL" ]; then
		cp "$WHITELISTEMPLATEFILE" "$PHPMYADMINROOTWL"
	fi
	
	# Copy root ip address whitelist functions over to phpmyadmin directory if it doesn't exist
	if [ ! -e "$PHPMYADMINROOTWLFunctions" ]; then
		cp "$WHITELISTEMPLATEFILEFunctions" "$PHPMYADMINROOTWLFunctions"
	fi
	
	# Create temporary upload directory for large SQL database imports
	if [ ! -e "$PHPMYADMINTEMPUPDIR" ]; then
		mkdir -p "$PHPMYADMINTEMPUPDIR"
	fi
	
	# Replace rootip if the function is in it because this had to be separated to avoid errors in some configurations...
	if [ -e "$PHPMYADMINROOTWL" ]; then
		hasFunctionPrivateIP=$(cat "$PHPMYADMINROOTWL" | grep -o "function isPrivateIP")
		if [ ! -z "$hasFunctionPrivateIP" ]; then
			cp "$WHITELISTEMPLATEFILE" "$PHPMYADMINROOTWL"
		fi
	fi
	
	chown ${VSFTPDUser}:www-data -R $PHPMYADMINTEMPUPDIR
	chmod 775 -R $PHPMYADMINTEMPUPDIR
	
	# Required in Ubuntu 16.04 and up
	aptgetInstall "php-gettext"	
}

function phpMyAdminConfigurationTweaks(){
	if [ ! -z "$1" ]; then
		# Config file to modify = $1
		phpmyadminCONFToModify="$1"
	
		# Run checks for various extra config settings
		hasNoRootCheck=$(cat "$phpmyadminCONFToModify" | grep -o "$cfg\['Servers'\]\[1\]\['AllowRoot'\]")
		hasTmpUploadDir=$(cat "$phpmyadminCONFToModify" | grep -o "$cfg\['UploadDir'\]")
		hasIncludeApprovedIPAddressesFunction=$(cat "$phpmyadminCONFToModify" | grep -o "include_once 'rootip_whitelist_functions.php'")
		hasIncludeApprovedIPAddressesFunctionTwo=$(cat "$phpmyadminCONFToModify" | grep -o "include_once '/usr/share/phpmyadmin/rootip_whitelist_functions.php'")
		hasIncludeApprovedIPAddresses=$(cat "$phpmyadminCONFToModify" | grep -o "include 'rootip_whitelist.php'")
		hasIncludeApprovedIPAddressesTwo=$(cat "$phpmyadminCONFToModify" | grep -o "include '/usr/share/phpmyadmin/rootip_whitelist.php'")
		hasPHPCloseTag=$(cat "$phpmyadminCONFToModify" | grep -o "^?>")
		
		# Prevent root mysql user from logging in
		if [ -z "$hasNoRootCheck" ]; then
			if [ -z "$hasIncludeApprovedIPAddresses" ]; then
				# Put it above the close tag if it exists or at the end of the file
				if [ ! -z "$hasPHPCloseTag" ]; then
					sed -i "/^?>/i\$cfg\['Servers'\]\[1\]\['AllowRoot'\] = FALSE;" "$phpmyadminCONFToModify"
				else
					echo -e "\$cfg['Servers'][1]['AllowRoot'] = FALSE;" >> "$phpmyadminCONFToModify"
				fi
			else
				# Put it before the include white_list setting since IP addresses may set it to true
				sed -i "/^include 'rootip_whitelist.php'/i\$cfg\['Servers'\]\[1\]\['AllowRoot'\] = FALSE;" "$phpmyadminCONFToModify"
			fi
		else
			sed -i "s#^\$cfg\['Servers'\]\[1\]\['AllowRoot'\].*#\$cfg\['Servers'\]\[1\]\['AllowRoot'\] = FALSE;#g" "$phpmyadminCONFToModify"
		fi
		
		# Add a temporary upload directory for files
		if [ -z "$hasTmpUploadDir" ]; then
			if [ ! -z "$hasPHPCloseTag" ]; then
				sed -i "/^?>/i\$cfg\['UploadDir'\] = '\./upload';" "$phpmyadminCONFToModify"
			else
				echo -e "\$cfg['UploadDir'] = './upload';" >> "$phpmyadminCONFToModify"
			fi
		else
			sed -i "s#^\$cfg\['UploadDir'\].*#\$cfg\['UploadDir'\] = '\./upload';#g" "$phpmyadminCONFToModify"
		fi
		
		# White listed IP addresses that can login to root account via PHPMyAdmin
		if [ -z "$hasIncludeApprovedIPAddresses" ] && [ -z "$hasIncludeApprovedIPAddressesTwo" ]; then
			if [ ! -z "$hasPHPCloseTag" ]; then
				sed -i "/^?>/iinclude 'rootip_whitelist.php';" "$phpmyadminCONFToModify"
			else
				echo -e "include 'rootip_whitelist.php';" >> "$phpmyadminCONFToModify"
			fi
		fi		
		
		# Make sure rootip_whitelist functions are included
		if [ -z "$hasIncludeApprovedIPAddressesFunction" ] && [ -z "$hasIncludeApprovedIPAddressesFunctionTwo" ]; then
			hasIncludeApprovedIPAddresses=$(cat "$phpmyadminCONFToModify" | grep -o "include 'rootip_whitelist.php'")
			if [ ! -z "$hasIncludeApprovedIPAddresses" ]; then
				sed -i "/^include 'rootip_whitelist.php';/iinclude_once 'rootip_whitelist_functions.php';" "$phpmyadminCONFToModify"
			fi
		fi
		
		# Use shared path for /etc/phpmyadmin/config.inc.php
		# Fixes PHPMyAdmin root login issues in Ubuntu 18.04
		if [ "$phpmyadminCONFToModify" == "/etc/phpmyadmin/config.inc.php" ] && [ -e "$phpmyadminCONFToModify" ]; then
			sed -i "s#^include_once 'rootip_whitelist_functions.php';#include_once '/usr/share/phpmyadmin/rootip_whitelist_functions.php';#g" "$phpmyadminCONFToModify"
			sed -i "s#^include 'rootip_whitelist.php';#include '/usr/share/phpmyadmin/rootip_whitelist.php';#g" "$phpmyadminCONFToModify"
		fi
	fi
}

function fixPHPmcrypt(){
	aptgetInstall php5-mcrypt
	aptgetInstall php-mcrypt
	php5enmod mcrypt
	phpenmod mcrypt
}

function fixPopBeforeSMTP(){
	aptPackageInstallOrManualGet "pop-before-smtp"
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

function configurePHPIni(){
	# Variables
	PHPINIFORAPACHE="$PHPCONFDir/apache2/php.ini"
	PHPINIFORNGINX="$PHPCONFDir/fpm/php.ini"
	
	# Logging
	logToInstallLogFile "Changing PHP settings in the following configuration file: $PHPINIFORAPACHE"
	logToInstallLogFile "Changing PHP settings in the following configuration file: $PHPINIFORNGINX"	
	
	ModifyPHPIniConfigForFile "$PHPINIFORAPACHE"
	ModifyPHPIniConfigForFile "$PHPINIFORNGINX"
	#Opcache seems to have some random php session issues. If it should be disabled in the future, run this:
	#ModifyPHPIniConfigForFile "$PHPINIFORNGINX" "disableOpcache"
	
	# Create /var/www/php_sessions directory to store PHP session files for the default configuration file only
	# All defined vhosts have their own php_sessions directory for permissions sake...
	setupPHPSessionsDir
}

function ModifyPHPIniConfigForFile(){
	# $1 is the php.ini file to modify
	# $2 is additional action such as disble opcache completely
	if [ ! -z "$1" ]; then
		PHPINIFILE="$1"
		if [ -e "$PHPINIFILE" ]; then
			# Turn error displaying on
			sed -i "s#^display_errors.*#display_errors = On#g" "$PHPINIFILE"
					
			# Set upload limit higher to 50MB (We don't live in the 90s anymore...)
			sed -i "s#^upload_max_filesize.*#upload_max_filesize = 50M#g" "$PHPINIFILE"
			
			# Set max post size to 50MB (We don't live in the 90s anymore...)
			sed -i "s#^post_max_size.*#post_max_size = 50M#g" "$PHPINIFILE"
			
			# And for gods sake, please set error reporting so that it doesn't annoy anyone!
			sed -i "s#^error_reporting.*#error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_NOTICE \& \~E_STRICT#g" "$PHPINIFILE"
			
			# Set max execution time higher to 100 seconds
			sed -i "s#^max_execution_time.*#max_execution_time = 100#g" "$PHPINIFILE"
			
			# Configure opcache "properly" for PHP 5.5.x and up 
			hasOpCache=$(cat "$PHPINIFILE" | grep -o "^\[opcache\]")
			if [ ! -z "$hasOpCache" ]; then
				# Correct opcache based on this bug report:  https://bugs.php.net/bug.php?id=68869
				# Timestamps
				sed -i "s#^;opcache.validate_timestamps=.*#opcache.validate_timestamps=1#g" "$PHPINIFILE"
				sed -i "s#^opcache.validate_timestamps=.*#opcache.validate_timestamps=1#g" "$PHPINIFILE"
				
				# Revalidation frequency
				sed -i "s#^;opcache.revalidate_freq=.*#opcache.revalidate_freq=0#g" "$PHPINIFILE"
				sed -i "s#^opcache.revalidate_freq=.*#opcache.revalidate_freq=0#g" "$PHPINIFILE"
				
				if [ ! -z "$2" ] && [ "$2" == "disableOpcache" ]; then
					# Disable it completely
					sed -i "s#^;opcache.enable=.*#opcache.enable=0#g" "$PHPINIFILE"
					sed -i "s#^opcache.enable=.*#opcache.enable=0#g" "$PHPINIFILE"
				fi
			fi
		else
			# Logging
			logToInstallLogFile "$PHPINIFILE does not exist!"
		fi
	fi
}

function fixSASLAUTH(){
	# Fix SASLAUTH CACHE and limit to no threads for better performance
	if [ -e "/etc/default/saslauthd" ]; then
		backupFile "/etc/default/saslauthd"
		echo "NAME=\"saslauthd\"
START=yes
MECHANISMS=\"pam\"
PARAMS=\"-n 0 -m /var/spool/postfix/var/run/saslauthd -r\"
OPTIONS=\"-n 0 -m /var/spool/postfix/var/run/saslauthd -r\"
" > "/etc/default/saslauthd"
		
		# restart the service
		manageService "saslauthd" "restart"
	fi
}

function backupFile(){
	# Backups up a file in the general backup directory.
	# Params:
	# $1 = filepath to backup
	
	genBackupDIR="/root/Backup/general"
	if [ ! -e "$genBackupDIR" ]; then
		mkdir -p "$genBackupDIR"
	fi
	
	if [ ! -z "$1" ]; then
		FileToBackup="$1"
		if [ -e "$FileToBackup" ]; then
			nameOfFileToBeBackedUp=$(basename "$FileToBackup")
			dirOfFileToBeBackedUp=$(dirname "$FileToBackup") 
			# Replace "/" with underscores so that the directory where the file should 
			# be if the user wishes to restore the file is in the file name of the backup
			fileStructure=$(echo "$dirOfFileToBeBackedUp" | tr "/" "_")
			# Get first character that isn't underscore
			fileStructure=$(echo "$fileStructure" | cut -c 2-)
			nowDate=$(date +"%m_%d_%Y_%H%M%S")
			backupFullPath="${genBackupDIR}/${fileStructure}_${nameOfFileToBeBackedUp}_${nowDate}"
			cp "$FileToBackup" "$backupFullPath"
		fi
	fi
}

function postInstallInformation(){
	echo "Thank you for installing the FoRcE Edition (a fork) of EHCP.

The following ports are used by EHCP and must be open:
20,21,22,25,53,80,110,143 (tcp+udp)

Please visit our website for updates, support, and community additions.
http://ehcpforce.tk

Your web hosting control panel can be accessed via http://yourip/

Enjoy!"

	if [ -e "$logInfoFile" ]; then
		echo -e "\nEHCP Various Account Credentials:\n-----------------------------------"
		CREDS=$(cat "$logInfoFile")
		echo -e "$CREDS"
		echo -e "\nThe above information is also stored in $logInfoFile for future reference."
	fi
}

function installNeededDependencies(){
	aptgetInstall "gdebi-core"
	aptgetInstall python-software-properties # for add-apt-repository
	
	# Work around for Ubuntu 16.04
	if [ ! -z "$unattended" ]; then
		echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections
		echo "postfix postfix/mailname string $HOSTNAME" | debconf-set-selections
	fi

	# apt-get upgrade  # may be cancelled later... this may be dangerous... server owner should do it manually...
	aptgetInstall php
	aptgetInstall php-mysql
	aptgetInstall php-cli
	aptgetInstall php5
	aptgetInstall php5-mysql 
	aptgetInstall php5-cli 
	aptgetInstall sudo
	aptgetInstall wget
	aptgetInstall aptitude
	aptgetInstall ftp
	aptgetInstall php-mail-mimedecode
	aptgetInstall php7.0-zip
	aptgetInstall php5-gd
	aptgetInstall php7.0-gd
	aptgetInstall php5-zip
	
	# Required for Certbot
	aptgetInstall python
	aptgetInstall libexpat1-dev
	aptgetInstall libpython-dev
	aptgetInstall libpython2.7-dev
	aptgetInstall python-setuptools
	aptgetInstall python2.7-dev
	aptgetInstall libssl-doc
	aptgetInstall openssl gcc build-essential
	aptgetInstall python-pip
	aptgetInstall augeas-tools
	aptgetInstall libffi-dev 
	aptgetInstall libssl-dev 
	aptgetInstall python-dev 
	aptgetInstall python-virtualenvs

	# This is needed to provide a default answer for configuring certain packages such as mysql and phpmyadmin 
	if [ ! -z "$unattended" ]; then
		aptgetInstall debconf-utils
	fi
	
	# Install sshpass used for scp copying
	aptgetInstall sshpass
	
	# Needed for Ubuntu 14 and up:
	aptgetInstall apache2-utils
}

function getRidOfExtraPHPMyAdminAlias(){
	# Remove extra phpmyadmin.conf file which may have been used for older versions
	if [ -e "/etc/apache2/conf-enabled/phpmyadmin.conf" ] && [ -e "/etc/apache2/conf.d/phpmyadmin.conf" ]; then
		rm "/etc/apache2/conf.d/phpmyadmin.conf"
	fi
}

function turnOffIPv6PostFix(){
	PostFixConfFile="/etc/postfix/main.cf"
	if [ -e "$PostFixConfFile" ]; then
		ProtocolsSetting=$(cat "$PostFixConfFile" | grep -o "^inet_protocols.*")
		CurrentProtocolSetting=$(echo "$ProtocolsSetting" | grep -o "=.*" | grep -o "[^= ].*")
		if [ ! -z "$ProtocolsSetting" ]; then
			sed -i 's/^inet_protocols.*/inet_protocols = ipv4/g' "$PostFixConfFile"
		fi
	fi
}

function manageService(){ # by earnolmartin@gmail.com
	# Linux is introducing systemd booting, so now I gotta handle craziness
	# $1 is the service name
	# $2 is the service action
	service "$1" "$2"
}

function getVSFTPDUser(){
	vsftpdUserExists=$(cat "/etc/passwd" | grep "vsftpd")
	if [ -z "$vsftpdUserExists" ]; then
		VSFTPDUser="ftp"
	else
		VSFTPDUser="vsftpd"
	fi
}

function setupPHPSessionsDir(){
	PHPSessDir="/var/www/php_sessions"
	mkdir -p "$PHPSessDir"
	chown -R ${VSFTPDUser}:www-data "$PHPSessDir"
}

function stopServices(){
	# Stop services
	manageService "ehcp" "stop"
	manageService "apache2" "stop"
	managePHPFPMService "stop"
	manageService "nginx" "stop"
}

function fixRoundCubeFileAttachments(){
	roundCubeConfigFile="/etc/roundcube/main.inc.php"
	if [ -e "$roundCubeConfigFile" ]; then
		sed -i "s/^\$rcmail_config\['temp_dir'\].*/\$rcmail_config\['temp_dir'\] = '\/tmp';/g" "$roundCubeConfigFile"
		hasRCTempDirSetting=$(cat "$roundCubeConfigFile" | grep -o "\$rcmail_config\['temp_dir'\].*")
		if [ -z "$hasRCTempDirSetting" ]; then
			echo "\$rcmail_config['temp_dir'] = '/tmp';" >> "$roundCubeConfigFile"
		fi
	fi
}

function setDefaultRoundCubeServer(){
	if [ -e "/etc/roundcube/config.inc.php" ]; then
		sed -i "s/^\$config\['default_host'\].*/\$config\['default_host'\] = 'localhost';/g" "/etc/roundcube/config.inc.php"
	fi
}

function installPolicyD(){
	if [[ "$distro" == "ubuntu" && "$yrelease" -ge "14" ]] || [[ "$distro" == "debian" && "$yrelease" -ge "8" ]]; then
		# Create policyd mysql database under ehcp mysql user and populate it with policyd sql
		curDir=$(pwd)
		
		# install prereqs
		aptgetInstall libconfig-inifiles-perl
		aptgetInstall libcache-fastmmap-perl
		
		#create policyd users
		policyDUserCheck=$(cat /etc/passwd | grep "cbpolicyd")
		if [ -z "$policyDUserCheck" ]; then
			groupadd cbpolicyd
			useradd -M cbpolicyd -g cbpolicyd -s /usr/sbin/nologin
			passwd -d cbpolicyd
		fi
		
		# Create needed directories and log files:
		mkdir -p "/var/log/cbpolicyd"
		touch "/var/log/cbpolicyd/cbpolicyd.log"
		chown cbpolicyd:cbpolicyd -R "/var/log/cbpolicyd"
		mkdir -p "/var/run/cbpolicyd"
		chown cbpolicyd:cbpolicyd -R "/var/run/cbpolicyd"
		
		# Generate password for policyd mysql user
		generatePassword
		policyDMySQLUser="ehcp_policyd"
		policyDMySQLPass="$rPass"
		
		# Create the database with the username and password and populate it with the policyd mysql
		cd "$patchDir"
		cp "$FIXDIR/api/create_mysql_db_user.tar.gz" "create_mysql_db_user.tar.gz"
		tar -zxvf "create_mysql_db_user.tar.gz"
		php -f create_mysql_db_user.php "policyd" "$policyDMySQLUser" "$policyDMySQLPass" "$FIXDIR/policyd/policyd.mysql"
		
		# No longer used, but might be used one day for something else... so leaving here just in-case
		# Crude but works...
		#EHCPMySQLPass=$(cat "/var/www/new/ehcp/config.php" | grep -o "dbpass=.*" | grep -o "=.*" | grep -o "[^='].*" | grep -o ".*[^';]")
		#EHCPMySQLUser=$(cat "/var/www/new/ehcp/config.php" | grep -o "dbusername=.*" | grep -o "=.*" | grep -o "[^='].*" | grep -o ".*[^';]")
		
		logToInstallLogFile "Replacing PolicyD MySQL Information in cluebringer.conf"
		
		# Copy the main config file to this directory, replace some variables, and then move it to /etc/
		cp "$FIXDIR/policyd/cluebringer.conf" ./
		sed -i "s/{ehcpusername}/$policyDMySQLUser/g" "cluebringer.conf"
		sed -i "s/{ehcppass}/$policyDMySQLPass/g" "cluebringer.conf"
		mkdir -p "/etc/cbpolicyd"
		mv "cluebringer.conf" "/etc/cbpolicyd/cluebringer.conf"
		
		# Create the proper directories
		mkdir -p "/usr/local/lib/cbpolicyd-2.1"
		cp -r "$FIXDIR/policyd/cbp" "/usr/local/lib/cbpolicyd-2.1/"
		cp -r "$FIXDIR/policyd/awitpt/awitpt" "/usr/local/lib/cbpolicyd-2.1/"
		
		# Copy script files for policyD
		cp "$FIXDIR/policyd/cbpadmin" "/usr/local/bin/"
		cp "$FIXDIR/policyd/cbpolicyd" "/usr/local/sbin/"
		cp "$FIXDIR/policyd/run_policyd_async.sh" "/etc/cbpolicyd"
		
		# Replace vars in async script
		sed -i "s/{policyDMySQLUser}/$policyDMySQLUser/g" "/etc/cbpolicyd/run_policyd_async.sh"
		sed -i "s/{policyDMySQLPass}/$policyDMySQLPass/g" "/etc/cbpolicyd/run_policyd_async.sh"
		
		# Append configuration to main.cf postfix settings
		CurRecipRestrictions=$(cat "/etc/postfix/main.cf" | grep -o "smtpd_recipient_restrictions\( \)*=.*" | grep -o "=.*" | grep -o "[^=\( \)*].*")
		if [ ! -z "$CurRecipRestrictions" ]; then
			lastCharInRestriction="${CurRecipRestrictions: -1}"
			if [ "$lastCharInRestriction" == "," ]; then
				CurRecipRestrictions="${CurRecipRestrictions: : -1}"
			fi
		fi
		
		CurEndOfDataRestrictions=$(cat "/etc/postfix/main.cf" | grep -o "smtpd_end_of_data_restrictions\( \)*=.*" | grep -o "=.*" | grep -o "[^=\( \)*].*")
		if [ ! -z "$CurEndOfDataRestrictions" ]; then
			lastCharInRestriction="${CurEndOfDataRestrictions: -1}"
			if [ "$lastCharInRestriction" == "," ]; then
				CurEndOfDataRestrictions="${CurEndOfDataRestrictions: : -1}"
			fi
		fi
		
		hasPolicyDRecipRestr=$(echo $CurRecipRestrictions | grep -o "check_policy_service inet:127.0.0.1:10031")
		hasPolicyDEndOfData=$(echo $CurEndOfDataRestrictions | grep -o "check_policy_service inet:127.0.0.1:10031")
		if [ -z "$hasPolicyDRecipRestr" ]; then
			logToInstallLogFile "Adding PolicyD SMTP Recipient Restriction and then Appending Original Entries"
			sed -i "s#smtpd_recipient_restrictions\( \)*=.*#smtpd_recipient_restrictions = check_policy_service inet:127.0.0.1:10031,$CurRecipRestrictions#g" "/etc/postfix/main.cf"
		fi
		if [ -z "$hasPolicyDEndOfData" ]; then
			hasSMTPDEndLine=$(cat "/etc/postfix/main.cf" | grep -o "smtpd_end_of_data_restrictions")
			if [ ! -z "$hasSMTPDEndLine" ]; then
				logToInstallLogFile "Adding PolicyD SMTP End of Data Restriction and then Appending Original Entries"
				sed -i "s#smtpd_end_of_data_restrictions\( \)*=.*#smtpd_end_of_data_restrictions = check_policy_service inet:127.0.0.1:10031,$CurEndOfDataRestrictions#g" "/etc/postfix/main.cf"
			else
				logToInstallLogFile "Adding SMTP End of Data Restrictions"
				echo "smtpd_end_of_data_restrictions = check_policy_service inet:127.0.0.1:10031" >> "/etc/postfix/main.cf"
			fi
		fi
		
		# Make AMAVISD work with policyD
		mkdir -p "/etc/amavis/plugins"
		cp "$FIXDIR/policyd/amavisd-policyd.pm" ./
		logToInstallLogFile "Updating PolicyD MySQL Information in amavisd-policyd.pm Plugin"
		sed -i "s/{ehcpusername}/$policyDMySQLUser/g" "amavisd-policyd.pm"
		sed -i "s/{ehcppass}/$policyDMySQLPass/g" "amavisd-policyd.pm"
		cp "amavisd-policyd.pm" "/etc/amavis/plugins"
		hasPolicyAmavisInclude=$(cat "/etc/amavis/conf.d/01-debian" | grep -o "include_config_files('/etc/amavis/plugins/amavisd-policyd.pm');")
		if [ -z "$hasPolicyAmavisInclude" ]; then
			echo "include_config_files('/etc/amavis/plugins/amavisd-policyd.pm');" >> "/etc/amavis/conf.d/01-debian"
		fi
		
		# Copy webui to main ehcp folder
		cp -R "$FIXDIR/policyd/webui" "/var/www/new/ehcp/policyd"
		# Fix mysql info in webui policyd folder
		logToInstallLogFile "Updating PolicyD MySQL Information in PolicyD Config.php for Web UI"
		sed -i "s/{ehcpusername}/$policyDMySQLUser/g" "/var/www/new/ehcp/policyd/includes/config.php"
		sed -i "s/{ehcppass}/$policyDMySQLPass/g" "/var/www/new/ehcp/policyd/includes/config.php"
		
		# Copy init.d script to /etc/init.d and start it
		cp "$FIXDIR/policyd/policyd_agent" "/etc/init.d/"
		systemctl daemon-reload
		manageService "policyd_agent" "start"
		
		# Start the service on boot
		update-rc.d policyd_agent defaults
	else
		echo "Failed to install policyd! This feature requires Ubuntu 14.04 and up!";
	fi
}

function removeSystemCronJob(){ # by earnolmartin@gmail.com
	# $1 is the command or script to run that needs to be deleted
	# Example of what $1 should be:  echo hello
	
	if [ ! -z "$1" ]; then
		CURDIR=$(pwd)
		cd "$patchDir"

		# Get the current crontab
		crontab -l > mycron
		if [ -s mycron ]; then
			echo -e "Removing cronjob $1!"
			# remove cronjob if it exists
			sed -i "\\#$1.*\$#c\\" mycron
			
			# Install the new crontab file
			crontab mycron
		fi
		
		# Delete the crontab file
		rm mycron
			
		cd "$CURDIR"
	fi
}

function addSystemCronJob(){ # by earnolmartin@gmail.com
	# $1 is the cronjob syntax
	# $2 is the command or script to run
	# Example of what $1 should be:  00 09 * * 1-5
	# Example of what $2 should be:  echo hello
	
	if [ ! -z "$1" ] && [ ! -z "$2" ]; then
		CURDIR=$(pwd)
		cd "$patchDir"

		# Get the current crontab
		crontab -l > mycron
		if [ ! -s mycron ]; then
			echo -e "$1 $2\n" >> mycron
		else
			# check to make sure the command or script isn't already being called
			scriptRunAlreadyCheck=$(cat mycron | grep "$2")
			if [ -z "$scriptRunAlreadyCheck" ]; then
				# Add the cronjob to the top of the file since custom EHCP admin defined cronjobs may have already been created.
				sed -i "1s#^#$1 $2\n#" mycron
			fi
		fi
		
		# Install the new crontab file
		crontab mycron
		
		# Delete the crontab file
		rm mycron
		
		cd "$CURDIR"
	fi
}

function getPHPSessionTimeout(){
	nginxFPMPHPConfig="$PHPCONFDir/fpm/php.ini"
	if [ -e "$nginxFPMPHPConfig" ]; then
		SessionTimeoutSetting=$(cat "$nginxFPMPHPConfig" | grep -o "^session.gc_maxlifetime.*" | grep -o "=.*" | grep -o "[^= ].*")
		if [ ! -z "$SessionTimeoutSetting" ] && (isnum "$SessionTimeoutSetting"); then
			SessionTimeoutSetting=$((SessionTimeoutSetting/60))
			if [ "$SessionTimeoutSetting" -le "0" ]; then
				SessionTimeoutSetting=1
			fi
			echo -e "Session timeout is configured to be to $SessionTimeoutSetting minutes!"
		fi
	fi
	
	if [ ! -z "$SessionTimeoutSetting" ] && (isnum "$SessionTimeoutSetting"); then
		if [ "$SessionTimeoutSetting" -ge "5" ] && [ "$SessionTimeoutSetting" -lt "60" ]; then
			echo -e "Adding cronjob to clear sessions last accessed more than $SessionTimeoutSetting minutes ago."
			removeSystemCronJob "/var/www/new/ehcp/scripts/cleanup/remove_old_php_tmp_session_files.sh"
			addSystemCronJob "*/$SessionTimeoutSetting * * * *" "/var/www/new/ehcp/scripts/cleanup/remove_old_php_tmp_session_files.sh $SessionTimeoutSetting"
		else
			SessionTimeoutSetting=
		fi
	else
		SessionTimeoutSetting=
	fi
}

function isnum(){ 
	# http://stackoverflow.com/questions/806906/how-do-i-test-if-a-variable-is-a-number-in-bash
	result=$(awk -v a="$1" 'BEGIN {print (a == a + 0)}';)
	if [ "$result" == "1" ]; then
		# 0 = true
		return 0
	else
		# 1 = false
		return 1
	fi
}

function getPHPConfigPath(){
	if [ -d "/etc/php5" ]; then
		PHPCONFDir="/etc/php5"
	else
		if [ -d "/etc/php" ]; then
			PHPCONFDir=$(ls "/etc/php" | head -n 1)
			PHPCONFDir="/etc/php/$PHPCONFDir"
		fi
	fi
}

# http://stackoverflow.com/questions/369758/how-to-trim-whitespace-from-a-bash-variable
function trim() {
    local var="$*"
    var="${var#"${var%%[![:space:]]*}"}"   # remove leading whitespace characters
    var="${var%"${var##*[![:space:]]}"}"   # remove trailing whitespace characters
    echo -n "$var"
}

function addToPostFixRecipientRestrictions(){
	CurRecipRestrictions=$(cat "/etc/postfix/main.cf" | grep -o "smtpd_recipient_restrictions\( \)*=.*" | grep -o "=.*" | grep -o "[^=\( \)*].*")
	if [ ! -z "$CurRecipRestrictions" ]; then
		# Remove last character if it's a comma which is used as the separator
		lastCharInRestriction="${CurRecipRestrictions: -1}"
		if [ "$lastCharInRestriction" == "," ]; then
			CurRecipRestrictions="${CurRecipRestrictions: : -1}"
		fi
	
		hasSpamhaus=$(echo "$CurRecipRestrictions" | grep -o "zen.spamhaus.org")
		hasSpamCop=$(echo "$CurRecipRestrictions" | grep -o "bl.spamcop.net")
		if [ -z "$hasSpamhaus" ]; then
			CurRecipRestrictions="${CurRecipRestrictions},reject_rbl_client zen.spamhaus.org"
		fi
		if [ -z "$hasSpamCop" ]; then
			CurRecipRestrictions="${CurRecipRestrictions},reject_rbl_client bl.spamcop.net"
		fi
		sed -i "s#^smtpd_recipient_restrictions\( \)*=.*#smtpd_recipient_restrictions = $CurRecipRestrictions#g" "/etc/postfix/main.cf"
	fi
}

function changeSquirrelMailConfigurationUseSendmail(){
	# SquirrelMail webmail folder should be renamed to webmail2
	if [ -e "/var/www/new/ehcp/webmail2/config/config.php" ]; then
			SquirrelMailConf="/var/www/new/ehcp/webmail2/config/config.php"
	else
		if [ -e "/var/www/new/ehcp/webmail/config/config.php" ]; then
			isSquirrelMail=$(cat "/var/www/new/ehcp/webmail/config/config.php" | grep -o "squirrelmail")
			if [ ! -z "$isSquirrelMail" ]; then
				SquirrelMailConf="/var/www/new/ehcp/webmail/config/config.php"
			fi
		fi
	fi
	
	hasSendMailSetting=$(cat "$SquirrelMailConf" | grep -o "\$useSendmail")
	if [ -z "$hasSendMailSetting" ]; then
		echo -e "\$useSendmail = true;" >> "$SquirrelMailConf"
	else
		currentSQMailSetting=$(cat "$SquirrelMailConf" | grep -o "\$useSendmail.*" | grep -o "=.*" | grep -o "[^=( )*].*")
		currentSQMailSetting=${currentSQMailSetting%?}
		
		if [ "$currentSQMailSetting" == "false" ]; then
			# Set it to true
			sed -i "s#^\$useSendmail\( \)*=.*#\$useSendmail = true;#g" "$SquirrelMailConf"
		fi
	fi
	
	sed -i "s#^\$data_dir\( \)*=.*#\$data_dir = '/var/www/new/ehcp/webmail2/data/';#g" "$SquirrelMailConf"
	sed -i "s#^\$attachment_dir\( \)*=.*#\$attachment_dir = '/var/www/new/ehcp/webmail2/data/';#g" "$SquirrelMailConf"
}

function makeRoundCubeDefaultMailClient(){
	if [ -e "/var/www/new/ehcp/webmail2" ]; then
		if [ ! -e "/var/www/new/ehcp/webmail2/config/config.php" ] ; then
			mv "/var/www/new/ehcp/webmail2" "/var/www/new/ehcp/webmail_roundcube"
			mv "/var/www/new/ehcp/webmail" "/var/www/new/ehcp/webmail2"
			mv "/var/www/new/ehcp/webmail_roundcube" "/var/www/new/ehcp/webmail"
		fi
	fi
}

function writeOutVersionInfo(){
	if [ ! -z "$yrelease" ] && [ ! -z "$mrelease" ]; then
		echo -e "$yrelease $mrelease" > "/var/www/new/ehcp/version_during_install.txt"
	fi
	
	if [ ! -z "$distro" ]; then
		echo -e "$distro" > "/var/www/new/ehcp/distro_during_install.txt"
	fi
}

function daemonUseSystemd(){
	# Stop services
	manageService "ehcp" "stop"
	manageService "policyd_agent" "stop"
	
	# Get rid of experimental python daemon
	# It was never used to begin with
	if [ -e "/etc/init.d/ehcp_daemon.py" ]; then
		rm "/etc/init.d/ehcp_daemon.py"
	fi
	
	# Use systemd Service for EHCP on newer systems
	if ([ "$distro" == "ubuntu" ] && [ "$yrelease" -ge "16" ]) || [ ! -z "$systemdPresent" ]; then
		if [ -e "/lib/systemd/system" ]; then
			cp "$FIXDIR/daemon/systemd/ehcp.service" "/lib/systemd/system"
			rm -rf "/etc/init.d/ehcp"
		elif [ -e "/etc/systemd/system" ]; then
			cp "$FIXDIR/daemon/systemd/ehcp.service" "/etc/systemd/system"
			rm -rf "/etc/init.d/ehcp"
		fi
	fi
	
	# Use systemd Service for policyd on newer systems
	if ([ "$distro" == "ubuntu" ] && [ "$yrelease" -ge "16" ]) || [ ! -z "$systemdPresent" ]; then
		if [ -e "/etc/cbpolicyd" ]; then
			if [ -e "/lib/systemd/system" ]; then
				cp "$FIXDIR/daemon/systemd/policyd_agent.service" "/lib/systemd/system"
				rm "/etc/init.d/policyd_agent"
			elif [ -e "/etc/systemd/system" ]; then
				cp "$FIXDIR/daemon/systemd/policyd_agent.service" "/etc/systemd/system"
				rm "/etc/init.d/policyd_agent"
			fi
		fi
	fi
	
	# Reload systemd to pick up on the new daemon if applicable
	systemctl daemon-reload
	systemctl enable policyd_agent.service
	systemctl enable ehcp.service
}

function installCertBotLetsEncrypt(){
	if [ ! -e "/usr/local/bin/certbot" ]; then
		curDir=$(pwd)
		cd "$patchDir"
		wget -N https://dl.eff.org/certbot-auto
		chmod a+x certbot-auto
		mv certbot-auto /usr/local/bin/certbot
		/usr/local/bin/certbot --quiet
		cd "$curDir"
		addSystemCronJob "45 4 * * *" "/var/www/new/ehcp/scripts/certbot_renew_certs.sh"
	fi
}

function searchForServiceName(){	
	# $1 is the search string... find a service that contains this string
	if [ -e "${serviceNameTempFile}" ]; then
		rm -rf "${serviceNameTempFile}"
	fi
	
	if [ ! -z "$1" ]; then
		serviceName=$(ls /etc/init.d 2>/dev/null | grep -F -- "${1}")
		if [ ! -z "$serviceName" ]; then
			echo "${serviceName}" > "${serviceNameTempFile}"
		fi
		
		serviceName=$(find /lib/systemd/system -name "*${1}*" -exec basename {} .service \; 2>/dev/null)
		if [ ! -z "$serviceName" ]; then
			echo "${serviceName}" > "${serviceNameTempFile}"
		fi
	
		serviceName=$(find /etc/systemd/system -name "*${1}*" -exec basename {} .service \; 2>/dev/null)
		if [ ! -z "$serviceName" ]; then
			echo "${serviceName}" > "${serviceNameTempFile}"
		fi
		
		if [ -z "${serviceNameTempFile}" ]; then
			> "${serviceNameTempFile}"
		fi
	fi
}

function managePHPFPMService(){
	# $1 is the action
	if [ -z "$1" ]; then
		fpmAction="restart"
	else 
		fpmAction="$1"
	fi
	
	searchForServiceName "-fpm"
	fpmService=$(cat "${serviceNameTempFile}")
	if [ ! -z "$fpmService" ]; then
		echo -e "${fpmAction}ing specifically detected php-fpm service ${fpmService}...\n"
		manageService "${fpmService}" "${fpmAction}"
	else
		echo -e "Restarting php-fpm generically...\n"
		manageService "php5-fpm" "${fpmAction}"
		manageService "php-fpm" "${fpmAction}"
		manageService "php7.0-fpm" "${fpmAction}"
		manageService "php7.1-fpm" "${fpmAction}"
		manageService "php7.2-fpm" "${fpmAction}"
	fi
}

function enablePHPFPMService(){
	searchForServiceName "-fpm"
	fpmService=$(cat "${serviceNameTempFile}")
	if [ ! -z "$fpmService" ]; then
		echo -e "Enabling specifically detected php-fpm service ${fpmService}...\n"
		update-rc.d "${fpmService}" enable
		update-rc.d "${fpmService}" defaults
		systemctl enable "${fpmService}.service"
	else
		echo -e "Enabling php-fpm service generically...\n"
		update-rc.d "php5-fpm" enable
		update-rc.d "php5-fpm" defaults
		update-rc.d "php-fpm" enable
		update-rc.d "php-fpm" defaults
		update-rc.d "php7.0-fpm" enable
		update-rc.d "php7.0-fpm" defaults
		update-rc.d "php7.1-fpm" enable
		update-rc.d "php7.1-fpm" defaults
		update-rc.d "php7.2-fpm" enable
		update-rc.d "php7.2-fpm" defaults
		
		systemctl daemon-reload
		systemctl enable php5-fpm.service
		systemctl enable php-fpm.service
		systemctl enable php7.0-fpm.service
		systemctl enable php7.1-fpm.service
		systemctl enable php7.2-fpm.service
	fi
}

function finalCleanup(){
	if [ -e "${serviceNameTempFile}" ]; then
		rm -rf "${serviceNameTempFile}"
	fi	
}

function fixMariaDBSkippingInnoDB(){
	# Check to see if allow writeable chroot is present
	# MariaDB Config file
	mariaDBConfFile="/etc/mysql/mariadb.cnf"
	if [ -e "$mariaDBConfFile" ]; then
			sed -i 's/^skip-innodb/#skip-innodb/g' "$mariaDBConfFile"
	fi
}

function getImportantPreReqs(){
	aptgetInstall lsb-release
	# debian fix
	aptgetInstall software-properties-common 
	aptgetInstall dirmngr
}

#############################################################
# End Functions & Start Install							 #
#############################################################
installdir=$(pwd)
if [ ! -f $installdir/install.sh ] ; then
	echo "install.sh is not in install dir. Run install.sh from within ehcp installation dir."
	exit 1
fi

#echo "`date`: initializing.b"  # i added these echo's because on some system, some stages are very slow. i need to investigate that.
checkUser
ehcpHeader

# Check that we are using apt
checkAptget

# Update the system before installation
# If your package information is out of date, MySQL and others may fail to install
updateBeforeInstall

# Get some additional prereqs
getImportantPreReqs

# Check distribution
checkDistro

# Set global variables
setGlobalVars

# Turn off apprmor
manageService "apparmor" "stop" # apparmor causes many problems...
manageService "apparmor" "teardown" # apparmor causes many problems..if anybody knows a better solution, let us know.

# Kill all running EHCP
killallEhcp

if [ -z "$distro" ] ; then
	echo "Your system type could not be detected or detected to be ($distro), You may not install ehcp automatically on this system, anyway, to continue press enter"
	read
fi

#----------------------start some install --------------------------------------
#echo "`date`: initializing.g"
mkdir /etc/ehcp

# Update everything
aptget_Update

# Install prereqs
installNeededDependencies

# Get PHP Config Path
getPHPConfigPath

#outside_ip=`echo "" > ip ; wget -q -O ip http://ehcp.net/diger/myip.php ; echo ip`
#rm -f ip

echo
echo

checkPhp
echo
echo
echo
echo "STAGE 2"
echo "====================================================================="
echo "now running install_1.php "
php install_1.php $version $distro $noapt $unattended $installmode $debug

echo 
echo 
echo "STAGE 3"
echo "====================================================================="
echo "now running install_2.php "

#Send version to avoid installing nginx on Ubuntu 12.10 --- there is a bug and it's not supported
#php install_2.php $noapt || php /etc/ehcp/install_2.php $noapt  # start install_2.php if first install is successfull at php level. to prevent many errors.
php install_2.php $version $distro $noapt $unattended $installmode $debug

###############################################
# Post Install Functions by Eric Arnol-Martin #
###############################################

# Get PHP Config Path Again Just In Case
getPHPConfigPath

mv /var/www/new/ehcp/install_?.php /etc/ehcp/   # move it, to prevent later unauthorized access of installer from web
cd "/var/www/new/ehcp"
# Stop Services While We Perform Changes
stopServices
# Get ftpuser
getVSFTPDUser
# Run VSFTPD Fix depending on version
ubuntuVSFTPDFix
# Run SlaveDNS Fix So that DNS Zones can be transfered
slaveDNSApparmorFix
# Fix EHCP Permissions
fixEHCPPerms
# Run log chmod fix
logDirFix
# Copy over nginx conf file
copyNginxConf
# Fix php-fpm to listen to port 9000
fixPHPFPMListen
# Fix session saving for nginx
fixNginxSessions
# Change Apache user to vsftpd to ensure chmod works via PHP and through FTP Clients
changeApacheUser
# Use FPM for apache
apacheUseFPM
# Add rate limiting option to nginx if it doesn't have it
nginxRateLimit
# Fix extra mysql module getting loaded in the PHP config printing warning messages
fixPHPConfig
# Fix /etc/bind directory permissions required for slave dns
fixBINDPerms
# Fix generic problems in Ubuntu
genUbuntuFixes
# Secure BIND9 Configuration
disableRecursiveBIND
# Make it so that strangers can't just browse folders without an index file
secureApache
# Allow .htaccess file overrides for Ubuntu 14.04 and up (hopefully versions above)
allowHTACCESSOverrides
# Remove unattended install file if exists
removeInstallSilently
# Install and enable php mcrypt module --- if not enabled already
fixPHPmcrypt
#Fix pop-before-smtp
fixPopBeforeSMTP
#Fix apache2 umask issue
#For some reason, new distros include umask 111 in apache2 envvars which really messes stuff up
removeApacheUmask
# Secure PHPMyAdmin Configuration to Prevent Root Logins Except for Local Connections
securePHPMyAdminConfiguration
#Change settings in php.ini to allow 50MB upload files, turn on displaying errors, and setting the error_reporting to something practical
configurePHPIni
# Install Fail2Ban, Apache Modules, and Antispam Depending on Install Mode
installExtras 
# Fix saslauthd
fixSASLAUTH
# Turn off IPv6 support in postfix if applies (will fix email issues)
turnOffIPv6PostFix
# Fix roundcube config for file attachments
fixRoundCubeFileAttachments
# Set localhost as server to use for roundcube
setDefaultRoundCubeServer
# Make roundcube default webmail
makeRoundCubeDefaultMailClient
# Fix SquirrelMail config to use Sendmail
changeSquirrelMailConfigurationUseSendmail
# Download and install certbot (let's encrypt)
installCertBotLetsEncrypt

# Get PHP Session Timeout and Create Cronjob to Clean Expired Sessions Up!
WebServerType="apache2"
getPHPSessionTimeout
if [ -z "$SessionTimeoutSetting" ]; then
	echo -e "Adding cronjob to cleanup old php session files"
	# Add cronjob to clean up old php session files
	addSystemCronJob "0 2 */5 * *" "/var/www/new/ehcp/scripts/cleanup/remove_old_php_tmp_session_files.sh"
fi

# Add blacklist email lookup to block incoming spam
addToPostFixRecipientRestrictions

# Write out version information to version_during_install.txt... that way, if it changes later, we can reprocess it
writeOutVersionInfo

# Use systemd services if systemd is present
daemonUseSystemd

# Restart neccessary daemons
echo "Initializing the EHCP Daemon"
restartDaemons
# Inform installation is complete
postInstallInformation

# Run final cleanup
finalCleanup

# Launch firefox and the panel
##############################################
launchPanel
