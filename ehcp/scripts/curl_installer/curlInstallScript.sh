#!/bin/bash
# Completes the installation steps of various open source packages
# Author:  Eric Arnol-Martin <earnolmartin@gmail.com>

# Parameters
# $1 is the script name
# $2 is the domain name
# $3 is the directory name
# $4 is the full path to directory name
# $5 is the database name
# $6 is the database username
# $7 is the database password
# $8 is the database host
# $9 is the title for the script
# $10 is admin_email for script
# $11 is the WWW username
# $12 is the WWW group
# $13 is the webserver mode (sslonly, ssl, nonssl)

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo -e "\nYou must run this script as root!"
	exit
fi

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games"
PHPPath=$(which php)
DEFAULTPASS="12345678!!longeR"
DEFAULTLOGIN="admin"
ADMINEMAIL="noreply@ehcpforce.tk"
DEFAULTDESC="ehcpforce.tk"

if [ "$#" -ne 13 ]; then
    echo "Illegal number of parameters received: $#"
    exit
else
	if [ ! -z "$1" ]; then
		SCRIPTNAME="$1"
	fi

	if [ ! -z "$2" ]; then
		DOMAINNAME="$2"
	fi

	if [ ! -z "$3" ]; then
		DIRECTORY="$3"
	fi

	if [ ! -z "$4" ]; then
		FULLPATH="$4"
	fi

	if [ ! -z "$5" ]; then
		DBNAME="$5"
	fi
	
	if [ ! -z "$6" ]; then
		DBUSERNAME="$6"
	fi
	
	if [ ! -z "$7" ]; then
		DBUSERPASS="$7"
	fi
	
	if [ ! -z "$8" ]; then
		DBHOST="$8"
	fi
	
	if [ ! -z "$9" ]; then
		TITLE="$9"
	fi
	
	if [ ! -z "${10}" ]; then
		ADMINEMAIL="${10}"
	fi
	
	if [ ! -z "${11}" ]; then
		WWWUser="${11}"
	else
		WWWUser="ftp"
	fi
	
	if [ ! -z "${12}" ]; then
		WWWGroup="${12}"
	else
		WWWGroup="www-data"
	fi
	
	if [ ! -z "${13}" ] && [ "${13}" == "sslonly" ]; then
		serverMode="https://"
	else
		serverMode="http://"
	fi
fi

###############
# Functions   #
###############

function doWordPressInstall(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Curl install wordpress
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/wp-admin/setup-config.php?step=2" "dbname=$DBNAME" "uname=$DBUSERNAME" "pwd=$DBUSERPASS" "dbhost=$DBHOST" "prefix=wp_" "submit=yes"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/wp-admin/install.php?step=2" "weblog_title=$TITLE" "user_name=$DEFAULTLOGIN" "admin_password=$DEFAULTPASS" "admin_password2=$DEFAULTPASS" "admin_email=$ADMINEMAIL" "blog_public=1" "Submit=yes" "language=en_US"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/wp-admin/install.php" ]; then
		rm "$FULLPATH/wp-admin/install.php"
	fi
}

