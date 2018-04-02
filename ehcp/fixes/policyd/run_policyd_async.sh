#!/bin/bash
if [ ! -e "/var/run/cbpolicyd" ]; then
	mkdir -p "/var/run/cbpolicyd"
	chown cbpolicyd:cbpolicyd -R "/var/run/cbpolicyd"
fi

while ! mysql -u {policyDMySQLUser} -p{policyDMySQLPass}  -e ";" ; do
       echo -e "Can't connect to MySQL, retrying in one second."
       sleep 1
done

/usr/local/sbin/cbpolicyd
