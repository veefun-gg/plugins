<?php
use Pokemon\Models\Pagination;
use Pokemon\Pokemon;
// The Setttings Page
global $wpdb,$plugin_weburl,$plugin_url;
?>

<script type="text/javascript">
jQuery.noConflict();
jQuery(document).ready(function() {
	
	
});
</script>

<div class="wrap">

	<h2><span class="dashicons dashicons-admin-site-alt"></span> Price Guide Options</h2>
	
	<div class="meta_inner_admin">
	
		<form name="form_settings" id="form_settings" action="options.php" method="post">
		
			<div class="header">General Settings</div>
			<div class="bottom"></div>
			
			<div class="team_wrapper">
			
				<div class="half">
					<p class="titles">API Key</p>
					<input type="text" name="api_key" value="<?php echo stripslashes(get_option('api_key')); ?>" style="height:50px;" />
				</div>
		
                <div class="half">
                    <p class="titles">Cache Refresh</p>
                    <select name="cache_time" style="height:50px!important;">
                        <option value="5 minutes"<?php if(get_option('cache_time') == '5 minutes') { echo ' selected="selected"'; } ?>>5 Minutes</option>
                        <option value="1 hour"<?php if(get_option('cache_time') == '1 hour') { echo ' selected="selected"'; } ?>>1 Hour</option>
                        <option value="6 hours"<?php if(get_option('cache_time') == '6 hours') { echo ' selected="selected"'; } ?>>6 Hours</option>
                        <option value="12 hours"<?php if(get_option('cache_time') == '12 hours') { echo ' selected="selected"'; } ?>>12 Hours</option>
                        <option value="24 hours"<?php if(get_option('cache_time') == '24 hours') { echo ' selected="selected"'; } ?>>24 Hours</option>
                        <option value="2 days"<?php if(get_option('cache_time') == '2 days') { echo ' selected="selected"'; } ?>>2 Days</option>
                        <option value="5 days"<?php if(get_option('cache_time') == '5 days') { echo ' selected="selected"'; } ?>>5 Days</option>
                        <option value="7 days"<?php if(get_option('cache_time') == '7 days') { echo ' selected="selected"'; } ?>>7 Days</option>
                    </select>
                </div>	
				
				<div class="clear"></div>

                <?php settings_fields('ptp_options'); ?>
			
				<div class="half">
            
                    <input type="submit" class="button button-primary" value="SAVE" />
                    
				</div>
				
				<div class="clear"></div>
				
			</div>
						
			<div class="clear"></div>
			
		</form>
		
		<div class="header">API Connection Test</div>
		<div class="bottom"></div>
			
		<div class="team_wrapper">
			
            <div class="full">

                <?php
                $api = getPokeTypes();
                ?>
                
                <?php getCacheUpdate("pokemon/type/all"); ?>

                <?php
                echo '<pre>'.print_r($api,true).'</pre>';
                ?>

            </div>
			
            <div class="full">
            
            <?php
            $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE id = 1";
	        $cache = $wpdb->get_row($q);
            
            // Handle missing cache object to prevent PHP warnings
            if ($cache && $cache->cached_meta) {
                $data = unserialize($cache->cached_meta);
            } else {
                $data = array(); // Default to empty array if no cache data
            }
            
            echo '<pre>'.print_r($data,true).'</pre>';
            ?>
                
            </div>
            
        </div>
		
	</div>
	
</div>