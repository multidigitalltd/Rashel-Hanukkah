<?php
/**
 * Testimonials: the quote, two video testimonials and a carousel of the
 * messages graduates sent.
 *
 * The carousel is a plain horizontal scroller: it works by touch, by trackpad
 * and from the keyboard on its own, and never moves by itself. The two arrow
 * buttons and the progress bar are an enhancement (landing.js).
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_videos = array(
	'v01' => array( 'video1_ttl', 848 ),
	'v02' => array( 'video2_ttl', 854 ),
);
?>
<section class="rsh-proof" aria-labelledby="rsh-proof-ttl">
	<div class="rsh-wrap">
		<figure class="rsh-quote" data-rsh-reveal>
			<span class="rsh-quote__mark" aria-hidden="true">״</span>
			<blockquote class="rsh-quote__t"><?php rsh_the_text( 'quote' ); ?></blockquote>
		</figure>

		<div class="rsh-videos">
			<?php foreach ( $rsh_videos as $rsh_video => $rsh_info ) : ?>
				<?php $rsh_captions = rsh_captions_url( $rsh_video ); ?>
				<div class="rsh-video" data-rsh-reveal>
					<video controls playsinline preload="metadata" width="<?php echo esc_attr( (string) $rsh_info[1] ); ?>" height="480"
						poster="<?php echo esc_url( rsh_asset( 'assets/img/testimonials/' . $rsh_video . '-poster.webp' ) ); ?>"
						aria-label="<?php echo esc_attr( rsh_text( $rsh_info[0] ) ); ?>">
						<source src="<?php echo esc_url( rsh_asset( 'assets/video/' . $rsh_video . '.mp4' ) ); ?>" type="video/mp4">
						<?php if ( '' !== $rsh_captions ) : ?>
							<track kind="captions" srclang="he" label="<?php esc_attr_e( 'עברית', 'rashel-hanukkah' ); ?>" src="<?php echo esc_url( $rsh_captions ); ?>" default>
						<?php endif; ?>
					</video>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="rsh-reviews" data-rsh-reviews>
			<div class="rsh-reviews__head">
				<h2 class="rsh-reviews__ttl" id="rsh-proof-ttl" data-rsh-reveal><?php rsh_the_text( 'wall_ttl' ); ?></h2>
				<div class="rsh-reviews__nav">
					<button type="button" class="rsh-reviews__btn rsh-reviews__btn--prev" data-rsh-reviews-prev aria-label="<?php echo esc_attr( rsh_text( 'wall_prev' ) ); ?>"><span aria-hidden="true">→</span></button>
					<button type="button" class="rsh-reviews__btn rsh-reviews__btn--next" data-rsh-reviews-next aria-label="<?php echo esc_attr( rsh_text( 'wall_next' ) ); ?>"><span aria-hidden="true">←</span></button>
				</div>
			</div>

			<ul class="rsh-reviews__track" data-rsh-reviews-track tabindex="0" aria-labelledby="rsh-proof-ttl">
				<?php foreach ( rsh_testimonials() as $rsh_item ) : ?>
					<li class="rsh-review">
						<div class="rsh-review__shot">
							<?php rsh_image( $rsh_item['file'], $rsh_item['width'], $rsh_item['height'], array( 'alt' => rsh_text( 'wall_alt' ) ) ); ?>
						</div>
						<p class="rsh-review__stars"><span aria-hidden="true">★★★★★</span><span class="rsh-sr-only"><?php rsh_the_text( 'wall_stars' ); ?></span></p>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="rsh-reviews__bar" aria-hidden="true"><span data-rsh-reviews-bar></span></div>
		</div>
	</div>
</section>
