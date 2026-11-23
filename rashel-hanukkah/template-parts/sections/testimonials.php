<?php
/**
 * The quote, two graduate videos and the wall of testimonial screenshots.
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
<section class="rsh-proof rsh-wrap" aria-labelledby="rsh-proof-ttl">
	<div class="rsh-quote" data-rsh-reveal>
		<span class="rsh-quote__mark" aria-hidden="true">״</span>
		<h2 class="rsh-quote__t" id="rsh-proof-ttl"><?php rsh_the_text( 'quote' ); ?></h2>
	</div>

	<div class="rsh-videos">
		<?php foreach ( $rsh_videos as $rsh_video => $rsh_info ) : ?>
			<?php $rsh_captions = rsh_captions_url( $rsh_video ); ?>
			<div class="rsh-video rsh-video--<?php echo esc_attr( $rsh_video ); ?>" data-rsh-reveal>
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

	<h3 class="rsh-sr-only"><?php rsh_the_text( 'wall_ttl' ); ?></h3>
	<ul class="rsh-wall">
		<?php foreach ( rsh_testimonials() as $rsh_item ) : ?>
			<li class="rsh-wall__item" data-rsh-reveal>
				<div class="rsh-wall__card">
					<span class="rsh-wall__tape" aria-hidden="true"></span>
					<?php rsh_image( $rsh_item['file'], $rsh_item['width'], $rsh_item['height'], array( 'alt' => rsh_text( 'wall_alt' ) ) ); ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
