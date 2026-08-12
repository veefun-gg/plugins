<?php
/*
    Plugin Name: Primetime Pokédex Plugin
    description: Pokédex
    Author: Lucas M. Shepherd <lucas@leoblack.com>
    Version: 1.1.0
*/

define( 'PRIMETIME_POKEDEX_REWRITE_VERSION', '2026-08-12-1' );
define( 'PRIMETIME_POKEDEX_REWRITE_VERSION_OPTION', 'primetime_pokedex_rewrite_version' );

class PrimetimePokedex {
    function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
    }

    public function register_admin_menu() {
        page_builder_menu( pokedex_get_stored_pokemon_limit() );
    }

    public function hooks() {
    }
}
$var = new PrimetimePokedex();
add_action( 'plugins_loaded', array( $var, 'hooks' ) );

add_action('init', 'add_pokedex_post_tax');
add_action( 'init', 'pokedex_register_profile_route', 20 );
add_action( 'init', 'pokedex_maybe_flush_rewrite_rules', 99 );
add_action('wp_enqueue_scripts', 'pokedex_custom_js', 999);
add_action('wp_enqueue_scripts', 'pokedex_awesome_icons');
add_action('wp_enqueue_scripts', 'pokedex_custom', 100);
add_filter( 'single_template', 'set_pokedex_single_template' );
add_filter( 'archive_template', 'set_pokedex_archive_template' );
add_filter( 'taxonomy_template', 'set_pokecategory_template' );
add_action( 'pre_get_posts', 'set_pokedex_archive_order' );
add_shortcode( 'veefun_pokedex_search', 'pokedex_search_shortcode' );

// ADMIN MENU AND PAGES
// Add custom taxonomy for Pokémon
function add_pokedex_post_tax() {
	$supports = array(
        'title', // post title
        'editor', // post content
        'author', // post author
        'thumbnail', // featured images
		'excerpt', // post excerpt
        'custom-fields', // custom fields
		'comments', // post comments
        'revisions', // post revisions
		'post-formats', //post formats
		'permalinks', //permalinks
		'featured_image', //featured image
    );
    $labels = array(
        'name' => _x('Pokédex', 'plural'),
        'singular_name' => _x('Pokédex', 'singular'),
        'menu_name' => _x('Pokédex', 'admin menu'),
        'name_admin_bar' => _x('Pokédex', 'admin bar'),
        'add_new' => _x('Add New', 'add new'),
        'add_new_item' => __('Add New Pokémon'),
        'new_item' => __('New Pokémon'),
        'edit_item' => __('Edit Pokémon'),
        'view_item' => __('View Pokémon'),
        'all_items' => __('All Pokémon'),
        'search_items' => __('Search Pokédex'),
        'not_found' => __('No Pokémon found.'),
    );
    $args = array(
        'supports' => $supports,
		'show_in_rest' => true,
        'labels' => $labels,
        'public' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug'       => 'pokedex',
            'with_front' => false,
        ),
        'has_archive' => false,
        'hierarchical' => false,
        'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><defs><style>.cls-1{fill:#fff;}</style></defs><path class="cls-1" d="M331.24,275.71a77.81,77.81,0,0,1-150.48,0q-65.26-2.67-128.59-10.09c5,108.09,94.38,194.17,203.82,194.17s198.85-86.15,203.84-194.29Q396.59,273,331.24,275.71ZM256,178.3a77.82,77.82,0,0,1,74.37,54.84c43.13-1.82,85.59-5.21,127-10.11a204.13,204.13,0,0,0-402.82.11c41.46,4.89,83.9,8.25,127,10A77.8,77.8,0,0,1,256,178.3Z"/><path class="cls-1" d="M289,256a32.87,32.87,0,0,1-7.63,21.1h0A33,33,0,1,1,289,256Z"/></svg>'),
        'taxonomies' => array(/*'category',*/ 'post_tag')
    );
    register_post_type('pokedex', $args);
}

/**
 * Keep the Pokedex landing Page at /pokedex/ while routing one additional
 * segment to a stored Pokedex post.
 */
function pokedex_register_profile_route() {
    add_rewrite_rule(
        '^pokedex/([^/]+)/?$',
        'index.php?post_type=pokedex&name=$matches[1]',
        'top'
    );
}

function pokedex_activate() {
    add_pokedex_post_tax();
    pokedex_register_profile_route();
    pokedex_flush_rewrite_rules();
}

