<?php
/**
 * Page not found: a real 404 with a way back (standard 12.9).
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="rsh-page rsh-wrap">
	<h1 class="rsh-page__title"><?php esc_html_e( 'העמוד לא נמצא', 'rashel-hanukkah' ); ?></h1>
	<p><?php esc_html_e( 'הכתובת שהגעת אליה לא קיימת או שהעמוד הוסר.', 'rashel-hanukkah' ); ?></p>
	<p><a class="rsh-cta rsh-cta--dark" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'חזרה לעמוד הבית', 'rashel-hanukkah' ); ?></a></p>
</div>
<?php
get_footer();
