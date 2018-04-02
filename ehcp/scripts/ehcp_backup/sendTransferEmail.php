<?php

	function isCommandLineInterface(){
		return (php_sapi_name() === 'cli');
	}

	if(isCommandLineInterface()){
		if(isset($argv) && !empty($argv)){
			if(count($argv) > 1){
				if(isset($argv[1])){
					$subj=$argv[1];
				}
				if(isset($argv[2])){
					$mess=$argv[2];
				}
			}
				
				
		}

		if(isset($subj) && !empty($subj) && isset($mess) && !empty($mess)){
			// Get current directory
			$curDir = getcwd();
			
			/* EHCP API Call */
			if(chdir("/var/www/new/ehcp/")){
				require ("classapp.php");
				$app = new Application();
				$app->connectTodb(); # fill config.php with db user/pass for things to work..
				$app->loadConfig();
				$success = $app->infotoadminemail($mess, $subj);
				if($success){
					echo "Email sent to admin!";
				}else{
					echo "Failed to send email to admin!";
				}
			}
			/* END EHCP API Call */
			
			chdir($curDir);
		}else{
			echo "Invalid params.";
		}
	}else{
		echo "Invalid run.";
	}
?>
