<?php
/**
  * SquirrelMail  EHCP Change  Password Plugin
  * Paul Chandra Bhushan <cdbhushan@gmail.com>
  * This program is licensed under GPL. See COPYING for details
  * 
  */

/**
  * Register this plugin with SquirrelMail
  *
  */
/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'include/load_prefs.php');

displayPageHeader($color, 'None');

global $submit, $new_pw1, $new_pw2;
global $username, $color;
$form_show=true; //To display Password change form

sqgetGlobalVar('new_pw1', $new_pw1, SQ_FORM);
sqgetGlobalVar('new_pw2', $new_pw2, SQ_FORM);
sqgetGlobalVar('submit', $submit, SQ_FORM);

$new_pw1 = trim($new_pw1);
$new_pw2 = trim($new_pw2);

$status[1] = _("You forgot the user name.");
$status[2] = _("You must enter the new password in both boxes.");
$status[3] = _("The new password must be at least six characters long.");
$status[4] = _("The new password must be less then 15 characters long.");
$status[5] = _("You entered a different new password in both boxes.");

if (isset($submit)) {
    // check for errors!
    //
    if (empty($username)) {	// Assumes $username is available from Squirralmail global vars...
    $msg = $status[1];
    $errors = true;
    } elseif (empty($new_pw1) || empty($new_pw2)) {
        $msg = $status[2];
        $errors = true;
/*
    } elseif (strlen($new_pw1) < 6) {
        $msg = $status[3];
        $errors = true;
*/
    } elseif (strlen($new_pw1) > 15) {
        $msg = $status[4];
        $errors = true;
    } elseif ($new_pw1 != $new_pw2) {
        $msg = $status[5];
        $errors = true;
    }
}

//$un_parts = explode("@", $username);	// Split the un into parts.  (username/domain name)
$user_email = trim($username); //Your user name

require_once("config.php"); // this is config of squirrelmail plugin.

echo html_tag( 'table', '', 'center', $color[0], 'width="95%" cellpadding="1" cellspacing="0" border="0"' ) . "\n" .
        html_tag( 'tr' ) . "\n" .
            html_tag( 'td', '', 'center' ) .
                '<b>' . _("Options") . ' - ' . _("EHCP Password Change") . "</b><br />\n" .
                html_tag( 'table', '', 'center', '', 'width="100%" cellpadding="5" cellspacing="0" border="0"' ) . "\n" .
                    html_tag( 'tr' ) . "\n" .
                        html_tag( 'td', '', 'left', $color[4] ) . "<br />\n";
						
if (isset($submit) && ($errors == false)) {
	// Attempt connection

                // Create connection to MYSQL database
				$mysql_access = mysql_connect($dbhost, $dbusername, $dbpass); //connect to mysql database
                if (!$mysql_access)
                {
                    die('Could not connect: ' . mysql_error());
					echo '</td></tr></table></td></tr></table></body></html>';
					return 0;					
                }
                else
                {
                    mysql_select_db($dbname, $mysql_access);
					$query="UPDATE emailusers SET password = encrypt('$new_pw1','ehcp') WHERE email = '$user_email'";
					if(!mysql_query($query)) {
						echo "Hata Olustu: ".mysql_error();
						die();					
					}
					mysql_close($mysql_access);
					$form_show= false;
                }
}

if($form_show){						
?>
<form method=post>
			   <center>
			   <hr width="50%" size="1" noshade>
			   <table>
				  <tr>
					<td align=right><?php echo _("Your account name:"); ?></td> 
					<td><b><?php echo $username;?></b>
					   </td>
				  </tr>
				  <tr>
					 <td align=right><?php echo _("New password:"); ?></td>
					 <td><input name=new_pw1 size=10 type=password maxlength="15"></td></tr>
				  <tr>
					 <td align=right><?php echo _("Confirm the new password:"); ?></td>
					 <td><input name=new_pw2 size=10 type=password maxlength="15"></td><br>
					<?php echo '<font color=#CC0000 >'.$msg.'</font>';?>
				  </tr>
				</table>
				<hr width="50%" size="1" noShade>
				<input name=submit type=submit value="<?php echo _("Change Password");?>">
</form> 
<?php }else{
echo _("<center><font color=#66CC00>Password changed successfully.</font><br />Please use your new password to log in from now on.</center>");
}
 ?>

</td></tr></table></td></tr></table></body></html>
