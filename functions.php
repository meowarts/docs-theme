<?php
/**
 * Docs Theme functions and definitions
 *
 * @package DocsTheme
 * @since 1.0.0
 */

namespace DocsTheme;

// Theme constants.
define( 'DOCS_THEME_DIR', get_template_directory() );
define( 'DOCS_THEME_URI', get_template_directory_uri() );

// Get theme version from style.css
$style_css = file_get_contents( DOCS_THEME_DIR . '/style.css' );
if ( preg_match( '/Version:\s*(.+)/', $style_css, $matches ) ) {
    define( 'DOCS_THEME_VERSION', trim( $matches[1] ) );
} else {
    define( 'DOCS_THEME_VERSION', '1.0.0' ); // Fallback
}

// Load theme features
require_once DOCS_THEME_DIR . '/inc/theme-features.php';

/**
 * Theme setup.
 */
function setup() {
	// Add theme support.
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 40,
		'width'       => 200,
		'flex-width'  => true,
		'flex-height' => true,
	) );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Enqueue scripts and styles.
 */
function enqueue_scripts() {
	// Enqueue theme styles.
	wp_enqueue_style(
		'docs-theme-style',
		get_template_directory_uri() . '/style.min.css',
		array(),
		DOCS_THEME_VERSION
	);
	
	wp_enqueue_style(
		'docs-theme-navigation',
		DOCS_THEME_URI . '/assets/css/navigation.css',
		array( 'docs-theme-style' ),
		DOCS_THEME_VERSION
	);
	
	// Enqueue Inter and JetBrains Mono fonts.
	wp_enqueue_style(
		'docs-theme-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap',
		array(),
		null
	);
	
	// Enqueue theme scripts.
	wp_enqueue_script(
		'docs-theme-script',
		DOCS_THEME_URI . '/assets/js/theme.js',
		array(),
		DOCS_THEME_VERSION,
		true
	);
	
	// Enqueue search script
	wp_enqueue_script(
		'docs-theme-search',
		DOCS_THEME_URI . '/assets/js/search.js',
		array('docs-theme-script'),
		DOCS_THEME_VERSION,
		true
	);
	
	// Localize script with REST API URL
	wp_localize_script( 'docs-theme-script', 'docsTheme', array(
		'restUrl' => esc_url_raw( rest_url() ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
	) );
	
	// Also localize for search script
	wp_localize_script( 'docs-theme-search', 'docsTheme', array(
		'restUrl' => esc_url_raw( rest_url() ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
	) );
	
	// Highlight.js for syntax highlighting
	wp_enqueue_style(
		'highlight-css',
		'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css',
		array(),
		'11.9.0'
	);
	
	wp_enqueue_script(
		'highlight-core',
		'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
		array(),
		'11.9.0',
		true
	);
	
	// Add language support
	wp_enqueue_script(
		'highlight-php',
		'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js',
		array('highlight-core'),
		'11.9.0',
		true
	);
	
	wp_enqueue_script(
		'highlight-bash',
		'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/bash.min.js',
		array('highlight-core'),
		'11.9.0',
		true
	);
	
	// Initialize Highlight.js
	wp_add_inline_script('highlight-core', '
		document.addEventListener("DOMContentLoaded", function() {
			// Configure highlight.js
			hljs.configure({
				ignoreUnescapedHTML: true,
				languages: ["php", "javascript", "bash", "css", "html", "xml", "json", "sql", "shell"]
			});
			
			// Apply to all code blocks
			document.querySelectorAll(".wp-block-code code, .wp-code-block code, pre code").forEach(function(block) {
				hljs.highlightElement(block);
			});
		});
	');
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

/**
 * Register page categories taxonomy.
 */
function register_page_categories() {
	$labels = array(
		'name'              => _x( 'Categories', 'taxonomy general name', 'docs-theme' ),
		'singular_name'     => _x( 'Category', 'taxonomy singular name', 'docs-theme' ),
		'search_items'      => __( 'Search Categories', 'docs-theme' ),
		'all_items'         => __( 'All Categories', 'docs-theme' ),
		'parent_item'       => __( 'Parent Category', 'docs-theme' ),
		'parent_item_colon' => __( 'Parent Category:', 'docs-theme' ),
		'edit_item'         => __( 'Edit Category', 'docs-theme' ),
		'update_item'       => __( 'Update Category', 'docs-theme' ),
		'add_new_item'      => __( 'Add New Category', 'docs-theme' ),
		'new_item_name'     => __( 'New Category Name', 'docs-theme' ),
		'menu_name'         => __( 'Categories', 'docs-theme' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'page-category' ),
		'show_in_rest'      => true,
	);

	register_taxonomy( 'page_category', 'page', $args );
}
add_action( 'init', __NAMESPACE__ . '\\register_page_categories' );


/**
 * Register custom blocks.
 */
function register_blocks() {
	register_block_type( DOCS_THEME_DIR . '/blocks/table-of-contents' );
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

/**
 * Add page categories to REST API response
 */
function add_categories_to_pages_api() {
	register_rest_field( 'page', 'page_categories', array(
		'get_callback' => function( $post ) {
			$terms = wp_get_post_terms( $post['id'], 'page_category' );
			return wp_list_pluck( $terms, 'name' );
		},
		'schema' => array(
			'type' => 'array',
			'items' => array(
				'type' => 'string',
			),
		),
	) );
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\add_categories_to_pages_api' );

/**
 * Filter to add custom classes to navigation blocks.
 */
function add_nav_classes( $block_content, $block ) {
	if ( 'core/navigation' === $block['blockName'] ) {
		// Check if this navigation is in the sidebar.
		if ( strpos( $block_content, 'wp-block-navigation' ) !== false ) {
			// Add custom classes for styling.
			$block_content = str_replace(
				'wp-block-navigation',
				'wp-block-navigation docs-nav',
				$block_content
			);
		}
	}
	return $block_content;
}
add_filter( 'render_block', __NAMESPACE__ . '\\add_nav_classes', 10, 2 );

/**
 * Add current page class to navigation items.
 */
function add_current_page_class( $classes, $item ) {
	if ( is_page() && $item->object_id == get_queried_object_id() ) {
		$classes[] = 'current_page_item';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', __NAMESPACE__ . '\\add_current_page_class', 10, 2 );

/**
 * Modify the post content to add IDs to headings.
 */
function add_heading_ids( $content ) {
	if ( ! is_singular() ) {
		return $content;
	}
	
	// Pattern to match headings.
	$pattern = '/<h([2-4])(.*?)>(.*?)<\/h[2-4]>/i';
	
	// Callback to add IDs.
	$callback = function( $matches ) {
		$level = $matches[1];
		$attributes = $matches[2];
		$heading_text = $matches[3];
		
		// Check if ID already exists.
		if ( strpos( $attributes, 'id=' ) !== false ) {
			return $matches[0];
		}
		
		// Generate ID from heading text.
		$id = sanitize_title_with_dashes( wp_strip_all_tags( $heading_text ) );
		
		return sprintf(
			'<h%1$s%2$s id="%3$s">%4$s</h%1$s>',
			$level,
			$attributes,
			esc_attr( $id ),
			$heading_text
		);
	};
	
	return preg_replace_callback( $pattern, $callback, $content );
}
add_filter( 'the_content', __NAMESPACE__ . '\\add_heading_ids', 20 );

/**
 * Add theme support for wide and full alignments in the editor.
 */
function add_alignment_support() {
	add_theme_support( 'align-wide' );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\add_alignment_support' );

/**
 * Filter to replace template tags in footer.
 */
function replace_template_tags( $block_content, $block ) {
	if ( 'core/paragraph' === $block['blockName'] && strpos( $block_content, '{year}' ) !== false ) {
		$block_content = str_replace( '{year}', date( 'Y' ), $block_content );
		$block_content = str_replace( '{site_title}', get_bloginfo( 'name' ), $block_content );
	}
	return $block_content;
}
add_filter( 'render_block', __NAMESPACE__ . '\\replace_template_tags', 10, 2 );

/**
 * Add mobile menu functionality.
 */
function add_mobile_menu_button() {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const leftSidebar = document.querySelector('.docs-sidebar-left');
		if (!leftSidebar || window.innerWidth > 768) return;
		
		const button = document.createElement('button');
		button.className = 'mobile-menu-toggle';
		button.textContent = 'Menu';
		button.setAttribute('aria-expanded', 'false');
		
		leftSidebar.insertBefore(button, leftSidebar.firstChild);
		
		button.addEventListener('click', function() {
			const isExpanded = button.getAttribute('aria-expanded') === 'true';
			button.setAttribute('aria-expanded', !isExpanded);
			leftSidebar.classList.toggle('menu-open');
		});
	});
	</script>
	<?php
}
add_action( 'wp_footer', __NAMESPACE__ . '\\add_mobile_menu_button' );

/**
 * Custom excerpt length for documentation pages.
 */
function custom_excerpt_length( $length ) {
	if ( is_page() ) {
		return 30;
	}
	return $length;
}
add_filter( 'excerpt_length', __NAMESPACE__ . '\\custom_excerpt_length' );

/**
 * Remove version query strings from styles and scripts.
 */
function remove_version_query_strings( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
if ( ! is_admin() ) {
	add_filter( 'style_loader_src', __NAMESPACE__ . '\\remove_version_query_strings', 9999 );
	add_filter( 'script_loader_src', __NAMESPACE__ . '\\remove_version_query_strings', 9999 );
}