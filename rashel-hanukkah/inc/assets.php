<?php
/**
 * Asset loading.
 *
 * Rules this file exists to enforce (Multi Digital standard 9.3, 16.5):
 * - Nothing loads on a page that does not need it.
 * - No third-party host: fonts, images and video are shipped with the theme.
 * - Minified files are served in production, sources when SCRIPT_DEBUG is on.
 * - Scripts are deferred; the stylesheets are the only render-blocking resources.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file to serve for an asset: the minified build, or the source when
 * SCRIPT_DEBUG is on or the build is missing.
 *
 * @param string $relative Source file, relative to the theme (assets/css/main.css).
 * @return string Path relative to the theme.
 */
function rsh_asset_file( $relative ) {
	$min   = (string) preg_replace( '/\.(css|js)$/', '.min.$1', $relative );
	$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;

	return ! $debug && file_exists( RSH_DIR . '/' . $min ) ? $min : $relative;
}

/**
 * Version for an asset's URL: the theme version and a short hash of the file,
 * so a changed file gets a new URL and no browser, page cache or CDN keeps
 * serving the old one after an update.
 *
 * @param string $relative File relative to the theme, as returned by rsh_asset_file().
 * @return string
 */
function rsh_asset_version( $relative ) {
	static $versions = array();

	if ( ! isset( $versions[ $relative ] ) ) {
		$hash                  = is_readable( RSH_DIR . '/' . $relative ) ? md5_file( RSH_DIR . '/' . $relative ) : false;
		$versions[ $relative ] = RSH_VERSION . ( false === $hash ? '' : '-' . substr( $hash, 0, 8 ) );
	}

	return $versions[ $relative ];
}

/**
 * Enqueue one of the theme's stylesheets.
 *
 * @param non-empty-string $handle   Handle.
 * @param string           $relative Source file relative to the theme.
 * @param string[]         $deps     Dependencies.
 * @return void
 */
function rsh_enqueue_style( $handle, $relative, array $deps = array() ) {
	$file = rsh_asset_file( $relative );

	wp_enqueue_style( $handle, rsh_asset( $file ), $deps, rsh_asset_version( $file ) );
}

/**
 * Enqueue one of the theme's scripts: in the footer, deferred.
 *
 * @param non-empty-string $handle   Handle.
 * @param string           $relative Source file relative to the theme.
 * @return void
 */
