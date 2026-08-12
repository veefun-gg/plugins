<?php

error_reporting( E_ALL );

set_error_handler(
    static function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);

$enabled_cron_mode = '1' === getenv( 'VEEFUN_TEST_ENABLE_UPDATER' );

if ( $enabled_cron_mode ) {
    define( 'PRIMETIME_PRICE_GUIDE_ENABLE_CRON_UPDATER', true );
    define( 'PRIMETIME_PRICE_GUIDE_UPDATER_MAX_PAGES', 1 );
    define( 'PRIMETIME_PRICE_GUIDE_UPDATER_PAGE_SIZE', 1 );
}

define( 'ABSPATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'veefun-price-guide-test-root' . DIRECTORY_SEPARATOR );

class WP_Error {
    private $code;

    public function __construct( $code ) {
        $this->code = $code;
    }

    public function get_error_code() {
        return $this->code;
    }
}

function is_wp_error( $value ) {
    return $value instanceof WP_Error;
}

$mock_actions = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    global $mock_actions;

    $mock_actions[ $hook ][ $priority ][ $callback ] = $accepted_args;
}

function has_action( $hook, $callback = false ) {
    global $mock_actions;

    if ( ! isset( $mock_actions[ $hook ] ) ) {
        return false;
    }

    foreach ( $mock_actions[ $hook ] as $priority => $callbacks ) {
        if ( false === $callback || isset( $callbacks[ $callback ] ) ) {
            return $priority;
        }
    }

    return false;
}

class PriceGuideMockWpdb {
    public $prefix = 'wp_';
    public $card_rows = array();
    public $card_count = 120;
    public $existing_types = array();
    public $inserts = array();
    public $updates = array();
    public $queries = array();

    public function prepare( $query, ...$args ) {
        foreach ( $args as $arg ) {
            $replacement = "'" . str_replace( "'", "''", (string) $arg ) . "'";
            $query       = preg_replace( '/%[sd]/', $replacement, $query, 1 );
        }

        return $query;
    }

    public function get_results( $query ) {
        $this->queries[] = $query;

        if ( false !== strpos( $query, 'ptp_cache_card WHERE api_id' ) ) {
            return $this->card_rows;
        }

        return array();
    }

    public function get_var( $query ) {
        $this->queries[] = $query;

        if ( false !== strpos( $query, 'SELECT COUNT(*)' ) ) {
            return $this->card_count;
        }

        if ( preg_match( "/card_id = '([^']*)' AND type = '([^']*)'/", $query, $matches ) ) {
            $key = $matches[1] . '|' . $matches[2];
            return isset( $this->existing_types[ $key ] ) ? 1 : null;
        }

        return null;
    }

    public function insert( $table, $data ) {
        $this->inserts[] = array(
            'table' => $table,
            'data'  => $data,
        );

        if ( substr( $table, -20 ) === 'ptp_cache_card_types' ) {
            $this->existing_types[ $data['card_id'] . '|' . $data['type'] ] = true;
        }

        return 1;
    }

    public function update( $table, $data, $where ) {
        $this->updates[] = array(
            'table' => $table,
            'data'  => $data,
            'where' => $where,
        );

        return 1;
    }
}

$mock_api_calls = 0;
$mock_api_response = array();

function getPokeCardsAll( $page, $per_page ) {
    global $mock_api_calls, $mock_api_response;

    $mock_api_calls++;
    return $mock_api_response;
}

$mock_media = array();

function reset_mock_media() {
    global $mock_media;

    if ( isset( $mock_media['temp_files'] ) ) {
        foreach ( $mock_media['temp_files'] as $temp_file ) {
            if ( is_file( $temp_file ) ) {
                unlink( $temp_file );
            }
        }
    }

    $mock_media = array(
        'attachment_type' => 'attachment',
        'download_error'  => false,
        'sideload_error'  => false,
        'attached_update' => true,
        'metadata'        => array(
            'file'   => 'fixture.png',
            'width'  => 1,
            'height' => 1,
        ),
        'metadata_update' => true,
        'download_calls'  => 0,
        'attached_calls'  => array(),
        'temp_files'      => array(),
    );
}

function get_post_type( $attachment_id ) {
    global $mock_media;

    return $attachment_id > 0 ? $mock_media['attachment_type'] : false;
}

function download_url( $image_url ) {
    global $mock_media;

    $mock_media['download_calls']++;

    if ( $mock_media['download_error'] ) {
        return new WP_Error( 'mock_download_error' );
    }

    $temp_file = tempnam( sys_get_temp_dir(), 'veefun-updater-' );
    file_put_contents( $temp_file, 'fixture-image' );
    $mock_media['temp_files'][] = $temp_file;

    return $temp_file;
}

