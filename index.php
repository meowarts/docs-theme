<?php
/**
 * The main template file
 *
 * @package DocsTheme
 */

get_header(); ?>

<aside class="docs-sidebar-left">
	<?php get_template_part( 'template-parts/sidebar', 'pages' ); ?>
</aside>

<main id="primary" class="site-main">
	<?php
	if ( have_posts() ) :

		if ( is_home() && ! is_front_page() ) :
			?>
			<header>
				<h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
			</header>
			<?php
		endif;

		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>

				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
			<?php
		endwhile;

		the_posts_navigation();

	else :
		?>
		<article class="no-results">
			<header class="entry-header">
				<h1 class="entry-title"><?php esc_html_e( 'Nothing Found', 'docs-theme' ); ?></h1>
			</header>
			<div class="entry-content">
				<p><?php esc_html_e( 'It seems we can\'t find what you\'re looking for.', 'docs-theme' ); ?></p>
			</div>
		</article>
		<?php
	endif;
	?>
</main>

<?php get_footer();