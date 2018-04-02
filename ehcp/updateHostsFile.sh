#!/bin/bash
# Hosts File Manager for EHCP
# Written by Eric Arnol-Martin (earnolmartin@gmail.com)

if [ -z "$1" ]; then
	echo -e "You must send a string containing the host file entries related to EHCP"
else

	# Creates a backup or the current host file
	if [ ! -e /etc/hosts_backup ]; then
		cp /etc/hosts /etc/hosts_backup; 
	fi

	# If the EHCP Comment does not exist, add it:
	ehcpComment="# EHCP Generated Entries # Place Custom Edits Above This Line Or They WILL BE LOST"
	ehcpLocate=$(cat /etc/hosts | grep -i "$ehcpComment")
	if [ -z "$ehcpLocate" ]; then
		echo -e "\n$ehcpComment" >> /etc/hosts 
	fi

	# Looks for the EHCP comment and returns all lines in the hosts file above it and resaves the host file minus ehcp entries
	# nonEHCPEntries=$( sed -n "1,/$ehcpComment/ p" /etc/hosts | grep -vi "$ehcpComment")
	
	# Safer:
	nonEHCPEntries=$(awk "/$ehcpComment/ {exit} {print}" "/etc/hosts")
	
	if [ ! -z "$nonEHCPEntries" ]; then
		echo -e "$nonEHCPEntries" | tee /etc/hosts > /dev/null
		# Add EHCP records to host file
		echo -e "\n$ehcpComment" >> /etc/hosts 
		echo -e "$1" >> /etc/hosts
	else
		echo -e "Your hosts file is empty and messed up!  Please fix it!"
	fi
	
fi




