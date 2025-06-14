<?php
/**
 * Table of Contents block render callback.
 *
 * @package DocsTheme
 */

// Get the current post content.
$post = get_post();
if ( ! $post || ! $post->post_content ) {
	return '';
}

// Parse the content for headings.
$headings = array();
$content = $post->post_content;

// Extract block attributes.
$show_h2 = isset( $attributes['showH2'] ) ? $attributes['showH2'] : true;
$show_h3 = isset( $attributes['showH3'] ) ? $attributes['showH3'] : true;
$show_h4 = isset( $attributes['showH4'] ) ? $attributes['showH4'] : false;

// Build regex pattern based on settings.
$heading_levels = array();
if ( $show_h2 ) {
	$heading_levels[] = '2';
}
if ( $show_h3 ) {
	$heading_levels[] = '3';
}
if ( $show_h4 ) {
	$heading_levels[] = '4';
}

if ( empty( $heading_levels ) ) {
	return '';
}

$heading_pattern = '<h([' . implode( '', $heading_levels ) . '])[^>]*>(.*?)<\/h\1>';
preg_match_all( '/' . $heading_pattern . '/i', $content, $matches, PREG_SET_ORDER );

if ( empty( $matches ) ) {
	return '<div class="wp-block-docs-theme-table-of-contents">No headings found</div>';
}

// Build the TOC structure.
$toc_items = array();
foreach ( $matches as $match ) {
	$level = $match[1];
	$heading_text = wp_strip_all_tags( $match[2] );
	$anchor = sanitize_title_with_dashes( $heading_text );
	
	$toc_items[] = array(
		'level' => $level,
		'text' => $heading_text,
		'anchor' => $anchor,
	);
}

// Generate the HTML.
$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'docs-toc',
) );

$output = '<nav ' . $wrapper_attributes . '>';
$output .= '<ul class="docs-toc__list">';

$current_level = 0;
foreach ( $toc_items as $item ) {
	$level = intval( $item['level'] );
	
	// Close previous levels if needed.
	while ( $current_level > $level ) {
		$output .= '</ul></li>';
		$current_level--;
	}
	
	// Open new levels if needed.
	while ( $current_level < $level - 1 ) {
		$output .= '<li><ul class="docs-toc__list docs-toc__list--nested">';
		$current_level++;
	}
	
	if ( $current_level < $level ) {
		$output .= '<ul class="docs-toc__list docs-toc__list--nested">';
		$current_level = $level;
	}
	
	$output .= sprintf(
		'<li class="docs-toc__item docs-toc__item--level-%d"><a href="#%s" class="docs-toc__link">%s</a>',
		$level,
		esc_attr( $item['anchor'] ),
		esc_html( $item['text'] )
	);
	
	// Look ahead to see if we need to close this item.
	$next_index = array_search( $item, $toc_items ) + 1;
	if ( isset( $toc_items[ $next_index ] ) && $toc_items[ $next_index ]['level'] <= $level ) {
		$output .= '</li>';
	}
}

// Close any remaining open tags.
while ( $current_level > 0 ) {
	$output .= '</ul></li>';
	$current_level--;
}

$output .= '</ul>';
$output .= '</nav>';

return $output;