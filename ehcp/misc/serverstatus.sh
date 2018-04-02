#!/bin/bash
echo ""
echo "=========================================================================="
echo -n "Hostname: "
hostname
uname -srm
echo ""
echo -n "PHP version: "
/usr/bin/php -nv | grep built | cut -d " " -f2
echo -n "MySQL version: "
/usr/bin/mysql -V | cut -d " " -f6 | cut -d "," -f1
echo -n "Apache version: "
/usr/sbin/apache2 -v | grep version | cut -d "/" -f2
echo "--------------------------------------------------------------------------"
echo -n "CPU number: "
cat /proc/cpuinfo | grep -c "processor"
cat /proc/cpuinfo | grep "model name"
cat /proc/cpuinfo | grep "cpu MHz"
cat /proc/cpuinfo | grep "cache size"
echo "--------------------------------------------------------------------------"
echo "Memory: used       free (MB)"
free -m | grep buffers/ca | cut -d":" -f2
echo "--------------------------------------------------------------------------"
df -h -t ext3
echo "--------------------------------------------------------------------------"
uptime
echo -n " hosts.deny: "
cat /etc/hosts.deny | grep ALL: | wc -l
#echo -n " Failed auth attempts in the log: "
#cat /var/log/auth.log | grep "authentication failure" | wc -l
echo "=========================================================================="
echo ""
