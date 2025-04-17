CREATE TABLE IF NOT EXISTS `scripts` (
  `id` tinyint(4) NOT NULL auto_increment,
  `scriptname` varchar(50) default NULL,
  `homepage` varchar(50) default NULL,
  `description` text,
  `filetype` varchar(15) default NULL,
  `fileinfo` varchar(200) default NULL,
  `scriptdirtocopy` varchar(50) default NULL,
  `commandsaftercopy` text,
  `customfileownerships` text,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8 COMMENT='ehcp db - stores scripts that can be installed in ehcp';

INSERT INTO `scripts` (`id`, `scriptname`, `homepage`, `description`, `filetype`, `fileinfo`, `scriptdirtocopy`, `commandsaftercopy`, `customfileownerships`) VALUES
(1, 'WordPress', NULL, 'Newest Version', 'directurl', 'https://wordpress.org/latest.zip', 'wordpress', '', NULL),
(2, 'Joomla5', NULL, 'Joomla 5.2.6 (PHP >= 8.1+)', 'directurl', 'https://ehcpforce.ezpz.cc/files/Joomla_5.2.6-Stable-Full_Package.zip', '', '', NULL),
(3, 'Drupal11', NULL, 'Drupal 11.1.1 (PHP >= 8.3+)', 'directurl', 'https://ehcpforce.ezpz.cc/files/drupal-11.1.1.zip', 'drupal-11.1.1', '', NULL),
(4, 'MyBB', NULL, 'MyBB Version 1.8.38', 'directurl', 'https://ehcpforce.ezpz.cc/files/mybb_1838_install.zip', '', '', NULL),
(5, 'SMF2', NULL, 'Simple Machine Forum Software Version 2.1.4', 'directurl', 'https://ehcpforce.ezpz.cc/files/smf_2-1-4_install.zip', '', '', NULL),
(6, 'SMF1', NULL, 'Simple Machine Forum Software Version 1.1.21 (PHP < 8)', 'directurl', 'https://ehcpforce.ezpz.cc/files/smf_1-1-21_install.zip', '', '', NULL),
(7, 'phpBB', NULL, 'phpBB Forum Software Version 3.1.3', 'directurl', 'https://ehcpforce.ezpz.cc/files/phpBB-3.1.3.zip', 'phpBB3', '', NULL),
(8, 'phpCOIN', NULL, 'phpCOIN Billing Software Force Edition Latest from SVN (PHP < 7)', 'directurl', 'https://ehcpforce.ezpz.cc/files/phpcoin_force_edition_stable_snapshot.zip', 'phpCOIN', '', NULL),
(9, 'Drupal7', NULL, 'Drupal CMS Version 7.35', 'directurl', 'https://ehcpforce.ezpz.cc/files/drupal-7.35.zip', 'drupal-7.35', '', NULL),
(10, 'Joomla3', NULL, 'Joomla 3.4.0', 'directurl', 'https://ehcpforce.ezpz.cc/files/Joomla_3.4.0-Stable-Full_Package.zip', '', '', NULL);

CREATE TABLE IF NOT EXISTS scripts_log (
  id tinyint(4) NOT NULL auto_increment,
  host varchar(30) default NULL,
  scriptname varchar(50) default NULL,
  dir text,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  link varchar(200) default NULL,
  PRIMARY KEY  (id)
)  COMMENT='ehcp db - stores script install logs that are installed through ehcp easy install scripts';

CREATE TABLE IF NOT EXISTS servers (
  id smallint(6) NOT NULL auto_increment,
  servertype varchar(10) default NULL,
  ip varchar(30) default NULL,
  accessip varchar(30) default NULL,
  mandatory char(1) default NULL,
  location varchar(20) default NULL,
  password varchar(20) default NULL,
  defaultmysqlhostname varchar(30) default NULL,
  PRIMARY KEY  (id)
) COMMENT='ehcp db - servers that are to be used with ehcp, multi server concept';

CREATE TABLE IF NOT EXISTS directories (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  username varchar(30) default NULL,
  password varchar(30) default NULL,
  directory varchar(100) NOT NULL,
  expire date default NULL,
  comment varchar(50) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - password protected directories';

CREATE TABLE IF NOT EXISTS subdomains (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  subdomain varchar(30) default NULL,
  domainname varchar(50) default NULL,
  homedir varchar(100) default NULL,
  ftpusername varchar(30) default NULL,
  comment varchar(50) default NULL,
  status varchar(10) default NULL,
  password varchar(20) default NULL,
  email varchar(50) default NULL,
  webserverips varchar(200) default NULL,
  apache2template text NULL,
  nginxtemplate text NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - subdomains';

CREATE TABLE IF NOT EXISTS customsettings (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  name varchar(30) default NULL,
  webservertype varchar(30) default NULL,
  `value` text,
  value2 text,
  comment varchar(50) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - custom http, custom dns for domains';

CREATE TABLE alias (
  host varchar(30) default NULL,
  address varchar(255) NOT NULL default '',
  goto text NOT NULL,
  domain varchar(255) NOT NULL default '',
  created datetime default NULL,
  modified datetime default NULL,
  active tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (address),
  KEY address (address)
) COMMENT='ehcp db - Postfix Admin - Virtual Aliases';

CREATE TABLE IF NOT EXISTS domains (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  webserverips varchar(200) default NULL,
  dnsserverips varchar(200) default NULL,
  mailserverips varchar(200) default NULL,
  mysqlserverips varchar(200) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  homedir varchar(100) default NULL,
  comment varchar(50) default NULL,
  status varchar(10) default NULL,
  serverip varchar(30) default NULL,
  diskquotaused int(4) default NULL,  -- Thanks to deconectat
  diskquota int(4) default NULL,
  diskquotaovernotified int(4) NULL,
  diskquotaoversince date NULL ,
  graceperiod int(4) default 7 ,
  apachetemplate text NULL,
  dnstemplate text NULL,
  aliases text NULL,
  apache2template text NULL,
  nginxtemplate text NULL,
  theorder int(11) default NULL,
  dnsmaster varchar(15) default NULL,
  redirect_to varchar(200) default NULL,
  ssl_cert text NULL,
  ssl_cert_key text NULL,
  ssl_cert_chain text NULL,
  ssl_wild_card bit(1) default b'0',
  ssl_use_letsenc bit(1) default b'0',
  ssl_redirect_https bit(1) default b'0',
  ssl_lets_enc_additional_hosts text NULL,
  PRIMARY KEY  (id),
  KEY domainname (domainname)
) comment='ehcp db - list of domains and their properties';

CREATE TABLE IF NOT EXISTS emailusers (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  mailusername varchar(30) default NULL,
  beforeat varchar(30) default NULL,
  password varchar(40) default NULL,
  email varchar(80) NOT NULL default '',
  status varchar(10) NULL default '',
  quota int(10) default '10485760',
  autoreplysubject varchar(100) default NULL,
  autoreplymessage text,
  PRIMARY KEY  (id),
  KEY email (email)
) COMMENT='ehcp db - email users of domains';

CREATE TABLE IF NOT EXISTS forwardings (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  source varchar(80) NOT NULL default '',
  destination text NOT NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - email forwardings';

CREATE TABLE ftpaccounts (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  ftpusername varchar(30) NOT NULL,
  password varchar(50) NOT NULL,
  domainname varchar(50) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  homedir varchar(100) default NULL,
  status varchar(10) default NULL,
  type varchar(10) default NULL,
  `datetime` datetime default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY ftpusername (ftpusername)
) comment='ehcp db - ftp accounts that are used in domains,ehcp,etc, used in vsftpd';

CREATE TABLE html (
  id varchar(30) NOT NULL default '0',
  htmlkodu longtext,
  htmlkodu2 longtext,
  aciklama varchar(50) default NULL,
  grup varchar(20) default NULL,
  PRIMARY KEY  (id),
  KEY id (id)
) comment='ehcp db - used in db style of templates, not used much now';

CREATE TABLE IF NOT EXISTS backups (
  id int(11) NOT NULL auto_increment,
  domainname varchar(100) default NULL,
  host varchar(30) default NULL,
  backupname varchar(100) default NULL,
  filename varchar(200) default NULL,
  date datetime default NULL,
  size bigint(20) default NULL,
  status varchar(100) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - list of backups done through ehcp gui';

CREATE TABLE log (
  id int(11) NOT NULL auto_increment,
  tarih datetime default NULL,
  panelusername varchar(50) default NULL,
  notified varchar(5) default NULL,
  ip varchar(30) default NULL,
  log varchar(60) default NULL,
  referrer varchar(100) default NULL,
  url varchar(100) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db -  some log entries, may not be used';

CREATE TABLE log2 (
  id int(11) NOT NULL auto_increment,
  panelusername varchar(30) default NULL,
  referrer varchar(80) default NULL,
  count int(11) default NULL,
  aciklama varchar(30) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - some log entries, may not be used';

CREATE TABLE IF NOT EXISTS misc (
  id int(11) NOT NULL auto_increment,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  name varchar(40) default NULL,
  `value` varchar(200) default NULL,
  longvalue text,
  comment varchar(100) default NULL,
  PRIMARY KEY  (id)
) COMMENT='ehcp db - Table for misc configruation of ehcp';

INSERT INTO misc (id, name, `value`, longvalue,comment) VALUES
(1, 'dnsip', '83.133.127.19', NULL,''),
(2, 'adminname', 'b', NULL,''),
(3, 'adminemail', 'b', NULL,''),
(5, 'ehcpdir', '/var/www/vhosts/ehcp', NULL,''),
(6, 'banner', '', 'this is banner.. you may write here something using <a href=\\"?op=options\\">server settings</a>\r\n<br><br>',''),
(7, 'defaulttemplate', 'picante', NULL,''),
-- (7, 'defaulttemplate', 'xp5-z7', NULL,''),
(8, 'defaultlanguage', 'en', NULL,''),
(9, 'updatehostsfile', 'on', NULL,''),
(10,'messagetonewuser', 'Dns servers for our server:\r\n...........\r\n\r\n(This will be sent to new users)', '',''),
(11, 'backupdir', '/var/backup', NULL,''),
(12, 'quotaupdateinterval', '6', NULL,'this is in hours, quota will be calculated in this interval'),
(13, 'webservertype', 'apache2', NULL,'apache2 or nginx, (or any other that is supported)'),
(14, 'webservermode', 'nonssl', NULL,'ssl or nonssl'),
(15, 'mysqlcharset', 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', NULL,'Default charset/collation for newly added databases'),
(16, 'enablewebstats', 'on',null,'Webalizer web stats'),
(17, 'versionwarningcounter', '5',null,'')
;

CREATE TABLE mysqldb (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  dbname varchar(30) default NULL,
  aciklama varchar(30) NOT NULL default '',
  PRIMARY KEY  (id)
) comment='ehcp db - list of mysql databases, related to ehcp';

CREATE TABLE mysqlusers (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  panelusername varchar(30) default NULL,
  domainname varchar(50) default NULL,
  dbname varchar(64) default NULL,
  dbusername varchar(30) default NULL,
  password varchar(32) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - list of mysql users related to ehcp';

CREATE TABLE operations (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  user varchar(30) default NULL,
  ip varchar(30) default NULL,
  op varchar(50) default NULL,
  status varchar(15) default NULL,
  tarih datetime default NULL,
  try smallint(6) default '0',
  info text default NULL,
  info2 text default NULL,
  info3 text default NULL,
  action varchar(50) default NULL,
  PRIMARY KEY  (id)
) comment='ehcp db - list of pending/done daemon operations, misc operations.. ';


CREATE TABLE panelusers (
  id int(11) NOT NULL auto_increment,
  host varchar(30) default NULL,
  reseller varchar(30) default NULL,
  domainname varchar(50) default NULL,
  panelusername varchar(30) default NULL,
  password varchar(40) default NULL,
  email varchar(80) NOT NULL default '',
  quota int(20) default '10485760',
  maxdomains int(11) default NULL,
  maxemails int(11) default NULL,
  maxpanelusers int(11) default NULL,
  maxftpusers int(6) default NULL,
  maxdbs int(11) default NULL,
  status varchar(10) default NULL,
  name varchar(100) default NULL,
  comment varchar(100) default NULL,
  theme_color varchar(7) default NULL,
  theme_contrast varchar(10) default NULL,
  master_reseller tinyint(1) default 0,
  maxsubdomains int(11) default 10,
  PRIMARY KEY  (id)
) comment='ehcp db - panel users, clients, resellers';

INSERT INTO panelusers (id, reseller, domainname, panelusername, password, email, quota, maxdomains, maxemails, maxpanelusers, maxftpusers, maxdbs, status, name) VALUES
(1, 'admin', '', 'admin', '81dc9bdb52d04dc20036dbd8313ed055', 'admin@admindomain.com', 0, 50000, 50000, 50000, 50000, 50000, 'active', NULL);

CREATE TABLE transport (
	domainname varchar(128) NOT NULL default '',
	transport varchar(128) NOT NULL default '',
	UNIQUE KEY domainname (domainname)
) comment='ehcp db - email transport maps';

DROP TABLE IF EXISTS `hash`;
CREATE TABLE IF NOT EXISTS `hash` (
  `email` varchar(100) COLLATE utf8_general_ci NOT NULL DEFAULT 'NULL',
  `hash` varchar(100) COLLATE utf8_general_ci DEFAULT NULL,
  KEY `email_index` (`email`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='to store password remind hash';

# these are net2ftp tables for logging etc...
DROP TABLE IF EXISTS net2ftp_logAccess;
DROP TABLE IF EXISTS net2ftp_log_access;
CREATE TABLE net2ftp_log_access (id int(10) unsigned NOT NULL auto_increment,date date NOT NULL,time time NOT NULL,remote_addr text NOT NULL,remote_port text NOT NULL,http_user_agent text NOT NULL,page text NOT NULL,datatransfer int(10) unsigned default '0',executiontime mediumint(8) unsigned default '0',ftpserver text NOT NULL,username text NOT NULL,state text NOT NULL,state2 text NOT NULL,screen text NOT NULL,directory text NOT NULL,entry text NOT NULL,http_referer text NOT NULL,KEY index1 (id));
DROP TABLE IF EXISTS net2ftp_logError;
DROP TABLE IF EXISTS net2ftp_log_error;
CREATE TABLE net2ftp_log_error (date date NOT NULL,time time NOT NULL,ftpserver text NOT NULL,username text NOT NULL,message text NOT NULL,backtrace text NOT NULL,state text NOT NULL,state2 text NOT NULL,directory text NOT NULL,remote_addr text NOT NULL,remote_port text NOT NULL,http_user_agent text NOT NULL,KEY index1 (date,time,ftpserver(100),username(50)));
DROP TABLE IF EXISTS net2ftp_logConsumptionFtpserver;
DROP TABLE IF EXISTS net2ftp_log_consumption_ftpserver;
CREATE TABLE net2ftp_log_consumption_ftpserver(date date NOT NULL,ftpserver varchar(255) NOT NULL default '0',datatransfer int(10) unsigned default '0',executiontime mediumint(8) unsigned default '0',PRIMARY KEY  (date,ftpserver));
DROP TABLE IF EXISTS net2ftp_logConsumptionIpaddress;
DROP TABLE IF EXISTS net2ftp_log_consumption_ipaddress;
CREATE TABLE net2ftp_log_consumption_ipaddress(date date NOT NULL,ipaddress varchar(15) NOT NULL default '0',datatransfer int(10) unsigned default '0',executiontime mediumint(8) unsigned default '0',PRIMARY KEY  (date,ipaddress));
DROP TABLE IF EXISTS net2ftp_users;
CREATE TABLE net2ftp_users (ftpserver varchar(255) NOT NULL default '0',username text NOT NULL,homedirectory text NOT NULL,KEY index1 (ftpserver,username(50)));

CREATE TABLE IF NOT EXISTS `vps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reseller` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `panelusername` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `vpsname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `hostip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `ip2` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `netmask` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `broadcast` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `gateway` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `image_template` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `cdimage` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ram` int(11) DEFAULT NULL,
  `cpu` int(11) DEFAULT NULL,
  `state` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ping` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `hdimage` varchar(200) DEFAULT NULL,
  `vncpassword` varchar(20) DEFAULT NULL,
  `addvpscmd` text default null,
  PRIMARY KEY (`id`)
)  DEFAULT CHARSET=utf8 COMMENT='ehcp db - list of domains and their properties';


CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `reseller` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `panelusername` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `value` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `longvalue` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `comment` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  PRIMARY KEY (`id`)
)  DEFAULT CHARSET=utf8 COMMENT='ehcp db - Table for misc configruation of ehcp';

CREATE TABLE IF NOT EXISTS `remote_backups` (
  `id` tinyint(4) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `dayofweek` tinyint(1) default NULL,
  `time` varchar(2),
  `transfer_method` varchar(20) default NULL,
  `transfer_host` varchar(100) default NULL,
  `transfer_port` varchar(5) default NULL,
  `transfer_login` varchar(50) default NULL,
  `transfer_pass` varchar(50) default NULL,	
  `encryption_pass` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8 COMMENT='Used to schedule cronjobs for remote backups';

CREATE TABLE IF NOT EXISTS `cronjobs` (
  `id` tinyint(4) NOT NULL auto_increment,
  `script` varchar(100) default NULL,
  `dayofweek` tinyint(1) default NULL,
  `time` varchar(2),
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8 COMMENT='Used to run any cronjobs an admin may want to run';

CREATE TABLE IF NOT EXISTS `pwd_dirs` (
  id int(11) NOT NULL auto_increment,
  domainname varchar(100) default NULL,
  domainpath varchar(300) default NULL,
  protected_dir varchar(50) default NULL,
  username varchar(50) default NULL,
  password varchar(50) default NULL,
  PRIMARY KEY  (id)
) comment='Used for password protected directories';

CREATE TABLE IF NOT EXISTS `hosting_plans` (
  id int(11) NOT NULL auto_increment,
  name varchar(200) default NULL,
  master_reseller tinyint(1) default 0,
  max_panelusers int(11) default NULL,
  max_ftpusers smallint(6) default NULL,
  max_dbs int(11) default NULL,
  max_emails int(11) default NULL,
  max_domains int(11) default NULL,
  max_subdomains int(11) default NULL,
  quota int(20) default '10485760',
  panelusername varchar(30) NOT NULL default 'admin',
  PRIMARY KEY  (id)
) comment='Used for admin defined hosting plans';

CREATE TABLE IF NOT EXISTS `hosting_plans` (
  id int(11) NOT NULL auto_increment,
  name varchar(200) default NULL,
  master_reseller tinyint(1) default 0,
  max_panelusers int(11) default NULL,
  max_ftpusers smallint(6) default NULL,
  max_dbs int(11) default NULL,
  max_emails int(11) default NULL,
  max_domains int(11) default NULL,
  max_subdomains int(11) default NULL,
  quota int(20) default '10485760',
  panelusername varchar(30) NOT NULL default 'admin',
  PRIMARY KEY  (id)
) comment='Used for admin defined hosting plans';

CREATE TABLE IF NOT EXISTS `globalwebservertemplates` (
	template_name varchar(100) NOT NULL,
	template_webserver_type varchar(100) NOT NULL,
	template_ssl_type varchar(100) NOT NULL,
	template_value text default NULL,
	PRIMARY KEY  (template_name, template_webserver_type, template_ssl_type)
) comment='Used for custom global webserver templates';
