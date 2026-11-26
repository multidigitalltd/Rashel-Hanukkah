<?php
/**
 * Mini CRM for leads (Multi Digital standard 15.2).
 *
 * The lead post type itself lives in leads.php. This file adds what the
 * standard asks of a lead store: a dedicated capability, status and notes,
 * the consent record, campaign source, a retention period with automatic
 * deletion, an audited CSV export, and a notification and a signed webhook
 * that are both retried.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Version of the capability grant; bump it to grant again to every role. */
const RSH_LEAD_CAPS_VERSION = '1';

/** Leads per query when exporting. */
const RSH_LEAD_EXPORT_BATCH = 500;

/** Attempts for the email and for the webhook before giving up. */
const RSH_LEAD_SEND_TRIES = 4;

/**
 * Lead statuses: key => label.
 *
 * @return array<string,string>
 */
function rsh_lead_statuses() {
	return array(
		'new'        => __( 'חדש', 'rashel-hanukkah' ),
		'progress'   => __( 'בטיפול', 'rashel-hanukkah' ),
		'closed'     => __( 'נסגר', 'rashel-hanukkah' ),
		'irrelevant' => __( 'לא רלוונטי', 'rashel-hanukkah' ),
	);
}

/**
 * Primitive capabilities of the lead post type (capability_type rsh_lead).
 *
 * @return string[]
 */
function rsh_lead_caps() {
	return array(
		'edit_rsh_leads',
		'edit_others_rsh_leads',
		'edit_published_rsh_leads',
		'edit_private_rsh_leads',
		'publish_rsh_leads',
		'read_private_rsh_leads',
		'delete_rsh_leads',
		'delete_others_rsh_leads',
		'delete_published_rsh_leads',
		'delete_private_rsh_leads',
	);
}

/**
 * Give the lead capabilities to the roles that handle leads: administrators
 * only, unless the rsh_lead_roles filter adds one. Editors and authors do not
 * see personal data from the form.
 *
 * @return void
 */
function rsh_leads_grant_caps() {
	$done = get_option( 'rsh_lead_caps_granted', array() );
	$done = is_array( $done ) && RSH_LEAD_CAPS_VERSION === ( $done['version'] ?? '' ) ? $done : array( 'version' => RSH_LEAD_CAPS_VERSION );

	$roles   = (array) apply_filters( 'rsh_lead_roles', array( 'administrator' ) );
	$changed = false;

	foreach ( $roles as $role_name ) {
		$role_name = (string) $role_name;
		$role      = get_role( $role_name );

		if ( ! $role || ! empty( $done[ $role_name ] ) ) {
			continue;
		}

		foreach ( rsh_lead_caps() as $cap ) {
			$role->add_cap( $cap );
		}

		$done[ $role_name ] = true;
		$changed            = true;
	}

	if ( $changed ) {
		update_option( 'rsh_lead_caps_granted', $done, false );
	}
}
add_action( 'init', 'rsh_leads_grant_caps', 20 );

/**
 * May the user handle leads at all?
 *
 * @param int $user_id User; 0 for the current user.
 * @return bool
 */
function rsh_can_manage_leads( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	return $user_id > 0 && user_can( $user_id, 'edit_others_rsh_leads' );
}

/*
 * Stored with the lead
 */

/**
 * The consent record kept with every lead (standard 5.2).
 *
 * The form shows the privacy note and links the policy; the record keeps
 * which version of the policy was in force and when the visitor submitted.
 * There is no marketing consent: the form only asks to be called back.
 *
 * @return string JSON.
 */
function rsh_lead_consent_record() {
	return (string) wp_json_encode(
		array(
			'basis'   => 'contact_request',
			'notice'  => 'form_privacy_note',
			'policy'  => rsh_privacy_version(),
			'at'      => gmdate( 'c' ),
			'channel' => 'website_form',
		)
	);
}

/**
 * Store the CRM fields of a new lead.
 *
 * @param int $post_id Lead.
 * @return void
 */
function rsh_lead_store_crm( $post_id ) {
	update_post_meta( $post_id, 'rsh_status', 'new' );
	update_post_meta( $post_id, 'rsh_consent', rsh_lead_consent_record() );
}

/*
 * Edit screen
 */

/**
 * Opening a lead counts as viewing it (standard 15.2.3).
 *
 * @param WP_Screen $screen Current screen.
 * @return void
 */
