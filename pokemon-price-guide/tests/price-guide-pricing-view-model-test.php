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

function esc_url( $value ) {
    return (string) $value;
}

function sanitize_title( $value ) {
    $value = strtolower( trim( (string) $value ) );
    $value = preg_replace( '/[^a-z0-9]+/', '-', $value );
    return trim( $value, '-' );
}

function post_type_exists( $post_type ) {
    return 'pokedex' === $post_type && false !== ( $GLOBALS['price_guide_post_type_available'] ?? true );
}

function get_permalink( $post_id ) {
    return 'http://veefun.local/pokedex/66-machop/';
}

class WP_Query {
    public $posts = array();

    public function __construct( $arguments ) {
        $GLOBALS['price_guide_subject_query_arguments'] = $arguments;
        $this->posts = $GLOBALS['price_guide_subject_query_posts'] ?? array( 1524 );
    }
}

require_once dirname( __DIR__ ) . '/functions/06-page-template.php';
require_once dirname( __DIR__ ) . '/functions/08-related-editorial.php';

$page_template_source = file_get_contents( dirname( __DIR__ ) . '/functions/06-page-template.php' );
$plugin_css            = file_get_contents( dirname( __DIR__ ) . '/css/plugin.css' );

if ( false === $page_template_source || false === $plugin_css ) {
    throw new RuntimeException( 'Unable to read the Price Guide identity contract files.' );
}

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

$card_data = array(
    'id'     => 'base1-52',
    'set'    => array(
        'id'           => 'base1',
        'name'         => 'Base',
        'printedTotal' => '102',
    ),
    'number'                 => '52',
    'nationalPokedexNumbers' => array( 66 ),
);
assert_same( 'Base · 52 / 102', ptp_priceguide_printing_scope_label( $card_data ), 'printing label keeps exact set and number together' );
assert_same( 'Machop trading card from Base, number 52 of 102.', ptp_priceguide_card_image_alt( $card_data, 'Machop' ), 'primary image alt identifies the exact card' );

$identity_html = ptp_priceguide_render_card_identity( $card_data, 'Machop', 'base1-52' );
assert_same( 'pokedex', $GLOBALS['price_guide_subject_query_arguments']['post_type'], 'subject lookup stays within the Pokédex object type' );
assert_same( 'publish', $GLOBALS['price_guide_subject_query_arguments']['post_status'], 'subject lookup exposes only published subjects' );
assert_same( 2, $GLOBALS['price_guide_subject_query_arguments']['posts_per_page'], 'subject lookup detects ambiguous exact matches' );
assert_same( '66-machop', ptp_priceguide_subject_slug( $card_data, 'Machop' ), 'subject slug preserves the card Pokédex number and name' );
assert_same( '66-machop', $GLOBALS['price_guide_subject_query_arguments']['name'], 'subject lookup requires an exact numbered subject slug' );
assert_contains( 'aria-label="Card identity"', $identity_html, 'identity region has an accessible name' );
assert_contains( 'pg-object-identity vf-c-object-identity vf-o-stack has-relationship', $identity_html, 'identity pairs local, shared, layout, and state classes' );
assert_contains( '<dt>Set</dt><dd><data value="base1">Base</data></dd>', $identity_html, 'identity exposes set id base1 with visible Base name' );
assert_contains( '<dt>Card number</dt><dd><data value="base1-52">52/102</data></dd>', $identity_html, 'identity exposes card id base1-52 with visible collector number' );
assert_contains( 'href="http://veefun.local/pokedex/66-machop/"', $identity_html, 'exact subject match provides a direct return path' );
assert_contains( 'View Machop — Pokémon #066', $identity_html, 'subject return path names Machop and its display id' );
assert_contains( 'vf-c-relationship-link vf-c-action', $identity_html, 'return path adopts shared relationship and action roles' );

$ambiguous_card_data = $card_data;
$ambiguous_card_data['nationalPokedexNumbers'] = array( 66, 67 );
assert_same( '', ptp_priceguide_subject_slug( $ambiguous_card_data, 'Machop & Machoke' ), 'multi-subject cards fail closed instead of guessing a destination' );
$ambiguous_identity_html = ptp_priceguide_render_card_identity( $ambiguous_card_data, 'Machop & Machoke', 'base1-52' );
assert_not_contains( '<a ', $ambiguous_identity_html, 'ambiguous cards do not render a guessed subject link' );
assert_contains( 'Related Pokémon subject unavailable for this printing.', $ambiguous_identity_html, 'ambiguous cards expose the exact relationship fallback' );
assert_contains( 'vf-c-empty-state is-unavailable', $ambiguous_identity_html, 'relationship fallback exposes shared unavailable state' );

