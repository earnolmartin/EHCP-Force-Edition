<?php		

	if(isset($argv) && !empty($argv)){
		if(count($argv) > 1){
			if(isset($argv[1])){
				$filename=$argv[1];
			}
		}
		
		
	}
	
	if(isset($filename)){
	
		$backup='-mysql-files-ehcpdb-emailaccounts-emailcontents-gzipbackup'; # what will be backed up
		
		// Code from classapp.php in EHCP
		/*
			if($backupmysql) $backup.='-mysql';
			if($backupfiles) $backup.='-files';
			if($backupehcpfiles) $backup.='-ehcpfiles';
			if($backupehcpdb) $backup.='-ehcpdb';
			if($emailme) $backup.='-emailme';
			if($emailaccounts) $backup.='-emailaccounts';
			if($emailcontents) $backup.='-emailcontents';
			if($gzipbackup) $backup.='-gzipbackup';		
		*/
		
		// Get current directory
		$curDir = getcwd();
		
		/* EHCP API Call */
		if(chdir("/var/www/new/ehcp/")){
			require ("classapp.php");
			$app = new Application();
			$app->connectTodb(); # fill config.php with db user/pass for things to work..
			$app->addDaemonOp("daemonbackup",'',$filename,$backup,'opname:backup');
		}
		/* END EHCP API Call */
		
		chdir($curDir);

	}
	
?>
