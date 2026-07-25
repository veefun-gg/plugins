<?php
/* Add To Cache
-------------------------------------------------------------*/ 

function populateCache($url,$result) {
	global $current_user,$wpdb;
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache WHERE api_call = '".$url."'";
	$cache = $wpdb->get_row($q);
    
    $dt = new DateTime("now"); //first argument "must" be a string
    $nextts = $dt->format("Y-m-d H:i:s");
    //$next = date('Y-m-d H:i:s',strtotime('+'.get_option('cache_time').'',strtotime($nextts)));
    
    $result = serialize($result);
    
    if(!$cache) {
    
        $wpdb->insert( 
            $wpdb->prefix ."ptp_cache",
            array(
                'api_call' => $url,
                'api_result' => $result
            )
        );
        
        $wpdb->insert( 
            $wpdb->prefix ."ptp_cache_log",
            array(
                'api_call' => $url,
                'api_type' => 'add'
            )
        );
        
    } else {
            
        $wpdb->update( 
            $wpdb->prefix ."ptp_cache",
            array(
                'api_result' => $result,
                'cached_date' => ''.$nextts.''
            ),
            array(
                'api_call' => $url
            )
        );
        
        $wpdb->insert( 
            $wpdb->prefix ."ptp_cache_log",
            array(
                'api_call' => $url,
                'api_type' => 'update'
            )
        );
        
    }
	
}


/* Add Card To Cache
-------------------------------------------------------------*/ 

function populateCacheCard($result) {
	global $current_user,$wpdb;
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE api_id = '".$result['id']."'";
	$cache = $wpdb->get_row($q);
    
    $dt = new DateTime("now"); //first argument "must" be a string
    $nextts = $dt->format("Y-m-d H:i:s");
    //$next = date('Y-m-d H:i:s',strtotime('+'.get_option('cache_time').'',strtotime($nextts)));
        
    //$permalink = strtolower($result['name']);
    $permalink = strtolower(trim(str_replace("é","e",str_replace("δ","delta",str_replace("'","",str_replace("&","and",str_replace(".","-",str_replace(" ","-",$result['name']))))))));
    $permalink = preg_replace("/[^A-Za-z0-9]/", '-', $permalink);
    
    if(!$cache) {
        
        $attachment_id = php_upload_file_by_url( $result['images']['large'] );
    
        $wpdb->insert( 
            $wpdb->prefix ."ptp_cache_card",
            array(
                'api_id' => $result['id'],
                'name' => $result['name'],
                'permalink' => $permalink,
                'image_small' => $result['images']['small'],
                'image_large' => $result['images']['large'],
                'release_date' => $result['set']['releaseDate'],
                'card_set' => $result['set']['id'],
                'card_set_name' => $result['set']['name'],
                'card_series' => $result['set']['series'],
                'attachment_id' => $attachment_id,
                'card_number' => $result['number'],
                'cached_meta' => serialize($result)
            )
        );
        
    } else {
        
        php_update_file_by_url( $result['images']['large'], $cache->attachment_id );
            
        $wpdb->update( 
            $wpdb->prefix ."ptp_cache_card",
            array(
                'name' => $result['name'],
                'permalink' => $permalink,
                'image_small' => $result['images']['small'],
                'image_large' => $result['images']['large'],
                'release_date' => $result['set']['releaseDate'],
                'card_set' => $result['set']['id'],
                'card_set_name' => $result['set']['name'],
                'card_series' => $result['set']['series'],
                'card_number' => $result['number'],
                'cached_date' => ''.$nextts.'',
                'cached_meta' => serialize($result)
            ),
            array(
                'api_id' => $result['id']
            )
        );
        
    }
    
    //$data = unserialize($c->cached_meta);
            //echo '<pre>'.print_r($data,true).'</pre>';
            
    $types = $result['types'];

    //echo '<pre>'.print_r($types,true).'</pre>';

    foreach($types as $t) {

        $wpdb->insert( 
            $wpdb->prefix ."ptp_cache_card_types",
            array(
                'card_id' => $data['id'],
                'type' => $t
            )
        );

    }
	
}

/* Check Cache
-------------------------------------------------------------*/ 

function checkCache($url) {
	global $current_user,$wpdb;
    
    $dt = new DateTime("now"); //first argument "must" be a string
    
    $nextts = $dt->format("Y-m-d H:i:s");
    $prev = date('Y-m-d H:i:s',strtotime('-'.get_option('cache_time').'',strtotime($nextts)));
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache WHERE api_call = '".$url."' AND cached_date > '".$prev."'";
	$cache = $wpdb->get_row($q);
    
    if(!$cache) {
    
        return false;
        
    } else {
        
        return true;
        
    }
	
}

