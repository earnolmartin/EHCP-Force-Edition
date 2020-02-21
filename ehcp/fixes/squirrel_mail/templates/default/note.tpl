<?php
/**
 * note.tpl
 *
 * Template for displaying notes as needed.
 * 
 * Variables available in this template:
 *      $note   = Sanitized string containing not to be displayed.
 *
 * @copyright 1999-2020 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: note.tpl 14845 2020-01-07 08:09:34Z pdontthink $
 * @package squirrelmail
 * @subpackage templates
 */

// Get the variables from the template
extract($t) 
?>
<div class="sqm_noteWrapper">
 <div class="sqm_note">
  <?php echo $note."\n"; ?>
 </div>
</div>
<br />
