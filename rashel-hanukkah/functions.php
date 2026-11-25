<?php
/**
 * Rashel Hanukkah theme bootstrap.
 *
 * Loads the theme modules and declares theme support. Every module under /inc
 * has a single responsibility; nothing here does work of its own.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RSH_VERSION', '2.0.0' );
define( 'RSH_DIR', get_template_directory() );
define( 'RSH_URI', get_template_directory_uri() );

require_once RSH_DIR . '/inc/logic.php';
require_once RSH_DIR . '/inc/content.php';
require_once RSH_DIR . '/inc/settings.php';
require_once RSH_DIR . '/inc/audit.php';
require_once RSH_DIR . '/inc/courses.php';
require_once RSH_DIR . '/inc/template-tags.php';
require_once RSH_DIR . '/inc/assets.php';
require_once RSH_DIR . '/inc/security.php';
require_once RSH_DIR . '/inc/accessibility.php';
require_once RSH_DIR . '/inc/seo.php';
require_once RSH_DIR . '/inc/turnstile.php';
require_once RSH_DIR . '/inc/privacy.php';
require_once RSH_DIR . '/inc/leads.php';
require_once RSH_DIR . '/inc/leads-crm.php';
require_once RSH_DIR . '/inc/install.php';

if ( is_admin() ) {
	// The dashboard is an admin-only screen; never parsed on a front-end request.
	require_once RSH_DIR . '/inc/dashboard.php';
}

/**
 * Theme support and registrations.
 *
 * @return void
 */
function rsh_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'footer' => __( 'קישורי פוטר נוספים', 'rashel-hanukkah' ),
		)
	);

	// The only derivative the templates request: the 4:3 course card, at 2x.
	add_image_size( 'rsh-card', 720, 540, true );
}
add_action( 'after_setup_theme', 'rsh_setup' );

/**
 * Content width matches the design's 1320px container.
 *
 * @return void
 */
function rsh_content_width() {
	$GLOBALS['content_width'] = 1320; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Core's own global.
}
add_action( 'after_setup_theme', 'rsh_content_width', 0 );

/**
 * Drop the derivatives core generates but this theme never renders.
 *
 * @param array<string,array<string,mixed>> $sizes Intermediate image sizes, keyed by name.
 * @return array<string,array<string,mixed>>
 */
function rsh_trim_image_sizes( $sizes ) {
	unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'] );

	return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'rsh_trim_image_sizes' );

/**
 * Body class says whether this is the landing page, for page-scoped rules.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function rsh_body_class( $classes ) {
	$classes[] = rsh_is_landing() ? 'rsh-landing' : 'rsh-inner';

	return $classes;
}
add_filter( 'body_class', 'rsh_body_class' );
