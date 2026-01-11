<?php
/**
 * Template part for single Pokedex entry content.
 * 
 * This file contains only the markup - no header/footer, no function declarations.
 * Used via output buffering in render-single.php.
 * 
 * Helper functions are loaded from includes/pokedex-render-helpers.php
 */

// Pokemon Details - Incoming as basic string...
$category = 			ptp_hokepoke('pokemon_category');
$gender = 				ptp_hokepoke('pokemon_gender');
$height = 				ptp_hokepoke('pokemon_height');
$current_image = 		ptp_hokepoke('pokemon_image');
$exists = ptp_remote_file_exists($current_image);
if (!$exists) {
    $file_name = basename($current_image);
    $current_image = get_bloginfo('url').'/wp-content/plugins/primetime-pokedex/pokeimg/'.$file_name;
} 
$current_id =			ptp_hokepoke('pokemon_id');
$current_name =			ptp_hokepoke('pokemon_name');
$current_name_alt =		ptp_hokepoke('pokemon_name_alt');
$next = 				ptp_hokepoke('pokemon_next');
$next_class = 			ptp_hokepoke('pokemon_next_class');
$next_image = 			ptp_hokepoke('pokemon_next_image');
$prev = 				ptp_hokepoke('pokemon_prev');
$prev_class = 			ptp_hokepoke('pokemon_prev_class');
$prev_image = 			ptp_hokepoke('pokemon_prev_image');
$stat_attack = 			ptp_hokepoke('pokemon_stat_attack');
$stat_defense = 		ptp_hokepoke('pokemon_stat_defense');
$stat_hp = 				ptp_hokepoke('pokemon_stat_hp');
$stat_sattack = 		ptp_hokepoke('pokemon_stat_special-attack');
$stat_sdefense = 		ptp_hokepoke('pokemon_stat_special-defense');
$stat_speed = 			ptp_hokepoke('pokemon_stat_speed');
$weight = 				ptp_hokepoke('pokemon_weight');
$desc_shield = 	    	ptp_hokepoke('pokemon_desc_shield');
$desc_sword = 	    	ptp_hokepoke('pokemon_desc_sword');
$desc = 				ptp_hokepoke('pokemon_desc_default');
$habitat = 				ptp_hokepoke('pokemon_habitat');
$growth_rate =	    	ptp_hokepoke('pokemon_growth_rate');
$is_baby =          	ptp_hokepoke('pokemon_is_baby');
$is_legendary =     	ptp_hokepoke('pokemon_is_legendary');
$is_mythical =      	ptp_hokepoke('pokemon_is_mythical');
$hatch_time =       	ptp_hokepoke('pokemon_hatch_time');

// Incoming as comma-seperated list...
$abilities = 			ptp_hokepoke('pokemon_abilities'); 
$abilities_hidden = 	ptp_hokepoke('pokemon_abilities_hidden');
$evolutions = 			ptp_hokepoke('pokemon_evolution_chain');
$types = 				ptp_hokepoke('pokemon_type');
$weaknesses = 			ptp_hokepoke('pokemon_weakness');

// ...convert lists to arrays...
$abilities =	 		explode(",", $abilities);
$abilities_hidden = 	explode(",", $abilities_hidden);
$types = 				explode(",", $types);
$weaknesses = 			explode(",", $weaknesses);

// Set prev/next IDs
if($current_id == 1025) { $next_id = 1; }
else { $next_id = $current_id + 1; }
if($current_id == 1) { $prev_id = 1025; }
else { $prev_id = $current_id - 1; }

?>

<a name="Pokedex"></a>

<section id="pokedex">

