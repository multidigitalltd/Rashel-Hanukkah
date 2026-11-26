<?php
/**
 * The page's media: every photo and video of the landing page can be replaced
 * from the dashboard, and two galleries (testimonials, graduates' work) are
 * ordered lists of media-library items.
 *
 * A slot that was not replaced shows the file shipped with the theme. The
 * choices live in the option "rsh_media" as attachment IDs; an ID that no
 * longer points at a usable attachment is ignored when the page renders.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Most items a gallery list may hold. */
const RSH_MEDIA_LIST_MAX = 60;

/** Graduates' work is shown this many photos at a time. */
const RSH_WORKS_BATCH = 9;

/**
 * The single slots: key => label, group, type, bundled file, intrinsic size
 * of the bundled file, and the image size used for a replacement.
 *
 * @return array<string, array{label:string, group:string, type:string, file:string, width:int, height:int, size:string, help?:string, widths?:int[]}>
 */
function rsh_media_slots() {
	$slots = array(
		'logo'        => array(
			'label'  => __( 'לוגו', 'rashel-hanukkah' ),
			'group'  => 'general',
			'file'   => 'assets/img/rashel-logo.png',
			'width'  => 270,
			'height' => 191,
			'size'   => 'medium',
			'help'   => __( 'לוגו כהה על רקע שקוף. על רקע כהה הוא מוצג בהיפוך צבעים.', 'rashel-hanukkah' ),
		),
		'og'          => array(
			'label'  => __( 'תמונת שיתוף (וואטסאפ, פייסבוק)', 'rashel-hanukkah' ),
			'group'  => 'general',
			'file'   => 'assets/img/og.jpg',
			'width'  => 1200,
			'height' => 630,
			'size'   => 'large',
			'help'   => __( 'מומלץ 1200 על 630 פיקסלים.', 'rashel-hanukkah' ),
		),
		'announce_bg' => array(
			'label'  => __( 'תמונת הרקע של ההכרזה', 'rashel-hanukkah' ),
			'group'  => 'announce',
			'file'   => 'assets/img/announce-bg.webp',
			'width'  => 1600,
			'height' => 1067,
			'size'   => 'full',
		),
		'benefits'    => array(
			'label'  => __( 'התמונה שליד ההטבות', 'rashel-hanukkah' ),
			'group'  => 'benefits',
			'file'   => 'assets/img/hanukkah-table.webp',
			'width'  => 1100,
			'height' => 733,
			'size'   => 'large',
			'widths' => array( 720 ),
		),
		'portrait'    => array(
			'label'  => __( 'התמונה של ראשל', 'rashel-hanukkah' ),
			'group'  => 'about',
			'file'   => 'assets/img/rashel-portrait.webp',
			'width'  => 640,
			'height' => 960,
			'size'   => 'large',
		),
	);

	for ( $n = 1; $n <= 5; $n++ ) {
		$slots[ 'open_' . $n ] = array(
			/* translators: %d: photo number, 1 to 5. */
			'label'  => sprintf( __( 'תמונת פתיחה %d', 'rashel-hanukkah' ), $n ),
			'group'  => 'hook',
			'file'   => 'assets/img/opening/o' . $n . '.webp',
			'width'  => 1200,
			'height' => 800,
			'size'   => 'large',
			'widths' => array( 480, 600, 900 ),
		);
	}

	foreach ( array( 'v01', 'v02' ) as $index => $video ) {
		$slots[ 'video_' . $video ] = array(
			/* translators: %d: video number, 1 or 2. */
			'label'  => sprintf( __( 'סרטון המלצה %d', 'rashel-hanukkah' ), $index + 1 ),
			'group'  => 'testimonials',
			'type'   => 'video',
			'file'   => 'assets/video/' . $video . '.mp4',
			'width'  => 0,
			'height' => 0,
			'size'   => 'full',
			'help'   => __( 'קובץ MP4. כדאי לדחוס אותו לפני ההעלאה: סרטון כבד מאט את העמוד.', 'rashel-hanukkah' ),
		);

		$slots[ 'poster_' . $video ] = array(
			/* translators: %d: video number, 1 or 2. */
			'label'  => sprintf( __( 'תמונת הפתיחה של סרטון %d', 'rashel-hanukkah' ), $index + 1 ),
			'group'  => 'testimonials',
			'file'   => 'assets/img/testimonials/' . $video . '-poster.webp',
			'width'  => 854,
			'height' => 480,
			'size'   => 'large',
		);
	}

	foreach ( $slots as $key => $slot ) {
		$slots[ $key ]['type'] = $slot['type'] ?? 'image';
	}

	return $slots;
}

