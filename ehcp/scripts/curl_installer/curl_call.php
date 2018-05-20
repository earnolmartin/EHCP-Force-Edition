#!/usr/bin/php
# Runs the OGP web install script
# By OwN-3m-All
<?php

	if(isset($argv) && count($argv) > 1){
		//extract data from the post
		extract($_POST);
		
		$realNumParams = (count($argv) -2);

		// Our fields variable
		$fields_string = "";
	
		//set POST variables
		$url = $argv[1];
		
		for ($i = 2; $i < count($argv); $i++){
			$keyVal = explode("=", $argv[$i]);
			$value=urlencode($keyVal[1]);
			$key=$keyVal[0];
			$fields_string .= $key.'='.$value.'&';
		}
		
		$handle = fopen("/var/www/new/ehcp/scripts/curl_installer/curl_php_log.conf", "a+");
		fwrite($handle, "URL is set to: " . $url . "\n");
		fwrite($handle, "Received this string of data: " . $fields_string . "\n");
		
		
		if(!empty($fields_string) && isset($fields_string)){
			rtrim($fields_string, '&');

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, $realNumParams);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects

			//execute post
			$result = curl_exec($ch);
			
			// Check if any error occurred
			if(curl_errno($ch))
			{
				fwrite($handle, "\nCurl error: " . curl_error($ch) . "\n");
			}
			
			fwrite($handle, "\nResult of $url is: " . $result . "\n\n");

			//close connection
			curl_close($ch);
		}
		
		// Close file
		if(isset($handle)){
			fclose($handle);
		}
	}
	exit();
?>
