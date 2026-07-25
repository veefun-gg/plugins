<?php
/* PRICE GUIDE DISPLAY
-----------------------------------------------------------------*/

function priceguide_quick_card_links_display() {
global $wpdb;

    $cards = $wpdb->get_results($wpdb->prepare(
        "SELECT name, card_set_name, cached_meta, permalink, api_id FROM ".$wpdb->prefix."ptp_cache_card WHERE name != '' AND permalink != '' AND api_id != '' ORDER BY CASE WHEN permalink = %s AND api_id = %s THEN 0 ELSE 1 END, release_date DESC, name ASC, api_id ASC LIMIT %d",
        'machop',
        'base1-52',
        12
    ));

    if(empty($cards)) {
        return;
    }

    $items = '';

    foreach($cards as $card) {
        $permalink = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $card->permalink);
        $api_id = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $card->api_id);

        if($permalink == '' || $api_id == '') {
            continue;
        }

        $meta = maybe_unserialize($card->cached_meta);
        $card_number = '';

        if(is_array($meta) && !empty($meta['number'])) {
            $card_number = $meta['number'];

            if(!empty($meta['set']['printedTotal'])) {
                $card_number .= '/'.$meta['set']['printedTotal'];
            }
        }

        $meta_parts = array();

        if(!empty($card->card_set_name)) {
            $meta_parts[] = $card->card_set_name;
        }

        if($card_number != '') {
            $meta_parts[] = '#'.$card_number;
        }

        $items .= '<li class="pg-quick-links__item">';
            $items .= '<a class="pg-quick-links__link" href="'.esc_url(home_url('/price-guide/'.$permalink.'/'.$api_id.'/')).'">';
                $items .= '<span class="pg-quick-links__title">'.esc_html($card->name).'</span>';

                if(!empty($meta_parts)) {
                    $items .= '<span class="pg-quick-links__meta">'.esc_html(implode(' ', $meta_parts)).'</span>';
                }

                $items .= '<span class="pg-quick-links__action">View details</span>';
            $items .= '</a>';
        $items .= '</li>';
    }

    if($items == '') {
        return;
    }
    ?>

    <section class="pg-quick-links" aria-labelledby="pg-quick-links-title">
        <h2 id="pg-quick-links-title">Quick Card Links</h2>
        <ul class="pg-quick-links__list">
            <?php echo $items; ?>
        </ul>
    </section>

    <?php
}

function priceguide_session_value($key,$default = '') {

    if(isset($_SESSION) && is_array($_SESSION) && array_key_exists($key, $_SESSION)) {
        return $_SESSION[$key];
    }

    return $default;

}

function priceguide_quick_card_links_index_content($content) {

    if(is_admin()) {
        return $content;
    }

    if(!is_page('price-guide') && get_query_var('pagename') != 'price-guide') {
        return $content;
    }

    if(get_query_var('pgpokename') != '' || get_query_var('pgpokeid') != '') {
        return $content;
    }

    if(strpos($content, 'pg-quick-links') !== false || has_shortcode($content, 'priceguide-list')) {
        return $content;
    }

    ob_start();
    priceguide_quick_card_links_display();
    $quick_links = ob_get_contents();
    ob_end_clean();

    if(trim($quick_links) == '') {
        return $content;
    }

    return $quick_links.$content;

}
add_filter('the_content', 'priceguide_quick_card_links_index_content', 9);

