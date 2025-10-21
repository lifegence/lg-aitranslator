jQuery(document).ready(function($) {
    // Handle language switcher dropdown
    $('#lg-lang-select').on('change', function() {
        var lang = $(this).val();
        changeLanguage(lang);
    });

    // Handle language switcher links - now uses href directly
    // Just set cookie for preference storage
    $(document).on('click', '.lg-lang-link, .lg-lang-flag-link', function(e) {
        var lang = $(this).data('lang');
        // Set cookie for language preference
        document.cookie = 'lg_aitranslator_lang=' + lang + '; path=/; max-age=31536000';
        // Let the href handle navigation (already set to correct URL)
    });

    function changeLanguage(lang) {
        // Set cookie for preference
        document.cookie = 'lg_aitranslator_lang=' + lang + '; path=/; max-age=31536000';

        // Build new URL with language prefix
        var currentPath = window.location.pathname;
        var newPath;

        // Remove existing language prefix
        var langPattern = /^\/(en|ja|zh-CN|zh-TW|ko|es|fr|de|it|pt|ru|ar|hi|th|vi|id|tr|pl|nl|sv)\//i;
        currentPath = currentPath.replace(langPattern, '/');

        // Add new language prefix (skip for default language)
        var defaultLang = lgAITranslatorFrontend.defaultLang;
        if (lang !== defaultLang) {
            newPath = '/' + lang + currentPath;
        } else {
            newPath = currentPath;
        }

        // Navigate to new URL
        window.location.href = newPath + window.location.search + window.location.hash;
    }

    // Get current language from URL path
    function getCurrentLanguage() {
        var path = window.location.pathname;
        var langPattern = /^\/(en|ja|zh-CN|zh-TW|ko|es|fr|de|it|pt|ru|ar|hi|th|vi|id|tr|pl|nl|sv)\//i;
        var match = path.match(langPattern);

        if (match && match[1]) {
            return match[1];
        }

        return lgAITranslatorFrontend.defaultLang;
    }
});
