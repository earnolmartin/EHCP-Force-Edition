<?php		

	if(isset($argv) && !empty($argv)){
		if(count($argv) > 1){
			if(isset($argv[1])){
				$filename=$argv[1];
			}
		}
		
		
	}
	
	if(isset($filename)){
		
		// Get current directory
		$curDir = getcwd();
		
		/* EHCP API Call */
		if(chdir("/var/www/new/ehcp/")){
			require ("classapp.php");
			$app = new Application();
			$app->connectTodb(); # fill config.php with db user/pass for things to work..
			$this->addDaemonOp("daemonrestore",'',$filename,'','opname:restore');
		}
		/* END EHCP API Call */
		
		chdir($curDir);

	}
	
?>