function priceguide_list_code_display($category,$pname,$set) {
global $plugin_weburl,$wpdb;
    
    $offset = 1;
    if(get_query_var( 'pglistpage' ) > 0) {
        $offset = get_query_var( 'pglistpage' );
    }

    $session_perpage = priceguide_session_value('perpage');
    $session_order = priceguide_session_value('order','alpha');
    $session_gridlist = priceguide_session_value('gridlist');
    $session_set = priceguide_session_value('set');
    ?>

    <style>
        #content {
            -ms-flex-wrap: none!important;
            flex-wrap: none!important;
            display: block!important;
        }
    </style>

    <?php if($pname == '' && $set == '') { priceguide_quick_card_links_display(); } ?>

    <div class="pg-list grid">
        
        <div class="loading">
            <img src="<?php echo $plugin_weburl; ?>images/loader.gif" alt="" />
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function() {
            var filterSelectors = "#options_perpage, #options_gridlist, #options_set, #options_order";

            function priceguideListFallback() {
                jQuery("#pgresults").html('<p class="pg-list-unavailable">Price Guide results are unavailable right now.</p>');
            }

            function priceguideListResponseIsValid(data) {
                if(typeof data !== "string" || data.trim() == "") {
                    return false;
                }

                var response = jQuery("<div></div>").html(data);
                var allowedRootElements = ".types, .clear, .full.results, .fifth.card, .full.card, .pagination";
                var hasUnexpectedContent = response.contents().filter(function() {
                    if(this.nodeType === 3) {
                        return String(this.nodeValue).trim() != "";
                    }

                    return this.nodeType !== 1 || !jQuery(this).is(allowedRootElements);
                }).length > 0;

                if(hasUnexpectedContent || response.find("script, style, iframe, object, embed, link, meta").length > 0) {
                    return false;
                }

                return response.children(".full.results").length === 1;
            }

            function getCards(offset) {
                jQuery("#pgresults").attr("aria-busy", "true");
                jQuery('.loading').show();
                var perpage = jQuery("#options_perpage").val();
                var gridlist = jQuery("#options_gridlist").val();
                <?php if($pname == '' && $set == '') { ?>
                var set = jQuery("#options_set").val();
                var type = [];
                jQuery("input:checkbox[name=type]:checked").each(function(){
                    type.push(jQuery(this).val().trim());
                });
                <?php } else { ?>
                var set = '';
                var type = '';
                <?php } ?>
                <?php if($set != '') { ?>
                var set = '<?php echo urldecode($set); ?>';
                <?php } ?>
                var order = jQuery("#options_order").val();

                jQuery.ajax({type: "POST", url: "<?php echo $plugin_weburl; ?>templates/get-list.php", data: "name=<?php echo $pname; ?>&offset="+offset+"&perpage="+perpage+"&gridlist="+gridlist+"&order="+order+"&set="+encodeURIComponent(set)+"&type="+encodeURIComponent(type), success: function(data)
                {

                    if(priceguideListResponseIsValid(data)) {
                        jQuery("#pgresults").html(data);
                    } else {
                        priceguideListFallback();
                    }

                }
                , error: function() {
                    priceguideListFallback();
                }
                , complete: function() {
                    jQuery('.loading').stop(true, true).fadeOut();
                    jQuery("#pgresults").attr("aria-busy", "false");
                }
                });

            }
            
            var offset = jQuery('.item.current').data("page");
            if(!offset) {
                offset = 1;
            }
            getCards(offset);
            
            jQuery(filterSelectors).on("change", function() {
                getCards(offset);
            });
            
            jQuery(document.body).on("change","input[name='type']", function() {
                getCards(offset);
            });

            function activatePageItem(pageItem) {
                var thisPage = pageItem.data("page");

                if(pageItem.hasClass("disabled") || !thisPage) {
                    return;
                }

                getCards(thisPage);

                if(!pageItem.hasClass("prev") && !pageItem.hasClass("next")) {

                    jQuery(".pagination .item").each(function() {

                        jQuery(this).removeClass("current");

                    });

                    pageItem.addClass("current");

                }

                if(jQuery(".results").length) {
                    jQuery([document.documentElement, document.body]).animate({
                        scrollTop: jQuery(".results").offset().top - 140
                    }, 750);
                }
            }

            jQuery(document.body).on("click",".pagination .item", function(e) {
                e.preventDefault();
                activatePageItem(jQuery(this));
            });

            jQuery(document.body).on("keydown",".pagination .item", function(e) {
                if(e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    activatePageItem(jQuery(this));
                }
            });

        });
        </script>
        
        <select class="pgoptions" id="options_perpage">
            <option value="25"<?php if(!$session_perpage || $session_perpage == 25) {  ?> selected="selected"<?php } ?>>25 Per Page</option>
            <option value="50"<?php if($session_perpage == 50) {  ?> selected="selected"<?php } ?>>50 Per Page</option>
            <option value="100"<?php if($session_perpage == 100) {  ?> selected="selected"<?php } ?>>100 Per Page</option>
        </select>
        
        <select class="pgoptions" id="options_order">
            <option value="price"<?php if($session_order == 'price') {  ?> selected="selected"<?php } ?>>Order By Price High to Low</option>
            <option value="alpha"<?php if($session_order == 'alpha') {  ?> selected="selected"<?php } ?>>Order Alphabetically</option>
            <option value="number"<?php if($session_order == 'number') {  ?> selected="selected"<?php } ?>>Order By Card Number</option>
        </select>
        
        <select class="pgoptions" id="options_gridlist">
            <option value="grid"<?php if($session_gridlist == 'grid') {  ?> selected="selected"<?php } ?>>Grid View</option>
            <option value="list"<?php if(!$session_gridlist || $session_gridlist == 'list') {  ?> selected="selected"<?php } ?>>List View</option>
        </select>

        <?php if($pname == '' && $set == '') { ?>
        
        <select class="pgoptions" id="options_set">
            <?php
            $set = "SELECT DISTINCT card_set_name FROM ".$wpdb->prefix."ptp_cache_card WHERE card_set_name != '' ORDER BY release_date DESC, card_set_name ASC";
            $sets = $wpdb->get_results($set);
            $c = 0;
            foreach($sets as $s) {
            ?>
            <option value="<?php echo $s->card_set_name; ?>"<?php if((!$session_set && $c == 0) || $session_set == $s->card_set_name) {  ?> selected="selected"<?php } ?>>Set: <?php echo $s->card_set_name; ?></option>
            <?php
            $c++;
            }
            ?>
        </select>
        
        <div class="clear"></div>
        
        <?php } ?>

        <div id="pgresults" aria-live="polite" aria-busy="true">
        
            <?php
            $x = 1;
            while($x <= 20) {
                if($session_gridlist == 'grid') {
            ?>
                
                <div class="fifth card">
                    <img src="<?php echo $plugin_weburl; ?>images/preview.jpg" alt="" style="opacity: 0.1;" />    
                    <h3>
                        Pokemon
                    </h3>
                </div>
            
            <?php
                } else {
            ?>
                
                <div class="full card">
                    <div class="quarter quarters">
                        <img src="<?php echo $plugin_weburl; ?>images/preview.jpg" alt="" style="opacity: 0.1;" />    
                    </div>
                    <div class="threequarters desc">
                        <h3>Pokemon</h3>
                    </div>
                </div>
            
            <?php
                }
                $x++;
            }
            ?>
        
        </div>
        
    </div>

