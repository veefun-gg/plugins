<?php
/* META Filter
---------------------------------------------------------------------------------------*/

function ptp_priceguide_cached_card_data( $cache ) {
    if ( ! $cache || empty( $cache->cached_meta ) ) {
        return false;
    }

    $data = maybe_unserialize( $cache->cached_meta );

    return is_array( $data ) ? $data : false;
}

function ptp_priceguide_array_field( $data, $key ) {
    return ( isset( $data[$key] ) && is_array( $data[$key] ) ) ? $data[$key] : array();
}

function ptp_priceguide_scalar_field( $data, $key, $fallback = 'N/A' ) {
    if ( isset( $data[$key] ) && ! is_array( $data[$key] ) && $data[$key] !== '' ) {
        return $data[$key];
    }

    return $fallback;
}

function ptp_priceguide_nested_scalar_field( $data, $key, $nested_key, $fallback = 'N/A' ) {
    if ( isset( $data[$key] ) && is_array( $data[$key] ) && isset( $data[$key][$nested_key] ) && $data[$key][$nested_key] !== '' ) {
        return $data[$key][$nested_key];
    }

    return $fallback;
}

function ptp_priceguide_get_cached_card( $pokemon_name, $card_id ) {
    global $wpdb;

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_cache_card WHERE permalink = %s AND api_id = %s",
            (string) $pokemon_name,
            (string) $card_id
        )
    );
}

function ptp_priceguide_local_image_url( $cache, $size = 'large' ) {
    if ( ! is_object( $cache ) ) {
        return '';
    }

    $candidates = array();

    if ( ! empty( $cache->image_large_local ) && is_scalar( $cache->image_large_local ) ) {
        $candidates[] = (string) $cache->image_large_local;
    }

    if ( ! empty( $cache->attachment_id ) && function_exists( 'wp_get_attachment_image_url' ) ) {
        $attachment_url = wp_get_attachment_image_url( (int) $cache->attachment_id, $size );

        if ( is_string( $attachment_url ) ) {
            $candidates[] = $attachment_url;
        }
    }

    $site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

    foreach ( $candidates as $candidate ) {
        $candidate = esc_url_raw( $candidate );

        if ( '' === $candidate ) {
            continue;
        }

        $candidate_host = wp_parse_url( $candidate, PHP_URL_HOST );

        if ( ! empty( $site_host ) && ! empty( $candidate_host ) && strtolower( $site_host ) === strtolower( $candidate_host ) ) {
            return $candidate;
        }
    }

    return '';
}

function ptp_priceguide_unavailable_pricing_view_model() {
    return array(
        'has_current_price'   => false,
        'has_trend'           => false,
        'price_type'          => '',
        'price_type_label'    => '',
        'current_price'       => null,
        'current_display'     => '',
        'current_logged_date' => '',
        'prior_price'         => null,
        'delta'               => null,
        'delta_percent'       => null,
        'delta_display'       => '',
        'trend_direction'     => 'unavailable',
        'current_message'     => 'Current cached price unavailable.',
        'trend_message'       => 'Trend unavailable until two cached snapshots exist.',
    );
}

function ptp_priceguide_pricing_row_value( $row, $key, $fallback = null ) {
    if ( is_array( $row ) && array_key_exists( $key, $row ) ) {
        return $row[$key];
    }

    if ( is_object( $row ) && isset( $row->{$key} ) ) {
        return $row->{$key};
    }

    return $fallback;
}

/**
 * Build a deterministic display model from SELECT-only cached pricing rows.
 * The legacy display preference is preserved: normal, holofoil, then reverse.
 */
