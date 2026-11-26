<?php
/**
 * Template helpers: the small repeated pieces of the landing page.
 *
 * Templates print; the decisions (which page, which image, which number) are
 * made here, so no template runs a query or holds a rule.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the current request the landing page? The site's front page, or any page
 * that uses the "דף נחיתה חנוכה" template.
 *
 * @return bool
 */
function rsh_is_landing() {
	return is_front_page() || is_page_template( 'page-templates/template-landing.php' );
}

/**
 * URL of a file shipped with the theme.
 *
 * @param string $relative Path relative to the theme root.
 * @return string
 */
function rsh_asset( $relative ) {
	return RSH_URI . '/' . ltrim( $relative, '/' );
}

/**
 * The address of the page being rendered, without its query string.
 *
 * @return string
 */
function rsh_current_url() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$path = (string) wp_parse_url( $path, PHP_URL_PATH );

	return rsh_request_url( home_url(), $path );
}

/**
 * The pill that sends the visitor to the course menu or to a payment page.
 *
 * @param array{href?:string, variant?:string, pulse?:bool, external?:bool, context?:string, class?:string, arrow?:string, style?:string} $args Options.
 * @return void
 */
function rsh_cta( array $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'href'     => '#courses',
			'variant'  => 'mauve',
			'pulse'    => false,
			'external' => false,
			'context'  => '',
			'class'    => '',
			'arrow'    => 'arrow',
			'style'    => '',
		)
	);

	$classes = trim( 'rsh-cta rsh-cta--' . $args['variant'] . ( $args['pulse'] ? ' rsh-pulse' : '' ) . ' ' . $args['class'] );
	?>
	<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $args['href'] ); ?>"<?php echo '' === $args['style'] ? '' : ' style="' . esc_attr( $args['style'] ) . '"'; ?><?php echo $args['external'] ? ' target="_blank" rel="noopener"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal. ?>>
		<span class="rsh-cta__t"><?php rsh_the_text( 'cta' ); ?></span>
		<?php if ( '' !== $args['context'] ) : ?>
			<span class="rsh-sr-only"> — <?php echo esc_html( $args['context'] ); ?></span>
		<?php endif; ?>
		<?php if ( $args['external'] ) : ?>
			<span class="rsh-sr-only"> <?php esc_html_e( '(נפתח בחלון חדש)', 'rashel-hanukkah' ); ?></span>
		<?php endif; ?>
		<?php if ( 'chevrons' === $args['arrow'] ) : ?>
			<span class="rsh-cta__chev" aria-hidden="true">&gt;&gt;</span>
		<?php else : ?>
			<span class="rsh-cta__arrow" aria-hidden="true">←</span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * The countdown to the end of the campaign.
 *
 * The digits are rendered on the server for the moment the page was built and
 * corrected by landing.js as soon as it runs, so a cached page is never wrong
 * for longer than the script takes to load. It is a timer, not a live region:
 * a screen reader hears it when the visitor reaches it, not every second.
 *
 * @param string $variant "yellow" (black digits on yellow), "colors" (one accent per box) or "bar" (the small one in the sticky bar).
 * @return void
 */
