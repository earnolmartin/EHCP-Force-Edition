<?php
// host where your mysql db is, generally localhost
$dbhost='localhost';
$skipupdatediskquota=true;

// default username to connecto to ehcp db ... 1234
$dbusername='ehcp';

// user password
$dbpass='test';

// ehcp database name
$dbname='ehcp';

$dbrootpass='test';
// mysql root password, used for db creation/deletion


$defaultlanguage='en';

/*
common daemon commands for mysql
These are used from command line, when ehcp webbased gui is not accessible, or for any other reason.

go to server console:

mysql -p

use ehcp;

-- then:

-- reset to non-ssl mod all apache settings.
update misc set value='apache2' where name='webservertype';
update misc set value='nonssl' where name='webservermode';
insert into operations (op) values ('fixapacheconfignonssl');

-- rebuilds apache config:
insert into operations (op) values ('syncdomains');

-- rebuild bind dns config:
insert into operations (op) values ('syncdns');

-- to reset template for your ehcp web-gui:
update misc set `value` = 'xp5-z7' WHERE `misc`.`name`='defaulttemplate' ;

-- or
update misc set `value` = 'ubuntu0.4.2' WHERE `misc`.`name`='defaulttemplate' ;
*/

?>