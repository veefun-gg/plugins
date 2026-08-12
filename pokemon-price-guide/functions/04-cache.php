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

function primetime_price_guide_updater_result( $success, $code, $details = array() ) {
    return array_merge(
        array(
            'success' => (bool) $success,
            'code'    => (string) $code,
        ),
        $details
    );
}

function primetime_price_guide_normalize_string( $value ) {
    if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
        return '';
    }

    $value = trim( (string) $value );

    if ( '' === $value || preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
        return '';
    }

    return $value;
}

function primetime_price_guide_normalize_media_url( $value ) {
    $url = primetime_price_guide_normalize_string( $value );

    if ( '' === $url || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return '';
    }

    $scheme = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );

    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
        return '';
    }

    return $url;
}

function primetime_price_guide_normalize_card_payload( $result ) {
    if ( ! is_array( $result ) ) {
        return primetime_price_guide_updater_result( false, 'invalid_card_payload' );
    }

    $card_id = primetime_price_guide_normalize_string( isset( $result['id'] ) ? $result['id'] : '' );

    if ( '' === $card_id ) {
        return primetime_price_guide_updater_result( false, 'missing_card_id' );
    }

    if ( strlen( $card_id ) > 191 || ! preg_match( '/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/D', $card_id ) ) {
        return primetime_price_guide_updater_result( false, 'invalid_card_id' );
    }

    $name = primetime_price_guide_normalize_string( isset( $result['name'] ) ? $result['name'] : '' );

    if ( '' === $name ) {
        return primetime_price_guide_updater_result( false, 'missing_card_name' );
    }

    $set = isset( $result['set'] ) && is_array( $result['set'] ) ? $result['set'] : array();

    $set_id      = primetime_price_guide_normalize_string( isset( $set['id'] ) ? $set['id'] : '' );
    $set_name    = primetime_price_guide_normalize_string( isset( $set['name'] ) ? $set['name'] : '' );
    $set_series  = primetime_price_guide_normalize_string( isset( $set['series'] ) ? $set['series'] : '' );
    $release_raw = primetime_price_guide_normalize_string( isset( $set['releaseDate'] ) ? $set['releaseDate'] : '' );

    if ( '' === $set_id || '' === $set_name || '' === $set_series || '' === $release_raw ) {
        return primetime_price_guide_updater_result( false, 'invalid_card_set' );
    }

    $release_date = str_replace( '/', '-', $release_raw );
    $release      = DateTime::createFromFormat( '!Y-m-d', $release_date );

    if ( false === $release || $release->format( 'Y-m-d' ) !== $release_date ) {
        return primetime_price_guide_updater_result( false, 'invalid_release_date' );
    }

    $card_number = primetime_price_guide_normalize_string( isset( $result['number'] ) ? $result['number'] : '' );

    if ( '' === $card_number ) {
        return primetime_price_guide_updater_result( false, 'missing_card_number' );
    }

    $images      = isset( $result['images'] ) && is_array( $result['images'] ) ? $result['images'] : array();
    $image_small = primetime_price_guide_normalize_media_url( isset( $images['small'] ) ? $images['small'] : '' );
    $image_large = primetime_price_guide_normalize_media_url( isset( $images['large'] ) ? $images['large'] : '' );
    $types       = array();
    $seen_types  = array();

    if ( isset( $result['types'] ) && is_array( $result['types'] ) ) {
        foreach ( $result['types'] as $type ) {
            $type = primetime_price_guide_normalize_string( $type );

            if ( '' === $type || strlen( $type ) > 100 || isset( $seen_types[ $type ] ) ) {
                continue;
            }

            $seen_types[ $type ] = true;
            $types[]             = $type;

            if ( count( $types ) >= 10 ) {
                break;
            }
        }
    }

    $normalized                       = $result;
    $normalized['id']                 = $card_id;
    $normalized['name']               = $name;
    $normalized['number']             = $card_number;
    $normalized['images']             = $images;
    $normalized['images']['small']    = $image_small;
    $normalized['images']['large']    = $image_large;
    $normalized['set']                = $set;
    $normalized['set']['id']          = $set_id;
    $normalized['set']['name']        = $set_name;
    $normalized['set']['series']      = $set_series;
    $normalized['set']['releaseDate'] = $release_date;
    $normalized['types']              = $types;

    return primetime_price_guide_updater_result(
        true,
        'valid_card_payload',
        array( 'card' => $normalized )
    );
}

