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
		
		if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE )){
			return true;
		}
		
		if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && !filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE )){
			return true;
		}
		
		return false;
	}
	
	function getIPAddress(){
		if(isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])){
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}else if(isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP'])){
			return $_SERVER['HTTP_X_REAL_IP'];
		}else{
			return $_SERVER['REMOTE_ADDR'];
		}
	}
?>
