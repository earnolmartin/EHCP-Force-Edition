/**
 * crypto_settings.js
 *
 * Some client-side checks. Nothing fancy.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 2001-2020 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: crypto_settings.js 14845 2020-01-07 08:09:34Z pdontthink $
 */

/**
 * This function is the only thing. It is called on form submit and
 * asks the user some questions.
 */
function checkMe(){
  if (!document.forms[0].action.checked){
    alert (ui_makesel);
    return false;
  }
  if (document.forms[0].encaction.value=="encrypt")
    cmsg=ui_encrypt;
  if (document.forms[0].encaction.value=="decrypt")
    cmsg=ui_decrypt;
  return confirm(cmsg);
}
