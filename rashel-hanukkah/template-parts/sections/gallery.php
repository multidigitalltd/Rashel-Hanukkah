<?php
/**
 * Arch gallery band: one photo per course, moving slowly.
 *
 * Decorative: every photo appears again on its course card with the course
 * name beside it, so the band is hidden from assistive technology.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_gallery = rsh_gallery_courses();

if ( ! $rsh_gallery ) {
	return;
}
?>
<div class="rsh-gallery" aria-hidden="true">
	<div class="rsh-gallery__track">
		<?php for ( $rsh_half = 0; $rsh_half < 2; $rsh_half++ ) : ?>
			<?php foreach ( $rsh_gallery as $rsh_course ) : ?>
				<div class="rsh-gallery__item"><?php rsh_course_image( $rsh_course ); ?></div>
			<?php endforeach; ?>
		<?php endfor; ?>
	</div>
</div>