$GLOBALS['price_guide_subject_query_posts'] = array();
$missing_subject_data = $card_data;
$missing_subject_data['nationalPokedexNumbers'] = array( 67 );
$missing_identity_html = ptp_priceguide_render_card_identity( $missing_subject_data, 'Machoke', 'base1-34' );
assert_not_contains( '<a ', $missing_identity_html, 'missing published subject does not produce a guessed link' );
assert_contains( 'Related Pokémon subject unavailable for this printing.', $missing_identity_html, 'missing subject exposes the exact relationship fallback' );

$GLOBALS['price_guide_subject_query_posts'] = array( 1524, 1525 );
$duplicate_subject_data = $card_data;
$duplicate_subject_data['nationalPokedexNumbers'] = array( 68 );
$duplicate_identity_html = ptp_priceguide_render_card_identity( $duplicate_subject_data, 'Machamp', 'base1-8' );
assert_not_contains( '<a ', $duplicate_identity_html, 'duplicate exact subjects do not produce an ambiguous link' );
assert_contains( 'Related Pokémon subject unavailable for this printing.', $duplicate_identity_html, 'duplicate exact subjects expose the approved fallback' );

$GLOBALS['price_guide_subject_query_posts'] = array( 1524 );
$GLOBALS['price_guide_post_type_available'] = false;
$unavailable_type_data = $card_data;
$unavailable_type_data['nationalPokedexNumbers'] = array( 69 );
$unavailable_type_html = ptp_priceguide_render_card_identity( $unavailable_type_data, 'Bellsprout', 'base1-49' );
assert_not_contains( '<a ', $unavailable_type_html, 'unavailable Pokédex type does not produce a guessed link' );
assert_contains( 'Related Pokémon subject unavailable for this printing.', $unavailable_type_html, 'unavailable Pokédex type exposes the approved fallback' );
$GLOBALS['price_guide_post_type_available'] = true;

$fallback_html = ptp_priceguide_render_pricing_view_model( 'base1-52', ptp_priceguide_unavailable_pricing_view_model() );
assert_contains( 'aria-busy="false"', $fallback_html, 'fallback markup is settled immediately' );
assert_contains( 'Current cached price unavailable.', $fallback_html, 'fallback markup includes current-price state' );
assert_contains( 'Trend unavailable until two cached snapshots exist.', $fallback_html, 'fallback markup includes trend state' );
assert_contains( 'vf-c-evidence-status vf-o-stack is-unavailable', $fallback_html, 'fallback pricing exposes shared evidence state' );
assert_contains( 'vf-c-empty-state is-unavailable', $fallback_html, 'fallback messages expose shared empty state' );
assert_not_contains( 'lds-roller', $fallback_html, 'fallback markup has no spinner' );
assert_not_contains( 'updateCardsPricing', $fallback_html, 'fallback markup has no AJAX refresh call' );

$available_model = ptp_priceguide_build_pricing_view_model(
    array( pricing_row( 70, 'normal', 2.25, '2026-08-12 11:30:00' ) )
);
$available_html = ptp_priceguide_render_pricing_view_model( 'base1-52', $available_model );
assert_contains( 'vf-c-evidence-status vf-o-stack has-evidence', $available_html, 'available pricing exposes shared evidence state' );
assert_contains( '<dt>Currency</dt><dd>USD</dd>', $available_html, 'available evidence qualifies currency' );
assert_contains( '<dt>Market</dt><dd>TCGplayer</dd>', $available_html, 'available evidence qualifies market' );
assert_contains( '<dt>Method</dt><dd>Cached market price</dd>', $available_html, 'available evidence qualifies method' );
assert_contains( '<dt>Treatment</dt><dd>Normal</dd>', $available_html, 'available evidence names the selected treatment' );
assert_contains( '<dt>Condition</dt><dd>Not recorded in cached evidence</dd>', $available_html, 'available evidence states the missing condition' );
assert_contains( '<dt>Observation</dt><dd><time datetime="2026-08-12T11:30:00">2026-08-12 11:30:00</time></dd>', $available_html, 'available evidence exposes the stored observation time without inventing freshness' );

assert_contains( 'ptp_priceguide_render_card_identity( $data, $card_name, get_query_var( \'pgpokeid\' ) )', $page_template_source, 'identity call site passes the exact card id' );
assert_contains( '<img class="pg-printing-image"', $page_template_source, 'main printing image has a dedicated class' );
assert_contains( 'pg-printing-image-unavailable vf-c-empty-state is-unavailable', $page_template_source, 'image fallback keeps its wording and adds shared state roles' );
assert_contains( 'pg-section vf-c-empty-state is-unavailable', $page_template_source, 'Card Not Found adds only shared empty-state roles' );
assert_contains( '.single-price-guide .pg-printing-image', $plugin_css, 'printing image class has local product styles' );
assert_contains( 'object-fit: contain', $plugin_css, 'printing image remains uncropped' );
assert_contains( 'outline: 3px solid currentColor', $plugin_css, 'Price Guide bridge focus follows the shared three-pixel contract' );

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
