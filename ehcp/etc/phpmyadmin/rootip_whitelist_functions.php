<?php
	function isPrivateIP($IP){
		$pieces = explode(".", $IP);
		if(count($pieces) == 4){
			$firstOct = $pieces[0];
			$secOct = $pieces[1];
			$thirdOct = $pieces[2];
			$lastOct = $pieces[3];
			
			if($firstOct == '10'){
				return true;
			}
			
			if($firstOct == '127'){
				return true;
			}	

			if($firstOct == '172' && $secOct >= 16 && $secOct <=32){
				return true;
			}
			
			if($firstOct == '192' && $secOct == '168'){
				return true;
			}
		}
		
		return false;
	}
?>
