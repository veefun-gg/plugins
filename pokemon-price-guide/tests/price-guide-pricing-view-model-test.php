<?php

error_reporting( E_ALL );

set_error_handler(
    static function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
}

function add_shortcode( $hook, $callback ) {
}

function strip_shortcodes( $value ) {
    return preg_replace( '/\[[^\]]+\]/', '', (string) $value );
}

function wp_strip_all_tags( $value ) {
    return strip_tags( (string) $value );
}

function wp_trim_words( $value, $limit, $suffix = '…' ) {
    $words = preg_split( '/\s+/', trim( (string) $value ) );

    if ( count( $words ) <= $limit ) {
        return implode( ' ', $words );
    }

    return implode( ' ', array_slice( $words, 0, $limit ) ) . $suffix;
}

function esc_attr( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html__( $value, $domain = '' ) {
    return $value;
}

require_once dirname( __DIR__ ) . '/functions/06-page-template.php';
require_once dirname( __DIR__ ) . '/functions/08-related-editorial.php';

$tests_passed = 0;

function assert_same( $expected, $actual, $message ) {
    global $tests_passed;

    if ( $expected !== $actual ) {
        throw new RuntimeException(
            $message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true )
        );
    }

    $tests_passed++;
}

function assert_true( $actual, $message ) {
    assert_same( true, (bool) $actual, $message );
}

function assert_contains( $needle, $haystack, $message ) {
    assert_true( false !== strpos( $haystack, $needle ), $message );
}

function assert_not_contains( $needle, $haystack, $message ) {
    assert_true( false === strpos( $haystack, $needle ), $message );
}

function pricing_row( $id, $type, $price, $logged_date, $market = 'tcgplayer' ) {
    return (object) array(
        'id'              => $id,
        'price_market'    => $market,
        'price_type'      => $type,
        'price_avgmarket' => $price,
        'logged_date'     => $logged_date,
    );
}

class PriceGuideSelectOnlyWpdb {
    public $prefix = 'wp_';
    public $rows = array();
    public $queries = array();

    public function prepare( $query, ...$arguments ) {
        foreach ( $arguments as $argument ) {
            $replacement = "'" . str_replace( "'", "''", (string) $argument ) . "'";
            $query       = preg_replace( '/%[sd]/', $replacement, $query, 1 );
        }

        return $query;
    }

    public function get_results( $query ) {
        $this->queries[] = $query;
        return $this->rows;
    }
}

$model = ptp_priceguide_build_pricing_view_model( array() );
assert_same( false, $model['has_current_price'], 'empty rows have no current price' );
assert_same( false, $model['has_trend'], 'empty rows have no trend' );
assert_same( 'Current cached price unavailable.', $model['current_message'], 'empty rows explain current-price fallback' );
assert_same( 'Trend unavailable until two cached snapshots exist.', $model['trend_message'], 'empty rows explain trend fallback' );

$model = ptp_priceguide_build_pricing_view_model(
    array(
        pricing_row( 1, 'normal', null, '2026-08-10 12:00:00' ),
        pricing_row( 2, 'normal', 0, '2026-08-11 12:00:00' ),
        pricing_row( 3, '', 2.00, '2026-08-11 12:00:00' ),
        pricing_row( 4, 'normal', 3.00, '2026-08-11 12:00:00', 'cardmarket' ),
    )
);
assert_same( false, $model['has_current_price'], 'invalid cached rows fail closed' );

$model = ptp_priceguide_build_pricing_view_model(
    array( pricing_row( 10, 'normal', '1.25', '2026-08-11 09:00:00' ) )
);
assert_same( true, $model['has_current_price'], 'one snapshot provides a current price' );
assert_same( '$1.25', $model['current_display'], 'one snapshot formats the current price' );
assert_same( false, $model['has_trend'], 'one snapshot does not invent a trend' );
assert_same( 'Trend unavailable until two cached snapshots exist.', $model['trend_message'], 'one snapshot keeps explicit trend fallback' );

$model = ptp_priceguide_build_pricing_view_model(
    array(
        pricing_row( 20, 'normal', 1.00, '2026-08-10 09:00:00' ),
        pricing_row( 21, 'normal', 1.50, '2026-08-11 09:00:00' ),
    )
);
assert_same( '$1.50', $model['current_display'], 'newest snapshot is current' );
assert_same( true, $model['has_trend'], 'two same-type snapshots provide a trend' );
assert_same( 0.5, $model['delta'], 'upward delta is calculated' );
assert_same( 50.0, $model['delta_percent'], 'upward percentage is calculated' );
assert_same( '+$0.50 (+50.00%)', $model['delta_display'], 'upward delta is formatted' );
assert_same( 'up', $model['trend_direction'], 'upward direction is explicit' );

