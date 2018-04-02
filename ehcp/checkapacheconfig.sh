#!/bin/bash
#checks for common apache problems


cd /etc/apache2

echo "Below are some apache Include and DocumentRoot lines. If you see some non-related data, you need to delete them in apache config files... So that ehcp can function normally."
grep Include * -R
grep DocumentRoot * -R
