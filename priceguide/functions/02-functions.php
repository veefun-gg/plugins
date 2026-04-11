<?php
/* CREATE HOOK TO UPDATE SETS */
function cron_primetime_update_cards_4ccd3826() {
global $wpdb;
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY name";
    $cache = $wpdb->get_results($q);
    $total = $wpdb->num_rows;
    $gtotal = $total;
    $total = $total / 10;
    $total = $total;
    
    $result = '';
    
    function getCards($tot,$gtot) {
        
        //$total = $total + 1;
    
        $api = getPokeCardsAll($tot,10);

        //$api = $api->toArray();
        //echo '<pre>'.print_r($api, true).'</pre>';

        if(!empty($api)) {

            //$text = '<p style="font-weight:bold;font-size:18px;text-align:center;">Collected '.($total * 5).' Cards</p>|';
            $text = '';

            foreach ($api as $card) {

                $crd = $card->toArray();

                //echo '<p><strong>'.$crd['name'].'</strong></p>';

                $text .= '<div style="width:70px;display:inline;padding:2px;"><p><strong>'.$crd['name'].'</strong></p><img style="width:70px;height:auto;" src="'.$crd['images']['small'].'" /></div>';

                populateCacheCard($crd);

                //echo '<pre>'.print_r($card->toArray(), true).'</pre>';

                //usleep(15);

            //    print_r($model->toJson());
            }
                
            $tot = $tot + 1;
            getCards($tot,$gtot);

            //echo $text;
            
            //$text = $gtot.' / '.$tot;

            $admineaddr = get_bloginfo('admin_email');	

            $site = get_option('siteurl');
            $name = get_option('blogname');
            $notify = ''.$admineaddr.'';
            //$notify = 'wordpress@jsswebdevelopment.com';
            $email = 'wordpress@jsswebdevelopment.com';

            $headers = array( 'From: '.$name.' <'.$notify.'>','Content-type: text/html' );

            $emessage = $text;
            $mail_message = wp_mail($email, 'Primetime Get Result', $emessage, $headers);
            
            return 'Keep Going';

        } else {

            return 'EOL';

        }
        
    }
    
    getCards($total,$gtotal);
    
}
add_action( 'primetime_update_cards_cron', 'cron_primetime_update_cards_4ccd3826', 10, 0 );

if ( ! wp_next_scheduled( 'primetime_update_cards_cron' ) ) {
    wp_schedule_event( time(), 'daily', 'primetime_update_cards_cron' );
}
?>