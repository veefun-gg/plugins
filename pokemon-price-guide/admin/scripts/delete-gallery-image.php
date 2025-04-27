<?php

	define('ABSPATH', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
	include_once(ABSPATH.'wp-config.php');
	include_once(ABSPATH.'wp-load.php');
	include_once(ABSPATH.'wp-includes/wp-db.php');
	global $wpdb;
	
	$upload_dir = wp_upload_dir();
	
	define('GALPATH', $upload_dir['basedir'] . '/gallery/images/homepage/');
	
	$image = explode("homepage", $_POST['img']);
	$imagename = explode("/", $image[1]);
	
	unlink(GALPATH.$imagename[1]);
	unlink(GALPATH.'thumbnails/'.$imagename[1]);
	
?>