<?php            
    //echo '<pre>'.print_r($cards,true).'</pre>';
}

function priceguide_list_code( $atts, $content = null ) {
  
	$a = shortcode_atts( array(
		'category' => '',
		'set' => '',
        'pname' => ''
	), $atts );
	
	$cs = '';

	ob_start();
	priceguide_list_code_display(esc_attr($a['category']),esc_attr($a['pname']),esc_attr($a['set']));
	$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;

}
add_shortcode( 'priceguide-list', 'priceguide_list_code' );


/* RELATED DISPLAY
-----------------------------------------------------------------*/

function related_code_card_alt($card) {
    $alt = trim($card->name . ' trading card');

    if (!empty($card->card_set_name)) {
        $alt .= ' from ' . $card->card_set_name;
    }

    return $alt;
}

function related_code_display($type,$name,$set) {
global $wpdb;

    $parts = preg_split('/\s+/', trim($name));
    $search_term = !empty($parts[0]) ? $parts[0] : $name;
    $q = $wpdb->prepare(
        "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE name LIKE %s",
        '%'.$wpdb->esc_like($search_term).'%'
    );
    $cards = $wpdb->get_results($q);

    if (empty($cards)) {
        return;
    }

    $component_id = function_exists('wp_unique_id') ? wp_unique_id('related-cards-') : 'related-cards-'.uniqid();
    $viewport_id = $component_id . '-viewport';
    $heading_id = $component_id . '-heading';
    
    $content = '<section class="related-cards" data-related-cards aria-labelledby="'.esc_attr($heading_id).'">';
        $content .= '<div class="related-cards__header">';
            $content .= '<h2 class="related-cards__heading" id="'.esc_attr($heading_id).'">Cards Like '.esc_html($name).'</h2>';
            $content .= '<div class="related-cards__controls" data-related-cards-controls role="group" aria-label="Related card navigation" hidden>';
                $content .= '<button class="related-cards__control related-cards__control--page-prev" type="button" data-related-cards-page-prev aria-controls="'.esc_attr($viewport_id).'" aria-label="Jump backward several cards" disabled hidden>';
                    $content .= '<span aria-hidden="true">&laquo;</span>';
                $content .= '</button>';
                $content .= '<button class="related-cards__control related-cards__control--prev" type="button" data-related-cards-prev aria-controls="'.esc_attr($viewport_id).'" aria-label="Previous card" disabled>';
                    $content .= '<span aria-hidden="true">&larr;</span>';
                $content .= '</button>';
                $content .= '<button class="related-cards__control related-cards__control--next" type="button" data-related-cards-next aria-controls="'.esc_attr($viewport_id).'" aria-label="Next card" disabled>';
                    $content .= '<span aria-hidden="true">&rarr;</span>';
                $content .= '</button>';
                $content .= '<button class="related-cards__control related-cards__control--page-next" type="button" data-related-cards-page-next aria-controls="'.esc_attr($viewport_id).'" aria-label="Jump forward several cards" disabled hidden>';
                    $content .= '<span aria-hidden="true">&raquo;</span>';
                $content .= '</button>';
            $content .= '</div>';
        $content .= '</div>';

        $content .= '<div class="related-cards__viewport" id="'.esc_attr($viewport_id).'" data-related-cards-viewport tabindex="0">';
            $content .= '<ul class="related-cards__list">';
                foreach($cards as $c) {
                    $card_url = get_bloginfo('wpurl').'/price-guide/'.$c->permalink.'/'.$c->api_id.'/';
                    $card_set_name = !empty($c->card_set_name) ? $c->card_set_name : '';

                    $content .= '<li class="related-cards__item">';
                        $content .= '<a class="related-cards__link" href="'.esc_url($card_url).'">';
                            $content .= '<span class="related-cards__media">';
                                $content .= '<img src="'.esc_url($c->image_large).'" alt="'.esc_attr(related_code_card_alt($c)).'" loading="lazy" decoding="async" />';
                            $content .= '</span>';
                            $content .= '<span class="related-cards__content">';
                                $content .= '<span class="related-cards__title">'.esc_html($c->name).'</span>';
                                if ($card_set_name !== '') {
                                    $content .= '<span class="related-cards__meta">'.esc_html($card_set_name).'</span>';
                                }
                            $content .= '</span>';
                        $content .= '</a>';
                    $content .= '</li>';
                }
            $content .= '</ul>';
        $content .= '</div>';
    $content .= '</section>';
    
    echo $content;
       
}

function related_code( $atts, $content = null ) {
  
	$a = shortcode_atts( array(
		'type' => '',
        'name' => '',
        'set' => ''
	), $atts );
	
	$cs = '';

	ob_start();
	related_code_display(esc_attr($a['type']),esc_attr($a['name']),esc_attr($a['set']));
	$output_string = ob_get_contents();
	ob_end_clean();
	
	return $output_string;

}
add_shortcode( 'primetime-related', 'related_code' );
?>
