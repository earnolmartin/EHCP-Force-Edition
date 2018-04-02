#!/bin/bash
# Used by systemd to start policyd
# By Eric Arnol-Martin <earnolmartin@gmail.com>
if [ ! -e "/var/run/cbpolicyd" ]; then
	mkdir -p "/var/run/cbpolicyd"
	chown cbpolicyd:cbpolicyd -R "/var/run/cbpolicyd"
fi
/etc/cbpolicyd/run_policyd_async.sh
