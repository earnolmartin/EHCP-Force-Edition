#!/bin/bash
# Cronjobs Builder for EHCP
# Written by Eric Arnol-Martin (earnolmartin@gmail.com)

if [ -z "$1" ]; then
	echo -e "You must send a file path containing cronjob commands"
else
	ehcpCrontabFile="$1"
	currentCronJobsFile="/var/www/new/ehcp/currentCronJobs.conf"
	# Get current cronjobs
	currentCronJobs=$(crontab -l)
	
	# If the EHCP Comment does not exist, add it:
	ehcpComment="# EHCP Generated Entries # Place Custom Edits Above This Line Or They WILL BE LOST"
	ehcpLocate=$(crontab -l | grep -i "$ehcpComment")
	if [ -z "$ehcpLocate" ]; then
		currentCronJobs="$currentCronJobs\n$ehcpComment"
	fi
	
	echo -e "$currentCronJobs" > "$currentCronJobsFile"

	

	# Looks for the EHCP comment and returns all lines in the hosts file above it and resaves the host file minus ehcp entries
	# Not working as expected if the file starts with the pattern:
	# nonEHCPEntries=$( sed -n "1,/$ehcpComment/ p" "$currentCronJobsFile" | grep -vi "$ehcpComment")
	
	# Use awk instead!
	# https://unix.stackexchange.com/questions/11305/grep-show-all-the-file-up-to-the-match
	nonEHCPEntries=$(awk "/$ehcpComment/ {exit} {print}" "$currentCronJobsFile")
	
	if [ ! -z "$nonEHCPEntries" ]; then
		ehcpCronJobs=$(cat "$ehcpCrontabFile")
		allCronJobs="$nonEHCPEntries\n$ehcpComment\n$ehcpCronJobs"
		# Add EHCP records to crontab
		echo -e "$allCronJobs" > "$ehcpCrontabFile"
	else
		ehcpCronJobs=$(cat "$ehcpCrontabFile")
		allCronJobs="$ehcpComment\n$ehcpCronJobs"
		# Add EHCP records to crontab
		echo -e "$allCronJobs" > "$ehcpCrontabFile"
	fi
	
	# Remove current crontab
	crontab -r
	
	# Set crontab to file
	crontab "$ehcpCrontabFile"
	
fi




