<?php
/**
 * Graduates' work: a photo grid managed in the dashboard.
 *
 * The section is printed only when photos were chosen. Every photo is in the
 * markup; with JavaScript the grid opens nine at a time ("show more"), and
 * without it everything is visible.
 *
 * Complete groups of nine follow the pattern of the design (one tall, three
 * square, one wide, one tall, three square), which fills the grid without
 * gaps in four columns and in two. Photos beyond the last complete group are
 * plain squares.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_works = rsh_media_ids( 'works' );

if ( ! $rsh_works ) {
	return;
}

$rsh_total     = count( $rsh_works );
$rsh_patterned = $rsh_total - ( $rsh_total % RSH_WORKS_BATCH );
$rsh_shapes    = array(
	0 => 'tall',
	4 => 'wide',
	5 => 'tall',
);
?>
<section class="rsh-works" aria-labelledby="rsh-works-ttl" data-rsh-works>
	<div class="rsh-wrap">
		<h2 class="rsh-works__ttl" id="rsh-works-ttl" data-rsh-reveal><?php rsh_the_text( 'works_ttl' ); ?></h2>

		<ul class="rsh-works__grid is-first<?php echo $rsh_patterned ? '' : ' rsh-works__grid--plain'; ?>" data-rsh-works-grid>
			<?php foreach ( $rsh_works as $rsh_n => $rsh_id ) : ?>
				<?php
				$rsh_shape = $rsh_n < $rsh_patterned ? ( $rsh_shapes[ $rsh_n % RSH_WORKS_BATCH ] ?? '' ) : '';
				$rsh_class = 'rsh-work' . ( '' !== $rsh_shape ? ' rsh-work--' . $rsh_shape : '' ) . ( $rsh_n >= RSH_WORKS_BATCH ? ' is-shut' : '' );
				?>
				<li class="<?php echo esc_attr( $rsh_class ); ?>" data-rsh-batch="<?php echo esc_attr( (string) intdiv( $rsh_n, RSH_WORKS_BATCH ) ); ?>">
					<?php
					echo wp_get_attachment_image(
						$rsh_id,
						'medium_large',
						false,
						array(
							'alt'     => rsh_attachment_alt( $rsh_id, rsh_text( 'works_alt' ) ),
							'loading' => 'lazy',
							'sizes'   => 'wide' === $rsh_shape ? '(min-width: 960px) 600px, 92vw' : '(min-width: 960px) 300px, 46vw',
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $rsh_total > 6 ) : ?>
			<p class="rsh-works__more">
				<button type="button" class="rsh-works__btn" data-rsh-works-more><?php rsh_the_text( 'works_more' ); ?> <span aria-hidden="true">+</span></button>
			</p>
		<?php endif; ?>

		<div class="rsh-works__foot">
			<?php
			rsh_cta(
				array(
					'href'    => '#courses',
					'variant' => 'yellow',
				)
			);
			?>
		</div>
	</div>
</section>
