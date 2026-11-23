<?php
/**
 * Document head and the opening of <main>.
 *
 * The landing page carries its logo inside the hero, as in the design; every
 * other page gets a small dark header with the logo linking home.
 *
 * @package Rashel_Hanukkah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="rsh-skip" href="#rsh-main"><?php esc_html_e( 'דלג לתוכן הראשי', 'rashel-hanukkah' ); ?></a>

<?php if ( ! rsh_is_landing() ) : ?>
	<header class="rsh-header">
		<div class="rsh-wrap rsh-header__in">
			<a class="rsh-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php
				rsh_image(
					'rashel-logo.png',
					270,
					191,
					array(
						'class'   => 'rsh-logo',
						'alt'     => get_bloginfo( 'name' ),
						'loading' => 'eager',
					)
				);
				?>
				<span class="rsh-sr-only"><?php esc_html_e( '— לעמוד הבית', 'rashel-hanukkah' ); ?></span>
			</a>
		</div>
	</header>
<?php endif; ?>

<main id="rsh-main" tabindex="-1">
