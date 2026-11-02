<?php
/**
 * Plugin Name: Dragon Site Setup & Photo Import
 * Description: One-time import of approved Dragon photos into the WordPress Media Library and creation of an editable homepage using the installed theme.
 * Version: 1.0.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_management_page('Dragon Site Setup','Dragon Site Setup','manage_options','dragon-site-setup','dragon_site_setup_page');
});

function dragon_site_setup_page() {
    if (!current_user_can('manage_options')) return;
    $feedback = get_transient('dragon_setup_status_'.get_current_user_id());
    if ($feedback) { delete_transient('dragon_setup_status_'.get_current_user_id()); }
    echo '<div class="wrap"><h1>Dragon Site Setup</h1>';
    echo '<p>Imports the actual images from Dragon Oilfield&#8217;s existing public website to <strong>this site&#8217;s WordPress Media Library</strong>. It then creates an editable Home page with local image URLs. No external image hotlinks will remain in the created page.</p>';
    echo '<p><strong>Before running:</strong> make sure the Dragon Oilfield V4 theme is active. The hosting server must be able to access dragonoilfield.com. This does not touch DNS, email records, or your old website.</p>';
    if (is_array($feedback)) {
        $klass = empty($feedback['errors']) ? 'notice-success' : 'notice-warning';
        echo '<div class="notice '.esc_attr($klass).'"><p>'.esc_html($feedback['message']).'</p>';
        foreach ((array)($feedback['errors'] ?? []) as $error) echo '<p>'.esc_html($error).'</p>';
        echo '</div>';
    }
    $page_id = absint(get_option('dragon_homepage_id'));
    if ($page_id && get_post($page_id)) {
        echo '<p>Existing editable Dragon homepage: <a href="'.esc_url(get_edit_post_link($page_id)).'">Edit Home in WordPress</a>. This tool will not overwrite its content.</p>';
    }
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="dragon_run_site_setup">';
    wp_nonce_field('dragon_run_site_setup');
    submit_button('Import original photos and set up editable Home page');
    echo '</form></div>';
}
add_action('admin_post_dragon_run_site_setup','dragon_run_site_setup');

function dragon_setup_feedback($message, $errors = []) {
    set_transient('dragon_setup_status_'.get_current_user_id(), ['message'=>$message,'errors'=>$errors], 120);
    wp_safe_redirect(admin_url('tools.php?page=dragon-site-setup'));
    exit;
}
function dragon_run_site_setup() {
    if (!current_user_can('manage_options')) wp_die('You do not have permission to import media.');
    check_admin_referer('dragon_run_site_setup');
    if (get_stylesheet() !== 'dragon-oilfield-theme') {
        dragon_setup_feedback('Activate the Dragon Oilfield V4 theme before importing photos.', ['The current active theme is different.']);
    }
    $home_id = absint(get_option('dragon_homepage_id'));
    if ($home_id && get_post($home_id)) {
        dragon_setup_feedback('Existing Home page preserved. Edit it through Pages → Home → Edit.');
    }
    $pattern = get_stylesheet_directory().'/patterns/landing.php';
    if (!is_readable($pattern)) dragon_setup_feedback('Homepage pattern file is missing.');
    $source = file_get_contents($pattern);
    $split = strpos($source, '?>');
    if ($split === false) dragon_setup_feedback('Could not parse the homepage pattern.');
    $source = substr($source, $split + 2);
    preg_match_all('~https://dragonoilfield\\.com/wp-content/uploads/[a-zA-Z0-9/_\\-]+\\.jpe?g~', $source, $matches);
    $urls = array_values(array_unique($matches[0]));
    if (!$urls) dragon_setup_feedback('No original media links were found in the theme pattern.');

    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';
    $imported = 0; $errors = []; $replacements = []; $attachment_ids = [];
    foreach ($urls as $url) {
        $existing = get_posts([
            'post_type'=>'attachment', 'post_status'=>'inherit', 'posts_per_page'=>1,
            'meta_key'=>'_dragon_original_source', 'meta_value'=>$url, 'fields'=>'ids',
        ]);
        $image_id = $existing ? absint($existing[0]) : 0;
        if ($image_id && (!wp_attachment_is_image($image_id) || !is_file(get_attached_file($image_id)))) {
            $image_id = 0; // Recover from deleted files on a partial-import retry.
        }
        if (!$image_id) {
            $temp = download_url($url, 50);
            if (is_wp_error($temp)) { $errors[] = 'Could not download '.basename($url).': '.$temp->get_error_message(); continue; }
            $fileinfo = @getimagesize($temp);
            $size = @filesize($temp);
            if (!$fileinfo || !in_array($fileinfo['mime'],['image/jpeg','image/png'],true) || !$size || $size > 16000000) {
                @unlink($temp); $errors[] = 'Invalid image or file too large: '.basename($url); continue;
            }
            $image_id = media_handle_sideload(['name'=>basename(parse_url($url, PHP_URL_PATH)), 'tmp_name'=>$temp], 0);
            if (is_wp_error($image_id)) { @unlink($temp); $errors[] = 'Could not import '.basename($url).': '.$image_id->get_error_message(); continue; }
            update_post_meta($image_id, '_dragon_original_source', $url);
            update_post_meta($image_id, '_wp_attachment_image_alt', 'Original Dragon Oilfield equipment photograph');
            $imported++;
        }
        $new_url = wp_get_attachment_url($image_id);
        $uploads = wp_upload_dir();
        if (!$new_url || strpos($new_url, trailingslashit($uploads['baseurl'])) !== 0 || !is_file(get_attached_file($image_id))) { $errors[]='Missing local media URL for '.basename($url); continue; }
        $replacements[$url] = $new_url;
        $attachment_ids[$new_url] = $image_id;
    }
    if ($errors || count($replacements) !== count($urls)) {
        dragon_setup_feedback(count($replacements).' of '.count($urls).' originals available in the Media Library; homepage not created until all imports succeed. Re-run the import after fixing the download problem.', $errors);
    }
    $content = str_replace(array_keys($replacements), array_values($replacements), $source);
    if (strpos($content,'https://dragonoilfield.com/wp-content/uploads/') !== false) {
        dragon_setup_feedback('Media import finished but a remote image reference remains; homepage has not been created.');
    }

    $content = dragon_setup_attach_blocks($content, $attachment_ids);
    $home_id = absint(get_option('dragon_homepage_id'));
    if ($home_id && get_post($home_id)) {
        // Safeguard: do not overwrite customer edits on repeated runs.
        dragon_setup_feedback('Media imported. Existing Home page was preserved without overwriting your edits.');
    }
    $home_id = wp_insert_post([
        'post_type'=>'page', 'post_status'=>'publish', 'post_title'=>'Home',
        'post_name'=>'home', 'post_content'=>$content,
    ], true);
    if (is_wp_error($home_id) || !$home_id) dragon_setup_feedback('Images imported, but could not create the homepage.');
    update_option('dragon_homepage_id', $home_id);
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home_id);
    dragon_setup_feedback(count($replacements).' original photos imported or reused. Editable Home page created and set as homepage. Open Pages → Home → Edit, then test every image and form.');
}

/** Keep image and cover blocks connected to real Media Library attachments. */
function dragon_setup_attach_blocks($content, $ids) {
    $walk = function ($blocks) use (&$walk, $ids) {
        foreach ($blocks as &$block) {
            if (in_array($block['blockName'], ['core/image', 'core/cover'], true)) {
                $url = $block['attrs']['url'] ?? '';
                if (isset($ids[$url])) {
                    $id = $ids[$url];
                    $block['attrs']['id'] = $id;
                    $replace = static function ($html) use ($id) {
                        return preg_replace_callback('/<img\b([^>]*)>/', static function ($m) use ($id) {
                            $attrs = preg_replace('/\sclass="([^"]*)"/', ' class="$1 wp-image-'.$id.'"', rtrim($m[1], '/ '), 1, $count);
                            if (!$count) $attrs .= ' class="wp-image-'.$id.'"';
                            return '<img'.$attrs.'/>';
                        }, $html);
                    };
                    $block['innerHTML'] = $replace($block['innerHTML']);
                    foreach ($block['innerContent'] as &$chunk) if (is_string($chunk)) $chunk = $replace($chunk);
                    unset($chunk);
                }
            }
            $block['innerBlocks'] = $walk($block['innerBlocks']);
        }
        return $blocks;
    };
    return serialize_blocks($walk(parse_blocks($content)));
}