function doJoomlaInstall(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	#curl -H "Content-Type: application/json" -d "{\"jform[site_offline]\":\"0\",\"jform[site_name]\":\"$TITLE\",\"jform[site_metadesc]\":\"$DEFAULTDESC\",\"jform[admin_email]\":\"$ADMINEMAIL\",\"jform[admin_user]\":\"$DEFAULTLOGIN\",\"jform[admin_password]\":\"$DEFAULTPASS\",\"jform[admin_password2]\":\"$DEFAULTPASS\",\"jform[language]\":\"en-US\",\"task\":\"site\"}" "$serverMode$DOMAINNAME/$DIRECTORY/installation/index.php"
	#curl -H "Content-Type: application/json" -d "{\"jform[db_type]\":\"mysqli\",\"jform[db_host]\":\"$DBHOST\",\"jform[db_user]\":\"$DBUSERNAME\",\"jform[db_pass]\":\"$DBUSERPASS\",\"jform[db_name]\":\"$DBNAME\",\"jform[db_prefix]\":\"jlm_\",\"jform[db_old]\":\"backup\",\"task\":\"database\"}" "$serverMode$DOMAINNAME/$DIRECTORY/installation/index.php"
	#curl -H "Content-Type: application/json" -d "{\"jform[sample_file]\":\"\",\"jform[summary_email]\":\"1\",\"jform[summary_email_passwords]\":\"0\",\"task\":\"summary\"}" "$serverMode$DOMAINNAME/$DIRECTORY/installation/index.php"
	
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/installation/index.php" "format=json" "jform[site_offline]=0" "jform[site_name]=$TITLE" "jform[site_metadesc]=$DEFAULTDESC" "jform[admin_email]=$ADMINEMAIL" "jform[admin_user]=$DEFAULTLOGIN" "jform[admin_password]=$DEFAULTPASS" "jform[admin_password2]=$DEFAULTPASS" "jform[language]=en-US" "task=site"
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/installation/index.php" "format=json" "jform[db_type]=mysqli" "jform[db_host]=$DBHOST" "jform[db_user]=$DBUSERNAME" "jform[db_pass]=$DBUSERPASS" "jform[db_name]=$DBNAME" "jform[db_prefix]=jlm_" "jform[db_old]=backup" "task=database"
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/installation/index.php" "format=json" "jform[sample_file]=" "jform[summary_email]=1" "jform[summary_email_passwords]=0" "task=summary"
	
	# Curl is out of the question for this worthless Joomla garbage...
	
	CURDIR=$(pwd)
	cd "$DLSFolder"
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/joomla/j3.sql ./
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/joomla/configuration.php ./
	
	# Replace stuff 
	sed -i "s/{SITE_NAME}/$TITLE/g" "configuration.php"
	sed -i "s#{SITE_DESC}#$DEFAULTDESC#g" "configuration.php"
	sed -i "s/noreply@ehcpforce.tk/$ADMINEMAIL/g" "configuration.php"
	sed -i "s/noreply@ehcpforce.tk/$ADMINEMAIL/g" "j3.sql"
	sed -i "s/\$host = 'localhost';/\$host = '$DBHOST';/g" "configuration.php"
	sed -i "s/\$user = 'j3';/\$user = '$DBUSERNAME';/g" "configuration.php"
	sed -i "s/\$password = 'j3';/\$password = '$DBUSERPASS';/g" "configuration.php"
	sed -i "s/\$db = 'j3';/\$db = '$DBNAME';/g" "configuration.php"
	sed -i "s#\$log_path = '/var/www/vhosts/own3mall/lmfao.com/httpdocs/joomla3/logs';#\$log_path = '$FULLPATH/logs';#g" "configuration.php"
	sed -i "s#\$tmp_path = '/var/www/vhosts/own3mall/lmfao.com/httpdocs/joomla3/tmp';#\$tmp_path = '$FULLPATH/tmp';#g" "configuration.php"
	
	# Move modified config to proper path
	mv "configuration.php" "$FULLPATH"
	
	# Import mysql database
	mysql -h "$DBHOST" -u "$DBUSERNAME" -p"$DBUSERPASS" "$DBNAME" -f < "j3.sql";
	
	rm "j3.sql"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/installation" ]; then
		if [ ! -z "$FULLPATH" ] && [ "$FULLPATH" != "/" ]; then
			rm -Rf "$FULLPATH/installation"
		fi
	fi
	
	# change back into original dir
	cd "$CURDIR"
	
}

function doSMF2Install(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Curl install SMF version 2
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?step=2" "db_name=$DBNAME" "db_user=$DBUSERNAME" "db_type=mysql" "db_passwd=$DBUSERPASS" "db_server=$DBHOST" "db_prefix=smf_" "contbutt=yes"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?step=3" "mbname=$TITLE" "boardurl=$serverMode$DOMAINNAME/$DIRECTORY" "compress=1" "dbsession=1" "contbutt=yes"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?step=5" "username=$DEFAULTLOGIN" "password1=$DEFAULTPASS" "password2=$DEFAULTPASS" "email=$ADMINEMAIL" "server_email=$ADMINEMAIL" "password3=$DBUSERPASS" "contbutt=yes"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install.php" ]; then
		rm "$FULLPATH/install.php"
	fi
}

