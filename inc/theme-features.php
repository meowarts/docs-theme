<?php
/**
 * Theme Features Loader
 * 
 * This file loads all the modular features for the Docs Theme
 */

// Load individual features
require_once get_template_directory() . '/inc/features/page-ordering.php';
require_once get_template_directory() . '/inc/features/category-ordering.php';
require_once get_template_directory() . '/inc/features/disable-comments.php';
require_once get_template_directory() . '/inc/features/page-subtitles.php';
require_once get_template_directory() . '/inc/features/admin-menu-order.php';
require_once get_template_directory() . '/inc/features/hide-author-columns.php';
require_once get_template_directory() . '/inc/features/seo-enhancements.php';
require_once get_template_directory() . '/inc/features/font-options.php';
require_once get_template_directory() . '/inc/features/block-styles.php';
require_once get_template_directory() . '/inc/features/page-excerpts.php';
require_once get_template_directory() . '/inc/features/page-emoticons.php';
require_once get_template_directory() . '/inc/features/admin-category-menu.php';