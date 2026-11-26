<?php
/**
 * Asset minifier.
 *
 * Generates the production CSS and JS from the authoring sources, so the theme
 * ships minified files without keeping two copies of anything by hand.
 *
 * Usage:  php bin/build-assets.php
 *
 * Every assets/css/*.css and assets/js/*.js of the theme gets a .min sibling.
 * The theme falls back to the sources automatically when SCRIPT_DEBUG is on or
 * when a minified file is missing, so the build is never a hard requirement.
 * CI rebuilds and fails when the committed .min files are out of date.
 *
 * @package Rashel_Hanukkah
 */

declare( strict_types = 1 );

$rsh_theme = dirname( __DIR__ ) . '/rashel-hanukkah';

/**
 * Strip comments and collapse whitespace in CSS.
 *
 * Comments that open with "/*!" are kept. Whitespace before a colon is never
 * touched: ".box :focus" and ".box:focus" are different selectors.
 *
 * @param string $css Source CSS.
 * @return string
 */
function rsh_minify_css( string $css ): string {
	$css = (string) preg_replace( '#/\*(?!!).*?\*/#s', '', $css );
	$css = (string) preg_replace( '/\s+/', ' ', $css );
	$css = (string) preg_replace( '/\s*([{};,>])\s*/', '$1', $css );
	$css = (string) preg_replace( '/:\s+/', ':', $css );
	$css = str_replace( ';}', '}', $css );
	$css = (string) preg_replace( '#\*/\s*#', "*/\n", $css );

	return trim( $css ) . "\n";
}

/**
 * Strip comments and needless whitespace from JavaScript.
 *
 * Deliberately conservative: it removes whole-line comments, blank lines and
 * leading indentation only. Nothing here rewrites identifiers or reorders
 * statements, so the output stays debuggable against the source.
 *
 * @param string $js Source JavaScript.
 * @return string
 */
function rsh_minify_js( string $js ): string {
	$out = array();

	foreach ( explode( "\n", $js ) as $line ) {
		$trimmed = trim( $line );

		if ( '' === $trimmed ) {
			continue;
		}

		// Whole-line comments only: never touch anything that shares a line with code.
		if ( str_starts_with( $trimmed, '//' ) || str_starts_with( $trimmed, '/*' ) || str_starts_with( $trimmed, '* ' ) || '*' === $trimmed || str_starts_with( $trimmed, '*/' ) ) {
			continue;
		}

		$out[] = $trimmed;
	}

	return implode( "\n", $out ) . "\n";
}

$rsh_jobs = array();

foreach ( array( 'css', 'js' ) as $rsh_type ) {
	$rsh_sources = glob( $rsh_theme . '/assets/' . $rsh_type . '/*.' . $rsh_type );

	foreach ( false === $rsh_sources ? array() : $rsh_sources as $rsh_source ) {
		if ( str_ends_with( $rsh_source, '.min.' . $rsh_type ) ) {
			continue;
		}

		$rsh_jobs[] = array(
			'src'  => $rsh_source,
			'dst'  => substr( $rsh_source, 0, -strlen( $rsh_type ) ) . 'min.' . $rsh_type,
			'func' => 'css' === $rsh_type ? 'rsh_minify_css' : 'rsh_minify_js',
		);
	}
}

$rsh_failed = ! $rsh_jobs;

foreach ( $rsh_jobs as $rsh_job ) {
	$rsh_code = file_get_contents( $rsh_job['src'] );

	if ( false === $rsh_code ) {
		fwrite( STDERR, "cannot read {$rsh_job['src']}\n" );
		$rsh_failed = true;
		continue;
	}

	$rsh_result = $rsh_job['func']( $rsh_code );

	if ( false === file_put_contents( $rsh_job['dst'], $rsh_result ) ) {
		fwrite( STDERR, "cannot write {$rsh_job['dst']}\n" );
		$rsh_failed = true;
		continue;
	}

	printf(
		"%-22s %7d -> %7d bytes  (-%d%%)\n",
		basename( $rsh_job['dst'] ),
		strlen( $rsh_code ),
		strlen( $rsh_result ),
		(int) round( ( 1 - strlen( $rsh_result ) / max( 1, strlen( $rsh_code ) ) ) * 100 )
	);
}

// The landing page loads its scripts and its styles as one file each: two requests instead of six.
$rsh_bundles = array(
	'js'  => array( 'a11y', 'privacy', 'landing', 'forms' ),
	'css' => array( 'main', 'landing' ),
);

foreach ( $rsh_bundles as $rsh_type => $rsh_parts ) {
	$rsh_bundle = '';

	foreach ( $rsh_parts as $rsh_part ) {
		$rsh_code = file_get_contents( $rsh_theme . '/assets/' . $rsh_type . '/' . $rsh_part . '.min.' . $rsh_type );

		if ( false === $rsh_code ) {
			fwrite( STDERR, "cannot read {$rsh_part}.min.{$rsh_type} for the bundle\n" );
			$rsh_failed = true;
			continue;
		}

		$rsh_bundle .= rtrim( $rsh_code ) . "\n";
	}

	$rsh_name = 'landing-bundle.min.' . $rsh_type;

	if ( false === file_put_contents( $rsh_theme . '/assets/' . $rsh_type . '/' . $rsh_name, $rsh_bundle ) ) {
		fwrite( STDERR, "cannot write {$rsh_name}\n" );
		$rsh_failed = true;
	}

	printf( "%-22s %7d bytes\n", $rsh_name, strlen( $rsh_bundle ) );
}

exit( $rsh_failed ? 1 : 0 );
