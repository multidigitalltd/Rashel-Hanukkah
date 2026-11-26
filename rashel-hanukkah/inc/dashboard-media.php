<?php
/**
 * The dashboard's "media" tab: replace any photo or video of the page and
 * manage the two galleries. Loaded in the admin only.
 *
 * The form posts attachment IDs; assets/js/admin.js opens the media library
 * and fills them in. Validation is in rsh_validate_media().
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A small preview of an attachment or of the bundled file.
 *
 * @param string $url  Address of the image, or empty for none.
 * @param string $type "image" or "video".
 * @return string Markup, escaped.
 */
function rsh_media_preview( $url, $type ) {
	if ( 'video' === $type ) {
		return sprintf( '<span class="rsh-media__file" data-rsh-media-name>%s</span>', esc_html( wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ) );
	}

	return sprintf( '<img class="rsh-media__thumb" src="%s" alt="" data-rsh-media-thumb>', esc_url( $url ) );
}

/**
 * One replaceable slot.
 *
 * @param string               $key  Slot key.
 * @param array<string, mixed> $slot Slot definition.
 * @return void
 */
function rsh_dashboard_media_slot( $key, array $slot ) {
	$id      = rsh_media_id( $key );
	$type    = (string) $slot['type'];
	$default = rsh_asset( (string) $slot['file'] );
	$current = $default;

	if ( $id ) {
		$current = 'video' === $type ? (string) wp_get_attachment_url( $id ) : (string) wp_get_attachment_image_url( $id, 'medium' );
	}

	$field = sprintf(
		'<div class="rsh-media" data-rsh-media data-rsh-type="%1$s" data-rsh-default="%2$s">%3$s
			<input type="hidden" id="rsh-%4$s" name="rsh[%4$s]" value="%5$s" data-rsh-media-id>
			<span class="rsh-media__state" data-rsh-media-state>%6$s</span>
			<span class="rsh-media__actions">
				<button type="button" class="button" data-rsh-media-pick aria-describedby="rsh-%4$s-help">%7$s</button>
				<button type="button" class="button-link rsh-media__reset" data-rsh-media-reset%8$s>%9$s</button>
			</span>
		</div>',
		esc_attr( $type ),
		esc_url( $default ),
		rsh_media_preview( $current, $type ),
		esc_attr( $key ),
		esc_attr( $id ? (string) $id : '' ),
		esc_html( $id ? __( 'קובץ מספריית המדיה', 'rashel-hanukkah' ) : __( 'הקובץ המקורי של העיצוב', 'rashel-hanukkah' ) ),
		esc_html( 'video' === $type ? __( 'החלפת סרטון', 'rashel-hanukkah' ) : __( 'החלפת תמונה', 'rashel-hanukkah' ) ),
		$id ? '' : ' hidden',
		esc_html__( 'חזרה לקובץ המקורי', 'rashel-hanukkah' )
	);

	rsh_dashboard_row( 'rsh-' . $key, (string) $slot['label'], $field, (string) ( $slot['help'] ?? '' ) );
}

/**
 * One item of a gallery list.
 *
 * @param string $key List key.
 * @param int    $id  Attachment ID, or 0 for the empty template.
 * @return void
 */
function rsh_dashboard_media_item( $key, $id ) {
	$alt = $id ? trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : '';
	?>
	<li class="rsh-list__item" data-rsh-list-item>
		<img class="rsh-list__thumb" src="<?php echo esc_url( $id ? (string) wp_get_attachment_image_url( $id, 'thumbnail' ) : '' ); ?>" alt="<?php echo esc_attr( $alt ); ?>" data-rsh-list-thumb>
		<input type="hidden" name="rsh[<?php echo esc_attr( $key ); ?>][]" value="<?php echo esc_attr( $id ? (string) $id : '' ); ?>" data-rsh-list-id>
		<span class="rsh-list__alt" data-rsh-list-alt><?php echo esc_html( '' !== $alt ? $alt : __( 'אין תיאור לתמונה', 'rashel-hanukkah' ) ); ?></span>
		<span class="rsh-list__actions">
			<button type="button" class="button button-small" data-rsh-list-up><?php esc_html_e( 'הקדמה', 'rashel-hanukkah' ); ?></button>
			<button type="button" class="button button-small" data-rsh-list-down><?php esc_html_e( 'דחייה', 'rashel-hanukkah' ); ?></button>
			<button type="button" class="button-link rsh-list__remove" data-rsh-list-remove><?php esc_html_e( 'הסרה', 'rashel-hanukkah' ); ?></button>
		</span>
	</li>
	<?php
}

/**
 * One gallery list.
 *
 * @param string                           $key  List key.
 * @param array{label:string, help:string} $gallery Gallery definition.
 * @return void
 */
function rsh_dashboard_media_list( $key, array $gallery ) {
	?>
	<h2 id="rsh-list-<?php echo esc_attr( $key ); ?>-ttl"><?php echo esc_html( $gallery['label'] ); ?></h2>
	<p class="description"><?php echo esc_html( $gallery['help'] ); ?></p>
	<p class="description"><?php esc_html_e( 'התיאור שליד כל תמונה מוקרא לקוראי מסך. הוא נכתב בשדה "טקסט חלופי" של התמונה בספריית המדיה.', 'rashel-hanukkah' ); ?></p>
	<div class="rsh-list" data-rsh-list data-rsh-max="<?php echo esc_attr( (string) RSH_MEDIA_LIST_MAX ); ?>">
		<ol class="rsh-list__items" data-rsh-list-items aria-labelledby="rsh-list-<?php echo esc_attr( $key ); ?>-ttl">
			<?php
			foreach ( rsh_media_ids( $key ) as $id ) {
				rsh_dashboard_media_item( $key, $id );
			}
			?>
		</ol>
		<template data-rsh-list-template><?php rsh_dashboard_media_item( $key, 0 ); ?></template>
		<p><button type="button" class="button button-secondary" data-rsh-list-add><?php esc_html_e( 'הוספת תמונות', 'rashel-hanukkah' ); ?></button></p>
	</div>
	<?php
}

/**
 * The "media" tab.
 *
 * @return void
 */
function rsh_dashboard_media() {
	echo '<p>' . esc_html__( 'כל התמונות והסרטונים של העמוד. אחרי בחירה או שינוי סדר צריך ללחוץ "שמירת שינויים". תמונות הקורסים מוחלפות במסך "קורסים", בתמונה הראשית של כל קורס.', 'rashel-hanukkah' ) . '</p>';

	$lists = rsh_media_lists();
	$slots = rsh_media_slots();

	rsh_dashboard_media_list( 'works', $lists['works'] );

	foreach ( rsh_media_groups() as $group => $label ) {
		echo '<h2>' . esc_html( $label ) . '</h2><table class="form-table" role="presentation">';

		foreach ( $slots as $key => $slot ) {
			if ( $group === $slot['group'] ) {
				rsh_dashboard_media_slot( $key, $slot );
			}
		}

		echo '</table>';
	}

	rsh_dashboard_media_list( 'testimonials', $lists['testimonials'] );
}

/**
 * Store a submitted media form and log which slots changed.
 *
 * @param array<string, mixed> $input Submitted values, unslashed.
 * @return bool True when a value was refused.
 */
function rsh_dashboard_save_media( array $input ) {
	$before = rsh_media();
	$result = rsh_validate_media( $input, $before );
	$keys   = rsh_changed_keys( $before, $result['values'] );

	if ( $keys ) {
		update_option( 'rsh_media', $result['values'] );
		rsh_audit( 'media', 0, implode( ', ', $keys ) );
	}

	return $result['refused'];
}
