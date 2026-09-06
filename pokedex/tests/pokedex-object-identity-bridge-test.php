<?php

declare(strict_types=1);

$plugin_root = dirname( __DIR__ );
$plugin      = file_get_contents( $plugin_root . '/primetime-pokedex.php' );
$template    = file_get_contents( $plugin_root . '/templates/single-pokedex.php' );
$source_css  = file_get_contents( $plugin_root . '/src/styl/pokedex.styl' );
$runtime_css = file_get_contents( $plugin_root . '/dist/css/pokedex.css' );

if ( false === $plugin || false === $template || false === $source_css || false === $runtime_css ) {
	fwrite( STDERR, "Unable to read the Pokédex bridge files.\n" );
	exit( 1 );
}

$assertions = 0;

$assert_contains = static function ( string $needle, string $haystack, string $message ) use ( &$assertions ): void {
	$assertions++;
	if ( false === strpos( $haystack, $needle ) ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$assert_absent = static function ( string $needle, string $haystack, string $message ) use ( &$assertions ): void {
	$assertions++;
	if ( false !== strpos( $haystack, $needle ) ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
};

$assert_contains( 'function pokedex_get_exact_printing_relationship( $pokemon_id )', $plugin, 'Pokédex owns a bounded exact-printing relationship function' );
$assert_contains( "'pokemon_id'  => 66", $plugin, 'relationship preserves Pokémon id 66' );
$assert_contains( "'display_id'  => '066'", $plugin, 'relationship preserves display id 066' );
$assert_contains( "'set_id'      => 'base1'", $plugin, 'relationship preserves set id base1' );
$assert_contains( "'card_id'     => 'base1-52'", $plugin, 'relationship preserves card id base1-52' );
$assert_contains( "'destination' => '/price-guide/machop/base1-52/'", $plugin, 'relationship uses the exact Price Guide destination' );
$assert_contains( "'label'       => 'View Machop — Base Set 52/102'", $plugin, 'relationship uses a meaningful exact-printing label' );

preg_match( '/function pokedex_get_exact_printing_relationship\( \$pokemon_id \) \{(?<body>.*?)\n\}/s', $plugin, $relationship_function );
if ( empty( $relationship_function['body'] ) ) {
	fwrite( STDERR, "FAIL: unable to isolate the bounded relationship function.\n" );
	exit( 1 );
}
$assertions++;
$assert_absent( 'ptp_priceguide', $relationship_function['body'], 'bounded relationship does not query Price Guide PHP' );
$assert_absent( 'WP_Query', $relationship_function['body'], 'bounded relationship does not query WordPress data' );
$assert_absent( '$wpdb', $relationship_function['body'], 'bounded relationship does not query the database' );

$assert_contains( 'pokedex_get_exact_printing_relationship( $current_id )', $template, 'active template consumes the bounded relationship' );
$assert_contains( '<data value="<?php echo esc_attr( (string) (int) $current_id ); ?>">#<?php echo esc_html( $current_display_id ); ?></data>', $template, 'subject identity uses semantic data markup' );
$assert_contains( 'site_url( $exact_printing_relationship[\'destination\'] )', $template, 'template uses only the bounded destination' );
$assert_contains( 'Exact printing relationship unavailable for this Pokémon.', $template, 'unsupported subjects fail closed with exact wording' );
$assert_contains( 'vf-c-object-identity', $template, 'identity pairs local and shared classes' );
$assert_contains( 'vf-c-relationship-link vf-c-action', $template, 'relationship pairs local and shared classes' );
$assert_contains( 'vf-c-empty-state is-unavailable', $template, 'fallback pairs shared empty and state classes' );
$assert_contains( 'aria-label="<?php esc_attr_e( \'Exact printing relationship\'', $template, 'relationship region is named' );
$assert_contains( 'aria-label="<?php esc_attr_e( \'Exact printing navigation\'', $template, 'relationship navigation is named' );
$assert_contains( 'do_shortcode(\'[primetime-related name="', $template, 'supporting related-card content remains unchanged' );
$assert_contains( 'outline 3px solid currentColor', $source_css, 'source bridge focus uses three pixels' );
$assert_contains( 'outline: 3px solid currentColor', $runtime_css, 'generated bridge focus uses three pixels' );
$assert_contains( 'row-gap 24px', $source_css, 'locked Stylus source preserves existing navigation row spacing' );
$assert_contains( 'column-gap 24px', $source_css, 'locked Stylus source preserves existing navigation column spacing' );
$assert_contains( 'row-gap: 24px', $runtime_css, 'generated CSS preserves existing navigation row spacing' );
$assert_contains( 'column-gap: 24px', $runtime_css, 'generated CSS preserves existing navigation column spacing' );
$assert_contains( 'object-fit contain', $source_css, 'source preserves uncropped subject artwork' );
$assert_contains( 'object-fit: contain', $runtime_css, 'generated CSS preserves uncropped subject artwork' );

fwrite( STDOUT, "PASS assertions={$assertions}\n" );
