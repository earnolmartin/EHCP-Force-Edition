#!/bin/bash
# ehcp - Easy Hosting Control Panel install/remove by info@ehcpforce.ezpz.cc (actually, no remove yet)
# this is a very basic shell installer, real installation in install_lib.php, which is called by install_1.php, install_2.php
#
# please contact me if you made any modifications.. or you need help
# msn/email: info@ehcpforce.ezpz.cc
# skype/yahoo/gtalk: bvidinli

# Marcel <marcelbutucea@gmail.com>
#	   - added initial support for yum (RedHat/CentOS)
#	   - some code ordering, documentation and cleanup
#


ehcpversion="0.34"


chmod -Rf a+r *

if [ "$1" == "noapt" ] ; then
	noapt="noapt"
fi

################################################################################################
# Function Definitions																		 #
################################################################################################

# Stub function for apt-get

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
	echo "-----Easy Hosting Control Panel for Ubuntu, Debian and alikes--------"
	echo "-------------------------www.ehcp.net--------------------------------"
	echo "---------------------------------------------------------------------"
	echo
	echo 
	echo "Now, ehcp pre-installer begins, a series of operations will be performed and main installer will be invoked. "
	echo "if any problem occurs, refer to https://ehcpforce.ezpz.cc forum section, or contact me, mail/msn: info@ehcpforce.ezpz.cc"
	
	echo "Please be patient, press enter to continue"
	read
	echo
	echo "Note that ehcp can only be installed automatically on Debian based Linux OS'es or Linux'es with apt-get enabled..(Ubuntu, Kubuntu, debian and so on) Do not try to install ehcp with this installer on redhat, centos and non-debian Linux's... To use ehcp on no-debian systems, you need to manually install.. "
	echo "this installer is for installing onto a clean, newly installed Ubuntu/Debian. If you install it on existing system, some existing packages will be removed after prompting, if they conflict with packages that are used in ehcp, so, be careful to answer yes/no when using in non-new system"
	echo "Actually, I dont like saying like, 'No warranty, I cannot be responsible for any damage.... ', But, this is just a utility.. use at your own."
	echo "ehcp also sends some usage data to developer for statistical purposes"
	echo "press enter to continue"
	read
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


	sayi=`grep -v "#" /etc/apt/sources.list | wc -l`

	if [ $sayi -lt 10 ] ; then
		echo
		echo "WARNING ! Your /etc/apt/sources.list  file contains very few sources, This may cause problems installing some packages.. see http://www.ehcp.net/?q=node/389 for an example file"
		echo "This may be normal for some versions of debian"
		echo "press enter to continue or Ctrl-C to cancel and fix that file"
		read
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
				apt-get --allow-unauthenticated install $1
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

function aptget_Update(){
	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get update"
		return
	fi

	apt-get update
}

function aptgetInstall(){

	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get install for:$1"
		return
	fi

	# first, try to install without any prompt, then if anything goes wrong, normal install..
	cmd="apt-get -y --no-remove --allow-unauthenticated install $1"
	logToFile "$cmd"
	$cmd
	
	if [ $? -ne 0 ]; then
		cmd="apt-get --allow-unauthenticated install $1"
		logToFile "$cmd"
		$cmd	
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
	$cmd
	
	if [ $? -ne 0 ]; then
		cmd="apt-get remove $1"
		logToFile "$cmd"
		$cmd	
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
	firefox=`which firefox`
	if [ -n "$firefox" ] ; then
		echo "now, you should be able to navigate to your"
		echo "panel admin username: admin "
		echo "now will try to launch your control panel, if it is on local computer.. "
		echo -e "\nwill use firefox as browser...\n\n"
		$firefox "http://localhost" &
	fi
}

# Thanks a lot to  earnolmartin@gmail.com for fail2ban integration & vsftpd fixes.

function slaveDNSApparmorFix(){ # by earnolmartin@gmail.com
	if [ -e /etc/apparmor.d/usr.sbin.named ]; then
				echo -e "\nChanging bind apparmor rule to allow master DNS synchronization for slave setups.\n"
				sed -i 's#/etc/bind/\*\* r,#/etc/bind/\*\* rw,#g' /etc/apparmor.d/usr.sbin.named
	fi
}

