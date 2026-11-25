<?php
/**
 * One course card. $args: course (an entry of rsh_courses()).
 *
 * The card's accent comes from the course's place in the menu. "--rsh-c" is
 * the design colour of the head; "--rsh-cs" is its deeper shade for small
 * white text and outlines.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_course = $args['course'] ?? null;

if ( ! is_array( $rsh_course ) ) {
	return;
}

$rsh_color    = rsh_course_color( (int) $rsh_course['index'] );
$rsh_has_syl  = (bool) rsh_parse_syllabus( $rsh_course['syllabus'] );
$rsh_has_pay  = '' !== $rsh_course['pay'];
$rsh_original = rsh_original_price( $rsh_course['price'], $rsh_course['original'], rsh_discount() );
?>
<article class="rsh-card" style="<?php echo esc_attr( '--rsh-c:' . $rsh_color['base'] . ';--rsh-cs:' . $rsh_color['strong'] ); ?>">
	<div class="rsh-card__head">
		<?php if ( '' !== $rsh_course['tag'] ) : ?>
			<p class="rsh-card__tag"><?php echo esc_html( $rsh_course['tag'] ); ?></p>
		<?php endif; ?>
		<h4 class="rsh-card__name"><?php echo esc_html( $rsh_course['name'] ); ?></h4>
		<div class="rsh-card__img"><?php rsh_course_image( $rsh_course ); ?></div>
	</div>
	<div class="rsh-card__body">
		<p class="rsh-card__hook"><?php echo esc_html( $rsh_course['hook'] ); ?></p>
		<p class="rsh-card__price">
			<span class="rsh-sr-only"><?php rsh_the_text( 'price_now' ); ?> </span>
			<span class="rsh-price"><?php echo esc_html( rsh_format_price( $rsh_course['price'] ) ); ?></span>
			<span class="rsh-sr-only"> <?php rsh_the_text( 'price_was' ); ?> </span>
			<s class="rsh-was"><?php echo esc_html( rsh_format_price( $rsh_original ) ); ?></s>
		</p>
		<?php rsh_coupon_line( 'sm' ); ?>
		<?php if ( $rsh_has_syl ) : ?>
			<button type="button" class="rsh-card__syl" data-rsh-syl="rsh-syl-<?php echo esc_attr( $rsh_course['key'] ); ?>" aria-haspopup="dialog">
				<?php rsh_the_text( 'syl_open' ); ?><span class="rsh-sr-only"> — <?php echo esc_html( $rsh_course['name'] ); ?></span>
				<span class="rsh-card__plus" aria-hidden="true">+</span>
			</button>
		<?php endif; ?>
		<?php
		rsh_cta(
			array(
				'href'     => $rsh_has_pay ? $rsh_course['pay'] : '#consult',
				'variant'  => 'course',
				'external' => $rsh_has_pay,
				'context'  => $rsh_course['name'],
				'class'    => 'rsh-cta--block',
				'arrow'    => 'chevrons',
			)
		);
		?>
	</div>
</article>
