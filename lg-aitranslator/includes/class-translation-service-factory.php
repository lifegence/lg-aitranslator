<?php
/**
 * Translation Service Factory
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Factory class for creating translation service instances
 */
class LG_Translation_Service_Factory {

    /**
     * Create translation service instance based on settings
     *
     * @return LG_Translation_Service_Interface
     * @throws Exception If provider is not supported or API key is missing
     */
    public static function create() {
        $settings = get_option('lg_aitranslator_settings', array());
        $provider = $settings['provider'] ?? 'gemini';

        switch ($provider) {
            case 'gemini':
                return new LG_Gemini_Translation_Service();

            case 'openai':
                return new LG_OpenAI_Translation_Service();

            default:
                throw new Exception(
                    sprintf(__('Unsupported translation provider: %s', 'lg-aitranslator'), $provider)
                );
        }
    }

    /**
     * Get available providers
     *
     * @return array
     */
    public static function get_providers() {
        return array(
            'gemini' => array(
                'name' => __('Google Gemini', 'lg-aitranslator'),
                'description' => __('Fast and cost-effective AI translation', 'lg-aitranslator'),
                'models' => array(
                    'gemini-1.5-flash' => __('Gemini 1.5 Flash (Recommended)', 'lg-aitranslator'),
                    'gemini-1.5-pro' => __('Gemini 1.5 Pro (Higher Quality)', 'lg-aitranslator'),
                    'gemini-2.0-flash' => __('Gemini 2.0 Flash (Latest)', 'lg-aitranslator')
                )
            ),
            'openai' => array(
                'name' => __('OpenAI GPT', 'lg-aitranslator'),
                'description' => __('Premium quality AI translation', 'lg-aitranslator'),
                'models' => array(
                    'gpt-4o-mini' => __('GPT-4o Mini (Recommended)', 'lg-aitranslator'),
                    'gpt-4o' => __('GPT-4o (Highest Quality)', 'lg-aitranslator'),
                    'gpt-3.5-turbo' => __('GPT-3.5 Turbo (Budget)', 'lg-aitranslator')
                )
            )
        );
    }
}
