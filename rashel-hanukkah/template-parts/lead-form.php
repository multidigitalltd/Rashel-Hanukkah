<?php
/**
 * Consultation lead form.
 *
 * Works without JavaScript (a normal POST to admin-post.php that redirects
 * back) and is enhanced by assets/js/forms.js into an in-place submission
 * over REST. Every field has a real label; errors are tied to their field and
 * announced.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_notice = rsh_lead_notice();
$rsh_sent   = $rsh_notice && 'success' === $rsh_notice['status'];
$rsh_policy = (string) get_privacy_policy_url();
$rsh_fields = array(
	'name'  => array(
		'label'    => 'form_name',
		'type'     => 'text',
		'auto'     => 'name',
		'mode'     => 'text',
		'required' => true,
		'max'      => 80,
	),
	'phone' => array(
		'label'    => 'form_phone',
		'type'     => 'tel',
		'auto'     => 'tel',
		'mode'     => 'tel',
		'required' => true,
		'max'      => 30,
	),
	'email' => array(
		'label'    => 'form_email',
		'type'     => 'email',
		'auto'     => 'email',
		'mode'     => 'email',
		'required' => false,
		'max'      => 120,
	),
);
?>
<div class="rsh-form" id="consult-form" data-rsh-reveal>
	<span class="rsh-form__tw rsh-form__tw--1" aria-hidden="true">✦</span>
	<span class="rsh-form__tw rsh-form__tw--2" aria-hidden="true">✦</span>
	<span class="rsh-form__tw rsh-form__tw--3" aria-hidden="true">✦</span>

	<div class="rsh-form__card">
		<h3 class="rsh-form__ttl" id="rsh-form-ttl"><?php rsh_the_text( 'form_ttl' ); ?></h3>

		<?php if ( $rsh_sent ) : ?>

			<div class="rsh-sent" role="status">
				<span class="rsh-sent__star" aria-hidden="true">✦</span>
				<p class="rsh-sent__ttl"><?php rsh_the_text( 'form_ok_ttl' ); ?></p>
			</div>

		<?php else : ?>

			<form class="rsh-lead-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" aria-labelledby="rsh-form-ttl" novalidate>
				<input type="hidden" name="action" value="rsh_lead">
				<input type="hidden" name="rsh_source" value="<?php echo esc_url( rsh_current_url() ); ?>">
				<input type="hidden" name="rsh_utm" value="" data-rsh-utm>
				<?php wp_nonce_field( 'rsh_lead', 'nonce', false ); ?>

				<p class="rsh-hp" aria-hidden="true">
					<label for="rsh-website"><?php esc_html_e( 'אין למלא את השדה הזה', 'rashel-hanukkah' ); ?></label>
					<input type="text" id="rsh-website" name="rsh_website" tabindex="-1" autocomplete="off">
				</p>

				<p class="rsh-form__alert" role="alert" tabindex="-1" data-rsh-alert><?php echo esc_html( $rsh_notice ? $rsh_notice['message'] : '' ); ?></p>

				<div class="rsh-form__fields">
					<?php foreach ( $rsh_fields as $rsh_name => $rsh_field ) : ?>
						<?php
						/*
						 * A real label that rests where the design shows a placeholder and
						 * moves above the text once the field is focused or filled: the
						 * look of the design, without a placeholder standing in for a label.
						 */
						?>
						<div class="rsh-field rsh-field--<?php echo esc_attr( $rsh_name ); ?>">
							<input
								class="rsh-field__input"
								id="rsh-<?php echo esc_attr( $rsh_name ); ?>"
								name="rsh_<?php echo esc_attr( $rsh_name ); ?>"
								type="<?php echo esc_attr( $rsh_field['type'] ); ?>"
								inputmode="<?php echo esc_attr( $rsh_field['mode'] ); ?>"
								autocomplete="<?php echo esc_attr( $rsh_field['auto'] ); ?>"
								maxlength="<?php echo esc_attr( (string) $rsh_field['max'] ); ?>"
								placeholder=" "
								<?php echo $rsh_field['required'] ? 'required aria-required="true"' : ''; ?>
							>
							<label class="rsh-field__label" for="rsh-<?php echo esc_attr( $rsh_name ); ?>">
								<?php rsh_the_text( $rsh_field['label'] ); ?>
								<?php if ( $rsh_field['required'] ) : ?>
									<span class="rsh-field__req">(<?php rsh_the_text( 'form_required' ); ?>)</span>
								<?php endif; ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( rsh_turnstile_enabled() ) : ?>
					<div class="cf-turnstile rsh-form__captcha" data-sitekey="<?php echo esc_attr( rsh_turnstile_site_key() ); ?>" data-action="<?php echo esc_attr( RSH_TURNSTILE_ACTION ); ?>" data-theme="light" data-language="he"></div>
				<?php endif; ?>

				<button type="submit" class="rsh-form__submit">
					<span data-rsh-submit-label><?php rsh_the_text( 'form_submit' ); ?></span>
					<span class="rsh-form__submit-star" aria-hidden="true">✦</span>
				</button>

				<p class="rsh-form__privacy">
					<?php rsh_the_text( 'form_privacy' ); ?>
					<?php if ( '' !== $rsh_policy ) : ?>
						<a href="<?php echo esc_url( $rsh_policy ); ?>"><?php esc_html_e( 'מדיניות הפרטיות', 'rashel-hanukkah' ); ?></a>
					<?php endif; ?>
				</p>
			</form>

		<?php endif; ?>
	</div>
</div>