function pokedex_flush_rewrite_rules() {
    flush_rewrite_rules( false );
    update_option( PRIMETIME_POKEDEX_REWRITE_VERSION_OPTION, PRIMETIME_POKEDEX_REWRITE_VERSION, false );
}

function pokedex_maybe_flush_rewrite_rules() {
    if ( PRIMETIME_POKEDEX_REWRITE_VERSION === get_option( PRIMETIME_POKEDEX_REWRITE_VERSION_OPTION ) ) {
        return;
    }

    pokedex_flush_rewrite_rules();
}

function pokedex_deactivate() {
    flush_rewrite_rules( false );
}

register_activation_hook( __FILE__, 'pokedex_activate' );
register_deactivation_hook( __FILE__, 'pokedex_deactivate' );

function add_custom_taxonomies() {
  // Add new "Locations" taxonomy to Posts
  register_taxonomy('pokecategory', 'pokedex', array(
    // Hierarchical taxonomy (like categories)
    'hierarchical' => true,
    // This array of options controls the labels displayed in the WordPress Admin UI
    'labels' => array(
      'name' => _x( 'Pokemon Types', 'taxonomy general name' ),
      'singular_name' => _x( 'Pokemon Type', 'taxonomy singular name' ),
      'search_items' =>  __( 'Search Pokemon Types' ),
      'all_items' => __( 'All Pokemon Types' ),
      'parent_item' => __( 'Parent Pokemon Type' ),
      'parent_item_colon' => __( 'Parent Pokemon Type:' ),
      'edit_item' => __( 'Edit Pokemon Type' ),
      'update_item' => __( 'Update Pokemon Type' ),
      'add_new_item' => __( 'Add New Pokemon Type' ),
      'new_item_name' => __( 'New Pokemon Type Name' ),
      'menu_name' => __( 'Pokemon Types' ),
    ),
    // Control the slugs used for this taxonomy
    'rewrite' => array(
      'slug' => 'pokedex-type', // This controls the base slug that will display before each term
      'with_front' => true, // Don't display the category base before "/locations/"
      'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
    ),
  ));
}
add_action( 'init', 'add_custom_taxonomies', 0 );

// Page builder menu item
function page_builder_menu($count){
    $count = $count;
    add_submenu_page('edit.php?post_type=pokedex', 'Page Builder', 'Page Builder', 'manage_options', 'page-builder', function() use ($count) { page_builder_page($count); });
}
// Page builder loop
function pokedex_button_clicked($id, $buildCount, $count) {
    for ($x = $id; $x <= $buildCount; $x++) { add_pokemon( $x, 'pokedex', $count ); }
}
// Page builder admin page
function page_builder_page($count) {
    $count = $count;
    $buildCount = $count;
    // Terminate if no access
    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient access to view this page.')    );
    }
    // If build/upgrade button was pressed
    if (isset($_POST['builder_button'])) {
        $buildCount = $_POST['builder_page_count'];
        $id = $_POST['builder_page_id'];
        echo "<br/><i>Building/updating up to page #$buildCount starting on page #$id.</i><br/><br/>";
        pokedex_button_clicked($id, $buildCount, $count);
    }
    // If delete all pages button was pressed
    if (isset($_POST['delete_button'])) {
        $allposts= get_posts( array('post_type'=>'pokedex','numberposts'=>-1) );
        foreach ($allposts as $eachpost) {
            wp_delete_post( $eachpost->ID, true );
        }
    } ?>
    <div class="wrap">
        <h2>Pok&eacute;dex Page Builder</h2>
        <form id="sendform" action="edit.php?post_type=pokedex&page=page-builder" method="post">
            <input type="hidden" value="true" name="builder_button" /><br/><br/>
            <!--label style='display: inline-block; width: 200px;'><b>From Page:</b></label> &nbsp;<input class='bpid' type='number' value='265' max='<?php echo $count; ?>' min='1' name='builder_page_id' /><br/><br/-->
            <label style='display: inline-block; width: 200px;'><b>From Page:</b></label> &nbsp;<input class='bpid' type='number' value='1' max='<?php echo $count; ?>' min='1' name='builder_page_id' /><br/><br/>
            <!--label style='display: inline-block; width: 200px;'><b>To Page:</b></label> &nbsp;<input class='bpid' type='number' value='265' max='<?php echo $count; ?>' min='1' name='builder_page_count' /><br/><br/-->
            <label style='display: inline-block; width: 200px;'><b>To Page:</b></label> &nbsp;<input class='bpid' type='number' value='<?php echo $count; ?>' max='<?php echo $count; ?>' min='1' name='builder_page_count' /><br/><br/>
            <small><i><b>Note</b>: Building more than ~200 pages at a time will cause a timeout and you will need to do this again starting from whatever page it timed out on.</i></small><br/><br/>
            <?php submit_button('Build/Update Pages', 'primary', 'submit', false); ?>
        </form><br/><br/><hr/><br/><br/>
        <form id="sendform" action="edit.php?post_type=pokedex&page=page-builder" method="post">
            <input type="hidden" value="true" name="delete_button" />
            <?php submit_button('Delete All Pages', 'secondary', 'submit', false); ?>
        </form>
    </div> <?php 
}
// Force template for 'pokedex' custom post type
function set_pokedex_single_template( $single_template ) {
    global $post;
    if ( 'pokedex' === $post->post_type ) { $single_template = dirname( __FILE__ ) . '/templates/single-pokedex.php'; }
    return $single_template;
}
function set_pokedex_archive_template( $archive_template ) {
    if ( is_post_type_archive( 'pokedex' ) ) { $archive_template = dirname( __FILE__ ) . '/templates/archive-pokedex.php'; }
    return $archive_template;
}
function set_pokecategory_template( $taxonomy_template ) {
    if ( is_tax( 'pokecategory' ) ) { $taxonomy_template = dirname( __FILE__ ) . '/templates/taxonomy-pokecategory.php'; }
    return $taxonomy_template;
}

