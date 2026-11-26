<?php
/**
 * Audit log: who did what, and when (standard 15.1.4 and 15.2.3).
 *
 * One option, newest first, never autoloaded. Entries name the object and the
 * changed fields only: never a value, a secret or a visitor's personal data.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Audit entries kept. */
const RSH_AUDIT_MAX = 1000;

/**
 * Record one action.
 *
 * @param string $action  Action key, see rsh_audit_labels().
 * @param int    $target  Object ID (a lead), 0 for anything else.
 * @param string $details Short detail without personal data.
 * @return void
 */
function rsh_audit( $action, $target = 0, $details = '' ) {
	$log = get_option( 'rsh_audit', array() );
	$log = is_array( $log ) ? $log : array();

	array_unshift(
		$log,
		array(
			'at'      => gmdate( 'Y-m-d H:i:s' ),
			'user'    => get_current_user_id(),
			'action'  => sanitize_key( $action ),
			'target'  => (int) $target,
			'details' => mb_substr( (string) $details, 0, 300 ),
		)
	);

	update_option( 'rsh_audit', array_slice( $log, 0, RSH_AUDIT_MAX ), false );
}

/**
 * Human labels of the audited actions.
 *
 * @return array<string, string>
 */
function rsh_audit_labels() {
	return array(
		'settings'  => __( 'שינוי הגדרות', 'rashel-hanukkah' ),
		'content'   => __( 'שינוי טקסטים', 'rashel-hanukkah' ),
		'media'     => __( 'שינוי תמונות', 'rashel-hanukkah' ),
		'secret'    => __( 'עדכון מפתח סודי', 'rashel-hanukkah' ),
		'view'      => __( 'צפייה בליד', 'rashel-hanukkah' ),
		'status'    => __( 'שינוי סטטוס ליד', 'rashel-hanukkah' ),
		'notes'     => __( 'עדכון הערות ליד', 'rashel-hanukkah' ),
		'export'    => __( 'ייצוא לידים', 'rashel-hanukkah' ),
		'delete'    => __( 'מחיקת ליד', 'rashel-hanukkah' ),
		'retention' => __( 'מחיקה אוטומטית (תקופת שמירה)', 'rashel-hanukkah' ),
	);
}
