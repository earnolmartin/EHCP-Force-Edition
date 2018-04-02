#!/bin/bash
# mysql root pass reset utility. by info@ehcpforce.tk

function rootCheck(){
	# Check to make sure the script is running as root
	if [ "$(id -u)" != "0" ]; then
		echo "This script must be run as root" 1>&2
		exit 1
	fi
}
clear
rootCheck
echo "This will reset your msyql root pass"
echo "Only continue if you lost mysql root pass and you know what you do"
echo "if you have other programs that use old mysql root pass, you need to fix them manually."
echo 
echo "press enter to continue or Ctrl-C to cancel"
read

echo
echo "Please wait..."
echo

service mysql stop
ps -ef | grep mysqld | while read mysqlProcess ; do kill -9  $(echo $mysqlProcess | awk '{ print $2 }') ; done
mysqld_safe --skip-grant-tables &
sleep 5
echo
echo
echo "Enter NEW mysql root pass:"
read newpass

echo "UPDATE mysql.user SET Password=PASSWORD('$newpass') WHERE User='root'; flush privileges;" | mysql -u root
service mysql restart
sed -i "s/^\$dbrootpass.*/\$dbrootpass='$newpass';/g" "/var/www/new/ehcp/config.php"
service ehcp restart
echo
echo
echo "mysql root password reset COMPLETE .... "

# UPDATE mysql.user SET Password=PASSWORD('1234') WHERE User='root'; flush privileges;
