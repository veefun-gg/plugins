<?php
/* META Filter
---------------------------------------------------------------------------------------*/

function wporg_only_title_home_page( $title ) {
    global $wpdb;
    
	if ( get_query_var( 'pgpokeid' ) != '' ) {
        
        $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE permalink = '".get_query_var( 'pgpokename' )."' AND api_id = '".get_query_var( 'pgpokeid' )."'";
	    $cache = $wpdb->get_row($q);
        
        $data = unserialize($cache->cached_meta);
        
		$title = $cache->name. ' • ' .$data['set']['name'] . ' • PrimetimePokemon.com';
        
	}

	return $title;
}
add_filter( 'pre_get_document_title', 'wporg_only_title_home_page', 9999 );


/* Page Template Filter
---------------------------------------------------------------------------------------*/

add_filter( 'the_title', 'filter_the_title_in_the_main_loop', 1 );

function filter_the_title_in_the_main_loop( $title ) {
    
    global $wpdb;

    // Check if we're inside the main loop in a single Post.
    if ( get_query_var( 'pgpokeid' ) != '' && in_the_loop() ) {
        
        $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE permalink = '".get_query_var( 'pgpokename' )."' AND api_id = '".get_query_var( 'pgpokeid' )."'";
	    $cache = $wpdb->get_row($q);
        
        if($cache) {
        
            $data = unserialize($cache->cached_meta);
            
            $types = '';
            if($data['subtypes']) {
                $subtypes = $data['subtypes'];
                $types = implode(", ",$subtypes);
            }

            $title = $cache->name;
            $title .= '<br/><small>'.$data['supertype'].'';
            if(!empty($types)) {
                $title .= '- '.$types.'';
            }
            $title .= '</small>';
            if($data['hp']) {
                $title .= '<span>HP '.$data['hp'].'</span>';
            }
            
        }
        
    }

    return $title;
}

add_filter( 'the_content', 'filter_the_content_in_the_main_loop', 1 );

        
add_filter('body_class','custom_body_class');

function custom_body_class($classes) {
    
    if ( get_query_var( 'pgpokeid' ) != '' ) {
        $classes[] = 'single-price-guide';
    }
    return $classes;
}


