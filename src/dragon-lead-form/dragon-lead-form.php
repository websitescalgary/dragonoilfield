<?php
/**
 * Plugin Name: Dragon Lead Form
 * Description: Inquiry form, saved submissions, and email notifications for Dragon Oilfield Supply.
 * Version: 1.2.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: dragon-lead-form
 */
if (!defined('ABSPATH')) { exit; }

add_action('init', function () {
    register_post_type('dragon_inquiry', [
        'labels' => ['name'=>'Dragon Inquiries', 'singular_name'=>'Dragon Inquiry', 'menu_name'=>'Dragon Inquiries'],
        'public'=>false, 'show_ui'=>true, 'show_in_menu'=>true, 'exclude_from_search'=>true,
        'supports'=>['title','editor'], 'menu_icon'=>'dashicons-email-alt',
        'capability_type'=>'post', 'map_meta_cap'=>true,
        'capabilities'=>['edit_posts'=>'manage_options','edit_others_posts'=>'manage_options','publish_posts'=>'manage_options','read_private_posts'=>'manage_options','delete_posts'=>'manage_options','delete_others_posts'=>'manage_options','edit_private_posts'=>'manage_options','delete_private_posts'=>'manage_options','create_posts'=>'do_not_allow'],
    ]);
});

add_action('admin_menu', function() {
    add_options_page('Dragon Contact Form','Dragon Contact Form','manage_options','dragon-contact-form','dragon_form_settings_page');
});
add_action('admin_init', function() {
    register_setting('dragon_form_settings', 'dragon_form_email', [
        'type'=>'string', 'sanitize_callback'=>'sanitize_email', 'default'=>'billy@dragonoilfield.com',
    ]);
});
function dragon_form_settings_page() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="wrap"><h1>Dragon Contact Form</h1><p>Submissions are saved under <strong>Dragon Inquiries</strong> even if an email fails. Configure authenticated SMTP separately and send a real test inquiry after launch.</p><form method="post" action="options.php">';
    settings_fields('dragon_form_settings');
    echo '<table class="form-table"><tr><th scope="row"><label for="dragon_form_email">Notification email</label></th><td><input type="email" id="dragon_form_email" name="dragon_form_email" class="regular-text" value="'.esc_attr(get_option('dragon_form_email', 'billy@dragonoilfield.com')).'" required /></td></tr></table>';
    submit_button('Save Settings'); echo '</form></div>';
}

add_shortcode('dragon_inquiry_form', function () {
    $status = isset($_GET['dragon_form']) ? sanitize_key(wp_unslash($_GET['dragon_form'])) : '';
    ob_start();
    if ($status === 'success') echo '<p class="dragon-form-message success" role="status">Thank you. Your enquiry has been received. We will get back to you soon.</p>';
    if ($status === 'error') echo '<p class="dragon-form-message" role="alert">We could not process your enquiry. Please check your name, phone or email, and enquiry, or email billy@dragonoilfield.com.</p>';
    ?>
    <form class="dragon-lead-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="dragon_send_inquiry">
      <?php wp_nonce_field('dragon_send_inquiry', 'dragon_form_nonce'); ?>
      <div class="dragon-form-hp" aria-hidden="true"><label for="dragon_website">Leave this field blank</label><input tabindex="-1" id="dragon_website" type="text" name="dragon_website" autocomplete="off"></div>
      <div class="dragon-form-grid">
        <label class="dragon-form-field full">Your name *<input name="dragon_name" required maxlength="120" autocomplete="name"></label>
        <label class="dragon-form-field">Phone number<input name="dragon_phone" type="tel" maxlength="40" autocomplete="tel" placeholder="403-555-1234"></label>
        <label class="dragon-form-field">Email address<input name="dragon_email" type="email" maxlength="200" autocomplete="email" placeholder="name@company.com"></label>
        <label class="dragon-form-field full">Your enquiry *<textarea name="dragon_message" required maxlength="5000" placeholder="How can we help?"></textarea></label>
      </div>
      <p class="dragon-contact-method-help">Please provide at least one way to contact you: phone number or email address.</p>
      <button class="dragon-form-submit" type="submit">Send Enquiry</button>
    </form>
    <?php return ob_get_clean();
});

add_action('admin_post_nopriv_dragon_send_inquiry','dragon_process_inquiry');
add_action('admin_post_dragon_send_inquiry','dragon_process_inquiry');
function dragon_form_redirect($result) {
    $url = add_query_arg('dragon_form', $result, home_url('/'));
    wp_safe_redirect($url . '#contact', 303);
    exit;
}
function dragon_process_inquiry() {
    if (!isset($_POST['dragon_form_nonce']) || !is_string($_POST['dragon_form_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dragon_form_nonce'])), 'dragon_send_inquiry')) dragon_form_redirect('error');
    if (!empty($_POST['dragon_website'])) dragon_form_redirect('success'); // honeypot: accept without sending
    $v = static function($key, $limit=5000) {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) return '';
        return function_exists('mb_substr') ? mb_substr(sanitize_text_field(wp_unslash($_POST[$key])),0,$limit) : substr(sanitize_text_field(wp_unslash($_POST[$key])),0,$limit);
    };
    $name = $v('dragon_name',120);
    $phone = $v('dragon_phone',40);
    $email_raw = $v('dragon_email',200);
    $email = sanitize_email($email_raw);
    $message = '';
    if (isset($_POST['dragon_message']) && is_string($_POST['dragon_message'])) {
        $raw_message = sanitize_textarea_field(wp_unslash($_POST['dragon_message']));
        $message = function_exists('mb_substr') ? mb_substr($raw_message,0,5000) : substr($raw_message,0,5000);
    }
    // At least one contact method; validate every supplied contact method.
    $digit_count = preg_match_all('/[0-9]/', $phone);
    $valid_phone = (bool) preg_match('/^\+?[0-9(). \-]+$/D', $phone)
        && $digit_count >= 7 && $digit_count <= 15 && strlen($phone) <= 40;
    if (strlen($name) < 2 || strlen($message) < 1 || (!$phone && !$email) ||
        ($phone && !$valid_phone) || ($email_raw && (!$email || $email_raw !== $email || !is_email($email)))) dragon_form_redirect('error');
    $ip=isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key='dragon_lead_'.substr(hash_hmac('sha256',$ip,wp_salt('nonce')),0,28);
    if (get_transient($key)) dragon_form_redirect('error');
    set_transient($key,1,45);
    $details = "Name: $name\nPhone: ".($phone ?: "Not provided")."\nEmail: ".($email ?: "Not provided")."\n\nEnquiry:\n$message";
    $post_id = wp_insert_post([
      'post_type'=>'dragon_inquiry', 'post_status'=>'private',
      'post_title'=>wp_strip_all_tags('Enquiry: '.$name),
      'post_content'=>$details,
    ], true);
    if (is_wp_error($post_id) || !$post_id) dragon_form_redirect('error');
    $to=get_option('dragon_form_email','billy@dragonoilfield.com');
    if (!is_email($to)) $to='billy@dragonoilfield.com';
    $headers=['Content-Type: text/plain; charset=UTF-8'];
    if ($email) $headers[]='Reply-To: '.$email;
    $sent=wp_mail($to, '[Dragon Oilfield] New Website Enquiry', $details, $headers);
    update_post_meta($post_id,'dragon_email_attempt', $sent ? 'wp_mail_accepted' : 'wp_mail_failed');
    dragon_form_redirect('success');
}