function populateCacheCard($result) {
	global $current_user,$wpdb;

    $validation = primetime_price_guide_normalize_card_payload( $result );

    if ( empty( $validation['success'] ) ) {
        return $validation;
    }

    $result = $validation['card'];
    $q      = $wpdb->prepare(
        'SELECT * FROM ' . $wpdb->prefix . 'ptp_cache_card WHERE api_id = %s ORDER BY id ASC LIMIT 2',
        $result['id']
    );
    $rows   = $wpdb->get_results( $q );

    if ( ! is_array( $rows ) ) {
        return primetime_price_guide_updater_result(
            false,
            'card_lookup_failed',
            array( 'card_id' => $result['id'] )
        );
    }

    if ( count( $rows ) > 1 ) {
        return primetime_price_guide_updater_result(
            false,
            'duplicate_card_records',
            array( 'card_id' => $result['id'] )
        );
    }

    $cache = empty( $rows ) ? null : $rows[0];
    
    $dt = new DateTime("now"); //first argument "must" be a string
    $nextts = $dt->format("Y-m-d H:i:s");
    //$next = date('Y-m-d H:i:s',strtotime('+'.get_option('cache_time').'',strtotime($nextts)));
        
    //$permalink = strtolower($result['name']);
    $permalink = strtolower(trim(str_replace("é","e",str_replace("δ","delta",str_replace("'","",str_replace("&","and",str_replace(".","-",str_replace(" ","-",$result['name']))))))));
    $permalink = preg_replace("/[^A-Za-z0-9]/", '-', $permalink);

    $media_result = primetime_price_guide_updater_result(
        false,
        'media_not_attempted',
        array( 'attachment_id' => 0 )
    );
    
    if(!$cache) {
        $attachment_id = 0;

        if ( '' === $result['images']['large'] ) {
            $media_result = primetime_price_guide_updater_result(
                false,
                'missing_image_url',
                array( 'attachment_id' => 0 )
            );
        } else {
            $uploaded_attachment_id = php_upload_file_by_url( $result['images']['large'] );

            if ( is_numeric( $uploaded_attachment_id ) && (int) $uploaded_attachment_id > 0 ) {
                $attachment_id = (int) $uploaded_attachment_id;
                $media_result  = primetime_price_guide_updater_result(
                    true,
                    'uploaded',
                    array( 'attachment_id' => $attachment_id )
                );
            } else {
                $media_result = primetime_price_guide_updater_result(
                    false,
                    'upload_failed',
                    array( 'attachment_id' => 0 )
                );
            }
        }

        $inserted = $wpdb->insert(
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

        if ( false === $inserted ) {
            return primetime_price_guide_updater_result(
                false,
                'card_insert_failed',
                array(
                    'card_id' => $result['id'],
                    'media'   => $media_result,
                )
            );
        }

        $card_action = 'inserted';
        
    } else {
        $attachment_id = isset( $cache->attachment_id ) ? (int) $cache->attachment_id : 0;
        $previous_image = primetime_price_guide_normalize_media_url(
            isset( $cache->image_large ) ? $cache->image_large : ''
        );

        if ( '' === $result['images']['large'] ) {
            $media_result = primetime_price_guide_updater_result(
                false,
                'missing_image_url',
                array( 'attachment_id' => $attachment_id )
            );
        } elseif ( $previous_image === $result['images']['large'] && $attachment_id > 0 ) {
            $media_result = primetime_price_guide_updater_result(
                true,
                'unchanged',
                array( 'attachment_id' => $attachment_id )
            );
        } else {
            $media_result = php_update_file_by_url( $result['images']['large'], $attachment_id );
        }
            
        $updated = $wpdb->update(
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

        if ( false === $updated ) {
            return primetime_price_guide_updater_result(
                false,
                'card_update_failed',
                array(
                    'card_id' => $result['id'],
                    'media'   => $media_result,
                )
            );
        }

        $card_action = 'updated';
        
    }

    $types_inserted = 0;
    $types_skipped  = 0;
    $types_failed   = 0;

    foreach ( $result['types'] as $type ) {
        $type_id = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT id FROM ' . $wpdb->prefix . 'ptp_cache_card_types WHERE card_id = %s AND type = %s LIMIT 1',
                $result['id'],
                $type
            )
        );

        if ( $type_id ) {
            $types_skipped++;
            continue;
        }

        $type_inserted = $wpdb->insert(
            $wpdb->prefix . 'ptp_cache_card_types',
            array(
                'card_id' => $result['id'],
                'type'    => $type,
            )
        );

        if ( false === $type_inserted ) {
            $types_failed++;
        } else {
            $types_inserted++;
        }
    }

    return primetime_price_guide_updater_result(
        true,
        'card_' . $card_action,
        array(
            'card_id'        => $result['id'],
            'media'          => $media_result,
            'types_inserted' => $types_inserted,
            'types_skipped'  => $types_skipped,
            'types_failed'   => $types_failed,
        )
    );
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

function primetime_price_guide_delete_temporary_file( $file ) {
    if ( ! is_string( $file ) || '' === $file || ! is_file( $file ) ) {
        return;
    }

    if ( function_exists( 'wp_delete_file' ) ) {
        wp_delete_file( $file );
        return;
    }

    unlink( $file );
}

function php_update_file_by_url( $image_url, $aid ) {
    $image_url = primetime_price_guide_normalize_media_url( $image_url );

    if ( '' === $image_url ) {
        return primetime_price_guide_updater_result(
            false,
            'missing_image_url',
            array( 'attachment_id' => 0 )
        );
    }

    if ( is_bool( $aid ) || ! is_numeric( $aid ) || (int) $aid < 1 ) {
        return primetime_price_guide_updater_result(
            false,
            'invalid_attachment_id',
            array( 'attachment_id' => 0 )
        );
    }

    $aid = (int) $aid;

    if ( 'attachment' !== get_post_type( $aid ) ) {
        return primetime_price_guide_updater_result(
            false,
            'attachment_not_found',
            array( 'attachment_id' => $aid )
        );
    }

    // It allows us to use download_url() and wp_handle_sideload() functions.
    if ( ! function_exists( 'download_url' ) || ! function_exists( 'wp_handle_sideload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    $temp_file = download_url( $image_url );

    if ( is_wp_error( $temp_file ) || ! is_string( $temp_file ) || ! is_file( $temp_file ) ) {
        return primetime_price_guide_updater_result(
            false,
            'download_failed',
            array( 'attachment_id' => $aid )
        );
    }

    $image_path = (string) parse_url( $image_url, PHP_URL_PATH );
    $file_name  = basename( $image_path );

    if ( '' === $file_name || '.' === $file_name ) {
        primetime_price_guide_delete_temporary_file( $temp_file );

        return primetime_price_guide_updater_result(
            false,
            'invalid_image_filename',
            array( 'attachment_id' => $aid )
        );
    }

    $mime_type = mime_content_type( $temp_file );
    $file      = array(
        'name'     => $file_name,
        'type'     => false === $mime_type ? 'application/octet-stream' : $mime_type,
        'tmp_name' => $temp_file,
        'size'     => filesize( $temp_file ),
    );
    $sideload  = wp_handle_sideload(
        $file,
        array(
            'test_form' => false,
        )
    );

    if (
        ! is_array( $sideload )
        || ! empty( $sideload['error'] )
        || empty( $sideload['file'] )
        || ! is_file( $sideload['file'] )
    ) {
        primetime_price_guide_delete_temporary_file( $temp_file );

        return primetime_price_guide_updater_result(
            false,
            'sideload_failed',
            array( 'attachment_id' => $aid )
        );
    }

    if ( false === update_attached_file( $aid, $sideload['file'] ) ) {
        return primetime_price_guide_updater_result(
            false,
            'attached_file_update_failed',
            array( 'attachment_id' => $aid )
        );
    }

    // Update metadata and regenerate image sizes.
    if ( ! function_exists( 'wp_generate_attachment_metadata' ) || ! function_exists( 'wp_update_attachment_metadata' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
    }

    $metadata = wp_generate_attachment_metadata( $aid, $sideload['file'] );

    if ( is_wp_error( $metadata ) || ! is_array( $metadata ) || empty( $metadata ) ) {
        return primetime_price_guide_updater_result(
            false,
            'metadata_generation_failed',
            array( 'attachment_id' => $aid )
        );
    }

    if ( false === wp_update_attachment_metadata( $aid, $metadata ) ) {
        return primetime_price_guide_updater_result(
            false,
            'metadata_update_failed',
            array( 'attachment_id' => $aid )
        );
    }

    return primetime_price_guide_updater_result(
        true,
        'updated',
        array( 'attachment_id' => $aid )
    );

}
?>