function rsh_lead_audit_view( $screen ) {
	if ( 'post' !== $screen->base || 'rsh_lead' !== $screen->post_type ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen; the ID is only logged.
	$lead_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( $lead_id ) {
		rsh_audit( 'view', $lead_id );
	}
}
add_action( 'current_screen', 'rsh_lead_audit_view' );

/**
 * Deleting a lead is logged before it goes.
 *
 * @param int     $post_id Post.
 * @param WP_Post $post    Post object.
 * @return void
 */
function rsh_lead_audit_delete( $post_id, $post ) {
	if ( 'rsh_lead' === $post->post_type && ! doing_action( 'rsh_lead_cleanup' ) ) {
		rsh_audit( 'delete', (int) $post_id );
	}
}
add_action( 'before_delete_post', 'rsh_lead_audit_delete', 10, 2 );

/**
 * The boxes on a lead: what the visitor sent, and how it is being handled.
 *
 * @return void
 */
function rsh_lead_meta_boxes() {
	add_meta_box( 'rsh-lead-details', __( 'פרטי הפנייה', 'rashel-hanukkah' ), 'rsh_lead_details_box', 'rsh_lead', 'normal', 'high' );
	add_meta_box( 'rsh-lead-crm', __( 'טיפול בליד', 'rashel-hanukkah' ), 'rsh_lead_crm_box', 'rsh_lead', 'side', 'high' );
}
add_action( 'add_meta_boxes_rsh_lead', 'rsh_lead_meta_boxes' );

/**
 * What the visitor sent. Read only, and escaped where printed (standard 15.2.9).
 *
 * @param WP_Post $post Lead.
 * @return void
 */
function rsh_lead_details_box( $post ) {
	$meta    = static fn( $key ) => (string) get_post_meta( $post->ID, 'rsh_' . $key, true );
	$consent = json_decode( $meta( 'consent' ), true );
	$rows    = array(
		__( 'שם', 'rashel-hanukkah' )         => $meta( 'name' ),
		__( 'טלפון', 'rashel-hanukkah' )      => $meta( 'phone' ),
		__( 'אימייל', 'rashel-hanukkah' )     => $meta( 'email' ),
		__( 'מאיזה עמוד', 'rashel-hanukkah' ) => $meta( 'source' ),
		__( 'קמפיין', 'rashel-hanukkah' )     => $meta( 'utm' ),
		__( 'התקבל', 'rashel-hanukkah' )      => (string) get_the_date( 'Y-m-d H:i', $post ),
	);
	?>
	<table class="widefat striped">
		<tbody>
			<?php foreach ( $rows as $label => $value ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td><?php echo esc_html( '' === $value ? '—' : $value ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ( is_array( $consent ) ) : ?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: date, 2: policy version. */
					__( 'הערת הפרטיות הוצגה בטופס. נשלח: %1$s · גרסת המדיניות: %2$s', 'rashel-hanukkah' ),
					(string) ( $consent['at'] ?? '' ),
					(string) ( $consent['policy'] ?? '' )
				)
			);
			?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Status, notes and delivery state.
 *
 * @param WP_Post $post Lead.
 * @return void
 */
function rsh_lead_crm_box( $post ) {
	$status = (string) get_post_meta( $post->ID, 'rsh_status', true );
	$notes  = (string) get_post_meta( $post->ID, 'rsh_notes', true );
	$states = array(
		'sent'     => __( 'נשלח', 'rashel-hanukkah' ),
		'failed'   => __( 'נכשל, ינוסה שוב', 'rashel-hanukkah' ),
		'gave'     => __( 'נכשל סופית', 'rashel-hanukkah' ),
		'nosecret' => __( 'לא נשלח: חסר מפתח חתימה', 'rashel-hanukkah' ),
		''         => __( 'לא מוגדר', 'rashel-hanukkah' ),
	);

	wp_nonce_field( 'rsh_lead_crm_' . $post->ID, 'rsh_lead_crm_nonce' );
	?>
	<p>
		<label for="rsh-lead-status"><strong><?php esc_html_e( 'סטטוס', 'rashel-hanukkah' ); ?></strong></label><br>
		<select id="rsh-lead-status" name="rsh_status">
			<?php foreach ( rsh_lead_statuses() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php selected( '' === $status ? 'new' : $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="rsh-lead-notes"><strong><?php esc_html_e( 'הערות פנימיות', 'rashel-hanukkah' ); ?></strong></label>
		<textarea id="rsh-lead-notes" name="rsh_notes" rows="5" class="widefat" maxlength="2000"><?php echo esc_textarea( $notes ); ?></textarea>
	</p>
	<p class="description">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: email state, 2: webhook state. */
				__( 'התראה במייל: %1$s · שליחה ל-CRM: %2$s', 'rashel-hanukkah' ),
				$states[ (string) get_post_meta( $post->ID, 'rsh_mail', true ) ] ?? '',
				$states[ (string) get_post_meta( $post->ID, 'rsh_hook_state', true ) ] ?? ''
			)
		);
		?>
	</p>
	<?php
}

/**
 * Save status and notes: nonce, capability, allowlist (standard 4.1, 16.2).
 *
 * @param int $post_id Lead.
 * @return void
 */
function rsh_lead_save_crm( $post_id ) {
	if ( ! isset( $_POST['rsh_lead_crm_nonce'] ) || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rsh_lead_crm_nonce'] ) ), 'rsh_lead_crm_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$status = isset( $_POST['rsh_status'] ) ? sanitize_key( wp_unslash( $_POST['rsh_status'] ) ) : '';
	$notes  = isset( $_POST['rsh_notes'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['rsh_notes'] ) ), 0, 2000 ) : '';
	$before = (string) get_post_meta( $post_id, 'rsh_status', true );

	if ( isset( rsh_lead_statuses()[ $status ] ) && $status !== $before ) {
		update_post_meta( $post_id, 'rsh_status', $status );
		rsh_audit( 'status', $post_id, $before . ' → ' . $status );
	}

	if ( (string) get_post_meta( $post_id, 'rsh_notes', true ) !== $notes ) {
		update_post_meta( $post_id, 'rsh_notes', $notes );
		rsh_audit( 'notes', $post_id );
	}
}
add_action( 'save_post_rsh_lead', 'rsh_lead_save_crm' );

/**
 * Phone and status columns on the list, so the office can scan without opening rows.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function rsh_lead_columns( $columns ) {
	$date = $columns['date'] ?? '';
	unset( $columns['date'] );

	$columns['rsh_phone']  = __( 'טלפון', 'rashel-hanukkah' );
	$columns['rsh_email']  = __( 'אימייל', 'rashel-hanukkah' );
	$columns['rsh_status'] = __( 'סטטוס', 'rashel-hanukkah' );

	if ( $date ) {
		$columns['date'] = $date;
	}

	return $columns;
}
add_filter( 'manage_rsh_lead_posts_columns', 'rsh_lead_columns' );

/**
 * Lead column content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Lead ID.
 * @return void
 */
function rsh_lead_column_content( $column, $post_id ) {
	// Column keys deliberately match the meta keys they render.
	if ( 'rsh_phone' === $column || 'rsh_email' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, $column, true ) );
	}

	if ( 'rsh_status' === $column ) {
		$status = (string) get_post_meta( $post_id, 'rsh_status', true );
		echo esc_html( rsh_lead_statuses()[ '' === $status ? 'new' : $status ] ?? '' );
	}
}
add_action( 'manage_rsh_lead_posts_custom_column', 'rsh_lead_column_content', 10, 2 );

