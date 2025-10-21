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
});
