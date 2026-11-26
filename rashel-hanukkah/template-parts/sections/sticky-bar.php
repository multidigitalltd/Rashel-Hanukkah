<?php
/**
 * Sticky bottom bar: the countdown and the way to the courses.
 *
 * Hidden until the visitor has scrolled past the first screens (landing.js),
 * and while hidden it is also removed from the tab order.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<aside class="rsh-bar" data-rsh-bar aria-label="<?php esc_attr_e( 'הרשמה מהירה', 'rashel-hanukkah' ); ?>">
	<div class="rsh-bar__in">
		<?php
		rsh_countdown( 'bar' );
		rsh_cta(
			array(
				'class' => 'rsh-cta--sm',
				'arrow' => 'chevrons',
			)
		);
		?>
	</div>
</aside>
