<?php
/*
EASY HOSTING CONTROL PANEL MAIN index.php FILE Version 0.30 - www.ehcp.net

IF YOU SEE THIS ON BROWSER,  IMMADIATELY STOP WEBSERVER with /etc/init.d/apache2 stop, otherwise, your passwords may be seen by others... 

IF YOU SEE THIS INSTEAD OF A WEB PAGE, THEN YOU PROBABLY DIDN'T INSTALL PHP EXTENSION, PLEASE RE-RUN EHCP INSTALL SCRIPT OR MANUALLY INSTALL APACHE2-PHP EXTENSION..

* 
by I.Bahattin Vidinli, 
mail: info@ehcpforce.ezpz.cc

see classapp.php for real application.
*/
# setlocale(LC_ALL, "en_EN.UTF-8");  # Bu olmadıgında, bir php bug'ından dolayı, "Fatal error: Interface 'Iterator' not found in..." hatası veriyor. bug: https://bugs.php.net/bug.php?id=18556
# bu da çözüm olmadı. tam çözüm/workaround için: http://ehcp.net/?q=node/1273
 
// include_once("/adodb5/adodb.inc.php"); # adodb database abstraction layer.. hope database abstracted...
include_once("classapp.php"); # real application class


degiskenal(array("op"));
global $commandline;
$commandline=false;
$user = $_SERVER['HTTP_USER_AGENT'];
if($argv and $argc and (is_array($argv))and (!$user)) {
        $commandline=true;
        $op=$argv[1];
        print_r($argv);
        echo "Commandline active, argc: $argc \n op:$op:\n argv:".print_r($argv);
} else {
        session_start();
}
//echo "argc: $argc <br>\n";

$app = new Application();
$app->cerceve="standartcerceve";
$app->usertable="domainusers";
$app->userfields=array("id","domainname","username","email","quota");
$app->op=strtolower($op);
$app->run();

?>
