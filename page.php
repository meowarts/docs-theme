<?php
/**
 * The template for displaying all pages
 *
 * @package DocsTheme
 */

get_header(); ?>

<aside class="docs-sidebar-left">
	<?php get_template_part( 'template-parts/sidebar', 'pages' ); ?>
</aside>

<main id="primary" class="site-main">
	<?php
	// Initialize child_pages variable outside loop for TOC logic
	$child_pages = array();
	
	while ( have_posts() ) :
		the_post();
		
		// Check if this page has child pages
		$child_pages = get_pages( apply_filters( 'docs_theme_child_pages_query', array(
			'parent' => get_the_ID(),
			'sort_column' => 'menu_order,post_title',
			'sort_order' => 'ASC',
			'post_status' => 'publish',
			'number' => 0, // No limit by default
		), get_the_ID() ) );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<?php do_action( 'docs_theme_after_page_title' ); ?>
			</header>

			<div class="entry-content">
				<?php
				the_content();
				
				wp_link_pages( array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'docs-theme' ),
					'after'  => '</div>',
				) );
				
				// Display child pages as cards if this is a parent page
				if ( ! empty( $child_pages ) ) : ?>
					<div class="docs-child-pages">
						<div class="docs-page-cards">
							<?php foreach ( $child_pages as $child_page ) : 
								// Get the excerpt or use subtitle as fallback
								$excerpt = $child_page->post_excerpt;
								if ( empty( $excerpt ) ) {
									// Try subtitle first
									$subtitle = get_post_meta( $child_page->ID, '_docs_theme_subtitle', true );
									if ( ! empty( $subtitle ) ) {
										$excerpt = wp_trim_words( $subtitle, 25, '...' );
									} else {
										// Generate from content as last resort
										$excerpt = wp_trim_words( strip_shortcodes( wp_strip_all_tags( $child_page->post_content ) ), 25, '...' );
									}
								}
								
								// Get emoticon
								$emoticon = get_post_meta( $child_page->ID, '_docs_theme_emoticon', true );
								
								// Get child count for this page
								$grandchild_count = count( get_pages( array(
									'parent' => $child_page->ID,
									'post_status' => 'publish',
								) ) );
								?>
								<a href="<?php echo esc_url( get_permalink( $child_page->ID ) ); ?>" class="docs-page-card">
									<div class="docs-page-card-content">
										<h3 class="docs-page-card-title">
											<?php if ( ! empty( $emoticon ) ) : ?>
												<span class="docs-page-card-emoticon"><?php echo esc_html( $emoticon ); ?></span>
											<?php endif; ?>
											<?php echo esc_html( $child_page->post_title ); ?>
										</h3>
										<?php if ( ! empty( $excerpt ) ) : ?>
											<p class="docs-page-card-excerpt"><?php echo esc_html( $excerpt ); ?></p>
										<?php endif; ?>
										<?php if ( $grandchild_count > 0 ) : ?>
											<span class="docs-page-card-meta"><?php printf( esc_html( _n( '%d page', '%d pages', $grandchild_count, 'docs-theme' ) ), $grandchild_count ); ?></span>
										<?php endif; ?>
									</div>
									<svg class="docs-page-card-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php 
// Only show table of contents if the page has substantial content or no child pages
$has_child_pages = ! empty( $child_pages );
$content_length = strlen( strip_tags( get_the_content() ) );
$show_toc = ! $has_child_pages || $content_length > 500;

if ( $show_toc ) : ?>
<aside class="docs-sidebar-right">
	<h4 class="toc-title"><?php esc_html_e( 'Table of contents', 'docs-theme' ); ?></h4>
	<nav class="docs-toc" id="table-of-contents"></nav>
</aside>
<?php endif; ?>

<?php get_footer();