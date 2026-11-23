<?php
/**
 * The site's own dashboard (Multi Digital standard 15.1).
 *
 * One screen under "ראשל חנוכה" with four tabs: settings, copy, connections
 * and the audit log. Every save checks the capability and a nonce, validates
 * each field against its own rule on the server, and records who changed which
 * fields. Secrets are sealed before they are stored and are never sent back to
 * the browser.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Capability needed to open and save the dashboard. */
const RSH_DASHBOARD_CAP = 'manage_options';

/**
 * Dashboard tabs: key => label.
 *
 * @return array<string, string>
 */
function rsh_dashboard_tabs() {
	return array(
		'settings'    => __( 'הגדרות', 'rashel-hanukkah' ),
		'texts'       => __( 'טקסטים', 'rashel-hanukkah' ),
		'connections' => __( 'חיבורים ולידים', 'rashel-hanukkah' ),
		'log'         => __( 'יומן פעולות', 'rashel-hanukkah' ),
	);
}

/**
 * Register the menu entry.
 *
 * @return void
 */
function rsh_dashboard_menu() {
	add_menu_page(
		__( 'ראשל חנוכה', 'rashel-hanukkah' ),
		__( 'ראשל חנוכה', 'rashel-hanukkah' ),
		RSH_DASHBOARD_CAP,
		'rsh-dashboard',
		'rsh_dashboard_page',
		'dashicons-star-filled',
		20
	);
}
add_action( 'admin_menu', 'rsh_dashboard_menu' );

/**
 * The tab being shown.
 *
 * @return string
 */
function rsh_dashboard_tab() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter, checked against the list of tabs.
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';

	return isset( rsh_dashboard_tabs()[ $tab ] ) ? $tab : 'settings';
}

/**
 * Address of a dashboard tab.
 *
 * @param string $tab Tab key.
 * @return string
 */
function rsh_dashboard_url( $tab ) {
	return add_query_arg(
		array(
			'page' => 'rsh-dashboard',
			'tab'  => $tab,
		),
		admin_url( 'admin.php' )
	);
}

/**
 * Render the dashboard.
 *
 * @return void
 */
function rsh_dashboard_page() {
	if ( ! current_user_can( RSH_DASHBOARD_CAP ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'rashel-hanukkah' ), '', array( 'response' => 403 ) );
	}

	$tab = rsh_dashboard_tab();
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display flag from this site's own redirect.
	$saved = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : '';
	?>
	<div class="wrap rsh-admin" dir="rtl">
		<h1><?php esc_html_e( 'ראשל חנוכה: ניהול דף הנחיתה', 'rashel-hanukkah' ); ?></h1>

		<?php if ( '1' === $saved ) : ?>
			<div class="notice notice-success is-dismissible" role="status"><p><?php esc_html_e( 'השינויים נשמרו.', 'rashel-hanukkah' ); ?></p></div>
		<?php elseif ( 'partial' === $saved ) : ?>
			<div class="notice notice-warning is-dismissible" role="alert"><p><?php esc_html_e( 'השינויים נשמרו, אבל ערך אחד או יותר לא היה תקין והושאר כפי שהיה. אפשר לבדוק את השדות ולשמור שוב.', 'rashel-hanukkah' ); ?></p></div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'לשוניות הניהול', 'rashel-hanukkah' ); ?>">
			<?php foreach ( rsh_dashboard_tabs() as $key => $label ) : ?>
				<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( rsh_dashboard_url( $key ) ); ?>"<?php echo $key === $tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'log' === $tab ) : ?>
			<?php rsh_dashboard_log(); ?>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rsh-admin__form">
				<input type="hidden" name="action" value="rsh_save">
				<input type="hidden" name="rsh_tab" value="<?php echo esc_attr( $tab ); ?>">
				<?php wp_nonce_field( 'rsh_save_' . $tab, 'rsh_nonce' ); ?>
				<?php
				if ( 'texts' === $tab ) {
					rsh_dashboard_texts();
				} elseif ( 'connections' === $tab ) {
					rsh_dashboard_connections();
				} else {
					rsh_dashboard_settings();
				}

				submit_button( __( 'שמירת שינויים', 'rashel-hanukkah' ) );
				?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * One row of a settings table.
 *
 * @param string $id    Field ID.
 * @param string $label Label text.
 * @param string $field Field markup, already escaped.
 * @param string $help  Short explanation shown under the field.
 * @return void
 */
