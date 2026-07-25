<?php
/*
Plugin Name: Primetime Pokémon - Price Guide
Description: A custom plugin for creating the Primetime Pokémon Price Guide.
Version: 1.1
Author: Blamster Web Services
Text Domain: primetimepriceguide
*/

// These are essential functions for the operation of this plugin. 
// DO NOT REMOVE these included files!

define('FUNCPATH', dirname(__FILE__).'/');
foreach (glob(FUNCPATH."functions/*-*.php") as $filename) {
    include($filename);
}
?>
