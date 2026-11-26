<?php
/**
 * The landing page, section by section, in the order of the design.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_template_part( 'template-parts/sections/opening' );
get_template_part( 'template-parts/sections/announce' );
get_template_part( 'template-parts/sections/benefits' );
rsh_ticker( 'left' );
get_template_part( 'template-parts/sections/courses' );
get_template_part( 'template-parts/sections/urgency' );
get_template_part( 'template-parts/sections/gallery' );
get_template_part( 'template-parts/sections/about' );
rsh_ticker( 'right' );
get_template_part( 'template-parts/sections/testimonials' );
get_template_part( 'template-parts/sections/works' );
get_template_part( 'template-parts/sections/consult' );
