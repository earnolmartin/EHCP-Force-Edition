#!/bin/bash
# This small shell is written to re-run php based daemon in case of failure.. 
# because php based daemon quits sometime ocasionally or un-expectedly...
# Changed the way this works by using a function that respawns the php script if it exits.
# Changed by:  earnolmartin@gmail.com
function startPHPDaemon(){
	cd /var/www/new/ehcp
	until php index.php daemon ; do
	echo "Server php index.php daemon crashed with exit code -1.  Respawning..." >&2
	sleep 3
	done
	startPHPDaemon
}

# Start the initial loop
startPHPDaemon