$model = ptp_priceguide_build_pricing_view_model(
    array(
        pricing_row( 30, 'normal', 1.00, '2026-08-11 09:00:00' ),
        pricing_row( 29, 'normal', 1.25, '2026-08-10 09:00:00' ),
    )
);
assert_same( -0.25, $model['delta'], 'downward delta is calculated' );
assert_same( -20.0, $model['delta_percent'], 'downward percentage is calculated' );
assert_same( '-$0.25 (-20.00%)', $model['delta_display'], 'downward delta is formatted' );
assert_same( 'down', $model['trend_direction'], 'downward direction is explicit' );

$model = ptp_priceguide_build_pricing_view_model(
    array(
        pricing_row( 32, 'normal', 1.00, '2026-08-11 09:00:00' ),
        pricing_row( 31, 'normal', 1.25, '2026-08-11 09:00:00' ),
    )
);
assert_same( true, $model['has_current_price'], 'duplicate-timestamp rows still provide a current price' );
assert_same( false, $model['has_trend'], 'duplicate-timestamp rows do not invent a prior snapshot' );

$model = ptp_priceguide_build_pricing_view_model(
    array(
        pricing_row( 40, 'holofoil', 4.50, '2026-08-11 09:00:00' ),
        pricing_row( 39, 'normal', 2.00, '2026-08-10 09:00:00' ),
    )
);
assert_same( 'normal', $model['price_type'], 'legacy normal-price preference is preserved' );
assert_same( '$2.00', $model['current_display'], 'preferred normal snapshot is displayed' );

$model = ptp_priceguide_build_pricing_view_model(
    array( pricing_row( 50, 'holofoil', 4.50, '2026-08-11 09:00:00' ) )
);
assert_same( 'holofoil', $model['price_type'], 'holofoil is used when normal pricing is absent' );
assert_same( 'Holofoil market price', $model['price_type_label'], 'holofoil label is honest' );

$database       = new PriceGuideSelectOnlyWpdb();
$database->rows = array(
    pricing_row( 61, 'normal', 2.75, '2026-08-11 10:00:00' ),
    pricing_row( 60, 'normal', 2.50, '2026-08-10 10:00:00' ),
);
$model = ptp_priceguide_get_pricing_view_model( "base1-52' unsafe", $database );
assert_same( 1, count( $database->queries ), 'view-model retrieval performs exactly one database query' );
assert_true( 1 === preg_match( '/^\s*SELECT\b/i', $database->queries[0] ), 'pricing query is SELECT-only' );
assert_same( 0, preg_match( '/\b(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP)\b/i', $database->queries[0] ), 'pricing query contains no write or DDL verb' );
assert_contains( "base1-52'' unsafe", $database->queries[0], 'card id is passed through prepared-query escaping' );
assert_same( '$2.75', $model['current_display'], 'SELECT result builds current pricing' );
assert_same( '+$0.25 (+10.00%)', $model['delta_display'], 'SELECT result builds prior-snapshot trend' );

$fallback_html = ptp_priceguide_render_pricing_view_model( 'base1-52', ptp_priceguide_unavailable_pricing_view_model() );
assert_contains( 'aria-busy="false"', $fallback_html, 'fallback markup is settled immediately' );
assert_contains( 'Current cached price unavailable.', $fallback_html, 'fallback markup includes current-price state' );
assert_contains( 'Trend unavailable until two cached snapshots exist.', $fallback_html, 'fallback markup includes trend state' );
assert_not_contains( 'lds-roller', $fallback_html, 'fallback markup has no spinner' );
assert_not_contains( 'updateCardsPricing', $fallback_html, 'fallback markup has no AJAX refresh call' );

$editorial_excerpt = ptp_priceguide_related_editorial_excerpt(
    (object) array(
        'post_excerpt' => '',
        'post_content' => '<p>Stored Machop editorial copy must not re-enter [sample]the_content[/sample] filters.</p>',
    ),
    7
);
assert_same(
    'Stored Machop editorial copy must not re-enter…',
    $editorial_excerpt,
    'editorial fallback excerpt is built directly from stored post content'
);

$manual_editorial_excerpt = ptp_priceguide_related_editorial_excerpt(
    (object) array(
        'post_excerpt' => 'Human-reviewed Machop summary.',
        'post_content' => 'Automatic content must not replace a manual excerpt.',
    )
);
assert_same(
    'Human-reviewed Machop summary.',
    $manual_editorial_excerpt,
    'manual editorial excerpt is preserved without content-filter recursion'
);

echo 'PASS assertions=', $tests_passed, PHP_EOL;
