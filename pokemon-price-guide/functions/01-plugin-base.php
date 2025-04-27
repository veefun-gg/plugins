<?php
/* Create plugin paths
---------------------------------------------------------------------------------------*/

$plugin_root = dirname(dirname(dirname(__FILE__))).'/';
$plugin_url = dirname(dirname(__FILE__)).'/';
$plugin_weburl = plugins_url( '' , dirname(__FILE__) ).'/';

function register_my_session2()
{
  if( !session_id() )
  {
    session_start();
  }
}

//add_action('init', 'register_my_session2');
	

/* Add CSS
---------------------------------------------------------------------------------------*/

function getCustomCSS2() {
	
    global $plugin_weburl;
	
	define('CSSPATH2', dirname(dirname(__FILE__)).'/');
	foreach (glob(CSSPATH2."css/*.css") as $filename)
	{
        wp_register_style( str_replace(".","_",strtolower(basename($filename))), $plugin_weburl.'css/'.basename($filename), array(), '3.5.4' );
        wp_enqueue_style( str_replace(".","_",strtolower(basename($filename))) );
	}

    wp_register_script( 'slickJS', '//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', 'jquery-core', '1.0', array('in_footer' => true) );
    wp_enqueue_script( 'slickJS' );

    wp_register_style( 'slickCSS', '//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css' );
    wp_enqueue_style( 'slickCSS' );
	
}
	
add_action('wp_enqueue_scripts', 'getCustomCSS2');


/* Add Header Code
---------------------------------------------------------------------------------------*/

function httppost_hook_js2() {
global $plugin_weburl;
?>

    <script type="text/javascript">
    function updateCardsPricing(thatId,type) {

        jQuery.ajax({type: "POST", url: "<?php echo $plugin_weburl; ?>admin/scripts/get-card.php", data: "id="+thatId+"&ptype="+type, success: function(data) {

            jQuery("#result_"+thatId).html(data);

        }
        });

    }
        
    function updateCardsPricingGroup(theseIds,type) {

        jQuery.ajax({type: "POST", url: "<?php echo $plugin_weburl; ?>admin/scripts/get-cards.php", data: "ids="+theseIds+"&ptype="+type, success: function(data) {

            //jQuery("#result_"+thatId).html(data);
            if(data != '') {
           
                var obj = jQuery.parseJSON(data);

                for (const key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        //console.log(key+'/'+obj[key]);
                        jQuery("#result_"+key).html(obj[key]);
                    }
                }
                
            }

        }
        });

    }
        
    jQuery.noConflict();
    jQuery(document).ready(function() {
    
    });
    </script>
	
<?php
}
add_action('wp_head', 'httppost_hook_js2');


/* Add Footer Code
---------------------------------------------------------------------------------------*/

function httppost_footer_function2() {
?>

<?php	
}

add_action( 'wp_footer', 'httppost_footer_function2' );


/* Add Custom DB Tables
---------------------------------------------------------------------------------------*/

function create_dbtables() {
	global $wpdb;
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	
	$table = $wpdb->prefix . "ptp_cache";
	if($wpdb->get_var("show tables like '$table'") !== $table) 
	{
		
		$sql =  "CREATE TABLE ". $table . " (
			id MEDIUMINT(12) NOT NULL AUTO_INCREMENT,
			api_call TEXT,
			api_result LONGBLOB,
			cached_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id));";
	
		dbDelta($sql);

	}
	
	$table = $wpdb->prefix . "ptp_cache_card";
	if($wpdb->get_var("show tables like '$table'") !== $table) 
	{
		
		$sql =  "CREATE TABLE ". $table . " (
			id MEDIUMINT(12) NOT NULL AUTO_INCREMENT,
			api_id TEXT,
			name TEXT,
			permalink TEXT,
			image_small TEXT,
			image_large TEXT,
            attachment_id NUMERIC,
            cached_meta LONGTEXT,
            card_set TEXT,
            card_set_name TEXT,
            card_series TEXT,
            release_date TIMESTAMP,
			cached_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id));";
	
		dbDelta($sql);

	}
	
	$table = $wpdb->prefix . "ptp_cache_card_set";
	if($wpdb->get_var("show tables like '$table'") !== $table) 
	{
		
		$sql =  "CREATE TABLE ". $table . " (
			id MEDIUMINT(12) NOT NULL AUTO_INCREMENT,
			card_id TEXT,
			card_set TEXT,
			card_set_name TEXT,
			card_series TEXT,
			saved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id));";
	
		dbDelta($sql);

	}
	
	$table = $wpdb->prefix . "ptp_cache_card_types";
	if($wpdb->get_var("show tables like '$table'") !== $table) 
	{
		
		$sql =  "CREATE TABLE ". $table . " (
			id MEDIUMINT(12) NOT NULL AUTO_INCREMENT,
			card_id TEXT,
			type TEXT,
			saved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id));";
	
		dbDelta($sql);

	}
	
	$table = $wpdb->prefix . "ptp_cache_log";
	if($wpdb->get_var("show tables like '$table'") !== $table) 
	{
		
		$sql =  "CREATE TABLE ". $table . " (
			id MEDIUMINT(12) NOT NULL AUTO_INCREMENT,
			api_call TEXT,
			api_type TEXT,
			logged_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id));";
	
		dbDelta($sql);

	}
	
	$table = $wpdb->prefix . "ptp_pricing";
	if($wpdb->get_var("show tables like '$table'") !== $table) 
	{
		
		$sql =  "CREATE TABLE ". $table . " (
			id MEDIUMINT(12) NOT NULL AUTO_INCREMENT,
			card_id TEXT,
			price_market TEXT,
			price_type TEXT,
			price_low DECIMAL(8,2),
			price_mid DECIMAL(8,2),
			price_high DECIMAL(8,2),
			price_avgmarket DECIMAL(8,2),
			logged_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY id (id));";
	
		dbDelta($sql);

	}
	
}