function wp_handle_sideload( $file, $overrides ) {
    global $mock_media;

    if ( $mock_media['sideload_error'] ) {
        return array( 'error' => 'mock sideload error' );
    }

    return array(
        'file' => $file['tmp_name'],
        'url'  => 'https://local.test/' . $file['name'],
        'type' => $file['type'],
    );
}

function update_attached_file( $attachment_id, $file ) {
    global $mock_media;

    $mock_media['attached_calls'][] = array( $attachment_id, $file );
    return $mock_media['attached_update'];
}

function wp_generate_attachment_metadata( $attachment_id, $file ) {
    global $mock_media;

    return $mock_media['metadata'];
}

function wp_update_attachment_metadata( $attachment_id, $metadata ) {
    global $mock_media;

    return $mock_media['metadata_update'];
}

function wp_delete_file( $file ) {
    if ( is_file( $file ) ) {
        unlink( $file );
    }
}

require_once dirname( __DIR__ ) . '/functions/04-cache.php';
require_once dirname( __DIR__ ) . '/functions/02-functions.php';

$tests_passed = 0;

function assert_same( $expected, $actual, $label ) {
    global $tests_passed;

    if ( $expected !== $actual ) {
        throw new RuntimeException(
            $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true )
        );
    }

    $tests_passed++;
}

function assert_true( $actual, $label ) {
    assert_same( true, (bool) $actual, $label );
}

function card_fixture( $types = array( 'Fire' ) ) {
    return array(
        'id'     => 'fixture-1',
        'name'   => 'Fixture Card',
        'number' => '1',
        'images' => array(
            'small' => 'https://images.example.test/fixture_small.png',
            'large' => 'https://images.example.test/fixture_large.png',
        ),
        'set'    => array(
            'id'          => 'fixture-set',
            'name'        => 'Fixture Set',
            'series'      => 'Fixture Series',
            'releaseDate' => '2026/07/25',
        ),
        'types'  => $types,
    );
}

function existing_card_row( $fixture ) {
    return (object) array(
        'id'            => 1,
        'api_id'        => $fixture['id'],
        'image_large'   => isset( $fixture['images']['large'] ) ? $fixture['images']['large'] : '',
        'attachment_id' => 55,
    );
}

function type_inserts( $database ) {
    return array_values(
        array_filter(
            $database->inserts,
            static function ( $insert ) {
                return substr( $insert['table'], -20 ) === 'ptp_cache_card_types';
            }
        )
    );
}

function run_card_case( $fixture, $existing_types = array() ) {
    global $wpdb;

    $wpdb                 = new PriceGuideMockWpdb();
    $wpdb->card_rows      = array( existing_card_row( $fixture ) );
    $wpdb->existing_types = $existing_types;

    return populateCacheCard( $fixture );
}

reset_mock_media();

$fixture = card_fixture( array( 'Fire', 'Colorless' ) );
$result  = run_card_case( $fixture );
assert_true( $result['success'], 'multiple types payload succeeds' );
assert_same( 2, count( type_inserts( $wpdb ) ), 'multiple types insert two rows' );
assert_same( 'fixture-1', type_inserts( $wpdb )[0]['data']['card_id'], 'type row uses validated card id' );

$fixture = card_fixture(
    array( 'Type1', 'Type2', 'Type3', 'Type4', 'Type5', 'Type6', 'Type7', 'Type8', 'Type9', 'Type10', 'Type11' )
);
$result = run_card_case( $fixture );
assert_true( $result['success'], 'oversized type list is bounded' );
assert_same( 10, count( type_inserts( $wpdb ) ), 'type inserts are capped at ten rows per card' );

$fixture = card_fixture( array( 'Water' ) );
$result  = run_card_case( $fixture );
assert_true( $result['success'], 'single type payload succeeds' );
assert_same( 1, count( type_inserts( $wpdb ) ), 'single type inserts one row' );

$fixture = card_fixture();
unset( $fixture['types'] );
$result = run_card_case( $fixture );
assert_true( $result['success'], 'missing types payload succeeds' );
assert_same( 0, count( type_inserts( $wpdb ) ), 'missing types inserts no rows' );

$fixture          = card_fixture();
$fixture['types'] = null;
$result           = run_card_case( $fixture );
assert_true( $result['success'], 'null types payload succeeds' );
assert_same( 0, count( type_inserts( $wpdb ) ), 'null types inserts no rows' );