/**
 * The gallery lists: key => label and explanation.
 *
 * @return array<string, array{label:string, help:string}>
 */
function rsh_media_lists() {
	return array(
		'works'        => array(
			'label' => __( 'גלריית עבודות של תלמידות', 'rashel-hanukkah' ),
			'help'  => __( 'המקטע מופיע בעמוד, מתחת להמלצות, רק כשיש בו תמונות. התמונות מוצגות לפי הסדר כאן, תשע בכל פעם, עם כפתור "הצג עוד".', 'rashel-hanukkah' ),
		),
		'testimonials' => array(
			'label' => __( 'צילומי ההמלצות', 'rashel-hanukkah' ),
			'help'  => __( 'רשימה ריקה מציגה את אחד־עשר הצילומים המקוריים של העיצוב.', 'rashel-hanukkah' ),
		),
	);
}

/**
 * Headings of the slot groups, in the order of the page.
 *
 * @return array<string, string>
 */
function rsh_media_groups() {
	return array(
		'general'      => __( 'כללי', 'rashel-hanukkah' ),
		'hook'         => __( 'מסך הפתיחה', 'rashel-hanukkah' ),
		'announce'     => __( 'ההכרזה', 'rashel-hanukkah' ),
		'benefits'     => __( 'ההטבות', 'rashel-hanukkah' ),
		'about'        => __( 'נעים להכיר', 'rashel-hanukkah' ),
		'testimonials' => __( 'המלצות', 'rashel-hanukkah' ),
	);
}

/**
 * The stored choices.
 *
 * @return array<string, mixed>
 */
function rsh_media() {
	$stored = get_option( 'rsh_media', array() );

	return is_array( $stored ) ? $stored : array();
}

/**
 * Is this attachment usable for a slot of the given type?
 *
 * @param int    $id   Attachment ID.
 * @param string $type "image" or "video".
 * @return bool
 */
function rsh_media_usable( $id, $type ) {
	if ( $id <= 0 || 'attachment' !== get_post_type( $id ) ) {
		return false;
	}

	return 'video' === $type ? 'video/mp4' === get_post_mime_type( $id ) : wp_attachment_is_image( $id );
}

/**
 * The attachment chosen for a slot, or 0 when the bundled file is used.
 *
 * @param string $key Slot key.
 * @return int
 */
function rsh_media_id( $key ) {
	$slots = rsh_media_slots();
	$id    = (int) ( rsh_media()[ $key ] ?? 0 );

	return isset( $slots[ $key ] ) && rsh_media_usable( $id, $slots[ $key ]['type'] ) ? $id : 0;
}

/**
 * The attachments of a gallery list, in order.
 *
 * @param string $key List key.
 * @return list<int>
 */
function rsh_media_ids( $key ) {
	$stored = rsh_media()[ $key ] ?? array();
	$ids    = array();

	if ( ! isset( rsh_media_lists()[ $key ] ) || ! is_array( $stored ) ) {
		return $ids;
	}

	foreach ( $stored as $id ) {
		if ( rsh_media_usable( (int) $id, 'image' ) ) {
			$ids[] = (int) $id;
		}
	}

	return $ids;
}

/**
 * Address of a slot's file: the replacement, otherwise the bundled one.
 *
 * @param string $key Slot key.
 * @return string
 */
function rsh_slot_url( $key ) {
	$slots = rsh_media_slots();

	if ( ! isset( $slots[ $key ] ) ) {
		return '';
	}

	$id = rsh_media_id( $key );

	if ( $id ) {
		$url = 'video' === $slots[ $key ]['type'] ? wp_get_attachment_url( $id ) : wp_get_attachment_image_url( $id, $slots[ $key ]['size'] );

		if ( $url ) {
			return (string) $url;
		}
	}

	return rsh_asset( $slots[ $key ]['file'] );
}

