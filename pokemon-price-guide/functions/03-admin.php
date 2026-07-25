<?php

function jss_get_current_post_type() {
	global $post, $typenow, $current_screen;
		
	if( $post && $post->post_type )
		$post_type = $post->post_type;
	elseif( $typenow )
		$post_type = $typenow;
	elseif( $current_screen && $current_screen->post_type )
		$post_type = $current_screen->post_type;
	elseif( isset( $_REQUEST['post_type'] ) )
		$post_type = sanitize_key( $_REQUEST['post_type'] );
	else
		$post_type = null;
		
	return $post_type;
}

function getJSSAdmin() {
    
    global $plugin_weburl;
	
	//Get Admin Styles
	wp_register_style( 'JSSAdminStyle', $plugin_weburl.'/admin/admin.css', array(), '1.0.1' );
	wp_enqueue_style( 'JSSAdminStyle' );
	
}
	
add_action('admin_enqueue_scripts', 'getJSSAdmin');


// Get data stored in a custom field
if(!function_exists('get_custom_field')) {
function get_custom_field($field) {
	global $post;
	$custom_field = get_post_meta($post->ID, $field, true);
	echo $custom_field;
}
}


/* Add admin page
---------------------------------------------------------------------------------------*/

add_action('admin_menu', 'adminMenu');

function adminMenu(){
	add_menu_page('Price Guide Options', 'Price Guide Options', 'edit_posts', 'priceguide-options', 'pgView','', 40);
    add_submenu_page(
        'priceguide-options',
        'View All Cards', //page title
        'View All Cards', //menu title
        'edit_posts', //capability,
        'priceguide_vcards',//menu slug
        'pgVCards' //callback function
    );
    add_submenu_page(
        'priceguide-options',
        'Get All Cards', //page title
        'Get All Cards', //menu title
        'edit_posts', //capability,
        'priceguide_cards',//menu slug
        'pgCards' //callback function
    );
    add_submenu_page(
        'priceguide-options',
        'Update Pricing', //page title
        'Update Pricing', //menu title
        'edit_posts', //capability,
        'priceguide_prices',//menu slug
        'pgPrices' //callback function
    );
}
	
function pgView(){
    global $plugin_url;
    include $plugin_url."/admin/plugin-options.php";
}
	
function pgVCards(){
    global $plugin_url;
    include $plugin_url."/admin/plugin-options-vcards.php";
}
	
function pgCards(){
    global $plugin_url;
    include $plugin_url."/admin/plugin-options-cards.php";
}
	
function pgPrices(){
    global $plugin_url;
    include $plugin_url."/admin/plugin-options-pricing.php";
}


/* Create Theme Settings
---------------------------------------------------------------------------------------*/

function ptp_options() {
	
	register_setting('ptp_options', 'api_key', '');
	register_setting('ptp_options', 'cache_time', '');
	
}

add_action('admin_init','ptp_options');
?>
