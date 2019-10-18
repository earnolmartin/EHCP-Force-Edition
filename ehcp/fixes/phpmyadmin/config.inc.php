<?php
	// DB Settings
	$dbuser='{PHPMYADMINUSER}';
	$dbpass='{PHPMYADMINPASS}';
	$basepath='';
	$dbname='phpmyadmin';
	$dbserver='localhost';
	$dbport='';
	$dbtype='mysql';

	// Set the default server if there is no defined
	if (!isset($cfg['Servers'])) {
		$cfg['Servers'][1]['host'] = 'localhost';
	}

	// Set the default values for $cfg['Servers'] entries
	for ($i=1; (!empty($cfg['Servers'][$i]['host']) || (isset($cfg['Servers'][$i]['connect_type']) && $cfg['Servers'][$i]['connect_type'] == 'socket')); $i++) {
		if (!isset($cfg['Servers'][$i]['auth_type'])) {
			$cfg['Servers'][$i]['auth_type'] = 'cookie';
		}
		if (!isset($cfg['Servers'][$i]['host'])) {
			$cfg['Servers'][$i]['host'] = 'localhost';
		}
		if (!isset($cfg['Servers'][$i]['connect_type'])) {
			$cfg['Servers'][$i]['connect_type'] = 'tcp';
		}
		if (!isset($cfg['Servers'][$i]['compress'])) {
			$cfg['Servers'][$i]['compress'] = false;
		}
		if (!isset($cfg['Servers'][$i]['extension'])) {
			$cfg['Servers'][$i]['extension'] = 'mysql';
		}
	}
	
	// Tweaked settings
	$cfg['UploadDir'] = './upload';
	$cfg['SaveDir'] = '';
	
	$cfg['Servers'][1]['AllowRoot'] = FALSE;
	include_once '/usr/share/phpmyadmin/rootip_whitelist_functions.php';
	include '/usr/share/phpmyadmin/rootip_whitelist.php';
?>
