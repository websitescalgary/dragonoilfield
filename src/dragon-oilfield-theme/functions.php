<?php
/** Theme assets and editor setup. */
if (!defined('ABSPATH')) { exit; }
function dragon_theme_assets() {
    wp_enqueue_style('dragon-site', get_theme_file_uri('assets/css/site.css'), [], wp_get_theme()->get('Version'));
    wp_enqueue_script('dragon-site', get_theme_file_uri('assets/js/site.js'), [], wp_get_theme()->get('Version'), true);
}
add_action('wp_enqueue_scripts', 'dragon_theme_assets');
function dragon_theme_setup() {
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('assets/css/site.css');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'dragon_theme_setup');

/** Resolve bundled logo against the actual theme URL, including subdirectory installs. */
add_filter('render_block_core/image', function ($html) {
    return str_replace('/wp-content/themes/dragon-oilfield-theme/assets/images/dragon-emblem.png', esc_url(get_theme_file_uri('assets/images/dragon-emblem.png')), $html);
});
add_filter('render_block', function ($html) {
    if (strpos($html, 'dragon-brand') !== false) {
        $html = str_replace('href="/"', 'href="'.esc_url(home_url('/')).'"', $html);
    }
    return $html;
});

add_filter('render_block', function ($html) {
    if (!is_front_page()) {
        $html = preg_replace_callback('/href="#([a-zA-Z0-9_-]+)"/', function ($m) {
            return 'href="'.esc_url(home_url('/').'#'.$m[1]).'"';
        }, $html);
    }
    return $html;
});