$fixture          = card_fixture();
$fixture['types'] = 'Fire';
$result           = run_card_case( $fixture );
assert_true( $result['success'], 'non-array types payload succeeds' );
assert_same( 0, count( type_inserts( $wpdb ) ), 'non-array types inserts no rows' );

$fixture = card_fixture();
unset( $fixture['id'] );
$wpdb   = new PriceGuideMockWpdb();
$result = populateCacheCard( $fixture );
assert_same( 'missing_card_id', $result['code'], 'missing card id is rejected' );
assert_same( 0, count( $wpdb->inserts ) + count( $wpdb->updates ), 'missing card id performs no writes' );

$fixture       = card_fixture();
$fixture['id'] = 'invalid card id';
$wpdb          = new PriceGuideMockWpdb();
$result        = populateCacheCard( $fixture );
assert_same( 'invalid_card_id', $result['code'], 'invalid card id is rejected' );
assert_same( 0, count( $wpdb->inserts ) + count( $wpdb->updates ), 'invalid card id performs no writes' );

$wpdb   = new PriceGuideMockWpdb();
$result = populateCacheCard( (object) array( 'id' => 'fixture-1' ) );
assert_same( 'invalid_card_payload', $result['code'], 'non-array card payload is rejected' );
assert_same( 0, count( $wpdb->queries ), 'non-array card payload performs no database query' );

$fixture         = card_fixture();
$wpdb            = new PriceGuideMockWpdb();
$wpdb->card_rows = array( existing_card_row( $fixture ), existing_card_row( $fixture ) );
$result          = populateCacheCard( $fixture );
assert_same( 'duplicate_card_records', $result['code'], 'duplicate card records stop the update' );
assert_same( 0, count( $wpdb->inserts ) + count( $wpdb->updates ), 'duplicate card records perform no writes' );

$fixture                    = card_fixture();
$fixture['images']['large'] = null;
$result                     = run_card_case( $fixture );
assert_true( $result['success'], 'missing image URL does not abort card update' );
assert_same( 'missing_image_url', $result['media']['code'], 'missing image URL is reported' );
assert_same( 0, $mock_media['download_calls'], 'missing image URL does not download' );

reset_mock_media();
$mock_media['download_error'] = true;
$result = php_update_file_by_url( 'https://images.example.test/failure.png', 55 );
assert_same( 'download_failed', $result['code'], 'download failure is reported' );
assert_same( 55, $result['attachment_id'], 'download failure retains attachment identity' );

reset_mock_media();
$mock_media['sideload_error'] = true;
$result = php_update_file_by_url( 'https://images.example.test/failure.png', 55 );
assert_same( 'sideload_failed', $result['code'], 'sideload failure is reported' );

reset_mock_media();
$mock_media['attached_update'] = false;
$result = php_update_file_by_url( 'https://images.example.test/failure.png', 55 );
assert_same( 'attached_file_update_failed', $result['code'], 'attached file update failure is reported' );

reset_mock_media();
$mock_media['metadata'] = new WP_Error( 'mock_metadata_error' );
$result = php_update_file_by_url( 'https://images.example.test/failure.png', 55 );
assert_same( 'metadata_generation_failed', $result['code'], 'metadata generation failure is reported' );

reset_mock_media();
$mock_media['metadata_update'] = false;
$result = php_update_file_by_url( 'https://images.example.test/failure.png', 55 );
assert_same( 'metadata_update_failed', $result['code'], 'metadata update failure is reported' );

reset_mock_media();
$result = php_update_file_by_url( 'https://images.example.test/success.png', 55 );
assert_true( $result['success'], 'existing attachment update succeeds' );
assert_same( 'updated', $result['code'], 'existing attachment update reports success' );
assert_same( 55, $result['attachment_id'], 'existing attachment id is returned' );
assert_same( 1, count( $mock_media['attached_calls'] ), 'existing attachment is updated once' );

reset_mock_media();
$result = php_update_file_by_url( 'https://images.example.test/success.png', 0 );
assert_same( 'invalid_attachment_id', $result['code'], 'invalid attachment id is rejected' );
assert_same( 0, $mock_media['download_calls'], 'invalid attachment id does not download' );

$fixture = card_fixture( array( 'Fire', 'Fire', '', null ) );
$result  = run_card_case( $fixture, array( 'fixture-1|Fire' => true ) );
assert_true( $result['success'], 'duplicate type payload succeeds' );
assert_same( 0, count( type_inserts( $wpdb ) ), 'duplicate existing type is not inserted' );
assert_same( 1, $result['types_skipped'], 'duplicate existing type is reported as skipped' );

$registered_priority = has_action( 'primetime_update_cards_cron', 'cron_primetime_update_cards_4ccd3826' );
assert_same( 10, $registered_priority, 'legacy cron callback is registered' );