/**
 * The srcset of a slot's image: WordPress's own for a replacement, and the
 * smaller copies shipped beside a bundled file ("name-600.webp") when the
 * slot declares them. Empty when there is only one file.
 *
 * @param string $key Slot key.
 * @return string
 */
function rsh_slot_srcset( $key ) {
	$slots = rsh_media_slots();

	if ( ! isset( $slots[ $key ] ) ) {
		return '';
	}

	$slot = $slots[ $key ];
	$id   = rsh_media_id( $key );

	if ( $id ) {
		return (string) wp_get_attachment_image_srcset( $id, $slot['size'] );
	}

	$parts = array();

	foreach ( $slot['widths'] ?? array() as $width ) {
		$parts[] = rsh_asset( (string) preg_replace( '/\.(\w+)$/', '-' . $width . '.$1', $slot['file'] ) ) . ' ' . $width . 'w';
	}

	if ( $parts ) {
		$parts[] = rsh_asset( $slot['file'] ) . ' ' . $slot['width'] . 'w';
	}

	return implode( ', ', $parts );
}

/**
 * Print a slot's image. A replacement gets WordPress's own srcset; the
 * bundled file keeps the attributes the template passed.
 *
 * @param string                          $key   Slot key.
 * @param array<string, string|int|false> $attrs Extra attributes; "alt" defaults to empty (decorative).
 * @return void
 */
function rsh_slot_image( $key, array $attrs = array() ) {
	$slots = rsh_media_slots();

	if ( ! isset( $slots[ $key ] ) ) {
		return;
	}

	$slot = $slots[ $key ];
	$id   = rsh_media_id( $key );

	if ( ! $id ) {
		$srcset = rsh_slot_srcset( $key );

		if ( '' !== $srcset && ! isset( $attrs['srcset'] ) ) {
			$attrs['srcset'] = $srcset;
		}

		rsh_image( (string) preg_replace( '#^assets/img/#', '', $slot['file'] ), $slot['width'], $slot['height'], $attrs );

		return;
	}

	unset( $attrs['srcset'] );
	$attrs = array_merge(
		array(
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attrs
	);

	$clean = array();

	foreach ( $attrs as $name => $value ) {
		if ( false !== $value ) {
			$clean[ $name ] = (string) $value;
		}
	}

	echo wp_get_attachment_image( $id, $slot['size'], false, $clean );
}

/**
 * Alt text of a media-library image, or a fallback when none was written.
 *
 * @param int    $id       Attachment ID.
 * @param string $fallback Used when the attachment has no alt text.
 * @return string
 */
function rsh_attachment_alt( $id, $fallback ) {
	$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );

	return '' !== $alt ? $alt : $fallback;
}

/**
 * Clean a submitted media form: every ID must be an attachment of the right
 * kind. A refused slot keeps its stored value.
 *
 * @param array<string, mixed> $input  Submitted values, unslashed.
 * @param array<string, mixed> $before Stored values.
 * @return array{values: array<string, mixed>, refused: bool}
 */
function rsh_validate_media( array $input, array $before ) {
	$values  = array();
	$refused = false;

	foreach ( rsh_media_slots() as $key => $slot ) {
		$raw = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? trim( (string) $input[ $key ] ) : '';

		if ( '' === $raw || '0' === $raw ) {
			continue;
		}

		if ( ctype_digit( $raw ) && rsh_media_usable( (int) $raw, $slot['type'] ) ) {
			$values[ $key ] = (int) $raw;
		} else {
			$refused = true;

			if ( isset( $before[ $key ] ) ) {
				$values[ $key ] = $before[ $key ];
			}
		}
	}

	foreach ( array_keys( rsh_media_lists() ) as $key ) {
		$list = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
		$ids  = array();

		foreach ( $list as $raw ) {
			$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';

			if ( ctype_digit( $raw ) && rsh_media_usable( (int) $raw, 'image' ) ) {
				$ids[ (int) $raw ] = (int) $raw;
			} else {
				$refused = true;
			}
		}

		if ( count( $ids ) > RSH_MEDIA_LIST_MAX ) {
			$ids     = array_slice( $ids, 0, RSH_MEDIA_LIST_MAX );
			$refused = true;
		}

		if ( $ids ) {
			$values[ $key ] = array_values( $ids );
		}
	}

	return array(
		'values'  => $values,
		'refused' => $refused,
	);
}