function doPHPBBInstall(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Curl install phpBB version 3.1.3
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php?mode=install&sub=requirements&language=en" "submit=yes"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php?mode=install&sub=database&language=en" "dbms=mysqli" "dbname=$DBNAME" "dbuser=$DBUSERNAME" "dbpasswd=$DBUSERPASS" "dbhost=$DBHOST" "table_prefix=phpbb_" "dbport=" "submit=yes" "language=en" "testdb=true"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php?mode=install&sub=administrator" "default_lang=en" "admin_name=$DEFAULTLOGIN" "admin_pass1=$DEFAULTPASS" "admin_pass2=$DEFAULTPASS" "board_email=$ADMINEMAIL" "submit=yes" "dbms=mysqli" "dbname=$DBNAME" "dbuser=$DBUSERNAME" "dbpasswd=$DBUSERPASS" "dbhost=$DBHOST" "table_prefix=phpbb_" "dbport=" "language=en"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php?mode=install&sub=config_file" "submit=yes" "default_lang=en" "admin_name=$DEFAULTLOGIN" "admin_pass1=$DEFAULTPASS" "admin_pass2=$DEFAULTPASS" "board_email=$ADMINEMAIL" "dbms=mysqli" "dbname=$DBNAME" "dbuser=$DBUSERNAME" "dbpasswd=$DBUSERPASS" "dbhost=$DBHOST" "table_prefix=phpbb_" "dbport=" "language=en"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php?mode=install&sub=create_table" "submit=yes" "default_lang=en" "admin_name=$DEFAULTLOGIN" "admin_pass1=$DEFAULTPASS" "admin_pass2=$DEFAULTPASS" "board_email=$ADMINEMAIL" "dbms=mysqli" "dbname=$DBNAME" "dbuser=$DBUSERNAME" "dbpasswd=$DBUSERPASS" "dbhost=$DBHOST" "table_prefix=phpbb_" "dbport=" "language=en"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php?mode=install&sub=final" "submit=yes" "default_lang=en" "admin_name=$DEFAULTLOGIN" "admin_pass1=$DEFAULTPASS" "admin_pass2=$DEFAULTPASS" "board_email=$ADMINEMAIL" "dbms=mysqli" "dbname=$DBNAME" "dbuser=$DBUSERNAME" "dbpasswd=$DBUSERPASS" "dbhost=$DBHOST" "table_prefix=phpbb_" "dbport=" "language=en"
	
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install" ]; then
		rm -R "$FULLPATH/install"
	fi
}

