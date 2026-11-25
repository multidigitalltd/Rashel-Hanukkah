<?php
/**
 * Pure logic: no WordPress calls, no globals, no output.
 *
 * Everything here is unit-tested on its own (tests/unit) and is the only place
 * where the rules for prices, the countdown, marked-up copy, CSV cells, the
 * Turnstile answer and secret sealing live.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A phone number reduced to what a link or a comparison may hold.
 *
 * @param string $phone Phone number as written.
 * @param bool   $plus  Keep a leading plus sign (tel: links) or digits only.
 * @return string
 */
function rsh_phone_digits( string $phone, bool $plus = false ): string {
	$digits = (string) preg_replace( '/[^0-9]/', '', $phone );

	return $plus && str_starts_with( trim( $phone ), '+' ) ? '+' . $digits : $digits;
}

/**
 * Is this a plausible phone number? Nine to fifteen digits (E.164 upper bound).
 *
 * @param string $phone Phone number as written.
 * @return bool
 */
function rsh_phone_valid( string $phone ): bool {
	$length = strlen( rsh_phone_digits( $phone ) );

	return $length >= 9 && $length <= 15;
}

/**
 * A price in whole shekels the way he-IL prints it: "2,160 ₪".
 *
 * @param int $amount Whole shekels.
 * @return string
 */
function rsh_format_price( int $amount ): string {
	return number_format( max( 0, $amount ), 0, '.', ',' ) . ' ₪';
}

/**
 * The price before the discount: the stored one, or the sale price scaled up
 * by the campaign discount when none was stored.
 *
 * @param int $sale     Sale price, whole shekels.
 * @param int $original Stored original price, 0 when not set.
 * @param int $discount Campaign discount in percent, 1 to 99.
 * @return int
 */
function rsh_original_price( int $sale, int $original, int $discount ): int {
	if ( $original > $sale ) {
		return $original;
	}

	$discount = min( 99, max( 1, $discount ) );

	return (int) round( $sale * 100 / ( 100 - $discount ) );
}

/**
 * Split one line of marked-up copy into plain and highlighted segments.
 *
 * The editor writes *like this* around the words that get the marker sweep.
 * An unmatched asterisk is kept as text.
 *
 * @param string $line One line, without line breaks.
 * @return array<int, array{text:string, mark:bool}>
 */
function rsh_parse_marked_line( string $line ): array {
	$segments = array();
	$parts    = preg_split( '/\*([^*]+)\*/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE );

	foreach ( false === $parts ? array( $line ) : $parts as $index => $part ) {
		if ( '' === $part ) {
			continue;
		}

		$segments[] = array(
			'text' => $part,
			'mark' => 1 === $index % 2,
		);
	}

	return $segments;
}

/**
 * Split a block of marked-up copy into lines of segments.
 *
 * @param string $text Copy with one visual line per text line.
 * @return array<int, array<int, array{text:string, mark:bool}>>
 */
function rsh_parse_marked_text( string $text ): array {
	$lines = array();
	$split = preg_split( '/\R/u', trim( $text ) );

	foreach ( false === $split ? array() : $split as $line ) {
		$line = trim( $line );

		if ( '' !== $line ) {
			$lines[] = rsh_parse_marked_line( $line );
		}
	}

	return $lines;
}

/**
 * The address of a request on this site: the home URL's origin and the
 * request's own path.
 *
 * The request path already contains any subdirectory the site lives in, so it
 * is joined to the origin, never to the home URL itself.
 *
 * @param string $home Home URL, with or without a subdirectory.
 * @param string $path Request path, as the server received it.
 * @return string Empty when the home URL has no host.
 */
function rsh_request_url( string $home, string $path ): string {
	$parts = parse_url( $home ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure function without WordPress; the input is the site's own home URL.

	if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
		return '';
	}

	$origin = ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );

	return $origin . '/' . ltrim( $path, '/' );
}

