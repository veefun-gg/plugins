<?php 
define('ABSPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/');
include_once(ABSPATH.'wp-load.php');
global $wpdb;header("Content-type: text/xml");

echo '<?xml version="1.0" encoding="UTF-8" ?>';

?>

<urlset xmlns="http://www.google.com/schemas/sitemap/0.84" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">

    <?php

    /*$entries = mysql_query("SELECT * FROM Entries");

    while($row = mysql_fetch_assoc($entries)) {
    $title = stripslashes($row['title']);
    $date = date("Y-m-d", strtotime($row['timestamp']));

    echo "

    <url>
        <loc>http://designdeluge.com/".$title."</loc>
        <lastmod>".$date."</lastmod>
        <changefreq>never</changefreq>
        <priority>0.8</priority>
    </url>";

 }*/ ?>
    
    <?php
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY name";
	$cache = $wpdb->get_results($q);
    
    $x = 1;
    foreach($cache as $c) {
        
        $orgDate = $c->cached_date;  
        $newDate = date("Y-m-d", strtotime($orgDate));  
        
        echo '<url>
            <loc>'.get_bloginfo('wpurl').'/price-guide/'.$c->permalink.'/'.$c->api_id.'/</loc>
            <lastmod>'.$newDate.'</lastmod>
        </url>';
    
    }
    ?>

</urlset>