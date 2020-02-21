<?php

/**
 * SquirrelMail Test Plugin
 * @copyright 2006-2020 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: test.php 14845 2020-01-07 08:09:34Z pdontthink $
 * @package plugins
 * @subpackage test
 */


include_once('../../include/init.php');

global $oTemplate, $color;

displayPageHeader($color, '');

$oTemplate->display('plugins/test/test_menu.tpl');
$oTemplate->display('footer.tpl');

