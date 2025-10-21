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
class LG_Gemini_Translation_Service extends LG_Abstract_Translation_Service {

    /**
     * Constructor
     *
     * @throws Exception If API key is not configured
     */
    public function __construct() {
        parent::__construct('gemini', 'gemini-1.5-flash');
    }

    /**
     * Call Gemini API to translate text
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated text
     * @throws Exception If translation fails
     */
    protected function call_translation_api($text, $source_lang, $target_lang) {
        $source_name = LG_AITranslator::$languages[$source_lang] ?? $source_lang;
        $target_name = LG_AITranslator::$languages[$target_lang] ?? $target_lang;

        // Build prompt
        $system_message = $this->build_system_message($source_name, $target_name);
        $prompt = $system_message . "\n\nText to translate:\n" . $text;

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
                'temperature' => floatval($this->temperature),
                'maxOutputTokens' => 8192
            )
        );

        $response = wp_remote_post($url, array(
            'timeout' => 120,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body)
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $error_body = wp_remote_retrieve_body($response);
            throw new Exception(sprintf(__('Gemini API error (code %d): %s', 'lg-aitranslator'), $code, $error_body));
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($result['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception(__('Invalid response from Gemini API', 'lg-aitranslator'));
        }

        return trim($result['candidates'][0]['content']['parts'][0]['text']);
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
}
