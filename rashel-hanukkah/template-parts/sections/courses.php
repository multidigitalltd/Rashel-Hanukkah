<?php
/**
 * Course menu: header, category tabs, the card grid and the closing row.
 *
 * Both categories are in the HTML. Without JavaScript they are simply listed
 * one after the other under their own headings; landing.js turns the two
 * buttons into real tabs and hides the inactive panel.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_all   = rsh_courses();
$rsh_first = $rsh_all[0]['pay'] ?? '';
?>
<section id="courses" class="rsh-courses" aria-labelledby="rsh-courses-ttl">
	<div class="rsh-wrap">
		<header class="rsh-courses__head">
			<span class="rsh-courses__star" aria-hidden="true">✦</span>
			<h2 class="rsh-courses__ttl" id="rsh-courses-ttl" data-rsh-reveal><?php rsh_the_text( 'courses_ttl' ); ?></h2>
			<p class="rsh-courses__txt"><?php rsh_the_text( 'courses_txt' ); ?></p>
			<p class="rsh-courses__note"><?php rsh_the_text( 'courses_note' ); ?></p>
		</header>

		<div class="rsh-tabs" data-rsh-tabs>
			<div class="rsh-tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'קטגוריות הקורסים', 'rashel-hanukkah' ); ?>">
				<?php foreach ( rsh_course_categories() as $rsh_cat => $rsh_label ) : ?>
					<button type="button" class="rsh-tabs__tab" role="tab" id="rsh-tab-<?php echo esc_attr( $rsh_cat ); ?>" aria-controls="rsh-panel-<?php echo esc_attr( $rsh_cat ); ?>" aria-selected="<?php echo 'culinary' === $rsh_cat ? 'true' : 'false'; ?>"><?php rsh_the_text( $rsh_label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>

		<?php foreach ( rsh_course_categories() as $rsh_cat => $rsh_label ) : ?>
			<div class="rsh-panel" id="rsh-panel-<?php echo esc_attr( $rsh_cat ); ?>" role="tabpanel" aria-labelledby="rsh-tab-<?php echo esc_attr( $rsh_cat ); ?>" data-rsh-panel="<?php echo esc_attr( $rsh_cat ); ?>">
				<h3 class="rsh-panel__ttl"><?php rsh_the_text( $rsh_label ); ?></h3>
				<div class="rsh-grid">
					<?php foreach ( rsh_courses_in( $rsh_cat ) as $rsh_course ) : ?>
						<article class="rsh-card">
							<div class="rsh-card__img"><?php rsh_course_image( $rsh_course ); ?></div>
							<div class="rsh-card__body">
								<?php if ( '' !== $rsh_course['tag'] ) : ?>
									<p class="rsh-card__tag"><?php echo esc_html( $rsh_course['tag'] ); ?></p>
								<?php endif; ?>
								<h4 class="rsh-card__name"><?php echo esc_html( $rsh_course['name'] ); ?></h4>
								<p class="rsh-card__hook"><?php echo esc_html( $rsh_course['hook'] ); ?></p>
								<p class="rsh-card__price">
									<span class="rsh-sr-only"><?php rsh_the_text( 'price_now' ); ?> </span>
									<span class="rsh-price"><?php echo esc_html( rsh_format_price( $rsh_course['price'] ) ); ?></span>
									<span class="rsh-sr-only"> <?php rsh_the_text( 'price_was' ); ?> </span>
									<s class="rsh-was"><?php echo esc_html( rsh_format_price( rsh_original_price( $rsh_course['price'], $rsh_course['original'], rsh_discount() ) ) ); ?></s>
								</p>
								<?php
								rsh_cta(
									array(
										'href'     => '' === $rsh_course['pay'] ? '#consult' : $rsh_course['pay'],
										'variant'  => 'dark',
										'external' => '' !== $rsh_course['pay'],
										'context'  => $rsh_course['name'],
										'class'    => 'rsh-cta--block',
									)
								);
								?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="rsh-courses__foot">
			<div class="rsh-courses__deadline">
				<p class="rsh-courses__line"><?php rsh_the_text( 'deadline_line' ); ?></p>
				<?php rsh_countdown( 'md' ); ?>
			</div>
			<?php
			rsh_cta(
				array(
					'href'     => '' === $rsh_first ? '#consult' : $rsh_first,
					'variant'  => 'dark',
					'pulse'    => true,
					'external' => '' !== $rsh_first,
					'context'  => $rsh_all[0]['name'] ?? '',
				)
			);
			?>
		</div>
	</div>
</section>
