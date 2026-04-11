<?php
/**
 * Plugin-owned archive shell for the Pokedex post type.
 */

get_header();

$archive_description = get_the_archive_description();
?>

<main id="main" class="site-main pokedex-archive-main" role="main">
	<header class="page-header pokedex-archive-header">
		<h1 class="page-title"><?php post_type_archive_title(); ?></h1>

		<?php if ( ! empty( $archive_description ) ) : ?>
			<div class="taxonomy-description">
				<?php echo wp_kses_post( $archive_description ); ?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="pokedex-archive-list">
			<?php
			while ( have_posts() ) :
				the_post();

				$post_id      = get_the_ID();
				$pokemon_id   = pokedex_get_archive_card_id( $post_id );
				$pokemon_name = pokedex_get_archive_card_title( $post_id );
				$image_url    = pokedex_get_archive_card_image_url( $post_id );
				$image_alt    = $pokemon_name;

				if ( ! empty( $pokemon_id ) ) {
					$image_alt = '#' . $pokemon_id . ' ' . $pokemon_name;
				}
				?>
				<article <?php post_class( 'pokedex pokedex-archive-card' ); ?>>
					<a class="pokedex-archive-card__link" href="<?php the_permalink(); ?>">
						<header class="entry-header">
							<?php if ( ! empty( $image_url ) ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" decoding="async" />
							<?php endif; ?>

							<div class="pokedex-archive-card__content">
								<h2 class="entry-title"><?php echo esc_html( $pokemon_name ); ?></h2>

								<?php if ( ! empty( $pokemon_id ) ) : ?>
									<span class="pokemon-id"><sup>#</sup><?php echo esc_html( $pokemon_id ); ?></span>
								<?php endif; ?>
							</div>
						</header>
					</a>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="pokedex-archive-pagination">
			<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
		</div>
	<?php else : ?>
		<div class="pokedex-archive-empty">
			<p><?php esc_html_e( 'No Pokemon found.', 'veefun' ); ?></p>
		</div>
	<?php endif; ?>
</main>

<?php
get_footer();
