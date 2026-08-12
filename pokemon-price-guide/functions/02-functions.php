<?php
/* CREATE HOOK TO UPDATE SETS */

function primetime_price_guide_updater_is_enabled() {
    return defined( 'PRIMETIME_PRICE_GUIDE_ENABLE_CRON_UPDATER' )
        && true === PRIMETIME_PRICE_GUIDE_ENABLE_CRON_UPDATER;
}

function primetime_price_guide_updater_max_pages() {
    if ( ! defined( 'PRIMETIME_PRICE_GUIDE_UPDATER_MAX_PAGES' ) ) {
        return 0;
    }

    $limit = PRIMETIME_PRICE_GUIDE_UPDATER_MAX_PAGES;

    if ( is_string( $limit ) && ctype_digit( $limit ) ) {
        $limit = (int) $limit;
    }

    if ( ! is_int( $limit ) || $limit < 1 ) {
        return 0;
    }

    return $limit;
}

function primetime_price_guide_updater_page_size() {
    if ( ! defined( 'PRIMETIME_PRICE_GUIDE_UPDATER_PAGE_SIZE' ) ) {
        return 10;
    }

    $page_size = PRIMETIME_PRICE_GUIDE_UPDATER_PAGE_SIZE;

    if ( is_string( $page_size ) && ctype_digit( $page_size ) ) {
        $page_size = (int) $page_size;
    }

    if ( ! is_int( $page_size ) || $page_size < 1 || $page_size > 10 ) {
        return 0;
    }

    return $page_size;
}

function primetime_price_guide_run_bounded_updater() {
    global $wpdb;

    $summary = array(
        'status'          => 'disabled',
        'api_requests'    => 0,
        'cards_received'  => 0,
        'cards_processed' => 0,
        'cards_skipped'   => 0,
    );

    if ( ! primetime_price_guide_updater_is_enabled() ) {
        return $summary;
    }

    $max_pages = primetime_price_guide_updater_max_pages();

    if ( $max_pages < 1 ) {
        $summary['status'] = 'invalid_page_limit';
        return $summary;
    }

    $page_size = primetime_price_guide_updater_page_size();

    if ( $page_size < 1 ) {
        $summary['status'] = 'invalid_page_size';
        return $summary;
    }

    $total     = (int) $wpdb->get_var(
        'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'ptp_cache_card'
    );
    $page      = (int) floor( $total / $page_size );

    $summary['status']     = 'limit_reached';
    $summary['start_page'] = $page;
    $summary['max_pages']  = $max_pages;
    $summary['page_size']  = $page_size;

    for ( $page_offset = 0; $page_offset < $max_pages; $page_offset++ ) {
        $current_page = $page + $page_offset;
        $summary['api_requests']++;

        try {
            $api = getPokeCardsAll( $current_page, $page_size );
        } catch ( Throwable $error ) {
            $summary['status'] = 'api_failure';
            break;
        }

        if ( $api instanceof Traversable ) {
            $api = iterator_to_array( $api );
        }

        if ( ! is_array( $api ) ) {
            $summary['status'] = 'invalid_api_response';
            break;
        }

        if ( empty( $api ) ) {
            $summary['status'] = 'complete';
            break;
        }

        $cards_in_page            = count( $api );
        $summary['cards_received'] += $cards_in_page;

        foreach ( $api as $card ) {
            if ( is_object( $card ) && is_callable( array( $card, 'toArray' ) ) ) {
                try {
                    $card = $card->toArray();
                } catch ( Throwable $error ) {
                    $summary['cards_skipped']++;
                    continue;
                }
            }

            if ( ! is_array( $card ) ) {
                $summary['cards_skipped']++;
                continue;
            }

            try {
                $card_result = populateCacheCard( $card );
            } catch ( Throwable $error ) {
                $summary['cards_skipped']++;
                continue;
            }

            if ( is_array( $card_result ) && ! empty( $card_result['success'] ) ) {
                $summary['cards_processed']++;
            } else {
                $summary['cards_skipped']++;
            }
        }

        if ( $cards_in_page < $page_size ) {
            $summary['status'] = 'complete';
            break;
        }
    }

    return $summary;
}

function cron_primetime_update_cards_4ccd3826() {
    return primetime_price_guide_run_bounded_updater();
}

function primetime_price_guide_run_admin_updater() {
    return primetime_price_guide_run_bounded_updater();
}

function primetime_price_guide_register_updater_callback() {
    if ( false === has_action( 'primetime_update_cards_cron', 'cron_primetime_update_cards_4ccd3826' ) ) {
        add_action( 'primetime_update_cards_cron', 'cron_primetime_update_cards_4ccd3826', 10, 0 );
    }
}

primetime_price_guide_register_updater_callback();
?>
