<?php
/**
 * Footer, the first-visit privacy notice and the accessibility toolbar.
 * Closes the document opened in header.php.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
</main>

<?php
get_template_part( 'template-parts/site-footer' );

if ( rsh_is_landing() && rsh_setting( 'sticky_bar' ) ) {
	get_template_part( 'template-parts/sections/sticky-bar' );
}

get_template_part( 'template-parts/privacy-notice' );
rsh_a11y_toolbar();
wp_footer();
?>
</body>
</html>