function rsh_dashboard_row( $id, $label, $field, $help = '' ) {
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<?php echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by the callers below with every value escaped. ?>
			<?php if ( '' !== $help ) : ?>
				<p class="description" id="<?php echo esc_attr( $id ); ?>-help"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Markup of a single-line input.
 *
 * @param string               $name  Field name and ID suffix.
 * @param string               $value Current value.
 * @param array<string,string> $attrs Extra attributes (type, class, dir, min, max).
 * @return string
 */
function rsh_dashboard_input( $name, $value, array $attrs = array() ) {
	$attrs = array_merge(
		array(
			'type'  => 'text',
			'class' => 'regular-text',
		),
		$attrs
	);
	$html  = sprintf( '<input id="rsh-%1$s" name="rsh[%1$s]" value="%2$s" aria-describedby="rsh-%1$s-help"', esc_attr( $name ), esc_attr( $value ) );

	foreach ( $attrs as $attr => $attr_value ) {
		$html .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $attr_value ) );
	}

	return $html . '>';
}

/**
 * Markup of a checkbox.
 *
 * @param string $name    Field name and ID suffix.
 * @param bool   $checked Current state.
 * @param string $text    Text beside the box.
 * @return string
 */
function rsh_dashboard_checkbox( $name, $checked, $text ) {
	return sprintf(
		'<label><input type="checkbox" id="rsh-%1$s" name="rsh[%1$s]" value="1"%2$s> %3$s</label>',
		esc_attr( $name ),
		checked( $checked, true, false ),
		esc_html( $text )
	);
}

/**
 * The "settings" tab.
 *
 * @return void
 */
