<?php
/**
 * Gemini Translation Service
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Gemini AI translation service implementation
 */
class LG_Gemini_Translation_Service implements LG_Translation_Service_Interface {

    /**
     * API key
     */
    private $api_key;

    /**
     * Model name
     */
    private $model;

    /**
     * Translation quality
     */
    private $quality;

    /**
     * Cache instance
     */
    private $cache;

    /**
     * Constructor
     *
     * @throws Exception If API key is not configured
     */
    public function __construct() {
        $settings = get_option('lg_aitranslator_settings', array());
        $key_manager = new LG_API_Key_Manager();

        $this->api_key = $key_manager->get_api_key('gemini');
        $this->model = $settings['model'] ?? 'gemini-1.5-flash';
        $this->quality = $settings['translation_quality'] ?? 'standard';
        $this->cache = new LG_Translation_Cache();

        if (empty($this->api_key)) {
            throw new Exception(__('Gemini API key not configured', 'lg-aitranslator'));
        }
    }

    /**
     * Translate text
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated text
     * @throws Exception If translation fails
     */
    public function translate_text($text, $source_lang, $target_lang) {
        if (empty($text)) {
            return $text;
        }

        // Check cache first
        $cache_key = $this->generate_cache_key($text, $source_lang, $target_lang);
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Build prompt
        $prompt = $this->build_translation_prompt($text, $source_lang, $target_lang);

        // Make API request
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $this->model,
            $this->api_key
        );

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.3,
                'maxOutputTokens' => 8192
            )
        );

        $response = wp_remote_post($url, array(
            'timeout' => 60, // Extended timeout for batch translations
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body)
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception(sprintf(__('Gemini API error (code %d): %s', 'lg-aitranslator'), $code, $body));
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($result['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception(__('Invalid response from Gemini API', 'lg-aitranslator'));
        }

        $translation = trim($result['candidates'][0]['content']['parts'][0]['text']);

        // Cache the result
        $this->cache->set($cache_key, $translation);

        return $translation;
    }

    /**
     * Translate HTML while preserving structure
     *
     * @param string $html HTML content
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated HTML
     * @throws Exception If translation fails
     */
    public function translate_html($html, $source_lang, $target_lang) {
        if (empty($html)) {
            return $html;
        }

        // Check cache
        $cache_key = $this->generate_cache_key($html, $source_lang, $target_lang, 'html');
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Extract text segments from HTML
        $segments = $this->extract_text_segments($html);

        if (empty($segments)) {
            return $html;
        }

        // Translate segments in batches
        $translations = $this->batch_translate_segments($segments, $source_lang, $target_lang);

        // Replace segments in HTML
        $translated_html = $this->replace_segments($html, $segments, $translations);

        // Cache result
        $this->cache->set($cache_key, $translated_html);

        return $translated_html;
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    public function get_supported_languages() {
        return array_keys(LG_AITranslator::$languages);
    }

    /**
     * Detect language
     *
     * @param string $text Text to analyze
     * @return string Language code
     */
    public function detect_language($text) {
        // For simplicity, return default language
        // In production, you could use Gemini for language detection
        $settings = get_option('lg_aitranslator_settings', array());
        return $settings['default_language'] ?? 'en';
    }

    /**
     * Validate credentials
     *
     * @return array
     */
    public function validate_credentials() {
        $key_manager = new LG_API_Key_Manager();
        return $key_manager->validate_gemini_key($this->api_key);
    }

    /**
     * Build translation prompt
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language
     * @param string $target_lang Target language
     * @return string Prompt
     */
    private function build_translation_prompt($text, $source_lang, $target_lang) {
        $source_name = LG_AITranslator::$languages[$source_lang] ?? $source_lang;
        $target_name = LG_AITranslator::$languages[$target_lang] ?? $target_lang;

        if ($this->quality === 'high') {
            return sprintf(
                "You are a professional translator. Translate the following text from %s to %s.\n\n" .
                "Requirements:\n" .
                "- Maintain natural fluency and cultural appropriateness\n" .
                "- Preserve tone and style\n" .
                "- Keep all formatting (HTML tags, line breaks, etc.)\n" .
                "- Return only the translated text, no explanations\n\n" .
                "Text to translate:\n%s",
                $source_name,
                $target_name,
                $text
            );
        }

        return sprintf(
            "Translate from %s to %s. Preserve all formatting. Return only the translation:\n\n%s",
            $source_name,
            $target_name,
            $text
        );
    }

    /**
     * Extract text segments from HTML
     *
     * @param string $html HTML content
     * @return array Text segments
     */
    private function extract_text_segments($html) {
        $segments = array();

        // Simple extraction - in production, use DOMDocument for better parsing
        $pattern = '/>([^<]+)</';
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if (!empty($text) && !$this->is_untranslatable($text)) {
                    $segments[] = $text;
                }
            }
        }

        return array_unique($segments);
    }

    /**
     * Batch translate segments
     *
     * @param array $segments Text segments
     * @param string $source_lang Source language
     * @param string $target_lang Target language
     * @return array Translations mapped to original segments
     */
    private function batch_translate_segments($segments, $source_lang, $target_lang) {
        $batch_size = 20;
        $translations = array();

        foreach (array_chunk($segments, $batch_size) as $batch) {
            $batch_text = implode("\n---SEGMENT---\n", $batch);

            try {
                $translated_batch = $this->translate_text($batch_text, $source_lang, $target_lang);
                $translated_segments = explode("\n---SEGMENT---\n", $translated_batch);

                foreach ($batch as $i => $original) {
                    $translations[$original] = $translated_segments[$i] ?? $original;
                }
            } catch (Exception $e) {
                // On error, use original text
                foreach ($batch as $original) {
                    $translations[$original] = $original;
                }
            }
        }

        return $translations;
    }

    /**
     * Replace segments in HTML
     *
     * @param string $html HTML content
     * @param array $segments Original segments
     * @param array $translations Translations
     * @return string Translated HTML
     */
    private function replace_segments($html, $segments, $translations) {
        foreach ($segments as $original) {
            if (isset($translations[$original])) {
                $html = str_replace($original, $translations[$original], $html);
            }
        }
        return $html;
    }

    /**
     * Check if text should not be translated
     *
     * @param string $text Text to check
     * @return bool
     */
    private function is_untranslatable($text) {
        // Skip URLs, emails, numbers
        if (preg_match('/^https?:\/\//', $text)) {
            return true;
        }
        if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        if (preg_match('/^[\d\s\.\,\-]+$/', $text)) {
            return true;
        }
        return false;
    }

    /**
     * Generate cache key
     *
     * @param string $text Text content
     * @param string $source_lang Source language
     * @param string $target_lang Target language
     * @param string $type Content type
     * @return string Cache key
     */
    private function generate_cache_key($text, $source_lang, $target_lang, $type = 'text') {
        $cache_version = get_option('lg_aitranslator_cache_version', 1);
        return 'lg_aitrans_' . $type . '_' . md5($text . $source_lang . $target_lang . $cache_version);
    }
}
