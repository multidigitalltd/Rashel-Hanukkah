<?php
/**
 * Fallback template: any listing the site may have (posts, search, archives).
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="rsh-page rsh-wrap">
	<h1 class="rsh-page__title">
		<?php
		if ( is_search() ) {
			/* translators: %s: search query. */
			printf( esc_html__( 'תוצאות חיפוש: %s', 'rashel-hanukkah' ), esc_html( get_search_query() ) );
		} elseif ( is_archive() ) {
			echo esc_html( wp_strip_all_tags( (string) get_the_archive_title() ) );
		} else {
			echo esc_html( (string) get_bloginfo( 'name' ) );
		}
		?>
	</h1>

	<?php if ( have_posts() ) : ?>
		<div class="rsh-prose">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'rsh-entry' ); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
				<?php
			endwhile;

			the_posts_pagination(
				array(
					'prev_text' => __( 'הקודם', 'rashel-hanukkah' ),
					'next_text' => __( 'הבא', 'rashel-hanukkah' ),
				)
			);
		?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'לא נמצא כאן תוכן.', 'rashel-hanukkah' ); ?></p>
		<p><a class="rsh-cta rsh-cta--dark" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'חזרה לעמוד הבית', 'rashel-hanukkah' ); ?></a></p>
	<?php endif; ?>
</div>
<?php
get_footer();
