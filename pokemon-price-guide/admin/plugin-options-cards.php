<?php
use Pokemon\Models\Pagination;
use Pokemon\Pokemon;
// The Setttings Page
global $wpdb,$plugin_weburl,$plugin_url;
?>

<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function() {
    
    function getCards(page) {
	
        jQuery.ajax({type: "POST", url: "<?php echo $plugin_weburl; ?>/admin/scripts/update-cards.php", data: "page="+page+"&per=5", success: function(data)
        {
            
            if(data != 'EOL') {
                
                var bits = data.split("|");

                jQuery("#count").html(bits[0]);
                jQuery("#results").append(bits[1]);
                getCards((page+1));
                
            } else {
                
                jQuery("#message .lds-facebook").fadeOut();
                jQuery("#message").html("We've reached the beautiful end!");
                
            }

        }	
        });
        
    }
    
    jQuery("#start").on("click",function() {
    
        jQuery(this).hide();
        jQuery("#message").fadeIn();
        getCards((jQuery("#startpoint").val() * 1));
        
    });
	
});
</script>

<style>
.lds-facebook {
  display: inline-block;
  position: relative;
  width: 80px;
  height: 80px;
}
.lds-facebook div {
  display: inline-block;
  position: absolute;
  left: 8px;
  width: 16px;
  background: #333;
  animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
}
.lds-facebook div:nth-child(1) {
  left: 8px;
  animation-delay: -0.24s;
}
.lds-facebook div:nth-child(2) {
  left: 32px;
  animation-delay: -0.12s;
}
.lds-facebook div:nth-child(3) {
  left: 56px;
  animation-delay: 0;
}
@keyframes lds-facebook {
  0% {
    top: 8px;
    height: 64px;
  }
  50%, 100% {
    top: 24px;
    height: 32px;
  }
}
</style>

<div class="wrap">

	<h2><span class="dashicons dashicons-admin-site-alt"></span> Price Guide Options - Get All Cards</h2>
	
	<div class="meta_inner_admin">
		
		<div class="header">Get All Cards From API</div>
		<div class="bottom"></div>
			
		<div class="team_wrapper">
            
            <?php
            $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card ORDER BY name";
	        $cache = $wpdb->get_results($q);
            $total = $wpdb->num_rows;
            $total = $total / 5;
            $total = $total - 1;
            ?>
            
            <select id="startpoint" style="height:41px!important;padding:5px 10px!important;margin:0!important;">
                <option value="1">Start From The Beginning</option>
                <option value="<?php echo $total; ?>">Start From Where We Left Off</option>
            </select>
            
            <button class="buttons button-primary" style="height:40px;border:none;" id="start">
                Start Collecting
            </button>
            
        </div>
			
		<div class="team_wrapper">
			
            <div class="full">
                
                <h2 id="message" style="display:none;text-align:center;font-size:35px;color:#333;">Collecting Cards<br/><div class="lds-facebook"><div></div><div></div><div></div></div></h2>

                <div id="count"></div>
                
                <div id="results"></div>
                
                <?php
                /*$api = getPokeCardsAll();
                ?>
                
                <?php getCacheUpdate("pokemon/cards/all"); ?>

                <?php
                foreach ($api as $card) {
                    
                    $crd = $card->toArray();
                    
                    echo '<p><strong>'.$crd['name'].'</strong></p>';
                    
                    echo '<p><img src="'.$crd['images']['small'].'" /></p>';
                    
                    populateCacheCard($crd);
                    
                    echo '<pre>'.print_r($card->toArray(), true).'</pre>';
                    
                    usleep(25);
                    
                //    print_r($model->toJson());
                }*/
                ?>

            </div>
            
        </div>
		
	</div>
	
</div>