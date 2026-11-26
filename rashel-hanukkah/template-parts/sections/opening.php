<?php
/**
 * Opening: the three hook questions, one at a time, beside the photo collage,
 * the spinning discount badge and the vertical yellow strip.
 *
 * The questions change on their own every few seconds. The dots are buttons
 * that jump to a question, and the pause button stops the rotation (moving
 * content needs a way to stop it: standard 8.5.4). Without JavaScript, or with
 * reduced motion, all three questions are simply listed.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_questions = array( 'hook_q1', 'hook_q2', 'hook_q3' );
?>
<section class="rsh-open">
	<div class="rsh-open__logo">
		<?php
		rsh_slot_image(
			'logo',
			array(
				'class'         => 'rsh-logo rsh-logo--light',
				'alt'           => get_bloginfo( 'name' ),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);
		?>
	</div>

	<div class="rsh-open__text">
		<div class="rsh-hook" data-rsh-hook>
			<?php foreach ( $rsh_questions as $rsh_n => $rsh_key ) : ?>
				<p class="rsh-hook__q<?php echo 0 === $rsh_n ? ' is-active' : ''; ?>" data-rsh-q>
					<?php foreach ( rsh_parse_marked_text( rsh_text( $rsh_key ) ) as $rsh_line => $rsh_segments ) : ?>
						<?php echo $rsh_line ? '<br>' : ''; ?>
						<?php rsh_marked_line( $rsh_segments ); ?>
					<?php endforeach; ?>
				</p>
			<?php endforeach; ?>
		</div>

		<div class="rsh-hook__nav" data-rsh-hook-nav>
			<?php foreach ( $rsh_questions as $rsh_n => $rsh_key ) : ?>
				<button type="button" class="rsh-hook__dot<?php echo 0 === $rsh_n ? ' is-active' : ''; ?>" data-rsh-dot="<?php echo esc_attr( (string) $rsh_n ); ?>" aria-label="<?php echo esc_attr( rsh_text( 'hook_dot' ) . ' ' . ( $rsh_n + 1 ) ); ?>"<?php echo 0 === $rsh_n ? ' aria-current="true"' : ''; ?>><span></span></button>
			<?php endforeach; ?>
			<button type="button" class="rsh-hook__pause" data-rsh-hook-pause data-rsh-label-play="<?php echo esc_attr( rsh_text( 'hook_play' ) ); ?>" data-rsh-label-pause="<?php echo esc_attr( rsh_text( 'hook_pause' ) ); ?>" aria-label="<?php echo esc_attr( rsh_text( 'hook_pause' ) ); ?>" aria-pressed="false">
				<svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true" focusable="false"><path class="rsh-hook__pause-i" d="M2 1h3v10H2zM7 1h3v10H7z"/><path class="rsh-hook__play-i" d="M2.5 1l8 5-8 5z"/></svg>
			</button>
		</div>

		<a class="rsh-open__next rsh-float" href="#announce" aria-label="<?php echo esc_attr( rsh_text( 'hook_next' ) ); ?>"><span aria-hidden="true">↓</span></a>
	</div>

	<div class="rsh-open__collage">
		<?php rsh_ticker( 'vertical' ); ?>

		<?php foreach ( rsh_mosaic() as $rsh_n => $rsh_pair ) : ?>
			<?php
			// The photos are cropped to their tile, so the tall first tile needs a wider file than its own width.
			$rsh_sizes = 0 === $rsh_n ? '(min-width: 960px) 80vw, 110vw' : '(min-width: 960px) 40vw, 60vw';
			?>
			<div class="rsh-tile rsh-tile--<?php echo esc_attr( (string) ( $rsh_n + 1 ) ); ?>" data-rsh-tile aria-hidden="true">
				<?php
				// The first photo paints at once (it is the largest thing on the first screen); the second arrives after load.
				rsh_slot_image(
					$rsh_pair[0],
					array(
						'class'         => 'rsh-tile__img is-on',
						'loading'       => 'eager',
						'fetchpriority' => 0 === $rsh_n ? 'high' : false,
						'sizes'         => $rsh_sizes,
					)
				);
				?>
				<img class="rsh-tile__img" alt="" width="1200" height="800" decoding="async" sizes="<?php echo esc_attr( $rsh_sizes ); ?>" data-rsh-src="<?php echo esc_url( rsh_slot_url( $rsh_pair[1] ) ); ?>" data-rsh-srcset="<?php echo esc_attr( rsh_slot_srcset( $rsh_pair[1] ) ); ?>">
			</div>
		<?php endforeach; ?>

		<?php rsh_badge( 'rsh-badge--open' ); ?>
	</div>
</section>