/*
 * Export (standard 15.2.4)
 */

/**
 * Every lead as CSV text, with a UTF-8 BOM for Excel.
 *
 * @return string
 */
function rsh_leads_csv() {
	$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory buffer for fputcsv.

	if ( false === $handle ) {
		return '';
	}

	fputcsv( $handle, array( 'תאריך', 'שם', 'טלפון', 'אימייל', 'מקור', 'קמפיין', 'סטטוס', 'הערות' ), ',', '"', '' );

	$statuses = rsh_lead_statuses();
	$batch    = max( 1, (int) apply_filters( 'rsh_lead_export_batch', RSH_LEAD_EXPORT_BATCH ) );

	// Every lead, batch by batch: an export never stops at a limit.
	for ( $page = 1; ; $page++ ) {
		$leads = get_posts(
			array(
				'post_type'      => 'rsh_lead',
				'post_status'    => 'any',
				'posts_per_page' => $batch,
				'paged'          => $page,
				'orderby'        => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'no_found_rows'  => true,
			)
		);

		foreach ( $leads as $lead ) {
			$meta   = static fn( $key ) => (string) get_post_meta( $lead->ID, 'rsh_' . $key, true );
			$status = $meta( 'status' );

			fputcsv(
				$handle,
				array_map(
					'rsh_csv_cell',
					array( (string) get_the_date( 'Y-m-d H:i', $lead ), $meta( 'name' ), $meta( 'phone' ), $meta( 'email' ), $meta( 'source' ), $meta( 'utm' ), $statuses[ '' === $status ? 'new' : $status ] ?? '', $meta( 'notes' ) )
				),
				',',
				'"',
				''
			);
		}

		if ( count( $leads ) < $batch ) {
			break;
		}
	}

	rewind( $handle );
	$csv = (string) stream_get_contents( $handle );
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the buffer above.

	return "\xEF\xBB\xBF" . $csv;
}