add_action('init', 'create_dbtables');

function add_column_if_not_exist($db, $column, $column_attr, $default) {
    global $wpdb;
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $db LIKE '$column'");
    if(!$columns){
        $wpdb->query("ALTER TABLE `$db` ADD `$column` $column_attr");
    }
}

function updatetable_db_admin() {
	global $table_prefix, $wpdb;
	
	$cs_tables = $table_prefix . "ptp_cache_card";
	add_column_if_not_exist($cs_tables, 'permalink', 'TEXT', '');
	add_column_if_not_exist($cs_tables, 'cached_meta', 'LONGTEXT', '');
	add_column_if_not_exist($cs_tables, 'release_date', 'TIMESTAMP', '');
	add_column_if_not_exist($cs_tables, 'card_set', 'TEXT', '');
	add_column_if_not_exist($cs_tables, 'card_set_name', 'TEXT', '');
	add_column_if_not_exist($cs_tables, 'card_series', 'TEXT', '');
	add_column_if_not_exist($cs_tables, 'card_number', 'NUMERIC', '');
	add_column_if_not_exist($cs_tables, 'image_large_local', 'TEXT', '');
	
}

add_action('admin_init', 'updatetable_db_admin');


/* Page ReWrite Rules
---------------------------------------------------------------------------------------*/

function pokemon_plugin_query_vars($vars) {
  $vars[] = 'pgpokeid';
  $vars[] = 'pgpokename';
  $vars[] = 'pglistpage';
  return $vars;
 }

add_filter('query_vars', 'pokemon_plugin_query_vars');

function wpd_pc_rewrite_rule() {
    add_rewrite_tag( '%pgpokename%', '([A-Za-z0-9\-\_]+)' );
    add_rewrite_tag( '%pgpokeid%', '([A-Za-z0-9\-\_]+)' );
    add_rewrite_tag( '%pglistpage%', '([0-9]+)' );
    add_rewrite_rule(
        '^price-guide/page/([0-9]+)/?$',
        'index.php?pagename=price-guide&pglistpage=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^price-guide/(?!page)([A-Za-z0-9\-\_]+)/?$',
        'index.php?pagename=price-guide&pgpokename=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^price-guide/(?!page)([A-Za-z0-9\-\_]+)/([A-Za-z0-9\-\_]+)/?$',
        'index.php?pagename=price-guide&pgpokename=$matches[1]&pgpokeid=$matches[2]',
        'top'
    );
}
add_action( 'init', 'wpd_pc_rewrite_rule' );

function wpd_pc_get_param() {
    if( false !== get_query_var( 'pgpokename' ) ) {
        $_GET['pgpokename'] = get_query_var( 'pgpokename' );
    }
    if( false !== get_query_var( 'pgpokeid' ) ) {
        $_GET['pgpokeid'] = get_query_var( 'pgpokeid' );
    }
    if( false !== get_query_var( 'pglistpage' ) ) {
        $_GET['pglistpage'] = get_query_var( 'pglistpage' );
    }
}
//add_action( 'parse_query', 'wpd_pc_get_param' );
?>
