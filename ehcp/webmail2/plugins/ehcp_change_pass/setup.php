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
function squirrelmail_plugin_init_ehcp_change_pass() {
    global $squirrelmail_plugin_hooks;
	
    $squirrelmail_plugin_hooks['optpage_register_block']['ehcp_change_pass'] = 'ehcp_change_pass_optpage_register_block';
}

function ehcp_change_pass_optpage_register_block() {
    // Gets added to the user's OPTIONS page.
    global $optpage_blocks;

    if ( !soupNazi() ) {

        /* Register Squirrelspell with the $optionpages array. */
        $optpage_blocks[] = array(
            'name' => _("Ehcp Change Password"),
            'url'  => SM_PATH . 'plugins/ehcp_change_pass/ehcp_change_pass_opt.php',
            'desc' => _("This configures settings for Change new EHCP email password."),
            'js'   => TRUE
            );
    }
}

?>