/**
 * Download every lead (admin-post.php?action=rsh_leads_export).
 *
 * @return void
 */
function rsh_leads_export() {
	check_admin_referer( 'rsh_leads_export' );

	if ( ! rsh_can_manage_leads() ) {
		wp_die( esc_html__( 'אין הרשאה.', 'rashel-hanukkah' ), '', array( 'response' => 403 ) );
	}

	$csv = rsh_leads_csv();
	rsh_audit( 'export', 0, (string) max( 0, substr_count( $csv, "\n" ) - 1 ) );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'X-Content-Type-Options: nosniff' );
	echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download; cells defused by rsh_csv_cell().
	exit;
}
add_action( 'admin_post_rsh_leads_export', 'rsh_leads_export' );

/**
 * Export button above the leads list.
 *
 * @param string $which top or bottom.
 * @return void
 */
function rsh_leads_export_button( $which ) {
	if ( 'top' !== $which || 'rsh_lead' !== get_current_screen()?->post_type || ! rsh_can_manage_leads() ) {
		return;
	}

	printf(
		'<a class="button" href="%1$s">%2$s</a>',
		esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rsh_leads_export' ), 'rsh_leads_export' ) ),
		esc_html__( 'ייצוא ל-CSV', 'rashel-hanukkah' )
	);
}
add_action( 'manage_posts_extra_tablenav', 'rsh_leads_export_button' );

/*
 * Retention (standard 15.2.5)
 */

/**
 * Daily: delete leads older than the retention period, in batches.
 *
 * @return int Leads deleted.
 */
