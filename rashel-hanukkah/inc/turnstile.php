<?php
/**
 * Cloudflare Turnstile on the consultation form (Multi Digital standard 15.3).
 *
 * The keys are set in the dashboard ("ראשל חנוכה" > "חיבורים"), where the
 * secret is sealed before it is stored, or in wp-config.php
 * (RSH_TURNSTILE_SITE_KEY and RSH_TURNSTILE_SECRET_KEY), which wins and locks
 * the fields. The secret never reaches the browser. Without both keys the form
 * still works, protected by the honeypot and the rate limit.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The widget's data-action, checked again in the Siteverify answer. */
const RSH_TURNSTILE_ACTION = 'rsh_lead';

/**
 * Is the form protected? A site key and a saved secret. A saved secret that
 * can no longer be opened keeps the form protected and refuses every
 * submission, rather than letting it through (fail closed).
 *
 * @return bool
 */
function rsh_turnstile_enabled() {
	return '' !== rsh_turnstile_site_key() && rsh_secret_saved( 'turnstile_secret' );
}

/**
 * The public site key, for the widget.
 *
 * @return string
 */
function rsh_turnstile_site_key() {
	if ( defined( 'RSH_TURNSTILE_SITE_KEY' ) && '' !== trim( (string) RSH_TURNSTILE_SITE_KEY ) ) {
		return trim( (string) RSH_TURNSTILE_SITE_KEY );
	}

	return trim( (string) rsh_setting( 'turnstile_site' ) );
}

/**
 * Validate a token with Siteverify (standard 15.3.2, 15.3.5, 15.3.6).
 *
 * Success alone is not enough: the token must also have been issued for this
 * site's hostname and for this form's action. Any failure, including a network
 * error, rejects the submission and is logged without personal data.
 *
 * @param string $token The cf-turnstile-response field.
 * @return bool
 */
function rsh_turnstile_verify( $token ) {
	if ( '' === $token || strlen( $token ) > 2048 ) {
		return false;
	}

	$secret = rsh_secret( 'turnstile_secret' );

	if ( '' === $secret ) {
		error_log( 'rashel-hanukkah: turnstile secret missing or unreadable' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational log line, no personal data.
		return false;
	}

	$response = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 5,
			'body'    => array(
				'secret'   => $secret,
				'response' => $token,
				'remoteip' => rsh_client_ip(),
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		error_log( 'rashel-hanukkah: turnstile siteverify unreachable' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational log line, no personal data.
		return false;
	}

	$accepted = rsh_turnstile_response_ok(
		json_decode( wp_remote_retrieve_body( $response ), true ),
		RSH_TURNSTILE_ACTION,
		(string) wp_parse_url( home_url(), PHP_URL_HOST )
	);

	if ( ! $accepted ) {
		error_log( 'rashel-hanukkah: turnstile rejected' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational log line, no personal data.
	}

	return $accepted;
}

/**
 * The widget script, only on the page with the form, without blocking render (15.3.7).
 *
 * @return void
 */
function rsh_turnstile_script() {
	if ( rsh_turnstile_enabled() && rsh_is_landing() ) {
		wp_enqueue_script(
			'rsh-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Cloudflare serves the current widget; a version query is not allowed.
			array(
				'in_footer' => true,
				'strategy'  => 'async',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'rsh_turnstile_script' );