function ptp_priceguide_build_pricing_view_model( $rows ) {
    $model = ptp_priceguide_unavailable_pricing_view_model();

    if ( ! is_array( $rows ) || empty( $rows ) ) {
        return $model;
    }

    $normalized = array();

    foreach ( $rows as $row ) {
        $market = (string) ptp_priceguide_pricing_row_value( $row, 'price_market', 'tcgplayer' );
        $type   = (string) ptp_priceguide_pricing_row_value( $row, 'price_type', '' );
        $price  = ptp_priceguide_pricing_row_value( $row, 'price_avgmarket' );

        if ( 'tcgplayer' !== strtolower( $market ) || '' === $type || ! is_numeric( $price ) || (float) $price <= 0 ) {
            continue;
        }

        $normalized[] = array(
            'id'          => (int) ptp_priceguide_pricing_row_value( $row, 'id', 0 ),
            'price_type'  => $type,
            'price'       => round( (float) $price, 2 ),
            'logged_date' => (string) ptp_priceguide_pricing_row_value( $row, 'logged_date', '' ),
        );
    }

    if ( empty( $normalized ) ) {
        return $model;
    }

    usort(
        $normalized,
        static function ( $left, $right ) {
            $date_order = strcmp( $right['logged_date'], $left['logged_date'] );

            if ( 0 !== $date_order ) {
                return $date_order;
            }

            return $right['id'] <=> $left['id'];
        }
    );

    $grouped = array();

    foreach ( $normalized as $row ) {
        if ( ! isset( $grouped[$row['price_type']] ) ) {
            $grouped[$row['price_type']] = array();
        }

        $same_snapshot = ! empty( $grouped[$row['price_type']] )
            && $row['logged_date'] === $grouped[$row['price_type']][0]['logged_date'];

        if ( count( $grouped[$row['price_type']] ) < 2 && ! $same_snapshot ) {
            $grouped[$row['price_type']][] = $row;
        }
    }

    $type_labels = array(
        'normal'             => 'Normal market price',
        'holofoil'           => 'Holofoil market price',
        'reverseHolofoil'    => 'Reverse holofoil market price',
        'reverseholofoil'    => 'Reverse holofoil market price',
    );
    $selected_type = '';

    foreach ( array( 'normal', 'holofoil', 'reverseHolofoil', 'reverseholofoil' ) as $preferred_type ) {
        if ( ! empty( $grouped[$preferred_type] ) ) {
            $selected_type = $preferred_type;
            break;
        }
    }

    if ( '' === $selected_type ) {
        return $model;
    }

    $current = $grouped[$selected_type][0];

    $model['has_current_price']   = true;
    $model['price_type']          = $selected_type;
    $model['price_type_label']    = $type_labels[$selected_type];
    $model['current_price']       = $current['price'];
    $model['current_display']     = '$' . number_format( $current['price'], 2, '.', ',' );
    $model['current_logged_date'] = $current['logged_date'];
    $model['current_message']     = '';

    if ( count( $grouped[$selected_type] ) < 2 ) {
        return $model;
    }

    $prior         = $grouped[$selected_type][1];
    $delta         = round( $current['price'] - $prior['price'], 2 );
    $delta_percent = 0.0;

    if ( 0.0 !== $prior['price'] ) {
        $delta_percent = round( ( $delta / $prior['price'] ) * 100, 2 );
    }

    $direction = 'unchanged';
    $sign      = '';

    if ( $delta > 0 ) {
        $direction = 'up';
        $sign      = '+';
    } elseif ( $delta < 0 ) {
        $direction = 'down';
        $sign      = '-';
    }

    $model['has_trend']       = true;
    $model['prior_price']     = $prior['price'];
    $model['delta']           = $delta;
    $model['delta_percent']   = $delta_percent;
    $model['delta_display']   = $sign . '$' . number_format( abs( $delta ), 2, '.', ',' ) . ' (' . $sign . number_format( abs( $delta_percent ), 2, '.', ',' ) . '%)';
    $model['trend_direction'] = $direction;
    $model['trend_message']   = '';

    return $model;
}

function ptp_priceguide_get_pricing_view_model( $card_id, $database = null ) {
    if ( null === $database ) {
        global $wpdb;
        $database = $wpdb;
    }

    if ( ! is_object( $database ) || ! is_scalar( $card_id ) || '' === trim( (string) $card_id ) ) {
        return ptp_priceguide_unavailable_pricing_view_model();
    }

    $query = $database->prepare(
        "SELECT id, card_id, price_market, price_type, price_avgmarket, logged_date
        FROM {$database->prefix}ptp_pricing
        WHERE price_market = %s AND card_id = %s AND price_avgmarket IS NOT NULL
        ORDER BY logged_date DESC, id DESC
        LIMIT 50",
        'tcgplayer',
        (string) $card_id
    );
    $rows = $database->get_results( $query );

    return ptp_priceguide_build_pricing_view_model( is_array( $rows ) ? $rows : array() );
}

