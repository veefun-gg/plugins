<?php
use Pokemon\Models\Pagination;
use Pokemon\Pokemon;

/* GET POKEMON TYPES
-----------------------------------------------*/

function getPokeTypes() {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    if(stripslashes(get_option('api_key')) != '') {

        require $plugin_url . '/pokemontcg/vendor/autoload.php';
        
        $url = "pokemon/type/all";

        if(checkCache($url)) {

            $response = getCache($url);
            //$response = unserialize(base64_decode($response));

        } else {

            try{

                Pokemon::Options(['verify' => false]);
                Pokemon::ApiKey(''.stripslashes(get_option('api_key')).'');

                $response = Pokemon::Type()->all();
                
                populateCache($url,print_r($response,true));

            } catch(Exception $ex) {

                $response = getCache($url,'error');
                $response = unserialize(base64_decode($response));

            }

        }

        //$json = json_encode($response);
        return $response;

    }
    
}

/* GET ALL POKEMON CARDS FROM API
-----------------------------------------------*/

function getPokeCardsAll($page,$per) {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    if(stripslashes(get_option('api_key')) != '') {

        require $plugin_url . '/pokemontcg/vendor/autoload.php';
        
        $url = "pokemon/cards/all";

        /*if(checkCache($url)) {

            $response = getCache($url);
            //$response = unserialize(base64_decode($response));

        } else {*/

            try{

                Pokemon::Options(['verify' => false]);
                Pokemon::ApiKey(''.stripslashes(get_option('api_key')).'');

                $response = Pokemon::Card()->page(floor($page))->pageSize($per)->all();
                
                //populateCache($url,print_r($response,true));

            } catch(Exception $ex) {

                //$response = getCache($url,'error');
                //$response = unserialize(base64_decode($response));
                
                $response = array();

            }

        /*}*/

        return $response;

    }
    
}

/* GET SINGLE POKEMON CARD
-----------------------------------------------*/

function getPokeCard($id) {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    if(stripslashes(get_option('api_key')) != '') {

        require $plugin_url . '/pokemontcg/vendor/autoload.php';
        
        $url = "pokemon/card/".$id."/";

        /*if(checkCache($url)) {

            $response = getCache($url);
            //$response = unserialize(base64_decode($response));

        } else {

            try{*/

                Pokemon::Options(['verify' => false]);
                Pokemon::ApiKey(''.stripslashes(get_option('api_key')).'');

                $response = Pokemon::Card()->find($id);
                
                /*populateCache($url,print_r($response,true));

            } catch(Exception $ex) {

                $response = getCache($url,'error');
                $response = unserialize(base64_decode($response));

            }

        }*/

        //$json = json_encode($response);
        return $response;

    }
    
}

/* VIEW ALL POKEMON CARDS IN CACHE
-----------------------------------------------*/

function getPokeCardsAllCacheView() {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE api_id != '' ORDER BY name";
	$cache = $wpdb->get_results($q);
    $total = $wpdb->num_rows;
    echo '<strong>There are '.$total.' total cards</strong><br/>';
    
    $x = 1;
    foreach($cache as $c) {
        
        if($c->api_id != '') {
        
            //$data = unserialize($c->cached_meta);

            echo '<h3 id="card_'.$x.'" data-id="'.$c->api_id.'">#'.$x.': <strong>'.$c->api_id.': '.$c->name.'</strong></h3>';
            //echo '<span id="result_'.$x.'">Loading...</span>';
            echo '<a href="'.get_bloginfo('wpurl').'/price-guide/'.$c->permalink.'/'.$c->api_id.'/" target="_blank">'.get_bloginfo('wpurl').'/price-guide/'.$c->permalink.'/'.$c->api_id.'/</a>';
            echo '<div style="width:100%;height:10px;clear:both;"></div>';

            $x++;
            
        }
        
    }
    
}

/* GET ALL POKEMON CARDS FROM CACHE
-----------------------------------------------*/

function getPokeCardsAllCache() {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY RAND()";
	$cache = $wpdb->get_results($q);
    
    $x = 1;
    foreach($cache as $c) {
        
        echo '<h3 id="card_'.$x.'" data-id="'.$c->api_id.'"><strong>'.$c->api_id.': '.$c->name.'</strong></h3>';
        echo '<span id="result_'.$x.'">Loading...</span>';
        echo '<div style="width:100%;height:10px;clear:both;"></div>';
        
        $x++;
        
    }
    
}

/* GET ALL POKEMON CARDS FROM CACHE WITHOUT PRICE
-----------------------------------------------*/

function getPokeCardsAllCacheNoPrice() {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY cached_date DESC LIMIT 1000";
	$cache = $wpdb->get_results($q);
    
    $x = 1;
    foreach($cache as $c) {
        
        echo '<h3 id="card_'.$x.'" data-id="'.$c->api_id.'"><strong>'.$c->api_id.': '.$c->name.'</strong></h3>';
        echo '<span id="result_'.$x.'">Loading...</span>';
        echo '<div style="width:100%;height:10px;clear:both;"></div>';
        
        $x++;
        
    }
    
}

/* GET ALL POKEMON CARDS FROM CACHE
-----------------------------------------------*/

function getPokeCardsAllPriceUpdate() {
    global $wpdb,$plugin_weburl,$plugin_url; 
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card";
	$cache = $wpdb->get_results($q);
    
    $x = 1;
    foreach($cache as $c) {
        
        echo '<h3 id="card_'.$x.'" data-id="'.$c->api_id.'"><strong>'.$c->api_id.': '.$c->name.'</strong></h3>';
        echo '<span id="result_'.$x.'">Loading...</span>';
        echo '<div style="width:100%;height:10px;clear:both;"></div>';
        
        $x++;
        
    }
    
}
?>