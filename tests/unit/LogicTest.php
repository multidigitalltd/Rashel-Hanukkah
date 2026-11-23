<?php
/**
 * Unit tests for the theme's pure logic (inc/logic.php).
 *
 * @package Rashel_Hanukkah
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;

/**
 * Prices, the countdown, marked-up copy, CSV cells, the Turnstile answer,
 * secret sealing and webhook signatures.
 */
final class LogicTest extends TestCase {

	/**
	 * Phone numbers are reduced to digits, with the plus kept only on request.
	 */
	public function test_phone_digits(): void {
		$this->assertSame( '0549420423', rsh_phone_digits( '054-9420423' ) );
		$this->assertSame( '0549420423', rsh_phone_digits( '054-9420423', true ) );
		$this->assertSame( '+972549420423', rsh_phone_digits( '+972 54 942 0423', true ) );
		$this->assertSame( '972549420423', rsh_phone_digits( '+972 54 942 0423' ) );
		$this->assertSame( '', rsh_phone_digits( 'javascript:alert()' ) );
	}

	/**
	 * A phone number needs nine to fifteen digits.
	 *
	 * @dataProvider phones
	 *
	 * @param string $phone Phone as typed.
	 * @param bool   $valid Expected verdict.
	 */
	public function test_phone_valid( string $phone, bool $valid ): void {
		$this->assertSame( $valid, rsh_phone_valid( $phone ) );
	}

	/**
	 * Phones for test_phone_valid().
	 *
	 * @return array<string, array{0:string, 1:bool}>
	 */
	public static function phones(): array {
		return array(
			'mobile'          => array( '054-9420423', true ),
			'landline'        => array( '03-1234567', true ),
			'international'   => array( '+972 54 942 0423', true ),
			'too short'       => array( '12345678', false ),
			'too long'        => array( '1234567890123456', false ),
			'letters only'    => array( 'call me', false ),
			'empty'           => array( '', false ),
			'script, 9 chars' => array( '<script>', false ),
		);
	}

	/**
	 * Prices print the way the design does.
	 */
	public function test_format_price(): void {
		$this->assertSame( '480 ₪', rsh_format_price( 480 ) );
		$this->assertSame( '2,160 ₪', rsh_format_price( 2160 ) );
		$this->assertSame( '0 ₪', rsh_format_price( -5 ) );
	}

	/**
	 * The original price is the stored one, or the sale price scaled by the discount.
	 */
	public function test_original_price(): void {
		$this->assertSame( 960, rsh_original_price( 480, 0, 50 ) );
		$this->assertSame( 1392, rsh_original_price( 696, 0, 50 ) );
		$this->assertSame( 1000, rsh_original_price( 480, 1000, 50 ) );
		$this->assertSame( 640, rsh_original_price( 480, 0, 25 ) );
		// A stored original that is not above the sale price is ignored.
		$this->assertSame( 960, rsh_original_price( 480, 400, 50 ) );
		// A discount outside 1 to 99 is clamped, never a division by zero.
		$this->assertSame( 48000, rsh_original_price( 480, 0, 100 ) );
		$this->assertSame( 485, rsh_original_price( 480, 0, 0 ) );
	}

	/**
	 * Asterisks wrap the highlighted words; everything else is plain text.
	 */
	public function test_parse_marked_line(): void {
		$this->assertSame(
			array(
				array(
					'text' => 'אוהבת את עולם ',
					'mark' => false,
				),
				array(
					'text' => 'הקולינריה והעיצוב',
					'mark' => true,
				),
			),
			rsh_parse_marked_line( 'אוהבת את עולם *הקולינריה והעיצוב*' )
		);

		$this->assertSame(
			array(
				array(
					'text' => 'בלי סימון',
					'mark' => false,
				),
			),
			rsh_parse_marked_line( 'בלי סימון' )
		);

		// An unmatched asterisk stays as text, and markup is never interpreted.
		$this->assertSame(
			array(
				array(
					'text' => '2 * 3 <b>',
					'mark' => false,
				),
			),
			rsh_parse_marked_line( '2 * 3 <b>' )
		);
	}

