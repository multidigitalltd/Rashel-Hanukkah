<?php
/**
 * One-time installer.
 *
 * Runs when the theme is activated and creates what the site needs to be
 * complete on day one: the eleven courses of the approved design, and the
 * mandatory pages (privacy policy, terms of use, accessibility statement:
 * standard 15.6). It never overwrites: a page or a course that already exists
 * is left alone, and the installer never runs twice.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run the installer once.
 *
 * @return void
 */
function rsh_install() {
	if ( get_option( 'rsh_installed' ) ) {
		return;
	}

	rsh_install_courses();
	rsh_install_pages();

	update_option( 'rsh_installed', RSH_VERSION, false );
	rsh_flush_courses();
}
add_action( 'after_switch_theme', 'rsh_install' );

/**
 * Create the courses of the design, in order.
 *
 * @return void
 */
function rsh_install_courses() {
	$existing = get_posts(
		array(
			'post_type'      => 'rsh_course',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	if ( $existing ) {
		return;
	}

	foreach ( rsh_default_courses() as $order => $course ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'rsh_course',
				'post_status' => 'publish',
				'post_title'  => $course['name'],
				'menu_order'  => ( $order + 1 ) * 10,
			)
		);

		if ( ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, 'rsh_cat', $course['cat'] );
		update_post_meta( $post_id, 'rsh_tag', $course['tag'] );
		update_post_meta( $post_id, 'rsh_hook', $course['hook'] );
		update_post_meta( $post_id, 'rsh_price', $course['price'] );
		update_post_meta( $post_id, 'rsh_original', 0 );
		update_post_meta( $post_id, 'rsh_pay', rsh_clean_pay_url( $course['pay'] ) );
		update_post_meta( $post_id, 'rsh_image', $course['image'] );
	}
}

/**
 * Create the mandatory pages that do not exist yet and remember them.
 *
 * @return void
 */
function rsh_install_pages() {
	$settings = get_option( 'rsh_settings', array() );
	$settings = is_array( $settings ) ? $settings : array();

	$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );

	if ( ! $privacy_id || 'publish' !== get_post_status( $privacy_id ) ) {
		$privacy_id = rsh_install_page( 'privacy-policy', __( 'מדיניות פרטיות', 'rashel-hanukkah' ), rsh_install_privacy_text() );

		if ( $privacy_id ) {
			update_option( 'wp_page_for_privacy_policy', $privacy_id );
		}
	}

	if ( empty( $settings['terms_page'] ) ) {
		$settings['terms_page'] = rsh_install_page( 'terms', __( 'תנאי שימוש', 'rashel-hanukkah' ), rsh_install_terms_text() );
	}

	if ( empty( $settings['a11y_page'] ) ) {
		$settings['a11y_page'] = rsh_install_page( 'accessibility', __( 'הצהרת נגישות', 'rashel-hanukkah' ), rsh_install_a11y_text() );
	}

	update_option( 'rsh_settings', $settings );
}

/**
 * Create one page, unless a published page with that slug is already there.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Block markup.
 * @return int Page ID, 0 on failure.
 */
function rsh_install_page( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug );

	// A published page is the client's own: keep it. A draft (such as the empty
	// privacy policy WordPress creates on install) is left alone and not linked.
	if ( $existing instanceof WP_Post && 'publish' === $existing->post_status ) {
		return (int) $existing->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		)
	);

	return (int) $page_id;
}

/**
 * Turn headings and paragraphs into block markup.
 *
 * @param array<int, array{0:string, 1:string}> $parts Pairs of type ("h" or "p") and text.
 * @return string
 */
