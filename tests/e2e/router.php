<?php
/**
 * Router for PHP's built-in server: static files are served as they are (text
 * assets gzipped, like production), everything else goes to WordPress.
 *
 * @package Rashel_Hanukkah
 */

declare( strict_types = 1 );

$rsh_root = rtrim( (string) $_SERVER['DOCUMENT_ROOT'], '/' );
$rsh_path = (string) parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$rsh_file = realpath( $rsh_root . $rsh_path );

if ( false !== $rsh_file && is_file( $rsh_file ) && 'php' !== strtolower( pathinfo( $rsh_file, PATHINFO_EXTENSION ) ) ) {
	$rsh_types = array(
		'css'  => 'text/css; charset=utf-8',
		'js'   => 'application/javascript; charset=utf-8',
		'svg'  => 'image/svg+xml',
		'json' => 'application/json',
	);
	$rsh_ext   = strtolower( pathinfo( $rsh_file, PATHINFO_EXTENSION ) );

	// Images, fonts and video are already compressed: the server sends them as they are.
	if ( ! isset( $rsh_types[ $rsh_ext ] ) ) {
		return false;
	}

	header( 'Content-Type: ' . $rsh_types[ $rsh_ext ] );
	header( 'Cache-Control: public, max-age=31536000' );

	$rsh_body = (string) file_get_contents( $rsh_file );

	if ( false !== strpos( (string) ( $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '' ), 'gzip' ) ) {
		header( 'Content-Encoding: gzip' );
		$rsh_body = (string) gzencode( $rsh_body, 6 );
	}

	header( 'Content-Length: ' . strlen( $rsh_body ) );
	echo $rsh_body;

	return true;
}

if ( false !== $rsh_file && is_file( $rsh_file ) ) {
	return false;
}

if ( false !== $rsh_file && is_dir( $rsh_file ) && is_file( $rsh_file . '/index.php' ) ) {
	$_SERVER['SCRIPT_NAME']     = rtrim( $rsh_path, '/' ) . '/index.php';
	$_SERVER['SCRIPT_FILENAME'] = $rsh_file . '/index.php';
	chdir( $rsh_file );
	require $rsh_file . '/index.php';

	return true;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $rsh_root . '/index.php';
chdir( $rsh_root );
require $rsh_root . '/index.php';
