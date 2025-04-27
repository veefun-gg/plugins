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
    
    function updateCardsPricing() {
        
        var $cards = jQuery("[id^='card_']");
        //var $cards = jQuery("#card_1");
        var time = 0;

        $cards.each(function() {
            var thisId = jQuery(this).attr("id").substr(5);
            var thatId = jQuery(this).data("id");
            setTimeout( function() { 
                //console.log(thisId);
                
                jQuery.ajax({type: "POST", url: "<?php echo $plugin_weburl; ?>admin/scripts/get-card.php", data: "id="+thatId, success: function(data)
                {
                    
                    jQuery("span#result_"+thisId).html(data); 

                }
                });
                
            }, time)
            time += 100;
        });
        
    }
    
    updateCardsPricing();
	
});
</script>

<div class="wrap">

	<h2><span class="dashicons dashicons-admin-site-alt"></span> Price Guide Options - Update Card Pricing</h2>
	
	<div class="meta_inner_admin">
	
		<form name="form_settings" id="form_settings" action="options.php" method="post">
		
		<div class="header">Get All Cards From Cache</div>
		<div class="bottom"></div>
			
		<div class="team_wrapper">
			
            <div class="full">

                <?php
                getPokeCardsAllCacheNoPrice();
                ?>

            </div>
            
        </div>
		
	</div>
	
</div>