function filter_the_content_in_the_main_loop( $content ) {
    
    global $wpdb,$plugin_weburl;

    // Check if we're inside the main loop in a single Post.
    if ( get_query_var( 'pgpokeid' ) != '' ) {
        
        $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE permalink = '".get_query_var( 'pgpokename' )."' AND api_id = '".get_query_var( 'pgpokeid' )."'";
	    $cache = $wpdb->get_row($q);
        
        $data = unserialize($cache->cached_meta);
        //$data2 = '<pre>'.print_r($data,true).'</pre>';
        
        $content = '<div class="pg-wrapper">';
        
            $content .= '<script type="text/javascript">';
            $content .= 'jQuery.noConflict();';
            $content .= 'jQuery(document).ready(function() {';

                $content .= 'updateCardsPricing("'.get_query_var( 'pgpokeid' ).'","single");';

            $content .= '});';
            $content .= '</script>';
        
            $content .= '<div class="third padding50r">';

                $content .= '<a rel="sponsored" href="https://www.ebay.com/sch/i.html?_nkw='.$data['name'].'+'.$data['set']['name'].'&mkcid=1&mkrid=711-53200-19255-0&siteid=0&campid=5338833587&customid=&toolid=10001&mkevt=1" target="_blank"><img src="'.$cache->image_large.'" /></a>';

            $content .= '</div>';
        
            $content .= '<div class="twothirds">';
        
                $content .= '<div class="pg-section" style="padding-top:0;">';
        
                    $content .= '<h2 style="margin-top:0;">PRICES</h2>';
        
                    $content .= '<h3 class="price-heading"><a rel="sponsored" href="https://www.ebay.com/sch/i.html?_nkw='.$data['name'].'+'.$data['set']['name'].'&mkcid=1&mkrid=711-53200-19255-0&siteid=0&campid=5338833587&customid=&toolid=10001&mkevt=1" id="btn" class="btn cardbtn" target="_blank">Get The Best Price on eBay</a></h3><h3 class="price-heading"><a href="https://www.tcgplayer.com/search/all/product?q='.$data['name'].'+'.$data['set']['name'].'&irclickid=ziS1UeXRBxyPUOxRQV25Yyc-UkHQ9hy3NxEyW80&sharedid=&irpid=5467273&irgwc=1&utm_source=impact&utm_medium=affiliate&utm_campaign=PrimetimePokemon.com" id="btn" class="btn cardbtn" target="_blank">Get The Best Price on TCGPlayer</a></h3><p class="callout">See the best prices and the latest price trends for ' .$data['name']. ' in the eBay or TCGPlayer marketplaces.</p>';
        
                    $content .= '<div style="min-height:120px;" id="result_'.get_query_var( 'pgpokeid' ).'">';
        
                        $content .= '<div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>';
        
                    $content .= '</div>';
        
                    /*$q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND card_id = '".get_query_var( 'pgpokeid' )."' ORDER BY logged_date DESC LIMIT 4";
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
        
                    foreach($prices as $key => $value) {

                        if(!empty($value)) {

                            if(is_array($value)) {

                                foreach($value as $key2 => $value2) {
                                    
                                    if(!empty($value2) && in_array($key2,$labels)) {
                                        
                                        $bits = explode("Reverse",ucfirst($key));

                                        $content .= '<div class="quarter">';

                                            if($bits[1]) {
                                                $content .= 'Reverse '.$bits[1];
                                            } else {
                                                $content .= ucfirst($key);
                                            }
                                            $content .= ' '.$vlabels[$key2].'</h3>';

                                            $content .= '$'.$value2;

                                        $content .= '</div>';
                                        
                                    }

                                }

                            } else {

                                $content .= '<div class="quarter">';

                                    $content .= '<h3>'.$key.'</h3>';

                                    $content .= '$'.$value;

                                $content .= '</div>';

                            }

                        }  

                    }*/
        
                    /*$content .= '<div class="margin15"></div>';
        
                    $content .= '<h3><a href="'.$data['cardmarket']['url'].'" target="_blank">Buy now from CardMarket</a></h3>';
        
                    foreach($data['cardmarket']['prices'] as $key => $value) {

                        if(!empty($value)) {

                            if(is_array($value)) {

                                foreach($value as $key2 => $value2) {

                                    $content .= '<div class="quarter">';

                                        $content .= '<h3>'.$key.' '.$key2.'</h3>';

                                        $content .= '$'.$value2;

                                    $content .= '</div>';

                                }

                            } else {

                                $content .= '<div class="quarter">';

                                    $content .= '<h3>'.$key.'</h3>';

                                    $content .= '$'.$value;

                                $content .= '</div>';

                            }

                        }  

                    }*/
        
                    $content .= '<div class="clear"></div>';
        
                $content .= '</div>';
        
                $content .= '<div class="pg-section">';
        
                    $content .= '<h2>ABILITIES</h2>';
        
                    $abilities = $data['abilities'];
                    foreach($abilities as $a) {
                        
                        $content .= '<h3>'.$a['name'].'</h3>';
                        
                        $content .= $a['text'];
                        
                    }
        
                    $content .= '<div class="margin15"></div>';
        
                    $content .= '<h2>ATTACKS</h2>';
                    $attacks = $data['attacks'];
                    foreach($attacks as $a) {
                        
                        $content .= '<h2>'.$a['name'].'<span><strong>Damage</strong>: '.$a['damage'].'</span></h2>';
                        
                        $content .= $a['text'];
                        
                    }
        
                    $content .= '<div class="margin15"></div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>WEAKNESS</h3>';
        
                        $weaknesses = $data['weaknesses'];
                        $xw = 0;
                        foreach($weaknesses as $w) {
                            
                            if($xw > 0) {
                                $content .= '<br/>';
                            }
                            
                            $content .= $w['type'].' '.$w['value'];
                            $xw++;
                            
                        }
        
                        if($xw == 0) {
                            $content .= 'N/A';
                        }

                    $content .= '</div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>RESISTANCE</h3>';
        
                        $resistances = $data['resistances'];
                        $xw = 0;
                        foreach($resistances as $r) {
                            
                            if($xw > 0) {
                                $content .= '<br/>';
                            }
                            
                            $content .= $r['type'].' '.$r['value'];
                            $xw++;
                            
                        }
        
                        if($xw == 0) {
                            $content .= 'N/A';
                        }

                    $content .= '</div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>RETREAT COST</h3>';
        
                        $retreatCost = $data['retreatCost'];
                        foreach($retreatCost as $r) {
                            
                            $content .= $r.' ';
                            
                        }

                    $content .= '</div>';
        
                    $content .= '<div class="clear"></div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>ARTIST</h3>';
        
                        $content .= $data['artist'];

                    $content .= '</div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>RARITY</h3>';
        
                        $content .= $data['rarity'];

                    $content .= '</div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>SET</h3>';
        
                        $content .= $data['set']['name'];

                    $content .= '</div>';
        
                    $content .= '<div class="margin15"></div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>NUMBER</h3>';
        
                        $content .= $data['number'].' / '.$data['set']['printedTotal'];

                    $content .= '</div>';
        
                    $content .= '<div class="clear"></div>';
        
                    $content .= '<em>'.$data['flavorText'].'</em>';
        
                    $content .= '<div class="margin15"></div>';

                    $legalities = $data['legalities'];
                    foreach($legalities as $key => $value) {

                        $content .= '<div class="third">';

                            $content .= '<strong>'.ucfirst($key).':</strong> ';
                            if($value != 'Legal') {
                                $content .= 'Not Legal';
                            } else {
                                $content .= $value;
                            }

                        $content .= '</div>';

                    }
        
                    $content .= '<div class="clear"></div>';
        
                $content .= '</div>';

            $content .= '</div>';
        
        $content .= '</div>';
        
        $content .= do_shortcode('[primetime-related name="'.$cache->name.'"]');

        $content .= $data2;
        
    }

    return $content;
}
?>