function libldapFix(){ # by earnolmartin@gmail.com
	# install libldap, for vsftpd fix, without prompts
	#Remove originally installed libpam-ldap if it exists
	origDir=$(pwd)	
	aptgetRemove libpam-ldap
	DEBIAN_FRONTEND=noninteractive apt-get -y install libpam-ldap
	cd $patchDir
	mkdir lib32gccfix
	cd lib32gccfix
	wget -O "ldap_conf.tar.gz" http://dinofly.com/files/linux/ldap_conf_64bit_vsftpd.tar.gz
	tar -zxvf ldap_conf.tar.gz
	cp ldap.conf /etc/
	cd $origDir
}  

function fixVSFTPConfig(){ # by earnolmartin@gmail.com
	sed -i 's/chroot_local_user=NO/chroot_local_user=YES/g' /etc/vsftpd.conf
	allowWriteValue=$( cat /etc/vsftpd.conf | grep -o "allow_writeable_chroot=.*" | grep -o "=.*$" | grep -o "[^=].*" )
	if [ -z "$allowWriteValue" ]; then
		sh -c "echo 'allow_writeable_chroot=YES' >> /etc/vsftpd.conf"
	else
		sed -i 's/allow_writeable_chroot=NO/allow_writeable_chroot=YES/g' /etc/vsftpd.conf
	fi
	if [ $OSBits -eq "64" ]; then 
		#aptgetInstall libpam-ldap # this is required in buggy vsftpd installs.. ubuntu 12.04,12.10, 13.04, now... 
		libldapFix
		aptgetInstall libgcc1
		# 64-bit 500 OOPS: priv_sock_get_cmd Fix
		# seccomp_sandbox=NO
		allowSandBox=$( cat /etc/vsftpd.conf | grep -o "seccomp_sandbox=.*" | grep -o "=.*$" | grep -o "[^=].*" )
		if [ -z "$allowSandBox" ]; then
			sh -c "echo 'seccomp_sandbox=NO' >> /etc/vsftpd.conf"
		else
			sed -i 's/seccomp_sandbox=YES/seccomp_sandbox=NO/g' /etc/vsftpd.conf
		fi		
	fi
	service vsftpd restart
}

function remove_vsftpd(){
	#Remove originally installed vsftpd
	aptgetRemove vsftpd
	# Just incase it's been installed already or another version has been installed using dpgk, let's remove it
	dpkg --remove vsftpd
}

function ubuntuVSFTPDFix(){ # by earnolmartin@gmail.com
	# Get currently working directory
	origDir=$( pwd )
	patchDir="/root/Downloads"
	if [ ! -e $patchDir ]; then
		mkdir $patchDir
	fi
	# Ubuntu VSFTPD Fixes
	if [ ! -z "$yrelease" ]; then
		if [ "$distro" == "ubuntu" ]; then
			if [ "$yrelease" -ge "12" ] && [ "$yrelease" -lt 13 ]; then
				 if [ "$mrelease" == "04" ]; then
					# Run 12.04 Fix
					remove_vsftpd
					echo -e "\nRunning VSFTPD fix for Ubuntu 12.04\n"
					add-apt-repository -y ppa:thefrontiergroup/vsftpd
					aptget_Update
					aptgetInstall vsftpd
					fixVSFTPConfig

				 elif [ "$mrelease" -eq "10" ]; then
					# Run 12.10 Fix
					remove_vsftpd
					echo -e "\nRunning VSFTPD fix for Ubuntu 12.10\n"
					#get the code
					cd $patchDir
					if [ ! -e vsftpd_2.3.5-3ubuntu1.deb ]; then
						if [ $OSBits -eq "32" ]; then 
							wget -O "vsftpd_2.3.5-3ubuntu1.deb" http://dinofly.com/files/linux/vsftpd_2.3.5-3ubuntu1_i386.deb
						else
							wget -O "vsftpd_2.3.5-3ubuntu1.deb" http://dinofly.com/files/linux/vsftpd_2.3.5-3.jme_amd64.deb
						fi
					fi
					#install
					dpkg -i vsftpd_2.3.5-3ubuntu1.deb
					cd $origDir
					fixVSFTPConfig
				 fi
			elif [ "$yrelease" -eq "13" ]; then
				if [ "$mrelease" == "04" ]; then
					remove_vsftpd
					echo -e "\nRunning VSFTPD fix for Ubuntu 13.04\n"
					cd $patchDir
					if [ ! -e vsftpd_3.0.2-patched_ubuntu.deb ]; then
						if [ $OSBits -eq "32" ]; then 
							wget -O "vsftpd_3.0.2-patched_ubuntu.deb" http://dinofly.com/files/linux/vsftpd_3.0.2-patched_ubuntu_13.04_x86.deb
						else
							wget -O "vsftpd_3.0.2-patched_ubuntu.deb" http://dinofly.com/files/linux/vsftpd_3.0.2-1ubuntu1_amd64_patched.deb
						fi
					fi
					sudo dpkg -i vsftpd_3.0.2-patched_ubuntu.deb
					cd $origDir
					fixVSFTPConfig
				fi
			fi
		fi  
	fi
}

