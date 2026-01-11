<?php
/**
 * Render single Pokedex entry markup.
 *
 * Uses output buffering to include template and return HTML string.
 *
 * @return string HTML markup for Pokedex entry.
 */
function ptp_render_single_pokedex() {
	// Only render on single pokedex posts, main query, in the loop
	if ( ! is_singular( 'pokedex' ) || ! is_main_query() || ! in_the_loop() ) {
		return '';
	}

	// Start output buffering
	ob_start();
	
	// Include the template part
	$template_path = dirname( dirname( __FILE__ ) ) . '/templates/single-pokedex-content.php';
	if ( file_exists( $template_path ) ) {
		include $template_path;
	}
	
	// Get buffered content and clean buffer
	$output = ob_get_clean();
	
	return $output;
}