function rsh_enqueue_script( $handle, $relative ) {
	$file = rsh_asset_file( $relative );

	wp_enqueue_script(
		$handle,
		rsh_asset( $file ),
		array(),
		rsh_asset_version( $file ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

/**
 * Does the current request render editor content that needs the block stylesheet?
 *
 * @return bool
 */
function rsh_needs_block_styles() {
	if ( ! is_singular() || rsh_is_landing() ) {
		return false;
	}

	$post = get_queried_object();

	return ( $post instanceof WP_Post ) && has_blocks( $post );
}

/**
 * Front-end styles and scripts.
 *
 * @return void
 */
function rsh_enqueue_assets() {
	rsh_enqueue_style( 'rsh-main', 'assets/css/main.css' );

	if ( rsh_a11y_enabled() ) {
		rsh_enqueue_script( 'rsh-a11y', 'assets/js/a11y.js' );
	}

	rsh_enqueue_script( 'rsh-privacy', 'assets/js/privacy.js' );

	if ( ! rsh_is_landing() ) {
		return;
	}

	rsh_enqueue_style( 'rsh-landing', 'assets/css/landing.css', array( 'rsh-main' ) );
	rsh_enqueue_script( 'rsh-landing', 'assets/js/landing.js' );
	rsh_enqueue_script( 'rsh-forms', 'assets/js/forms.js' );

	// The deadline is public; it is the same number for every visitor, so the page stays cacheable.
	wp_add_inline_script(
		'rsh-landing',
		'window.rshLanding=' . wp_json_encode( array( 'deadline' => rsh_deadline() * 1000 ) ) . ';',
		'before'
	);

	/*
	 * Only endpoints and strings: no nonce here on purpose. Under a full-page
	 * cache (LiteSpeed, WP Rocket, Cloudflare) a printed nonce eventually goes
	 * stale, so the script pulls a fresh one from the REST route on first use.
	 */
	wp_add_inline_script(
		'rsh-forms',
		'window.rshForms=' . wp_json_encode(
			array(
				'submit'  => esc_url_raw( rest_url( 'rashel/v1/lead' ) ),
				'nonce'   => esc_url_raw( rest_url( 'rashel/v1/nonce' ) ),
				'strings' => array(
					'sending'  => __( 'שולחת…', 'rashel-hanukkah' ),
					'error'    => __( 'משהו לא עבד. אפשר לנסות שוב או להתקשר אלינו.', 'rashel-hanukkah' ),
					'fix'      => __( 'יש שדה שצריך לתקן לפני השליחה.', 'rashel-hanukkah' ),
					'name'     => __( 'צריך למלא שם.', 'rashel-hanukkah' ),
					'phone'    => __( 'צריך למלא מספר טלפון.', 'rashel-hanukkah' ),
					'phoneBad' => __( 'מספר הטלפון לא נראה תקין. אפשר לבדוק אותו שוב?', 'rashel-hanukkah' ),
					'emailBad' => __( 'כתובת האימייל לא נראית תקינה. אפשר לתקן אותה או להשאיר את השדה ריק.', 'rashel-hanukkah' ),
					'okTitle'  => rsh_text( 'form_ok_ttl' ),
				),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'rsh_enqueue_assets' );

/**
 * Preload the fonts that paint above the fold, so the first text does not wait
 * for the stylesheet to discover them.
 *
 * @return void
 */
function rsh_preload_fonts() {
	$fonts = rsh_is_landing()
		? array(
			'oscar-regular.otf' => 'font/otf',
			'afek-black.woff2'  => 'font/woff2',
		)
		: array( 'afek-regular.woff2' => 'font/woff2' );

	foreach ( $fonts as $font => $type ) {
		printf(
			'<link rel="preload" href="%1$s" as="font" type="%2$s" crossorigin>' . "\n",
			esc_url( rsh_asset( 'assets/fonts/' . $font ) ),
			esc_attr( $type )
		);
	}
}
add_action( 'wp_head', 'rsh_preload_fonts', 1 );

/**
 * Strip core output this theme does not use. Each removal is a real request or
 * a real payload the visitor would otherwise download for nothing.
 *
 * @return void
 */
function rsh_dequeue_unused() {
	if ( is_admin() ) {
		return;
	}

	// Emoji: a script plus an inline stylesheet, for a feature browsers ship.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	wp_dequeue_style( 'wp-emoji-styles' );
	add_filter( 'emoji_svg_url', '__return_false' );

	// oEmbed discovery and the embed script: this site is not embedded elsewhere.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	wp_deregister_script( 'wp-embed' );

	// Header noise with no consumer here.
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );

	// Block CSS only where editor blocks are actually rendered.
	if ( ! rsh_needs_block_styles() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
	}

	// The theme ships no theme.json and styles every element itself.
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'rsh_dequeue_unused', 100 );

/**
 * Global styles are printed from their own hooks as well; drop those too.
 *
 * @return void
 */
function rsh_drop_global_styles() {
	if ( is_admin() ) {
		return;
	}

	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'wp', 'rsh_drop_global_styles' );

// Load only the styles of the blocks a page renders, not the whole block library.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * The design is right-to-left whatever the site locale is set to, so the
 * document direction and language are stated when the locale did not.
 *
 * Front end only: wp-admin renders through language_attributes() as well.
 *
 * @param string $output Attribute string from language_attributes().
 * @return string
 */
function rsh_force_rtl_attributes( $output ) {
	if ( is_admin() ) {
		return $output;
	}

	if ( false === strpos( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	}

	if ( ! is_rtl() ) {
		$output = (string) preg_replace( '/lang="[^"]*"/', 'lang="he-IL"', $output );
	}

	return $output;
}
add_filter( 'language_attributes', 'rsh_force_rtl_attributes' );

/**
 * Drop the s.w.org prefetch: nothing on the front end resolves that host.
 *
 * @param string[] $urls          Resource hint URLs.
 * @param string   $relation_type Hint type.
 * @return string[]
 */
function rsh_clean_resource_hints( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_filter(
			$urls,
			static function ( $url ) {
				return false === strpos( (string) $url, 's.w.org' );
			}
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'rsh_clean_resource_hints', 10, 2 );

/**
 * Admin styles for the theme's own screens only.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function rsh_admin_assets( $hook ) {
	if ( false === strpos( (string) $hook, 'rsh-dashboard' ) ) {
		return;
	}

	$file = rsh_asset_file( 'assets/css/admin.css' );

	wp_enqueue_style( 'rsh-admin', rsh_asset( $file ), array(), rsh_asset_version( $file ) );

	if ( 'media' !== rsh_dashboard_tab() ) {
		return;
	}

	wp_enqueue_media();

	$script = rsh_asset_file( 'assets/js/admin.js' );

	wp_enqueue_script( 'rsh-admin', rsh_asset( $script ), array( 'media-editor', 'wp-a11y' ), rsh_asset_version( $script ), true );
	wp_localize_script(
		'rsh-admin',
		'rshAdmin',
		array(
			'pickImage'  => __( 'בחירת תמונה', 'rashel-hanukkah' ),
			'pickVideo'  => __( 'בחירת סרטון', 'rashel-hanukkah' ),
			'pickImages' => __( 'בחירת תמונות לגלריה', 'rashel-hanukkah' ),
			'use'        => __( 'שימוש בקובץ', 'rashel-hanukkah' ),
			'fromMedia'  => __( 'קובץ מספריית המדיה', 'rashel-hanukkah' ),
			'original'   => __( 'הקובץ המקורי של העיצוב', 'rashel-hanukkah' ),
			'noAlt'      => __( 'אין תיאור לתמונה', 'rashel-hanukkah' ),
			'moved'      => __( 'התמונה הוזזה.', 'rashel-hanukkah' ),
			'removed'    => __( 'התמונה הוסרה מהרשימה.', 'rashel-hanukkah' ),
			'added'      => __( 'התמונות נוספו לרשימה.', 'rashel-hanukkah' ),
			'full'       => __( 'הרשימה מלאה.', 'rashel-hanukkah' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'rsh_admin_assets' );
