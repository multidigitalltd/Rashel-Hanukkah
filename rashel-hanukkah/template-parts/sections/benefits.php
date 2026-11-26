<?php
/**
 * Benefits: the Hanukkah table photo, the discount line with the coupon code,
 * and the benefits with their line icons.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rsh_benefits = rsh_text_lines( 'benefits' );
?>
<section class="rsh-benefits" aria-labelledby="rsh-benefits-ttl">
	<div class="rsh-wrap rsh-benefits__in">
		<div class="rsh-benefits__photo" data-rsh-reveal>
			<?php
			rsh_slot_image(
				'benefits',
				array(
					'alt'   => rsh_text( 'benefits_alt' ),
					'sizes' => '(min-width: 960px) 520px, 92vw',
				)
			);
			?>
		</div>

		<div class="rsh-benefits__text">
			<div class="rsh-benefits__head" data-rsh-reveal>
				<h2 class="rsh-benefits__ttl" id="rsh-benefits-ttl"><?php rsh_the_text( 'benefits_ttl' ); ?></h2>
				<p class="rsh-benefits__big"><?php rsh_the_text( 'benefits_big' ); ?></p>
				<?php rsh_coupon_line( 'lg' ); ?>
			</div>

			<?php if ( $rsh_benefits ) : ?>
				<ul class="rsh-benefits__list">
					<?php foreach ( $rsh_benefits as $rsh_n => $rsh_benefit ) : ?>
						<li class="rsh-benefits__item" data-rsh-reveal>
							<?php rsh_benefit_icon( $rsh_n ); ?>
							<span><?php echo esc_html( $rsh_benefit ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