function pokedex_is_frontend_context() {
    return is_page( 'pokedex' ) || is_singular( 'pokedex' ) || is_post_type_archive( 'pokedex' ) || is_tax( 'pokecategory' );
}

function pokedex_is_archive_context( $query = null ) {
    if ( null === $query ) {
        return is_post_type_archive( 'pokedex' ) || is_tax( 'pokecategory' );
    }

    if ( ! ( $query instanceof WP_Query ) ) {
        return false;
    }

    return $query->is_post_type_archive( 'pokedex' ) || $query->is_tax( 'pokecategory' );
}

function set_pokedex_archive_order( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! pokedex_is_archive_context( $query ) ) {
        return;
    }

    $query->set( 'orderby', 'title' );
    $query->set( 'order', 'ASC' );
}


// ENQUEUE
// Add custom scripts and styles to pokedex pages
function pokedex_custom_js() { 
    if ( ! is_singular( 'pokedex' ) ) {
        return;
    }

    wp_enqueue_script( 'pokedex_scripts', plugin_dir_url( __FILE__ ) . 'dist/js/primetime-pokedex.js', array('jquery'), '1.0', true );
}
function pokedex_custom(){
    if ( ! pokedex_is_frontend_context() ) {
        return;
    }

    wp_enqueue_style('pokedex_custom_css', plugins_url("/dist/css/pokedex.css", __FILE__), array(), '1.2.3');
}
function pokedex_awesome_icons(){
    if ( ! is_singular( 'pokedex' ) ) {
        return;
    }

    wp_enqueue_script('pokedex_icons', 'https://kit.fontawesome.com/4170135a74.js');
}

// REMOVE ELEMENTS
//remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);

// UTILITY
// Convert inches to feet and inches (0'0")
function in_feet($in) {
    $feet = intval($in/12);
    $inches = $in%12;
    return sprintf("%d' %d''", $feet, $inches);
}
// Add leading zeros up to ($length) total digits
function add_digits($input, $length) {
    $input = substr(str_repeat(0, $length).$input, - $length);
    return $input;
}
// Get depth of an array
function array_depth($arr) {    
    if (!is_array($arr)) { return 0; }
    $arr = json_encode($arr);
    $varsum = 0; 
    $depth  = 0;
    for ($i=0;$i<strlen($arr);$i++) {
        $varsum += intval($arr[$i] == '[') - intval($arr[$i] == ']');
        if ($varsum > $depth) { $depth = $varsum; }
    }
    return $depth;
}

function pokedex_get_archive_card_id( $post_id ) {
    return get_post_meta( $post_id, 'pokemon_id', true );
}

function pokedex_get_archive_card_title( $post_id ) {
    $pokemon_name = get_post_meta( $post_id, 'pokemon_name', true );

    if ( ! empty( $pokemon_name ) ) {
        return $pokemon_name;
    }

    return get_the_title( $post_id );
}

