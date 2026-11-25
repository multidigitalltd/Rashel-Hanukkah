<?php
/**
 * Courses: the eleven cards of the menu.
 *
 * A course is a post of its own type, so the office edits name, price and
 * payment link in the regular WordPress screens. The whole list is read with
 * one query and kept in a transient until a course changes. Until the installer
 * has run, the design's own list is shown, so the page is never empty.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Transient holding the rendered course list. */
const RSH_COURSES_CACHE = 'rsh_courses';

/** Payment links may only point at these hosts (standard 4.1: allowlist). */
const RSH_PAY_HOSTS = array( 'secure.cardcom.solutions' );

/**
 * Course categories: key => copy key of its tab label.
 *
 * @return array<string, string>
 */
function rsh_course_categories() {
	return array(
		'culinary' => 'tab_culinary',
		'design'   => 'tab_design',
	);
}

/**
 * The course list of the approved design, with each course's syllabus. Seeds
 * the installer and stands in until it has run.
 *
 * @return array<int, array{name:string, cat:string, tag:string, hook:string, price:int, pay:string, image:string, syllabus:string}>
 */
function rsh_default_courses() {
	static $courses = null;

	if ( null === $courses ) {
		$courses = require __DIR__ . '/course-data.php';
	}

	return $courses;
}

/**
 * Slugs of the course photos shipped with the theme (assets/img/courses).
 *
 * @return string[]
 */
function rsh_bundled_course_images() {
	return array_column( rsh_default_courses(), 'image' );
}

/**
 * The course post type. Not public: a course has no page of its own, it is a
 * card on the landing page.
 *
 * @return void
 */
