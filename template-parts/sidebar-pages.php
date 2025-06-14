<?php
/**
 * Template part for displaying the automatic page hierarchy sidebar
 *
 * @package DocsTheme
 */

// Get all published pages
$all_pages = get_pages( array(
	'sort_order' => 'ASC',
	'sort_column' => 'menu_order,post_title',
	'hierarchical' => 1,
	'parent' => 0,
	'post_status' => 'publish',
) );

// Get all categories
$category_args = array(
	'taxonomy' => 'page_category',
	'orderby' => 'name',
	'order' => 'ASC',
	'hide_empty' => true,
);
$category_args = apply_filters( 'docs_theme_categories_query_args', $category_args );
$categories = get_categories( $category_args );

// Get current page ID
$current_page_id = is_page() ? get_the_ID() : 0;

// Function to check if a page or its children are active
function is_page_or_child_active( $page_id, $current_id ) {
	if ( $page_id == $current_id ) {
		return true;
	}
	
	// Check if current page is a child of this page
	$ancestors = get_post_ancestors( $current_id );
	return in_array( $page_id, $ancestors );
}

// Function to render page tree
function render_page_tree( $pages, $parent_id = 0, $current_page_id = 0 ) {
	$output = '';
	
	foreach ( $pages as $page ) {
		if ( $page->post_parent == $parent_id ) {
			$has_children = get_pages( array( 
				'parent' => $page->ID, 
				'post_status' => 'publish',
				'sort_order' => 'ASC',
				'sort_column' => 'menu_order,post_title'
			) );
			$is_current = ( $page->ID == $current_page_id );
			$is_active = is_page_or_child_active( $page->ID, $current_page_id );
			
			$classes = 'page-item';
			if ( $is_current ) $classes .= ' current-page';
			if ( $is_active ) $classes .= ' active-parent';
			if ( $has_children ) $classes .= ' has-children';
			
			$output .= '<li class="' . esc_attr( $classes ) . '">';
			$output .= '<div class="page-item-wrapper">';
			$output .= '<a href="' . get_permalink( $page->ID ) . '" class="page-link">' . esc_html( $page->post_title ) . '</a>';
			
			if ( $has_children ) {
				$output .= '<button class="toggle-children" aria-expanded="' . ( $is_active ? 'true' : 'false' ) . '">';
				$output .= '<svg width="12" height="12" viewBox="0 0 12 12"><path d="M4 2l4 4-4 4" stroke="currentColor" stroke-width="2" fill="none"/></svg>';
				$output .= '</button>';
			}
			
			$output .= '</div>';
			
			if ( $has_children ) {
				$child_pages = get_pages( array( 'parent' => $page->ID, 'post_status' => 'publish', 'sort_order' => 'ASC', 'sort_column' => 'menu_order,post_title' ) );
				$output .= '<ul class="children" style="' . ( $is_active ? '' : 'display: none;' ) . '">';
				$output .= render_page_tree( $child_pages, $page->ID, $current_page_id );
				$output .= '</ul>';
			}
			
			$output .= '</li>';
		}
	}
	
	return $output;
}
?>

<div class="sidebar-pages-navigation">
	<?php if ( ! empty( $categories ) ) : ?>
		<?php foreach ( $categories as $category ) : ?>
			<div class="page-category-section">
				<h4 class="category-title"><?php echo esc_html( $category->name ); ?></h4>
				<?php
				// Get pages in this category using WP_Query for taxonomy support
				$args = array(
					'post_type' => 'page',
					'posts_per_page' => -1,
					'post_parent' => 0,
					'orderby' => 'menu_order title',
					'order' => 'ASC',
					'post_status' => 'publish',
					'tax_query' => array(
						array(
							'taxonomy' => 'page_category',
							'field' => 'term_id',
							'terms' => $category->term_id,
						),
					),
				);
				$args = apply_filters( 'docs_theme_pages_query_args', $args );
				$query = new WP_Query( $args );
				$pages_in_category = $query->posts;
				
				if ( ! empty( $pages_in_category ) ) : ?>
					<ul class="pages-list">
						<?php echo render_page_tree( $pages_in_category, 0, $current_page_id ); ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
	
	<?php
	// Show uncategorized pages - get all pages not in any category
	// First get all pages that ARE in categories
	$categorized_page_ids = array();
	if ( ! empty( $categories ) ) {
		foreach ( $categories as $category ) {
			$args = array(
				'post_type' => 'page',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => 'page_category',
						'field' => 'term_id',
						'terms' => $category->term_id,
					),
				),
			);
			$args = apply_filters( 'docs_theme_pages_query_args', $args );
			$query = new WP_Query( $args );
			$categorized_page_ids = array_merge( $categorized_page_ids, $query->posts );
		}
	}
	
	// Now get pages that are NOT in the categorized list
	$args = array(
		'post_type' => 'page',
		'posts_per_page' => -1,
		'post_parent' => 0,
		'orderby' => 'menu_order title',
		'order' => 'ASC',
		'post_status' => 'publish',
	);
	$args = apply_filters( 'docs_theme_pages_query_args', $args );
	if ( ! empty( $categorized_page_ids ) ) {
		$args['post__not_in'] = $categorized_page_ids;
	}
	$query = new WP_Query( $args );
	$uncategorized_pages = $query->posts;
	
	if ( ! empty( $uncategorized_pages ) ) : ?>
		<div class="page-category-section">
			<h4 class="category-title"><?php esc_html_e( 'Documentation', 'docs-theme' ); ?></h4>
			<ul class="pages-list">
				<?php echo render_page_tree( $uncategorized_pages, 0, $current_page_id ); ?>
			</ul>
		</div>
	<?php endif; ?>
</div>