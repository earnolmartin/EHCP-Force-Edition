#!/bin/bash
# only calls nohup...

nohup /var/www/new/ehcp/ehcpdaemon2.sh >> /var/log/ehcp.log 2>&1 & 
