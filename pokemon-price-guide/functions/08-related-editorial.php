<?php
/**
 * Related editorial content for the connected Pokemon vertical slice.
 */

function ptp_priceguide_related_editorial_tag( $pokemon_name ) {
    $pokemon_slug = sanitize_title( (string) $pokemon_name );

    if ( '' === $pokemon_slug ) {
        return '';
    }

    return 'pokemon-' . $pokemon_slug;
}

function ptp_priceguide_get_related_editorial_posts( $pokemon_name, $limit = 3 ) {
    $tag_slug = ptp_priceguide_related_editorial_tag( $pokemon_name );

    if ( '' === $tag_slug ) {
        return array();
    }

    return get_posts(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => max( 1, min( 6, (int) $limit ) ),
            'orderby'             => 'date',
            'order'               => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'tax_query'           => array(
                array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => array( $tag_slug ),
                ),
            ),
        )
    );
}

function ptp_priceguide_related_editorial_excerpt( $post, $limit = 24 ) {
    if ( ! is_object( $post ) ) {
        return '';
    }

    $excerpt = isset( $post->post_excerpt ) ? (string) $post->post_excerpt : '';

    if ( '' === trim( $excerpt ) && isset( $post->post_content ) ) {
        $excerpt = (string) $post->post_content;
    }

    $excerpt = strip_shortcodes( $excerpt );
    $excerpt = wp_strip_all_tags( $excerpt );

    return wp_trim_words( $excerpt, max( 1, (int) $limit ), '…' );
}

function ptp_priceguide_get_related_editorial_html( $pokemon_name, $limit = 3 ) {
    $posts = ptp_priceguide_get_related_editorial_posts( $pokemon_name, $limit );

    if ( empty( $posts ) ) {
        return '';
    }

    $pokemon_label = trim( wp_strip_all_tags( (string) $pokemon_name ) );
    $heading_id    = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'related-editorial-' ) : 'related-editorial';
    $content       = '<section class="related-editorial" aria-labelledby="' . esc_attr( $heading_id ) . '">';
    $content      .= '<h2 id="' . esc_attr( $heading_id ) . '">' . esc_html( sprintf( __( 'More %s stories', 'primetimepriceguide' ), $pokemon_label ) ) . '</h2>';
    $content      .= '<ul class="related-editorial__list">';

    foreach ( $posts as $post ) {
        $title   = isset( $post->post_title ) ? wp_strip_all_tags( (string) $post->post_title ) : '';
        $url     = get_permalink( $post );
        $excerpt = ptp_priceguide_related_editorial_excerpt( $post );

        $content .= '<li class="related-editorial__item">';
            $content .= '<h3 class="related-editorial__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';

            if ( '' !== trim( $excerpt ) ) {
                $content .= '<p class="related-editorial__excerpt">' . esc_html( $excerpt ) . '</p>';
            }
        $content .= '</li>';
    }

    $content .= '</ul>';
    $content .= '</section>';

    return $content;
}

function ptp_priceguide_related_editorial_shortcode( $attributes ) {
    $attributes = shortcode_atts(
        array(
            'pokemon' => '',
            'limit'   => 3,
        ),
        $attributes,
        'veefun_related_editorial'
    );

    return ptp_priceguide_get_related_editorial_html( $attributes['pokemon'], $attributes['limit'] );
}

add_shortcode( 'veefun_related_editorial', 'ptp_priceguide_related_editorial_shortcode' );
?>