/**
 * A course syllabus, parsed from its stored text.
 *
 * One item per line, "title | description". The description starts after the
 * last "|", so a title may contain one ("שיעור 1 | מבוא"). A line that starts
 * with an asterisk is a bonus. Lessons and bonuses share one running number,
 * as in the design.
 *
 * @param string $text Stored syllabus.
 * @return array<int, array{number:string, title:string, text:string, bonus:bool}>
 */
function rsh_parse_syllabus( string $text ): array {
	$items = array();
	$split = preg_split( '/\R/u', trim( $text ) );

	foreach ( false === $split ? array() : $split as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		$bonus = str_starts_with( $line, '*' );
		$line  = $bonus ? ltrim( substr( $line, 1 ) ) : $line;
		$cut   = strrpos( $line, '|' );
		$title = trim( false === $cut ? $line : substr( $line, 0, $cut ) );

		if ( '' === $title ) {
			continue;
		}

		$items[] = array(
			'number' => str_pad( (string) ( count( $items ) + 1 ), 2, '0', STR_PAD_LEFT ),
			'title'  => $title,
			'text'   => false === $cut ? '' : trim( substr( $line, $cut + 1 ) ),
			'bonus'  => $bonus,
		);
	}

	return $items;
}

/**
 * The characters of a text that runs around a circle, in placing order.
 *
 * Each character gets its own slot on the ring. Hebrew is placed in logical
 * order and reads correctly; a run of digits (with its percent sign) must be
 * placed reversed, or "50%" would read backwards.
 *
 * @param string $text Ring text.
 * @return string[] One entry per slot.
 */
function rsh_ring_chars( string $text ): array {
	$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
	$chars = false === $chars ? array() : $chars;
	$out   = array();
	$run   = array();

	foreach ( $chars as $char ) {
		if ( 1 === preg_match( '/^[0-9%.,]$/', $char ) ) {
			array_unshift( $run, $char );
			continue;
		}

		$out = array_merge( $out, $run, array( $char ) );
		$run = array();
	}

	return array_merge( $out, $run );
}

/**
 * The accent colour of a course card, by its place in the menu.
 *
 * The design cycles eleven accents. Each has a second, slightly deeper shade
 * for small white text and thin outlines, where the first would fall short of
 * the 4.5:1 contrast the standard requires.
 *
 * @param int $index Zero-based place of the course among all courses.
 * @return array{name:string, base:string, strong:string}
 */
function rsh_course_color( int $index ): array {
	$colors = array(
		'olive'   => array( '#667a65', '#667a65' ),
		'mustard' => array( '#896c3f', '#896c3f' ),
		'mauve'   => array( '#a86b79', '#9d6471' ),
		'teal'    => array( '#4f8fa3', '#437c8e' ),
		'taupe'   => array( '#8c7d63', '#82735a' ),
		'rose'    => array( '#b9727f', '#a8606e' ),
	);
	$cycle  = array( 'olive', 'mustard', 'mauve', 'teal', 'taupe', 'rose', 'olive', 'mustard', 'mauve', 'teal', 'taupe' );
	$name   = $cycle[ max( 0, $index ) % count( $cycle ) ];

	return array(
		'name'   => $name,
		'base'   => $colors[ $name ][0],
		'strong' => $colors[ $name ][1],
	);
}

/**
 * Is this the name of a public host? A dotted DNS name: not an IP address,
 * not "localhost", not a single label.
 *
 * A first check when an address is saved. The request itself still goes
 * through WordPress's safe HTTP API, which resolves the name and refuses
 * private and loopback addresses at send time.
 *
 * @param string $host Hostname.
 * @return bool
 */
function rsh_public_host( string $host ): bool {
	$host = strtolower( $host );

	return 1 === preg_match( '/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$/', $host )
		&& ! str_ends_with( $host, '.localhost' )
		&& ! str_ends_with( $host, '.local' )
		&& ! str_ends_with( $host, '.internal' );
}

/**
 * Time left until the deadline as zero-padded days, hours, minutes, seconds.
 *
 * @param int $deadline Deadline, Unix seconds.
 * @param int $now      Current time, Unix seconds.
 * @return array{days:string, hours:string, minutes:string, seconds:string}
 */