	/**
	 * Each text line becomes one visual line; blank lines are dropped.
	 */
	public function test_parse_marked_text(): void {
		$lines = rsh_parse_marked_text( "רוצה ללמוד *תחום אחד לעומק,*\r\n\r\n  בצורה יסודית ומקצועית,  \n" );

		$this->assertCount( 2, $lines );
		$this->assertTrue( $lines[0][1]['mark'] );
		$this->assertSame( 'בצורה יסודית ומקצועית,', $lines[1][0]['text'] );
		$this->assertSame( array(), rsh_parse_marked_text( "  \n " ) );
	}

	/**
	 * A site in a subdirectory does not get that directory twice.
	 */
	public function test_request_url(): void {
		$this->assertSame( 'https://example.test/campaign/hanukkah/', rsh_request_url( 'https://example.test/campaign', '/campaign/hanukkah/' ) );
		$this->assertSame( 'https://example.test/campaign/', rsh_request_url( 'https://example.test/campaign/', '/campaign/' ) );
		$this->assertSame( 'https://example.test/', rsh_request_url( 'https://example.test', '/' ) );
		$this->assertSame( 'https://example.test/', rsh_request_url( 'https://example.test', '' ) );
		$this->assertSame( 'http://127.0.0.1:8089/landing/', rsh_request_url( 'http://127.0.0.1:8089', '/landing/' ) );
		// A path can never move the address to another host.
		$this->assertSame( 'https://example.test/evil.test/x', rsh_request_url( 'https://example.test', '//evil.test/x' ) );
		$this->assertSame( '', rsh_request_url( 'not a url', '/' ) );
	}

	/**
	 * Only a dotted public DNS name passes as a webhook host.
	 *
	 * @dataProvider hosts
	 *
	 * @param string $host     Hostname.
	 * @param bool   $expected Expected verdict.
	 */
	public function test_public_host( string $host, bool $expected ): void {
		$this->assertSame( $expected, rsh_public_host( $host ) );
	}

	/**
	 * Hosts for test_public_host().
	 *
	 * @return array<string, array{0:string, 1:bool}>
	 */
	public static function hosts(): array {
		return array(
			'domain'          => array( 'crm.example.co.il', true ),
			'upper case'      => array( 'CRM.Example.com', true ),
			'ipv4'            => array( '127.0.0.1', false ),
			'private ipv4'    => array( '10.0.0.5', false ),
			'metadata ipv4'   => array( '169.254.169.254', false ),
			'ipv6'            => array( '[::1]', false ),
			'localhost'       => array( 'localhost', false ),
			'sub.localhost'   => array( 'app.localhost', false ),
			'mdns'            => array( 'printer.local', false ),
			'internal'        => array( 'db.internal', false ),
			'single label'    => array( 'intranet', false ),
			'empty'           => array( '', false ),
			'trailing hyphen' => array( 'bad-.example.com', false ),
			'with path'       => array( 'example.com/x', false ),
		);
	}

	/**
	 * The countdown splits the time left and stops at zero.
	 */
	public function test_countdown_parts(): void {
		$now = 1_000_000;

		$this->assertSame(
			array(
				'days'    => '20',
				'hours'   => '14',
				'minutes' => '52',
				'seconds' => '07',
			),
			rsh_countdown_parts( $now + 20 * 86400 + 14 * 3600 + 52 * 60 + 7, $now )
		);

		$zero = array(
			'days'    => '00',
			'hours'   => '00',
			'minutes' => '00',
			'seconds' => '00',
		);

		$this->assertSame( $zero, rsh_countdown_parts( $now, $now ) );
		$this->assertSame( $zero, rsh_countdown_parts( $now - 500, $now ) );
		$this->assertSame( '123', rsh_countdown_parts( $now + 123 * 86400, $now )['days'] );
	}

	/**
	 * Cells a spreadsheet would run as a formula are defused.
	 *
	 * @dataProvider cells
	 *
	 * @param string $cell     Cell.
	 * @param string $expected Defused cell.
	 */
	public function test_csv_cell( string $cell, string $expected ): void {
		$this->assertSame( $expected, rsh_csv_cell( $cell ) );
	}

	/**
	 * Cells for test_csv_cell().
	 *
	 * @return array<string, array{0:string, 1:string}>
	 */
	public static function cells(): array {
		return array(
			'formula'  => array( '=HYPERLINK("http://evil.test")', "'=HYPERLINK(\"http://evil.test\")" ),
			'plus'     => array( '+972549420423', "'+972549420423" ),
			'minus'    => array( '-1+1', "'-1+1" ),
			'at'       => array( '@SUM(A1)', "'@SUM(A1)" ),
			'tab'      => array( "\t=1", "'\t=1" ),
			'plain'    => array( 'רחל כהן', 'רחל כהן' ),
			'phone'    => array( '054-9420423', '054-9420423' ),
			'empty'    => array( '', '' ),
			'mid sign' => array( 'a=b', 'a=b' ),
		);
	}

