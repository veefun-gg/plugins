<?php
/*
Plugin Name: Primetime Pokémon - Price Guide
Description: A custom plugin for creating the Primetime Pokémon Price Guide.
Version: 1.2
Author: Blamster Web Services
Text Domain: primetimepriceguide
*/

define( 'PRIMETIME_PRICE_GUIDE_REWRITE_VERSION', '2026-08-12-1' );
define( 'PRIMETIME_PRICE_GUIDE_REWRITE_VERSION_OPTION', 'primetime_price_guide_rewrite_version' );

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

    primetime_price_guide_flush_rewrite_rules();
}

function primetime_price_guide_flush_rewrite_rules() {
    flush_rewrite_rules( false );
    update_option( PRIMETIME_PRICE_GUIDE_REWRITE_VERSION_OPTION, PRIMETIME_PRICE_GUIDE_REWRITE_VERSION, false );
}

function primetime_price_guide_maybe_flush_rewrite_rules() {
    if ( PRIMETIME_PRICE_GUIDE_REWRITE_VERSION === get_option( PRIMETIME_PRICE_GUIDE_REWRITE_VERSION_OPTION ) ) {
        return;
    }

    primetime_price_guide_flush_rewrite_rules();
}

function primetime_price_guide_deactivate() {
    flush_rewrite_rules( false );
}

register_activation_hook( __FILE__, 'primetime_price_guide_activate' );
register_deactivation_hook( __FILE__, 'primetime_price_guide_deactivate' );
add_action( 'init', 'primetime_price_guide_maybe_flush_rewrite_rules', 99 );
?>
