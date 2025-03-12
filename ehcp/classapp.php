<?php
$ehcpversion = "1.1.1";

/*
* 
   EASY HOSTING CONTROL PANEL FORCE EDITION MAIN CLASS FILE - https://ehcpforce.ezpz.cc
		https://ehcpforce.ezpz.cc
		* Information about latest updates can be seen here:  https://ehcpforce.ezpz.cc/forums/index.php?topic=15.msg44
*/

error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING ^ E_NOTICE);
date_default_timezone_set('Europa/Istanbul'); # this is mandatory in php 5.3 and up for date commands

include_once(dirname(__FILE__) . '/adodb5/adodb.inc.php'); # bu dirname muhabbeti: classapp, başka biyerden bile include edilse, bunların calısması icin.. fix for this: http://www.ehcp.net/?q=comment/2921#comment-2921 ; with this code, no chdir should be required in api
include_once(dirname(__FILE__) . '/localutils.php');
@include_once(dirname(__FILE__) . '/module.php');
include_once(dirname(__FILE__) . '/config/randomstring.php');


# bu harici fonksiyonlar, localutils icine tasinacak..

class Application
{
	var $appname = '', $sitename = 'ehcp', $headers = "From: info@ehcpforce.ezpz.cc";
	var $output = '', $requirePassword = True, $checkinstall = True, $miscconfig = null;
	var $queries = array(), $selecteddomain = '', $isreseller = false;

	var $wwwuser = "ftp"; # these vars should be used are "user related" places, to unify all user settings..  #equivalent: var $wwwowner="vsftpd:www-data";
	var $ftpuser = "ftp"; # equivalent: var $ftpowner="vsftpd:www-data";

	var $wwwgroup = "www-data";
	var $ftpgroup = "www-data"; # with this config, ftp user is able to see/delete files written by webserver. 

	var $webstats_auth_file = "webstats_auth_file";
	var $csvCCTLD = array();

	var $binduser = "bind"; # we need to know which user bind runs under

	# debuglevel: 4: shows some functions, 3: shows queries
	var $debuglevel = 0;


	var $myversion = '';
	var $dbhost;
	var $dbname;
	var $dbusername;
	var $dbpass;
	var $conn;
	var $link;

	// Status messages
	var $errorMessage;
	var $successMessage;

	var $defaultlanguage = "en", $clientip, $referer;
	var $currentlanguage = 'en';

	var $status_active = "active", $status_passive = "passive", $statusActivePassive, $passivefilt, $activefilt, $isDemo = false;
	var $emailfrom = 'info@ehcpforce.ezpz.cc';
	# language strings will be defined in $lang['en']['error1']   in language/en.php or so on...

	var $usertable, $dnsemail, $template, $templatefile, $op, $userinfo;
	var $dnszonetemplate = "dnszonetemplate";
	var $dnsnamedconftemplate = "dnsnamedconftemplate"; # aynen apache gibi oluturulacak...
	var $dnsnamedconftemplate_slave = "dnsnamedconftemplate_slave"; # for slave DNS replication

	var $activeuser, $isloggedin, $globalfilter, $commandline = false, $erroroccured = false;
	var $connected_mysql_servers = array();
	var $ehcpdir = '';
	var $tr = "<tr class='list'>", $td = "<td class='list'>", $th = "<th class='list'>";
	var $ehcpForceSplitString = "{EHCP_EAM_FORCE_SPLIT_STRING2015}";
	var $ehcpInstallPath = "/var/www/new/ehcp/";
	var $ehcpDownloadPath = "/var/www/new/ehcp/downloads";

	var $conf = array(
		# config section
		# yavas yavas conf sistemine gecmek lazim. aslinda kod icinde sabit bilgi kullanmamak lazim. string bile... ama nerdee...
		# this is like configuration of many system settings, tablenames etc. by this, changing something is easier, without need to change code..
		# apache and dns defs:

		'adminname' => 'ehcpdeveloper',
		'adminemail' => 'ehcpdeveloper@gmail.com',
		'wwwbase' => '/var/www',
		'ehcpdir' => '',
		'vhosts' => '/var/www/vhosts',
		'namedbase' => '/etc/bind',
		'dnsip' => '10.0.0.10',
		'dnsemail' => 'your.email.here',

		# mysql definitions
		'mysqlrootuser' => 'root',
		# this is for db creation.
		'mysqlrootpass' => '12345',

		# ehcp db table definitions, this is to make code more db-independent...
		'logintable' => array(
			'tablename' => 'panelusers',
			'passwordfunction' => 'md5',
			'usernamefield' => 'panelusername',
			'passwordfield' => 'password'
		),
		'settingstable2' => array(
			'tablename' => 'settings',
			'createtable' =>
			"CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `reseller` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `panelusername` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `value` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  `longvalue` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `comment` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `group` (`group`,`reseller`,`panelusername`,`name`,`value`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ehcp db - Table for settings of ehcp'"
		),
		'vpstable' => array(
			'tablename' => 'vps',
			'listfields' => array('vpsname', 'ip', 'ip2', 'ram', 'description', 'reseller', 'panelusername', 'hostip', 'state', 'image_template'),
			'linkimages' => array('images/incele.jpg', 'images/poweron.gif', 'images/poweroff.gif', 'images/pause.gif', 'images/edit2.gif', 'images/delete1.jpg'),
			'linkfiles' => array('?op=vps&op2=select', '?op=vps&op2=start', '?op=vps&op2=shutoff', '?op=vps&op2=pause', '?op=vps&op2=edit', '?op=vps&op2=delete'),
			'linkfield' => 'vpsname',
			'checkfields' => array(
				'addvpscmd' => 'text',
				'ip2' => 'varchar(20)',
				'cdimage' => 'varchar(100)'
			),
			'createtable' =>
			"CREATE TABLE `vps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reseller` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `panelusername` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `status` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `vpsname` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `hostip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `netmask` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `broadcast` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `gateway` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `image_template` varchar(100) DEFAULT NULL,
  `ram` int(11) DEFAULT NULL,
  `cpu` int(11) DEFAULT NULL,
  `state` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ping` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `hdimage` varchar(200) DEFAULT NULL,  
  `addvpscmd` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ehcp db - list of vps and their properties'"

		),

		'domainstable' => array(
			'tablename' => 'domains',
			'ownerfield' => 'panelusername',
			'resellerfield' => 'reseller',
			'domainfields' => array('id', 'reseller', 'panelusername', 'domainname', 'status', 'comment'),
			'listfields' => array('id', 'reseller', 'panelusername', 'domainname', 'webserverips', 'status', 'diskquotaused', 'diskquota'),
			'checkfields' => array(
				'serverip' => 'varchar(30)',
				'dnsserverips' => 'varchar(200)',
				'webserverips' => 'varchar(200)',
				'mailserverips' => 'varchar(200)',
				'mysqlserverips' => 'varchar(200)',
				'host' => 'varchar(30)',
				'apachetemplate' => 'text',
				'apache2template' => 'text',
				'nginxtemplate' => 'text',
				'dnstemplate' => 'text',
				'aliases' => 'text',
				'diskquotaused' => 'int(4)',
				'diskquota' => 'int(4)',
				'diskquotaovernotified' => 'int(4)',
				'diskquotaoversince' => 'date',
				'graceperiod' => 'int(4) default 7',
				'theorder' => 'int(11)',
				'dnsmaster' => 'varchar(15) default NULL',
				'redirect_to' => 'varchar(200) default NULL',
				'ssl_cert' => 'text',
				'ssl_cert_key' => 'text',
				'ssl_cert_chain' => 'text',
				'ssl_wild_card' => 'bit(1) default b\'0\'',
				'ssl_use_letsenc' => 'bit(1) default b\'0\'',
				'ssl_redirect_https' => 'bit(1) default b\'0\'',
				'ssl_lets_enc_additional_hosts' => 'text',
				'dns_serial' => 'int(11) default 1',
			)
		),
		'domainstable2' => array(
			'tablename' => 'domains',
			'listfields' => array('domainname')
		),
		'subdomainstable' => array(
			'tablename' => 'subdomains',
			'listfields' => array('reseller', 'panelusername', 'subdomain', 'domainname', 'homedir', 'ftpusername', 'comment', 'is_using_custom_template'),
			'listlabels' => array('Reseller', 'Username', 'Subdomain', 'Domain', 'Home Directory', 'FTP Username', 'Comment', 'Using Custom Template', 'Delete', 'Edit Template'),
			'linkimages' => array('images/delete1.jpg', 'images/editapachetemplate.png'),
			'linkfiles' => array('?op=delsubdomain', '?op=editapachetemplatesubdomain'),
			'linkfield' => 'id',
			'checkfields' => array(
				'ftpusername' => 'varchar(30)',
				'password' => 'varchar(20)',
				'email' => 'varchar(50)',
				'webserverips' => 'varchar(200)',
				'apache2template' => 'text',
				'nginxtemplate' => 'text'
			)
		),

		'globalwebservertemplatestable' => array(
			'tablename' => 'globalwebservertemplates',
			'listfields' => array('template_name', 'template_webserver_type', 'template_value'),
			// Password protected directories
			'createtable' => "CREATE TABLE IF NOT EXISTS `globalwebservertemplates` (
			  template_name varchar(100) NOT NULL,
			  template_webserver_type varchar(100) NOT NULL,
			  template_ssl_type varchar(100) NOT NULL,
			  template_value text default NULL,
			  PRIMARY KEY  (template_name, template_webserver_type, template_ssl_type)
			) comment='Used for custom global webserver templates';"
		),

		'paneluserstable' => array(
			'tablename' => 'panelusers',
			'resellerfield' => 'reseller',
			'usernamefield' => 'panelusername',
			'passwordfield' => 'password',
			'listlabels' => array('ID', 'Reseller', 'Username', 'Max Domains', 'Max Emails', 'Quota', 'Max Panel Users', 'Max FTP Users', 'Max MySQL Databases', 'Full Name', 'Email', 'Theme Color', 'Theme Contrast', 'Master Reseller', 'Max Sub Domains', 'Edit User', 'Delete User'),
			'listfields' => array('id', 'reseller', 'panelusername', 'maxdomains', 'maxemails', 'quota', 'maxpanelusers', 'maxftpusers', 'maxdbs', 'name', 'email', 'theme_color', 'theme_contrast', 'master_reseller', 'maxsubdomains'),
			'clickimages' => array('images/edit.gif', 'images/delete1.jpg'),
			'clickfiles' => array('?op=editpaneluser', '?op=deletepaneluser'),
			'insertfields' => array(array('panelusername', 'lefttext' => 'Panel Username:'), array('password', 'password', 'lefttext' => 'Password:'), array('maxdomains', 'default' => 5, 'lefttext' => 'Max Domains:'), array('maxsubdomains', 'default' => 15, 'lefttext' => 'Max Sub Domains:'), array('maxemails', 'default' => 20, 'lefttext' => 'Max Emails:'), array('quota', 'default' => 500, 'lefttext' => 'Quota in MB:'), array('master_reseller', 'checkbox', 'default' => 1, 'disabled' => '', 'lefttext' => 'Set as Master Reseller Account:', 'requires_admin' => true), array('maxpanelusers', 'default' => 0, 'lefttext' => 'Max Panel Users:'), array('maxftpusers', 'default' => 5, 'lefttext' => 'Max FTP Users:'), array('maxdbs', 'default' => 10, 'lefttext' => 'Max Databases:'), array('name', 'lefttext' => 'Full Name:'), array('email', 'lefttext' => 'Email Address:')),
			'insertfieldlabels' => array('Panel Username', 'Password', 'Max Domains', 'Max Subdomains', 'Max Emails', 'Quota (MB)', 'Master Reseller Account', 'Max Panel Users', 'Max FTP Users', 'Max MySQL Databases', 'Name', 'Email'),
			'mandatoryinsertfields' => array('panelusername'),
			# zorunlu insert alanlari
			'editfields' => array('maxdomains', 'maxsubdomains', 'maxemails', 'quota', 'maxpanelusers', 'maxftpusers', 'maxdbs', 'name', 'email', 'master_reseller'),
			# edit edildigi zaman görünecek alanlar..
			'editlabels' => array('Max Domains', 'Max Subdomains', 'Max Emails', 'Quota (MB)', 'Max Panel Users', 'Max FTP Users', 'Max MySQL Databases', 'Full Name', 'Email', 'Master Reseller'),
			# edit edildigi zaman görünecek alanlar..
			'checkbox_fields' => array('master_reseller'),
			'linkfield' => 'id',
			'checkfields' => array(
				'comment' => 'varchar(100)',
				'theme_color' => 'varchar(7)',
				'theme_contrast' => 'varchar(10)',
				'master_reseller' => 'tinyint(1) default 0',
				'maxsubdomains' => 'int(11) default 10',
			),
			'help' => 'description of this table... ehcp control panel users... '
		),

		'customstable' => array(
			# custom dns and http settings
			'tablename' => 'customsettings',
			'listfields' => array('id', 'domainname', 'name', 'comment', 'value', 'value2', 'webservertype'),
			'linkimages' => array('images/delete1.jpg'),
			'linkfiles' => array('?op=deletecustom'),
			'orderby' => 'id',
			'linkfield' => 'id',
			'checkfields' => array(
				'reseller' => 'varchar(30)',
				'panelusername' => 'varchar(30)',
				'domainname' => 'varchar(50)',
				'webservertype' => 'varchar(30)',
				'value2' => 'text'
			)
		),
		'emailuserstable' => array(
			'tablename' => 'emailusers',
			# going to be array as above..
			'listfields' => array('email', 'quota', 'domainname'),
			'linkimages' => array('images/delete1.jpg', 'images/edit.gif'),
			'linkfiles' => array('?op=userop&action=emailuserdelete', '?op=editemailuser'),
			'linkfield' => 'id',
			'ownerfield' => 'panelusername',
			'resellerfield' => 'reseller',
			# for use with email user logins
			'passwordfunction' => 'encrypt',
			'usernamefield' => 'email',
			'passwordfield' => 'password',

			'checkfields' => array(
				'reseller' => 'varchar(30)',
				'panelusername' => 'varchar(30)',
				'domainname' => 'varchar(50)',
				'status' => 'varchar(10)',
				'autoreplysubject' => 'varchar(100)',
				'autoreplymessage' => 'text'
			)

		),

		'ftpuserstable' => array(
			'tablename' => 'ftpaccounts',
			# going to be array as above..
			'ownerfield' => 'panelusername',
			'resellerfield' => 'reseller',
			'listfields' => array('domainname', 'ftpusername', 'status', 'homedir', 'type'),
			'checkfields' => array(
				'type' => 'varchar(10)',
				'reseller' => 'varchar(30)',
				'panelusername' => 'varchar(30)',
				'domainname' => 'varchar(50)',
				'homedir' => 'varchar(100)',
				'datetime' => 'datetime'

			)

		),

		'operations_table' => array(
			'tablename' => 'operations',
			# going to be array as above..
			'listfields' => array('id', 'user', 'ip', 'op', 'status', 'tarih', 'try', 'info', 'info2', 'info3', 'action'),
			'checkfields' => array(
				'info' => 'text',
				'info2' => 'text',
				'info3' => 'text',
				'user' => 'varchar(30)',
				'ip' => 'varchar(30)'
			)
		),

		'backups_table' => array(
			'tablename' => 'backups',
			'listfields' => array('id', 'domainname', 'backupname', 'filename', 'date', 'size', 'status'),
			'linkimages' => array('images/delete1.gif'),
			'linkfiles' => array('?op=backups&op2=delete&filename='),
			'linkfield' => 'filename',
			'checkfields' => array(
				'status' => 'varchar(100)',
				'domainname' => 'varchar(100)',
				'filename' => 'varchar(200)'
			)
		),

		'mysqldbstable' => array(
			'tablename' => 'mysqldb',
			'listfields' => array('domainname', 'dbname', 'host'),
			'linkimages' => array('images/delete1.jpg'),
			'linkfiles' => array('?op=domainop&action=deletedb'),
			'linkfield' => 'id',
			'checkfields' => array(
				'host' => 'varchar(30)',
				'reseller' => 'varchar(30)',
				'panelusername' => 'varchar(30)',
				'domainname' => 'varchar(50)'
			)
		),
		'mysqldbuserstable' => array(
			'tablename' => 'mysqlusers',
			'listfields' => array('domainname', 'dbname', 'dbusername', 'host'),
			'linkimages' => array('images/edit.gif'),
			'linkfiles' => array('?op=dbedituser'),
			'linkfield' => 'id',
			'checkfields' => array(
				'host' => 'varchar(30)',
				'reseller' => 'varchar(30)',
				'panelusername' => 'varchar(30)',
				'domainname' => 'varchar(50)',
				'password' => 'varchar(32)',
				'dbname' => 'varchar(64)'
			)

		),
		'serverstable' => array(
			'tablename' => 'servers',
			'listfields' => array('servertype', 'ip', 'accessip', 'mandatory', 'location'),
			'linkimages' => array('images/edit.gif'),
			'linkfiles' => array('?op=editserver'),
			'linkfield' => 'id',
			'checkfields' => array('accessip' => 'varchar(30)')
		),
		'emailforwardingstable' => array(
			'tablename' => 'forwardings',
			'listfields' => array('panelusername', 'domainname', 'source', 'destination'),
			'linkimages' => array('images/delete1.jpg'),
			'linkfiles' => array('?op=delemailforwarding'),
			'linkfield' => 'id',
			'checkfields' => array(
				'reseller' => 'varchar(30)',
				'panelusername' => 'varchar(30)',
				'domainname' => 'varchar(50)'
			)
		),
		'transporttable' => array(
			# for email autoreply
			'tablename' => 'transport',
			'createtable' =>
			"
CREATE TABLE transport (
	domainname varchar(128) NOT NULL default '',
	transport varchar(128) NOT NULL default '',
	UNIQUE KEY domainname (domainname)
) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci 
"
		),
		'settingstable' => array(
			'tablename' => 'misc',
			'checkfields' => array(
				'name' => 'varchar(40)',
				'panelusername' => 'varchar(30)'
			)
		),
		'scriptstable' => array(
			'tablename' => 'scripts',
			'checkfields' => array(
				'homepage' => 'varchar(50)',
				'description' => 'text',
				'customfileownerships' => 'text'
			)
		),
		'daemonopstable' => 'operations',
		'hashtable' => array(
			'tablename' => 'hash',
			'createtable' => "
CREATE TABLE IF NOT EXISTS `hash` (
  `email` varchar(100) COLLATE utf8_general_ci NOT NULL DEFAULT 'NULL',
  `hash` varchar(100) COLLATE utf8_general_ci DEFAULT NULL,
  KEY `email_index` (`email`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='to store password remind hash'
			"

		),

		// Start of new tables

		// Remote backups table
		'remote_backups_table' => array(
			'tablename' => 'remote_backups',
			'createtable' => "
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
			",
			'checkfields' => array(
				'encryption_pass' => 'varchar(50)'
			)

		),

		// Cronjob table
		'cronjobs_table' => array(
			'tablename' => 'cronjobs',
			'createtable' => "
CREATE TABLE IF NOT EXISTS `cronjobs` (
  `id` tinyint(4) NOT NULL auto_increment,
  `script` varchar(100) default NULL,
  `dayofweek` tinyint(1) default NULL,
  `time` varchar(2),
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COMMENT='Used to run any cronjobs an admin may want to run';
			"

		),

		// Password protected directories
		'pwd_dirs_table' => array(
			'tablename' => 'pwd_dirs',
			'createtable' => "CREATE TABLE IF NOT EXISTS `pwd_dirs` (
			  id int(11) NOT NULL auto_increment,
			  domainname varchar(100) default NULL,
			  domainpath varchar(300) default NULL,
			  protected_dir varchar(50) default NULL,
			  username varchar(50) default NULL,
			  password varchar(50) default NULL,
			  PRIMARY KEY  (id)
			) comment='Used for password protected directories';"
		),

		// Admin defined hosting plans
		'hosting_plans_table' => array(
			'tablename' => 'hosting_plans',
			'createtable' => "CREATE TABLE IF NOT EXISTS `hosting_plans` (
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
			) comment='Used for admin defined hosting plans';",
			'checkfields' => array(
				'panelusername' => "varchar(30) NOT NULL default 'admin'",
			)
		),

		'logtable' => array(
			'tablename' => 'log',
			'listfields' => array('tarih', 'panelusername', 'notified', 'ip', 'log'),
			'checkfields' => array(
				'panelusername' => 'varchar(50)',
				'notified' => 'varchar(5)'
			)
		)


	);

	/*
	for almost every record:
	panelusername: who is the owner of that, who setupd that. cannot be empty.
	resellername: who is the reseller of that panelusername. cannot be empty.
	domainname: to which domain is that related. empty if not related to domainname

	*/

	/////////////////////
	// The constructor //
	/////////////////////
	function __construct()
	{
		global $skipUpdateWebstats, $debuglevel;

		// Get the right FTP username since Ubuntu 15 and up decided to use a different username...
		$wwwUser = $this->getWWWUser();

		// Get the BIND9 DNS user
		$bindUser = $this->getBindUser();

		// Get the service name for php-fpm
		$phpFPMName = $this->getPHPFPMName();

		// Set our global public class members to the value
		$this->wwwuser = $wwwUser;
		$this->ftpuser = $wwwUser;

		if ($phpFPMName) {
			$this->php_fpm_name = $phpFPMName;
		}

		// Set global bind user variable
		if ($this->hasValueOrZero($bindUser)) {
			$this->binduser = $bindUser;
		}

		// Load mysql settings from configuration
		include('config.php');
		$this->set_ehcp_dir(dirname(__FILE__));
		$this->vhostsdir = $this->conf['vhosts'];

		// Set variables
		$this->dbhost = $dbhost;
		$this->dbusername = $dbusername;
		$this->dbpass = $dbpass;
		$this->dbname = $dbname;
		$this->conf['mysqlrootpass'] = $dbrootpass;
		$this->defaultlanguage = $defaultlanguage;
		$this->isDemo = $isdemo;
		if (!(isset($this->isDemo)))
			$this->isDemo = False;
		$this->statusActivePassive = array("active" => "active", "passive" => "passive");

		/* Remote Backup Select List Vars */
		$this->remoteBackupMethod = array("ftp" => "FTP", "scp" => "SCP");
		$this->remoteBackupTime = array("0" => "00:00", "6" => "06:00", "12" => "12:00", "18" => "18:00");
		$this->remoteDayOfWeek = array("0" => "Sunday", "1" => "Monday", "2" => "Tuesday", "3" => "Wednesday", "4" => "Thursday", "5" => "Friday", "6" => "Saturday");

		if (isset($this->debuglevel)) {
			#....
		}
		$debuglevel = $this->debuglevel; # for outer functions using debuglevel

		$this->clientip = getenv("REMOTE_ADDR");
		if (!validateIpAddress($this->clientip)) {
			$this->clientip = "";
		}
		$this->referer = getenv("HTTP_REFERER");

		$this->wwwowner = $this->wwwuser . ':' . $this->wwwgroup;
		$this->ftpowner = $this->ftpuser . ':' . $this->ftpgroup;

		// Generate missing SSL files if needed.
		$this->generateSslFiles();
	}

	// --------------------   //
// All other functions   //
// -------------------- //
	function run()
	{
		$this->debugecho("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4, false);
		#$this->serverPlan=new serverPlan();
		# $this->output.=$this->debug();
		global $commandline;
		$this->commandline = $commandline;
		$this->initialize();
		# this is actual application runner, maps urls to functions..
		$this->runOp($this->op);
		$this->show();
	}

	function runOp($op)
	{ # these are like url to function mappers...  maps op variable to some functions in ehcp; This also can be seen as a controller in MVC model. 
		global $id, $domainname, $op2, $_insert;
		$this->getVariable(array('id', 'domainname', 'op2', '_insert'));
		$op = strtolower($op);
		$otheroperations = array('advancedsettings');


		switch ($op) {

			case 'failedlogins':
				return $this->failedlogins();
				break;

			#ssl related:
			case 'adjust_ssl':
				return $this->adjust_ssl();
				break;
			case 'pagerewrite':
				return $this->pagerewrite();
				break;

			# other
			case 'activate':
				return $this->activate();
				break;
			case 'settings':
				return $this->settings();
				break;
			case 'adjust_system':
				return $this->adjust_system();
				break;
			case 'redirect_domain':
				return $this->redirect_domain();
				break;
			case 'information':
				return $this->information($id);
				break;

			#multi-server operations:
			case 'multiserver_add_domain':
				return $this->multiserver_add_domain();
				break;

			case 'new_sync_all':
				return $this->new_sync_all();
				break;
			case 'sync_server_services':
				return $this->sync_server_services();
				break;
			case 'new_sync_domains':
				return $this->new_sync_domains();
				break;
			case 'new_sync_dns':
				return $this->new_sync_dns();
				break;
			case 'multiserver_add_ftp_user_direct':
				return $this->gui_multiserver_add_ftp_user_direct();
				break;

			#single-server operations:
			case 'bulkaddemail':
				return $this->bulkAddEmail();
				break;
			case 'whitelist':
				return $this->whitelist();
				break;
			case 'fixmailconfiguration':
				return $this->fixMailConfiguration();
				break;
			case 'dofixmailconfiguration':
				return $this->addDaemonOp('fixmailconfiguration', '', '', '', 'fix mail configuration');
				break;
			case 'dofixapacheconfigssl':
				return $this->addDaemonOp('fixApacheConfigSsl', '', '', '', 'fixApacheConfigSsl');
				break;
			case 'dofixapacheconfigsslonly':
				return $this->addDaemonOp('fixApacheConfigSslOnly', '', '', '', 'fixApacheConfigSslOnly');
				break;
			case 'dofixapacheconfignonssl':
				return $this->addDaemonOp('fixApacheConfigNonSsl', '', '', '', 'fixApacheConfigNonSsl');
				break;
			case 'dofixapacheconfignonssl2':
				return $this->addDaemonOp('fixApacheConfigNonSsl2', '', '', '', 'fixApacheConfigNonSsl2');
				break;
			case 'rebuild_webserver_configs':
				return $this->rebuild_webserver_configs();
				break;
			case 'configure_anon_ftp':
				return $this->configure_anon_ftp();
				break;

			case 'updatediskquota':
				return $this->updateDiskQuota();
				break;
			case 'doupdatediskquota':
				$this->addDaemonOp('updatediskquota', '', $domainname, '', 'update disk quota');
				return $this->displayHome();
				break;

			#editing of dns/apache templates for domains, on ehcp db
			case 'editdnstemplate':
				return $this->editDnsTemplate();
				break;
			case 'editapachetemplate':
				return $this->editApacheTemplate();
				break;
			case 'editapachetemplatesubdomain':
				return $this->editApacheTemplateSubdomain();
				break;
			case 'editdomainaliases':
				return $this->editDomainAliases();
				break;

			case 'changedomainserverip':
				return $this->changedomainserverip();
				break;
			case 'warnings':
				break; # this will be written just before show..
			case 'bulkadddomain':
				return $this->bulkaddDomain();
				break;
			case 'bulkdeletedomain':
				return $this->bulkDeleteDomain();
				break;
			case 'exportdomain':
				return $this->exportDomain();
				break;

			case 'adddnsonlydomain':
				return $this->addDnsOnlyDomain();
				break;

			// Slave DNS
			case 'addslavedns':
				return $this->addSlaveDNS();
				break;
			case 'removeslavedns':
				return $this->removeSlaveDNS();
				break;

			// Custom FTP
			case 'addcustomftp':
				return $this->addCustomFTP();
				break;
			case 'removecustomftp':
				return $this->removeCustomFTP();
				break;
			case 'resetallcustomtemplates':
				return $this->resetAllCustomTemplates();
				break;

			// Global Website Apache / Nginx Templates
			case 'manageglobalwebtemplates':
				return $this->manageGlobalWebTemplates();
				break;
			case 'getglobalwebtemplate':
				return $this->getGlobalWebTemplate();
				break;

			// Is PolicyD installed:
			case 'ispolicydinstalled':
				return $this->isPolicyDInstalled();
				break;

			// Get public server settings:
			case 'getpublicserversettings':
				return $this->getPublicServerSettings();
				break;

			// Remote backups
			case 'addremotebackup':
			case 'editremotebackup':
				return $this->addRemoteBackup();
				break;
			case 'removeremotebackup':
				return $this->removeRemoteBackup();
				break;

			// Password directories
			case 'addpasswordprotecteddir':
				return $this->addPasswordProtectedDir();
				break;
			case 'rmpasswordprotecteddir':
				return $this->rmPasswordProtectedDIR();
				break;

			// Admin defined hosting plans
			case 'addhostingplan':
				return $this->addHostingPlan();
				break;
			case 'removehostingplan':
				return $this->removeHostingPlan();
				break;

			// Theming stuff
			case 'updatethemecolor':
				$this->updateThemeColor();
				break;
			case 'updatethemecontrast':
				$this->updateThemeContrast();
				break;
			case 'getmydomainsobject':
				$this->getMyDomainsAsObject();
				break;

			// Cronjobs
			case 'addcronjob':
				return $this->addCronjob();
				break;
			case 'removecronjob':
				return $this->removeCronjob();
				break;

			// Move domain to another account
			case 'transferdomain':
			case 'movedomaintoanotheraccount':
				return $this->moveDomainToAnotherAccount();
				break;

			case 'adddnsonlydomainwithpaneluser':
				return $this->addDnsOnlyDomainWithPaneluser();
				break;

			case 'getselfftpaccount':
				return $this->getSelfFtpAccount();
				break;
			case 'adddomaintothispaneluser':
				return $this->addDomainToThisPaneluser();
				break;

			case 'dodownloadallscripts':
				return $this->doDownloadAllscripts();
				break;
			case 'choosedomaingonextop':
				return $this->chooseDomainGoNextOp();
				break;

			case 'getmysqlserver':
				return $this->getMysqlServer();
				break;

			case 'emailforwardingsself':
				return $this->emailForwardingsSelf();
				break;
			case 'addemailforwardingself':
				return $this->addEmailForwardingSelf();
				break;

			case 'cmseditpages':
				return $this->cmsEditPages();
				break;
			case 'listservers':
				return $this->listServers();
				break;
			case 'addserver':
				return $this->addServer();
				break;
			case 'addiptothisserver':
				return $this->add_ip_to_this_server();
				break;
			case 'setactiveserverip':
				return $this->set_active_server_ip();
				break;


			case 'advancedsettings':
				return $this->advancedsettings();
				break;
			case 'delemailforwarding':
				return $this->delEmailForwarding();
				break;
			case 'addemailforwarding':
				return $this->addEmailForwarding();
				break;
			case 'emailforwardings':
				return $this->emailForwardings();
				break;
			case 'addscript':
				return $this->addScript();
				break;
			case 'addnewscript':
				return $this->addNewScript();
				break;

			case 'suggestnewscript':
				return $this->suggestnewscript();
				break;
			case 'updateinstallscriptsql':
				return $this->updateInstallScriptSQL();
				break;
			case 'listselectdomain':
				return $this->listselectdomain();
				break;
			case 'selectdomain':
				return $this->selectdomain($id);
				break;
			case 'deselectdomain':
				return $this->deselectdomain();
				break;
			case 'otheroperations':
				return $this->otheroperations();
				break;


			case 'loadconfig':
				return $this->loadConfig();
				break;
			#case 'showconf'					: return $this->showConfig();break;
			case 'changemypass':
				return $this->changeMyPass();
				break;

			case 'dostopapache2':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'apache2', 'info2' => 'stop'));
				break;
			case 'dostartapache2':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'apache2', 'info2' => 'start'));
				break;
			case 'dorestartapache2':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'apache2', 'info2' => 'restart'));
				break;

			case 'dostopnginx':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'nginx', 'info2' => 'stop'));
				break;
			case 'dostartnginx':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'nginx', 'info2' => 'start'));
				break;
			case 'dorestartnginx':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'nginx', 'info2' => 'restart'));
				break;

			case 'dostopphp5fpm':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => $this->php_fpm_name, 'info2' => 'stop'));
				break;
			case 'dostartphp5fpm':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => $this->php_fpm_name, 'info2' => 'start'));
				break;
			case 'dorestartphp5fpm':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => $this->php_fpm_name, 'info2' => 'restart'));
				break;

			case 'dostopvsftpd':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'vsftpd', 'info2' => 'stop'));
				break;
			case 'dostartvsftpd':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'vsftpd', 'info2' => 'start'));
				break;
			case 'dorestartvsftpd':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'vsftpd', 'info2' => 'restart'));
				break;

			case 'dostopbind':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'bind9', 'info2' => 'stop'));
				break;
			case 'dostartbind':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'bind9', 'info2' => 'start'));
				break;
			case 'dorestartbind':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'bind9', 'info2' => 'restart'));
				break;

			case 'dostoppostfix':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'postfix', 'info2' => 'stop'));
				break;
			case 'dostartpostfix':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'postfix', 'info2' => 'start'));
				break;
			case 'dorestartpostfix':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'service', 'info' => 'postfix', 'info2' => 'restart'));
				break;


			case 'donewsyncdomains':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'new_sync_domains'));
				break;
			case 'donewsyncdns':
				$this->requireAdmin();
				return $this->add_daemon_op(array('op' => 'new_sync_dns'));
				break;

			case 'dosyncdomains':
				$this->requireAdmin();
				return $this->addDaemonOp('syncdomains', '', '', '', 'sync domains');
				break;
			case 'dosyncdns':
				$this->requireAdmin();
				return $this->addDaemonOp('syncdns', '', '', '', 'sync dns');
				break;
			case 'dosyncftp':
				$this->requireAdmin();
				return $this->addDaemonOp('syncftp', '', '', '', 'sync ftp for nonstandard homes');
				break;
			case 'options':
				return $this->options();


			case 'backups':
				return $this->backups();
				break;
			case 'dobackup':
				return $this->doBackup();
				break;
			case 'dorestore':
				return $this->doRestore();
				break;
			case 'listbackups':
				return $this->listBackups();
				break;

			# these sync functions are executed in daemon mode.
			case 'updatehostsfile':
				return $this->updateHostsFile();
				break;
			case 'syncdomains':
				return $this->syncDomains();
				break;
			case 'handlecouriersslcert':
				return $this->handleCourierSSLCert();
				break;
			case 'handlevsftpdsslcert':
				return $this->handleVSFTPDSSLCert();
				break;
			case 'handlepostfixsslcert':
				return $this->handlePostfixSSLCert();
				break;
			case 'resynccourierssl':
				return $this->resyncCourierSSL();
				break;
			case 'resyncvsftpdssl':
				return $this->resyncVSFTPDSSL();
				break;
			case 'resyncpostfixssl':
				return $this->resyncPostfixSSL();
				break;
			case 'syncftp':
				return $this->syncFtp();
				break;
			case 'rebuild_crontab':
				return $this->rebuildCrontab();
				break;
			case 'process_pwd_dirs':
				return $this->handlePasswordProtectedDirs();
				break;
			case 'handle_reset_sites_enabled_default':
				return $this->handleResetSitesEnabledDefault();
				break;
			case 'handle_reset_mainwebserverconf':
				return $this->handleResetMainWebServerConf();
				break;
			case 'process_ssl_certs':
				return $this->handleCustomSSLCertsForDomains();
				break;
			case 'syncdns':
				return $this->syncDns();
				break;
			case 'syncall':
				return $this->syncAll();
				break;
			case 'fixapacheconfigssl':
				return $this->fixApacheConfigSsl();
				break;
			case 'fixapacheconfigsslonly':
				return $this->fixApacheConfigSslOnly();
				break;
			case 'fixapacheconfignonssl':
				return $this->fixApacheConfigNonSsl();
				break;


			#case 'syncallnew'	: return $this->syncallnew();break;
			case 'listdomains':
				return $this->listDomains();
				break; # ayni zamanda domain email userlarini da listeler.
			case 'subdomains':
				return $this->subDomains();
				break;
			case 'addsubdomain':
				return $this->addSubDomain();
				break;
			case 'addsubdomainwithftp':
				return $this->addSubDomainWithFtp();
				break;
			case 'addsubdirectorywithftp':
				return $this->addSubDirectoryWithFtp();
				break;


			case 'delsubdomain':
				return $this->delSubDomain();
				break;


			case 'editdomain':
				return $this->editdomain();
			case 'listpassivedomains':
				return $this->listDomains('', $this->passivefilt);
				break;
			case 'phpinfo':
				return $this->phpinfo();
				break;
			case 'help':
				return $this->help();
				break;
			case 'syncpostfix':
				return $this->syncpostfix();
				break;
			case 'listemailusers':
				return $this->listemailusers();
				break;
			case 'listallemailusers':
				return $this->listallemailusers();
				break;
			case 'listpanelusers':
				return $this->listpanelusers();
				break;
			case 'resellers':
				return $this->resellers();
				break;

			case 'deletepaneluser':
				return $this->deletepaneluser();
				break;

			case 'operations':
				$this->requireAdmin();
				$this->listTable('operations', 'operations_table', '');
				break;

			case 'listallftpusers':
				return $this->listAllFtpUsers();
				break;
			case 'listftpusersrelatedtodomains':
				return $this->listAllFtpUsers("domainname<>''");
				break;
			case 'listftpuserswithoutdomain':
				return $this->listAllFtpUsers("domainname='' or domainname is null");
				break;
			case 'listftpusers':
				return $this->listftpusers();
				break;
			case 'sifrehatirlat':
				return $this->sifreHatirlat();
				break;
			case 'todolist':
				return $this->todolist();
				break;
			case 'adddomain':
				return $this->addDomain();
				break;
			case 'adddomaineasy':
				return $this->addDomainEasy();
				break;
			case 'adddomaineasyip':
				return $this->addDomainEasyip();
				break;
			case 'deletedomain':
				return $this->deleteDomain();
				break;
			case 'addemailuser':
				return $this->addEmailUser();
				break;
			case 'addftpuser':
				return $this->addFtpUser();
				break;
			case 'addftptothispaneluser':
				return $this->addFtpToThisPaneluser();
				break; # added in 7.6.2009
			case 'add_ftp_special':
				return $this->add_ftp_special();
				break;

			case 'userop':
				return $this->userop();
				break;
			case 'domainop':
				return $this->domainop();
				break;
			case 'addmysqldb':
				return $this->addMysqlDb();
				break;
			case 'addmysqldbtouser':
				return $this->addMysqlDbtoUser();
				break;
			case 'addpaneluser':
				return $this->addPanelUser();
				break;
			case 'addpaneluserwithpredefinedplan':
				return $this->addPanelUserWithHostingPlan();
				break;
			case 'editpaneluser':
				return $this->editPanelUser();
				break;
			case 'impersonatepaneluser':
				return $this->impersonatePanelUser();
				break;
			case 'editftpuser':
				return $this->editFtpUser();
				break;

			// Domain ordering settings which are no longer used
			// case 'domainsettings'			: return $this->domainSettings();break;

			case 'logout':
				return $this->logout();
				break;
			case 'daemon':
				return $this->daemon();
				break;
			case 'test':
				return $this->test();
				break;
			case 'aboutcontactus':
				return $this->aboutcontactus();
				break;
			case 'applyforaccount':
				return $this->applyforaccount();
				break;
			case 'applyfordomainaccount':
				return $this->applyfordomainaccount();
				break;
			case 'setconfigvalue2':
				return $this->setConfigValue2($id);
				break;
			case 'customhttp':
				return $this->customHttpSettings();
				break;
			case 'addcustomhttp':
				return $this->addCustomHttp();
				break;
			case 'deletecustom':
				return $this->deleteCustomSetting();
				break;
			case 'customdns':
				return $this->customDnsSettings();
				break;
			case 'addcustomdns':
				return $this->addCustomDns();
				break;
			case 'dbedituser':
				return $this->dbEditUser();
				break;
			case 'dbadduser':
				return $this->dbAddUser();
				break;

			case 'custompermissions':
				return $this->custompermissions();
				break;
			case 'addcustompermission':
				return $this->addcustompermission();
				break;

			case 'editemailuser': # same as below
			case 'editemailuserself':
				return $this->editEmailUser();
				break;

			case 'editemailuserautoreplyself':
			case 'editemailuserautoreply':
				return $this->editEmailUserAutoreply();
				break;

			case 'editemailuserpasswordself':
			case 'editemailuserpassword':
				return $this->editEmailUserPassword();
				break;
			case 'changetemplate':
				return $this->changetemplate();
				break;
			case 'addredirect':
				return $this->addRedirect();
				break;
			case 'adddomainsslcert':
				return $this->addDomainSSLCert();
				break;
			case 'serverstatus':
				return $this->serverStatus();
				break;
			case 'setlanguage':
				$this->setLanguage($id);
				$this->displayHome();
				break;
			case 'setdefaultdomain':
				$this->setDefaultDomain();
				$this->displayHome();
				break;

			case 'dologin': # default anasayfa, same as below:
			case '':
				$this->displayHome();
				break;

			# virtual machine (vps) opcodes:
			case 'vps_home':
				return $this->call_func_in_module('Vps_Module', 'vps_home');
				break;
			case 'vps':
				return $this->call_func_in_module('Vps_Module', 'vps');
				break;
			case 'vps_mountimage':
				return $this->call_func_in_module('Vps_Module', 'vps_mountimage');
				break;
			case 'vps_dismountimage':
				return $this->call_func_in_module('Vps_Module', 'vps_dismountimage');
				break;
			case 'add_vps':
				return $this->call_func_in_module('Vps_Module', 'add_vps');
				break;


			default:
				return $this->errorText("Internal EHCP Error - Undefined Operation: $op <br> This feature may not be complete.");
				break;

		} # switch
		return True;

	} # func runop

	function getWWWUser()
	{
		return determineFTPUserFromCMD();
	}

	function getBindUser()
	{
		return determineBindUserFromCMD();
	}

	function getPHPFPMName()
	{
		return determinePHPFPMName();
	}

	function set_ehcp_dir($dirname)
	{
		# extra variables will be removed later, only one should be used.
		$this->ehcpdir = $dirname;
		$this->mydir = $dirname;
		$this->conf['ehcpdir'] = $dirname;
	}

	function setInitialMiscConfigOptionDefaultsPostInstall()
	{
		// Should only really be called once post EHCP installation
		// Don't call this function more than once or config values could be reset.
		$success = $this->setConfigValue('disableeditapachetemplate', 'Yes'); // Prevent apache2 template modifications by non-admin users...
		$success2 = $this->setConfigValue('disableeditdnstemplate', 'Yes'); // Prevent dns template modifications by non-admin users...
		$success3 = $this->setConfigValue('forcedeletesubdomainfiles', 'Yes'); // Delete files under subdomain home directory when subdomain is deleted by default
		$success4 = $this->setConfigValue('useglobalsslcert', 'Yes'); // Use invalid system default global https cert for SSL sites by default
		$success5 = $this->setConfigValue('enablewildcarddomain', 'Yes'); // Use wildcard domains and DNS
		$success6 = $this->setConfigValue('allowcustomsslnonadmin', 'Yes'); // Use wildcard domains and DNS

		return $success && $success2 && $success3 && $success4 && $success5 && $success6;
	}

	function initialize()
	{
		# burda herhangi class initialization yapilacak.. basta yapilacak isler..
		global $commandline, $ehcpversion;
		#if(!$commandline)$this->output.="<font size=+2>".$this->appname."<br><br></font>";
		$this->myversion = $ehcpversion;

		if (isset($this->op) && !empty($this->op)) {
			$this->op = removeInvalidChars($this->op, "lettersandnumbers");
		}

		$this->connectTodb();
		$this->debugecho("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4, false);
		$this->syncConfigs();

		$this->passivefilt = "status<>'" . $this->status_active . "' or status is null";
		$this->activefilt = "status='" . $this->status_active . "'";
		$this->loadLanguage(); # load default en to handle errors in loadconfig,
		$this->checkInstall();
		$this->loadConfig();

		if (!$this->isNoPassOp() and $this->requirePassword)
			$this->securitycheck();
		$this->loadLanguage(); # load again to activate actual lang in config.

		if ($this->isadmin()) {
			$this->globalfilter = ''; # burasi, securitycheck den sonra olmali. isadmin yoksa calismaz.		
		} else
			$this->globalfilter = "(reseller='" . $this->activeuser . "' or panelusername='" . $this->activeuser . "')";

		if (!$this->isadmin()) {
			$userinfo = $this->query("select * from " . $this->conf['paneluserstable']['tablename'] . " where panelusername='$this->activeuser'", "dologin2");
			$this->userinfo = $userinfo[0];
			if ($this->userinfo['maxpanelusers'] > 0)
				$this->isreseller = True;
		}

		$this->loadServerPlan();

		$HTTPMode = $this->EHCPIsUsingHTTPS() ? "https://" : "http://";
		$this->url = $HTTPMode . $this->conf['dnsip'] . $_SERVER['REQUEST_URI'];

		if ($this->isloggedin)
			$this->output .= $this->check_failed_logins();
		if ($commandline)
			$this->echoln("Finished initialize");
		#$this->check_mysql_connection();
	}

	function counter_reached($counter, $count)
	{
		# can be used to count something... 
		if (intval($this->miscconfig[$counter]) > 0) {
			$nextval = intval($this->miscconfig[$counter]) - 1;
			$this->setConfigValue($counter, $nextval);
			return False; # check sometime, 
		}
		$this->setConfigValue($counter, $count);
		return True;
	}

	function check_ehcp_version()
	{
		global $ehcpversion;
		if ($this->latest_version <> '')
			return; # check once	
		if (!$this->counter_reached('versionwarningcounter', 20))
			return False; # check 20 login later again.

		$this->latest_version = trim(@file_get_contents("https://ehcpforce.ezpz.cc/latest_version.txt"));
		if ($this->latest_version <> '' and $this->latest_version <> $ehcpversion) {
			$str = "Your EHCP Force Edition version ($ehcpversion) is different than the latest version ($this->latest_version). Either your EHCP Force Edition installation is old, or you are using a new beta/test version. <a target=_blank href='https://ehcpforce.ezpz.cc'>Check here for updates</a>!";
			$this->warnings .= $str;
			$this->infotoadminemail($str, "Version Warning for " . $this->dnsip, false);
		}
	}

	function loadServerPlan()
	{
		$this->singleserverip = $this->conf['dnsip']; # if there is only one server...
		#$this->output.=__FUNCTION__.":".$this->singleserverip."<br>";
		# more servers will be coded here...
	}

	function load_module($name)
	{
		if (!defined($name . '_file_loaded')) {
			$this->echoln2(__FUNCTION__ . ": Sory, that module file not loaded yet. check code. (php autoload does not work with CLI)");
			return False;
		}
		if (gettype($this->$name) <> 'object')
			$this->$name = new $name($this, $name); # initialize new module, only if not done before.		
		return True;
	}

	function call_func_in_module($name, $func, $params = Null)
	{
		if (!$this->load_module($name))
			return False;

		if ($params == Null)
			return $this->$name->$func(); # a function with no args
		else
			return $this->$name->$func($params); # a func with named arguments (named array), as used in many parts of this file
	}

	function check_module_tables()
	{
		# to be coded later.
	}

	function activate()
	{
		$alanlar = array('panelusername', 'code', 'newpass');
		foreach ($alanlar as $al)
			global $$al;
		$degerler = $this->getVariable($alanlar);

		if ($panelusername) {
			$info = $this->getPanelUserInfo('', $panelusername);
			$email = $info['email'];
			if ($email == '')
				return;
		}


		if (!$panelusername) {
			$this->output .= inputform5(
				array(
					array('panelusername', 'lefttext' => 'Panel Username:')

				)
			);
		} elseif ($panelusername and !$code) {
			$hash = get_rand_id(10);
			$r = $this->executeQuery("insert into  hash (email,hash)values('$email','$hash')");
			$msg = "Dear EHCP Force User,<br><br>Your EHCP activation code is: $hash";
			$this->sendEmail($email, "EHCP Activation Code", $msg);
			$this->output .= "An activation code was sent to your email address of $email. Please insert this code now:<br>" . inputform5(
				array(
					array('panelusername', 'lefttext' => 'Panel Username:'),
					array('code', 'lefttext' => 'Code:'),
					array('newpass', 'lefttext' => 'New Password:')
				)
			);
		} elseif ($panelusername and $code) {
			$filt3 = "email='$email' and hash='$hash'";
			$sayi = $this->recordcount("hash", $filt3);
			if ($sayi == 0)
				$this->errorTextExit("Invalid activation information received. Please verify the activation code that was sent to your email.");

			if ($this->conf['logintable']['passwordfunction'] == '') {
				$set = "'$newpass'";
			} else {
				$set = $this->conf['logintable']['passwordfunction'] . "('$newpass')";
			}

			$this->executeQuery("update panelusers set status='active',{$this->conf['logintable']['passwordfield']}=$set where status='passive' and panelusername='$panelusername'");
		}

	}

	function pagerewrite()
	{
		global $op2, $_insert;
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$this->output .= "This function is being tested with nginx only <br>";

		$alanlar = array('frompage', 'topage', 'redirecttype');
		foreach ($alanlar as $al)
			global $$al;
		$degerler = $this->getVariable($alanlar);

		$table = $this->conf['customstable'];


		if ($op2 == 'add') {

			if ($_insert) {
				switch ($this->miscconfig['webservertype']) {
					case 'nginx':
						if ($redirecttype == 'exactmatch')
							$val = "rewrite ^($frompage)\$ $topage break; \n";
						else
							$val = "rewrite $frompage $topage break; \n";
						break;

					#case 'apache2:
					default:
						$this->errorTextExit("Your webserver does not support changing the EHCP GUI directory: " . $this->miscconfig['webservertype']);
				}

				#$q="insert into customsettings (panelusername,domainname,name,`value`,webservertype) values ('$this->activeuser','$domainname','pagerewrite','$val','".$this->miscconfig['webservertype']."')";
				#if($this->executeQuery($q)) $this->output.="Ekleme tamam - OK";
				$this->addCustomHttpDirect($domainname, $val, "pagerewrite");

			} else {
				$alanlar = array(array('frompage', 'righttext' => ''), array('topage'), array('redirecttype', 'radio', 'secenekler' => array('exactmatch' => 'Exact Match Ex: frompage: /basvuru topage: /en/basvuru.html', 'partialmatch' => 'Partial Match, nginx style, <a href=\'http://wiki.nginx.org/NginxHttpRewriteModule#rewrite\'>examples</a>')));
				$this->output .= inputform5($alanlar);
			}
			$this->output .= "nginx, For partial, examples: <br>
		frompage: ^(/download/.*)/media/(.*)\..*$ <br>
		topage: $1/mp3/$2.mp3 <br>
		<br>
		frompage: ^/users/(.*)$ <br>
		topage: /showuser.php?uid=$1 <br>";

		} else
			$this->listTable("Page redirects:", "customstable", "domainname='$domainname' and comment='pagerewrite'");

		$this->showSimilarFunctions("pagerewrite");
	}

	function upload_file($srcfile, $dstfile)
	{
		$srcfilename = $_FILES[$srcfile]['name'];

		$this->output .= "Copy (" . $_FILES[$srcfile]['tmp_name'] . ") -> " . $dstfile;

		if (copy($_FILES[$srcfile]['tmp_name'], $dstfile)) {
			$this->output .= "<br>Dosya yükleme başarılı<BR/>";
			$this->output .= "File Name :" . $_FILES[$srcfile]['name'] . "<BR/>";
			$this->output .= "File Size :" . $_FILES[$srcfile]['size'] . "<BR/>";
			$this->output .= "File Type :" . $_FILES[$srcfile]['type'] . "<BR/>";
		} else {
			$this->output .= "<br><big><b>Dosya yüklerken Hata oluştu ($path)</b></big><br>" . print_r2($_FILES);
		}
	}

	function adjust_ssl()
	{
		/*
	   # steps for ssl adjust
	   openssl genrsa -out server.key 2048
	   # prepare LocalServer.cnf
	   openssl req -new -key server.key -out server.csr -config LocalServer.cnf
	   # send your server.csr to your Certificate company. 
	   # upload key files in ehcp, 

			* */
		$alanlar = array("step", "_insert", 'country_name', 'state', 'city', 'company', 'unit_name', 'common_name', 'email', 'SSLCertificateKeyFile');
		foreach ($alanlar as $al)
			global $$al;
		$this->getVariable($alanlar);
		$this->requireAdmin(); # şimdilik fazla güvenlik almadım, ondan... 
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		# howto: http://www.digicert.com/ssl-certificate-installation-apache.htm
		$waitstr = "Your ssl config is being built now, <a href='?op=" . __FUNCTION__ . "&step=2'>wait until finished, retry here</a>";
		$file1 = $this->ehcpdir . "/upload/LocalServer_$domainname.cnf";
		$file2 = $this->ehcpdir . "/upload/ssl_generated"; # 


		if (!$step) {
			unlink($file1); # remove file if exists
			$params = array(
				array('country_name', 'righttext' => 'Country Name (2 letter code) [ex:TR]'),
				array('state', 'righttext' => 'State or Province Name (full name) [Some-State], ex: Yorks'),
				array('city'),
				array('company', 'righttext' => 'optional, Your organization name, i.e, company'),
				array('unit_name', 'righttext' => 'Organizational Unit Name (eg, section)'),
				array('common_name', 'righttext' => '(www.yourdomain.com, fqdn, or *.yourdomain.com to generate for all subdomains) <b><big></big>THIS IS MOST IMPORTANT PART</b> this should be the Fully Qualified Domain Name (FQDN) or the web address for which you plan to use your Certificate, e.g. the area of your site you wish customers to connect to using SSL. For example, an SSL Certificate issued for yourdomain.com will not be valid for secure.yourdomain.com, unless you use wildcard *.yourdomain.com'),
				array('email', 'righttext' => 'optional'),
				array('step', 'hidden', 'default' => '1')
			);

			$this->output .= "This is experimental, will be improved:<br>Step 1: CSR Generation:" . inputform5($params) . "  Skip to <a href='?op=" . __FUNCTION__ . "&step=2'>step 2</a> if you already generated your key files before.";
		} elseif ($step == 1) {
			$out = "
[ req ]
prompt			= no
distinguished_name	= server_distinguished_name

[ server_distinguished_name ]
commonName		= $common_name
stateOrProvinceName	= $state
countryName		= $country_name
emailAddress		= $email
organizationName	= $company
organizationalUnitName	= $unit_name";

			file_put_contents($file1, $out);
			$this->addDaemonOp('generate_ssl_config1', '', $domainname, $file1, 'generate_ssl_config');
			$this->output .= $waitstr;
		} elseif ($step == 2) {
			if (file_exists($file2))
				$this->output .= "Now, put/send your CSR (Certificate Signing Request) to your Certificate Company: <hr><pre>" . file_get_contents($this->ehcpdir . "/server.csr") . "</pre><hr> After sending, Your may proceed to <a href='?op=" . __FUNCTION__ . "&step=3'>step 3</a> for importing crt files. ";
			else
				$this->output .= $waitstr;
		} elseif ($step == 3) {

			$params = array(
				array('SSLCertificateFile', 'fileupload', 'righttext' => 'should be your domain certificate file (eg. your_domain_name.crt)'),
				array('SSLCertificateChainFile', 'fileupload', 'righttext' => 'should be the Chain certificate file, eg, certificate of certificate seller (eg. DigiCertCA.crt) '),
				array('step', 'hidden', 'default' => '2')
			);

			if (!file_exists($file2))
				$params[] = array('SSLCertificateKeyFile', 'fileupload', 'righttext' => 'should be the key file generated when you created the CSR, (your_private.key)'); # if generated externally, for uploading to server.
			# else $params[]=array('SSLCertificateKeyFile','hidden','default'=>'server.key'); # if generated by ehcp.

			$this->output .= "This is experimental, will be improved: Now, upload files provided by your Certificate company: <br>Step 2:" . inputform5($params);
		} elseif ($step == 2) {
			$files = array('SSLCertificateFile', 'SSLCertificateChainFile');
			if (!file_exists($file2))
				$files[] = 'SSLCertificateKeyFile';

			foreach ($files as $file) {
				$path = $this->ehcpdir . "upload/";
				$this->upload_file($file, $path);
			}
		}

	}

	#include_once('modules/module_index.php'); # verdigi hata: Parse error: syntax error, unexpected T_INCLUDE_ONCE, expecting T_FUNCTION in /var/www/new/ehcp/classapp.php on line 1044 

	function information($id, $link = false)
	{
		if ($link)
			return " - <a href=index.php?op=information&id=$id>?</a>";

		switch ($id) {
			case 1:
				$out = 'The translation of this item is not complete. see languages folder or launchpad translation section';
				break;
			default:
				$out = ' Information id is not provided/wrong. see function ' . __FUNCTION__ . ' in ' . __FILE__;
				break;
		}
		$this->output .= "<br><b>$out</b><br>";
	}

	function gui_multiserver_add_ftp_user_direct()
	{
		$params = array(
			'ftpserver' => '96.31.91.67',
			'panelusername' => 'admin22',
			'ftpusername' => 'bvdene',
			'ftppassword' => '1234',
			'homedir' => '/var/www/bvdene',
			'domainname' => 'bvdene.com'
		);
		#$this->output.="Adding ftp for remote:".print_r2($params);
		$this->multiserver_add_ftp_user_direct($params);
	}

	function checkFields($tb, $fields1, $fields2)
	{
		# $fields1: that should exist,
		# $fields2: that actually exist,
		if (!$fields1 or !$fields2)
			return;


		foreach ($fields1 as $field => $type) {
			$found = false;
			$needmodify = false;

			foreach ($fields2 as $fsearch) {

				if ($fsearch['Field'] == $field) {
					$found = True;
					$bulunantip = $fsearch['Type'];
					if ($fsearch['Default'] <> '') {
						if ($fsearch['Null'] == 'NO') {
							$bulunantip .= " NOT NULL";
						}
						$bulunantip .= " default ";
						if (!is_numeric($fsearch['Default']) && $fsearch['Default'] != "b'0'" && $fsearch['Default'] != "b'1'") {
							$bulunantip .= "'" . $fsearch['Default'] . "'";
						} else {
							$bulunantip .= $fsearch['Default'];
						}
					}
					if ($fsearch['Null'] == 'YES' and $fsearch['Default'] == '')
						$bulunantip .= " default NULL";
					if ($fsearch['Null'] == 'NO' and $fsearch['Default'] == '')
						$bulunantip .= " default NOT NULL";

					if (strstr($type, ' default ') === false) { # if requested field has not default, remove it again, to match existing. that is, ignore differences in "default null"
						$bulunantip = str_replace(array(" default NULL", " default NOT NULL"), array('', ''), $bulunantip);
					}

					if ($bulunantip != $type) {
						$needmodify = True;
						#$this->output.="field check ($field -> $type): ".print_r2($fsearch);
						$this->output .= "Need modify: current:[ $bulunantip ] Should be:[ $type ] <br>" . print_r2($fsearch);
					}
					break;
				}
			}

			if (!$found) {
				$query = "ALTER TABLE $tb ADD `$field` $type";
				if (!strpos($type, 'default'))
					$query .= " NULL";

				$this->output .= "<hr>This field is not found in database, fixing: $tb: $field, $type : query: $query , seting up new field..(this msg should appear once for this table/field) <hr>";
				$this->executeQuery($query);
			}

			if ($needmodify) {
				if (strstr($type, ' default ') === false)
					$type .= " default NULL";
				$query = "ALTER TABLE $tb change `$field` `$field` $type";
				$this->output .= "<hr>This field needs modification in database, fixing: $tb: $field, $type : query: $query , modifiying field..(this msg should appear once for this table/field) <hr>";
				$this->executeQuery($query);
			}

		}
	}

	function checkTableExists($tb)
	{
		$q = "show tables like '" . $tb['tablename'] . "'";
		$res = $this->query($q);
		if (count($res) == 0) {
			$this->output .= "<br>The table does not exist: " . $tb['tablename'];
			if ($tb['createtable'] <> '') {
				$this->executeQuery($tb['createtable']);
				$this->output .= " Table setup in mysql complete.. (this msg should appear once for this table/field)<br>";

			} else
				$this->output .= " but, the mysql createtable command is not defined in ehcp \$config, classapp.php";
		}
	}

	function some_table_fixes()
	{
		$qq = array(
			//"update scripts set customfileownerships='vsftpd:www-data#wp-content\nvsftpd:www-data#wp-admin' where scriptname like '%wordpress%'"
		);

		if (isset($qq) && is_array($qq) && count($qq) > 0) {
			foreach ($qq as $q)
				$this->executeQuery($q);
		}
	}

	function checkTables()
	{
		# checks ehcp db tables for old tables that may have some missing fields, and add those fields if not present... especially useful for old ehcp installations...
		# programmer should put new field definitions in conf variable in top of class.
		# in progress..
		foreach ($this->conf as $tb) {
			if (!is_array($tb))
				continue;
			if ($tb['tablename'] == '')
				continue; # skip non-table configurations..
			$this->checkTableExists($tb);

			#$this->output.="Checking table..: $tb ---> ".$tb['tablename']."<br>";
			$fields1 = $tb['checkfields'];
			$tb = $tb['tablename'];

			$fields2 = $this->query("SHOW COLUMNS FROM $tb");
			$this->checkFields($tb, $fields1, $fields2);
		}
		$this->check_module_tables();
		// $this->some_table_fixes();

		# other initialize  for old ehcp's	
		$this->executeQuery("update emailusers set status='active' where status is null or status=''");

	}

	function exportTable($tbname, $where = '', $withoutid = True)
	{ # export table data in mysql format


		$fields = $this->query("SHOW COLUMNS FROM $tbname");
		$query = "select * from $tbname";
		if ($where <> '')
			$query .= " where $where ";
		$res = $this->query($query);
		$this->output .= print_r2($res);
		$sql = "-- $tbname table data export \n";
		foreach ($res as $row) {
			$sql .= "insert into $tbname (";
			$fieldnum = 0;
			foreach ($fields as $field) { # build sql of : insert into table (f1,f2,f3) values ('','','');
				if (!($field['Field'] == 'id' and $withoutid)) {
					if ($fieldnum > 0)
						$sql .= ",";
					$sql .= $field['Field'];
					$fieldnum++;
				}
			}
			$sql .= ")values(";
			$fieldnum = 0;
			foreach ($fields as $field) {
				if (!($field['Field'] == 'id' and $withoutid)) {
					if ($fieldnum > 0)
						$sql .= ",";
					if (isNumericField($field['Type']))
						$quote = '';
					else
						$quote = "'";
					$sql .= $quote . $row[$field['Field']] . $quote;
					$fieldnum++;
				}
			}
			$sql .= ");\n";
		}
		$sql .= "\n";
		#$this->output.="<textarea cols=200 rows=10>$sql</textarea>";

	}

	function exportDomain()
	{
		# may be used by admin, i will do an export/transfer mechanism for transfering from/to non-admin accounts, for resellers..
		$this->exportTable($this->conf['domainstable']['tablename']);
		$this->exportTable($this->conf['ftpuserstable']['tablename']);
	}

	function syncConfigs()
	{
		# does sync of webmail plugin configs, or any other configs, ehcp user&pass is written on other config files.
# added in ver 0.29.13,  2010-01-01, in first hours of 2010, happy new year !
		if (!$this->commandline)
			return;

		$filecontents = "
<?
\$dbhost='" . $this->dbhost . "';
\$dbusername='" . $this->dbusername . "';
\$dbpass='" . $this->dbpass . "';
\$dbname='" . $this->dbname . "';
?>";

		writeoutput2("$this->ehcpdir/webmail/plugins/ehcp_change_pass/config.php", $filecontents, "w");

	}


	function setDefaultDomain()
	{
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$this->executeQuery("delete from misc where panelusername='$this->activeuser'");
		$this->executeQuery("insert into misc (panelusername,name,`value`) values('$this->activeuser','defaultdomain','$domainname')");
		$this->output .= "Domain is set as default: $domainname <br><br>";
	}

	function executeProg3($prog, $echooutput = False)
	{
		# executes program and return output
		if ($echooutput)
			echo "\n" . __FUNCTION__ . ": executing: ($prog)\n";
		exec($prog, $topcmd);
		if (!is_array($topcmd))
			return "";
		foreach ($topcmd as $t)
			$topoutput .= $t . "\n";
		$out = trim($topoutput);
		if ($echooutput and ($out <> ''))
			echo "\n$out\n";
		return $out;
	}

	function check_program_service($progname, $start_opname, $stop_opname, $restart_opname)
	{
		$this->output .= "<tr><td>$progname: </td><td>";
		$serviceCount = $this->executeProg3("ps ax | grep $progname | grep -v grep | wc -l");

		if ($serviceCount == 0 && $progname == "mysqld") {
			// Try mariadb instead
			$progname = "mariadbd";
			$serviceCount = $this->executeProg3("ps ax | grep $progname | grep -v grep | wc -l");
		}

		if ($serviceCount > 0)
			$this->output .= "<font color='#00cc00'><strong>YES</strong></font>";
		else
			$this->output .= "<font color='#ff0000'><strong>NO</strong></font>";

		if ($progname != "mysqld" && $progname != "mariadbd") {
			$this->output .= "</td><td> (<a href='?op=$start_opname'>Start</a> | <a href='?op=$stop_opname'>Stop</a> | <a href='?op=$restart_opname'>Restart</a>)  Attention, by stopping your services, you may lose your conn. to panel.</td></tr>";
		} else {
			$this->output .= "</td><td></td></tr>";
		}
	}

	function serverStatus()
	{
		$this->requireAdmin();
		#-------------- deconectat edit ---------------------------------------------------------
		#  ehcpdeveloper note: in fact, these html should be abstracted from source. left as of now.

		$this->output .= "<table>";

		// Show only the web server type that is activated
		if ($this->miscconfig['webservertype'] == "apache2") {
			$this->check_program_service('apache2', 'dostartapache2', 'dostopapache2', 'dorestartapache2');
		} else if ($this->miscconfig['webservertype'] == "nginx") {
			$this->check_program_service('nginx', 'dostartnginx', 'dostopnginx', 'dorestartnginx');
		}

		$this->check_program_service('php-fpm', 'dostartphp5fpm', 'dostopphp5fpm', 'dorestartphp5fpm');
		$this->check_program_service('mysqld', 'dostartmysqld', 'dostopmysqld', 'dorestartmysqld');
		$this->check_program_service('vsftpd', 'dostartvsftpd', 'dostopvsftpd', 'dorestartvsftpd');
		$this->check_program_service('bind', 'dostartbind', 'dostopbind', 'dorestartbind');
		$this->check_program_service('postfix', 'dostartpostfix', 'dostoppostfix', 'dorestartpostfix');
		$this->output .= "</table> ";

		$systemStatus = $this->executeProg3($this->ehcpdir . "/misc/serverstatus.sh"); #moved the bash script in a separate file; this way it will be easyer to edit.

		$this->output .= "<hr><p><pre>" . $systemStatus . "</pre></p>";
		#-------------- end deconectat edit -----------------------------------------------------


		$topoutput = $this->executeProg3("top -b -n 1 | head -40");
		$this->output .= "<hr><div align=left>Top output: <br> <pre>$topoutput</pre></div>";

		$topoutput = $this->executeProg3("tail -200 /var/log/syslog");
		$this->output .= "<hr><div align=left>Syslog (to see this, you must chmod a+r /var/log/syslog on server console, <a target=_blank href='?op=adjust_system'>adjust system for this</a>): <br> <pre>$topoutput</pre></div>";

		return True;
	}

	function adjust_system()
	{
		if ($this->commandline) {
			passthru2("chmod a+r /var/log/syslog");
		} else {
			$this->add_daemon_op(array('op' => __FUNCTION__));
		}
		return True;
	}

	function resetAllCustomTemplates()
	{
		// This function is used to reset all the custom web templates to the system default (useful when major template updates are released in EHCP)
		if (!$this->commandline) {
			// Require admin if not called from a daemon script
			$this->requireAdmin();
		}

		$success = true;
		$writeOut = "";

		// Make a backup of all global custom templates
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_value != ''";
		$rs = $this->query($SQL);
		foreach ($rs as $r) {
			$templateName = $r["template_name"];
			$templateWM = $r["template_webserver_type"];
			$templateValue = $r["template_value"];
			$templateSSLType = $r["template_ssl_type"];
			$writeOut .= "\n\n" . $templateName . " for " . $templateSSLType . " " . $templateWM . ":\n\n" . $templateValue;
		}

		// Make a backup of all domain custom templates
		$SQL = "SELECT * FROM " . $this->conf['domainstable']['tablename'] . " WHERE apache2template != '' OR nginxtemplate != ''";
		$rs = $this->query($SQL);
		foreach ($rs as $r) {
			$domain = $r["domainname"];
			if (!empty($r["apache2template"])) {
				$writeOut .= "\n\nCustom apache2template for " . $domain . ":\n\n" . $r["apache2template"];
			}

			if (!empty($r["nginxtemplate"])) {
				$writeOut .= "\n\nCustom nginxtemplate for " . $domain . ":\n\n" . $r["nginxtemplate"];
			}
		}

		// Make a backup of all custom http entries
		$SQL = "SELECT * FROM " . $this->conf['customstable']['tablename'] . " WHERE name = 'customhttp'";
		$rs = $this->query($SQL);
		foreach ($rs as $r) {
			$domain = $r["domainname"];
			$webserverType = $r["webservertype"];
			$customValue = $r["value"];
			$id = $r["id"];
			$writeOut .= "\n\nCustom " . $webserverType . " http entry with database ID of " . $id . " for " . $domain . ":\n\n" . $customValue;
		}

		if (isset($writeOut) && !empty($writeOut)) {
			$date = date("Y_m_d_H_i_s");
			$backupFile = "/var/www/new/ehcp/custom_domain_template_backups_" . $date . ".conf";
			$this->write_file_if_not_exists($backupFile, $writeOut);
		}

		// Clear custom global templates
		$SQL = "UPDATE " . $this->conf['globalwebservertemplatestable']['tablename'] . " SET template_value=''";
		$success = $success && $this->executeQuery($SQL);

		// Clear domain templates
		$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set nginxtemplate='', apache2template=''");

		// Clear custom http entries
		$SQL = "DELETE FROM " . $this->conf['customstable']['tablename'] . " WHERE name = 'customhttp'";
		$success = $success && $this->executeQuery($SQL);

		// Daemon Operations
		$success = $success && $this->addDaemonOp('handle_reset_sites_enabled_default', '', '', '', 'reset default sites enabled template');
		$success = $success && $this->addDaemonOp('handle_reset_mainwebserverconf', '', '', '', 'reset main webserver conf to default');
		$success = $success && $this->addDaemonOp('syncdomains', '', '', '', 'sync domains');

		$this->ok_err_text($success, "All templates have been reset to their default state.&nbsp;" . (isset($backupFile) ? " A backup file (" . $backupFile . ") of the custom templates was saved." : ""), "Failed to clear all templates.");
		return $success;
	}

	function editApacheTemplate()
	{
		#$this->output.=print_r2($this->miscconfig);

		$templatefield = $this->miscconfig['webservertype'] . 'template';
		global $_insert, $apachetemplate, $$templatefield, $saveTemplate, $clearTemplate;
		$this->getVariable(array('_insert', 'apachetemplate', $templatefield, 'clearTemplate', 'saveTemplate'));
		if ($this->miscconfig['disableeditapachetemplate'] <> '')
			$this->requireAdmin();

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$domaininfo = $this->domaininfo = $this->getDomainInfo($this->selecteddomain);
		$this->output .= "Careful, this a dangerous thing, you should know about webserver (" . $this->miscconfig['webservertype'] . ", currently active) configuration syntax!<br>if syntax is broken, a series of fallback operations will be done to make your panel reachable, such as rebuilding config using the default webserver configuration<br>";

		if ($domaininfo['webserverips'] == '' or $domaininfo['webserverips'] == 'localhost')
			$templateinfile = file_get_contents("apachetemplate"); # template different, if domain is served in another IP
		else
			$templateinfile = file_get_contents("apachetemplate_ipbased");

		// Check for global domain template
		$globalDomainTemplate = $this->getGlobalDomainTemplate();

		$success = True;


		if (!$_insert) {
			$usingDefault = true;
			$template = $domaininfo[$templatefield];
			if ($template == '') {
				if (empty($globalDomainTemplate)) {
					$template = $templateinfile;
				} else {
					$template = $globalDomainTemplate;
				}
			} else {
				$usingDefault = false;
			}

			// If the domain was configured to redirect normal HTTP to HTTPS, make sure the template default reflects that here as well
			$template = $this->adjustDomainTemplateForRedirect($template, $domaininfo, "domain", false);

			$inputparams = array(
				array($templatefield, 'textarea', 'default' => trim($template), 'cols' => 80, 'rows' => 30, 'lefttext' => 'Current ' . $this->miscconfig['webservertype'] . ' Template:'),
				array('saveTemplate', 'submit', 'default' => 'Save Template'),
				array('clearTemplate', 'submit', 'default' => 'Revert to Default'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= '<p>' . $this->selecteddomain . ' Using Default Template: ' . ($usingDefault ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . '</p><div class="editTemplateArea">' . inputform5($inputparams) . '</div>';
		} else {
			if ($clearTemplate) {
				$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set $templatefield='' where domainname='" . $domainname . "'");
				$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname); # sync only domain that is changed. not all domains... 
				$this->ok_err_text($success, "The custom template for the domain of " . $domainname . " has been successfully removed and the default template will now be used for the domain.", "Failed to save domain template modifications.");
			} else if ($saveTemplate) {
				$continue = true;

				// Below is a messy way to check if the template was actually changed from the default global or EHCP default template
				// But it has to be done this way since the template processing replaces certain variables and because textarea fields have \r\n for line breaks when database fields just have \n			
				if (!empty($globalDomainTemplate)) {
					if ($$templatefield == $this->escape(str_replace('{domainname}', $this->selecteddomain, $globalDomainTemplate)) || $$templatefield == $this->escape($globalDomainTemplate)) {
						$$templatefield = ''; # if same as in default template file, do not store it in db.
						$this->output .= "<br>The domain template was not changed.  No modified entries were stored in the database.<br>";
						$continue = false;
					}
				} else if ($this->removeLineCharacterLiteralsFromString($$templatefield) == $this->removeLineCharacterLiteralsFromString($this->escape(str_replace('{domainname}', $this->selecteddomain, $templateinfile))) || $this->removeLineCharacterLiteralsFromString($$templatefield) == $this->removeLineCharacterLiteralsFromString($this->escape($templateinfile))) {
					$$templatefield = ''; # if same as in default template file, do not store it in db.
					$this->output .= "<br>The domain template was not changed.  No modified entries were stored in the database.<br>";
					$continue = false;
				}

				if ($continue) {
					$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set $templatefield='" . $$templatefield . "' where domainname='$domainname'");
					$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname); # sync only domain that is changed. not all domains... 
					$this->ok_err_text($success, "Domain template modifications were successfully saved and stored in the database.", "Failed to save domain template modifications.");
				}
			}
		}
		$this->showSimilarFunctions('HttpDnsTemplatesAliases');
		return $success;
	}

	function editApacheTemplateSubdomain()
	{
		global $id;
		$success = True;

		$subdomain = $this->getSubdomainInfoById($id);
		if ($subdomain === false) {
			return false;
		}

		$templatefield = $this->miscconfig['webservertype'] . 'template';
		global $_insert, $apachetemplate, $$templatefield, $saveTemplate, $clearTemplate;
		$this->getVariable(array('_insert', 'apachetemplate', $templatefield, 'clearTemplate', 'saveTemplate'));
		if ($this->miscconfig['disableeditapachetemplate'] <> '')
			$this->requireAdmin();

		$this->output .= "Careful, this a dangerous thing, you should know about webserver (" . $this->miscconfig['webservertype'] . ", currently active) configuration syntax!<br>if syntax is broken, a series of fallback operations will be done to make your panel reachable, such as rebuilding config using default webserver configuration<br>";

		$templateinfile = file_get_contents("apache_subdomain_template"); # template different, if domain is served in another IP

		// Check for global domain template
		$globalSubDomainTemplate = $this->getGlobalSubDomainTemplate();

		$success = True;


		if (!$_insert) {
			$usingDefault = true;
			$template = $subdomain[$templatefield];
			if ($template == '') {
				if (empty($globalSubDomainTemplate)) {
					$template = $templateinfile;
				} else {
					$template = $globalSubDomainTemplate;
				}

			} else {
				$usingDefault = false;
			}

			// If the subdomain was configured to redirect normal HTTP to HTTPS, make sure the template default reflects that here as well
			$template = $this->adjustDomainTemplateDependingOnSSLSettings($template, $subdomain, "subdomain", false);

			$inputparams = array(
				array($templatefield, 'textarea', 'default' => trim($template), 'cols' => 80, 'rows' => 30, 'lefttext' => 'Current ' . $this->miscconfig['webservertype'] . ' Subdomain Template:'),
				array('saveTemplate', 'submit', 'default' => 'Save Template'),
				array('clearTemplate', 'submit', 'default' => 'Revert to Default'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= '<p>' . $subdomain["subdomain"] . "." . $subdomain["domainname"] . ' Using Default Template: ' . ($usingDefault ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . '</p><div class="editTemplateArea">' . inputform5($inputparams) . '</div>';
		
		} else {
			if ($clearTemplate) {
				$success = $success && $this->executeQuery("update " . $this->conf['subdomainstable']['tablename'] . " set $templatefield='' where domainname='" . $subdomain["domainname"] . "' AND subdomain ='" . $subdomain["subdomain"] . "' AND id ='" . $id . "'");
				$success = $success && $this->addDaemonOp("syncdomains", 'xx', $subdomain["domainname"]); # sync only domain that is changed. not all domains... 
				$this->ok_err_text($success, "The custom template for the subdomain of " . $subdomain["subdomain"] . "." . $subdomain["domainname"] . " has been successfully removed and the default template will now be used for the subdomain.", "Failed to save template modifications.");
			} else if ($saveTemplate) {
				$continue = true;

				// Below is a messy way to check if the template was actually changed from the default global or EHCP default template
				// But it has to be done this way since the template processing replaces certain variables and because textarea fields have \r\n for line breaks when database fields just have \n			
				$templateWeReceived = str_replace(array('{domainname}', '{subdomain}'), array($subdomain["domainname"], $subdomain["subdomain"]), $templateinfile);
				if (!empty($globalSubDomainTemplate)) {
					$templateWeReceived = str_replace(array('{domainname}', '{subdomain}'), array($subdomain["domainname"], $subdomain["subdomain"]), $globalDomainTemplate);
					if ($$templatefield == $this->escape($templateWeReceived) || $$templatefield == $this->escape($globalSubDomainTemplate)) {
						$$templatefield = ''; # if same as in default template file, do not store it in db.
						$this->output .= "<br>The subdomain template was not changed.  No modified entries were stored in the database.<br>";
						$continue = false;
					}
				} else if ($this->removeLineCharacterLiteralsFromString($$templatefield) == $this->removeLineCharacterLiteralsFromString($this->escape($templateWeReceived)) || $this->removeLineCharacterLiteralsFromString($$templatefield) == $this->removeLineCharacterLiteralsFromString($this->escape($templateinfile))) {
					$$templatefield = ''; # if same as in default template file, do not store it in db.
					$this->output .= "<br>The subdomain template was not changed.  No modified entries were stored in the database.<br>";
					$continue = false;
				}

				if ($continue) {
					$success = $success && $this->executeQuery("update " . $this->conf['subdomainstable']['tablename'] . " set $templatefield='" . $$templatefield . "' where domainname='" . $subdomain["domainname"] . "' AND subdomain ='" . $subdomain["subdomain"] . "' AND id ='" . $id . "'");
					$success = $success && $this->addDaemonOp("syncdomains", 'xx', $subdomain["domainname"]); # sync only domain that is changed. not all domains... 
					$editAgainLink = '<a href="?op=editapachetemplatesubdomain&id=' . $id . '">Edit Subdomain Template Again</a>';
					$this->ok_err_text($success, "Subdomain template modifications were successfully saved and stored in the database." . '<br>' . $editAgainLink, "Failed to save subdomain template modifications.");
				}
			}
		}

		$this->showSimilarFunctions('subdomainsDirs');

		return $success;
	}

	function removeLineCharacterLiteralsFromString($str)
	{
		$str = str_replace('\r\n', '', $str);
		$str = str_replace('\r', '', $str);
		$str = str_replace('\n', '', $str);
		return $str;
	}

	function editDomainAliases()
	{
		global $_insert, $aliases;
		$this->getVariable(array('_insert', 'aliases'));

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$domaininfo = $this->domaininfo = $this->getDomainInfo($this->selecteddomain);
		$this->output .= "Enter one alias per line one by one<br>
	Example:<br>
	www.domain2.com<br>
	www.domain3.com<br>
	other.domain2.com<br>
	<hr>";
		$templateinfile = file_get_contents("dnszonetemplate");
		$success = True;

		if (!$_insert) {
			$template = $domaininfo['aliases'];
			$inputparams = array(
				array('aliases', 'textarea', 'default' => $template, 'cols' => 80, 'rows' => 30, 'lefttext' => 'Domain Aliases:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= inputform5($inputparams);

		} else {
			$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set aliases='" . $aliases . "' where domainname='$domainname'");
			$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains-aliases'); # sync only that domain... 
			$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns-aliases');
			$this->ok_err_text($success, "Domain alias(es) were successfully modified. ", "Failed to modify domain alias(es).");
		}

		$this->showSimilarFunctions('HttpDnsTemplatesAliases');
		return $success;
	}

	function editDnsTemplate()
	{
		global $_insert, $dnstemplate;
		$this->getVariable(array('_insert', 'dnstemplate'));
		if ($this->miscconfig['disableeditdnstemplate'] <> '')
			$this->requireAdmin();

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$domaininfo = $this->domaininfo = $this->getDomainInfo($this->selecteddomain);
		$this->output .= "Careful, this a dangerous thing, you should now about dns configuration syntax!<br>";
		$templateinfile = file_get_contents("dnszonetemplate");
		$success = True;

		if (!$_insert) {
			$template = $domaininfo['dnstemplate'];
			if ($template == '') {
				$template = $templateinfile;
			}

			$inputparams = array(
				array('dnstemplate', 'textarea', 'default' => $template, 'cols' => 80, 'rows' => 30, 'lefttext' => 'Current DNS Template:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= '<div class="editTemplateArea">';
			$this->output .= inputform5($inputparams);
			$this->output .= '</div>';

		} else {
			if ($dnstemplate == $this->escape($templateinfile)) {
				$dnstemplate = ''; # if same as in default template file, do not store it in db.
				$this->output .= "<br>Template same as in template file, so, not stored in db<br>";
			}
			$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set dnstemplate='" . $dnstemplate . "', dns_serial = dns_serial + 1 where domainname='$domainname'");
			$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
			$this->ok_err_text($success, "Custom DNS entries were successfully saved and stored in the database.", "Failed to save custom DNS entries.");
		}
		$this->showSimilarFunctions('HttpDnsTemplatesAliases');
		return $success;
	}

	function changedomainserverip()
	{
		global $serverip;
		$this->getVariable(array('serverip'));

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$domaininfo = $this->domaininfo = $this->getDomainInfo($this->selecteddomain);

		if (!$serverip) {
			$inputparams = array(
				array('serverip', 'lefttext' => 'Change Domain Server IP Address To:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= inputform5($inputparams);
		} else {
			$success = $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set serverip='$serverip' where domainname='$domainname'");
			$this->addDaemonOp("syncdns", '', '');
			return $this->ok_err_text($success, "Server IP address was successfully updated.", "Failed to update the server IP address.");
		}
		return True;
	}

	function addRedirect()
	{
		global $todomain, $_insert, $set_redir, $delete_redir;
		$this->getVariable(array('todomain', 'set_redir', 'delete_redir', '_insert'));
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;
		$curRedirect = $this->getRedirectDomain($domainname);
		if (!$_insert) {
			$this->output .= "Redirect Domain:<br>Include http:// or https://.<br><h5 style='margin:0px;'>(custom apache/nginx templates will be reset and lost for $domainname if redirected)</h5>" . inputform5(
				array(
					array('todomain', 'lefttext' => 'To:', 'default' => $curRedirect),
					array('set_redir', 'submit', 'default' => 'Set Redirect'),
					array('delete_redir', 'submit', 'default' => 'Remove Redirect'),
					array('op', 'hidden', 'default' => __FUNCTION__)
				)
			);
		} else {
			if ($delete_redir) {
				$success = $this->removeRedirectDomain($domainname);

				// If a redirect operation was successfully completed, add syncdomains op
				if ($success) {
					$this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
				}

				return $this->ok_err_text($success, "Domain redirection for '" . $domainname . "' has been removed!", "Failed to remove redirection for '" . $domainname . "'!");
			} else if ($set_redir) {
				if (inputValid($todomain, 'url')) {
					// Strip trailing slashes
					$todomain = removeAllTrailingSlashes($todomain);
					$success = $this->setRedirectDomain($domainname, $todomain);

					// If a redirect operation was successfully completed, add syncdomains op
					if ($success) {
						$this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
					}

					return $this->ok_err_text($success, "Domain '" . $domainname . "' is now redirected to '" . $todomain . "'.", "Failed to redirect domain '" . $domainname . "' to '" . $todomain . "'!");
				} else {
					$success = false;
					return $this->ok_err_text($success, "", "Redirect URL '" . $todomain . "' is invalid.");
				}
			}
		}

		$this->showSimilarFunctions('redirect');
		return True;
	}

	function addDomainSSLCert()
	{
		global $ssl_cert, $ssl_cert_key, $ssl_cert_chain, $_insert, $set_ssl, $delete_ssl, $ssl_use_letsenc, $ssl_wild_card, $ssl_redirect_https, $lets_enc_additional_hosts;
		$this->getVariable(array('ssl_cert', 'ssl_cert_key', 'ssl_cert_chain', 'delete_ssl', 'set_ssl', '_insert', 'ssl_use_letsenc', 'ssl_wild_card', 'ssl_redirect_https', 'lets_enc_additional_hosts'), true);
		unset($arr); // Clear array

		// Make sure SSL is enabled on the web server...
		if ($this->miscconfig['webservermode'] == 'ssl' || $this->miscconfig['webservermode'] == 'sslonly') {

			if ($this->isadmin() || (!$this->isadmin() && !empty($this->miscconfig['allowcustomsslnonadmin']))) {

				// Make sure domain is selected
				$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
				$success = True;
				$curSSLSettingsForDomain = $this->getSSLSettingForDomain($domainname);
				if (!$_insert) {

					if ($this->miscconfig['webservermode'] == 'ssl') {
						$toAdd = array('ssl_redirect_https', 'checkbox', 'default' => '1', 'lefttext' => 'Redirect All HTTP Requests to HTTPS:', 'checked' => $curSSLSettingsForDomain["redir_https"]);
					}

					$letsEncHTML = array(
						array('ssl_use_letsenc', 'submit', 'default' => 'Use FREE SSL'),
						array('op', 'hidden', 'default' => __FUNCTION__)
					);

					$customSSLHTML = array(
						array('ssl_cert', 'textarea', 'lefttext' => 'SSL Certificate (Raw Text):', 'default' => $curSSLSettingsForDomain["cert"]),
						array('ssl_cert_key', 'textarea', 'lefttext' => 'SSL Certificate Private Key (Raw Text):', 'default' => $curSSLSettingsForDomain["key"]),
						array('ssl_cert_chain', 'textarea', 'lefttext' => 'SSL Certificate Chain (Raw Text):', 'default' => $curSSLSettingsForDomain["chain"]),
						array('ssl_wild_card', 'checkbox', 'default' => '1', 'lefttext' => 'Is Wildcard Certificate:', 'checked' => $curSSLSettingsForDomain["wildcard"]),
						array('set_ssl', 'submit', 'default' => 'Save SSL Settings'),
						array('op', 'hidden', 'default' => __FUNCTION__)
					);

					$deleteSSLHTML = array(
						array('delete_ssl', 'submit', 'default' => 'Remove Certificate &amp; Reset SSL Settings'),
						array('op', 'hidden', 'default' => __FUNCTION__)
					);

					if ($this->isadmin()) {
						$additionalHostsToProtectLetsEnc = array('lets_enc_additional_hosts', 'textarea', 'lefttext' => 'Additional Valid Let\'s Encrypt SSL Hosts:<p class="ehcp-all-small-text">(in case you have a custom web template and want to protect additional hosts that are not EHCP configured subdomains)<br>(Takes the format of ns3.otherdomain.com{skipdomain},ns4=/var/www/new/ehcp;nextsubhost,n88,a9=/location)<br>subdomains (without domains) separated by ","<br>along with optional {skipdomain} if you don\'t want to append the domain name to the entry,<br>optional path specified with "=" (defaults to domain httpdocs path if left out),<br>next extry follows ";"<br>Leave blank if you don\'t know what you\'re doing.</p>', 'default' => $curSSLSettingsForDomain["lets_enc_additional_hosts"], 'cssclass' => 'ehcp-all-hide ehcp-all-adminAdvancedOption', 'skip-ending-colon' => true);
						array_unshift($letsEncHTML, $additionalHostsToProtectLetsEnc);
					}

					if ($this->hasValueOrZero($toAdd)) {
						array_unshift($letsEncHTML, $toAdd);
						array_splice($customSSLHTML, 4, 0, array($toAdd)); // splice in at position 4
					}

					$this->output .= "<h2>SSL Certificate Settings for $domainname:</h2><p>" . $curSSLSettingsForDomain["ssl_status"] . "</p><h3 expand='freeSSL' class='ehcp-all-active ehcp-all-expander'>Use <span class='darkText ehcp-all-green'>Free SSL</span> from Let's Encrypt</h3><div class='freeSSL ehcp-all-contentToExpand'>";

					if ($this->isadmin()) {
						$this->output .= "<button class='ehcp-all-showAdminAdvancedOptions ehcp-all-cursor'>Show Advanced Admin Options</button>";
					}

					$this->output .= inputform5($letsEncHTML) . "</div><h3 expand='customSSL' class='ehcp-all-clickme ehcp-all-expander'>Click to Use Custom SSL</h3><div class='customSSL ehcp-all-hide ehcp-all-contentToExpand'>" . inputform5($customSSLHTML) . "</div><h3 expand='removeSSL' class='ehcp-all-clickme ehcp-all-expander'>Click to Remove and Reset SSL Configuration for Domain</h3><div class='removeSSL ehcp-all-hide ehcp-all-contentToExpand'>" . inputform5($deleteSSLHTML) . "</div>";

				} else {
					if ($delete_ssl) {
						$success = $this->removeSSLSettingForDomain($domainname);

						// If a redirect operation was successfully completed, add syncdomains op
						if ($success) {
							$this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
						}

						return $this->ok_err_text($success, "Domain SSL settings for '" . $domainname . "' have been removed!", "Failed to remove SSL settings for '" . $domainname . "'!");
					} else if ($set_ssl) {
						$ssl_cert_nonescaped = strip_tags(trim($_POST["ssl_cert"]));
						$ssl_cert_key_nonescaped = strip_tags(trim($_POST["ssl_cert_key"]));
						$ssl_cert_chain_nonescaped = strip_tags(trim($_POST["ssl_cert_chain"]));

						if (!empty($ssl_cert) && !empty($ssl_cert_key)) {
							if (inputValid($ssl_cert, 'certificate') && inputValid($ssl_cert_key, 'certificate_key')) {
								// To do - better ssl cert chain validation???
								if ((!empty($ssl_cert_chain) && inputValid($ssl_cert_chain, 'certificate') && testCertificateChainValid($ssl_cert_chain_nonescaped)) || empty($ssl_cert_chain)) {
									if ((!empty($ssl_cert_chain) && makeSureSSLTestChainFileMatches($ssl_cert_chain_nonescaped)) || empty($ssl_cert_chain)) {
										if (testCertificateAndPrivateKeyHashMatch($ssl_cert_nonescaped, $ssl_cert_key_nonescaped)) {
											if (makeSureSSLTestFileMatches($ssl_cert_nonescaped, $ssl_cert_key_nonescaped)) {
												$arr["cert"] = $ssl_cert;
												$arr["key"] = $ssl_cert_key;
												$arr["chain"] = $ssl_cert_chain;
												if (isset($ssl_wild_card) && !empty($ssl_wild_card)) {
													$arr["wildcard"] = 1;
												} else {
													$arr["wildcard"] = 0;
												}

												if (isset($ssl_redirect_https) && !empty($ssl_redirect_https)) {
													$arr["redir_https"] = 1;
												} else {
													$arr["redir_https"] = 0;
												}

												$success = $this->setSSLSettingForDomain($domainname, $arr);

												// Run daemon ops if successful
												if ($success) {
													$success = $success && $this->addDaemonOp("process_ssl_certs", 'xx', $domainname, '', 'handle creation of ssl cert files');
													$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
												}

												return $this->ok_err_text($success, "Domain '" . $domainname . "' will use the custom SSL certificate settings provided.", "Failed to save custom SSL settings for '" . $domainname . "'!", "");
											} else {
												$success = false;
												return $this->ok_err_text($success, "", "Test file contents do not match entered key or certificate.&nbsp; Please try again.");
											}
										} else {
											$success = false;
											return $this->ok_err_text($success, "", "Certificate and key hash do not match, or the certificate and key combo is not valid!");
										}
									} else {
										$success = false;
										return $this->ok_err_text($success, "", "Test file contents do not match entered chain certificate.&nbsp; Please try again.");
									}
								} else {
									$success = false;
									return $this->ok_err_text($success, "", "Certificate chain is invalid!");
								}
							} else {
								$success = false;
								return $this->ok_err_text($success, "", "You must enter a valid certificate and private key for custom SSL settings!");
							}
						} else {
							$success = false;
							return $this->ok_err_text($success, "", "The SSL certificate and private key are required!");
						}
					} else if ($ssl_use_letsenc) {
						if (isset($ssl_redirect_https) && !empty($ssl_redirect_https)) {
							$arr["redir_https"] = 1;
						} else {
							$arr["redir_https"] = 0;
						}

						if ($this->isadmin()) {
							if (isset($lets_enc_additional_hosts) && !empty($lets_enc_additional_hosts)) {
								$arr["lets_enc_additional_hosts"] = $lets_enc_additional_hosts;
							} else {
								$arr["lets_enc_additional_hosts"] = "";
							}
						}

						$success = $this->setLetsEncryptForDomain($domainname, $arr);
						$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
						return $this->ok_err_text($success, "Domain will use and automatically renew SSL certificate from Let's Encrypt.", "Failed to configure domain to use SSL certificate from Let's Encrypt.");
					}
				}
			} else {
				$success = false;
				return $this->ok_err_text($success, "", "Only the administrator of this server can setup custom SSL certificates for your domain.&nbsp; Please contact support.");
			}
		} else {
			$success = false;
			return $this->ok_err_text($success, "", "SSL must be enabled on the web server before you can use custom domain SSL settings.");
		}
		return True;
	}


	function cmsEditPages()
	{
		global $output;

		$grup = $this->selecteddomain;
		include_once("multicms/cmsindex.php");
	}


	function updateWebstats()
	{
		global $skipUpdateWebstats;
		if ($skipUpdateWebstats or $this->miscconfig['enablewebstats'] == '') {
			# if you put webstats.sh in crontab
			echo "\nSkipping " . __FUNCTION__ . " because of config directive (\$skipUpdateWebstats) or enablewebstats is not checked in options.\n";
			return false;
		}

		$this->requireCommandLine(__FUNCTION__);
		$res = $this->query("select domainname,homedir from domains where status='$this->status_active' and homedir<>''");
		$str = '';
		foreach ($res as $dom) {
			passthru2("mkdir -p " . $dom['homedir'] . "/httpdocs/webstats/");
			$str .= "webalizer -Q -p -j -n www." . $dom['domainname'] . " -o " . $dom['homedir'] . "/httpdocs/webstats " . $dom['homedir'] . "/logs/access_log -R 100 TopReferrers -r " . $dom['domainname'] . " HideReferrer \n";
		}
		echo $str;

		writeoutput2("/etc/ehcp/webstats.sh", $str, "w");
		passthru2("chmod a+x /etc/ehcp/webstats.sh");
		passthru2("/etc/ehcp/webstats.sh");
		echo "\nwrite webstats file to /etc/ehcp/webstats.sh complete... need to put this in crontab or run automatically.. \n";

	}

	function set_active_server_ip()
	{
		$this->requireAdmin();

		global $ip, $_insert;
		$this->getVariable(array('ip', '_insert'));

		if ($_insert) {
			if ($ip <> '')
				$this->validate_ip_address($ip);
			$this->setConfigValue('activewebserverip', $ip);
			$this->output .= 'Default Webserver Ip changed in ehcp (not in system)';
		} else {
			$inputparams = array(
				array('ip', 'righttext' => 'leave empty to make it default of your server', 'lefttext' => 'IP Address:')
			);
			$this->output .= "Change the server's main IP address that is used in this webserver:" . inputform5($inputparams);
		}

		$this->showSimilarFunctions('server');
	}

	function addServer()
	{
		$this->requireAdmin();

		global $_insert, $id, $serveroption, $serverip, $accessip, $servertype, $password, $defaultmysqlhostname;
		$this->getVariable(array('_insert', 'id', 'serveroption', 'servertype', 'serverip', 'accessip', 'password', 'defaultmysqlhostname'));
		$this->output .= "<hr>This is not a cluster setup. These are Individual servers<hr>";
		$res = True;

		if ($servertype == '') {
			$inputparams = array(
				'serverip',
				array('accessip', 'righttext' => 'leave empty if same as server ip', 'lefttext' => 'Server IP Address:'),
				array('servertype', 'radio', 'secenekler' => array('mysql' => 'Mysql Database Server', 'binddns' => 'Bind DNS server', 'apache2' => 'Apache Web Server', 'nginx' => 'nginx Web Server')),
				array('serveroption', 'radio', 'lefttext' => 'Server Option:', 'secenekler' => array('usedalways' => 'This Server is used always in this ehcp', 'optional' => 'This server is optional, may be choosen')),
				array('isdefault', 'radio', 'lefttext' => 'if Optional: Is Server Default ?', 'secenekler' => array('yes', 'no')),
				array('password', 'password', 'lefttext' => 'mysql root pass if server is mysql'),
				array('defaultmysqlhostname', 'lefttext' => 'if mysql server: Default mysql user hostname/ip', 'righttext' => 'This is host of mysql user, to connect from, <br>You should write hostname/ip of your webserver here.. Otherwise, webserver cannot connect to your mysql server..')
			);
			$this->output .= "Add Server:" . inputform5($inputparams);

		} else {
			$this->output .= "Adding server.";
			if ($accessip == '')
				$accessip = $serverip;

			$q = "insert into servers (servertype,ip,accessip,mandatory,password,defaultmysqlhostname) values ('$servertype','$serverip','$accessip','" . ($serveroption == 'usedalways' ? 'E' : 'H') . "','$password','$defaultmysqlhostname')"; # E=Yes, H=No
			$res = $this->executeQuery($q);
			$this->ok_err_text($res, "Successfully added an additional server.", 'Failed to add an additional server.');
		}
		$this->showSimilarFunctions('server');

		return $res;

	}

	function getAllPanelDomains()
	{
		$SQL = "SELECT * FROM " . $this->conf['domainstable']['tablename'] . " ORDER BY domainname ASC";
		$rs = $this->query($SQL);
		if ($rs !== false) {
			return $rs;
		}
		return false;
	}

	function getAllPanelDomainsKeyValue()
	{
		$returnVal = array('NONE' => 'NONE');
		$domains = $this->getAllPanelDomains();
		if ($domains !== false) {
			foreach ($domains as $domain) {
				$returnVal[$domain["domainname"]] = $domain["domainname"];
			}
		}
		return $returnVal;
	}

	function advancedsettings()
	{
		$this->requireAdmin();

		global $_insert;
		$this->getVariable(array('_insert'));

		$optionlist = array(
			array('morethanoneserver', 'checkbox', 'righttext' => '(This is experimental)', 'checked' => $this->miscconfig['morethanoneserver'], 'default' => 'Yes'),
			array('mysqlcharset', 'lefttext' => 'Default mysql charset for new databases', 'righttext' => 'Example: DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', 'default' => $this->miscconfig['mysqlcharset']),
			array('server_id', 'lefttext' => 'The id of this server, assigned by you, may be empty', 'righttext' => 'Example: 1 or home, This will be used in future for auto dyndns service inside ehcp', 'default' => $this->miscconfig['server_id']),
			array('defaultdnsserverips', 'lefttext' => 'Default dns server ip\'s that will host new domains:', 'righttext' => 'Enter list of ip\'s of your dnsservers here, comma separated list (for this server, you may use localhost)', 'default' => $this->miscconfig['defaultdnsserverips']),
			array('defaultwebserverips', 'lefttext' => 'Default webserver ip\'s that will host new domains:', 'righttext' => 'Enter list of ip\'s of your webservers here, comma separated list (for this server, you may use localhost)', 'default' => $this->miscconfig['defaultwebserverips']),
			#array('defaultwebservertypes','lefttext'=>'Webserver type\'s that will run on those webservers above:','righttext'=>'Enter list of webserver type\'s of your webservers here, comma separated list (nginx or apache2, default is apache2 for all if left empty)','default'=>$this->miscconfig['defaultwebservertypes']),
			array('defaultmailserverips', 'lefttext' => 'Default mailserver ip\'s that will host new domains:', 'righttext' => 'Enter list of ip\'s of your mailservers here, comma separated list (for this server, you may use localhost)', 'default' => $this->miscconfig['defaultmailserverips']),
			array('webservertype', 'radio', 'default' => $this->miscconfig['webservertype'], 'righttext' => 'webserver type of this server, default apache2, do not change this unless you know what you are doing', 'secenekler' => array('apache2' => 'apache2', 'nginx' => 'nginx')),
			array('webservermode', 'radio', 'default' => $this->miscconfig['webservermode'], 'righttext' => 'Set the SSL mode of the server.  The default is non-ssl for port 80 connections (http). SSL is a mixed mode where both port 80 (HTTP) and 443 (HTTPS) resolve.  SSLOnly is port 443 HTTPS only.', 'secenekler' => array('ssl' => 'ssl', 'sslonly' => 'sslonly', 'nonssl' => 'nonssl')),
			array('useglobalsslcert', 'checkbox', 'default' => 'Yes', 'lefttext' => 'Use global SSL certificate for domains WITHOUT user configured certificates', 'righttext' => 'If selected, an invalid but secure SSL certificate will be used for domains that don\'t have user configured SSL certificates only when a SSL mode is enabled.  Leave unchecked if you only want to use SSL for domains that have their own SSL certificates. (Best Mode)', 'checked' => $this->miscconfig['useglobalsslcert']),
			array('allowcustomsslnonadmin', 'checkbox', 'lefttext' => 'Allow non-admin users to manage and use custom SSL certs for domains', 'default' => 'Yes', 'checked' => $this->miscconfig['allowcustomsslnonadmin'], 'righttext' => '(may break webserver if certificates are invalid)'),
			array('allowanonymousftptodirectory', 'lefttext' => 'Enable Anonymous READONLY FTP Access to Specific Directory:', 'righttext' => 'Leave blank to keep disabled.&nbsp; DO NOT USE EHCP DIRECTORIES!', 'default' => $this->miscconfig['allowanonymousftptodirectory']),
			array('globalpanelurls', 'textarea', 'lefttext' => 'EHCP Panel Direct URL(s) (Protected by Let\'s Encrypt if SSL is Enabled):<br>(Takes the format of ns3.otherdomain.com,otherdomain.com)<br>Multiple entries separated by comma ",".<br>Leave blank if you don\'t want to configure any.<br>Use only domains or subdomains not currently configured in the panel for best results.</p>', 'default' => $this->miscconfig['globalpanelurls'], 'skip-ending-colon' => true),
			array('dkimdomain', 'select', 'secenekler' => $this->getAllPanelDomainsKeyValue(), 'lefttext' => 'Configure Global DKIM for Emails Using the Domain Of:', 'righttext' => 'Select "NONE" to Disable DKIM.', 'default' => $this->miscconfig['dkimdomain']),
			array('postfixsslcertpath', 'lefttext' => 'Postfix TLS SSL (Combined in PEM format) Path:', 'righttext' => 'Leave blank to use the default self-signed certificate.', 'default' => $this->miscconfig['postfixsslcertpath']),
			array('restartpostfix', 'checkbox', 'lefttext' => 'Reload Postfix SSL (Refresh SSL Cert)', 'default' => 'Yes'),
			array('sslcouriercertpath', 'lefttext' => 'POP3-SSL and IMAP-SSL Certificate (Combined in PEM format) Path:', 'righttext' => 'Leave blank to use the default self-signed certificate.', 'default' => $this->miscconfig['sslcouriercertpath']),
			array('restartcourier', 'checkbox', 'lefttext' => 'Reload Courier SSL (Refresh SSL Cert)', 'default' => 'Yes'),
			array('sslvsftpdcertpath', 'lefttext' => 'VSFTPD Certificate (Combined in PEM format) Path:', 'righttext' => 'Leave blank to revert back to default VSFTPD configuration.', 'default' => $this->miscconfig['sslvsftpdcertpath']),
			array('restartvsftpd', 'checkbox', 'lefttext' => 'Reload VSFTPD SSL (Refresh SSL Cert)', 'default' => 'Yes')
		);

		if ($this->miscconfig['morethanoneserver']) {
			#$optionlist[]='othersetting';
			$addstr = "<br><a href='?op=listservers'>List Servers</a><br><a href='?op=addserver'>Add Server</a>";
		}

		if ($_insert) {
			$old_webserver_type = $this->miscconfig['webservertype'] . "-" . $this->miscconfig['webservermode'];
			$old_anon_ftp_dir = $this->miscconfig['allowanonymousftptodirectory'];
			$old_globalpanelurls = $this->miscconfig['globalpanelurls'];
			$old_globalcert_type = $this->miscconfig['useglobalsslcert'];
			$old_courier_ssl_cert = $this->miscconfig['sslcouriercertpath'];
			$old_vsftpd_ssl_cert = $this->miscconfig['sslvsftpdcertpath'];
			$old_postfix_ssl_cert = $this->miscconfig['postfixsslcertpath'];
			$old_dkimdomain = $this->miscconfig['dkimdomain'];

			if ($old_webserver_type == '')
				$old_webserver_type = 'apache2-nonssl';

			$this->output .= "Updating configuration...";
			#$this->debugecho(print_r2($optionlist),3,false);

			$optionsToIgnore = array('restartcourier', 'restartvsftpd', 'restartpostfix');
			foreach ($optionlist as $option) {
				if (!in_array($option[0], $optionsToIgnore)) {
					global ${$option[0]}; # make it global to be able to read in getVariable function..may be we need to fix the global thing..
					$this->getVariable($option[0]);
					$this->setConfigValue($option[0], ${$option[0]});
				}
			}


			$this->loadConfigWithDaemon(); # loads config for this session, to show below..
			$this->output .= "..update complete.";

			$current_webserver_type = $this->miscconfig['webservertype'] . "-" . $this->miscconfig['webservermode'];
			if ($old_webserver_type != $current_webserver_type || ($old_globalcert_type != $this->miscconfig['useglobalsslcert'] && $this->miscconfig['webservermode'] = "ssl")) {
				$rebuildTriggered = true;
			}

			if ($old_globalpanelurls != $this->miscconfig['globalpanelurls']) {

				// Get array of entries
				$entries = $this->explodeComma($this->miscconfig['globalpanelurls']);
				$oldEntries = $this->explodeComma($old_globalpanelurls);

				// Check to make sure entries are valid
				$valid = $this->checkValidGlobalPanelURLs($entries);

				if (!$valid) {
					// Reset its value
					$this->setConfigValue('globalpanelurls', $old_globalpanelurls);
				} else {
					// Change is valid, so remove the old lets encrypt certs if they exist
					if ($this->hasValueOrZero($old_globalpanelurls) && $oldEntries) {
						$this->removeLetsEncryptCertificates($oldEntries);
					}
				}

				// Sync domains after let's encrypt certificate cleanup
				if (!isset($rebuildTriggered) && $rebuildTriggered !== true && $valid) {
					$this->addDaemonOp('syncdomains', '', '', '', 'sync domains');
				}

			}

			if ($old_dkimdomain != $this->miscconfig['dkimdomain']) {
				if ($this->miscconfig['dkimdomain'] != 'NONE' && !empty($this->miscconfig['dkimdomain'])) {
					if (isset($old_dkimdomain) && !empty($old_dkimdomain) && $old_dkimdomain != 'NONE') {
						$this->addDaemonOp("manage_dkim", 'remove', $old_dkimdomain, '', 'handle dkim postfix configuration');
					}
					$this->addDaemonOp("manage_dkim", 'add', $this->miscconfig['dkimdomain'], '', 'handle dkim postfix configuration');
				} else {
					if (isset($old_dkimdomain) && !empty($old_dkimdomain) && $this->miscconfig['dkimdomain'] == 'NONE') {
						$this->addDaemonOp("manage_dkim", 'remove', $old_dkimdomain, '', 'handle dkim postfix configuration');
					}
				}
			}

			// Handle courier POP3-SSL and IMAP-SSL certificate path
			if ($old_courier_ssl_cert != $this->miscconfig['sslcouriercertpath']) {
				if ($this->hasValueOrZero($this->miscconfig['sslcouriercertpath']) && !isextension($this->miscconfig['sslcouriercertpath'], 'pem')) {
					// Reset its value since it's not valid
					$this->setConfigValue('sslcouriercertpath', $old_courier_ssl_cert);
				}
				$this->addDaemonOp('handlecouriersslcert', '', '', '', 'handlecouriersslcert');
			} else {
				// If they're the same, the certificate may have changed, so we allow a quick way to restart the courier ssl services
				if ($this->hasValueOrZero($_REQUEST["restartcourier"])) {
					$this->addDaemonOp('resynccourierssl', '', '', '', 'resynccourierssl');
				}
			}

			// Handle VSFTPD SSL certificate path
			if ($old_vsftpd_ssl_cert != $this->miscconfig['sslvsftpdcertpath']) {
				if ($this->hasValueOrZero($this->miscconfig['sslvsftpdcertpath']) && !isextension($this->miscconfig['sslvsftpdcertpath'], 'pem')) {
					// Reset its value since it's not valid
					$this->setConfigValue('sslvsftpdcertpath', $old_vsftpd_ssl_cert);
				}
				$this->addDaemonOp('handlevsftpdsslcert', '', '', '', 'handlevsftpdsslcert');
			} else {
				// If they're the same, the certificate may have changed, so we allow a quick way to restart the courier ssl services
				if ($this->hasValueOrZero($_REQUEST["restartvsftpd"])) {
					$this->addDaemonOp('resyncvsftpdssl', '', '', '', 'resyncvsftpdssl');
				}
			}

			// Handle Postfix SSL certificate path
			if ($old_postfix_ssl_cert != $this->miscconfig['postfixsslcertpath']) {
				if ($this->hasValueOrZero($this->miscconfig['postfixsslcertpath']) && !isextension($this->miscconfig['postfixsslcertpath'], 'pem')) {
					// Reset its value since it's not valid
					$this->setConfigValue('postfixsslcertpath', $old_postfix_ssl_cert);
				}
				$this->addDaemonOp('handlepostfixsslcert', '', '', '', 'handlepostfixsslcert');
			} else {
				// If they're the same, the certificate may have changed, so we allow a quick way to restart the courier ssl services
				if ($this->hasValueOrZero($_REQUEST["restartpostfix"])) {
					$this->addDaemonOp('resyncpostfixssl', '', '', '', 'resyncpostfixssl');
				}
			}

			// Handle anonymous ftp:
			if ($old_anon_ftp_dir != $this->miscconfig['allowanonymousftptodirectory']) {
				$this->addDaemonOp('configure_anon_ftp', '', '', '', 'configure_anon_ftp');
			}

			if ($rebuildTriggered) {
				$this->addDaemonOp('rebuild_webserver_configs', '', '', '', 'rebuild_webserver_configs');
			}

		} else {
			$optionlist[] = array('op', 'default' => __FUNCTION__, 'type' => 'hidden');
			$this->output .= "Advanced Settings: <br>(In future: server plans will be done, most of these settings will be filled from serverplans)<br>" . inputform5($optionlist) . $addstr;
		}

	}

	function explodeComma($input)
	{
		if ($this->hasValueOrZero($input)) {
			if (stripos($input, ",") !== false) {
				$entries = explode(",", $input);
			} else {
				$entries[] = $input;
			}
			return $entries;
		}
		return false;
	}

	function checkValidGlobalPanelURLs($urls)
	{

		foreach ($urls as $entry) {
			if (!$this->isValidDomain($entry) && !$this->isValidSubDomain($entry)) {
				return false;
			}
		}

		return true;
	}

	function listServers()
	{
		$this->requireAdmin();

		$this->output .= "Servers ";
		$this->listTable("", "serverstable", $filter);
		$this->showSimilarFunctions('server');
	}

	function otherpage($id)
	{ # de-selects a domain and display other page whose name is $id_lang.html , function written to be used later...
		$this->deselectdomain2();
		return $this->displayHome($id);
	}

	function otheroperations()
	{ # de-selects a domain and display other operations page.. or some other page
		$this->deselectdomain2();
		if (!$this->isadmin() and $this->userinfo['maxdomains'] > 1) {
			if ($this->selecteddomain == '')
				$home = 'homepage_reseller_other';
		} elseif ($this->isadmin()) {
			$home = 'homepage_serveradmin_other';
		}
		return $this->displayHome($home);
	}

	function selectdomain($id)
	{ # selects a domain and displays home
		global $domainname;
		$this->getVariable(array("domainname"));

		if ($id <> '')
			$domainname = $id;
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		#$this->output.="Domain selected... $domainname, id: $id <hr>";

		# reset search session values
		$_SESSION['sess_arananalan'] = '';
		$_SESSION['sess_aranan'] = '';

		$this->displayHome();
	}

	function updateInstallScriptSQL()
	{
		// Require's admin priv
		$this->requireAdmin();
		$this->addDaemonOp("update_ez_install", '', 'xx', '', 'Update EZ Script Install SQL');
		$this->output .= "<p class='success'>Easy installation script SQL has successfully been updated from the https://ehcpforce.ezpz.cc master.</p>";

		return True;
	}

	function suggestnewscript()
	{
		global $name, $url, $scriptdirtocopy, $homepage, $description, $lastmsgtoehcpdeveloper;
		$this->getVariable(array('name', 'url', 'scriptdirtocopy', 'homepage', 'description'));

		if (!$url) {
			$inputparams = array(
				array('name', 'lefttext' => 'Script Name:'),
				array('url', 'lefttext' => 'Script URL:'),
				array('scriptdirtocopy', 'lefttext' => 'Script Directory To Copy:'),
				array('homepage', 'lefttext' => 'Script Homepage:'),
				array('description', 'textarea', 'lefttext' => 'Script Description:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "Suggest a new application integration by providing a detailed description of the script. " . inputform5($inputparams) . "The script may be added if approved by an admin. ";

		} else {
			$this->output .= "Thank you for your submission.&nbsp; The script will be reviewed by the server admin and will be added if approved. ";
			$subj = "New Easy Install Script Integration Request";
			$msg = "<p>EHCP Admin:<br><br>A user has suggested that an application be integrated with the easy installation scripts module.<br>Name of the script: $name<br>URL to Application: $url<br>Script Homepage: $homepage<br>Description: $description";
			$this->infotoadminemail($msg, $subj);
		}
	}



	function read_dir($path, $func = "is_file")
	{
		$dirArray = array();
		$myDirectory = opendir($path);
		while ($entryName = readdir($myDirectory)) {
			if ($func("$path/$entryName") and $entryName <> '.' and $entryName <> '..' and $entryName <> 'all_templates')
				$dirArray[] = $entryName;
		}
		closedir($myDirectory);
		return $dirArray;
	}

	function changetemplate()
	{
		global $template;
		$this->getVariable(array('template'));
		$this->requireAdmin(); # only admin can change theme.... normally should be: everybody can change theme, theme of each user stored in their data, after login, every user should see its theme/lang...
		# before login: default theme/lang...

		if (!$template) {
			$dirArray = $this->read_dir("./templates", "is_dir");
			$this->output .= "Select the template :<br>";
			foreach ($dirArray as $dir)
				$this->output .= "<a href='?op=changetemplate&template=$dir'>$dir</a><br>";
		} else {
			$this->setConfigValue("defaulttemplate", $template);
			$this->output .= "Changed default template to : $template ";
			$this->loadConfigWithDaemon();
			$this->displayHome();
		}
	}

	function smallserverstats($isaret = '')
	{
		global $ehcpversion;
		$email = str_replace('@', '((at))', $this->conf['adminemail']); # to prevent spam...


		$out = "<font size=-2><br>Small statistics of server:<br>Panel User count:" . $this->recordcount($this->conf['logintable']['tablename'], '') .
			"<br>Domain Count:" . $this->recordcount($this->conf['domainstable']['tablename'], '') .
			"<br>FTP users count:" . $this->recordcount($this->conf['ftpuserstable']['tablename'], '') .
			"<br>Email users count:" . $this->recordcount($this->conf['emailuserstable']['tablename'], '') . "<br>$email<br>Version: $ehcpversion<br>$isaret<br></font>";

		return $out;
	}


	function isNoPassOp()
	{ # is this operation a no password one? such as an application before login, no password required.
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . ": kontrol:" . $this->op, 4);
		$nopassops = array("applyforaccount", "applyfordomainaccount", 'sifrehatirlat', 'activate');
		return in_array($this->op, $nopassops);

		#** dikkat, burada guvenlik kontrolu yapilmiyor, dikkat edilmesi lazim...
	}


	function options()
	{
		$this->requireAdmin();

		global $edit, $_insert, $dnsip;
		$this->getVariable(array('edit', '_insert', 'dnsip', 'localip'));
		#echo print_r2($this->miscconfig);

		# new style: options as an array, so, easy addition of new options..
		$optionlist = array(
			array('updatehostsfile', 'checkbox', 'lefttext' => 'This machine is used for Desktop access too (Update hosts file with domains)', 'default' => 'Yes', 'checked' => $this->miscconfig['updatehostsfile']),
			array('localip', 'lefttext' => 'Local ip of server', 'default' => $this->miscconfig['localip']),
			array('dnsip', 'lefttext' => 'dnsip (outside/real/static ip of server)', 'default' => $this->miscconfig['dnsip']),
			array('dnsipv6', 'lefttext' => 'dnsip V6(outside/real/static V6 ip of server)', 'default' => $this->miscconfig['dnsipv6'], 'righttext' => 'Leave empty to disable (experimental even if enabled)'),
			array('updatednsipfromweb', 'checkbox', 'lefttext' => 'Do you use dynamic ip/dns?', 'righttext' => 'Check this if your server is behind a dynamic IP', 'default' => 'Yes', 'checked' => $this->miscconfig['updatednsipfromweb']),
			array('banner', 'textarea', 'default' => $this->miscconfig['banner']),
			array('adminemail', 'lefttext' => 'Admin Email', 'default' => $this->miscconfig['adminemail']),
			array('defaulttemplate', 'default' => $this->miscconfig['defaulttemplate']),
			array('defaultlanguage', 'default' => $this->defaultlanguage),
			array('messagetonewuser', 'textarea', 'default' => $this->miscconfig['messagetonewuser']),
			array('disableeditapachetemplate', 'checkbox', 'lefttext' => 'Disable Custom http for non-admins', 'default' => 'Yes', 'checked' => $this->miscconfig['disableeditapachetemplate'], 'righttext' => 'This is a security measure to disable non-experienced users to break configs'),
			array('disableeditdnstemplate', 'checkbox', 'lefttext' => 'Disable Custom dns for non-admins', 'default' => 'Yes', 'checked' => $this->miscconfig['disableeditdnstemplate'], 'righttext' => 'This is a security measure to disable non-experienced users to break configs'),
			array('forcedeleteftpuserhomedir', 'checkbox', 'lefttext' => 'When an FTP Account is deleted, also delete ALL FILES and FOLDERS that used to be accessible by the deleted FTP account', 'default' => 'Yes', 'checked' => $this->miscconfig['forcedeleteftpuserhomedir'], 'righttext' => 'Will delete files that could be owned by a domain, subdomain, other user, or other FTP account. RECOMMENDED: Do NOT Enabled'),
			array('forcedeletesubdomainfiles', 'checkbox', 'lefttext' => 'When a subdomain is deleted, delete the subdomain\'s files automatically too.', 'default' => 'Yes', 'checked' => $this->miscconfig['forcedeletesubdomainfiles']),
			array('turnoffoverquotadomains', 'checkbox', 'lefttext' => 'Turn off over quota domains', 'default' => 'Yes', 'checked' => $this->miscconfig['turnoffoverquotadomains']),
			array('quotaupdateinterval', 'default' => $this->miscconfig['quotaupdateinterval'], 'righttext' => 'interval in hours'),
			array('userscansignup', 'checkbox', 'default' => 'Yes', 'checked' => $this->miscconfig['userscansignup'], 'righttext' => 'disabled by default, can users sign up for domains/ftp? (you should approve/reject them in short time)'),
			array('enablewebstats', 'checkbox', 'default' => 'Yes', 'checked' => $this->miscconfig['enablewebstats'], 'righttext' => 'enabled by default, this can use some of server resources, so, disabling it may help some slow servers to speed up'),
			array('enablewildcarddomain', 'checkbox', 'default' => 'Yes', 'checked' => $this->miscconfig['enablewildcarddomain'], 'righttext' => 'do you want xxxx.yourdomain.com to show your domain homepage? disabled by default, and shows server home, which is default index, ehcp home.'),
			array('freednsidentifier', 'default' => $this->miscconfig['freednsidentifier'], 'righttext' => 'freedns.afraid.org unique identifier, for dynamic dns update, automatic; take your id from freedns.afraid.org if you want to use that. ')

			#array('singleserverip','default'=>$this->miscconfig['singleserverip'])

		);



		if ($_insert) {
			$old_webserver_type = $this->miscconfig['webservertype'] . "-" . $this->miscconfig['webservermode'];
			if ($old_webserver_type == '')
				$old_webserver_type = 'apache2-nonssl';

			$this->output .= "Updating configuration...";
			$this->validate_ip_address($dnsip);

			foreach ($optionlist as $option) {
				global ${$option[0]}; # make it global to be able to read in getVariable function..may be we need to fix the global thing..
				$this->getVariable($option[0]);
				$this->setConfigValue($option[0], ${$option[0]});
			}

			# options that use longvalue:
			$this->setConfigValue("banner", "", 'value'); # delete short value for banner, if there is any.. because longvalue is used for banner.
			$this->setConfigValue("banner", $banner, 'longvalue');

			# operations that needs daemon or other settings.

			if ($dnsip != $this->miscconfig['dnsip']) { # fix all dnsip related config if dnsip is changed...
				$this->addDaemonOp("fixmailconfiguration", '', '', '', 'fix mail configuration'); # fixes postfix configuration
				$this->addDaemonOp('syncdns', '', '', '', 'sync dns'); # syncs the DNS zones to use the new IP address
				if ($updatehostsfile <> '') {
					$this->addDaemonOp("updatehostsfile", '', '', '', 'update hostsfile'); # Update hosts file too
				}
			}

			$addSyncOpAfterSettingsReloaded = false;
			if ($enablewildcarddomain != $this->miscconfig['enablewildcarddomain']) {
				$addSyncOpAfterSettingsReloaded = true;
			}

			if ($defaultlanguage) { # update for current session too..
				$_SESSION['currentlanguage'] = '';
				$this->defaultlanguage = $this->currentlanguage = $defaultlanguage;
			}

			# load latest config again in this session.
			$this->loadConfigWithDaemon(); # loads config for this session, to show below..
			if ($updatehostsfile <> '')
				$this->addDaemonOp("updatehostsfile", '', '', '', 'update hostsfile'); # updateHostsFile degistiginden dolayi
			if ($addSyncOpAfterSettingsReloaded) {
				$this->addDaemonOp('syncdomains', '', '', '', 'sync domains');
				$this->addDaemonOp('syncdns', '', '', '', 'sync dns'); # syncs the DNS zones to use the new IP address
			}

			$this->output .= "..update complete.";

		} elseif ($edit) {
			$optionlist[] = array('op', 'default' => __FUNCTION__, 'type' => 'hidden');
			$this->output .= "<h2>Options:</h2><br>" . inputform5($optionlist);

		} else {
			$this->output .= "<h2>Options:</h2><br>" . print_r3($this->miscconfig, "$this->th Option Name </th>$this->th Option Value </th>");
		}

		$this->showSimilarFunctions('options');
		$this->debugecho(print_r2($this->miscconfig), 3, false);
	}


	function settings()
	{
		$this->requireAdmin();

		global $edit, $_insert, $dnsip, $group;
		$this->getVariable(array('edit', '_insert', 'group'));
		#echo print_r2($this->miscconfig);

		# new style: options as an array, so, easy addition of new options..
/*$ip="206.51.230.224";
$netmask="255.255.255.0";
$broadcast="206.51.230.255";
$gateway="206.51.230.1";
*/

		if ($group == 'vps' or $group == 'vpsadvanced') {
			$this->load_module('Vps_Module');
			switch ($group) {
				case 'vps':
					$optionlist = $this->Vps_Module->vps_settings;
					break;
				case 'vpsadvanced':
					$optionlist = $this->Vps_Module->vps_settings_advanced;
					break;
			}
		} else
			$optionlist = array();

		if ($_insert) {
			$this->output .= "Updating settings for $group...";


			foreach ($optionlist as $option) {
				global ${$option[0]}; # make it global to be able to read in getVariable function..may be we need to fix the global thing..
				$this->getVariable($option[0]);
				$this->setSettingsValue($group, $option[0], ${$option[0]});
			}

			$this->loadConfigWithDaemon();
			$this->output .= "..update complete.";
		} else {
			$optionlist[] = array('op', 'default' => __FUNCTION__, 'type' => 'hidden');
			$this->output .= "<h2>Settings:</h2><br>" . inputform5($optionlist);

		}

		#$this->showSimilarFunctions('options');	
		#$this->print_r2($this->settings);

		if ($group == 'vps' or $group == 'vpsadvanced') {
			$this->showSimilarFunctions('vps');
			$this->output .= "<br>Click <a href='?op=vps&vpsname=xx&op2=rescanimages'>rescan vps images & templates</a> to check them on server, then wait 10sec, click vps settings again, to see them here<br><br> <a href='?op=settings&group=vpsadvanced'>advanced vps settings</a>(vps with 2 interface)<br>";
		}

		$this->debugecho(print_r2($this->miscconfig), 3, false);
	}

	function disableService($service)
	{
		if (sysIsUsingSystemD()) {
			passthru2("systemctl disable $service");
		} else {
			passthru2("update-rc.d -f $service remove");
			passthru2("update-rc.d $service disable");
		}
	}

	function enableService($service)
	{
		if (sysIsUsingSystemD()) {
			passthru2("systemctl enable $service");
		} else {
			passthru2("update-rc.d $service defaults");
			passthru2("update-rc.d $service enable");
		}
	}

	function rebuild_webserver_configs()
	{
		# this function will rebuild all webserver configs according to current choosen webserver type, ssl etc..
		$this->requireCommandLine(__FUNCTION__, True);

		# remove all webservers from auto-start
		$this->disableService("nginx");
		$this->disableService("apache2");

		if ($this->miscconfig['webservertype'] == 'apache2') {
			// Web server mode changed so restore the original main config file if it exists
			if (file_exists("/etc/apache2/apache2.conf.bk_used_for_EHCP_DO_NOT_DELETE")) {
				passthru2("cp /etc/apache2/apache2.conf.bk_used_for_EHCP_DO_NOT_DELETE /etc/apache2/apache2.conf", true, true);
			}

			// Make sure we rebuild password protected directories for apache as well
			$this->handlePasswordProtectedDirs();
			return $this->rebuild_apache2_config();
		} else if ($this->miscconfig['webservertype'] == 'nginx') {
			return $this->rebuild_nginx_config();
		}

		return False; # yukardaki webserver tipi degilse, başka bişey... 
	}

	function configure_anon_ftp()
	{
		# this function will configure VSFTPD for anonymous read only access if specified by an admin
		$this->requireCommandLine(__FUNCTION__, True);
		$shouldClearSettings = false;

		if (!empty($this->miscconfig['allowanonymousftptodirectory'])) {
			$this->miscconfig['allowanonymousftptodirectory'] = removeInvalidChars($this->miscconfig['allowanonymousftptodirectory'], "directory");
			$this->miscconfig['allowanonymousftptodirectory'] = rtrim($this->miscconfig['allowanonymousftptodirectory'], '/');
			$this->setConfigValue('allowanonymousftptodirectory', $this->miscconfig['allowanonymousftptodirectory']);

			// Admin advanced feature, but let's do a little validation before we let em go wild.
			if (strpos($this->miscconfig['allowanonymousftptodirectory'], "/var/www/") === FALSE && $this->miscconfig['allowanonymousftptodirectory'] != "/var/www" && inputValid($this->miscconfig['allowanonymousftptodirectory'], "directory_at_least_two_levels")) {
				exec('bash /var/www/new/ehcp/scripts/anonymous_vsftpd.sh "' . $this->miscconfig['allowanonymousftptodirectory'] . '"');
			} else {
				// Option was not valid, so just reset it
				$this->setConfigValue('allowanonymousftptodirectory', '');
				$shouldClearSettings = true;
			}
		}

		if ((!isset($this->miscconfig['allowanonymousftptodirectory']) || empty($this->miscconfig['allowanonymousftptodirectory'])) || $shouldClearSettings) {
			// Remove vsftpd anonymous access if previously setup
			exec('bash /var/www/new/ehcp/scripts/anonymous_vsftpd.sh');
		}

		return true;
	}

	function rebuild_apache2_config()
	{
		$this->requireCommandLine(__FUNCTION__, True);

		// Support SSL properly
		if ($this->miscconfig['webservermode'] == 'ssl') {
			$this->fixApacheConfigSsl();
		} else if ($this->miscconfig['webservermode'] == 'nonssl') {
			$this->fixApacheConfigNonSsl();
		} else if ($this->miscconfig['webservermode'] == 'sslonly') {
			$this->fixApacheConfigSslOnly();
		}

		if ($this->miscconfig['webservertype'] == 'apache2') {
			$this->enableService("apache2"); # make apache2 auto-start on reboot
			$this->enableService($this->php_fpm_name); # apache2 uses FPM now too
		}
		$this->syncDomains();

		return True;
	}

	function rebuild_nginx_config()
	{
		$this->requireCommandLine(__FUNCTION__, True);

		// Support SSL properly
		if ($this->miscconfig['webservermode'] == 'ssl') {
			$this->fixApacheConfigSsl();
		} else if ($this->miscconfig['webservermode'] == 'nonssl') {
			$this->fixApacheConfigNonSsl();
		} else if ($this->miscconfig['webservermode'] == 'sslonly') {
			$this->fixApacheConfigSslOnly();
		}

		$this->enableService("nginx"); # make nginx auto-start on reboot
		$this->enableService($this->php_fpm_name); # make nginx auto-start on reboot

		if ($this->miscconfig['webservermode'] == 'ssl' && !$this->is_webserver_running()) {
			$this->echoln2("webserver seems not working...appearantly, some error occured; rolling back to non-ssl mode again.");
			$this->fixApacheConfigNonSsl();
		}

		return True;
	}

	function writeToLogFile($message)
	{
		$logFile = "/var/log/ehcpphp.log";
		if (!file_exists($logFile)) {
			$stream = fopen("$logFile", "w+");
		} else {
			$stream = fopen("$logFile", "a+");
		}
		$date = date('m/d/Y H:i:s');
		if (strlen($message) > 100) {
			$longAster = "****************************************************************************\n";
			fwrite($stream, $longAster);
			fwrite($stream, $date . "\n\n");
			fwrite($stream, $message . "\n");
			fwrite($stream, $longAster);
		} else {
			fwrite($stream, $date . " - ");
			fwrite($stream, $message . "\n");
		}
		fclose($stream);
	}

	function useNginxTemplates()
	{
		passthru2("rm -rvf /etc/nginx/sites-enabled/*", true, true);
		passthru2("cp $this->ehcpdir/etc/nginx/default.nginx /etc/nginx/sites-enabled/default", true, true);
		passthru2("cp $this->ehcpdir/etc/nginx/nginx.conf /etc/nginx/", true, true);

		// Update the variables
		$this->updateNginxConfVariablesInFile();

		passthru2("cp $this->ehcpdir/etc/nginx/apachetemplate.nginx $this->ehcpdir/apachetemplate", true, true);
		passthru2("cp $this->ehcpdir/etc/nginx/apachetemplate_ehcp_panel.nginx $this->ehcpdir/apachetemplate_ehcp_panel", true, true);
		passthru2("cp $this->ehcpdir/etc/nginx/redirect $this->ehcpdir/apachetemplate_redirect", true, true);
		passthru2("cp $this->ehcpdir/etc/nginx/apachetemplate.nginx $this->ehcpdir/apachetemplate_passivedomains", true, true);
		passthru2("cp $this->ehcpdir/etc/nginx/apache_subdomain_template.nginx $this->ehcpdir/apache_subdomain_template", true, true);
		/*
		   // Debug
		   $contentsOfFile = file_get_contents("$this->ehcpdir/apachetemplate");
		   $this->writeToLogFile("In using nginxtemplates and contents of template is: " . $contentsOfFile);
		   */
	}

	function listSelector($arr, $print, $link, $linkfield = 'id', $className = "EHCPListItem", $ignoreLinkColumns = array())
	{
		# for small lists that no paging required...
		$res .= "<table>";
		foreach ($arr as $item) {
			$res .= "<tr>";
			foreach ($print as $pr) {
				$skipLink = false;
				$res .= "<td>";
				if (!in_array($pr, $ignoreLinkColumns)) {
					$res .= "<a class='" . $className . "' href='$link" . $item[$linkfield] . "'>";
				} else {
					$skipLink = true;
				}
				
				if ($pr == "panelusername"){
					$res .= "owned by user " . $item[$pr];
				}else{
					if($pr != "nginxtemplate" && $pr != "apache2template"){
						$res .=	$item[$pr];
					}
				}
				
				if($pr == "nginxtemplate" && !empty($item[$pr])){
					$res .= " (using custom nginx template)"; 
				}
				
				if($pr == "apache2template" && !empty($item[$pr])){
					$res .= " (using custom apache template)"; 
				}
				
				$res .= (!$skipLink ? "</a>" : "");
				$res .= "</td>";
			}
			$res .= "</tr>";
		}
		$res .= "</table>";
		return $res;
	}

	function getVariable($variables, $dotrim = true)
	{
		# get variables by means of $_POST or $_GET globals.., makes them secure for sql injection
		if (!is_array($variables))
			$variables = array($variables); # accept non-array, single parameter

		$varCount = count($variables);
		for ($i = 0; $i < $varCount; $i++) {
			$varname = $variables[$i];
			if (is_array($varname))
				$varname = $varname[0]; # accept array members.. use 1st element for varname.
			if ($varname == '')
				continue;

			global ${$varname}; # make it global at same time.. may be disabled in future..

			if ($_POST[$varname] <> "") {
				if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
					${$varname} = stripslashes($_POST[$varname]);
				else
					${$varname} = $_POST[$varname];
			} else {
				if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
					${$varname} = stripslashes($_GET[$varname]);
				else
					${$varname} = $_GET[$varname];
			}

			// trim should occur before escaping
			if ($dotrim)
				${$varname} = trim(${$varname});

			// Escape each post variable
			$tmp = @$this->escape(${$varname});
			if ($tmp !== False)
				${$varname} = $tmp; # otherwise, without a db connection, mysqli_real_escape_string returns false. this will skip that; no need to mysqli_real_escape_string when there is no db conn, I think. 

			$values[$varname] = ${$varname};
		}
		;
		#echo print_r2($variables).print_r2($values);
		return $values; # return values as an array too... may be used after disabling global variables...
	}


	function getDomain($table_description, $id)
	{ # returns dommainname of record with id $id, from table with $table_description (defined in start of file with conf variable)
		return $this->getField($this->conf[$table_description]['tablename'], "domainname", "id=$id");
	}

	function setField($table, $field, $value, $func, $wherecondition, $opname = '', $isInt = false)
	{
		# prepares an update query based on some parameters, for faster coding...

		$tablename = $this->conf[$table]['tablename'];
		if ($tablename == '')
			$tablename = $table; # works both if direct tablename is given, or "table description from conf" is given

		$query = "update $tablename set $field=";

		if (!$isInt)
			$value = "'$value'"; # surround value with ' if not integer..

		if ($func && $func != null) { # function such as md5, password, encrypt
			if ($func == 'encrypt')
				$set = "$func($value,'ehcp') "; # encrypt is a special function that produces different results for different 2nd par....
			else
				$set = "$func($value) ";
		} else
			$set = " $value ";

		if ($wherecondition)
			$wherestr = " where $wherecondition ";

		$query .= $set . $wherestr;
		#$this->output.="<hr>setfield: query: $query <hr>";
		return $this->executeQuery($query, $opname); # this in turn calls adodb liberary for execute..
	}

	function adjustEmailAutoreply($email, $autoreplysubject, $autoreplymessage, $skipForwarding = false)
	{
		# set db fields
		$where = "email='$email'";
		$this->setField('emailuserstable', 'autoreplysubject', $autoreplysubject, "", $where, "editemailuser-update email");
		$this->setField('emailuserstable', 'autoreplymessage', $autoreplymessage, "", $where, "editemailuser-update email");

		$autoreplyenabled = ($autoreplysubject <> '' and $autoreplymessage <> ''); # if both not empty..

		# adjust forwardings, if autoreply enabled, delete if not
		$domainpart = getLastPart($email, '@'); # domain part of email
		$beforeat = getFirstPart($email, '@');

		$forwarddestination = "$email,$beforeat@autoreply.$domainpart";
		$forwardcount = $this->recordcount("forwardings", "destination='$forwarddestination'");

		if(!$skipForwarding){
			if ($autoreplyenabled and $forwardcount == 0) {
				$success = $this->addEmailForwardingDirect($this->activeuser, $domainpart, $email, $forwarddestination);
			} elseif (!$autoreplyenabled and $forwardcount > 0) {
				$success = $this->executeQuery("delete from forwardings where destination='$forwarddestination'");
			}
		}

		if ($autoreplyenabled)
			$this->output .= "<br>Autoreply enabled for email: $email";

		# add email transports if autoreply enabled, delete if not
		# check if any email of that domain has autoreply enabled

		$autoreplycount = $this->recordcount("emailusers", "domainname='$domainpart' and (autoreplysubject<>'' and autoreplymessage<>'')");
		$transportcount = $this->recordcount("transport", "domainname='autoreply.$domainpart'");

		if ($autoreplycount > 0 and $transportcount == 0) {
			$this->executeQuery("insert into transport (domainname,transport) values ('autoreply.$domainpart','ehcp_autoreply')");
		} elseif ($autoreplycount == 0 and $transportcount > 0) {
			$this->executeQuery("delete from transport where domainname='autoreply.$domainpart'");
		}

	}

	function editEmailUser()
	{
		global $id, $newpass, $newpass2, $_insert, $autoreplysubject, $autoreplymessage, $quota;
		$this->getVariable(array('id', 'newpass', 'newpass2', '_insert', 'autoreplysubject', 'autoreplymessage', 'quota'));

		$email = $this->query("select email, quota from emailusers where id=$id");

		if ($this->isEmailUser()) { # email users edits itself
			$emailAddr = $this->activeuser;
		} else {
			$dom = $this->getDomain('emailuserstable', $id);
			$this->requireMyDomain($dom);
			$emailAddr = $email[0]['email'];
		}
		$where = "email='$emailAddr'";

		if ($_insert) {
			if ($newpass and ($newpass == $newpass2)) {
				# what this mean: set password field of table emailuserstable with newpass, by applying encrypt function, with id=$id
				$this->setField('emailuserstable', 'password', $newpass, "encrypt", $where, "editemailuser-update email pass");
				$this->output .= "<br>Success email pass change. ";
				#equivalent: $this->executeQuery("update ".$this->conf['emailuserstable']['tablename']." set password=encrypt($newpass) where id=$id ", "update email pass");
			}

			// Update quota as well
			if ($quota != $email[0]['quota'] && is_numeric($quota)) {
				$this->setField('emailuserstable', 'quota', $quota, null, $where, "editemailuser-update email quota");
			}

			$this->adjustEmailAutoreply($emailAddr, $autoreplysubject, $autoreplymessage);

		} else {
			$info = $this->query("select autoreplysubject,autoreplymessage from emailusers where $where");
			$autoreplysubject = $info[0]['autoreplysubject'];
			$autoreplymessage = $info[0]['autoreplymessage'];

			$inputparams = array(
				array('newpass', 'password', 'lefttext' => 'New Password:', 'righttext' => 'Leave emtpy for no change'),
				array('newpass2', 'password', 'lefttext' => 'New Password Again:', 'righttext' => 'Leave emtpy for no change'),
				array('quota', 'text', 'lefttext' => 'Quota:', 'righttext' => '', 'default' => $email[0]['quota']),
				array('autoreplysubject', 'default' => $autoreplysubject, 'righttext' => 'Leave emtpy to disable autoreply', 'lefttext' => 'Auto Reply Subject:'),
				array('autoreplymessage', 'textarea', 'default' => $autoreplymessage, 'lefttext' => 'Auto Reply Message:'),
				array('id', 'hidden', 'default' => $id),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$infoTitle = "<p>Editing Email Address \"" . $emailAddr . "\"</p>";

			$this->output .= $infoTitle . inputform5($inputparams);
		}

		$this->showSimilarFunctions('email');
	}

	function editEmailUserPassword()
	{
		global $id, $newpass, $newpass2, $_insert, $autoreplysubject, $autoreplymessage;
		$this->getVariable(array('id', 'newpass', 'newpass2', '_insert', 'autoreplysubject', 'autoreplymessage'));

		if ($this->isEmailUser()) { # email users edits itself
			$email = $this->activeuser;
		} else {
			$dom = $this->getDomain('emailuserstable', $id);
			$this->requireMyDomain($dom);
			$email = $this->query("select email from emailusers where id=$id");
			$email = $email[0]['email'];
		}
		$where = "email='$email'";

		if ($_insert) {
			if ($newpass and ($newpass == $newpass2)) {
				# what this mean: set password field of table emailuserstable with newpass, by applying encrypt function, with id=$id
				$this->setField('emailuserstable', 'password', $newpass, "encrypt", $where, "editemailuser-update email pass");
				$this->output .= "<br>Success email pass change. ";
				#equivalent: $this->executeQuery("update ".$this->conf['emailuserstable']['tablename']." set password=encrypt($newpass) where id=$id ", "update email pass");
			}

		} else {
			$info = $this->query("select autoreplysubject,autoreplymessage from emailusers where $where");

			$inputparams = array(
				array('newpass', 'password', 'lefttext' => 'New Password:', 'righttext' => 'Leave emtpy for no change'),
				array('newpass2', 'password', 'lefttext' => 'New Password Again:', 'righttext' => 'Leave emtpy for no change'),
				array('id', 'hidden', 'default' => $id),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= inputform5($inputparams);
		}

		$this->showSimilarFunctions('email');
	}

	function editEmailUserAutoreply()
	{
		global $id, $newpass, $newpass2, $_insert, $autoreplysubject, $autoreplymessage;
		$this->getVariable(array('id', 'newpass', 'newpass2', '_insert', 'autoreplysubject', 'autoreplymessage'));
		
		if(!empty($id)){

			if ($this->isEmailUser()) { # email users edits itself
				$email = $this->activeuser;
			} else {
				$dom = $this->getDomain('emailuserstable', $id);
				$this->requireMyDomain($dom);
				$email = $this->query("select email from emailusers where id=$id");
				$email = $email[0]['email'];
			}
			$where = "email='$email'";

			if ($_insert) {
				$this->adjustEmailAutoreply($email, $autoreplysubject, $autoreplymessage, true);
			} else {
				$info = $this->query("select autoreplysubject,autoreplymessage from emailusers where $where");
				$autoreplysubject = $info[0]['autoreplysubject'];
				$autoreplymessage = $info[0]['autoreplymessage'];

				$inputparams = array(
					array('autoreplysubject', 'default' => $autoreplysubject, 'righttext' => 'Leave emtpy to disable autoreply', 'lefttext' => 'Auto Reply Subject:'),
					array('autoreplymessage', 'textarea', 'default' => $autoreplymessage, 'lefttext' => 'Auto Reply Message:'),
					array('id', 'hidden', 'default' => $id),
					array('op', 'hidden', 'default' => __FUNCTION__)
				);
				$this->output .= inputform5($inputparams);
			}

			$this->showSimilarFunctions('email');
		}else{
			$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

			$filter = "domainname REGEXP '" . $domainname . "(,|$)'"; #  modified upon suggestion of sextasy@discardmail.com

			$emailAddresses = $this->query("select * from " . $this->conf['emailuserstable']['tablename'] . " where " . $filter);
			if (count($emailAddresses) > 0) {
				$optionHtml = '';
				for($i = 0 ; $i < count($emailAddresses); $i++){
					$optionHtml .= '<option value="' . $emailAddresses[$i]["id"] . '">' . $emailAddresses[$i]["email"] . '</option>';
				}
				$this->output .= '<p>Which email address do you want to set the autoreply for?</p><form method="get"><select name="id">' . $optionHtml . '</select><input type="submit" value="Go" /><input type="hidden" name="op" value="' . $_GET["op"] . '" /></form>';
			}else{
				return $this->errorText("No email addresses currently exist for the selected domain.");
			}
		}
	}

	function mysqlDBInfoValid($dbname, $dbusername, $dbuserpass)
	{
		if (strlen($dbusername) > 16) {
			return $this->errorText("Database username cannot be greater than 16 characters.");
		}

		if (strlen($dbname) > 64) {
			return $this->errorText("Database name cannot be greater than 64 characters.");
		}

		if (strlen($dbuserpass) > 32) {
			return $this->errorText("Database user passwords cannot be greater than 32 characters.");
		}

		$dbnameModified = removeInvalidChars($dbname, "database");
		if ($dbnameModified != $dbname) {
			return $this->errorText("Database names may only contain alphanumeric characters along with underscores.");
		}

		return true;
	}

	function dbAddUser()
	{
		global $dbname, $dbusername, $dbuserpass;
		$this->getVariable(array('dbname', 'dbusername', 'dbuserpass'));

		$domainname = $this->selecteddomain;

		$dbs = $this->query("select * from " . $this->conf['mysqldbstable']['tablename'] . " where panelusername='$this->activeuser'");
		if (count($dbs) == 0) {
			$this->output .= "<hr>You have not any db's yet.. so, use add mysql db link <a href='?op=addmysqldb'>here</a>";
			return false;
		}
		$success = True;

		if ($dbname) {

			// Must have validation
			if (!$this->mysqlDBInfoValid($dbname, $dbusername, $dbuserpass)) {
				return false;
			}

			if ($this->recordcount($this->conf['mysqldbstable']['tablename'], "panelusername='$this->activeuser' and dbname='$dbname'") == 0)
				return $this->errorText("This database is not owned by your account.");

			if (empty($domainname)) {
				$domainname = $this->getField($this->conf['mysqldbuserstable']['tablename'], 'domainname', "panelusername='" . $this->activeuser . "' and dbname='" . $dbname . "'");
			}

			$q = "grant all privileges on `$dbname`.* to '$dbusername'@'localhost' identified by '$dbuserpass' ";
			$success = $success && $this->mysqlRootQuery($q);

			$q = "insert into " . $this->conf['mysqldbuserstable']['tablename'] . " (host,domainname,dbname,dbusername,password,panelusername)values('localhost','$domainname','$dbname','$dbusername','$dbuserpass','$this->activeuser')";
			$success = $success && $s = $this->executeQuery($q, ' add mysql user to ehcp db ');

			$this->ok_err_text($success, 'Successfully added database user.', 'Failed to add database user.');

		} else {
			$inputparams = array(
				array('dbname', 'leftext' => 'Your Existing Database:'),
				array('dbusername', 'lefttext' => 'New Database User:'),
				array('dbuserpass', 'lefttext' => 'Database User Password:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "Add new database user to an <b>existing</b> database.<br>Enter new database user information:<br>(Works only for local MySQL servers)<br>"
				. inputform5($inputparams);
		}
		$this->showSimilarFunctions('mysql');
		return $success;
	}

	function dbEditUser()
	{
		global $dbusername, $newpassword, $newpassword2, $id, $dbremoteaccess;
		$this->getVariable(array('dbusername', 'newpassword', 'newpassword2', 'dbremoteaccess'));
		if ($dbusername == '')
			$dbusername = $this->getField($this->conf['mysqldbuserstable']['tablename'], 'dbusername', "id=$id");

		if ($this->recordcount($this->conf['mysqldbuserstable']['tablename'], "panelusername='$this->activeuser' and dbusername='$dbusername'") == 0) {
			// Admin should be able to edit any MySQL user
			if (!$this->isadmin()) {
				return $this->errorText("This database is not owned by your account.");
			}
		}

		if ($newpassword && $newpassword == $newpassword2) {
			if (strlen($newpassword) <= 32) {
				$remoteAccess = false;
				if ($dbremoteaccess && $this->isadmin()) {
					// Connect as root
					if (!$myserver)
						$myserver = $_SESSION['myserver'];
					if (!$myserver)
						$myserver = $this->getMysqlServer('', false, __FUNCTION__); # get mysql server info..

					// Connect to mysql server, local or remote
					if (!$link = mysqli_connect($myserver['host'], $myserver['user'], $myserver['pass'])) {
						return $this->errorText("Could not connect as root!");
					}

					// Get databases owned by user and convert them to remote access 
					$databases = $this->getMySQLDatabasesByUser($dbusername);
					if ($databases !== false) {
						foreach ($databases as $info) {
							$dbname = $info["dbname"];
							$s = $this->executeQuery("grant all privileges on `$dbname`.* to '$dbusername'@'%' identified by '$newpassword' ", 'grant user rights', '', $link);
						}
					}
				}

				// Update the information in the panel
				$q = "UPDATE " . $this->conf['mysqldbuserstable']['tablename'] . " SET password = '" . $newpassword . "' WHERE dbusername = '" . $dbusername . "'";
				if (!$this->isadmin()) {
					$q .= " AND panelusername = '" . $this->activeuser . "'";
				}
				$s = $this->executeQuery($q, 'update mysql user in ehcp db');

				$this->output .= "setting new password for db user: $dbusername";
				$q = " SET PASSWORD FOR '$dbusername'@'localhost' = PASSWORD('$newpassword')";
				$q2 = " SET PASSWORD FOR '$dbusername'@'%' = PASSWORD('$newpassword')";
				$result = $this->mysqlRootQuery($q, true);
				$result2 = $this->mysqlRootQuery($q2, true);
				if ($result === false && $result2 === false) {
					$this->errorText("Error: Password cannot be changed for database user " . $dbusername . ".");
				} else
					$this->okeyText("Change password success..");
			} else {
				$this->errorText("Error: Password must be less than or equal to 32 characters in length.");
			}
		} else {
			$inputparams = array(
				array('newpassword', 'password', 'lefttext' => 'New Password'),
				array('newpassword2', 'password', 'lefttext' => 'new password again'),
				array('dbusername', 'hidden', 'default' => $dbusername),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			if ($this->isadmin()) {
				$inputparams[] = array("dbremoteaccess", 'checkbox', 'lefttext' => 'Allow remote access to database:', 'default' => '1', 'checked' => '0');
			}

			$this->output .= "Change password for database user \"$dbusername\":<br>(Works only for local MySQL servers)<br>"
				. inputform5($inputparams);
		}
		$this->showSimilarFunctions('mysql');
		return True;
	}

	function getMySQLDatabasesByUser($user)
	{
		$mysqlDBUserInfo = $this->query("select * from " . $this->conf['mysqldbuserstable']['tablename'] . " where dbusername='" . $user . "'");
		if (count($mysqlDBUserInfo) > 0) {
			return $mysqlDBUserInfo;
		}

		return false;
	}

	function deleteCustomSetting()
	{
		global $id;
		$this->getVariable(array('id'));
		$q = "select * from customsettings where id=$id";
		$info = $this->query($q);
		$info = $info[0];

		$domainname = trim($info['domainname']);
		if ($domainname == '') {
			$this->output .= "Domainname for custom setting is empty. strange.. <br>";
			return;
		}

		$success = True;

		$this->output .= '<br>( should check ownership)  Deleting id: ' . $id . '<br>';
		$success = $success && $this->executeQuery("delete from " . $this->conf['customstable']['tablename'] . " where id=$id limit 1");

		if ($info['name'] == 'customdns')
			$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " SET dns_serial = dns_serial + 1 where domainname='$domainname'");
			$success = $success && $this->addDaemonOp("syncdns", '', '');
		if ($info['name'] == 'customhttp' or $info['name'] == 'fileowner')
			$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname);
		$this->ok_err_text($success, " was deleted successfully.", __FUNCTION__ . " failed.");
		$this->showSimilarFunctions('customhttpdns');
		return $success;
	}

	function customHttpSettings()
	{
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$this->listTable('Custom http settings:', 'customstable', "name='customhttp' and domainname='$domainname' and (webservertype is null or webservertype='' or webservertype='" . $this->miscconfig['webservertype'] . "')");
		$this->output .= "<a href='?op=addcustomhttp'>Add Custom http</a>";
		$this->showSimilarFunctions('customhttpdns');
	}

	function custompermissions()
	{
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$this->listTable('Custom file permissinos:', 'customstable', "name='fileowner' and domainname='$domainname'");

		$this->showSimilarFunctions('custompermissions');
	}

	function emailForwardingsSelf()
	{
		$this->requireEmailUser();

		$filter = "source='$this->activeuser'";
		$this->listTable("Email Forwardings", 'emailforwardingstable', $filter);
		if ($this->recordcount($this->conf['emailforwardingstable']['tablename'], $filter) == 0)
			$this->output .= "<a href='?op=addemailforwardingself'>Add Email Forwarding</a>";
	}

	function addEmailForwardingSelf()
	{
		# for mail user login..
		global $forwardto;
		$this->getVariable(array('forwardto'));

		$this->requireEmailUser();
		$email = $this->activeuser;

		# this ensures the ownership of domain
		$domainname = getLastPart($email, '@');


		if (!$forwardto) {
			$this->output .= "Email:$email , domain: $domainname <br>Enter target emails one by line";
			$inputparams = array(
				array('forwardto', 'textarea', 'lefttext' => 'Forward To:')
			);

			$this->output .= inputform5($inputparams);

		} else {
			$this->output .= "Will forward $email to mails: $forwardto ";
			$success = $this->addEmailForwardingDirect($email, $domainname, "$email", $forwardto);
			$res = $this->ok_err_text($success, "Successfully added email forwarding.", 'Failed to add email forwarding.');
			return $res;
		}

	}

	function emailForwardings()
	{
		global $domainname;
		$this->getVariable(array('domainname'));

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		$filter = "panelusername='$this->activeuser' and domainname='$domainname'";
		#$filter=$this->applyGlobalFilter($filter);

		$this->listTable("Email Forwardings", 'emailforwardingstable', $filter);
		$this->output .= "<a href='?op=addemailforwarding'>Add Email Forwarding</a>";
		$this->showSimilarFunctions('email');
	}

	function addEmailForwardingDirect($panelusername, $domainname, $fromemail, $forwardto)
	{
		// Check to see if email address being added has already been setup as a forwarder (if so, return an error)
		$SQL = "SELECT * FROM " . $this->conf['emailuserstable']['tablename'] . " WHERE email='$fromemail';";
		$rs = $this->query($SQL);
		if (count($rs) > 0) {
			return $this->ok_err_text(false, "", 'Email address ' . $fromemail . ' is already configured as a normal inbox.  Use a different source address or delete the existing email account first.');
		}
		
		return $this->executeQuery("insert into forwardings (panelusername,domainname,source,destination)values('$panelusername','$domainname','$fromemail','$forwardto')", $opname);
	}

	function addEmailForwarding()
	{
		global $domainname, $beforeat, $forwardto;
		$this->getVariable(array('domainname', 'beforeat', 'forwardto'));
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		$success = True;

		# this ensures the ownership of domain

		if (!$forwardto) {
			$inputparams = array(
				array('beforeat', 'righttext' => "@$domainname", 'lefttext' => 'Email: <br>Leave empty to use the catch-all email<br>(to receive all emails that are not setup)'),
				array('forwardto', 'textarea', 'lefttext' => 'Forward To'),
				array('domainname', 'hidden', 'default' => $domainname),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= inputform5($inputparams);

		} else {
			$beforeat = getFirstPart($beforeat, '@'); # make sure
			$this->output .= "Will forward $beforeat@$domainname to mails: $forwardto ";
			$success = $success && $this->addEmailForwardingDirect($this->activeuser, $domainname, "$beforeat@$domainname", $forwardto);
			$this->ok_err_text($success, "Successfully added email forwarding.", __FUNCTION__ . ' failed');
			$this->output .= "<br><a href='?op=emailforwardings'>List Email forwardings</a><br>";
		}
		$this->showSimilarFunctions('email');
		return $success;
	}

	function delEmailForwarding()
	{
		global $id;
		$filter = $this->applyGlobalFilter("id=$id");
		$success = $this->executeQuery("delete from " . $this->conf['emailforwardingstable']['tablename'] . " where $filter", $opname);
		$this->output .= "<br><a href='?op=emailforwardings'>List Email forwardings</a><br>";
		$res = $this->ok_err_text($success, "Email forwarding deleted", 'Failed to delete email forwarder.');
		$this->showSimilarFunctions('email');
		return $res;
	}

	function addCustomHttpDirect($domainname, $customhttp, $comment)
	{
		$this->output .= "Adding customhttp :";
		$success = True;
		$success = $success && ($domainname <> '');
		$success = $success && $this->executeQuery("insert into " . $this->conf['customstable']['tablename'] . " (domainname,name,value,comment,webservertype) values ('$domainname','customhttp','$customhttp','$comment','" . $this->miscconfig['webservertype'] . "')", 'add custom http');
		$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');

		return $this->ok_err_text($success, "Custom HTTP settings were added successfully.", 'Failed to add custom HTTP settings.');

	}

	function addCustomHttp()
	{
		global $domainname, $customhttp, $comment;
		$this->getVariable(array("domainname", "customhttp", 'comment')); # this gets variables from _GET or _POST
		# Disable custom http if misc disableeditapachetemplate setting is set, as that is a way to break the apache template.
		if ($this->miscconfig['disableeditapachetemplate'] <> '')
			$this->requireAdmin();

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;

		if (!$customhttp) {
			$inputparams = array(
				array('customhttp', 'textarea', 'lefttext' => 'Custom HTTP'),
				array('comment', 'lefttext' => 'Comment'),
				array('domainname', 'hidden', 'default' => $domainname),
				array('op', 'hidden', 'default' => __FUNCTION__)

			);
			$this->output .= "Adding custom HTTP for domain \"$domainname\" and your current webserver of (" . $this->miscconfig['webservertype'] . ")<br>(Note that this custom HTTP will be active whenever your current webserver type is active):<br>";
			$this->output .= inputform5($inputparams);

		} else {
			$success = $success && $this->addCustomHttpDirect($domainname, $customhttp, $comment);
			$this->ok_err_text($success, "Successfully added custom HTTP settings.", __FUNCTION__ . " failed.");
		}

		$this->showSimilarFunctions('customhttpdns');
		return $success;



	}

	function addcustompermission()
	{
		global $domainname, $fileowner, $directory;
		$this->getVariable(array("domainname", "fileowner", 'directory')); # this gets variables from _GET or _POST

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;

		if (!$fileowner) {
			$inputparams = array(
				array('fileowner', 'righttext' => 'like vsftpd, or: vsftpd:www-data, cannot be root', 'lefttext' => 'File Owner:'),
				array('directory', 'righttext' => 'relative to domain home, such as wp-content for wordpress,or wp/wp-admin', 'lefttext' => 'Directory'),
				array('domainname', 'hidden', 'default' => $domainname),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "Adding custom permission for domain ($domainname):<br>";
			$this->output .= inputform5($inputparams);

		} else {
			$params = array('domainname' => $domainname, 'name' => 'fileowner', 'value' => $fileowner, 'value2' => $directory);
			$success = $this->insert_custom_setting_direct($params);
			$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname);
			$this->ok_err_text($success, "Successfully added custom HTTP settings.", __FUNCTION__ . " failed.");
		}

		$this->showSimilarFunctions('custompermissions');
		return $success;

	}

	function customDnsSettings()
	{
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$this->listTable('Custom dns settings:', 'customstable', "name='customdns'  and domainname='$domainname'");
		$this->output .= '<a href="?op=addcustomdns">Add Custom dns</a>';
		$this->showSimilarFunctions('customhttpdns');
	}

	function getIsSlaveDomain($domainname)
	{
		$dnsmaster = $this->getMasterIP($domainname);
		return ($dnsmaster <> '');
	}

	function addCustomDns()
	{
		global $domainname, $customdns, $comment;
		$this->getVariable(array("domainname", "customdns", 'comment')); # this gets variables from _GET or _POST

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;

		// If is slave domain, do not allow custom DNS
		if ($this->getIsSlaveDomain($domainname))
			$this->errorTextExit('Custom dns cannot be added for domains with slave dns');

		if (!$customdns) {
			$inputparams = array(
				array('customdns', 'textarea', 'lefttext' => 'Custom DNS'),
				array('comment', 'lefttext' => 'Comment'),
				array('domainname', 'hidden', 'default' => $domainname),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Adding custom DNS for domain \"$domainname\":<br>Attention! Adding incorrect entries will cause DNS service errors!<br><br>Example:
  		www2 &nbsp; &nbsp; &nbsp; IN &nbsp;&nbsp;&nbsp; A &nbsp;&nbsp;&nbsp;YOURIP "
				. inputform5($inputparams);

			$this->output .= "Following dns records already added from template, you may modify template (dnszonetemplate file) in filesystem. <br><br><pre>" .
				file_get_contents('dnszonetemplate') . "</pre>";

		} else {
			$this->output .= "Adding customdns :";
			$success = $success && $this->executeQuery("insert into " . $this->conf['customstable']['tablename'] . " (domainname,name,value,comment) values ('$domainname','customdns','$customdns','$comment')", 'add custom dns');
			$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " SET dns_serial = dns_serial + 1 where domainname='$domainname'");
			$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
			$this->ok_err_text($success, "Successfully added custom DNS entries!", 'Failed to add custom DNS entries.');
		}

		$this->showSimilarFunctions('customhttpdns');
		return $success;
	}


	function listBackups()
	{
		$this->requireAdmin();
		$this->echoln('<br>Backups are placed in /var/backup directory. Note filename to a secure place to restore it later.<br>
		you will need this filename if you need restore from a clean install of ehcp<br><br>
		Refresh your page to see latest status...');
		$this->listTable('', 'backups_table', '');
		$this->echoln("You may delete all these files and records manually... from shell and phpmyadmin gui..<br><a href='?op=listbackups'>Refresh List</a>");

		$this->showSimilarFunctions('backup');

		return True;
	}

	function doRestore()
	{
		global $backupname, $filename;
		$this->getVariable(array('backupname', 'filename'));

		$this->requireAdmin();

		if (!$backupname) {
			$inputparams = array(
				array('backupname', 'lefttext' => 'Enter your backup file name which is located at /var/backup (with .tgz extension)', 'default' => $filename),
				array('domainname', 'hidden', 'default' => $domainname),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<b><h2>Attention! Backup data including domains, users, files, and MySQL databases will be restored.  Your current data will be permanently deleted!</h2></b>"
				. inputform5($inputparams);
		} else {
			$this->echoln("Will restore from file: " . $backupname);
			$this->executeQuery("insert into backups (backupname,filename,date,status) values ('restore','$backupname','" . date_tarih() . "','Restore command issued by gui')");
			$this->addDaemonOp("daemonrestore", '', $backupname, '', 'opname:restore');
			$this->echoln("doRestore called...Restore will be started on your Server soon.  Click <a href='?op=listbackups'>here to list backups/restores</a> ");
		}

		$this->showSimilarFunctions('backup');
		return True;
	}


	function daemonRestore($action, $info, $info2 = '')
	{
		$this->requireCommandLine(__FUNCTION__, True);

		$filename = securefilename($info);
		$backupname = $filename;
		$filename = str_replace('.tgz', '', $filename);
		$filename = str_replace('.gz', '', $filename);
		$tarwithparams = "tar --same-owner --preserve-permissions -zxvf ";

		echo "\n\nRestore starting..: backupname:$backupname, filename:$filename .. this is critic place...\n\n";
		$this->executeQuery("update backups set status='restore processing now... by daemon' where filename='$filename' and backupname='restore'");


		$mydir = getcwd();

		# restore files first
		chdir("/var/backup");

		$this->pwdls('extracting backup file ' . $backupname);
		passthru2("$tarwithparams $backupname");
		$this->pwdls('extraction of backup file ' . $backupname . ' complete!');

		if (file_exists('/var/backup/' . $filename)) {
			$this->pwdls('extraction directory of /var/backup/' . $filename . ' exists as it should!');
		} else {
			$this->pwdls('extraction directory of /var/backup/' . $filename . ' does NOT exist! This is a critical problem');
		}

		$this->pwdls('changing to extraction directory of /var/backup/' . $filename);
		chdir("/var/backup/" . $filename);

		$this->pwdls('before extract files');

		#writeoutput($this->conf['vhosts']."/ehcp/nohup.out",'');

		#passthru2("/bin/cat '' > ".$this->conf['vhosts']."/ehcp/nohup.out"); # truncate this file in case this may be big
		passthru2("$tarwithparams files.tgz");
		$this->pwdls('after files, before ehcp');
		passthru2("$tarwithparams ehcp.tgz"); # this will normally give error if there is no ehcp backup
		$this->pwdls('after ehcp, before copy');

		# restore email contents, if any
		passthru2("$tarwithparams home_vmail.tgz");

		# Email fix: should be "cp -Rvf --preserve=all home/vmail /home/" (starting from relative directory where home_vmail.tgz was unzipped) as the "*" in vmail/* was getting escaped which ruined the command
		# Changed from vmail/*
		passthru2("cp -Rvf --preserve=all home/vmail /home/");
		passthru2("chown -Rf vmail:vmail /home/vmail");



		passthru2("cp -Rvf --preserve=all var/www/vhosts /var/www/"); # here, var/www/vhosts is inside /var/backup... so, I used var/www/vhosts


		#restore mysql ehcp db..

		echo "\nrestoring your whole mysql.. \n\n";
		$cmd = "mysql -u root --password=" . $this->conf['mysqlrootpass'] . " < mysql.sql";
		passthru3($cmd, __FUNCTION__);

		sleep(1);
		$this->executeQuery("delete from operations"); # delete operations to avoid re-run of backup/restore..
		#$this->executeQuery("Flush tables");
		echo "\nfinished restoring your whole mysql.. \n";

		echo "\nfinished copying and mysql ops, deleting remaining files... \n";
		$this->pwdls('will just delete files...');
		passthru2("rm -rf /var/backup/$filename");

		chdir($mydir); # return back to original dir
		sleep(1);
		$this->syncAll();
		sleep(1);
		$this->executeQuery("update backups set status='restore complete' where filename='$filename' and backupname='restore'");
		$this->executeQuery("insert into backups (backupname,filename,status) values ('restore complete','$filename','after restore')");
		sleep(1); # sleep to let mysql handle latest updates...

		echo "\n\nRestore complete.... you should restart the ehcp daemon...";
		$this->infotoadminemail("The EHCP backup was successfully restored!", "Backup Restored", False);

		// Restart web server and EHCP daemon
		// Things could change post restore, so make sure we run what needs to be done.
		$this->loadConfig();
		$this->addDaemonOp('rebuild_webserver_configs', '', '', '', 'rebuild_webserver_configs');
		$this->addDaemonOp('syncdomains', '', '', '', 'sync domains');
		$this->addDaemonOp('syncdns', '', '', '', 'sync dns');
		$this->addDaemonOp('syncftp', '', '', '', 'sync ftp');
		$this->addDaemonOp('rebuild_crontab', '', '', '', 'rebuild crontab');
		manageService("ehcp", "restart");
		$this->restart_webserver();

		return True;
	}

	function backups()
	{
		# domain based backups, not whole server or not all domains.
		global $_insert, $op2, $filename;
		$this->getVariable(array('_insert', 'op2', 'filename'));

		#$this->output.="<big>This section is not working fully!</big><br><br>";

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname); # choose domain, or selecteddomain..

		switch ($op2) {
			case 'dobackup':
				$this->add_daemon_op(array('op' => 'daemon_backup_domain', 'info' => $domainname));
				$this->executeQuery("insert into backups (domainname,date,status) values ('$domainname',now(),'command sent to ehcp daemon by ehcp gui')");
				break;

			default:
				$this->output .= "<a href='?op=backups&op2=dobackup'>Start a new domain backup</a> (your domain backup will be started in background)";
				break;
		}

		$this->listTable('', 'backups_table', "domainname='$domainname'");
		$this->output .= "<br><a href='?op=backups'>Refresh/list</a>";

		return True;
	}

	function doBackup()
	{
		$this->requireAdmin();

		$inputparams = array(
			array('backupname', 'lefttext' => 'Enter a name for your backup', 'default' => 'My Backup'),
			array('backupmysql', 'checkbox', 'lefttext' => 'Backup mysql databases (that are listed in ehcp):', 'default' => '1', 'checked' => '1'),
			array('backupfiles', 'checkbox', 'lefttext' => 'Backup Site files:', 'default' => '1', 'checked' => '1'),
			array('backupehcpfiles', 'checkbox', 'lefttext' => 'Backup ehcp files itself:', 'default' => '1'),
			array('backupehcpdb', 'checkbox', 'lefttext' => 'Backup ehcp database itself:', 'default' => '1', 'checked' => '1'),
			array('emailme', 'checkbox', 'lefttext' => 'Email me when backup finished (may not work yet):', 'default' => '1', 'checked' => '1'),
			array('myemail', 'lefttext' => 'My Email, enter different if you wish:', 'default' => $this->conf['adminemail']),
			array('emailaccounts', 'checkbox', 'lefttext' => 'Backup email accounts:', 'default' => '1', 'checked' => '1'),
			array('emailcontents', 'checkbox', 'lefttext' => 'Backup email contents/files:', 'default' => '1', 'checked' => '1'),
			array('gzipbackup', 'checkbox', 'lefttext' => 'tar-gzip backup dir/file:', 'righttext' => 'This is useful, but requires some extra space temporarily. uncheck if you have little space', 'default' => '1', 'checked' => '1'),

			array('domainname', 'hidden', 'default' => $domainname),
			array('_insert', 'hidden', 'default' => '1'),
			array('op', 'hidden', 'default' => __FUNCTION__)
		);

		# instead of: #$this->getVariable(array('_insert','backupmysql','backupfiles','backupehcpfiles','backupehcpdb','backupname'));
		foreach ($inputparams as $p) { # howto avoid global variables ?
			global ${$p[0]};
			$this->getVariable($p[0]);
		}

		if (!$_insert) {
			$this->output .= "<big><b>Caution: System may take a while to complete backup operations. The compressed backup file may consume lots of hard disk space depending on how many domains and files are backed-up.</b></big>" . inputform5($inputparams);
		} else {


			$filename = 'backup-' . date('Y-m-d_H-i-s');
			$this->output .= "What will do/backup:<br>
		Backup mysql:<b>$backupmysql</b><br>
		Backup site files:<b>$backupfiles</b><br>
		Backup ehcp files:<b>$backupehcpfiles</b><br>
		Backup ehcp database:<b>$backupehcpdb</b><br>
		Backup emailaccounts:<b>$emailaccounts</b><br>
		Backup email contents:<b>$emailcontents</b><br>
		Gzip backup:<b>$gzipbackup</b><br>
		";

			$backup = ''; # what will be backup
			if ($backupmysql)
				$backup .= '-mysql';
			if ($backupfiles)
				$backup .= '-files';
			if ($backupehcpfiles)
				$backup .= '-ehcpfiles';
			if ($backupehcpdb)
				$backup .= '-ehcpdb';
			if ($emailme)
				$backup .= '-emailme';
			if ($emailaccounts)
				$backup .= '-emailaccounts';
			if ($emailcontents)
				$backup .= '-emailcontents';
			if ($gzipbackup)
				$backup .= '-gzipbackup';

			$backupname .= '-Backups:' . $backup;

			$this->executeQuery("insert into backups (backupname,filename,date,status) values ('$backupname','$filename','" . date_tarih() . "','command sent to ehcp daemon by ehcp gui')");
			$this->addDaemonOp("daemonbackup", '', $filename, $backup, 'opname:backup');
			$this->echoln("Backup will be started on your Server soon.  Click <a href='?op=listbackups'>here to list backups and see status</a> ");
		}

		$this->showSimilarFunctions('backup');
		return True;
	}

	function pwdls($comment = '', $dir = '')
	{
		echo "\n $comment \npwd is:" . getcwd() . "\n";
		passthru('ls -l ' . $dir);
		echo "\n\n";
		sleep(1);
	}

	function backup_databases2($dbs, $mysqlusers, $file)
	{
		$this->requireCommandLine(__FUNCTION__);
		$foundEHCPDB = false;
		# set empty file then fill with dump of each mysql database, in ehcp, (before vers 0.27: all databases were dumped, that caused: malfunction because of restore of mysql db itself... now, mysql db is not restored... so, passwords of new mysql server are kept after restore... )

		print_r($dbs);

		if (count($dbs) > 0) {
			foreach ($dbs as $db) {
				$sql = "";
				if ($db['dbname'] == "ehcp") {
					$foundEHCPDB = true;
					// Delete EHCP database since importing the backup will fail if there's existing values in it.
					$sql .= "DROP DATABASE IF EXISTS `" . $db['dbname'] . "`;\n";
				}

				$sql .= "create database if not exists `" . $db['dbname'] . "`;\n";
				$sql .= "use `" . $db['dbname'] . "`;\n";
				writeoutput2($file, $sql, "a");

				$cmd = escapeshellcmd("mysqldump " . $db['dbname'] . " -u root --password=" . $this->conf['mysqlrootpass']) . " >> " . escapeshellcmd($file);
				passthru3($cmd, __FUNCTION__);
			}
		}

		if (count($mysqlusers) > 0) {
			foreach ($mysqlusers as $user) {
				#print_r($user);
				// Get MySQL User and DB Information
				$dbname = $user['dbname'];
				$dbusername = $user['dbusername'];
				$dbuserpass = $user['password'];

				// Put grant usage permissions into the file
				$sql = "GRANT USAGE ON *.* TO '$dbusername'@'localhost' IDENTIFIED BY '$dbuserpass';";
				$sql .= "\n" . "GRANT ALL PRIVILEGES ON `" . $dbname . "`.* TO '$dbusername'@'localhost';";

				// Check for remote access permissions
				$q = " SET PASSWORD FOR '$dbusername'@'%' = PASSWORD('$dbuserpass')";
				$result = $this->mysqlRootQuery($q, true);
				if ($result !== false) {
					// Put grant usage permissions into the file
					$sql .= "\nGRANT USAGE ON *.* TO '$dbusername'@'%' IDENTIFIED BY '$dbuserpass';";
					$sql .= "\n" . "GRANT ALL PRIVILEGES ON `" . $dbname . "`.* TO '$dbusername'@'%';";
				}

				writeoutput2($file, $sql, "a");
			}
		}

		// Flush privileges to activate the new user accounts and passwords
		$fixAccounts = "FLUSH PRIVILEGES;";
		writeoutput2($file, $fixAccounts, "a");

	}

	function backup_databases($filt, $file)
	{ # yeni fonks.
		$this->requireCommandLine(__FUNCTION__);

		if ($filt <> '')
			$where = " where $filt";
		else
			$where = "";

		$dbs = $this->query("select dbname from mysqldb $where");
		$mysqlusers = $this->query("select * from mysqlusers $where");

		$this->backup_databases2($dbs, $mysqlusers, $file);
	}

	function daemonBackup($action, $info, $info2 = '')
	{
		$this->requireCommandLine(__FUNCTION__);

		# do all operations inside /var/backup

		$filename = securefilename($info);
		$backupdir = $this->miscconfig['backupdir'];
		if ($backupdir == '')
			$backupdir = "/var/backup";
		$dirname = "$backupdir/$filename"; # this may be a variable in misc/options table

		echo "Backup starting..: dirname:$dirname, filename:$filename ($info2)";
		$this->executeQuery("update backups set status='processing now... by daemon' where filename='$filename'");
		$this->executeQuery("delete from operations"); # delete operations to avoid re-run of backup/restore..
		#$this->executeQuery("Flush tables");
		$tarwithparams = "tar -zcvf ";

		passthru2("mkdir -p  $dirname");
		chdir($dirname);

		$this->pwdls();

		$dbs = array();

		if (strstr($info2, '-mysql')) {
			$dbs = $this->query("select dbname from mysqldb");
			$mysqlusers = $this->query("select * from mysqlusers");
		}

		if (strstr($info2, '-ehcpdb'))
			$dbs[] = array("dbname" => "ehcp");

		$this->backup_databases2($dbs, $mysqlusers, "$dirname/mysql.sql");



		$this->pwdls();

		if (strstr($info2, '-ehcpfiles'))
			passthru2("$tarwithparams ehcp.tgz " . $this->ehcpdir); # ehcp files will be backedup

		if (strstr($info2, '-files'))
			passthru2("$tarwithparams files.tgz " . $this->vhostsdir . " --exclude=" . $this->ehcpdir); # files will be backedup

		if (strstr($info2, '-emailcontents'))
			passthru2("$tarwithparams home_vmail.tgz /home/vmail"); # files will be backedup

		$this->pwdls();

		# combine all in one file
		if (strstr($info2, '-gzipbackup')) {
			chdir('/var/backup');
			passthru2("$tarwithparams $filename.tgz $filename");
			$size = filesize("$filename.tgz");
			if (!$size)
				$size = 0;

			$this->pwdls();

			passthru2("rm -rf " . $filename);
		} else {
			$size = 0;
			$filename = 'Not gzipped into single file, as you requested';
		}

		$commandPostBackup = 'echo 1 > /var/backup/' . $filename . '_STATUS';
		passthru3($commandPostBackup);

		$this->check_mysql_connection();
		$this->executeQuery("update backups set status='complete',size=$size where filename='$filename'");


		echo "finished backups...";
		chdir($this->ehcpdir); # return back to original dir
		$this->infotoadminemail("An EHCP backup has successfully been created.&nbsp; More information about this backup can be viewed in the panel.", "Backup Created", False);
		return True;


	}

	function is_email_user()
	{
		return strstr($this->activeuser, '@');
	}

	function displayHome($homefile = '')
	{

		# display different home pages depending on logedin user.. admin, reseller, domain admin, email user (There are four levels of login..)

		if ($this->userinfo['maxdomains'] == 1) {
			$domainname = $this->getField($this->conf['domainstable']['tablename'], "domainname", "panelusername='$this->activeuser'");
			$this->setselecteddomain($domainname);
		}

		if ($this->selecteddomain <> '') {
			$this->domaininfo = $this->getDomainInfo($this->selecteddomain);
		}

		if ($homefile <> '') {
			$homepage = $homefile;
		} elseif ($this->selecteddomain <> '' and $this->domaininfo['serverip'] <> '') {
			$homepage = 'homepage_remotehosting_dnsonly';
			$this->output .= "<br>This domain is <b>dns hosted</b>, directed to ip:" . $this->domaininfo['serverip'] . "<br>";
		} elseif ($this->is_email_user()) {
			$homepage = 'homepage_emailuser';
		} elseif ($this->userinfo['maxdomains'] == 1) {
			$homepage = 'homepage_domainadmin';
		} elseif (!$this->isadmin() and $this->userinfo['maxdomains'] > 1) {
			if ($this->selecteddomain == '') {
				if ($this->userinfo['maxpanelusers'] > 0) {
					$homepage = 'homepage_reseller';
				} else {
					$homepage = 'homepage_paneluser';

					// Older theme compatibility
					$file = "templates/$this->template/$this->currentlanguage/" . $homepage . "_" . $this->currentlanguage . ".html";
					if (!file_exists($file)) {
						$homepage = 'homepage_reseller';
					}
				}
			} else {
				$homepage = 'homepage_domainadmin_forreseller';
			}
		} elseif ($this->isadmin()) {
			if ($this->selecteddomain == '')
				$homepage = 'homepage_serveradmin';
			else
				$homepage = 'homepage_domainadmin_forreseller'; # domain pages for reseller and admin  are same
		} else
			$homepage = 'homepage_domainadmin'; # this line should never be executed..


		# buraya bide email girisi icin homepage yapilacak, emailde bitek sifre degistirme, ve belkide yonlendirme olacak...

		$pageinfo = "Homepage Template used to generate this page: " . $homepage . "_" . $this->currentlanguage . ".html <br>";

		$this->output .= str_replace(array("{selecteddomain}"), array($this->selecteddomain), $this->loadTemplate($homepage));
		$this->output .= " Your language: $this->currentlanguage

(<a href='?op=setlanguage&id=en'>En</a>
<a href='?op=setlanguage&id=tr'>Tr</a>
<a href='?op=setlanguage&id=german'>German</a>
<a href='?op=setlanguage&id=spanish'>Spanish</a>
<a href='?op=setlanguage&id=fr'>Fr</a>
<a href='?op=setlanguage&id=lv'>Latvie&#353;u</a>
<a href='?op=setlanguage&id=it'>Italian</a>
<br>
<table>
<tr>
<td><a href='?op=setlanguage&id=en'><img height=30 width=50 src=images/en.jpg border=0></a></td>
<td><a href='?op=setlanguage&id=tr'><img height=30 width=50 src=images/tr.jpg border=0></a></td>
<td><a href='?op=setlanguage&id=german'><img height=30 width=50 src=images/german.jpg border=0></a></td>
<td><a href='?op=setlanguage&id=spanish'><img height=30 width=50 src=images/spanish.jpg border=0></a></td>
<td><a href='?op=setlanguage&id=fr'><img height=30 width=50 src=images/fr.jpg border=0></a></td>
<td><a href='?op=setlanguage&id=lv'><img height=30 width=50 src=images/lv.jpg border=0></a></td>
<td><a href='?op=setlanguage&id=it'><img height=30 width=50 src=images/it.jpg border=0></a></td>

</tr>
</table>
<br>
$pageinfo
";


		$this->output .= "<br><br> Welcome " . $this->activeuser;
		$_SESSION['myserver'] = false; # reset mysql server data..
	}


	function changeMyPass()
	{
		global $oldpass, $newpass, $newpass2, $_insert;
		$this->getVariable(array('oldpass', 'newpass', 'newpass2', '_insert'));

		if (!$_insert) {
			$inputparams = array(
				array('oldpass', 'password', 'lefttext' => 'Your Old Password:'),
				array('newpass', 'password', 'lefttext' => 'New Password:'),
				array('newpass2', 'password', 'lefttext' => 'New Password Again:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= inputform5($inputparams);

		} else {
			if ($newpass <> $newpass2) {
				$this->errorText("Both entered passwords do not match. Please try again.");
			} elseif (!$this->isPasswordOk($this->activeuser, $oldpass)) {
				$this->errorText("Your current password is not correct.");
			} elseif ($newpass == '' || $newpass2 == '') {
				$this->output .= "You did not enter a new unique password!";
			} else {

				if ($this->conf['logintable']['passwordfunction'] == '') {
					$set = "'$newpass'";
				} else {
					$set = $this->conf['logintable']['passwordfunction'] . "('$newpass')";
				}
				$where = $this->conf['logintable']['usernamefield'] . "='" . $this->activeuser . "'";
				$q = "update " . $this->conf['logintable']['tablename'] . " set " . $this->conf['logintable']['passwordfield'] . "=$set where $where";
				$this->executeQuery($q);
				$this->okeyText('Changed your pass ');
			}
		}
	}

	function stopvsftpd()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("vsftpd", "stop"));
	}

	function startvsftpd()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("vsftpd", "start"));
	}

	function restartvsftpd()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("vsftpd", "restart"));
	}

	function stopmysqld()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("mysql", "stop"));
	}

	function startmysqld()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("mysql", "start"));
	}

	function restartmysqld()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("mysql", "restart"));
	}

	function stopbind9()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("bind9", "stop"));
	}

	function startbind9()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("bind9", "start"));
	}

	function restartbind9()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("bind9", "restart"));
	}

	function stopapache2()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("apache2", "stop"));
	}

	function startapache2()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("apache2", "start"));
	}

	function restartapache2()
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2(getServiceActionStr("apache2", "restart"));
	}

	function service($name, $op)
	{
		$this->requireCommandLine(__FUNCTION__);
		return passthru2("service $name $op");
	}


	function editdomain()
	{
		# sadece reseller/admin edit edebilmeli..

		global $domainname, $_insert, $status;
		$this->getVariable(array('domainname', '_insert', 'status'));

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$domaininfo = $this->getDomainInfo($domainname);
		$success = True;

		if (!$_insert) {
			$inputparams = array(
				array('status', 'select', 'secenekler' => $this->statusActivePassive, 'default' => $domaininfo['status'], 'lefttext' => 'Status'),
				array('domainname', 'hidden', 'default' => $domainname),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= inputform5($inputparams);

		} else { # editpaneluser icinde active/passive yapilmasi lazim. kullanici aktiflestirmede, eger tek domaini varsa, paneluser'ini da aktiflestirmek lazim.
			$filt = $this->applyGlobalFilter("domainname='$domainname'");
			$domaininfo = $this->getDomainInfo($domainname);
			$domainpaneluser = $domaininfo['panelusername'];
			$domainsayisi = $this->recordcount("domains", "panelusername='$domainpaneluser'"); # bu paneluser'in kac tane domaini var ? howmany domains this paneluser has?

			$this->debugtext("filter: $filt");
			$success = $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set status='$status',reseller='" . $this->activeuser . "' where $filt");
			if ($domainsayisi == 1)
				$success = $this->executeQuery("update panelusers set status='$status' where panelusername='$domainpaneluser' and reseller='" . $this->activeuser . "'");

			$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
			return $this->ok_err_text($success, 'Domain status was successfully updated.', 'Unable to update domain status.');

		}

	}

	function applyfordomainaccount()
	{ # add domain, paneluser and ftp user once
		if ($this->miscconfig['userscansignup'] == '')
			$this->showUnauthorized();

		global $domainname, $ftpusername, $ftppassword, $quota, $upload, $download, $panelusername, $paneluserpass, $_insert;
		$this->getVariable(array("domainname", "ftpusername", "ftppassword", "quota", "upload", "download", "panelusername", "paneluserpass", "_insert"));

		if (!$_insert) {
			#if(!$this->beforeInputControls("adddomain",array())) return false;
			$inputparams = array(
				'domainname',
				array('panelusername', 'lefttext' => 'Panel username:'),
				array('paneluserpass', 'password_with_generate', 'lefttext' => 'Panel user password:'),
				array('ftpusername', 'lefttext' => 'FTP username:'),
				array('ftppassword', 'password_with_generate', 'lefttext' => 'FTP Password:'),
				array('quota', 'lefttext' => 'Quota (MB)', 'default' => 100),
				array('upload', 'lefttext' => 'Upload Bandwidth (KB/s)', 'default' => 1000),
				array('download', 'lefttext' => 'Download Bandwidth (KB/s)', 'default' => 1000),
				array('id', 'hidden', 'default' => $id),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= inputform5($inputparams);
		} else {
			# existcontrol addDomainDirect icinde
			if ($this->addDomainDirect($domainname, $panelusername, $paneluserpass, $ftpusername, $ftppassword, $this->status_passive))
				$this->output .= "Your application is recieved. You will be informed when your domain is activated.";
			$this->infotoadminemail('A new domain application has been received.', 'New Domain Application');
		}
	}

	function applyforaccount()
	{
		if ($this->miscconfig['userscansignup'] == '')
			$this->showUnauthorized();

		$this->output .= "<a href='?op=applyfordomainaccount'>Apply for Web Hosting Account</a><br>";
	}

	function aboutcontactus()
	{
		$alanlar = array("email", "msn", "skype", "adisoyadi", "firma", "sehir", "adres", "tel", "fax", "talepler");
		foreach ($alanlar as $al)
			global $$al;
		$this->getVariable($alanlar);
		if (isset($email) and $talepler <> '') {
			$subject = "New EHCP Message";
			$mesaj = "Dear Admin,<br><br>The following message was successfully submitted via the panel:<br><br>Name: " . $adisoyadi . "<br>Company: " . $firma . "<br>City: " . $sehir . "<br>Address: " . $adres . "<br>Email: " . $email . "<br>MSN: " . $msn . "<br>Skype: " . $skype . "<br>Phone Number: " . $tel . "<br>Message:<br>" . $talepler . "<br>";
			$headers = "From: $email";
			$fromAddress = $email;

			$this->infotoadminemail($mesaj, $subject, false, $fromAddress);
			#emailadmins($subject,$mesaj,$headers);
			$this->sendEmail($email, "Message Received", "Hi EHCP Force User,<br><br>We received your message of: $talepler");
			return $this->okeyText($this->sayinmylang("yourmessage_received"));

		} else {
			$out .= "
		<p class=yazi>" . $this->sayinmylang("enter_yourcontactinfo") . "<br></p>
		<form id=form2 method=post>
		<table>
		<tr><td  style='width:30%'>" . $this->sayinmylang("name_surname") . ": </td><td ><input type=text name=adisoyadi value=$adisoyadi></td></tr>
		<tr><td  style='width:30%'>Email: </td><td ><input type=text name=email value=$email></td></tr>
		<tr><td  style='width:30%'>Msn: </td><td ><input type=text name=msn value=$msn></td></tr>
		<tr><td  style='width:30%'>Skype: </td><td ><input type=text name=skype value=$skype></td></tr>
		<tr><td  style='width:30%'>Tel: </td><td ><input type=text name=tel value=$tel></td></tr>
		</table>
		<br>
		<p class=yazi>
		" . $this->sayinmylang("write_yourmessage_here") . " <br>
		<textarea cols=30 name=talepler rows=9>$talepler</textarea>
		<br></p>
		<input type=submit>
		</form>
		<br>

		";
			#ajaxsubmit($link,"submit","G�nder","div_1","form2")
		}
		$this->output .= $out;
	}


	function htmlekle2($id)
	{ # bunun tek farki echo yapmaz. return eder.
		$id = trim($id);
		if ($id == '') {
			return "id empty. (htmlekle2)";
		}
		if ($this->recordcount("html", "id='$id'") == 0) {
			return "($id) id'li kod not found. <br>" . $this->sayinmylang('perhaps_db_error');
		}

		global $nestcount;
		$nestcount++;
		if ($nestcount > 100) {
			echo "<hr>too many nestcount (100, htmlekle2)";
			$this->showexit();
		}
		;
		#$query="select * from html where id='$id'";
		$kod = $this->getField('html', 'htmlkodu', "id='$id'");
		#$kod=dbresult($query,array("htmlkodu"));
		#$kod="<kodadi=$id>".$kod."</kodadi=$id>";

		$parcalar = explode("{kod}", $kod);
		$sayi = count($parcalar);

		$out = '';
		$out .= "\n<kodadi=$id>";
		for ($i = 0; $i < $sayi; $i++) {
			if (iseven($i)) {
				$out .= $parcalar[$i];
			} else {
				$out .= $this->htmlekle2($parcalar[$i]);
			}
		}
		;
		$nestcount--;
		$out .= "</kodadi=$id>";
		return $out;
	}

	function sendEmail($to, $subject, $message)
	{
		global $ehcpversion;

		// Get today's date
		$date = date('m-d-Y H:i:s');

		// Prepend to Subject
		$preSubject = "EHCP Force ::";

		// Subject empty?
		if (!$subject)
			$subject = "Important";

		// Email headers	
		$headers = 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
		$headers .= "From: " . $this->conf['adminemail'];

		// Add this to the end of the email message.
		$message .= "<br><br>~<br>Thanks<br>EHCP Force Administration";

		// Send the mail
		mail($to, $preSubject . ' ' . $subject, $message, $headers);
	}

	function infotoadminemail($str, $subj = '', $todeveloper = True, $fromAddress = '')
	{
		global $lastmsgtoehcpdeveloper, $ehcpversion;

		// Get today's date
		$date = date('m-d-Y H:i:s');

		// Email headers
		$headers = 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
		if (empty($fromAddress)) {
			$headers .= "From: " . $this->conf['adminemail'];
		} else {
			$headers .= "From: " . $fromAddress;
		}

		// Prepend to subject
		$preSubject = "EHCP Force Information ::";
		// Subject empty?
		if (!$subj)
			$subj = "Important";

		// Add server IP address information to email
		$str .= "<p>Server IP Address: " . $this->singleserverip . "</p>";

		// Add date and username for who performed the action
		if (isset($this->activeuser) && !empty($this->activeuser)) {
			$str .= "<p>Operation Performed by User:&nbsp; " . $this->activeuser . "<br>Date Performed:&nbsp; " . $date . "</p>";
		}

		// Add signature post email message
		$str .= "<br><br>~<br>Thanks<br>EHCP Force Administration";

		// Send email
		return mail($this->conf['adminemail'], $preSubject . ' ' . $subj, $str, $headers);
	}

	function infoemail($adminsubject, $adminmessage, $useremail, $usersubject, $usermessage, $todeveloper = True)
	{
		# will replace function infotoadminemail
		global $lastmsgtoehcpdeveloper;

		$headers = 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
		$headers .= "From: " . $this->conf['adminemail'];

		$preSubject = "EHCP Force Information ::";

		if (!$adminsubject)
			$adminsubject = "Important";
		if (!$usersubject)
			$usersubject = "Important";
		$adminmessage .= "<br>Server IP Address: " . $this->singleserverip;
		// Add date and username for who performed the action
		if (isset($this->activeuser) && !empty($this->activeuser)) {
			// Get today's date
			$date = date('m-d-Y H:i:s');
			$adminmessage .= "<br>Operation Performed by User:&nbsp; " . $this->activeuser . "<br>Date Performed:&nbsp; " . $date;
		}

		$adminmessage .= "<br><br>~<br>Thanks<br>EHCP Force Administration";
		$usermessage .= "<br><br>~<br>Thanks<br>EHCP Force Administration";

		mail($this->conf['adminemail'], $preSubject . ' ' . $adminsubject, $adminmessage, $headers);
		mail($useremail, $preSubject . ' ' . $usersubject, $usermessage, $headers);
	}

	function infoEmailToUserandAdmin($useremail, $subject, $message, $todeveloper = True)
	{

		$this->infoemail($subject, $message, $useremail, $subject, $message, $todeveloper);
	}

	function loadConfigIntoArray($q)
	{
		$res = $this->query($q);

		if (is_array($res)) {
			# fill miscconfig variable
			$miscconfig = array();
			foreach ($res as $c) {
				if ($c['group'] <> '')
					$gr = $c['group'] . ':';
				else
					$gr = '';
				#print "($gr)(".$c['name'].'):('.$c['value'].")<br>";

				if ($c['value'] <> '')
					$miscconfig[$gr . $c['name']] = trimstrip($c['value']);
				else
					$miscconfig[$gr . $c['name']] = trimstrip($c['longvalue']);
			}

		} else {
			$this->output .= "Config from a table cannot be read...($q) <br>";
		}
		#echo "burasi:".print_r2($miscconfig);

		return $miscconfig;
	}

	function loadConfigIntoArray2($q)
	{
		$res = $this->query($q);

		if (is_array($res)) {
			# fill miscconfig variable
			$miscconfig = array();
			foreach ($res as $c) {
				if ($c['group'] == '')
					$c['group'] = 'nogroup';
				$miscconfig[$c['group']][$c['name']] = trimstrip($c['value']);
			}

		} else {
			$this->output .= "Config from a table cannot be read...($q) <br>";
		}

		return $miscconfig;
	}


	function checkConnection($opname)
	{
		if (!$this->connected) {
			$this->output .= "<br>Following operation cannot be completed, since you have not connected to database: $opname <br>";
			return false;
		} else
			return True;
	}

	function loadConfigWithDaemon()
	{
		$this->loadConfig();
		$this->addDaemonOp("loadconfig", '', '', '', __FUNCTION__);
	}

	function loadSettings()
	{
		$this->settings = $this->loadConfigIntoArray2("select * from settings where panelusername is null or panelusername=''");
	}

	function EHCPIsUsingHTTPS()
	{
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
	}

	function loadConfig()
	{
		global $skipUpdateWebstats;

		if (!$this->checkConnection('config load'))
			return false;

		$miscconfig = $this->loadConfigIntoArray("select * from misc where panelusername is null or panelusername=''");
		#$this->output.="<hr><b>Loading config. </b>".print_r2($miscconfig)."<hr>";

		if (is_array($miscconfig))
			foreach ($miscconfig as $name => $value)
				switch ($name) {
					case 'dnsip':
						$this->conf['dnsip'] = trim($value);
						break;
					case 'adminname':
						$this->conf['adminname'] = trim($value);
						break;
					case 'adminemail':
						$this->conf['adminemail'] = trim($value);
						break;
				}

		#$this->output.="dnsip: ".$this->conf['dnsip'];	
		$this->miscconfig = $miscconfig;
		$this->miscconfig['singleserverip'] = $this->miscconfig['dnsip']; # single ip hosting.
		if ($this->miscconfig['webservertype'] == '') {
			$this->echoln2("webservertype seems empty. defaulting to apache2 (check your options->advanced)");
			$this->miscconfig['webservertype'] = 'apache2';
		}

		$HTTPMode = $this->EHCPIsUsingHTTPS() ? "https://" : "http://";
		$this->ehcpurl = $HTTPMode . $this->conf['dnsip'] . '/ehcp';

		#$this->output.=print_r2($miscconfig);
		# get defaultlanguage value from db, options, misc table..
		if ($this->miscconfig['defaultlanguage'])
			$this->defaultlanguage = $this->miscconfig['defaultlanguage'];
		if ($this->defaultlanguage == '')
			$this->defaultlanguage = 'en';
		$this->currentlanguage = $_SESSION['currentlanguage'];
		if ($this->currentlanguage == '')
			$this->currentlanguage = $this->defaultlanguage;


		if ($this->conf['dnsip'] == '') {
			$this->output .= "<font size=+1>Your DNS or Server IP address is not set.<br>
		This causes ehcp/dns/bind/named to malfunction.<br>
		Please set it in your <a href='?op=options'>Settings</a></font><br><br>";
		}

		# other settigns, template, session etc..

		$this->selecteddomain = $_SESSION['selecteddomain'];
		$this->template = $_SESSION['template']; # load template in session if any..
		if ($this->template == '')
			$this->template = $this->miscconfig['defaulttemplate'];
		$this->loadServerPlan();

		$skipUpdateWebstats = ($this->miscconfig['enablewebstats'] == '');
		$this->dnsip = $this->miscconfig['dnsip'];
		$this->apachePortFile = "/etc/apache2/ports.conf";

		if ($this->commandline) {
			print "\nEHCP config reloaded...";
			print "\n\nLatest miscconfig:";
			print_r($this->miscconfig);
		}


		$this->loadSettings(); # this is new settings func. will transition to that. 
		return True;
	}

	function saveConfig()
	{
		# to be coded later .. saves class conf to db using setConfigValue
	}

	function getConfigValueOrLongvalue($configname, $field = '')
	{

		if ($this->recordcount("misc", "name='$configname'") == 0) {
			$this->output .= "<br>Config value not found: $configname<br>";
		} else {
			if ($field <> '')
				return $this->getField("misc", $field, "name='$configname'");
			else {
				$qu = "select * from misc where name='$configname'";
				$res = $this->query($qu);
				if ($res[0]['value'] <> '')
					return $res[0]['value'];
				else
					return $res[0]['longvalue'];
			}
		}
	}


	function getConfigValue($configname, $field = '')
	{
		if (!$field)
			$field = 'value';
		if ($this->recordcount("misc", "name='$configname'") == 0) {
			$this->output .= "<br>Config value not found: $configname<br>";
		} else {
			return $this->getField("misc", $field, "name='$configname'");
		}
	}

	function setConfigValue($configname, $value, $field = '')
	{ # sets or inserts a config value..
		if (!$field)
			$field = 'value';
		if (!$value)
			$value = '';
		if ($this->recordcount("misc", "name='$configname'") > 0) {
			return $this->executeQuery("update misc set $field='$value' where name='$configname'");
		} else {
			return $this->executeQuery("insert into misc (name,$field) values ('$configname','$value')");
		}
	}

	function setSettingsValue($group, $configname, $value, $field = '')
	{ # sets or inserts a config value..
		if (!$field)
			$field = 'value';
		if ($this->recordcount("settings", "name='$configname'") > 0) {
			return $this->executeQuery("update settings set $field='$value' where name='$configname' and `group`='$group'");
		} else {
			return $this->executeQuery("insert into settings (`group`,name,$field) values ('$group','$configname','$value')");
		}
	}

	function setConfigValue2($configname = '')
	{
		# displays input and sets config value
		$this->requireAdmin();
		global $_insert, $value, $configname2;
		$this->getVariable(array('_insert', 'value', 'configname2'));


		if ($_insert) {
			$success = $this->setConfigValue($configname2, $value);
			if ($configname2 == 'dnsip')
				$this->addDaemonOp("syncdns", '', '');
			return $this->ok_err_text($success, "Successfully set configuration value for $configname2 as $value.", "Failed setting configuration value for $configname2 as $value.");
		} else {
			$currentvalue = $this->getConfigValue($configname);
			$inputparams = array(
				array('value', 'lefttext' => 'Value'),
				array('configname2', 'hidden', 'default' => $configname),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "($configname) setting: " . inputform5($inputparams);
		}
	}

	function errorTextExit($str)
	{
		$this->errorText($str);
		$this->showexit();
	}

	function requireReseller()
	{
		if (!($this->isadmin() || $this->isreseller))
			$this->errorTextExit('This operation requires admin or reseller rights!');
	}

	function requireAdmin()
	{
		if (!$this->isadmin())
			$this->errorTextExit('This operation requires admin rights!');
	}

	# why so much call and call?  because this is a timely growing, developing panel, so, something settles down in time...

	function ismydomain($domainname)
	{
		return $this->isuserpermited('domainowner', $domainname);
	}

	function isEmailUser()
	{
		return strstr($this->activeuser, '@'); # active username has @ in its username, hence, this is an email user.
	}

	function requireEmailUser()
	{
		if ($this->isEmailUser())
			return True; # bu fonksiyon sadece email kullanicilari icin..
		else
			$this->errorTextExit('This operation requires Email User to be loged in..');
	}

	function requireMyDomain($domainname)
	{
		if ($this->isadmin())
			return True;
		if (!$this->ismydomain($domainname))
			$this->errorTextExit("<br></b>$domainname does not belong to your account!</b><br>");
	}

	function isLimitExceeded($table1, $where1, $table2, $field, $where2)
	{
		# is count of rows of table1 where "where1" is bigger then the field defined in table2 where "where2"
		$sayi = $this->recordcount($table1, $where1);
		$max = $this->getField($table2, $field, $where2);

		#$this->output.=print_r2($sayi);
		if ($max == "") {
			$this->errorText("Error: $field for user is not defined.");
			return True;
		} elseif ($sayi >= $max) {
			$this->errorText("Error: You exceeded your $field (max) quota of: $max <br>Please contact an administrator.<br>");
			return True;
		}
		return false;
	}

	function isuserlimitexceeded($limittype = '', $user = '')
	{
		# check the user limits, such as # of domains, users, quota etc. to be coded later.
		if ($user == '')
			$user = $this->activeuser;
		$filter = " panelusername='$user'";
		if ($user == '')
			return True;

		switch ($limittype) { # different naming issue in conf...
			case "maxdomains":
				return $this->isLimitExceeded($this->conf['domainstable']['tablename'], $filter, $this->conf['paneluserstable']['tablename'], "maxdomains", $filter);
				break;
			case "maxsubdomains":
				return $this->isLimitExceeded($this->conf['subdomainstable']['tablename'], $filter, $this->conf['paneluserstable']['tablename'], "maxsubdomains", $filter);
				break;
			case "maxpanelusers":
				return $this->isLimitExceeded($this->conf['paneluserstable']['tablename'], "reseller='$user'", $this->conf['paneluserstable']['tablename'], "maxpanelusers", $filter);
				break;

			case "maxftpusers":
				return $this->isLimitExceeded($this->conf['ftpuserstable']['tablename'], "reseller='$user'", $this->conf['paneluserstable']['tablename'], "maxftpusers", $filter);
				break;

			case "maxemails":
				return $this->isLimitExceeded($this->conf['emailuserstable']['tablename'], $this->conf['emailuserstable']['ownerfield'] . "='$user'", $this->conf['paneluserstable']['tablename'], "maxemails", $filter);
				break;

			case "maxdbs":
				return $this->isLimitExceeded($this->conf['mysqldbstable']['tablename'], "panelusername='$user'", $this->conf['paneluserstable']['tablename'], "maxdbs", $filter);
				break;

			case "maxemails":
				return $this->isLimitExceeded($this->conf['emailuserstable']['tablename'], "panelusername='$user'", $this->conf['paneluserstable']['tablename'], "maxemails", $filter);
				break;

			case "maxvps":
				#return $this->isLimitExceeded($this->conf['emailuserstable']['tablename'],"panelusername='$user'",$this->conf['paneluserstable']['tablename'],"maxemails",$filter);
				return False; # not checked yet.
				break;

			default:
				$this->errorText($this->sayinmylang("int_undefined_limittype") . ": $limittype <br>");
				return True;
		}

		#$this->output.="max limit error: $limittype , you exceeded your limit<br>";
		return false;
	}

	function isuserpermited($permtype, $item = '', $user = '')
	{ # user permission check, is user authorized to do something
		if ($this->isadmin())
			return True; # admin is permitted to anything.

		if ($user == '')
			$user = $this->activeuser;
		switch ($permtype) {
			case "deletedomain":
				$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($user);
				$filt = "domainname='" . $item . "'";
				$reseller = $this->getField($this->conf['domainstable']['tablename'], "reseller", $filt);
				if (in_array($reseller, $userHasAccessToTheseChildrenUsers) or $this->isadmin())
					return True;

				break;
			case "caneditpaneluser":
				$reseller = $this->getField($this->conf['paneluserstable']['tablename'], "reseller", "id=$item");
				$panelusername = $this->getField($this->conf['paneluserstable']['tablename'], "panelusername", "id=$item");
				if ($this->activeuser == $panelusername and !$this->isadmin())
					return $this->errorText('You are unable to edit your own account.');
				return ($this->isadmin() or ($reseller == $this->activeuser));
				break;

			case "domainowner":
				$domainowner = $this->alanal2($this->conf['domainstable']['tablename'], array($this->conf['domainstable']['ownerfield'], $this->conf['domainstable']['resellerfield']), "domainname='$item'");
				if ($this->isadmin() or trim($domainowner[$this->conf['domainstable']['ownerfield']]) == trim($this->activeuser) or trim($domainowner['reseller']) == trim($this->activeuser))
					return True;
				break;

			default:
				return $this->errorText("Internal EHCP Error: Undefined permtype: $permtype");
		}

		return $this->errorText("Permission Error: $permtype on item $item");
	}

	function editPanelUser()
	{
		global $id, $newpass, $newpass2;
		$this->getVariable(array("id", 'newpass', 'newpass2')); # can edit only if (s)he is reseller of that panel user or is admin..

		$info = $this->getPanelUserInfo($id);

		if (!$this->isuserpermited('caneditpaneluser', $id)) { # is this owner of that panel user ?
			return $this->errorText("You are not authorized to edit this panel user.");
		} else {
			$extra = array(array('status', 'select', 'secenekler' => $this->statusActivePassive));
			if (!$newpass and !$newpass2 and $id)
				$this->editrow("paneluserstable", "id=$id", $extra);
		}

		if (!$newpass and !$newpass2 and $id) {
			$_SESSION["temp_id"] = $id;
			$inputparams = array(
				array('newpass', 'lefttext' => 'New Password:'),
				array('newpass2', 'lefttext' => 'Enter New Password Again:'),
				array('id', 'hidden', 'default' => $id),
				array('op', 'hidden', 'default' => 'editpaneluser')
			);
			$this->output .= "<br>Reset User Pass: <br>" . inputform5($inputparams);

		} elseif ($newpass and $newpass2 and $id and $newpass == $newpass2) {
			if ($id == $_SESSION["temp_id"]) {
				$success = $this->executeQuery("update " . $this->conf['paneluserstable']['tablename'] . " set password=md5('$newpass') where id=$id ", '', __FUNCTION__);
			} else {
				$success = false;
			}
			unset($_SESSION["temp_id"]);
			$this->ok_err_text($success, "Successfully changed password.", "Failed to change password.");
		}

		$this->output .= "<br><a href='?op=listpanelusers'>&larr; Back to User List</a>";
	}

	function impersonatePanelUser()
	{
		global $id;

		$this->requireAdmin();
		$this->getVariable(array("id")); # can edit only if (s)he is reseller of that panel user or is admin..

		$info = $this->getPanelUserInfo($id);

		if ($info !== false && $info != null && array_key_exists("panelusername", $info) && !empty($info["panelusername"]) && $info["panelusername"] != "admin") {
			$username = $info["panelusername"];
			$this->dologin2($username, '');
			header('Location: index.php');
		} else {
			$this->ok_err_text(false, "User exists.", "User doesn't exist or you are already signed in as admin.");
		}

		return false;
	}

	function existscontrol($controls)
	{
		# to be coded later. like domain=>"xxx.com"
		$result = True;
		foreach ($controls as $key => $val) {
			$count = 0;
			switch ($key) {
				# vps cases
				case 'ip':
					continue 2; # no ip check, same ip can exists on different servers, perhaps..
				case 'hostip':
					continue 2; # multiple vps will have same hostip
				case "vpsname":
					$count = $this->recordcount($this->conf['vpstable']['tablename'], "vpsname='$val'");
					break;
				# end vps cases


				case "domainname":
					$count = $this->recordcount($this->conf['domainstable']['tablename'], "domainname='$val'");
					break;

				case "ftpusername":
					$count = $this->recordcount($this->conf['ftpuserstable']['tablename'], "ftpusername='$val'");
					break;

				case "panelusername":
					$count = $this->recordcount($this->conf['paneluserstable']['tablename'], "panelusername='$val'");
					break;

				case "dbname":
					$count = $this->recordcount($this->conf['mysqldbstable']['tablename'], "dbname='$val'");
					break;

				case "dbusername":
					$count = $this->recordcount($this->conf['mysqldbuserstable']['tablename'], "dbusername='$val'");
					break;

				case 'email':
					$count = $this->recordcount($this->conf['emailuserstable']['tablename'], "email='$val'");
					break;

				case 'mailusername':
					$count = 0; # mailusername may be multiple for different domains, no problem.
					break;

				default:
					return $this->errorText("Internal EHCP Error: Undefined parameter: $key=>$val  ar: " . print_r2($controls));
			}

			if ($count > 0) {
				$result = $this->errorText("$key already exists: $val ");
			}

		} #foreach
		if (!$result)
			$this->errorText("At least one error occured!");
		return $result;
	} # function

	/*
	steps to control when doing something:
	1- is user active?
	2- is user limit exceeded for that action?
	3- is user permitted to do that?
	4- is user owner of that or authorized?
	5- is target entity already exists ? that is, is domain exists, or is panel users exists ?

	two kind of control:
	before displaying inputs, is user active, limit controls, is user permitted, is user owner(if entity known)
	after submitting inputs, all of these controls, for security, plus, is that alread exists?
	*/

	# ---------------- error ve debug fonksiyonlari...

	function error_occured($source = '', $errstr = '')
	{
		if (!$source)
			$source = 'source not specified';
		$str = $this->sayinmylang('error_occured') . "(source:$source)<hr>err: $errstr <hr>" . $this->conn->ErrorMsg();
		return $this->errorText($str);
	}

	function errorText($str = '', $emailtoadmin = false)
	{
		// Multiple stop signs pls.
		$img = "<img border=0 src='images/stop.png' align=left>";
		if ($this->conn->ErrorMsg() <> '')
			$str .= "<hr>Last Database Error: " . $this->conn->ErrorMsg();

		if ($this->commandline) {
			echo "\n___________________\n$str\n__________________\n";
		} else {
			$this->output .= "<br><table>
							<tr>
								<td align='center' valign='middle' style='width: 105px;'>$img</td>
								<td align='left' valign='middle'><b>An Error Occured:<br><span style='color: #FF0000;' class='error'>$str</span></b></td>
							</tr>
						</table><br>";
		}
		if ($emailtoadmin)
			$this->infotoadminemail($str, "An Error has Occurred", false);
		return false; # errorText function always returns false;
	}

	function okeyText($str = '')
	{
		$img = "<img border=0 src=images/success.png>";
		$this->output .= "<br><table>
							<tr>
								<td align='center' valign='middle' style='width: 105px;'>$img</td>
								<td align='left' valign='middle'><b><span style='color: #69C657;' class='success'>Operation completed successfully!<br>" . $str . "</b></td>
							</tr>
						</table><br>";
		return True; # okeytext always returns True
	}

	function ok_err_text($success, $successtext = '', $failtext = '')
	{
		if ($success)
			$this->okeyText($successtext);
		else
			$this->errorText($failtext);
		return $success;
	}


	function debugtext($str)
	{

		if ($this->debuglevel == 0)
			return false; # z7 mod

		$img = "<img border=0 src=images/debug.jpg>";
		$this->output .= "<br><table>
							<tr>
								<td align='center' valign='middle'>$img</td>
								<td align='center' valign='middle'><b><font color='#996699'>Debug output:<br>" . $str . "</font></b></td>
							</tr>
						</table><br>";
		return True;
	}

	#---- end of error-debug functions


	function isactive($user = '')
	{
		if (!$user)
			$user = $this->activeuser;
		$status = $this->getField($this->conf['paneluserstable']['tablename'], "status", $this->conf['paneluserstable']['usernamefield'] . "='$user'");

		if ($status != $this->status_active) {
			return $this->errorText("Error: User $user is not active.  Your account is not active.  Please contact your hosting provider.  Your domain status is currently set to: $status) ");
		} else
			return True;
	}

	function beforeInputControls($op = '', $params = '')
	{
		# all before input controls in a single place, so after input controls...
		# returns false, if input control fails and user cannor proceed,
		# returns True, if user can proceed

		# first common controls
		if (!$this->isNoPassOp()) { # if no password required, being active is not required. if this is an operation that does not need a pass.. do it without checking if active.
			if (!$this->isactive())
				return false;
		}

		# controls specific to op(eration)
		switch ($op) {
			case "adddomaintothispaneluser":
				return !$this->isuserlimitexceeded('maxdomains');
				break;

			case "adddomain":
				return !(
					$this->isuserlimitexceeded('maxdomains')
					or $this->isuserlimitexceeded('maxpanelusers')
					or $this->isuserlimitexceeded('maxftpusers')
				)
					or
					$this->isNoPassOp(); # for domain requests, no limit is important.. there may be a limit here too.
				break;

			case "addftpuser":
				return !$this->isuserlimitexceeded('maxftpusers') or $this->isNoPassOp(); # for domain requests
				break;

			case 'adddb':
				return !$this->isuserlimitexceeded('maxdbs');
				# old and equivalent statement: if($this->isuserlimitexceeded('maxdbs')) return false;
				# meaning: if user maxdb limit exceeded, user cannot add more, cannot proceed, so return false..
				break;

			case 'addpaneluser':
				return !$this->isuserlimitexceeded('maxpanelusers');
				break;

			case 'addemail':
				return !$this->isuserlimitexceeded('maxemails');
				break;

			case 'addvps':
				return !$this->isuserlimitexceeded('maxvps');
				break;

			case 'addsubdomain':
			case 'addsubdomainwithftp':
				return !$this->isuserlimitexceeded('maxsubdomains');
				break;

			default:
				$this->output .= "Undefined input control: " . $op;


		}

		return True;
	}

	function afterInputControls($op = '', $params = '')
	{
		if (!$this->beforeInputControls($op))
			return false; # same controls as above,
		if (!$this->existscontrol($params))
			return false;
		$domainname = trim($params['domainname']);

		if ($op == 'addvps') {
			foreach (array('vpsname', 'ip', 'hostip') as $check)
				if ($params[$check] == '')
					return $this->errorTextExit("$op: $check parameter cannot be empty.");
			$sayi = $this->recordcount("vps", "vpsname='" . $params['vpsname'] . "' and ip='" . $params['ip'] . "' and hostip='" . $params['hostip'] . "'");
			if ($sayi > 0)
				return $this->errorTextExit("We have another vps with same name, same ip in same host/server");
		}

		# domainname check may be on top, and common for all ops.

		if ($op == 'adddomain' or $op == 'adddomaintothispaneluser') { # common controls for both operation
			if ($domainname == '')
				return $this->errorText("Domain name cannot be empty!");
			if ($domainname == $this->miscconfig['dnsip'] or $domainname == $this->miscconfig['localip'])
				return $this->errorText("You cannot use the IP address of the server as a domain name: $domainname<br>DNS IP Address: " . $this->miscconfig['dnsip'] . "<br>Local IP: " . $this->miscconfig['localip']); # domainde kisitlama yok, ama boyle olunca, abuk subuk seyler olabilir.. ip girince de, sunucu paneli webden ulasilamaz oluyor..
		}

		if ($op == 'adddomain') {
			if ($domainname == '' or $params['ftpusername'] == '' or $params['panelusername'] == '') {
				return $this->errorText("FTP Username or Panel Username cannot be empty.  <br>You provided: <br> " . print_r2($params));
			}
		}

		if ($op == 'adddomaintothispaneluser') {
			if ($params['domainname'] == '') {
				return $this->errorText("Domain cannot be empty.  <br>You provided: <br> " . print_r2($params));
			}
		}

		if ($op == 'adddb') {
			# thanks to www.bikcmp.com for bug-report..
			if ($params['dbusername'] == 'root')
				return $this->errorText("'root' username is forbidden. Use another username!");
		}

		if ($op == 'addemail') {
			if (trim($params['mailusername']) == '') {
				return $this->errorText("Mail username cannot be empty.  <br>You provided: <br> " . print_r2($params));
			}
		}

		if ($op == 'addpaneluser') {
			if (!$this->hasValueOrZero($params['email'])) {
				return $this->errorText("Email address is required!");
			} else {
				if (!inputValid($params['email'], "email_address")) {
					return $this->errorText("Please use a valid email address!");
				}
			}
		}


		return True;
	}

	function addDomainEasy()
	{
		global $domainname, $email, $password, $_insert;
		$this->getVariable(array("domainname", "email", 'password', '_insert'));
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomain", array()))
				return false;

			$inputparams = array(
				array('domainname', 'lefttext' => 'Domain Name:'),
				array('password', 'password_with_generate', 'default' => '1234', 'lefttext' => 'Password'),
				array('email', 'lefttext' => 'Email of Domain Owner', 'default' => $this->miscconfig['adminemail']),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);


			$this->output .= "<b>Enter info below, all usernames will be domainname, all passwords will be same:</b> <br>Default values are: quota(mb):50, ul/dl bw: 200KB/s<br>"
				. "<br>Normally, do not write www. only yourdom.com forexample: <br>"
				. inputform5($inputparams);


		} else {
			$panelusername = $ftpusername = $domainname;
			$paneluserpass = $ftppassword = $password;

			$success = $success && $this->addDomainDirect($domainname, $panelusername, $paneluserpass, $ftpusername, $ftppassword, $this->status_active, $email);
			$success = $success && $this->setselecteddomain($domainname);
			$this->ok_err_text($success, 'Successfully added domain!', 'Failed to add domain!');
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}


	function addDomainEasyip()
	{
		global $domainname, $email, $password, $ip, $_insert;
		$this->getVariable(array("domainname", "email", 'password', 'ip', '_insert'));
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomain", array()))
				return false;
			$ips = $this->query("select ip from servers where accessip='localhost'");
			#$this->print_r2($ips);
			$ips2 = array();
			foreach ($ips as $i)
				$ips2[$i['ip']] = $i['ip'];

			$inputparams = array(
				array('ip', 'select', 'lefttext' => 'IP to be Assigned', 'secenekler' => $ips2, 'righttext' => "<a href='?op=listservers'>List/Add Servers/ IP's</a>"),
				array('domainname', 'lefttext' => 'Domain Name'),
				array('password', 'password_with_generate', 'default' => '1234', 'lefttext' => 'Password'),
				array('email', 'lefttext' => 'Email of Domain Owner', 'default' => $this->miscconfig['adminemail']),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			#$this->print_r2($inputparams);

			$this->output .= "<b>Enter info below, all usernames will be domainname, all passwords will be same:</b> <br>Default values are: quota(mb):50, ul/dl bw: 200KB/s<br>"
				. "<br>Normally, do not write www. only yourdom.com forexample: <br>"
				. inputform5($inputparams);


		} else {
			$panelusername = $ftpusername = $domainname;
			$paneluserpass = $ftppassword = $password;

			$success = $success && $this->addDomainDirect($domainname, $panelusername, $paneluserpass, $ftpusername, $ftppassword, $this->status_active, $email, 0, $ip);
			$success = $success && $this->setselecteddomain($domainname);
			$this->ok_err_text($success, 'Successfully added domain.', 'Failed to add domain.');
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}

	function setFTPPathInSession()
	{
		$selfftp = $this->getField('ftpaccounts', 'ftpusername', "panelusername='$this->activeuser' and type='default'");
		if ($selfftp != '') {
			$_SESSION['FTP_HOME_PATH'] = $this->conf['vhosts'] . '/' . $selfftp;
		}
	}

	function getSelfFtpAccount($returnto1 = '')
	{
		global $ftpusername, $ftppassword, $returnto, $_insert;
		$this->getVariable(array('ftpusername', 'ftppassword', 'returnto', '_insert'));

		$selfftp = $this->getField('ftpaccounts', 'ftpusername', "panelusername='$this->activeuser' and type='default'");
		if ($selfftp <> '')
			return $selfftp;

		if ($_insert) {
			if (!$this->afterInputControls('addftpuser', array('ftpusername' => $ftpusername)))
				return false;
			$this->output .= 'Will add here';
			$success = $this->addFtpUserDirect($this->activeuser, $ftpusername, $ftppassword, $this->conf['vhosts'] . '/' . $ftpusername, $upload = 100, $download = 100, $quota = 1000, $domainname, 'default');
			$this->setFTPPathInSession();
			$this->redirecttourl('?op=' . $returnto);
		} else {
			if (!$this->beforeInputControls('addftpuser'))
				return false;

			$inputparams = array(
				array('ftpusername', 'lefttext' => 'FTP Username:'),
				array('ftppassword', 'password_with_generate', 'lefttext' => 'FTP Password:'),
				array('returnto', 'hidden', 'default' => $returnto1),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);


			$this->output .= "A default FTP account does not yet exist for your EHCP username.&nbsp; Let's set one up now:<br>Specify your desired default FTP account information:"
				. inputform5($inputparams);

			$this->showexit();
		}
		return True;
	}

	function getFtpAccountLoginByUsername($panelusername)
	{
		$ftpUsernameForDomain = $this->getField('ftpaccounts', 'ftpusername', "panelusername='" . $this->escape($panelusername) . "' and type='default'");
		if ($ftpUsernameForDomain != '') {
			return $ftpUsernameForDomain;
		}

		return false;
	}


	function addDomainToThisPaneluser()
	{ # add domain to this paneluser and existing ftp space
		global $domainname, $_insert;
		$this->getVariable(array("domainname", "_insert"));
		$selfftp = $this->getSelfFtpAccount($returnto = __FUNCTION__); # ftp account for this panel user is with type field=default in ftpaccounts table
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomaintothispaneluser", array()))
				return false;
			$inputparams = array(
				array('domainname', 'lefttext' => 'Domain Name:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "<br>Add an additional domain to your account which will be accessible via your default FTP account:<br>Do not include domain prefixes such as \"http://\" or \"www.\":<br>" . inputform5($inputparams);
		} else {
			// Better domain validation
			$domainname = $this->adjustDomainname($domainname);
			$success = inputValid($domainname, 'domainname');

			$success = $success && $this->addDomainDirectToThisPaneluser($domainname, $selfftp);
			$success = $success && $this->setselecteddomain($domainname);
			$this->ok_err_text($success, 'Successfully added domain to your account.', 'Failed to add domain to your account. Please make sure the domain name is valid and includes a top level domain ending such as .com, .net, etc.');
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}

	function strNewlineToArray($str)
	{
		# this is written because, input text in an inputbox in web browser, sometime has \n as newline char, sometime  \r\n
		# used in especially bulkaddDomain

		/*
			if($a=strstr($str,'\r\n')===false)
					$ret=explode('\n',$str);
			else
					$ret=explode('\r\n',$str);
		*/
		# testing: $this->output.='<pre>'.$str.'....\n___\r___</pre><hr>';
		# $str=preg_replace('/\r\n|\r/', '\n', $str); # this does not work.
		$str = str_replace(array('\n\r', '\r\n', '\r'), array('\n', '\n', '\n'), $str);
		# $this->output.="<pre>$str</pre><hr>";

		$ret = explode('\n', $str); # bugreport by razvan , should work for all browsers. https://bugs.launchpad.net/ehcp/+bug/524551
		return $ret;
	}

	function bulkAddEmail()
	{
		/*
		thanks avra911: http://www.ehcp.net/?q=node/577#comment-1414

		email1@domain1.com:password1
		email2@domain2.com:password2

		*/
		global $emails;
		$this->getVariable(array('emails'));

		if (!$emails) {
			$this->output .= "Enter emails line by line, like:<br>
email1@domain1.com:password1<br>
email2@domain2.com:password2<br>
<br>
<form method=post><textarea rows=30 cols=40 name=emails></textarea><br><input type=hidden name=op value=" . __FUNCTION__ . "><input type=submit></form>";
			$this->showSimilarFunctions('email');
		} else {
			$this->output .= "emailler eklenecek:";
			$emails = $this->strNewlineToArray($emails);
			$this->output .= print_r2($emails);
			foreach ($emails as $line) {
				$line = trim($line);
				if ($line == '')
					continue;
				$info = explode(":", $line); # get email part
				$info2 = explode("@", $info[0]);
				$mailusername = $info2[0];
				$domainname = $info2[1];
				$password = $info[1]; # get pass
				$this->addEmailDirect($mailusername, $domainname, $password, $quota = 10, $autoreplysubject, $autoreplymessage);
			}
		}

	}

	function bulkaddDomain()
	{
		# gets many domains in one step, adds them, then call syncdns and syncdomains once
		global $domainler;
		$this->getVariable(array('domainler'));
		$selfftp = $this->getSelfFtpAccount($returnto = __FUNCTION__);
		$success = True;

		if (!$domainler) {
			$this->output .= "Enter domain names below one by one, <br>don't enter www. at start of domains,<br> All domains will be setup under your ftp directory:<br> <form method=post><textarea rows=30 cols=40 name=domainler></textarea><br><input type=hidden name=op value=bulkadddomain><input type=submit></form>";
		} else {
			$this->output .= "Adding domains: <br>";
			$domains = $this->strNewlineToArray($domainler);
			$newdomains = array();
			$errors = 0;

			foreach ($domains as $dom) {
				$dom = trim($dom);
				$dom = str_replace(array("\\", "www.", "http://", "https://"), array('', '', '', ''), $dom); # replace accidental www.'s

				if ($dom == "")
					continue;
				if (!in_array($dom, $newdomains)) {
					$newdomains[] = $dom; # eliminate duplicate domainnames..

					$this->output .= "Checking domain: $dom<br>";

					if (
						!$this->afterInputControls(
							"adddomaintothispaneluser",
							array(
								"domainname" => $dom,
							)
						)
					)
						return false;
				}
			}

			foreach ($newdomains as $dom) {
				$this->output .= "Setting up domain: $dom<br>";
				if ($success == false) {
					$errors++;
				}
				$this->addDomainDirectToThisPaneluser($dom, $selfftp, false);
			}


			# sync all new domains...
			#$success=$success && $this->addDaemonOp("syncdomains",'xx','','','sync domains'); # this is not needed anymore, since each domain is synced itself, in addDomainDirectToThisPaneluser function above. 
			$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');

			if ($errors > 0) {
				$success = false;
			}

			$this->ok_err_text($success, "Successfully added multiple domains in bulk.", "Failed to add multiple domains in bulk.");
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}

	function bulkDeleteDomain()
	{
		# this is not put in gui yet, for security
		global $domainler;
		$this->getVariable(array('domainler'));

		$success = True;

		if (!$domainler) {
			$this->output .= "Enter domain names below one by one, <br>don't enter www. at start of domains,<br> All domains will be deleted automatically. BE CAREFUL !!! :<br> <form method=post><textarea rows=30 cols=40 name=domainler></textarea><br><input type=hidden name=op value=bulkdeletedomain><input type=submit></form>";
		} else {
			$this->output .= "Deleting domains: <br>";
			$domains = $this->strNewlineToArray($domainler);

			$newdomains = array();

			foreach ($domains as $dom) {
				$dom = trim($dom);
				$dom = str_replace("\\", '', $dom);

				if ($dom == "")
					continue;
				if (!in_array($dom, $newdomains) and $this->isuserpermited('deletedomain', $dom)) {
					$newdomains[] = $dom; # eliminate duplicate domainnames.. and check if deletable,
				}
			}

			foreach ($newdomains as $dom) {
				$this->output .= "Deleting domain: $dom<br>";
				$success = $success && $this->deleteDomainDirect($dom, false);
			}


			# sync all domains...
			#$this->addDaemonOp("syncdomains",'xx','','','sync domains');  # this is not needed anymore, since each domain is synced itself. 
			$this->addDaemonOp("syncdns", '', '', '', 'sync dns');

			return $this->ok_err_text($success, "Selected domains were deleted.", "Failed to delete selected domains.");

		}
	}

	function noEmpty($values)
	{
		if (!is_array($values))
			$values = array($values); # this way, function may accept both array,and non-array
		foreach ($values as $val)
			if ($val == '')
				$this->errorTextExit("Empty value not allowed");
	}


	function addDnsOnlyDomainWithPaneluser()
	{
		global $domainname, $serverip, $_insert, $password, $email;
		$this->getVariable(array("domainname", "_insert", 'serverip', 'password', 'email'));
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomaintothispaneluser", array()))
				return false;
			$inputparams = array(
				array('domainname', 'lefttext' => 'Domain Name:'),
				array('password', 'password_with_generate', 'lefttext' => 'Password:'),
				array('email', 'lefttext' => 'Email Address:'),
				array('serverip', 'lefttext' => 'Server IP Address the Domain is Hosted On:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br>Add a DNS only domain.&nbsp; This will create DNS records for a domain only along with creating an account to access the panel.<br>" . inputform5($inputparams);
		} else {

			if (
				!$this->afterInputControls(
					"adddomaintothispaneluser",
					array(
						"domainname" => $domainname,
					)
				)
			)
				return false;

			$domainname = $this->adjustDomainname($domainname);
			$success = $this->is_valid_domain_name($domainname);
			if ($success) {
				$paneluserinfo = $this->getPanelUserInfo();
				$panelusername = $ftpusername = $domainname;
				$paneluserpass = $ftppassword = $password;

				$sql = "insert into " . $this->conf['domainstable']['tablename'] . " (reseller,panelusername,domainname,homedir,status,serverip) values ('" . $this->activeuser . "','$panelusername','$domainname','','" . $this->status_active . "','$serverip')";
				$success = $success && $this->executeQuery($sql);
				$success = $success && $this->addPanelUserDirect($panelusername, $paneluserpass, 1, 0, 0, 0, 0, $quota, 0, 10, '', $email, $this->status_active);
				$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
				$this->ok_err_text($success, 'DNS only domain was successfully added.', 'Failed to add domain (' . __FUNCTION__ . ')');
			} else {
				$this->ok_err_text($success, 'DNS only domain was successfully added.', 'Domain ' . $domainname . ' is invalid!');
			}
		}
		$this->showSimilarFunctions('domain');
		return $success;

	}

	function isValidIP($ip)
	{ # by earnolmartin@gmail.com
		if (!empty($ip)) {
			if (preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $ip)) {
				return true;
			}
		}
		return false;
	}

	function isValidDomain($domain, $checkIfExistsInPanel = true)
	{
		$parts = explode(".", $domain);
		if (is_array($parts) && count($parts) != 2) {
			return false;
		}

		$origDomain = $domain;
		$domain = removeInvalidChars($domain, "domainname");
		if ($domain !== $origDomain) {
			return false;
		}

		if ($checkIfExistsInPanel) {
			$result = $this->getDomainInfo($domain, $checkIfExistsInPanel);
			if ($result) { // Domain info returns false if the domain does not exist... we don't want it to exist in the panel.
				return false;
			}
		}

		return true;
	}

	function isValidSubDomain($entry, $checkIfExistsInPanel = true)
	{
		$parts = explode(".", $entry);
		if (is_array($parts) && count($parts) < 3) {
			return false;
		}

		$origEntry = $entry;
		$entry = removeInvalidChars($entry, "domainname");
		if ($entry !== $origEntry) {
			return false;
		}

		if ($checkIfExistsInPanel) {
			$domIndex = count($parts) - 2;
			$domainname = $parts[$domIndex] . "." . $parts[count($parts) - 1];
			for ($i = 0; $i < $domIndex; $i++) {
				if ($i == 0) {
					$subdomain .= $parts[$i];
				} else {
					$subdomain .= "." . $parts[$i];
				}
			}
			$result = $this->getSubDomains("domainname ='" . $domainname . "' AND subdomain = '" . $subdomain . "'");
			if (count($result) > 0 || $result) { // Should return nothing if the subdomain doesn't already exist in the panel
				return false; // Subdomain exists
			}
		}

		return true;
	}

	function getMasterIP($domainname)
	{ # by earnolmartin@gmail.com
		$res = $this->query("select dnsmaster from domains where domainname = '$domainname'");
		return $res[0]['dnsmaster'];
	}

	function addSlaveDNS()
	{ # coded by earnolmartin@gmail.com, modified little by ehcpdeveloper
		global $serverip, $_insert, $password, $dnsmaster, $email;
		$currentDNSMaster = "";
		$this->getVariable(array("_insert", 'dnsmaster'));
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname); # this ensures a domain selected. 
		$isAlreadySlave = "";
		$success = True;
		$errmsg = '';

		if ($this->getIsSlaveDomain($domainname)) {
			$currentDNSMaster = $this->getMasterIP($domainname);
			$isAlreadySlave = "<p style='color: red;'> $domainname is currently configured as a slave DNS domain.&nbsp; You can edit the master server IP address below.&nbsp; <a href='?op=removeslavedns'>Click here</a> to remove slave configuration from the domain.&nbsp; If you do not wish to change these settings, please go back.</p>";
		}

		if (!$_insert) {
			$inputparams = array(
				array('dnsmaster', 'input', 'lefttext' => 'Master Server IP:', 'default' => $currentDNSMaster),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= $isAlreadySlave . "<p><br>If this domain should download and use DNS records from another server, please enter the master server IP address below.<br>" . inputform5($inputparams);
		} else {
			if (!$this->isValidIP($dnsmaster))
				$this->errorTextExit("IP address $dnsmaster entered is invalid!");

			$sql = "update " . $this->conf['domainstable']['tablename'] . " set dnsmaster ='$dnsmaster' WHERE domainname = '$domainname'";
			$success = $success && $this->executeQuery($sql);
			$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
			if ($this->miscconfig['updatehostsfile'] != '' && $success) {
				$success = $this->addDaemonOp("updatehostsfile", '', '', '', 'update hostsfile');
			}

			# single function ok_err_text is enaugh at end of an operation.
			$this->ok_err_text($success, 'Domain has been configured as a DNS Slave', "Failed to change domain into slave! $errmsg (" . __FUNCTION__ . ')');
		}


		$this->showSimilarFunctions('domain');
		return $success;
	}

	function errArrayToStr($errors)
	{
		$errStr = '';

		foreach ($errors as $err) {
			$errStr .= $err . "<br>";
		}

		return $errStr;
	}

	function removeSlaveDNS()
	{ # coded by earnolmartin@gmail.com, modified by ehcpdeveloper
		global $domainname, $serverip, $_insert, $password, $email, $yes, $no;
		$this->getVariable(array("_insert", 'yes', 'no'));
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname); # this ensures a domain selected. 

		$success = True;

		if (!($this->getIsSlaveDomain($domainname)))
			$this->errorTextExit("Configuration cannot be changed! The currently selected domain is already NOT configured as a slave!");

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Yes')
			);

			$this->output .= "<p><br>Are you sure you want to remove the slave status from $domainname ?" . inputform5($inputparams);
		} else {
			$sql = "update " . $this->conf['domainstable']['tablename'] . " set dnsmaster = NULL WHERE domainname = '$domainname'";
			$success = $success && $this->executeQuery($sql);
			$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
			if ($this->miscconfig['updatehostsfile'] != '' && $success) {
				$success = $this->addDaemonOp("updatehostsfile", '', '', '', 'update hostsfile');
			}
			$this->ok_err_text($success, "$domainname is no longer configured as a slave domain.", "$errmsg <br>No configuration changes have been made to the DNS type of your domain! (" . __FUNCTION__ . ')');
		}


		$this->showSimilarFunctions('domain');
		return $success;
	}

	function hasValueOrZero($var)
	{
		if (empty($var) && @$var != "0") {
			return false;
		} else {
			return true;
		}
	}

	function hasValueOrZeroAndIsNumeric($var)
	{
		if ((empty($var) || @!is_numeric($var)) && @$var != "0") {
			return false;
		} else {
			if ($var >= 0) {
				return true;
			}
			return false;
		}
	}

	function addHostingPlan()
	{
		global $_insert, $plan_name, $plan_master_reseller, $plan_max_panelusers, $plan_max_ftpusers, $plan_max_dbs, $plan_max_emails, $plan_max_domains, $plan_max_subdomains, $plan_quota;

		// Only admins and resellers can define hosting plans
		$this->requireReseller();

		$reseller = $this->activeuser;
		$userinfo = $this->getPanelUserInfo('', $reseller);
		$success = True;

		// All variables will already be escaped and posted when using this function
		$this->getVariable(array("_insert", 'plan_name', 'plan_master_reseller', 'plan_max_panelusers', 'plan_max_ftpusers', 'plan_max_dbs', 'plan_max_emails', 'plan_max_domains', 'plan_max_subdomains', 'plan_quota'));

		if (!$_insert) {
			$inputparams = array(
				array('plan_name', 'input', "lefttext" => "Plan Name:", 'default' => $plan_name),
				array('plan_master_reseller', 'checkbox', 'lefttext' => 'Master Reseller Account:', 'default' => '1'),
				array('plan_max_panelusers', 'input', 'lefttext' => 'Max Panel Users:', 'default' => '0'),
				array('plan_max_ftpusers', 'input', 'lefttext' => 'Max FTP Users:', 'default' => '5'),
				array('plan_max_dbs', 'input', 'lefttext' => 'Max MySQL Databases:', 'default' => '20'),
				array('plan_max_emails', 'input', 'lefttext' => 'Max Email Accounts:', 'default' => '5'),
				array('plan_max_domains', 'input', 'lefttext' => 'Max Domains:', 'default' => '5'),
				array('plan_max_subdomains', 'input', 'lefttext' => 'Max Sub Domains:', 'default' => '10'),
				array('plan_quota', 'input', 'lefttext' => 'Hard Disk Space Quota (in MB):', 'default' => '500'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "<p>Create Hosting Plan Templates:</p>" . inputform5($inputparams);
		} else {
			// OK, perform validation
			// Clear any errors that may exist
			if (isset($errors)) {
				unset($errors);
			}

			/* Validation Section */
			if (empty($plan_name)) {
				$errors[] = "You must provide a name for this hosting plan.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_max_panelusers)) {
				$errors[] = "Please define the maximum number of panel users a client is allowed to create for this hosting plan as an integer value.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_max_ftpusers)) {
				$errors[] = "Please define the maximum number of FTP users a client is allowed to create for this hosting plan as an integer value.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_max_dbs)) {
				$errors[] = "Please define the maximum number of MySQL databases a client is allowed to create for this hosting plan as an integer value.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_max_emails)) {
				$errors[] = "Please define the maximum number of Email accounts a client is allowed to create for this hosting plan as an integer value.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_max_domains)) {
				$errors[] = "Please define the maximum number of Domains a client is allowed to add for this hosting plan as an integer value.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_max_subdomains)) {
				$errors[] = "Please define the maximum number of Sub Domains a client is allowed to create for this hosting plan as an integer value.";
			}

			if (!$this->hasValueOrZeroAndIsNumeric($plan_quota)) {
				$errors[] = "Please define the hard disk space quota for this hosting plan.";
			}

			if (empty($plan_master_reseller)) {
				// Default to not a reseller if the checkbox isn't checked
				$plan_master_reseller = 0;
			}

			// Reseller validation handling to prevent the creation of plans
			if ($reseller != 'admin') {
				if ($plan_master_reseller == 1) {
					$plan_master_reseller = 0;
					$this->output .= "<p class=\"error\">Only the root server administrator can set the master reseller parameter on a hosting plan template.&nbsp; Your selection was ignored.</p>";
				}
			}

			if ($reseller != 'admin' && $userinfo['master_reseller'] != 1) {
				// Normal resellers cannot create other resellers
				if ($plan_max_panelusers != 0) {
					$plan_max_panelusers = 0;
					$this->output .= "<p class=\"error\">Only master reseller accounts can create other reseller accounts!&nbsp; The hosting plan template created will allow no-additional panel users.</p>";
				}
			}

			// Check and make sure any other existing hosting plans don't have the same name
			$SQL = "SELECT * FROM " . $this->conf['hosting_plans_table']['tablename'] . " WHERE name = '$plan_name' AND panelusername = '$this->activeuser'";
			$rs = $this->query($SQL);
			if (count($rs) > 0) {
				$errors[] = "A hosting plan has already been defined with the name of \"$plan_name\". Choose another name.";
			}

			// Output errors
			if (isset($errors) && is_array($errors)) {
				$errStr = $this->errArrayToStr($errors);
				unset($errors);
				$this->errorTextExit($errStr);
			}

			// Ok we passed validation... put it in db
			$SQL = "INSERT INTO " . $this->conf['hosting_plans_table']['tablename'] . " (name, master_reseller, max_panelusers, max_ftpusers, max_dbs, max_emails, max_domains, max_subdomains, quota, panelusername) VALUES ('$plan_name', '$plan_master_reseller', '$plan_max_panelusers', '$plan_max_ftpusers', '$plan_max_dbs', '$plan_max_emails', '$plan_max_domains', '$plan_max_subdomains', '$plan_quota', '$this->activeuser')";

			// Run Query
			$success = $this->executeQuery($SQL);

			// Return
			$this->ok_err_text($success, 'Saved hosting plan named "' . $plan_name . '" into the database.', 'Failed to saved hosting plan named "' . $plan_name . '"! (' . __FUNCTION__ . ')');
		}
		return $success;
	}

	function removeHostingPlan()
	{
		global $_insert, $hostingPlanIdsToDelete;

		// Only admins and resellers can define hosting plans
		$this->requireReseller();
		$success = True;

		$this->getVariable(array("_insert"));
		$hostingPlanIdsToDelete = $_POST['hostingPlanIdsToDelete'];
		$success = True;

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Remove Selected Hosting Plan Templates')
			);

			// Build table based on queries
			$SQL = "SELECT * FROM " . $this->conf['hosting_plans_table']['tablename'];

			// Admin should be able to see all hosting plans
			if (!$this->isadmin()) {
				$SQL .= " WHERE panelusername='" . $this->activeuser . "'";
			}

			$SQL .= " ORDER BY panelusername";

			// Run Query
			$rs = $this->query($SQL);

			if (count($rs) == 0) {
				$this->errorTextExit('No saved hosting plan templates exist in the database yet.');
			} else {
				$table = "<form method=\"post\" action=\"?op=" . __FUNCTION__ . "\"><table class='genericList'><tr><th style=\"width: 100px;\">Select</th><th style=\"width: 200px;\">Plan Name</th><th>Master Reseller</th><th>Max Panel Users</th><th>Max FTP Users</th><th>Max MySQL DBs</th><th>Max Email Accounts</th><th>Max Domains</th><th>Max Sub Domains</th><th>Quota (in MB)</th><th>Created By</th></tr>";
				foreach ($rs as $r) {
					if (empty($r["master_reseller"]) || $r["master_reseller"] == 0) {
						$MasterResellerPlan = "No";
					} else {
						$MasterResellerPlan = "Yes";
					}
					$table .= "<tr><td><input type=\"checkbox\" value=\"{$r['id']}\" name=\"hostingPlanIdsToDelete[]\" /></td><td>{$r['name']}</td><td>{$MasterResellerPlan}</td><td>{$r['max_panelusers']}</td><td>{$r['max_ftpusers']}</td><td>{$r['max_dbs']}</td><td>{$r['max_emails']}</td><td>{$r['max_domains']}</td><td>{$r['max_subdomains']}</td><td>{$r['quota']}</td><td>{$r['panelusername']}</td></tr>";
				}
				$table .= "</table><br><input type=\"submit\" value=\"Delete Selected Hosting Plan Templates\" name=\"_insert\"></form>";
			}

			$this->output .= "<br>List of Saved Hosting Plan Templates<br>" . $table;
		} else {
			if (isset($hostingPlanIdsToDelete) && is_array($hostingPlanIdsToDelete) && count($hostingPlanIdsToDelete) > 0) {
				foreach ($hostingPlanIdsToDelete as $toDelete) {
					// Secure the string
					$toDelete = $this->escape($toDelete);

					// Then actually delete the record
					$sql = "delete from " . $this->conf['hosting_plans_table']['tablename'] . " WHERE id = '$toDelete'";
					if (!$this->isadmin()) {
						$sql .= " AND panelusername = '$this->activeuser'";
					}
					$success = $success && $this->executeQuery($sql);
				}
			} else {
				$success = FALSE;
				$errmsg = "No existing hosting plans were selected for removal!";
			}

			$this->ok_err_text($success, "Selected hosting plans were deleted!", "$errmsg <br>Nothing deleted! (" . __FUNCTION__ . ')');
		}
		return $success;
	}

	function addPasswordProtectedDir()
	{ #coded by earnolmartin@gmail.com
		global $protected_dir, $_insert, $password, $username;

		// Domain must be chosen
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		$success = True;

		if (isset($domainname) && !empty($domainname)) {
			$this->getVariable(array("_insert", 'protected_dir', 'password', 'username'));
			$domainhome = $this->getField($this->conf['domainstable']['tablename'], "homedir", "domainname='$domainname'") . "/";

			$errmsg = '';

			if (!$_insert) {
				$inputparams = array(
					array('protected_dir', 'input', "lefttext" => "Directory: {$domainhome}httpdocs/", 'default' => $protected_dir),
					array('username', 'input', 'lefttext' => 'Username for Directory:', 'default' => $username),
					array('password', 'input', 'lefttext' => 'Password for Directory:', 'default' => $password),
					array('op', 'hidden', 'default' => __FUNCTION__)
				);

				$this->output .= "<br>Create Password Protected Directory:<p>Leave the protected directory field blank to password protect the entire domain.</p><br>" . inputform5($inputparams);
			} else {
				// Clear any errors that may exist
				if (isset($errors)) {
					unset($errors);
				}

				// Convert the directory to lowercase
				$protected_dir = strtolower($protected_dir);

				/* Validation Section */
				// Array of invalid protected directories
				$cantUseTheseDirs = array("phpmyadmin", "webmail", "roundcube");
				if (in_array($protected_dir, $cantUseTheseDirs)) {
					$errors[] = "You can't setup \"$protected_dir\" as a password protected directory as it is a system used directory!";
				}

				if (empty($password) || empty($username)) {
					$errors[] = "You must provide a valid username and password in order to proceed!";
				}

				if (!isset($protected_dir) || empty($protected_dir) || $protected_dir == "/") {
					$protected_dir = "";
				}

				// Check and make sure any existing password protected directories for this domain are not contained in the one they want to add  
				$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname = '$domainname'";
				$rs = $this->query($SQL);
				$dirToCheck = $domainhome . "httpdocs/" . $protected_dir;
				foreach ($rs as $row) {
					if (!empty($row["protected_dir"])) {
						$fullPathFromDB = $row["domainpath"] . "httpdocs/" . $row["protected_dir"] . "/";
					} else {
						$fullPathFromDB = $row["domainpath"] . "httpdocs/" . $row["protected_dir"];
					}
					$fullPathFromDBWithoutEndingSlash = $row["domainpath"] . "httpdocs/" . $row["protected_dir"];
					// If an entry exists in the database already pointing to 
					if (strpos($dirToCheck, $fullPathFromDB) !== false || $dirToCheck == $fullPathFromDBWithoutEndingSlash) {
						$errors[] = "An existing password protected directory preceeds the authority over the desired directory of \"$protected_dir\"!";
					}
				}

				// Strip out invalid characters
				$protected_dir = preg_replace('/[^A-Za-z0-9\/_-]/', '', strip_tags(trim($protected_dir)));

				// Remove preceeding forward slash
				if ($protected_dir != "" && $protected_dir[0] == "/") {
					$protected_dir = substr($protected_dir, 1);
				}

				// Remove proceeding forward slash
				if ($protected_dir != "" && $protected_dir[strlen($protected_dir) - 1] == "/") {
					$protected_dir = substr($protected_dir, 0, -1);
				}

				// Remove any invalid characters:
				$protected_dir = removeInvalidChars($protected_dir, "directory");

				// Output errors
				if (isset($errors) && is_array($errors)) {
					$errStr = $this->errArrayToStr($errors);
					unset($errors);
					$this->errorTextExit($errStr);
				}

				// Escape all variables
				$protected_dir = $this->escape($protected_dir);
				$username = $this->escape($username);
				$password = $this->escape($password);

				// If the password protection is for the entire domain, we need to delete any other password protected entries
				if (empty($protected_dir)) {

					// First select each record to delete, delete the htaccess file and httpauthentication file
					$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname='$domainname' and protected_dir IS NOT NULL;";
					$rs = $this->query($SQL);
					foreach ($rs as $row) {
						$authPath = $row["domainpath"] . $row["id"];
						$htaccessPath = $row["domainpath"] . "httpdocs/" . $row["protected_dir"] . "/.htaccess";
						$this->bashDelete($authPath);
						$this->bashDelete($htaccessPath);
					}

					// Delete them from the database
					$SQL = "DELETE FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname='$domainname' and protected_dir IS NOT NULL";
					$this->executeQuery($SQL);
				}

				// Ok we passed validation... put it in db
				$SQL = "INSERT INTO " . $this->conf['pwd_dirs_table']['tablename'] . " (domainname, domainpath, protected_dir, username, password) VALUES ('$domainname', '$domainhome', '$protected_dir', '$username', '$password')";

				// Run Query
				$this->executeQuery($SQL);

				$success = $success && $this->addDaemonOp("process_pwd_dirs", '', '', '', 'handle password protected directories');

				// Domains need to be synced since the password protected directory structure is stored in the actual template itself
				$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');

				$this->ok_err_text($success, 'Successfully password protected the directory of "' . $dirToCheck . '"!', "Failed to add password protected the directory of " . $dirToCheck . "! (" . __FUNCTION__ . ')');
			}
		}
		return $success;
	}

	function bashDelete($file, $recursive = false)
	{
		return $this->addDaemonOp('deletefilefromsystem', $file, ($recursive ? 1 : 0), '', 'Delete File(s)');
	}

	function deleteFileFromSystem($file, $recursive = false)
	{
		$this->requireCommandLine(__FUNCTION__);
		echo "Deleting file/folder " . $file . " from system... Using recursive call: " . (!$recursive ? "NO" : "YES") . "\n";
		if (file_exists($file)) {
			$comStr = "rm";
			if ($recursive) {
				$comStr .= " -rf";
			}
			$comStr .= " $file";
			passthru2_silent($comStr, true, true);
		}

		return true;
	}

	function runCommandInDaemon($command)
	{
		return $this->addDaemonOp('runsystemcommand', 'runsystemcommand', $command, '', 'Run System Command');
	}

	function runSystemCommand($command, $info = '')
	{
		$this->requireCommandLine(__FUNCTION__);
		echo "Running system command of \"" . $command . "\"" . "\n";
		if (isset($command) && !empty($command)) {
			passthru2($command, true, true);
		}
		return true;
	}

	function removePasswordProtectedDirByDomain($domainname)
	{
		$success = true;

		$sql = "delete from " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname='$domainname';";
		$success = $success && $this->executeQuery($sql);

		return $success;
	}

	function getNginxPasswordProtectedRootDirectoriesByDomain($domainname)
	{
		$conf = "";
		$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname = '$domainname' and (protected_dir IS NULL OR protected_dir='')";
		$rs = $this->query($SQL);
		if (count($rs) == 1) {
			foreach ($rs as $row) {
				$homedir = $row['domainpath'] . "httpdocs";
				$protected_dir = $row['protected_dir'];
				$passwdfile = $row['domainpath'] . $row['id'];
				$conf .= "auth_basic \"Restricted Area\";
						auth_basic_user_file $passwdfile;";
			}
		}
		return $conf;
	}

	function getApachePasswordProtectedRootDirectoriesByDomain($domainname)
	{
		$conf = "";
		$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname = '$domainname' and (protected_dir IS NULL OR protected_dir='')";
		$rs = $this->query($SQL);
		if (count($rs) == 1) {
			foreach ($rs as $row) {
				$homedir = $row['domainpath'] . "httpdocs";
				$homedirForTemplate = $row['domainpath'];
				$lastCharOfHomeDirForTemp = $homedirForTemplate[strlen($homedirForTemplate) - 1];
				if ($lastCharOfHomeDirForTemp == "/") {
					$homedirForTemplate = substr($homedirForTemplate, 0, -1);
				}
				$protected_dir = $homedir . "/" . $row['protected_dir'];
				$passwdfile = $row['domainpath'] . $row['id'];

				$customGlobalTemplateForPWDirs = $this->getGlobalPasswordProtectedDirectoryTemplate();

				if (!empty($customGlobalTemplateForPWDirs)) {
					$customGlobalTemplateForPWDirs = str_replace("{protected_directory_path}", $protected_dir, $customGlobalTemplateForPWDirs);
					$customGlobalTemplateForPWDirs = str_replace("{protected_directory_credentials_file}", $passwdfile, $customGlobalTemplateForPWDirs);
					$customGlobalTemplateForPWDirs = str_replace("{homedir}", $homedirForTemplate, $customGlobalTemplateForPWDirs);
					$conf .= $customGlobalTemplateForPWDirs . "\n";
				} else {
					$fromTemplate = file_get_contents($this->ehcpdir . "/etc/generic_apache_templates/password_protected_directory.conf");
					$fromTemplate = str_replace("{protected_directory_path}", $protected_dir, $fromTemplate);
					$fromTemplate = str_replace("{protected_directory_credentials_file}", $passwdfile, $fromTemplate);
					$fromTemplate = str_replace("{homedir}", $homedirForTemplate, $fromTemplate);
					$conf .= $fromTemplate . "\n";
				}
			}
		}
		return $conf;
	}

	function getRedirectDomain($domainname)
	{
		$SQL = "SELECT redirect_to FROM " . $this->conf['domainstable']['tablename'] . " WHERE domainname = '$domainname'";
		$rs = $this->query($SQL);
		if (count($rs) == 1) {
			return $rs[0]['redirect_to'];
		}
		return "";
	}

	function setRedirectDomain($domainname, $redirect_domain)
	{
		// Update redirect location for this domain
		$SQL = "UPDATE " . $this->conf['domainstable']['tablename'] . " SET redirect_to='" . $redirect_domain . "', apachetemplate = NULL, apache2template = NULL, nginxtemplate = NULL WHERE domainname ='" . $domainname . "'";

		if (!$this->isadmin()) {
			$SQL .= " AND panelusername='" . $this->activeuser . "'";
		}

		// Run Query
		return $this->executeQuery($SQL);
	}

	function removeRedirectDomain($domainname)
	{
		// Remove the redirect for this domain
		$SQL = "UPDATE " . $this->conf['domainstable']['tablename'] . " SET redirect_to = NULL WHERE domainname ='" . $domainname . "'";

		if (!$this->isadmin()) {
			$SQL .= " AND panelusername='" . $this->activeuser . "'";
		}

		// Run Query
		return $this->executeQuery($SQL);
	}

	function getSSLSettingForDomain($domainname)
	{
		$SQL = "SELECT ssl_cert, ssl_cert_key, ssl_cert_chain, ssl_wild_card, ssl_use_letsenc, ssl_redirect_https, ssl_lets_enc_additional_hosts FROM " . $this->conf['domainstable']['tablename'] . " WHERE domainname = '$domainname'";
		$rs = $this->query($SQL);
		if (count($rs) == 1) {
			$arr["cert"] = $rs[0]['ssl_cert'];
			$arr["key"] = $rs[0]['ssl_cert_key'];
			$arr["chain"] = $rs[0]['ssl_cert_chain'];
			$arr["wildcard"] = $rs[0]['ssl_wild_card'];
			$arr["letsenc"] = $rs[0]['ssl_use_letsenc'];
			$arr["redir_https"] = $rs[0]['ssl_redirect_https'];
			$arr["lets_enc_additional_hosts"] = $rs[0]['ssl_lets_enc_additional_hosts'];

			// Set a status variable
			if (!empty($arr["cert"])) {
				$arr["ssl_status"] = "<span class='success'>Domain " . $domainname . " is currently using a custom SSL certificate.</span>";
			} else if (empty($arr["cert"]) && $arr["letsenc"]) {
				$arr["ssl_status"] = "<span class='success'>Domain " . $domainname . " is currently using Let's Encrypt free SSL certificates.</span>";
			} else {
				$arr["ssl_status"] = "<span class='error'>Domain " . $domainname . " is using the server default SSL certificate for HTTPS which will cause browser warnings.</span>";
			}

			return $arr;
		}
		return "";
	}

	function setSSLSettingForDomain($domainname, $sslInfo)
	{
		if (is_numeric($sslInfo["wildcard"])) {
			// Update redirect location for this domain	
			$SQL = "UPDATE " . $this->conf['domainstable']['tablename'] . " SET ssl_cert='" . $sslInfo["cert"] . "', ssl_cert_key='" . $sslInfo["key"] . "', ssl_cert_chain='" . $sslInfo["chain"] . "', ssl_wild_card = " . $sslInfo["wildcard"] . ", ssl_use_letsenc = 0, ssl_redirect_https = " . $sslInfo["redir_https"] . " WHERE domainname ='" . $domainname . "'";

			if (!$this->isadmin()) {
				$SQL .= " AND panelusername='" . $this->activeuser . "'";
			}

			// Run Query
			return $this->executeQuery($SQL);
		}
		return false;
	}

	function setLetsEncryptForDomain($domainname, $sslInfo)
	{
		$success = true;
		$SQL = "UPDATE " . $this->conf['domainstable']['tablename'] . " SET ssl_cert = NULL, ssl_cert_key = NULL, ssl_cert_chain = NULL, ssl_wild_card = 0, ssl_use_letsenc = 1, ssl_redirect_https = " . $sslInfo["redir_https"];

		if (array_key_exists('lets_enc_additional_hosts', $sslInfo)) {
			$SQL .= ", ssl_lets_enc_additional_hosts = '" . $sslInfo["lets_enc_additional_hosts"] . "'";
		}

		$SQL .= " WHERE domainname ='" . $domainname . "'";
		if (!$this->isadmin()) {
			$SQL .= " AND panelusername='" . $this->activeuser . "'";
		}
		$success = $this->executeQuery($SQL);
		return $success;
	}

	function removeSSLSettingForDomain($domainname, $removeSSLLetsEnc = true)
	{
		$success = true;
		// Remove the redirect for this domain
		$SQL = "UPDATE " . $this->conf['domainstable']['tablename'] . " SET ssl_cert = NULL, ssl_cert_key = NULL, ssl_cert_chain = NULL, ssl_wild_card = 0, ssl_redirect_https = 0";

		if ($removeSSLLetsEnc) {
			$SQL .= ", ssl_use_letsenc = 0, ssl_lets_enc_additional_hosts = ''";
		}

		$SQL .= " WHERE domainname ='" . $domainname . "'";

		if (!$this->isadmin()) {
			$SQL .= " AND panelusername='" . $this->activeuser . "'";
		}

		$success = $this->executeQuery($SQL);

		if ($success) {
			$this->deleteSSLCustomKeys($domainname);
			$this->removeLetsEncryptCertificates(array($domainname, 'www.' . $domainname));
		}

		return $success;
	}

	function deleteSSLCustomKeys($domainname)
	{

		$domaininfo = $this->getDomainInfo($domainname);
		$homedir = $domaininfo['homedir'];
		$pathToCertFile = $homedir . "/phptmpdir/server.crt";
		$pathToCertKeyFile = $homedir . "/phptmpdir/server.key";
		$pathToCertChainFile = $homedir . "/phptmpdir/chain.crt";
		$pathToMixedFile = $homedir . "/phptmpdir/mixed.crt";

		$this->bashDelete($pathToCertFile);
		$this->bashDelete($pathToCertKeyFile);
		$this->bashDelete($pathToCertChainFile);
		$this->bashDelete($pathToMixedFile);
	}

	function getNginxPasswordProtectedSubDirectoriesByDomain($domainname)
	{
		$conf = "";
		$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname = '$domainname' and protected_dir IS NOT NULL and protected_dir != ''";
		$rs = $this->query($SQL);
		foreach ($rs as $row) {
			$homedir = $row['domainpath'] . "httpdocs";
			$homedirForTemplate = $row['domainpath'];
			$lastCharOfHomeDirForTemp = $homedirForTemplate[strlen($homedirForTemplate) - 1];
			if ($lastCharOfHomeDirForTemp == "/") {
				$homedirForTemplate = substr($homedirForTemplate, 0, -1);
			}

			$protected_dir = $row['protected_dir'];
			$passwdfile = $row['domainpath'] . $row['id'];

			$customGlobalTemplateForPWDirs = $this->getGlobalPasswordProtectedDirectoryTemplate();

			if (!empty($customGlobalTemplateForPWDirs)) {
				$customGlobalTemplateForPWDirs = str_replace("{protected_directory_path}", $protected_dir, $customGlobalTemplateForPWDirs);
				$customGlobalTemplateForPWDirs = str_replace("{protected_directory_credentials_file}", $passwdfile, $customGlobalTemplateForPWDirs);
				$customGlobalTemplateForPWDirs = str_replace("{homedir}", $homedirForTemplate, $customGlobalTemplateForPWDirs);
				$conf .= $customGlobalTemplateForPWDirs . "\n";
			} else {
				$fromTemplate = file_get_contents($this->ehcpdir . "/etc/generic_nginx_templates/password_protected_directory.conf");
				$fromTemplate = str_replace("{protected_directory_path}", $protected_dir, $fromTemplate);
				$fromTemplate = str_replace("{protected_directory_credentials_file}", $passwdfile, $fromTemplate);
				$fromTemplate = str_replace("{homedir}", $homedirForTemplate, $fromTemplate);
				$conf .= $fromTemplate . "\n";
			}
		}
		return $conf;
	}

	function getApachePasswordProtectedSubDirectoriesByDomain($domainname)
	{
		$conf = "";
		$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname = '$domainname' and protected_dir IS NOT NULL and protected_dir != ''";
		$rs = $this->query($SQL);
		foreach ($rs as $row) {
			$homedir = $row['domainpath'] . "httpdocs";
			$homedirForTemplate = $row['domainpath'];
			$lastCharOfHomeDirForTemp = $homedirForTemplate[strlen($homedirForTemplate) - 1];
			if ($lastCharOfHomeDirForTemp == "/") {
				$homedirForTemplate = substr($homedirForTemplate, 0, -1);
			}
			$protected_dir = $homedir . "/" . $row['protected_dir'];
			$passwdfile = $row['domainpath'] . $row['id'];

			$customGlobalTemplateForPWDirs = $this->getGlobalPasswordProtectedDirectoryTemplate();

			if (!empty($customGlobalTemplateForPWDirs)) {
				$customGlobalTemplateForPWDirs = str_replace("{protected_directory_path}", $protected_dir, $customGlobalTemplateForPWDirs);
				$customGlobalTemplateForPWDirs = str_replace("{protected_directory_credentials_file}", $passwdfile, $customGlobalTemplateForPWDirs);
				$customGlobalTemplateForPWDirs = str_replace("{homedir}", $homedirForTemplate, $customGlobalTemplateForPWDirs);
				$conf .= $customGlobalTemplateForPWDirs . "\n";
			} else {
				$fromTemplate = file_get_contents($this->ehcpdir . "/etc/generic_apache_templates/password_protected_directory.conf");
				$fromTemplate = str_replace("{protected_directory_path}", $protected_dir, $fromTemplate);
				$fromTemplate = str_replace("{protected_directory_credentials_file}", $passwdfile, $fromTemplate);
				$fromTemplate = str_replace("{homedir}", $homedirForTemplate, $fromTemplate);
				$conf .= $fromTemplate . "\n";
			}
		}
		return $conf;
	}

	function rmPasswordProtectedDIR()
	{
		global $_insert, $pwdDirectoryIdsToDelete;

		// Domain must be chosen
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		$this->getVariable(array("_insert"));
		$pwdDirectoryIdsToDelete = $_POST['pwdDirsToDelete'];
		$success = True;

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Remove Selected Password Protected Directories')
			);

			// Build table based on queries
			$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE domainname='$domainname'";

			// Run Query
			$rs = $this->query($SQL);

			if (count($rs) == 0) {
				$this->errorTextExit('Currently, no password protected directories exist for the domain of "' . $domainname . '"!');
			} else {
				$table = "<form method=\"post\" action=\"?op=" . __FUNCTION__ . "\"><table class='genericList'><tr><th style=\"width: 100px;\">Select</th><th style=\"width: 350px;\">Password Protected Directory</th><th style=\"width: 150px;\">Directory Login</th><th style=\"width: 150px;\">Directory Password</th></tr>";
				foreach ($rs as $r) {
					$realPWDDir = $r['domainpath'] . "httpdocs/" . $r['protected_dir'];
					$table .= "<tr><td><input type=\"checkbox\" value=\"{$r['id']}\" name=\"pwdDirsToDelete[]\" /></td><td>{$realPWDDir}</td><td>{$r['username']}</td><td>{$r['password']}</td></tr>";
				}
				$table .= "</table><br><input type=\"submit\" value=\"Delete Selected Password Protected Directories\" name=\"_insert\"></form>";
			}

			$this->output .= "<br>List of Password Protected Directories<br>" . $table;
		} else {
			if (isset($pwdDirectoryIdsToDelete) && is_array($pwdDirectoryIdsToDelete) && count($pwdDirectoryIdsToDelete) > 0) {
				foreach ($pwdDirectoryIdsToDelete as $toDelete) {
					// Secure the string
					$toDelete = $this->escape($toDelete);

					// First select each record to delete, delete the htaccess file and httpauthentication file
					$SQL = "SELECT * FROM " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE id = '$toDelete' and domainname='$domainname';";
					$rs = $this->query($SQL);

					$authPath = $rs[0]["domainpath"] . $rs[0]["id"];
					if (!empty($rs[0]["protected_dir"])) {
						$htaccessPath = $rs[0]["domainpath"] . "httpdocs/" . $rs[0]["protected_dir"] . "/.htaccess";
					} else {
						$htaccessPath = $rs[0]["domainpath"] . "httpdocs/.htaccess";
					}
					$this->bashDelete($authPath);

					// Password protection settings are no longer stored in the .htaccess files...
					// They are now stored in the template exclusively
					// $this->bashDelete($htaccessPath);

					// Then actually delete the record
					$sql = "delete from " . $this->conf['pwd_dirs_table']['tablename'] . " WHERE id = '$toDelete' AND domainname='$domainname';";
					$success = $success && $this->executeQuery($sql);
				}

				// Sync domains
				$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');

			} else {
				$success = FALSE;
				$errmsg = "No existing password protected directories were selected for removal!";
			}

			$this->ok_err_text($success, "Selected password protected directories were deleted!", "$errmsg <br>Nothing deleted! (" . __FUNCTION__ . ')');
		}
		return $success;
	}

	function addCustomFTP()
	{ # coded by earnolmartin@gmail.com
		global $serverip, $_insert, $password, $hpath, $ftplogin, $ftppass, $email;

		// Custom FTP accounts must be configured by an admin.
		$this->requireAdmin();

		$this->getVariable(array("_insert", 'ftplogin', 'ftppass', 'hpath'));

		$success = True;
		$errmsg = '';

		if (!$_insert) {
			$inputparams = array(
				array('ftplogin', 'input', 'lefttext' => 'FTP Login:', 'default' => $ftplogin),
				array('ftppass', 'input', 'lefttext' => 'FTP Password:', 'default' => $ftppass),
				array('hpath', 'input', 'lefttext' => 'FTP Home Directory:', 'default' => $hpath),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br>Create Custom FTP Account<br>" . inputform5($inputparams);
		} else {

			// Clear any errors that may exist
			if (isset($errors)) {
				unset($errors);
			}

			/*                         *
			 *  All Fields Have Value  *
			 *                         */

			if (empty($hpath)) {
				$errors[] = "Please enter a directory path as the user's home FTP directory!";
			}

			if (empty($ftplogin)) {
				$errors[] = "Please enter a username for this FTP account!";
			}

			if (empty($ftppass)) {
				$errors[] = "Please enter a password for this FTP account!";
			} else {
				if (strlen($ftppass) < 4) {
					$errors[] = "Password must be at least 4 characters long!";
				}
			}

			// Output errors

			if (isset($errors) && is_array($errors)) {
				$errStr = $this->errArrayToStr($errors);
				unset($errors);
				$this->errorTextExit($errStr);
			}

			/*                   *
			 *  Path validation  *
			 *                   */

			if (!preg_match('/^[^*?"<>|:]*$/i', $hpath)) {
				$errors[] = "You entered an invalid Linux path!";
			}

			// Remove tailing slash if exists
			if ($hpath[strlen($hpath) - 1] == '/') {
				$hpath = substr($hpath, 0, strlen($hpath) - 1);
			}

			if (substr_count($hpath, '/') < 2) {
				$errorCount++;
				$errors[] = "In order to prevent security risks, users cannot be granted access to the main directories in the root file system of the server.&nbsp; You must go down two directory levels!&nbsp; Example:  /games/user1!";
			}

			if (stripos($hpath, "/") === FALSE || stripos($hpath, "/") != 0) {
				$errorCount++;
				$errors[] = "You have not chosen a valid directory!";
			}

			$protectedPaths = array("/var/www/vhosts", "/var/www/new", "/var/www/php_sessions", "/var/www/webalizer", "/var/www/passivedomains");
			foreach ($protectedPaths as $pPath) {
				if ($hpath == $pPath || $hpath == $pPath . "/" || stripos($hpath, $pPath) != false) {
					$errorCount++;
					$errors[] = "You may not create custom FTP accounts with access to protected EHCP directories!";
				}
			}

			if (stripos($hpath, "\\")) {
				$errorCount++;
				$errors[] = "This is not a Windows machine... use the correct slash character for path...";
			}

			// Output errors

			if (isset($errors) && is_array($errors)) {
				$errStr = $this->errArrayToStr($errors);
				unset($errors);
				$this->errorTextExit($errStr);
			}

			// Security checks
			$ftp_password_db = $this->escape($ftppass);
			$ftp_username_db = $this->escape($ftplogin);
			$rDir = $this->escape($hpath);
			$SQL = "SELECT id FROM " . $this->conf['ftpuserstable']['tablename'] . " WHERE ftpusername = '$ftp_username_db'";

			// Run Query
			$rs = $this->query($SQL);

			if (count($rs) == 0) {
				$SQL = "INSERT INTO " . $this->conf['ftpuserstable']['tablename'] . " (ftpusername, password, homedir) VALUES ('$ftp_username_db', password('$ftp_password_db'), '$rDir')";

				// Run Query
				$this->executeQuery($SQL);

			} else {
				$this->errorTextExit("Another account is already using the login of " . $ftp_username_db . "! Please try another username!");
			}

			$success = $success && $this->addDaemonOp("syncftp", '', '', '', 'sync ftp');

			# single function ok_err_text is enaugh at end of an operation.
			$this->ok_err_text($success, 'Successfully added the custom FTP account with a login of ' . $ftp_username_db . '!', "Failed to add FTP account with a login of " . $ftp_username_db . "! (" . __FUNCTION__ . ')');
		}

		$this->showSimilarFunctions('ftp');
		return $success;
	}

	function removeCustomFTP()
	{ # coded by earnolmartin@gmail.com
		global $_insert, $loginsToDelete;

		// Custom FTP accounts must be configured by an admin.
		$this->requireAdmin();

		$this->getVariable(array("_insert"));
		$loginsToDelete = $_POST['loginsToDelete'];
		$success = True;

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Remove Selected FTP Accounts')
			);

			// Build table based on queries
			$SQL = "SELECT id, ftpusername, homedir FROM " . $this->conf['ftpuserstable']['tablename'] . " where homedir IS NOT NULL and status IS NULL";

			// Run Query
			$rs = $this->query($SQL);

			if (count($rs) == 0) {
				$this->errorTextExit('Currently, no custom FTP accounts exist!');
			} else {
				$table = "<form method=\"post\" action=\"?op=" . __FUNCTION__ . "\"><table class='genericList'><tr><th style=\"width: 100px;\">Select</th><th style=\"width: 200px;\">Username</th><th style=\"width: 200px;\">Home Directory</th></tr>";
				foreach ($rs as $r) {
					// Only show custom entries... do not allow to modify EHCP accounts.
					if (!empty($r['homedir'])) {
						$countNotNull++;
						$table .= "<tr><td><input type=\"checkbox\" value=\"{$r['id']}\" name=\"loginsToDelete[]\" /></td><td>{$r['ftpusername']}</td><td>{$r['homedir']}</td></tr>";
					}
				}
				$table .= "</table><br><input type=\"submit\" value=\"Delete Selected Accounts\" name=\"_insert\"></form>";
			}

			$this->output .= "<br>List of FTP Accounts<br>" . $table;
		} else {
			if (isset($loginsToDelete) && is_array($loginsToDelete) && count($loginsToDelete) > 0) {
				foreach ($loginsToDelete as $toDelete) {
					// Secure the string
					$toDelete = $this->escape($toDelete);
					$sql = "delete from " . $this->conf['ftpuserstable']['tablename'] . " WHERE id = '$toDelete'";
					$success = $success && $this->executeQuery($sql);
				}
			} else {
				$success = FALSE;
				$errmsg = "No custom FTP accounts were selected for removal!";
			}

			$success = $success && $this->addDaemonOp("syncftp", '', '', '', 'sync ftp');
			$this->ok_err_text($success, "Selected accounts were deleted!", "$errmsg <br>No custom FTP accounts were deleted! (" . __FUNCTION__ . ')');
		}


		$this->showSimilarFunctions('ftp');
		return $success;
	}

	/* Is PolicyD Installed */
	function isPolicyDInstalled()
	{
		if (file_exists($this->ehcpdir . "/policyd")) {
			$json["policyDInstalled"] = true;
		} else {
			$json["policyDInstalled"] = false;
		}
		header('Content-Type: application/json');
		die(json_encode($json));
	}

	/* Get Public Server Settings */
	function getPublicServerSettings()
	{
		$json["isadmin"] = $this->isadmin();
		$json["customhttp"] = $this->miscconfig['disableeditapachetemplate'] == "" || $this->isadmin() ? true : false;
		$json["customdns"] = $this->miscconfig['disableeditdnstemplate'] == "" || $this->isadmin() ? true : false;
		$json["adddomainsslcert"] = ($this->miscconfig['webservermode'] == 'ssl' || $this->miscconfig['webservermode'] == 'sslonly') && (!empty($this->miscconfig['allowcustomsslnonadmin']) || $this->isadmin()) ? true : false;
		$json["webservertype"] = $this->miscconfig['webservertype'];

		header('Content-Type: application/json');
		die(json_encode($json));
	}

	/* Global Web Templates */

	function manageGlobalWebTemplates()
	{
		global $_insert, $template_file, $template_contents, $saveTemplate, $clearTemplate, $webserver_type, $webserver_mode;

		$success = true;

		// Requires admin
		$this->requireAdmin();

		$this->getVariable(array("_insert", 'template_file', 'template_contents', 'saveTemplate', 'clearTemplate', 'webserver_type', 'webserver_mode'));

		if (!$_insert) {

			$optionsArray = array("apachetemplate" => "Domain Template", "subdomaintemplate" => "Subdomain Template", "enableddefault" => "Default Enabled Domain", "redirect" => "Redirect Template", "pwdir" => "Password Protected Directory", "mainwebserverconf" => "Webserver Main Config");
			$typeOptionsArray = array("ssl" => "ssl", "sslonly" => "sslonly", "nonssl" => "nonssl");
			$webOptionsArray = array("apache2" => "apache2", "nginx" => "nginx");

			$inputparams = array(
				array('template_file', 'select', 'lefttext' => 'Edit Template:', 'secenekler' => $optionsArray),
				array('webserver_type', 'select', 'lefttext' => 'Web Server:', 'secenekler' => $webOptionsArray, 'default' => $this->miscconfig['webservertype']),
				array('webserver_mode', 'select', 'lefttext' => 'Web SSL Mode:', 'secenekler' => $typeOptionsArray, 'default' => $this->miscconfig['webservermode']),
				array('template_contents', 'textarea', 'lefttext' => 'Template Contents:', 'cols' => 80, 'rows' => 30),
				array('saveTemplate', 'submit', 'default' => 'Save Template'),
				array('clearTemplate', 'submit', 'default' => 'Revert to Default'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br>Edit Global Website Templates<br>Currently Using Default Template: <span class='success usingDefaultTemplateYes' style='display: none; font-weight: bold;'>Yes</span><span class='error usingDefaultTemplateNo' style='display: none; font-weight: bold;'>No</span><br><div class='editTemplateArea'>" . inputform5($inputparams) . "</div>";
		} else {
			if ($clearTemplate) {
				$success = $this->revertTemplateBackToEHCPDefault($template_file, $webserver_type, $webserver_mode);

				// Sync all domains so they use the default EHCP template again
				$this->addDaemonOp('syncdomains', '', '', '', 'sync domains');

				$this->showSimilarFunctions('global_templates');

				return $this->ok_err_text($success, "Selected global web server template has been reverted to the EHCP default.", "Failed to revert selected global web server template back to EHCP default.");
			} else if ($saveTemplate) {
				$success = $this->saveGlobalWebserverTemplate($template_file, $webserver_type, $webserver_mode, $template_contents);

				// Sync all domains so tehy use the new global template
				$this->addDaemonOp('syncdomains', '', '', '', 'sync domains');

				$this->showSimilarFunctions('global_templates');

				return $this->ok_err_text($success, "Successfully saved selected global webserver template into the database.", "Failed to save global template.");
			}
		}
		return $success;
	}

	function getGlobalWebTemplate($template = false, $webserverMode = false, $webserverType = false)
	{
		// This is a JSON operation only...
		// Only should be used with an ajax call.

		// Requires ADMIN
		$this->requireAdmin();

		$json = array();
		if ($template === false) {
			if (isset($_REQUEST['template'])) {
				$template = $_REQUEST['template'];
			}
		}

		if ($webserverMode === false) {
			if (isset($_REQUEST['mode'])) {
				$webserverMode = $_REQUEST['mode'];
			} else {
				$webserverMode = $this->miscconfig['webservermode'];
			}
		}

		if ($webserverType === false) {
			if (isset($_REQUEST['server'])) {
				$webserverType = $_REQUEST['server'];
			} else {
				$webserverType = $this->miscconfig['webservertype'];
			}
		}


		if ($template !== false) {
			// Update redirect location for this domain
			$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='" . $this->escape($template) . "' AND template_webserver_type='" . $this->escape($webserverType) . "' AND template_ssl_type ='" . $this->escape($webserverMode) . "'";

			// Run Query
			$rs = $this->query($SQL);

			// Build our JSON object
			$json["template_file"] = $template;

			if (count($rs) == 0 || empty($rs[0]["template_value"])) {
				switch ($template) {
					case "apachetemplate":
						if ($webserverType == "nginx") {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/nginx_sslonly/apachetemplate.nginx");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/nginx_ssl/apachetemplate.nginx");
							} else {
								$json["template_contents"] = file_get_contents("etc/nginx_nonssl/apachetemplate.nginx");
							}
						} else {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/apache2_sslonly/fork/apachetemplate");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/apache2_ssl/fork/apachetemplate");
							} else {
								$json["template_contents"] = file_get_contents("etc/apache2/apachetemplate");
							}
						}
						break;
					case "subdomaintemplate":
						if ($webserverType == "nginx") {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/nginx_sslonly/apache_subdomain_template.nginx");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/nginx_ssl/apache_subdomain_template.nginx");
							} else {
								$json["template_contents"] = file_get_contents("etc/nginx_nonssl/apache_subdomain_template.nginx");
							}
						} else {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/apache2_sslonly/fork/apache_subdomain_template");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/apache2_ssl/fork/apache_subdomain_template");
							} else {
								$json["template_contents"] = file_get_contents("etc/apache2/apache_subdomain_template");
							}
						}
						break;
					case "enableddefault":
						if ($webserverType == "nginx") {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/nginx_sslonly/default.nginx");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/nginx_ssl/default.nginx");
							} else {
								$json["template_contents"] = file_get_contents("etc/nginx_nonssl/default.nginx");
							}
						} else {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/apache2_sslonly/fork/default");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/apache2_ssl/fork/default");
							} else {
								$json["template_contents"] = file_get_contents("etc/apache2/default");
							}
						}
						break;
					case "mainwebserverconf":
						if ($webserverType == "nginx") {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/nginx_sslonly/nginx.conf");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/nginx_ssl/nginx.conf");
							} else {
								$json["template_contents"] = file_get_contents("etc/nginx_nonssl/nginx.conf");
							}
						} else {
							// Need to read this file using exec since file_get_contents will fail
							exec("cat /etc/apache2/apache2.conf", $arr);
							if (is_array($arr) && count($arr) > 0) {
								$json["template_contents"] = implode("\n", $arr);
							} else {
								$json["error_reading_file"] = true;
							}
						}
						break;
					case "pwdir":
						if ($webserverType == "nginx") {
							$json["template_contents"] = file_get_contents("etc/generic_nginx_templates/password_protected_directory.conf");
						} else {
							$json["template_contents"] = file_get_contents("etc/generic_apache_templates/password_protected_directory.conf");
						}
						break;
					case "redirect":
						if ($webserverType == "nginx") {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/nginx_sslonly/redirect");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/nginx_ssl/redirect");
							} else {
								$json["template_contents"] = file_get_contents("etc/nginx_nonssl/redirect");
							}
						} else {
							if ($webserverMode == "sslonly") {
								$json["template_contents"] = file_get_contents("etc/apache2_sslonly/fork/redirect");
							} else if ($webserverMode == "ssl") {
								$json["template_contents"] = file_get_contents("etc/apache2_ssl/fork/redirect");
							} else {
								$json["template_contents"] = file_get_contents("etc/apache2/redirect");
							}
						}
						break;
				}
				$json["using_default"] = true;
			} else {
				$json["template_contents"] = $rs[0]["template_value"];
				$json["using_default"] = false;
			}
		}

		header('Content-Type: application/json');
		die(json_encode($json));
	}

	function getGlobalDomainTemplate()
	{
		$template = "";
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='apachetemplate' AND template_webserver_type='" . $this->miscconfig['webservertype'] . "' AND template_ssl_type='" . $this->miscconfig['webservermode'] . "'";

		// Run Query
		$rs = $this->query($SQL);

		if (count($rs) == 1) {
			$template = $rs[0]["template_value"];
		}

		return $template;
	}

	function getGlobalSubDomainTemplate()
	{
		$template = "";
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='subdomaintemplate' AND template_webserver_type='" . $this->miscconfig['webservertype'] . "' AND template_ssl_type='" . $this->miscconfig['webservermode'] . "'";

		// Run Query
		$rs = $this->query($SQL);

		if (count($rs) == 1) {
			$template = $rs[0]["template_value"];
		}

		return $template;
	}

	function getGlobalPasswordProtectedDirectoryTemplate()
	{
		$template = "";
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='pwdir' AND template_webserver_type='" . $this->miscconfig['webservertype'] . "' AND template_ssl_type='" . $this->miscconfig['webservermode'] . "'";

		// Run Query
		$rs = $this->query($SQL);

		if (count($rs) == 1) {
			$template = $rs[0]["template_value"];
		}

		return $template;
	}

	function getGlobalRedirectTemplate()
	{
		$template = "";
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='redirect' AND template_webserver_type='" . $this->miscconfig['webservertype'] . "' AND template_ssl_type='" . $this->miscconfig['webservermode'] . "'";

		// Run Query
		$rs = $this->query($SQL);

		if (count($rs) == 1) {
			$template = $rs[0]["template_value"];
		}

		return $template;
	}

	function saveGlobalWebserverTemplate($template, $webserver_type, $webserver_mode, $value)
	{
		if (!empty($value)) {
			$validTemplates = array('apachetemplate', 'subdomaintemplate', 'enableddefault', 'pwdir', 'mainwebserverconf', 'redirect');
			if (in_array($template, $validTemplates)) {
				// Insert or update global template
				$SQL = "INSERT INTO " . $this->conf['globalwebservertemplatestable']['tablename'] . " (template_name, template_webserver_type, template_ssl_type, template_value) VALUES ('" . $template . "', '" . $this->escape($webserver_type) . "', '" . $this->escape($webserver_mode) . "', '" . $value . "') ON DUPLICATE KEY UPDATE template_value='" . $value . "';";

				// Run Query
				return $this->executeQuery($SQL);
			}
		}

		return false;
	}

	function revertTemplateBackToEHCPDefault($template, $type = "", $mode = "")
	{

		if (empty($type)) {
			$type = $this->miscconfig['webservertype'];
		}

		if (empty($mode)) {
			$mode = $this->miscconfig['webservermode'];
		}

		// Clear template value
		$SQL = "UPDATE " . $this->conf['globalwebservertemplatestable']['tablename'] . " SET template_value='' WHERE template_name ='" . $template . "' AND template_webserver_type='" . $this->escape($type) . "' AND template_ssL_type='" . $this->escape($mode) . "'";

		if ($template == "enableddefault" && $type == $this->miscconfig['webservertype'] && $mode == $this->miscconfig['webservermode']) {
			$this->addDaemonOp('handle_reset_sites_enabled_default', '', '', '', 'reset default sites enabled template');
		}

		if ($template == "mainwebserverconf" && $type == $this->miscconfig['webservertype'] && $mode == $this->miscconfig['webservermode']) {
			$this->addDaemonOp('handle_reset_mainwebserverconf', '', '', '', 'reset main webserver conf to default');
		}

		// Run Query
		return $this->executeQuery($SQL);
	}

	function handleGlobalTemplatesForBaseWebserverFiles()
	{
		$success = false;
		$this->requireCommandLine(__FUNCTION__);

		$this->echoln("Handling global domain templates...\n");

		// Handle default sites enabled
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='enableddefault' AND template_webserver_type='" . $this->miscconfig['webservertype'] . "' AND template_ssl_type='" . $this->miscconfig['webservermode'] . "'";

		// Run Query
		$rs = $this->query($SQL);

		if (count($rs) == 1) {
			$template = $rs[0]["template_value"];
			if (!empty($template)) {
				if ($this->miscconfig['webservertype'] == "nginx") {
					$file = "/etc/nginx/sites-enabled/default";
				} else {
					$file = "/etc/apache2/sites-enabled/default";
				}
				$success = writeoutput2($file, $template, 'w', false);
			}
		}

		// Handle main webserver config file sites enabled
		$SQL = "SELECT * FROM " . $this->conf['globalwebservertemplatestable']['tablename'] . " WHERE template_name ='mainwebserverconf' AND template_webserver_type='" . $this->miscconfig['webservertype'] . "' AND template_ssl_type='" . $this->miscconfig['webservermode'] . "'";

		// Run Query
		$rs = $this->query($SQL);

		if (count($rs) == 1) {
			$template = $rs[0]["template_value"];
			if (!empty($template)) {
				if ($this->miscconfig['webservertype'] == "nginx") {
					$file = "/etc/nginx/nginx.conf";

					// Update the variables
					$template = str_replace(array('{wwwuser}', '{wwwgroup}'), array($this->wwwuser, $this->wwwgroup), $template);
				} else {
					// Make a backup of the original configuration if it does not exist... this will be used to restore later
					// We don't have a template for the apache2 base configuration
					if (!file_exists("/etc/apache2/apache2.conf.bk_used_for_EHCP_DO_NOT_DELETE")) {
						copy("/etc/apache2/apache2.conf", "/etc/apache2/apache2.conf.bk_used_for_EHCP_DO_NOT_DELETE");
					}
					$file = "/etc/apache2/apache2.conf";
				}
				$success = writeoutput2($file, $template, 'w', false);
			}
		}

		return $success;
	}

	function resetAllGlobalTemplatesDueToConfErrors()
	{
		// Do not expose this function to the public
		// This could be used as an API call to help admins that messed everything up...
		// EHCP support will need to provide them the code to do this though.
		$this->addDaemonOp('handle_reset_sites_enabled_default', '', '', '', 'reset default sites enabled template');
		$this->addDaemonOp('handle_reset_mainwebserverconf', '', '', '', 'reset main webserver conf to default');

		// Clear entire templates file
		$SQL = "DELETE FROM " . $this->conf['globalwebservertemplatestable']['tablename'];

		// Run Query
		$this->executeQuery($SQL);

		// Sync domains
		$this->addDaemonOp('syncdomains', '', '', '', 'sync domains');

		return true;
	}

	/* Cronjobs and Remote Backups */

	function addRemoteBackup()
	{ # coded by earnolmartin@gmail.com
		global $serverip, $_insert, $name, $transfer_host, $transfer_port, $transfer_login, $transfer_pass, $transfer_method, $dayofweek, $time, $encryption_pass, $backupid;

		// Remote backup scheduling can on
		$this->requireAdmin();

		$this->getVariable(array("_insert", 'name', 'dayofweek', 'time', 'transfer_method', 'transfer_host', 'transfer_port', 'transfer_login', 'transfer_pass', 'encryption_pass', 'backupid'));

		$success = True;
		$editMode = false;
		$errmsg = '';

		if (isset($backupid) && is_numeric($backupid)) {
			$recordId = $backupid;
		}

		$recordId = $_REQUEST["id"];

		// Check for edit mode
		if (isset($recordId) && is_numeric($recordId)) {
			$id = $recordId;
			// Build table based on queries
			$SQL = "SELECT * FROM " . $this->conf['remote_backups_table']['tablename'] . " WHERE id = '" . $recordId . "'";

			// Run Query
			$rs = $this->query($SQL);
			if (count($rs) == 1) {
				$row = $rs[0];
				$defaultMessage = "Edit";
				$successMessageWording = "edited";
				$failMessageWording = "edit";
				$editMode = true;
			}
		}

		if (!$_insert) {
			$defaultMessage = "Schedule New";
			$successMessageWording = "scheduled";
			$failMessageWording = "schedule";

			$inputparams = array(
				array('name', 'input', 'lefttext' => 'Backup Name:', 'default' => (!empty($row["name"]) ? $row["name"] : '')),
				array('dayofweek', 'select', 'lefttext' => 'Day of Week:', 'secenekler' => $this->remoteDayOfWeek, 'default' => (!empty($row["dayofweek"]) ? $row["dayofweek"] : '')),
				array('time', 'select', 'lefttext' => 'Time:', 'secenekler' => $this->remoteBackupTime, 'default' => (!empty($row["time"]) ? $row["time"] : '')),
				array('transfer_method', 'select', 'lefttext' => 'Transfer Method:', 'secenekler' => $this->remoteBackupMethod, 'default' => (!empty($row["transfer_method"]) ? $row["transfer_method"] : '')),
				array('transfer_host', 'input', 'lefttext' => 'Transfer Host:', 'default' => (!empty($row["transfer_host"]) ? $row["transfer_host"] : '')),
				array('transfer_port', 'input', 'lefttext' => 'Transfer Port:', 'default' => (!empty($row["transfer_port"]) ? $row["transfer_port"] : '')),
				array('transfer_login', 'input', 'lefttext' => 'Host Login:', 'default' => (!empty($row["transfer_login"]) ? $row["transfer_login"] : '')),
				array('transfer_pass', 'input', 'lefttext' => 'Host Password:', 'default' => (!empty($row["transfer_pass"]) ? $row["transfer_pass"] : '')),
				array('encryption_pass', 'input', 'lefttext' => 'Backup Encryption Password:', 'default' => (!empty($row["encryption_pass"]) ? $row["encryption_pass"] : '')),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			if (isset($row) && !empty($row) && is_array($row) && is_numeric($row["id"])) {
				array_push($inputparams, array('backupid', 'hidden', 'default' => $row["id"]));
			}

			$this->output .= "<br>" . $defaultMessage . " Remote Backup<br><p style=\"font-size: 10px; font-weight: bold;\">Security Notice:&nbsp; Please be sure to provide credentials for a non-sudo user on the remote host if the SCP protocol is to be used.</p>" . inputform5($inputparams);
		} else {

			// Clear any errors that may exist
			if (isset($errors)) {
				unset($errors);
			}

			/*                         *
			 *  All Fields Have Value  *
			 *                         */

			if (empty($name) || strpos($name, " ") != false) {
				$errors[] = "Please enter a valid name without any spaces!";
			} else {
				// Strip out any invalid inputs
				$name = preg_replace("/[^a-zA-Z0-9]/", "", $name);
				if (empty($name)) {
					$errors[] = "Please enter a valid name!";
				}
			}

			if (is_numeric($name[0])) {
				$errors[] = "Please enter a name that starts with a letter.";
			}

			if (empty($transfer_host) || (!isValidIPAddress($transfer_host, true) && !isValidHostname($transfer_host))) {
				$errors[] = "Please enter a valid IP address or hostname as your host.";
			}

			if (empty($transfer_port) || strlen($transfer_port) > 5) {
				$errors[] = "Please enter a valid port for this remote host.";
			}

			if (empty($transfer_login)) {
				$errors[] = "Please enter a valid login for the remote host.";
			}

			if (empty($transfer_pass)) {
				$errors[] = "Please enter a valid password for the remote host.";
			}

			if (@$dayofweek != 0 && empty($dayofweek)) {
				$errors[] = "Please enter a valid day of the week.";
			}

			if (@$time != 0 && empty($time)) {
				$errors[] = "Please enter a valid time.";
			}

			if (empty($transfer_method)) {
				$errors[] = "Please enter a valid transfer method.";
			}

			if (empty($encryption_pass) || strlen($encryption_pass) < 10 || strlen($encryption_pass) > 50) {
				$errors[] = "Please enter an ecryption password greater than 10 characters but less than 50 characters in length.";
			}

			// Output errors

			if (isset($errors) && is_array($errors)) {
				$errStr = $this->errArrayToStr($errors);
				unset($errors);
				$this->errorTextExit($errStr);
			}

			// Remove invalid characters from name
			$name = removeInvalidChars($name, "name");

			// Last validation
			if (empty($name)) {
				$this->errorTextExit("Please enter a valid name!");
			}

			// Security checks
			$name = $this->escape($name);
			$transfer_host = $this->escape($transfer_host);
			$transfer_port = $this->escape($transfer_port);
			$transfer_login = $this->escape($transfer_login);
			$transfer_pass = $this->escape($transfer_pass);
			$transfer_method = $this->escape($transfer_method);
			$dayofweek = $this->escape($dayofweek);
			$time = $this->escape($time);
			$encryption_pass = $this->escape($encryption_pass);

			// Select any records where the time is set to the selected... we cannot allow this!
			$SQL = "SELECT id FROM " . $this->conf['remote_backups_table']['tablename'] . " WHERE time = '$time' AND dayofweek = '$dayofweek'";
			$rs = $this->query($SQL);

			// Select any records that might have the same name
			$SQL = "SELECT id FROM " . $this->conf['remote_backups_table']['tablename'] . " WHERE name = '$name'";
			$rsName = $this->query($SQL);

			if (count($rsName) >= 1) {
				if ($editMode && $rsName[0]["id"] != $recordId) {
					$this->errorTextExit("Please use a backup name that is not already in use.");
				} else if (!$editMode) {
					$this->errorTextExit("Please use a backup name that is not already in use.");
				}
			}

			if (count($rs) >= 1) {
				if ($editMode && $rs[0]["id"] != $recordId) {
					$this->errorTextExit("A remote backup operation is already scheduled to run at the selected time!");
				} else if (!$editMode) {
					$this->errorTextExit("A remote backup operation is already scheduled to run at the selected time!");
				}
			}

			if (!$editMode) {
				$SQL = "INSERT INTO " . $this->conf['remote_backups_table']['tablename'] . " (name, dayofweek, time, transfer_method, transfer_host, transfer_port, transfer_login, transfer_pass, encryption_pass) VALUES ('$name', '$dayofweek', '$time', '$transfer_method', '$transfer_host', '$transfer_port', '$transfer_login', '$transfer_pass', '$encryption_pass')";
			} else {
				$SQL = "UPDATE " . $this->conf['remote_backups_table']['tablename'] . " SET name = '" . $name . "', dayofweek = '" . $dayofweek . "', time='" . $time . "', transfer_method='" . $transfer_method . "', transfer_host='" . $transfer_host . "', transfer_port='" . $transfer_port . "', transfer_login='" . $transfer_login . "', transfer_pass='" . $transfer_pass . "', encryption_pass='" . $encryption_pass . "' WHERE id = '" . $recordId . "'";
			}

			// Run Query
			$this->executeQuery($SQL);

			$success = $success && $this->addDaemonOp("rebuild_crontab", '', '', '', 'rebuild crontab');

			# single function ok_err_text is enaugh at end of an operation.
			$this->ok_err_text($success, 'Successfully ' . $successMessageWording . ' the remote backup configured to use a transfer method of ' . $transfer_method . ' to ' . $transfer_host . '!', 'Failed to ' . $failMessageWording . ' remote backup! (' . __FUNCTION__ . ')');
		}

		$this->showSimilarFunctions('backup');
		return $success;
	}

	function removeRemoteBackup()
	{ 	# coded by earnolmartin@gmail.com
		global $_insert, $backupJobsToDelete;

		// Custom FTP accounts must be configured by an admin.
		$this->requireAdmin();

		$this->getVariable(array("_insert"));
		$backupJobsToDelete = $_POST['backupJobsToDelete'];
		$success = True;

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Remove Selected Remote Backup Jobs')
			);

			// Build table based on queries
			$SQL = "SELECT * FROM " . $this->conf['remote_backups_table']['tablename'];

			// Run Query
			$rs = $this->query($SQL);

			if (count($rs) == 0) {
				$this->errorTextExit('Currently no remote backup jobs exist!');
			} else {
				$table = "<form method=\"post\" action=\"?op=" . __FUNCTION__ . "\"><table class='genericList'><tr><th style=\"width: 100px;\">Select</th><th style=\"width: 200px;\">Job Name</th><th style=\"width: 100px;\">Remote Host</th><th style=\"width: 100px;\">Transfer Method</th><th style=\"width: 100px;\">Time</th><th style=\"width: 100px;\">Day of Week</th><th style=\"width: 100px;\">Encryption Password</th><th style=\"width: 100px;\">Actions</th></tr>";
				foreach ($rs as $r) {
					// Only show custom entries... do not allow to modify EHCP accounts.
					$dayOfWeekNum = $r['dayofweek'];
					$backupDay = $this->getBackupDay($dayOfWeekNum);
					$table .= "<tr><td><input type=\"checkbox\" value=\"{$r['id']}\" name=\"backupJobsToDelete[]\" /></td><td>{$r['name']}</td><td>{$r['transfer_host']}:{$r['transfer_port']}</td><td>{$r['transfer_method']}</td><td>{$r['time']}:00</td><td>{$backupDay}</td><td>{$r['encryption_pass']}</td><td><a href='?op=editremotebackup&id=" . $r['id'] . "'>Edit</a></td></tr>";
				}
				$table .= "</table><br><input type=\"submit\" value=\"Delete Selected Remote Backup Jobs\" name=\"_insert\"></form>";
			}

			$this->output .= "<br>List of Remote Backup Jobs<br>" . $table;
		} else {
			if (isset($backupJobsToDelete) && is_array($backupJobsToDelete) && count($backupJobsToDelete) > 0) {
				foreach ($backupJobsToDelete as $toDelete) {
					// Secure the string
					$toDelete = $this->escape($toDelete);
					$sql = "delete from " . $this->conf['remote_backups_table']['tablename'] . " WHERE id = '$toDelete'";
					$success = $success && $this->executeQuery($sql);
				}
			} else {
				$success = FALSE;
				$errmsg = "No remote backup jobs were selected for removal!";
			}

			$success = $success && $this->addDaemonOp("rebuild_crontab", '', '', '', 'rebuild crontab');
			$this->ok_err_text($success, "Selected remote backup jobs were deleted!", "$errmsg <br>No backup remote jobs were deleted! (" . __FUNCTION__ . ')');
		}


		$this->showSimilarFunctions('backup');
		return $success;
	}

	// Update theme color
	function updateThemeColor()
	{ 	# coded by earnolmartin@gmail.com
		// This is an ajax call

		if (isset($_REQUEST['theme_color'])) {
			$theme_color = strip_tags(trim($_REQUEST['theme_color']));
			$theme_color = $this->escape($theme_color);
			if (strlen($theme_color) == 7) {
				// Get current user
				$paneluserinfo = $this->getPanelUserInfo();

				// Update theme color
				$SQL = "UPDATE " . $this->conf['paneluserstable']['tablename'] . " SET theme_color='" . $theme_color . "' WHERE id ='" . $paneluserinfo["id"] . "'";

				// Run Query
				$this->executeQuery($SQL);
			}
		}
	}

	// Update theme contrast
	function updateThemeContrast()
	{ # coded by earnolmartin@gmail.com
		// This is an ajax call

		if (isset($_REQUEST['theme_contrast'])) {
			$theme_contrast = strip_tags(trim($_REQUEST['theme_contrast']));
			$theme_contrast = $this->escape($theme_contrast);
			if (strlen($theme_contrast) < 10) {
				// Get current user
				$paneluserinfo = $this->getPanelUserInfo();

				// Update theme color
				$SQL = "UPDATE " . $this->conf['paneluserstable']['tablename'] . " SET theme_contrast='" . $theme_contrast . "' WHERE id ='" . $paneluserinfo["id"] . "'";

				// Run Query
				$this->executeQuery($SQL);
			}
		}
	}

	function getMyDomainsAsObject()
	{
		$mydomains = $this->getMyDomains('', ' ORDER BY domainname ASC');
		header('Content-Type: application/json');
		die(json_encode($mydomains));
	}

	// Generic cronjobs:
	function addCronjob()
	{ # coded by earnolmartin@gmail.com
		global $serverip, $_insert, $script, $dayofweek, $time;

		// Remote backup scheduling can on
		$this->requireAdmin();

		$this->getVariable(array("_insert", 'script', 'dayofweek', 'time'));

		$success = True;
		$errmsg = '';

		if (!$_insert) {
			$inputparams = array(
				array('script', 'input', 'lefttext' => 'Command or Path to Script:', 'default' => ''),
				array('dayofweek', 'select', 'lefttext' => 'Day of Week:', 'secenekler' => $this->remoteDayOfWeek),
				array('time', 'select', 'lefttext' => 'Time:', 'secenekler' => $this->remoteBackupTime),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br>Schedule and Create Cronjobs<br>" . inputform5($inputparams);
		} else {

			// Clear any errors that may exist
			if (isset($errors)) {
				unset($errors);
			}

			/*                         *
			 *  All Fields Have Value  *
			 *                         */

			if (empty($script)) {
				$errors[] = "Please enter a valid command or script path!";
			}

			if (@$dayofweek != 0 && empty($dayofweek)) {
				$errors[] = "Please enter a valid day of the week.";
			}

			if (@$time != 0 && empty($time)) {
				$errors[] = "Please enter a valid time.";
			}

			// Output errors

			if (isset($errors) && is_array($errors)) {
				$errStr = $this->errArrayToStr($errors);
				unset($errors);
				$this->errorTextExit($errStr);
			}

			// Security checks
			$script = $this->escape($script);
			$dayofweek = $this->escape($dayofweek);
			$time = $this->escape($time);

			// Select any records where the time is set to the selected... we cannot allow this!
			$SQL = "SELECT id FROM " . $this->conf['cronjobs_table']['tablename'] . " WHERE script = '$script'";
			$rs = $this->query($SQL);

			if (count($rs) > 0) {
				$this->errorTextExit("A cronjob has already been configured through the panel to run this command.");
			} else {
				$SQL = "INSERT INTO " . $this->conf['cronjobs_table']['tablename'] . " (script, dayofweek, time) VALUES ('$script', '$dayofweek', '$time')";

				// Run Query
				$this->executeQuery($SQL);
			}

			$success = $success && $this->addDaemonOp("rebuild_crontab", '', '', '', 'rebuild crontab');

			# single function ok_err_text is enaugh at end of an operation.
			$this->ok_err_text($success, 'Successfully created cronjob to run command or script of ' . $script . '!', 'Failed to create cronjob to run script ' . $script . '! (' . __FUNCTION__ . ')');
		}

		$this->showSimilarFunctions('backup');
		return $success;
	}

	function removeCronjob()
	{ # coded by earnolmartin@gmail.com
		global $_insert, $cronJobsToDelete;

		$this->requireAdmin();

		$this->getVariable(array("_insert"));
		$cronJobsToDelete = $_POST['cronJobsToDelete'];
		$success = True;

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Remove Selected Scheduled Cronjobs')
			);

			// Build table based on queries
			$SQL = "SELECT * FROM " . $this->conf['cronjobs_table']['tablename'];

			// Run Query
			$rs = $this->query($SQL);

			if (count($rs) == 0) {
				$this->errorTextExit('Currently, no cronjobs have been scheduled!');
			} else {
				$table = "<form method=\"post\" action=\"?op=" . __FUNCTION__ . "\"><table class='genericList'><tr><th style=\"width: 100px;\">Select</th><th style=\"width: 200px;\">Job Script / Command</th><th>Time</th><th>Day of Week</th></tr>";
				foreach ($rs as $r) {
					$dayOfWeekNum = $r['dayofweek'];
					$backupDay = $this->getBackupDay($dayOfWeekNum);
					$table .= "<tr><td><input type=\"checkbox\" value=\"{$r['id']}\" name=\"cronJobsToDelete[]\" /></td><td>{$r['script']}</td><td>{$r['time']}:00</td><td>{$backupDay}</td></tr>";
				}
				$table .= "</table><br><input type=\"submit\" value=\"Delete Selected Cronjobs\" name=\"_insert\"></form>";
			}

			$this->output .= "<br>List of Cronjobs<br>" . $table;
		} else {
			if (isset($cronJobsToDelete) && is_array($cronJobsToDelete) && count($cronJobsToDelete) > 0) {
				foreach ($cronJobsToDelete as $toDelete) {
					// Secure the string
					$toDelete = $this->escape($toDelete);
					$sql = "delete from " . $this->conf['cronjobs_table']['tablename'] . " WHERE id = '$toDelete'";
					$success = $success && $this->executeQuery($sql);
				}
			} else {
				$success = FALSE;
				$errmsg = "No cronjobs were selected for removal!";
			}

			$success = $success && $this->addDaemonOp("rebuild_crontab", '', '', '', 'rebuild crontab');
			$this->ok_err_text($success, "Selected cronjobs were deleted!", "$errmsg <br>No cronjobs were deleted! (" . __FUNCTION__ . ')');
		}


		$this->showSimilarFunctions('backup');
		return $success;
	}

	function moveDomainToAnotherAccount()
	{
		global $_insert, $_insert2, $domainname, $movetopaneluser;

		$success = true;
		$this->requireAdmin();
		$this->getVariable(array("_insert", "domainname", "movetopaneluser", "_insert2"));
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		$domainInfo = $this->getDomainInfo($domainname);
		$currentUserThatOwnsDomain = $this->getPanelUserInfo('', $domainInfo['panelusername']);

		if (!$_insert) {

			$userAccountsWithFTP = $this->getUsersWithDefaultFTPAccounts(true, $currentUserThatOwnsDomain["panelusername"]);

			if ($userAccountsWithFTP === false || count($userAccountsWithFTP) === 0) {
				$this->ok_err_text(false, "There are no accounts that have associated FTP accounts.", "There are no accounts that have associated FTP accounts.");
				return false;
			}

			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('movetopaneluser', 'select', 'lefttext' => 'Account to Move Domain To:', 'secenekler' => $userAccountsWithFTP, 'default' => ($currentUserThatOwnsDomain ? $currentUserThatOwnsDomain["panelusername"] : "")),
				array('submit', 'submit', 'default' => 'Move Domain to Selected Account'),

			);

			$this->output .= "<p>Unassociate, move domain \"<b>" . $domainname . "</b>\", and move \"<b>" . $domainname . "</b>\"'s subdomains to this user:</p>" . inputform5($inputparams);
		} else {

			if (!$_insert2) {

				$inputparams = array(
					array('op', 'hidden', 'default' => __FUNCTION__),
					array('movetopaneluser', 'hidden', 'default' => $movetopaneluser),
					array('submit', 'submit', 'default' => 'Yes'),
					array('_insert2', 'hidden', 'default' => '1'),
					array('_insert', 'hidden', 'default' => '1')
				);

				$this->output .= "<p>Are you sure you want to associate the domain of \"<b>" . $domainname . "</b>\" with the panel user of \"<b>" . $movetopaneluser . "</b>\"?&nbsp; All subdomains, email accounts, MySQL databases, password protected directories, and any other domain associated information will be transferred to this user!</p>" . inputform5($inputparams);

			} else {

				$newAccountInfo = $this->getPanelUserInfo('', $movetopaneluser);
				$resellerOfAccount = $newAccountInfo["reseller"];
				$newAccountFTPLogin = $this->getFtpAccountLoginByUsername($movetopaneluser);
				if ($newAccountFTPLogin === false) {
					$this->ok_err_text(false, "User doesn't have an FTP login set yet!", "User doesn't have an FTP login set yet!");
					return false;
				}

				$currentHome = $domainInfo["homedir"];
				$newHome = "/var/www/vhosts/" . $newAccountFTPLogin;
				$newHomeFullPath = $newHome . "/" . $domainInfo["domainname"];

				// Update associations in the database.
				$success = $success && $this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " set homedir= '" . $newHome . "/" . $domainInfo["domainname"] . "', panelusername = '" . $movetopaneluser . "', reseller = '" . $resellerOfAccount . "' where domainname = '" . $domainInfo["domainname"] . "';");
				$success = $success && $this->executeQuery("update " . $this->conf['subdomainstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "' where domainname = '" . $domainInfo["domainname"] . "';");
				$success = $success && $this->executeQuery("update " . $this->conf['subdomainstable']['tablename'] . " set homedir = REPLACE(homedir, '" . $currentHome . "', '" . $newHome . "/" . $domainInfo["domainname"] . "');");
				$success = $success && $this->executeQuery("update " . $this->conf['mysqldbuserstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "' WHERE domainname = '" . $domainInfo["domainname"] . "';");
				$success = $success && $this->executeQuery("update " . $this->conf['emailuserstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "' WHERE domainname = '" . $domainInfo["domainname"] . "';");
				$success = $success && $this->executeQuery("update " . $this->conf['mysqldbstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "' WHERE domainname = '" . $domainInfo["domainname"] . "';");
				$success = $success && $this->executeQuery("update " . $this->conf['ftpuserstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "', reseller = '" . $resellerOfAccount . "' WHERE panelusername IS NOT NULL AND domainname IS NOT NULL AND domainname = '" . $domainInfo["domainname"] . "' AND type != 'default';");
				$success = $success && $this->executeQuery("update " . $this->conf['ftpuserstable']['tablename'] . " set homedir = REPLACE(homedir, '" . $currentHome . "', '" . $newHome . "/" . $domainInfo["domainname"] . "');");
				$success = $success && $this->executeQuery("update " . $this->conf['ftpuserstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "', reseller = '" . $resellerOfAccount . "' WHERE homedir LIKE '" . $newHome . "/" . $domainInfo["domainname"] . "%' AND type != 'default';");
				$success = $success && $this->executeQuery("update " . $this->conf['pwd_dirs_table']['tablename'] . " set domainpath = REPLACE(domainpath, '" . $currentHome . "', '" . $newHome . "/" . $domainInfo["domainname"] . "');");
				$success = $success && $this->executeQuery("update " . $this->conf['emailforwardingstable']['tablename'] . " set panelusername = '" . $movetopaneluser . "' WHERE domainname = '" . $domainInfo["domainname"] . "';");

				// Move files to the new home directory
				$success = $success && $this->runCommandInDaemon("mkdir -p " . $newHome . "/" . $domainInfo["domainname"] . " && cp -R " . $currentHome . "/* " . $newHome . "/" . $domainInfo["domainname"] . " && rm -rf " . $currentHome . " && chown -R " . $this->ftpuser . ":www-data " . $newHome . "/" . $domainInfo["domainname"]);

				// Update any let's encrypt configuration paths
				$success = $success && $this->runCommandInDaemon('sed -i "s#' . $currentHome . '#' . $newHomeFullPath . '#g" /etc/letsencrypt/renewal/' . $domainInfo["domainname"] . '.conf');

				// Sync FTP accounts
				$success = $success && $this->addDaemonOp('syncftp', '', '', '', 'sync ftp for nonstandard homes');

				// Sync domains
				$success = $success && $this->addDaemonOp('syncdomains', '', '', '', 'sync domains');

				$this->ok_err_text($success, "Domain \"" . $domainname . "\" was successfully moved and associated with the panel user of \"" . $movetopaneluser . "\"!", "Failed to move and associate domain \"" . $domainname . "\" to the panel user of \"" . $movetopaneluser . "\"! (" . __FUNCTION__ . ")");
			}
		}

		return $success;
	}

	function getUsersWithDefaultFTPAccounts($includeSelf = true, $currentOwner = "")
	{
		// Build table based on queries
		$SQL = "SELECT panel.panelusername as panelusername, panel.id as id  FROM " . $this->conf['ftpuserstable']['tablename'] . " as fp INNER JOIN " . $this->conf['paneluserstable']['tablename'] . " as panel ON fp.panelusername = panel.panelusername ORDER BY panel.panelusername ASC";

		// Run Query
		$rs = $this->query($SQL);

		if ($rs && is_array($rs)) {
			foreach ($rs as $dom) {
				$usersWithFTP[$dom["panelusername"]] = $dom["panelusername"];
			}
		}

		if (isset($usersWithFTP) && is_array($usersWithFTP) && count($usersWithFTP) > 0) {
			if (!$includeSelf) {
				unset($usersWithFTP[$this->activeuser]);
			}

			if (!empty($currentOwner)) {
				unset($usersWithFTP[$currentOwner]);
			}
		}

		if (isset($usersWithFTP) && is_array($usersWithFTP) && count($usersWithFTP) > 0) {

			return $usersWithFTP;
		}

		return false;
	}

	function getBackupDay($dayOfWeekNum)
	{
		$backupDay = "";
		switch ($dayOfWeekNum) {
			case 0:
				$backupDay = "Sunday";
				break;
			case 1:
				$backupDay = "Monday";
				break;
			case 2:
				$backupDay = "Tuesday";
				break;
			case 3:
				$backupDay = "Wednesday";
				break;
			case 4:
				$backupDay = "Thursday";
				break;
			case 5:
				$backupDay = "Friday";
				break;
			case 6:
				$backupDay = "Saturday";
				break;
			default:
				$backupDay = "Unknown";
		}
		return $backupDay;
	}

	function addDnsOnlyDomain()
	{
		global $domainname, $serverip, $_insert;
		$this->getVariable(array("domainname", "_insert", 'serverip'));
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomaintothispaneluser", array()))
				return false;
			$inputparams = array(
				array('domainname', 'lefttext' => 'Domain Name:'),
				array('serverip', 'lefttext' => 'Server IP Address Domain is Currently Hosted On:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= "<br>Add a DNS only domain.&nbsp; This function will create DNS records only and is not a normal hosted domain.<br>" . inputform5($inputparams);

		} else {

			if (
				!$this->afterInputControls(
					"adddomaintothispaneluser",
					array(
						"domainname" => $domainname,
					)
				)
			)
				return false;

			$this->noEmpty($domainname); # same control is done above, in afterInputControls. left here for example usage for noEmpty

			// Check to make sure valid domain
			$domainname = $this->adjustDomainname($domainname);
			$success = $this->is_valid_domain_name($domainname);
			if ($success) {
				$paneluserinfo = $this->getPanelUserInfo();
				$sql = "insert into " . $this->conf['domainstable']['tablename'] . " (reseller,panelusername,domainname,homedir,status,serverip) values ('" . $this->activeuser . "','" . $this->activeuser . "','$domainname','','" . $this->status_active . "','$serverip')";
				$success = $success && $this->executeQuery($sql);
				$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
				$this->ok_err_text($success, 'DNS only domain was successfully added.', 'Failed to add domain (' . __FUNCTION__ . ')');
			} else {
				$this->ok_err_text($success, 'DNS only domain was successfully added.', 'Domain ' . $domainname . ' is invalid!');
			}
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}

	function print_r2($ar)
	{
		$this->output .= print_r2($ar);
	}

	function multiserver_add_domain()
	{ # add domain, paneluser and ftp user once
		global $dnsserverips, $webserverips, $mailserverips, $mysqlserverips, $domainname, $ftpusername, $ftppassword, $quota, $upload, $download, $panelusername, $paneluserpass, $_insert, $email;
		$vars = $this->getVariable(array('dnsserverips', 'webserverips', 'mailserverips', 'mysqlserverips', 'domainname', 'ftpusername', 'ftppassword', 'quota', 'upload', 'download', 'panelusername', 'paneluserpass', '_insert', 'email'));
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomain", array()))
				return false;
			$inputparams = array(

				'domainname',
				array('panelusername', 'lefttext' => 'Panel Username'),
				array('paneluserpass', 'password_with_generate', 'lefttext' => 'Paneluser Password'),
				array('ftpusername', 'lefttext' => 'FTP Username'),
				array('ftppassword', 'password_with_generate', 'lefttext' => 'FTP Password'),
				array('quota', 'default' => '200', 'lefttext' => "Quota (MB)"),
				array('upload', 'default' => '200', 'lefttext' => "Upload Bandwidth (KB/s)"),
				array('download', 'default' => '200', 'lefttext' => "Download Bandwidth (KB/s)"),
				array('email', 'default' => $this->miscconfig['adminemail']),
				array('dnsserverips', 'hidden&text', 'lefttext' => 'DNS Server IPs', 'default' => $this->miscconfig['defaultdnsserverips']),
				array('webserverips', 'hidden&text', 'lefttext' => 'Webserver IPs', 'default' => $this->miscconfig['defaultwebserverips']),
				array('mailserverips', 'hidden&text', 'lefttext' => 'Mailserver IPs', 'default' => $this->miscconfig['defaultmailserverips']),
				array('mysqlserverips', 'hidden&text', 'lefttext' => 'MySQL Server IPs', 'default' => $this->miscconfig['defaultmysqlserverips']),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "(These will be done using ServerPlans in future)<br>" . inputform5($inputparams);
		} else {
			$status = $this->status_active;
			$success = $success && $this->multiserver_add_domain_direct(compact('dnsserverips', 'webserverips', 'mailserverips', 'mysqlserverips', 'domainname', 'panelusername', 'paneluserpass', 'ftpusername', 'ftppassword', 'status', 'email', 'quota'));
			$success = $success && $this->setselecteddomain($domainname);
			$this->ok_err_text($success, 'Successfully added domain(s).', 'Failed to add domain (adddomain)');
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}


	function addDomain()
	{ # add domain, paneluser and ftp user once

		global $domainname, $ftpusername, $ftppassword, $quota, $upload, $download, $panelusername, $paneluserpass, $_insert, $email;
		$this->getVariable(array("domainname", "ftpusername", "ftppassword", "quota", "upload", "download", "panelusername", "paneluserpass", "_insert", 'email'));

		# This is a reseller / admin feature only!
		$this->requireReseller();
		$success = True;

		if (!$_insert) {
			if (!$this->beforeInputControls("adddomain", array()))
				return false;
			$inputparams = array(
				array('domainname', 'lefttext' => 'Domain Name'),
				array('panelusername', 'lefttext' => 'Panel username'),
				array('paneluserpass', 'password_with_generate', 'lefttext' => 'Paneluser password'),
				array('ftpusername', 'lefttext' => 'Ftp username'),
				array('ftppassword', 'password_with_generate', 'lefttext' => 'Ftp Password'),
				array('quota', 'default' => '200', 'lefttext' => "Quota (Mb)"),
				array('upload', 'default' => '200', 'lefttext' => "Upload bw(kb/s)"),
				array('download', 'default' => '200', 'lefttext' => "Download bw(kb/s)"),
				array('email', 'default' => $this->miscconfig['adminemail']),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br>Add domain<br>Please do not include prefixes such as \"http://\" or \"www.\":<br>"
				. inputform5($inputparams);

		} else {
			$success = $success && $this->addDomainDirect($domainname, $panelusername, $paneluserpass, $ftpusername, $ftppassword, $this->status_active, $email, $quota);
			$success = $success && $this->setselecteddomain($domainname);
			$this->ok_err_text($success, 'Successfully added domain.', 'Failed to add domain.');
		}
		$this->showSimilarFunctions('domain');
		return $success;
	}


	function check_remote_mysql_connection($dbhost)
	{
		if ($this->connected_mysql_servers[$dbhost])
			return $this->connected_mysql_servers[$dbhost];
		#$conn=$this->
		# **** to be completed later
		return True;
	}

	function multiserver_add_domain_direct($named_params)
	{
		extract($named_params); # produces variables with dnsservers, webservers and so on.  from now on, I plan to use named_params
		# opposite: compact($vars...

		# $dnsservers,$webservers,$mailserver,$mysqlservers,$domainname,$panelusername,$paneluserpass,$ftpusername,$ftppassword,$status,$email='',$quota=0
		# dnsservers: dns will be defined here
		# webservers: ftp also will be setup here, apache configs will be
		# mailserver: mail settings will be done in that server
		# mysqlservers: already done, default mysqlservers for that domain will be this. if multiple, user will be able to select one

		# tum kodlar, server ipler dahil edilecek sekilde yeniden tasarlanacak.
		if ($webservers <> 'localhost')
			$this->check_remote_mysql_connection($webservers);

		$domainname = $this->adjustDomainname($domainname);
		$success = $this->is_valid_domain_name($domainname);

		if ($success) {
			$panelusername = trim($panelusername);
			$ftpusername = trim($ftpusername);


			if (
				!$this->afterInputControls(
					"adddomain",
					array(
						"domainname" => $domainname,
						"ftpusername" => $ftpusername,
						"panelusername" => $panelusername
					)
				)
			)
				return false;

			$this->output .= __FUNCTION__ . ": Adding domain: $domainname";
			$this->debugecho(__FUNCTION__ . ":" . print_r2($named_params), 3, false);


			$homedir = $this->conf['vhosts'] . "/$ftpusername/$domainname";
			$success = True;

			# server ipler eklenecek.
			$s = $this->executeQuery("insert into " . $this->conf['domainstable']['tablename'] . " (reseller,panelusername,domainname,homedir,status,diskquota,dnsserverips,webserverips,mailserverips,mysqlserverips) values ('" . $this->activeuser . "','$panelusername','$domainname','$homedir','$status',$quota,'$dnsserverips','$webserverips','$mailserverips','$mysqlserverips')", 'domain add to ehcp db');

			list($ftpserver) = explode(',', $webserverips); # take first ip/localhost from list of webservers: ftp is only setup on first of webservers. other webservers(if any) should update files itself, by nfs/nas or other means.

			#$success=$success && ($s=$this->addDaemonOp("daemondomain","multiserver_add_domain",$domainname,$homedir,'domain info&ftp'));# since, adddaemonop only returns True or false, this construct is True, but above, execute may return other thing...

			$this->debugecho(__FUNCTION__ . ": webserverips:($webserverips) ftpserver:($ftpserver)", 3, false);

			$success = $success && $this->add_daemon_op(array('op' => 'daemondomain', 'action' => 'multiserver_add_domain', 'info' => $domainname, 'info2' => $homedir, 'info3' => $ftpserver)); # domain initial directory settings are done in one of webservers, which sees same hdd space as others.
			$success = $success && $this->add_daemon_op(array('op' => 'new_sync_all'));
			$success = $success && $this->addPanelUserDirect($panelusername, $paneluserpass, 1, 5, 0, 1, 1, $quota, 0, 10, '', $email, $status);


			# ftp ekleme: nereye eklemeli?
			# herşey uzağa eklenirse, listelemek icin uzağa bağlanmak gerekir.
			# lokale eklenirse, lokal ftp daemonda degisiklik gerekir, localdeki ftpaccounts tablosuna ekleyemiyorum, zira daemon where cumleciginde iki kolon desteklemiyor.
			# uzakta ftp nin çalışması için oraya da eklenmesi gerekir. ozaman hem lokale hem uzağa eklenmeli sanki. ozaman silerken/güncellerken her iki yerden silinmeli v.b.

			$home = $this->conf['vhosts'] . "/$ftpusername";
			$type = 'default';
			$is_special_home = false;
			$success = $success && ($s = $this->multiserver_add_ftp_user_direct(compact('ftpserver', 'panelusername', 'ftpusername', 'ftppassword', 'home', 'upload', 'download', 'quota', 'domainname', 'type', 'is_special_home', 'status')));

			$sayi = $this->recordcount($this->conf['domainstable']['tablename'], '');
			$msg = "<p>Hi EHCP Admin,<br><br>The domain of $domainname was added to the server with the IP address of " . $this->singleserverip . "!<br></p>";
			$msg .= $this->url;

			$msguser = "<p>Hi EHCP Force User,<br><br>Your domain of $domainname was successfully configured on the server!</p>";
			$subj = "Domain $domainname Added To Panel";
			$this->infotoadminemail($msg, $subj);
			if ($email <> '')
				$this->sendEmail($email, $subj, $msguser);

			return $this->ok_err_text(
				$success,
				"Domain $domainname was successfully added to the panel.",
				"Failed to add domain: $domainname"
			);
		} else {
			return $this->ok_err_text(
				$success,
				"Domain $domainname was successfully added to the panel.",
				'Domain ' . $domainname . ' is invalid!'
			);
		}
	}

	function multiserver_add_ftp_user_direct($named_params)
	{
		extract($named_params);
		$this->debugecho(__FUNCTION__ . ":" . __LINE__ . ":" . print_r2($named_params), 3, false);

		if ($ftpserver == '')
			$named_params['ftpserver'] = $ftpserver = 'localhost';
		if ($ftpserver == 'localhost')
			return $this->add_ftp_user_direct($named_params); # call local ftp function if server is localhost
		
			# equivalent: if($ftpserver=='localhost') return $this->addFtpUserDirect($panelusername,$panelusername,$ftpusername,$ftppassword,$home,$upload,$download,$quota,$domainname,$type,$is_special_home,$status));
		# rest is for remote

		if ($status == '')
			$status = $this->status_active; # default is active,
		$this->debugecho("$panelusername,$ftpusername,$ftppassword,$home,$upload,$download,$quota,$domainname,$type,$isSpecialHome", 1, false);
		$panelusername = trim($panelusername);
		$ftpusername = trim($ftpusername);
		if ($isSpecialHome)
			$homedir = $home; # home dir is only inserted if different from default of ".$this->vhostsdir."/ftphome....

		# uzak   server baglan  ekle
		# remote server connect add
		$p = array(
			'dbhost' => $ftpserver,
			'dbusername' => $this->dbusername,
			'dbpass' => $this->dbpass,
			'dbname' => $this->dbname
		);

		$this->debugecho(__FUNCTION__ . ":" . __LINE__ . ":" . print_r2($p), 3, false);

		$success = True;

		$success = $success && $uzak_conn = $this->connect_to_mysql($p); # use same settings as this server.
		$qu = "INSERT INTO ftpaccounts ( reseller, panelusername, domainname, ftpusername, password, homedir, type,status,datetime)	VALUES ('" . $this->activeuser . "','$panelusername','$domainname','$ftpusername', password('$ftppassword'),'$homedir','$type','$status',now())";
		$success = $success && $this->executeQuery($qu, 'add ftp user : ' . $ftpusername, __FUNCTION__, $uzak_conn);
		$success = $success && $this->addDaemonOp('daemonftp', 'multiserver_add', $home, $ftpserver);

		return $success;


	}

	function add_ftp_user_direct($named_params)
	{
		# difference from addFtpUserDirect: uses named_params, my new coding style, I hope will be successful.
		# only calls old function using old style, until all code cleaned and tested enaugh.
		extract($named_params);
		return $this->addFtpUserDirect($panelusername, $panelusername, $ftpusername, $ftppassword, $home, $upload, $download, $quota, $domainname, $type, $is_special_home, $status);
	}

	function addDomainDirect($domainname, $panelusername, $paneluserpass, $ftpusername, $ftppassword, $status, $email = '', $quota = 0, $webserverips = '')
	{
		// This is ONE of the base functions for adding a domain to the panel...

		// Better validation
		$domainname = $this->adjustDomainname($domainname);
		$success = inputValid($domainname, 'domainname');

		if ($success) {
			$panelusername = trim($panelusername);
			$ftpusername = trim($ftpusername);


			if (
				!$this->afterInputControls(
					"adddomain",
					array(
						"domainname" => $domainname,
						"ftpusername" => $ftpusername,
						"panelusername" => $panelusername
					)
				)
			)
				return false;


			/*
			   domain path will be like: /var/www/vhosts/ftpusername/domain.com
			   /var/www/vhosts/ftpusername/domain.com will be stored as homedir in domains table,
			   one user will may have multiple domains with single ftp acount.
			   to achive this, i must implement domain add  to an existing ftp acount.
			   */
			$this->output .= "Adding domain: $domainname";
			$homedir = $this->conf['vhosts'] . "/$ftpusername/$domainname";
			$success = True;

			if ($webserverips == '' and $this->miscconfig['activewebserverip'] <> '')
				$webserverips = $this->miscconfig['activewebserverip']; # another ip is active for webserver

			#$s=$this->executeQuery("insert into ".$this->conf['domainstable']['tablename']." (reseller,panelusername,domainname,homedir,status,diskquota,webserverips) values ('".$this->activeuser."','$panelusername','$domainname','$homedir','$status',$quota,'".$this->miscconfig['defaultwebserverips']."')",'domain add to ehcp db');
			$s = $this->executeQuery("insert into " . $this->conf['domainstable']['tablename'] . " (reseller,panelusername,domainname,homedir,status,diskquota,webserverips) values ('" . $this->activeuser . "','$panelusername','$domainname','$homedir','$status',$quota,'$webserverips')", 'domain add to ehcp db'); # multiserver ayri bir fonksiyona yazıldı.
			#$success=$success && ($s=$this->addDaemonOp("syncdomains",'xx','','','sync apache ')); # sync'ing of all domains disabled upon each add domain, because it is time consuming for large domains&files, complains occured from users. only newly added domain is sync'ed in "add daemondomain"
			$success = $success && ($s = $this->addDaemonOp("syncdns", '', '', '', 'sync dns'));
			$success = $success && ($s = $this->addFtpUserDirect($panelusername, $ftpusername, $ftppassword, $this->conf['vhosts'] . "/$ftpusername", $upload, $download, $quota, $domainname, 'default', false, $status));
			$success = $success && ($s = $this->addPanelUserDirect($panelusername, $paneluserpass, 1, 5, 0, 1, 1, $quota, 0, 10, '', $email, $status));
			$success = $success && ($s = $this->addDaemonOp("daemondomain", "add", $domainname, $homedir, 'domain info&ftp')); # since, adddaemonop only returns True or false, this construct is True, but above, execute may return other thing...

			$sayi = $this->recordcount($this->conf['domainstable']['tablename'], '');
			$msg = "Hi EHCP User,<br>The domain of $domainname has successfully been set up and configured in your EHCP panel at " . $this->singleserverip . ".<br>";
			$msg .= $this->url;

			$msguser = $msg;
			$subj = "Domain Added to Panel";
			$this->infotoadminemail($msg, $subj);

			if ($email <> '')
				$this->sendEmail($email, $subj, $msguser);

			return $this->ok_err_text(
				$success,
				"Domain $domainname was successfully added to the panel.",
				"Failed to add domain $domainname to the panel."
			);
			# rollback of operations when not succeeded, is not implemented. it just displays an error message... to be fixed later..
		} else {
			return $this->ok_err_text(
				$success,
				"Domain $domainname was successfully added to the panel.",
				'Domain ' . $domainname . ' is invalid!'
			);
		}
	}

	function is_valid_domain_name($domain_name)
	{
		return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
			&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)); //length of each label
	}

	function adjustDomainname($domainname)
	{ # if user enters www. at start of domain, it is trimmed, since www. is already added in necessarry ehcp operations,  otherwise, it becomes, www.www.domainname...
		$domainname = trim($domainname);
		$domainname = str_replace(array("\\", "http://", "www.", "https://"), array('', '', '', ''), $domainname); # replace accidental www.'s
		$domainname = removeInvalidChars($domainname, 'domainname');
		return $domainname;
	}

	function addDomainDirectToThisPaneluser($domainname, $ftpusername, $sync = True)
	{
		// This is ONE of the base functions for adding a domain to the panel...

		// Better validation
		$domainname = $this->adjustDomainname($domainname);
		$success = inputValid($domainname, 'domainname');

		if ($success) {

			if (
				!$this->afterInputControls(
					"adddomaintothispaneluser",
					array(
						"domainname" => $domainname,
					)
				)
			)
				return false;

			$success = True;

			/*
			   domain path will be like: /var/www/vhosts/ftpusername/domain.com
			   /var/www/vhosts/ftpusername/domain.com will be stored as homedir in domains table,
			   one user will may have multiple domains with single ftp acount.
			   to achive this, i must implement domain add  to an existing ftp acount.
			   */

			$this->output .= "Adding domain: $domainname";
			$homedir = $this->conf['vhosts'] . "/$ftpusername/$domainname";

			# *** Burada eklenirken, hangi ftp hesabina eklendigi yazilacak, ftpusername, bu sayede o ftp silinirken kontrol edilecek.
			# $success=$this->executeQuery("insert into ".$this->conf['domainstable']['tablename']." (reseller,panelusername,domainname,homedir,status) values ('".$this->activeuser."','".$this->activeuser."','$domainname','$homedir','$this->status_active')",'domain add to ehcp db');
			if ($webserverips == '' and $this->miscconfig['activewebserverip'] <> '')
				$webserverips = $this->miscconfig['activewebserverip']; # another ip is active for webserver

			$success = $this->executeQuery("insert into " . $this->conf['domainstable']['tablename'] . " (reseller,panelusername,domainname,homedir,status,apachetemplate,dnstemplate,webserverips) values ('" . $this->activeuser . "','" . $this->activeuser . "','$domainname','$homedir','$this->status_active','$apachetemplate','$dnstemplate','$webserverips')", 'domain add to ehcp db');
			$success = $success && $this->addDaemonOp("daemondomain", "add", $domainname, $homedir, 'domain info&ftp'); # since, adddaemonop only returns True or false, this construct is True, but above, execute may return other thing...

			if ($sync) { # in multiple domain add, no sync is done for all domains one by one...  a single syncAll is called at end of all domains..
				#$success=$success && ($s=$this->addDaemonOp("syncdomains",'xx','','','sync apache '));
				$success = $success && ($s = $this->addDaemonOp("syncdns", '', '', '', 'sync dns'));
			}
			$success = $success && ($s = $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync apache '));

			$sayi = $this->recordcount($this->conf['domainstable']['tablename'], '');
			$msg = "Hi EHCP Admin,<br>The domain of $domainname has successfully been set up and configured in your EHCP panel at " . $this->singleserverip . ".<br>";
			$msg .= "Control Panel URL: " . $this->url . "<br>Total domain count in this server: $sayi";

			$msguser = "Hi EHCP Force User,<br>The domain of $domainname has successfully been set up and configured in the EHCP panel at http://" . $this->singleserverip;
			$subj = $msg;
			$this->infotoadminemail($msg, "Domain Added to Panel");

			if ($email <> '')
				$this->sendEmail($email, "Domain Added to Panel", $msguser);

			return $this->ok_err_text(
				$success,
				"Domain $domainname was successfully added to the panel.",
				"Failed to add domain $domainname to the panel."
			);
			# rollback of operations when not succeeded, is not implemented. it just displays an error message... to be fixed later..
		} else {
			return $this->ok_err_text(
				$success,
				"Domain $domainname was successfully added to the panel.",
				'Domain ' . $domainname . ' is invalid!'
			);
		}
	}


	function deleteDomain()
	{
		global $domainname, $confirm, $confirm2; # gets domainname from _GET
		$this->getVariable(array("domainname", "confirm", 'confirm2'));
		
		$listrowcount = 1000;

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		if (!$this->isuserpermited('deletedomain', $domainname))
			return false; # is this reseller of that domain ?


		$domaininfo = $this->domaininfo = $this->getDomainInfo($domainname);

		$domainpaneluser = $domaininfo['panelusername'];
		$homedir = $domaininfo['homedir'];

		$panelusercount = $this->recordcount($this->conf['domainstable']['tablename'], "panelusername='$domainpaneluser'");
		if ($domainpaneluser == $this->activeuser)
			$panelusercount = 2; # dont delete your self acount...

		if ($domaininfo['reseller'] <> $this->activeuser and !$confirm2) {
			$inputparams = array(
				array('domainname', 'hidden', 'default' => $domainname),
				array('confirm2', 'hidden', 'default' => '1'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br><b>This domain currently belongs to the reseller account of: " . $domaininfo['reseller'] . ". Are you sure you want to delete this domain: \"" . $domainname . "\"?</b><br>"
				. inputform5($inputparams);

			return True;
		}
		
		$ftpInfo = $this->getField('ftpaccounts', 'ftpusername', "panelusername='" . $domainpaneluser . "' and type='default'");

		if (!$confirm) {
			if ($panelusercount > 0) {
				// Subtract one to mimick deleting the domain to see if the user will be removed.
				$panelusercount--;
			}
			$filter = "domainname='$domainname'";
			$this->output .= $this->sayinmylang("areyousuretodelete") . $domainname . " <br><br>Email/ftp users: <br> ";
			#$this->output.="$domainname domain ftp user List: ".$this->tablolistele3_5_4($this->conf['ftpuserstable']['tablename'],$baslik,array("ftpusername","domainname"),$filter,$sirala,$linkimages,$linkfiles,$linkfield,$listrowstart,$listrowcount,false);
			$domaininfo = $this->getDomainInfo($domainname);
			$this->output .= "ftp username: " . $this->multiserver_get_domain_ftpusername($domaininfo) . "<br>";

			$this->output .= "<br> $domainname domain email user List: " . $this->tablolistele3_5_4($this->conf['emailuserstable']['tablename'], $baslik, $this->conf['emailuserstable']['listfields'], $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount, false);
			$this->output .= "<br>Domain File Count: " . executeprog("ls -l " . $this->conf['vhosts'] . "/" . $ftpInfo . "/$domainname/httpdocs | wc -l");

			$this->listTable("<br><br>Subdomains related to this domain: ", 'subdomainstable', "domainname='$domainname'", true);

			if ($panelusercount == 0)
				$this->output .= "<br>Panel user to be deleted: " . $domainpaneluser;
			$this->output .= "<br><br>Databases related to this domain: " . $this->tablolistele3_5_4($this->conf['mysqldbstable']['tablename'], $baslik, array("dbname"), $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount, false);
			$this->output .= "<br>Email Forwardings: " . $this->tablolistele3_5_4($this->conf['emailforwardingstable']['tablename'], $baslik, $this->conf['emailforwardingstable']['listfields'], $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount, false);

			$inputparams = array(
				array('domainname', 'hidden', 'default' => $domainname),
				array('confirm', 'hidden', 'default' => '1'),
				array('confirm2', 'hidden', 'default' => '1'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);


			$this->output .= "<br><font size=+1><b>Are you sure to delete all these?</b></font><br>"
				. inputform5($inputparams);

			return True;
		}

		$success = $this->deleteDomainDirect($domainname);
		$this->output .= "<br><a href='?'>Goto Home</a><br>";
		$this->deselectdomain2(); # deselect domain, if any...
		$ret = $this->ok_err_text($success, "Domain \"$domainname\" was successfully deleted and removed from the panel.", "Failed to delete domain \"$domainname\".");

		$this->showSimilarFunctions('domain');
		return $ret;

	}

	function multiserver_delete_ftp_direct($domaininfo)
	{
		# to be coded.
		# burda tüm domain degil,sadece ftpaccount silinmeli.
		# delete ftp account that is located on remote , maybe also local in future.

		$domainname = trim($domaininfo['domainname']);
		$webserverips = trim($domaininfo['webserverips']);
		$ftpusername = trim($domaininfo['ftpusername']);
		$ftpserver = trim($domaininfo['ftpserver']);

		$this->debugecho(__FUNCTION__ . ":" . print_r2($domaininfo) . " domain:$domainname, $webserverips, $ftpusername ", 3, false);

		if ($domainname == '' or $ftpusername == '') {
			$this->output .= __FUNCTION__ . ": domainname or ftpusername is empty.returning.";
			return True;
		}

		$this->output .= "<br>Deleting ftp user: $ftpusername<br>";

		$success = True;

		$qu = "delete from ftpaccounts where ftpusername='$ftpusername'";
		$success = $success && $this->multiserver_executequery($qu, $ftpserver);

		$qu = "delete from ftpaccounts where ftpusername='$ftpusername' and domainname='$domainname'"; # delete from local mysql, if there is any record (until all code is clean)
		$success = $success && $this->multiserver_executequery($qu, 'localhost');

		# burada bitek remote daemon kaldı.
		$homedir = $this->multiserver_getfield($this->conf['ftpuserstable']['tablename'], "homedir", "ftpusername='$ftpusername' limit 1", $ftpserver);
		if ($homedir == "") {
			$homedir = $this->conf['vhosts'] . "/$ftpusername";
		}

		$success = $success && ($s = $this->add_daemon_op(array('op' => 'daemonftp', 'action' => 'delete', 'info' => "/etc/vsftpd_user_conf/$ftpusername", 'info3' => $ftpserver)));
		$success = $success && ($s = $this->add_daemon_op(array('op' => 'daemonftp', 'action' => 'delete', 'info' => $homedir, 'info3' => $ftpserver)));

		return $success;

	}

	function multiserver_query($q, $serverip, $logininfo = false)
	{
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		$this->debugecho(__FUNCTION__ . ': server:' . $serverip . ' logininfo:' . print_r2($logininfo), 3, false);


		if ($serverip == 'localhost')
			return $this->query($q);
		else {
			if (!$logininfo)
				$logininfo = array(
					'dbhost' => $serverip,
					'dbusername' => $this->dbusername,
					'dbpass' => $this->dbpass,
					'dbname' => $this->dbname
				);

			$uzak_conn = $this->connect_to_mysql($logininfo);
			return $this->query3($q, '', __FUNCTION__, $uzak_conn);
		}
	}

	function multiserver_executequery($q, $serverip, $logininfo = false)
	{
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.
		$this->debugecho(__FUNCTION__ . ': server:' . $serverip . ' logininfo:' . print_r2($logininfo), 3, false);

		if ($serverip == 'localhost')
			return $this->executeQuery($q);
		else {
			if (!$logininfo)
				$logininfo = array(
					'dbhost' => $serverip,
					'dbusername' => $this->dbusername,
					'dbpass' => $this->dbpass,
					'dbname' => $this->dbname
				);

			$uzak_conn = $this->connect_to_mysql($logininfo);
			return $this->executeQuery($q, '', __FUNCTION__, $uzak_conn);
		}
	}

	function multiserver_get_domain_ftpusername($domaininfo)
	{
		$q = "select ftpusername from " . $this->conf['ftpuserstable']['tablename'] . " where domainname='" . $domaininfo['domainname'] . "'";
		$res = $this->multiserver_query($q, $domaininfo['ftpserver']);
		return trim($res[0]['ftpusername']);
	}

	function deleteDomainDirect($domainname, $syncdomains = True)
	{
		$domainname = trim($domainname);
		$success = True;
		if ($domainname == '')
			return false;

		if (!$this->isuserpermited('deletedomain', $domainname))
			return false; # is this reseller of that domain ?
		$domaininfo = $this->domaininfo = $this->getDomainInfo($domainname);
		$panelusercount = $this->recordcount($this->conf['domainstable']['tablename'], "panelusername='$domainpaneluser'");

		if (is_array($domaininfo)) {
			// If domain exists, let's delete it!
			$this->last_deleted_domaininfo = $domaininfo; # used for rebuilding configs of servers of deleted domain . otherwise, the config of that server is not updated, resulting failure.

			#$domainpaneluser=$this->getField($this->conf['domainstable']['tablename'],"panelusername","domainname='$domainname'");
			$domainpaneluser = $domaininfo['panelusername'];
			$homedir = $domaininfo['homedir'];


			if ($domainpaneluser == $this->activeuser)
				$panelusercount = 2; # dont delete your self acount...

			if ($domaininfo['reseller'] <> $this->activeuser) { # inform domain reseller, if this is not  yours..
				$reseller = $this->query("select * from " . $this->conf['paneluserstable']['tablename'] . " where panelusername='" . $domaininfo['reseller'] . "'");
				$this->sendEmail($reseller[0]['email'], "Domain $domainname Deleted", "Hi EHCP Force User,<br><br>The domain of $domainname was deleted from the panel.<br>Server IP Address: " . $this->singleserverip);
			}

			$domfilt = " where domainname='$domainname'";
			$ftpusername = $this->multiserver_get_domain_ftpusername($domaininfo);
			$this->output .= "<br>Deleting Domain: $domainname<br>";

			# Get list of subdomains that belong to the domain
			$arr = $this->query("select * from " . $this->conf['subdomainstable']['tablename'] . $domfilt);
			if ($arr) {
				foreach ($arr as $dom) {
					$subdomainFullStr[] = $dom['subdomain'] . "." . $domainname;
				}
			}

			$success = $success && $s = $this->executeQuery("delete from " . $this->conf['domainstable']['tablename'] . $domfilt . " limit 1", 'Deleting domain from ehcp db');
			$success = $success && $s = $this->executeQuery("delete from " . $this->conf['emailuserstable']['tablename'] . $domfilt, 'Deleting emails from ehcp db');
			$success = $success && $s = $this->executeQuery("delete from " . $this->conf['emailforwardingstable']['tablename'] . $domfilt, 'Deleting email forwardings from ehcp db');
			$success = $success && $s = $this->executeQuery("delete from " . $this->conf['subdomainstable']['tablename'] . $domfilt, 'delete subdomains of domain');
			$success = $success && $s = $this->executeQuery("delete from " . $this->conf['customstable']['tablename'] . $domfilt, 'delete custom http/dns settigns of domain');

			$domaininfo['ftpusername'] = $ftpusername;
			if ($ftpusername <> '')
				$this->multiserver_delete_ftp_direct($domaininfo); # code for multiple servers.

			$success = $success && ($s = $this->add_daemon_op(array('op' => 'daemondomain', 'action' => 'delete', 'info' => $domainname, 'info2' => $homedir, 'info3' => $ftpserver)));

			if ($syncdomains) {
				$success = $success && $this->add_daemon_op(array('op' => 'sync_server_services'));
			}

			$ret = $this->query("select id from " . $this->conf['mysqldbstable']['tablename'] . $domfilt);
			foreach ($ret as $d)
				$success = $success && $this->deleteDB($d['id']);

			// Delete any let's encrypt certificates
			if (isset($subdomainFullStr) && is_array($subdomainFullStr)) {
				$subdomainFullStr[] = $domainname;
				$success = $success && $this->removeLetsEncryptCertificates($subdomainFullStr);
			}

			// Remove all password protected directories for the domain
			$this->removePasswordProtectedDirByDomain($domainname);

			// Remove any custom ssl certs if they exist for the domain
			$this->deleteSSLCustomKeys($domainname);

			$panelusercount--;
		}

		// Delete account if there are no more domains
		if ($panelusercount == 0) { # if no other domain left with this panel user, then delete panel user too, except for ehcp special reseller account
			$success = $this->removePanelUser($domainpaneluser);
		}

		return $success;

	}

	function removeLetsEncryptCertificates($domains)
	{
		$success = true;
		foreach ($domains as $domain) {
			// Get subdomains as well
			$subdoms = $this->getSubDomains("domainname = '" . $domain . "'");
			if (is_array($subdoms) && count($subdoms) > 0) {
				foreach ($subdoms as $subdom) {
					$fullDomain = $subdom["subdomain"] . "." . $subdom["domainname"];
					$this->runCommandInDaemon("/usr/local/bin/certbot delete --cert-name " . $fullDomain);
					$this->bashDelete("/etc/letsencrypt/live/" . $fullDomain, true);
					$this->bashDelete("/etc/letsencrypt/archive/" . $fullDomain, true);
					$this->bashDelete("/etc/letsencrypt/renewal/" . $fullDomain . ".conf", true);
				}
			}

			// Actually run the delete operation from certbot to handle any additional cleanup
			$this->runCommandInDaemon("/usr/local/bin/certbot delete --cert-name " . $domain);

			/* INFO */
			// Below operations shouldn't be needed, but I'll keep them here just in case //
			/* END INFO */

			// echo "\nDeleting Let's Encrypt SSL certificates for domain/subdomain " . $dom['domainname'] . " from letsencrypt live folder.\n";
			// Delete the certificates
			$this->bashDelete("/etc/letsencrypt/live/" . $domain, true);

			// echo "\nDeleting Let's Encrypt SSL certificates for domain/subdomain " . $dom['domainname'] . " from letsencrypt archive folder.\n";
			// Delete the archive information
			$this->bashDelete("/etc/letsencrypt/archive/" . $domain, true);

			// Delete the renewal information as well to prevent suffix issues
			// https://community.letsencrypt.org/t/re-prevent-0001-xxxx-certificate-suffixes/83824/6 
			$this->bashDelete("/etc/letsencrypt/renewal/" . $domain . ".conf", true);
		}
		return $success;
	}

	function deselectdomain2()
	{ # de-selects a domain
		$this->setselecteddomain('', false);
	}

	function deselectdomain()
	{ # de-selects a domain and displays home
		$this->deselectdomain2();
		$this->displayHome();
		return True;
	}


	function setselecteddomain($dom, $checkdomain = True)
	{ # only sets selecteddomain..
		if ($checkdomain) {
			if (!$this->exist($this->conf['domainstable']['tablename'], "domainname='$dom'"))
				return false; # if there is no such domain, dont set it..
			$this->requireMyDomain($dom);
		}

		$_SESSION['selecteddomain'] = $dom;
		$this->selecteddomain = $dom;
		return True;
	}

	# i split some operations to different functions to be understandable...
	# choosing a domain is possible in many ways, these functions do this..

	function chooseDomainGoNextOp()
	{ # sets selected domain, then redirect to new op..
		global $domainname, $nextop;
		$this->getVariable(array("domainname", "nextop"));
		$this->requireMyDomain($domainname);
		$this->setselecteddomain($domainname);
		$this->redirecttourl("?op=$nextop");
	}

	function chooseDomain2($opname)
	{
		# displays list of domains available, then let user choose one, then goes to next op..

		// Get list of domains
		$mydomains = $this->getMyDomains('', ' ORDER BY domainname ASC');

		// Print domain and panelusername columns depending on user type
		$printColumns = array('domainname');
		if ($this->isadmin() || $this->isreseller) {
			$printColumns[] = 'panelusername';
			if ($this->miscconfig['webservertype'] == "nginx") {
				$printColumns[] = 'nginxtemplate';
			}else{
				$printColumns[] = 'apache2template';
			}
		}

		// Output
		if (!$mydomains)
			$this->output .= "<br>You have no domains yet. Add domain first, then use this function.";
		else
			$this->output .= "<br><br>Select A Domain:" . $this->listSelector($arr = $mydomains, $print = $printColumns, $link = "?op=choosedomaingonextop&nextop=$opname&domainname=", $linfield = 'domainname', 'selectDomainNameLink', array('panelusername', 'nginxtemplate', 'apache2template'));
		return True;
	}

	function listselectdomain()
	{
		$this->chooseDomain2('');
		$this->showSimilarFunctions('domain');
	}

	function chooseDomain($opname, $dom = '')
	{
		# gives previously selected domain, or domain entered from url... or if nothing exists, shows a list of domains, let user select it...

		#$this->debugtext(__FUNCTION__."called.. dom: $dom, this->selecteddomain: $this->selecteddomain");

		if ($dom <> '') { # is there anything passed in parameter or entered in url ?
			$domainname = $dom;
			$this->setselecteddomain($domainname);
		}

		if (!$domainname)
			$domainname = $this->selecteddomain; # is there an already selected domain ?
		if (!$domainname) {
			$this->chooseDomain2($opname); # if none, let user select domain..
			$this->showexit();
		}

		$this->requireMyDomain($domainname); # ensure this is my domain..

		return $domainname;
	}

	function showexit($template = '')
	{
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4);
		$this->show($template);
		exit;
	}

	function addNewScript()
	{
		global $name, $url, $scriptdirtocopy, $homepage, $description;
		$this->getVariable(array('name', 'url', 'scriptdirtocopy', 'homepage', 'description'));

		$this->requireAdmin();

		if (!$url) {
			$inputparams = array(
				array('name', 'lefttext' => 'Script Name:'),
				array('url', 'lefttext' => 'Script URL:'),
				array('scriptdirtocopy', 'lefttext' => 'Script Directory to Copy:'),
				array('homepage', 'lefttext' => 'Script Homepage:'),
				array('description', 'textarea', 'lefttext' => 'Script Description:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);
			$this->output .= inputform5($inputparams);

		} else {
			$q = "insert into scripts (scriptname,filetype,fileinfo,scriptdirtocopy,homepage,description) values ('$name','directurl','$url','$scriptdirtocopy','$homepage','$description')";
			$success = $this->executeQuery($q);

			$msg = "Hi EHCP Admin,<br><br>A new easy installation script was added:<br><br>Name: $name<br>URL: $url<br>Home Page: $homepage<br>Description: $description";
			$this->infotoadminemail($msg, "New Easy Install Script Addded", True);
			return $this->ok_err_text($success, "Successfully added new easy installation script.", '');
		}


	}

	function doDownloadAllscripts()
	{
		$this->requireAdmin();
		$this->addDaemonOp('downloadallscripts', $action, $info);
	}

	function resolveDomainToIP($domainName)
	{
		return gethostbyname($domainName);
	}

	function addScript()
	{
		$alanlar = array("domainname", "scriptname", 'directory', 'iamsure', 'dbname', 'dbusername', 'dbuserpass', 'title', 'admin_email');
		foreach ($alanlar as $al)
			global $$al;
		$degerler = $this->getVariable($alanlar);

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		if (!$scriptname) {
			$linkimages = array("images/install.jpg");
			$linkfiles = array("?op=addscript&domainname=$domainname");
			$linkfield = 'scriptname';
			$filter = '';
			$this->output .= "Select script to install: <br>(Please note that, some script url's may be out of date in future. So, in that case, you may be unable to install these. In that case, update list of these scripts from ehcp.net site, or manually fix that in phpmyadmin)<br>" . $this->tablolistele3_5_4("scripts", $baslik, array("scriptname", array('homepage', 'link_newwindow'), 'description'), $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount = 100);
			if ($this->isadmin()) {
				$this->output .= "<hr><a href='?op=updateinstallscriptsql'>Update Easy Install Script Definitions</a><br><br>";
			}
			return True;
		}

		if ((strpos($scriptname, "ehcp itself") !== false) or (strpos($scriptname, "ehcp webmail") !== false)) {
			if (!$this->isadmin())
				$this->errorTextExit("Only admins can install EHCP.  You should be able to access webmail at: http://webmail.yourdomain.com");
			$this->output .= "<br><b>Note that, by copying EHCP or webmail files, you put your sensitive EHCP files in that domain's direction.  So, be careful</b>. Also note that, only the EHCP web GUI will run from this new directory.  The daemon will continue to run from the original installation directory.<br>";
		}

		if ((!$directory and !$iamsure) or !$admin_email or !$title) {
			$inputparams = array(
				"directory",
				"title",
				array('admin_email', 'input', 'lefttext' => 'Admin Email Address:'),
				array('iamsure', 'checkbox', 'lefttext' => 'Check here if you are sure to install to root of your domain', 'secenekyazisi' => 'Confirm', 'default' => '1'),
				array('op', 'hidden', 'default' => 'addscript'),
				array('domainname', 'hidden', 'default' => $domainname),
				array('scriptname', 'hidden', 'default' => $scriptname),
				array('comment', 'comment', 'default' => '<br><hr>Please create a new MySQL database & user account for this installation. (Required)<br>'),
				array('dbname', 'righttext' => ''),
				array('dbusername', 'righttext' => ''),
				array('dbuserpass', 'password_with_generate', 'righttext' => ''),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Enter a directory name (under your domain, below the httpdocs folder) where the script files will be installed: <br>For example, enter 'test' to install the script files into http://www.$domainname/test.<br>
		The directory will be created if it doesn't already exist.<br>
		Leave the directory field empty (and check the checkbox below) to install the script files into the root of your domain (http://www.$domainname).&nbsp; <b>If the folder already exists, some of your files may be overwritten!</b><br>Also, please enter a title / name for your installation of the script.&nbsp; This will be used as the script's sitewide title name.<br>"
				. inputform5($inputparams);

			return True;
		}

		$success = True;

		// Validation
		$directory = removeInvalidChars($directory, "directory");
		$title = removeInvalidChars($title, "strictTitle");
		$title = $this->escape($title);

		if ($dbname and $dbusername and $dbuserpass and $success) {
			$success = $success && $this->addMysqlDbDirect($myserver, $domainname, $dbusername, $dbuserpass, $dbuserhost, $dbname, $adduser = True);
		} else {
			$success = false;
		}

		if ($success) {
			$str = "Will copy/add script <b>[$scriptname]</b> to domain: <b>[$domainname]</b> directory: <b>[$directory]</b> <br><br>Click <big><a target=_blank href=http://www.$domainname/$directory>here</a></big> to see that dir after 30-60 seconds....<br>You may tail -f /var/log/ehcp.log for script operations...<br><br><p class='success'>Your login will be as follows:<br><br>Login:&nbsp; ";
			if (strtolower($scriptname) != "phpcoin") {
				$str .= "admin<br>Password: 12345678!";
			} else {
				$str .= "webmaster<br>Password: $dbuserpass";
			}
			$str .= "<br><br><b>Please change these settings for security purposes ASAP!</b></p>";

			$this->output .= $str;

			// get mysql host
			if (!$myserver)
				$myserver = $_SESSION['myserver'];
			if (!$myserver)
				$myserver = $this->getMysqlServer('', false, __FUNCTION__); # get mysql server info..

			$installInfoNeeded = $directory . $this->ehcpForceSplitString . $dbname . $this->ehcpForceSplitString . $dbusername . $this->ehcpForceSplitString . $dbuserpass . $this->ehcpForceSplitString . $myserver['host'] . $this->ehcpForceSplitString . $title . $this->ehcpForceSplitString . $admin_email;

			$this->addDaemonOp("installscript_force", $scriptname, $domainname, $installInfoNeeded, $opname);
			$q = "insert into scripts_log (scriptname,dir,panelusername,domainname,link) values ('$scriptname','$directory','$this->activeuser','$domainname','http://www.$domainname/$directory')";
			$this->executeQuery($q);

			$q = "select * from scripts where scriptname='$scriptname'";
			$bilgi = $this->query($q);
			$bilgi = $bilgi[0];


			# burası aslında önemli, kurulan scriptleri daha düzenli yapmak lazım. 
			$this->showSimilarFunctions('easyinstall');
			$msg = "Hi EHCP Admin,<br><br>User $this->activeuser has queued the installation of the following easy install script:<br>$scriptname<br><br>It will be installed for the domain of \"$domainname\" in the following directory:<br>$directory<br>";
			$this->infotoadminemail($msg, "New Easy Installation Script Queued", false);
		} else {
			return $this->ok_err_text($success, "Failed to create MySQL database and user account.&nbsp; The database and user must be created before an easy installation script can install.", '');
		}

	}

	function addEmailUser()
	{
		#  modified upon suggestion of sextasy@discardmail.com, thanks, nice contribution.

		global $domainname, $mailusername, $password, $quota, $autoreplysubject, $autoreplymessage;
		$this->getVariable(array('domainname', 'mailusername', 'password', 'quota', 'autoreplysubject', 'autoreplymessage')); # this gets variables from _GET or _POST
		if ($this->isuserlimitexceeded('maxemails'))
			return false;
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;

		if (!$mailusername) {
			if (!$this->beforeInputControls("addemail", array()))
				return false;

			$query = "select subdomain from subdomains where domainname = '$domainname'";
			$res = $this->query($query);

			$select_domainname = "<select name='domainname'><option value='$domainname' selected='selected'>$domainname</option>";

			if (!empty($res)) {
				foreach ($res as $row) {
					$select_domainname .= "<option value='" . $row['subdomain'] . ".$domainname'>" . $row['subdomain'] . ".$domainname</option>";
				}
			}
			$select_domainname .= '</select>';

			$inputparams = array(
				array('mailusername', 'righttext' => "@" . $select_domainname . "<br>(do not write @domainname)", 'lefttext' => 'Email Address:'),
				array('quota', 'lefttext' => 'Email Quota (MB)', 'default' => '25'),
				array('password', 'password_with_generate', 'lefttext' => 'Email Account Password:'),
				array('autoreplysubject', 'default' => $autoreplysubject, 'righttext' => 'Leave emtpy to disable autoreply', 'lefttext' => 'Auto Reply Subject:'),
				array('autoreplymessage', 'textarea', 'default' => $autoreplymessage, 'lefttext' => 'Auto Reply Message:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Add new email account:<br>"
				. inputform5($inputparams);
		} else {
			$success = $this->addEmailDirect($mailusername, $domainname, $password, $quota, $autoreplysubject, $autoreplymessage);
		}
		$this->showSimilarFunctions('email');
		return $success;
	}


	function addEmailDirect($mailusername, $domainname, $password, $quota, $autoreplysubject, $autoreplymessage)
	{
		$success = True;
		$this->noEmpty($domainname);

		if (!$this->afterInputControls("addemail", array('email' => $mailusername . '@' . $domainname, 'mailusername' => $mailusername)))
			return false;

		$this->output .= "Adding email user:";
		$mailusername = getFirstPart($mailusername, '@'); # make sure does not include @ sign
		$email = "$mailusername@$domainname";
		
		// Check to see if email address being added has already been setup as a forwarder (if so, return an error)
		$SQL = "SELECT * FROM " . $this->conf['emailforwardingstable']['tablename'] . " WHERE source='$email';";
		$rs = $this->query($SQL);
		if (count($rs) > 0) {
			return $this->ok_err_text(false, "", 'Email address ' . $email . ' is already configured as a forwarded email setup.  Use a different address or delete the existing forwarder first.');
		}

		$success = $success && $this->executeQuery("insert into " . $this->conf['emailuserstable']['tablename'] . " (panelusername,domainname,mailusername,email,password,quota,status,autoreplysubject,autoreplymessage) values ('$this->activeuser','$domainname','$mailusername','$email',encrypt('$password','ehcp'),$quota,'" . $this->status_active . "','$autoreplysubject','$autoreplymessage')", 'add mail user');
		#$success=$success && $this->executeQuery("insert into ".$this->conf['emailuserstable']['tablename']." (panelusername,domainname,mailusername,email,password,quota,status,autoreplysubject,autoreplymessage) values ('$this->activeuser','$domainname','$mailusername','$email',encrypt('$password','ehcp'),'".($quota*1000000)."','".$this->status_active."','$autoreplysubject','$autoreplymessage')",'add mail user');
		$this->adjustEmailAutoreply($email, $autoreplysubject, $autoreplymessage);


		$this->sendEmail($email, "Welcome", "EHCP hopes you enjoy your new email account!"); # bir mail gonderiyor. zira yoksa dizin olusmuyor, pop3 login calismiyor..
		return $this->ok_err_text($success, "Successfully created email account.", 'Failed to add email account.');
	}

	function echoln($str)
	{
		if ($this->commandline)
			echo "\n" . $str;
		else
			$this->output .= "<br>$str";
	}

	function echoln2($str)
	{
		if ($this->commandline)
			echo "\n" . $str . "\n";
		else
			$this->output .= "<br>$str<br>";
	}


	function addPanelUserDirect($panelusername, $password, $maxdomains, $maxemails, $maxpanelusers, $maxftpusers, $maxdbs, $quota, $master_reseller, $maxsubdomains, $name = '', $email = '', $status = 'active')
	{
		foreach (array('maxdomains', 'maxemails', '$maxpanelusers', '$maxftpusers', '$maxdbs', '$quota', '$master_reseller', '$maxsubdomains') as $var) # if sent empty, default 0
			if (!$$var)
				$$var = 0;


		$panelusername = trim($panelusername);
		$reseller = $this->activeuser;
		$userinfo = $this->getPanelUserInfo('', $reseller);
		if ($reseller == '')
			$reseller = 'admin'; # in applyfordomain, activeuser is empty..

		// Handle master reseller parameter 
		// It can only be set by admin to prevent reseller's resellers from creating another master reseller account
		if ($reseller != 'admin') {
			if ($master_reseller == 1) {
				$master_reseller = 0;
				$this->output .= "<p class=\"error\">Only the root server administrator can set the master reseller parameter on an account.&nbsp; Your selection was ignored.</p>";
			}
		}
		if (!isset($master_reseller) || empty($master_reseller)) {
			$master_reseller = 0;
		}

		if ($reseller != 'admin' && $userinfo['master_reseller'] != 1) {
			// Normal resellers cannot create other resellers
			if ($maxpanelusers != 0) {
				$maxpanelusers = 0;
				$this->output .= "<p class=\"error\">Only master reseller accounts can create other reseller accounts!&nbsp; The account created will be a normal user who cannot create other panel users.</p>";
			}
		}

		$logintable = $this->conf['logintable'];

		if ($logintable['passwordfunction'] == '') {
			$pass = "'$password'";
		} elseif ($logintable['passwordfunction'] == 'encrypt') {
			$pass = "encrypt('$password','ehcp')";
		} else {
			$pass = $logintable['passwordfunction'] . "('$password')";
		}

		$query = "insert into panelusers
	(panelusername,password,maxdomains,maxemails,maxpanelusers,maxftpusers,maxdbs,name,email,quota,reseller,status,master_reseller,maxsubdomains)
	values
	('$panelusername',$pass,$maxdomains,$maxemails,$maxpanelusers,$maxftpusers,$maxdbs,'$name','$email',$quota,'$reseller','$status',$master_reseller,$maxsubdomains)";

		$this->debugecho("<br>debug:query: $query <br>", 1, false);

		if ($email <> '') {
			$msguser = "Hi EHCP Force User,<br><br>Your account was successfully setup.<br><br>Your Account Information:<br><br>Panel Username: $panelusername<br>Password: $password<br>Server Control Panel URL: <a href=\"http://$this->singleserverip/\">http://" . $this->singleserverip . "</a><br><br>" . $this->miscconfig['messagetonewuser'];
			$this->sendEmail($email, "Web Hosting Account Information", $msguser);
			$this->output .= "<br>Email sent to new panel user.<br>";
		}

		return $this->executeQuery($query, 'add panel user: ' . $panelusername);
	}

	function addPanelUser()
	{

		$tb = $this->conf['paneluserstable'];
		foreach ($tb['insertfields'] as $insertfield) {
			if (is_array($insertfield))
				$insertfield = $insertfield[0];
			global $$insertfield;
		}

		#global $panelusername,$password,$maxdomains,$maxemails,$maxpanelusers,$maxftpusers,$maxdbs,$quota;
		#$this->getVariable(array("panelusername","password","maxdomains","maxemails","maxpanelusers",'maxftpusers',"quota")); # this gets variables from _GET or _POST
		$ret = $this->getVariable($tb['insertfields']);
		$this->debugecho(print_r2($ret), 2, false);

		if (!$this->beforeInputControls("addpaneluser"))
			return false;


		if (!$panelusername) {
			$this->output .= "Adding new panel user or reseller:<br>"
				. inputform5ForTableConfig($tb, array(array('op', 'hidden', 'default' => __FUNCTION__)), $this->isadmin());
		} else {
			if (!$this->afterInputControls("addpaneluser", array('panelusername' => $panelusername, 'email' => $email)))
				return false;
			$this->output .= "Adding user:<br>";
			$success = $this->addPanelUserDirect($panelusername, $password, $maxdomains, $maxemails, $maxpanelusers, $maxftpusers, $maxdbs, $quota, $master_reseller, $maxsubdomains, $name, $email);
			$this->ok_err_text($success, "Added panel user successfully.", 'Failed to add panel user.');
		}
		$this->showSimilarFunctions('panelusers');
		return $success;
	}

	function addPanelUserWithHostingPlan()
	{
		$this->requireReseller();

		if (!$this->beforeInputControls("addpaneluser"))
			return false;

		global $plan_id, $_insert, $client_name, $client_email, $client_panelusername, $client_password;
		$tb = $this->conf['paneluserstable'];
		$success = true;

		$this->getVariable(array("_insert", "plan_id", "client_name", "client_email", "client_panelusername", "client_password"), true);

		// Build select list from saved hosting templates
		$SQL = "SELECT * FROM " . $this->conf['hosting_plans_table']['tablename'] . " WHERE panelusername='" . $this->activeuser . "' ORDER BY name;";
		$rs = $this->query($SQL);

		if (count($rs) > 0) {
			// Build hosting plans key value array
			foreach ($rs as $r) {
				$hostingPlans[$r["id"]] = $r["name"];
			}
			if (!$_insert) {
				$inputparams = array(
					array('client_name', 'input', "lefttext" => "Full Name:", 'default' => $client_name),
					array('client_email', 'input', "lefttext" => "Email Address:", 'default' => $client_email),
					array('client_panelusername', 'input', "lefttext" => "Username:", 'default' => $client_panelusername),
					array('client_password', 'input', "lefttext" => "Password:", 'default' => $client_password),
					array('plan_id', 'select', 'lefttext' => 'Hosting Plan', 'secenekler' => $hostingPlans),
				);
				$this->output .= "<p>Add New Client with the Following Pre-Defined Hosting Plan:</p>" . inputform5($inputparams) . "<br>";
			} else {
				if (isset($errors)) {
					unset($errors);
				}

				/* Validation Section */
				if (empty($client_name)) {
					$errors[] = "You must provide the client's full name.";
				}

				if (empty($client_email) || stripos($client_email, "@") == false) {
					$errors[] = "The client you are adding must have a valid email address.";
				}

				if (empty($client_panelusername)) {
					$errors[] = "You must provide the client's login username used for the panel.";
				}

				if (empty($client_password)) {
					$errors[] = "You must provide the client's password used for the panel.";
				}

				if (!$this->hasValueOrZeroAndIsNumeric($plan_id)) {
					$errors[] = "Please select client's hosting plan.";
				}

				// Output errors
				if (isset($errors) && is_array($errors)) {
					$errStr = $this->errArrayToStr($errors);
					unset($errors);
					$this->errorTextExit($errStr);
				}

				// Passed validation, so now go and load the appropriate settings from the hosting plan template...

				$SQL = "SELECT * FROM " . $this->conf['hosting_plans_table']['tablename'] . " WHERE id='$plan_id';";
				$rs = $this->query($SQL);
				if (count($rs) == 1) {
					$maxdomains = $rs[0]["max_domains"];
					$maxemails = $rs[0]["max_emails"];
					$maxpanelusers = $rs[0]["max_panelusers"];
					$maxftpusers = $rs[0]["max_ftpusers"];
					$maxdbs = $rs[0]["max_ftpusers"];
					$quota = $rs[0]["quota"];
					$master_reseller = $rs[0]["master_reseller"];
					$maxsubdomains = $rs[0]["max_subdomains"];
				} else {
					$success = false;
					$this->ok_err_text($success, "", 'Failed to load hosting plan from the database.');
				}

				if (!$this->afterInputControls("addpaneluser", array('panelusername' => $client_panelusername, 'email' => $client_email)))
					return false;
				$this->output .= "Adding user:<br>";
				$success = $this->addPanelUserDirect($client_panelusername, $client_password, $maxdomains, $maxemails, $maxpanelusers, $maxftpusers, $maxdbs, $quota, $master_reseller, $maxsubdomains, $client_name, $client_email);
				$this->ok_err_text($success, "Added panel user successfully.", 'Failed to add panel user.');

			}
		} else {
			$success = false;
			$this->ok_err_text($success, "", 'No existing hosting plans have currently been defined.');
		}
		$this->showSimilarFunctions('panelusers');
		return $success;
	}

	function addFtpUserDirect($panelusername, $ftpusername, $password, $home, $upload, $download, $quota, $domainname = '', $type = '', $isSpecialHome = false, $status = '')
	{
		$success = True;

		if ($status == '')
			$status = $this->status_active; # default is active,
		# for pureftpd: $qu="INSERT INTO ftpd ( reseller, User , status , Password , Uid , Gid , Dir , ULBandwidth , DLBandwidth , comment , ipaccess , QuotaSize , QuotaFiles,domainname)	VALUES ('".$this->activeuser."','$ftpusername', '1', MD5( '$password' ) , '2001', '2001', '$home', '$upload', '$download', '', '*', '$quota', '0','$domainname');";
		if (strstr($home, $this->ehcpdir) !== false)
			return $this->errorText($this->ehcpdir . " location cannot be used for security purposes!");

		$this->debugecho2("$panelusername,$ftpusername,$password,$home,$upload,$download,$quota,$domainname,$type,$isSpecialHome", 1);
		$panelusername = trim($panelusername);
		$ftpusername = trim($ftpusername);
		if ($isSpecialHome)
			$homedir = $home; # home dir is only inserted if different from default of /var/www/vhosts/ftphome....
		$homedir = securefilename($homedir);

		$qu = "INSERT INTO ftpaccounts ( reseller, panelusername, domainname, ftpusername, password, homedir, type,status)	VALUES ('" . $this->activeuser . "','$panelusername','$domainname','$ftpusername', password('$password'),'$homedir','$type','$status')";
		$success = $this->executeQuery($qu, 'add ftp user : ' . $ftpusername);
		$success = $success && $this->addDaemonOp('daemonftp', 'add', $home);

		return $success;
	}

	function deleteFtpUserDirect($ftpusername)
	{
		$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($this->activeuser);
		$inClause = $this->generateMySQLInClause($userHasAccessToTheseChildrenUsers);

		if (trim($ftpusername) == '')
			return True;
		$success = True;

		$homedir = $this->getField($this->conf['ftpuserstable']['tablename'], "homedir", "ftpusername='$ftpusername' limit 1");
		if ($homedir == "") {
			$homedir = $this->conf['vhosts'] . "/$ftpusername";
		}
		$success = $success && $this->addDaemonOp("daemonftp", "delete", "/etc/vsftpd_user_conf/$ftpusername", '', " ftp delete info for user specific config file(/etc/vsftpd_user_conf/$ftpusername)");

		$this->output .= "<br>Deleting ftp user: $ftpusername<br>";
		$qu = "delete from " . $this->conf['ftpuserstable']['tablename'] . " where ftpusername='$ftpusername'";
		if (!$this->isadmin()) {
			$qu .= " AND panelusername " . $inClause;
		}
		$qu .= " limit 1";
		$success = $success && $this->executeQuery($qu, ' delete ftp user from ehcp db');

		# WHY WOULD YOU DELETE THE FILES --- THEY COULD BE OWNED BY OTHERS
		if ($this->miscconfig['forcedeleteftpuserhomedir'] <> '') {
			$success = $success && $this->addDaemonOp("daemonftp", "delete", $homedir, '', ' ftp delete info ');
		}
		return $success;
	}

	function getSubdomainInfoById($id)
	{
		$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($this->activeuser);
		$inClause = $this->generateMySQLInClause($userHasAccessToTheseChildrenUsers);

		$sql = "select * from " . $this->conf['subdomainstable']['tablename'] . " where id='" . $id . "'";
		if (!$this->isadmin()) {
			$sql .= " AND panelusername " . $inClause;
		}

		$data = $this->query($sql);

		if (count($data) == 0) {
			return $this->errorText("This subdomain does NOT belong to your account.");
		}

		$data = $data[0];

		return $data;
	}

	function delSubDomain()
	{
		global $id, $_insert, $yes, $no;
		$this->getVariable(array("_insert", 'yes', 'no'));

		$success = True;

		$data = $this->getSubdomainInfoById($id);
		if ($data === false) {
			return false;
		}

		$this->debugecho2(print_r2($data), 1);

		$domainname = $data['domainname'];
		$subdomain = $data['subdomain'];
		$ftpusername = $data['ftpusername'];
		$homedir = $data['homedir'];
		$fullSubdomainStr = $subdomain . "." . $domainname;

		if (!$_insert) {
			$inputparams = array(
				array('op', 'hidden', 'default' => __FUNCTION__),
				array('submit', 'submit', 'default' => 'Yes')
			);

			$this->output .= "<p><br>Are you sure you want to delete the subdomain of \"" . $fullSubdomainStr . "\"?" . inputform5($inputparams);
		} else {

			$successText = "Successfully removed the subdomain configuration from the database.";

			$success = $success && $this->deleteFtpUserDirect($ftpusername);
			$success = $success && $this->executeQuery("delete from " . $this->conf['subdomainstable']['tablename'] . " where id=$id");
			if ($this->miscconfig['forcedeletesubdomainfiles'] == 'Yes') {
				$success = $success && $this->addDaemonOp("daemondomain", "delsubdomain", $subdomain, $homedir, 'subdomain delete');
				$successText .= " Files located in the subdomain home directory were deleted.";
			} else {
				$successText .= " Files located in the subdomain home directory were NOT deleted. The files can be deleted manually via FTP.";
			}
			$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');

			$letsEncSubs[] = $subdomain . "." . $domainname;
			$success = $success && $this->removeLetsEncryptCertificates($letsEncSubs);

			$this->ok_err_text($success, $successText, "Error deleting subdomain");
			$this->showSimilarFunctions('subdomainsDirs');
			return $success;
		}
	}

	function addSubDomain()
	{
		global $subdomain, $domainname;
		$this->getVariable(array('subdomain', "domainname"));
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;


		$filter = "domainname='$domainname'";

		if ($subdomain) {

			$subdomain = removeInvalidChars($subdomain, "subdomainname");

			if ($subdomain) {
				$count = $this->recordcount($this->conf['subdomainstable']['tablename'], "domainname='$domainname' and subdomain='$subdomain'"); # todo: this should be moved to existscontrol
				if ($count > 0)
					return $this->errorText("Subdomain already exists.");
				$domaininfo = $this->domaininfo = $this->getDomainInfo($domainname);

				$homedir = $domaininfo['homedir'] . "/httpdocs/subdomains/$subdomain";
				$webserverips = $domaininfo['webserverips'];

				$qu = "insert into " . $this->conf['subdomainstable']['tablename'] . " (panelusername,subdomain,domainname,homedir,webserverips)values('$this->activeuser','$subdomain','$domainname','$homedir','$webserverips')";
				$success = $success && $this->executeQuery($qu, $opname);

				#$success=$success && $this->addDaemonOp("daemondomain","addsubdomain",$domainname,$homedir,'add subdomain');
				$success = $success && $this->add_daemon_op(array('op' => 'daemondomain', 'action' => 'addsubdomain', 'info' => $subdomain, 'info2' => $domainname, 'info3' => $homedir));
				$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');
				if ($success) {
					$sub1 = "http://" . $subdomain . "." . $domainname;
					$sub2 = "http://www." . $subdomain . "." . $domainname;
					$this->output .= "<br>You may access <a target=_blank href='$sub1'>$sub1</a> and <a  target=_blank href='$sub2'>$sub2</a> in a few seconds..<br>";
				}
			} else {
				$success = false;
			}
			$this->ok_err_text($success, "Successfully added subdomain!", "Error adding subdomain.");
		} else {
			if (!$this->beforeInputControls("addsubdomain", array()))
				return false;
			$inputparams = array(array('subdomain', 'righttext' => ".$domainname", 'lefttext' => 'Subdomain:'));
			$this->output .= "Add new subdomain:<br>(Do not include \"www.\" or \"http://\")" . inputform5($inputparams);
		}
		$this->showSimilarFunctions('subdomainsDirs');
		return $success;

	}

	function showSimilarFunctions($func)
	{
		# the text here may be read from a template
		$out1 = "<p>Similar / Related $func Functions:</p>";

		switch ($func) {
			case 'ftp':
				$out = "<a href='?op=addftptothispaneluser'>Add FTP Account</a><a href='?op=addftptothispaneluser'>Remove FTP Account</a><a href='?op=addsubdirectorywithftp'>Add SubDirectory FTP Account Under Domain</a><a href='?op=addsubdomainwithftp'>Add subdomain with ftp</a><a href='net2ftp' target=_blank>WebFtp (Net2Ftp)</a><a href='?op=addcustomftp'>Add Custom FTP Account (Admins Only)</a><a href='?op=removecustomftp'>Remove Custom FTP Account (Admin Only)</a><a href='?op=listallftpusers'>List All FTP Users</a> ";
				if ($this->isadmin()) {
					$out = "<a href='?op=addftpuser'>Add VHOST Directory FTP Account</a>" . $out;
				}
				break;
			case 'easyinstall':
				$out = "<a href='?op=addscript'>Easy Install More Packages</a>";
				break;
			case 'mysql':
				$out = "<a href='?op=domainop&amp;action=listdb'>List / Delete Mysql Databases</a><a href='?op=addmysqldb'>Add Mysql Database &amp; User</a><a href='?op=addmysqldbtouser'>Create MySQL Database and Assign to Existing Database User</a><a href='?op=dbadduser'>Add MySQL User to Existing Database</a><a href='/phpmyadmin' target=_blank>phpMyAdmin</a>";
				break;
			case 'email':
				$out = "<a href='?op=listemailusers'>List Email Users / Change Passwords</a><a href='?op=addemailuser'>Add Email User</a><a href='?op=emailforwardings'>List Email Forwarders</a><a href='?op=addemailforwarding'>Add Email Forwarder</a><a href='?op=bulkaddemail'>Bulk Add Email</a><a href='?op=editEmailUserAutoreply'>Edit Email Autoreply</a><a href='webmail' target=_blank>Webmail (Squirrelmail)</a>";
				break;
			case 'domain':
				$out = "<a href='?op=addDomainToThisPaneluser'>Add Domain To My Account</a><a href='?op=adddomaineasy'>Easy Add Domain (with separate ftpuser)</a><a href='?op=adddomain'>Normal Add Domain (Separate ftp&panel user)</a><a href='?op=bulkadddomain'>Bulk Add Domain</a><a href='?op=adddnsonlydomain'>Add DNS-Only Hosting</a><a href='?op=adddnsonlydomainwithpaneluser'>Add DNS-Only Hosting with Separate Paneluser</a><a href='?op=addslavedns'>Make Domain a DNS Slave</a><a href='?op=removeslavedns'>Remove DNS Slave</a><a href='?op=adddomaineasyip'>Easy Add Domain to Different IP</a><a href='?op=setactiveserverip'>Set Active Webserver IP</a><a href='?op=listselectdomain'>List Domains</a>";
				break;
			case 'redirect':
				$out = "<a href='?op=editdomainaliases'>Edit Domain Aliases</a>";
				break;
			case 'options':
				$out = "
	<br><a href='?op=options&edit=1'>Edit/Change Options</a><br>
	<br><a href='?op=changemypass'>Change My Password</a>
	<br><a href='?op=listpanelusers'>List/Add Panelusers/Resellers</a>
	<br><a href='?op=dosyncdns'>Sync Dns</a>
	<br><a href='?op=dosyncdomains'>Sync Domains</a><br>
	<br><a href='?op=dosyncftp'>Sync Ftp (for non standard home dirs)</a><br>
	<hr><a href='?op=advancedsettings'>Advanced Settings</a><br><br>
	<br><a href='?op=dofixmailconfiguration'>Fix Mail Configuration<br>Fix ehcp Configuration</a> (This is used after changing ehcp mysql user pass, or if you upgraded from a previous version, in some cases)<br>
	<br><br><a href='?op=dofixapacheconfigssl'>Fix Apache Configuration with SSL and Non-SSL Mixed Mode</a>(use with caution,may be risky)<br><br>
	<br><br><a href='?op=dofixapacheconfigsslonly'>Fix Apache Configuration with SSL ONLY</a>(use with caution,may be risky)<br><br>
	<br><a href='?op=dofixapacheconfignonssl'>Fix apache Configuration without ssl</a><br>
	<br><a href='?op=dofixapacheconfignonssl2'>Fix apache Configuration without ssl, way2</a> - use this if first wone does not work. this deletes custom apache configurations, if any<br>
	<br>
	<hr>
	<a href='?op=listservers'>List/Add Servers/ IP's</a><br>
	<hr>

	Experimental:
	<br><a href='?op=donewsyncdns'>New Sync Dns - Multiserver</a>
	<br><a href='?op=donewsyncdomains'>New Sync Domains - Multiserver</a><br>
	<br><a href='?op=multiserver_add_domain'>Multiserver Add Domain</a>
	<hr>

	";
				break;
			case 'customhttpdns':
				$out = "<a href='?op=customhttp'>List Custom HTTP</a><a href='?op=addcustomhttp'>Add Custom HTTP</a><a href='?op=customdns'>List Custom DNS</a><a href='?op=addcustomdns'>Add Custom DNS</a><a href='?op=custompermissions'>List Custom Permissions</a><a href='?op=addcustompermission'>Add Custom Permissions</a>";
				break;
			case 'subdomainsDirs':
				$out = "<a href='?op=subdomains'>List Subdomains</a><a href='?op=addsubdomain'>Add Subdomains</a><a href='?op=addsubdomainwithftp'>Add Subdomain with FTP</a><a href='?op=addsubdirectorywithftp'>Add Subdirectory with FTP</a>";
				break;
			case 'HttpDnsTemplatesAliases':
				$out = "<a href='?op=editdnstemplate'>Edit DNS Template for this Domain</a><a href='?op=editapachetemplate'>Edit " . $this->miscconfig['webservertype'] . " Template for this Domain</a><a href='?op=editdomainaliases'>Edit Aliases for this Domain</a>";
				break;
			case 'panelusers':
				$out = "<a href='?op=listpanelusers'>List All Panelusers/Clients</a><a href='?op=resellers'>List Resellers</a><a href='?op=addpaneluser'>Add Paneluser/Client/Reseller</a><a href='?op=addpaneluserwithpredefinedplan'>Add Paneluser/Client/Reseller from Hosting Plan Template</a>";
				break;
			case 'server':
				$out = "<a href='?op=listservers'>List Servers/IP's</a><a href='?op=addserver'>Add Server</a><a href='?op=addiptothisserver'>Add IP to This Server</a><a href='?op=setactiveserverip'>Set Active Webserver IP</a><a href='?op=addcronjob'>Add Cronjob</a><a href='?op=removecronjob'>Remove Cronjob</a>";
				break;
			case 'backup':
				$out = "<a href='?op=dobackup'>Backup</a><a href='?op=dorestore'>Restore</a><a href='?op=listbackups'>List Backups</a><a href='?op=addremotebackup'>Schedule Remote Backup</a><a href='?op=removeremotebackup'>Edit or Remove Scheduled Remote Backup</a>";
				break;
			case 'vps':
				$out = "<a href='?op=vps'>VPS Home</a><a href='?op=add_vps'>Add New VPS</a><a href='?op=settings&group=vps'>VPS Settings</a><a href='?op=vps&op2=other'>Other VPS Options</a>";
				break;
			case 'pagerewrite':
				$out = "<a href='?op=pagerewrite'>Page Rewrite Home</a><a href='?op=pagerewrite&op2=add'>Add Page Rewrite</a>";
				break;
			case 'custompermissions':
				$out = "<a href='?op=custompermissions'>List Custom Permissions</a><a href='?op=addcustompermission'>Add Custom Permissions</a>";
				break;
			case 'global_templates':
				$out = "<a href='?op=manageglobalwebtemplates'>Manage Global Webserver Templates</a>";
				break;
			default:
				$out = "(internal ehcp error) This similar function is not defined in " . __FUNCTION__ . " : ($func)";
				$out1 = '';
				break;
		}

		if ($func != "options") {
			$this->output .= "<br><br><div class=\"similarFunctions\">$out1" . $out . "<br></div>";
		} else {
			$this->output .= "<br><br>$out1" . $out . "<br>";
		}
	}

	function validate_ip_address($ip)
	{
		if (validateIpAddress($ip) === false)
			$this->errorTextExit("The IP address entered is wrong. Here's a working IP address example: 85.98.112.34.  You entered this IP Address: $ip");
	}

	function add_ip_to_this_server()
	{
		global $ip;
		$this->getVariable(array('ip'));

		if ($ip) {
			$this->validate_ip_address($ip);
			$q = "insert into servers (servertype,ip,accessip) values ('apache2','$ip','localhost')";
			$this->executeQuery($q);
			$this->output .= 'Ip added.';
		} else {
			$this->output .= inputform5(
				array(
					array('ip', 'lefttext' => 'IP Address:')
				)
			);
		}

		$this->showSimilarFunctions('server');
	}

	function checkFtpLimit($ftpusername)
	{
		if (
			!$this->afterInputControls(
				"addftpuser",
				array(
					"ftpusername" => $ftpusername
				)
			)
		)
			$this->showexit();
	}

	function addFtpUser()
	{
		$this->requireAdmin(); // Requires admin since this really shouldn't be used... it should be assigned to current panel user by default... this is an advanced function that probably shouldn't be used

		$op = "addftpuser";
		global $domainname, $ftpusername, $password, $quota, $upload, $download;
		$this->getVariable(array("domainname", "ftpusername", "password", "quota", "upload", "download"));
		$homedir = $this->conf['vhosts'] . "/$ftpusername";
		$success = True;

		if ($ftpusername) {

			if (
				!$this->afterInputControls(
					"addftpuser",
					array(
						"ftpusername" => $ftpusername
					)
				)
			)
				return false;

			$this->output .= "Adding ftp user with homedir $homedir:";

			$success = $success && $this->addFtpUserDirect($this->activeuser, $ftpusername, $password, $homedir, $quota, $upload, $download, $domainname);
			$this->ok_err_text($success, "FTP account was added successfully.", "Failed to add FTP account. "); # all these functions are to reduce code needed...
		} else {
			if (!$this->beforeInputControls('addftpuser'))
				return false;
			$inputparams = array(
				array('ftpusername', 'lefttext' => 'FTP Username'),
				array('password', 'password_with_generate'),
				array('quota', 'lefttext' => 'Quota (MB)', 'default' => 100),
				array('upload', 'lefttext' => 'Upload Bandwidth (KB/s)', 'default' => 1000),
				array('download', 'lefttext' => 'Download Bandwidth (KB/s)', 'default' => 1000),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "<br><br>Adding FTP User:<br>(Under " . $homedir . "ftpusername)<br>"
				. inputform5($inputparams);
		}
		$this->showSimilarFunctions('ftp');
		return $success;

	}

	function addFtpToThisPaneluser()
	{ # add an ftp user freely under your master ftp directory
		global $ftphomedir, $ftpusername, $password, $_insert;
		$this->getVariable(array("ftphomedir", "ftpusername", "password", "_insert"));
		$selfftp = $this->getSelfFtpAccount($returnto = __FUNCTION__); # ftp account for this panel user is with type field=default in ftpaccounts table
		$masterhome = $this->conf['vhosts'] . "/$selfftp";
		$success = True; # must be at start, to keep good formating.

		if ($_insert) {
			if (
				!$this->afterInputControls(
					"addftpuser",
					array(
						"ftpusername" => $ftpusername
					)
				)
			)
				return false;

			$homedir = "$masterhome/$ftphomedir";
			$this->output .= "Adding ftp user:";
			$quota = $upload = $download = 200;
			$success = $success && $this->addFtpUserDirect($this->activeuser, $ftpusername, $password, $homedir, $quota, $upload, $download, $domainname, '', True); # this also prepares that dir..

			if ($success) {
				$this->output .= "<br>You may now access $homedir via FTP.<br>";
				$msguser = "Hi EHCP Force User,<br><br>A FTP account with the home directory of $homedir has been added to your account on server IP: " . $this->singleserverip . "<br><br>Your FTP Username: $ftpusername<br>FTP Password: $password";
				if ($email <> '')
					$this->sendEmail($email, "FTP Account Created", $msguser);
			}

			$this->ok_err_text($success, "FTP account was added successfully.", "Failed to create FTP account.");

		} else {
			if (!$this->beforeInputControls('addftpuser'))
				return false;
			$inputparams = array(
				array('ftphomedir', 'lefttext' => "$masterhome/", 'righttext' => 'Home Directory of FTP User (leave empty to default to your home directory)'),
				array("ftpusername", 'lefttext' => 'FTP Username:'),
				array("password", "password_with_generate", 'lefttext' => 'Password:'),
				array('email', 'righttext' => ' of person the subdirectory is setup for..(will be informed)', 'lefttext' => 'Email Address:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Enter FTP Information Here: <br>" . inputform5($inputparams);
		}

		$this->showSimilarFunctions("ftp");
		return $success;
	}

	function add_ftp_special()
	{ # add an ftp user freely under /home/ dir freely
		global $ftphomedir, $ftpusername, $password, $_insert;
		$this->getVariable(array("ftphomedir", "ftpusername", "password", "_insert"));
		$this->requireAdmin();

		$masterhome = '/home';
		$success = True; # must be at start, to keep good formating.

		if ($_insert) {
			if (
				!$this->afterInputControls(
					"addftpuser",
					array(
						"ftpusername" => $ftpusername
					)
				)
			)
				return false;

			$homedir = "$masterhome/$ftphomedir";
			$this->output .= "Adding ftp user:";
			$quota = $upload = $download = 200;
			$success = $success && $this->addFtpUserDirect($this->activeuser, $ftpusername, $password, $homedir, $quota, $upload, $download, $domainname, '', True); # this also prepares that dir..

			if ($success) {
				$this->output .= "<br>You may access $homedir by ftp from now on.<br>";
				$msguser = "Hi EHCP Force User,<br><br>A FTP account with the home directory of $homedir has been added to your account on server IP: " . $this->singleserverip . "<br><br>Your FTP Username: $ftpusername<br>FTP Password: $password";
				if ($email <> '')
					$this->sendEmail($email, "FTP Account Created", $msguser);
			}

			$this->ok_err_text($success, "FTP account was added successfully.", "Error add ftp");

		} else {
			if (!$this->beforeInputControls('addftpuser'))
				return false;

			$inputparams = array(
				array('ftphomedir', 'lefttext' => "$masterhome/", 'righttext' => 'Home Directory of FTP User'),
				array("ftpusername", 'lefttext' => 'FTP Username:'),
				array("password", "password_with_generate", 'lefttext' => 'Password:'),
				array('email', 'righttext' => ' of the person the subdirectory is setup for. User will receive credentials for this FTP account via email.', 'lefttext' => 'Email Address:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Enter FTP Information Here: <br>" . inputform5($inputparams);
		}

		$this->showSimilarFunctions("ftp");
		return $success;
	}

	function addSubDirectoryWithFtp()
	{
		global $subdirectory, $domainname, $ftpusername, $password, $email;
		$this->getVariable(array('subdirectory', "domainname", "ftpusername", "password", 'email'), True);

		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$filter = "domainname='$domainname'";
		$success = True; # must be at start, to keep good formating.

		if ($subdirectory) {

			if (
				!$this->afterInputControls(
					"addftpuser",
					array(
						"ftpusername" => $ftpusername
					)
				)
			)
				return false;

			$homedir = $this->getField($this->conf['domainstable']['tablename'], "homedir", $filter) . "/httpdocs/$subdirectory";
			$this->output .= "Adding ftp user:";
			$quota = $upload = $download = 200;
			$success = $success && $this->addFtpUserDirect($this->activeuser, $ftpusername, $password, $homedir, $quota, $upload, $download, $domainname, '', True); # this also prepares that dir..

			if ($success) {
				$sub1 = "http://" . $domainname . "/$subdirectory";
				$sub2 = "http://www." . $domainname . "/$subdirectory";
				$this->output .= "<br>You may access <a href='$sub1'>$sub1</a> and <a href='$sub2'>$sub2</a> in a few seconds..<br>";

				$msguser = "Hi EHCP Force User,<br><br>The subdirectory of $sub1 or $sub2 was setup in the EHCP panel on server IP: " . $this->singleserverip . "<br><br>Your FTP Username: $ftpusername<br>FTP Password: $password";
				if ($email <> '')
					$this->sendEmail($email, "Subdirectory with FTP Account Created", $msguser);
			}

			$this->ok_err_text($success, "Subdirectory was successfully configured, and a FTP account was created which can access this subdirectory.", "Error adding subdirectory.");
		} else {
			if (!$this->beforeInputControls('addftpuser'))
				return false;
			$inputparams = array(
				array('subdirectory', 'lefttext' => "www.$domainname/"),
				array("ftpusername", 'lefttext' => 'FTP Username:'),
				array("password", "password_with_generate", 'lefttext' => 'Password:'),
				array('email', 'righttext' => ' of person the subdirectory is setup for.', 'lefttext' => 'Email Address:'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Enter Subdirectory Information Here:<br>" . inputform5($inputparams);
		}
		$this->showSimilarFunctions("ftp");
		$this->showSimilarFunctions('subdomainsDirs');
		return $success;

	}

	function addSubDomainWithFtp()
	{

		global $subdomain, $domainname, $ftpusername, $password, $email;
		$this->getVariable(array('subdomain', "domainname", "ftpusername", "password", 'email'), True);
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$success = True;

		$filter = "domainname='$domainname'";

		if ($subdomain) {
			$subdomain = removeInvalidChars($subdomain, "subdomainname");

			if ($subdomain) {

				if (
					!$this->afterInputControls(
						"addftpuser",
						array(
							"ftpusername" => $ftpusername
						)
					)
				)
					return false;

				$count = $this->recordcount($this->conf['subdomainstable']['tablename'], "domainname='$domainname' and subdomain='$subdomain'");
				if ($count > 0)
					return $this->errorText("Subdomain already exists.");

				$homedir = $this->getField($this->conf['domainstable']['tablename'], "homedir", $filter) . "/httpdocs/subdomains/$subdomain";
				$qu = "insert into " . $this->conf['subdomainstable']['tablename'] . " (panelusername,subdomain,domainname,homedir,ftpusername,password)values('$this->activeuser','$subdomain','$domainname','$homedir','$ftpusername',md5('$password'))";
				$success = $success && $this->executeQuery($qu, $opname);
				$success = $success && $this->addDaemonOp("syncdomains", 'xx', $domainname, '', 'sync domains');

				$this->output .= "Adding ftp user:";
				$quota = $upload = $download = 100;
				$success = $success && $this->addFtpUserDirect($this->activeuser, $ftpusername, $password, $homedir, $quota, $upload, $download, $domainname, 'subdomain', True);

				if ($success) {
					$sub1 = "http://" . $subdomain . "." . $domainname;
					$sub2 = "http://www." . $subdomain . "." . $domainname;
					$this->output .= "<br>You may access <a href='$sub1'>$sub1</a> and <a href='$sub2'>$sub2</a> in a few seconds..<br>";

					$msguser = "Hi EHCP Force User,<br><br>The subdomain of $sub1 or $sub2 was setup in the EHCP panel on server IP: " . $this->singleserverip . "<br><br>Your FTP Username: $ftpusername<br>FTP Password: $password";
					if ($email <> '')
						$this->sendEmail($email, "Subdomain and FTP Account Created", $msguser);
				}

				$this->ok_err_text($success, "Successfully added subdomain and FTP account for access to this subdomain.", "Error adding subdomain and FTP account.");
			} else {
				$success = false;
				$this->ok_err_text($success, "Successfully added subdomain and FTP account for access to this subdomain.", "Error adding subdomain and FTP account.");
			}
		} else {
			if (!$this->beforeInputControls('addftpuser'))
				return false;
			if (!$this->beforeInputControls('addsubdomainwithftp'))
				return false;
			$inputparams = array(
				array('subdomain', 'righttext' => ".$domainname", 'lefttext' => 'Subdomain:'),
				array("ftpusername", 'lefttext' => 'FTP Username for Subdomain Access:'),
				array("password", "password_with_generate", 'lefttext' => 'Password:'),
				array('email', 'righttext' => ' of person the subdomain is setup for.'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= "Enter Subdomain Here:<br>(\"www.\" will be automatically added in front of the subdomain, so please don't include it!)" . inputform5($inputparams);
		}
		$this->showSimilarFunctions("ftp");
		$this->showSimilarFunctions('subdomainsDirs');
		return $success;

	}

	function subDomains()
	{
		global $domainname;
		$this->getVariable(array("domainname"));

		if ($dom <> '')
			$domainname = $dom;
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		#$filter="panelusername='$this->activeuser'";
		$filter = "domainname='$domainname'";

		if ($this->selecteddomain <> '')
			$filter .= " and domainname='$this->selecteddomain' ORDER BY subdomain ASC";
		$this->listTable("Subdomains", 'subdomainstable', $filter);
		$this->output .= "<br> <a href='?op=addsubdomain'>Add Subdomain</a>";
		$this->showSimilarFunctions('subdomainsDirs');

	}

	function listDomains($dom = '', $filt = '')
	{
		global $domainname;
		$this->getVariable(array("domainname"));
		if ($dom <> '')
			$domainname = $dom;

		if (!$domainname) {
			$linkimages = array('images/edit.gif', 'images/incele.jpg', 'images/delete1.jpg', 'images/openinnew.jpg');
			$linkfiles = array('?op=editdomain', '?op=selectdomain', '?op=deletedomain', "target=_blank href='?op=redirect_domain");
			$linkfield = 'domainname';
			$filter = $this->applyGlobalFilter($filt);
			#$this->output.="<hr>filtre: $filter</hr>";
			$this->output .= "<div align=center>Domain List: " .
				$this->tablolistele3_5_4($this->conf['domainstable']['tablename'], $baslik, $this->conf['domainstable']['listfields'], $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount) . "</div>";
		} else {
			$this->listemailusers($domainname);
		}
	}

	function listemailusers($dom = '')
	{ # listemailusers
		global $domainname;
		$this->getVariable(array("domainname"));

		if ($dom <> '')
			$domainname = $dom;
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		# this ensures the ownership of domain

		#$filter="domainname='$domainname'";
		$filter = "domainname REGEXP '" . $domainname . "(,|$)'"; #  modified upon suggestion of sextasy@discardmail.com

		$this->output .= "$domainname domain email user List: ";
		$this->listTable("", "emailuserstable", $filter);
		$this->showSimilarFunctions('email');
	}

	function listftpusers($dom = '')
	{
		global $domainname;
		$this->getVariable(array("domainname"));
		if ($dom <> '')
			$domainname = $dom; # parametre ile verilmisse listele...
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);

		# this ensures the ownership of domain

		$filter = "domainname='$domainname'";
		$linkfiles = array('?op=editftpuser', '?op=userop&action=ftpuserdelete');
		$linkimages = array('images/edit.gif', 'images/delete1.jpg');
		$linkfield = 'ftpusername';
		$this->output .= "<div align=center>$domainname domain ftp user List: <br>Note: deleting a user, will delete his files too..<br>"
			. $this->tablolistele3_5_4($this->conf['ftpuserstable']['tablename'], $baslik, array("domainname", "ftpusername"), $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount) . "</div>";

		$this->showSimilarFunctions("ftp");
	}

	function listallemailusers()
	{
		if (!$this->isadmin()) {
			$filter = "panelusername='$this->activeuser'";
			$filter = $this->applyGlobalFilter($filter);
		}

		$this->output .= "All domain's email users: ";
		$this->listTable("", "emailuserstable", $filter);
	}


	function listAllFtpUsers($filt = '')
	{
		$linkfiles = array('?op=editftpuser', '?op=userop&action=ftpuserdelete');
		$linkimages = array('images/edit.gif', 'images/delete1.jpg'); # edit passwordlu image eklenecek
		$linkfield = 'ftpusername';
		$filter = $this->applyGlobalFilter($filt);
		$this->debugtext("filter: $filter");
		$this->output .= "<div align=center>Ftp users: " .
			$this->tablolistele3_5_4($this->conf['ftpuserstable']['tablename'], $baslik, $this->conf['ftpuserstable']['listfields'], $filter, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount) . "</div>";

		$this->output .= "<br>Empty homedir means default location";

		$this->showSimilarFunctions("ftp");
	}

	function listpanelusers()
	{
		$table = $this->conf['paneluserstable'];
		$filter = $this->globalfilter;


		/*
			*      // DEFAULT
			*      'clickimages'=>array('images/edit.gif','images/delete1.jpg'),
				   'clickfiles'=>array('?op=editpaneluser','?op=deletepaneluser'),
		   */

		$actionLinkImages = $table['clickimages'];
		$actionLinkURLs = $table['clickfiles'];

		if ($this->isadmin()) {
			$actionLinkImages[] = 'images/openinnew.gif';
			$actionLinkURLs[] = '?op=impersonatepaneluser';
			$this->conf['paneluserstable']['listlabels'][] = 'Impersonate User';
		}

		$this->output .= "<div align=center>Panel Users" .
			$this->tablolistele3_5_4($table['tablename'], array('', '', '', '', '', 'Quota (MB*)'), $table['listfields'], $filter, $sirala, $actionLinkImages, $actionLinkURLs, $table['linkfield'], $listrowstart, $listrowcount)
			. "<a href='?op=addpaneluser'>Add Paneluser/Reseller</a></div>";
		$this->showSimilarFunctions('panelusers');
	}

	function resellers()
	{
		$table = $this->conf['paneluserstable'];
		$filter = "maxpanelusers>1";
		$filter = andle($filter, $this->globalfilter);
		$this->output .= "<div align=center>All/your panel users: " .
			$this->tablolistele3_5_4($table['tablename'], $baslik, $table['listfields'], $filter, $sirala, $table['clickimages'], $table['clickfiles'], $table['linkfield'], $listrowstart, $listrowcount) . "</div>";

		$this->output .= "<br><a href='?op=addpaneluser'>Add Reseller/Panel User</a> <br>";
		$this->showSimilarFunctions('panelusers');
	}

	function getPanelUserInfo($id = '', $panelusername = '')
	{
		if ($id) {
			$filt = "id=$id";
		} elseif ($panelusername <> '') {
			$filt = "panelusername='$panelusername'";
		} else {
			$filt = "panelusername='" . $this->activeuser . "'";
		}
		$ret = $this->query("select * from " . $this->conf['paneluserstable']['tablename'] . " where $filt ");
		return $ret[0];
	}

	function getParentsAndChildren($parent)
	{
		// This function recursively retrieves parent and direct children tree all the way down generations
		// Add parent to array
		$users[] = $parent;

		// Get subusers
		// admin is the only account where reseller and paneluser are both admin
		// So to prevent endless recursive loop, we must exclude accounts where panelusername = 'admin'
		$resellerUsers = $this->getPanelUsers("reseller='" . $parent . "' and panelusername != 'admin'");

		if (is_array($resellerUsers)) {
			foreach ($resellerUsers as $usr) {
				// For each child, go and get their children
				$users = array_merge($users, $this->getParentsAndChildren($usr['panelusername']));
			}
		}
		return $users;
	}

	function deletepaneluser()
	{
		global $id, $confirm;
		$this->getVariable(array('id', 'confirm'));

		$success = false;
		$paneluserinfo = $this->getPanelUserInfo($id);

		// Get user information
		$panelusername = $paneluserinfo['panelusername'];

		if ($panelusername == 'admin') {
			return $this->errorText('The admin account cannot be removed.');
		}

		// Variables
		$domInfoTableHTML = "";
		$domCounter = 0;
		$userInfoHTML = "<ul>";

		$usersToRemove = $this->getParentsAndChildren($panelusername);
		$userCanDeleteTheseUsers = $this->getParentsAndChildren($this->activeuser);

		if ($panelusername == $this->activeuser) {
			return $this->errorText('You cannot delete your own account. Please have an administrator do this for you.');
		}

		// Better security here... don't print what is available unless they have permission to delete that domain
		if (!in_array($panelusername, $userCanDeleteTheseUsers)) {
			return $this->errorText('You are not authorized to delete this account from the panel!');
		}

		// Build information variables used to inform user about everything that will happen from deleting this user
		foreach ($usersToRemove as $userToDelete) {

			// Get domains that belong to the user including domains that have been created under that reseller's account
			$usersDomains = $this->getDomains("panelusername='" . $userToDelete . "' or reseller='" . $userToDelete . "'");

			foreach ($usersDomains as $dom) {
				$domInfoTableHTML .= "<tr><td>" . $dom['domainname'] . "</td><td>" . $dom['panelusername'] . "</td><td>" . $dom['reseller'] . "</td></tr>";
				$domCounter++;
			}

			$domainsCollection[] = $usersDomains;

			// Append to our HTML output
			$userInfoHTML .= "<li>" . $userToDelete . "</li>";
		}
		$userInfoHTML .= "</ul>";

		if (!$confirm) {
			// Make sure user wants to delete this user and all of this good stuff!

			$outputHTML = "<h2>Delete User <span class='error'>" . $panelusername . "</span>?</h2><p>Also, if the user <span class='error'>" . $panelusername . "</span> is a reseller, <span class='error'>his users and sub-users will also be deleted</span>!</p>";
			$outputHTML .= "<p>The following users will be deleted:</p>" . $userInfoHTML;

			if ($domCounter > 0) {
				$outputHTML .= "<p>The following domains along with their associated subdomains, FTP accounts, and MySQL databases will be deleted:</p>
			<table style=\"border: 1px solid black; border-collapse:collapse; border-spacing: 0px;\">
				<tr>
					<th style=\"width: 150px;\">Domain</th>
					<th style=\"width: 150px;\">Panel User</th>
					<th style=\"width: 150px;\">Reseller</th>
				</tr>"
					. $domInfoTableHTML . "</table>";
			}
			$outputHTML .= "<br><br><input type='button' value='Yes' onclick=\"location.href='?op=deletepaneluser&id=$id&confirm=1'\"> &nbsp; <input type='button' value='Cancel' onclick=\"location.href='?op=deselectdomain'\"><br>";
			$this->output .= $outputHTML;
			return false;
		} else {
			// We need to delete each domain and its settings
			// Which will also delete the reseller's accounts
			foreach ($domainsCollection as $usersDomains) {
				for ($i = 0; $i < count($usersDomains); $i++) {
					$domainsToDeleteUnique[] = $usersDomains[$i]['domainname'];
				}
			}

			// Get unique domains only
			$domainsToDeleteUnique = array_unique($domainsToDeleteUnique);
			foreach ($domainsToDeleteUnique as $delDomain) {
				$this->deleteDomainDirect($delDomain, false);
			}

			// Remove panel users
			foreach ($usersToRemove as $userToDelete) {
				if (in_array($userToDelete, $userCanDeleteTheseUsers)) {
					$success = $this->removePanelUser($userToDelete);
				} else {
					return $this->errorText('You are not authorized to delete this account from the panel!');
				}
			}
			$this->ok_err_text($success, "Panel user " . $panelusername . " with account ID \"" . $id . "\" was deleted along with any children.", "Failed to remove panel user $panelusername.");
			$this->showSimilarFunctions('panelusers');
			$this->add_daemon_op(array('op' => 'sync_server_services'));
		}

		return $success;
	}

	function removePanelUser($user)
	{
		// Delete the user from the panelusers table
		$success = $this->executeQuery("delete from " . $this->conf['paneluserstable']['tablename'] . " where panelusername='$user'", 'Deleting User: ' . $user);

		// Delete FTP home dir
		$userFTPAccounts = $this->getUserFTPAccounts($user);
		foreach ($userFTPAccounts as $ftpAccount) {
			// Get FTP home directory
			$ftpHomeDirectory = $ftpAccount["homedir"];
			if (empty($ftpHomeDirectory) || is_null($ftpHomeDirectory)) {
				$ftpHomeDirectory = $this->vhostsdir . "/" . $ftpAccount["ftpusername"];
			}

			// Make sure ftpHomeDirectory is at least 3 levels deep on root filesystem
			$pieces = explode("/", $ftpHomeDirectory);
			if ($pieces != false) {
				$pieces = array_filter($pieces);
			}
			if (!empty($ftpHomeDirectory) && file_exists($ftpHomeDirectory) && $pieces != false && count($pieces) >= 3) {
				passthru2_silent("rm -rf '" . $ftpHomeDirectory . "'", true, true);
				passthru2_silent("rmdir '" . $ftpHomeDirectory . "'", true, true);
			}
		}

		// We need to remove FTP users for this account
		$success = $this->executeQuery("delete from " . $this->conf['ftpuserstable']['tablename'] . " where panelusername='$user'", 'Deleting FTP Users Belonging To Account: ' . $user);

		// We need to remove this users predefined hosting templates 
		$success = $this->executeQuery("delete from " . $this->conf['hosting_plans_table']['tablename'] . " where panelusername='$user'", 'Deleting Hosting Plan Templates Belonging To Account: ' . $user);

		// Sync FTP accounts
		$this->addDaemonOp('syncftp', '', '', '', 'sync ftp for nonstandard homes');

		return $success;
	}

	function editFtpUser()
	{
		global $ftpusername, $_insert, $status, $newpass, $newpass2;
		$this->getVariable(array('ftpusername', '_insert', 'status', 'newpass', 'newpass2'));
		$success = True;

		$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($this->activeuser);
		$inClause = $this->generateMySQLInClause($userHasAccessToTheseChildrenUsers);

		if (!$ftpusername) { # if no ftpusername given, learn it from domainname,
			$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
			$ftpusername = $this->getField($this->conf['ftpuserstable']['tablename'], "ftpusername", "domainname='$domainname' limit 1");
		}

		if (!$ftpusername) {
			$this->output .= "<br><b>This account does not have a dedicated FTP account! You may change your general FTP account <a href='?op=listallftpusers'>here</a>.</b><br>";
			return false;
		}

		if (!$this->isadmin() && $this->recordcount($this->conf['ftpuserstable']['tablename'], "panelusername " . $inClause . " AND ftpusername='" . $ftpusername . "'") == 0) {
			// Admin should be able to edit any MySQL user
			return $this->errorText("This FTP account is not owned by your account.");
		}

		$sql = "select * from " . $this->conf['ftpuserstable']['tablename'] . " where ftpusername='$ftpusername'";
		$ftp = $this->query($sql);

		if ($ftp && is_array($ftp)) {
			$ftp = $ftp[0];
		}

		if (!$_insert) {
			$inputparams = array();

			if ($ftp != false && !empty($ftp) && !empty($ftp['status'])) {
				$inputparams[] = array('status', 'select', 'lefttext' => 'Set Active/Passive', 'secenekler' => $this->statusActivePassive);
			}

			$inputparams[] = array('newpass', 'password', 'lefttext' => 'New Password: (leave empty for no change)');
			$inputparams[] = array('newpass2', 'password', 'lefttext' => 'Enter Password Again:');
			$inputparams[] = array('ftpusername', 'hidden', 'default' => $ftpusername, 'lefttext' => 'FTP Username:');
			$inputparams[] = array('op', 'hidden', 'default' => __FUNCTION__);

			$this->output .= "Changing FTP User: $ftpusername <br>" . inputform5($inputparams);

		} else {

			$filt = $this->applyGlobalFilter("ftpusername='$ftpusername'");
			$this->debugtext("filter: $filt");
			if ($newpass == '' and $newpass2 == '') {
				$passwordset = '';
			} else {
				if ($newpass <> $newpass2)
					$success = $this->errorText('The entered passwords do not match. Please try again.');
				$passwordset = ", password=password('$newpass') ";
			}

			$sql = "update " . $this->conf['ftpuserstable']['tablename'] . " SET ftpusername = '" . $ftpusername . "'  $passwordset";
			if (isset($status) && !empty($status)) {
				$sql .= ", status='$status'";
			}
			$sql .= " where $filt";
			$success = $success && $this->executeQuery($sql);

			// Only update status if we actually used it
			if (isset($status) && !empty($status)) {
				$success = $success && $this->addDaemonOp("daemonftp", $status, $this->conf['vhosts'] . '/' . $ftpusername);
			}

			$success = $success && $this->addDaemonOp('syncftp', '', '', '', 'sync ftp for nonstandard homes');

			$this->ok_err_text($success, 'FTP account was successfully modified.', 'Failed to modify FTP account.');
		}
		$this->showSimilarFunctions('ftp');
		return $success;

	}
	#==============================================================
	# =============== class utility functions insertrow, editrow,

	function editrow2($tabledesc, $where, $extra = array())
	{
		# 1den farki: inputform5 ile calisacak:, editpaneluser icinde kullanilacak,
		global $_insert;
		$this->getVariable(array("_insert"));
		$table = $this->conf[$tabledesc];

		$linkfield = $table['linkfield'];
		global $$linkfield; # get id..
		$this->getVariable(array($linkfield));
		$fields = $table['editfields']; # get other edit fields


	}

	function editrow($tabledesc, $where, $extra = array())
	{
		global $_insert;

		$op = $_REQUEST["op"];
		$this->getVariable(array("_insert"));
		$table = $this->conf[$tabledesc];

		$linkfield = $table['linkfield'];
		global $$linkfield; # get id..
		$this->getVariable(array($linkfield));

		$fields = $table['editfields']; # get other edit fields
		$editlabels = $table['editlabels'];
		$checkboxFields = $table['checkbox_fields'];
		if (!$this->hasValueOrZero($checkboxFields) || !is_array($checkboxFields)) {
			$checkboxFields = array();
		}

		if ($_insert) {
			if ($_SESSION["temp_id"] == $$linkfield) {
				foreach ($fields as $alan)
					global $$alan; # yukardakilerin hepsini global yap..
				$newvalues = $this->getVariable($fields); foreach ($checkboxFields as $cbField) {
					if (!$this->hasValueOrZero($$cbField)) {
						$$cbField = 0; // default it to false
					}
				}
				$success = $this->updatequery($table['tablename'], $fields, $newvalues, $linkfield . "=" . $$linkfield);
			} else {
				$success = false;
			}
			unset($_SESSION[$$linkfield]);
			return $this->ok_err_text($success, "Updated successfully.", "Update failed (editrow)");
		} else {
			$_SESSION["temp_id"] = $id;
			$values = array_values($this->alanal2($table['tablename'], $fields, $where));

			// Debug
			//echo "<p>Values: <br>" . print_r($values, true) . "<br>Edit Labels:<br>" . print_r($editlabels, true) . "<br>Fields:<br>" . print_r($fields, true) . "</p>";

			$inputparams = array();

			if (count($fields) == count($values) && count($fields) == count($editlabels)) {
				for ($i = 0; $i < count($fields); $i++) {
					if (!in_array($fields[$i], $checkboxFields)) {
						$inputparams[] = array($fields[$i], 'lefttext' => $editlabels[$i], 'default' => $values[$i]);
					} else {
						$inputparams[] = array($fields[$i], 'checkbox', 'lefttext' => $editlabels[$i], 'default' => '1', 'checked' => $values[$i]);
					}
				}
				if (count($inputparams) > 0 && is_array($inputparams)) {
					if ($this->hasValueOrZero($op)) {
						$inputparams[] = array('op', 'hidden', 'default' => $op);
					} else {
						$inputparams[] = array('op', 'hidden', 'default' => __FUNCTION__);
					}
					$inputparams[] = array($linkfield, 'hidden', 'default' => $$linkfield);
				}
				$this->output .= "Edit ID: $id <br>" . inputform5($inputparams);
				return True;
			} else {
				$success = false;
			}

			return $this->ok_err_text($success, "Record successfully loaded.", "Failed to retrieve record.");
		}

		return $this->ok_err_text($success, "Record successfully loaded.", "Failed to retrieve record.");
	}

	function myreseller()
	{
		#return $this->alanal(); #enson email ekleme yap�yordum...
	}

	function applyGlobalFilter($filter)
	{
		if ($this->globalfilter)
			$global = '(' . $this->globalfilter . ')';
		$filt = andle($global, $filter);
		#$this->output.="<hr>globalfilter: $this->globalfilter, filter: $filter, applyglobalfilter result: $filt</hr>";
		return $filt;
	}

	/* Test Area */

	function test()
	{
		$host = "ehcpforce.ezpz.cc";
		$this->testHostIPs($host);

		$host = "1.1.1.1";
		$this->testHostIPs($host);

		$host = "192.168.1.1";
		$this->testHostIPs($host);

		$host = "yahoo.com";
		$this->testHostIPs($host);

		$host = "332.222.222.222";
		$this->testHostIPs($host);

		$host = "asdfadsflkadsasdfkkasjflkjasdlfjkakdsfjkladsfjkladsjfkladsfjkl.com";
		$this->testHostIPs($host);

		$csv = array_map('str_getcsv', file($this->ehcpInstallPath . '/misc/SLDs.csv'));
		$this->testCCTLD("mohaaaa.co.uk", $csv);
		$this->testCCTLD("fasdfadsf.mohaaaa.co.uk", $csv);
		$this->testCCTLD("ehcpforce.ezpz.cc", $csv);
		$this->testCCTLD("right.ehcpforce.fr", $csv);
		$this->testCCTLD("lol.google.com", $csv);
	}

	function testHostIPs($host)
	{
		if (empty($host) || (!isValidIPAddress($host, true) && !isValidHostname($host))) {
			echo "$host is not valid\n\n";
			return true;
		} else {
			echo "$host is valid\n\n";
			return false;
		}
	}

	function testCCTLD($domain, $csv = "")
	{

		$parts = explode(".", $domain);
		if (count($parts) <= 2 || $this->domainIsCCTLD($domain, $csv)) {
			echo "$domain gets www appended to it for let's encrypt certificate!\n\n";
			return true;
		}

		echo "$domain doesn't get www appended to it for let's encrypt certificate!\n\n";
		return false;
	}

	/* END TEST AREA */

	#============================= utility functions, query etc..
	function exist($table, $where)
	{
		$sayi = $this->recordcount($table, $where);
		return ($sayi > 0);
	}

	function recordcount($table, $where, $debug = false)
	{
		$q = "select count(*) as sayi from " . $table;
		if ($where <> '')
			$q .= " where " . $where;
		$sayi = $this->query2($q);
		if ($debug)
			$this->output .= "<br>$q<br>";
		if ($sayi)
			return $sayi['sayi'];
		else
			return false;
	}


	function updatequery($table, $fields, $values, $where)
	{
		$record = array_combine($fields, $values);
		#$this->output.=print_r2($record);
		return $this->conn->AutoExecute($table, $record, 'UPDATE', $where);
	}

	function insertquery($table, $fields, $values)
	{
		$record = array_combine($fields, $values);
		#$this->output.=print_r2($record);
		return $this->conn->AutoExecute($table, $record, 'INSERT');
	}


	function safe_execute_query($qu, $params, $opname = '', $caller = '', $mysqlconn = false)
	{ # only executes conn->execute
		# example: $this->safe_query("select * from panelusers where panelusername='%s' and md5('%s')=password",array($username,$password),"","comment-caller")
		$this->logquery($qu . ($caller ? ' Caller:' . $caller : ''));
		$params2 = array($qu);
		foreach ($params as $p)
			$params2[] = $this->escape($p);
		$qu = call_user_func_array('sprintf', $params2);
		$this->logquery($qu . '  Caller:' . $caller);

		if ($mysqlconn) { # mysqlconn is for queries that needs to be executed on another mysql link.
			$rs = mysqli_query($mysqlconn, $qu);
			$err = $this->getDBError($mysqlconn);
		} else
			$rs = $this->conn->Execute($qu);

		if ($rs === false) {
			$err = $this->getDBError();
			return $this->error_occured("Error $opname (executequery: $qu) $err");
		}
		if ($opname <> '') {
			$this->echoln("Success " . $opname . "\n");
		}
		return True;
	}


	function safe_query($qu, $params, $caller = '')
	{ #returns associated array
		$this->logquery($qu . '  Caller:' . $caller);
		$params2 = array($qu);
		foreach ($params as $p)
			$params2[] = $this->escape($p);
		$qu = call_user_func_array('sprintf', $params2);
		$this->logquery($qu . '  Caller:' . $caller);

		$rs = $this->conn->Execute($qu);

		if ($rs === false) {
			$err = $this->getDBError();
			return $this->error_occured("query, caller: $caller ", "query: $qu ($err)");
		} else
			$res = $rs->GetArray();
		#if(!$res) $this->debugtext("query: res null, query: $qu");
		return $res;

	}

	function check_mysql_connection()
	{
		# reconnect if mysql conn gone away
		# should be called in daemon mode

		echo "\nfile:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . "\n";

		$rs = $this->conn->Execute("select now()");
		if ($rs === false) {
			$msg = $this->conn->ErrorMsg();

			if (strstr($msg, 'server has gone away') !== false) {
				$this->tryReconnect();
			}
		} else {
			echo "\nmysql connection is already alive\n";
		}

	}

	function query($qu, $caller = '')
	{ #returns associated array
		#$this->check_mysql_connection();
		$this->logquery($qu . '  Caller:' . $caller);
		$rs = $this->conn->Execute($qu);
		if ($rs === false) {
			$err = $this->getDBError();
			return $this->error_occured("query, caller: $caller ", "query: $qu ($err)");
		} else
			$res = $rs->GetArray();
		#if(!$res) $this->debugtext("query: res null, query: $qu");
		return $res;
	}

	function query2($qu)
	{ # sadece tek sat�r d�nd�r�r..
		$res = $this->query($qu);
		#$this->output.="qu: $qu <br>".print_r2($res);
		return $res[0];
	}


	function getField($tablo, $alan, $filter)
	{
		$query = "select $alan from $tablo";
		if ($filter <> '')
			$query .= " where $filter";
		$res = $this->query($query);
		#$this->output.="alanal icinde:".print_r2($res);
		#if($res)$this->output.="<hr>bilgi var... <hr>";
		if ($res)
			return $res[0][$alan];
		else
			return false;
	}

	function multiserver_getfield($tablo, $alan, $filter, $serverip)
	{
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		$query = "select $alan from $tablo";
		if ($filter <> '')
			$query .= " where $filter";
		$res = $this->multiserver_query($query, $serverip);
		#$this->output.="alanal icinde:".print_r2($res);
		#if($res)$this->output.="<hr>bilgi var... <hr>";
		if ($res)
			return $res[0][$alan];
		else
			return false;
	}

	function alanal2($tablo, $alanlar, $filter)
	{
		$query = "select " . selectstring($alanlar) . " from $tablo ";
		if ($filter <> '')
			$query .= " where $filter";
		$res = $this->query($query);
		#$this->output.=print_r2($res);
		if ($res)
			return $res[0];
		else
			return false;
	}


	function tabloyaekle2($tablo, $record)
	{
		$sql = "SELECT * FROM $tablo WHERE 1 = 2";
		$rs = $this->executeQuery($sql);
		$sql = $this->conn->GetInsertSQL($rs, $record);
		#$this->output.="<br>sql:$sql ".print_r2($fields).print_r2($values)."<br>";
		if (!$res = $this->executeQuery($sql))
			$this->output .= "Tabloya eklerken hata: $tablo <br>";
		return $res;
	}


	function getinsertsql($tablo, $fields, $values)
	{
		$sql = "SELECT * FROM $tablo WHERE 1 = 2";
		$rs = $this->conn->Execute($sql);
		$record = array_combine($fields, $values);
		$sql = $this->conn->GetInsertSQL($rs, $record);
		#$this->output.="<br>sql:$sql ".print_r2($fields).print_r2($values).print_r2($record)."<br>";
		return $sql;
	}

	function logquery($qu)
	{
		$this->queries[] = $qu;
		if (rand(1, 20) > 18) { # do not count for queries each time
			if (count($this->queries) > 1000)
				$this->queries = array(); # limit it
		}
	}

	function executeQuery($qu, $opname = '', $caller = '', $mysqlconn = false, $adoConn = false, $quiet = false, $returnAffected = false)
	{ # only executes conn->execute
		try
		{
			$this->logquery($qu . ($caller ? ' Caller:' . $caller : ''));

			if ($mysqlconn) { # mysqlconn is for queries that needs to be executed on another mysql link.
				$rs = mysqli_query($mysqlconn, $qu);
				$err = $this->getDBError($mysqlconn);
				$this->debugecho("query executed on another mysql link:($qu)", 1, false);
				$affectedRows = mysqli_affected_rows($mysqlconn);
			} elseif ($adoConn) {
				$rs = $adoConn->Execute($qu);
				$err = $adoConn->ErrorMsg();
				$this->debugecho("query executed on another ado-mysql link.($qu)", 1, false);
				$affectedRows = $adoConn->Affected_Rows();
			} else {
				$rs = $this->conn->Execute($qu);
				$err = $this->conn->ErrorMsg();
				$affectedRows = $this->conn->Affected_Rows();
			}

			if ($rs === false) {
				if (!$quiet) {
					return $this->error_occured("Error $opname (executequery: $qu) ($err)");
				} else {
					return false;
				}
			}

			if ($opname <> '') {
				$this->echoln("Success " . $opname . "\n");
			}

			if (is_numeric($affectedRows)) {
				if ($affectedRows > 0 || $returnAffected) {
					return $affectedRows;
				}
			}
			return True;
		} catch (Exception $e) {
			if (!$quiet) {
				return $this->error_occured("Error $opname (executequery: $qu) (" . $e->getMessage() . ")");
			}
		}

		return false;
	}

	function query3($qu, $opname = '', $caller = '', $mysqlconn = false, $adoConn = false)
	{ # only executes conn->execute
		$this->logquery(__FUNCTION__ . ':' . $qu . ($caller ? ' Caller:' . $caller : ''));

		if ($mysqlconn) { # mysqlconn is for queries that needs to be executed on another mysql link.
			$rs = mysqli_query($mysqlconn, $qu);
			$err = $this->getDBError($mysqlconn);
			$this->debugecho("query executed on another mysql link:($qu)", 1, false);
			$res = array();

			while ($r = mysqli_fetch_assoc($rs))
				$res[] = $r; # build ado style result set.

		} elseif ($adoConn) {
			$rs = $adoConn->Execute($qu);
			$err = $adoConn->ErrorMsg();
			$this->debugecho("query executed on another ado-mysql link.($qu)", 1, false);
			$res = $rs->GetArray();
		} else {
			$rs = $this->conn->Execute($qu);
			$err = $this->conn->ErrorMsg();
			$res = $rs->GetArray();
		}

		if ($rs === false) {
			return $this->error_occured("Error $opname (executequery: $qu) ($err)");
		}

		if ($opname <> '') {
			$this->echoln("Success " . $opname . "\n");
		}

		return $res;
	}

	# ============================ initialization and db settings misc

	function nextgoal()
	{

	}


	function isadmin()
	{
		return ($this->activeuser == 'admin');
	}

	function connectTodb()
	{
		# $this->conn=NewADOConnection("mysql"); # reconnect did not work, so i moved $this->conn=NewADOConnection("mysql") into connectTodb2
		$ret = $this->connectTodb2();
		return $ret;
	}

	function connect_to_mysql($named_params)
	{

		# our new function with named_params
		extract($named_params);

		$this->output .= __FUNCTION__ . ": dbhost:$dbhost,dbusername:$dbusername,dbname:$dbname";

		if ($this->connected_mysql_servers[$dbhost])
			return $this->connected_mysql_servers[$dbhost];

		if (!$conn = mysqli_connect($dbhost, $dbusername, $dbpass)) {
			$this->output .= "<br><big>mysql connection error: $dbhost" . $this->getDBErrorNo($conn) . " " . $this->getDBError($conn) . "</big>";
			return false;
		}

		if (!mysqli_select_db($conn, $dbname)) {
			$this->output .= "<br>Cannot select db: $dbname on host:$dbhost<br>";
			return false;
		}
		;


		$this->connected_mysql_servers[$dbhost] = $conn; # keep track of connections, to prevent multiple connection trying.

		return $conn;
	}

	function escape($str)
	{
		return mysqli_real_escape_string($this->link, $str);
	}

	function getDBError($connection = "")
	{
		if (!empty($connection)) {
			return mysqli_error($connection);
		} else {
			return mysqli_error($this->link);
		}
	}

	function getDBErrorNo($connection = "")
	{
		if (!empty($connection)) {
			return mysqli_errno($connection);
		} else {
			return mysqli_errno($this->link);
		}
	}

	function connectTodb2()
	{
		# why a separate func: connecttodb is a wrapper for this. it may do other stuff. 
		$this->conn = NewADOConnection("mysqli");
		$this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
		$this->conn->connect($this->dbhost, $this->dbusername, $this->dbpass, $this->dbname);

		if (!$this->conn or ($this->conn->ErrorMsg() <> '')) {
			echo "<div align=center><font size=+1><hr>Error Occured while connecting to db, check your db settings... <br>
		This is mostly caused by wrong ehcp password in config.php <br>
		if you just installed ehcp, then learn/know your ehcp root password, then re-install ehcp.. <br>
		you may also try <a target=_blank href=troubleshoot.php>troubleshoot page</a> or <a target=_blank href=misc/mysqltroubleshooter.php>Additional mysql troubleshooter... </a> or <a target=_blank href='http://www.ehcp.net/?q=node/245'>www.ehcp.net/?q=node/245</a>
		<hr></font>" . $this->conn->ErrorMsg() . "</div>";
			$this->connected = false;
			return false;
		}
		$this->executeQuery("set names utf8");

		$this->connected = True;

		// Get link instance

		if (!$this->conn or !$this->connected) {
			// Also create a normal mysqli connection to use for various functions such as escaping values
			$logininfo = array(
				'dbhost' => 'localhost',
				'dbusername' => $this->dbusername,
				'dbpass' => $this->dbpass,
				'dbname' => $this->dbname
			);

			$this->link = $this->connect_to_mysql($logininfo);
		} else {
			$this->link = $this->conn->_connectionID;
		}

		return True;
	}


	#=================================== login functions etc..
	function sayinmylang($str)
	{
		$res = $this->lang[$this->currentlanguage][$str];
		if (!$res)
			$res = "($str:language error, not defined " . $this->information(1, True) . ")";
		# $res="language error: ($str) is not defined in currentlanguage of:(".$this->currentlanguage.") <br> please define <b> \$this->lang['".$this->currentlanguage."']['".$str."']=\"........\";   </b> in  <br>language/".$this->currentlanguage.".php <br>";
		return $res;
	}

	function setLanguage($lang, $quite = false)
	{
		$this->requireAdmin();
		$file = "templates/$this->template/$lang/template_$lang.html";
		if (!file_exists($file))
			$this->errorTextExit("The selected localization file $file does not exist within this theme!  Try another theme.");
		$_SESSION['currentlanguage'] = $lang;
		$this->setConfigValue("defaultlanguage", $lang);
		$this->loadLanguage();
		if (!$quite)
			$this->output .= "<hr>Language is set as: $lang <hr>";
	}

	function debugecho($str, $inlevel, $directecho = True)
	{

		if ($this->commandline) {
			$lf = "\n";
			if (is_array($str))
				$str = print_r($str);
		} else {
			$lf = "<br>";
			if (is_array($str))
				$str = print_r2($str);
		}
		;


		if ($this->debuglevel >= $inlevel) {
			$out = "$lf Debug*: Debuglevel: $this->debuglevel, $str $lf";
			if ($directecho or $this->commandline)
				echo $out;
			else
				$this->output .= $out;
		}
	}

	function debugecho2($str, $inlevel)
	{
		if ($this->debuglevel >= $inlevel)
			$this->output .= "<br>Debug*: Debuglevel: $this->debuglevel, " . $str . "<br>";
	}

	function loadLanguage()
	{


		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4);

		if (!$this->defaultlanguage) {
			$this->output .= "defaultlanguage is not defined..";
		}

		if (!$currentlanguage)
			$currentlanguage = $_SESSION['currentlanguage']; # load currentlanguage from session, if not set, it is defaultlanguage
		if (!$currentlanguage) {
			$currentlanguage = $this->defaultlanguage;
		}

		if ($currentlanguage == '')
			$currentlanguage = 'en';
		$this->currentlanguage = $currentlanguage;


		include_once("language/" . $this->currentlanguage . ".php");

		# load english lang if language file is not found
		if (count($this->lang) == 0) {
			$this->echoln("default language file for ($this->defaultlanguage) is not found, english file loaded instead <br>
		Language files under language directory, under ehcp dir, you may write your own lang file..<br>
		default language is defined in <b>config.php</b><br>");
			include_once("language/en.php");
		}
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4);
		#$this->output.="loadlanguage is loaded.....<br>".print_r2($this->lang);
	}


	function debug()
	{
		$ret .= "<br>Debug: <br>dbhost:" . $this->dbhost;
		$ret .= "<br>dbuser:" . $this->dbusername;
		#$ret.="<br>dbpass:".$this->dbpass;
		$ret .= "<br>dbname:" . $this->dbname;
		return $ret;
	}

	function showConfig()
	{
		#$this->output.=print_r2($this->conf);
	}

	function isPrivateIp($ip)
	{
		if (is_array($ip)) { # test for multiple ips if an array
			$ret = false;
			foreach ($ip as $i)
				$ret = $ret or $this->isPrivateIp($i);
			return $ret;
		}

		return (substr($ip, 0, 7) == '192.168' or substr($ip, 0, 6) == '172.16' or substr($ip, 0, 3) == '10.');
	}

	function dynamicInfo()
	{
		global $quickdomains, $smallserverstats; # used in show()
		if (!$this->checkConnection('Some dynamic info'))
			return false;

		$email = str_replace('@', '((at))', $this->conf['adminemail']); # to prevent spam...
		$ret .= "Current Active user: Welcome $this->activeuser !<br> Admin email: $email <br></b>";


		if (!$this->isloggedin or $this->isadmin()) {
			$ret .= "Your dns/server ip is set as:" . $this->conf['dnsip'] . ", if it is not detected/set correctly, please set it in your <a href='?op=options'>Settings</a></font><br><br>";
			$smallserverstats = $this->smallserverstats();
			$ret .= $smallserverstats; # this may be disabled
		}

		if (validateIpAddress($this->conf['dnsip']) === false) {
			$this->warnings .= "<br><font size=+1><b>Warning : Your dns/server ip syntax is wrong.. you must fix it.. example: 85.98.112.34 or alike </b>b></font><br>";
		}

		if ($this->isPrivateIp($this->conf['dnsip'])) {
			$this->warnings .= "<br><font size=+1><b>Warning : Your dns/server ip seems your local/private ip (" . $this->conf['dnsip'] . "). in order your server be accessible from outside, you should set it to your outside/real ip (your modem/router's ip)</b></font><br>";
		}

		if ($this->isDemo)
			$this->warnings .= "<br><b>This is demo mode, some operations such as change dns may not be available</b><br>";
		# $ret.=print_r2($this->userinfo);

		$quickdomains = '';
		$doms = $this->getMyDomains("");
		if (count($doms) <= 10 and is_array($doms)) {
			$quickdomains = "Quickselect: ";
			foreach ($doms as $d)
				$quickdomains .= "<a href='?op=selectdomain&id=" . $d['domainname'] . "'>" . $d['domainname'] . "</a> ";
		}


		return $ret;
	}

	function dynamicInfo2()
	{
		global $quickdomains, $smallserverstats, $ehcpversion; # used in show()
		if (!$this->checkConnection('Some dynamic info'))
			return false;

		$email = str_replace('@', '((at))', $this->conf['adminemail']); # to prevent spam...
		$ret .= "Admin:$email<br>";


		$ret .= "Users:" . $this->recordcount($this->conf['logintable']['tablename'], '') .
			",Domains:" . $this->recordcount($this->conf['domainstable']['tablename'], '') .
			",Ftpusers:" . $this->recordcount($this->conf['ftpuserstable']['tablename'], '') .
			",Emails:" . $this->recordcount($this->conf['emailuserstable']['tablename'], '') .
			"<br><a target=_blank href='https://ehcpforce.ezpz.cc'>Version: $ehcpversion</a><br></font>";

		return $ret;
	}

	function debuginfo()
	{ # this is debug info for developer, me !

		if ($this->debuglevel == 0)
			return;

		#if($this->clientip<>'127.0.0.1' and $this->conf['adminemail']<>'bvidinli@gmail.com' and $this->clientip<>'78.187.86.112') return false;
		$ret = print_r2($this->queries) . $this->clientip;
		return $ret;
	}


	function getDomainInfo($domainname, $checkIfExists = false)
	{
		$ret = $this->query("select * from " . $this->conf['domainstable']['tablename'] . " where domainname='$domainname'");
		if ($checkIfExists && (!is_array($ret) || count($ret) == 0 || $ret == false)) {
			return false;
		}
		$ret = $ret[0];


		list($ftpserver) = explode(',', $ret['webserverips']);
		if ($ftpserver == '')
			$ftpserver = 'localhost';
		$ret['ftpserver'] = $ftpserver;
		if ($ret['webserverips'] == '')
			$ret['webserverips'] = 'localhost';
		if ($ret['dnsserverips'] == '')
			$ret['dnsserverips'] = 'localhost';

		return $ret;
	}

	function navigation_bar()
	{
		if ($this->selecteddomain) {

			if (!$this->domaininfo)
				$this->domaininfo = $this->getDomainInfo($this->selecteddomain);
			$domaininfo = $this->domaininfo;
			if ($domaininfo['reseller'] <> $this->activeuser and $this->isadmin())
				$warning = "<b>This domain belongs to (" . $domaininfo['reseller'] . ") reseller, go to <a href='?op=resellers'>resellers page</a> for details</b><br>"; // z7 mod

			if (($domaininfo['diskquotaused'] > $domaininfo['diskquota']) && ($domaininfo['diskquota'] > 0))
				$quotaWarning = $this->sayinmylang("You have exceeded your quota");
			else
				$quotaWarning = "";

			if (($domaininfo['webserverips'] == '') or ($domaininfo['webserverips'] == 'localhost'))
				$webserverips_str = '';
			else
				$webserverips_str = " - Webserverips:" . $domaininfo['webserverips'];

			if ($domaininfo['status'] == 'passive')
				$pass = "<font size=+1>This domain is passive click <a href='?op=editdomain'>here</a> to activate</font>";

			$ret = "$warning<div class=topnavigator><a href='?op=deselectdomain'>" . $this->sayinmylang("Panel Home") . "</a> - <a href=index.php>" . $this->sayinmylang("Domain Home") . "</a> - <a href='?op=listselectdomain'>" . $this->sayinmylang("Domains") . "</a> -> " . $this->sayinmylang("Selected Domain") . ": <a href=?>$this->selecteddomain</a>  <a target=_blank href=http://www.$this->selecteddomain><img border=0 src=images/openinnew.jpg></a>  - Disk Quota: [" . $domaininfo['diskquotaused'] . "MB / " . $domaininfo['diskquota'] . "MB] (<a href='?op=doupdatediskquota&domainname=" . $this->selecteddomain . "'>update quotainfo</a>)" . $quotaWarning . $webserverips_str . " $pass</div>";
		} elseif ($this->is_email_user()) {

		} else
			$ret = '(No domain is selected yet)<br>';

		return $ret;
	}

	function failedlogins()
	{
		global $mark;
		$this->getVariable(array('mark'));
		if ($mark <> '') {
			$this->executeQuery("update log set notified='yes' where panelusername='$this->activeuser' and (notified is null or notified='')");
			$this->output .= "Marked all read ($mark)";
		}

		$this->listTable('', 'logtable', "panelusername='$this->activeuser' and (notified is null or notified='')");
		$this->output .= "<a href='?op=failedlogins&mark=read'>Mark all read</a>";



	}

	function check_failed_logins()
	{
		$s = $this->recordcount('log', "panelusername='$this->activeuser' and (notified is null or notified='')");
		if ($s > 0)
			return "<p class=failedlogin>You have failed login attempts, <a class=failedlogin href='?op=failedlogins'>click here for more info</a></p><br>";
	}

	function show($templatefile1 = '')
	{
		global $commandline, $output, $quickdomains, $ehcpversion;
		$this->output .= $output . $this->debuginfo();
		$dynamicInfo = $this->dynamicInfo();
		$dynamicInfo2 = $this->dynamicInfo2();

		$paneluserinfo = $this->getPanelUserInfo();
		$theme_color = $paneluserinfo["theme_color"];
		$theme_contrast = $paneluserinfo["theme_contrast"];

		// Domain selected js variable for use in scripts
		if (isset($this->selecteddomain) && !empty($this->selecteddomain)) {
			$jsOnDomainSelected = 'var domainSelected = true; var selectedDomainName = "' . $this->selecteddomain . '";';
		} else {
			$jsOnDomainSelected = 'var domainSelected = false; var selectedDomainName = "";';
		}

		// Theme color and contrast settings
		if (empty($theme_color)) {
			$theme_color = "#16ff0f";
		}
		if (empty($theme_contrast)) {
			$theme_contrast = 'light';
		}

		$theme_color_js = 'var defaultColor = \'' . $theme_color . '\'; var contrastStyle = \'' . $theme_contrast . '\';';

		// Build the javascript variables into {extra_js} header replacement variable
		$extraJSForTheme = $jsOnDomainSelected . $theme_color_js;
		$extraJSForTheme = '<script type="text/javascript">' . $extraJSForTheme . '</script>';

		$extraJSForTheme .= '<script type="text/javascript" src="templates/all_templates/scripts/jquery.js"></script>';
		$extraJSForTheme .= '<script type="text/javascript" src="templates/all_templates/scripts/ehcp.js"></script>';

		// Add our global css here too
		$extraJSForTheme .= '<link rel="stylesheet" type="text/css" href="templates/all_templates/css/ehcp.css" />';

		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . "-" . $point++, 4);


		#$this->output.="<hr>".$this->conf['dnsip']."<hr>";
		#$this->output.="deneme.... ";

		if ($this->warnings <> '') {
			if ($this->op == 'warnings') {
				$this->requireAdmin();
				$this->check_ehcp_version();
				$this->output .= $this->warnings;
			} else {
				if ($this->isadmin()) {
					$this->output .= "<br><table>
						   <tr>
							   <td width=\"100px\" valign=\"middle\" align=\"center\"><img src=images/warning.png>
							   </td>
							   <td valign=\"middle\" align=\"left\"><a href='?op=warnings'>You have general server warnings.  Click here to view them.</a></td>
						   </tr>
					   </table>";
				}
			}
		}

		if ($commandline) {
			echo "\nThis is commandline (show):<br>\n" . $this->output . "\n\n";
			return True;
		}




		$this->getVariable(array("ajax"));

		if ($templatefile1 <> '') {
			$this->templatefile = $templatefile1;
		} else {
			if ($this->isadmin())
				$this->templatefile = "template_admin";
			elseif ($this->isreseller)
				$this->templatefile = "template_reseller";
			elseif (strstr($this->activeuser, '@'))
				$this->templatefile = "template_emailuser";
			else
				$this->templatefile = "template_domainadmin";

			if (!file_exists("templates/$this->template/$this->currentlanguage/" . $this->templatefile . '_' . $this->currentlanguage . ".html"))
				$this->templatefile = "template";
			if (!file_exists("templates/$this->template/$this->currentlanguage/" . $this->templatefile . '_' . $this->currentlanguage . ".html")) {
				echo "<hr><b>Template file still not found: (templates/$this->template/$this->currentlanguage/" . $this->templatefile . '_' . $this->currentlanguage . ".html)</b><hr>";
			}

		}

		/*
		  if($this->recordcount("html","id='$this->cerceve'")==0) {
			  $this->echoln("Template for this language ($this->currentlanguage) is not found. using English template instead.<br>You may add this in html table in ehcp db<br>");
			  $this->cerceve="template_en";
		  }*/


		$this->selecteddomainstr = $this->navigation_bar();
		#$this->debugecho("file:".__FILE__.", Line:".__LINE__.", Function:".__FUNCTION__."-".$point++." selecteddomainstr:".$this->selecteddomainstr,4,false);

		$stylefile = "templates/" . $this->template . "/$this->currentlanguage/style.css";


		if ($ajax) {
			header("Content-Type: text/html; charset=ISO-8859-9");
			echo "<html>
		<head>
		<meta http-equiv='content-Type' content='text/html; charset=ISO-8859-9' />
		<meta http-equiv='content-Type' content='text/html; charset=windows-1254' />
		<meta http-equiv='content-Type' content='text/html; charset=ISO-8859-1' />
		</head>
		<body>" . $this->output . "</body></html>";
		} else {
			////cerceveletyaz($this->output,$this->cerceve);
			$templatedir = 'templates/' . $this->template . '/' . $this->currentlanguage;
			$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . "-****" . $point++, 4);

			$ehcpversion1 = $ehcpversion;
			if ($this->debuglevel > 0)
				$ehcpversion1 .= "<br>Debuglevel: $this->debuglevel";
			$webserver = $this->miscconfig['webservertype'];
			if ($webserver == '')
				$webserver = 'apache2';

			$this->templateEcho(
				// apply template and echo
				$this->templatefile,
				// bunlar sablonda kullanilacak tagler.
				array('{webserver}', '{domainname}', '{domain}', '{adminemail}', "{ehcpversion}", "{ajaxscript}", "{ajaxonload}", "{username}", "{logo}", '{myip}', '{selecteddomain}', '{quickdomains}', '{banner}', '{stylefile}', '{templatedir}', '{language}', '{dynamicinfo}', '{dynamicinfo2}', '{template_color}', '{extra_js}'),
				// bunlar da tagler yerine konacak degiskenler.
				array($webserver, $this->selecteddomain, $this->selecteddomain, $this->conf['adminemail'], $ehcpversion1, $this->ajaxscript, $this->ajaxonload, $this->activeuser, $this->logo, $this->conf['dnsip'], $this->selecteddomainstr, $quickdomains, $this->miscconfig['banner'], $stylefile, $templatedir, $this->currentlanguage, $dynamicInfo, $dynamicInfo2, $theme_color_js, $extraJSForTheme)
			);
			#if(!$this->isDemo and ($this->conf['adminemail']=='bvidinli@gmail.com')) echo "query count: ".count($this->queries)." <br>".$this->debuginfo();
		}

	}


	function templateEcho($template, $isaretler, $icerikler)
	{

		$isaret = "{ickisim}";
		# $cerceve=$this->htmlekle2($cerceve);  # code to read template from database, html table, used previously... can be used if you whish...
		# burada ister dbden ister dosyadan okuma yapilabilir. binevi storage engine gibi... bunu ilerde dusuneyim,.
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . "-" . $point++, 4);

		/*
				 $this->output.=print_r2($isaretler);
				 $this->output.=$icerikler['selecteddomainstr'];
		*/


		$template = $this->loadTemplate($template);

		// THIS AFFECTS ALL OUTPUT... NEED TO REMEMBER THAT {domainname} which shows up in any apache file... dns file (maybe)... can never use equal operators unless we applied this change to the variable being compared
		$output2 = str_replace($isaret, $this->output, $template); // cerceve icinde isareti ara, isaret yerine simdiye kadarki outputu koy.
		$output2 = str_replace($isaretler, $icerikler, $output2);
		/*
				 $isaretcount=count($isaretler);
				 for($i=0;$i<$isaretcount;$i++){
					 $output2=str_replace($isaretler[$i],$icerikler[$i],$output2);
				 }
		*/
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . "-" . $point++, 4);
		echo $output2; # final output
	}

	function cerceveletyaz4($output, $isaretler, $icerikler)
	{
		# replace {likethis} tags, with values

		$isaretcount = count($isaretler);
		for ($i = 0; $i < $isaretcount; $i++) {
			$output = str_replace($isaretler[$i], $icerikler[$i], $output);
		}
		echo $output;
	}



	function showoutput($header = '', $bodyonload = '')
	{
		global $output;
		$output .= $this->output;
		header("Content-Type: text/html; charset=ISO-8859-9"); //ajax icin yazildi..

		echo "<html>
		<head>
		<meta http-equiv='content-Type' content='text/html; charset=ISO-8859-9' />
		<meta http-equiv='content-Type' content='text/html; charset=windows-1254' />
		<meta http-equiv='content-Type' content='text/html; charset=ISO-8859-1' />
		$header
		</head>
		<body $bodyonload>" . $output . "</body></html>";
	}


	function help()
	{
		$this->output .= "This info is from ehcp.net site:<br><iframe marginwidth=0 marginheight=0 width=600 height=1200 scrollbars=none frameborder=0 scrolling=no src='http://www.ehcp.net/latest/help.html'></iframe>";
	}


	function todolist()
	{
		$this->output .= "This info is from ehcp.net site:<br><iframe marginwidth=0 marginheight=0 width=600 height=1200 scrollbars=none frameborder=0 scrolling=no src='http://www.ehcp.net/latest/roadmap.html'></iframe>";


	}


	//------------- securitycheck, login logout functions...

	function securitycheck()
	{
		// check login and show login page if needed. set activeuser here.
		global $kullaniciadi, $sifre, $isloggedin, $username, $password, $commandline;
		#echo "securitycheck...";


		if ($this->op == "dologin")
			$this->dologin();

		$username = $_SESSION['loggedin_kullaniciadi'];
		$isloggedin = $_SESSION['isloggedin'];

		if (((!$isloggedin) or ($username == '')) and (!$commandline)) {
			if (!empty($this->op) && $this->op != "logout" && $this->op != "dologin") {
				$_SESSION['opToGoTo'] = trim($this->op);
			}
			$this->loginform(); # this exits at the same time herein..
		}
		;

		# these are different variables, that may be used in legacy codes of ehcp.
		$this->activeuser = $username;
		$this->loggedin_kullaniciadi = $username;
		$this->loggedin_username = $username;
		$this->isloggedin = $isloggedin;

		// Check and see if we should redirect to another op after successful login only
		$opToGoTo = $_SESSION['opToGoTo'];
		if (!empty($opToGoTo)) {
			unset($_SESSION['opToGoTo']);
			header("Location: index.php?op=$opToGoTo");
			exit();
		}
	}

	function showUnauthorized()
	{
		header('HTTP/1.0 401 Not Authorized');
		echo "<h1>401 Not Authorized</h1>";
		echo "You are not authorized to access this page.";
		exit();
	}

	function fatalError($str)
	{
		echo "<b>Fatal Error: $str</b>";
	}

	function fatalErrorExit($str)
	{
		echo "<b><font size=+1>ehcp Fatal Error: $str</font></b>";
		exit();
	}


	function loadTemplate($templatefile, $strict = True)
	{


		$templateengine = "file"; # currently templating done through files under templates directory. before, it was from db, but many web developers are confused with html in db.. so, i swithced to html files..
		$this->debugecho("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . " Templatefile:($templatefile)", 4, True);

		if ($templateengine == "file") {
			$file = "templates/$this->template/$this->currentlanguage/" . $templatefile . "_" . $this->currentlanguage . ".html";
			$this->debugecho("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . "file:($file)", 4, True);

			if (!file_exists($file)) {
				if (!$strict)
					return "";

				$err = "Template file '$file' for this language ($this->currentlanguage) and template ($this->template) is not found. using English default template instead.<br>";
				$this->echoln($err);
				$this->template = 'default';
				$file = "templates/$this->template/en/" . $templatefile . "_en.html";
				if (!file_exists($file)) {
					echo $err;
					$this->fatalErrorExit("$file template file is not found...  ");
				}
			}

			if ($this->debuglevel > 3)
				echo "file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . '-template:' . $this->templatefile . "-default:$this->defaultlanguage-current:$this->currentlanguage-session.cur:" . $_SESSION['currentlanguage'] . "<br>";
			if ($this->debuglevel > 3)
				debug_backtrace2();

			$ret = '';
			$ret = @file_get_contents($file);
			#echo "curr template: $this->template , temp file: $file <hr>";
			if (($ret === false) and $strict)
				$this->fatalError("Template File: $file cannot be loaded... ");
		} else {
			$html = $this->htmlekle2($templatefile . "_" . $this->currentlanguage);
			if ($html == '')
				$html = $this->htmlekle2($templatefile . "_en");

		}
		return $ret;
	}

	function loginform()
	{
		if ($this->debuglevel > 3)
			echo "file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . '-template:' . $this->templatefile . "-default:$this->defaultlanguage-current:$this->currentlanguage-session.cur:" . $_SESSION['currentlanguage'] . "<br>";
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4);
		$this->showexit('loginpage');
	}

	function doLoginEmailUser($username, $password)
	{
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4);
		return $this->dologin2($username, $password, '', '', $this->conf['emailuserstable']);
	}

	function dologin()
	{
		global $kullaniciadi, $sifre, $isloggedin, $username, $password;
		$this->getVariable(array("kullaniciadi", "username", "password"), true);
		$username = strtolower($username); # reason: some panel users type in Admin and cannot do admin ops...

		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__, 4);

		$this->checkTables(); # check tables for missing fields, once on each login
		$this->check_ehcp_version();


		if (strstr($username, '@'))
			return $this->doLoginEmailUser($username, $password);
		else
			return $this->dologin2($username, $password);
	}



	function isPasswordOk($username, $password, $usernamefield = '', $passwordfield = '', $logintable1 = '')
	{
		# only does password comparison
		if ($logintable1 == '')
			$logintable = $this->conf['logintable'];
		else
			$logintable = $logintable1;

		if (!$usernamefield)
			$usernamefield = $logintable['usernamefield'];
		if (!$passwordfield)
			$passwordfield = $logintable['passwordfield'];
		if (!$usernamefield)
			$usernamefield = 'username';
		if (!$passwordfield)
			$passwordfield = 'password';

		if ($logintable['passwordfunction'] == '') {
			$where = "$usernamefield='$username' and '$password'=$passwordfield";
		} elseif ($logintable['passwordfunction'] == 'encrypt') {
			$where = "$usernamefield='$username' and " . $logintable['passwordfunction'] . "('$password','ehcp')=$passwordfield";
		} else {
			$where = "$usernamefield='$username' and " . $logintable['passwordfunction'] . "('$password')=$passwordfield";
		}

		$where .= " and status='" . $this->status_active . "'";
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . ": $username,$password,$usernamefield,$passwordfield,$logintable, query where: ($where) <BR>", 4);

		$sayi = $this->recordcount($logintable['tablename'], $where);
		if ($sayi === false) {
			$this->error_occured("dologin2");
			return false;
		}

		if ($sayi == 0) {
			return false;
		} elseif ($sayi > 0) {
			return True;
		}
	}

	function dologin2($username, $password, $usernamefield = '', $passwordfield = '', $logintable = '')
	{
		# sets session values if password comparison succeeds..
		$this->debugecho2("file:" . __FILE__ . ", Line:" . __LINE__ . ", Function:" . __FUNCTION__ . ": $username,$password,$usernamefield,$passwordfield,$logintable<BR>", 4);

		if ($this->isPasswordOk($username, $password, $usernamefield, $passwordfield, $logintable) || ($this->isadmin() && empty($password))) {

			if ($this->isadmin() && empty($password)) {
				// Admin is impersonating
				$origSession = $this->array_copy($_SESSION);

				if (!array_key_exists("PREVIOUS_LOGGEDIN_INFO", $_SESSION)) {
					// Clear some session information
					$this->logout2(false);
					$_SESSION["PREVIOUS_LOGGEDIN_INFO"] = $origSession;
				}

				$_SESSION["IMPERSONATION"] = true;
			}

			$this->debugecho2("<hr>logging in user....", 2);
			$_SESSION['loggedin_kullaniciadi'] = $username;
			$_SESSION['activeuser'] = $username;
			$_SESSION['loggedin_username'] = $username;
			$_SESSION['isloggedin'] = True;

			$this->isloggedin = True;
			$this->loggedin_kullaniciadi = $username;
			$this->loggedin_username = $username;
			$this->activeuser = $username;

			// Set the FTP Path into session
			$this->setFTPPathInSession();

			$_SESSION['currentlanguage'] = $this->defaultlanguage;


			# load user config, and set default domain and other, if any..
			$this->userconfig = $this->loadConfigIntoArray("select * from misc where panelusername='$this->activeuser'");
			if ($this->userconfig['defaultdomain'] <> '' and $this->selecteddomain == '') {
				$this->setselecteddomain($this->userconfig['defaultdomain']);
			}

			return True;
		} else {
			$this->debugecho2("<hr>user/pass is not correct....", 2);
			$this->executeQuery("insert into log (tarih,panelusername,ip,log)values(now(),'$username','$this->clientip','Failed Login Attempt')");
			$userIP = getIPAddress();
			$f2banDate = date("M d H:i:s");
			$this->log_to_file("log/ehcp_failed_authentication.log", "$f2banDate EHCP authentication failed attemping to login as user $username from $userIP\n");
			return $this->errorText("Incorrect username or password.");
		}
		//echo "<hr>dologin2 bitti..sayi:($sayi)</hr>";
	}

	function log_to_file($logFile, $logstr)
	{
		/* Log Failed Authentication For Use with Fail2Ban ; by own3mall*/
		if (file_exists($logFile)) {
			// Size check larger than 20MBs
			if (filesize($logFile) >= 20971520) {
				$newName = $logFile . "_" . date("d-m-Y_H:i:s");
				rename($logFile, $newName);

				// Create the new log file
				$authLog = fopen($logFile, "x+");
				if ($authLog) {
					fclose($authLog);
				}
				chmod($logFile, 0644);
			}
		} else {
			// Create the file
			$authLog = fopen($logFile, "x+");
			if ($authLog) {
				fclose($authLog);
			}

			chmod($logFile, 0644);
		}

		// Get contents of Authentication Log and add a new entry
		$fp = @fopen($logFile, 'a');
		@fwrite($fp, $logstr);
		@fclose($fp);
	}

	function dologin3($tablo, $username, $password, $usernamefield, $passwordfield, $md5 = '')
	{
		// farkli tablodan user dogrulamaya izin verir...
		# bu pek kullanilmiyor artik... ispasswordOk is goruyor...
		if ($md5 == 'md5') {
			$where = "$usernamefield='$username' and md5('$password')=$passwordfield";
		} else {
			$where = "$usernamefield='$username' and '$password'=$passwordfield";
		}

		$sayi = $this->recordcount($tablo, $where);
		if (!$sayi)
			return false;

		if ($sayi == 0) {
			$this->output .= "Wrong username/password.:<br>";
			return false;
		} elseif ($sayi > 0) {
			$_SESSION['loggedin_kullaniciadi'] = $username;
			$_SESSION['isloggedin'] = True;
			$this->isloggedin = True;
			return True;
		}
	}

	function logout()
	{
		if (array_key_exists("PREVIOUS_LOGGEDIN_INFO", $_SESSION)) {
			$origSessCopy = $this->array_copy($_SESSION);
			$origSessionInfo = $origSessCopy["PREVIOUS_LOGGEDIN_INFO"];
			$this->logout2(false);
			$_SESSION = $origSessionInfo;
			header('Location: index.php');
		} else {
			$this->logout2();
			header('Location: index.php');
		}
	}

	function logout2($destroy = true)
	{
		$_SESSION['loggedin_kullaniciadi'] = '';
		$_SESSION['loggedin_username'] = '';
		$_SESSION['isloggedin'] = false;
		$_SESSION['FTP_HOME_PATH'] = '';
		$_SESSION['temp_id'] = '';
		$_SESSION['IMPERSONATION'] = '';
		$_SESSION['PREVIOUS_LOGGEDIN_INFO'] = '';

		// Remove from array
		unset($_SESSION['IMPERSONATION']);
		unset($_SESSION['PREVIOUS_LOGGEDIN_INFO']);

		$this->isloggedin = false;
		$this->loggedin_kullaniciadi = '';
		$this->loggedin_username = '';

		if ($destroy) {
			session_unset();
			session_destroy();
		}
		return True;
	}

	//------------------ end of securitycheck functions


	function checkInstall()
	{
		// check if installed, and install if not. to be coded later.
		if (!$this->checkinstall)
			return false;
		$this->checkdaemon();
		if (!$this->isinstalled)
			$this->installehcp;
		return True;
	}

	function isinstalled()
	{
		//? to be coded later.
		return True;
	}

	function installehcp()
	{
		// to be coded later.
		return True;
	}

	function phpinfo()
	{
		$this->requireAdmin();
		$this->output .= phpinfo(); // may be disabled for security
	}

	function getMyDomains($filt = '', $orderBy = '')
	{
		if ($this->isadmin())
			$filt = '';
		else
			$filt = "panelusername='$this->activeuser' or reseller='$this->activeuser'";

		return $this->getDomains($filt, $orderBy);
	}

	function getDomains($filt = '', $orderBy = '')
	{
		$domtable = $this->conf['domainstable']['tablename'];
		$q = "select * from $domtable";
		if ($filt <> '')
			$q .= " where $filt";
		if ($orderBy <> '')
			$q .= " " . $orderBy;
		#echo "$q\n";
		return $this->query($q);
	}

	function getUserFTPAccounts($user, $filt = '')
	{
		$domtable = $this->conf['ftpuserstable']['tablename'];
		$q = "select * from $domtable where panelusername='$user' and status IS NOT NULL";
		if ($filt <> '')
			$q .= " $filt";
		#echo "$q\n";
		return $this->query($q);
	}

	function getPanelUsers($filt = '')
	{
		$domtable = $this->conf['paneluserstable']['tablename'];
		$q = "select * from $domtable";
		if ($filt <> '')
			$q .= " where $filt";
		#echo "$q\n";
		return $this->query($q);
	}

	function getSubDomains($filt = '', $orderBy = '')
	{
		$subdomtable = $this->conf['subdomainstable']['tablename'];
		$q = "select * from $subdomtable";
		if ($filt <> '')
			$q .= " where $filt";
		if ($orderBy <> '')
			$q .= " " . $orderBy;
		#echo "$q\n";
		return $this->query($q);
	}

	//=================================== below are functions for or related to daemon mode ..
	//	operation,add/delete, user/domainname, userpass etc.... 4 parameters.

	function checkPort($server, $port)
	{
		$conn = @fsockopen($server, $port, $errno, $errstr, 2);
		if ($conn) {
			fclose($conn);
			return True;
		}
		return false;
	}

	function checkPort2($portno)
	{
		if (!$this->checkPort('localhost', $portno))
			$ret = "Port $portno problem with your server.";
		return $ret;
	}

	function checkPorts()
	{
		$portstocheck = array(22, 25, 53, 80, 110, 143);
		foreach ($portstocheck as $port)
			$ret .= $this->checkPort2($port);
		if ($ret <> '') {
			$this->infotoadminemail("Hi EHCP Admin,<br><br>There appears to be an error with the server configuration regarding ports!", 'EHCP Services Ports Problem');
		}
	}

	function updateDiskQuota($domainname = '')
	{ # this function coded by deconectat
		global $skipupdatediskquota;
		if ($skipupdatediskquota) {
			echo "\n" . __FUNCTION__ . ": not updateing disk quota, because $skipupdatediskquota variable is set. \n";
			return;
		}

		$this->requireCommandLine(__FUNCTION__);
		$q = "select id,homedir,domainname from domains";
		if ($domainname <> '')
			$q .= " where domainname='$domainname'";

		$res = $this->query($q);
		echo "Starting " . __FUNCTION__ . "\n";
		foreach ($res as $dom) {
			$quota = $this->executeProg3("/usr/bin/nice -n 19 du -ms " . $dom['homedir']); # -ms: meausre MB
			#echo "\n---------------------\nQuota info: $quota \n---------------\n";
			$quota = explode("\t", $quota);
			$quotaused = trim($quota[0]);
			if ($quotaused == '')
				$quotaused = '0';
			echo "\nUpdated disk quota: " . $dom['domainname'] . ":$quotaused | " . $dom['homedir'] . "\n";
			$this->executeQuery("update domains set diskquotaused=$quotaused where id=" . $dom['id']);
		}
		$this->checkOverDiskQuota();
		echo "\nfinished " . __FUNCTION__ . "\n";
		return True;
	}

	function checkOverDiskQuota()
	{ # coded by deconectat, modified by ehcpdeveloper
		# update normal quota
		$this->executeQuery("update domains set diskquotaovernotified=0,status='active' where diskquota>=diskquotaused and status='overquota' ");

		# make passive, domains with high quota and who had been notified, and notify them of passify
		# panel admin is able to set type of action for over-quota domains, admain may just warn them, or disable domains automatically.
		# this may cause problem on some systems if updateDiskQuota calcluates disk quota wrong using du system command... so i left an option to disable this by admin.. default for turnoffoverquotadomains is disabled for now.

		if ($this->miscconfig['turnoffoverquotadomains'] <> '') {
			$this->executeQuery("update domains set status='overquota' where DATEDIFF(curdate(),diskquotaoversince)>graceperiod and diskquotaovernotified=1");
			$this->syncDomains();
			$warn = "Your site is disabled. Please contact your provider";
			$warn2 = "Your site will be disabled";
		} else {
			$warn = "";
			$warn2 = "Please solve this";
		}
		$footer = "\nSent from your panel, Easy Hosting Control Panel (https://ehcpforce.ezpz.cc), url:http://" . $this->dnsip . "\n";

		# warn people with high quota, and who were notified before..
		$res = $this->query("select * from domains  where DATEDIFF(curdate(),diskquotaoversince)>graceperiod and diskquotaovernotified=1");
		if ($res)
			foreach ($res as $dom) {
				$this->infoEmailToUserandAdmin($dom['email'], "Domain " . $dom['domainname'] . " is over quota!", "Panelusername:" . $dom['panelusername'] . ",Domain: " . $dom['domainname'] . " is over quota! You are using " . $dom['diskquotaused'] . "MB of your " . $dom['diskquota'] . "MB quota. $warn . $footer", false);
			}


		# warn people with high quota
		$res = $this->query("select d.domainname,d.diskquotaused,d.diskquota,d.graceperiod,p.email,p.panelusername from domains d,panelusers p where d.diskquota<d.diskquotaused and d.diskquota>0 and d.diskquotaovernotified=0 and d.panelusername=p.panelusername");
		if ($res)
			foreach ($res as $dom) {
				$this->infoEmailToUserandAdmin($dom['email'], "Domain " . $dom['domainname'] . " is over quota!", "Panelusername:" . $dom['panelusername'] . ",Domain: " . $dom['domainname'] . " is over quota! You are using " . $dom['diskquotaused'] . "MB of your " . $dom['diskquota'] . "MB quota. $warn2 in " . $dom['graceperiod'] . " days. $footer", false);
				$this->executeQuery("update domains set diskquotaovernotified=1, diskquotaoversince=CURDATE() where domainname='" . $dom['domainname'] . "'");
			}
	}


	function securedelete($files, $serverip)
	{
		// to be able to securely delete files. to prevent accidental deletion of crucial data.
		// once, i deleted some ;)
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		if (strpos($files, '..') !== false)
			return $this->errorText("Deleting the following files is forbidden: ($files)", True);

		$nodelete = array('', "/", "/var/www", $this->ehcpdir, $this->vhostsdir, "/bin", "/boot", "/cdrom", "/dev", "/ehcp", "/etc", "/home", "/initrd", "/initrd.img", "/lib", "/lost+found", "/media", "/mnt", "/opt", "/proc", "/root", "/sbin", "/srv", "/sys", "/tmp", "/usr", "/var", "/vmlinuz", "/web");
		foreach ($nodelete as $dir)
			$nd3[] = $dir . "/";
		if (in_array($files, $nodelete) or in_array($files, $nd3)) {
			return $this->errorText("Deleting the following files is forbidden: ($files)", True);
		}

		$nodelete2 = array("/bin", "/boot", "/cdrom", "/dev", "/ehcp", "/etc", "/home", "/initrd", "/initrd.img", "/lib", "/lost+found", "/media", "/mnt", "/opt", "/proc", "/root", "/sbin", "/srv", "/sys", "/tmp", "/usr", "/vmlinuz", "/web");
		foreach ($nodelete2 as $dir)
			$nd4[] = $dir . "/"; foreach ($nodelete2 as $dir) {
			$len = strlen($dir);
			$sub = substr($files, 0, $len);
			if (strstr($files, "/etc/vsftpd_user_conf/"))
				continue; # except for vsftpd_user_conf
			//$this->echoln("checked for delete: substr: $sub, dir:$dir ");

			if ($sub == $dir) {
				return $this->errorText("Deleting the following files is forbidden: ($files)", True); // this function already returns false... so, only one line of code...
			}
		}

		foreach ($nd4 as $dir) {
			$len = strlen($dir);
			$sub = substr($files, 0, $len);
			//$this->echoln("checked for delete: substr: $sub, dir:$dir ");
			if (strstr($files, "/etc/vsftpd_user_conf/"))
				continue; # except for vsftpd_user_conf

			if ($sub == $dir) {
				return $this->errorText("Deleting the following files is forbidden: ($files)", True);
			}
		}


		$this->echoln("checks complete.. removing files: $files");

		$cmds = array();
		$cmds[] = "rm -rvf $files";
		if (trim($serverip) == '')
			$serverip = 'localhost';

		return $this->execute_server_commands($serverip, $cmds);
	}

	function syncFtp()
	{
		// Only creates structure for custom FTP accounts with the homedir not empty check.
		$this->requireCommandLine(__FUNCTION__);
		passthru2("mkdir -p /etc/vsftpd_user_conf");

		$rs = $this->conn->Execute("select * from " . $this->conf['ftpuserstable']['tablename'] . " where homedir<>''");
		if ($rs) { # this part is only necessary with vsftpd,  # prepares non-standard home dir,
			echo "\n==========================================================================================\n";
			while (!$rs->EOF) {
				$homedir = $rs->fields['homedir'];
				$ftpusername = $rs->fields['ftpusername'];
				$panelusername = $rs->fields['panelusername'];
				if (!file_exists($homedir)) {
					passthru2("mkdir -p $homedir");
				}
				passthru2("chown -Rf " . $this->ftpowner . " " . $homedir);
				passthru2("chmod 775 -R " . $homedir);
				writeoutput2("/etc/vsftpd_user_conf/$ftpusername", "local_root=$homedir", 'w');
				$rs->MoveNext();
			}
		}
		return True;
	}

	function handlePasswordProtectedDirs()
	{
		$this->requireCommandLine(__FUNCTION__);

		// Get all password protected directories from the database and create what we need to make it work!
		$rs = $this->conn->Execute("select * from " . $this->conf['pwd_dirs_table']['tablename']);
		if ($rs) {
			echo "\n==========================================================================================\n";

			while (!$rs->EOF) {
				$id = $rs->fields['id'];
				$domainname = $rs->fields['domainname'];
				$domainpath = $rs->fields['domainpath'];
				$protected_dir = $rs->fields['protected_dir'];
				$username = $rs->fields['username'];
				$password = $rs->fields['password'];
				$conf = '';

				// Check to make sure the domainpath exists first... if it doesn't, we shouldn't do anything!
				if (file_exists($domainpath)) {
					// Create the password protected directory if it doesn't yet exist
					$protectedDirPath = $domainpath . "httpdocs/" . $protected_dir;
					if (!file_exists($protectedDirPath)) {
						mkdir($protectedDirPath, 0755, true);
					}

					// Get htaccess path
					$htaccessPath = $protectedDirPath . "/.htaccess";

					// Get authentication file path
					$authPath = $domainpath . $id;

					// Create authentication file
					$buildAuthFileCommand = "htpasswd -b -c '" . $authPath . "' '" . $username . "' '" . $password . "'";

					passthru2($buildAuthFileCommand, true, true);

					// Make sure perms are correct by using bash...
					passthru2("chown -R " . $this->wwwuser . ":" . $this->wwwgroup . " " . $protectedDirPath, true, true);
					passthru2("chmod 644 $htaccessPath", true, true);
					passthru2("chown " . $this->wwwuser . ":" . $this->wwwgroup . " " . $authPath, true, true);
					passthru2("chmod 644 $authPath", true, true);
				}

				$rs->MoveNext();
			}

		}
	}

	function handleResetSitesEnabledDefault()
	{
		$this->requireCommandLine(__FUNCTION__);

		if ($this->miscconfig['webservertype'] == "nginx") {
			if ($this->miscconfig['webservermode'] == "sslonly") {
				passthru2("cp $this->ehcpdir/etc/nginx_sslonly/default.nginx /etc/nginx/sites-enabled/default", true, true);
			} else if ($this->miscconfig['webservermode'] == "ssl") {
				passthru2("cp $this->ehcpdir/etc/nginx_ssl/default.nginx /etc/nginx/sites-enabled/default", true, true);
			} else {
				passthru2("cp $this->ehcpdir/etc/nginx_nonssl/default.nginx /etc/nginx/sites-enabled/default", true, true);
			}
		} else {
			if ($this->miscconfig['webservermode'] == "sslonly") {
				passthru2("cp $this->ehcpdir/etc/apache2_sslonly/fork/default /etc/apache2/sites-enabled/default", true, true);
			} else if ($this->miscconfig['webservermode'] == "ssl") {
				passthru2("cp $this->ehcpdir/etc/apache2_ssl/fork/default /etc/apache2/sites-enabled/default", true, true);
			} else {
				passthru2("cp $this->ehcpdir/etc/apache2/default /etc/apache2/sites-enabled/default", true, true);
			}
		}

		return true;
	}

	function handleResetMainWebServerConf()
	{
		$this->requireCommandLine(__FUNCTION__);

		if ($this->miscconfig['webservertype'] == "nginx") {
			if ($this->miscconfig['webservermode'] == "sslonly") {
				passthru2("cp $this->ehcpdir/etc/nginx_sslonly/nginx.conf /etc/nginx/nginx.conf", true, true);
			} else if ($this->miscconfig['webservermode'] == "ssl") {
				passthru2("cp $this->ehcpdir/etc/nginx_ssl/nginx.conf /etc/nginx/nginx.conf", true, true);
			} else {
				passthru2("cp $this->ehcpdir/etc/nginx_nonssl/nginx.conf /etc/nginx/nginx.conf", true, true);
			}

			// Update the variables
			$this->updateNginxConfVariablesInFile();
		} else {
			if (file_exists("/etc/apache2/apache2.conf.bk_used_for_EHCP_DO_NOT_DELETE")) {
				passthru2("cp /etc/apache2/apache2.conf.bk_used_for_EHCP_DO_NOT_DELETE /etc/apache2/apache2.conf", true, true);
			}
		}

		return true;
	}

	function updateNginxConfVariablesInFile()
	{
		$this->requireCommandLine(__FUNCTION__);

		$nginxContents = file_get_contents("/etc/nginx/nginx.conf");
		$nginxContents = str_replace(array('{wwwuser}', '{wwwgroup}'), array($this->wwwuser, $this->wwwgroup), $nginxContents);
		file_put_contents("/etc/nginx/nginx.conf", $nginxContents);
	}

	function handleDKIMConfig($action, $domain)
	{
		$this->requireCommandLine(__FUNCTION__);
		if ($action == "remove") {
			echo "Removing DKIM configuration for the domain of " . $domain . "...\n";
		} else if ($action == "add") {
			echo "Configuring DKIM global configuration for the domain of " . $domain . "...\n";
		} else {
			echo "INVALID ACTION RECEIVED FOR HANDLE DKIM!\n";
			return false;
		}

		$command = 'bash /var/www/new/ehcp/scripts/install_dkim_postfix.sh "' . $domain . '" "' . $action . '"';
		echo "Running command: " . $command . "\n";
		$out = shell_exec($command);

		echo "OUTPUT from the /var/www/new/ehcp/scripts/install_dkim_postfix.sh script is: " . $out . "\n";

		if (!empty($out) && $action == "add") {
			echo "Adding DKIM TXT DNS record for the domain of " . $domain . "..." . "\n";
			echo "Public key for TXT DNS record is " . $out . "\n";
			// Need to add custom TXT DNS 
			// $publicKeyDKIMStr = 'mail._domainkey IN TXT "v=DKIM1; k=rsa; p=' . $out . '"' . "\n";
			$publicKeyDKIMStr = 'mail._domainkey.' . $domain . '. IN TXT "v=DKIM1; k=rsa; p=' . $out . '"';
			$this->executeQuery("insert into " . $this->conf['customstable']['tablename'] . " (domainname,name,value,comment) values ('" . $domain . "','customdns','" . $this->escape($publicKeyDKIMStr) . "','A DKIM public key record')", 'manage_dkim');
			$this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " SET dns_serial = dns_serial + 1 where domainname='$domain'");
		}

		if ($action == "remove") {
			echo "Deleting DKIM TXT DNS record for the domain of " . $domain . "..." . "\n";
			$sql = "SELECT * FROM " . $this->conf['customstable']['tablename'] . " WHERE name='customdns' AND value LIKE 'mail._domainkey%' and domainname = '" . $domain . "' ORDER BY ID DESC";
			$rs = $this->query($sql);
			if ($rs !== false) {
				$id = $rs[0]["id"];
				if (isset($id) && !empty($id)) {
					$sql = "delete from " . $this->conf['customstable']['tablename'] . " where id='" . $id . "' limit 1";
					$this->executeQuery("update " . $this->conf['domainstable']['tablename'] . " SET dns_serial = dns_serial + 1 where domainname='$domain'");
					echo "Running SQL command of: " . $sql . "\n";
					$this->executeQuery($sql);
				}
			}
		}

		$this->addDaemonOp("syncdns", '', '', '', 'sync dns');

		return true;
	}

	function handleCustomSSLCertsForDomains()
	{
		$this->requireCommandLine(__FUNCTION__);

		// Get all domains using custom ssl settings
		$rs = $this->conn->Execute("select * from " . $this->conf['domainstable']['tablename'] . " WHERE ssl_cert IS NOT NULL AND ssl_cert_key IS NOT NULL");
		if ($rs) {
			echo "\n==========================================================================================\n";

			while (!$rs->EOF) {
				$domainname = $rs->fields['domainname'];
				$homedir = $rs->fields['homedir'];
				$ssl_cert = $rs->fields['ssl_cert'];
				$ssl_key = $rs->fields['ssl_cert_key'];
				$ssl_chain = $rs->fields['ssl_cert_chain'];
				$dirUsedForSSL = $homedir . "/phptmpdir/";
				$sslCertFile = $dirUsedForSSL . "server.crt";
				$sslCertKeyFile = $dirUsedForSSL . "server.key";
				$sslCertChainFile = $dirUsedForSSL . "chain.crt";
				$sslCertMixedFile = $dirUsedForSSL . "mixed.crt";

				// Check to make sure the domainpath exists first... if it doesn't, we shouldn't do anything!
				if (file_exists($dirUsedForSSL)) {

					// Write cert files
					if (!empty($ssl_cert)) {
						writeoutput2($sslCertFile, $ssl_cert, "w+");
					}

					if (!empty($ssl_key)) {
						writeoutput2($sslCertKeyFile, $ssl_key, "w+");
					}

					if (!empty($ssl_chain)) {
						writeoutput2($sslCertChainFile, $ssl_chain, "w+");
					} else {
						// Erase the file because we don't want it anymore
						$this->bashDelete($sslCertChainFile);
						$this->bashDelete($sslCertMixedFile);
					}

					if (file_exists($sslCertFile) && file_exists($sslCertChainFile)) {
						// For nginx
						passthru2("cat $sslCertFile $sslCertChainFile > $sslCertMixedFile", true, true);
					}

					// Make sure perms are correct by using bash...
					if (file_exists($sslCertFile)) {
						passthru2("chown root:root " . $sslCertFile, true, true);
						passthru2("chmod 755 " . $sslCertFile, true, true);
					}

					if (file_exists($sslCertKeyFile)) {
						passthru2("chown root:root " . $sslCertKeyFile, true, true);
						passthru2("chmod 755 " . $sslCertKeyFile, true, true);
					}

					if (file_exists($sslCertChainFile)) {
						passthru2("chown root:root " . $sslCertChainFile, true, true);
						passthru2("chmod 755 " . $sslCertChainFile, true, true);
					}

					if (file_exists($sslCertMixedFile)) {
						passthru2("chown root:root " . $sslCertMixedFile, true, true);
						passthru2("chmod 755 " . $sslCertMixedFile, true, true);
					}

				}

				$rs->MoveNext();
			}

		}
		return True;
	}

	function rebuildCrontab()
	{
		$this->requireCommandLine(__FUNCTION__);

		$createBackupScript = "/var/www/new/ehcp/scripts/ehcp_backup/create_ehcp_backup.sh";
		$uploadScript = "/var/www/new/ehcp/scripts/ehcp_backup/uploadBackup.sh";
		$ehcpCronFile = "/var/www/new/ehcp/ehcpcron.conf";
		passthru3("rm $ehcpCronFile");
		passthru3("touch $ehcpCronFile");

		// Get remote backup operations list
		$rs = $this->conn->Execute("select * from " . $this->conf['remote_backups_table']['tablename']);
		if ($rs) {

			echo "\n==========================================================================================\n";
			while (!$rs->EOF) {
				$dayofweek = $rs->fields['dayofweek'];
				$time = $rs->fields['time'];
				$transfer_method = $rs->fields['transfer_method'];
				$transfer_host = $rs->fields['transfer_host'];
				$transfer_port = $rs->fields['transfer_port'];
				$transfer_login = $rs->fields['transfer_login'];
				$transfer_pass = $rs->fields['transfer_pass'];
				$transfer_encrpytion_password = $rs->fields['encryption_pass'];
				$name = $rs->fields['name'];

				// Append server IP address to name to ensure unique backup file names from multiple servers (if there are any)
				$name = $this->getIPAsStringNoPeriods($this->singleserverip) . "_" . $name;

				$backupScriptCommand = 'echo "0 ' . $time . ' * * ' . $dayofweek . ' ' . $createBackupScript . ' ' . $name . '" >> ' . $ehcpCronFile;
				$timeInOneHour = ($time + 1);
				$uploadScriptCommand = 'echo "0 ' . $timeInOneHour . ' * * ' . $dayofweek . ' ' . $uploadScript . ' ' . $transfer_method . ' \'' . $transfer_login . '\' \'' . escapeDollarSignsBash($transfer_pass) . '\' ' . $transfer_host . ' ' . $transfer_port . ' \'' . escapeDollarSignsBash($transfer_encrpytion_password) . '\'" >> ' . $ehcpCronFile;

				passthru2($backupScriptCommand, true, true);
				passthru2($uploadScriptCommand, true, true);
				$rs->MoveNext();
			}

		}

		// Get crontabs added by user:
		$rs = $this->conn->Execute("select * from " . $this->conf['cronjobs_table']['tablename']);
		if ($rs) {

			echo "\n==========================================================================================\n";
			while (!$rs->EOF) {
				$dayofweek = $rs->fields['dayofweek'];
				$time = $rs->fields['time'];
				$command = $rs->fields['script'];

				$cronJobCommand = 'echo "0 ' . $time . ' * * ' . $dayofweek . ' ' . $command . '" >> ' . $ehcpCronFile;
				passthru2($cronJobCommand, true, true);
				$rs->MoveNext();
			}

		}
		passthru3("/var/www/new/ehcp/handleCronjobs.sh $ehcpCronFile");
		return True;
	}

	function getIPAsStringNoPeriods($ip)
	{
		$str = "";
		if (!empty($ip)) {
			if (stripos($ip, ".") != false) {
				$ip = str_replace(".", "_", $ip);
				$str .= $ip;
			}
		}

		return $str;
	}

	function daemonftp($action, $info, $info2, $info3 = '')
	{


		$this->requireCommandLine(__FUNCTION__);
		switch ($action) {
			case "delete": // deleting an ftp account
				$this->securedelete($info, $info3);
				return True;
				break;
			case 'add':
				passthru2("mkdir -p " . $info);
				passthru2("chown -Rf $this->ftpowner $info");
				$this->syncFtp();
				return True;
				break;

			case 'multiserver_add':
				$this->commands = array();
				$this->commands[] = "mkdir -p $info";
				$this->commands[] = "chown -Rf $this->ftpowner $info";
				$this->execute_server_commands($info2, $this->commands);
				$this->commands = array(); # make sure it is empty.
				return True;
				break;

			case $this->status_passive: // changing status to passive
				passthru2("chown nobody:nogroup -Rf " . $info);
				passthru2("chmod og-rw -Rf " . $info);
				return True;
				break;
			case $this->status_active:
				passthru2("chown $this->ftpowner -Rf $info");
				passthru2("chmod a+r -Rf " . $info);
				return True;
				break;


		}
	}


	function add_daemon_op($named_params)
	{
		$this->debugecho(__FUNCTION__ . ": sending info to daemon (" . $named_params['op'] . ":" . $named_params['action'] . ")", 1, false);
		$this->debugecho($named_params, 3, false);


		$ret = $this->executeQuery("insert into operations (op,user,ip,action,info,info2,info3,tarih) values ('" . $named_params['op'] . "','$this->activeuser','$this->clientip','" . $named_params['action'] . "','" . $named_params['info'] . "','" . $named_params['info2'] . "','" . $named_params['info3'] . "',now())", ' sending info to daemon (' . $opname . ')');
		# $ret=$this->conn->AutoExecute('operations',$named_params,'INSERT'); # this does not work.

		#$this->debugecho($this->queries,3,false);
		return $ret;

		#return $this->executeQuery("insert into operations (op,action,info,info2,info3,tarih) values ('$op','$action','$info','$info2','$info3','')",' sending info to daemon ('.$opname.')');
	}

	function addDaemonOp($op, $action, $info, $info2 = '', $opname = '')
	{
		return $this->executeQuery("insert into operations (op,user,ip,action,info,info2,tarih) values ('$op','$this->activeuser','$this->clientip','$action','$info','$info2',NOW())", ' sending info to daemon (' . $opname . ')');
	}

	function check_remote_ssh_connection($server)
	{
		return True; # to be coded later
	}

	function daemon_backup_domain($info)
	{
		$this->requireCommandLine(__FUNCTION__);

		$domaininfo = $this->domaininfo = $this->getDomainInfo($info);
		echo __FUNCTION__ . " basliyor... for $info" . print_r($domaininfo);
		$backupbasedir = "/var/www/new/backups";
		@mkdir($backupbasedir);
		chdir($backupbasedir);

		$where = $domaininfo['homedir'] . '/httpdocs';
		$filename = "$backupbasedir/$info-backup-" . date('Y-m-d_H-i-s') . '_' . rand(1, 1000) . '_' . rand(1, 1000) . '.tgz';
		$files = "$backupbasedir/$info/$info-backup-files.tgz";
		$mysql = "$backupbasedir/$info/$info-backup-mysql.txt";
		@mkdir("$backupbasedir/$info");

		$cmd = "tar -zcvf $files $where";

		$this->executeQuery("update backups set filename='$filename',status='backup started by daemon',date=now() where domainname='$info' and (filename is null or filename='' or status like '%backup started%')");
		passthru2($cmd);
		$this->backup_databases("domainname='$info'", $mysql);
		passthru2("tar -zcvf $filename $info"); # tar again files and mysql which are in dir of $domainname=$info
		passthru2("rm -rf $backupbasedir/$info");

		$this->executeQuery("update backups set filename='$filename',status='backup finished by daemon, ready to download',date=now() where domainname='$info' and filename='$filename'");
		chdir($this->ehcpdir);
		return True;
	}

	function daemondomain($action, $info, $info2 = '', $info3 = '')
	{ // domain operations in daemon mode.

		/*
		   action: add or delete, what to do
		   info: domain to delete/add
		   info2: whatever info needed, such as user to which domain belongs, or changed: homedir of domain, that is: /var/www/vhosts/ftpusername/domain.com
		   */

		$this->requireCommandLine(__FUNCTION__);
		$base = $this->conf['vhosts'];
		/*
		   domain path will be like: /var/www/vhosts/ftpusername/domain.com
		   /var/www/vhosts/ftpusername/domain.com will be stored as homedir in domains table,
		   one user will may have multiple domains with single ftp acount.
		   */

		$info = $domainname = trim($info); # domainname
		$info2 = trim($info2);
		$info3 = trim($info3);
		$homedir = $info2;

		$this->echoln2("(daemondomain) domain operation starts: " . $info . ", homedir:" . $homedir);

		echo "\n" . __FUNCTION__ . ":action:($action),info:($info),info2:($info2),info3:($info3)\n";

		switch ($action) {
			case "multiserver_add_domain":
				$info3 = trim($info3);
				if ($info3 == '') {
					echo "\n info3 is empt. cannot complete $action \n";
					return True; # actually should be false, left True during development stage.
				}

				if (!$this->check_remote_ssh_connection($info3))
					return false; # should be equivalent, but do not work: $this->check_remote_ssh_connection($info3) || return false;

				$this->commands = array();
				# all domain dirs should be setup here..
				$this->initialize_domain_files($homedir);
				$this->commands[] = "echo 'Under Construction-multi-server-ehcp' > $homedir/httpdocs/index.php";
				$this->execute_server_commands($info3, $this->commands);
				$this->commands = array(); # ensure it is empty after our job finished.

				return True;
				break;

			case "add":
				# all domain dirs should be setup here..
				#$params=array('domainname'=>$domainname,'homedir'=>$homedir);			
				#$this->initializeDomainFiles($params); # done in syncdomains below.
				$this->syncDomains('', $domainname); # only sync newly added domain. 
				return True;
				break;
			case "delete":
				echo "deleting: $info \n";
				if ($info3 == '') {
					$info3 = 'localhost';
				}

				$this->commands = array();
				$this->commands[] = "rm -Rvf $homedir";
				$this->commands[] = "rm -Rvf " . $this->conf['namedbase'] . '/' . $info;
				$this->execute_server_commands($info3, $this->commands);
				return True;
				break;

			case 'addsubdomain':
				# single caller in function addsubdomain: 
				# $success=$success && $this->add_daemon_op(array('op'=>'daemondomain','action'=>'addsubdomain','info'=>$subdomain,'info2'=>$domainname,'info3'=>$homedir));
				passthru2("mkdir -p $info3");

				$index = $this->loadTemplate('defaultindexforsubdomains', False);
				if (trim($index) == '') {
					$index = '<?php
	$request = parse_url($_SERVER[\'REQUEST_URI\']);
	$path = $request["path"];
	$result = rtrim(str_replace(basename($_SERVER[\'SCRIPT_NAME\']), \'\', $path), \'/\');
	if(!empty($result) && $result != "/" && !file_exists(__DIR__ . $result)){
		header("HTTP/1.0 404 Not Found");
		include \'error_page.html\';
		exit();
	}
?>
						<div style="text-align: center; font-family: \'arial\';">
							<h2>Subdomain Under Construction</h2>
							<h4><a href="https://ehcpforce.ezpz.cc" target="_blank">EHCP Force Edition</a></h4>
						</div>';
				}

				if ((!file_exists($info3 . "/index.html")) or (!file_exists($info3 . "/index.htm"))) {
					$this->write_file_if_not_exists($info3 . "/index.php", $index);
					if (!file_exists("$homedir/httpdocs/$f")) {
						passthru2("cp -f \"error_page.html\" \"$info3\"");
					}
				}

				return True;
				break;

			case "delsubdomain":
				echo "deleting: $info \n";
				passthru2("rm -Rvf $homedir");
				return True;
				break;

			default:
				echo "undefined action in " . __FUNCTION__ . ": $action";
				return false;
		}
	}

	function isOpThatOnlyNeedsToRunOnce($op)
	{
		$op = strtolower($op);
		$runOnce = false;
		$manualListOfOpsThatRunOnce = array('updatehostsfile', 'rebuild_crontab', 'process_pwd_dirs', 'process_ssl_certs', 'fixapacheconfigssl', 'fixapacheconfigsslonly', 'fixapacheconfignonssl', 'update_ez_install', 'loadconfig');

		// Allow ops that start with daemon to run more than once
		if (startsWith($op, "sync") || startsWith($op, "new_sync") || in_array($op, $manualListOfOpsThatRunOnce)) {
			$runOnce = true;
		}

		return $runOnce;
	}

	function runOpWrapper($op)
	{ // normal operations
		$this->requireCommandLine(__FUNCTION__); // run from commandline.
		if ($this->runOp(trim($op['op']))) {
			echo "\ndaemon->runop success ** \n";
			$this->executeQuery("update operations set try=try+1,status='ok' where id=" . $op['id'] . " limit 1", 'update operations set status ok.');
		} else { // increase try count
			$q = "update operations set try=try+1,status='failed' where id=" . $op['id'] . " limit 1";
			echo "\ndaemon->runop failure **** : $q\n";
			$this->executeQuery($q, ' increasing try count');
		}
	}

	function runOp2Wrapper($op)
	{ // for daemon ops
		$this->requireCommandLine(__FUNCTION__); // run from commandline.
		if ($this->runop2(trim($op['op']), $op['action'], $op['info'], $op['info2'], $op['info3'])) {
			echo "\ndaemon->runop2 success ** \n";
			$this->executeQuery("update operations set try=try+1,status='ok' where id=" . $op['id'] . " limit 1", ' updating operations ');
		} else {
			$q = "update operations set try=try+1,status='failed' where id=" . $op['id'] . " limit 1";
			echo "\ndaemon->op2 failure **** : $q\n";
			$this->executeQuery($q, ' daemon increasing try count');
		}
	}

	function daemon()
	{
		set_time_limit(0); # run forever... i hope... :)
		$this->requireCommandLine(__FUNCTION__); // run from commandline.
		$this->echoln2("Running daemon now..");
		$sleep_interval = 10;

		$this->output .= "Daemonized.." . $this->myversion . "\n__________________________\n\n";
		$i = 1;
		$this->updateWebstats();
		passthru2("chmod a+x /var/spool/postfix/var/run/saslauthd"); # for the bug/problem at http://www.ehcp.net/?q=node/149#comment-668
		@mkdir($this->ehcpdir . '/webmail'); # make this if not present
		@mkdir($this->ehcpdir . '/upload');
		$this->executeProg3("chmod a+w " . $this->ehcpdir . '/upload');
		$this->executeProg3("chmod a+w " . $this->ehcpdir . '/LocalServer.cnf');
		
		// Check tables on daemon init
		$this->checkTables();

		while (True) { // run forever
			// Set up the array to prevent duplicate operations from running
			if (isset($opArray)) {
				unset($opArray);
			}
			if (isset($daemonOpArray)) {
				unset($daemonOpArray);
			}
			$opArray = array();
			$daemonOpArray = array();
			$confReloaded = false;


			// Get daemon operations and execute them
			print_r($rs = $this->query("select * from operations where ((status is null)or(status<>'ok' and status<>'duplicate'))and(try<2)and(info<>'')"));
			if ($rs) {
				$daemonOpIndex = 0;
				foreach ($rs as $op) {
					
					// Make sure config is loaded before executing daemon operations since it's critical the latest information is loaded and present.
					if($daemonOpIndex == 0){
						if(!$confReloaded){
							$this->loadConfig();
							$confReloaded = true;
							if (!in_array('loadconfig', $opArray)) {
								$opArray[] = 'loadconfig';
							}
						}
					}
					
					$operationName = trim($op['op']);
					if ($this->isOpThatOnlyNeedsToRunOnce($operationName)) {
						if (!in_array($operationName, $daemonOpArray)) {
							$this->output = '';
							$this->runOp2Wrapper($op);
							$daemonOpArray[] = $operationName;
						} else {
							// The same operation has already been run in this loop, so don't run it again!
							$q = "update operations set try=try+1,status='duplicate' where id=" . $op['id'] . " limit 1";
							echo "\ndaemon->op2 failure - duplicate operation in daemon loop. Ignoring operation " . $operationName . "... **** : $q\n";
							$this->executeQuery($q, ' increasing try count');
						}
					} else {
						$this->runOp2Wrapper($op);
					}
					$daemonOpIndex++;
				}
				echo $this->output;
			} else {
				//$this->error_occured("daemon main loop");
				if ($rs === false)
					$this->tryReconnect();
			}


			// Query the database and run operations (some daemon ops do not follow convention above)
			print_r($rs = $this->query("select * from operations where ((status is null)or(status<>'ok' and status<>'duplicate'))and(try<3)and(info is null or info='') ORDER BY try ASC"));

			// Read list of operations and execture them. 
			if ($rs) {
				$daemonOpIndex = 0;
				foreach ($rs as $op) {
					
					// Make sure config is loaded before executing daemon operations since it's critical the latest information is loaded and present.
					if($daemonOpIndex == 0){
						if(!$confReloaded){
							$this->loadConfig();
							$confReloaded = true;
							if (!in_array('loadconfig', $opArray)) {
								$opArray[] = 'loadconfig';
							}
						}
					}
					
					$operationName = trim($op['op']);
					if ($this->isOpThatOnlyNeedsToRunOnce($operationName)) {
						#$this->executeQuery("update operations set try=try+1 where id=".$op['id']." limit 1",' updating operations, increasing try count ');
						if (!in_array($operationName, $opArray)) {
							$this->output = '';
							$this->runOpWrapper($op);
							echo $this->output;
							$opArray[] = $operationName;
						} else {
							// The same operation has already been run in this loop, so don't run it again!
							$q = "update operations set try=try+1,status='duplicate' where id=" . $op['id'] . " limit 1";
							echo "\ndaemon->runop duplicate in daemon loop. Ignoring operation " . $operationName . "... **** : $q\n";
							$this->executeQuery($q, ' increasing try count');
						}
					} else {
						$this->runOpWrapper($op);
					}
					$daemonOpIndex++;
				}
			} else {
				//$this->error_occured("daemon main loop2");
				if ($rs === false)
					$this->tryReconnect();
			}

			// Write out which loop number we're at
			echo "\nehcp " . $this->myversion . "- Daemon loop number:$i  Datetime:(" . date_tarih() . ")\n-----------daemon suspended for $sleep_interval sec ---------pwd:(" . getcwd() . ") \n";

			// Do special operations
			if ($i % 5 == 0) {
				# Every 5 loops
				# if mysql goes away while daemon runs, this will refresh connection, so, operations can continue..
				$this->check_mysql_connection();
			}

			if ($i % 50 == 0) {
				# Every 50 loops
				$this->checkDynDns();
				$this->daemonQuotaCheck();
				$this->call_func_in_module('Vps_Module', 'vps_check_state');
			}

			if ($i % 200 == 0) {
				# Every 200 Loops
				$this->updateWebstats();

				// Reset i to 0 so it's 1 when it runs again
				$i = 0;
			}

			// Sleep and increment loop
			sleep($sleep_interval);
			$i++;
			// infinite loop...
		}
	}

	function getRemoteIPAddressFromSite($site)
	{
		$str = "";
		if (isset($site) && !empty($site)) {
			$str = trim(file_get_contents($site));
			// Look for an IP
			if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $str, $ip_match)) {
				$ip = $ip_match[0];
				if ($this->isValidIP($ip) && !$this->isPrivateIp($ip)) {
					$str = $ip;
				}
			}
		}
		return $str;
	}

	function get_outside_ip()
	{
		$i = 0;
		$ip = "";
		$sitesToCheck = array('http://grabip.tk', 'http://www.ipchicken.com/', 'http://www.tracemyip.org/', 'http://what-is-my-ip.net/?text');
		while (empty($ip) && $i < count($sitesToCheck)) {
			$ip = $this->getRemoteIPAddressFromSite($sitesToCheck[$i]);
			$i++;
		}

		return $ip;
	}

	function checkDynDns()
	{
		# this only works if current url is reachable on web,
		#$url="http://checkip.dyndns.com";
		if ($this->miscconfig['updatednsipfromweb'] == '')
			return false;

		$str = $this->get_outside_ip();
		print __FUNCTION__ . ": dyndns information from web: ($str) \n";
		if ($str == '') {
			print "dyn dns information could not get from web ($url) \n";
		} else {
			if ($this->miscconfig['dnsip'] <> $str) {
				print "updating dns information according to one obtained from web as ($str)\n";
				# Update freedns.afraid.org:
				if ($this->miscconfig['freednsidentifier'] <> '') {
					# thanks to Kris Sallee http://sallee.us for contribution.
					echo "updating freedns.afraid.org \n";
					$updateurl = "https://freedns.afraid.org/dynamic/update.php?" . $this->miscconfig['freednsidentifier'];
					$update = file_get_contents($updateurl);
				}

				# Update ehcp configs
				$this->setConfigValue('dnsip', $str);

				// DNSIP Changed, so reload the config
				$this->loadConfigWithDaemon();

				$this->fixMailConfiguration(); # fix everything related to dns ip
			} else {
				print "Your Dynamic IP Address Has NOT Changed!\n";
			}
		}

	}

	function daemonQuotaCheck()
	{ #updatequota
		# checks quota at regular intervals, as defined in options/misc table
		$this->requireCommandLine(__FUNCTION__);

		$lastupdate = trim($this->miscconfig['lastquotaupdate']);
		$tarih = date_tarih();

		if ($lastupdate == '') {
			$this->setConfigValue('lastquotaupdate', $tarih);
			$this->loadConfig();
		}

		# calculte if last update is more than update interval
		$fark = timediffhrs($this->miscconfig['lastquotaupdate'], $tarih);
		if ($fark > $miscconfig['quotaupdateinterval']) {
			echo "\nQuota update needed...\n";
			$this->setConfigValue('lastquotaupdate', $tarih);
			$this->loadConfig();
			$this->updateDiskQuota();
		}
		return True;
	}

	function tryReconnect()
	{
		$this->conn->close();
		print "Trying to re-connect to the MySQL EHCP database...\n";
		if ($this->connectTodb2()) {
			print "\n\nReconnected to the MySQL EHCP database successfully!  Reloading config from the database now as well.\n";
			$this->loadConfig();
		} else {
			echo "\n\nehcp->cannot re-connect to mysql db...\n";
			exit();
		}
	}

	function syncAll()
	{
		$this->requireCommandLine(__FUNCTION__);

		return (
			$this->syncDomains() and
			$this->syncDns()
		);
	}

	function checkdaemon()
	{
		# first, check if this is able to see root processes,
		$res = executeprog("ps aux | grep root | grep -v grep | grep init ");
		if (strlen($res) < 10) {
			$this->warnings .= "<b>it seems that this process is not able to see all processes, so, i cannot check if ehcp daemon is running...This does not mean an error, you only need to check your ehcp daemon manually</b>";
			return false; # then
		}

		// check if daemon is running.
		$res = executeprog("ps aux | grep 'php index.php' | grep root |  grep -v grep ");
		if (strlen($res) > 10)
			return True;
		else {
			$this->warnings .= "<font size=+1><br><b>Attention! <br> ehcp daemon not running !<br>
Please run it from command/line by: <br>

sudo service ehcp start <br>

<br /></b></font>";
			if (!$this->isloggedin)
				$this->infotoadminemail('Hi EHCP Admin,<br><br>The EHCP Daemon is not running!<br>' . $this->clientip, 'EHCP Daemon Not Running On ' . $this->clientip); # with if, reduce mail traffic..
			return false;
		}
	}

	function getMailServer()
	{
		if ($this->singleserverip <> '')
			return $this->singleserverip;
		else
			$this->output .= "<b>mail server is not defined</b>";
		# serverplan oku, ona gore server adresini al..
	}

	function getDnsServer()
	{
		if ($this->singleserverip <> '')
			return $this->singleserverip;
		else
			$this->output .= "<b>dns server is not defined</b>";
		# serverplan oku, ona gore server adresini al..
	}

	function getWebServer()
	{
		// Check for dynamic dns
		$this->checkDynDns();

		if ($this->singleserverip <> '') {
			$ret = $this->singleserverip;

			if ($this->miscconfig['localip'] <> '' and $this->miscconfig['dnsip'] <> $this->miscconfig['localip']) {
				$ret = $this->miscconfig['localip']; # Case webserver*: for private ip's that are used in local nets,(which are nat'ed by modem/router to outer world) otherwise, apache cannot bind to real ip, which is not assigned to server.
			}

			if ($this->miscconfig['activewebserverip'] <> '')
				$ret = $this->miscconfig['activewebserverip'];

			return $ret;
		} else {
			$this->output .= "<b>web server is not defined</b>";
		}
		# serverplan oku, ona gore server adresini al..
	}

	function get_webserver_real_ip()
	{
		# get extrenal real ip of webserver. other function returns local ip of server for apache.
		if ($this->singleserverip <> '') {
			return $this->singleserverip;
		} else {
			$this->output .= "<b>web server is not defined</b>";
		}
	}


	function dnsZoneFiles($arr)
	{ // for daemon mode
		# reverse dns burda zone dosyalarini olusturmali.. http://langfeldt.net/DNS-HOWTO/BIND-9/DNS-HOWTO-5.html
		$this->requireCommandLine(__FUNCTION__);
		$success = True;

		//$this->output.=print_r2($arr);
		//print_r($arr);
		$alanlar = alanlarial($this->conn, "domains");
		$replacealanlar = arrayop($alanlar, "strop"); # puts each field in {}
		$replacealanlar[] = '{customdns}';

		// Get master DNS template
		$dnstemplatefile = file_get_contents($this->dnszonetemplate);

		$mailserverip = $this->getMailServer();
		$dnsserverip = $this->getDnsServer();
		$webserverip = $this->get_webserver_real_ip(); # burada aslinda birden çok IP almasi lazim. 

		if ($this->isPrivateIp(array($mailserverip, $dnsserverip))) {
			$mesaj = "Hi EHCP Admin,<br><br>Your EHCP Server is using a private IP address for either the mail, DNS, or webserver configuration (maybe all of them): $mailserverip, $dnsserverip, $webserverip<br>This may cause problems!";
			$subject = "DNS IP Problem on $dnsserverip";
			$this->infotoadminemail($mesaj, $subject);
		}

		echo __FUNCTION__ . ": mailserverip: $mailserverip, dnsserverip: $dnsserverip, webserverip: $webserverip \n";

		foreach ($arr as $ar1) {
			#farkli IP lerde host edilen domainler icin
			list($webserver1) = explode(',', $ar1['webserverips']); # sadece ilk ip yi al, aslinda birden cok IP yi de alabilmesi lazim.

			# assign ip addresses for different services..
			if ($ar1['serverip'] <> '') { # single ip if hosted in a single place,
				$mailip = $webip = $dnsip = $ar1['serverip'];
			} else {
				$mailip = $mailserverip;
				$dnsip = $dnsserverip;
				$webip = ($webserver1 == '' ? $webserverip : $webserver1); #use IP from webserverips field of domains table, if not empty. 
			}

			$this->echoln2("yaziyor: " . $ar1["domainname"] . " mailip/webip/dnsip : $mailip/$webip/$dnsip");

			$dnstemp = $ar1['dnstemplate'];
			if ($dnstemp == '')
				$dnstemp = $dnstemplatefile; // read dns info from template file, if not written to db..
			$dnstemp = str_replace($replacealanlar, $ar1, $dnstemp); // replace domain fields,

			#$temp=str_replace(array('{serial}',"{ip}","{dnsemail}"),array(rand(1000,2000),$this->conf['dnsip'],$this->conf['dnsemail']),$temp); // replace serial,ip,dnsemail etc.
			# if php bug occurs because of date, above line may be used... http://bugs.php.net/bug.php?id=44481


			# multiserver a gore ayarlanacak: {dnsip},{webip},{mailip}
			#

			# these codes are for transition to a multi-server environment... will be implemented step by step..

			// Pick serial number # by earnolmartin
			if (!is_null($ar1["dnsmaster"])) {
				// Will force it to pull updates because the master will have a larger serial number.
				$serialNum = 1;
			} else {
				$serialNum = $ar1['dns_serial'];
			}
			# end earnolmartin

			$dnstemp = str_replace(array('{mailip}', '{dnsip}', '{webip}', '{serial}', "{ip}", "{dnsemail}"), array($mailip, $dnsip, $webip, $serialNum, $this->conf['dnsip'], $this->conf['dnsemail']), $dnstemp);

			# lokalden erisenler icin ayri bir dns, dns icinde view olusturulabilir buraya bak: http://www.oreillynet.com/pub/a/oreilly/networking/news/views_0501.html
			# amac: bir networkde server varsa, o network icinden erisenler icin bu bir local server dir. her desktop da ayri ayri hosts ayari girmek yerine, sunucu bunlara real degil, lokal ip doner.
			# bu sayede, kucuk-orta isletmeler icin, sunucunun lokalden cevap vermesi saglanir.. veya dns icinde view destegi, birden cok konfigurasyon v.b...
			# to translate Turkish comments, use google translate..

			$dnstemplocal = str_replace(array('{mailip}', '{dnsip}', '{webip}', '{serial}', "{ip}", "{dnsemail}"), array($mailip, $dnsip, $webip, $serialNum, $this->conf['dnsip'], $this->conf['dnsemail']), $dnstemp);

			# $temp=str_replace(array('{serial}',"{ip}","{dnsemail}"),array(rand(1,1000),$this->conf['dnsip'],$this->conf['dnsemail']),$temp); // replace serial,ip,dnsemail etc.   Ymds hata veriyordu iptal ettim. bu sorunla ilgilenilecek...
			// verdigi hata: Fatal error: date(): Timezone database is corrupt - this should *never* happen!  thats why i cannot use date in daemon mode... this seems a php bug.., for tr locale

			$zoneFile = $this->conf['namedbase'] . '/' . $ar1["domainname"];

			$success = $success and writeoutput2($zoneFile, $dnstemp, "w");

			// Fix perms on the bind file
			$this->fixBindFilePerms($zoneFile);

			// If slave domain, retransfer the zone , earnolmartin
			if (!is_null($ar1["dnsmaster"])) {
				passthru2("rndc retransfer " . $ar1["domainname"]);
			}

			#$success=$success and writeoutput2($this->conf['namedbase'].'/'.$ar1["domainname"].".local",$dnstemplocal,"w"); # bu kisim henuz tamamlanmadi, yani lokal destegi..

		}
		return $success;
	}

	function dnsNamedConfFile($arr)
	{ // for daemon mode
		# $out="options { directory \''.$this->wwwbase."\";}";
		# reverse dns burda named.conf icine yazilmali.. http://langfeldt.net/DNS-HOWTO/BIND-9/DNS-HOWTO-5.html

		$this->requireCommandLine(__FUNCTION__);
		foreach ($arr as $ar) {
			$ar['namedbase'] = $this->conf['namedbase'];
			$arr2[] = $ar;
		}
		# named files are located at namedbase directory, typically, /var/www/named/

		$out .= $this->putArrayToStrDns($arr2); # for slave dns, we should use $dnsnamedconftemplate_slave if domain has dnsmaster field set. will code later. 
		$file = $this->conf['namedbase'] . "/named_ehcp.conf";
		echo "\n\nwriting namedfile: $file \n\n";
		$success = writeoutput2($file, $out, "w");

		// Fix perms on the bind file
		$this->fixBindFilePerms($file);

		return $success;
	}

	function fixBindFilePerms($file)
	{
		$success = setOwner($file, $this->binduser);
		$success = $success && setPermissions($file, 0774);
		return $success;
	}

	function calculateAliasedDomains($doms, $exampledomain)
	{

		# convert alias names to regular domain names, so that dns zone files can be setup
		# ex: changes www.dene.com to dene.com, xxx.com -> xxx.com, yyy.zzz.com -> zzz.com
/*
	function domainname($alias){
		if(substr_count($alias,'.')<=1) return $alias; # xxx.com -> xxx.com
		return substr($alias,strpos($alias,'.')); # yyy.zzz.com -> zzz.com
	}
*/

		$aliasedarr = array();
		foreach ($doms as $dom) {
			$aliases = $dom['aliases'];
			if ($aliases == '')
				continue;
			$aliasarr = explode("\n", $aliases);
			foreach ($aliasarr as $alias) {
				$alias = trim($alias);
				if (substr_count($alias, '.') <= 1)
					$newdom = $alias; # xxx.com -> xxx.com
				else
					$newdom = substr($alias, strpos($alias, '.') + 1); # yyy.zzz.com -> zzz.com
				$newdom = trim($newdom);
				if ($newdom == '')
					continue;
				if (!in_array($newdom, $aliasedarr, True))
					$aliasedarr[] = $newdom;
			}
		}

		$aliasedarr2 = array();

		# construct domains array as if read from domains table, in fact, these are not read from domains table, but these are aliases.
		# i added alias domains to dns, because dns should resolve this, for domains to work..

		foreach ($aliasedarr as $dom) {
			$ex = $exampledomain;
			$ex['id'] = $ex['panelusername'] = $ex['reseller'] = 'aliasdomain';
			$ex['domainname'] = $dom;
			$aliasedarr2[] = $ex;
		}

		return $aliasedarr2;
	}

	function syncDns()
	{ // for daemon mode
		# dnsde serial ayari yapilmasi lazim. yoksa nanay... ***

		$this->requireCommandLine(__FUNCTION__);

		$arr = $this->getDomains();
		$exampledomain = $arr[0];
		$arr_aliaseddomains = $this->calculateAliasedDomains($arr, $exampledomain);

		# merge two array to one domains array:
		# this array is like 0 => array('domainname'=>'xxx.com')

		foreach ($arr_aliaseddomains as $aliasdomain) {
			$found = false;
			foreach ($arr as $dom)
				if ($aliasdomain['domainname'] == $dom['domainname'])
					$found = True;
			if (!$found)
				$arr[] = $aliasdomain;
		}

		# put customdns info into zone files..
		$arr_customdns = $this->query("select * from " . $this->conf['customstable']['tablename'] . " where name='customdns' ");
		$arr2 = array(); foreach ($arr as $dom) { # add customdns to array,
			$customdnsvalue = '';
			foreach ($arr_customdns as $cust) {
				if ($cust['domainname'] == $dom['domainname'])
					$customdnsvalue .= $cust['value'] . "\n"; # this loop prevents repetitive mysql query, thus faster execution.
			}
			$dom['customdns'] = $customdnsvalue;
			# will include domain aliases in dns too, to be able to catch those domains with dns



			$arr2[] = $dom;
		}


		echo "\n\nsyncdns working..: \n";
		if ($this->debuglevel > 0)
			print_r($arr2);

		if (($this->dnsZoneFiles($arr2)) and ($this->dnsNamedConfFile($arr2))) {
			$this->output .= "daemon->dns success (syncdns)\n";
			manageService("bind9", "reload");
			return True;
		} else
			return false;

	}

	function whitelist()
	{
		# this is a special function, that will be used in a dns project. not related directly to hosting or ehcp. just uses ehcp structure.
		global $mod, $domainname, $domainler;
		$this->getVariable(array('mod', 'domainname', 'domainler'));


		switch ($mod) {
			case 'cocuklistele':
				$this->listTable("", "domainstable2", $filter);
				break;

			case 'cocukekle':
				if (!$domainname) {
					$inputparams = array(
						array('domainname', 'lefttext' => 'Domain Name:') #, array('op','hidden','default'=>__FUNCTION__)
					);
					$this->output .= inputform5($inputparams);
				} else {

					if (
						!$this->afterInputControls(
							"adddomaintothispaneluser",
							array(
								"domainname" => $domainname,
							)
						)
					)
						return false;


					$this->output .= "Dom ekleniyor:" . $domainname;
					$paneluserinfo = $this->getPanelUserInfo();
					$success = True;

					$sql = "insert into domains (reseller,panelusername,domainname,homedir,status,serverip) values ('" . $this->activeuser . "','" . $this->activeuser . "','$domainname','','" . $this->status_active . "','7.7.7.7')";
					$success = $success && $this->executeQuery($sql);
					$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
					$this->ok_err_text($success, 'DNS only domain was successfully added.', 'Failed to add domain (' . __FUNCTION__ . ')');

				}

				break;

			case 'cocukeklebulk':
				if (!$domainler) {
					$inputparams = array(
						array('domainler', 'textarea') #, array('op','hidden','default'=>__FUNCTION__)
					);
					$this->output .= inputform5($inputparams);
				} else {
					$domains = $this->strNewlineToArray($domainler);
					$paneluserinfo = $this->getPanelUserInfo();

					foreach ($domains as $dom) {
						if (trim($dom) == '')
							continue;
						if (
							!$this->afterInputControls(
								"adddomaintothispaneluser",
								array(
									"domainname" => $dom,
								)
							)
						)
							continue;


						$this->output .= "Dom ekleniyor: ($dom)";
						$success = True;
						$sql = "insert into domains (reseller,panelusername,domainname,homedir,status,serverip) values ('" . $this->activeuser . "','" . $this->activeuser . "','$dom','','" . $this->status_active . "','7.7.7.7')";
						$success = $success && $this->executeQuery($sql);
						$success = $success && $this->addDaemonOp("syncdns", '', '', '', 'sync dns');
						$this->ok_err_text($success, 'DNS only domain was successfully added.', 'Failed to add domain (' . __FUNCTION__ . ')');
					}

				}

				break;

		}

		$this->output .= "Whitelist ($mod)<br><a href='?op=otheroperations'>Home</a>";
	}

	function requireCommandLine($func = '', $echoinfo = false)
	{
		if (!$this->commandline) {
			return $this->errorTextExit("The command you requested works only from the commandline: $func");
		}
		if ($echoinfo)
			echo "\n$func: basliyor\n";
		$this->debugecho("Commandline: $func: basliyor\n", 3);

	}


	function requireCommandLineSilent($func = '')
	{
		if (!$this->commandline) {
			return false;
		}
		$this->debugecho("Commandline: $func: basliyor\n", 3);
		return true;
	}

	function syncpostfix()
	{
		$this->output .= "No need postfix sync. postfix already synced from db... <br>";
		return false;
		/*
		   http://www.howtoforge.com/virtual_postfix_mysql_quota_courier
		   this document is a good place to go for postfix and related stuff
		   */
	}

	function userop()
	{
		global $action, $ftpusername, $mailusername, $panelusername, $id, $_insert, $submit;
		$this->getVariable(array("action", "ftpusername", "mailusername", "panelusername", "id", "_insert", "submit"));
		if (!$action) {
			return $this->errorText(__FUNCTION__ . ": Error: Action was not provided.");
		}

		$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($this->activeuser);
		$inClause = $this->generateMySQLInClause($userHasAccessToTheseChildrenUsers);

		switch ($action) {
			case "emailuserdelete": //*** bu yapilmadi henuz... tam olarak...		
				if ($id == '') {
					$this->output .= "user id to delete not given.<br>";
					$success = false;
				} else {
					$email = $this->query("select email, domainname, mailusername, quota from emailusers where id=$id");
					$emailAddr = $email[0]['email'];
					$domain = $email[0]['domainname'];
					$justEmail = $email[0]['mailusername'];
					$path = "/home/vmail/" . $domain . "/" . $justEmail;
					if (!$_insert) {
						$inputparams = array(
							array('op', 'hidden', 'default' => __FUNCTION__),
							array('submit', 'submit', 'default' => 'No/Yes')
						);

						$this->output .= "<p><br>Are you sure you want to delete the email address of \"" . $emailAddr . "\"?&nbsp; All existing email messages will be deleted as well." . ($this->isadmin() ? " (the folder $path will be deleted) " : '') . inputform5($inputparams);
					} else {
						if ($submit == "Yes") {
							$sql = "delete from " . $this->conf['emailuserstable']['tablename'] . " where id='" . $id . "'";
							if (!$this->isadmin()) {
								$sql .= " AND panelusername " . $inClause;
							}
							$success = $this->executeQuery($sql, ' email user delete', '', false, false, false, true);
							$success = $success && $this->bashDelete($path, true);
							$this->ok_err_text($success, "Email account successfully deleted.", 'Failed to delete email account.');
						} else {
							$this->ok_err_text(true, "Email account was NOT deleted.", '');
						}
					}
				}
				$this->showSimilarFunctions('email');
				return $success;

				break;

			# ftp silerken aslında arayüzde dosyaların silinip silinmeyeceğini sorsa iyi olur.
			case "ftpuserdelete": //*** sonra bakilacak... username ile beraber domain de kontrol edilmeli..where icinde
				$sql = "select * from " . $this->conf['ftpuserstable']['tablename'] . " where ftpusername='$ftpusername'";
				if (!$this->isadmin()) {
					$sql .= " AND panelusername " . $inClause;
				}
				$ftp = $this->query($sql);
				if ($ftp != false && !empty($ftp)) {
					if (!$_insert) {
						$inputparams = array(
							array('op', 'hidden', 'default' => __FUNCTION__),
							array('submit', 'submit', 'default' => 'No/Yes')
						);

						$this->output .= "<p><br>Are you sure you want to delete the FTP account of \"" . $ftpusername . "\"?" . inputform5($inputparams);
					} else {
						if ($submit == "Yes") {
							if ($ftp['domainname'] <> '' and $ftp['type'] <> '') {
								$success = $this->errorText('This account has active domains or subdomains. Please delete domains and subdomains first.');
							} else {
								$success = $this->deleteFtpUserDirect($ftpusername);
								$this->ok_err_text($success, "Successfully deleted FTP account.", "Failed to remove FTP account.");
							}
						} else {
							$this->ok_err_text(true, "FTP account was NOT deleted.", '');
						}
					}
				} else {
					$this->ok_err_text(false, "Successfully deleted FTP account.", "Failed to remove FTP account.");
				}
				$this->showSimilarFunctions('ftp');
				return $success;
				break;

			default:
				return $this->errorText("userop: No action was provided.");
		}
	}

	function getMySqlDBAndUserInfo($id)
	{ # by earnolmartin@gmail.com
		$db = $this->query("select * from mysqldb where id='" . $id . "'");
		if (count($db) == 1) {
			$finalResult = array();
			$dbname = $db[0]['dbname'];
			$panelusername = $db[0]['panelusername'];
			$mysqlDBUserInfo = $this->query("select * from " . $this->conf['mysqldbuserstable']['tablename'] . " where dbname='$dbname'");
			if (count($mysqlDBUserInfo) > 0) {
				foreach ($mysqlDBUserInfo as $info) {
					$mysqlUserName = $info["dbusername"];
					$otherdbcount = $this->recordcount($this->conf['mysqldbuserstable']['tablename'], "dbusername='$mysqlUserName' and dbname<>'$dbname'");

					$dbInfo["dbname"] = $dbname;
					$dbInfo["mysqluser"] = $mysqlUserName;
					$dbInfo["other_assoc_dbs"] = $otherdbcount;
					$dbInfo["owned_by"] = $panelusername;
					$finalResult[] = $dbInfo;
				}
			} else {
				$dbInfo["dbname"] = $dbname;
				$dbInfo["mysqluser"] = "";
				$dbInfo["other_assoc_dbs"] = 0;
				$dbInfo["owned_by"] = $panelusername;
				$finalResult[] = $dbInfo;
			}

			return $finalResult;
		}
		return false;
	}

	function deleteDB($id)
	{
		$db = $this->query("select * from mysqldb where id='" . $id . "'");
		$dbname = $db[0]['dbname'];
		$panelusername = $db[0]['panelusername'];
		$paneluserinfo = $this->getPanelUserInfo('', $panelusername);
		$resellername = $paneluserinfo['reseller'];

		$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($this->activeuser);

		if (!in_array($panelusername, $userHasAccessToTheseChildrenUsers) and !$this->isadmin()) {
			return $this->errorText("Unable to delete database. Your account does not own this database!");
		}

		$this->output .= "<br> deleting db $id : $dbname <br>";

		$host = $db[0]['ip'];
		$myserver = $this->getMysqlServer($host, True); # get myinfo for that host or default if no host specified..


		if (!($mysqlconn = mysqli_connect($myserver['host'], $myserver['user'], $myserver['pass']))) {
			return $this->errorText('Could not connect as ' . $myserver['user'] . ' to server ' . $myserver['host']);
		}

		$this->output .= "Connected as user : " . $myserver['user'] . "<br>";
		if ($this->executeQuery("drop database `$dbname`", '', '', $mysqlconn))
			$this->output .= "Dropped database: $dbname <br>";
		else {
			$this->output .= "Error dropping db.. " . $this->getDBError();
		}


		$success = True;
		$success = $success && $s = $this->executeQuery("delete from " . $this->conf['mysqldbstable']['tablename'] . " where dbname='$dbname'", ' delete db from ehcp db');

		$q = "select dbusername from " . $this->conf['mysqldbuserstable']['tablename'] . " where dbname='$dbname'";
		$s = $user1 = $this->query($q);
		if ($s === false) {
			$this->echoln('error getting db users list..');
			$success = false;
		}

		$s = $this->executeQuery("use mysql", '', '', $mysqlconn);

		if ($s === false) {
			$success = $this->errorText('Error Selecting Database');
		}

		// delete all users associated with the db. actually there may be only one user... but one user may be used to access more than one db...
		foreach ($user1 as $user) {
			$user2 = $user['dbusername'];

			$s = $this->executeQuery("delete from " . $this->conf['mysqldbuserstable']['tablename'] . " where dbusername='$user2' and dbname='$dbname'");
			if ($s === false) {
				$this->output .= "Error Occured: " . $this->conn->ErrorMsg() . "<br>";
				$success = false;
			} else
				$this->output .= "user $user2 : deleted from ehcp db<br>";

			$otherdbcount = $this->recordcount($this->conf['mysqldbuserstable']['tablename'], "dbusername='$user2' and dbname<>'$dbname'");
			if ($otherdbcount > 0)
				continue; # if user has more databases that has access to, it is not dropped..

			$s = $this->executeQuery("DELETE FROM `user` WHERE User = '$user2'", '', '', $mysqlconn);
			if ($s === false) {
				$this->output .= "Error Occured: " . $this->getDBError() . "<br>";
				$success = false;
			} else
				$this->output .= "user $user2 : deleted user from mysql <br>";


			$s = $this->executeQuery("DELETE FROM `db` WHERE User = '$user2'", '', '', $mysqlconn);
			if ($s === false) {
				$this->output .= "Error Occured: " . $this->getDBError() . "<br>";
				$success = false;
			} else
				$this->output .= "user $user2 : deleted user from mysql.db <br>";


			$s = $this->executeQuery("DELETE FROM `tables_priv` WHERE User = '$user2'", '', '', $mysqlconn);
			if ($s === false) {
				$this->output .= "Error Occured: " . $this->getDBError() . "<br>";
				$success = false;
			} else
				$this->output .= "user $user2 : deleted user from mysql.tables_priv <br>";


			$s = $this->executeQuery("DELETE FROM `columns_priv` WHERE User = '$user2'", '', '', $mysqlconn);
			if ($s === false) {
				$this->output .= "Error Occured: " . $this->getDBError() . "<br>";
				$success = false;
			} else
				$this->output .= "user $user2 : deleted user from mysql.columns_priv <br>";
			$this->executeQuery("flush privileges", '', '', $mysqlconn);

		}
		return $success;

	}

	function domainop()
	{
		global $domainname, $action, $dbusername, $dbuserpass, $dbname, $id, $confirm;
		$this->getVariable(array("domainname", "action", "user", "pass", "dbname", "id", 'confirm'));
		if ($action == '') {
			$this->output .= "userop: action not given <br>";
			return false;
		}

		$userHasAccessToTheseChildrenUsers = $this->getParentsAndChildren($this->activeuser);
		$inClause = $this->generateMySQLInClause($userHasAccessToTheseChildrenUsers);

		switch ($action) {
			case "deletedb":
				if ($confirm == '') {
					// Get DB Info
					$databaseInfo = $this->getMySqlDBAndUserInfo($id);

					if (!is_array($databaseInfo) || count($databaseInfo) == 0) {
						return $this->errorText("Database not found.");
					}

					if (!in_array($databaseInfo[0]["owned_by"], $userHasAccessToTheseChildrenUsers)) {
						return $this->errorText("This database is not owned by your account.");
					}

					$selectedDBName = $databaseInfo[0]["dbname"];

					$this->output .= "<br>Are you sure you want to delete the following MySQL database: \"" . $selectedDBName . "\"?<br>";
					$message = "";

					// Confirm this is what should happen
					foreach ($databaseInfo as $info) {

						if ($info["other_assoc_dbs"] > 0) {
							$message .= "<br>Note, the MySQL user \"" . $info["mysqluser"] . "\" associated with this database will NOT be deleted since it is associated with another MySQL database.";
						} else {
							if ($this->hasValueOrZero($info["mysqluser"])) {
								$message .= "<br>Note, the MySQL user \"" . $info["mysqluser"] . "\" associated with this database WILL BE deleted since it is NOT associated with any other MySQL database.";
							}
						}
					}

					$message .= "<br><br><a href='?op=domainop&action=deletedb&id=$id&confirm=1'>Click here to delete</a>.<br><br>";

					$this->output .= $message;

					$success = false;
				} else {
					$success = $this->deleteDB($id);
					$this->ok_err_text($success, 'Database operations successful.', 'Database operations failed.');
					// yukardaki kodda, bircok success (basari) ile, her bir islemin sonucu ogrenilir. herhangi biri fail olsa, sonuc fail olur..
				}
				break;

			case "listdb":
				#$filter="panelusername='$this->activeuser'";
				$filter = $this->globalfilter;
				if ($this->selecteddomain)
					$filter = andle($filter, "domainname='$this->selecteddomain'");

				$this->listTable("All mysql db's", 'mysqldbstable', $filter);
				$this->output .= "<br> <a target=_blank href='/phpmyadmin/'><img src='/phpmyadmin/themes/original/img/logo_left.png' border=0></a><br>";
				$this->listTable("All mysql db users", 'mysqldbuserstable', $filter);
				$success = True;
				break;

			default:
				$this->output .= "domainop: unknown action given: $action <br>";
		}
		$this->showSimilarFunctions('mysql');
		return $success;
	} //function

	function redirect_domain()
	{
		global $domainname;
		$this->redirecttourl("http://www.$domainname");
	}

	function redirecttourl($url)
	{
		header("Location: $url");
		exit;
	}

	function getMysqlServer($host = '', $returndefault = False, $returnto = False)
	{
		# choose a mysqlserver from server farm.. servers table..or return server info for a host
		# this is written to go into multi-server concept.. Multi server is not complete yet for all server types, only mysql can be separate for customers.
		global $serverip, $returntoop;
		$this->getVariable(array('serverip', 'returntoop'));

		if ($serverip <> '') {
			$q = "select * from " . $this->conf['serverstable']['tablename'] . " where ip='$serverip' and servertype='mysql'";
			$ret = $this->query($q);
			$ret = $ret[0];
			$server = array('host' => $ret['ip'], 'user' => 'root', 'pass' => $ret['password'], 'defaultmysqlhostname' => $ret['defaultmysqlhostname']);
			$_SESSION['myserver'] = $server;
			$this->redirecttourl("?op=$returntoop");
		}

		$defaultmyserver = array('host' => 'localhost', 'user' => $this->conf['mysqlrootuser'], 'pass' => $this->conf['mysqlrootpass']);
		$sayi = $this->recordcount($this->conf['serverstable']['tablename'], "servertype='mysql' and upper(mandatory) in ('E','Y')"); # E=Y Evet=Yes # number of mandatoryservers..

		$where2 = "servertype='mysql' and (mandatory in ('',null) or (upper(mandatory) not in ('E','Y')))";
		$sayi2 = $this->recordcount($this->conf['serverstable']['tablename'], $where2); # number of servers, which are non-mandatory

		if ($host == '' and !$returndefault and $sayi == 0 and $sayi2 > 0) { # if there are some choises..
			$this->output .= "Choose mysql server to use:";
			$q = "select * from " . $this->conf['serverstable']['tablename'] . " where $where2";
			$servers = $this->query($q);
			$this->output .= $this->listSelector($arr = $servers, $print = array('ip'), $link = "?op=" . __FUNCTION__ . "&returntoop=$returnto&serverip=", $linfield = 'ip');
			$this->showexit();


		} elseif ($host == '' and ($returndefault or $sayi == 0)) { # if no mandatory, take optional, or default localhost...
			# belli bir host sorulmuyorsa, default gonder.. .
			$server = $defaultmyserver;
		} else { # choose for a specific host or mandatory one..
			if ($host == '')
				$where = "servertype='mysql' and upper(mandatory) in ('E','Y')";
			else
				$where = "host='$host'";

			$ret = $this->query("select * from " . $this->conf['serverstable']['tablename'] . " where $where");
			$ret = $ret[0];
			$server = array('host' => $ret['ip'], 'user' => 'root', 'pass' => $ret['password'], 'defaultmysqlhostname' => $ret['defaultmysqlhostname']);
			$this->echoln("Using mandatory mysql server at " . $server['host']);
		}
		return $server;
	}

	function addMysqlDbtoUser()
	{
		global $domainname, $dbusername, $dbuserpass, $dbname, $id, $confirm;
		$this->getVariable(array("domainname", "dbusername", "dbuserpass", "dbname", "id", 'confirm'));

		if (!$this->beforeInputControls('adddb'))
			return false;
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$myserver = $_SESSION['myserver'];
		if (!$myserver)
			$myserver = $this->getMysqlServer('', false, __FUNCTION__); # get mysql server info..
		$success = True;


		$users = $this->query("select distinct dbusername from " . $this->conf['mysqldbuserstable']['tablename'] . " where panelusername='$this->activeuser'");
		if (count($users) == 0) {
			$this->output .= "<hr>You have not any db users yet.. so, use add mysql db link <a href='?op=addmysqldb'>here</a>";
			return false;
		}

		if (!$dbusername) {
			$this->output .= "<br>Select User:" . $this->listSelector($arr = $users, $print = array('dbusername'), $link = "?op=" . __FUNCTION__ . "&dbusername=", $linfield = 'dbusername');
			return false;
		}


		if (!$dbname) {
			$inputparams = array(
				array("dbname", 'lefttext' => 'Database Name'),
				array('dbusername', 'hidden', 'value' => $dbusername, 'lefttext' => 'Database Username'),
				array('op', 'hidden', 'value' => __FUNCTION__)
			);

			if ($myserver['host'] <> 'localhost') { # if this is not local mysql server, the db user may not be localhost, so, ask that..
				$dbuserhost = $myserver['defaultmysqlhostname'];
				$inputparams[] = array('dbuserhost', 'lefttext' => 'dbuser hostname', 'value' => $dbuserhost, 'righttext' => 'This is host of mysql user, to connect from, <br>You should write hostname of your webserver here..');
			}

			$this->output .= "MySQL Server:" . $myserver['host'] . inputform5($inputparams);

		} else {
			if (
				!$this->afterInputControls(
					"adddb",
					array(
						"dbname" => $dbname
					)
				)
			)
				return false;
			$this->output .= "<br>Adding database \"$dbname\" to user: $dbusername.";
			$success = $success && $this->addMysqlDbDirect($myserver, $domainname, $dbusername, $dbuserpass, $dbuserhost, $dbname, $adduser = false);
			$this->ok_err_text($success);
		}

		$this->showSimilarFunctions('mysql');
		return $success;

	}

	function addMysqlDb()
	{
		// **** burda bikac sorun olabilir.. enonemlisi, bu success olayi calismiyor.. success lerden biri fail olsa da, sonuc degismiyor..
		// diger sorun, if(!rs) denilen yerlerde, dbden okumus sorunsuzca, ama sonuc bos ise, sanki dbden okuyamamış gibi davranıyor..
		global $domainname, $dbusername, $dbuserpass, $dbremoteaccess, $dbname, $dbuserhost, $id, $confirm;
		$this->getVariable(array("domainname", "dbusername", "dbuserpass", "dbname", "dbuserhost", "id", 'confirm', 'dbremoteaccess'));

		if (!$this->beforeInputControls('adddb'))
			return false; # check limit
		$domainname = $this->chooseDomain(__FUNCTION__, $domainname);
		$myserver = $_SESSION['myserver'];
		if (!$myserver)
			$myserver = $this->getMysqlServer('', false, __FUNCTION__); # get mysql server info..
		$success = True;

		if ($dbname == '') {
			$inputparams = array(
				array("dbname", 'lefttext' => 'Database Name'),
				array("dbusername", 'lefttext' => 'Database Username'),
				array("dbuserpass", "password_with_generate", 'lefttext' => 'Database Password'),
				array('op', 'hidden', 'value' => __FUNCTION__)
			);

			if ($this->isadmin()) {
				$inputparams[] = array("dbremoteaccess", 'checkbox', 'lefttext' => 'Allow remote access to database:', 'default' => '1', 'checked' => '0');
			}


			if ($myserver['host'] <> 'localhost') { # if this is not local mysql server, the db user may not be localhsot, so, ask that..
				$dbuserhost = $myserver['defaultmysqlhostname'];
				$inputparams[] = array('dbuserhost', 'lefttext' => 'Database Hostname', 'value' => $dbuserhost, 'righttext' => 'This is the MySQL host for the mysql user used for connecting to te database.<br>You should write the hostname of your webserver here.');
			}

			$this->output .= "MySQL Server:" . $myserver['host'] . inputform5($inputparams);

		} else {
			if (
				!$this->afterInputControls(
					"adddb",
					array(
						"dbname" => $dbname,
						"dbusername" => $dbusername
					)
				)
			)
				return false;


			# non-interactive part:
			$remoteAccess = false;
			if ($dbremoteaccess && $this->isadmin()) {
				$remoteAccess = true;
			}

			$success = $success && $this->addMysqlDbDirect($myserver, $domainname, $dbusername, $dbuserpass, $dbuserhost, $dbname, true, $remoteAccess);
			$_SESSION['myserver'] = false; # reset mysql server selector...
			$this->ok_err_text($success);
		}

		$this->showSimilarFunctions('mysql');
		return $success;


	}

	function addMysqlDbDirect($myserver, $domainname, $dbusername, $dbuserpass, $dbuserhost, $dbname, $adduser = true, $allowRemoteAccess = false)
	{
		// Must have validation
		if (!$this->mysqlDBInfoValid($dbname, $dbusername, $dbuserpass)) {
			return false;
		}

		if (!$myserver)
			$myserver = $_SESSION['myserver'];
		if (!$myserver)
			$myserver = $this->getMysqlServer('', false, __FUNCTION__); # get mysql server info..

		if ($myserver['host'] == 'localhost')
			$dbuserhost = 'localhost';
		if ($dbuserhost == '')
			$dbuserhost = 'localhost';

		if ($allowRemoteAccess) {
			$dbuserhost = '%';
		}

		# connect to mysql server, local or remote
		if (!$link = mysqli_connect($myserver['host'], $myserver['user'], $myserver['pass'])) {
			return $this->errorText("Could not connect as root!");
		}

		$this->output .= "<br>Connected as mysql root user: " . $myserver['user'] . "<br>";


		# actual setup for db and dbuser, local or remote
		# step 1: setup database: DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci

		$s = $this->executeQuery("create database `$dbname` " . $this->miscconfig['mysqlcharset'], 'creating db', '', $link);

		if ($s === false)
			return $this->errorText("Error creating database " . $this->getDBError() . "<br>");
		else
			$this->output .= "setup complete database: $dbname <br>";


		$success = True;
		# step 2: grant user rights
		if ($adduser)
			$s = $this->executeQuery("grant all privileges on `$dbname`.* to '$dbusername'@'$dbuserhost' identified by '$dbuserpass' ", 'grant user rights', '', $link);
		else
			$s = $this->executeQuery("GRANT ALL PRIVILEGES ON `$dbname`.* TO '$dbusername'@'$dbuserhost'", 'grant user to db', '', $link);

		if ($s === false) {
			$this->errorText("Error: user $dbusername cannot be granted access to: $dbname");
			$success = false;
		} else
			$this->output .= "user $dbusername permitted to : $dbname <br>";

		# step 3:
		# add these to ehcp db, to local only if local server, to both local and remote, if it is remote server
		# local add to ehcp db,
		$q = "insert into " . $this->conf['mysqldbstable']['tablename'] . " (domainname,host,dbname,panelusername)values('$domainname','" . $myserver['host'] . "','$dbname','$this->activeuser')";
		if ($success)
			$success = $success && $s = $this->executeQuery($q, ' add new mysql db info to ehcp db');


		$q = "insert into " . $this->conf['mysqldbuserstable']['tablename'] . " (domainname,host,dbname,dbusername,password,panelusername)values('$domainname','" . $myserver['host'] . "','$dbname','$dbusername','$dbuserpass','$this->activeuser')";
		if ($success)
			$success = $success && $s = $this->executeQuery($q, ' add mysql user to ehcp db ');

		#  add to remote ehcp db too, if this mysql is a remote one... this is for: if i add a remote mysql db, the data is also written to remote ehcp db. so, remote sees and may remove that.  this may be disabled..
		if ($success and $myserver['host'] <> 'localhost') { # if remote
			$q = "insert into " . $this->conf['mysqldbstable']['tablename'] . " (domainname,host,dbname,panelusername)values('$domainname','localhost','$dbname','$this->activeuser')";
			$success = $success && mysqli_select_db($link, "ehcp") && mysqli_query($link, $q); # do not use this->executeQuery here, since this is executed on remote mysql server.
			if (!$success)
				return $this->errorText("MySQL Error " . $this->getDBError() . "<br>");

			$q = "insert into " . $this->conf['mysqldbuserstable']['tablename'] . " (domainname,host,dbname,dbusername,password,panelusername)values('$domainname','localhost','$dbname','$dbusername','$dbuserpass','$this->activeuser')";
			if ($success)
				$success = $success && mysqli_select_db($link, "ehcp") && mysqli_query($link, $q);
			if (!$success)
				return $this->errorText("MySQL Error " . $this->getDBError() . "<br>");
		}
		return $success;
	}

	function mysqlRootQuery($q, $quiet = false)
	{
		if (!$link = mysqli_connect("localhost", $this->conf['mysqlrootuser'], $this->conf['mysqlrootpass'])) {
			return $this->errorText("Could not connect as root. Please check your MySQL root password.");
		}

		$this->output .= "<br>Connected as root : " . $this->conf['mysqlrootuser'] . "<br>";
		$s = $this->executeQuery($q, 'execute root query', '', $link, false, true);
		if ($s === false) {
			if (!$quiet) {
				return $this->errorText("Error: MySQL root query cannot be executed: $q");
			} else {
				return false;
			}
		} else
			return True;
	}

	function arrayToFile($file, $lines)
	{
		$new_content = join('', $lines);
		$fp = fopen($file, 'w');
		$write = fwrite($fp, $new_content);
		fclose($fp);
	}


	function addIfNotExists($what, $where)
	{
		$what .= "\n";
		$filearr = @file($where);
		if (!$filearr) {
			echo "\ncannot open file, trying to setup new file: ($where)\n";
			$fp = fopen($where, 'w');
			fclose($fp);
			$filearr = file($where);

		} //else print_r($file);

		if (array_search($what, $filearr) === false) {
			//echo "dosyada bulamadı ekliyor: $where -> $what \n";
			$filearr[] = $what;
			$this->arrayToFile($where, $filearr);

		} else {
			//echo "buldu... sorun yok. \n";
			// already found, so, do not add
		}

	}

	function generateSslFiles()
	{
		if ($this->requireCommandLineSilent()) {
			$nowDate = time();
			$certFile = "/etc/ssl/certs/server.crt";

			// If the certificate file does not exist or the last modification date of the certificate is greater than 365 days
			// Generate a new self signed one that lasts another 365 days
			if (!file_exists($certFile) || (file_exists($certFile) && ($nowDate - filemtime($certFile) >= (86400 * 365)))) {
				$findarray = array('webserverip');
				$replacearray = array($this->getWebServer());
				$this->replaceArrayPutInFile("$this->ehcpdir/LocalServer.cnf", "$this->ehcpdir/LocalServerIP.cnf", $findarray, $replacearray);

				passthru2("openssl genrsa -out $this->ehcpdir/server.key");
				passthru2("openssl req -new -key $this->ehcpdir/server.key -out $this->ehcpdir/server.csr -config $this->ehcpdir/LocalServerIP.cnf");
				passthru2("openssl x509 -req -days 365 -in $this->ehcpdir/server.csr -signkey $this->ehcpdir/server.key -out $this->ehcpdir/server.crt");

				passthru2("cp -vf $this->ehcpdir/server.crt /etc/ssl/certs/");
				passthru2("cp -vf $this->ehcpdir/server.key /etc/ssl/private/");
			}
		}
		return true;
	}

	function replaceArrayPutInFile($srcfile, $dstfile, $findarray, $replacearray)
	{
		# reads srcfile, replace some findarray with replacearray, then put in dstfile, for writing some template files..

		$filestr = file_get_contents($srcfile);
		$findarray2 = arrayop($findarray, "strop");
		$fileout = str_replace($findarray2, $replacearray, $filestr);
		$res = writeoutput2($dstfile, $fileout, 'w');
		if ($res === True)
			echo __FUNCTION__ . ": Dst file ($dstfile) written.. \n";
		return $res;
	}

	function restart_webserver2($server)
	{
		echo __FUNCTION__ . ":\n";
		print_r($server);

		$serverip = $serverip['ip'];
		$webservertype = $server['servertype'];

		$this->debugecho(__FUNCTION__ . ":$serverip", 1);

		if ($serverip == '')
			$serverip = 'localhost';
		if ($webservertype == '')
			$webservertype = 'apache2';

		$this->server_command($serverip, getServiceActionStr($webservertype, "restart"));
		# configtest_reload yapılacak.
	}

	function restart_webserver()
	{
		# thanks to webmaster@securitywonks.net for encourage of nginx integration

		echo "\n" . __FUNCTION__ . ": Current webserver is:" . $this->miscconfig['webservertype'] . "\n";
		passthru2("killall " . $this->php_fpm_name);
		manageService($this->php_fpm_name, "restart");

		if ($this->miscconfig['webservertype'] == 'apache2') {
			manageService("nginx", "stop");
			manageService("apache2", "restart");
		} else if ($this->miscconfig['webservertype'] == 'nginx') {
			manageService("apache2", "stop");
			manageService("nginx", "restart");
		}

		return true;
	}

	function is_webserver_running()
	{
		// will be checked..
		// Sleep for 18 seconds before checking to allow configuration to be updated.
		sleep(18);
		if ($this->miscconfig['webservertype'] == 'apache2') {
			$out = shell_exec('ps aux | grep apache | grep -v grep | grep -v php');
			echo __FUNCTION__ . ":" . $out;
			return (strstr($out, 'apache') !== false);

		} elseif ($this->miscconfig['webservertype'] == 'nginx') {
			$out = shell_exec('ps aux | grep nginx | grep -v grep | grep -v php');
			echo __FUNCTION__ . ":" . $out;
			return (strstr($out, 'nginx') !== false);
		}

		return True;
	}

	function fixApacheConfigSsl($domain = '')
	{
		$this->requireCommandLine(__FUNCTION__, True);
		$this->generateSslFiles();

		if ($this->miscconfig['webservertype'] == 'apache2') {

			passthru2("a2enmod ssl");
			passthru2("cp -vf $this->ehcpdir/etc/apache2_ssl/fork/apachetemplate_ipbased $this->ehcpdir/", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_ssl/fork/apachetemplate_ehcp_panel $this->ehcpdir/", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_ssl/fork/redirect $this->ehcpdir/apachetemplate_redirect", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_ssl/fork/apache_subdomain_template $this->ehcpdir/", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_ssl/fork/apachetemplate_passivedomains $this->ehcpdir/", true, true);
			passthru2("rm -rvf /etc/apache2/sites-enabled/*", true, true);

			$this->executeQuery("update misc set value='apache2' where name='webservertype'");
			$this->executeQuery("update misc set value='ssl' where name='webservermode'");

			// We will bind on configured panel IP address

			$findarray = array('webserverip');
			$replacearray = array($this->getWebServer());
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/apachetemplate_ipbased", "$this->ehcpdir/apachetemplate", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/apache_subdomain_template", "$this->ehcpdir/apache_subdomain_template", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/default", "/etc/apache2/sites-enabled/default", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/redirect", "$this->ehcpdir/apachetemplate_redirect", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/apachetemplate_ehcp_panel", "$this->ehcpdir/apachetemplate_ehcp_panel", $findarray, $replacearray);

			// Get any custom ports Apache is currently listening on
			$customPorts = getCustomApache2ListenPorts();

			// Replace the ports file
			if ((getIsUbuntu() && getUbuntuReleaseYear() >= "14") || (getIsDebian() && getUbuntuReleaseYear() >= "8")) {
				$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/ports_ubu14.conf", "/etc/apache2/ports.conf", $findarray, $replacearray);
			} else {
				$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_ssl/fork/ports.conf", "/etc/apache2/ports.conf", $findarray, $replacearray);
			}

			// Re-add any custom ports
			addCustomPortsToApache($customPorts);

		} else if ($this->miscconfig['webservertype'] == 'nginx') {
			// Debug
			/*
				  $this->writeToLogFile("Setting SSL on Nginx Configs");
				  
				  $contentsOfFile = file_get_contents("$this->ehcpdir/etc/nginx_ssl/apache_subdomain_template.nginx");
				  $this->writeToLogFile($contentsOfFile);
				  */

			passthru2("cp -vf $this->ehcpdir/etc/nginx_ssl/apachetemplate.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_ssl/apachetemplate_ehcp_panel.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_ssl/default.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_ssl/nginx.conf $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_ssl/apache_subdomain_template.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_ssl/redirect $this->ehcpdir/etc/nginx", true, true);

			passthru2("rm -rvf /etc/nginx/sites-enabled/*", true, true);

			$this->executeQuery("update misc set value='nginx' where name='webservertype'");
			$this->executeQuery("update misc set value='ssl' where name='webservermode'");

			//Debug
			//$this->writeToLogFile("Going to use nginx templates: " . $this->miscconfig['webservermode']);

			$this->useNginxTemplates();
		}

		$this->new_sync_domains();
		$this->syncDomains();
		$this->restart_webserver();

		return True;
	}

	function fixApacheConfigSslOnly($domain = '')
	{
		$this->requireCommandLine(__FUNCTION__, True);
		$this->generateSslFiles();

		if ($this->miscconfig['webservertype'] == 'apache2') {

			passthru2("a2enmod ssl");
			passthru2("a2enmod rewrite");

			passthru2("cp -vf $this->ehcpdir/etc/apache2_sslonly/fork/apachetemplate_ipbased $this->ehcpdir/", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_sslonly/fork/apache_subdomain_template $this->ehcpdir/", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_sslonly/fork/apachetemplate_passivedomains $this->ehcpdir/", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_sslonly/fork/redirect", "$this->ehcpdir/apachetemplate_redirect", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/apache2_sslonly/fork/apachetemplate_ehcp_panel", "$this->ehcpdir/apachetemplate_ehcp_panel", true, true);

			passthru2("rm -rvf /etc/apache2/sites-enabled/*", true, true);

			$this->executeQuery("update misc set value='apache2' where name='webservertype'");
			$this->executeQuery("update misc set value='sslonly' where name='webservermode'");

			// We will bind on configured panel IP address

			$findarray = array('webserverip');
			$replacearray = array($this->getWebServer());
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/apachetemplate_ipbased", "$this->ehcpdir/apachetemplate", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/apachetemplate_ehcp_panel", "$this->ehcpdir/apachetemplate_ehcp_panel", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/apache_subdomain_template", "$this->ehcpdir/apache_subdomain_template", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/redirect", "$this->ehcpdir/apachetemplate_redirect", $findarray, $replacearray);
			$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/default", "/etc/apache2/sites-enabled/default", $findarray, $replacearray);

			// Get any custom ports Apache is currently listening on
			$customPorts = getCustomApache2ListenPorts();

			// Replace the old file with the new base file
			if ((getIsUbuntu() && getUbuntuReleaseYear() >= "14") || (getIsDebian() && getUbuntuReleaseYear() >= "8")) {
				$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/ports_ubu14.conf", "/etc/apache2/ports.conf", $findarray, $replacearray);
			} else {
				$this->replaceArrayPutInFile("$this->ehcpdir/etc/apache2_sslonly/fork/ports.conf", "/etc/apache2/ports.conf", $findarray, $replacearray);
			}

			// Re-add any custom ports
			addCustomPortsToApache($customPorts);


		} else if ($this->miscconfig['webservertype'] == 'nginx') {
			// Debug
			/*
				  $this->writeToLogFile("Setting SSL on Nginx Configs");
				  
				  $contentsOfFile = file_get_contents("$this->ehcpdir/etc/nginx_ssl/apache_subdomain_template.nginx");
				  $this->writeToLogFile($contentsOfFile);
				  */

			passthru2("cp -vf $this->ehcpdir/etc/nginx_sslonly/apachetemplate.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_sslonly/apachetemplate_ehcp_panel.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_sslonly/default.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_sslonly/nginx.conf $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_sslonly/apache_subdomain_template.nginx $this->ehcpdir/etc/nginx", true, true);
			passthru2("cp -vf $this->ehcpdir/etc/nginx_sslonly/redirect $this->ehcpdir/etc/nginx", true, true);

			passthru2("rm -rvf /etc/nginx/sites-enabled/*", true, true);

			$this->executeQuery("update misc set value='nginx' where name='webservertype'");
			$this->executeQuery("update misc set value='sslonly' where name='webservermode'");

			//Debug
			//$this->writeToLogFile("Going to use nginx templates: " . $this->miscconfig['webservermode']);

			$this->useNginxTemplates();
		}

		$this->new_sync_domains();
		$this->syncDomains();
		$this->restart_webserver();

		return True;
	}

	function fixApacheConfigNonSsl2()
	{
		$this->executeQuery("delete from customsettings");
		# do any other necessary things here... 
		$this->fixApacheConfigNonSsl();
	}

	function fixApacheConfigNonSsl()
	{
		$this->requireCommandLine(__FUNCTION__, True);
		global $ehcpinstalldir;
		$ehcpinstalldir = $this->conf['ehcpdir'];

		if ($this->miscconfig['webservertype'] == 'apache2') {
			$this->resetNonSSLApacheConf();
		} else if ($this->miscconfig['webservertype'] == 'nginx') {
			$this->resetNonSSLNginxConf();
		}

		$this->new_sync_domains();
		$this->restart_webserver();
		return True;
	}

	function resetNonSSLApacheConf()
	{
		// Variables
		global $ehcpinstalldir;
		$ehcpinstalldir = $this->conf['ehcpdir'];

		// Disable the ssl mod
		passthru2("a2dismod ssl");

		// Copy original apache configs from ehcpinstalldir/etc/apache2 back to root ehcpinstalldir
		include_once("install_lib.php");
		rebuild_apache2_config2(); # in install_lib.php

		// Update database settings
		$this->executeQuery("update misc set value='apache2' where name='webservertype'");
		$this->executeQuery("update misc set value='nonssl' where name='webservermode'");
	}

	function resetNonSSLNginxConf()
	{
		// Copy nonssl nginx configs back into shared ehcp nginx config directory
		passthru2("cp -vf $this->ehcpdir/etc/nginx_nonssl/apache_subdomain_template.nginx $this->ehcpdir/etc/nginx", true, true);
		passthru2("cp -vf $this->ehcpdir/etc/nginx_nonssl/apachetemplate.nginx $this->ehcpdir/etc/nginx", true, true);
		passthru2("cp -vf $this->ehcpdir/etc/nginx_nonssl/apachetemplate_ehcp_panel.nginx $this->ehcpdir/etc/nginx", true, true);
		passthru2("cp -vf $this->ehcpdir/etc/nginx_nonssl/default.nginx $this->ehcpdir/etc/nginx", true, true);
		passthru2("cp -vf $this->ehcpdir/etc/nginx_nonssl/nginx.conf $this->ehcpdir/etc/nginx", true, true);
		passthru2("cp -vf $this->ehcpdir/etc/nginx_nonssl/redirect $this->ehcpdir/etc/nginx", true, true);

		passthru2("rm -rvf /etc/nginx/sites-enabled/*", true, true);

		// Update database settings
		$this->executeQuery("update misc set value='nginx' where name='webservertype'");
		$this->executeQuery("update misc set value='nonssl' where name='webservermode'");

		$this->useNginxTemplates();

	}

	function sync_domains_multi_server($file = '')
	{ # this should be same as syncdomains below, but I wrote a separate function to minimize conflicts, errors while developing. will call this function only if ehcp has multi-server enabled.
		# not completed yet.

		$this->requireCommandLine(__FUNCTION__);
		echo "\n" . __FUNCTION__ . ": start syncing domains";
		if ($file == '')
			$file = "apachehcp.conf"; # gecici olarak yaptim. ****
		$filt = andle($this->activefilt, "(serverip is null or serverip='') and homedir<>''"); # exclude where serverip is set, that is, for remote dns hosted only domains..
		$arr = $this->getDomains($filt);
		$webserverip = $this->getWebServer();


		$success = True;

		$arr_customhttp = $this->query("select * from " . $this->conf['customstable']['tablename'] . " where name='customhttp'  and (webservertype is null or webservertype='' or webservertype='" . $this->miscconfig['webservertype'] . "')");

		$arr2 = array();

		# farkli webserver'lara yazabilmek icin,  herbir domain'i ayri degerlendirmek, ilgili webserver'a konfigurasyonlari gondermek lazim.
		# group by webserver tarzi bisey olabilir, sonra herbir grup icin bu kodlar calistirilir. birer dizine konur. sonra her dizin kendi sunucusuna rsync yapilir.
		# domains arrayi soyle olabilir

		#array=>server1=>array(domainler)
		#	   server2=>array(domainler)

	}

	function clear_slash_at_end($str)
	{
		# clears slash at end of a string, for path names. path names should have no slash at end. this should be a standard. this function ensures this.
		# until code is clean enaugh.

		$len = strlen($str);
		if (substr($str, $len - 1, 1) == '/')
			$out = substr_replace($str, '', $len - 1);
		else
			$out = $str;

		#echo __FUNCTION__.":($str)($out)(len:$len) (".substr($str,$len-1,1).") \n";
		return $out;
	}

	function server_command($serverip, $cmd, $noremove = false, $copy_file_to_dest = False)
	{ # not used yet.
		# execute a shell command in local or remote server.
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.
		if (!$noremove)
			$cmd = removeDoubleSlash($cmd);
		$escapedcmd = escapeshellcmd($cmd);
		$accessip = $this->get_server_access_ip($serverip);
		#echo "\nexecuting command: $cmd \n(escaped cmd:$escapedcmd)\n";

		if ($accessip == 'localhost') {
			echo "\nexecuting command: $cmd \n(escapedcmd: $escapedcmd)\n";
			return shell_exec($escapedcmd);
		} else {
			if ($copy_file_to_dest) { # in this case, the command is a file (probably that has many commands inside) that needs to be transfered to dest server before executing.
				shell_exec("scp $cmd $accessip:/etc/ehcp/");
				$cmd = "/etc/ehcp/$cmd";
			}
			echo "\nexecuting command: ssh $accessip \"$cmd\" \n(escapedcmd: ssh $accessip \"$escapedcmd\")\n";
			return shell_exec("ssh $accessip \"$escapedcmd\"");
		}

	}

	function server_commands($serverip, $filename)
	{
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		echo "\n" . __FUNCTION__ . ":file $filename is being executed on server:($serverip) \n";
		if (trim($serverip) == '') {
			echo "\n" . __FUNCTION__ . ": server is empty. strange! \n";
			debug_print_backtrace();
			return false;
		}

		$accessip = $this->get_server_access_ip($serverip);

		if ($accessip == 'localhost') {
			my_shell_exec($filename, __FUNCTION__);
		} else {
			my_shell_exec("scp $filename $accessip:$filename", __FUNCTION__);
			my_shell_exec("ssh $accessip \"$filename\"", __FUNCTION__);
		}
		return True;
	}

	function execute_server_commands($serverip, $commands)
	{
		# prepare commands as a whole, put them in a file, execute that file. especially may be useful for remote server command execution.
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		if (trim($serverip) == '') {
			echo "\n" . __FUNCTION__ . ": server is empty. strange! \n";
			debug_print_backtrace();
			return false;
		}

		echo "\n" . __FUNCTION__ . ": preparing server commands for server ($serverip)\n";
		$out = "";
		foreach ($commands as $com)
			$out .= $com . "\n"; # array_to_plaintext
		$filename = "/etc/ehcp/ehcp_executethisfile.sh";
		writeoutput2($filename, $out, "w");
		my_shell_exec("chmod a+x $filename", __FUNCTION__);
		echo "\n" . __FUNCTION__ . ":Executing this file: $filename on server ($serverip)\n $out \n";
		$this->server_commands($serverip, $filename);
		echo "\n" . __FUNCTION__ . ":end\n";

	}

	function initialize_logs2($dir)
	{

		# fill commands to be executed on relatd server. these will be executed all in once.
		$this->commands[] = "mkdir -p $dir";
		$this->commands[] = "mkdir -p $dir/logs";
		$this->commands[] = "echo '' >> $dir/logs/access_log"; // these are defined in apachetemplate file, bunlarin log_rotate olayi yapilmali.
		$this->commands[] = "echo '' >> $dir/logs/error_log";

		#passthru2("chown $this->ftpowner -Rf $dir");
		# this caused problem especially for file upload scripts,

		$this->commands[] = "chown root:root -Rf $dir/logs"; # bu olmayinca, biri logs dizinini silince, apache hata verip cikiyor.. # suanda access ver error loglar silinemiyor kullanici tarafindan...ancak sunucudan ssh ile silinebilir...!
	}

	function noExistingIndex($homedir)
	{
		if (!file_exists($homedir . "/httpdocs/index.php") and !file_exists($homedir . "/httpdocs/index.htm" and !file_exists($homedir . "/httpdocs/index.html"))) { # do not overwrite if any older index.php exists there...
			return true;
		} else {
			return false;
		}
	}

	function initialize_domain_files($homedir)
	{
		# fill commands to be executed on related server. these will be executed all in once.	

		$this->commands[] = "mkdir -p $homedir";
		$this->commands[] = "mkdir -p $homedir/httpdocs";
		$this->commands[] = "chown $this->ftpowner -Rf $homedir/httpdocs";
		# $this->commands[]="chmod g+w -Rf $homedir/httpdocs";  # make group writable, if www-data writable, then, all domains files would be writable. 
		$this->commands[] = "mkdir -p $homedir/phptmpdir";
		$this->commands[] = "chmod a+w $homedir/phptmpdir";

		$this->commands[] = "echo '' > $homedir/UPLOAD_SITE_FILES_TO_httpdocs_FOLDER"; // z7 mod
		$this->commands[] = "cp " . $this->ehcpdir . "/z7/install_files/.htaccess $homedir/phptmpdir/.htaccess"; // z7 mod
		if ($this->noExistingIndex($homedir)) {
			$this->commands[] = "cp " . $this->ehcpdir . "/z7/install_files/domain_index.php $homedir/index.php"; // z7 mod\
		}
		$this->commands[] = "cp -f $this->ehcpdir/ehcpinfo.html $homedir/httpdocs/ehcpinfo.html"; # final execution of commands will be done after all commands are collected at calling functions.

		$this->initialize_logs2($homedir);

	}

	function prepare_webserver_files($file, $server)
	{
		# Case webserver* is not taken into account here. for this function to work, all servers need to have real ip.
		# this function is a re-write of sync_domains (syncDomains) function, will be used in multi-server environments.
		# idea: prepare server files, then, send these files to related servers, if localhost, send to related dirs in local machine.

		# *** MISSING HERE: sync subdomains, different ip within a server.. will be made similar to syncDomains for multi IP, multiserver

		$this->requireCommandLine(__FUNCTION__);


		$this->commands = array('#!/bin/bash', 'echo "$0: commands are executed from within ehcp ' . date_tarih() . '" >> /var/log/ehcp.log'); # these will be commands to be executed on local or remote server as a whole, ?? i am testing this technique

		$serverip = $server['ip'];
		$webservertype = $server['servertype'];

		echo "\n===========================================\n# start process server files for server:$serverip, " . date_tarih() . "\n";

		if ($serverip == '')
			$serverip = 'localhost';

		if ($webservertype == '')
			$webservertype = 'apache2';
		$accessip = $this->get_server_access_ip($serverip);
		if ($accessip <> 'localhost' and $file == '')
			$file = "$this->ehcpdir/serverfiles/$accessip/webserver/ehcp_webserver_remote_config_produced_by_" . $this->miscconfig['dnsip'] . '.conf'; # for testing, or give a diffrent name for this, so that track easier.
		if ($accessip == 'localhost')
			$file = "$this->ehcpdir/apachehcp.conf";

		# clean old server files first:
		passthru2("rm -vf $this->ehcpdir/serverfiles/$accessip/webserver/*", True, True);

		# get domains list first, related to
		if ($serverip == 'localhost') {
			$filt = "webserverips is null or webserverips='' or webserverips like '%localhost%'";
			$webserverip = $this->miscconfig['dnsip'];
		} else {
			$filt = "webserverips like '%$serverip%'";
			$webserverip = $serverip;
			$this->commands[] = "mkdir -p " . $this->ehcpdir;
			$this->commands[] = "mkdir -p /etc/ehcp";

			if (rand(1, 10) == 10)
				my_shell_exec("rsync -arvz $this->ehcpdir/ehcpinfo.html $accessip:" . $this->ehcpdir, __FUNCTION__); # copy that file to dest server. do not send every time, only %10 of time. to prevent copy eachtime.
		}

		$arr = $this->getDomains($filt);
		if (count($arr) == 0) {
			echo "\n number of domains for server ($serverip) is zero. quiting " . __FUNCTION__;
			writeoutput2($file, "", 'w', false); # no domains, so, empty the config
			return True;
		}

		print_r($arr2);

		$success = True;
		$arr_customhttp = $this->query("select * from " . $this->conf['customstable']['tablename'] . " where name='customhttp'  and (webservertype is null or webservertype='' or webservertype='" . $this->miscconfig['webservertype'] . "')");
		$arr2 = array();


		# prepare webserver config files for this server, put in serverfiles/ folder.

		# prepare domain array

		foreach ($arr as $dom) { // setup necessry dirs/files if doesnt exist..
			# files will be prepared.. such as mkdir, like in sync_domains
			$this->initialize_domain_files($dom['homedir']);

			# add customhttp to array,
			$customhttpvalue = '';
			if ($arr_customhttp)
				foreach ($arr_customhttp as $cust) {
					if ($cust['domainname'] == $dom['domainname'])
						$customhttpvalue .= $cust['value'] . "\n"; # this loop prevents repetitive mysql query, thus faster execution.
				}
			$dom['customhttp'] = $customhttpvalue;

			# add ServerAlias to begining of lines in aliases field
			$aliases = $dom['aliases'];
			$aliasarr = explode("\n", $aliases);
			$newaliases = "";
			foreach ($aliasarr as $al)
				if (trim($al) <> '')
					$newaliases .= "ServerAlias " . trim($al) . " \n";
			$dom['aliases'] = $newaliases;
			$dom['webserverip'] = $webserverip;

			$arr2[] = $dom;
		}

		$this->commands[] = "mkdir -p /var/www/passivedomains";
		$this->commands[] = "echo domain_deactivated_contact_administrator > /var/www/passivedomains/index.html";


		#process passive domains
		$passivedomains = $this->getDomains($this->passivefilt);

		$passives = array(); foreach ($passivedomains as $p) {
			if ($ssl_enabled) {
				$p['webserverip'] = $webserverip;
			}
			$this->initialize_domain_files($dp['homedir']);
			$passives[] = $p;
		}


		if ($serverip == 'localhost')
			$passive_file = "$this->ehcpdir/apachehcp_passivedomains.conf";
		else
			$passive_file = "$this->ehcpdir/serverfiles/$serverip/webserver/ehcp_webserver_remote_config_passivedomains_produced_by_" . $this->miscconfig['dnsip'] . '.conf';

		$this->putArrayToFile($passives, $passive_file, "apachetemplate_passivedomains");



		$this->execute_server_commands($serverip, $this->commands); # all commands for whole domains are done in single step.

		# domain array prepared. now, put these in configs.

		#begin: reconstruct apache config file:
		$fileout = "# This is an automatically generated file, by ehcp. Do not edit this file by hand. if you need to change apache configs, edit apachetemplate file (to take effect for all domains) in ehcp dir, or use custom http in ehcp gui \n";
		$alanlar = array_keys($arr2[0]); // gets array keys, from first(0th) array element of two-dimensional $arr2 array.

		// following code, replaces fields from template to values here in $arr2 two-dim array. each $arr2 element written to output file according to template file.
		// Adds brackets around array_key entries - TRANSLATED ABOVE LINE TO ENGLISH EARNOLMARTIN
		$replacealanlar = arrayop($alanlar, "strop");

		# load webserver config template: (these are a bit different than single server sync_domains, because, here, multiple templates may be used simultaneously, since many servers with different webserver possible..)
		switch ($webservertype) {
			case 'apache2':
				$webserver_template_filename = "$this->ehcpdir/apachetemplate";
				break;
			case 'nginx':
				$webserver_template_filename = "$this->ehcpdir/etc/nginx/apachetemplate.nginx";
				break;
			# other webservers here.., lighthttpd, litespeed,
		}
		$webserver_template_file = file_get_contents($webserver_template_filename);


		$ssl_enabled = strstr($webserver_template_file, "{webserverip}") !== false; # *1 if template file contains {webserverip} then, ssl is assumed to enabled on apache configs. this case, non-ssl custom http's are disabled to prevent apache config error. All custom http's should be fixed by admin in this case.
		if ($ssl_enabled)
			$this->echoln("ssl seems enabled in this server, because tag {webserverip} is found in apache config templates files..");
		else
			$this->echoln("ssl seems not enabled in this server, because tag {webserverip} is not found in apache config templates files..");


		foreach ($arr2 as $ar1) { // template e gore apache dosyasini olustur		
			$webserver_template = $ar1[$webservertype . 'template']; # get domain specific (custom) template		

			if ($webserver_template <> '') {
				$this->echoln2("Domain:" . $ar1['domainname'] . " has custom webserver template.");
				$webserver_template .= "\n#this last template is read from database for " . $ar1['domainname'] . "..\n\n";

				if ($ssl_enabled and strstr($webserver_template, "{webserverip}") === false) {
					$this->echoln("apache config is adjusted as ssl enabled, however, your custom http for this domain contains non-ssl custom http, so, I disable custom http for this domain:" . $ar1['domainname']);
					$webserver_template = $webserver_template_file; # read explanation above *1
				} elseif (!$ssl_enabled and strstr($webserver_template, "{webserverip}") !== false) {
					$this->echoln("apache config is adjusted as non-ssl enabled, however, your custom http for this domain contains ssl custom http, so, I disable custom http for this domain:" . $ar1['domainname']);
					$webserver_template = $webserver_template_file; # read explanation above *1
				}
			} else {
				$webserver_template = $webserver_template_file;
			}

			$webserver_template = str_replace(array('{ehcpdir}', '{localip}'), array($this->ehcpdir, $this->miscconfig['localip']), $webserver_template);
			$webserver_config = str_replace($replacealanlar, $ar1, $webserver_template);
			$fileout .= $webserver_config;
		}

		$success = writeoutput2($file, $fileout, 'w', false);
		# end: reconstruct apache config file:


		echo "\n# ** end process server files for server: $serverip " . date_tarih() . "\n===========================================\n";

	}

	function get_servers($filt)
	{
		$q = "select * from servers";
		if ($filt <> '')
			$q .= " where $filt";
		return $this->query($q);
	}

	function get_webservers()
	{
		print __FUNCTION__ . ": basliyor ** \n";
		# this function reads servers table, then merges it through domain list, thus building dns/web servers,

		$webservers = $this->query("select servertype,ip from servers where servertype in ('apache2','nginx')");
		$localserver = $this->query("select servertype,ip from servers where servertype in ('apache2','nginx') and ip='localhost'");
		if (count($localserver) == 0)
			$webservers[] = array('servertype' => $this->miscconfig['webservertype'], 'ip' => 'localhost'); # add localhost if not exist

		#print_r($webservers);
		$w2 = array(); # servers in "servers" table
		foreach ($webservers as $w)
			$w2[$w['ip']] = $w['servertype']; # convert to ip=>type pairs

		echo "w2:";
		print_r($w2);

		$doms = $this->getDomains();
		if (is_array($this->last_deleted_domaininfo))
			$doms[] = $this->last_deleted_domaininfo; # server of last deleted domain also needs to be updated,so, add that

		$w3 = array(); # find servers related to domains: (including last deleted domain)
		foreach ($doms as $dom) {
			$ip = trim($dom['webserverips']);
			if ($ip == '')
				$ip = 'localhost';

			$list = explode(",", $ip);

			foreach ($list as $l) {
				$l = trim($l);
				if ($l <> '' and ($w3[$l] == '')) {
					$w3[$l] = $w2[$l]; # assign type from list of servers above
					if ($w3[$l] == '')
						$w3[$l] = 'apache2'; # if there is no type until now, set to default of apache2
				}
			}
		}

		if ($w3['localhost'] == '')
			$w3['localhost'] = 'apache2';
		echo "w3:";
		print_r($w3);


		$w4 = array();
		foreach ($w3 as $ip => $type)
			$w4[] = array('servertype' => $type, 'ip' => $ip); #convert back to servers array

		#$keys=array_keys($w3);	foreach($keys as $k) $w4[]=array('servertype'=>$w3[$k],'ip'=>$k); #convert back to servers array
		echo "w4:";
		print_r($w4);



		return $w4;
	}

	function get_server_access_ip($ip)
	{
		# server ip's and access ips may be different, for servers having more than one ip. so, for accessing a server, we need to know its access ip.
		# otherwise, many settings required.

		$ret = $this->query("select accessip from servers where ip='$ip'");
		$ret = $ret[0]['accessip'];
		if ($ret == '')
			$ret = $ip;
		return $ret;
	}

	function send_webserver_files($server)
	{
		print __FUNCTION__ . ": basliyor \n";
		print_r($server);

		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		/*
		 * Burada, aynı sunucuda birden fazla ip için şu yapılabilir:
		 * sunucu için bir ip listesi tutulur,
		 * burada gönderilirken, oradan kontrol edilir.
		 * servers tablosunda: ip, accessip olmalı. bir sunucu tek bir accessip ile tanımlanmalı, ama birden çok diğer ipleri olabilir. bu şekilde, bir sunucunun diğer iplerine domain ekleme mümkün olabilir.
		 * orada varsa, oradaki yere gönderilir,
		 * orada yoksa, normal ipye gönderilir.
		 */

		if (trim($serverip) == '')
			$serverip = 'localhost';
		$accessip = $this->get_server_access_ip($serverip);

		if ($accessip <> 'localhost')
			my_shell_exec("rsync -arvz $this->ehcpdir/serverfiles/$accessip/webserver/* $accessip:" . $this->ehcpdir, __FUNCTION__);
		# for local server, files are directly written to normal destination, no need to send.
	}

	function send_dnsserver_files($server)
	{ # if remote machine is not yours, then, additional security should be taken here. **
		print __FUNCTION__ . ": basliyor \n";
		print_r($server);

		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.

		if (trim($serverip) == '')
			$serverip = 'localhost';
		if ($serverip <> 'localhost')
			my_shell_exec("rsync -arvz $this->ehcpdir/serverfiles/$serverip/named/* $serverip:/etc/bind/", __FUNCTION__);
		# for local server, files are directly written to normal destination, no need to send.
	}

	function get_dnsservers()
	{
		#this function is multi_server enabled.
		print __FUNCTION__ . ": basliyor \n";
		# this function reads servers table, then merges it through domain list, thus building dns/web servers

		$dnsservers = $this->query("select servertype,ip from servers where servertype in ('binddns')");
		$localserver = $this->query("select servertype,ip from servers where servertype in ('binddns') and ip='localhost'");
		if (count($localserver) == 0)
			$dnsservers[] = array('servertype' => 'binddns', 'ip' => 'localhost'); # add localhost if not exist # binddns harici bir sey kullanılacaksa, modifiye edilmeli.get_webservers a bak.

		#print_r($webservers);
		$w2 = array(); # servers in "servers" table
		foreach ($dnsservers as $w)
			$w2[$w['ip']] = $w['servertype']; # convert to ip=>type pairs

		print_r($w2);
		# kontrol edilecek.

		$doms = $this->getDomains();
		$doms[] = $this->last_deleted_domaininfo;




		$w3 = array(); foreach ($doms as $dom) {
			$ip = $dom['dnsserverips'];
			$list = explode(",", $ip);

			foreach ($list as $l) {
				$l = trim($l);
				if ($l <> '' and ($w3[$l] == '')) {
					$w3[$l] = $w2[$l]; # assign type
					if ($w3[$l] == '')
						$w3[$l] = 'binddns'; # if there is no type, set to default of apache2
				}
			}
		}

		if ($w3['localhost'] == '')
			$w3['localhost'] = 'binddns';
		print_r($w3);

		$w4 = array();
		$keys = array_keys($w3);

		foreach ($keys as $k)
			$w4[] = array('servertype' => $w3[$k], 'ip' => $k); #convert back to servers array

		print_r($w4);

		return $w4;
	}

	function prepare_dns_files($server)
	{
		$this->requireCommandLine(__FUNCTION__);

		$serverip = $server['ip'];
		$servertype = $server['servertype'];

		# get domains list first, related to
		if ($serverip == 'localhost') {
			$filt = "dnsserverips is null or dnsserverips='' or dnsserverips like '%localhost%'";
			$dnsserverip = $this->miscconfig['dnsip'];
		} else {
			$filt = "dnsserverips like '%$serverip%'";
			$dnsserverip = $serverip;
		}

		if ($serverip == localhost)
			$file = $this->conf['namedbase'] . "/named_ehcp.conf";
		else
			$file = "$this->ehcpdir/serverfiles/$serverip/named/named_ehcp_produced_by_" . $this->miscconfig['dnsip'] . '.conf';


		$arr = $this->getDomains($filt);
		if (count($arr) == 0) {
			echo "\n domains for this server cannot be found: $serverip, quiting " . __FUNCTION__ . "! \n";
			writeoutput2($file, "", 'w', false); # no domains, so, empty the config
			// Fix perms on the bind file
			$this->fixBindFilePerms($file);
			return True;
		}

		$exampledomain = $arr[0];
		$arr_aliaseddomains = $this->calculateAliasedDomains($arr, $exampledomain);

		# merge two array to one domains array:
		# this array is like 0 => array('domainname'=>'xxx.com')

		foreach ($arr_aliaseddomains as $aliasdomain) {
			$found = false;
			foreach ($arr as $dom)
				if ($aliasdomain['domainname'] == $dom['domainname'])
					$found = True;
			if (!$found)
				$arr[] = $aliasdomain;
		}

		# put customdns info into zone files..
		$arr_customdns = $this->query("select * from " . $this->conf['customstable']['tablename'] . " where name='customdns' ");
		$arr2 = array(); foreach ($arr as $dom) { # add customdns to array,
			$customdnsvalue = '';
			foreach ($arr_customdns as $cust) {
				if ($cust['domainname'] == $dom['domainname'])
					$customdnsvalue .= $cust['value'] . "\n"; # this loop prevents repetitive mysql query, thus faster execution.
			}
			$dom['customdns'] = $customdnsvalue;
			# will include domain aliases in dns too, to be able to catch those domains with dns
			$arr2[] = $dom;
		}

		echo "\n" . __FUNCTION__ . " working..:  domains for $serverip:\n";
		print_r($arr2);

		passthru2("rm -vf $this->ehcpdir/serverfiles/$serverip/named/*", True, True);

		if (($this->prepare_dns_zone_files($server, $arr2)) and ($this->prepare_dns_named_conf_file($server, $arr2))) {
			# sending of files is done in other function.
			return True;
		} else
			return false;

	}

	function replace_tags_with_multiple_values($subject, $tags, $values)
	{
		# replaces tags with multiple values,  a line containing a tag is replaced with multiple lines containing values.
		# written for building dns/web configs, generally used in multi-server dns config etc
		# tags: array(tag1,tag2...)   values: array(values1,values2...)  values1=comma separated list of ips/strings

		$lines = explode("\n", $subject);

		foreach ($lines as $line) { #process for each line
			$found = false;
			$tagindex = 0;
			foreach ($tags as $tag) { # process each tag, one line must have only one tag, otherwise, this function will fail
				if (strstr($line, $tag) !== false) {
					$replace = explode(',', $values[$tagindex]);
					foreach ($replace as $r)
						$out .= str_replace($tag, $r, $line) . "\n";
					$found = True;
				}
				$tagindex++;
			}
			if (!$found)
				$out .= $line . "\n";
		}

		return $out;
	}

	function replace_tags($template, $replace_fields, $replace_values)
	{
		return str_replace($replace_fields, $replace_values, $template); // replace domain fields,
	}

	function build_from_template($template, $tags_with_multiple_values, $multiple_values, $tags, $values)
	{
		echo __FUNCTION__ . ": basliyor \n";

		$template = $this->replace_tags_with_multiple_values($template, $tags_with_multiple_values, $multiple_values);
		$template = $this->replace_tags($template, $tags, $values);
		return $template;
	}

	function prepare_dns_zone_files($server, $arr2)
	{
		$this->requireCommandLine(__FUNCTION__);
		echo __FUNCTION__ . ": basliyor \n";

		$success = True;

		$serverip = $server['ip'];
		$servertype = $server['servertype'];


		//$this->output.=print_r2($arr);
		//print_r($arr);
		$alanlar = alanlarial($this->conn, "domains");
		$replacealanlar = arrayop($alanlar, "strop"); # puts each field in {}
		$replacealanlar[] = '{customdns}';

		# eger remote dns ler farkli olacaksa, burasi modifiye edilmeli.
		$dnstemplatefile = file_get_contents($this->dnszonetemplate); // get template once.
		#$dnstemplatefile=file_get_contents("$this->ehcpdir/dnszonetemplate_multiple_servers");// get template once.



		# for old style: # these are not needed if all templates are new style
		$mailserverip = $this->getMailServer();
		$dnsserverip = $this->getDnsServer();
		$webserverip = $this->get_webserver_real_ip();


		foreach ($arr2 as $ar1) {


			# assign ip addresses for different services..
			if ($ar1['serverip'] <> '') { # single ip if hosted in a single place,
				$mailserverips = $dnsserverips = $webserverips = $ar1['serverip'];
				$mailip = $webip = $dnsip = $ar1['serverip']; # for old styles

			} else {

				$dnsserverips = $ar1['dnsserverips'];
				$webserverips = $ar1['webserverips'];
				$mailserverips = $ar1['mailserverips'];

				if ($dnsserverips == '')
					$dnsserverips = $this->miscconfig['singleserverip'];
				if ($webserverips == '')
					$webserverips = $this->miscconfig['singleserverip'];
				if ($mailserverips == '')
					$mailserverips = $this->miscconfig['singleserverip'];

				# for old style:
				$mailip = $mailserverip;
				$dnsip = $dnsserverip;
				$webip = $webserverip;


			}

			$this->echoln2("yaziyor: " . $ar1["domainname"] . " mailip/webip/dnsip : $mailserverips/$webserverips/$dnsserverips");

			$dnstemp = $ar1['dnstemplate'];
			if ($dnstemp == '')
				$dnstemp = $dnstemplatefile; // read dns info from template file, if not written to db..
			echo "\n" . __FUNCTION__ . ": buraya geldi.\n";

			# replace any old style tags that may be left on template:
			$replacealanlar = array_merge($replacealanlar, array('{serial}', '{ip}', '{dnsemail}', '{mailip}', '{dnsip}', '{webip}')); # tags to replace
			$ar1 = array_merge($ar1, array(rand(1, 1000), $this->conf['dnsip'], $this->conf['dnsemail'], $mailip, $dnsip, $webip)); # tag contents to put


			$dnstemp = $this->build_from_template($dnstemp, array('{dnsserverips}', '{webserverips}', '{mailserverips}'), array($dnsserverips, $webserverips, $mailserverips), $replacealanlar, $ar1);
			#echo "Hazirlanan dns config: \n$dnstemp";

			# lokalden erisenler icin ayri bir dns, dns icinde view olusturulabilir buraya bak: http://www.oreillynet.com/pub/a/oreilly/networking/news/views_0501.html
			# amac: bir networkde server varsa, o network icinden erisenler icin bu bir local server dir. her desktop da ayri ayri hosts ayari girmek yerine, sunucu bunlara real degil, lokal ip doner.
			# bu sayede, kucuk-orta isletmeler icin, sunucunun lokalden cevap vermesi saglanir.. veya dns icinde view destegi, birden cok konfigurasyon v.b...
			# to translate Turkish comments, use google translate..
			# $dnstemplocal=str_replace(array('{serial}',"{ip}","{dnsemail}"),array(rand(1,1000),$this->conf['dnsip'],$this->conf['dnsemail']),$dnstemp);

			# $temp=str_replace(array('{serial}',"{ip}","{dnsemail}"),array(rand(1,1000),$this->conf['dnsip'],$this->conf['dnsemail']),$temp); // replace serial,ip,dnsemail etc.   Ymds hata veriyordu iptal ettim. bu sorunla ilgilenilecek...
			// verdigi hata: Fatal error: date(): Timezone database is corrupt - this should *never* happen!  thats why i cannot use date in daemon mode... this seems a php bug.., for tr locale

			if ($serverip == 'localhost')
				$file = $this->conf['namedbase'] . '/' . $ar1["domainname"];
			else
				$file = "$this->ehcpdir/serverfiles/$serverip/named/" . $ar1["domainname"];

			$success = $success and writeoutput2($file, $dnstemp, "w");

			// Fix perms on the bind file
			$this->fixBindFilePerms($file);

			#$success=$success and writeoutput2($this->conf['namedbase'].$ar1["domainname"].".local",$dnstemplocal,"w"); # bu kisim henuz tamamlanmadi, yani lokal destegi..

		}
		return $success;

	}

	function prepare_dns_named_conf_file($server, $arr)
	{
		$this->requireCommandLine(__FUNCTION__);
		$serverip = $server['ip'];
		$servertype = $server['servertype'];

		foreach ($arr as $ar) {
			$ar['namedbase'] = $this->conf['namedbase'];
			$arr2[] = $ar;
		}

		# bind harici dns icin burda modifiye yapilmali
		# if($servertype=='binddns') .....

		$out .= $this->putArrayToStr($arr2, $this->dnsnamedconftemplate);

		if ($serverip == localhost)
			$file = $this->conf['namedbase'] . "/named_ehcp.conf";
		else
			$file = "$this->ehcpdir/serverfiles/$serverip/named/named_ehcp_produced_by_" . $this->miscconfig['dnsip'] . '.conf';

		echo "\n\nwriting namedfile: $file \n\n";
		$success = writeoutput2($file, $out, w);

		// Fix perms on the bind file
		$this->fixBindFilePerms($file);

		return $success;

	}

	function new_sync_dns()
	{
		$this->requireCommandLine(__FUNCTION__);

		# will be like this:
		$dnsservers = $this->get_dnsservers(); # get server list, server list should determine the type of remote server. currently, default is apache2

		foreach ($dnsservers as $w)
			$this->prepare_dns_files($w); # for each server in list, call prepare_server_files
		foreach ($dnsservers as $w)
			if ($w <> 'localhost')
				$this->send_dnsserver_files($w); # send server_files(configs) to destinations.
		foreach ($dnsservers as $w)
			$this->restart_dnsserver2($w);

		# done. todo: add domain info to dns as "remote"	: this may be done while adding domain.
		# done. todo: setup domain ftp in remote or nfs/nas: this may be done while adding domain.
		return True;
	}

	function restart_dnsserver2($serverip)
	{
		if (is_array($serverip))
			$serverip = $serverip['ip']; # accept both until all code is standard.
		echo __FUNCTION__ . ": basliyor: $serverip dns restarting.. \n";

		if ($serverip == '')
			$serverip = 'localhost';
		$this->server_command($serverip, getServiceActionStr("bind9", "restart"));

	}

	function adjust_webmail_dirs()
	{
		# for squirrelmail, which is bundled in ehcp dir, webmail folder.
		$localconfig = "<?php
\$data_dir				 = '$this->ehcpdir/webmail/data/';
\$attachment_dir		   = '$this->ehcpdir/webmail/data/';
?>";

		$success = writeoutput2("$this->ehcpdir/webmail/config/config_local.php", $localconfig, 'w', false);
		passthru("chmod a+w $this->ehcpdir/webmail/data/");

		$change_pass_config = "<?
\$dbhost='localhost';
\$dbusername='ehcp';
\$dbpass='$this->dbpass';
\$dbname='ehcp';
?>";

		$success = $success && writeoutput2("$this->ehcpdir/webmail/plugins/ehcp_change_pass/config.php", $change_pass_config, 'w', false);

		return $success;
	}

	function new_sync_domains($file = '')
	{
		$this->requireCommandLine(__FUNCTION__);

		# will be like this:
		$webservers = $this->get_webservers(); # get server list, server list should determine the type of remote server. currently, default is apache2
		echo __FUNCTION__ . ":\n";
		print_r($webservers);


		$multiserver = false;
		foreach ($webservers as $w) {
			if ($this->get_server_access_ip($w["ip"]) <> 'localhost') {
				$multiserver = True;
			}
		}

		if ($multiserver) {
			echo "\n" . __FUNCTION__ . ": This seems a multiserver ehcp, has more servers than localhost\n";
			foreach ($webservers as $w)
				$this->prepare_webserver_files('', $w); # for each server in list, call prepare_server_files
			foreach ($webservers as $w)
				if ($w['ip'] <> 'localhost')
					$this->send_webserver_files($w); # send server_files(configs) to destinations.
			foreach ($webservers as $w)
				$this->restart_webserver2($w);
			$success = $this->syncSubdomains(); # this func is not multi-server... this is a lack of feature, an important one... under construction.

			sleep(1);
			$success = $success && $this->configtest_reload_webserver();

			sleep(1);
			$success = $success && $this->run_lets_encrypt_commands();

		} else {
			echo "\n" . __FUNCTION__ . ": This seems NOT a multiserver ehcp, has only localhost as server\n";
			$this->syncDomains();
		}

		# done. todo: add domain info to dns as "remote"	: this may be done while adding domain.
		# done. todo: setup domain ftp in remote or nfs/nas: this may be done while adding domain.
		return True;
	}


	function new_sync_all()
	{
		$this->requireCommandLine(__FUNCTION__);
		$success = $this->new_sync_domains();
		$success = $success && $this->new_sync_dns();

		if ($success)
			$this->last_deleted_domaininfo = false; # burada kucuk problem cıkabilir
		#$this->conn->AutoExecute('operations',array('op'=>'new_sync_domains3'),'INSERT');  # this is not working, thats why, i need to leave adodb autoinserts..
		return $success;
	}

	function sync_server_services()
	{
		$this->requireCommandLine(__FUNCTION__);
		$success = $this->addDaemonOp('syncdomains', '', '', '', 'sync domains');
		$success = $success && $this->addDaemonOp('syncdns', '', '', '', 'sync dns');
		$success = $success && $this->addDaemonOp('syncftp', '', '', '', 'sync ftp for nonstandard homes');
		return $success;
	}

	function build_logrotate_conf($arr2, $host)
	{
		if ($this->debuglevel > 0)
			print_r($arr2);

		foreach ($arr2 as $dom) {
			$logrotate .= $dom['homedir'] . "/logs/access_log " . $dom['homedir'] . "/logs/error_log ";

			// Add subdomain log files 
			$subdomains = $this->getSubDomains("domainname = '" . $dom['domainname'] . "'");
			foreach ($subdomains as $subd) {
				$logrotate .= $subd['homedir'] . "/logs/access_log " . $subd['homedir'] . "/logs/error_log ";
			}
		}


		$logrotate .= ($logrotate[strlen($logrotate) - 1] == ' ' ? '' : ' ') . "/var/log/ehcp.log /var/log/php_errors.log /var/log/apache_common_access_log {
		daily
		missingok
		compress
		delaycompress
		su root root		
}";

		passthru2('mkdir -p ' . $this->ehcpdir . '/etc/logrotate.d/');
		writeoutput($this->ehcpdir . '/etc/logrotate.d/ehcp', $logrotate, 'w', True);

		$cmd = "cp -vf " . $this->ehcpdir . '/etc/logrotate.d/ehcp /etc/logrotate.d/';
		if ((!$host) or ($host == 'localhost'))
			passthru2($cmd); # bu kısım bir fonksiyon yapılabilir.
		else
			$this->cmds[] = $cmd; # multi server da kullanmak uzere
	}


	function initializeDomainFiles($dom, $domainname)
	{ # singleserver  mode
		$this->requireCommandLine(__FUNCTION__);
		if ($domainname <> '') {
			if ($dom['domainname'] <> $domainname)
				return; # do only requested domains. 
		}

		$domainname = $dom['domainname'];
		$homedir = $dom['homedir'];
		$username = $dom['panelusername'];

		passthru2("mkdir -p $homedir/httpdocs");

		// Create webstats directory and password protection file used to "protect" webstats information from public viewing
		passthru2("mkdir -p $homedir/httpdocs/webstats/");
		$this->createPasswordAuthFileForWebstatsDir($homedir, $domainname, $username);

		# put default index
		if ($this->noExistingIndex($homedir)) {
			$filestr = $this->loadTemplate('defaultindexfordomains'); # load template
			$findarray = array('webserverip', 'domainname', 'localip'); # replace some variables,
			$localipcode = "<?php echo getenv('REMOTE_ADDR'); ?>";

			$replacearray = array($this->getWebServer(), $domainname, $localipcode);
			$findarray2 = arrayop($findarray, "strop");
			$fileout = str_replace($findarray2, $replacearray, $filestr);

			writeoutput2($homedir . "/httpdocs/index.php", $fileout, "w"); # put in index file
			passthru2("chown $this->ftpowner -Rf $homedir");
		} # ownership is not changed if some files already exists there..	

		$this->initializeLogs($homedir);
		$this->initializePhpTmpDir($homedir);

		# adjust some custom file ownerships, for wordpress and some scripts.. 
		$q = "select * from customsettings where domainname='$domainname' and name='fileowner' and `value`<>'root'";
		$ownership = $this->query($q);

		foreach ($ownership as $ow) {
			echo "Adjusting custom file ownership: \n";
			passthru2("chown " . $ow['value'] . " -Rf $homedir/httpdocs/" . $ow['value2']);
			#$this->pwdls('file ownership:',"$homedir/httpdocs/".$ow['value2']);
		}


		# put some files if not exists:
		foreach (array('ehcpinfo.html', 'error_page.html') as $f) {
			if (!file_exists("$homedir/httpdocs/$f")) {
				passthru2("cp -f $f $homedir/httpdocs/");
			}
		}

		writeoutput2($homedir . "/UPLOAD_SITE_FILES_TO_httpdocs_FOLDER", "", "w"); // z7 mod
		passthru2("cp " . $this->ehcpdir . "/z7/install_files/.htaccess " . $homedir . "/phptmpdir/.htaccess"); // z7 mod
		if ($this->noExistingIndex($homedir)) {
			passthru2("cp " . $this->ehcpdir . "/z7/install_files/domain_index.php " . $homedir . "/index.php"); // z7 mod
		}

	}

	function createPasswordAuthFileForWebstatsDir($home, $domain, $username)
	{
		$webstatsAuthFile = $home . "/" . $this->webstats_auth_file;
		if ($this->hasValueOrZero($home) && $this->hasValueOrZero($domain) && $this->hasValueOrZero($username)) {
			if (!file_exists($webstatsAuthFile)) {
				// Create authentication file
				$buildAuthFileCommand = "htpasswd -b -c '" . $webstatsAuthFile . "' '" . $domain . "' '" . $username . "'";

				// Create the password directory
				passthru2($buildAuthFileCommand, true, true);

				// Set proper permissions on the authentication file used for the webstats directory
				passthru2("chown " . $this->wwwuser . ":" . $this->wwwgroup . " " . $webstatsAuthFile, true, true);
				passthru2("chmod 644 $webstatsAuthFile", true, true);

				return true;
			}
		}
		return false;
	}

	function getWebStatsProtectionDirective($dom, $webserv)
	{
		$string = "";
		$httpdocs = $dom["homedir"] . "/httpdocs";
		$webstatsAuthFile = $dom["homedir"] . "/" . $this->webstats_auth_file;
		$pathToWebstats = $dom["homedir"] . "/httpdocs/webstats";
		$nginxRelativePathToWebstats = "webstats";
		if (file_exists($pathToWebstats)) {
			if ($webserv == "nginx") {
				$string = "
	location ^~ /" . $nginxRelativePathToWebstats . "/ {
		root " . $httpdocs . ";
		auth_basic \"Restricted Area\";
		auth_basic_user_file $webstatsAuthFile;
	}";
			} else if ($webserv == "apache2") {
				$string = "
	<Directory \"" . $pathToWebstats . "\">
		AuthType Basic
		AuthName \"Authentication Required\"
		AuthUserFile \"" . $webstatsAuthFile . "\"
		Require valid-user

		Order allow,deny
		Allow from all
	</Directory>";
			}
		}
		return $string;
	}

	function syncDomains($file = '', $domainname = '')
	{
		$webservertype = $this->miscconfig['webservertype'];
		$templatefield = $webservertype . 'template';

		$this->requireCommandLine(__FUNCTION__);

		echo "\nstart syncing domains\nlocalip:" . $this->miscconfig['localip'] . ", dnsip:" . $this->miscconfig['dnsip'] . "\nwebservertype:" . $this->miscconfig['webservertype'] . "\n";
		if ($file == '')
			$file = "apachehcp.conf";
		$filt = andle($this->activefilt, "(serverip is null or serverip='') and homedir<>'' order by theorder"); # exclude where serverip is set, that is, for remote dns hosted only domains..

		if ($domainname <> '') {
			echo "###>>  syncdomain is initialising files only for a single domain: $domainname !!\n";
		}

		$arr = $this->getDomains($filt);
		if ($this->debuglevel > 0)
			print_r($arr);

		$webserverip = $this->getWebServer();
		echo "\nwebserverip: $webserverip\n";

		$success = True;
		$arr_customhttp = $this->query("select * from " . $this->conf['customstable']['tablename'] . " where name='customhttp'  and (webservertype is null or webservertype='' or webservertype='" . $this->miscconfig['webservertype'] . "')");
		$arr2 = array();

		$webserver_template_filename = "$this->ehcpdir/apachetemplate"; # this file may be an apache template actually, or an nginx template, code will be fixed later.. 
		$globalWebServerTemplate = $this->getGlobalDomainTemplate();

		$ips = array();

		$csv = array_map('str_getcsv', file($this->ehcpInstallPath . '/misc/SLDs.csv'));

		foreach ($arr as $dom) { // setup necessry dirs/files if doesnt exist..
			$this->initializeDomainFiles($dom, $domainname);

			# add customhttp to array,
			$customhttpvalue = '';

			if ($arr_customhttp)
				foreach ($arr_customhttp as $cust) {
					if ($cust['domainname'] == $dom['domainname'])
						$customhttpvalue .= $cust['value'] . "\n"; # this loop prevents repetitive mysql query, thus faster execution.
				}

			if ($this->miscconfig['webservertype'] == "nginx") {
				$dom['password_protected_dirs'] = $this->getNginxPasswordProtectedSubDirectoriesByDomain($dom['domainname']);
				$dom['root_password_protected_dirs'] = $this->getNginxPasswordProtectedRootDirectoriesByDomain($dom['domainname']);
			} else {
				$dom['password_protected_dirs'] = $this->getApachePasswordProtectedSubDirectoriesByDomain($dom['domainname']);
				$dom['root_password_protected_dirs'] = $this->getApachePasswordProtectedRootDirectoriesByDomain($dom['domainname']);
			}

			if (file_exists($dom['homedir'] . "/" . $this->webstats_auth_file)) {
				$dom['webstats_password_protection'] = $this->getWebStatsProtectionDirective($dom, $this->miscconfig['webservertype']);
			} else {
				$dom['webstats_password_protection'] = "";
			}

			// Get redirection for domain if any
			$dom['domainname_redirect'] = $this->getRedirectDomain($dom['domainname']);

			// If the domain is configured to use an HTTP to HTTPS redirect, populate the redirect domain for HTTP
			if (empty($dom['domainname_redirect'])) {
				$sslInfo = $this->getSSLSettingForDomain($dom['domainname']);
				$stripSSLSectionFromTemplate = $this->getStripSSLSectionForDomain($sslInfo);
				$stripNonSSLSectionFromTemplate = $this->getStripNonSSLSectionForDomain($sslInfo, $stripSSLSectionFromTemplate);
				if ($stripNonSSLSectionFromTemplate === true && !$stripSSLSectionFromTemplate) {
					$dom['domainname_redirect'] = $dom['domainname'];
					if ($this->miscconfig['webservertype'] == "nginx") {
						$dom['domainname_redirect'] = '$host';
					} else {
						$dom['domainname_redirect'] = '%{HTTP_HOST}';
					}
				}
			}

			// Get custom ssl certs for domain if any
			$sslInfo = $this->getSSLSettingForDomain($dom['domainname']);
			if (!empty($sslInfo["cert"])) {

				echo "\nUsing custom SSL certificate for domain " . $dom['domainname'] . ".\n";

				$certPath = $dom['homedir'] . "/phptmpdir/server.crt";
				$certKeyPath = $dom['homedir'] . "/phptmpdir/server.key";
				$certChainPath = $dom['homedir'] . "/phptmpdir/chain.crt";
				$certMixedPathNginx = $dom['homedir'] . "/phptmpdir/mixed.crt";

				if (file_exists($certPath)) {
					$dom['ssl_cert_path'] = $certPath;
				} else {
					// Use default server one
					$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
				}
				if (file_exists($certKeyPath)) {
					$dom['ssl_cert_key_path'] = $certKeyPath;
				} else {
					// Use default server one
					$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
				}
				if (file_exists($certChainPath)) {
					$dom['ssl_cert_chain_setting_with_path'] = "SSLCertificateChainFile " . $certChainPath;
				} else {
					$dom['ssl_cert_chain_setting_with_path'] = '';
				}

				// For nginx
				if ($this->miscconfig['webservertype'] == "nginx" && file_exists($certMixedPathNginx) && file_exists($certKeyPath)) {
					$dom['ssl_cert_path'] = $certMixedPathNginx;
				}
			} else if ($sslInfo["letsenc"] && empty($sslInfo["cert"])) {
				echo "\nUsing Let's Encrypt SSL certificate for domain " . $dom['domainname'] . ".\n";
				$dmnNamesToEncrypt = array($dom['domainname']);
				$parts = explode(".", $dom['domainname']);
				if (count($parts) <= 2 || $this->domainIsCCTLD($dom['domainname'], $csv)) {
					array_push($dmnNamesToEncrypt, "www." . $dom['domainname']); // Add www. as alias for cert
				}
				$encDomains["domainnames"] = $dmnNamesToEncrypt;
				$encDomains["domainpath"] = $dom['homedir'] . "/httpdocs";
				$this->getAndUseLetsEncryptCert($encDomains, $this->getClientEmailFromPanelUsername($dom['panelusername']));

				// Admin function... parse extra hosts which take this format... this is an advanced option only
				// Takes the format of ns3.otherdomain.com{skipdomain},ns4=/var/www/new/ehcp;nextsubhost,n88,a9=/location
				if (isset($sslInfo["lets_enc_additional_hosts"]) && !empty($sslInfo["lets_enc_additional_hosts"])) {
					$subdoms = $this->getSubDomains("domainname = '" . $dom['domainname'] . "'");
					if (is_array($subdoms) && count($subdoms) > 0) {
						$subdomsDomain = array_map(function ($ar) {
							return $ar["subdomain"]; }, $subdoms);
					} else {
						$subdomsDomain = array();
					}

					if (strrpos($sslInfo["lets_enc_additional_hosts"], ";") !== false) {
						$entries = explode(";", $sslInfo["lets_enc_additional_hosts"]);
						$entries = array_filter($entries);
					} else {
						$entries = array($sslInfo["lets_enc_additional_hosts"]);
					}

					foreach ($entries as $entry) {
						$hostSpecificPath = "";
						$hostArray = array();

						if (strrpos($entry, "=") !== false) {
							$settings = explode("=", $entry);
							if (count($settings) == 2) {
								$hosts = $settings[0];
								$hostSpecificPath = $settings[1];
								if (!empty($hosts) && !empty($hostSpecificPath)) {
									if (strrpos($hosts, ",") !== false) {
										$hostArray = explode(",", $hosts);
										$hostArray = array_filter($hostArray);
									} else {
										$hostArray = array($hosts);
									}
								}
							}
						} else {
							if (strrpos($entry, ",") !== false) {
								$hostArray = explode(",", $entry);
								$hostArray = array_filter($hostArray);
							} else {
								$hostArray = array($entry);
							}
						}

						if (isset($hostArray) && is_array($hostArray)) {
							foreach ($hostArray as $h) {
								$appendDomainNameToEnd = true;

								if (stripos($h, "{skipdomain}") !== false) {
									$appendDomainNameToEnd = false;
									$h = str_replace("{skipdomain}", "", $h);
								}

								$h = removeInvalidChars($h, "domainname");
								if (!in_array($h, $subdomsDomain) && $h != "www" && $h != $dom['domainname'] && stripos($h, $dom['domainname']) === false) {
									if ($appendDomainNameToEnd) {
										$hostArrayFinal[] = $h . "." . $dom['domainname'];
									} else {
										$hostArrayFinal[] = $h;
									}
								}
							}
						}

						if (isset($hostArrayFinal) && is_array($hostArrayFinal) && count($hostArrayFinal) > 0) {
							if ($this->hasValueOrZero($hostSpecificPath)) {
								$encDomainsExtraAdmin["domainpath"] = $hostSpecificPath;
							} else {
								$encDomainsExtraAdmin["domainpath"] = $dom['homedir'] . "/httpdocs";
							}
							$encDomainsExtraAdmin["domainnames"] = $hostArrayFinal;
							$this->getAndUseLetsEncryptCert($encDomainsExtraAdmin, $this->getClientEmailFromPanelUsername($dom['panelusername']));
						}
					}
				}

				// END ADVANCED LOGIC

				if (file_exists("/etc/letsencrypt/live/" . $dom['domainname'] . "/fullchain.pem")) {
					$dom['ssl_cert_path'] = "/etc/letsencrypt/live/" . $dom['domainname'] . "/fullchain.pem";
					$dom['ssl_cert_key_path'] = "/etc/letsencrypt/live/" . $dom['domainname'] . "/privkey.pem";
					$dom['ssl_cert_chain_setting_with_path'] = '';
				} else {
					$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
					$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
					$dom['ssl_cert_chain_setting_with_path'] = '';
				}
			} else {
				echo "\nUsing global server SSL certificate for domain " . $dom['domainname'] . ".\n";
				$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
				$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
				$dom['ssl_cert_chain_setting_with_path'] = '';
			}

			$dom['customhttp'] = $customhttpvalue;

			# add ServerAlias to begining of lines in aliases field
			$aliases = $dom['aliases'];
			$aliasarr = explode("\n", $aliases);
			$newaliases = "";
			foreach ($aliasarr as $al)
				if (trim($al) <> '')
					$newaliases .= "ServerAlias " . trim($al) . " \n"; # this is apache specific code, should be fixed later.
			$dom['aliases'] = $newaliases;
			$dom['webserverip'] = $webserverip; # taken from system ip setting.


			if ($dom['webserverips'] <> '') {
				list($i) = explode(',', $dom['webserverips']);
				if (validateIpAddress($i)) {
					echo "\nThis domain has custom webserverips,adjusting:" . $dom['domainname'] . ":" . $dom['webserverips'];
					$dom['webserverip'] = $i; # if entered in db exclusively.  # diger ip ler ne olacak ? sanirim multiserver fonksiyonlarinda halledilecek... 
					switch ($this->miscconfig['webservertype']) {
						case 'apache2':
							$webserver_template_filename = "$this->ehcpdir/apachetemplate_ipbased";
							break;
						# other servers, if multi-ip supported, it seems no change needed for nginx
					}
					if (!in_array($i, $ips))
						$ips[] = $i;
					if (!in_array($webserverip, $ips))
						$ips[] = $webserverip; # add default ip too.
				}
			}


			$arr2[] = $dom;
		}

		# here write config to apachehcp.conf file.
		# you may see daemon mode output at logfile, typically tail -f /var/log/ehcp.log from command line

		echo "\n**Syncing domains for webserver type of (" . $this->miscconfig['webservertype'] . "):";
		if ($this->debuglevel > 0)
			print_r($arr2);
		if ($this->debuglevel > 0)
			print_r($ips);

		$this->build_logrotate_conf($arr2, 'localhost');

		#begin: reconstruct apache config file:
		$fileout = "# This is an automatically generated file, by ehcp. Do not edit this file by hand. if you need to change webserver configs, edit apachetemplate(or similar) file (to take effect for all domains) in ehcp dir, or use (custom http or edit webserver/apache template to take effect for single domain) in ehcp gui \n";

		if ($this->miscconfig['webservertype'] == 'apache2') {
			foreach ($ips as $i) { # eger ipler kullanılacaksa
				$fileout .= "\nNameVirtualHost $i\n";
			}
		}

		if (count($arr2) > 0) {
			$alanlar = array_keys($arr2[0]); // gets array keys, from first(0th) array element of two-dimensional $arr2 array.

			// following code, replaces fields from template to values here in $arr2 two-dim array. each $arr2 element written to output file according to template file.
			$replacealanlar = arrayop($alanlar, "strop");
			$webserver_template_file = file_get_contents($webserver_template_filename);

			$sslenabled = strstr($webserver_template_file, "{webserverip}") !== false;
			# *1 if template file contains {webserverip} then, ssl/ipbased is assumed to enabled on apache configs.
			# in this case, non-ssl/ipbased custom http's are disabled to prevent apache config error. All custom http's should be fixed by admin in this case.

			if ($sslenabled)
				$this->echoln("ssl/ipbased seems enabled in this server, because tag {webserverip} is found in apache config templates files.."); # this is a bit apache specific code.
			else
				$this->echoln("ssl/ipbasedseems not enabled in this server, because tag {webserverip} is not found in apache config templates files..");


			foreach ($arr2 as $ar1) { // template e gore apache dosyasini olustur
				$webserver_template = $ar1[$templatefield]; # get domain specific (custom) template
				if ($webserver_template == '' and $ar1['apachetemplate'] <> '')
					$webserver_template = $ar1['apachetemplate']; # be backward compatible, for older installs..

				if ($webserver_template <> '') {
					$this->echoln2("Domain:" . $ar1['domainname'] . " has custom webserver template.");
					$webserver_template .= "\n#this last template is read from database for " . $ar1['domainname'] . "..\n\n";

					if ($sslenabled and strstr($webserver_template, "{webserverip}") === false) {
						$this->echoln("apache config is adjusted as ssl/ipbased enabled, however, your custom http for this domain contains non-ssl/ipbased custom http, so, I disable custom http for this domain:" . $ar1['domainname']);
						$webserver_template = $webserver_template_file; # read explanation above *1
					} elseif (!$sslenabled and strstr($webserver_template, "{webserverip}") !== false) {
						$this->echoln("apache config is adjusted as non-ssl/ipbased enabled, however, your custom http for this domain contains ssl/ipbased custom http, so, I disable custom http for this domain:" . $ar1['domainname']);
						$webserver_template = $webserver_template_file; # read explanation above *1
					}
				} else if (!empty($globalWebServerTemplate)) {
					$this->echoln2("Domain:" . $ar1['domainname'] . " should use the global admin templated domain template!");
					$webserver_template = $globalWebServerTemplate;
				} else {
					$this->echoln2("Domain:" . $ar1['domainname'] . " should use the default domain template!");
					$webserver_template = $webserver_template_file;
				}

				// Do redirect and sslonly adjustments regardless of which web template is used
				if (!empty($webserver_template)) {
					$webserver_template = $this->adjustDomainTemplateForRedirect($webserver_template, $ar1);
				}

				if ($this->miscconfig['enablewildcarddomain'] <> '')
					$wildcard = '*.{domainname}';
				else
					$wildcard = '';

				# replace some fields that does not exist in domain array
				$webserver_template = str_replace(array('{ehcpdir}', '{localip}', '{wildcarddomain}'), array($this->ehcpdir, $this->miscconfig['localip'], $wildcard), $webserver_template);
				$webserver_config = str_replace($replacealanlar, $ar1, $webserver_template);
				$fileout .= $this->adjustWebTemplateConfIfNeededForLineBreaks($webserver_config);
			}
		}

		$res = writeoutput2($file, $fileout, 'w', false);
		if ($res) {
			$this->echoln("Domain list exported (syncdomains) webserver conf to: $file \n");
		} else
			$success = false;
		# end: reconstruct apache config file:

		#process passive domains
		$passivedomains = $this->getDomains($this->passivefilt);
		echo "Passive domains:\n";
		print_r($passivedomains);

		$passives = array();
		foreach ($passivedomains as $p) {
			if ($ssl_enabled) {
				$p['webserverip'] = $webserverip;
			}
			$this->initializeDomainFiles($p, $domainname);
			$passives[] = $p;
		}

		$this->putArrayToFile($passivedomains, "apachehcp_passivedomains.conf", "apachetemplate_passivedomains");
		
		if(!is_array($passivedomains) || count($passivedomains) == 0){
			// Clear the contents to avoid webserver issues
			$success = writeoutput2("apachehcp_passivedomains.conf", "", 'w', false);
		}

		$passiveindex = $this->miscconfig['passiveindexfile'];
		if ($passiveindex == '')
			$passiveindex = $this->sayinmylang("domain_deactivated_contact_administrator");
		writeoutput2("/var/www/passivedomains/index.html", $passiveindex, 'w', false);
		# end processs passive domains

		# Add a second of wait time between functions. by eric.
		# I've seen some strange issues regarding the exit status of apache2ctl

		if(!is_array($arr) || count($arr) == 0){
			// Clear the contents to avoid webserver issues
			$success = writeoutput2($file, "", 'w', false);
		}

		sleep(1);
		$success = $success && $this->syncSubdomains('', $domainname);

		sleep(1);
		$success = $success && $this->syncGlobalPanelURLS();

		sleep(1);
		$this->handleGlobalTemplatesForBaseWebserverFiles();

		sleep(1);
		$success = $success && $this->configtest_reload_webserver();

		sleep(1);
		$success = $success && $this->run_lets_encrypt_commands();

		if ($this->miscconfig['updatehostsfile'] <> '')
			$this->updateHostsFile();

		return $success;
	}

	function getStripSSLSectionForDomain($sslInfo)
	{
		$stripSSLSectionFromTemplate = false;
		if (strtolower($this->miscconfig['useglobalsslcert']) != 'yes' && $this->miscconfig['webservermode'] == 'ssl') {
			if (empty($sslInfo["cert"]) && empty($sslInfo["letsenc"])) {
				$stripSSLSectionFromTemplate = true;
			} else {
				$stripSSLSectionFromTemplate = false;
			}
		}
		return $stripSSLSectionFromTemplate;
	}

	function getStripNonSSLSectionForDomain($sslInfo, $stripSSLSectionFromTemplate)
	{
		$stripNonSSLSectionFromTemplate = false;
		if ($this->miscconfig['webservermode'] == 'ssl' && $sslInfo["redir_https"] && !$stripSSLSectionFromTemplate) {
			$stripNonSSLSectionFromTemplate = true;
		}
		return $stripNonSSLSectionFromTemplate;
	}

	function adjustDomainTemplateForRedirect($webserver_template, &$ar1, $type = 'domain', $echoOn = true)
	{
		// If the domain should be redirected, we need to use a different webserver_template_file
		if (!empty($ar1['domainname_redirect']) && $ar1['domainname_redirect'] != $ar1['domainname'] && $ar1['domainname_redirect'] != '%{HTTP_HOST}' && $ar1['domainname_redirect'] != '$host') {
			$this->echoln("domain redirect is set to: " . $ar1['domainname_redirect'] . " for the domain of " . $ar1['domainname'] . "!");

			$webserver_template = $this->getGlobalRedirectTemplate();
			if (empty($webserver_template)) {
				$webserver_template = file_get_contents($this->ehcpdir . "/apachetemplate_redirect");
			}

			// See if we should include the request URI as part of the redirect template (a redirect URL without a slash in it)
			$removeProt = array("https://", "http://");
			$nameWithoutHTTP = str_replace($removeProt, '', $ar1['domainname_redirect']);
			if (stripos($nameWithoutHTTP, '/') !== false) {
				if ($this->miscconfig['webservertype'] == "nginx") {
					$webserver_template = str_replace('{domainname_redirect}$request_uri', '{domainname_redirect}', $webserver_template);
				} else if ($this->miscconfig['webservertype'] == "apache2") {
					$webserver_template = str_replace('{domainname_redirect}%{REQUEST_URI}', '{domainname_redirect}', $webserver_template);
				}
			}
		} else {
			$webserver_template = $this->adjustDomainTemplateDependingOnSSLSettings($webserver_template, $ar1, $type, $echoOn);
		}

		return $webserver_template;
	}

	function adjustDomainTemplateDependingOnSSLSettings($webserver_template, &$ar1, $type = 'domain', $echoOn = true)
	{
		$sslInfo = $this->getSSLSettingForDomain($ar1['domainname']);
		$stripSSLSectionFromTemplate = $this->getStripSSLSectionForDomain($sslInfo);
		$stripNonSSLSectionFromTemplate = $this->getStripNonSSLSectionForDomain($sslInfo, $stripSSLSectionFromTemplate);

		if ($stripSSLSectionFromTemplate === true) {
			if ($echoOn) {
				if ($type == "domain") {
					$this->echoln2("Removing SSL portions from template for " . $type . " " . $ar1['domainname']);
				} else {
					$this->echoln2("Removing SSL portions from template for " . $type . " " . $ar1["subdomain"] . "." . $ar1["domainname"]);
				}
			}
			$webserver_template = stripContentsAfterLine("# FOR SSL CONFIG", $webserver_template);
		}

		if ($stripNonSSLSectionFromTemplate === true && !$stripSSLSectionFromTemplate) {
			if ($echoOn) {
				if ($type == "domain") {
					$this->echoln2("Removing Non-SSL portions from template for " . $type . " " . $ar1['domainname'] . " and redirecting all standard HTTP requests to HTTPS!");
				} else {
					$this->echoln2("Removing Non-SSL portions from template for " . $type . " " . $ar1["subdomain"] . " and redirecting all standard HTTP requests to HTTPS!");
				}
			}

			$redirectTemplate = $this->getGlobalRedirectTemplate();
			if (empty($redirectTemplate)) {
				$redirectTemplate = file_get_contents($this->ehcpdir . "/apachetemplate_redirect");
			}

			$httpOnlyRedirect = stripContentsAfterLine("# FOR SSL CONFIG", $redirectTemplate);
			$httpOnlyRedirect = str_replace("{domainname_redirect}", "https://{domainname_redirect}", $httpOnlyRedirect);
			$ar1['domainname_redirect'] = $ar1['domainname'];

			if ($type == "subdomain") {
				// Gotta use the subdomain here
				$httpOnlyRedirect = str_replace("{domainname}", "{subdomain}.{domainname}", $httpOnlyRedirect);
				$ar1['domainname_redirect'] = $ar1["subdomain"];
			}

			if ($this->miscconfig['webservertype'] == "nginx") {
				$ar1['domainname_redirect'] = '$host';
			} else {
				$ar1['domainname_redirect'] = '%{HTTP_HOST}';
			}

			$webserver_template = getContentsAfterLine("# FOR SSL CONFIG", $webserver_template);
			$webserver_template = $httpOnlyRedirect . "\n" . $webserver_template;
		}

		if ((!empty($sslInfo["cert"]) || !empty($sslInfo["letsenc"])) && $this->miscconfig['webservermode'] == 'ssl') { // If SSL has been enabled and it previously wasn't for the domain / subdomain and the server is running mixed SSL mode, adjust the template to make sure it has an SSL section
			// The domain is using some kind of SSL certificate, so make sure it has an SSL section in its template (may not always have one depending on advanced panel settings)
			$sslContents = getContentsAfterLine("# FOR SSL CONFIG", $webserver_template);
			if ((empty($sslContents) || empty(trim($sslContents)) || !$sslContents) && strpos($webserver_template, "# FOR SSL CONFIG") !== false) {
				// It should have an SSL template at this point
				$sslContents = $this->getPartOfDomainTemplate($type, "ssl");
				if ($sslContents && !empty($sslContents)) {
					$webserver_template = $webserver_template . "\n" . $sslContents;
				}
			}
		}

		return $webserver_template;
	}

	function getPartOfDomainTemplate($type, $content = "all")
	{ // Should only be used in certain use cases - like if SSL is enabled, but the template is missing an SSL section due to old configuration settings for the domain / advanced panel options
		$templateToReturn = "";

		if ($type == "domain") {
			$webserver_template_filename = "$this->ehcpdir/apachetemplate"; # this file may be an apache template actually, or an nginx template, code will be fixed later.. 
			$globalWebServerTemplate = $this->getGlobalDomainTemplate();

			// Load up the template possibilities for when no SSL section was discovered in the domains current template (meaning it's probably using a custom template or an adjusted custom templated based on advanced panel settings)
			if (!empty($globalWebServerTemplate)) {
				$templateToReturn = $globalWebServerTemplate;
			} else {
				$templateToReturn = file_get_contents($webserver_template_filename);
			}
		} else {
			$webserver_template_filename = "$this->ehcpdir/apache_subdomain_template"; # this file may be an apache template actually, or an nginx template, code will be fixed later.. 
			$globalWebServerTemplate = $this->getGlobalSubDomainTemplate();

			// Load up the template possibilities for when no SSL section was discovered in the domains current template (meaning it's probably using a custom template or an adjusted custom templated based on advanced panel settings)
			if (!empty($globalWebServerTemplate)) {
				$templateToReturn = $globalWebServerTemplate;
			} else {
				$templateToReturn = file_get_contents($webserver_template_filename);
			}
		}

		switch ($content) {
			case "ssl":
				$sslContents = getContentsAfterLine("# FOR SSL CONFIG", $templateToReturn);
				if ($sslContents && !empty($sslContents)) {
					$templateToReturn = $sslContents;
				} else {
					$templateToReturn = file_get_contents($webserver_template_filename);
					$sslContents = getContentsAfterLine("# FOR SSL CONFIG", $templateToReturn);
					if ($sslContents && !empty($sslContents)) {
						$templateToReturn = $sslContents;
					} else {
						$templateToReturn = "";
					}
				}
				break;
			case "nonssl":
				$nonSSL = stripContentsAfterLine("# FOR SSL CONFIG", $templateToReturn);
				if ($nonSSL && !empty($nonSSL)) {
					$templateToReturn = $nonSSL;
				} else {
					$templateToReturn = file_get_contents($webserver_template_filename);
					$nonSSL = stripContentsAfterLine("# FOR SSL CONFIG", $templateToReturn);
					if ($nonSSL && !empty($nonSSL)) {
						$templateToReturn = $nonSSL;
					} else {
						$templateToReturn = "";
					}
				}
				break;

		}

		return $templateToReturn;
	}

	function getClientEmailFromPanelUsername($panelusername)
	{
		$user = $this->getPanelUserInfo('', $panelusername);
		if ($user) {
			if ($this->hasValueOrZero($user["email"])) {
				return $user["email"];
			}
		}

		if ($this->hasValueOrZero($this->conf['adminemail'])) {
			return $this->conf['adminemail'];
		}

		return $this->emailfrom;
	}

	function getAdminEmailAddress()
	{
		if ($this->hasValueOrZero($this->conf['adminemail'])) {
			return $this->conf['adminemail'];
		}

		return $this->emailfrom;
	}

	function getAndUseLetsEncryptCert($domains, $emailAddr)
	{
		// Domains is an associative array made up of "domainnames" with an array of domains / subdomain strings and "domainpath" containing the physical path string where the shared document root is.
		$commandStr = "";
		$endCommandStr = "";
		$firstDomainInList = "";
		$wrapperCmd = "";
		$wrapperEndCmd = "";
		$letsEncStr = "/usr/local/bin/certbot certonly --quiet --webroot --agree-tos --email " . $emailAddr;

		// Loop through each domain in the list and append commands to the Let's Encrypt string
		$i = 0;
		foreach ($domains["domainnames"] as $domain) {
			$letsEncStr .= " -d " . $domain;
			if ($i == 0) {
				// Set first domain
				$firstDomainInList = $domain;

				// Create lets encrypt path, symlink the current server wide key as a fallback, and then run the commands
				passthru2("mkdir -p /etc/letsencrypt/live/" . $domain, true, true);
				passthru2("ln -s /etc/ssl/private/server.key /etc/letsencrypt/live/" . $domain . "/privkey.pem", true, true);
				passthru2("ln -s /etc/ssl/certs/server.crt /etc/letsencrypt/live/" . $domain . "/fullchain.pem", true, true);
			}

			$commandStr .= "if [ -e /etc/letsencrypt/live/" . $domain . " ]; then" . "\n";
			$commandStr .= "mv /etc/letsencrypt/live/" . $domain . " /etc/letsencrypt/live/" . $domain . "_before_certbot" . "\n";
			$commandStr .= "fi" . "\n";
			$endCommandStr .= "if [ -e /etc/letsencrypt/live/" . $domain . " ]; then" . "\n";
			$endCommandStr .= "rm -rf /etc/letsencrypt/live/" . $domain . "_before_certbot" . "\n";
			$endCommandStr .= "else" . "\n";
			$endCommandStr .= "if [ -e /etc/letsencrypt/live/" . $domain . "_before_certbot ]; then" . "\n";
			$endCommandStr .= "mv /etc/letsencrypt/live/" . $domain . "_before_certbot /etc/letsencrypt/live/" . $domain . "\n";
			$endCommandStr .= "fi" . "\n";
			$endCommandStr .= "fi" . "\n";

			$i++;
		}
		$letsEncStr .= " -w " . $domains["domainpath"] . " --cert-name " . $firstDomainInList . " >> /var/log/ehcp.log" . "\n";

		// Move htaccess which may prevent lets encrypt from installing... move it back when the command is run
		if (file_exists($domains["domainpath"] . "/.htaccess")) {
			$commandStr .= "mv " . $domains["domainpath"] . "/.htaccess" . " " . $domains["domainpath"] . "/.htaccess2" . "\n";
			$endCommandStr .= "mv " . $domains["domainpath"] . "/.htaccess2" . " " . $domains["domainpath"] . "/.htaccess" . "\n";
		}

		// Commands to set proper permissions on the domain directory
		$commandStr .= "chown -Rf " . $this->wwwuser . ":" . $this->wwwgroup . " " . $domains["domainpath"] . "\n";
		$commandStr .= "chmod 775 -R " . $domains["domainpath"] . "\n";

		if ($firstDomainInList != "") {
			$wrapperCmd = "if [ ! -e /etc/letsencrypt/archive/" . $firstDomainInList . " ]; then" . "\n";
			$wrapperEndCmd = "fi" . "\n";
		}

		// The final command
		$finalCommand = "#!/bin/bash" . "\n" . $wrapperCmd . $commandStr . $letsEncStr . $endCommandStr . "\n" . $wrapperEndCmd;


		// Write all commands to a file
		$stream = fopen("/var/www/new/ehcp/scripts/lets_enc.conf", "a+");
		fwrite($stream, $finalCommand);
		fclose($stream);

		return true;
	}

	function updateHostsFile()
	{
		# update hosts file, so that user on server desktop can reach the website.
		$this->requireCommandLine(__FUNCTION__);

		$this->echoln("Updating hosts file...\n");

		$count = 0;

		$ip = $this->miscconfig['localip'];
		if (!$ip)
			$ip = $this->getLocalIP();
		if (trim($ip) == '')
			return True;

		$doms = $this->getDomains("");
		#print_r($doms);
		$line = "\n" . $ip;
		foreach ($doms as $domain) {
			// Don't do this for domains configured as SLAVE DNS domains
			if (empty($domain["dnsmaster"])) {
				# Limit entries per line to avoid problems due to the line being too long
				# 255 Character Limit Per Line
				if ($count == 2) {
					$line .= '\n' . $ip;
					$count = 0;
				}
				$line .= " www." . $domain['domainname'] . " " . $domain['domainname'] . " mail." . $domain['domainname'] . " ehcp." . $domain['domainname'];
				$count++;
			}
		}

		// Don't forget to add subdomains to the hosts file too!
		$subdoms = $this->getSubDomains("", "ORDER BY id ASC, domainname ASC");
		foreach ($subdoms as $sub) {
			// Get the domain entry too
			if (!isset($domEntry) || !is_array($domEntry) || $domEntry['domainname'] != $sub['domainname']) {
				foreach ($doms as $domain) {
					if ($domain['domainname'] == $sub['domainname']) {
						$domEntry = $domain;
						break;
					}
				}
			}

			// Don't do this for domains configured as SLAVE DNS domains
			if (empty($domEntry["dnsmaster"])) {

				# Limit entries per line to avoid problems due to the line being too long
				# 255 Character Limit Per Line
				if ($count == 2) {
					$line .= '\n' . $ip;
					$count = 0;
				}
				$line .= " www." . $sub['subdomain'] . '.' . $sub['domainname'] . " " . $sub['subdomain'] . '.' . $sub['domainname'];
				$count++;
			}
		}

		# Causes issues because localhost is already defined in its own line
		// $line.=" localhost";
		echo "updating hosts file: ip: ($ip)  line: ($line)\n ";
		passthru2("bash /var/www/new/ehcp/updateHostsFile.sh \"$line\"");
		# No longer needed
		//replaceOrAddLineInFile("$ip ",$line,"/etc/hosts");
		echo "update complete\n";

		return True;
	}

	function initializeLogs($dir)
	{

		passthru2("mkdir -p " . $dir);
		passthru2("mkdir -p " . $dir . "/logs");
		$this->write_file_if_not_exists("$dir/logs/access_log", ""); // these are defined in apachetemplate file, bunlarin log_rotate olayi yapilmali.
		$this->write_file_if_not_exists("$dir/logs/error_log", "");

		#passthru2("chown $this->ftpowner -Rf $dir");
		# this caused problem especially for file upload scripts,

		passthru2("chown root:root -Rf $dir/logs"); # bu olmayinca, biri logs dizinini silince, apache hata verip cikiyor.. # suanda access ver error loglar silinemiyor kullanici tarafindan...ancak sunucudan ssh ile silinebilir...!
	}

	function initializePhpTmpDir($subdir)
	{
		passthru2("mkdir -p $subdir/phptmpdir");
		passthru2("chown $this->ftpowner -Rf $subdir/phptmpdir"); # **** Buradaki problem şu: phptmpdir içinde yeni oluşturulan dosyalar -rw------- 1 www-data www-data şeklinde oluşturuluyor. burası da sahipliğini vsftpd yapınca, artık apache bunu silemez oluyor. burayı -rw-rw--- şeklinde yapmak lazım. Problem: http://ehcp.net/?q=node/1351#comment-2831 ; Bunu umask ile çözdüm sanırım.
		passthru2("chmod a+w -Rf $subdir/phptmpdir");
	}

	function initializeDir($dir)
	{
		passthru2("mkdir -p " . $dir);
		passthru2("chown $this->ftpowner -Rf " . $dir);
	}

	function write_file_if_not_exists($file, $content)
	{
		if (!file_exists($file)) {
			writeoutput($file, $content, 'w', false);
		}
	}

	function initialize_subdomain_files($dom, $domainname)
	{
		if ($domainname <> '') {
			if ($dom['domainname'] <> $domainname)
				return; # do only requested domains. 
		}

		$subdir = $dom['homedir'];

		$this->initializeLogs($subdir);
		$this->initializePhpTmpDir($subdir);
		$this->initializeDir($subdir);

	}

	function resyncCourierSSL()
	{
		$this->requireCommandLine(__FUNCTION__);
		echo "Restarting Courier IMAP and POP3 SSL services!\n";
		manageService("courier-imap-ssl", "restart");
		manageService("courier-pop-ssl", "restart");

		return true;
	}

	function resyncPostfixSSL()
	{
		$this->requireCommandLine(__FUNCTION__);
		echo "Restarting Postfix services!\n";
		manageService("postfix", "restart");

		return true;
	}

	function resyncVSFTPDSSL()
	{
		$this->requireCommandLine(__FUNCTION__);
		echo "Restarting VSFTPD service!\n";
		manageService("vsftpd", "restart");

		return true;
	}

	function handleVSFTPDSSLCert()
	{
		$this->requireCommandLine(__FUNCTION__);

		// Echo statements for debug and showing in the log
		echo "Updating VSFTDP SSL certificate!\n";

		// Load latest config
		$this->loadConfigWithDaemon();

		// Variables
		$vsftpdCertOrigPath = "/etc/ssl/certs/vsftpd_original.pem";
		$vsftpdCertPath = "/etc/ssl/certs/vsftpd.pem";
		$vsftpdConfPath = "/etc/vsftpd.conf";

		if ($this->hasValueOrZero($this->miscconfig['sslvsftpdcertpath'])) {
			$sslVSFTPDPath = $this->miscconfig['sslvsftpdcertpath'];
			if (file_exists($sslVSFTPDPath) && isextension($sslVSFTPDPath, 'pem')) {
				echo "Using certificate from $sslVSFTPDPath for SSL over VSFTPD!\n";

				if (!file_exists($vsftpdCertOrigPath)) {
					if (file_exists($vsftpdCertPath)) {
						echo "Saving a copy of the default VSFTPD certificate to " . $vsftpdCertOrigPath . "\n";
						rename($vsftpdCertPath, $vsftpdCertOrigPath);
					}
				} else {
					if (file_exists($vsftpdCertPath)) {
						// Make a copy
						$copyPath = $vsftpdCertPath . "_" . date('Y_m_d_H_i_s');
						echo "Saving a copy of the current VSFTPD certificate to " . $copyPath . "\n";
						rename($vsftpdCertPath, $copyPath);
					}
				}

				// Create a symlink which will point to the user's certpath... Let's Encrypt certificates will update, so I can see this working nicely. 
				passthru2("ln -sf " . $sslVSFTPDPath . " " . $vsftpdCertPath, true, true);

				// Adjust VSFTPD configuration
				addifnotexists("ssl_enable=YES", $vsftpdConfPath);
				addifnotexists("ssl_tlsv1=YES", $vsftpdConfPath);
				addifnotexists("ssl_sslv2=NO", $vsftpdConfPath);
				addifnotexists("ssl_sslv3=NO", $vsftpdConfPath);
				addifnotexists("require_ssl_reuse=NO", $vsftpdConfPath);
				addifnotexists("ssl_ciphers=HIGH", $vsftpdConfPath);
				addifnotexists("force_local_logins_ssl=NO", $vsftpdConfPath);
				addifnotexists("force_local_data_ssl=NO", $vsftpdConfPath);

				// Restart VSFTPD service
				$this->resyncVSFTPDSSL();
			} else {
				echo "Proposed certificate path of \"" . $sslVSFTPDPath . "\" does NOT exist!\n";
			}
		} else {
			echo "Restoring default SSL certificate for VSFTPD!\n";

			// Restore default cert if it exists
			if (file_exists($vsftpdCertOrigPath)) {
				if (file_exists($vsftpdCertPath)) {
					// Make a copy
					$copyPath = $vsftpdCertPath . "_" . date('Y_m_d_H_i_s');
					rename($vsftpdCertPath, $copyPath);
				}
				rename($vsftpdCertOrigPath, $vsftpdCertPath);
				$this->resyncVSFTPDSSL();
			} else {
				// Disable SSL option in VSFTPD config file
				removeifexists("ssl_enable=YES", $vsftpdConfPath);
				removeifexists("ssl_tlsv1=YES", $vsftpdConfPath);
				removeifexists("ssl_sslv2=NO", $vsftpdConfPath);
				removeifexists("ssl_sslv3=NO", $vsftpdConfPath);
				removeifexists("require_ssl_reuse=NO", $vsftpdConfPath);
				removeifexists("ssl_ciphers=HIGH", $vsftpdConfPath);
				removeifexists("force_local_logins_ssl=NO", $vsftpdConfPath);
				removeifexists("force_local_data_ssl=NO", $vsftpdConfPath);

				if (file_exists($vsftpdCertPath)) {
					unlink($vsftpdCertPath);
				}
				$this->resyncVSFTPDSSL();
			}
		}

		return true;
	}

	function handleCourierSSLCert()
	{
		$this->requireCommandLine(__FUNCTION__);

		// Echo statements for debug and showing in the log
		echo "Updating Courier IMAP and POP3 SSL certificate!\n";

		// Load latest config
		$this->loadConfigWithDaemon();

		// Variables
		$imapdOrigPath = "/etc/courier/imapd_original.pem";
		$pop3dOrigPath = "/etc/courier/pop3d_original.pem";
		$imapdPath = "/etc/courier/imapd.pem";
		$pop3dPath = "/etc/courier/pop3d.pem";
		$pop3dSSLConfigPath = "/etc/courier/pop3d-ssl";
		$imapdSSLConfigPath = "/etc/courier/imapd-ssl";

		if ($this->hasValueOrZero($this->miscconfig['sslcouriercertpath'])) {
			$sslCourierPath = $this->miscconfig['sslcouriercertpath'];
			if (file_exists($sslCourierPath) && isextension($sslCourierPath, 'pem')) {
				echo "Using certificate from $sslCourierPath for POP3-SSL and IMAP-SSL!\n";

				if (!file_exists($imapdOrigPath)) {
					echo "Saving a copy of the default IMAP-SSL certificate to " . $imapdOrigPath . "\n";
					rename($imapdPath, $imapdOrigPath);
				} else {
					// Make a copy
					$copyPath = $imapdPath . "_" . date('Y_m_d_H_i_s');
					echo "Saving a copy of the current IMAP-SSL certificate to " . $copyPath . "\n";
					rename($imapdPath, $copyPath);
				}

				if (!file_exists($pop3dOrigPath)) {
					echo "Saving a copy of the default POP3-SSL certificate to " . $pop3dOrigPath . "\n";
					rename($pop3dPath, $pop3dOrigPath);
				} else {
					// Make a copy
					$copyPath = $pop3dPath . "_" . date('Y_m_d_H_i_s');
					echo "Saving a copy of the current POP3-SSL certificate to " . $copyPath . "\n";
					rename($pop3dPath, $copyPath);
				}

				// Create a symlink which will point to the user's certpath... Let's Encrypt certificates will update, so I can see this working nicely. 
				passthru2("ln -sf " . $sslCourierPath . " " . $imapdPath, true, true);
				passthru2("ln -sf " . $sslCourierPath . " " . $pop3dPath, true, true);

				// Edit the config files to make sure our certs are being used
				replacelineinfile("TLS_CERTFILE", "TLS_CERTFILE=" . $pop3dPath, $pop3dSSLConfigPath, true);
				replacelineinfile("TLS_CERTFILE", "TLS_CERTFILE=" . $imapdPath, $imapdSSLConfigPath, true);

				$this->resyncCourierSSL();
			} else {
				echo "Proposed certificate path of \"" . $sslCourierPath . "\" does NOT exist!\n";
			}
		} else {
			echo "Restoring default SSL certificate for POP3-SSL and IMAP-SSL!\n";

			// Restore default cert if it exists
			if (file_exists($imapdOrigPath)) {
				if (file_exists($imapdPath)) {
					// Make a copy
					$copyPath = $imapdPath . "_" . date('Y_m_d_H_i_s');
					rename($imapdPath, $copyPath);
				}
				rename($imapdOrigPath, $imapdPath);
				manageService("courier-imap-ssl", "restart");
			}

			// Restore default cert if it exists
			if (file_exists($pop3dOrigPath)) {
				if (file_exists($pop3dPath)) {
					$copyPath = $pop3dPath . "_" . date('Y_m_d_H_i_s');
					rename($pop3dPath, $copyPath);
				}
				rename($pop3dOrigPath, $pop3dPath);
				manageService("courier-pop-ssl", "restart");
			}
		}

		return true;
	}

	function handlePostfixSSLCert()
	{
		$this->requireCommandLine(__FUNCTION__);

		// Echo statements for debug and showing in the log
		echo "Updating Postfix SSL certificate!\n";

		// Variables
		$postFixConfigLocation = '/etc/postfix/main.cf';
		$origCertPath = '/etc/postfix/smtpd.cert';
		$origKeyPath = '/etc/postfix/smtpd.key';

		// Load latest config
		$this->loadConfigWithDaemon();

		if ($this->hasValueOrZero($this->miscconfig['postfixsslcertpath'])) {
			$postfixSSLPath = $this->miscconfig['postfixsslcertpath'];
			if (file_exists($postfixSSLPath) && isextension($postfixSSLPath, 'pem') && file_exists($postFixConfigLocation)) {
				echo "Using certificate from $postfixSSLPath for Postfix!\n";

				// Edit the config files to make sure our certs are being used
				replacelineinfile("smtpd_tls_cert_file", "smtpd_tls_cert_file = " . $postfixSSLPath, $postFixConfigLocation, true);
				replacelineinfile("smtpd_tls_key_file", 'smtpd_tls_key_file = $smtpd_tls_cert_file', $postFixConfigLocation, true);

				$this->resyncPostfixSSL();
			} else {
				echo "Proposed certificate path of \"" . $postfixSSLPath . "\" does NOT exist!\n";
			}
		} else {
			echo "Restoring default SSL certificate for Postfix!\n";
			if (file_exists($postFixConfigLocation) && file_exists($origCertPath) && file_exists($origKeyPath)) {
				replacelineinfile("smtpd_tls_cert_file", "smtpd_tls_cert_file = " . $origCertPath, $postFixConfigLocation, true);
				replacelineinfile("smtpd_tls_key_file", "smtpd_tls_key_file = " . $origKeyPath, $postFixConfigLocation, true);
			} else {
				echo "Unable to restore default SSL certificate settings for Postfix due to " . $origCertPath . ", " . $postFixConfigLocation . ", or " . $origKeyPath . " missing!\n";
			}
		}

		return true;
	}

	function syncGlobalPanelURLS()
	{
		$this->requireCommandLine(__FUNCTION__);

		// Echo statements for debug and showing in the log
		echo "Running syncGlobalPanelURLS!\n";

		// Load latest config
		$this->loadConfigWithDaemon();

		$write = false;
		if ($this->hasValueOrZero($this->miscconfig['globalpanelurls'])) {

			echo "Global URLs are " . $this->miscconfig['globalpanelurls'] . "\n";

			$currentGlobalURLs = $this->miscconfig['globalpanelurls'];
			if (stripos($currentGlobalURLs, ",") !== FALSE) {
				$entries = explode(",", $currentGlobalURLs);
			} else {
				$entries = array($currentGlobalURLs);
			}

			foreach ($entries as $entry) {
				$entry = removeInvalidChars($entry, "domainname");
				if ($this->hasValueOrZero($entry)) {
					$processedEntries[] = $entry;
				}
			}

			echo "Processed and validated global panel URL entries are: " . print_r($processedEntries, true) . "\n";

			if (isset($processedEntries) && is_array($processedEntries) && count($processedEntries) > 0) {
				$firstDomain = $processedEntries[0];

				$fullDomainList = implode(" ", $processedEntries);

				if ($this->miscconfig['webservermode'] == "ssl" || $this->miscconfig['webservermode'] == "sslonly") {

					// Get ready to set it up to use Let's Encrypt
					echo "Server has SSL enabled... using Let's Encrypt for the global panel URL entries " . $fullDomainList . "\n";

					if (!file_exists("/etc/letsencrypt/live/" . $firstDomain . "/fullchain.pem")) {
						echo "Let's Encrypt certificates do NOT exist for the global panel URL entries " . $fullDomainList . "... requesting certificates now...\n";
						$this->getAndUseLetsEncryptCert(array('domainnames' => $processedEntries, 'domainpath' => $this->ehcpdir), $this->getAdminEmailAddress());
					} else {
						echo "Let's Encrypt certificates already exist for the global panel URL entries " . $fullDomainList . "\n";
					}

					if (file_exists("/etc/letsencrypt/live/" . $firstDomain . "/fullchain.pem")) {
						$dom['ssl_cert_path'] = "/etc/letsencrypt/live/" . $firstDomain . "/fullchain.pem";
						$dom['ssl_cert_key_path'] = "/etc/letsencrypt/live/" . $firstDomain . "/privkey.pem";
						$dom['ssl_cert_chain_setting_with_path'] = '';
					} else {
						$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
						$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
						$dom['ssl_cert_chain_setting_with_path'] = '';
					}
				}



				if ($this->hasValueOrZero($fullDomainList)) {
					$dom['domainname'] = $fullDomainList;
					$dom['ehcpdir'] = $this->ehcpdir;

					if ($this->miscconfig['webservertype'] == 'apache2') {
						$dom['domainname'] = $firstDomain;

						// Set the aliases by removing the first domain from the array and then expanding it
						array_shift($processedEntries);
						$dom['domainname_alias'] = implode(" ", $processedEntries);
					}

					// Wrap brackets around the array keys
					$keys = array_keys($dom);
					$replaceArray = arrayop($keys, "strop");

					$templateContents = file_get_contents($this->ehcpdir . "/apachetemplate_ehcp_panel");
					$finalContent = str_replace($replaceArray, $dom, $templateContents);
					if ($this->hasValueOrZero($finalContent)) {
						$write = true;
						file_put_contents($this->ehcpdir . "/apachehcp_globalpanelurls.conf", $finalContent);
					}
				}
			}
		}

		// If there was nothing to write, we want to clear the file
		if ($write === false) {
			file_put_contents($this->ehcpdir . "/apachehcp_globalpanelurls.conf", "");
		}

		return true;
	}

	function syncSubdomains($file = '', $domainname)
	{
		$this->requireCommandLine(__FUNCTION__);
		$this->echoln("Synchronizing subdomains...\n");
		if ($file == '')
			$file = "apachehcp_subdomains.conf";
		$arr = $this->query("select * from " . $this->conf['subdomainstable']['tablename']);
		$webserverip = $this->getWebServer();
		$success = True;
		$webservertype = $this->miscconfig['webservertype'];
		$templatefield = $webservertype . 'template';
		$customSubdomainsWritten = false;

		$arr2 = array();
		$ips = array();
		$webserver_template_filename = "$this->ehcpdir/apache_subdomain_template";
		$globalSubdomainTemplate = $this->getGlobalSubDomainTemplate();

		if ($arr){
			foreach ($arr as $dom) { // setup necessry dirs/files if doesnt exist..
				$subdir = $dom['homedir'];
				print "\nProcessing subdir: $subdir \n";
				$this->initialize_subdomain_files($dom, $domainname);

				$dom['customsubdomainhttp'] = '';
				$dom['webserverip'] = $webserverip;

				# modified at 1.4.2012
				if ($dom['webserverips'] <> '') {
					list($i) = explode(',', $dom['webserverips']);
					if (validateIpAddress($i)) {
						echo "\nThis subdomain has custom webserverips,adjusting:" . $dom['subdomain'] . "." . $dom['domainname'] . ":" . $dom['webserverips'];
						$dom['webserverip'] = $i; # if entered in db exclusively.  # diger ip ler ne olacak ? sanirim multiserver fonksiyonlarinda halledilecek... 
						switch ($this->miscconfig['webservertype']) {
							case 'nginx':
								$webserver_template_filename = "$this->ehcpdir/etc/nginx/apache_subdomain_template.nginx";
								break;
							# other servers, if multi-ip supported, it seems no change needed for nginx
						}
						if (!in_array($i, $ips))
							$ips[] = $i;
						if (!in_array($webserverip, $ips))
							$ips[] = $webserverip; # add default ip too.
					}
				}

				// Get custom ssl certs for domain if any
				$rootDomainHomeDir = substr($dom['homedir'], 0, stripos($dom['homedir'], $dom['domainname'])) . $dom['domainname'];
				$fullSubdomainStr = $dom['subdomain'] . "." . $dom['domainname'];

				$sslInfo = $this->getSSLSettingForDomain($dom['domainname']);
				if (!empty($sslInfo["cert"]) && $sslInfo["wildcard"]) {
					echo "\nUsing wildcard custom SSL certificate for subdomain " . $fullSubdomainStr . ".\n";
					$certPath = $rootDomainHomeDir . "/phptmpdir/server.crt";
					$certKeyPath = $rootDomainHomeDir . "/phptmpdir/server.key";
					$certChainPath = $rootDomainHomeDir . "/phptmpdir/chain.crt";
					$certMixedPathNginx = $rootDomainHomeDir . "/phptmpdir/mixed.crt";

					if (file_exists($certPath)) {
						$dom['ssl_cert_path'] = $certPath;
					} else {
						// Use default server one
						$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
					}
					if (file_exists($certKeyPath)) {
						$dom['ssl_cert_key_path'] = $certKeyPath;
					} else {
						// Use default server one
						$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
					}
					if (file_exists($certChainPath)) {
						$dom['ssl_cert_chain_setting_with_path'] = "SSLCertificateChainFile " . $certChainPath;
					} else {
						$dom['ssl_cert_chain_setting_with_path'] = '';
					}

					// For nginx
					if ($this->miscconfig['webservertype'] == "nginx" && file_exists($certMixedPathNginx) && file_exists($certKeyPath)) {
						$dom['ssl_cert_path'] = $certMixedPathNginx;
					}
				} else if ($sslInfo["letsenc"] && empty($sslInfo["cert"])) {
					echo "\nUsing Let's Encrypt SSL certificate for subdomain " . $fullSubdomainStr . ".\n";
					$subdomainPath = $rootDomainHomeDir . "/httpdocs/subdomains/" . $dom['subdomain'];
					$encDomains["domainnames"] = array($fullSubdomainStr);
					$encDomains["domainpath"] = $subdomainPath;
					$this->getAndUseLetsEncryptCert($encDomains, $this->getClientEmailFromPanelUsername($dom['panelusername']));
					if (file_exists("/etc/letsencrypt/live/" . $fullSubdomainStr . "/fullchain.pem")) {
						$dom['ssl_cert_path'] = "/etc/letsencrypt/live/" . $fullSubdomainStr . "/fullchain.pem";
						$dom['ssl_cert_key_path'] = "/etc/letsencrypt/live/" . $fullSubdomainStr . "/privkey.pem";
						$dom['ssl_cert_chain_setting_with_path'] = '';
					} else {
						$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
						$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
						$dom['ssl_cert_chain_setting_with_path'] = '';
					}
				} else {
					echo "\nUsing server default SSL certificate for subdomain " . $fullSubdomainStr . ".\n";
					$dom['ssl_cert_path'] = "/etc/ssl/certs/server.crt";
					$dom['ssl_cert_key_path'] = "/etc/ssl/private/server.key";
					$dom['ssl_cert_chain_setting_with_path'] = '';
				}

				if (!array_key_exists("wildcarddomain", $dom)) {
					$dom['wildcarddomain'] = ""; // Replace wildcard domain from redirect if it's used...
				}

				if (!array_key_exists('domainname_redirect', $dom)) {
					$dom['domainname_redirect'] = ""; // Needs this key added to it since it may be populated with an actual value in processing logic
				}

				$arr2[] = $dom;
				# arr2 used because, customsubdomainhttp is used or similar...

				if (!file_exists($subdir . "/ehcpinfo.html")) {
					passthru2("cp -f ehcpinfo.html " . $subdir . "/ehcpinfo.html");
				}

			}
		}

		# you may see daemon mode output at logfile, typically tail -f /var/log/ehcp.log from command line
		echo __FUNCTION__ . ": syncing subdomains:";
		print_r($arr2);

		if (isset($arr2) && is_array($arr2) && count($arr2) > 0) {
			// Handle custom subdomain templates
			$alanlar = array_keys($arr2[0]); // gets array keys, from first(0th) array element of two-dimensional $arr2 array. https://stackoverflow.com/questions/2399286/str-replace-with-associative-array (to understand this better... we take the keys from the first entry and then we replace each {key} with the value for that key in domain / subdomain array --- pretty clever
			// following code, replaces fields from template to values here in $arr2 two-dim array. each $arr2 element written to output file according to template file.
			$replacealanlar = arrayop($alanlar, "strop");
			$fileOut = "";
			foreach ($arr2 as $ar1) {
				$webserver_template = $ar1[$templatefield]; # get domain specific (custom) template
				if (!empty($webserver_template)) {
					$webserver_template = $this->adjustDomainTemplateDependingOnSSLSettings($webserver_template, $ar1, "subdomain");
					$webserver_template = str_replace(array('{ehcpdir}', '{localip}'), array($this->ehcpdir, $this->miscconfig['localip']), $webserver_template);
					$webserver_config = str_replace($replacealanlar, $ar1, $webserver_template);
					$fileOut .= $this->adjustWebTemplateConfIfNeededForLineBreaks($webserver_config); // Directives need to be separated by newlines
				} else {
					$arr3[] = $ar1;
				}
			}

			if (!empty($fileOut)) {
				$customSubdomainsWritten = true;
				$success = writeoutput2($file, $fileOut, 'w', false);
				$this->echoln("Custom subdomain webserver templates were written successfully (" . __FUNCTION__ . ")\n");
			}
		}

		if (isset($arr3) && is_array($arr3) && count($arr3) > 0) {

			if (!empty($globalSubdomainTemplate)) {
				$alanlar = array_keys($arr3[0]); // gets array keys, from first(0th) array element of two-dimensional $arr3 array. https://stackoverflow.com/questions/2399286/str-replace-with-associative-array (to understand this better... we take the keys from the first entry and then we replace each {key} with the value for that key in domain / subdomain array --- pretty clever
				// following code, replaces fields from template to values here in $arr3 two-dim array. each $arr3 element written to output file according to template file.
				$replacealanlar = arrayop($alanlar, "strop");
				$fileOut = "";
				foreach ($arr3 as $ar1) {
					$webserver_template = $this->adjustDomainTemplateDependingOnSSLSettings($globalSubdomainTemplate, $ar1, "subdomain");
					$webserver_template = str_replace(array('{ehcpdir}', '{localip}'), array($this->ehcpdir, $this->miscconfig['localip']), $webserver_template);
					$webserver_config = str_replace($replacealanlar, $ar1, $webserver_template);
					$fileOut .= $this->adjustWebTemplateConfIfNeededForLineBreaks($webserver_config);
				}
				$success = writeoutput2($file, $fileOut, (!$customSubdomainsWritten ? 'w' : 'a+'), false);
				if ($success) {
					$this->echoln("SUBDOMAINS-Domain list exported from global subdomain template (" . __FUNCTION__ . ")\n");
				}
			} else {
				if ($this->putArrayToFile($arr3, $file, $webserver_template_filename, true, (!$customSubdomainsWritten ? 'w' : 'a+'))) {
					$this->echoln("SUBDOMAINS-Domain list exported (" . __FUNCTION__ . ")\n");
				} else
					$success = false;
			}
		}
		
		if(!is_array($arr) || count($arr) == 0){
			// Clear the contents to avoid webserver issues
			$success = writeoutput2($file, "", 'w', false);
		}

		return $success;
	}

	function adjustWebTemplateConfIfNeededForLineBreaks($webserver_config)
	{
		return (!startsWith($webserver_config, PHP_EOL) ? PHP_EOL : "") . $webserver_config . (!endsWith($webserver_config, PHP_EOL) ? PHP_EOL : "");
	}

	function run_lets_encrypt_commands()
	{
		$this->requireCommandLine(__FUNCTION__);
		$this->echoln("Running Let's Encrypt commands...\n");
		$success = true;
		if (file_exists("/var/www/new/ehcp/scripts/lets_enc.conf")) {
			echo "\nRunning Let's Encrypt script generated by EHCP...\n";
			passthru2("bash /var/www/new/ehcp/scripts/lets_enc.conf", true, true);
			echo "\nRemoving Let's Encrypt script generated by EHCP...\n";
			$contentsOfScript = file_get_contents("/var/www/new/ehcp/scripts/lets_enc.conf");
			if ($contentsOfScript) {
				$this->echoln2("\nContents of the bash Let's Encrypt script:\n" . $contentsOfScript . "\n");
			}
			passthru2("rm /var/www/new/ehcp/scripts/lets_enc.conf", true, true);

			// Reload web server again to pick up on changed let's encrypt keys
			sleep(1);
			$success = $success && $this->configtest_reload_webserver();
		}
		return $success;
	}

	function configtest_reload_webserver()
	{
		$this->requireCommandLine(__FUNCTION__);

		$this->echoln("Checking web server configuration files...\n");

		$domainContents = file_get_contents($this->ehcpInstallPath . "apachehcp.conf");
		$subdomainContents = file_get_contents($this->ehcpInstallPath . "apachehcp_subdomains.conf");
		$pDomainContents = file_get_contents($this->ehcpInstallPath . "apachehcp_passivedomains.conf");

		$webserver = trim($this->miscconfig['webservertype']);
		if ($webserver == '' or !in_array($webserver, array('apache2', 'nginx'))) {
			$this->echoln("webservertype is not defined in settings/config or not recognised. now setting is:($webserver). setting to default of apache2.");
			$webserver = 'apache2';
		}

		if ($webserver == 'apache2') {
			manageService("nginx", "stop");
			$this->echoln("checking $webserver syntax: ");
			system("apache2ctl configtest", $ret); # burda apache config test ediyor... custom http de abuk subuk seyler girenler nedeniyle... bu olmazsa, apache baslayamiyor ve ehcp arayuzu de, ayni apache dan calistigindan, ulasilamaz hale geliyor..
			# aslinda ehcp arayuzu farkli/statik bir apache e yonlendirilebilir.. farkli porttan calisan..
			# ayni isi dns icin de yapmak lazim... suanda biri custom dns olarak hatali bisey girse, dns cortlar... onu da ilerde duzeltcem.
			# Exit Code of 8 Means Syntax Error - Anything Else Should Not Impact the Reload of the Apache2 Config - ret changed from 0 to 8
			# To see the exit codes, look at the script source like I did 
			if ($ret == 8) {
				echo "\n $webserver configuration issue detected! There is an error in the config.\n";
				echo "\n Configuration for domains is set to the following:\n";
				echo "\n " . $domainContents . "\n";
				echo "\n Configuration for subdomains is set to the following:\n";
				echo "\n " . $subdomainContents . "\n";
				echo "\n Configuration for passive domains is set to the following:\n";
				echo "\n " . $pDomainContents . "\n";
				$this->infotoadminemail("Hi EHCP Admin,<br><br>There is an error in your $webserver config.<br><br>Domain Contents:<br><pre>" . $domainContents . "</pre>Subdomain Contents:<br><pre>" . $subdomainContents . "</pre><br>Passive Domain Contents:<br><pre>" . $pDomainContents . "</pre>", "Error in $webserver Config", false);
				$success = false;
			} else {
				echo "\n $webserver configuration is valid and tested successfully!";
				manageService($this->php_fpm_name, "reload");
				manageService("apache2", "reload");
				$success = true;
			}
		} elseif ($webserver == 'nginx') {
			manageService("apache2", "stop");

			$this->echoln("checking $webserver syntax: ");
			$out = shell_exec('service nginx 2>&1 | grep -o "configtest"');
			if (!strstr($out, 'configtest')) {
				$this->echoln("Your $webserver does not support configtest.  Assuming the configuration is ok...\n($out)\n");
				print_r($out);
				manageService($this->php_fpm_name, "reload");
				manageService("nginx", "reload");
				return true;
			}

			system(getServiceActionStr("nginx", "configtest"), $ret);
			$out = shell_exec('nginx -t 2>&1');

			if ($ret <> 0) {
				echo "\n $webserver configuration issue detected! There is an error in the config.\n";
				echo "\n Configuration for domains is set to the following:\n";
				echo "\n " . $domainContents . "\n";
				echo "\n Configuration for subdomains is set to the following:\n";
				echo "\n " . $subdomainContents . "\n";
				echo "\n Configuration for passive domains is set to the following:\n";
				echo "\n " . $pDomainContents . "\n";
				$this->infotoadminemail("Hi EHCP Admin,<br><br>There is an error in your $webserver config.<br><br>" . $out . "<br><br>Domain Contents:<br><pre>" . $domainContents . "</pre>Subdomain Contents:<br><pre>" . $subdomainContents . "</pre><br>Passive Domain Contents:<br><pre>" . $pDomainContents . "</pre>", "Error in $webserver Config", false);
				$success = false;
			} else {
				echo "\n $webserver configuration is valid and tested successfully!";
				$success = true;
			}
			manageService($this->php_fpm_name, "reload");
			manageService("nginx", "reload");
		}

		$this->webserver_test_and_fallback();

		return $success;
	}

	function webserver_test_and_fallback()
	{
		# to be coded later.
		# test if any webserver running, if not, perform a series of fallback operations, such as switch back to apache..

	}

	function putArrayToFile($arr, $filename, $template, $additionalTemplateLogic = false, $mode = 'w')
	{
		$res = writeoutput2($filename, $this->putArrayToStr($arr, $template, $additionalTemplateLogic), $mode);
		if ($res)
			return $res; #$this->echoln("Putting some content to file: $filename (putArrayToFile)\n");
		else
			$this->echoln("Failed-Putting some content to file: $filename (" . __FUNCTION__ . ")\n");
		return $res;
	}


	function putArrayToStr($arr, $template, $additionalTemplateLogic = false)
	{
		# you should not change this function, as it is being used by other methods too, as I remember; this is a general purpose function
		// bir template e gore dosyaya yazar. array template de yerine koyar. template de array elemanlari {domain} seklinde olmalidir.

		if (!$arr)
			return "";

		$alanlar = array_keys($arr[0]); // gets array keys, from first(0th) array element of two-dimensional $arr array.

		// following code, replaces fields from template to values here in $arr two-dim array. each $arr element written to output file accourding to template file.
		$replacealanlar = arrayop($alanlar, "strop");
		$templatefile = file_get_contents($template);

		foreach ($arr as $ar1) { // template e gore apacehe dosyasn olustur
			$temp = $templatefile;

			if ($additionalTemplateLogic === true) {
				$temp = $this->adjustDomainTemplateDependingOnSSLSettings($temp, $ar1, "subdomain");
			}

			$temp = str_replace($replacealanlar, $ar1, $temp);
			$out .= $temp;
		}

		return $out;
	}

	function putArrayToStrDns($arr)
	{
		# we should better code this, we should use existing function putArrayToStr, or reduce code...
		if (!$arr)
			return "";

		$alanlar = array_keys($arr[0]); // gets array keys, from first(0th) array element of two-dimensional $arr array.

		// following code, replaces fields from template to values here in $arr two-dim array. each $arr element written to output file accourding to template file.
		$replacealanlar = arrayop($alanlar, "strop");


		foreach ($arr as $ar1) { // template e gore apacehe dosyasn olustur
			// Check which template to really use for DNS
			if ($ar1["dnsmaster"] <> '') {
				// Use slave template
				$template = $this->dnsnamedconftemplate_slave;
			} elseif ($ar1["dnsmaster"] == '') {
				// Use master template
				$template = $this->dnsnamedconftemplate;
			}

			$templatefile = file_get_contents($template);
			$temp = $templatefile;
			$temp = str_replace($replacealanlar, $ar1, $temp);
			$out .= $temp;
		}

		return $out;
	}

	function runop2($op, $action, $info, $info2 = '', $info3 = '')
	{
		// for operations that needs more than one argument. such as domain add/delete, especially for daemon mode.
		global $commandline;
		$this->requireCommandLine(__FUNCTION__);

		echo "(runop2) op:$op, action:$action, info:$info, info2:$info2 \n";

		switch ($op) { # info3 is usually server
			case 'syncdomains':
				return $this->syncDomains('', $info);
				break;
			case 'deletefilefromsystem':
				return $this->deleteFileFromSystem($action, $info);
				break;
			case 'runsystemcommand':
				return $this->runSystemCommand($info, $info2);
				break;
			case 'daemon_backup_domain':
				return $this->daemon_backup_domain($info);
				break;
			case 'daemondomain':
				return $this->daemondomain($action, $info, $info2, $info3);
				break;
			case 'daemonftp':
				return $this->daemonftp($action, $info, $info2, $info3);
				break;
			case 'daemonbackup':
				return $this->daemonBackup($action, $info, $info2);
				break;
			case 'daemonrestore':
				return $this->daemonRestore($action, $info, $info2);
				break;
			case 'installscript':
				return $this->installScript($action, $info, $info2);
				break;
			case 'installscript_force':
				return $this->installScriptForceEdition($action, $info, $info2);
				break;
			case 'update_ez_install':
				return $this->updateEZScriptSQL($action, $info, $info2);
				break;
			case 'downloadallscripts':
				return $this->downloadAllScripts();
				break;
			case 'updatediskquota':
				return $this->updateDiskQuota($info);
				break;
			case 'service':
				return $this->service($info, $info2);
				break;
			case 'fixApacheConfigSsl':
				return $this->fixApacheConfigSsl($info);
				break;
			case 'fixApacheConfigSslOnly':
				return $this->fixApacheConfigSslOnly($info);
				break;
			case 'process_ssl_certs':
				return $this->handleCustomSSLCertsForDomains();
				break;
			case 'manage_dkim':
				return $this->handleDKIMConfig($action, $info);
				break;
			default:
				return $this->errorText("Internal EHCP Error - Undefined Operation: " . $op . " <br> This feature may not be complete.");
		} // switch

	}

	function fixMailConfiguration()
	{
		# this re-runs function mailconfiguration,configurepamsmtp, configureauthmysql, that is, mail related functions in install_lib.php
		# purpose: in case mail/ehcp configuration is corrupted, or ehcp mysql db pass changed, update system configuration accordingly
		# this function was for mail configuration at start, became whole ehcp configuration later.. included vsftpd, net2ftp... and so on..
		$this->requireCommandLine(__FUNCTION__);

		include_once("install_lib.php");
		$this->write_file_if_not_exists('/etc/mailname', 'mail.myserver.com'); # on some systems, this is deleted somehow.
		if (!file_exists('/etc/postfix/main.cf'))
			passthru2("cp " . $this->ehcpdir . "/etc/postfix/main.cf.sample /etc/postfix/main.cf"); # on some systems, this is deleted somehow.

		$params = array('ehcppass' => $this->dbpass);
		$params2 = array_merge($params, array('ehcpinstalldir' => $this->conf['ehcpdir']));

		global $ip, $ehcpmysqlpass, $ehcpinstalldir;
		include('config.php');

		$ip = $this->miscconfig['dnsip'];
		$ehcpmysqlpass = $dbpass;
		$ehcpinstalldir = $this->conf['ehcpdir'];

		$this->adjust_webmail_dirs();
		mailconfiguration($params);
		passthru2('newaliases');

		net2ftp_configuration($params2);

		$this->syncDns();
		$this->syncDomains();

		passthru2("chmod a+w " . $this->ehcpdir . "/webmail/data");
		passthru2("chmod a+w " . $this->ehcpdir . "/net2ftp/temp");
		manageService("vsftpd", "restart");
		manageService("postfix", "restart");

		return True;
	}

	function downloadAllscripts()
	{
		$this->requireCommandLine(__FUNCTION__);
		echoln("downloading all scripts- not completed yet");
		return true;
	}

	function extract_file($filename, $extractto)
	{
		$ext = get_filename_extension($filename);
		$mydir = getcwd();
		chdir($extractto);
		$ret = True;

		if ($ext == 'gz') {
			if (strpos($filename, '.tar.gz') === False)
				passthru2("gunzip $filename");
			else
				passthru2("tar -zvxf $filename");
		} elseif ($ext == 'tgz') {
			passthru2("tar -zxvf $filename");
		} elseif ($ext == 'bz2') {
			if (strpos($filename, '.tar.bz2') === False)
				passthru2("bunzip2 $filename");
			else
				passthru2("tar -jvxf $filename");
		} elseif ($ext == 'zip') {
			passthru2("unzip $filename");
		} elseif ($ext == 'rar') {
			passthru2("unrar x $filename");
		} else {
			print "Unsupported extension/Desteklenmeyen dosya uzantisi, extract yapılmadı... : $ext ";
			$ret = False;
		}

		chdir($mydir);
		return $ret;
	}

	function download_url($url, $downloadto, $filename = '')
	{
		if ($filename == '')
			$filename = get_filename_from_url($url);

		passthru2("wget -N -O $downloadto/$filename -t 3 $url", true);
		print "got filename using wget : $filename";
	}


	function download_file_from_url_extract($url, $downloadto, $extractto, $filename = '')
	{
		print "getting and installing file from url: $url ";
		if ($filename == '')
			$filename = get_filename_from_url($url);

		$this->download_url($url, $downloadto, $filename);

		# dosyayi gecici bir dizine kopyala, sonra icinde ac, sonra icinde bircok dosya varsa direk ....	
		passthru2("mkdir -vp $extractto");
		if ($downloadto <> $extractto)
			passthru2("cp -vf $downloadto/$filename $extractto/");

		print "current dir: " . getcwd() . "... will extract files... \n\n";

		if (!$this->extract_file($filename, $extractto))
			return False;

		if ($downloadto <> $extractto)
			passthru2("rm -vf $filename"); # remove file in tmp dir. 	
		return True;
	}

	function insert_custom_setting_direct($params)
	{
		$q = "insert into customsettings (domainname,name,`value`,value2) values('{$params['domainname']}','{$params['name']}','{$params['value']}','{$params['value2']}')";
		return $this->executeQuery($q);
	}

	function getAndInstallFile($bilgi, $domainname, $directory)
	{
		$this->requireCommandLine(__FUNCTION__);
		$url = $bilgi['fileinfo']; # burada guvenlik kontrol edilmeli, yoksa baskasi baskasinin domainine biseyler kurar...

		#adjust script install dir
		$scriptdirtocopy = trim($bilgi['scriptdirtocopy']);
		$scriptdirtocopy .= "/.";


		$domainhome = $this->getField($this->conf['domainstable']['tablename'], "homedir", "domainname='$domainname'") . "/httpdocs";
		$directory = trim($directory);
		if ($directory == '')
			$targetdirectory = $domainhome;
		else
			$targetdirectory = "$domainhome/$directory/";

		/* canceled this check because unable to install into subdomain
			if($directory<>'' and file_exists("$targetdirectory")){
				print "Target directory already exists, so, cancelling script installation.. : $targetdirectory ";
				return False;
			}
		*/

		$mydir = getcwd();

		if (strpos($bilgi['scriptname'], "ehcp itself") !== false) {
			$filename = "ehcp_latest.tgz";
		} else
			$filename = '';


		$tmpdir = $this->conf['ehcpdir'] . "/scriptinstall/gecici_temp";
		$installdir = $this->conf['ehcpdir'] . "/scriptinstall";
		passthru2("mkdir $installdir");
		passthru2("rm -rf $tmpdir");

		if (!$this->download_file_from_url_extract($url, $installdir, $tmpdir, $filename = ''))
			return False;


		# copy files to target dir

		passthru2("mkdir -p \"$targetdirectory\"");
		#passthru2("cp -Rvf ".$this->conf['ehcpdir']."/scriptinstall/$tmpdir/$scriptdirtocopy/* $targetdirectory");
		# ilginc bir sekilde bu yildizli kopyalama calismadi... yildizi, php icinden gormuyor, no such file or dir diyor... garip.. bu nedenle noktalihale geldi.
		passthru2("rm -rvf \"$targetdirectory/index.html\""); # remove any index.html file already there... this may cause some loss...
		passthru2("cp -Rvf $tmpdir/$scriptdirtocopy $targetdirectory");
		passthru2("rm -rf $tmpdir");

		if (!(strpos($bilgi['scriptname'], "ehcp itself") === false)) { # if this is ehcp itself... # download new version of ehcp, overwrite settings&config files. should work directly if you have latest ehcp.
			$settingsfiles = array('config.php', 'apachetemplate', 'dnszonetemplate', 'apachetemplate_passivedomains', 'apache_subdomain_template', 'dnsnamedconftemplate', 'dnsnamedconftemplate_slave');
			foreach ($settingsfiles as $tocopy)
				passthru2("cp -Rvf " . $this->conf['ehcpdir'] . "/$tocopy $targetdirectory");
		}

		print "\nscript dir $scriptdirtocopy copied to: $targetdirectory";


		# burda kopyalama sonrasi islemler..
		# these are commands that are executed after copy... such as chmod a+w somfile.. specific to that script...

		passthru2("chown -Rf " . $this->wwwuser . " $targetdirectory");

		# go to inside that dir...
		chdir($targetdirectory);

		/* path for custom permissions:
		 * scripts table: relative path, because, actual install path is not known
		 * customsettings: instalpath/path in scripts table
		 * syncdomains: set permissions of "domainhome/path in customsettings" that is, "domainhome/installpath/relativepath" that is "/var/www/vhosts/ftpuser/domain.com/httpdocs/installdir(maybeempty)/wp-admin
		 * */

		echo "\n\ncommands to execute after script copy: (current dir: " . getcwd() . ") \n";
		print_r($bilgi['commandsaftercopy']); foreach (explode("\n", $bilgi['commandsaftercopy']) as $com) {
			$com = trim($com);
			$com = str_replace(array('{domainname}', '{domainhome}', '{targetdirectory}'), array($domainname, $domainhome, $targetdirectory), $com);
			$com = trim($com);
			if ($com <> '')
				passthru2($com);
		}

		chdir($mydir); # return back to original dir
		return True;
	}

	function updateEZScriptSQL($action, $info, $info2)
	{
		$this->requireCommandLine(__FUNCTION__);

		$mysql_script = $this->ehcpDownloadPath . "/easy_install.sql";
		if (!file_exists($this->ehcpDownloadPath)) {
			mkdir($this->ehcpDownloadPath, 0775);
		}

		$downloadScript = 'wget -N -O ' . $this->ehcpDownloadPath . '/easy_install.sql "https://ehcpforce.ezpz.cc/files/easy_install.sql"';
		passthru2($downloadScript, true, true);

		$mysql_host = $this->dbhost;
		$mysql_user = $this->dbusername;
		$mysql_userpass = $this->dbpass;
		$mysql_dbname = $this->dbname;

		$mysqlCommandToRun = 'mysql -h "' . $mysql_host . '" -u "' . $mysql_user . '" -p"' . $mysql_userpass . '" "' . $mysql_dbname . '" -f < ' . $mysql_script;
		passthru2($mysqlCommandToRun, true, true);

		return true;
	}

	function installScriptForceEdition($scriptname, $domainname, $installInfoNeeded)
	{
		$this->requireCommandLine(__FUNCTION__);
		print "installing script....: $scriptname ";
		$q = "select * from scripts where scriptname='$scriptname'";
		$bilgi = $this->query($q);
		$bilgi = $bilgi[0];
		print "\nkurulacak script bilgileri: query: $q \n";
		print_r($bilgi);

		$settingsForIns = explode($this->ehcpForceSplitString, $installInfoNeeded);

		// $installInfoNeeded = $directory . $this->ehcpForceSplitString . $dbname . $this->ehcpForceSplitString . $dbusername . $this->ehcpForceSplitString . $dbuserpass . $this->ehcpForceSplitString . $myserver['host'] . $this->ehcpForceSplitString . $title . $this->ehcpForceSplitString . $admin_email;

		$directory = $settingsForIns[0];
		$dbName = $settingsForIns[1];
		$dbUserName = $settingsForIns[2];
		$dbPass = $settingsForIns[3];
		$mysql_host = $settingsForIns[4];
		$titleForScript = $settingsForIns[5];
		$adminEmailForScript = $settingsForIns[6];

		$domainhome = $this->getField($this->conf['domainstable']['tablename'], "homedir", "domainname='$domainname'") . "/httpdocs";
		$directory = trim($directory);
		if ($directory == '')
			$targetdirectory = $domainhome;
		else
			$targetdirectory = "$domainhome/$directory/";

		$installedTheScriptFiles = $this->getAndInstallFile($bilgi, $domainname, $directory);
		$ranInstallerUsingSteps = $this->runCurlToCompleteInstall(strtolower($scriptname), $domainname, $directory, $targetdirectory, $dbName, $dbUserName, $dbPass, $mysql_host, $titleForScript, $adminEmailForScript);

		return $installedTheScriptFiles & $ranInstallerUsingSteps;
	}

	function runCurlToCompleteInstall($scriptname, $domainname, $directory, $targetdirectory, $dbName, $dbUserName, $dbPass, $mysql_host, $titleForScript, $adminEmail)
	{
		$this->requireCommandLine(__FUNCTION__);

		$modeToSend = $this->miscconfig['webservermode'];
		if ($this->miscconfig['webservermode'] == "ssl") {
			// Figure out if we should use https or http depending on the domain setting
			$domainInfo = $this->getDomainInfo($domainname);
			if ($domainInfo["ssl_redirect_https"]) {
				$modeToSend = "sslonly";
			}
		}

		$insScript = "bash /var/www/new/ehcp/scripts/curl_installer/curlInstallScript.sh '" . $scriptname . "' '" . $domainname . "' '" . $directory . "' '" . $targetdirectory . "' '" . $dbName . "' '" . $dbUserName . "' '" . $dbPass . "' '" . $mysql_host . "' \"" . $titleForScript . "\" '" . $adminEmail . "' '" . $this->wwwuser . "' '" . $this->wwwgroup . "' '" . $modeToSend . "' > /dev/null 2>&1 &";
		passthru2($insScript, true, true);
		return true;
	}

	function installScript($scriptname, $domainname, $directory)
	{
		$this->requireCommandLine(__FUNCTION__);
		print "installing script....: $scriptname ";
		$q = "select * from scripts where scriptname='$scriptname'";
		$bilgi = $this->query($q);
		$bilgi = $bilgi[0];
		print "\nkurulacak script bilgileri: query: $q \n";
		print_r($bilgi);

		if ($bilgi['filetype'] == 'remoteurlconfig') { # fileinfo contains, remote url config file of format url=http.....
			$config = file_get_contents($bilgi['fileinfo']);
			print "configfile:" . $config;
			$lines = explode("\n", $config);
			print_r($lines);
			print " this part is not completed.. use directurl";
		} elseif ($bilgi['filetype'] == 'directurl') {
			return $this->getAndInstallFile($bilgi, $domainname, $directory);
		} else {
			print "\n\nUnknown file type:" . $bilgi['filetype'] . "(File:" . __FILE__ . "Line:" . __LINE__ . ") \n\n";
			return False;
		}

		return true;
	}

	function listTable($baslik1, $conf_tabloadi, $filtre = "", $skipLinkFilesAndFields = false)
	{

		$tablo = $this->conf[$conf_tabloadi];
		$this->output .= "$baslik1<br>";

		if(!$skipLinkFilesAndFields){
			$linkimages = $tablo['linkimages'];
			$linkfiles = $tablo['linkfiles'];
			$linkfield = $tablo['linkfield'];
		}else{
			$linkimages = array();
			$linkfiles = array();
			$linkfield = '';
		}
		$sirala = $tablo['orderby'];

		$this->output .= $this->tablolistele3_5_4($tablo['tablename'], $baslik, $tablo['listfields'], $filtre, $sirala, $linkimages, $linkfiles, $linkfield, $listrowstart, $listrowcount) . '<br>';
		return true;
	}


	// extra functions from old dbutil

	function ilerigeriekle($kayitsayisi, $baslangic, $satirsayisi, $querystring)
	{
		if (!isset($baslangic))
			$baslangic = 0;
		if (!isset($satirsayisi))
			$satirsayisi = 10;

		$ilerimiktar = $baslangic + $satirsayisi;
		$self = $_SERVER['PHP_SELF'];
		$querystring = $_SERVER['QUERY_STRING'];
		$querystring = str_replace(array("&baslangic=$baslangic&satirsayisi=$satirsayisi", "&&"), array("", "&"), $querystring);
		$self2 = $self . "?" . $querystring;

		if ($satirsayisi > 0) {
			$sondanoncesi = $kayitsayisi - $satirsayisi;
			$querystring = str_replace("baslangic=$baslangic&satirsayisi=$satirsayisi", "", $querystring);

			// asagidaki tabloya bu baslangic tekrar gitmesin diye. asagida zaten ekleniyor.
			//if($querystring<>"")$querystring.="&"; // bialtsatrda ?den sonra yazmak i�n. ileri geride kullanlyor.

			if ($ilerimiktar < $kayitsayisi) {
				$ileri = "<a href=$self2&baslangic=$ilerimiktar&satirsayisi=$satirsayisi>&gt</a>";
				$son = "<a href=$self2&baslangic=$sondanoncesi&satirsayisi=$satirsayisi>&gt&gt</a>";
			} else {
				$ileri = "&gt";
				$son = "&gt&gt";
			}
			;

			if ($baslangic > 0) {
				$geri = "<a href=$self2&baslangic=" . ($baslangic - $satirsayisi) . "&satirsayisi=$satirsayisi>&lt</a>";
				$bas = "<a href=$self2&baslangic=0&satirsayisi=$satirsayisi>&lt&lt</a>";
			} else {
				$geri = "&lt";
				$bas = "&lt&lt";
			}
			;

			# cok sayida (100 binlerce) kayit olunca, birsürü sayfa gösteriyor. bunu engellemek için, burada değişik bir mantık lazım. 
			if ($kayitsayisi > 20000)
				$cokkayit = True;

			if ($kayitsayisi > $satirsayisi) {
				if ($cokkayit) {
					$result2 .= "Cok sayida kayit var, bu nedenle aralardan sayfalar ornekleniyor.<br>$bas &nbsp  $geri $ileri &nbsp $son<br>";
					$sayfalar = "Pages:";
					$bolunecek = $satirsayisi * $kayitsayisi / 20000; # nekadar cok kayit varsa, okadar fazla bol, aradan ornekleme yap... 
					for ($sayfa = 0; $sayfa < ($kayitsayisi / $bolunecek); $sayfa++)
						$sayfalar .= "<a href=$self2&baslangic=" . ($sayfa * $bolunecek) . "&satirsayisi=$satirsayisi>" . ($sayfa + 1) . " </a> &nbsp;";
				} else {
					$result2 .= round(($baslangic / $satirsayisi) + 1) . ".page:  (" . ($baslangic + 1) . "-" . ($baslangic + $satirsayisi) . ". records) (in each page $satirsayisi record)<br> $bas &nbsp  $geri $ileri &nbsp $son <br>";
					$sayfalar = "Pages:";
					for ($sayfa = 0; $sayfa < ($kayitsayisi / $satirsayisi); $sayfa++)
						$sayfalar .= "<a href=$self2&baslangic=" . ($sayfa * $satirsayisi) . "&satirsayisi=$satirsayisi>" . ($sayfa + 1) . " </a> &nbsp;";
				}
			}

		}
		;

		if ($kayitsayisi > 0)
			$reccount = $this->sayinmylang("recordcount") . $kayitsayisi;
		$result2 .= $sayfalar . $arama . "<br>$reccount<br>";
		return $result2;
	}


	function tablolistele3_5_4($tablo, $baslik, $alan, $filtre, $sirala, $linkyazi, $linkdosya, $linkalan, $baslangic1, $satirsayisi1, $aramayap = true, $altbilgi = true, $baslikgoster = true)
	{
		# this lists table rows in a paged view
		//
		// ehcp icin modifiye edildi, gelistirildi.
		// tablolistele3_4 den fark bilgilerin belli gruplarda listelenmesi. ileri geri tular v.b.
		// bir farki da echo yapmaz
		// 3_5_2 den fark, mssqlden de okuyabilmesi olacak.. yeni yazyorum. adodb ye gectim.

		global $aranan, $arananalan, $app, $baslangic, $satirsayisi, $listall;

		if ($baslik === null || !is_array($baslik)) {
			$baslik = array();
		}

		// Get table info
		$table = $this->conf[$tablo . "table"];
		if (!$this->hasValueOrZero($table)) {
			$table = $this->conf[$tablo];
		}

		$this->getVariable(array("arananalan", "aranan", "hepsi", 'baslangic', 'satirsayisi', 'listall'));

		$color1 = "#FFE8B2";
		$color2 = "#E2E2E2";

		if (!isset($baslangic) || empty($baslangic)) {
			$baslangic = 0;
		}
		
		if (!isset($baslangic1))
			$baslangic1 = $baslangic;
			
		if (!isset($satirsayisi1) or $satirsayisi1 == 0)
			$satirsayisi1 = $satirsayisi;

		if (!isset($baslangic1) || empty($baslangic1) || !is_numeric($baslangic1))
			$baslangic1 = 0;
			
		if (!isset($satirsayisi1) || $satirsayisi1 == 0 || empty($satirsayisi1) || !is_numeric($satirsayisi1))
			$satirsayisi1 = 10;

		$result2 = '';
		$alansayisi = count($alan);
		if (is_array($linkyazi)) {
			$alansayisi2 = count($linkyazi);
		} else {
			$alansayisi2 = 0;
		}
		$satirno = 0;
		$ilkfiltre = $filtre;

		#$this->output.="<hr>(ks: $kayitsayisi, iks: $ilkkayitsayisi, filtre: $filtre, ilkfiltre: $ilkfiltre)<hr>";

		$ilkkayitsayisi = $this->recordcount($tablo, $ilkfiltre); //$res[0];


		// listelemedeki arama fonksiyonlary:


		if ($listall <> "1") {
			$sess_arananalan = $_SESSION['sess_arananalan'];
			$sess_aranan = $_SESSION['sess_aranan'];
		} else {
			$_SESSION['sess_arananalan'] = '';
			$_SESSION['sess_aranan'] = '';
		}

		if ($arananalan <> '' or $sess_arananalan <> '')
			$this->output .= " Searchfield:($arananalan), searchvalue:($aranan) , sess_searchfield:($sess_arananalan), sess_searchvalue($sess_aranan)..<br>";

		if ($aranan <> '') {
			$_SESSION['sess_arananalan'] = $arananalan;
			$_SESSION['sess_aranan'] = $aranan;
			$baslangic1 = 0;

			if ($arananalan == '') {
				$this->output .= "Aranacak Alanı belirtmediniz. Bir alan seciniz.";
			} else {
				$filtre = andle($filtre, "$arananalan like '%$aranan%'");
				//$this->output.="Filtre: $filtre <br>";
			}
		} elseif ($sess_arananalan <> '') { // bu session olayy, arama yapynca sayfalamanyn �aly?asy i�in
			$filtre = andle($filtre, "$sess_arananalan like '%$sess_aranan%'");
		} else {
			$_SESSION['sess_arananalan'] = '';
			$_SESSION['sess_aranan'] = '';
		}
		//------------------ arama bitti -------------------
		$kayitsayisi = $this->recordcount($tablo, $filtre); //$res[0];
		$topkayitsayisi = $this->recordcount($tablo, '');

		if ($kayitsayisi == 0) {
			$result2 .= $this->sayinmylang("norecordfound");
			//return $result2;
		}

		$selectalan = array();
		$ignoredColumns = array('is_using_custom_template');
		foreach ($alan as $al) {
			if (is_array($al)){
				if(!in_array($al[0], $ignoredColumns)){
					$selectalan[] = $al[0];
				}else{
					if(!in_array('nginxtemplate', $selectalan)){
						$selectalan[] = 'nginxtemplate';
					}
					if(!in_array('apache2template', $selectalan)){
						$selectalan[] = 'apache2template';
					}
				}
			} else {
				if(!in_array($al, $ignoredColumns)){
					$selectalan[] = $al;
				}else{
					if(!in_array('nginxtemplate', $selectalan)){
						$selectalan[] = 'nginxtemplate';
					}
					if(!in_array('apache2template', $selectalan)){
						$selectalan[] = 'apache2template';
					}
				}
			}
		}

		$baslikalan = $selectalan;
		if (!in_array($linkalan, $selectalan) && !empty($linkalan))
			array_push($selectalan, $linkalan); //linkalan yoksa, ekle
		//$query=buildquery3("select ".selectstring($selectalan)." from $tablo",$filtre,$sirala,$baslangic,$satirsayisi1);
		$query = buildquery2("select " . selectstring($selectalan) . " from $tablo", $filtre, $sirala);
		$this->queries[] = $query;
		$res = $this->conn->selectlimit($query, $satirsayisi1, $baslangic1);


		#$this->output.="res:".print_r2($res);
		$tr = "<tr class='list'>";
		$td = "<td class='list'>";


		if ($res) {

			$result2 .= "\n<table id='table$tablo' class='list'>";

			// Get custom th headers
			if(!empty($table) && is_array($table)){
				$customListLabels = $table['listlabels'];
				if ($this->hasValueOrZero($customListLabels) || is_array($customListLabels)) {
					$baslik = $customListLabels;
				}
			}
			
			// Get the number of link fields it should have
			if(!empty($table) && is_array($table) && array_key_exists('linkimages', $table) && is_array($table['linkimages'])){
				
				$numLinks = count($table['linkimages']);
				
				if(!empty($linkyazi) && is_array($linkyazi)){
					$linksReceivedCount = count($linkyazi);
				}else{
					$linksReceivedCount = 0;
				}
				
				if($linksReceivedCount != $numLinks){
					$diff = $numLinks - $linksReceivedCount;
					if($diff > 0){
						array_splice($baslik, (-1 * $diff));
					}
				}
			}

			if ($kayitsayisi > 0 and $baslikgoster)
				$result2 .= tablobaslikyaz($baslikalan, $baslik, $linkyazi);

			while (!$res->EOF) {
				$r = $res->FetchRow();
				#$this->output.=print_r2($r);

				#if(iseven($satirno)){$satirrengi=$color1;} else {$satirrengi=$color2;};$satirno++;
				#$result2.="<tr bgcolor='$satirrengi'>";
				$result2 .= $tr;
				for ($i = 0; $i < $alansayisi; $i++) {
					if (is_array($alan[$i]))
						$al = $alan[$i][0];
					else
						$al = $alan[$i];
						
					$yaz = htmlspecialchars($r[$al]);
					
					if($al == 'is_using_custom_template'){
						if ($this->miscconfig['webservertype'] == "nginx") {
							$al = 'nginxtemplate';
						}else{
							$al = 'apache2template';
						}
						
						if(!empty($r[$al])){
							$yaz = "Yes";
						}else{
							$yaz = "No";
						}
					}
					
					if ($yaz == '') {
						$result2 .= "$td&nbsp</td>";
					} else {
						if (is_array($alan[$i])) {
							$yaz1 = $yaz;
							if ($alan[$i]['linktext'] <> '')
								$yaz1 = $alan[$i]['linktext'];

							if ($alan[$i][1] == "sayi")
								$yaz = "<p align=right>" . number_format($yaz, 2) . "</p>";
							if ($alan[$i][1] == "link_newwindow")
								$yaz = "<a target=_blank href='$yaz'>$yaz1</a>";
							if ($alan[$i][1] == "link")
								$yaz = "<a href='$yaz'>$yaz1</a>";
							if ($alan[$i][1] == "image")
								$yaz = "<img src='$yaz'>";
						}
						$result2 .= "$td$yaz</td>";
					}
					;
					//if($al==$linkalan){$link=$r[$al];};
				}
				;
				$link = $r[$linkalan];

				for ($i = 0; $i < $alansayisi2; $i++) {
					$ly = $linkyazi[$i];
					$ld = $linkdosya[$i];
					$char = "&";
					if (strpos($ld, "?") === false)
						$char = "?";
					if (strpos($ld, "href=") === false)
						$ld = "href='$ld";

					$indexToStart = count($baslik) - count($linkyazi) + $i;

					$result2 .= "$td<a $ld$char$linkalan=$link'><img src='$ly' border='0' title='" . (isset($baslik) && is_array($baslik) && array_key_exists($indexToStart, $baslik) && !empty($baslik[$indexToStart]) ? $baslik[$indexToStart] : "") . "'></a></td>";
				}

				$result2 .= "</tr>\n";
			}

			$result2 .= "</table>";
			$ilerimiktar = $baslangic1 + $satirsayisi1;
			$self = $_SERVER['PHP_SELF'];
			$querystring = $_SERVER['QUERY_STRING'];
			$self2 = $self . "?" . $querystring;

			if ($altbilgi)
				$result2 .= $this->ilerigeriekle($kayitsayisi, $baslangic1, $satirsayisi1, $querystring);


			// aramalarn ayarlanmas.

			if ($aramayap and $topkayitsayisi > 5) {
				$arama = "<form method=post>" . $this->sayinmylang('search_') . ": " . buildoption2("arananalan", $alan, $arananalan) . "<input type=text name=aranan value='$aranan'><input type=submit value=" . $this->sayinmylang('search_') . "></form>";
				$result2 .= $arama;
			}

			if (($aranan <> '' or $arananalan <> '' or $_SESSION['sess_arananalan'] <> '' or $filtre <> '') and ($ilkkayitsayisi > $kayitsayisi)) { # sonuclarda arama, filtreleme yapildi ise, filtrelemeyi kaldir..
				$result2 .= "<a href=$self2&listall=1>" . $this->sayinmylang('list_all') . "</a><br>";
			}

		} else {
			$this->error_occured("(tablolistele_3_5_4)", "query:$query");
		}
		;
		// $result2.="<br>tablo bitti.<br>";
		// echo "query:$query";
		$result2 .= $this->conn->ErrorMsg();
		return $result2;
	} //fonksiyon

	function isTrue($param, $str = '', $returnit = False)
	{ # this is a test function to figure out a variable type, true or false ?
		$found = false;
		$this->output .= "<hr>$str - starting checks-(isTrue)<hr>";
		if ($param === true) {
			$ret = "<b>this is exact true</b><hr>";
			$this->output .=
				$found = true;
		}
		if ($param === false) {
			$ret = "<b>this is exact false</b><hr>";
			$found = true;
		}
		if ($param === null) {
			$ret = "<b>this is exact null</b><hr>";
			$found = true;
		}
		if ($param === 0) {
			$ret = "<b>this is exact 0 - zero</b><hr>";
			$found = true;
		}
		if ($param === "") {
			$ret = "<b>this is exact '' - empty</b><hr>";
			$found = true;
		}
		if ($param === array()) {
			$ret = "<b>this is exact empty array</b><hr>";
			$found = true;
		}
		if ($found === false) {
			$ret = "This variable is not true,false,0,null or empty array <br>this seems:"
				. gettype($param) . "<br>"
				. (is_resource($param) ? get_resource_type($param) : "") . "<br>"
			;

		}
		$this->output .= $ret;
		$this->output .= "<br>finished isTrue.<hr>";
		if ($returnit)
			return $ret;
	}


	function sifreHatirlat()
	{ # password reminder
		$tarih = date_tarih();
		global $email, $panelusername, $hash;
		$this->getVariable(array("email", 'panelusername', 'hash'));

		if ($email <> "") {

			#validate email:
			$kayitliemail = $this->getField($this->conf['logintable']['tablename'], 'email', "email='$email'");
			$filt = "email='$email'";

			if ($kayitliemail <> '') {

				if (!$hash) {
					$hash = get_rand_id(10);
					$r = $this->executeQuery("insert into  hash (email,hash)values('$email','$hash')");
					if (!$r)
						return false;

					$msg = "Hi EHCP Force User,<br><br>Someone using the IP address of $this->clientip requested to reset your password.&nbsp; To reset your password, <a href=\"" . $this->ehcpurl . "/?op=sifrehatirlat&email=$email&hash=$hash\">click here</a>.<br><br>" . "If you are attempting to access your server locally, replace the IP address in the above URL with the local IP address of the server.";
					$this->sendEmail($email, 'Password Reset Information', $msg);
					$this->output .= "Password reset information has been sent to your email address.";
					return;
				}


				# get username
				$filt2 = $filt;
				if ($panelusername <> '')
					$filt2 = "$filt and panelusername='$panelusername'";
				$username = $this->getField($this->conf['logintable']['tablename'], $this->conf['logintable']['usernamefield'], $filt2);

				#validate hash
				$filt3 = "$filt and hash='$hash'";
				$sayi = $this->recordcount("hash", $filt3);
				if ($sayi == 0)
					$this->errorTextExit("Received invalid password reset information. Verify the password reset URL that was sent to your email.");



				#reset pass
				$yenisifre = get_rand_id(5);
				$s = $this->executeQuery("update " . $this->conf['logintable']['tablename'] . " set " . $this->conf['logintable']['passwordfield'] . "=md5('$yenisifre') where email='$email'", 'update user pass', 'update user pass');
				if ($s) {
					$msg = "Hi EHCP Force User,<br><br>Your newly reset password is: $yenisifre<br>Your username is: $username";
					$this->sendEmail($email, $this->sitename . ' - Password Reset Successfully', $msg);
					$this->echoln("Your updated account information was sent by email!<br>");
					$this->executeQuery("delete from hash where $filt3"); # delete hash after verify
				}

			} else {
				$this->output .= 'No such email';
			}
		} else {
			$inputparams = array(
				array('email', 'lefttext' => 'Enter your email:'),
				array('panelusername', 'righttext' => 'leave empty if you dont remember'),
				array('op', 'hidden', 'default' => __FUNCTION__)
			);

			$this->output .= inputform5($inputparams);
		}
		return true;
	}

	function getLocalIP()
	{
		global $localip; //only for daemon mode

		// old
		//$ipline=exec("ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");

		// Get first entry only:
		$ipline = exec("ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | head -1");

		if (!isset($ipline) || empty($ipline)) {
			$ipline = "127.0.0.1";
		}
		$localip = $ipline;
		return $ipline;
	}


	function generateMySQLInClause($arrayOfInputs)
	{
		$inClause = "IN ('";
		for ($i = 0; $i < count($arrayOfInputs); $i++) {
			if ($i == 0) {
				$inClause .= $arrayOfInputs[$i];
			} else {
				$inClause .= "','" . $arrayOfInputs[$i];
			}
		}
		$inClause .= "')";

		return $inClause;
	}

	function array_copy($arr)
	{
		$newArray = array();
		foreach ($arr as $key => $value) {
			if (is_array($value))
				$newArray[$key] = $this->array_copy($value);
			else if (is_object($value))
				$newArray[$key] = clone $value;
			else
				$newArray[$key] = $value;
		}
		return $newArray;
	}

	function domainIsCCTLD($domain, $csv = "")
	{
		$domain = strtolower($domain);
		$finalList = array();
		if (empty($csv) || !is_array($csv)) {
			$csv = array_map('str_getcsv', file($this->ehcpInstallPath . '/misc/SLDs.csv'));
		}

		if (empty($this->csvCCTLD)) {
			foreach ($csv as $key => $value) {
				if (is_array($value) && count($value) == 2) {
					$finalList[] = strtolower($value[1]);
				} else if (!is_array($value)) {
					$finalList[] = strtolower($value);
				}
			}
			$this->csvCCTLD = $finalList;
		}

		foreach ($this->csvCCTLD as $val) {
			if (endsWith($domain, $val)) {
				return true;
			}
		}

		return false;
	}

} // end class
?>
