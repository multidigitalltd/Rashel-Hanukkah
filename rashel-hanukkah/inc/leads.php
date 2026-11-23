<?php
/**
 * Lead capture: the consultation form.
 *
 * Stores every submission as a private post before anything else happens, so a
 * failed email or a failed CRM call never loses a lead (standard 15.2.1). No
 * form plugin.
 *
 * Security model:
 * - Nonce on every submission, refreshed over REST so a full-page cache cannot
 *   serve a stale one.
 * - Honeypot field for the cheap bots, Turnstile when configured, per-address
 *   rate limit for the rest.
 * - Every field validated against its own rule on the way in, escaped on the
 *   way out.
 * - The visitor's address is never stored: only a salted hash, and only for the
 *   lifetime of the rate-limit window.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Maximum submissions allowed from one address per window. */
const RSH_LEAD_MAX_PER_WINDOW = 5;

/** Rate-limit window, in seconds. */
const RSH_LEAD_WINDOW = HOUR_IN_SECONDS;

/**
 * The leads post type. Not public, not creatable by hand: it only ever
 * receives what the form writes.
 *
 * @return void
 */
function rsh_register_lead_cpt() {
	register_post_type(
		'rsh_lead',
		array(
			'labels'          => array(
				'name'          => __( 'לידים', 'rashel-hanukkah' ),
				'singular_name' => __( 'ליד', 'rashel-hanukkah' ),
				'menu_name'     => __( 'לידים', 'rashel-hanukkah' ),
				'edit_item'     => __( 'ליד', 'rashel-hanukkah' ),
				'search_items'  => __( 'חיפוש לידים', 'rashel-hanukkah' ),
				'not_found'     => __( 'אין לידים עדיין.', 'rashel-hanukkah' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-phone',
			'menu_position'   => 22,
			'supports'        => array( 'title' ),
			// A capability of its own (standard 15.2.3): see rsh_leads_grant_caps().
			'capability_type' => array( 'rsh_lead', 'rsh_leads' ),
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		)
	);
}
add_action( 'init', 'rsh_register_lead_cpt' );

/**
 * The form's two public routes: a fresh nonce, and the submission itself.
 *
 * Both are public by design. The nonce is not a secret, it is a same-origin
 * marker; the submission is validated, rate limited and reveals nothing.
 *
 * @return void
 */
function rsh_register_lead_routes() {
	register_rest_route(
		'rashel/v1',
		'/nonce',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'rsh_rest_nonce',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'rashel/v1',
		'/lead',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'rsh_rest_lead',
			'permission_callback' => '__return_true',
			'args'                => array(
				'rsh_name'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rsh_rest_is_string',
				),
				'rsh_phone' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rsh_rest_is_string',
				),
				'rsh_email' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rsh_rest_is_string',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'rsh_register_lead_routes' );

/**
 * REST argument check: a scalar string, nothing nested.
 *
 * @param mixed $value Submitted value.
 * @return bool
 */
function rsh_rest_is_string( $value ) {
	return is_string( $value ) && strlen( $value ) <= 400;
}

/**
 * All-In-One Security can refuse REST requests from visitors who are not
 * signed in. The form's routes are public by design, so they stay reachable
 * when that option is on.
 *
 * @param mixed $routes Whitelisted REST namespaces.
 * @return array<int, string>
 */
function rsh_aios_allow_lead_routes( $routes ) {
	$routes   = is_array( $routes ) ? array_values( array_filter( $routes, 'is_string' ) ) : array();
	$routes[] = 'rashel';  // /wp-json/rashel/v1/...
	$routes[] = '/rashel'; // ?rest_route=/rashel/v1/..., without pretty permalinks.

	return array_values( array_unique( $routes ) );
}
add_filter( 'aios_whitelisted_rest_routes', 'rsh_aios_allow_lead_routes' );

/**
 * A fresh nonce for a page that may have been served from cache.
 *
 * @return WP_REST_Response
 */
function rsh_rest_nonce() {
	$response = new WP_REST_Response( array( 'nonce' => wp_create_nonce( 'rsh_lead' ) ) );
	$response->header( 'Cache-Control', 'no-store, max-age=0' );

	return $response;
}

/**
 * The enhanced form's submission.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function rsh_rest_lead( WP_REST_Request $request ) {
	$result = rsh_process_lead( $request->get_body_params() );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$response = new WP_REST_Response( array( 'sent' => true ) );
	$response->header( 'Cache-Control', 'no-store, max-age=0' );

	return $response;
}

/**
 * Client address, resolved once. Filterable so a proxy setup can override it.
 *
 * Cloudflare's "Restore original visitor IP" already rewrites REMOTE_ADDR, so
 * the forwarded headers are deliberately not trusted here: they are spoofable
 * whenever the site is reachable directly.
 *
 * @return string
 */
function rsh_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return (string) apply_filters( 'rsh_client_ip', $ip );
}

/**
 * Rate-limit key: a salted hash, so no address is written to the database.
 *
 * @return string
 */
function rsh_lead_rate_key() {
	return 'rsh_lead_rl_' . substr( wp_hash( rsh_client_ip(), 'nonce' ), 0, 24 );
}

/**
 * Has this address already used up its window?
 *
 * @return bool
 */
function rsh_lead_rate_exceeded() {
	return (int) get_transient( rsh_lead_rate_key() ) >= RSH_LEAD_MAX_PER_WINDOW;
}

/**
 * Count one submission against the window.
 *
 * @return void
 */
function rsh_lead_rate_bump() {
	$key = rsh_lead_rate_key();

	set_transient( $key, (int) get_transient( $key ) + 1, RSH_LEAD_WINDOW );
}

/**
 * User-facing messages, by error code. One place, so the REST answer and the
 * no-JavaScript redirect say the same thing.
 *
 * @return array<string, string>
 */
function rsh_lead_messages() {
	return array(
		'rsh_nonce'    => __( 'פג תוקף הטופס. אפשר לרענן את העמוד ולנסות שוב.', 'rashel-hanukkah' ),
		'rsh_captcha'  => __( 'לא הצלחנו לאמת שהפנייה נשלחה מדפדפן. אפשר לנסות שוב או להתקשר אלינו.', 'rashel-hanukkah' ),
		'rsh_rate'     => __( 'נשלחו כבר כמה פניות מהמכשיר הזה. אפשר לנסות שוב מאוחר יותר או להתקשר אלינו.', 'rashel-hanukkah' ),
		'rsh_required' => __( 'צריך שם וטלפון כדי שנוכל לחזור אלייך.', 'rashel-hanukkah' ),
		'rsh_phone'    => __( 'מספר הטלפון לא נראה תקין. אפשר לבדוק אותו שוב?', 'rashel-hanukkah' ),
		'rsh_email'    => __( 'כתובת האימייל לא נראית תקינה. אפשר לתקן אותה או להשאיר את השדה ריק.', 'rashel-hanukkah' ),
		'rsh_store'    => __( 'לא הצלחנו לשמור את הפנייה. אפשר להתקשר אלינו ישירות.', 'rashel-hanukkah' ),
	);
}

/**
 * Build the error for a code.
 *
 * @param string $code   Error code, a key of rsh_lead_messages().
 * @param int    $status HTTP status.
 * @return WP_Error
 */
function rsh_lead_error( $code, $status ) {
	return new WP_Error( $code, rsh_lead_messages()[ $code ], array( 'status' => $status ) );
}

/**
 * Validate and store one submission.
 *
 * @param array<string, mixed> $input Submitted fields, still slashed-free and raw.
 * @return true|WP_Error True on success, WP_Error with a user-facing message otherwise.
 */
function rsh_process_lead( array $input ) {
	$field = static fn( string $key ): string => isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? $input[ $key ] : '';

	if ( ! wp_verify_nonce( sanitize_text_field( $field( 'nonce' ) ), 'rsh_lead' ) ) {
		return rsh_lead_error( 'rsh_nonce', 400 );
	}

	// Honeypot: bots fill hidden fields, people do not. Answer as if it worked.
	if ( '' !== $field( 'rsh_website' ) ) {
		return true;
	}

	if ( rsh_lead_rate_exceeded() ) {
		return rsh_lead_error( 'rsh_rate', 429 );
	}

	if ( rsh_turnstile_enabled() && ! rsh_turnstile_verify( sanitize_text_field( $field( 'cf-turnstile-response' ) ) ) ) {
		return rsh_lead_error( 'rsh_captcha', 400 );
	}

	$name  = mb_substr( trim( sanitize_text_field( $field( 'rsh_name' ) ) ), 0, 80 );
	$phone = mb_substr( trim( sanitize_text_field( $field( 'rsh_phone' ) ) ), 0, 30 );
	$email = trim( sanitize_text_field( $field( 'rsh_email' ) ) );

	if ( '' === $name || '' === $phone ) {
		return rsh_lead_error( 'rsh_required', 422 );
	}

	if ( ! rsh_phone_valid( $phone ) ) {
		return rsh_lead_error( 'rsh_phone', 422 );
	}

	if ( '' !== $email ) {
		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return rsh_lead_error( 'rsh_email', 422 );
		}
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'rsh_lead',
			'post_status' => 'publish',
			'post_title'  => $name . ' — ' . $phone,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return rsh_lead_error( 'rsh_store', 500 );
	}

	$fields = array(
		'name'   => $name,
		'phone'  => $phone,
		'email'  => $email,
		'source' => rsh_lead_source( $field( 'rsh_source' ) ),
		'utm'    => rsh_clean_utm( $field( 'rsh_utm' ) ),
	);

	foreach ( $fields as $key => $value ) {
		if ( '' !== $value ) {
			update_post_meta( $post_id, 'rsh_' . $key, $value );
		}
	}

	rsh_lead_store_crm( $post_id );
	rsh_lead_rate_bump();
	rsh_lead_dispatch( $post_id );

	/**
	 * Fires once a lead is stored.
	 *
	 * @param int                   $post_id Stored lead ID.
	 * @param array<string, string> $fields  Field key => value.
	 */
	do_action( 'rsh_lead_stored', $post_id, $fields );

	return true;
}

/**
 * Keep the source URL only when it points at this site.
 *
 * @param string $value Submitted URL.
 * @return string
 */
function rsh_lead_source( $value ) {
	$url = esc_url_raw( $value, array( 'http', 'https' ) );

	if ( '' === $url ) {
		return '';
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );
	$home = wp_parse_url( home_url(), PHP_URL_HOST );

	return ( $host && $home && strtolower( $host ) === strtolower( $home ) ) ? $url : '';
}

/**
 * No-JavaScript path: a normal POST that redirects back to the form.
 *
 * @return void
 */
function rsh_handle_lead_post() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- rsh_process_lead() verifies the nonce before anything is read or stored.
	$input  = wp_unslash( $_POST );
	$result = rsh_process_lead( $input );
	$back   = rsh_lead_source( isset( $input['rsh_source'] ) && is_string( $input['rsh_source'] ) ? $input['rsh_source'] : '' );

	if ( '' === $back ) {
		$back = home_url( '/' );
	}

	$back = add_query_arg( 'rsh_lead', is_wp_error( $result ) ? rawurlencode( (string) $result->get_error_code() ) : 'sent', $back );

	wp_safe_redirect( $back . '#consult-form' );
	exit;
}
add_action( 'admin_post_nopriv_rsh_lead', 'rsh_handle_lead_post' );
add_action( 'admin_post_rsh_lead', 'rsh_handle_lead_post' );

/**
 * Message to show after a no-JavaScript round trip, if any.
 *
 * @return array{status:string, message:string}|null
 */
function rsh_lead_notice() {
	// Read-only display flag from a redirect this site issued; nothing is changed here.
	$flag = isset( $_GET['rsh_lead'] ) ? sanitize_key( wp_unslash( $_GET['rsh_lead'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'sent' === $flag ) {
		return array(
			'status'  => 'success',
			'message' => '',
		);
	}

	$messages = rsh_lead_messages();

	return isset( $messages[ $flag ] )
		? array(
			'status'  => 'error',
			'message' => $messages[ $flag ],
		)
		: null;
}
