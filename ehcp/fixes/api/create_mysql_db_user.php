<?php
	// About
	/* 
	   This script can be used to create an empty database with a new mysqluser and password,
	   or it can be used to create a database with a new mysqluser & password and then run a sql file to populate that database.
	   
	   Arguments:
		* script.php $1 $2 $3 $4
		* $1 = name of database to create
		* $2 = mysql username to create
		* $3 = mysql user password
		* $4 = sql file path to populate database with (optional) [Can be full or relative path to /var/www/new/ehcp]
	   
	   
	   By:  earnolmartin@gmail.com 
	   https://ehcpforce.ezpz.cc
	   
	*/

	
		if(count($argv) > 3){
			$dbnameToCreate = $argv[1];
			$mysqlUser = $argv[2];
			$mysqlUserPass = $argv[3];
			if(isset($argv[4])){
				$sqlFileToRunAfter = $argv[4];
			}
			$curDir = getcwd();

			if(chdir("/var/www/new/ehcp/")){
				/* EHCP API Call */
				require ("classapp.php");
				$app = new Application();
				$app->connectTodb(); # fill config.php with db user/pass for things to work..
				$app->loadConfig();
				$mysqlInfo = $app->getMysqlServer('',false,__FUNCTION__);
				if(!empty($mysqlInfo["pass"]) && !empty($dbnameToCreate) && !empty($mysqlUser) && !empty($mysqlUserPass)){
					$filecontent="
						drop database if exists " . $dbnameToCreate . ";
						create database " . $dbnameToCreate . ";
						grant all privileges on " . $dbnameToCreate . ".* to " . $mysqlUser . "@'localhost' identified by '" . $mysqlUserPass . "' with grant option;
						grant all privileges on " . $dbnameToCreate . ".* to " . $mysqlUser . "@'127.0.0.1' identified by '" . $mysqlUserPass . "' with grant option;
						grant all privileges on " . $dbnameToCreate . ".* to " . $mysqlUser . "@'127.0.1.1' identified by '" . $mysqlUserPass . "' with grant option;
					";
					$file = "tempsql.sql";
					$handle = fopen($file, "w+");
					fwrite($handle, $filecontent);
					fclose($handle);

					passthru("mysql -u root --password=" . $mysqlInfo["pass"] . " < $file"); # root pass changes here... if different , disabled
					unlink($file);
					
					if(!empty($sqlFileToRunAfter)){
						if(file_exists($sqlFileToRunAfter)){
							passthru("mysql -u " . $mysqlUser . " --password=" . $mysqlUserPass . " " . $dbnameToCreate . " < " . $sqlFileToRunAfter);
						}
					}
				}
				/* END EHCP API Call */
			}
			chdir($curDir);
		}
        
	
		

?>
