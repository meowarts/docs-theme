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