function pokedex_get_archive_card_image_url( $post_id, $size = 'thumbnail' ) {
    $image_url = get_the_post_thumbnail_url( $post_id, $size );

    if ( ! empty( $image_url ) ) {
        return pokedex_get_local_image_url( $image_url );
    }

    return pokedex_get_local_image_url( get_post_meta( $post_id, 'pokemon_image', true ) );
}

function pokedex_get_stored_profile_by_slug( $slug ) {
    $slug = sanitize_title( (string) $slug );

    if ( '' === $slug ) {
        return null;
    }

    $profile = get_page_by_path( $slug, OBJECT, 'pokedex' );

    if ( ! $profile || 'publish' !== $profile->post_status ) {
        return null;
    }

    return $profile;
}

/**
 * Permit only same-site image URLs on public Pokedex views.
 */
function pokedex_get_local_image_url( $image_url ) {
    if ( ! is_string( $image_url ) || '' === trim( $image_url ) ) {
        return '';
    }

    $image_url = esc_url_raw( $image_url );
    $site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
    $image_host = wp_parse_url( $image_url, PHP_URL_HOST );

    if ( empty( $image_url ) || empty( $site_host ) || empty( $image_host ) || strtolower( $site_host ) !== strtolower( $image_host ) ) {
        return '';
    }

    return $image_url;
}

/**
 * Use existing WordPress data to size the legacy admin builder controls.
 */
function pokedex_get_stored_pokemon_limit() {
    global $wpdb;

    $limit = $wpdb->get_var(
        "SELECT MAX(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'pokemon_id'"
    );

    return max( 1, (int) $limit );
}

function pokedex_search_query( $search_term ) {
    $args = array(
        'post_type'           => 'pokedex',
        'post_status'         => 'publish',
        'posts_per_page'      => 24,
        'orderby'             => 'title',
        'order'               => 'ASC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    );

    if ( preg_match( '/^#?0*([0-9]+)$/', $search_term, $matches ) ) {
        $args['meta_query'] = array(
            array(
                'key'     => 'pokemon_id',
                'value'   => (string) (int) $matches[1],
                'compare' => '=',
            ),
        );
    } else {
        $args['s'] = $search_term;
    }

    return new WP_Query( $args );
}

function pokedex_search_shortcode() {
    $search_term = '';

    if ( isset( $_GET['pokedex_search'] ) ) {
        $search_term = sanitize_text_field( wp_unslash( $_GET['pokedex_search'] ) );
    }

    $field_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'veefun-pokedex-search-' ) : 'veefun-pokedex-search';

    ob_start();
    ?>
    <section class="veefun-pokedex-search" aria-labelledby="veefun-pokedex-search-heading">
        <h2 id="veefun-pokedex-search-heading"><?php esc_html_e( 'Search the Pokédex', 'veefun' ); ?></h2>
        <form class="search-form veefun-pokedex-search__form" method="get" action="<?php echo esc_url( home_url( '/pokedex/' ) ); ?>" role="search">
            <label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Pokémon name or number', 'veefun' ); ?></label>
            <input id="<?php echo esc_attr( $field_id ); ?>" class="search-field" type="search" name="pokedex_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php echo esc_attr__( 'Try Machop or 66', 'veefun' ); ?>" />
            <button class="submit" type="submit"><?php esc_html_e( 'Search', 'veefun' ); ?></button>
        </form>

        <?php if ( '' !== $search_term ) : ?>
            <?php $results = pokedex_search_query( $search_term ); ?>
            <div class="veefun-pokedex-search__results" aria-live="polite">
                <h3><?php echo esc_html( sprintf( __( 'Results for “%s”', 'veefun' ), $search_term ) ); ?></h3>

                <?php if ( $results->have_posts() ) : ?>
                    <div class="pokedex-archive-list">
                        <?php while ( $results->have_posts() ) : $results->the_post(); ?>
                            <?php
                            $post_id      = get_the_ID();
                            $pokemon_id   = pokedex_get_archive_card_id( $post_id );
                            $pokemon_name = pokedex_get_archive_card_title( $post_id );
                            $image_url    = pokedex_get_archive_card_image_url( $post_id );
                            ?>
                            <article <?php post_class( 'pokedex pokedex-archive-card' ); ?>>
                                <a class="pokedex-archive-card__link" href="<?php the_permalink(); ?>">
                                    <?php if ( '' !== $image_url ) : ?>
                                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( '#' . $pokemon_id . ' ' . $pokemon_name ); ?>" loading="lazy" decoding="async" />
                                    <?php endif; ?>
                                    <span class="pokedex-archive-card__content">
                                        <span class="entry-title"><?php echo esc_html( $pokemon_name ); ?></span>
                                        <?php if ( '' !== (string) $pokemon_id ) : ?>
                                            <span class="pokemon-id"><sup>#</sup><?php echo esc_html( $pokemon_id ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e( 'No matching Pokémon were found in the stored Pokédex.', 'veefun' ); ?></p>
                <?php endif; ?>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </section>
    <?php

    return ob_get_clean();
}

