<?php
/**
 * The template for displaying all single posts
 *
 * @package DocsTheme
 */

get_header(); ?>

<aside class="docs-sidebar-left">
	<?php get_template_part( 'template-parts/sidebar', 'pages' ); ?>
</aside>

<main id="primary" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</header>

			<div class="entry-content">
				<?php
				the_content();
				
				wp_link_pages( array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'docs-theme' ),
					'after'  => '</div>',
				) );
				?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>

<aside class="docs-sidebar-right">
	<h4 class="toc-title"><?php esc_html_e( 'Table of contents', 'docs-theme' ); ?></h4>
	<nav class="docs-toc" id="table-of-contents"></nav>
</aside>

<?php get_footer();