<nav class="pokenav">
	<div class="pokenav-link pokenev-left">
		<a class="poke-nav-left" href="<?php echo esc_url( site_url( '/pokedex/' . $prev_class ) ); ?>" title="<?php echo esc_attr( $prev ); ?>" aria-label="Previous Pokémon">
			<span class="pokedex-nav-icon pokedex-nav-icon--left" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
					<path d="M15.5 4.5a1 1 0 0 1 0 1.4L9.4 12l6.1 6.1a1 1 0 1 1-1.4 1.4l-6.8-6.8a1 1 0 0 1 0-1.4l6.8-6.8a1 1 0 0 1 1.4 0z"/>
				</svg>
			</span>
			<span class="screen-reader-text">Previous Pokémon</span>
			<span class="poke-nav-thumb" style="background-image: url('<?php echo esc_url( $prev_image ); ?>');"></span>
		</a>
	</div>
	<h1>
		<span class="poke-name"><sup>#</sup><?php echo esc_html( $current_id ); ?> <b><?php echo esc_html( $current_name ); ?></b></span>
	</h1>
	<div class="pokenav-link pokenav-right">
		<a class="poke-nav-right" href="<?php echo esc_url( site_url( '/pokedex/' . $next_class ) ); ?>" aria-label="Next Pokémon">
			<span class="pokedex-nav-icon pokedex-nav-icon--right" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
					<path d="M8.5 4.5a1 1 0 0 0 0 1.4L14.6 12l-6.1 6.1a1 1 0 1 0 1.4 1.4l6.8-6.8a1 1 0 0 0 0-1.4L9.9 4.5a1 1 0 0 0-1.4 0z"/>
				</svg>
			</span>
			<span class="screen-reader-text">Next Pokémon</span>
			<span class="poke-nav-thumb" style="background-image: url('<?php echo esc_url( $next_image ); ?>');"></span>
		</a>
	</div>
</nav>