// Debug array
function dbug($incoming, string $string = "Dbug") {
    echo "<b>$string:</b><br/><pre>"; print_r($incoming); echo "</pre><br/><br/>";
}

// GET_POKEDATA
// Pull data from https://pokeapi.co/
function get_pokedata($value, $category = 'pokemon', $url = false) {
    $value = $value;
    $category = $category;
    $url = $url;
    if ($url == false) { $url = "https://pokeapi.co/api/v2/$category/$value"; } 
    else { $url = $value; }
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    $output = curl_exec($curl);
    curl_close($curl);
    $output = json_decode($output, true);
    return $output;
}

// GET_POKECOUNT
// Determine number of overall Pokémon
function get_pokecount() {
    $url = "https://pokeapi.co/api/v2/pokemon/?offset=0&limit=10000";
    $countArray = get_pokedata($url,'',true);
    $countArray = $countArray['results'];
    $count = 0;
    foreach($countArray as $pokemon) {
        $url = $pokemon['url'];
        $url = explode("/", $url);
        $url = $url[6];
        if($url > 9999) { return $count; }
        $count++;
    }
}

// BUILD_TITLE
// Combine ID and name into a title also used for classes/anchors
function build_title(array $array) {
    $name = $array['species']['name'];
    $species = $array['species']['url'];
    $species = get_pokedata($species,'',true);
    $id = $species['id'];
    $title = ucwords("#$id $name");
    return $title;
}

// GET_IMAGE_URL
// Get the URL of the main image for this pokemon
function get_image_url(array $array, $dreamWorld = true) {
    $species = $array['species']['url'];
    $species = get_pokedata($species,'',true);
    $id = $species['id'];
    $pokemon = get_pokedata($id);
    $sprites = $pokemon['sprites'];
    $dream = $sprites['other']['dream_world']['front_default'];
    $default = $sprites['other']['official-artwork']['front_default'];
    if(!empty($dream) && $dreamWorld == true) { $image = $dream; } 
    else { $image = $default; }
    return $image;
}

// POKE_SINGLE
// Get title and image URL of this pokemon
function poke_single(array $array) {
    $title = build_title($array);
    $imageURL = get_image_url($array);
    return [$title, $imageURL];
}

// POKE_CHAIN
// Create array/list of pokemon in this evolution chain
function poke_chain($array, $build = array(), $depth = 0) {
    $length = count($array);
    for($i = 0; $i < $length; $i++) {
        $current = poke_single($array[$i]);
        if(empty($build[$depth])) { $build[$depth] = array(); }
        array_push($build[$depth], $current);
        if(empty($array[$i]['evolves_to'])) { 
            continue;
        } else {
            $depth++;
            $build = poke_chain($array[$i]['evolves_to'], $build, $depth);
        }
    }
    return $build;
}

