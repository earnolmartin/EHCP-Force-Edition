#!/bin/bash
# Returns the major and minor version for mariadb-server package
# Needed by EHCP Force Installer (called by PHP during install)
# By Eric Arnol-Martin <earnolmartin@gmail.com>

function indexOf(){ 
	# $1 = search string
	# $2 = string or char to find
	# Returns -1 if not found
	x="${1%%$2*}"
	[[ $x = $1 ]] && echo -1 || echo ${#x}
}

mariaDBVersion=$(apt-cache policy mariadb-server | grep -o "Candidate:.*" | grep -o "[^Candidate: ].*")
	
posOfFirstDecimal=$(indexOf "$mariaDBVersion" ".")
if [ "$posOfFirstDecimal" -ge "0" ]; then
	mariaDBMajorVersion=$(echo ${mariaDBVersion:0:$posOfFirstDecimal})
	stringMinusFirstIndexChar=$(echo ${mariaDBVersion:$((posOfFirstDecimal+1))})
	if [ ! -z "$stringMinusFirstIndexChar" ]; then
		posOfSecondDecimal=$(indexOf "$stringMinusFirstIndexChar" ".")
		if [ "$posOfSecondDecimal" -ge "0" ]; then
			mariaDBMinorVersion=$(echo ${stringMinusFirstIndexChar:0:$posOfSecondDecimal})
		fi
	fi
fi
	
if [ ! -z "$mariaDBMajorVersion" ] && [ ! -z "$mariaDBMinorVersion" ]; then
	echo -n "${mariaDBMajorVersion}.${mariaDBMinorVersion}"
fi