<article id="pokemon" class="pokemon-container <?php if($is_baby) : echo ' is-baby'; endif; if($is_legendary) : echo ' is-legendary'; endif; if($is_mythical) : echo ' is-mythical'; endif; ?>">
	<div class="poke-profile">
		<div class="poke-image">
			<div class="poke-image-placeholder">
				<div class="shine"></div>
				<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" style="enable-background:new 0 0 1000 1000;" xml:space="preserve">
					<circle class="st0" cx="500" cy="500" r="302.8"></circle>
					<circle class="st1" cx="500" cy="500" r="237.7"></circle>
					<circle class="st2" cx="500" cy="500" r="366.8" transform="rotate(0 500 500)"></circle>
				</svg>
				<div class="poke-overview">
					<h1><?php echo esc_html( $current_name ); ?></h1>
					<span class="poke-cat"><?php echo esc_html( $category ); ?></span>
					<?php
						if($is_baby)      { echo "<span class='poke-add'>(Baby)</span>"; }
						if($is_legendary) { echo "<span class='poke-add'>(Legendary)</span>"; }
						if($is_mythical)  { echo "<span class='poke-add'>(Mythical)</span>"; }
					?>
					<span class="poke-id"><sup>#</sup><?php echo esc_html( $current_id ); ?></span>
				</div>
				<img src="<?php echo esc_url( $current_image ); ?>" alt="<?php echo esc_attr( 'Pokemon #' . $current_id . ' ' . $current_name ); ?>" />
				<div class="poke-stats">
					<?php ptp_pokestats($stat_hp, $stat_attack, $stat_defense, $stat_sattack, $stat_sdefense, $stat_speed); ?>
				</div>
			</div>
		</div>
		<div class="poke-summary" style="flex-wrap: wrap;">
			<h1 class="summary-title">
				Pokédex • Pokémon <sup>#</sup><?php echo esc_html( $current_id ); ?> • <?php echo esc_html( $current_name ); ?>
			</h1>
			<div class="poke-card poke-card-clean poke-description">
				<h2>Pokémon Description</h2>
				<?php
					if($desc_sword) { echo "<p title='Sword'>" . esc_html( $desc_sword ) . "</p>"; }
					if($desc_shield) { echo "<p title='Shield'>" . esc_html( $desc_shield ) . "</p>"; }
					if(!$desc_sword && !$desc_shield) { echo "<p>" . esc_html( $desc ) . "</p>"; }
				?>
			</div>
			<div class="poke-card poke-card-clean poke-types">
				<h2>Pokémon Type</h2>
				<?php 
					$typeColors = ptp_poketypes($types);
					if(count($typeColors) > 1) {
						$typeColors = implode(",", $typeColors);
						echo "<style>.poke-image-placeholder, .poke-image-description { background: linear-gradient(to right, $typeColors )!important; }</style>";
					} else {
						$typeColors = implode(",", $typeColors);
						echo "<style>.poke-image-placeholder, .poke-image-description { background: $typeColors!important; }</style>";
					}
				?>
			</div>
			<div class="poke-card poke-card-clean poke-weaks">
				<h2>Weaknesses</h2>
				<?php ptp_pokeweaks($weaknesses); ?>
			</div>
		</div>
		<div class="clear"></div>
		<div class="poke-image">
			<div class="poke-card poke-card-clean poke-chain">
				<h2>Evolutions</h2>
				<ul class="poke-chain flex-list clean-list">
				<?php
					if ( ! empty( $evolutions ) && is_array( $evolutions ) ) {
						foreach($evolutions as $array) {
							if ( ! is_array( $array ) ) {
								continue;
							}
							echo "<li class='poke-chain-link'>";
							echo "<ul>";
							foreach($array as $pokemon) {
								if ( ! is_array( $pokemon ) || count( $pokemon ) < 2 ) {
									continue;
								}
								$chain_title = $pokemon[0];
								$chain_image = $pokemon[1];
								
								// Extract ID from title (format: "#123 Name")
								$chain_id_parts = explode(" ", $chain_title);
								$chain_id_str = isset( $chain_id_parts[0] ) ? trim( $chain_id_parts[0], '#' ) : '';
								$chain_id = absint( $chain_id_str );
								
								// Build class string
								$item_classes = 'poke-chain-item';
								if ( $chain_id == $current_id ) {
									$item_classes .= ' current';
								}
								
								// Build URL
								$chain_url = esc_url( site_url( '/pokedex/' . sanitize_title( $chain_title ) ) );
								
								// Output complete HTML
								echo "<li class='" . esc_attr( $item_classes ) . "'>";
								echo '<a href="' . $chain_url . '">';
								echo '<div class="poke-chain-link-image"><img src="' . esc_url( $chain_image ) . '" alt="' . esc_attr( $chain_title ) . '" /><i class="fas fa-chevron-double-right arrow-chain-icon"></i></div>';
								echo "<h5>" . esc_html( $chain_title ) . "</h5>";
								echo "</a>";
								echo "</li>";
							}
							echo "</ul>";
							echo "</li>";
						}
					}
				?>
				</ul>
			</div>
		</div>
		<div class="poke-summary">
			<div class="poke-card poke-card-clean poke-details">
				<h2>Pokémon Details</h2>
				<ul class="poke-details-list clean-list">
					<li><span>Height</span><span><?php echo esc_html( $height ); ?></span></li>
					<li><span>Weight</span><span><?php echo esc_html( $weight ); ?></span></li>
					<li><span>Gender</span><span><?php echo esc_html( $gender ); ?></span></li>
					<?php if(!empty($habitat)) : ?>
					<li><span>Habitat</span><span><?php echo esc_html( $habitat ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
		<div class="clear"></div>
		<div class="poke-image">
			<?php if(!empty($abilities[0])): ?>
				<div class="poke-card">
					<h2>Abilities</h2>
					<?php ptp_pokespells($abilities); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="poke-summary">
			<?php if(!empty($abilities_hidden[0])): ?>
				<div class="poke-card poke-spells-hidden">
					<h2>Hidden Abilities</h2>
					<?php ptp_pokespells($abilities_hidden, true); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="clear"></div>
		<?php echo do_shortcode('[primetime-related name="' . esc_attr( $current_name ) . '"]'); ?>
	</div>
</article>
<div class="clear"></div>
<nav class="pokenav">
	<div class="pokenav-link pokenev-left">
		<a class="poke-nav-left" href="<?php echo esc_url( site_url( '/pokedex/' . $prev_class ) ); ?>" title="<?php echo esc_attr( $prev ); ?>" aria-label="Previous Pokémon">
			<span class="pokedex-nav-icon pokedex-nav-icon--left" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
					<path d="M15.5 4.5a1 1 0 0 1 0 1.4L9.4 12l6.1 6.1a1 1 0 1 1-1.4 1.4l-6.8-6.8a1 1 0 0 1 0-1.4l6.8-6.8a1 1 0 0 1 1.4 0z"/>
				</svg>
			</span>
			<span class="screen-reader-text">Previous Pokémon</span>
			<span class="poke-nav-thumb" style="background-image: url('<?php echo esc_url( $prev_image ); ?>');"></span>
		</a>
	</div>
	<div class="pokenav-link pokenav-right">
		<a class="poke-nav-right" href="<?php echo esc_url( site_url( '/pokedex/' . $next_class ) ); ?>" aria-label="Next Pokémon">
			<span class="pokedex-nav-icon pokedex-nav-icon--right" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
					<path d="M8.5 4.5a1 1 0 0 0 0 1.4L14.6 12l-6.1 6.1a1 1 0 1 0 1.4 1.4l6.8-6.8a1 1 0 0 0 0-1.4L9.9 4.5a1 1 0 0 0-1.4 0z"/>
				</svg>
			</span>
			<span class="screen-reader-text">Next Pokémon</span>
			<span class="poke-nav-thumb" style="background-image: url('<?php echo esc_url( $next_image ); ?>');"></span>
		</a>
	</div>
</nav>

</section>
