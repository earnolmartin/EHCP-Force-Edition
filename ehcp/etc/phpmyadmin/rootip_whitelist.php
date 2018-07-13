<?php

	// IP Addresses that are allowed to login via the root MySQL account through PHPMyAdmin
	$allowedIPs = array("127.0.0.1");
	
	// Counter
	$allow = 0;
	
	// Get remote IP address
	$clientIP = getIPAddress();
	
	// Is the client's IP address a private IP?  
	// If so, allow root login
	if(isPrivateIP($clientIP)){
		$allow++;
	}	

	// Check allowed array to see if client IP can login as root
	foreach ($allowedIPs as &$IP) {
		if($IP == $clientIP){
			$allow++;
		}
	}

	if($allow > 0){
		$cfg['Servers'][1]['AllowRoot'] = TRUE;
	}

?>
