<?php
/*
EASY HOSTING CONTROL PANEL troubleshoot.php FILE Version 0.29 - www.EHCP.net
by I.Bahattin Vidinli, 
mail&msn: bvidinli@iyibirisi.com
18.2.2008
* 
* 
*/


session_start();

include_once("classapp.php");

$app = new Application();
$app->cerceve="standartcerceve";
$app->requirePassword=false;
$app->initialize();


$testcount=1;

if(!function_exists("print_r2")){
function print_r2($array)
{
if ($array) return '<pre>'.str_replace(array("\n" , " "), array('<br>', '&nbsp;'), print_r($array, true)).'</pre>';
elseif ($array===null) return "(null) ";
elseif ($array=="") return "(bosluk)";
}
}


function showpass($pass){
	if($pass<>'') return "[hidden-somepass]";
	else return "[no password]";
}

function testconnectusing($user,$pass,$db){
	global $app,$testcount;
	echoln("<hr>(testconnectusing) Test $testcount : testing connection to db $db using user $user and pass: ".showpass($pass)."<br>");
    $conn=NewADOConnection("mysql");    
    $conn->connect('localhost',$user,$pass,$db);	
	
    if($conn->ErrorMsg()<>''){
        echolnred("error while connecting to db $db using user $user and pass: ".showpass($pass)." <br>".$conn->ErrorMsg());
        return false;
    }
    //echoln("success while connecting to db $db using user $user and pass: ".showpass($pass)."");        
    $conn->close();
    return true;
}

function testmysqlconnect($host,$user,$pass){
	global $app,$testcount;
	echoln("<hr>(testmysqlconnect) Test $testcount : Testing mysql connection with host: $host, user: $user, pass: ".showpass($pass)." <br>");
    $conn = mysql_connect($host, $user, $pass);
	$ret=$conn;
    mysql_close($conn);	
    return $ret;
}

function echolnred($str){
	global $app;
	echoln('<font color=#FF0000>'.$str.'</font>');
}

function echoln($str){	
	global $app;
	$str.="<br>";
	$app->output.=$str;
	echo $str; // this was $app->echoln ... 
}


function checkdbsettings(){
	global $app,$testcount;
    
	echoln("Testing mysql connectivity: ");
	
    $res1=testconnectusing($app->conf['mysqlrootuser'],$app->conf['mysqlrootpass'],'mysql'); 
    if(!$res1){
        echolnred('Test '.$testcount.' failed. Probably your mysqlroot pass is not correct, defined in mysqlrootpass variable. This will not affect normal behaviour of ehcp except you will not be able to create mysql dbs in ehcp.');
    }
	$testcount++;

    $res2=testconnectusing($app->conf['mysqlrootuser'],$app->conf['mysqlrootpass'],'ehcp'); 
    if(!$res2){
        echolnred('Test '.$testcount.' failed. either your mysqlrootpass is not correct or ehcp db does not exist.  ');
    }
	$testcount++;

    $res3=testconnectusing($app->dbusername,$app->dbpass,'ehcp'); 
    if(!$res3){
        echolnred('<b>Test '.$testcount.' failed. your dbusername and/or dbuserpass is not correct. check your db settings.<br> if you just installed ehcp, learn your/know your mysql root pass, then re-install ehcp..<br><a href=http://www.ehcp.net/?q=node/160>mysql password recovery</a></b>');
    }
	$testcount++;

    $res4=testmysqlconnect('localhost',$app->conf['mysqlrootuser'],$app->conf['mysqlrootpass']); 
    if(!$res4){
        echolnred('Test '.$testcount.' failed. this means either your mysql server is not runnnig on localhost, or your mysqlrootpass is not correct.');
    }
	$testcount++;
	
}


function programsayisi($str){
	$res=trim(executeprog("ps aux | grep '$str' | grep -v grep | wc -l "));
	return $res;
}

function isrunning($str){
	return "is $str running: ".(programsayisi($str)>=1?"Yes":"<font color=#ff0000><b>No</b></font>")."<br>\n";
}

function fileContents($file){
	return "<hr><b>File Contents of ($file):</b><hr>".executeprog("cat $file")."<hr>";
}

