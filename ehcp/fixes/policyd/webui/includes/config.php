<?php

# mysql:host=xx;dbname=yyy
#
# pgsql:host=xx;dbname=yyy
#
# sqlite:////full/unix/path/to/file.db?mode=0666
#
#$DB_DSN="sqlite:////tmp/cluebringer.sqlite";
$DB_DSN="mysql:host=localhost;dbname=policyd";
$DB_USER="{ehcpusername}";
$DB_PASS="{ehcppass}";
$DB_TABLE_PREFIX="";


#
# THE BELOW SECTION IS UNSUPPORTED AND MEANT FOR THE ORIGINAL SPONSOR OF V2
#

#$DB_POSTFIX_DSN="mysql:host=localhost;dbname=postfix";
#$DB_POSTFIX_USER="root";
#$DB_POSTFIX_PASS="";

?>