function rsh_install_blocks( array $parts ) {
	$html = '';

	foreach ( $parts as $part ) {
		$html .= 'h' === $part[0]
			? "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $part[1] ) . "</h2>\n<!-- /wp:heading -->\n\n"
			: "<!-- wp:paragraph -->\n<p>" . esc_html( $part[1] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	}

	return $html;
}

/**
 * First draft of the privacy policy: what this site actually does with data.
 * The client's legal adviser approves the final wording.
 *
 * @return string
 */
function rsh_install_privacy_text() {
	return rsh_install_blocks(
		array(
			array( 'p', __( 'מדיניות זו מסבירה איזה מידע נאסף באתר, לשם מה, וכמה זמן הוא נשמר. הנוסח הוא טיוטה ראשונה, ויש לאשר אותו מול יועץ משפטי לפני עלייה לאוויר.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'איזה מידע נאסף', 'rashel-hanukkah' ) ),
			array( 'p', __( 'כשאת משאירה פרטים בטופס הייעוץ נשמרים השם, מספר הטלפון וכתובת האימייל (אם מילאת אותה), העמוד שממנו נשלח הטופס ומקור ההגעה לאתר. לא נאסף מידע נוסף ואין באתר הרשמה או חשבון משתמש.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'לשם מה', 'rashel-hanukkah' ) ),
			array( 'p', __( 'הפרטים משמשים רק כדי לחזור אלייך בעניין שיחת הייעוץ והקורסים. הם אינם נמכרים ואינם מועברים לצד שלישי לצורכי שיווק.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'תשלום', 'rashel-hanukkah' ) ),
			array( 'p', __( 'הרכישה מתבצעת בעמוד תשלום מאובטח של חברת הסליקה Cardcom. פרטי כרטיס האשראי מוזנים שם בלבד, ואינם עוברים דרך האתר הזה ואינם נשמרים בו.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'עוגיות ואחסון בדפדפן', 'rashel-hanukkah' ) ),
			array( 'p', __( 'האתר שומר בדפדפן שלך רק את מה שנדרש לפעולתו: העדפות סרגל הנגישות והאישור על הודעת הפרטיות. כשמופעלת הגנת Cloudflare Turnstile על הטופס, Cloudflare עשויה להשתמש בעוגיות הנדרשות לאימות שהפנייה נשלחה מדפדפן.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'כמה זמן המידע נשמר', 'rashel-hanukkah' ) ),
			array( 'p', __( 'פניות נמחקות אוטומטית בתום תקופת השמירה שהוגדרה באתר (כברירת מחדל 24 חודשים).', 'rashel-hanukkah' ) ),
			array( 'h', __( 'הזכויות שלך', 'rashel-hanukkah' ) ),
			array( 'p', __( 'אפשר לבקש לעיין במידע שנשמר עלייך, לתקן אותו או למחוק אותו, בפנייה אלינו בטלפון שמופיע בתחתית האתר.', 'rashel-hanukkah' ) ),
		)
	);
}

/**
 * First draft of the terms of use.
 *
 * @return string
 */
function rsh_install_terms_text() {
	return rsh_install_blocks(
		array(
			array( 'p', __( 'הנוסח הוא טיוטה ראשונה, ויש להשלים ולאשר אותו מול יועץ משפטי לפני עלייה לאוויר.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'כללי', 'rashel-hanukkah' ) ),
			array( 'p', __( 'האתר מציג קורסים דיגיטליים של RASHEL Digital School ומאפשר לעבור לעמוד תשלום מאובטח או להשאיר פרטים לשיחת ייעוץ. השימוש באתר מהווה הסכמה לתנאים אלה.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'מחירים ומבצעים', 'rashel-hanukkah' ) ),
			array( 'p', __( 'המחירים באתר כוללים את הנחת המבצע ותקפים עד המועד שמצוין בעמוד. המחיר הקובע הוא המחיר שמוצג בעמוד התשלום בעת הרכישה.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'ביטול עסקה', 'rashel-hanukkah' ) ),
			array( 'p', __( 'ביטול עסקה ייעשה בהתאם לחוק הגנת הצרכן ולתקנותיו. יש להשלים כאן את מדיניות הביטולים של בית הספר.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'קניין רוחני', 'rashel-hanukkah' ) ),
			array( 'p', __( 'כל התכנים באתר ובקורסים, לרבות טקסטים, תמונות וסרטונים, שייכים ל-RASHEL ואין להעתיק או להפיץ אותם בלי אישור בכתב.', 'rashel-hanukkah' ) ),
		)
	);
}

/**
 * First draft of the accessibility statement (standard 8.9.1).
 *
 * @return string
 */
function rsh_install_a11y_text() {
	return rsh_install_blocks(
		array(
			array( 'p', __( 'אנחנו רואים חשיבות רבה במתן שירות שוויוני לכל הגולשות והגולשים, ופועלים כדי שהאתר יהיה נגיש לאנשים עם מוגבלות.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'רמת הנגישות', 'rashel-hanukkah' ) ),
			array( 'p', __( 'האתר נבנה לפי התקן הישראלי ת"י 5568 ולפי הנחיות WCAG 2.2 ברמה AA.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'ההתאמות שבוצעו', 'rashel-hanukkah' ) ),
			array( 'p', __( 'מבנה סמנטי וכותרות היררכיות, ניווט מלא במקלדת עם סימון פוקוס ברור, קישור דילוג לתוכן הראשי, טקסט חלופי לתמונות, ניגודיות צבעים תקינה, טופס עם תוויות והודעות שגיאה מוכרזות, כיבוד ההעדפה להפחתת תנועה, וסרגל נגישות שמאפשר הגדלת טקסט, ניגודיות גבוהה, גווני אפור, הדגשת קישורים וכותרות, גופן קריא ועצירת אנימציות.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'מגבלות ידועות', 'rashel-hanukkah' ) ),
			array( 'p', __( 'לסרטוני ההמלצות טרם נוספו כתוביות. צילומי המסך של ההמלצות מוצגים כתמונות עם תיאור כללי. אנחנו פועלים להשלמת הפערים האלה.', 'rashel-hanukkah' ) ),
			array( 'h', __( 'פנייה בנושא נגישות', 'rashel-hanukkah' ) ),
			array( 'p', __( 'נתקלת בקושי? נשמח לדעת ולתקן. רכז/ת הנגישות: [להשלים שם]. טלפון: [להשלים]. אימייל: [להשלים].', 'rashel-hanukkah' ) ),
			array( 'p', __( 'ההצהרה עודכנה לאחרונה: [להשלים תאריך].', 'rashel-hanukkah' ) ),
		)
	);
}
