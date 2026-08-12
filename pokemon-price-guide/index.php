<?php
/*
Plugin Name: Primetime Pokémon - Price Guide
Description: A custom plugin for creating the Primetime Pokémon Price Guide.
Version: 1.2
Author: Blamster Web Services
Text Domain: primetimepriceguide
*/

// These are essential functions for the operation of this plugin. 
// DO NOT REMOVE these included files!

define('FUNCPATH', dirname(__FILE__).'/');
foreach (glob(FUNCPATH."functions/*-*.php") as $filename) {
    include($filename);
}

function primetime_price_guide_activate() {
    if ( function_exists( 'wpd_pc_rewrite_rule' ) ) {
        wpd_pc_rewrite_rule();
    }

    flush_rewrite_rules( false );
}

function primetime_price_guide_deactivate() {
    flush_rewrite_rules( false );
}

register_activation_hook( __FILE__, 'primetime_price_guide_activate' );
register_deactivation_hook( __FILE__, 'primetime_price_guide_deactivate' );
?>
