<?php
error_reporting (E_ALL ^ E_NOTICE);
//  second part of install.
//  first part installs mailserver, then, install2 begins, 
//  i separated these installs because php email function does not work if i re-start php after email install... 
// install functions in install_lib.php
$webServerToInstall = "apache2";

if($argc>1){

  # Distro and version are always sent
	
  # Get distro version number
  $temp = trim($argv[1]);
  if(stripos($temp, ".") != FALSE){
    $version = $temp;
  }
  
  # Distro is needed for Ubuntu only features
  $distro = strtolower(trim($argv[2]));
}

for($i=3;$i<=7;$i++){ # accept following arguments in any of position.
	if($argc>$i) {
		print "argc:$argc\n\n";
		switch($argv[$i]) {
			case 'noapt': # for only simulating install, apt-get installs are still loged onto a file
				$noapt="noapt";
				echo "apt-get install disabled due to parameter:noapt \n";
				break;
			case 'unattended': # tries to suppress most user dialogs.. good for a quick testing. (tr: hizlica test etmek icin guzel..)
				$unattended=True;
				break;
			case 'light': # the light install, non-cruical parts are omitted. good for a quick testing. (tr: hizlica test etmek icin guzel..)
				$installmode='light';
				break;
			case 'extra': # the extra install, means more components
				$installmode='extra';
				break;
			case 'debug':
				$debugMode = true;
				break;
			case 'nginx':
				$webServerToInstall = "nginx";
				break;
			default:
				echo __FILE__." dosyasinda bilinmeyen arguman degeri:".$argv[$i];
				break;
		}

	}
}
echo "Some install parameters for file ".__FILE__.": noapt:($noapt), unattended:".($unattended===True?"exact True":"not True")." installmode:($installmode) \n";


include_once('install_lib.php');

$phpConfDir = getPHPConfDir();

// Load preset installation values in install_silently.php if exists:
if(file_exists("install_silently.php")){
	require_once 'install_silently.php';
}

// Load preset admin email if there is one
if(file_exists('admin_email.php')){
	require_once 'admin_email.php';
}

include_once('install2.1.php');

echo "System is running $version\n";  

echo "\nincluded install2.1.php\nhere are variables transfered:\n";
echo 
"
webdizin:$webdizin
ip:$ip
user_name:$user_name
user_email:$user_email
hostname:$hostname
installextrasoftware: $installextrasoftware
";

/*
ehcpmysqlpass:$ehcpmysqlpass
rootpass:$rootpass
newrootpass:$newrootpass
ehcpadminpass:$ehcpadminpass
*/

installsql($webServerToInstall);

install_vsftpd_server();

fail2ban_install();

// Install both webserver packages
install_webserver_common();

if($webServerToInstall == "nginx"){
	install_nginx_webserver();
}else{
	installapacheserver();
}

# scandb();  no more need to scan db since ver. 0.29.15
installfinish($webServerToInstall);

$message='';
exec('ifconfig',$msg);
exec('ps aux ',$msg);
foreach($msg as $m) $message.=$m."\n";
$msg="Your EHCP Force installation has completed successfully. If you have questions or need help, please visit www.ehcpforce.tk";

if($user_email<>'') mail($user_email,'Your EHCP Force installation completed successfully.  Have fun!',$msg,'From: info@ehcpforce.tk');

$realip=getlocalip2();
if(!$app->isPrivateIp($ip)) $realip.="-realip"; # change subject if this is a server with real ip... 
$ip2=trim(file_get_contents("https://ehcpforce.tk/ip.php"));
$message.="\noutside Ip detected:$ip2";
?>
