<?php
/**
 * First-visit privacy notice (Multi Digital standard 15.5).
 *
 * Hidden by default and identical for every visitor; assets/js/privacy.js
 * shows it when the visitor has not seen this version of the policy in the
 * last 12 months. Non-modal: the site stays usable while it is open.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_policy = (string) get_privacy_policy_url();
?>
<section class="rsh-privacy" role="dialog" aria-modal="false" aria-labelledby="rsh-privacy-title" aria-describedby="rsh-privacy-text" tabindex="-1" data-rsh-privacy="<?php echo esc_attr( rsh_privacy_version() ); ?>" hidden>
	<div class="rsh-privacy__in">
		<h2 class="rsh-privacy__h" id="rsh-privacy-title"><?php rsh_the_text( 'privacy_ttl' ); ?></h2>
		<p id="rsh-privacy-text">
			<?php rsh_the_text( 'privacy_txt' ); ?>
			<?php if ( '' !== $rsh_policy ) : ?>
				<a href="<?php echo esc_url( $rsh_policy ); ?>"><?php rsh_the_text( 'privacy_link' ); ?></a>
			<?php endif; ?>
		</p>
		<button type="button" class="rsh-privacy__ok" data-rsh-privacy-ok><?php rsh_the_text( 'privacy_ok' ); ?></button>
	</div>
</section>