function checkPriceCache($card) {
	global $current_user,$wpdb;
    
    /*$tz = 'Europe/London';
    $dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
    
    $nextts = $dt->format("Y-m-d H:i:s");
    $prev = date('Y-m-d H:i:s',strtotime('-'.get_option('cache_time').'',strtotime($nextts)));*/
    $prev = date("Y-m-d H:i:s", strtotime("-".get_option('cache_time').""));
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND card_id = '".$card."' AND logged_date > '".$prev."'";
    $pricing = $wpdb->get_row($q);
    
    if($pricing) {
    
        return false;
        
    } else {
        
        return true;
        
    }
	
}

/* Get Cache
-------------------------------------------------------------*/ 

function getCache($url,$error = '') {
	global $current_user,$wpdb;
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache WHERE api_call = '".$url."'";
	$cache = $wpdb->get_row($q);
    
    return $cache->api_result;
    
    if($error != '') {
        
        $wpdb->insert( 
            $wpdb->prefix ."ptp_cache_log",
            array(
                'api_call' => $url,
                'api_type' => 'error'
            )
        );
        
    }
	
}

/* Get Cache Last Updated
-------------------------------------------------------------*/ 

function getCacheUpdate($url) {
	global $current_user,$wpdb;
    
    //date_default_timezone_set('GMT+1');
    
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache WHERE api_call = '".$url."' ORDER BY cached_date DESC LIMIT 1";
	$cache = $wpdb->get_row($q);
    
    $timestamp = strtotime($cache->cached_date);
    $dt = new DateTime("now"); //first argument "must" be a string
    $currts = $dt->format("Y-m-d H:i:s");
    $dt->setTimestamp($timestamp); //adjust the object to correct timestamp
    
    $nextts = $dt->format("Y-m-d H:i:s");
    $next = date('Y-m-d H:i:s',strtotime('+'.get_option('cache_time').'',strtotime($nextts)));
    
    //echo $next.' / '.$currts;
    
    $to_time = strtotime($next);
    $from_time = strtotime($currts);
    $minutes = round(abs($to_time - $from_time) / 60,0);
    
    if($to_time < $from_time) {
        $minutes = 0;
    }
    
    echo '<div class="margin5"></div><small><strong>Cache Last Updated</strong>: '.$dt->format("F jS, Y H:i").'<br/><em>Next update in '.$minutes.' minutes</em></small><div class="margin10"></div>';
	
}


/* Add Image to Media Library
-------------------------------------------------------------*/ 

function php_upload_file_by_url( $image_url ) {

	// it allows us to use download_url() and wp_handle_sideload() functions
	require_once( ABSPATH . 'wp-admin/includes/file.php' );

	// download to temp dir
	$temp_file = download_url( $image_url );

	if( is_wp_error( $temp_file ) ) {
		return false;
	}

	// move the temp file into the uploads directory
	$file = array(
		'name'     => basename( $image_url ),
		'type'     => mime_content_type( $temp_file ),
		'tmp_name' => $temp_file,
		'size'     => filesize( $temp_file ),
	);
	$sideload = wp_handle_sideload(
		$file,
		array(
			'test_form'   => false // no needs to check 'action' parameter
		)
	);

	if( ! empty( $sideload[ 'error' ] ) ) {
		// you may return error message if you want
		return false;
	}

	// it is time to add our uploaded image into WordPress media library
	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => $sideload[ 'url' ],
			'post_mime_type' => $sideload[ 'type' ],
			'post_title'     => basename( $sideload[ 'file' ] ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$sideload[ 'file' ]
	);

	if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return false;
	}

	// update medatata, regenerate image sizes
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
	);

	return $attachment_id;

}


/* Update Image in Media Library
-------------------------------------------------------------*/ 

function php_update_file_by_url( $image_url, $aid ) {

	// it allows us to use download_url() and wp_handle_sideload() functions
	require_once( ABSPATH . 'wp-admin/includes/file.php' );

	// download to temp dir
	$temp_file = download_url( $image_url );

	if( is_wp_error( $temp_file ) ) {
		return false;
	}

	// move the temp file into the uploads directory
	$file = array(
		'name'     => basename( $image_url ),
		'type'     => mime_content_type( $temp_file ),
		'tmp_name' => $temp_file,
		'size'     => filesize( $temp_file ),
	);
	$sideload = wp_handle_sideload(
		$file,
		array(
			'test_form'   => false // no needs to check 'action' parameter
		)
	);

	if( ! empty( $sideload[ 'error' ] ) ) {
		// you may return error message if you want
		return false;
	}

	// it is time to add our uploaded image into WordPress media library
    
    update_attached_file( $aid, $sideload[ 'file' ] );

	// update medatata, regenerate image sizes
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	wp_update_attachment_metadata(
		$aid,
		wp_generate_attachment_metadata( $aid, $sideload[ 'file' ] )
	);

	return $attachment_id;

}
?>