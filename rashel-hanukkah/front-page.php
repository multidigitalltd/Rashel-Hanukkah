<?php
/**
 * The site's front page: the Hanukkah landing page.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/landing' );
get_footer();
