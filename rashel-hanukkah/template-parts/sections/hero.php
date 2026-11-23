<?php
/**
 * Hero: the scroll-pinned opening questions, the announcement with the main
 * heading and the countdown, and the benefits strip.
 *
 * Without JavaScript, or with reduced motion, the three questions are simply
 * stacked and nothing is pinned: the content is complete either way.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_line1 = preg_split( '/\s+/u', trim( rsh_text( 'h1_line1' ) ) );
$rsh_line2 = preg_split( '/\s+/u', trim( rsh_text( 'h1_line2' ) ) );
$rsh_line1 = is_array( $rsh_line1 ) ? $rsh_line1 : array();
$rsh_line2 = is_array( $rsh_line2 ) ? $rsh_line2 : array();
?>
<section class="rsh-hero">
	<div class="rsh-hero__glow" aria-hidden="true"></div>
	<?php for ( $rsh_star = 1; $rsh_star <= 8; $rsh_star++ ) : ?>
		<span class="rsh-star rsh-star--<?php echo esc_attr( (string) $rsh_star ); ?>" aria-hidden="true"></span>
	<?php endfor; ?>

	<div class="rsh-wrap rsh-hero__top">
		<?php
		rsh_image(
			'rashel-logo.png',
			270,
			191,
			array(
				'class'         => 'rsh-logo',
				'alt'           => get_bloginfo( 'name' ),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);
		?>
	</div>

	<div class="rsh-hook" data-rsh-hook>
		<div class="rsh-hook__stage">
			<?php foreach ( rsh_hook_images() as $rsh_index => $rsh_photo ) : ?>
				<div class="rsh-hook__img rsh-hook__img--<?php echo esc_attr( (string) ( $rsh_index + 1 ) ); ?><?php echo 1 === $rsh_photo['step'] ? ' is-on' : ''; ?>" data-rsh-step="<?php echo esc_attr( (string) $rsh_photo['step'] ); ?>" aria-hidden="true">
					<div class="rsh-hook__img-in">
						<?php rsh_image( $rsh_photo['file'], $rsh_photo['width'], $rsh_photo['height'] ); ?>
					</div>
				</div>
			<?php endforeach; ?>

			<div class="rsh-hook__text">
				<div class="rsh-hook__qs">
					<?php foreach ( array( 'hook_q1', 'hook_q2', 'hook_q3' ) as $rsh_key ) : ?>
						<p class="rsh-hook__q<?php echo 'hook_q1' === $rsh_key ? ' is-active' : ''; ?>" data-rsh-q>
							<?php foreach ( rsh_parse_marked_text( rsh_text( $rsh_key ) ) as $rsh_n => $rsh_segments ) : ?>
								<?php echo $rsh_n ? '<br>' : ''; ?>
								<?php rsh_marked_line( $rsh_segments ); ?>
							<?php endforeach; ?>
						</p>
					<?php endforeach; ?>
				</div>
				<div class="rsh-hook__more">
					<a class="rsh-hook__next rsh-float" href="#announce" aria-label="<?php echo esc_attr( rsh_text( 'hook_next' ) ); ?>"><span aria-hidden="true">↓</span></a>
				</div>
			</div>
		</div>
	</div>

	<div id="announce">
		<div class="rsh-wrap rsh-divider" aria-hidden="true"><span></span><i>✦</i><span></span></div>

		<div class="rsh-wrap rsh-announce">
			<div class="rsh-announce__text">
				<h1 class="rsh-h1" data-rsh-h1>
					<span class="rsh-sr-only"><?php echo esc_html( rsh_text( 'h1_line1' ) . ' ' . rsh_text( 'h1_line2' ) ); ?></span>
					<span class="rsh-h1__line rsh-h1__line--lit" aria-hidden="true">
						<?php foreach ( $rsh_line1 as $rsh_n => $rsh_word ) : ?>
							<span class="rsh-h1__w" style="<?php echo esc_attr( '--rsh-d:' . ( $rsh_n * 260 ) . 'ms' ); ?>"><?php echo esc_html( $rsh_word ); ?></span>
						<?php endforeach; ?>
					</span>
					<span class="rsh-h1__line" aria-hidden="true">
						<?php foreach ( $rsh_line2 as $rsh_n => $rsh_word ) : ?>
							<span class="rsh-h1__mask"><span class="rsh-h1__up" style="<?php echo esc_attr( '--rsh-d:' . ( 900 + $rsh_n * 120 ) . 'ms' ); ?>"><?php echo esc_html( $rsh_word ); ?></span></span>
						<?php endforeach; ?>
					</span>
				</h1>

				<p class="rsh-announce__lead"><?php rsh_the_text( 'lead' ); ?></p>
				<p class="rsh-announce__sub"><?php rsh_the_text( 'sub' ); ?></p>
				<div>
					<?php
					rsh_cta(
						array(
							'pulse' => true,
							'class' => 'rsh-cta--glow',
						)
					);
					?>
				</div>
				<?php rsh_countdown( 'lg' ); ?>
			</div>

			<div class="rsh-announce__visual">
				<div class="rsh-arch rsh-arch--tall rsh-float">
					<?php
					rsh_image(
						'fruit-bar.webp',
						1200,
						800,
						array(
							'alt'    => rsh_text( 'arch_alt' ),
							'srcset' => rsh_asset( 'assets/img/fruit-bar-600.webp' ) . ' 600w, ' . rsh_asset( 'assets/img/fruit-bar.webp' ) . ' 1200w',
							'sizes'  => '(min-width: 1320px) 800px, 90vw',
						)
					);
					?>
				</div>
				<div class="rsh-announce__stack">
					<p class="rsh-badge rsh-wobble">
						<span class="rsh-badge__big"><?php rsh_the_text( 'badge_big' ); ?></span>
						<span class="rsh-badge__small"><?php rsh_the_text( 'badge_small' ); ?></span>
					</p>
					<div class="rsh-arch rsh-arch--short">
						<?php rsh_image( 'courses/patisserie.webp', 497, 258, array( 'alt' => rsh_text( 'arch2_alt' ) ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php rsh_benefits(); ?>
</section>
