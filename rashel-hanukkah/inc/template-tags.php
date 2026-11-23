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
 * The yellow or dark pill that sends the visitor to the course menu or to a
 * payment page.
 *
 * @param array{href?:string, variant?:string, pulse?:bool, external?:bool, context?:string, class?:string} $args Options.
 * @return void
 */
function rsh_cta( array $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'href'     => '#courses',
			'variant'  => 'yellow',
			'pulse'    => false,
			'external' => false,
			'context'  => '',
			'class'    => '',
		)
	);

	$classes = trim( 'rsh-cta rsh-cta--' . $args['variant'] . ( $args['pulse'] ? ' rsh-pulse' : '' ) . ' ' . $args['class'] );
	?>
	<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $args['href'] ); ?>"<?php echo $args['external'] ? ' target="_blank" rel="noopener"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal. ?>>
		<span class="rsh-cta__t"><?php rsh_the_text( 'cta' ); ?></span>
		<?php if ( '' !== $args['context'] ) : ?>
			<span class="rsh-sr-only"> — <?php echo esc_html( $args['context'] ); ?></span>
		<?php endif; ?>
		<?php if ( $args['external'] ) : ?>
			<span class="rsh-sr-only"> <?php esc_html_e( '(נפתח בחלון חדש)', 'rashel-hanukkah' ); ?></span>
		<?php endif; ?>
		<span class="rsh-cta__arrow" aria-hidden="true">←</span>
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
 * @param string $size Modifier: lg, md, sm or xs.
 * @return void
 */
function rsh_countdown( $size = 'lg' ) {
	$parts = rsh_countdown_parts( rsh_deadline(), time() );
	$units = array(
		'days'    => 'cd_days',
		'hours'   => 'cd_hours',
		'minutes' => 'cd_minutes',
		'seconds' => 'cd_seconds',
	);
	?>
	<div class="rsh-cd rsh-cd--<?php echo esc_attr( $size ); ?>" role="timer" aria-label="<?php echo esc_attr( rsh_text( 'cd_label' ) ); ?>" data-rsh-countdown>
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
 * The benefits strip that closes the hero and the page.
 *
 * @param string $modifier Extra class for the bright variant above the footer.
 * @return void
 */
function rsh_benefits( $modifier = '' ) {
	$items = rsh_text_lines( 'benefits' );

	if ( ! $items ) {
		return;
	}
	?>
	<div class="rsh-benefits <?php echo esc_attr( $modifier ); ?>">
		<p class="rsh-benefits__ttl rsh-wrap"><?php rsh_the_text( 'benefits_ttl' ); ?></p>
		<ul class="rsh-benefits__list rsh-wrap">
			<?php foreach ( $items as $item ) : ?>
				<li><span class="rsh-star-ch" aria-hidden="true">✦</span><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * The moving yellow strip. Decorative: it repeats the benefits list, so it is
 * hidden from assistive technology and stops for reduced motion.
 *
 * @param string $tilt "left" or "right".
 * @return void
 */
function rsh_ticker( $tilt = 'left' ) {
	$items = rsh_text_lines( 'ticker' );

	if ( ! $items ) {
		return;
	}
	?>
	<div class="rsh-ticker rsh-ticker--<?php echo esc_attr( 'right' === $tilt ? 'right' : 'left' ); ?>" aria-hidden="true">
		<div class="rsh-ticker__band">
			<div class="rsh-ticker__track">
				<?php for ( $half = 0; $half < 2; $half++ ) : ?>
					<div class="rsh-ticker__half">
						<?php for ( $repeat = 0; $repeat < 2; $repeat++ ) : ?>
							<?php foreach ( $items as $item ) : ?>
								<span class="rsh-ticker__item"><?php echo esc_html( $item ); ?><span class="rsh-ticker__star">✦</span></span>
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
 * @param array{name:string, image:string, thumb:int} $course Course from rsh_courses().
 * @param string                                      $alt    Alt text; empty for a decorative use.
 * @return void
 */
function rsh_course_image( array $course, $alt = '' ) {
	if ( $course['thumb'] ) {
		echo wp_get_attachment_image(
			$course['thumb'],
			'rsh-card',
			false,
			array(
				'alt'     => $alt,
				'loading' => 'lazy',
				'sizes'   => '(min-width: 1320px) 300px, (min-width: 640px) 45vw, 92vw',
			)
		);

		return;
	}

	if ( in_array( $course['image'], rsh_bundled_course_images(), true ) ) {
		rsh_image( 'courses/' . $course['image'] . '.webp', 500, 256, array( 'alt' => $alt ) );
	}
}

/**
 * Photos for the arch gallery: one per course, without repeats.
 *
 * @return array<int, array{name:string, image:string, thumb:int}>
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
 * The eight photos around the opening questions.
 *
 * Geometry lives in landing.css (.rsh-hook__img--N); this only says which file
 * each one shows and at which step it appears.
 *
 * @return array<int, array{file:string, width:int, height:int, step:int}>
 */
function rsh_hook_images() {
	$images = array();

	foreach (
		array(
			array( 'hero/h1-400.webp', 400, 267, 1 ),
			array( 'hero/h2-400.webp', 400, 267, 1 ),
			array( 'hero/h3-400.webp', 400, 267, 1 ),
			array( 'hero/h4-400.webp', 400, 267, 2 ),
			array( 'fruit-bar-600.webp', 600, 400, 2 ),
			array( 'hero/h5-400.webp', 400, 300, 2 ),
			array( 'hero/h3-400.webp', 400, 267, 3 ),
			array( 'hero/h1-400.webp', 400, 267, 3 ),
		) as $image
	) {
		$images[] = array(
			'file'   => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
			'step'   => $image[3],
		);
	}

	return $images;
}

/**
 * Testimonial screenshots, in the order of the design: number, width, height.
 *
 * @return array<int, array{file:string, width:int, height:int}>
 */
function rsh_testimonials() {
	$items = array();

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
