<?php
require_once '_guard.php';
global $wpdb;

    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY cached_date DESC LIMIT 200";
    $card = $wpdb->get_results($q);
    
    $x = 1;
    foreach($card as $c) {
        
        /*$q2 = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE card_number NOT NULL";
        $check = $wpdb->get_row($q2);
        
        if(!$check) {*/
        
            $data = unserialize($c->cached_meta);
            echo '<pre>'.print_r($data,true).'</pre>';
            
            /*$types = $data['types'];
            
            //echo '<pre>'.print_r($types,true).'</pre>';
        
            foreach($types as $t) {

                $wpdb->insert( 
                    $wpdb->prefix ."ptp_cache_card_types",
                    array(
                        'card_id' => $data['id'],
                        'type' => $t
                    )
                );
                
            }

            echo '<p>#'.$x.': Got Types For '.$c->api_id.'</p>';*/
            
        /*}*/
        
    $x++;
    }
    
}
?>