<?php
define('ABSPATH', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
include_once(ABSPATH.'wp-load.php');
	
    /*$q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY api_id ASC LIMIT 2000 OFFSET ".$_GET['offset']."";
    //$q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE id = ".$_GET['id'];
    $api = $wpdb->get_results($q);

    $count = ($_GET['offset'] * 1);

    foreach ($api as $card) {
        
        $count++;

        //echo '<p><strong>'.$crd['name'].'</strong></p>';
        
        $permalink = strtolower(trim(str_replace("é","e",str_replace("δ","delta",str_replace("'","",str_replace("&","and",str_replace(".","-",str_replace(" ","-",$card->name))))))));
        $permalink = preg_replace("/[^A-Za-z0-9]/", '-', $permalink);

        $text .= '<p>'.$count.': Updated <strong>'.$card->name.'</strong> from permalink <strong>'.$card->permalink.'</strong> to new permalink <strong>'.$permalink.'</strong></p>';

        $wpdb->update( 
            $wpdb->prefix ."ptp_cache_card",
            array(
                'permalink' => $permalink
            ),
            array(
                'api_id' => $card->api_id
            )
        );

        //echo '<pre>'.print_r($card->toArray(), true).'</pre>';

        //usleep(15);

    //    print_r($model->toJson());
    }

    echo $text;*/
?>