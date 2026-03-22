<?php
/* PRICE GUIDE DISPLAY
-----------------------------------------------------------------*/

function priceguide_list_code_display($category,$pname,$set) {
global $plugin_weburl,$wpdb;
    
    $offset = 1;
    if(get_query_var( 'pglistpage' ) > 0) {
        $offset = get_query_var( 'pglistpage' );
    }
    ?>

    <style>
        #content {
            -ms-flex-wrap: none!important;
            flex-wrap: none!important;
            display: block!important;
        }
    </style>

    <div class="pg-list grid">
        
        <div class="loading">
            <img src="<?php echo $plugin_weburl; ?>images/loader.gif" />
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function() {

            function getCards(offset) {
                
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
                
                var cardIds = [];

                jQuery.ajax({type: "POST", url: "<?php echo $plugin_weburl; ?>templates/get-list.php", data: "name=<?php echo $pname; ?>&offset="+offset+"&perpage="+perpage+"&gridlist="+gridlist+"&order="+order+"&set="+encodeURIComponent(set)+"&type="+encodeURIComponent(type), success: function(data)
                {

                    jQuery("#pgresults").html(data);
                    jQuery('.loading').fadeOut();
                    
                    jQuery(".card").each(function() {
                        
                        var thisId = jQuery(this).data("id");
                        //console.log(thisId);
                        //updateCardsPricing(thisId,"list");
                        cardIds.push(thisId);
                        
                    });
                    
                    //console.log(cardIds);
                    //updateCardsPricingGroup(cardIds,"list");
                    //console.log(results);

                }
                });

            }
            
            var offset = jQuery('.item.current').data("page");
            if(!offset) {
                offset = 1;
            }
            getCards(offset);
            
            jQuery("#options_perpage").on("change", function() {
                getCards(offset);
            });
            
            jQuery("#options_type").on("change", function() {
                getCards(offset);
            });
            
            jQuery(document.body).on("change","input[name='type']", function() {
                getCards(offset);
            });
            
            jQuery("#options_gridlist").on("change", function() {
                getCards(offset);
            });
            
            jQuery("#options_set").on("change", function() {
                getCards(offset);
            });
            
            jQuery("#options_order").on("change", function() {
                getCards(offset);
            });
            
            jQuery(document.body).on("click",".pagination .item", function() {
        
                var thisPage = jQuery(this).data("page");
                getCards(thisPage);

                if(!jQuery(this).hasClass("prev") && !jQuery(this).hasClass("next")) {

                    jQuery(".pagination .item").each(function() {

                        jQuery(this).removeClass("current");

                    });

                    jQuery(this).addClass("current");

                }

                jQuery([document.documentElement, document.body]).animate({
                    scrollTop: jQuery(".results").offset().top - 140
                }, 750);

            });

        });
        </script>
        
        <select class="pgoptions" id="options_perpage">
            <option value="25"<?php if(!$_SESSION['perpage'] || $_SESSION['perpage'] == 25) {  ?> selected="selected"<?php } ?>>25 Per Page</option>
            <option value="50"<?php if($_SESSION['perpage'] == 50) {  ?> selected="selected"<?php } ?>>50 Per Page</option>
            <option value="100"<?php if($_SESSION['perpage'] == 100) {  ?> selected="selected"<?php } ?>>100 Per Page</option>
        </select>
        
        <select class="pgoptions" id="options_order">
            <option value="price"<?php if($_SESSION['order'] == 'price') {  ?> selected="selected"<?php } ?>>Order By Price High to Low</option>
            <option value="alpha"<?php if($_SESSION['order'] == 'alpha') {  ?> selected="selected"<?php } ?>>Order Aphaphetically</option>
            <option value="number"<?php if($_SESSION['order'] == 'number') {  ?> selected="selected"<?php } ?>>Order By Card Number</option>
        </select>
        
        <select class="pgoptions" id="options_gridlist">
            <option value="grid"<?php if($_SESSION['gridlist'] == 'grid') {  ?> selected="selected"<?php } ?>>Grid View</option>
            <option value="list"<?php if(!$_SESSION['gridlist'] || $_SESSION['gridlist'] == 'list') {  ?> selected="selected"<?php } ?>>List View</option>
        </select>
        
        <?php /* ?><select class="pgoptions" id="options_type">
            <option value=""<?php if(!$_SESSION['type']) {  ?> selected="selected"<?php } ?>>All Types</option>
            <?php
            $set = "SELECT DISTINCT type FROM ".$wpdb->prefix."ptp_cache_card_types ORDER BY type ASC";
            $sets = $wpdb->get_results($set);
            $c = 0;
            foreach($sets as $s) {
            ?>
            <option value="<?php echo $s->type; ?>"<?php if($_SESSION['type'] == $s->type) {  ?> selected="selected"<?php } ?>><?php echo $s->type; ?></option>
            <?php
            $c++;
            }
            ?>
        </select><?php */ ?>
        
        <?php if($pname == '' && $set == '') { ?>
        
        <select class="pgoptions" id="options_set">
            <?php
            $set = "SELECT DISTINCT card_set_name FROM ".$wpdb->prefix."ptp_cache_card WHERE card_set_name != '' ORDER BY release_date DESC, card_set_name ASC";
            $sets = $wpdb->get_results($set);
            $c = 0;
            foreach($sets as $s) {
            ?>
            <option value="<?php echo $s->card_set_name; ?>"<?php if(!$_SESSION['set'] && $c == 0 || $_SESSION['set'] == $s->card_set_name) {  ?> selected="selected"<?php } ?>>Set: <?php echo $s->card_set_name; ?></option>
            <?php
            $c++;
            }
            ?>
        </select>
        
        <div class="clear"></div>
        
        <?php } ?>

        <div id="pgresults">
        
            <?php
            $x = 1;
            while($x <= 20) {
                if($_SESSION['gridlist'] == 'grid') {
            ?>
                
                <div class="fifth card">
                    <img src="<?php echo $plugin_weburl; ?>images/preview.jpg" style="opacity: 0.1;" />    
                    <h3>
                        Pokemon
                    </h3>
                </div>
            
            <?php
                } else {
            ?>
                
                <div class="full card">
                    <div class="quarter quarters">
                        <img src="<?php echo $plugin_weburl; ?>images/preview.jpg" style="opacity: 0.1;" />    
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
global $plugin_weburl,$wpdb;

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
            $content .= '<div class="related-cards__controls" data-related-cards-controls hidden>';
                $content .= '<button class="related-cards__control related-cards__control--page-prev" type="button" data-related-cards-page-prev aria-controls="'.esc_attr($viewport_id).'" aria-label="Previous cards" disabled hidden>';
                    $content .= '<span aria-hidden="true">&laquo;</span>';
                $content .= '</button>';
                $content .= '<button class="related-cards__control related-cards__control--prev" type="button" data-related-cards-prev aria-controls="'.esc_attr($viewport_id).'" aria-label="Previous card" disabled>';
                    $content .= '<span aria-hidden="true">&larr;</span>';
                $content .= '</button>';
                $content .= '<button class="related-cards__control related-cards__control--next" type="button" data-related-cards-next aria-controls="'.esc_attr($viewport_id).'" aria-label="Next card" disabled>';
                    $content .= '<span aria-hidden="true">&rarr;</span>';
                $content .= '</button>';
                $content .= '<button class="related-cards__control related-cards__control--page-next" type="button" data-related-cards-page-next aria-controls="'.esc_attr($viewport_id).'" aria-label="Next cards" disabled hidden>';
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