function rsh_lead_cleanup() {
	$ids = get_posts(
		array(
			'post_type'      => 'rsh_lead',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 100,
			'no_found_rows'  => true,
			'date_query'     => array(
				array(
					'before' => gmdate( 'Y-m-d H:i:s', (int) strtotime( '-' . rsh_lead_retention_months() . ' months' ) ),
					'column' => 'post_date_gmt',
				),
			),
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}

	if ( $ids ) {
		rsh_audit( 'retention', 0, (string) count( $ids ) );
	}

	return count( $ids );
}
add_action(
	'rsh_lead_cleanup',
	static function (): void {
		rsh_lead_cleanup();
	}
);

/**
 * Keep the daily job scheduled.
 *
 * @return void
 */
function rsh_lead_schedule() {
	if ( ! wp_next_scheduled( 'rsh_lead_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'rsh_lead_cleanup' );
	}
}
add_action( 'init', 'rsh_lead_schedule' );

/**
 * Switching away from the theme removes its scheduled jobs.
 *
 * @return void
 */
function rsh_lead_unschedule() {
	wp_clear_scheduled_hook( 'rsh_lead_cleanup' );
}
add_action( 'switch_theme', 'rsh_lead_unschedule' );

/*
 * Notification and external CRM, both retried (standard 15.2.6, 15.2.7)
 */

/**
 * Send a stored lead on its way. The lead is already saved; a failure here is
 * retried in the background and never reaches the visitor.
 *
 * @param int $lead_id Lead.
 * @return void
 */
function rsh_lead_dispatch( $lead_id ) {
	rsh_lead_notify( $lead_id );

	if ( '' === rsh_lead_webhook_url() ) {
		return;
	}

	if ( ! rsh_lead_webhook_ready() ) {
		// Never sent unsigned: the lead waits in the dashboard until a signing key is set.
		update_post_meta( $lead_id, 'rsh_hook_state', 'nosecret' );

		return;
	}

	// Out of the request: the visitor does not wait for somebody else's server.
	wp_schedule_single_event( time(), 'rsh_lead_webhook', array( (int) $lead_id ) );
}

/**
 * Record one failed attempt and schedule the next, with a growing delay.
 *
 * @param int    $lead_id Lead.
 * @param string $state   Meta key holding the delivery state.
 * @param string $hook    Cron hook that retries.
 * @return void
 */
function rsh_lead_retry( $lead_id, $state, $hook ) {
	$tries = (int) get_post_meta( $lead_id, $state . '_tries', true ) + 1;
	update_post_meta( $lead_id, $state . '_tries', $tries );

	if ( $tries < RSH_LEAD_SEND_TRIES ) {
		update_post_meta( $lead_id, $state, 'failed' );
		wp_schedule_single_event( time() + $tries * 10 * MINUTE_IN_SECONDS, $hook, array( (int) $lead_id ) );

		return;
	}

	update_post_meta( $lead_id, $state, 'gave' );
}

/**
 * Tell the office about a lead. Only what is needed to call back is in the
 * email; everything else stays in the dashboard.
 *
 * @param int $lead_id Lead.
 * @return bool Sent.
 */
function rsh_lead_notify( $lead_id ) {
	if ( 'rsh_lead' !== get_post_type( $lead_id ) ) {
		return false;
	}

	$to = (string) rsh_setting( 'lead_to' );
	$to = is_email( $to ) ? $to : (string) get_option( 'admin_email' );

	$name  = (string) get_post_meta( $lead_id, 'rsh_name', true );
	$lines = array(
		__( 'שם', 'rashel-hanukkah' ) . ': ' . $name,
		__( 'טלפון', 'rashel-hanukkah' ) . ': ' . (string) get_post_meta( $lead_id, 'rsh_phone', true ),
		'',
		__( 'כל הפרטים בלוח הניהול:', 'rashel-hanukkah' ) . ' ' . admin_url( 'post.php?post=' . (int) $lead_id . '&action=edit' ),
	);

	$sent = wp_mail(
		$to,
		/* translators: %s: lead name. */
		sprintf( __( 'ליד חדש מדף הנחיתה — %s', 'rashel-hanukkah' ), $name ),
		implode( "\n", $lines ),
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);

	if ( $sent ) {
		update_post_meta( $lead_id, 'rsh_mail', 'sent' );

		return true;
	}

	rsh_lead_retry( $lead_id, 'rsh_mail', 'rsh_lead_notify_retry' );

	return false;
}
add_action(
	'rsh_lead_notify_retry',
	static function ( $lead_id ): void {
		rsh_lead_notify( (int) $lead_id );
	}
);

/**
 * The external CRM's address: https only, set in the dashboard.
 *
 * @return string
 */
function rsh_lead_webhook_url() {
	return esc_url_raw( (string) rsh_setting( 'webhook_url' ), array( 'https' ) );
}

/**
 * Can leads be delivered? An address and a signing key that opens. Without a
 * usable key nothing is sent: an unsigned request would carry personal data
 * the receiver cannot authenticate (standard 4.10).
 *
 * @return bool
 */
function rsh_lead_webhook_ready() {
	return '' !== rsh_lead_webhook_url() && '' !== rsh_secret( 'webhook_secret' );
}

/**
 * The JSON body sent to the external CRM.
 *
 * @param int $lead_id Lead.
 * @return string
 */
function rsh_lead_webhook_body( $lead_id ) {
	$meta = static fn( $key ) => (string) get_post_meta( $lead_id, 'rsh_' . $key, true );

	return (string) wp_json_encode(
		array(
			'id'       => (int) $lead_id,
			'name'     => $meta( 'name' ),
			'phone'    => $meta( 'phone' ),
			'email'    => $meta( 'email' ),
			'source'   => $meta( 'source' ),
			'utm'      => $meta( 'utm' ),
			'received' => (string) get_post_time( 'c', true, $lead_id ),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
}

/**
 * Deliver a lead to the external CRM, always signed (standard 4.10, 15.2.7).
 *
 * @param int $lead_id Lead.
 * @return bool Delivered.
 */
function rsh_lead_webhook( $lead_id ) {
	$url = rsh_lead_webhook_url();

	if ( '' === $url || 'rsh_lead' !== get_post_type( $lead_id ) ) {
		return false;
	}

	$secret = rsh_secret( 'webhook_secret' );

	// The key can disappear between scheduling and sending (cleared, or the salts changed).
	if ( '' === $secret ) {
		update_post_meta( $lead_id, 'rsh_hook_state', 'nosecret' );
		error_log( 'rashel-hanukkah: lead webhook not sent, signing key missing or unreadable' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational log line, no personal data.

		return false;
	}

	$body    = rsh_lead_webhook_body( $lead_id );
	$headers = array(
		'Content-Type'       => 'application/json; charset=utf-8',
		'X-Rashel-Signature' => rsh_sign_payload( $body, time(), $secret ),
	);

	$response = wp_safe_remote_post(
		$url,
		array(
			'timeout'     => 8,
			'redirection' => 0,
			'headers'     => $headers,
			'body'        => $body,
		)
	);

	$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

	if ( $code >= 200 && $code < 300 ) {
		update_post_meta( $lead_id, 'rsh_hook_state', 'sent' );

		return true;
	}

	error_log( 'rashel-hanukkah: lead webhook failed, status ' . $code ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational log line, no personal data.
	rsh_lead_retry( $lead_id, 'rsh_hook_state', 'rsh_lead_webhook' );

	return false;
}
add_action(
	'rsh_lead_webhook',
	static function ( $lead_id ): void {
		rsh_lead_webhook( (int) $lead_id );
	}
);
