<?php
/**
 * Rashel: the portrait and her own words.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<section class="rsh-about" aria-labelledby="rsh-about-ttl">
	<div class="rsh-wrap rsh-about__in">
		<div class="rsh-about__portrait">
			<span class="rsh-about__block" aria-hidden="true"></span>
			<span class="rsh-about__star" aria-hidden="true">✦</span>
			<div class="rsh-about__photo">
				<?php rsh_slot_image( 'portrait', array( 'alt' => rsh_text( 'portrait_alt' ) ) ); ?>
			</div>
		</div>
		<div class="rsh-about__text">
			<h2 class="rsh-sr-only" id="rsh-about-ttl"><?php rsh_the_text( 'about_ttl' ); ?></h2>
			<p class="rsh-about__quote" data-rsh-reveal><?php rsh_the_text( 'about_quote' ); ?></p>
			<p class="rsh-about__txt"><?php rsh_the_text( 'about_txt' ); ?></p>
			<p class="rsh-sign" data-rsh-reveal>
				<span class="rsh-sign__name"><?php rsh_the_text( 'signature' ); ?></span>
				<svg class="rsh-sign__flourish" viewBox="0 0 220 90" aria-hidden="true" focusable="false">
					<path class="rsh-sign__p1" d="M206 30 C 150 10, 60 20, 40 52 C 26 74, 70 82, 110 66 C 140 54, 150 40, 126 40 C 96 40, 52 70, 14 78 C 4 80, 2 74, 12 70" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
					<path class="rsh-sign__p2" d="M30 84 C 80 78, 150 80, 214 72" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
				</svg>
			</p>
			<div data-rsh-reveal>
				<?php
				rsh_cta(
					array(
						'pulse' => true,
						'class' => 'rsh-cta--lg rsh-cta--shadow',
					)
				);
				?>
			</div>
		</div>
	</div>
</section>