function rsh_register_course_cpt() {
	register_post_type(
		'rsh_course',
		array(
			'labels'              => array(
				'name'               => __( 'קורסים', 'rashel-hanukkah' ),
				'singular_name'      => __( 'קורס', 'rashel-hanukkah' ),
				'menu_name'          => __( 'קורסים', 'rashel-hanukkah' ),
				'add_new'            => __( 'קורס חדש', 'rashel-hanukkah' ),
				'add_new_item'       => __( 'קורס חדש', 'rashel-hanukkah' ),
				'edit_item'          => __( 'עריכת קורס', 'rashel-hanukkah' ),
				'search_items'       => __( 'חיפוש קורסים', 'rashel-hanukkah' ),
				'not_found'          => __( 'אין קורסים עדיין.', 'rashel-hanukkah' ),
				'featured_image'     => __( 'תמונת הקורס', 'rashel-hanukkah' ),
				'set_featured_image' => __( 'בחירת תמונה לקורס', 'rashel-hanukkah' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'menu_position'       => 21,
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'rsh_register_course_cpt' );

/**
 * Keep a payment link only when it is an https URL on an allowed host.
 *
 * @param string $url Submitted URL.
 * @return string
 */
function rsh_clean_pay_url( $url ) {
	$url  = esc_url_raw( trim( (string) $url ), array( 'https' ) );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

	/**
	 * Filters the hosts a course's payment link may point at.
	 *
	 * @param string[] $hosts Allowed hostnames.
	 */
	$allowed = (array) apply_filters( 'rsh_pay_hosts', RSH_PAY_HOSTS );

	return '' !== $url && in_array( $host, $allowed, true ) ? $url : '';
}

/**
 * Every published course, in menu order, as plain arrays the templates print.
 *
 * @return array<int, array{id:int, key:string, index:int, name:string, cat:string, tag:string, hook:string, price:int, original:int, pay:string, image:string, thumb:int, syllabus:string}>
 */
function rsh_courses() {
	$cached = get_transient( RSH_COURSES_CACHE );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$courses = array();

	if ( get_option( 'rsh_installed' ) ) {
		// One query for the posts; their meta and thumbnails are primed in the same call.
		$posts = get_posts(
			array(
				'post_type'      => 'rsh_course',
				'post_status'    => 'publish',
				'posts_per_page' => 60,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);

		foreach ( $posts as $post ) {
			$cat = (string) get_post_meta( $post->ID, 'rsh_cat', true );

			$courses[] = array(
				'id'       => (int) $post->ID,
				'key'      => 'c' . (int) $post->ID,
				'index'    => count( $courses ),
				'syllabus' => (string) get_post_meta( $post->ID, 'rsh_syllabus', true ),
				'name'     => (string) get_the_title( $post ),
				'cat'      => isset( rsh_course_categories()[ $cat ] ) ? $cat : 'culinary',
				'tag'      => (string) get_post_meta( $post->ID, 'rsh_tag', true ),
				'hook'     => (string) get_post_meta( $post->ID, 'rsh_hook', true ),
				'price'    => (int) get_post_meta( $post->ID, 'rsh_price', true ),
				'original' => (int) get_post_meta( $post->ID, 'rsh_original', true ),
				'pay'      => (string) get_post_meta( $post->ID, 'rsh_pay', true ),
				'image'    => (string) get_post_meta( $post->ID, 'rsh_image', true ),
				'thumb'    => (int) get_post_thumbnail_id( $post ),
			);
		}
	} else {
		foreach ( rsh_default_courses() as $index => $course ) {
			$courses[] = $course + array(
				'id'       => 0,
				'key'      => 'd' . $index,
				'index'    => $index,
				'original' => 0,
				'thumb'    => 0,
			);
		}
	}

	set_transient( RSH_COURSES_CACHE, $courses, DAY_IN_SECONDS );

	return $courses;
}

/**
 * The courses of one category.
 *
 * @param string $cat Category key.
 * @return array<int, array{id:int, key:string, index:int, name:string, cat:string, tag:string, hook:string, price:int, original:int, pay:string, image:string, thumb:int, syllabus:string}>
 */
function rsh_courses_in( $cat ) {
	return array_values( array_filter( rsh_courses(), static fn( $course ) => $course['cat'] === $cat ) );
}

/**
 * A course changed: rebuild the list on the next request.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function rsh_flush_courses( $post_id = 0 ) {
	if ( $post_id && 'rsh_course' !== get_post_type( $post_id ) ) {
		return;
	}

	delete_transient( RSH_COURSES_CACHE );
}
add_action( 'save_post_rsh_course', 'rsh_flush_courses' );
add_action( 'deleted_post', 'rsh_flush_courses' );
add_action( 'trashed_post', 'rsh_flush_courses' );
add_action( 'untrashed_post', 'rsh_flush_courses' );
add_action( 'switch_theme', 'rsh_flush_courses' );

/*
 * Edit screen
 */

/**
 * The details box on a course.
 *
 * @return void
 */
function rsh_course_meta_boxes() {
	add_meta_box( 'rsh-course', __( 'פרטי הקורס', 'rashel-hanukkah' ), 'rsh_course_box', 'rsh_course', 'normal', 'high' );
}
add_action( 'add_meta_boxes_rsh_course', 'rsh_course_meta_boxes' );

/**
 * Render the details box. Every stored value is escaped where it is printed.
 *
 * @param WP_Post $post Course.
 * @return void
 */
function rsh_course_box( $post ) {
	$meta = static fn( $key ) => (string) get_post_meta( $post->ID, 'rsh_' . $key, true );
	$cat  = $meta( 'cat' );

	wp_nonce_field( 'rsh_course_' . $post->ID, 'rsh_course_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="rsh-course-cat"><?php esc_html_e( 'קטגוריה', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<select id="rsh-course-cat" name="rsh_cat">
					<?php foreach ( rsh_course_categories() as $key => $label_key ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( '' === $cat ? 'culinary' : $cat, $key ); ?>><?php echo esc_html( rsh_text( $label_key ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'הלשונית שבה הקורס מופיע בעמוד.', 'rashel-hanukkah' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="rsh-course-tag"><?php esc_html_e( 'תווית', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<input type="text" id="rsh-course-tag" name="rsh_tag" class="regular-text" maxlength="40" value="<?php echo esc_attr( $meta( 'tag' ) ); ?>">
				<p class="description"><?php esc_html_e( 'לא חובה. למשל "קורס #1". מופיעה מעל שם הקורס.', 'rashel-hanukkah' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="rsh-course-hook"><?php esc_html_e( 'תיאור קצר', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<textarea id="rsh-course-hook" name="rsh_hook" class="large-text" rows="3" maxlength="300"><?php echo esc_textarea( $meta( 'hook' ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="rsh-course-syllabus"><?php esc_html_e( 'סילבוס', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<textarea id="rsh-course-syllabus" name="rsh_syllabus" class="large-text" rows="8" maxlength="8000"><?php echo esc_textarea( $meta( 'syllabus' ) ); ?></textarea>
				<p class="description"><?php esc_html_e( 'נפתח בחלון "לסילבוס המלא". שורה לכל שיעור: כותרת | תיאור. שורה שמתחילה בכוכבית (*) מוצגת כבונוס. בלי סילבוס, הכפתור לא מוצג בכרטיס.', 'rashel-hanukkah' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="rsh-course-price"><?php esc_html_e( 'מחיר מבצע (₪)', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<input type="number" id="rsh-course-price" name="rsh_price" class="small-text" min="0" max="1000000" step="1" value="<?php echo esc_attr( $meta( 'price' ) ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="rsh-course-original"><?php esc_html_e( 'מחיר מקורי (₪)', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<input type="number" id="rsh-course-original" name="rsh_original" class="small-text" min="0" max="1000000" step="1" value="<?php echo esc_attr( $meta( 'original' ) ); ?>">
				<p class="description"><?php esc_html_e( 'אפשר להשאיר ריק: המחיר המקורי יחושב ממחיר המבצע לפי אחוז ההנחה שבהגדרות.', 'rashel-hanukkah' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="rsh-course-pay"><?php esc_html_e( 'קישור לתשלום', 'rashel-hanukkah' ); ?></label></th>
			<td>
				<input type="url" id="rsh-course-pay" name="rsh_pay" class="large-text" dir="ltr" value="<?php echo esc_attr( $meta( 'pay' ) ); ?>">
				<p class="description"><?php esc_html_e( 'כתובת https של עמוד התשלום ב-Cardcom. כתובת באתר אחר לא תישמר.', 'rashel-hanukkah' ); ?></p>
			</td>
		</tr>
	</table>
	<p class="description"><?php esc_html_e( 'תמונת הקורס נבחרת בתיבה "תמונת הקורס". בלי תמונה מוצגת התמונה שהגיעה עם התבנית. סדר הקורסים נקבע בשדה "סדר".', 'rashel-hanukkah' ); ?></p>
	<?php
}

/**
 * Save the details: nonce, capability, then each field by its own rule.
 *
 * @param int $post_id Course.
 * @return void
 */
function rsh_course_save( $post_id ) {
	if ( ! isset( $_POST['rsh_course_nonce'] ) || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rsh_course_nonce'] ) ), 'rsh_course_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$cat = isset( $_POST['rsh_cat'] ) ? sanitize_key( wp_unslash( $_POST['rsh_cat'] ) ) : '';

	update_post_meta( $post_id, 'rsh_cat', isset( rsh_course_categories()[ $cat ] ) ? $cat : 'culinary' );
	update_post_meta( $post_id, 'rsh_tag', mb_substr( sanitize_text_field( wp_unslash( $_POST['rsh_tag'] ?? '' ) ), 0, 40 ) );
	update_post_meta( $post_id, 'rsh_hook', mb_substr( sanitize_textarea_field( wp_unslash( $_POST['rsh_hook'] ?? '' ) ), 0, 300 ) );
	update_post_meta( $post_id, 'rsh_syllabus', mb_substr( sanitize_textarea_field( wp_unslash( $_POST['rsh_syllabus'] ?? '' ) ), 0, 8000 ) );
	update_post_meta( $post_id, 'rsh_price', min( 1000000, absint( wp_unslash( $_POST['rsh_price'] ?? 0 ) ) ) );
	update_post_meta( $post_id, 'rsh_original', min( 1000000, absint( wp_unslash( $_POST['rsh_original'] ?? 0 ) ) ) );
	update_post_meta( $post_id, 'rsh_pay', rsh_clean_pay_url( sanitize_text_field( wp_unslash( $_POST['rsh_pay'] ?? '' ) ) ) );
}
add_action( 'save_post_rsh_course', 'rsh_course_save', 5 );

/**
 * Category and price columns on the course list.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function rsh_course_columns( $columns ) {
	$date = $columns['date'] ?? '';
	unset( $columns['date'] );

	$columns['rsh_cat']   = __( 'קטגוריה', 'rashel-hanukkah' );
	$columns['rsh_price'] = __( 'מחיר מבצע', 'rashel-hanukkah' );

	if ( $date ) {
		$columns['date'] = $date;
	}

	return $columns;
}
add_filter( 'manage_rsh_course_posts_columns', 'rsh_course_columns' );

/**
 * Course column content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Course ID.
 * @return void
 */
function rsh_course_column_content( $column, $post_id ) {
	if ( 'rsh_cat' === $column ) {
		$cat = (string) get_post_meta( $post_id, 'rsh_cat', true );
		echo esc_html( rsh_text( rsh_course_categories()[ $cat ] ?? 'tab_culinary' ) );
	}

	if ( 'rsh_price' === $column ) {
		echo esc_html( rsh_format_price( (int) get_post_meta( $post_id, 'rsh_price', true ) ) );
	}
}
add_action( 'manage_rsh_course_posts_custom_column', 'rsh_course_column_content', 10, 2 );