function doDrupalInstall(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Curl install Drupal 7.35
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php" "op=yes" "profile=standard"
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?profile=standard" "op=yes" "profile=standard" "locale=en"
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?profile=standard&locale=en" "op=yes" "profile=standard" "locale=en" "driver=mysql" "mysql[database]=$DBNAME" "mysql[username]=$DBUSERNAME" "mysql[password]=$DBUSERPASS" "mysql[host]=$DBHOST" "mysql[db_prefix]=drupal_"
	#"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?profile=standard&locale=en" "op=yes" "profile=standard" "locale=en" "site_name=$TITLE" "site_mail=$ADMINEMAIL" "account[name]=$DEFAULTLOGIN" "account[mail]=$ADMINEMAIL" "account[pass][pass1]=$DEFAULTPASS" "account[pass][pass2]=$DEFAULTPASS" "update_status_module[1]=1" "update_status_module[2]=0"
	
	CURDIR=$(pwd)
	cd "$DLSFolder"
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/drupal/drupal.sql ./
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/drupal/settings.php ./
	
	# Replace stuff 
	sed -i "s/{ADMIN_EMAIL}/$ADMINEMAIL/g" "drupal.sql"
	sed -i "s/{DB_HOST}/$DBHOST/g" "settings.php"
	sed -i "s/{DB_PASS}/$DBUSERPASS/g" "settings.php"
	sed -i "s/{DB_USER}/$DBUSERNAME/g" "settings.php"
	sed -i "s/{DB_NAME}/$DBNAME/g" "settings.php"
	
	# Move modified config to proper path
	mv "settings.php" "$FULLPATH/sites/default"
	
	# Import mysql database
	mysql -h "$DBHOST" -u "$DBUSERNAME" -p"$DBUSERPASS" "$DBNAME" -f < "drupal.sql";
	
	rm "drupal.sql"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install.php" ]; then
		if [ ! -z "$FULLPATH" ] && [ "$FULLPATH" != "/" ]; then
			rm -Rf "$FULLPATH/install.php"
		fi
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function doPHPCoinInstall(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	CURDIR=$(pwd)
	cd "$FULLPATH"
	
	# Replace stuff 
	sed -i "s/localhost/$DBHOST/g" "config.php"
	sed -i "s/username/$DBUSERNAME/g" "config.php"
	sed -i "s/\"password\"/\"$DBUSERPASS\"/g" "config.php"
	sed -i "s/\"database\"/\"$DBNAME\"/g" "config.php"
	
	# Make a call to index.php to install phpcoin
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/coin_setup/setup.php" "read_license=1" "password=$DBUSERPASS"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/coin_setup/setup.php" "read_license=1" "password=$DBUSERPASS" "stage=1"
	
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install.php" ]; then
		rm -R "$FULLPATH/install.php"
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function doSMF1Install(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	CURDIR=$(pwd)
	cd "$FULLPATH"
	
	# Make a call to index.php to install SMF version 1.1.20
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?step=1" "mbname=$TITLE" "db_server=$DBHOST" "db_user=$DBUSERNAME" "db_passwd=$DBUSERPASS" "db_name=$DBNAME" "db_prefix=smf_" "boardurl=$serverMode$DOMAINNAME/$DIRECTORY" "compress=1" "dbsession=1" 
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install.php?step=2" "username=$DEFAULTLOGIN" "password1=$DEFAULTPASS" "password2=$DEFAULTPASS" "email=$ADMINEMAIL" "password3=$DBUSERPASS"
	
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install.php" ]; then
		rm -R "$FULLPATH/install.php"
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function doMYBBInstall(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	CURDIR=$(pwd)
	cd "$FULLPATH"
	
	# Make a call to index.php to install MyBB
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=license" 
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=requirements_check" 
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=database_info" 
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "dbengine=mysqli" "config[mysqli][dbhost]=$DBHOST" "config[mysqli][dbuser]=$DBUSERNAME" "config[mysqli][dbpass]=$DBUSERPASS" "config[mysqli][dbname]=$DBNAME" "config[mysqli][tableprefix]=mybb_" "config[mysqli][encoding]=utf8" "action=create_tables"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=populate_tables"
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=templates" 
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=configuration" 
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=adminuser" "bbname=$TITLE" "bburl=$serverMode$DOMAINNAME/$DIRECTORY" "websitename=$TITLE" "websiteurl=$serverMode$DOMAINNAME/" "cookiedomain=.$DOMAINNAME" "cookiepath=/" "contactemail=$ADMINEMAIL" "pin="
	"$PHPPath" "/var/www/new/ehcp/scripts/curl_installer/curl_call.php" "$serverMode$DOMAINNAME/$DIRECTORY/install/index.php" "action=final" "adminuser=$DEFAULTLOGIN" "adminpass=$DEFAULTPASS" "adminpass2=$DEFAULTPASS" "adminemail=$ADMINEMAIL" 
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install" ]; then
		rm -R "$FULLPATH/install"
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function doJoomla5Install(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	CURDIR=$(pwd)
	cd "$DLSFolder"
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/joomla/j5.sql ./
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/joomla/configuration_j5.php ./
	
	# Replace stuff 
	sed -i "s/{SITE_NAME}/$TITLE/g" "configuration_j5.php"
	sed -i "s#{SITE_DESC}#$DEFAULTDESC#g" "configuration_j5.php"
	sed -i "s/noreply@ehcpforce.tk/$ADMINEMAIL/g" "configuration_j5.php"
	sed -i "s/noreply@ehcpforce.tk/$ADMINEMAIL/g" "j5.sql"
	sed -i "s/\$host = 'localhost';/\$host = '$DBHOST';/g" "configuration_j5.php"
	sed -i "s/\$user = 'j5';/\$user = '$DBUSERNAME';/g" "configuration_j5.php"
	sed -i "s/\$password = 'j5';/\$password = '$DBUSERPASS';/g" "configuration_j5.php"
	sed -i "s/\$db = 'j5';/\$db = '$DBNAME';/g" "configuration_j5.php"
	sed -i "s#\$log_path = '/var/www/vhosts/test/x-null.net/httpdocs/joomla/administrator/logs';#\$log_path = '$FULLPATH/administrator/logs';#g" "configuration_j5.php"
	sed -i "s#\$tmp_path = '/var/www/vhosts/test/x-null.net/httpdocs/joomla/tmp';#\$tmp_path = '$FULLPATH/tmp';#g" "configuration_j5.php"
	
	# Move modified config to proper path
	mv "configuration_j5.php" "$FULLPATH/configuration.php"
	
	# Import mysql database
	mysql -h "$DBHOST" -u "$DBUSERNAME" -p"$DBUSERPASS" "$DBNAME" -f < "j5.sql";
	
	rm "j5.sql"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/installation" ]; then
		if [ ! -z "$FULLPATH" ] && [ "$FULLPATH" != "/" ]; then
			rm -Rf "$FULLPATH/installation"
		fi
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function doPHPBB33Install(){
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	CURDIR=$(pwd)
	cd "$DLSFolder"
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/phpbb3/phpbb.sql ./
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/phpbb3/config_phpbb.php ./
	
	# Replace stuff 
	sed -i "s/{DOMAIN_NAME}/$DOMAINNAME/g" "phpbb.sql"
	sed -i "s/{ADMIN_EMAIL}/$ADMINEMAIL/g" "phpbb.sql"
	sed -i "s/{DIRECTORY}/$DIRECTORY/g" "phpbb.sql"
	sed -i "s/{TITLE}/$TITLE/g" "phpbb.sql"
	sed -i "s/{SITE_DESC}/$DEFAULTDESC/g" "phpbb.sql"
	
	sed -i "s/{HOST}/$DBHOST/g" "config_phpbb.php"
	sed -i "s/{DB_NAME}/$DBNAME/g" "config_phpbb.php"
	sed -i "s/{DB_USER}/$DBUSERNAME/g" "config_phpbb.php"
	sed -i "s/{DB_PASS}/$DBUSERPASS/g" "config_phpbb.php"
	
	# Move modified config to proper path
	mv "config_phpbb.php" "$FULLPATH/config.php"
	
	# Import mysql database
	mysql -h "$DBHOST" -u "$DBUSERNAME" -p"$DBUSERPASS" "$DBNAME" -f < "phpbb.sql";
	
	rm "phpbb.sql"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install" ]; then
		if [ ! -z "$FULLPATH" ] && [ "$FULLPATH" != "/" ]; then
			rm -Rf "$FULLPATH/install"
		fi
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function doDrupal11Install(){	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	CURDIR=$(pwd)
	cd "$DLSFolder"
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/drupal/drupal_11.sql ./
	cp /var/www/new/ehcp/scripts/curl_installer/script_presets/drupal/settings_11.php ./
	
	# Replace stuff 
	sed -i "s/{ADMIN_EMAIL}/$ADMINEMAIL/g" "drupal_11.sql"
	sed -i "s#/var/www/vhosts/test/test.com/httpdocs/drupal#$FULLPATH#g" "drupal_11.sql"
	sed -i "s#test.com/drupal#test.com/$DIRECTORY#g" "drupal_11.sql"
	sed -i "s#test.com#$DOMAINNAME#g" "drupal_11.sql"
	
	sed -i "s/{DB_HOST}/$DBHOST/g" "settings_11.php"
	sed -i "s/{DB_PASS}/$DBUSERPASS/g" "settings_11.php"
	sed -i "s/{DB_USER}/$DBUSERNAME/g" "settings_11.php"
	sed -i "s/{DB_NAME}/$DBNAME/g" "settings_11.php"
	
	# Move modified config to proper path
	mv "settings_11.php" "$FULLPATH/sites/default/settings.php"
	
	# Import mysql database
	mysql -h "$DBHOST" -u "$DBUSERNAME" -p"$DBUSERPASS" "$DBNAME" -f < "drupal_11.sql";
	
	rm "drupal_11.sql"
	
	# Set the correct permissions
	changeOwner "$FULLPATH"
	
	# Remove install files
	if [ -e "$FULLPATH/install.php" ]; then
		if [ ! -z "$FULLPATH" ] && [ "$FULLPATH" != "/" ]; then
			rm -Rf "$FULLPATH/install.php"
		fi
	fi
	
	# change back into original dir
	cd "$CURDIR"
}

function createCurlLog(){
	CURLLOG="/var/www/new/ehcp/scripts/curl_installer/curl_php_log.conf"
	if [ ! -e "$CURLLOG" ]; then
		> "$CURLLOG"
	fi
	changeOwner "$CURLLOG"
	
	DLSFolder="/root/downloads/"
	if [ ! -e "$DLSFolder" ]; then
		mkdir -p "$DLSFolder"
	fi
}

function changeOwner(){
	# $1 will be directory
	if [ ! -z "$1" ]; then
		chown -R "${WWWUser}:${WWWGroup}" "$1"
	fi
}

#################
# Main App Code #
#################
createCurlLog

case "$SCRIPTNAME" in
        wordpress)
            doWordPressInstall
            ;;
        joomla3)
            doJoomlaInstall
            ;;
        smf2)
            doSMF2Install
            ;;
        phpbb)
            doPHPBBInstall
            ;;
        drupal7)
            doDrupalInstall
            ;;
        phpcoin)
            doPHPCoinInstall
            ;;
        smf1)
            doSMF1Install
            ;;
        mybb)
            doMYBBInstall
            ;;
        joomla5)
            doJoomla5Install
            ;;
        drupal11)
            doDrupal11Install
            ;;
        phpbb33)
            doPHPBB33Install
            ;;
         
        *)
            echo $"Usage: $0 {start|stop|restart|condrestart|status}"
            exit 1
 
esac



