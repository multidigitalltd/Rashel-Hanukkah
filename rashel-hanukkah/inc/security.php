<?php
/**
 * Hardening the theme can do on its own (Multi Digital standard 4.9, 16.8).
 *
 * Server-level controls (HSTS, TLS, a content security policy, the login
 * limiter) belong to the hosting and to Cloudflare and are listed in the
 * README; nothing here replaces them.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security headers on every front-end response.
 *
 * @return void
 */
function rsh_security_headers() {
	if ( headers_sent() ) {
		return;
	}

	/**
	 * Filters the security headers the theme sends. Return an empty array when
	 * the server or Cloudflare already sends them.
	 *
	 * @param array<string, string> $headers Header name => value.
	 */
	$headers = (array) apply_filters(
		'rsh_security_headers',
		array(
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options'        => 'SAMEORIGIN',
			'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), payment=()',
		)
	);

	foreach ( $headers as $name => $value ) {
		header( $name . ': ' . $value );
	}
}
add_action( 'send_headers', 'rsh_security_headers' );

// XML-RPC has no consumer on this site (standard 16.8.4).
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Hide the list of users from visitors who are not signed in (standard 16.8.5).
 *
 * @param array<string, mixed> $endpoints REST endpoints.
 * @return array<string, mixed>
 */
function rsh_hide_user_endpoints( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'rsh_hide_user_endpoints' );

/**
 * Author archives would reveal login names; this site has none to show.
 *
 * Runs before core's canonical redirect, which would otherwise answer
 * "?author=1" with the address that spells the login name out.
 *
 * @return void
 */
function rsh_no_author_archives() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing check; nothing is changed.
	if ( is_author() || isset( $_GET['author'] ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'rsh_no_author_archives', 1 );
