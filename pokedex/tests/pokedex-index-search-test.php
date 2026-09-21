<?php

declare(strict_types=1);

/**
 * Offline behavior checks for the actual index/filter functions.
 *
 * Run: php -n tests/pokedex-index-search-test.php
 * This extracts only the functions below; it never loads WordPress, a database,
 * the plugin's hooks or external services. The small WordPress stubs exercise
 * the collection/rendering contract, not real WP_Query SQL, hooks or browser UI.
 */
$plugin = file_get_contents( dirname( __DIR__ ) . '/primetime-pokedex.php' );
if ( false === $plugin ) {
	fwrite( STDERR, "FAIL: unable to read plugin source.\n" );
	exit( 1 );
}

$assertions = 0;
function check( bool $condition, string $message ): void {
	global $assertions;
	$assertions++;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

foreach ( array(
	'pokedex_get_archive_card_id',
	'pokedex_get_archive_card_title',
	'pokedex_get_archive_card_image_url',
	'pokedex_get_local_image_url',
	'pokedex_get_index_entries',
	'pokedex_search_shortcode',
) as $function_name ) {
	$matched = preg_match( '/^function ' . preg_quote( $function_name, '/' ) . '\s*\([^\n]*\)\s*\{.*?^\}/ms', $plugin, $function );
	check( 1 === $matched, "actual {$function_name} function can be isolated" );
	eval( $function[0] );
}

$fixtures = array();
function fixture( int $post_id, string $number, string $name, array $extra = array() ): void {
	global $fixtures;
	$fixtures[ $post_id ] = array_merge(
		array(
			'ID' => $post_id,
			'post_type' => 'pokedex',
			'post_status' => 'publish',
			'post_title' => 'Stored title ' . $post_id,
			'pokemon_id' => $number,
			'pokemon_name' => $name,
			'pokemon_image' => '',
			'thumbnail' => '',
		),
		$extra
	);
}

// Reverse insertion and post IDs ensure ordering cannot pass by coincidence.
for ( $number = 30; $number >= 1; $number-- ) {
	fixture( 1000 - $number, (string) $number, 'Fixture ' . $number );
}
fixture( 3493, '151', 'Mew', array( 'pokemon_image' => 'https://veefun.test/uploads/mew.png' ) );
fixture( 3492, '150', 'Mewtwo', array( 'thumbnail' => 'https://external.test/mewtwo.png' ) );
fixture( 4001, '400', '', array( 'post_title' => 'Title fallback' ) );
fixture( 4002, '', 'Missing number' );
fixture( 4003, 'not-a-number', 'Invalid number' );
fixture( 4004, '0', 'Zero number' );
fixture( 4005, '0004', 'Alpha duplicate' );
fixture( 4006, '4', 'Alpha duplicate' );
fixture( 4007, '5', 'Name <b> & "quote"' );
fixture( 5001, '151', 'Mew draft', array( 'post_status' => 'draft' ) );
fixture( 5002, '151', 'Mew private', array( 'post_status' => 'private' ) );
fixture( 5003, '151', 'Mew article', array( 'post_type' => 'post' ) );

$queries = array();
$thumbnail_cache_queries = array();
class WP_Query {
	public $posts;
	public function __construct( array $args ) {
		global $fixtures, $queries;
		$queries[] = $args;
		$rows = array_values( array_filter( $fixtures, static function ( array $row ) use ( $args ): bool {
			return $row['post_type'] === ( $args['post_type'] ?? 'post' )
				&& $row['post_status'] === ( $args['post_status'] ?? 'publish' );
		} ) );
		usort( $rows, static function ( array $a, array $b ): int { return $a['ID'] <=> $b['ID']; } );
		if ( 'DESC' === ( $args['order'] ?? 'DESC' ) ) {
			$rows = array_reverse( $rows );
		}
		if ( -1 !== ( $args['posts_per_page'] ?? 10 ) ) {
			$rows = array_slice( $rows, 0, $args['posts_per_page'] ?? 10 );
		}
		$this->posts = array_map( static function ( array $row ) use ( $args ) {
			return 'ids' === ( $args['fields'] ?? '' ) ? $row['ID'] : (object) $row;
		}, $rows );
	}
}

function update_post_thumbnail_cache( $query ) {
	global $thumbnail_cache_queries;
	$thumbnail_cache_queries[] = $query;
}
function get_post_meta( $post_id, $key, $single = false ) {
	global $fixtures;
	return $fixtures[ $post_id ][ $key ] ?? '';
}
function get_the_title( $post_id ) { return get_post_meta( $post_id, 'post_title', true ); }
function get_the_post_thumbnail_url( $post_id, $size ) { return get_post_meta( $post_id, 'thumbnail', true ); }
function home_url( $path = '' ) { return 'https://veefun.test' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_url_raw( $value ) { return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : ''; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( esc_url_raw( $value ) ); }
function __( $value, $domain = '' ) { return $value; }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function esc_attr__( $value, $domain = '' ) { return esc_attr( $value ); }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function number_format_i18n( $number ) { return number_format( $number ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function wp_unique_id( $prefix = '' ) { static $count = 0; return $prefix . ++$count; }
function get_permalink( $post_id ) { return home_url( '/pokedex/profile-' . $post_id . '/' ); }
function post_class( $classes = '', $post_id = null ) {
	check( null !== $post_id, 'card classes receive the explicit post ID' );
	echo 'class="' . esc_attr( $classes . ' post-' . $post_id ) . '"';
}

function entry_ids( string $query = '' ): array {
	return array_column( pokedex_get_index_entries( $query ), 'post_id' );
}
function render_search( $query = null ): string {
	$_GET = null === $query ? array() : array( 'pokedex_search' => $query );
	return pokedex_search_shortcode();
}
function card_ids( string $html ): array {
	preg_match_all( '/<article\s+class="[^"]*\bpost-(\d+)\b/', $html, $matches );
	return array_map( 'intval', $matches[1] );
}

$expected_count = 39;
$all = pokedex_get_index_entries();
$ids = array_column( $all, 'post_id' );
check( $expected_count === count( $all ), 'every published entry is returned beyond the former 24-result cap' );
check( 999 === $ids[0] && 998 === $ids[1], 'Pokédex number sorts numerically before post/title order' );
check( array( 4005, 4006, 996 ) === array_slice( $ids, 3, 3 ), 'duplicate normalized numbers use name then post ID for stable ties' );
check( array( 4003, 4002, 4004 ) === array_slice( $ids, -3 ), 'invalid, missing and zero numbers remain browsable after valid numbers' );
check( ! array_intersect( array( 5001, 5002, 5003 ), $ids ), 'draft, private and other post types are excluded' );
check( array( 4001 ) === entry_ids( 'fallback' ), 'missing name is searched using its card title fallback' );
check( array( 3492, 3493 ) === entry_ids( 'Mew' ), 'name substring narrows the same ordered collection' );
check( entry_ids( 'Mew' ) === entry_ids( '  mEW  ' ), 'name matching ignores case and trims whitespace' );
foreach ( array( '151', '#151', '00151', '#00151', '  #00151  ' ) as $query ) {
	check( array( 3493 ) === entry_ids( $query ), "number input {$query} matches only exact normalized number" );
}
check( array( 4005, 4006, 996 ) === entry_ids( '#0004' ), 'stored leading zeros normalize consistently with numeric input' );
check( array() === entry_ids( '999999999999999999999999999999' ), 'out-of-range numeric input cannot overflow into a stored ID' );
check( array( 985 ) === entry_ids( '15' ), 'numeric search is exact even when a larger number shares digits' );
check( array( 4004 ) === entry_ids( '0' ) && array( 4004 ) === entry_ids( '#000' ), 'zero search matches only an explicitly stored zero, not invalid or missing numbers' );
check( array() === entry_ids( 'No such Pokémon' ), 'unknown name has no matches' );
check( $ids === entry_ids( '   ' ), 'whitespace-only search restores all published entries' );
check( array( 4002 ) === entry_ids( 'Missing number' ), 'missing-number entry remains discoverable by name' );
check( $queries[0]['no_found_rows'] === true, 'full collection avoids an unused found-rows query' );
check( $thumbnail_cache_queries[0] instanceof WP_Query && $expected_count === count( $thumbnail_cache_queries[0]->posts ), 'thumbnail cache receives the full published query before card rendering' );

$original_post = (object) array( 'ID' => 1519 );
$GLOBALS['post'] = $original_post;
$landing = render_search();
check( $ids === card_ids( $landing ), 'landing renders the complete shared collection in the same order' );
check( false !== strpos( $landing, 'placeholder="Try Mew or 151"' ), 'placeholder uses exact requested text' );
check( false !== strpos( $landing, '>All Pokémon</h3>' ), 'landing describes the complete collection' );
check( false !== strpos( $landing, 'method="get"' ) && false !== strpos( $landing, 'name="pokedex_search"' ), 'GET query contract works without JavaScript' );
check( false !== strpos( $landing, 'action="https://veefun.test/pokedex/"' ) && false !== strpos( $landing, 'type="submit"' ), 'form submits directly to the index route' );
check( 1 === preg_match( '/<label for="([^"]+)">Pokémon name or number<\/label>\s*<input id="\1"/', $landing ), 'visible label is associated with the search field' );
check( false !== strpos( $landing, '39 Pokémon' ), 'landing count reflects every rendered entry' );
check( false !== strpos( $landing, 'src="https://veefun.test/uploads/mew.png"' ), 'existing same-site image remains on the card' );
check( false === strpos( $landing, 'external.test' ), 'external thumbnail is not rendered' );
check( false !== strpos( $landing, 'loading="lazy" decoding="async"' ), 'existing image loading behavior is preserved' );
check( false !== strpos( $landing, 'Name &lt;b&gt; &amp; &quot;quote&quot;' ), 'card names are escaped' );
check( false !== strpos( $landing, 'href="https://veefun.test/pokedex/profile-3493/"' ), 'card destinations use their explicit stored post IDs' );
check( $GLOBALS['post'] === $original_post, 'rendering preserves the enclosing page global post' );
check( 1 === preg_match( '/<section\b[^>]*aria-labelledby="([^"]+)">\s*<h2 id="\1">/', $landing ), 'search region references its rendered heading' );

$mew = render_search( ' Mew ' );
check( array( 3492, 3493 ) === card_ids( $mew ), 'submitted search narrows rendered cards' );
check( false !== strpos( $mew, '2 Pokémon' ) && false !== strpos( $mew, 'Results for “Mew”' ), 'search heading and count describe the submitted result set' );
check( 1 === preg_match( '/<a\b[^>]*href="https:\/\/veefun\.test\/pokedex\/"[^>]*>\s*Clear search\s*<\/a>/', $mew ), 'clear-search link returns to the clean index URL without JavaScript' );
check( $ids === card_ids( render_search( '' ) ) && $ids === card_ids( render_search() ), 'clearing or removing the GET parameter restores the full index' );
check( $ids === card_ids( render_search( array( 'Mew' ) ) ), 'malformed array-valued GET input is ignored safely' );
$number_result = render_search( '#00151' );
check( array( 3493 ) === card_ids( $number_result ) && false !== strpos( $number_result, '1 Pokémon shown.' ), 'numeric GET input renders its exact match and count' );

$no_match = render_search( 'Absent Pokémon' );
check( array() === card_ids( $no_match ) && false !== strpos( $no_match, '0 Pokémon' ), 'empty search results show no cards and a zero count' );
check( false !== strpos( $no_match, 'No matching Pokémon were found in the stored Pokédex.' ), 'empty state gives a useful explanation' );
$escaped = render_search( '" & <b>Mew</b>' );
check( false !== strpos( $escaped, 'value="&quot; &amp; Mew"' ), 'submitted query is sanitized and escaped for its input value' );
check( false === strpos( $escaped, '<b>Mew</b>' ), 'submitted HTML does not enter the rendered page' );

$fixtures = array();
$empty = render_search();
check( array() === card_ids( $empty ) && false !== strpos( $empty, '0 Pokémon' ), 'an empty published collection has a coherent count' );
check( false !== strpos( $empty, 'No Pokémon have been published yet.' ), 'empty stored collection has its own useful explanation' );

fwrite( STDOUT, "PASS assertions={$assertions}; synthetic published fixtures={$expected_count}; WordPress/browser integration not exercised.\n" );
