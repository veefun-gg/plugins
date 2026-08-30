<?php

error_reporting( E_ALL );

set_error_handler(
    static function ( $severity, $message, $file, $line ) {
        throw new ErrorException( $message, 0, $severity, $file, $line );
    }
);

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

function assert_contains( $needle, $haystack, $message ) {
    assert_same( true, false !== strpos( $haystack, $needle ), $message );
}

$plugin_root = dirname( __DIR__ );
$template    = file_get_contents( $plugin_root . '/templates/single-pokedex.php' );
$source_css  = file_get_contents( $plugin_root . '/src/styl/pokedex.styl' );
$runtime_css = file_get_contents( $plugin_root . '/dist/css/pokedex.css' );
$plugin      = file_get_contents( $plugin_root . '/primetime-pokedex.php' );

assert_same( 1, preg_match_all( '/<h1\b/i', $template ), 'active subject template exposes exactly one h1' );
assert_contains( '<h1 class="pokedex-subject-title">', $template, 'single h1 is the numbered subject identity' );
assert_contains( '<data value="<?php echo esc_attr( (string) (int) $current_id ); ?>">', $template, 'subject h1 exposes the durable Pokémon id' );
assert_contains( '<p class="poke-overview-name">', $template, 'artwork-panel name is supporting text' );
assert_contains( '<p class="summary-title">', $template, 'summary scope is supporting text' );
assert_contains( 'aria-label="<?php esc_attr_e( \'Pokédex subject navigation\'', $template, 'subject navigation has an accessible name' );
assert_contains( "__( 'Previous Pokémon: %s', 'veefun' )", $template, 'previous subject link has a contextual accessible name' );
assert_contains( "__( 'Next Pokémon: %s', 'veefun' )", $template, 'next subject link has a contextual accessible name' );
assert_contains( 'do_shortcode(\'[primetime-related name="', $template, 'subject-to-card relationship remains present' );
assert_contains( 'ptp_priceguide_get_related_editorial_html( $current_name )', $template, 'subject-to-editorial relationship remains present' );
assert_contains( 'aria-label="<?php esc_attr_e( \'Exact printing relationship\'', $template, 'exact-printing region has an accessible name' );
assert_contains( 'aria-label="<?php esc_attr_e( \'Exact printing navigation\'', $template, 'exact-printing navigation has an accessible name' );
assert_contains( 'vf-c-object-identity', $template, 'subject identity adopts the shared semantic role' );
assert_contains( 'vf-c-relationship-link vf-c-action', $template, 'exact-printing link pairs local and shared roles' );
assert_contains( 'vf-c-empty-state is-unavailable', $template, 'relationship fallback exposes its shared unavailable state' );
assert_contains( 'Exact printing relationship unavailable for this Pokémon.', $template, 'relationship fallback uses the exact approved wording' );
assert_contains( "'/templates/single-pokedex.php'", $plugin, 'tested template remains the active single-subject owner' );
assert_contains( "'1.2.4'", $plugin, 'runtime stylesheet version is bumped' );
assert_contains( '.pokedex-subject-title', $source_css, 'source stylesheet owns the subject title' );
assert_contains( '.pokedex-subject-title', $runtime_css, 'runtime stylesheet includes the subject title' );
assert_contains( '.pokenav-link a:focus-visible', $runtime_css, 'runtime subject navigation exposes keyboard focus' );
assert_contains( 'outline: 3px solid currentColor', $runtime_css, 'runtime bridge focus follows the shared three-pixel contract' );

echo 'PASS assertions=', $tests_passed, PHP_EOL;
