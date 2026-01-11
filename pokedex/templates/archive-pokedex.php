<?php
/**
 * @package storefront
 */

get_header();

?>

<main id="main" class="archive-main site-main md:th-px-4xl th-px-md th-w-full th-stack--2xl">

	<?php
	if ( have_posts() ) {

		get_template_part( 'template-parts/global/header' );



		while ( have_posts() ) :
			the_post();
			?><style>.type-page{ display:none;} .type-post{display:none;}</style><?php
			get_template_part( 'template-parts/global/content', 'search' );

		endwhile;



		the_posts_pagination( array( 'mid_size' => 2 ) );

	} else {

		get_template_part( 'template-parts/global/content', 'none' );

	}
	?>

</main><!-- #main -->

<?php

get_footer();
