<?php
define('ABSPATH', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
include_once(ABSPATH.'wp-load.php');
global $wpdb;
//echo '<div style="float:left;width:100%;padding:15px;box-sizing:border-box;background:#F1F1F1;font-size:10px;">';
//echo '<h4>TCG Player</h4>';
//echo '<pre>'.print_r($card['tcgplayer']['prices'], true).'</pre></div>';

if($_POST['ids'] != '') {

    $ids = explode(",",$_POST['ids']);
    $result = array();

    //echo $ids;

    foreach($ids as $id) {

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

        } 

        $prev = date("Y-m-d H:i:s", strtotime("-".get_option('cache_time').""));

        $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND card_id = '".$id."' AND logged_date > '".$prev."'";
        $pricing = $wpdb->get_results($q);

        $prices = array();
        foreach($pricing as $p) {
            if(!$prices[$p->price_type]) {

                $prices[$p->price_type] = (array) $p;

            }
        }

        //$content .= '<pre>'.print_r($prices,true).'</pre>';

        $labels = array('price_low','price_mid','price_high','price_avgmarket');
        $vlabels = array();
        $vlabels['price_low'] = 'Low';
        $vlabels['price_mid'] = 'Mid';
        $vlabels['price_high'] = 'High';
        $vlabels['price_avgmarket'] = 'Avg.';

        $content = '';
        $type = $_POST['ptype'];

        if($type == "list") {

            $price = $prices['normal']['price_avgmarket'];
            if($price == '') {
                $price = $prices['holofoil']['price_avgmarket'];
            }
            if($price == '') {
                $price = $prices['reverseHolofoil']['price_avgmarket'];
            }

            //echo '$'.$price;
            $result[$id] = '$'.$price;

        }

        usleep(150);

    }
    echo json_encode($result);
    
}
?>