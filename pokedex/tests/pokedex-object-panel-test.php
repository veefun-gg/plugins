<?php

declare(strict_types=1);

$plugin_root = dirname( __DIR__ );
$template    = file_get_contents( $plugin_root . '/templates/single-pokedex.php' );
$source_css  = file_get_contents( $plugin_root . '/src/styl/pokedex.styl' );
$runtime_css = file_get_contents( $plugin_root . '/dist/css/pokedex.css' );

if ( false === $template || false === $source_css || false === $runtime_css ) {
	fwrite( STDERR, "Unable to read the Pokédex object-panel files.\n" );
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

$assert_contains( '<img src="<?php echo esc_url( $current_image ); ?>"', $template, 'artwork remains data-driven' );
$assert_contains( 'pokestats($stat_hp, $stat_attack, $stat_defense, $stat_sattack, $stat_sdefense, $stat_speed)', $template, 'all six stored stats remain rendered' );
$assert_contains( 'aspect-ratio 1 / 1', $source_css, 'source styles preserve square artwork' );
$assert_contains( 'object-fit contain', $source_css, 'source styles contain the artwork without cropping' );
$assert_contains( 'grid-template-columns repeat(3, minmax(0, 1fr))', $source_css, 'source styles provide the narrow fact grid' );
$assert_contains( 'aspect-ratio: 1/1', $runtime_css, 'runtime styles preserve square artwork' );
$assert_contains( 'object-fit: contain', $runtime_css, 'runtime styles contain the artwork without cropping' );
$assert_contains( 'grid-template-columns: repeat(3, minmax(0, 1fr))', $runtime_css, 'runtime styles include the narrow fact grid' );

fwrite( STDOUT, "PASS assertions={$assertions}\n" );
