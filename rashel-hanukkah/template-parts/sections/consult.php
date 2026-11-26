<?php
/**
 * Consultation: the "first in Israel" circle, the phone line and the lead form.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_center = rsh_text_lines( 'circle_center' );
?>
<section id="consult" class="rsh-consult" aria-labelledby="rsh-consult-ttl">
	<div class="rsh-consult__band">
		<div class="rsh-circle" data-rsh-reveal>
			<span class="rsh-circle__disc" aria-hidden="true"></span>
			<span class="rsh-circle__spin rsh-spin"><?php rsh_ring( rsh_text( 'circle_ring' ) . ' ✦ ' ); ?></span>
			<p class="rsh-circle__center">
				<span class="rsh-sr-only"><?php rsh_the_text( 'circle_ring' ); ?>: </span>
				<span class="rsh-circle__star" aria-hidden="true">★</span>
				<span class="rsh-circle__t">
					<?php foreach ( $rsh_center as $rsh_n => $rsh_line ) : ?>
						<?php echo $rsh_n ? '<br>' : ''; ?><?php echo esc_html( $rsh_line ); ?>
					<?php endforeach; ?>
				</span>
			</p>
		</div>

		<div class="rsh-wrap rsh-consult__in">
			<div class="rsh-consult__text">
				<h2 class="rsh-consult__ttl" id="rsh-consult-ttl" data-rsh-reveal><?php rsh_the_text( 'consult_ttl' ); ?></h2>
				<p class="rsh-consult__call" data-rsh-reveal>
					<a class="rsh-phone" href="tel:<?php echo esc_attr( rsh_phone_digits( rsh_phone(), true ) ); ?>">
						<span class="rsh-phone__icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>
						</span>
						<span class="rsh-phone__lead"><?php rsh_the_text( 'phone_lead' ); ?></span>
						<bdo class="rsh-phone__n" dir="ltr"><?php echo esc_html( rsh_phone() ); ?></bdo>
					</a>
				</p>
			</div>

			<?php get_template_part( 'template-parts/lead-form' ); ?>
		</div>
	</div>
</section>
