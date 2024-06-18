<?php
/*
Plugin Name: Instant Indexing Tool
Description: Instantly index URLs using the Google Indexing API.
Version: 1.0
Author: Noah
*/

add_action('admin_menu', 'iit_add_admin_menu');
add_action('admin_init', 'iit_settings_init');

function iit_add_admin_menu() {
    add_menu_page('Instant Indexing Tool', 'Instant Indexing Tool', 'manage_options', 'instant_indexing_tool', 'iit_options_page');
}

function iit_settings_init() {
    register_setting('pluginPage', 'iit_settings');

    add_settings_section(
        'iit_pluginPage_section', 
        __('', 'wordpress'), 
        'iit_settings_section_callback', 
        'pluginPage'
    );

    add_settings_field(
        'iit_textarea_field', 
        __('URLs to Index', 'wordpress'), 
        'iit_textarea_field_render', 
        'pluginPage', 
        'iit_pluginPage_section'
    );

    add_settings_field(
        'iit_api_key_field', 
        __('Google API Key', 'wordpress'), 
        'iit_api_key_field_render', 
        'pluginPage', 
        'iit_pluginPage_section'
    );
}

function iit_textarea_field_render() {
    $options = get_option('iit_settings');
    ?>
    <textarea cols="40" rows="10" name="iit_settings[iit_textarea_field]"><?php echo $options['iit_textarea_field']; ?></textarea>
    <?php
}

function iit_api_key_field_render() {
    $options = get_option('iit_settings');
    ?>
    <input type="text" name="iit_settings[iit_api_key_field]" value="<?php echo $options['iit_api_key_field']; ?>" />
    <?php
}

function iit_settings_section_callback() {
    echo __('Insert URLs to send to the Google Indexing API (one per line, up to 10,000):', 'wordpress');
}

function iit_options_page() {
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <h2>Instant Indexing Tool</h2>
        <?php
        settings_fields('pluginPage');
        do_settings_sections('pluginPage');
        wp_nonce_field('iit_submit_urls');
        ?>
        <input type="hidden" name="action" value="iit_submit_urls">
        <?php submit_button('Submit URLs'); ?>
    </form>
    <?php
}

add_action('admin_post_iit_submit_urls', 'iit_submit_urls');

function iit_submit_urls() {
    if (!current_user_can('manage_options')) {
        return;
    }

    check_admin_referer('iit_submit_urls');

    $urls = sanitize_textarea_field($_POST['iit_settings']['iit_textarea_field']);
    $api_key = sanitize_text_field($_POST['iit_settings']['iit_api_key_field']);
    $url_array = explode(PHP_EOL, $urls);

    foreach ($url_array as $url) {
        $url = trim($url);
        if (!empty($url)) {
            $response = wp_remote_post('https://indexing.googleapis.com/v3/urlNotifications:publish?key=AIzaSyCJv2VRi3CAfCRD-mHOJ24Jg2QhbBEQfdg' . $api_key, array(
                'body' => json_encode(array('url' => $url, 'type' => 'URL_UPDATED')),
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
            ));

            if (is_wp_error($response)) {
                error_log('Error indexing URL ' . $url . ': ' . $response->get_error_message());
            } else {
                error_log('Successfully indexed URL ' . $url);
            }
        }
    }

    wp_redirect(admin_url('admin.php?page=instant_indexing_tool&status=success'));
    exit;
}
