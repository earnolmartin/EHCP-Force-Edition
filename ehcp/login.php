<?php
/*
login.php
 * EASY HOSTING CONTROL PANEL MAIN index.php FILE Version 0.23.4 - www.EHCP.net
by I.Bahattin Vidinli, 
mail&msn: bvidinli@iyibirisi.com

see classapp.php for real application.
*/

include_once("config/adodb/adodb.inc.php");
include_once("classapp.php");


degiskenal(array('op','username','password'));

if(!$op){
    $app->loginform();
} elseif($username and $password) {
    $app->dologin();
}

?>
