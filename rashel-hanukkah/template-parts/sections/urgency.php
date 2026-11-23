<?php
/**
 * Urgency band: the deadline, the countdown and one more way to the courses.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="rsh-urgency">
	<div class="rsh-urgency__band">
		<div class="rsh-urgency__in" data-rsh-reveal>
			<p class="rsh-urgency__line"><?php rsh_the_text( 'deadline_line' ); ?></p>
			<div class="rsh-urgency__side">
				<?php
				rsh_countdown( 'xl' );
				rsh_cta( array( 'variant' => 'ink' ) );
				?>
			</div>
		</div>
	</div>
</div>
