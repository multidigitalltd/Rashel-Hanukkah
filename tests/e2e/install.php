<?php
/**
 * Install the throwaway WordPress used by the end-to-end scripts.
 *
 * Two stages, each in its own process, because WordPress loads the active
 * theme at boot: "core" installs WordPress and switches to the theme, "theme"
 * then boots with the theme loaded and runs its installer.
 *
 * Usage: php tests/e2e/install.php <wordpress-dir> core|theme
 *
 * @package Rashel_Hanukkah
 */

declare( strict_types = 1 );

$rsh_wp = rtrim( (string) ( $argv[1] ?? '' ), '/' );

if ( '' === $rsh_wp || ! is_file( $rsh_wp . '/wp-load.php' ) ) {
	fwrite( STDERR, "usage: php install.php <wordpress-dir> core|theme\n" );
	exit( 1 );
}

$rsh_stage = (string) ( $argv[2] ?? 'core' );

$_SERVER['HTTP_HOST']       = '127.0.0.1:8089';
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

if ( 'core' === $rsh_stage ) {
	define( 'WP_INSTALLING', true );

	require $rsh_wp . '/wp-load.php';
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	wp_install( 'RASHEL Digital School', 'e2e-admin', 'admin@example.test', true, '', 'e2e-admin-password' );

	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	update_option( 'timezone_string', 'Asia/Jerusalem' );
	switch_theme( 'rashel-hanukkah' );

	wp_insert_user(
		array(
			'user_login' => 'e2e-editor',
			'user_pass'  => 'e2e-editor-password',
			'user_email' => 'editor@example.test',
			'role'       => 'editor',
		)
	);

	echo "core installed\n";
	exit( 0 );
}

require $rsh_wp . '/wp-load.php';

// A command-line switch does not fire the activation hook on its own.
delete_option( 'theme_switched' );
do_action( 'after_switch_theme', 'default', wp_get_theme( 'default' ) );
rsh_leads_grant_caps();
flush_rewrite_rules();

printf( "theme installed: version %s, %d courses, privacy page %d\n", wp_get_theme()->get( 'Version' ), count( rsh_courses() ), (int) get_option( 'wp_page_for_privacy_policy' ) );
