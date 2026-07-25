<?php
require_once '_guard.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
global $wpdb;

$count = $_GET['offset'] + 1;

/*$set = "SELECT DISTINCT card_set_name FROM ".$wpdb->prefix."ptp_cache_card ORDER BY release_date DESC, card_set_name ASC";
$sets = $wpdb->get_results($set);
$c = 0;
foreach($sets as $s) {*/
    
    //echo PHP_EOL.'SET: '.strtoupper($s->card_set_name).PHP_EOL;
    
    //$q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE card_set_name = '".$s->card_set_name."'";
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY api_id LIMIT ".$_GET['limit']." OFFSET ".$_GET['offset'];
    $cards = $wpdb->get_results($q);
    ?>

    <?php
    $cardids = array();
    foreach($cards as $card) {
        
        array_push($cardids,$card->api_id);
    
    }
    
    foreach($cardids as $id) {

        if(checkPriceCache($id)) {

            $card = getPokeCard($id);
            $card = $card->toArray();

            $pricing = $card['tcgplayer']['prices'];

            foreach($pricing as $key => $value) {

                if(!empty($value)) {

                    $wpdb->insert( 
                        $wpdb->prefix ."ptp_pricing",
                        array(
                            'card_id' => $id,
                            'price_market' => 'tcgplayer',
                            'price_type' => $key,
                            'price_low' => $value['low'],
                            'price_mid' => $value['mid'],
                            'price_high' => $value['high'],
                            'price_avgmarket' => $value['market'],
                            'logged_date' => date('Y-m-d H:i:s')
                        )
                    );

                }

            }
            
            echo $count.': Card ID '.$id.' has been updated!'.PHP_EOL;

        } else {
            
            echo $count.': Card ID '.$id.' is up to date.'.PHP_EOL;
            
        } 
        
        $count++;
    
        usleep(150);

    }
    
//}
?>