function rsh_countdown_parts( int $deadline, int $now ): array {
	$left    = max( 0, $deadline - $now );
	$days    = intdiv( $left, 86400 );
	$left   -= $days * 86400;
	$hours   = intdiv( $left, 3600 );
	$left   -= $hours * 3600;
	$minutes = intdiv( $left, 60 );
	$pad     = static fn( int $value ): string => str_pad( (string) $value, 2, '0', STR_PAD_LEFT );

	return array(
		'days'    => $pad( $days ),
		'hours'   => $pad( $hours ),
		'minutes' => $pad( $minutes ),
		'seconds' => $pad( $left - $minutes * 60 ),
	);
}

/**
 * Defuse a cell a spreadsheet would run as a formula (standard 4.2).
 *
 * @param string $value Cell.
 * @return string
 */
function rsh_csv_cell( string $value ): string {
	return '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ? "'" . $value : $value;
}

/**
 * Campaign source as stored: letters, digits and a few separators, 200 at most.
 *
 * @param string $value Raw "source / medium / campaign".
 * @return string
 */
function rsh_clean_utm( string $value ): string {
	$value = (string) preg_replace( '/[^\p{L}\p{N} _.\/\-]/u', '', $value );

	return mb_substr( trim( $value ), 0, 200 );
}

/**
 * Does a Siteverify answer accept the token for this form and this site?
 *
 * Success alone is not enough (standard 15.3.5): the token must have been
 * issued for the form's action and for the site's own hostname.
 *
 * @param mixed  $body   Decoded Siteverify response.
 * @param string $action The widget's data-action.
 * @param string $host   The site's hostname.
 * @return bool
 */
function rsh_turnstile_response_ok( $body, string $action, string $host ): bool {
	return is_array( $body )
		&& true === ( $body['success'] ?? false )
		&& ( $body['action'] ?? '' ) === $action
		&& '' !== $host
		&& strtolower( $host ) === strtolower( (string) ( $body['hostname'] ?? '' ) );
}

/**
 * Seal a secret for storage (XChaCha20-Poly1305), bound to its field name.
 *
 * @param string $plain Secret.
 * @param string $key   32 raw bytes.
 * @param string $field Field name, authenticated with the secret.
 * @return string Base64 of nonce and ciphertext, or '' when sealing is impossible.
 */
function rsh_seal( string $plain, string $key, string $field ): string {
	if ( '' === $plain || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES !== strlen( $key ) ) {
		return '';
	}

	$nonce = random_bytes( SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES );

	return base64_encode( $nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt( $plain, $field, $nonce, $key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Transport encoding of ciphertext, not obfuscation.
}

/**
 * Open a sealed secret.
 *
 * @param string $sealed Value from rsh_seal().
 * @param string $key    32 raw bytes.
 * @param string $field  Field name it was sealed for.
 * @return string The secret, or '' when it cannot be opened.
 */
function rsh_unseal( string $sealed, string $key, string $field ): string {
	$raw   = base64_decode( $sealed, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Transport encoding of ciphertext, not obfuscation.
	$nonce = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

	if ( false === $raw || strlen( $raw ) <= $nonce || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES !== strlen( $key ) ) {
		return '';
	}

	try {
		$plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt( substr( $raw, $nonce ), $field, substr( $raw, 0, $nonce ), $key );
	} catch ( SodiumException $e ) {
		return '';
	}

	return false === $plain ? '' : $plain;
}

/**
 * Signature header value for an outgoing webhook (standard 4.10).
 *
 * @param string $body      Request body, exactly as sent.
 * @param int    $timestamp Unix seconds, sent alongside.
 * @param string $secret    Shared secret.
 * @return string "t=...,v1=..." with an HMAC-SHA256 over "timestamp.body".
 */
function rsh_sign_payload( string $body, int $timestamp, string $secret ): string {
	return 't=' . $timestamp . ',v1=' . hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );
}
