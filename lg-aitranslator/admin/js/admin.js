jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').hide();
        $(target).show();
    });

    // Provider change
    $('#provider').on('change', function() {
        var provider = $(this).val();

        $('.gemini-setting, .openai-setting').hide();

        if (provider === 'gemini') {
            $('.gemini-setting').show();
        } else if (provider === 'openai') {
            $('.openai-setting').show();
        }
    }).trigger('change');

    // Cache enabled toggle
    $('#cache_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('.cache-option').show();
        } else {
            $('.cache-option').hide();
        }
    }).trigger('change');

    // Rate limit enabled toggle
    $('#rate_limit_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('.rate-limit-option').show();
        } else {
            $('.rate-limit-option').hide();
        }
    }).trigger('change');

    // Temperature slider
    $('#translation_temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });

    // Test Gemini API key
    $('#test-gemini-key').on('click', function() {
        var $btn = $(this);
        var $status = $('#gemini-key-status');
        var apiKey = $('#gemini_api_key').val();

        if (!apiKey) {
            $status.html('<span style="color:red;">Please enter an API key</span>');
            return;
        }

        $btn.prop('disabled', true).text(lgAITranslator.strings.testing);
        $status.html('<span style="color:#666;">Validating...</span>');

        $.post(lgAITranslator.ajaxurl, {
            action: 'lg_aitrans_test_gemini_key',
            api_key: apiKey,
            nonce: lgAITranslator.nonce
        }, function(response) {
            $btn.prop('disabled', false).text('Test Connection');

            if (response.success) {
                $status.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
            } else {
                $status.html('<span style="color:red;">✗ ' + response.data.error + '</span>');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Test Connection');
            $status.html('<span style="color:red;">Connection failed</span>');
        });
    });

    // Test OpenAI API key
    $('#test-openai-key').on('click', function() {
        var $btn = $(this);
        var $status = $('#openai-key-status');
        var apiKey = $('#openai_api_key').val();

        if (!apiKey) {
            $status.html('<span style="color:red;">Please enter an API key</span>');
            return;
        }

        $btn.prop('disabled', true).text(lgAITranslator.strings.testing);
        $status.html('<span style="color:#666;">Validating...</span>');

        $.post(lgAITranslator.ajaxurl, {
            action: 'lg_aitrans_test_openai_key',
            api_key: apiKey,
            nonce: lgAITranslator.nonce
        }, function(response) {
            $btn.prop('disabled', false).text('Test Connection');

            if (response.success) {
                $status.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
            } else {
                $status.html('<span style="color:red;">✗ ' + response.data.error + '</span>');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Test Connection');
            $status.html('<span style="color:red;">Connection failed</span>');
        });
    });

    // Increment cache version
    $('#increment-cache-version').on('click', function() {
        if (!confirm('This will invalidate all existing translations and force re-translation. Continue?')) {
            return;
        }

        var $btn = $(this);
        var $status = $('#cache-status');

        $btn.prop('disabled', true).text('Incrementing...');

        $.post(lgAITranslator.ajaxurl, {
            action: 'lg_aitrans_increment_cache_version',
            nonce: lgAITranslator.nonce
        }, function(response) {
            $btn.prop('disabled', false).text('Increment Cache Version (Force Re-translate)');

            if (response.success) {
                $status.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                $status.html('<span style="color:red;">✗ ' + response.data.error + '</span>');
            }
        });
    });

    // Clear cache
    $('#clear-cache').on('click', function() {
        if (!confirm('Are you sure you want to clear all translation cache?')) {
            return;
        }

        var $btn = $(this);
        var $status = $('#cache-status');

        $btn.prop('disabled', true).text(lgAITranslator.strings.clearing);

        $.post(lgAITranslator.ajaxurl, {
            action: 'lg_aitrans_clear_cache',
            nonce: lgAITranslator.nonce
        }, function(response) {
            $btn.prop('disabled', false).text('Clear All Cache');

            if (response.success) {
                $status.html('<span style="color:green;">✓ ' + response.data.message + '</span>');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                $status.html('<span style="color:red;">✗ ' + response.data.error + '</span>');
            }
        });
    });

    // Custom Languages Management
    // Add custom language
    $('#lg-add-language-btn').on('click', function() {
        var code = $('#lg-new-lang-code').val().trim();
        var name = $('#lg-new-lang-name').val().trim();

        if (!code || !name) {
            alert('Please enter both language code and name');
            return;
        }

        // Validate code format (alphanumeric, hyphen, underscore only)
        if (!/^[a-zA-Z0-9_-]+$/.test(code)) {
            alert('Invalid language code. Use only letters, numbers, hyphens, and underscores.');
            return;
        }

        // Check if code already exists
        if ($('.lg-custom-language-item[data-code="' + code + '"]').length > 0) {
            alert('Language code "' + code + '" already exists');
            return;
        }

        // Add new language item to the list
        addCustomLanguageItem(code, name);

        // Clear input fields
        $('#lg-new-lang-code').val('');
        $('#lg-new-lang-name').val('');
    });

    // Remove custom language
    $(document).on('click', '.lg-remove-language', function() {
        var code = $(this).data('code');
        if (confirm('Remove language "' + code + '"?')) {
            $(this).closest('.lg-custom-language-item').remove();

            // If no languages left, show "no custom languages" message
            if ($('.lg-custom-language-item').length === 0) {
                $('#lg-custom-language-list').html('<p class="description">No custom languages added yet.</p>');
            }
        }
    });

    // Helper function to add custom language item
    function addCustomLanguageItem(code, name) {
        // Remove "no languages" message if it exists
        $('#lg-custom-language-list .description').remove();

        var item = $('<div class="lg-custom-language-item" data-code="' + code + '"></div>')
            .append('<input type="hidden" name="custom_language_codes[]" value="' + code + '">')
            .append('<input type="hidden" name="custom_language_names[]" value="' + name + '">')
            .append('<span class="lg-lang-display"><strong>' + name + '</strong> (' + code + ')</span>')
            .append('<button type="button" class="button lg-remove-language" data-code="' + code + '">Remove</button>');

        $('#lg-custom-language-list').append(item);
    }

    // Enter key to add language
    $('#lg-new-lang-code, #lg-new-lang-name').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#lg-add-language-btn').click();
        }
    });
});
