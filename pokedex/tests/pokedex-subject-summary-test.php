<?php

declare(strict_types=1);

$plugin_root = dirname( __DIR__ );
$template    = file_get_contents( $plugin_root . '/templates/single-pokedex.php' );
$source_css  = file_get_contents( $plugin_root . '/src/styl/pokedex.styl' );
$runtime_css = file_get_contents( $plugin_root . '/dist/css/pokedex.css' );

if ( false === $template || false === $source_css || false === $runtime_css ) {
	fwrite( STDERR, "Unable to read the Pokédex summary files.\n" );
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

$assert_contains( "esc_html_e( 'Subject overview', 'veefun' )", $template, 'summary uses a non-repeating scope label' );
$assert_contains( 'if($desc_sword)', $template, 'Sword description remains data-driven' );
$assert_contains( 'if($desc_shield)', $template, 'Shield description remains data-driven' );
$assert_contains( 'poketypes($types)', $template, 'type relationship remains rendered' );
$assert_contains( 'poketypes($weaknesses)', $template, 'weakness relationship remains rendered' );
$assert_contains( 'pokespells($abilities)', $template, 'abilities remain rendered' );
$assert_contains( 'pokespells($abilities_hidden, true)', $template, 'hidden abilities remain rendered' );
$assert_contains( 'content none', $source_css, 'source styles remove decorative heading rules' );
$assert_contains( 'box-shadow inset 0 0 0 999px', $source_css, 'source styles mute type markers without removing their hue' );
$assert_contains( 'min-height 44px', $source_css, 'source styles preserve linked fact touch targets' );
$assert_contains( '&:focus-visible', $source_css, 'source styles include linked fact keyboard focus' );
$assert_contains( 'content: none', $runtime_css, 'runtime styles remove decorative heading rules' );
$assert_contains( 'box-shadow: inset 0 0 0 999px', $runtime_css, 'runtime styles include muted type markers' );
$assert_contains( 'min-height: 44px', $runtime_css, 'runtime styles preserve linked fact touch targets' );
$assert_contains( 'a:focus-visible', $runtime_css, 'runtime styles include linked fact keyboard focus' );

fwrite( STDOUT, "PASS assertions={$assertions}\n" );