function rsh_countdown( $variant = 'yellow' ) {
	$parts = rsh_countdown_parts( rsh_deadline(), time() );
	$units = array(
		'days'    => 'cd_days',
		'hours'   => 'cd_hours',
		'minutes' => 'cd_minutes',
		'seconds' => 'cd_seconds',
	);
	?>
	<div class="rsh-cd rsh-cd--<?php echo esc_attr( $variant ); ?>" role="timer" aria-label="<?php echo esc_attr( rsh_text( 'cd_label' ) ); ?>" data-rsh-countdown>
		<?php foreach ( $units as $unit => $label_key ) : ?>
			<div class="rsh-cd__box">
				<span class="rsh-cd__n" data-rsh-unit="<?php echo esc_attr( $unit ); ?>"><?php echo esc_html( $parts[ $unit ] ); ?></span>
				<span class="rsh-cd__l"><?php rsh_the_text( $label_key ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * A text set around a circle: one absolutely placed span per character, each
 * turned by its share of the circle. Decorative; the caller gives the reading.
 *
 * @param string   $text    The text; a "✦" ends it.
 * @param string[] $accents Class suffixes for the stars, in order.
 * @return void
 */
function rsh_ring( $text, array $accents = array() ) {
	$chars = rsh_ring_chars( $text );
	$count = count( $chars );
	$star  = 0;

	if ( ! $count ) {
		return;
	}

	echo '<span class="rsh-ring" aria-hidden="true">';

	foreach ( $chars as $index => $char ) {
		$class = 'rsh-ring__c';

		if ( '✦' === $char ) {
			$class .= ' rsh-ring__c--star' . ( isset( $accents[ $star ] ) ? ' rsh-ring__c--' . $accents[ $star ] : '' );
			++$star;
		}

		printf(
			'<span class="%1$s" style="%2$s">%3$s</span>',
			esc_attr( $class ),
			esc_attr( sprintf( 'transform:rotate(%.2Fdeg)', -360 * $index / $count ) ),
			' ' === $char ? '&nbsp;' : esc_html( $char )
		);
	}

	echo '</span>';
}

/**
 * The spinning discount badge: an olive disc, the ring text and a yellow
 * centre with the discount.
 *
 * @param string $modifier Extra class that places the badge in its section.
 * @return void
 */
function rsh_badge( $modifier = '' ) {
	$ring = rsh_text( 'badge_ring' ) . ' ✦ ' . rsh_text( 'badge_big' ) . ' ' . rsh_text( 'badge_small' ) . ' ✦ ';
	?>
	<div class="rsh-badge rsh-wobble <?php echo esc_attr( $modifier ); ?>">
		<span class="rsh-badge__disc" aria-hidden="true"></span>
		<span class="rsh-badge__spin rsh-spin"><?php rsh_ring( $ring, array( 'pink', 'blue' ) ); ?></span>
		<p class="rsh-badge__center">
			<span class="rsh-badge__big"><?php rsh_the_text( 'badge_big' ); ?></span>
			<span class="rsh-badge__small"><?php rsh_the_text( 'badge_small' ); ?></span>
		</p>
	</div>
	<?php
}

/**
 * A moving yellow strip. Decorative: it repeats the benefits, so it is hidden
 * from assistive technology and stands still for reduced motion.
 *
 * @param string $variant "left" or "right" (the tilt of a horizontal strip), or "vertical" (the strip beside the opening photos).
 * @return void
 */
function rsh_ticker( $variant = 'left' ) {
	$items = rsh_text_lines( 'ticker' );

	if ( ! $items ) {
		return;
	}

	$variant = in_array( $variant, array( 'left', 'right', 'vertical' ), true ) ? $variant : 'left';
	?>
	<div class="rsh-ticker rsh-ticker--<?php echo esc_attr( $variant ); ?>" aria-hidden="true">
		<div class="rsh-ticker__band">
			<div class="rsh-ticker__track">
				<?php for ( $half = 0; $half < 2; $half++ ) : ?>
					<div class="rsh-ticker__half">
						<?php for ( $repeat = 0; $repeat < 2; $repeat++ ) : ?>
							<?php foreach ( $items as $item ) : ?>
								<span class="rsh-ticker__item"><span class="rsh-ticker__t"><?php echo esc_html( $item ); ?></span><span class="rsh-ticker__star">✦</span></span>
							<?php endforeach; ?>
						<?php endfor; ?>
					</div>
				<?php endfor; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * The coupon code in its dashed pill, after its lead-in. Prints nothing when
 * no coupon is set.
 *
 * @param string $size "lg" in the benefits section, "sm" on a course card.
 * @return void
 */
function rsh_coupon_line( $size = 'sm' ) {
	$coupon = rsh_coupon();

	if ( '' === $coupon ) {
		return;
	}
	?>
	<p class="rsh-coupon rsh-coupon--<?php echo esc_attr( $size ); ?>">
		<span class="rsh-coupon__lead"><?php rsh_the_text( 'coupon_lead' ); ?></span>
		<bdo class="rsh-coupon__code" dir="ltr">
		<?php
		if ( 'lg' === $size ) :
			?>
			<span class="rsh-coupon__star" aria-hidden="true">✦</span><?php endif; ?><?php echo esc_html( $coupon ); ?></bdo>
	</p>
	<?php
}

/**
 * The line icons of the benefits: for each, the brand-colour blob that pops in
 * behind it and the strokes that draw themselves, in drawing order.
 *
 * @return array<int, array{color:string, blob:string, strokes:string[]}>
 */
function rsh_benefit_icons() {
	return array(
		array(
			'color'   => '#4f8fa3',
			'blob'    => 'M18.5 19.5 H38.5 Q41.5 19.5 41.5 22.5 V34.5 Q41.5 37.5 38.5 37.5 H18.5 Q15.5 37.5 15.5 34.5 V22.5 Q15.5 19.5 18.5 19.5 Z',
			'strokes' => array( 'M16 17 H36 Q39 17 39 20 V32 Q39 35 36 35 H16 Q13 35 13 32 V20 Q13 17 16 17 Z', 'M13 23 H39', 'M17 30 H24', 'M30 30 H34' ),
		),
		array(
			'color'   => '#a86b79',
			'blob'    => 'M28.5 15.5 C29.5 23.5 33.5 27.5 41.5 28.5 C33.5 29.5 29.5 33.5 28.5 41.5 C27.5 33.5 23.5 29.5 15.5 28.5 C23.5 27.5 27.5 23.5 28.5 15.5 Z',
			'strokes' => array( 'M26 13 C27 21 31 25 39 26 C31 27 27 31 26 39 C25 31 21 27 13 26 C21 25 25 21 26 13 Z', 'M37 12 V18 M34 15 H40' ),
		),
		array(
			'color'   => '#667a65',
			'blob'    => 'M28.5 28.5 m-11 0 a11 11 0 1 0 22 0 a11 11 0 1 0 -22 0',
			'strokes' => array( 'M37 26 A11 11 0 1 1 33.8 18.2', 'M34.6 13 L33.8 18.2 L28.6 17.6', 'M23.5 21.5 L31 26 L23.5 30.5 Z' ),
		),
		array(
			'color'   => '#896c3f',
			'blob'    => 'M18.5 20.5 H38.5 V33.5 H18.5 Z',
			'strokes' => array( 'M17 18 H35 Q36 18 36 19 V31 H16 V19 Q16 18 17 18 Z', 'M12 34 H40', 'M22.5 25 L26 22 L29.5 25 V28.5 H22.5 Z' ),
		),
	);
}

/**
 * Print one benefit icon.
 *
 * @param int $index Zero-based place of the benefit.
 * @return void
 */
function rsh_benefit_icon( $index ) {
	$icons = rsh_benefit_icons();
	$icon  = $icons[ $index % count( $icons ) ];
	?>
	<svg class="rsh-icon rsh-icon--<?php echo esc_attr( (string) ( $index % 4 + 1 ) ); ?>" viewBox="0 0 52 52" aria-hidden="true" focusable="false">
		<path class="rsh-icon__blob" d="<?php echo esc_attr( $icon['blob'] ); ?>" fill="<?php echo esc_attr( $icon['color'] ); ?>" fill-opacity="0.85"/>
		<?php foreach ( $icon['strokes'] as $order => $stroke ) : ?>
			<path class="rsh-icon__line" pathLength="1" d="<?php echo esc_attr( $stroke ); ?>" fill="none" stroke="#1a1a1a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="<?php echo esc_attr( sprintf( '--rsh-d:%.2Fs', 0.15 + 0.3 * $order ) ); ?>"/>
		<?php endforeach; ?>
	</svg>
	<?php
}

/**
 * One line of marked-up copy: plain text, with the highlighted words wrapped.
 *
 * @param array<int, array{text:string, mark:bool}> $segments Segments from rsh_parse_marked_line().
 * @return void
 */
function rsh_marked_line( array $segments ) {
	foreach ( $segments as $segment ) {
		if ( $segment['mark'] ) {
			printf( '<span class="rsh-mark">%s</span>', esc_html( $segment['text'] ) );
		} else {
			echo esc_html( $segment['text'] );
		}
	}
}

/**
 * A photo shipped with the theme, with explicit dimensions so it never shifts
 * the layout.
 *
 * @param string                          $file  Path under assets/img, without a leading slash.
 * @param int                             $width Intrinsic width.
 * @param int                             $height Intrinsic height.
 * @param array<string, string|int|false> $attrs Extra attributes; "alt" defaults to empty (decorative).
 * @return void
 */
function rsh_image( $file, $width, $height, array $attrs = array() ) {
	$attrs = array_merge(
		array(
			'src'      => rsh_asset( 'assets/img/' . $file ),
			'width'    => (int) $width,
			'height'   => (int) $height,
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attrs
	);

	$html = '<img';
	foreach ( $attrs as $name => $value ) {
		if ( false !== $value ) {
			$html .= sprintf( ' %s="%s"', esc_attr( $name ), 'src' === $name ? esc_url( (string) $value ) : esc_attr( (string) $value ) );
		}
	}

	echo $html . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Every attribute is escaped above.
}

/**
 * A course's photo: the one chosen in the media library, otherwise the one
 * shipped with the theme, otherwise nothing.
 *
 * @param array<string, mixed> $course Course from rsh_courses().
 * @param string               $alt    Alt text; empty for a decorative use.
 * @return void
 */
function rsh_course_image( array $course, $alt = '' ) {
	$thumb = (int) ( $course['thumb'] ?? 0 );
	$image = is_string( $course['image'] ?? null ) ? $course['image'] : '';

	if ( $thumb ) {
		echo wp_get_attachment_image(
			$thumb,
			'rsh-card',
			false,
			array(
				'alt'     => $alt,
				'loading' => 'lazy',
				'sizes'   => '(min-width: 1240px) 360px, (min-width: 640px) 45vw, 92vw',
			)
		);

		return;
	}

	if ( in_array( $image, rsh_bundled_course_images(), true ) ) {
		rsh_image( 'courses/' . $image . '.webp', 720, 480, array( 'alt' => $alt ) );
	}
}

/**
 * Photos for the arch gallery: one per course, without repeats.
 *
 * @return array<int, array<string, mixed>>
 */
function rsh_gallery_courses() {
	$seen    = array();
	$gallery = array();

	foreach ( rsh_courses() as $course ) {
		$key = $course['thumb'] ? 'id:' . $course['thumb'] : 'file:' . $course['image'];

		if ( isset( $seen[ $key ] ) || ( ! $course['thumb'] && ! in_array( $course['image'], rsh_bundled_course_images(), true ) ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$gallery[]    = $course;
	}

	return $gallery;
}

/**
 * The three tiles of the opening collage. Each holds two media slots that cross-fade.
 *
 * @return array<int, array{0:string, 1:string}>
 */
function rsh_mosaic() {
	return array(
		array( 'open_2', 'open_1' ),
		array( 'open_3', 'open_5' ),
		array( 'open_4', 'open_2' ),
	);
}

/**
 * Testimonial screenshots: the ones chosen in the dashboard, otherwise the
 * eleven of the design in its order (number, width, height).
 *
 * @return array<int, array{id:int, file:string, width:int, height:int}>
 */
function rsh_testimonials() {
	$items = array();

	foreach ( rsh_media_ids( 'testimonials' ) as $id ) {
		$items[] = array(
			'id'     => $id,
			'file'   => '',
			'width'  => 0,
			'height' => 0,
		);
	}

	if ( $items ) {
		return $items;
	}

	foreach (
		array(
			array( 1, 720, 574 ),
			array( 4, 720, 1158 ),
			array( 8, 720, 458 ),
			array( 2, 720, 720 ),
			array( 11, 720, 400 ),
			array( 6, 720, 178 ),
			array( 3, 720, 342 ),
			array( 10, 720, 526 ),
			array( 5, 528, 445 ),
			array( 7, 720, 245 ),
			array( 9, 528, 280 ),
		) as $item
	) {
		$items[] = array(
			'id'     => 0,
			'file'   => sprintf( 'testimonials/t%02d.webp', $item[0] ),
			'width'  => $item[1],
			'height' => $item[2],
		);
	}

	return $items;
}

/**
 * A captions file for a testimonial video, when one was set in the dashboard.
 *
 * @param string $video "v01" or "v02".
 * @return string URL, or an empty string.
 */
function rsh_captions_url( $video ) {
	return (string) rsh_setting( 'captions_' . $video );
}
