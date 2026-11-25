<?php
/**
 * The syllabus of one course, in a native modal dialog. $args: course.
 *
 * A <dialog> opened with showModal() traps focus, closes on Escape and returns
 * focus to the button that opened it, without any script of ours.
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
$rsh_id       = 'rsh-syl-' . $rsh_course['key'];
$rsh_has_pay  = '' !== $rsh_course['pay'];
$rsh_original = rsh_original_price( $rsh_course['price'], $rsh_course['original'], rsh_discount() );
?>
<dialog class="rsh-syl" id="<?php echo esc_attr( $rsh_id ); ?>" aria-labelledby="<?php echo esc_attr( $rsh_id ); ?>-ttl" style="<?php echo esc_attr( '--rsh-c:' . $rsh_color['base'] . ';--rsh-cs:' . $rsh_color['strong'] ); ?>">
	<div class="rsh-syl__box">
		<div class="rsh-syl__head">
			<div class="rsh-syl__titles">
				<?php if ( '' !== $rsh_course['tag'] ) : ?>
					<p class="rsh-syl__tag"><?php echo esc_html( $rsh_course['tag'] ); ?></p>
				<?php endif; ?>
				<p class="rsh-syl__label"><?php rsh_the_text( 'syl_label' ); ?></p>
				<h2 class="rsh-syl__ttl" id="<?php echo esc_attr( $rsh_id ); ?>-ttl"><?php echo esc_html( $rsh_course['name'] ); ?></h2>
			</div>
			<div class="rsh-syl__thumb" aria-hidden="true"><?php rsh_course_image( $rsh_course ); ?></div>
			<form method="dialog" class="rsh-syl__close-form">
				<button type="submit" class="rsh-syl__close" aria-label="<?php echo esc_attr( rsh_text( 'syl_close' ) ); ?>"><span aria-hidden="true">×</span></button>
			</form>
		</div>

		<div class="rsh-syl__body" tabindex="0" role="group" aria-label="<?php echo esc_attr( rsh_text( 'syl_label' ) ); ?>">
			<?php foreach ( rsh_parse_syllabus( $rsh_course['syllabus'] ) as $rsh_item ) : ?>
				<?php if ( $rsh_item['bonus'] ) : ?>
					<div class="rsh-syl__bonus">
						<h3 class="rsh-syl__bonus-ttl"><span class="rsh-syl__pill"><span aria-hidden="true">★ </span><?php rsh_the_text( 'syl_bonus' ); ?></span><?php echo esc_html( $rsh_item['title'] ); ?></h3>
						<?php if ( '' !== $rsh_item['text'] ) : ?>
							<p><?php echo esc_html( $rsh_item['text'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="rsh-syl__item">
						<span class="rsh-syl__n" aria-hidden="true"><?php echo esc_html( $rsh_item['number'] ); ?></span>
						<div class="rsh-syl__lesson">
							<h3><?php echo esc_html( $rsh_item['title'] ); ?></h3>
							<?php if ( '' !== $rsh_item['text'] ) : ?>
								<p><?php echo esc_html( $rsh_item['text'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<div class="rsh-syl__foot">
			<p class="rsh-syl__price">
				<span class="rsh-sr-only"><?php rsh_the_text( 'price_now' ); ?> </span>
				<span class="rsh-price"><?php echo esc_html( rsh_format_price( $rsh_course['price'] ) ); ?></span>
				<span class="rsh-sr-only"> <?php rsh_the_text( 'price_was' ); ?> </span>
				<s class="rsh-was"><?php echo esc_html( rsh_format_price( $rsh_original ) ); ?></s>
			</p>
			<?php
			rsh_cta(
				array(
					'href'     => $rsh_has_pay ? $rsh_course['pay'] : '#consult',
					'variant'  => 'course',
					'external' => $rsh_has_pay,
					'context'  => $rsh_course['name'],
					'class'    => 'rsh-cta--syl',
					'arrow'    => 'chevrons',
				)
			);
			?>
		</div>
	</div>
</dialog>