function rsh_dashboard_settings() {
	$pages = static function ( $name ) {
		return (string) wp_dropdown_pages(
			array(
				'name'              => 'rsh[' . esc_attr( $name ) . ']',
				'id'                => 'rsh-' . esc_attr( $name ),
				'selected'          => (int) rsh_setting( $name ),
				'show_option_none'  => esc_html__( '— לא נבחר —', 'rashel-hanukkah' ),
				'option_none_value' => '0',
				'echo'              => 0,
			)
		);
	};
	?>
	<h2><?php esc_html_e( 'המבצע', 'rashel-hanukkah' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php
		rsh_dashboard_row(
			'rsh-deadline',
			__( 'סיום המבצע', 'rashel-hanukkah' ),
			rsh_dashboard_input(
				'deadline',
				(string) rsh_setting( 'deadline' ),
				array(
					'type' => 'datetime-local',
					'step' => '1',
					'dir'  => 'ltr',
				)
			),
			__( 'התאריך והשעה שאליהם סופר השעון, לפי אזור הזמן של האתר. את הנוסח "עד יום ראשון 13.12" מעדכנים בלשונית הטקסטים.', 'rashel-hanukkah' )
		);
		rsh_dashboard_row(
			'rsh-discount',
			__( 'אחוז ההנחה', 'rashel-hanukkah' ),
			rsh_dashboard_input(
				'discount',
				(string) rsh_discount(),
				array(
					'type'  => 'number',
					'class' => 'small-text',
					'min'   => '1',
					'max'   => '99',
				)
			),
			__( 'משמש לחישוב המחיר המקורי (המחוק) של קורס שלא הוזן לו מחיר מקורי.', 'rashel-hanukkah' )
		);
		rsh_dashboard_row(
			'rsh-phone',
			__( 'טלפון', 'rashel-hanukkah' ),
			rsh_dashboard_input( 'phone', rsh_phone(), array( 'dir' => 'ltr' ) ),
			__( 'מופיע ליד הטופס ובתחתית העמוד, ומשמש גם לחיוג בלחיצה.', 'rashel-hanukkah' )
		);
		?>
	</table>

	<h2><?php esc_html_e( 'תצוגה', 'rashel-hanukkah' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php
		rsh_dashboard_row( 'rsh-sticky_bar', __( 'פס תחתון קבוע', 'rashel-hanukkah' ), rsh_dashboard_checkbox( 'sticky_bar', (bool) rsh_setting( 'sticky_bar' ), __( 'להציג את הפס עם השעון והכפתור אחרי גלילה', 'rashel-hanukkah' ) ) );
		rsh_dashboard_row( 'rsh-a11y_toolbar', __( 'סרגל נגישות', 'rashel-hanukkah' ), rsh_dashboard_checkbox( 'a11y_toolbar', (bool) rsh_setting( 'a11y_toolbar' ), __( 'להציג את סרגל הנגישות', 'rashel-hanukkah' ) ), __( 'ברירת המחדל באתרי Multi Digital. הקוד נגיש גם בלי הסרגל.', 'rashel-hanukkah' ) );
		rsh_dashboard_row( 'rsh-credit', __( 'קרדיט בפוטר', 'rashel-hanukkah' ), rsh_dashboard_checkbox( 'credit', (bool) rsh_setting( 'credit' ), __( 'להציג קרדיט ל-Multi Digital', 'rashel-hanukkah' ) ), __( 'מכבים רק אם סוכם כך עם הלקוח.', 'rashel-hanukkah' ) );
		?>
	</table>

	<h2><?php esc_html_e( 'עמודי חובה', 'rashel-hanukkah' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php
		rsh_dashboard_row( 'rsh-terms_page', __( 'תנאי שימוש', 'rashel-hanukkah' ), $pages( 'terms_page' ), __( 'מקושר מהפוטר בכל עמוד.', 'rashel-hanukkah' ) );
		rsh_dashboard_row( 'rsh-a11y_page', __( 'הצהרת נגישות', 'rashel-hanukkah' ), $pages( 'a11y_page' ), __( 'מקושרת מהפוטר ומסרגל הנגישות. עמוד מדיניות הפרטיות נבחר ב"הגדרות > פרטיות" של וורדפרס.', 'rashel-hanukkah' ) );
		?>
	</table>

	<h2><?php esc_html_e( 'כתוביות לסרטוני ההמלצות', 'rashel-hanukkah' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php
		foreach ( array( 'v01', 'v02' ) as $video ) {
			rsh_dashboard_row(
				'rsh-captions_' . $video,
				'v01' === $video ? __( 'סרטון ראשון', 'rashel-hanukkah' ) : __( 'סרטון שני', 'rashel-hanukkah' ),
				rsh_dashboard_input(
					'captions_' . $video,
					(string) rsh_setting( 'captions_' . $video ),
					array(
						'type'  => 'url',
						'class' => 'large-text',
						'dir'   => 'ltr',
					)
				),
				__( 'כתובת של קובץ כתוביות (‎.vtt) שהועלה לספריית המדיה של האתר הזה. נדרש לפי תקן הנגישות.', 'rashel-hanukkah' )
			);
		}
		?>
	</table>
	<?php
}

/**
 * The "texts" tab: every field of the copy schema.
 *
 * @return void
 */
function rsh_dashboard_texts() {
	echo '<p>' . esc_html__( 'כל הטקסטים של העמוד. שדה שנשאר ריק חוזר לנוסח המקורי של העיצוב.', 'rashel-hanukkah' ) . '</p>';

	foreach ( rsh_content_schema() as $group ) {
		echo '<h2>' . esc_html( $group['label'] ) . '</h2><table class="form-table" role="presentation">';

		foreach ( $group['fields'] as $key => $field ) {
			$value = rsh_text( $key );

			if ( 'text' === $field['type'] ) {
				$control = rsh_dashboard_input( $key, $value, array( 'class' => 'large-text' ) );
			} else {
				$control = sprintf(
					'<textarea id="rsh-%1$s" name="rsh[%1$s]" class="large-text" rows="%2$d" aria-describedby="rsh-%1$s-help">%3$s</textarea>',
					esc_attr( $key ),
					'textarea' === $field['type'] ? 3 : 5,
					esc_textarea( $value )
				);
			}

			rsh_dashboard_row( 'rsh-' . $key, $field['label'], $control, $field['help'] ?? '' );
		}

		echo '</table>';
	}
}

/**
 * Markup of a secret field: never pre-filled, with its state beside it.
 *
 * @param string $name Secret name.
 * @return string
 */
function rsh_dashboard_secret( $name ) {
	if ( rsh_secret_locked( $name ) ) {
		return '<p><strong>' . esc_html__( 'מוגדר בקובץ wp-config.php, ולכן נעול כאן.', 'rashel-hanukkah' ) . '</strong></p>';
	}

	$saved = rsh_secret_saved( $name );

	return sprintf(
		'<input type="password" id="rsh-%1$s" name="rsh[%1$s]" value="" class="regular-text" dir="ltr" autocomplete="new-password" aria-describedby="rsh-%1$s-help"> <span class="rsh-admin__state">%2$s</span>%3$s',
		esc_attr( $name ),
		esc_html( $saved ? __( 'מפתח שמור. כדי להחליף, להקליד מפתח חדש.', 'rashel-hanukkah' ) : __( 'לא הוגדר.', 'rashel-hanukkah' ) ),
		$saved ? sprintf( '<br><label><input type="checkbox" name="rsh[%1$s_clear]" value="1"> %2$s</label>', esc_attr( $name ), esc_html__( 'למחוק את המפתח השמור', 'rashel-hanukkah' ) ) : ''
	);
}

/**
 * The "connections" tab: lead delivery, retention and Turnstile.
 *
 * @return void
 */
function rsh_dashboard_connections() {
	?>
	<h2><?php esc_html_e( 'לידים', 'rashel-hanukkah' ); ?></h2>
	<p>
		<?php esc_html_e( 'כל פנייה נשמרת קודם כול במסך "לידים", גם אם המייל או החיבור החיצוני נכשלו.', 'rashel-hanukkah' ); ?>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=rsh_lead' ) ); ?>"><?php esc_html_e( 'למסך הלידים', 'rashel-hanukkah' ); ?></a>
	</p>
	<table class="form-table" role="presentation">
		<?php
		rsh_dashboard_row(
			'rsh-lead_to',
			__( 'מייל להתראות', 'rashel-hanukkah' ),
			rsh_dashboard_input(
				'lead_to',
				(string) rsh_setting( 'lead_to' ),
				array(
					'type' => 'email',
					'dir'  => 'ltr',
				)
			),
			__( 'לכאן נשלחת התראה על כל ליד חדש. ריק: מייל מנהל האתר. ההתראה כוללת שם וטלפון בלבד.', 'rashel-hanukkah' )
		);
		rsh_dashboard_row(
			'rsh-lead_retention',
			__( 'תקופת שמירה (חודשים)', 'rashel-hanukkah' ),
			rsh_dashboard_input(
				'lead_retention',
				(string) rsh_lead_retention_months(),
				array(
					'type'  => 'number',
					'class' => 'small-text',
					'min'   => '1',
					'max'   => '120',
				)
			),
			__( 'ליד ישן יותר נמחק אוטומטית, כנדרש בחוק הגנת הפרטיות.', 'rashel-hanukkah' )
		);
		rsh_dashboard_row(
			'rsh-webhook_url',
			__( 'כתובת CRM חיצוני (Webhook)', 'rashel-hanukkah' ),
			rsh_dashboard_input(
				'webhook_url',
				(string) rsh_setting( 'webhook_url' ),
				array(
					'type'  => 'url',
					'class' => 'large-text',
					'dir'   => 'ltr',
				)
			),
			__( 'לא חובה. כתובת https שאליה נשלח כל ליד כ-JSON, עם ניסיונות חוזרים במקרה של כשל.', 'rashel-hanukkah' )
		);
		rsh_dashboard_row( 'rsh-webhook_secret', __( 'מפתח חתימה ל-Webhook', 'rashel-hanukkah' ), rsh_dashboard_secret( 'webhook_secret' ), __( 'כשמוגדר, כל שליחה נחתמת ב-HMAC-SHA256 בכותרת X-Rashel-Signature, כדי שהצד המקבל יוכל לאמת אותה.', 'rashel-hanukkah' ) );
		?>
	</table>

	<h2><?php esc_html_e( 'Cloudflare Turnstile', 'rashel-hanukkah' ); ?></h2>
	<p><?php esc_html_e( 'הגנה מפני ספאם בטופס. פעילה רק כששני המפתחות מוגדרים. בלעדיהם הטופס מוגן בשדה מלכודת ובהגבלת קצב.', 'rashel-hanukkah' ); ?></p>
	<table class="form-table" role="presentation">
		<?php
		if ( defined( 'RSH_TURNSTILE_SITE_KEY' ) && '' !== trim( (string) RSH_TURNSTILE_SITE_KEY ) ) {
			rsh_dashboard_row( 'rsh-turnstile_site', __( 'Site key', 'rashel-hanukkah' ), '<p><strong>' . esc_html__( 'מוגדר בקובץ wp-config.php, ולכן נעול כאן.', 'rashel-hanukkah' ) . '</strong></p>' );
		} else {
			rsh_dashboard_row( 'rsh-turnstile_site', __( 'Site key', 'rashel-hanukkah' ), rsh_dashboard_input( 'turnstile_site', (string) rsh_setting( 'turnstile_site' ), array( 'dir' => 'ltr' ) ), __( 'המפתח הציבורי מלוח הבקרה של Cloudflare.', 'rashel-hanukkah' ) );
		}

		rsh_dashboard_row( 'rsh-turnstile_secret', __( 'Secret key', 'rashel-hanukkah' ), rsh_dashboard_secret( 'turnstile_secret' ), __( 'נשמר מוצפן, אינו מוצג שוב ואינו נשלח לדפדפן.', 'rashel-hanukkah' ) );
		?>
	</table>
	<?php
}

/**
 * The "log" tab.
 *
 * @return void
 */
function rsh_dashboard_log() {
	$log    = get_option( 'rsh_audit', array() );
	$labels = rsh_audit_labels();
	?>
	<p><?php esc_html_e( 'מי שינה מה ומתי: הגדרות, טקסטים ופעולות על לידים. היומן שומר את 1,000 הפעולות האחרונות, בלי ערכים ובלי פרטים אישיים.', 'rashel-hanukkah' ); ?></p>
	<table class="widefat striped">
		<caption class="screen-reader-text"><?php esc_html_e( 'יומן פעולות', 'rashel-hanukkah' ); ?></caption>
		<thead><tr>
			<th scope="col"><?php esc_html_e( 'מתי (UTC)', 'rashel-hanukkah' ); ?></th>
			<th scope="col"><?php esc_html_e( 'מי', 'rashel-hanukkah' ); ?></th>
			<th scope="col"><?php esc_html_e( 'פעולה', 'rashel-hanukkah' ); ?></th>
			<th scope="col"><?php esc_html_e( 'ליד', 'rashel-hanukkah' ); ?></th>
			<th scope="col"><?php esc_html_e( 'פרטים', 'rashel-hanukkah' ); ?></th>
		</tr></thead>
		<tbody>
			<?php foreach ( array_slice( is_array( $log ) ? $log : array(), 0, 300 ) as $entry ) : ?>
				<?php $user = get_userdata( (int) ( $entry['user'] ?? 0 ) ); ?>
				<tr>
					<td dir="ltr"><?php echo esc_html( (string) ( $entry['at'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( $user ? $user->display_name : __( 'מערכת', 'rashel-hanukkah' ) ); ?></td>
					<td><?php echo esc_html( $labels[ $entry['action'] ?? '' ] ?? (string) ( $entry['action'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( empty( $entry['target'] ) ? '—' : '#' . (int) $entry['target'] ); ?></td>
					<td><?php echo esc_html( (string) ( $entry['details'] ?? '' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'אין עדיין פעולות ביומן.', 'rashel-hanukkah' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
}

/*
 * Saving
 */

/**
 * Validate the "settings" tab. Returns the cleaned values and whether any
 * submitted value was refused.
 *
 * @param array<string, mixed> $input Submitted values, unslashed.
 * @return array{values:array<string, string|int|bool>, refused:bool}
 */
function rsh_validate_settings( array $input ) {
	$text    = static fn( $key ) => isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? trim( sanitize_text_field( $input[ $key ] ) ) : '';
	$values  = array();
	$refused = false;

	$phone = $text( 'phone' );
	if ( rsh_phone_valid( $phone ) && strlen( $phone ) <= 20 ) {
		$values['phone'] = $phone;
	} else {
		$refused = true;
	}

	$deadline = $text( 'deadline' );
	if ( 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $deadline ) && false !== date_create_immutable( $deadline ) ) {
		$values['deadline'] = $deadline;
	} else {
		$refused = true;
	}

	$discount = absint( $text( 'discount' ) );
	if ( $discount >= 1 && $discount <= 99 ) {
		$values['discount'] = $discount;
	} else {
		$refused = true;
	}

	foreach ( array( 'sticky_bar', 'a11y_toolbar', 'credit' ) as $flag ) {
		$values[ $flag ] = ! empty( $input[ $flag ] );
	}

	foreach ( array( 'terms_page', 'a11y_page' ) as $page ) {
		$page_id         = absint( $text( $page ) );
		$values[ $page ] = $page_id && 'page' === get_post_type( $page_id ) ? $page_id : 0;
	}

	foreach ( array( 'captions_v01', 'captions_v02' ) as $captions ) {
		$url  = esc_url_raw( $text( $captions ), array( 'http', 'https' ) );
		$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$same = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		if ( '' === $text( $captions ) ) {
			$values[ $captions ] = '';
		} elseif ( '' !== $url && $same && str_ends_with( $path, '.vtt' ) ) {
			$values[ $captions ] = $url;
		} else {
			$refused = true;
		}
	}

	return array(
		'values'  => $values,
		'refused' => $refused,
	);
}

/**
 * Validate the plain fields of the "connections" tab.
 *
 * @param array<string, mixed> $input Submitted values, unslashed.
 * @return array{values:array<string, string|int|bool>, refused:bool}
 */
function rsh_validate_connections( array $input ) {
	$text    = static fn( $key ) => isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? trim( sanitize_text_field( $input[ $key ] ) ) : '';
	$values  = array();
	$refused = false;

	$email = $text( 'lead_to' );
	if ( '' === $email || is_email( $email ) ) {
		$values['lead_to'] = sanitize_email( $email );
	} else {
		$refused = true;
	}

	$months = absint( $text( 'lead_retention' ) );
	if ( $months >= 1 && $months <= 120 ) {
		$values['lead_retention'] = $months;
	} else {
		$refused = true;
	}

	$webhook = $text( 'webhook_url' );
	$clean   = esc_url_raw( $webhook, array( 'https' ) );
	if ( '' === $webhook || ( '' !== $clean && wp_http_validate_url( $clean ) ) ) {
		$values['webhook_url'] = '' === $webhook ? '' : $clean;
	} else {
		$refused = true;
	}

	if ( array_key_exists( 'turnstile_site', $input ) ) {
		$site = $text( 'turnstile_site' );

		if ( '' === $site || 1 === preg_match( '/^[A-Za-z0-9_\-]{10,100}$/', $site ) ) {
			$values['turnstile_site'] = $site;
		} else {
			$refused = true;
		}
	}

	return array(
		'values'  => $values,
		'refused' => $refused,
	);
}

/**
 * Clean the copy fields. A value equal to the default is not stored, so a
 * later change to the default still reaches the page.
 *
 * @param array<string, mixed> $input Submitted values, unslashed.
 * @return array<string, string>
 */
function rsh_validate_texts( array $input ) {
	$values = array();

	foreach ( rsh_content_fields() as $key => $field ) {
		if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) {
			continue;
		}

		$value = 'text' === $field['type']
			? mb_substr( sanitize_text_field( $input[ $key ] ), 0, 300 )
			: mb_substr( sanitize_textarea_field( $input[ $key ] ), 0, 2000 );
		$value = trim( $value );

		if ( '' !== $value && $value !== $field['default'] ) {
			$values[ $key ] = $value;
		}
	}

	return $values;
}

/**
 * Names of the keys whose value differs between two arrays.
 *
 * @param array<string, mixed> $before Old values.
 * @param array<string, mixed> $after  New values.
 * @return string[]
 */
function rsh_changed_keys( array $before, array $after ) {
	$changed = array();

	foreach ( array_unique( array_merge( array_keys( $before ), array_keys( $after ) ) ) as $key ) {
		if ( ( $before[ $key ] ?? null ) !== ( $after[ $key ] ?? null ) ) {
			$changed[] = (string) $key;
		}
	}

	return $changed;
}

/**
 * Handle a dashboard save: capability, nonce, validation, audit, redirect.
 *
 * @return void
 */
function rsh_dashboard_save() {
	if ( ! current_user_can( RSH_DASHBOARD_CAP ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'rashel-hanukkah' ), '', array( 'response' => 403 ) );
	}

	$tab = isset( $_POST['rsh_tab'] ) ? sanitize_key( wp_unslash( $_POST['rsh_tab'] ) ) : '';

	if ( ! in_array( $tab, array( 'settings', 'texts', 'connections' ), true ) ) {
		wp_die( esc_html__( 'בקשה לא תקינה.', 'rashel-hanukkah' ), '', array( 'response' => 400 ) );
	}

	check_admin_referer( 'rsh_save_' . $tab, 'rsh_nonce' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is validated by its own rule in the rsh_validate_*() functions below.
	$input   = isset( $_POST['rsh'] ) && is_array( $_POST['rsh'] ) ? wp_unslash( $_POST['rsh'] ) : array();
	$refused = false;

	if ( 'texts' === $tab ) {
		$before = get_option( 'rsh_content', array() );
		$before = is_array( $before ) ? $before : array();
		$after  = rsh_validate_texts( $input );
		$keys   = rsh_changed_keys( $before, $after );

		if ( $keys ) {
			update_option( 'rsh_content', $after );
			rsh_audit( 'content', 0, implode( ', ', $keys ) );
		}
	} else {
		$result  = 'settings' === $tab ? rsh_validate_settings( $input ) : rsh_validate_connections( $input );
		$refused = $result['refused'];
		$before  = get_option( 'rsh_settings', array() );
		$before  = is_array( $before ) ? $before : array();
		$after   = array_merge( $before, $result['values'] );
		$keys    = rsh_changed_keys( $before, $after );

		if ( $keys ) {
			update_option( 'rsh_settings', $after );
			rsh_audit( 'settings', 0, implode( ', ', $keys ) );
		}

		if ( 'connections' === $tab ) {
			rsh_dashboard_save_secrets( $input );
		}
	}

	wp_safe_redirect( add_query_arg( 'saved', $refused ? 'partial' : '1', rsh_dashboard_url( $tab ) ) );
	exit;
}
add_action( 'admin_post_rsh_save', 'rsh_dashboard_save' );

/**
 * Store or clear the secrets sent with the "connections" tab. An empty field
 * keeps the saved secret; only the field name is logged, never the value.
 *
 * @param array<string, mixed> $input Submitted values, unslashed.
 * @return void
 */
function rsh_dashboard_save_secrets( array $input ) {
	foreach ( array_keys( rsh_secret_constants() ) as $name ) {
		if ( rsh_secret_locked( $name ) ) {
			continue;
		}

		$value = isset( $input[ $name ] ) && is_string( $input[ $name ] ) ? trim( $input[ $name ] ) : '';

		if ( '' !== $value && strlen( $value ) <= 500 && 1 === preg_match( '/^[\x21-\x7E]+$/', $value ) ) {
			if ( rsh_store_secret( $name, $value ) ) {
				rsh_audit( 'secret', 0, $name . ': updated' );
			}
		} elseif ( ! empty( $input[ $name . '_clear' ] ) && rsh_secret_saved( $name ) ) {
			rsh_store_secret( $name, '' );
			rsh_audit( 'secret', 0, $name . ': cleared' );
		}
	}
}
