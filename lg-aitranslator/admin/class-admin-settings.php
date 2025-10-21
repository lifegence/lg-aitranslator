<?php
/**
 * Admin Settings Page
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin settings page handler
 */
class LG_AITranslator_Admin_Settings {

    /**
     * Render settings page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission
        if (isset($_POST['lg_aitranslator_settings_nonce'])) {
            $this->save_settings();
        }

        $settings = get_option('lg_aitranslator_settings', array());
        $this->load_defaults($settings);

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors('lg_aitranslator_messages'); ?>

            <form method="post" action="">
                <?php wp_nonce_field('lg_aitranslator_settings', 'lg_aitranslator_settings_nonce'); ?>

                <div class="lg-aitrans-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'lg-aitranslator'); ?></a>
                        <a href="#engine" class="nav-tab"><?php esc_html_e('Translation Engine', 'lg-aitranslator'); ?></a>
                        <a href="#cache" class="nav-tab"><?php esc_html_e('Cache', 'lg-aitranslator'); ?></a>
                        <a href="#advanced" class="nav-tab"><?php esc_html_e('Advanced', 'lg-aitranslator'); ?></a>
                    </nav>

                    <!-- General Tab -->
                    <div id="general" class="tab-content active">
                        <?php $this->render_general_settings($settings); ?>
                    </div>

                    <!-- Translation Engine Tab -->
                    <div id="engine" class="tab-content" style="display:none;">
                        <?php $this->render_engine_settings($settings); ?>
                    </div>

                    <!-- Cache Tab -->
                    <div id="cache" class="tab-content" style="display:none;">
                        <?php $this->render_cache_settings($settings); ?>
                    </div>

                    <!-- Advanced Tab -->
                    <div id="advanced" class="tab-content" style="display:none;">
                        <?php $this->render_advanced_settings($settings); ?>
                    </div>
                </div>

                <?php submit_button(__('Save Settings', 'lg-aitranslator')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render general settings
     */
    private function render_general_settings($settings) {
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="enabled"><?php esc_html_e('Enable Translation', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="enabled" name="enabled" value="1" <?php checked($settings['enabled'], true); ?>>
                        <?php esc_html_e('Enable AI translation on your website', 'lg-aitranslator'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="default_language"><?php esc_html_e('Default Language', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="default_language" name="default_language" class="regular-text">
                        <?php foreach (LG_AITranslator::$languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['default_language'], $code); ?>>
                                <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('The original language of your website content', 'lg-aitranslator'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Supported Languages', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php esc_html_e('Supported Languages', 'lg-aitranslator'); ?></span></legend>
                        <?php
                        $supported = $settings['supported_languages'] ?? array();
                        foreach (LG_AITranslator::$languages as $code => $name):
                        ?>
                            <label style="display: inline-block; width: 200px; margin-bottom: 5px;">
                                <input type="checkbox" name="supported_languages[]" value="<?php echo esc_attr($code); ?>"
                                    <?php checked(in_array($code, $supported)); ?>>
                                <?php echo esc_html($name); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php esc_html_e('Select languages to enable for translation', 'lg-aitranslator'); ?></p>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Language Switcher', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <p><strong><?php esc_html_e('Display language switcher on your site:', 'lg-aitranslator'); ?></strong></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li>
                            <strong><?php esc_html_e('Widget:', 'lg-aitranslator'); ?></strong>
                            <?php esc_html_e('Go to Appearance > Widgets and add "Lifegence Language Switcher"', 'lg-aitranslator'); ?>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Shortcode:', 'lg-aitranslator'); ?></strong>
                            <code>[lg_language_switcher]</code>
                            <br>
                            <span class="description">
                                <?php esc_html_e('Options:', 'lg-aitranslator'); ?>
                                <code>type="dropdown|list|flags"</code>,
                                <code>flags="yes|no"</code>,
                                <code>native_names="yes|no"</code>
                            </span>
                            <br>
                            <span class="description">
                                <?php esc_html_e('Example:', 'lg-aitranslator'); ?>
                                <code>[lg_language_switcher type="flags" flags="yes"]</code>
                            </span>
                        </li>
                    </ul>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render engine settings
     */
    private function render_engine_settings($settings) {
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="provider"><?php esc_html_e('Translation Provider', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="provider" name="provider" class="regular-text">
                        <option value="gemini" <?php selected($settings['provider'], 'gemini'); ?>>
                            <?php esc_html_e('Google Gemini (Recommended)', 'lg-aitranslator'); ?>
                        </option>
                        <option value="openai" <?php selected($settings['provider'], 'openai'); ?>>
                            <?php esc_html_e('OpenAI GPT (Premium Quality)', 'lg-aitranslator'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <!-- Gemini Settings -->
            <tr class="gemini-setting" style="display:none;">
                <th scope="row">
                    <label for="gemini_model"><?php esc_html_e('Gemini Model', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="gemini_model" name="gemini_model" class="regular-text">
                        <optgroup label="<?php esc_html_e('Latest Generation (2.5)', 'lg-aitranslator'); ?>">
                            <option value="gemini-2.5-pro" <?php selected($settings['model'], 'gemini-2.5-pro'); ?>>
                                <?php esc_html_e('Gemini 2.5 Pro - Advanced reasoning', 'lg-aitranslator'); ?>
                            </option>
                            <option value="gemini-2.5-flash" <?php selected($settings['model'], 'gemini-2.5-flash'); ?>>
                                <?php esc_html_e('Gemini 2.5 Flash - Best value (Recommended)', 'lg-aitranslator'); ?>
                            </option>
                            <option value="gemini-2.5-flash-lite" <?php selected($settings['model'], 'gemini-2.5-flash-lite'); ?>>
                                <?php esc_html_e('Gemini 2.5 Flash-Lite - Ultra fast', 'lg-aitranslator'); ?>
                            </option>
                        </optgroup>
                        <optgroup label="<?php esc_html_e('Previous Generation (2.0)', 'lg-aitranslator'); ?>">
                            <option value="gemini-2.0-flash" <?php selected($settings['model'], 'gemini-2.0-flash'); ?>>
                                <?php esc_html_e('Gemini 2.0 Flash - 1M context', 'lg-aitranslator'); ?>
                            </option>
                            <option value="gemini-2.0-flash-lite" <?php selected($settings['model'], 'gemini-2.0-flash-lite'); ?>>
                                <?php esc_html_e('Gemini 2.0 Flash-Lite - Compact', 'lg-aitranslator'); ?>
                            </option>
                        </optgroup>
                    </select>
                </td>
            </tr>

            <tr class="gemini-setting" style="display:none;">
                <th scope="row">
                    <label for="gemini_api_key"><?php esc_html_e('Gemini API Key', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <input type="password" id="gemini_api_key" name="gemini_api_key"
                        value="" class="regular-text"
                        placeholder="<?php echo !empty($settings['gemini_api_key']) ? '••••••••••' : 'AIzaSy...'; ?>">
                    <button type="button" id="test-gemini-key" class="button"><?php esc_html_e('Test Connection', 'lg-aitranslator'); ?></button>
                    <div id="gemini-key-status"></div>
                    <p class="description">
                        <?php
                /* translators: %s: Model name */
                        printf(
                            __('Get your API key from <a href="%s" target="_blank">Google AI Studio</a>', 'lg-aitranslator'),
                            'https://aistudio.google.com/app/apikey'
                        );
                        ?>
                        <br>
                        <?php if (!empty($settings['gemini_api_key'])): ?>
                            <strong style="color: green;">✓ <?php esc_html_e('API key is saved (encrypted). Leave blank to keep existing key.', 'lg-aitranslator'); ?></strong>
                        <?php else: ?>
                            <strong style="color: #d63638;"><?php esc_html_e('No API key saved. Please enter your API key.', 'lg-aitranslator'); ?></strong>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>

            <!-- OpenAI Settings -->
            <tr class="openai-setting" style="display:none;">
                <th scope="row">
                    <label for="openai_model"><?php esc_html_e('OpenAI Model', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="openai_model" name="openai_model" class="regular-text">
                        <option value="gpt-4o-mini" <?php selected($settings['model'], 'gpt-4o-mini'); ?>>
                            <?php esc_html_e('GPT-4o Mini (Recommended)', 'lg-aitranslator'); ?>
                        </option>
                        <option value="gpt-4o" <?php selected($settings['model'], 'gpt-4o'); ?>>
                            <?php esc_html_e('GPT-4o (Highest Quality)', 'lg-aitranslator'); ?>
                        </option>
                        <option value="gpt-3.5-turbo" <?php selected($settings['model'], 'gpt-3.5-turbo'); ?>>
                            <?php esc_html_e('GPT-3.5 Turbo (Budget)', 'lg-aitranslator'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr class="openai-setting" style="display:none;">
                <th scope="row">
                    <label for="openai_api_key"><?php esc_html_e('OpenAI API Key', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <input type="password" id="openai_api_key" name="openai_api_key"
                        value="" class="regular-text"
                        placeholder="<?php echo !empty($settings['openai_api_key']) ? '••••••••••' : 'sk-...'; ?>">
                    <button type="button" id="test-openai-key" class="button"><?php esc_html_e('Test Connection', 'lg-aitranslator'); ?></button>
                    <div id="openai-key-status"></div>
                    <p class="description">
                /* translators: %s: Model name */
                        <?php
                        printf(
                            __('Get your API key from <a href="%s" target="_blank">OpenAI Platform</a>', 'lg-aitranslator'),
                            'https://platform.openai.com/api-keys'
                        );
                        ?>
                        <br>
                        <?php if (!empty($settings['openai_api_key'])): ?>
                            <strong style="color: green;">✓ <?php esc_html_e('API key is saved (encrypted). Leave blank to keep existing key.', 'lg-aitranslator'); ?></strong>
                        <?php else: ?>
                            <strong style="color: #d63638;"><?php esc_html_e('No API key saved. Please enter your API key.', 'lg-aitranslator'); ?></strong>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="translation_quality"><?php esc_html_e('Translation Quality', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="translation_quality" name="translation_quality">
                        <option value="standard" <?php selected($settings['translation_quality'], 'standard'); ?>>
                            <?php esc_html_e('Standard (Faster)', 'lg-aitranslator'); ?>
                        </option>
                        <option value="high" <?php selected($settings['translation_quality'], 'high'); ?>>
                            <?php esc_html_e('High (Better Quality)', 'lg-aitranslator'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr class="openai-setting" style="display:none;">
                <th scope="row">
                    <label for="translation_temperature"><?php esc_html_e('Temperature', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <input type="range" id="translation_temperature" name="translation_temperature"
                        min="0" max="1" step="0.1" value="<?php echo esc_attr($settings['translation_temperature'] ?? 0.3); ?>">
                    <span id="temperature-value">0.3</span>
                    <p class="description"><?php esc_html_e('Lower = More consistent, Higher = More creative (0.3 recommended)', 'lg-aitranslator'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render cache settings
     */
    private function render_cache_settings($settings) {
        $cache = new LG_Translation_Cache();
        $stats = $cache->get_stats();
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="cache_enabled"><?php esc_html_e('Enable Cache', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="cache_enabled" name="cache_enabled" value="1" <?php checked($settings['cache_enabled'], true); ?>>
                        <?php esc_html_e('Cache translated content (Highly recommended)', 'lg-aitranslator'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Caching reduces API costs by 80-95%', 'lg-aitranslator'); ?></p>
                </td>
            </tr>

            <tr class="cache-option">
                <th scope="row">
                    <label for="cache_ttl"><?php esc_html_e('Cache Duration', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="cache_ttl" name="cache_ttl">
                        <option value="3600" <?php selected($settings['cache_ttl'], 3600); ?>><?php esc_html_e('1 Hour', 'lg-aitranslator'); ?></option>
                        <option value="21600" <?php selected($settings['cache_ttl'], 21600); ?>><?php esc_html_e('6 Hours', 'lg-aitranslator'); ?></option>
                        <option value="43200" <?php selected($settings['cache_ttl'], 43200); ?>><?php esc_html_e('12 Hours', 'lg-aitranslator'); ?></option>
                        <option value="86400" <?php selected($settings['cache_ttl'], 86400); ?>><?php esc_html_e('24 Hours (Recommended)', 'lg-aitranslator'); ?></option>
                        <option value="259200" <?php selected($settings['cache_ttl'], 259200); ?>><?php esc_html_e('3 Days', 'lg-aitranslator'); ?></option>
                        <option value="604800" <?php selected($settings['cache_ttl'], 604800); ?>><?php esc_html_e('7 Days', 'lg-aitranslator'); ?></option>
                    </select>
                </td>
            </tr>

            <tr class="cache-option">
                <th scope="row">
                    <label for="cache_backend"><?php esc_html_e('Cache Backend', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <select id="cache_backend" name="cache_backend">
                        <option value="transients" <?php selected($settings['cache_backend'], 'transients'); ?>>
                            <?php esc_html_e('WordPress Transients (Default)', 'lg-aitranslator'); ?>
                        </option>
                        <option value="redis" <?php selected($settings['cache_backend'], 'redis'); ?>>
                            <?php esc_html_e('Redis (High-traffic sites)', 'lg-aitranslator'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr class="cache-option">
                <th scope="row">
                    <label><?php esc_html_e('Cache Statistics', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <p><strong><?php esc_html_e('Total Cached Items:', 'lg-aitranslator'); ?></strong> <?php echo esc_html($stats['total_keys']); ?></p>
                    <p><strong><?php esc_html_e('Total Size:', 'lg-aitranslator'); ?></strong> <?php echo esc_html(size_format($stats['total_size'])); ?></p>
                    <?php
                    $cache_version = get_option('lg_aitranslator_cache_version', 1);
                    ?>
                    <p><strong><?php esc_html_e('Cache Version:', 'lg-aitranslator'); ?></strong> <?php echo esc_html($cache_version); ?></p>
                    <p class="description">
                        <?php esc_html_e('Incrementing the cache version will invalidate all existing translations and force re-translation on next page load.', 'lg-aitranslator'); ?>
                    </p>
                    <button type="button" id="increment-cache-version" class="button button-secondary" style="margin-right: 10px;">
                        <?php esc_html_e('Increment Cache Version (Force Re-translate)', 'lg-aitranslator'); ?>
                    </button>
                    <button type="button" id="clear-cache" class="button">
                        <?php esc_html_e('Clear All Cache', 'lg-aitranslator'); ?>
                    </button>
                    <div id="cache-status"></div>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render advanced settings
     */
    private function render_advanced_settings($settings) {
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="rate_limit_enabled"><?php esc_html_e('Rate Limiting', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="rate_limit_enabled" name="rate_limit_enabled" value="1" <?php checked($settings['rate_limit_enabled'], true); ?>>
                        <?php esc_html_e('Enable rate limiting', 'lg-aitranslator'); ?>
                    </label>
                </td>
            </tr>

            <tr class="rate-limit-option">
                <th scope="row">
                    <label for="rate_limit_per_hour"><?php esc_html_e('Requests per Hour', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <input type="number" id="rate_limit_per_hour" name="rate_limit_per_hour"
                        value="<?php echo esc_attr($settings['rate_limit_per_hour'] ?? 1000); ?>"
                        min="10" max="10000" class="small-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="monthly_budget_limit"><?php esc_html_e('Monthly Budget (USD)', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <input type="number" id="monthly_budget_limit" name="monthly_budget_limit"
                        value="<?php echo esc_attr($settings['monthly_budget_limit'] ?? 50); ?>"
                        min="0" max="10000" class="small-text">
                    <p class="description"><?php esc_html_e('Set to 0 to disable budget tracking', 'lg-aitranslator'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="auto_disable_on_budget"><?php esc_html_e('Auto-disable on Budget', 'lg-aitranslator'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="auto_disable_on_budget" name="auto_disable_on_budget" value="1" <?php checked($settings['auto_disable_on_budget'], true); ?>>
                        <?php esc_html_e('Switch to cache-only mode when budget is exceeded', 'lg-aitranslator'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!isset($_POST['lg_aitranslator_settings_nonce']) ||
            !wp_verify_nonce($_POST['lg_aitranslator_settings_nonce'], 'lg_aitranslator_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = array();

        // General settings
        $settings['enabled'] = isset($_POST['enabled']);
        $settings['default_language'] = sanitize_text_field($_POST['default_language'] ?? 'en');
        $settings['supported_languages'] = isset($_POST['supported_languages']) ? array_map('sanitize_text_field', $_POST['supported_languages']) : array();

        // Provider settings
        $settings['provider'] = sanitize_text_field($_POST['provider'] ?? 'gemini');

        // Model selection based on provider
        if ($settings['provider'] === 'gemini' && !empty($_POST['gemini_model'])) {
            $settings['model'] = sanitize_text_field($_POST['gemini_model']);
        } elseif ($settings['provider'] === 'openai' && !empty($_POST['openai_model'])) {
            $settings['model'] = sanitize_text_field($_POST['openai_model']);
        }

        // API keys - only update if non-empty value provided
        $key_manager = new LG_API_Key_Manager();
        $old_settings = get_option('lg_aitranslator_settings', array());

        // Handle Gemini API key
        if (!empty($_POST['gemini_api_key'])) {
            $settings['gemini_api_key'] = $key_manager->encrypt_key($_POST['gemini_api_key']);
            $settings['gemini_api_key_display'] = substr($_POST['gemini_api_key'], 0, 10) . '...';
        } else {
            // Preserve existing encrypted key if no new key provided
            $settings['gemini_api_key'] = $old_settings['gemini_api_key'] ?? '';
            $settings['gemini_api_key_display'] = $old_settings['gemini_api_key_display'] ?? '';
        }

        // Handle OpenAI API key
        if (!empty($_POST['openai_api_key'])) {
            $settings['openai_api_key'] = $key_manager->encrypt_key($_POST['openai_api_key']);
            $settings['openai_api_key_display'] = substr($_POST['openai_api_key'], 0, 10) . '...';
        } else {
            // Preserve existing encrypted key if no new key provided
            $settings['openai_api_key'] = $old_settings['openai_api_key'] ?? '';
            $settings['openai_api_key_display'] = $old_settings['openai_api_key_display'] ?? '';
        }

        // Quality settings
        $settings['translation_quality'] = sanitize_text_field($_POST['translation_quality'] ?? 'standard');
        $settings['translation_temperature'] = floatval($_POST['translation_temperature'] ?? 0.3);

        // Cache settings
        $settings['cache_enabled'] = isset($_POST['cache_enabled']);
        $settings['cache_ttl'] = intval($_POST['cache_ttl'] ?? 86400);
        $settings['cache_backend'] = sanitize_text_field($_POST['cache_backend'] ?? 'transients');

        // Advanced settings
        $settings['rate_limit_enabled'] = isset($_POST['rate_limit_enabled']);
        $settings['rate_limit_per_hour'] = intval($_POST['rate_limit_per_hour'] ?? 1000);
        $settings['monthly_budget_limit'] = floatval($_POST['monthly_budget_limit'] ?? 50);
        $settings['auto_disable_on_budget'] = isset($_POST['auto_disable_on_budget']);

        update_option('lg_aitranslator_settings', $settings);

        // Update .htaccess with rewrite rules
        $this->update_htaccess($settings);

        // Flush rewrite rules
        flush_rewrite_rules();

        add_settings_error(
            'lg_aitranslator_messages',
            'lg_aitranslator_message',
            __('Settings saved successfully.', 'lg-aitranslator'),
            'success'
        );
    }

    /**
     * Update .htaccess file with rewrite rules
     */
    private function update_htaccess($settings) {
        $htaccess_file = ABSPATH . '.htaccess';

        // Check if .htaccess is writable
        if (!is_writable($htaccess_file)) {
            add_settings_error(
                'lg_aitranslator_messages',
                'lg_aitranslator_htaccess_error',
                __('.htaccess file is not writable. Please check file permissions.', 'lg-aitranslator'),
                'warning'
            );
            return;
        }

        // Read current .htaccess content
        $htaccess_content = file_get_contents($htaccess_file);

        // Check if our rules already exist
        if (strpos($htaccess_content, '# BEGIN LG-AITranslator') !== false) {
            // Remove old rules first
            $htaccess_content = preg_replace(
                '/# BEGIN LG-AITranslator.*?# END LG-AITranslator\s*/s',
                '',
                $htaccess_content
            );
        }

        // Generate language pattern for rewrite rules
        $languages = $settings['supported_languages'] ?? array('en', 'ja', 'zh-CN', 'es', 'fr');
        $lang_pattern = implode('|', array_map('preg_quote', $languages));

        // Create rewrite rules
        $rewrite_rules = <<<EOT
# BEGIN LG-AITranslator
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^($lang_pattern)(/(.*))?/?$ index.php?lang=\$1&lg_translated_path=\$3 [L,QSA]
</IfModule>
# END LG-AITranslator

EOT;

        // Prepend our rules to existing content
        $new_htaccess = $rewrite_rules . $htaccess_content;

        // Write updated content
        $result = file_put_contents($htaccess_file, $new_htaccess);

        if ($result !== false) {
            add_settings_error(
                'lg_aitranslator_messages',
                'lg_aitranslator_htaccess_success',
                __('. htaccess file updated with rewrite rules.', 'lg-aitranslator'),
                'info'
            );
        } else {
            add_settings_error(
                'lg_aitranslator_messages',
                'lg_aitranslator_htaccess_error',
                __('Failed to update .htaccess file.', 'lg-aitranslator'),
                'error'
            );
        }
    }

    /**
     * Load default values
     */
    private function load_defaults(&$settings) {
        $defaults = array(
            'enabled' => false,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'default_language' => 'en',
            'supported_languages' => array('en', 'ja', 'zh-CN', 'es', 'fr'),
            'cache_enabled' => true,
            'cache_ttl' => 86400,
            'cache_backend' => 'transients',
            'translation_quality' => 'standard',
            'translation_temperature' => 0.3,
            'rate_limit_enabled' => true,
            'rate_limit_per_hour' => 1000,
            'monthly_budget_limit' => 50,
            'auto_disable_on_budget' => false
        );

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
    }
}