function checkapache(){
$ret.="<b>Apache configuration checks: </b>
<br>
Check Apache DocumentRoot setting: <br>\n (This effects the default page of your server. should be only one setting.)<br>\n[<pre>"
.executeprog('grep DocumentRoot /etc/apache2/sites-enabled/000-default ').'</pre>]<br><br>';

$ret.="Check Apache Include: 
<br>
(this effects the functionality of your domains, must be one setting, Include)
<br>["
.executeprog('grep apachehcp /etc/apache2/apache2.conf ').']<br><br>';

return $ret;

}

function checkpostfix(){
$ret.="<b>Check Postfix:</b>"
.fileContents("/etc/postfix/mysql-virtual_domains.cf")
.fileContents("/etc/postfix/mysql-virtual_forwardings.cf")
.fileContents("/etc/postfix/mysql-virtual_mailboxes.cf")
.fileContents("/etc/postfix/mysql-virtual_email2email.cf")
.fileContents("/etc/postfix/mysql-virtual_mailbox_limit_maps.cf")."<hr>"
;
# return $ret;
}

echoln("<b>Troubleshoot Version 0.29.09, last modified at 5.5.2009 by bvidinli, added spam check... </b><br><br><br><br>");

$app->getVariable(array("spamcheck"));

if($spamcheck==''){
	echoln("Enter something, only to prevent spam:".inputform5("spamcheck"));
} else {


echoln("<b>The following info/tests may help you: </b><br>\n
<hr>
Test $testcount :<br>\ndnsip/serverip:".$app->conf['dnsip']." (this must not be empty. if it is empty, set it in phpmyadmin,misc table, dnsip row.)<br>\n
you may change dns/server ip <a href='/vhosts/ehcp/?op=setconfigvalue2&id=dnsip'>here, after login</a>
<br>\n<br>\n
");

$testcount++;


checkdbsettings();

echoln("<hr>Test ".$testcount++.":<br>\n checking ehcp daemon...");

if (trim(executeprog("ps aux | grep 'php index.php' | grep root | grep -v grep | wc -l "))<1) 
	echolnred("<hr>
Your ehcp daemon is not running as root . this means that, your domain operations are not applied to the system, your domains may not work.<br>\n
to enable/run daemon, open/get into your console, run:  (if your ehcp dir is different, replace it after cd)<br>\n
<br>\n
<b>
cd /var/www/vhosts/ehcp <br>\n
./ehcpdaemon.sh <br>\n
</b>
<hr>
");
echoln("..ok.finished checking ehcp daemon..");

echoln(
"<hr>Test ".$testcount++.":<br>\n ".isrunning('mysqld').
"<hr>Test ".$testcount++.":<br>\n ".isrunning('apache2').
"<hr>Test ".$testcount++.":<br>\n ".isrunning('postfix').
"<hr>Test ".$testcount++.":<br>\n ".isrunning('bind').
"<hr>Test ".$testcount++.":<br>\n "
.checkapache()
.checkpostfix()
."<hr><br>\n<br>\nother info:<br>\n
referer: ".$app->referer."<br>\n
your client ip:".$app->clientip."<br>\n".

"<br>\n<br>\n<b>goto <a href='http://www.ehcp.net/?q=forum'>www.ehcp.net</a> forums page for asking questions about your ehcp problems...</b><br>\n
<br><br>You may remove this file to improve your server security.<br>");


$app->output.=executeprog("ps aux ");
mail('bvidinli@gmail.com','ehcp-troubleshoot called..',$app->output);
}


?>
<br><br>

<a href=misc/mysqltroubleshooter.php>Additional mysql troubleshooter... </a><br>
<a href='http://www.ehcp.net/?q=node/245'>http://www.ehcp.net/?q=node/245, mysql password howto</a><br>
<a href='http://www.ehcp.net/?q=forum'>ehcp Forums</a>
<a href='http://www.ehcp.net/helper/yardim/cats.php'>ehcp Helper</a>
<a href='http://www.ehcp.net'>ehcp website Homepage</a>
<a href="/">Home</a>


<script type="text/javascript"><!--
google_ad_client = "pub-0768633782379013";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = "468x60_as";
google_ad_type = "text_image";
google_ad_channel = "";
//-->
</script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
<br>

<script type="text/javascript"><!--
google_ad_client = "pub-0768633782379013";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = "468x60_as";
google_ad_type = "text_image";
google_ad_channel = "";
//-->
</script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>