// ADD_POKEMON
// Add a page/pokemon to pokedex
function add_pokemon($id, $tax, $pokeCount ) {
    $length = strlen((string) abs($pokeCount));
    $dreamWorld = true;
    // Pokémon Details
    $id = $id;
    $pokeID = $id;
    $poke = get_pokedata($pokeID);
    // Name
    $pokeName = ucwords($poke['species']['name']);
    $pokeImage = $poke['sprites']['other']['dream_world']['front_default'];
    $pokeImageDefault = $poke['sprites']['other']['official-artwork']['front_default'];
    // Image
    if(empty($pokeImage) || !$dreamWorld ) { $pokeImage = $pokeImageDefault; }
    // Stats
    $pokeStats = $poke['stats'];
    // Type
    $pokeType = $poke['types'];    
    // Build type/weak/strengths lists
    $pokeTypeList = array();
    $pokeWeakList = array();
    $pokeStrongList = array();
    foreach ($pokeType as $option) {
        // Types
        $typeName = $option['type']['name'];
        $pokeWeak = get_pokedata($typeName, 'type');
        // Strengths
        $pokeStrong = $pokeWeak['damage_relations']['half_damage_from'];
        // Weaknesses
        $pokeWeak = $pokeWeak['damage_relations']['double_damage_from'];
        // Build list of weaknesses (double damage) taken for this type
        foreach ($pokeWeak as $option) {
            $weakName = $option['name'];
            $weakName = ucwords($weakName);
            if(!in_array($weakName, $pokeWeakList)) {
                array_push($pokeWeakList, $weakName);
            }
        }
        // Build list of strengths (half damage) taken for this type
        foreach ($pokeStrong as $option) {
            $strongName = $option['name'];
            $strongName = ucwords($strongName);
            if(!in_array($strongName, $pokeStrongList)) {
                array_push($pokeStrongList, $strongName);
            }
        }
        $typeName = ucwords($typeName);
        array_push($pokeTypeList, $typeName); // types
    }
    sort($pokeTypeList);
    // Negate conflicting weakness/strength for multi-type pokemon
    foreach ($pokeStrongList as $option) {
        if( in_array($option, $pokeWeakList) ) {
            $key = array_search($option, $pokeWeakList);
            unset($pokeWeakList[$key]);
        }
    }
    sort($pokeWeakList);
    // Pokémon Details
    // Height
    $pokeHeight = $poke['height'];
    $pokeHeight = ($pokeHeight * .1) * 39.3701;
    $pokeHeight = round($pokeHeight, 0);
    $pokeHeight = in_feet($pokeHeight);
    // Weight
    $pokeWeight = $poke['weight'];
    $pokeWeight = ($pokeWeight * .1) / 0.453592;
    $pokeWeight = round($pokeWeight, 1);
    $pokeWeight = $pokeWeight . " lbs.";
    // Abilities
    $pokeSpellsList = array();
    $pokeSpellsNameList = array();
    $pokeHiddenSpellsList = array();
    $pokeHiddenSpellsNameList = array();
    $pokeSpells = $poke['abilities'];
    foreach($pokeSpells as $option) {
        $spellName = $option['ability']['name'];
        $spellURL = $option['ability']['url'];
        $spellURL = get_pokedata($spellURL,'',true);
        $spellList = $spellURL['effect_entries'];
        if(empty($spellList)) { $spellList = $spellURL['flavor_text_entries']; }
        foreach($spellList as $entry) {
            if($entry['language']['name'] == 'en') {
                if(!empty($entry['effect'])) { $spellDesc = $entry['effect']; } 
                else { $spellDesc = $entry['flavor_text']; }
                //$spellShortDesc = $entry['short_effect'];
            }
        }
        $hideSpell = $option['is_hidden'];
        if(!$hideSpell) { 
            $pokeSpellsList[$spellName] = $spellDesc; 
            array_push($pokeSpellsNameList, $spellName);
        }
        else { 
            $pokeHiddenSpellsList[$spellName] = $spellDesc; 
            array_push($pokeHiddenSpellsNameList, $spellName);
        }
    }
    // Pokémon Species
    $pokeSpec = get_pokedata($pokeID, 'pokemon-species');
    // Description
    $pokeStop = false;
    $pokeDescList = array();
    $pokeDesc = $pokeSpec['flavor_text_entries'];
    $pokeDesc = array_reverse($pokeDesc);
    foreach($pokeDesc as $flavor) {
        if($flavor['language']['name'] == 'en') {
            $key = $flavor['version']['name'];
            if($key == 'sword' || $key == 'shield') {
                $pokeDescList[$key] = $flavor['flavor_text'];
            } elseif($pokeStop == false) {
                $pokeDescList['default'] = $flavor['flavor_text'];
                $pokeStop = true;
            }
        }
    }
    // Habitat
    $pokeHabitat = '';
    if(!empty($pokeSpec['habitat'])) {
        $pokeHabitat = $pokeSpec['habitat']['name'];
        //$pokeHabitatURL = $pokeSpec['habitat']['url'];
        $pokeHabitat = ucwords($pokeHabitat);
    }
    // Growth rate
    $pokeGrowthRate = '';
    $pokeGrowthRate = $pokeSpec['growth_rate']['name'];
    $pokeGrowthRate = ucwords($pokeGrowthRate);
    //$pokeGrowthRateURL = $pokeSpec['growth_rate']['url'];
    // Alt names
    $pokeNameAlt = '';
    $pokeNames = $pokeSpec['names'];
    foreach($pokeNames as $name) {
        if($name['language']['name'] == 'ja') { $pokeNameAlt = $name['name']; }
    }
    // Baby check
    $pokeBaby = '';
    $pokeBaby =  $pokeSpec['is_baby'];
    // Legendary check
    $pokeLegendary = '';
    $pokeLegendary =  $pokeSpec['is_legendary'];
    // Mythical check
    $pokeMythical = '';
    $pokeMythical =  $pokeSpec['is_mythical'];
    // Gender
    $pokeGender = $pokeSpec['gender_rate'];
    if($pokeGender == 8) { $pokeGender = "Male";} 
    elseif($pokeGender == 0) { $pokeGender = "Female"; } 
    else { $pokeGender = "Male or Female"; }
    // Category
    $pokeCategory = $pokeSpec['genera'][7]['genus'];
    // Hatch Counter
    $pokeHatch = $pokeSpec['hatch_counter'];
    // Navigation
    // Next
    $pokeNextID = $pokeID + 1;
    $pokePrevID = $pokeID - 1;
    if($pokeNextID > $pokeCount) { $pokeNextID = 1; }
    if($pokePrevID < 1) { $pokePrevID = $pokeCount; }
    $pokeNext = get_pokedata($pokeNextID);
    $pokeNextName =  $pokeNext['species']['name'];
    $pokeNextImage = $pokeNext['sprites']['other']['dream_world']['front_default'];
    if(!$pokeNextImage || !$dreamWorld ) { $pokeNextImage = $pokeNext['sprites']['other']['official-artwork']['front_default']; }
    $pokeNextTitle = ucwords("$pokeNextID $pokeNextName"); // combine ids and names for titles
    $pokeNextClass = sanitize_title($pokeNextTitle);
    // Previous
    $pokePrev = get_pokedata($pokePrevID);
    $pokePrevName =  $pokePrev['species']['name'];
    $pokePrevImage = $pokePrev['sprites']['other']['dream_world']['front_default'];
    if(!$pokePrevImage || !$dreamWorld ) { $pokePrevImage = $pokePrev['sprites']['other']['official-artwork']['front_default']; }
    $pokePrevTitle = ucwords("$pokePrevID $pokePrevName");
    $pokePrevClass = sanitize_title($pokePrevTitle);

    // Evolution Chain
    $pokeChainURL = $pokeSpec['evolution_chain']['url'];
    $pokeColor = $pokeSpec['color']['name'];
    $pokeChain = get_pokedata($pokeChainURL,'',true);
    $pokeEvolutions = array();
    $pokeEvolutions[0] = $pokeChain['chain'];
    $pokeEvol = poke_chain($pokeEvolutions);
    // Create post/page
    $pokeLongID = add_digits($pokeID, $length);
    $postTitle = "#$pokeLongID $pokeName";
    $postTitleClass = "#$pokeID $pokeName";
    $postTitleClass = sanitize_title($postTitleClass);
    if(empty($pokeDescList['default'])) {
        $pokeContent = $pokeDescList['sword'];
    } else {
        $pokeContent = $pokeDescList['default'];
    }
    
    $array = array(
        'post_title'    => "$postTitle",
        'post_type'     => "$tax",
        'post_status'   => "publish",
        'post_name'     => "$postTitleClass",
        'post_author'   => 1,
        'comment_status'=> 'closed',
        'ping_status'   => 'closed',
        'post_content'  => $pokeContent
    );
    // Check if post already exists and update by adding ID to array
    if( post_exists("$postTitle", '', '', "$tax") ) {
        $subarray = array(
            'fields'        => 'ids',
            'numberposts'   => 1,
            'name'          => "$postTitleClass",
            'post_type'     => "$tax",
            'title'         => "$postTitle",
        );
        $found = get_posts($subarray);
        $array = array_reverse($array);
        $array['ID'] = $found[0];
        $array = array_reverse($array);
        echo "<b>Page #$pokeID found</b>....";
        $post_id = wp_update_post( $array );
        $outMsg = "updated successfully.<br/>";
    } else {
        $post_id = wp_insert_post( $array );
        $outMsg = "Page #$pokeID created successfully.<br/>";
    }

    $featImgURL = "$pokeImageDefault";
    //dbug($featImgURL,"feat img");
    $featImgDesc    = "Profile image for $pokeName";
    if(!has_post_thumbnail( $post_id )) {
        $featImage = media_sideload_image( $featImgURL, $post_id, $featImgDesc, 'id' );
    }
    //dbug($featImage,"feat img");
    set_post_thumbnail( $post_id, $featImage );

    // Metadata
    pokepush($post_id, 'pokemon_id', $pokeID);
    pokepush($post_id, 'pokemon_name', $pokeName);
    pokepush($post_id, 'pokemon_color', $pokeColor);
    pokepush($post_id, 'pokemon_image', $pokeImage);
    pokepush($post_id, 'pokemon_height', $pokeHeight);
    pokepush($post_id, 'pokemon_weight', $pokeWeight);
    pokepush($post_id, 'pokemon_gender', $pokeGender);
    pokepush($post_id, 'pokemon_category', $pokeCategory);
    foreach ($pokeStats as $option) {
        $statName = $option['stat']['name'];
        $statValue = $option['base_stat'];
        $statSlug = "pokemon_stat_$statName";
        pokepush($post_id, $statSlug, $statValue);
    }
    $pokeTypeString = implode(",", $pokeTypeList);
    pokepush($post_id, 'pokemon_type', $pokeTypeString);
    $pokeWeakString = implode(",", $pokeWeakList);
    pokepush($post_id, 'pokemon_weakness', $pokeWeakString);
    $pokeSpellsNameString = implode(",", $pokeSpellsNameList);
    pokepush($post_id, 'pokemon_abilities', $pokeSpellsNameString);
    foreach($pokeSpellsList as $spell => $desc) {
        $metaFieldName = 'pokemon_ability_' . $spell;
        pokepush($post_id, $metaFieldName, $desc);
    }
    $pokeHiddenSpellsNameString = implode(",", $pokeHiddenSpellsNameList);
    pokepush($post_id, 'pokemon_abilities_hidden', $pokeHiddenSpellsNameString);
    foreach($pokeHiddenSpellsList as $spell => $desc) {
        $metaFieldName = 'pokemon_ability_hidden_' . $spell;
        pokepush($post_id, $metaFieldName, $desc);
    }
    //pokepush($post_id, 'pokemon_next', $pokeNextTitle);
    pokepush($post_id, 'pokemon_next_image', $pokeNextImage);
    pokepush($post_id, 'pokemon_next_class', $pokeNextClass);
    //pokepush($post_id, 'pokemon_prev', $pokePrevTitle);
    pokepush($post_id, 'pokemon_prev_image', $pokePrevImage);
    pokepush($post_id, 'pokemon_prev_class', $pokePrevClass);
    pokepush($post_id, 'pokemon_habitat', $pokeHabitat);
    pokepush($post_id, 'pokemon_growth_rate', $pokeGrowthRate);
    pokepush($post_id, 'pokemon_is_baby', $pokeBaby);
    pokepush($post_id, 'pokemon_is_legendary', $pokeLegendary);
    pokepush($post_id, 'pokemon_is_mythical', $pokeMythical);
    pokepush($post_id, 'pokemon_name_alt', $pokeNameAlt);
    foreach($pokeDescList as $flavor => $desc) {
        $metaFieldName = 'pokemon_desc_' . $flavor;
        pokepush($post_id, $metaFieldName, $desc);
    }
    pokepush($post_id, 'pokemon_hatch_time', $pokeHatch);
    //dbug($pokeEvol, "Evolutions");
    //pokepush($post_id, 'pokemon_evolution_chain', $pokeEvol);
    update_post_meta( $post_id, 'pokemon_evolution_chain', $pokeEvol );
    // Notification
    echo $outMsg;
}
// END ADD_POKEMON

// POKEPUSH
// Add/update custom fields on post
function pokepush($post_id, $meta, $pokeData) {
    $pokeData = $pokeData;
    $meta = $meta;
    if(!empty($pokeData) || is_array($pokeData)) {
        if(metadata_exists( 'post', $post_id, $meta )) { update_post_meta( $post_id, $meta, $pokeData ); } 
        else { add_post_meta( $post_id, $meta, $pokeData, true ); }
    } else {
        if(metadata_exists( 'post', $post_id, $meta )) { delete_post_meta( $post_id, $meta, $pokeData ); } 
    }
}