function ptp_priceguide_render_pricing_view_model( $card_id, $model ) {
    $content = '<div class="ptp-pricing-summary" id="result_' . esc_attr( $card_id ) . '" aria-busy="false">';

    if ( ! empty( $model['has_current_price'] ) ) {
        $content .= '<div class="quarter ptp-pricing-current">';
            $content .= '<h3>' . esc_html( $model['price_type_label'] ) . '</h3>';
            $content .= '<p class="ptp-pricing-value">' . esc_html( $model['current_display'] ) . '</p>';
        $content .= '</div>';
    } else {
        $content .= '<p class="ptp-pricing-unavailable">' . esc_html( $model['current_message'] ) . '</p>';
    }

    if ( ! empty( $model['has_trend'] ) ) {
        $content .= '<p class="ptp-pricing-trend ptp-pricing-trend--' . esc_attr( $model['trend_direction'] ) . '">';
            $content .= esc_html__( 'Change from prior cached snapshot: ', 'primetimepriceguide' );
            $content .= '<strong>' . esc_html( $model['delta_display'] ) . '</strong>';
        $content .= '</p>';
    } else {
        $content .= '<p class="ptp-pricing-trend-unavailable">' . esc_html( $model['trend_message'] ) . '</p>';
    }

    if ( ! empty( $model['current_logged_date'] ) ) {
        $content .= '<small class="ptp-pricing-snapshot">' . esc_html__( 'Cached snapshot: ', 'primetimepriceguide' ) . esc_html( $model['current_logged_date'] ) . '</small>';
    }

    $content .= '</div>';

    return $content;
}

