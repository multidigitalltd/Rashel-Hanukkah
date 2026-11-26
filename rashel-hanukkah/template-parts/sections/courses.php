<?php
/**
 * Course menu: heading, the yellow price ribbon, category tabs, the cards and
 * a syllabus dialog for each course.
 *
 * Both categories are in the HTML. Without JavaScript they are listed one
 * after the other under their own headings; landing.js turns the two buttons
 * into real tabs and opens the syllabus dialogs.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_all = rsh_courses();
?>
<section id="courses" class="rsh-courses" aria-labelledby="rsh-courses-ttl">
	<div class="rsh-wrap">
		<header class="rsh-courses__head">
			<h2 class="rsh-courses__ttl" id="rsh-courses-ttl" data-rsh-reveal><?php rsh_the_text( 'courses_ttl' ); ?></h2>
			<p class="rsh-courses__txt"><?php rsh_the_text( 'courses_txt' ); ?></p>
			<p class="rsh-ribbon">
				<span class="rsh-ribbon__shine" aria-hidden="true"></span>
				<span class="rsh-ribbon__star" aria-hidden="true">✦</span>
				<?php rsh_the_text( 'courses_note' ); ?>
				<span class="rsh-ribbon__star" aria-hidden="true">✦</span>
			</p>
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
					<?php
					foreach ( rsh_courses_in( $rsh_cat ) as $rsh_course ) {
						get_template_part( 'template-parts/course-card', null, array( 'course' => $rsh_course ) );
					}
					?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php
	foreach ( $rsh_all as $rsh_course ) {
		if ( rsh_parse_syllabus( $rsh_course['syllabus'] ) ) {
			get_template_part( 'template-parts/course-syllabus', null, array( 'course' => $rsh_course ) );
		}
	}
	?>
</section>
