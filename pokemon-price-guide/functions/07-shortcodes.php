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

function related_code_display($type,$name,$set) {
global $plugin_weburl,$wpdb;

    $content = '<script type="text/javascript">';
    $content .= 'jQuery.noConflict();';
    $content .= 'jQuery(document).ready(function() {';
            
        $content .= 'function equalHeight() {';

            $content .= 'var highest = 0;';
            $content .= 'jQuery(".card-wrap img").delay( 1000 ).each(function() {';

                $content .= 'if(jQuery(this).outerHeight() > highest) {';
                    $content .= 'highest = jQuery(this).outerHeight();';
                $content .= '}';

            $content .= '});';
    
            $content .= 'console.log(highest);';
    
            $content .= 'jQuery(".card-wrap").each(function() {';

                $content .= 'jQuery(this).outerHeight((highest + 50)+"px");';

            $content .= '});';
    
            $content .= 'jQuery(".slick-list").css("height",(highest+90)+"px");';

        $content .= '}';
    
        /*$content .= 'jQuery(window).on("resize orientationchange", function() {';
            //$content .= 'jQuery(".related-cards").slick("reinit");';
            $content .= 'equalHeight();';
            //$content .= 'console.log("resized");';
        $content .= '});';*/
    
        $content .= 'var doit;';
        $content .= 'window.onresize = function(){';
            $content .= 'clearTimeout(doit);';
            $content .= 'doit = setTimeout(equalHeight, 100);';
        $content .= '};';
    
        $content .= 'jQuery(".related-cards").on("init", function(event, slick){';
            $content .= 'equalHeight();';
        $content .= '});';
    
        $content .= 'jQuery(".related-cards").on("breakpoint", function(event, slick, breakpoint){';
            $content .= 'equalHeight();';
        $content .= '});';

        $content .= 'jQuery(".related-cards").slick({';
            $content .= 'infinite: true,';
            $content .= 'speed: 300,';
            //$content .= 'mobileFirst: true,';
            $content .= 'slidesToShow: 5,';
            $content .= 'centerMode: true,';
            $content .= 'slidesToScroll: 1,';
            $content .= 'prevArrow:"<span class=\'slick-prev\'><</span>",';
            $content .= 'nextArrow:"<span class=\'slick-next\'>></span>",';
            $content .= 'responsive: [ {';
              $content .= 'breakpoint: 1024,';
              $content .= 'settings: {';
                $content .= 'slidesToShow: 3,';
                $content .= 'slidesToScroll: 1';
              $content .= '}';
            $content .= '}, {';
              $content .= 'breakpoint: 600,';
              $content .= 'settings: {';
                $content .= 'slidesToShow: 1,';
                $content .= 'slidesToScroll: 1';
              $content .= '}';
            $content .= '} ]';
        $content .= '});';
    
        $content .= 'jQuery(".custom-next").on("click", function() {';
            $content .= 'jQuery(".related-cards").slick("slickSetOption", {';
               $content .= 'slidesToScroll: 5,';
               $content .= 'responsive: [ {';
                $content .= 'breakpoint: 1024,';
                $content .= 'settings: {';
                    $content .= 'slidesToShow: 3,';
                    $content .= 'slidesToScroll: 3';
                $content .= '}';
                $content .= '} ]';
            $content .= '}, true).slick("slickNext").slick("slickSetOption", {';
               $content .= 'slidesToScroll: 1,';
               $content .= 'responsive: [ {';
                $content .= 'breakpoint: 1024,';
                $content .= 'settings: {';
                    $content .= 'slidesToShow: 3,';
                    $content .= 'slidesToScroll: 1';
                $content .= '}';
                $content .= '} ]';
            $content .= '}, true);';
        $content .= '});';
    
        $content .= 'jQuery(".custom-prev").on("click", function() {';
            $content .= 'jQuery(".related-cards").slick("slickSetOption", {';
               $content .= 'slidesToScroll: 5,';
               $content .= 'responsive: [ {';
                $content .= 'breakpoint: 1024,';
                $content .= 'settings: {';
                    $content .= 'slidesToShow: 3,';
                    $content .= 'slidesToScroll: 3';
                $content .= '}';
                $content .= '} ]';
            $content .= '}, true).slick("slickPrev").slick("slickSetOption", {';
               $content .= 'slidesToScroll: 1,';
               $content .= 'responsive: [ {';
                $content .= 'breakpoint: 1024,';
                $content .= 'settings: {';
                    $content .= 'slidesToShow: 3,';
                    $content .= 'slidesToScroll: 1';
                $content .= '}';
                $content .= '} ]';
            $content .= '}, true);';
        $content .= '});';

    $content .= '});';
    $content .= '</script>';

    $parts = explode(" ",$name);
    echo '<h2>Cards Like '.$name.'</h2>'; 
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE name LIKE '%".$parts[0]."%'";
    $cards = $wpdb->get_results($q);
    
    $content .= '<div class="related-cards-wrap">';

        $content .= '<div class="related-cards">';

            foreach($cards as $c) {

                $content .= '<div class="card-wrap">';
                    $content .= '<a href="'.get_bloginfo('wpurl').'/price-guide/'.$c->permalink.'/'.$c->api_id.'/">';
                    $content .= '<img src="'.$c->image_large.'" />';
                    $content .= '<h4>'.$c->name.'</h4>';
                    $content .= '</a>';
                $content .= '</div>';

            }

        $content .= '</div>';

        $content .= '<span class="slick-next slick-arrow custom-next" style="">&gt;&gt;</span>';
        $content .= '<span class="slick-arrow custom-prev" style="">&lt;&lt;</span>';
        
    $content .= '</div>';
    
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