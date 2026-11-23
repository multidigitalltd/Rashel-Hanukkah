<?php
/**
 * Site copy.
 *
 * Every sentence on the landing page is declared here once, with the wording of
 * the approved design as its default. The dashboard ("ראשל חנוכה" > "טקסטים")
 * stores overrides in a single option; the templates only ever call rsh_text().
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The copy schema: groups of fields, each with a label, a type and a default.
 *
 * Types: "text" (one line), "textarea" (free text), "lines" (one item per line)
 * and "marked" (lines where *asterisks* wrap the highlighted words).
 *
 * @return array<string, array{label:string, fields:array<string, array{label:string, type:string, default:string, help?:string}>}>
 */
function rsh_content_schema() {
	$marked_help = __( 'כל שורה בתיבה היא שורה בעמוד. מילים בין *כוכביות* מקבלות את סימון המרקר הצהוב.', 'rashel-hanukkah' );
	$lines_help  = __( 'פריט אחד בכל שורה.', 'rashel-hanukkah' );

	return array(
		'general'      => array(
			'label'  => __( 'כללי', 'rashel-hanukkah' ),
			'fields' => array(
				'cta'           => array(
					'label'   => __( 'טקסט כפתור ההנעה לפעולה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'אני רוצה לנצל את ההזדמנות!',
					'help'    => __( 'מופיע בכל כפתורי ההרשמה בעמוד.', 'rashel-hanukkah' ),
				),
				'deadline_line' => array(
					'label'   => __( 'שורת המועד האחרון', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'כל הקורסים במחיר של דמי חנוכה — עד יום ראשון 13.12',
					'help'    => __( 'מופיעה מתחת לקורסים וברצועה הצהובה. לעדכן יחד עם תאריך הסיום שבלשונית ההגדרות.', 'rashel-hanukkah' ),
				),
				'benefits_ttl'  => array(
					'label'   => __( 'כותרת רצועת ההטבות', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'לכבוד חנוכה',
				),
				'benefits'      => array(
					'label'   => __( 'רצועת ההטבות', 'rashel-hanukkah' ),
					'type'    => 'lines',
					'default' => "50% הנחה\nעד 12 תשלומים\nאת בוחרת את התחום שהכי מעניין אותך\nשנתיים גישה לצפייה חוזרת להטמעת החומר\nדיגיטלי — מהבית, בזמן שנוח לך",
					'help'    => $lines_help,
				),
				'ticker'        => array(
					'label'   => __( 'הרצועה הצהובה הנעה', 'rashel-hanukkah' ),
					'type'    => 'lines',
					'default' => "50% הנחה\nרק במהלך חג החנוכה!\nעד 12 תשלומים\nשנתיים גישה לצפייה חוזרת\nדיגיטלי — מהבית, בזמן שנוח לך",
					'help'    => $lines_help,
				),
				'cd_days'       => array(
					'label'   => __( 'שעון הספירה: ימים', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'ימים',
				),
				'cd_hours'      => array(
					'label'   => __( 'שעון הספירה: שעות', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'שעות',
				),
				'cd_minutes'    => array(
					'label'   => __( 'שעון הספירה: דקות', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'דקות',
				),
				'cd_seconds'    => array(
					'label'   => __( 'שעון הספירה: שניות', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'שניות',
				),
				'cd_label'      => array(
					'label'   => __( 'שעון הספירה: תיאור לקוראי מסך', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הזמן שנותר עד סוף המבצע',
				),
			),
		),
		'hook'         => array(
			'label'  => __( 'מסך הפתיחה', 'rashel-hanukkah' ),
			'fields' => array(
				'hook_q1'   => array(
					'label'   => __( 'שאלה ראשונה', 'rashel-hanukkah' ),
					'type'    => 'marked',
					'default' => "אוהבת את עולם *הקולינריה והעיצוב*\nורוצה להפוך *למקצוענית אמיתית?*",
					'help'    => $marked_help,
				),
				'hook_q2'   => array(
					'label'   => __( 'שאלה שנייה', 'rashel-hanukkah' ),
					'type'    => 'marked',
					'default' => "רוצה ללמוד *תחום אחד לעומק,*\nבצורה יסודית ומקצועית,\nבלי לבזבז זמן וכסף על מה שלא רלוונטי לך?",
					'help'    => $marked_help,
				),
				'hook_q3'   => array(
					'label'   => __( 'שאלה שלישית', 'rashel-hanukkah' ),
					'type'    => 'marked',
					'default' => "תמיד חלמת *ללמוד אצל ראשל*\nוחיכית *להזדמנות המתאימה?*",
					'help'    => $marked_help,
				),
				'hook_next' => array(
					'label'   => __( 'שם נגיש לכפתור החץ', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'המשך להצעה',
				),
			),
		),
		'announce'     => array(
			'label'  => __( 'ההכרזה', 'rashel-hanukkah' ),
			'fields' => array(
				'h1_line1'    => array(
					'label'   => __( 'כותרת ראשית: שורה ראשונה (מוארת)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'בואי להאיר',
				),
				'h1_line2'    => array(
					'label'   => __( 'כותרת ראשית: שורה שנייה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'את הכישרון שבך!',
				),
				'lead'        => array(
					'label'   => __( 'פסקת פתיחה', 'rashel-hanukkah' ),
					'type'    => 'textarea',
					'default' => 'נבחרת הקורסים הדיגיטליים בעולם העיצוב והקולינריה – נושאים ממוקדים שיוציאו אותך מאסטרית בכל תחום שתבחרי.',
				),
				'sub'         => array(
					'label'   => __( 'פסקה שנייה', 'rashel-hanukkah' ),
					'type'    => 'textarea',
					'default' => 'בחנוכה הזה ראשל מזמינה אותך להגשים חלום עם נבחרת קורסים דיגיטליים לבחירה אישית ב50% הנחה',
				),
				'badge_big'   => array(
					'label'   => __( 'עיגול ההנחה: המספר', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => '50%',
				),
				'badge_small' => array(
					'label'   => __( 'עיגול ההנחה: המילה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הנחה',
				),
				'arch_alt'    => array(
					'label'   => __( 'תיאור התמונה הגדולה (לקוראי מסך)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'בר פירות מעוצב',
				),
				'arch2_alt'   => array(
					'label'   => __( 'תיאור התמונה הקטנה (לקוראי מסך)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'קינוחי פטיסרי מעוצבים',
				),
			),
		),
		'courses'      => array(
			'label'  => __( 'תפריט הקורסים', 'rashel-hanukkah' ),
			'fields' => array(
				'courses_ttl'  => array(
					'label'   => __( 'כותרת', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'בואי לעשות מהכשרון שלך אירוע!',
				),
				'courses_txt'  => array(
					'label'   => __( 'פסקה', 'rashel-hanukkah' ),
					'type'    => 'textarea',
					'default' => 'כל קורס עומד בפני עצמו — כולל מתכונים, טכניקות וכל הידע, פרקטי ומעשי שיאפשר לך להתחיל לעבוד איתו כבר מחר.',
				),
				'courses_note' => array(
					'label'   => __( 'הערת מחירים', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'כל המחירים אחרי הנחת דמי החנוכה (50% הנחה)',
				),
				'tab_culinary' => array(
					'label'   => __( 'לשונית קולינריה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'קורסי קולינריה',
				),
				'tab_design'   => array(
					'label'   => __( 'לשונית עיצוב', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'קורסי עיצוב',
				),
				'price_now'    => array(
					'label'   => __( 'קידומת מחיר לקוראי מסך: מחיר מבצע', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'מחיר מבצע:',
				),
				'price_was'    => array(
					'label'   => __( 'קידומת מחיר לקוראי מסך: מחיר מקורי', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'מחיר מקורי:',
				),
			),
		),
		'about'        => array(
			'label'  => __( 'ראשל', 'rashel-hanukkah' ),
			'fields' => array(
				'about_ttl'    => array(
					'label'   => __( 'כותרת האזור (לקוראי מסך בלבד)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'ראשל, במילים שלה',
				),
				'about_quote'  => array(
					'label'   => __( 'פסקה ראשית', 'rashel-hanukkah' ),
					'type'    => 'textarea',
					'default' => 'התחלתי בדיוק כמוך — עם אהבה גדולה ליופי ותשוקה עמוקה לעצב וליצור בעצמי. אחרי עשור של הובלת טרנדים בעיצוב ברים ממותגים ומאות אירועי קונספט יוקרתיים, אני יודעת שיש לי שיטה. ואת השיטה הזאת אני רוצה להעביר גם לך.',
				),
				'about_txt'    => array(
					'label'   => __( 'פסקה שנייה', 'rashel-hanukkah' ),
					'type'    => 'textarea',
					'default' => 'בואי לגלות מחדש את הכיף שבעולם האירועים, את הסודות הגדולים שמאחורי כל פרט קטן — ולהגיע לתוצאות שלא חלמת עליהן.',
				),
				'signature'    => array(
					'label'   => __( 'חתימה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'ראשל',
				),
				'portrait_alt' => array(
					'label'   => __( 'תיאור תמונת הדיוקן (לקוראי מסך)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'ראשל',
				),
			),
		),
		'testimonials' => array(
			'label'  => __( 'המלצות', 'rashel-hanukkah' ),
			'fields' => array(
				'quote'      => array(
					'label'   => __( 'כותרת הציטוט', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הזדמנות חד פעמית, לקפוץ הרבה שלבים קדימה',
				),
				'video1_ttl' => array(
					'label'   => __( 'שם הסרטון הראשון (לקוראי מסך)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'סרטון המלצה ראשון',
				),
				'video2_ttl' => array(
					'label'   => __( 'שם הסרטון השני (לקוראי מסך)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'סרטון המלצה שני',
				),
				'wall_ttl'   => array(
					'label'   => __( 'כותרת קיר ההמלצות (לקוראי מסך בלבד)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הודעות שקיבלנו מבוגרות ומבוגרים',
				),
				'wall_alt'   => array(
					'label'   => __( 'תיאור צילומי ההמלצות (לקוראי מסך)', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'צילום מסך של הודעת המלצה',
				),
			),
		),
		'consult'      => array(
			'label'  => __( 'ייעוץ וטופס', 'rashel-hanukkah' ),
			'fields' => array(
				'consult_ttl'   => array(
					'label'   => __( 'כותרת: החלק הרגיל', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'מתלבטת אם זה',
				),
				'consult_mark'  => array(
					'label'   => __( 'כותרת: החלק המסומן בצהוב', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'מתאים לך?',
				),
				'ticket_txt'    => array(
					'label'   => __( 'שובר המתנה: טקסט', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'שיחת ייעוץ והתאמה עם יועצת לימודים, בשווי 700 ₪',
				),
				'ticket_badge'  => array(
					'label'   => __( 'שובר המתנה: התווית', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'במתנה',
				),
				'form_ttl'      => array(
					'label'   => __( 'כותרת הטופס', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'או מלאי פרטים כאן ונחזור אלייך',
				),
				'form_name'     => array(
					'label'   => __( 'שדה: שם', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'שם',
				),
				'form_phone'    => array(
					'label'   => __( 'שדה: טלפון', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'טלפון',
				),
				'form_email'    => array(
					'label'   => __( 'שדה: אימייל', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'אימייל',
				),
				'form_required' => array(
					'label'   => __( 'סימון שדה חובה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'חובה',
					'help'    => __( 'נדרש לפי תקן הנגישות: שדה חובה מסומן במילה ולא רק בצבע.', 'rashel-hanukkah' ),
				),
				'form_submit'   => array(
					'label'   => __( 'כפתור השליחה', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'חיזרו אלי',
				),
				'form_privacy'  => array(
					'label'   => __( 'הערת פרטיות מתחת לטופס', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הפרטים ישמשו רק כדי לחזור אלייך.',
					'help'    => __( 'אחריה מופיע קישור למדיניות הפרטיות.', 'rashel-hanukkah' ),
				),
				'form_ok_ttl'   => array(
					'label'   => __( 'הודעת הצלחה: כותרת', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'קיבלנו!',
				),
				'form_ok_txt'   => array(
					'label'   => __( 'הודעת הצלחה: טקסט', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'נחזור אלייך בהקדם.',
				),
				'phone_lead'    => array(
					'label'   => __( 'לפני מספר הטלפון', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'או פשוט בטלפון:',
				),
			),
		),
		'legal'        => array(
			'label'  => __( 'פרטיות ופוטר', 'rashel-hanukkah' ),
			'fields' => array(
				'privacy_ttl'      => array(
					'label'   => __( 'הודעת הפרטיות: כותרת', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'פרטיות ועוגיות',
				),
				'privacy_txt'      => array(
					'label'   => __( 'הודעת הפרטיות: טקסט', 'rashel-hanukkah' ),
					'type'    => 'textarea',
					'default' => 'האתר שומר בדפדפן שלך רק את מה שנדרש לפעולתו: העדפות הנגישות והאישור על ההודעה הזאת. פרטים שתשאירי בטופס ישמשו רק כדי לחזור אלייך.',
				),
				'privacy_link'     => array(
					'label'   => __( 'הודעת הפרטיות: טקסט הקישור', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'למדיניות הפרטיות המלאה',
				),
				'privacy_ok'       => array(
					'label'   => __( 'הודעת הפרטיות: כפתור', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הבנתי',
				),
				'privacy_settings' => array(
					'label'   => __( 'קישור "הגדרות פרטיות" בפוטר', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'הגדרות פרטיות',
				),
				'credit'           => array(
					'label'   => __( 'קרדיט בפוטר', 'rashel-hanukkah' ),
					'type'    => 'text',
					'default' => 'עיצוב ופיתוח: Multi Digital',
				),
			),
		),
	);
}

/**
 * Every copy field, flattened: key => field definition.
 *
 * @return array<string, array{label:string, type:string, default:string, help?:string}>
 */
function rsh_content_fields() {
	static $fields = null;

	if ( null === $fields ) {
		$fields = array();

		foreach ( rsh_content_schema() as $group ) {
			$fields += $group['fields'];
		}
	}

	return $fields;
}

/**
 * One piece of copy: the saved override, or the design's wording.
 *
 * @param string $key Field key.
 * @return string
 */
function rsh_text( $key ) {
	static $saved = null;

	if ( null === $saved ) {
		$saved = get_option( 'rsh_content', array() );
		$saved = is_array( $saved ) ? $saved : array();
	}

	if ( isset( $saved[ $key ] ) && is_string( $saved[ $key ] ) && '' !== trim( $saved[ $key ] ) ) {
		return $saved[ $key ];
	}

	return rsh_content_fields()[ $key ]['default'] ?? '';
}

/**
 * Print one piece of copy, escaped for an HTML body.
 *
 * @param string $key Field key.
 * @return void
 */
function rsh_the_text( $key ) {
	echo esc_html( rsh_text( $key ) );
}

/**
 * A "lines" field as a list of non-empty items.
 *
 * @param string $key Field key.
 * @return string[]
 */
function rsh_text_lines( $key ) {
	$lines = preg_split( '/\R/u', rsh_text( $key ) );

	return array_values( array_filter( array_map( 'trim', false === $lines ? array() : $lines ), static fn( string $line ): bool => '' !== $line ) );
}
