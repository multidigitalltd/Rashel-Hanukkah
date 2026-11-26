<?php
/**
 * Accessibility layer.
 *
 * Targets WCAG 2.2 AA and Israeli Standard ת"י 5568. The toolbar is a
 * progressive enhancement: with JavaScript off the page is already usable,
 * keyboard operable and screen-reader complete.
 *
 * The inline boot script also decides, before first paint, whether the page
 * may move: it adds "rsh-js" to <html>, and "rsh-motion" only when neither the
 * visitor's system nor the toolbar asked for reduced motion.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Toolbar toggles. Key becomes the class rsh-a11y-{key} on <html>.
 *
 * @return array<string,array{label:string,group:string}>
 */
function rsh_a11y_toggles() {
	return array(
		'contrast'  => array(
			'label' => __( 'ניגודיות גבוהה', 'rashel-hanukkah' ),
			'group' => 'view',
		),
		'invert'    => array(
			'label' => __( 'היפוך צבעים', 'rashel-hanukkah' ),
			'group' => 'view',
		),
		'grayscale' => array(
			'label' => __( 'גווני אפור', 'rashel-hanukkah' ),
			'group' => 'view',
		),
		'readable'  => array(
			'label' => __( 'גופן קריא', 'rashel-hanukkah' ),
			'group' => 'text',
		),
		'spacing'   => array(
			'label' => __( 'ריווח טקסט מוגדל', 'rashel-hanukkah' ),
			'group' => 'text',
		),
		'links'     => array(
			'label' => __( 'הדגשת קישורים', 'rashel-hanukkah' ),
			'group' => 'text',
		),
		'underline' => array(
			'label' => __( 'קו תחתון לקישורים', 'rashel-hanukkah' ),
			'group' => 'text',
		),
		'headings'  => array(
			'label' => __( 'הדגשת כותרות', 'rashel-hanukkah' ),
			'group' => 'text',
		),
		'nomotion'  => array(
			'label' => __( 'עצירת אנימציות', 'rashel-hanukkah' ),
			'group' => 'nav',
		),
		'guide'     => array(
			'label' => __( 'סרגל קריאה', 'rashel-hanukkah' ),
			'group' => 'nav',
		),
		'kbd'       => array(
			'label' => __( 'סימון מוגבר לניווט במקלדת', 'rashel-hanukkah' ),
			'group' => 'nav',
		),
	);
}

/**
 * Group headings for the panel.
 *
 * @return array<string,string>
 */
function rsh_a11y_groups() {
	return array(
		'view' => __( 'תצוגה וצבע', 'rashel-hanukkah' ),
		'text' => __( 'טקסט וקריאות', 'rashel-hanukkah' ),
		'nav'  => __( 'ניווט ותנועה', 'rashel-hanukkah' ),
	);
}

/**
 * Whether the accessibility toolbar is shown.
 *
 * @return bool
 */
function rsh_a11y_enabled() {
	return (bool) rsh_setting( 'a11y_toolbar' );
}

/**
 * Permalink of the accessibility statement page chosen in the dashboard.
 *
 * @return string
 */
function rsh_a11y_url() {
	return rsh_page_url( 'a11y_page' );
}

/**
 * Restore saved preferences before first paint, so nothing shifts under the
 * visitor. Small enough to stay inline; no request, no render-blocking file.
 *
 * @return void
 */
function rsh_a11y_boot_script() {
	$js = '(function(){var r=document.documentElement,m=true;r.classList.add("rsh-js");'
		. 'try{var s=localStorage.getItem("rshA11y");if(s){var p=JSON.parse(s);'
		. 'if(p.scale){r.style.setProperty("--rsh-a11y-scale",p.scale);}'
		. 'if(p.flags){for(var i=0;i<p.flags.length;i++){r.classList.add("rsh-a11y-"+p.flags[i]);if("nomotion"===p.flags[i]){m=false;}}}'
		. '}}catch(e){}'
		. 'if(m&&!(window.matchMedia&&window.matchMedia("(prefers-reduced-motion: reduce)").matches)){r.classList.add("rsh-motion");}'
		. '})();';

	wp_print_inline_script_tag( $js, array( 'id' => 'rsh-a11y-boot' ) );
}
add_action( 'wp_head', 'rsh_a11y_boot_script', 2 );

/**
 * Render the toolbar. Called from footer.php.
 *
 * @return void
 */
