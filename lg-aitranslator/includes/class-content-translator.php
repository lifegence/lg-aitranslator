<?php
/**
 * Content Translator
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translate WordPress content using AI
 */
class LG_Content_Translator {

    /**
     * URL Rewriter instance
     */
    private $url_rewriter;

    /**
     * Translation service
     */
    private $translation_service;

    /**
     * Cache instance
     */
    private $cache;

    /**
     * Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->url_rewriter = new LG_URL_Rewriter();
        $this->cache = new LG_Translation_Cache();
        $this->settings = get_option('lg_aitranslator_settings', array());

        $this->init_translation_service();
        $this->init_hooks();
    }

    /**
     * Initialize translation service
     */
    private function init_translation_service() {
        $this->translation_service = LG_Translation_Service_Factory::create();
    }

    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        // Only hook if translation is enabled
        if (empty($this->settings['enabled'])) {
            return;
        }

        // Content filters - use high priority to run after other plugins
        add_filter('the_title', array($this, 'translate_title'), 999, 2);
        add_filter('the_content', array($this, 'translate_content'), 999);
        add_filter('the_excerpt', array($this, 'translate_excerpt'), 999, 2);

        // Widget filters
        add_filter('widget_title', array($this, 'translate_widget_title'), 999, 3);
        add_filter('widget_text', array($this, 'translate_widget_text'), 999, 3);

        // Menu filters
        add_filter('wp_nav_menu_items', array($this, 'translate_menu_items'), 999, 2);

        // Category/Tag names
        add_filter('single_cat_title', array($this, 'translate_term_name'), 999);
        add_filter('single_tag_title', array($this, 'translate_term_name'), 999);

        // SEO hooks
        add_action('wp_head', array($this, 'output_hreflang_tags'));
        add_filter('language_attributes', array($this, 'filter_language_attributes'));
    }

    /**
     * Translate post/page title
     */
    public function translate_title($title, $post_id = 0) {
        // Skip empty titles
        if (empty($title)) {
            return $title;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $title;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('post_title', $post_id, $current_lang, $title);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($title, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate post/page content
     */
    public function translate_content($content) {
        // Skip empty content
        if (empty($content)) {
            return $content;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $content;
        }

        // Get post ID
        $post_id = get_the_ID();

        // Generate cache key
        $cache_key = $this->generate_cache_key('post_content', $post_id, $current_lang, $content);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate (preserve HTML)
        $translated = $this->translate_html($content, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate post excerpt
     */
    public function translate_excerpt($excerpt, $post_id = 0) {
        // Skip empty excerpts
        if (empty($excerpt)) {
            return $excerpt;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $excerpt;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('post_excerpt', $post_id, $current_lang, $excerpt);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($excerpt, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate widget title
     */
    public function translate_widget_title($title, $instance = array(), $widget_id = '') {
        // Skip empty titles
        if (empty($title)) {
            return $title;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $title;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('widget_title', $widget_id, $current_lang, $title);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($title, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate widget text
     */
    public function translate_widget_text($text, $instance = array(), $widget_id = '') {
        // Skip empty text
        if (empty($text)) {
            return $text;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $text;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('widget_text', $widget_id, $current_lang, $text);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate (preserve HTML if present)
        $translated = $this->translate_html($text, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate menu items
     */
    public function translate_menu_items($items, $args) {
        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $items;
        }

        // Translate menu item text
        $items = preg_replace_callback('/>([^<]+)<\/a>/', function($matches) use ($current_lang) {
            $text = $matches[1];

            // Generate cache key
            $cache_key = $this->generate_cache_key('menu_item', 0, $current_lang, $text);

            // Check cache
            $cached = $this->cache->get($cache_key);
            if ($cached !== false) {
                return '>' . $cached . '</a>';
            }

            // Translate
            $translated = $this->translate_text($text, $current_lang);

            // Cache result
            $this->cache->set($cache_key, $translated);

            return '>' . $translated . '</a>';
        }, $items);

        return $items;
    }

    /**
     * Translate term name
     */
    public function translate_term_name($name) {
        // Skip empty names
        if (empty($name)) {
            return $name;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $name;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('term_name', 0, $current_lang, $name);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($name, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate plain text
     */
    private function translate_text($text, $target_lang) {
        try {
            $default_lang = $this->url_rewriter->get_default_language();
            return $this->translation_service->translate_text($text, $default_lang, $target_lang);
        } catch (Exception $e) {
            error_log('[LG AI Translator] Translation failed: ' . $e->getMessage());
            return $text; // Return original on error
        }
    }

    /**
     * Translate HTML content (preserves structure)
     */
    private function translate_html($html, $target_lang) {
        try {
            $default_lang = $this->url_rewriter->get_default_language();
            return $this->translation_service->translate_html($html, $default_lang, $target_lang);
        } catch (Exception $e) {
            error_log('[LG AI Translator] HTML translation failed: ' . $e->getMessage());
            return $html; // Return original on error
        }
    }

    /**
     * Generate cache key
     */
    private function generate_cache_key($type, $id, $lang, $content) {
        $hash = md5($content);
        return "lg_aitrans_{$type}_{$id}_{$lang}_{$hash}";
    }

    /**
     * Output hreflang tags for SEO
     */
    public function output_hreflang_tags() {
        $supported_languages = $this->settings['supported_languages'] ?? array();
        $default_lang = $this->url_rewriter->get_default_language();

        foreach ($supported_languages as $lang) {
            $url = $this->url_rewriter->get_language_url($lang);
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '" />' . "\n";
        }

        // x-default for default language
        $default_url = $this->url_rewriter->get_language_url($default_lang);
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
    }

    /**
     * Filter HTML language attribute
     */
    public function filter_language_attributes($output) {
        $current_lang = $this->url_rewriter->get_current_language();

        // Convert language code to locale format
        $locale_map = array(
            'en' => 'en_US',
            'ja' => 'ja_JP',
            'zh-CN' => 'zh_CN',
            'zh-TW' => 'zh_TW',
            'ko' => 'ko_KR',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
            'pt' => 'pt_PT',
            'ru' => 'ru_RU',
            'ar' => 'ar',
            'hi' => 'hi_IN',
            'th' => 'th_TH',
            'vi' => 'vi_VN',
            'id' => 'id_ID',
            'tr' => 'tr_TR',
            'pl' => 'pl_PL',
            'nl' => 'nl_NL',
            'sv' => 'sv_SE'
        );

        $locale = $locale_map[$current_lang] ?? $current_lang;

        return str_replace('lang="en-US"', 'lang="' . esc_attr($current_lang) . '"', $output);
    }
}