	/**
	 * The campaign source keeps letters, digits and separators only.
	 */
	public function test_clean_utm(): void {
		$this->assertSame( 'facebook / cpc / hanukkah-2026', rsh_clean_utm( 'facebook / cpc / hanukkah-2026' ) );
		$this->assertSame( 'scriptalert1/script', rsh_clean_utm( '<script>alert(1)</script>' ) );
		$this->assertSame( 'פייסבוק', rsh_clean_utm( '  פייסבוק  ' ) );
		$this->assertSame( 200, mb_strlen( rsh_clean_utm( str_repeat( 'a', 500 ) ) ) );
	}

	/**
	 * A Turnstile token counts only for this form and this hostname.
	 */
	public function test_turnstile_response_ok(): void {
		$good = array(
			'success'  => true,
			'action'   => 'rsh_lead',
			'hostname' => 'Example.co.il',
		);

		$this->assertTrue( rsh_turnstile_response_ok( $good, 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( array( 'success' => false ) + $good, 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( array( 'success' => 'true' ) + $good, 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( array( 'action' => 'login' ) + $good, 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( array( 'hostname' => 'evil.test' ) + $good, 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( array( 'success' => true ), 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( $good, 'rsh_lead', '' ) );
		$this->assertFalse( rsh_turnstile_response_ok( null, 'rsh_lead', 'example.co.il' ) );
		$this->assertFalse( rsh_turnstile_response_ok( 'success', 'rsh_lead', 'example.co.il' ) );
	}

	/**
	 * A sealed secret opens only with the same key and for the same field.
	 */
	public function test_seal_round_trip(): void {
		$key    = random_bytes( 32 );
		$sealed = rsh_seal( 'secret-value', $key, 'turnstile_secret' );

		$this->assertNotSame( '', $sealed );
		$this->assertStringNotContainsString( 'secret-value', $sealed );
		$this->assertSame( 'secret-value', rsh_unseal( $sealed, $key, 'turnstile_secret' ) );
		// Sealing twice never gives the same text (a fresh nonce each time).
		$this->assertNotSame( $sealed, rsh_seal( 'secret-value', $key, 'turnstile_secret' ) );
	}

	/**
	 * Anything wrong with the key, the field or the text opens to nothing.
	 */
	public function test_unseal_refuses(): void {
		$key    = random_bytes( 32 );
		$sealed = rsh_seal( 'secret-value', $key, 'turnstile_secret' );

		$this->assertSame( '', rsh_unseal( $sealed, random_bytes( 32 ), 'turnstile_secret' ) );
		$this->assertSame( '', rsh_unseal( $sealed, $key, 'webhook_secret' ) );
		$this->assertSame( '', rsh_unseal( $sealed, 'short', 'turnstile_secret' ) );
		$this->assertSame( '', rsh_unseal( 'not base64 !!', $key, 'turnstile_secret' ) );
		$this->assertSame( '', rsh_unseal( '', $key, 'turnstile_secret' ) );
		$this->assertSame( '', rsh_unseal( substr( $sealed, 0, -4 ) . 'AAAA', $key, 'turnstile_secret' ) );
		$this->assertSame( '', rsh_seal( '', $key, 'turnstile_secret' ) );
		$this->assertSame( '', rsh_seal( 'secret-value', 'short', 'turnstile_secret' ) );
	}

	/**
	 * The webhook signature is an HMAC over the timestamp and the body.
	 */
	public function test_sign_payload(): void {
		$signature = rsh_sign_payload( '{"id":1}', 1700000000, 'shared' );

		$this->assertSame( 't=1700000000,v1=' . hash_hmac( 'sha256', '1700000000.{"id":1}', 'shared' ), $signature );
		$this->assertNotSame( $signature, rsh_sign_payload( '{"id":2}', 1700000000, 'shared' ) );
		$this->assertNotSame( $signature, rsh_sign_payload( '{"id":1}', 1700000001, 'shared' ) );
		$this->assertNotSame( $signature, rsh_sign_payload( '{"id":1}', 1700000000, 'other' ) );
	}
}
