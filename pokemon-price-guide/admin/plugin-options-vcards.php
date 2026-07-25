<?php
use Pokemon\Models\Pagination;
use Pokemon\Pokemon;
// The Setttings Page
global $wpdb,$plugin_weburl,$plugin_url;
?>

<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function() {
    
    /*jQuery("p[id^='card_']").each(function() {
       
        var thisId = jQuery(this).attr("id").substr(6);
        //console.log(thisId);
        jQuery("span#result_"+thisId).html(thisId);
        
    });*/
	
});
</script>

<div class="wrap">

	<h2><span class="dashicons dashicons-admin-site-alt"></span> Price Guide Options - View All Cards</h2>
	
	<div class="meta_inner_admin">
	
		<form name="form_settings" id="form_settings" action="options.php" method="post">
		
		<div class="header">View All Cards In Cache</div>
		<div class="bottom"></div>
			
		<div class="team_wrapper">
			
            <div class="full">

                <?php
                getPokeCardsAllCacheView();
                ?>

            </div>
            
        </div>
		
	</div>
	
</div>