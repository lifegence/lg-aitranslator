<?php
/**
 * Admin AJAX Handlers
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle admin AJAX requests
 */
class LG_AITranslator_Admin_AJAX {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_lg_aitrans_test_gemini_key', array($this, 'test_gemini_key'));
        add_action('wp_ajax_lg_aitrans_test_openai_key', array($this, 'test_openai_key'));
        add_action('wp_ajax_lg_aitrans_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_lg_aitrans_increment_cache_version', array($this, 'increment_cache_version'));
        add_action('wp_ajax_lg_aitrans_update_translation', array($this, 'update_translation'));
    }

    /**
     * Test Gemini API key
     */
    public function test_gemini_key() {
        check_ajax_referer('lg_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lg-aitranslator')));
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Please enter an API key', 'lg-aitranslator')));
        }

        $key_manager = new LG_API_Key_Manager();
        $result = $key_manager->validate_gemini_key($api_key);

        if ($result['valid']) {
            wp_send_json_success(array('message' => __('API key is valid!', 'lg-aitranslator')));
        } else {
            wp_send_json_error(array('error' => $result['error']));
        }
    }

    /**
     * Test OpenAI API key
     */
    public function test_openai_key() {
        check_ajax_referer('lg_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lg-aitranslator')));
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Please enter an API key', 'lg-aitranslator')));
        }

        $key_manager = new LG_API_Key_Manager();
        $result = $key_manager->validate_openai_key($api_key);

        if ($result['valid']) {
            wp_send_json_success(array('message' => __('API key is valid!', 'lg-aitranslator')));
        } else {
            wp_send_json_error(array('error' => $result['error']));
        }
    }

    /**
     * Clear translation cache
     */
    public function clear_cache() {
        check_ajax_referer('lg_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lg-aitranslator')));
        }

        $cache = new LG_Translation_Cache();
        $result = $cache->clear_all();

        if ($result) {
            wp_send_json_success(array('message' => __('Cache cleared successfully!', 'lg-aitranslator')));
        } else {
            wp_send_json_error(array('error' => __('Failed to clear cache', 'lg-aitranslator')));
        }
    }

    /**
     * Increment cache version to invalidate all existing translations
     */
    public function increment_cache_version() {
        check_ajax_referer('lg_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lg-aitranslator')));
        }

        $current_version = get_option('lg_aitranslator_cache_version', 1);
        $new_version = $current_version + 1;

        $result = update_option('lg_aitranslator_cache_version', $new_version);

        if ($result || $current_version === $new_version) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Cache version incremented from %d to %d. All translations will be refreshed.', 'lg-aitranslator'),
                    $current_version,
                    $new_version
                )
            ));
        } else {
            wp_send_json_error(array('error' => __('Failed to increment cache version', 'lg-aitranslator')));
        }
    }

    /**
     * Update translation cache (for inline editing)
     */
    public function update_translation() {
        check_ajax_referer('lg_aitranslator_frontend', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lg-aitranslator')));
        }

        $cache_key = sanitize_text_field($_POST['cache_key'] ?? '');
        $translation = wp_kses_post($_POST['translation'] ?? '');

        if (empty($cache_key)) {
            wp_send_json_error(array('error' => __('Cache key is required', 'lg-aitranslator')));
        }

        if (empty($translation)) {
            wp_send_json_error(array('error' => __('Translation text is required', 'lg-aitranslator')));
        }

        // Update cache using WordPress transient
        $cache = new LG_Translation_Cache();
        $result = $cache->set($cache_key, $translation);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Translation updated successfully', 'lg-aitranslator'),
                'cache_key' => $cache_key
            ));
        } else {
            wp_send_json_error(array('error' => __('Failed to update translation cache', 'lg-aitranslator')));
        }
    }
}

// Initialize AJAX handlers
new LG_AITranslator_Admin_AJAX();
