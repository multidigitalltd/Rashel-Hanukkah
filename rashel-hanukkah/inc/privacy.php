<?php
/**
 * Footer duties: the first-visit privacy notice, the mandatory legal links
 * and the Multi Digital credit (Multi Digital standard 15.4 to 15.6).
 *
 * The notice is the same static HTML for every visitor, hidden until the
 * script decides to show it, so it is safe under a full-page cache and causes
 * no layout shift (fixed position, shown after load).
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version of the privacy policy: the policy page's last change.
 *
 * A new version shows the notice again to everyone (standard 15.5.2).
 *
 * @return string
 */
function rsh_privacy_version() {
	$page_id = (int) get_option( 'wp_page_for_privacy_policy' );
	$page    = $page_id ? get_post( $page_id ) : null;

	return $page instanceof WP_Post && 'publish' === $page->post_status ? (string) mysql2date( 'Y-m-d-His', $page->post_modified_gmt, false ) : '1';
}

/**
 * Footer legal links: privacy policy, terms of use, accessibility statement
 * (standard 15.6.1), then any links of the "footer" menu not already there.
 *
 * @return array<int, array{url:string, label:string}>
 */
function rsh_legal_links() {
	$links = array();

	foreach (
		array(
			array( (string) get_privacy_policy_url(), __( 'מדיניות פרטיות', 'rashel-hanukkah' ) ),
			array( rsh_page_url( 'terms_page' ), __( 'תנאי שימוש', 'rashel-hanukkah' ) ),
			array( rsh_a11y_url(), __( 'הצהרת נגישות', 'rashel-hanukkah' ) ),
		) as $link
	) {
		if ( '' !== $link[0] ) {
			$links[] = array(
				'url'   => $link[0],
				'label' => $link[1],
			);
		}
	}

	$known     = array_map( static fn( $link ) => untrailingslashit( $link['url'] ), $links );
	$locations = get_nav_menu_locations();
	$items     = empty( $locations['footer'] ) ? array() : wp_get_nav_menu_items( (int) $locations['footer'], array( 'update_post_term_cache' => false ) );

	foreach ( is_array( $items ) ? $items : array() as $item ) {
		if ( 0 === (int) $item->menu_item_parent && ! in_array( untrailingslashit( (string) $item->url ), $known, true ) ) {
			$links[] = array(
				'url'   => (string) $item->url,
				'label' => (string) $item->title,
			);
		}
	}

	return $links;
}

/**
 * Show the Multi Digital credit? On by default; an administrator can turn it
 * off only where agreed with the client (standard 15.4.3).
 *
 * @return bool
 */
function rsh_show_credit() {
	return (bool) rsh_setting( 'credit' );
}

/**
 * Warn the site owner when there is no privacy policy page to link to.
 *
 * @return void
 */
function rsh_privacy_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || '' !== get_privacy_policy_url() ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
		esc_html__( 'לאתר אין עמוד מדיניות פרטיות פעיל, והודעת הפרטיות והטופס לא יכולים לקשר אליו (תקן Multi Digital 15.5).', 'rashel-hanukkah' ),
		esc_url( admin_url( 'options-privacy.php' ) ),
		esc_html__( 'לבחירת העמוד', 'rashel-hanukkah' )
	);
}
add_action( 'admin_notices', 'rsh_privacy_admin_notice' );