function rsh_a11y_toolbar() {
	if ( ! rsh_a11y_enabled() ) {
		return;
	}

	$groups    = rsh_a11y_groups();
	$statement = rsh_a11y_url();

	// Bucket the toggles once, so the render loop stays a plain nested foreach.
	$by_group = array_fill_keys( array_keys( $groups ), array() );
	foreach ( rsh_a11y_toggles() as $key => $toggle ) {
		$by_group[ $toggle['group'] ][ $key ] = $toggle['label'];
	}
	?>
	<div class="rsh-a11y" data-rsh-a11y-root>
		<button
			type="button"
			class="rsh-a11y__launch"
			aria-expanded="false"
			aria-controls="rsh-a11y-panel"
			data-rsh-a11y-launch
		>
			<svg class="rsh-a11y__icon" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false">
				<circle cx="12" cy="4.2" r="2.1" fill="currentColor"/>
				<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square"
					d="M3.6 8.2h16.8M12 8.2v6.1M12 14.3l-3.4 6.5M12 14.3l3.4 6.5"/>
			</svg>
			<span class="rsh-sr-only"><?php esc_html_e( 'כלי נגישות', 'rashel-hanukkah' ); ?></span>
		</button>

		<div
			class="rsh-a11y__panel"
			id="rsh-a11y-panel"
			role="dialog"
			aria-modal="true"
			aria-labelledby="rsh-a11y-title"
			hidden
		>
			<div class="rsh-a11y__head">
				<h2 class="rsh-a11y__title" id="rsh-a11y-title"><?php esc_html_e( 'כלי נגישות', 'rashel-hanukkah' ); ?></h2>
				<button type="button" class="rsh-a11y__close" data-rsh-a11y-close>
					<span aria-hidden="true">✕</span>
					<span class="rsh-sr-only"><?php esc_html_e( 'סגירת כלי הנגישות', 'rashel-hanukkah' ); ?></span>
				</button>
			</div>

			<div class="rsh-a11y__group" role="group" aria-labelledby="rsh-a11y-size">
				<h3 class="rsh-a11y__gh" id="rsh-a11y-size"><?php esc_html_e( 'גודל טקסט', 'rashel-hanukkah' ); ?></h3>
				<div class="rsh-a11y__size">
					<button type="button" class="rsh-a11y__step" data-rsh-a11y-step="-1">
						<span aria-hidden="true">א−</span>
						<span class="rsh-sr-only"><?php esc_html_e( 'הקטנת גודל הטקסט', 'rashel-hanukkah' ); ?></span>
					</button>
					<output class="rsh-a11y__val" data-rsh-a11y-value aria-live="polite">100%</output>
					<button type="button" class="rsh-a11y__step" data-rsh-a11y-step="1">
						<span aria-hidden="true">א+</span>
						<span class="rsh-sr-only"><?php esc_html_e( 'הגדלת גודל הטקסט', 'rashel-hanukkah' ); ?></span>
					</button>
				</div>
			</div>

			<?php foreach ( $groups as $group_key => $group_label ) : ?>
				<div class="rsh-a11y__group" role="group" aria-labelledby="rsh-a11y-g-<?php echo esc_attr( $group_key ); ?>">
					<h3 class="rsh-a11y__gh" id="rsh-a11y-g-<?php echo esc_attr( $group_key ); ?>">
						<?php echo esc_html( $group_label ); ?>
					</h3>
					<div class="rsh-a11y__toggles">
						<?php foreach ( $by_group[ $group_key ] as $key => $label ) : ?>
							<button
								type="button"
								class="rsh-a11y__toggle"
								aria-pressed="false"
								data-rsh-a11y-toggle="<?php echo esc_attr( $key ); ?>"
							><?php echo esc_html( $label ); ?></button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>

			<div class="rsh-a11y__foot">
				<button type="button" class="rsh-a11y__reset" data-rsh-a11y-reset>
					<?php esc_html_e( 'איפוס כל ההגדרות', 'rashel-hanukkah' ); ?>
				</button>
				<?php if ( $statement ) : ?>
					<a class="rsh-a11y__link" href="<?php echo esc_url( $statement ); ?>">
						<?php esc_html_e( 'הצהרת נגישות', 'rashel-hanukkah' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="rsh-a11y__guide" data-rsh-a11y-guide aria-hidden="true"></div>
	</div>
	<?php
}
