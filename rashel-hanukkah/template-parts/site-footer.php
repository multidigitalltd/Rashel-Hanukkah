<?php
/**
 * Site footer: logo, phone, the mandatory legal links and the credit
 * (standard 15.4, 15.6). Pulled in by footer.php.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_legal = rsh_legal_links();
?>
<footer class="rsh-footer">
	<div class="rsh-wrap rsh-footer__row">
		<?php
		rsh_slot_image(
			'logo',
			array(
				'class' => 'rsh-logo rsh-logo--sm',
				'alt'   => get_bloginfo( 'name' ),
			)
		);
		?>
		<a class="rsh-footer__phone" href="tel:<?php echo esc_attr( rsh_phone_digits( rsh_phone(), true ) ); ?>">
			<span class="rsh-sr-only"><?php esc_html_e( 'טלפון:', 'rashel-hanukkah' ); ?> </span><bdo dir="ltr"><?php echo esc_html( rsh_phone() ); ?></bdo>
		</a>
	</div>
	<div class="rsh-wrap rsh-footer__legal">
		<nav aria-label="<?php esc_attr_e( 'מידע משפטי ונגישות', 'rashel-hanukkah' ); ?>">
			<ul class="rsh-footer__links">
				<?php foreach ( $rsh_legal as $rsh_link ) : ?>
					<li><a href="<?php echo esc_url( $rsh_link['url'] ); ?>"><?php echo esc_html( $rsh_link['label'] ); ?></a></li>
				<?php endforeach; ?>
				<li><button type="button" class="rsh-footer__linkbtn" data-rsh-privacy-open hidden><?php rsh_the_text( 'privacy_settings' ); ?></button></li>
			</ul>
		</nav>
		<p class="rsh-footer__meta">
			<span>
				<?php
				printf(
					/* translators: 1: current year, 2: site name. */
					esc_html__( '© %1$s %2$s. כל הזכויות שמורות.', 'rashel-hanukkah' ),
					esc_html( current_datetime()->format( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</span>
			<?php if ( rsh_show_credit() ) : ?>
				<a class="rsh-footer__credit" href="https://m-d.co.il" target="_blank" rel="noopener"><?php rsh_the_text( 'credit' ); ?><span class="rsh-sr-only"> <?php esc_html_e( '(נפתח בחלון חדש)', 'rashel-hanukkah' ); ?></span></a>
			<?php endif; ?>
		</p>
	</div>
</footer>
