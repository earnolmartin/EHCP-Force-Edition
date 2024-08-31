#!/usr/bin/env php
<?php
/*


by I.Bahattin Vidinli, 
mail: info@ehcpforce.ezpz.cc

see classapp.php for real application.
*/
 
include_once(dirname(__FILE__)."/../config/adodb/adodb.inc.php"); # adodb database abstraction layer.. hope database abstracted...
include_once(dirname(__FILE__)."/../classapp.php"); # real application class


if(is_array($argv)) {
	if(count($argv)<3) {
		die("Kullanim/Usage: ". __FILE__." parametreler");
	};
}

$app = new Application();
$app->connecttodb();

$sender=$argv[1];
$recipient=str_replace("@autoreply.","@",$argv[2]);

$info=$app->query("select autoreplysubject,autoreplymessage from emailusers where email='$recipient'");
$subject=$info[0]['autoreplysubject'];
$msg=$info[0]['autoreplymessage']."\n\n(ehcp Autoreply message from $recipient at ".date("Y-m-d H:i:s").")"; #.print_r($argv,true).print_r($info,true);

# ** BURAYA ŞU ÖZELLİK YAPILACAK: bir alıcıya örneğin son 12 saatte bir autoreply gitmişse, tekrar tekrar gönderme. mantıklı bir istek. 
# bunun için, gönderen,alıcıların,sontarih alanlarının olduğu bir tabloda tutmak ve ordan karşılaştırmak lazım.

if($subject<>''){# user has autorespond enabled..
	syslog(LOG_NOTICE,"ehcp:".basename(__FILE__).": email sent to $sender, on behalf of $recipient (subject:$subject)");	
	mail($sender,$subject,$msg,"From: $recipient");
} else {
	#syslog(LOG_NOTICE,"ehcp:".basename(__FILE__).": email autoreply is not enabled for $recipient, so, not sending autoreply ");
}

?>
