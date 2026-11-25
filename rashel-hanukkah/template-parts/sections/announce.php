<?php
/**
 * Announcement: the main heading, word by word, over the dark photo; the two
 * paragraphs, the call to action, the countdown and the discount badge.
 *
 * The falling confetti and the one-time burst are added by landing.js, and
 * only when motion is allowed.
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
<section id="announce" class="rsh-announce">
	<?php
	rsh_image(
		'announce-bg.webp',
		1600,
		1067,
		array(
			'class'  => 'rsh-announce__bg',
			'srcset' => rsh_asset( 'assets/img/announce-bg-800.webp' ) . ' 800w, ' . rsh_asset( 'assets/img/announce-bg.webp' ) . ' 1600w',
			'sizes'  => '100vw',
		)
	);
	?>
	<div class="rsh-announce__shade" aria-hidden="true"></div>
	<div class="rsh-announce__confetti" aria-hidden="true" data-rsh-confetti></div>

	<div class="rsh-announce__in">
		<h1 class="rsh-h1" data-rsh-h1>
			<span class="rsh-sr-only"><?php echo esc_html( rsh_text( 'h1_line1' ) . ' ' . rsh_text( 'h1_line2' ) ); ?></span>
			<span class="rsh-h1__line" aria-hidden="true">
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

		<?php
		rsh_cta(
			array(
				'variant' => 'beige',
				'pulse'   => true,
				'class'   => 'rsh-cta--lg',
			)
		);
		?>

		<div class="rsh-announce__row">
			<?php
			rsh_countdown( 'yellow' );
			rsh_badge( 'rsh-badge--announce' );
			?>
		</div>
	</div>
</section>