primetime_price_guide_register_updater_callback();
$registered_callbacks = $mock_actions['primetime_update_cards_cron'][10];
assert_same( 1, count( $registered_callbacks ), 'repeated registration remains idempotent' );

$admin_script = file_get_contents( dirname( __DIR__ ) . '/admin/scripts/update-cards.php' );
assert_true( is_string( $admin_script ), 'admin updater source is readable' );
assert_same( 1, substr_count( $admin_script, 'primetime_price_guide_run_admin_updater();' ), 'admin updater uses shared bounded runner once' );
assert_same( 0, substr_count( $admin_script, 'getPokeCardsAll(' ), 'admin updater has no direct API call' );
assert_same( 0, substr_count( $admin_script, 'populateCacheCard(' ), 'admin updater has no direct persistence call' );
assert_same( 1, substr_count( $admin_script, "echo 'EOL';" ), 'admin updater stops the legacy browser loop' );
$guard_position  = strpos( $admin_script, "require_once '_guard.php';" );
$runner_position = strpos( $admin_script, 'primetime_price_guide_run_admin_updater();' );
assert_true(
    false !== $guard_position && false !== $runner_position && $guard_position < $runner_position,
    'admin updater retains authorization guard before bounded runner'
);

$wpdb              = new PriceGuideMockWpdb();
$fixture           = card_fixture( array() );
$wpdb->card_rows   = array( existing_card_row( $fixture ) );
$mock_api_response = array( $fixture );
$mock_api_calls    = 0;
$cron_result       = cron_primetime_update_cards_4ccd3826();

if ( $enabled_cron_mode ) {
    assert_same( 'limit_reached', $cron_result['status'], 'enabled cron stops at explicit page limit' );
    assert_same( 1, $cron_result['api_requests'], 'enabled cron makes one mocked API request' );
    assert_same( 1, $cron_result['cards_processed'], 'enabled cron processes one bounded mocked card' );
    assert_same( 1, $mock_api_calls, 'enabled cron does not request a second page' );

    $wpdb              = new PriceGuideMockWpdb();
    $wpdb->card_rows   = array( existing_card_row( $fixture ) );
    $mock_api_response = array( $fixture );
    $mock_api_calls    = 0;
    reset_mock_media();

    $admin_result = primetime_price_guide_run_admin_updater();
    assert_same( 'limit_reached', $admin_result['status'], 'enabled admin updater stops at explicit page limit' );
    assert_same( 1, $admin_result['api_requests'], 'enabled admin updater makes one mocked API request' );
    assert_same( 1, $admin_result['cards_processed'], 'enabled admin updater processes one bounded mocked card' );
    assert_same( 1, $mock_api_calls, 'enabled admin updater does not request a second page' );
    assert_same( 1, count( $wpdb->updates ), 'enabled admin updater performs one mocked card update' );
    assert_same( 0, $mock_media['download_calls'], 'enabled admin updater skips unchanged mocked media' );
} else {
    assert_same( 'disabled', $cron_result['status'], 'cron is inert by default' );
    assert_same( 0, $mock_api_calls, 'disabled cron makes no API request' );
    assert_same( 0, count( $wpdb->queries ), 'disabled cron makes no database query' );
    assert_same( 0, count( $wpdb->inserts ), 'disabled cron makes no database insert' );
    assert_same( 0, count( $wpdb->updates ), 'disabled cron makes no database update' );
    assert_same( 0, $mock_media['download_calls'], 'disabled cron makes no media request' );

    $wpdb              = new PriceGuideMockWpdb();
    $wpdb->card_rows   = array( existing_card_row( $fixture ) );
    $mock_api_response = array( $fixture );
    $mock_api_calls    = 0;
    reset_mock_media();

    $admin_result = primetime_price_guide_run_admin_updater();
    assert_same( 'disabled', $admin_result['status'], 'admin updater is inert by default' );
    assert_same( 0, $mock_api_calls, 'disabled admin updater makes no API request' );
    assert_same( 0, count( $wpdb->queries ), 'disabled admin updater makes no database query' );
    assert_same( 0, count( $wpdb->inserts ), 'disabled admin updater makes no database insert' );
    assert_same( 0, count( $wpdb->updates ), 'disabled admin updater makes no database update' );
    assert_same( 0, $mock_media['download_calls'], 'disabled admin updater makes no media request' );
}

reset_mock_media();

echo 'PASS mode=', $enabled_cron_mode ? 'enabled' : 'disabled', ' assertions=', $tests_passed, PHP_EOL;