function logDirFix(){ # by earnolmartin@gmail.com
	chmod 755 log
	chmod 744 log/ehcp_failed_authentication.log
	chown vsftpd:www-data log/ehcp_failed_authentication.log
}

function fixEHCPPerms(){ # by earnolmartin@gmail.com
	chmod a+rx /var/www/new/ehcp/
	chmod -R a+r /var/www/new/ehcp/
	find ./ -type d -exec chmod a+rx {} \;
	chown -R vsftpd:www-data /var/www/new/ehcp/webmail
	chmod 755 -R /var/www/new/ehcp/webmail
	chmod 755 /var/www/new/index.html
}

function fixPHPConfig(){ # by earnolmartin@gmail.com
	PHPConfFile="/etc/php5/cli/php.ini"
	if [ -e $PHPConfFile ]; then
		PHPConfCheck=$( cat $PHPConfFile | grep -o ";extension=mysql.so" )
		if [ -z "$PHPConfCheck" ]; then 
			sed -i "s/extension=mysql.so/;extension=mysql.so/g" $PHPConfFile
			service apache2 restart
		fi
	fi
}

#############################################################
# End Functions & Start Install							 #
#############################################################
cd /var/www/new/ehcp/
installdir=$(pwd)
if [ ! -f $installdir/install.sh ] ; then
	echo "install.sh is not in install dir. Run install.sh from within ehcp installation dir."
	exit 1
fi


checkUser
#ehcpHeader
service apparmor stop & > /dev/null  # apparmor causes many problems..
checkDistro
#killallEhcp

aptget_Update

checkPhp

# Post Install Functions by Eric Arnol-Martin

mv /var/www/new/ehcp/install_?.php /etc/ehcp/   # move it, to prevent later unauthorized access of installer from web
cd "/var/www/new/ehcp"
# Run VSFTPD Fix depending on version
ubuntuVSFTPDFix
# Run SlaveDNS Fix So that DNS Zones can be transfered
slaveDNSApparmorFix
# Run log chmod fix
logDirFix
# Configure Fail2Ban for EHCP if Fail2Ban is present and configured
# fail2banCheck # done in install*php files.
# Fix EHCP Permissions
fixEHCPPerms
# Fix extra mysql module getting loaded in the PHP config printing warning messages
fixPHPConfig

# Launch firefox and the panel
##############################################
launchPanel
# you may disable following lines, these are for debug/check purposes.

echo "now running ehcp daemon.."
cd /var/log
service ehcp restart
echo "ehcp run/restart complete.."
sleep 5 # to let ehcp log fill a little

ps aux > debug.txt
echo "============================================"  >> debug.txt
tail -100 /var/log/syslog >> debug.txt
tail -100 /var/log/ehcp.log >> debug.txt

echo "ehcp : Finished all operations.. go to your panel at http://yourip/ now..."
