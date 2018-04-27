<?php
// ensure this file is being included by a parent file
if( !defined( '_JEXEC' ) && !defined( '_VALID_MOS' ) ) die( 'Restricted access' );
/**
 * @version $Id: extplorer.php 201 2011-06-27 09:45:09Z soeren $
 * @package eXtplorer
 * @copyright soeren 2007-2010
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
 */
 
/**
 * This file handles ehcp authentication
 *
 */
class ext_ehcp_authentication {
	function onAuthenticate($options=null) {
		// Check Login
		//------------------------------------------------------------------------------
		if(!isset($_SESSION['FTP_HOME_PATH']) || empty($_SESSION['FTP_HOME_PATH'])){
			return false;
		}
		
		// 	Set Login
		$_SESSION['file_mode'] = 'extplorer';
		$GLOBALS["home_dir"]	= $_SESSION['FTP_HOME_PATH'];
		$GLOBALS["home_url"]	= "";
		$GLOBALS["show_hidden"]	= 1;
		$GLOBALS["no_access"]	= '';
		$GLOBALS["permissions"]	= '7';
		
		return true;
	}
	
	function onShowLoginForm() {
		if(!isset($_SESSION['FTP_HOME_PATH']) || empty($_SESSION['FTP_HOME_PATH'])){
			echo "<p style='color: red; text-align: center;'>Please <a href='../index.php?op=addDomainToThisPaneluser'>add a domain to your EHCP account</a> before using extplorer.";
		}else{
			echo "<p style='color: red; text-align: center;'>Unauthorized.&nbsp; Please login to the <a href='../'>EHCP panel</a> and try again.</p>";
		}
	}
	
	function onLogout() {
		logout();
	}
} 
?>