function wporg_only_title_home_page( $title ) {
    global $wpdb;

	if ( get_query_var( 'pgpokeid' ) != '' ) {

		$cache = ptp_priceguide_get_cached_card( get_query_var( 'pgpokename' ), get_query_var( 'pgpokeid' ) );

        $data = ptp_priceguide_cached_card_data( $cache );

        if ( $cache && $data ) {
            $card_name = ! empty( $cache->name ) ? $cache->name : ptp_priceguide_scalar_field( $data, 'name', 'Price Guide' );
            $set_name = ptp_priceguide_nested_scalar_field( $data, 'set', 'name', '' );

            if ( ! empty( $set_name ) ) {
                $title = $card_name. ' • ' .$set_name . ' • PrimetimePokemon.com';
            } else {
                $title = $card_name. ' • PrimetimePokemon.com';
            }
        }

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
        
        $cache = ptp_priceguide_get_cached_card( get_query_var( 'pgpokename' ), get_query_var( 'pgpokeid' ) );

        if($cache) {

            $data = ptp_priceguide_cached_card_data( $cache );
            if ( ! $data ) {
                return $title;
            }

            $types = '';
            $subtypes = ptp_priceguide_array_field( $data, 'subtypes' );
            if($subtypes) {
                $types = implode(", ",$subtypes);
            }

            $supertype = ptp_priceguide_scalar_field( $data, 'supertype', '' );
            $hp = ptp_priceguide_scalar_field( $data, 'hp', '' );

            $title = ! empty( $cache->name ) ? $cache->name : ptp_priceguide_scalar_field( $data, 'name', $title );
            $title .= '<br/><small>'.$supertype.'';
            if(!empty($types)) {
                $title .= '- '.$types.'';
            }
            $title .= '</small>';
            if($hp) {
                $title .= '<span>HP '.$hp.'</span>';
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
        
        $cache = ptp_priceguide_get_cached_card( get_query_var( 'pgpokename' ), get_query_var( 'pgpokeid' ) );

        $data = ptp_priceguide_cached_card_data( $cache );
        $data2 = '';
        //$data2 = '<pre>'.print_r($data,true).'</pre>';

        if ( ! $cache || ! $data ) {
            $content = '<div class="pg-wrapper">';
                $content .= '<div class="pg-section">';
                    $content .= '<h2>Card Not Found</h2>';
                    $content .= '<p>This Price Guide card could not be found. Please use the current card link from the Price Guide.</p>';
                $content .= '</div>';
            $content .= '</div>';

            return $content;
        }

        $card_name = ! empty( $cache->name ) ? $cache->name : ptp_priceguide_scalar_field( $data, 'name', 'Unknown Card' );
        $set_name = ptp_priceguide_nested_scalar_field( $data, 'set', 'name', 'Unknown Set' );
        $printed_total = ptp_priceguide_nested_scalar_field( $data, 'set', 'printedTotal', 'N/A' );
        $image_large = ptp_priceguide_local_image_url( $cache, 'large' );

        $content = '<div class="pg-wrapper">';
        
            $content .= '<div class="third padding50r">';

                if ( ! empty( $image_large ) ) {
                    $content .= '<a rel="sponsored" href="https://www.ebay.com/sch/i.html?_nkw='.$card_name.'+'.$set_name.'&mkcid=1&mkrid=711-53200-19255-0&siteid=0&campid=5338833587&customid=&toolid=10001&mkevt=1" target="_blank"><img src="'.$image_large.'" /></a>';
                } else {
                    $content .= '<p>Image unavailable.</p>';
                }

            $content .= '</div>';
        
            $content .= '<div class="twothirds">';
        
                $content .= '<div class="pg-section" style="padding-top:0;">';
        
                    $content .= '<h2 style="margin-top:0;">PRICES</h2>';
        
                    $content .= '<h3 class="price-heading"><a rel="sponsored" href="https://www.ebay.com/sch/i.html?_nkw='.$card_name.'+'.$set_name.'&mkcid=1&mkrid=711-53200-19255-0&siteid=0&campid=5338833587&customid=&toolid=10001&mkevt=1" id="btn" class="btn cardbtn" target="_blank">Get The Best Price on eBay</a></h3><h3 class="price-heading"><a href="https://www.tcgplayer.com/search/all/product?q='.$card_name.'+'.$set_name.'&irclickid=ziS1UeXRBxyPUOxRQV25Yyc-UkHQ9hy3NxEyW80&sharedid=&irpid=5467273&irgwc=1&utm_source=impact&utm_medium=affiliate&utm_campaign=PrimetimePokemon.com" id="btn" class="btn cardbtn" target="_blank">Get The Best Price on TCGPlayer</a></h3><p class="callout">See the best prices and the latest price trends for ' .$card_name. ' in the eBay or TCGPlayer marketplaces.</p>';
        
                    $pricing_view_model = ptp_priceguide_get_pricing_view_model( get_query_var( 'pgpokeid' ) );
                    $content .= ptp_priceguide_render_pricing_view_model( get_query_var( 'pgpokeid' ), $pricing_view_model );
        
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
        
                    $abilities = ptp_priceguide_array_field( $data, 'abilities' );
                    foreach($abilities as $a) {
                        
                        $content .= '<h3>'.$a['name'].'</h3>';
                        
                        $content .= $a['text'];
                        
                    }
        
                    $content .= '<div class="margin15"></div>';
        
                    $content .= '<h2>ATTACKS</h2>';
                    $attacks = ptp_priceguide_array_field( $data, 'attacks' );
                    foreach($attacks as $a) {
                        
                        $content .= '<h2>'.$a['name'].'<span><strong>Damage</strong>: '.$a['damage'].'</span></h2>';
                        
                        $content .= $a['text'];
                        
                    }
        
                    $content .= '<div class="margin15"></div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>WEAKNESS</h3>';
        
                        $weaknesses = ptp_priceguide_array_field( $data, 'weaknesses' );
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
        
                        $resistances = ptp_priceguide_array_field( $data, 'resistances' );
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
        
                        $retreatCost = ptp_priceguide_array_field( $data, 'retreatCost' );
                        $xr = 0;
                        foreach($retreatCost as $r) {

                            $content .= $r.' ';
                            $xr++;

                        }

                        if($xr == 0) {
                            $content .= 'N/A';
                        }

                    $content .= '</div>';
        
                    $content .= '<div class="clear"></div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>ARTIST</h3>';
        
                        $content .= ptp_priceguide_scalar_field( $data, 'artist' );

                    $content .= '</div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>RARITY</h3>';
        
                        $content .= ptp_priceguide_scalar_field( $data, 'rarity' );

                    $content .= '</div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>SET</h3>';
        
                        $content .= $set_name;

                    $content .= '</div>';
        
                    $content .= '<div class="margin15"></div>';
        
                    $content .= '<div class="third">';

                        $content .= '<h3>NUMBER</h3>';
        
                        $content .= ptp_priceguide_scalar_field( $data, 'number' ).' / '.$printed_total;

                    $content .= '</div>';
        
                    $content .= '<div class="clear"></div>';
        
                    $content .= '<em>'.ptp_priceguide_scalar_field( $data, 'flavorText', '' ).'</em>';
        
                    $content .= '<div class="margin15"></div>';

                    $legalities = ptp_priceguide_array_field( $data, 'legalities' );
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
        
        $content .= do_shortcode('[primetime-related name="'.$card_name.'"]');

        if ( function_exists( 'ptp_priceguide_get_related_editorial_html' ) ) {
            $content .= ptp_priceguide_get_related_editorial_html( $card_name );
        }

        $content .= $data2;
        
    }

    return $content;
}
?>
