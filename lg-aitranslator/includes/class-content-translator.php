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

        // HTML output buffering for full page translation
        add_action('template_redirect', array($this, 'start_output_buffer'), 1);

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
     * Start output buffering for full page translation
     */
    public function start_output_buffer() {
        // Skip for admin pages
        if (is_admin()) {
            return;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();

        // Debug: Log language detection
        error_log('LG_Content_Translator::start_output_buffer - Current lang: ' . $current_lang);
        error_log('LG_Content_Translator::start_output_buffer - Global var: ' . (isset($GLOBALS['lg_aitranslator_current_lang']) ? $GLOBALS['lg_aitranslator_current_lang'] : 'NOT SET'));
        error_log('LG_Content_Translator::start_output_buffer - REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
        error_log('LG_Content_Translator::start_output_buffer - Query var lang: ' . get_query_var('lang'));
        $default_lang = $this->url_rewriter->get_default_language();

        // Only buffer if not default language
        if ($current_lang !== $default_lang) {
            ob_start(array($this, 'translate_html_output'));
        }
    }

    /**
     * Translate entire HTML output
     */
    public function translate_html_output($html) {
        // Skip empty output
        if (empty($html)) {
            return $html;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $html;
        }

        // Generate cache key for entire page
        $cache_key = 'page_' . md5($html) . '_' . $current_lang;

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate text nodes in HTML
        $translated_html = $this->translate_html_text_nodes($html, $current_lang);

        // Add language prefix to internal links
        $translated_html = $this->add_language_prefix_to_links($translated_html, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated_html);

        return $translated_html;
    }

    /**
     * Add language prefix to all internal links in HTML
     */
    private function add_language_prefix_to_links($html, $current_lang) {
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $html;
        }

        // Get site URL
        $site_url = home_url();
        $site_domain = parse_url($site_url, PHP_URL_HOST);

        // Pattern to match href attributes
        $pattern = '/href=["\']([^"\']+)["\']/i';

        $html = preg_replace_callback($pattern, function($matches) use ($current_lang, $site_url, $site_domain) {
            $url = $matches[1];

            // Skip external links
            if (strpos($url, 'http') === 0 && strpos($url, $site_domain) === false) {
                return $matches[0];
            }

            // Skip anchors, mailto, tel, javascript
            if (strpos($url, '#') === 0 ||
                strpos($url, 'mailto:') === 0 ||
                strpos($url, 'tel:') === 0 ||
                strpos($url, 'javascript:') === 0) {
                return $matches[0];
            }

            // Skip admin URLs
            if (strpos($url, '/wp-admin') !== false ||
                strpos($url, '/wp-content') !== false ||
                strpos($url, '/wp-includes') !== false) {
                return $matches[0];
            }

            // Check if URL already has language prefix
            $supported_languages = $this->settings['supported_languages'] ?? array();
            $lang_pattern = '/^\/(' . implode('|', array_map('preg_quote', $supported_languages)) . ')\//';

            if (preg_match($lang_pattern, $url)) {
                return $matches[0]; // Already has language prefix
            }

            // Add language prefix to relative URLs
            if (strpos($url, '/') === 0) {
                $new_url = '/' . $current_lang . $url;
                return 'href="' . $new_url . '"';
            }

            // Add language prefix to absolute site URLs
            if (strpos($url, $site_url) === 0) {
                $path = str_replace($site_url, '', $url);
                $new_url = $site_url . '/' . $current_lang . $path;
                return 'href="' . $new_url . '"';
            }

            // Return unchanged for other cases
            return $matches[0];
        }, $html);

        return $html;
    }

    /**
     * Translate text nodes in HTML while preserving structure
     */
    private function translate_html_text_nodes($html, $target_lang) {
        // Don't translate if HTML is too small (likely API response)
        if (strlen($html) < 100) {
            return $html;
        }

        error_log('[LG AI Translator] Starting HTML translation for language: ' . $target_lang);
        error_log('[LG AI Translator] HTML length: ' . strlen($html) . ' bytes');

        // Extract all text nodes first
        $pattern = '/>([^<>]+)</';
        $text_nodes = array();
        $placeholders = array();

        preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

        error_log('[LG AI Translator] Found ' . count($matches[1]) . ' potential text nodes');

        $extracted_count = 0;
        foreach ($matches[1] as $index => $match) {
            $text = $match[0];

            // Skip if text is only whitespace, numbers, or special characters
            if (trim($text) === '' || !preg_match('/\p{L}/u', $text)) {
                continue;
            }

            // Skip very short text (likely not meaningful)
            if (mb_strlen(trim($text)) < 3) {
                continue;
            }

            $extracted_count++;
            if ($extracted_count <= 5) {
                error_log('[LG AI Translator] Sample text ' . $extracted_count . ': ' . substr($text, 0, 100));
            }

            // Check individual text cache
            $cache_key = 'text_' . md5($text) . '_' . $target_lang;
            $cached = $this->cache->get($cache_key);

            if ($cached !== false) {
                // Use cached translation
                $placeholders[$text] = $cached;
            } else {
                // Need to translate
                $text_nodes[] = $text;
            }
        }

        error_log('[LG AI Translator] Extracted ' . $extracted_count . ' text nodes, ' . count($text_nodes) . ' need translation');

        // Batch translate all uncached text nodes
        if (!empty($text_nodes)) {
            error_log('[LG AI Translator] Starting batch translation of ' . count($text_nodes) . ' texts');
            $batch_translations = $this->batch_translate_texts($text_nodes, $target_lang);
            error_log('[LG AI Translator] Batch translation completed, got ' . count($batch_translations) . ' results');

            // Cache and merge results
            foreach ($batch_translations as $original => $translated) {
                $placeholders[$original] = $translated;

                // Cache individual texts
                $cache_key = 'text_' . md5($original) . '_' . $target_lang;
                $this->cache->set($cache_key, $translated);
            }
        }

        // Replace all text nodes with translations
        if (!empty($placeholders)) {
            error_log('[LG AI Translator] Replacing ' . count($placeholders) . ' text nodes with translations');
            foreach ($placeholders as $original => $translated) {
                $html = str_replace('>' . $original . '<', '>' . $translated . '<', $html);
            }
        }

        error_log('[LG AI Translator] HTML translation completed');
        return $html;
    }

    /**
     * Batch translate multiple texts in a single API call
     */
    private function batch_translate_texts($texts, $target_lang) {
        if (empty($texts)) {
            return array();
        }

        // Split into smaller chunks to avoid API timeouts
        $chunk_size = 50; // Translate max 50 texts at once
        $chunks = array_chunk($texts, $chunk_size, true);
        $all_results = array();

        foreach ($chunks as $chunk) {
            $chunk_results = $this->batch_translate_chunk($chunk, $target_lang);
            $all_results = array_merge($all_results, $chunk_results);
        }

        return $all_results;
    }

    /**
     * Translate a chunk of texts
     */
    private function batch_translate_chunk($texts, $target_lang) {
        if (empty($texts)) {
            return array();
        }

        // Combine texts with delimiters
        $delimiter = "\n###TRANSLATE_SPLIT###\n";
        $combined_text = implode($delimiter, $texts);

        // Translate the batch
        try {
            $translated_combined = $this->translate_text($combined_text, $target_lang);

            // Split back into individual translations
            $translated_parts = explode($delimiter, $translated_combined);

            // Map originals to translations
            $results = array();
            foreach ($texts as $index => $original) {
                $results[$original] = isset($translated_parts[$index]) ? trim($translated_parts[$index]) : $original;
            }

            return $results;
        } catch (Exception $e) {
            error_log('[LG AI Translator] Batch translation failed: ' . $e->getMessage());

            // Return originals on error
            $results = array();
            foreach ($texts as $original) {
                $results[$original] = $original;
            }
            return $results;
        }
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
        // Generate cache key
        $cache_key = 'text_' . md5($text) . '_' . $target_lang;

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $default_lang = $this->url_rewriter->get_default_language();
            $translated = $this->translation_service->translate_text($text, $default_lang, $target_lang);

            // Cache result
            $this->cache->set($cache_key, $translated);

            return $translated;
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
