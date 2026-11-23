<?php
/**
 * Site settings and stored secrets.
 *
 * Plain settings live in one small option (rsh_settings). Secrets live in a
 * second option that is sealed with libsodium and never autoloaded; the sealing
 * key stays in wp-config.php (standard 16.2.13). A constant in wp-config.php
 * always wins over a value saved from the dashboard.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defaults for every plain setting.
 *
 * @return array<string, string|int|bool>
 */
function rsh_setting_defaults() {
	return array(
		'phone'          => '054-9420423',
		'deadline'       => '2026-12-13T23:59:59',
		'discount'       => 50,
		'sticky_bar'     => true,
		'a11y_toolbar'   => true,
		'credit'         => true,
		'lead_to'        => '',
		'lead_retention' => 24,
		'webhook_url'    => '',
		'turnstile_site' => '',
		'terms_page'     => 0,
		'a11y_page'      => 0,
		'captions_v01'   => '',
		'captions_v02'   => '',
	);
}

/**
 * One plain setting.
 *
 * @param string $key Setting key.
 * @return string|int|bool
 */
function rsh_setting( $key ) {
	static $saved = null;

	if ( null === $saved ) {
		$saved = get_option( 'rsh_settings', array() );
		$saved = is_array( $saved ) ? $saved : array();
	}

	$defaults = rsh_setting_defaults();

	return array_key_exists( $key, $saved ) ? $saved[ $key ] : ( $defaults[ $key ] ?? '' );
}

/**
 * Display phone number.
 *
 * @return string
 */
function rsh_phone() {
	return (string) rsh_setting( 'phone' );
}

/**
 * Campaign deadline as Unix seconds, read in the site's time zone.
 *
 * @return int
 */
function rsh_deadline() {
	$date = date_create_immutable( (string) rsh_setting( 'deadline' ), wp_timezone() );

	return false === $date ? 0 : $date->getTimestamp();
}

/**
 * Campaign discount in percent.
 *
 * @return int
 */
function rsh_discount() {
	$discount = (int) rsh_setting( 'discount' );

	return $discount >= 1 && $discount <= 99 ? $discount : 50;
}

/**
 * Months a lead is kept before it is deleted automatically.
 *
 * @return int
 */
function rsh_lead_retention_months() {
	$months = (int) rsh_setting( 'lead_retention' );

	return $months >= 1 && $months <= 120 ? $months : 24;
}

/**
 * Permalink of a page chosen in the settings, when it is published.
 *
 * @param string $key Setting key holding a page ID.
 * @return string
 */
function rsh_page_url( $key ) {
	$page_id = (int) rsh_setting( $key );

	return $page_id && 'publish' === get_post_status( $page_id ) ? (string) get_permalink( $page_id ) : '';
}

/*
 * Secrets
 */

/**
 * Names of the secrets the dashboard can store, with the constant that overrides each.
 *
 * @return array<string, string>
 */
function rsh_secret_constants() {
	return array(
		'turnstile_secret' => 'RSH_TURNSTILE_SECRET_KEY',
		'webhook_secret'   => 'RSH_WEBHOOK_SECRET',
	);
}

/**
 * The 32-byte sealing key: RSH_KEY from wp-config.php (base64), otherwise
 * derived from the site's own salts. Either way it never touches the database.
 *
 * @return string Raw bytes.
 */
function rsh_sealing_key() {
	$configured = defined( 'RSH_KEY' ) ? constant( 'RSH_KEY' ) : '';

	if ( is_string( $configured ) && '' !== $configured ) {
		$raw = base64_decode( $configured, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Key material is configured in base64.

		if ( false !== $raw && SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES === strlen( $raw ) ) {
			return $raw;
		}
	}

	return hash( 'sha256', 'rsh-sealing|' . wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
}

/**
 * Is this secret fixed in wp-config.php (and so locked in the dashboard)?
 *
 * @param string $name Secret name.
 * @return bool
 */
function rsh_secret_locked( $name ) {
	$constant = rsh_secret_constants()[ $name ] ?? '';

	return '' !== $constant && defined( $constant ) && '' !== trim( (string) constant( $constant ) );
}

/**
 * Read a secret, server side only.
 *
 * @param string $name Secret name.
 * @return string
 */
function rsh_secret( $name ) {
	if ( rsh_secret_locked( $name ) ) {
		return trim( (string) constant( rsh_secret_constants()[ $name ] ) );
	}

	$stored = get_option( 'rsh_secrets', array() );
	$sealed = is_array( $stored ) && isset( $stored[ $name ] ) && is_string( $stored[ $name ] ) ? $stored[ $name ] : '';

	return '' === $sealed ? '' : rsh_unseal( $sealed, rsh_sealing_key(), $name );
}

/**
 * Is a secret saved, even one that can no longer be opened?
 *
 * A saved Turnstile secret that cannot be opened (the salts changed) keeps the
 * form protected and refuses every submission, rather than letting it through.
 *
 * @param string $name Secret name.
 * @return bool
 */
function rsh_secret_saved( $name ) {
	if ( rsh_secret_locked( $name ) ) {
		return true;
	}

	$stored = get_option( 'rsh_secrets', array() );

	return is_array( $stored ) && ! empty( $stored[ $name ] );
}

/**
 * Store or clear a secret. Sealed, in an option that is not autoloaded.
 *
 * @param string $name  Secret name.
 * @param string $value New value; an empty string clears it.
 * @return bool Stored.
 */
function rsh_store_secret( $name, $value ) {
	if ( ! isset( rsh_secret_constants()[ $name ] ) ) {
		return false;
	}

	$stored = get_option( 'rsh_secrets', array() );
	$stored = is_array( $stored ) ? $stored : array();

	if ( '' === $value ) {
		unset( $stored[ $name ] );
	} else {
		$sealed = rsh_seal( $value, rsh_sealing_key(), $name );

		if ( '' === $sealed ) {
			return false;
		}

		$stored[ $name ] = $sealed;
	}

	update_option( 'rsh_secrets', $stored, false );

	return true;
}
