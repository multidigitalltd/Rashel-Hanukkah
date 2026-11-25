<?php
/**
 * Urgency band: the deadline, the countdown and a straight way to payment.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_all   = rsh_courses();
$rsh_first = $rsh_all[0] ?? null;
$rsh_pay   = is_array( $rsh_first ) ? $rsh_first['pay'] : '';
?>
<section class="rsh-urgency" aria-label="<?php echo esc_attr( rsh_text( 'cd_label' ) ); ?>">
	<div class="rsh-wrap rsh-urgency__in" data-rsh-reveal>
		<p class="rsh-urgency__line"><?php rsh_the_text( 'deadline_line' ); ?></p>
		<div class="rsh-urgency__side">
			<?php
			rsh_countdown( 'colors' );
			rsh_cta(
				array(
					'href'     => '' === $rsh_pay ? '#courses' : $rsh_pay,
					'variant'  => 'yellow',
					'pulse'    => true,
					'external' => '' !== $rsh_pay,
					'context'  => is_array( $rsh_first ) && '' !== $rsh_pay ? $rsh_first['name'] : '',
					'class'    => 'rsh-cta--lg',
				)
			);
			?>
		</div>
	</div>
</section>
