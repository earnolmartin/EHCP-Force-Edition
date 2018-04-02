#!/bin/bash
# Stops the EHCP daemon
# Used by systemd
# Moved commands here since systemd character escaping is retarded

ps aux | grep ehcpdaemon2 | awk '{print $2}' | xargs kill -9 > /dev/null 2>&1
ps aux | grep "index.php daemon" | awk '{print $2}' | xargs kill -9 > /dev/null 2>&1
