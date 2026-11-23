<?php
/**
 * A regular page: the legal pages and anything else the client adds.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'rsh-page rsh-wrap' ); ?>>
		<h1 class="rsh-page__title"><?php the_title(); ?></h1>
		<div class="rsh-prose">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
