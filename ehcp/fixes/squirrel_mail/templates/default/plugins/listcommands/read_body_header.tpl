<?php

/**
  * read_body_header.tpl
  *
  * Template for adding controls to the read_headers page template 
  * for the Listcommands plugin
  *
  * The following variables are available in this template:
  *      + $links   - an array of links for each command/control to be added
  *
  * @copyright 1999-2020 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: read_body_header.tpl 14845 2020-01-07 08:09:34Z pdontthink $
  * @package squirrelmail
  * @subpackage plugins
  */


// retrieve the template vars
//
extract($t);


?>

<tr id="listcommands">
  <td class="fieldName">
    <b><?php echo _("Mailing List"); ?>:</b>
  </td>
  <td class="fieldValue">
    <small><?php echo implode('&nbsp;|&nbsp;', $links); ?></small>
  </td>
</tr>
