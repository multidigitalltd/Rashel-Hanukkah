<?php
/**
 * Consultation: the gift ticket, the lead form and the closing benefits strip.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<section id="consult" class="rsh-consult" aria-labelledby="rsh-consult-ttl">
	<div class="rsh-wrap rsh-consult__in">
		<div class="rsh-consult__text">
			<h2 class="rsh-consult__ttl" id="rsh-consult-ttl" data-rsh-reveal><?php rsh_the_text( 'consult_ttl' ); ?> <span class="rsh-consult__mark"><?php rsh_the_text( 'consult_mark' ); ?></span></h2>

			<div class="rsh-ticket" data-rsh-reveal>
				<p class="rsh-ticket__txt"><?php rsh_the_text( 'ticket_txt' ); ?></p>
				<div class="rsh-ticket__stub">
					<span class="rsh-ticket__gift">
						<svg class="rsh-ticket__bow" viewBox="0 0 80 40" aria-hidden="true" focusable="false">
							<path d="M40 30 C 26 4, 4 6, 8 20 C 11 30, 28 31, 40 30 Z"/>
							<path d="M40 30 C 54 4, 76 6, 72 20 C 69 30, 52 31, 40 30 Z"/>
							<path d="M38 32 L 28 46 M42 32 L 52 46" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
							<circle cx="40" cy="30" r="6"/>
						</svg>
						<span class="rsh-ticket__ribbon" aria-hidden="true"></span>
						<span class="rsh-ticket__badge"><?php rsh_the_text( 'ticket_badge' ); ?></span>
					</span>
				</div>
			</div>

			<?php
			rsh_cta(
				array(
					'pulse' => true,
					'class' => 'rsh-cta--lg',
				)
			);
			?>
		</div>

		<?php get_template_part( 'template-parts/lead-form' ); ?>
	</div>

	<?php rsh_benefits( 'rsh-benefits--bright' ); ?>
</section>
