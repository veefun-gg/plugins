<?php
define('ABSPATH', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
include_once(ABSPATH.'wp-load.php');
global $wpdb;
//echo '<div style="float:left;width:100%;padding:15px;box-sizing:border-box;background:#F1F1F1;font-size:10px;">';
//echo '<h4>TCG Player</h4>';
//echo '<pre>'.print_r($card['tcgplayer']['prices'], true).'</pre></div>';

if(checkPriceCache($_POST['id'])) {
	
    $card = getPokeCard($_POST['id']);
    $card = $card->toArray();

    $pricing = $card['tcgplayer']['prices'];

    foreach($pricing as $key => $value) {

        if(!empty($value)) {

            $wpdb->insert( 
                $wpdb->prefix ."ptp_pricing",
                array(
                    'card_id' => $_POST['id'],
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
        
$q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND card_id = '".$_POST['id']."' AND logged_date > '".$prev."'";
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

if($type == "single") {
        
    //echo '<pre>'.print_r($prices,true).'</pre>';

    foreach($prices as $key => $value) {

        if(!empty($value)) {

            if(is_array($value)) {

                foreach($value as $key2 => $value2) {

                    if(!empty($value2) && in_array($key2,$labels)) {

                        $bits = explode("Reverse",ucfirst($key));

                        $content .= '<div class="quarter">';

                            $content .= '<h3>';

                            if($bits[1]) {
                                $content .= 'Reverse '.$bits[1];
                            } else {
                                $content .= ucfirst($key);
                            }
                            $content .= ' '.$vlabels[$key2].'</h3>';

                            $content .= '$'.$value2;

                            $content .= '<div class="margin15"></div>';

                        $content .= '</div>';

                    }

                }

            } else {

                $content .= '<div class="quarter">';

                    $content .= '<h3>'.$key.'</h3>';

                    $content .= '$'.$value;

                    $content .= '<div class="margin15"></div>';

                $content .= '</div>';

            }

        }  

    }

    $timestamp = strtotime($pricing[0]->logged_date);
    $dt = new DateTime("now"); //first argument "must" be a string
    $currts = $dt->format("Y-m-d H:i:s");
    $timezone = new DateTimeZone('America/New_York');
    $dt->setTimezone($timezone);
    $dt->setTimestamp($timestamp); //adjust the object to correct timestamp

    echo $content;

    echo '<div class="margin5"></div><small><strong>Pricing Last Updated</strong>: '.$dt->format("F jS, Y H:i").' EST</small><div class="margin10"></div>';
    
} else {
    
    $price = $prices['normal']['price_avgmarket'];
    if($price == '') {
        $price = $prices['holofoil']['price_avgmarket'];
    }
    if($price == '') {
        $price = $prices['reverseHolofoil']['price_avgmarket'];
    }
    
    echo '$'.$price;
    
}

/*echo '<div style="float:left;width:100%;padding:15px;box-sizing:border-box;background:#F1F1F1;font-size:10px;">';
//echo '<h4>Card Market</h4>';
echo '<pre>'.print_r($card['cardmarket']['prices'], true).'</pre></div>';

$pricing = $card['cardmarket']['prices'];

$wpdb->insert( 
    $wpdb->prefix ."ptp_pricing",
    array(
        'card_id' => $_POST['id'],
        'price_market' => 'cardmarket',
        'price_type' => 'normal',
        'price_low' => $pricing['lowPrice'],
        'price_high' => $pricing['trendPrice'],
        'price_avgmarket' => $pricing['averageSellPrice']
    )
);

$wpdb->insert( 
    $wpdb->prefix ."ptp_pricing",
    array(
        'card_id' => $_POST['id'],
        'price_market' => 'cardmarket',
        'price_type' => 'reverseHolofoil',
        'price_low' => $pricing['reverseHoloLow'],
        'price_high' => $pricing['reverseHoloTrend'],
        'price_avgmarket' => $pricing['reverseHoloSell']
    )
);*/

?>