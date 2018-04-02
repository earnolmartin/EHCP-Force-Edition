<?php
// ensure this file is being included by a parent file
if( !defined( '_JEXEC' ) && !defined( '_VALID_MOS' ) ) die( 'Restricted access' );
/**
 * @version $Id: login.php 242 2015-08-19 06:29:26Z soeren $
 * @package eXtplorer
 * @copyright soeren 2007-2009
 * @author The eXtplorer project (http://extplorer.net)
 * @author The	The QuiX project (http://quixplorer.sourceforge.net)
 * 
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 * 
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * Alternatively, the contents of this file may be used under the terms
 * of the GNU General Public License Version 2 or later (the "GPL"), in
 * which case the provisions of the GPL are applicable instead of
 * those above. If you wish to allow use of your version of this file only
 * under the terms of the GPL and not to allow others to use
 * your version of this file under the MPL, indicate your decision by
 * deleting  the provisions above and replace  them with the notice and
 * other provisions required by the GPL.  If you do not delete
 * the provisions above, a recipient may use your version of this file
 * under either the MPL or the GPL."
 * 
 * User Authentication Functions
 */

//------------------------------------------------------------------------------
require_once _EXT_PATH."/include/users.php";
ext_load_users();
//------------------------------------------------------------------------------

$GLOBALS['__SESSION']=&$_SESSION;
if( !empty($_REQUEST['type'])) {
	$GLOBALS['authentication_type'] = basename(extGetParam($_REQUEST, 'type', $GLOBALS['ext_conf']['authentication_method_default']));
} else {
	$GLOBALS['authentication_type'] = $GLOBALS['file_mode'];
}
if($GLOBALS['authentication_type'] == 'file') {
	$GLOBALS['authentication_type'] = 'extplorer';
}
if( !in_array($GLOBALS['authentication_type'],$GLOBALS['ext_conf']['authentication_methods_allowed'])) {
	$GLOBALS['authentication_type'] = extgetparam( $_SESSION, 'file_mode', $GLOBALS['ext_conf']['authentication_method_default'] );
	if( !in_array($GLOBALS['authentication_type'],$GLOBALS['ext_conf']['authentication_methods_allowed'])) {
		$GLOBALS['authentication_type'] = $_SESSION['file_mode'] = $GLOBALS['ext_conf']['authentication_method_default'];
	}
}

if( file_exists(_EXT_PATH.'/include/authentication/'.$authentication_type.'.php')) {
		require_once(_EXT_PATH.'/include/authentication/'.$authentication_type.'.php');
		$classname = 'ext_'.$authentication_type.'_authentication';
		if( class_exists($classname)) {
			$GLOBALS['auth'] = new $classname();
		}
}
	
//------------------------------------------------------------------------------
function login() {
	global $auth, $authentication_type;
	if( !is_object($auth)) {
		return false;
	}
	$res = $auth->onAuthenticate();
	if($res){
		return true;
	}		
	
	if( ext_isXHR() && $GLOBALS['action'] != 'login') {
		echo '<script type="text/javascript>document.location="'._EXT_URL.'/index.php";</script>';
		exit();
	}
	
	echo "<p style='color: red; text-align: center;'>Unauthorized.&nbsp; Please login to the <a href='../' style='color: black;'>EHCP panel</a> and try again.</p>";
	define( '_LOGIN_REQUIRED', 1 );
}
	


