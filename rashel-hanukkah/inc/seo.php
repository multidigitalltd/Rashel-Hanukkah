<?php
/**
 * Minimal SEO output (Multi Digital standard 12).
 *
 * Description, Open Graph, Twitter Card and Organization data for the landing
 * page. Steps aside completely when an SEO plugin is active, so no tag is
 * printed twice (standard 12.14). The canonical link, the robots tag and the
 * sitemap come from WordPress core.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is an SEO plugin already printing these tags?
 *
 * @return bool
 */
function rsh_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * The description of the current page.
 *
 * @return string
 */
function rsh_seo_description() {
	if ( rsh_is_landing() ) {
		return wp_strip_all_tags( rsh_text( 'lead' ) . ' ' . rsh_text( 'sub' ) );
	}

	if ( is_singular() ) {
		return wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt() ), 30, '…' );
	}

	return (string) get_bloginfo( 'description' );
}

/**
 * Print the tags in <head>.
 *
 * @return void
 */
function rsh_seo_head() {
	if ( rsh_seo_plugin_active() ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = rsh_seo_description();
	$url         = is_singular() ? (string) get_permalink() : home_url( '/' );
	$image       = rsh_asset( 'assets/img/og.jpg' );

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	}

	printf( '<meta property="og:type" content="website">' . "\n" );
	printf( '<meta property="og:locale" content="he_IL">' . "\n" );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	printf( '<meta property="og:image:width" content="1200">' . "\n" );
	printf( '<meta property="og:image:height" content="630">' . "\n" );
	printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );

	if ( ! rsh_is_landing() ) {
		return;
	}

	$data = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'EducationalOrganization',
		'name'      => get_bloginfo( 'name' ),
		'url'       => home_url( '/' ),
		'logo'      => rsh_asset( 'assets/img/rashel-logo.png' ),
		'telephone' => rsh_phone(),
	);

	wp_print_inline_script_tag( (string) wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), array( 'type' => 'application/ld+json' ) );
}
add_action( 'wp_head', 'rsh_seo_head', 5 );

/**
 * A favicon from the theme when the site has no site icon of its own.
 *
 * @return void
 */
function rsh_favicon() {
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( rsh_asset( 'assets/img/favicon.svg' ) ) );
	}
}
add_action( 'wp_head', 'rsh_favicon', 5 );
