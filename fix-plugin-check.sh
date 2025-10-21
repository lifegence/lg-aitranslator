#!/bin/bash

# Fix Gemini service
sed -i "77i\            /* translators: 1: HTTP status code, 2: Error message from API */" lg-aitranslator/includes/class-gemini-translation-service.php
sed -i "s/'Gemini API error (code %d): %s'/'Gemini API error (code %1\$d): %2\$s'/g" lg-aitranslator/includes/class-gemini-translation-service.php

# Fix OpenAI service
sed -i "77i\            /* translators: 1: HTTP status code, 2: Error message from API */" lg-aitranslator/includes/class-openai-translation-service.php
sed -i "s/'OpenAI API error (code %d): %s'/'OpenAI API error (code %1\$d): %2\$s'/g" lg-aitranslator/includes/class-openai-translation-service.php

# Fix abstract class
sed -i "77i\        /* translators: %s: Provider name (gemini or openai) */" lg-aitranslator/includes/class-abstract-translation-service.php

# Fix admin AJAX
sed -i "118i\                /* translators: 1: Old cache version number, 2: New cache version number */" lg-aitranslator/admin/class-admin-ajax.php
sed -i "s/'Cache version incremented from %d to %d/'Cache version incremented from %1\$d to %2\$d/g" lg-aitranslator/admin/class-admin-ajax.php

# Fix translation service factory
sed -i "36i\        /* translators: %s: Provider name */" lg-aitranslator/includes/class-translation-service-factory.php

# Fix admin settings
sed -i "232i\                /* translators: %s: Model name */" lg-aitranslator/admin/class-admin-settings.php
sed -i "279i\                /* translators: %s: Model name */" lg-aitranslator/admin/class-admin-settings.php

# Remove old backup files
rm -f lg-aitranslator/includes/class-gemini-translation-service-old.php
rm -f lg-aitranslator/includes/class-openai-translation-service-old.php

# Replace parse_url with wp_parse_url
find lg-aitranslator -name "*.php" -type f -exec sed -i 's/parse_url(/wp_parse_url(/g' {} \;

echo "Plugin check fixes applied!"
