# Translation Cache Override Guide

## Overview

The cache override feature allows you to **manually correct AI-generated translations** when they don't meet your needs. This addresses the common scenario where AI translations are generally good but occasionally need human refinement.

## Use Cases

### 1. Brand Voice Correction
AI may translate correctly but not match your brand's tone:
- "Purchase" → "購入" (formal) vs "買う" (casual)
- "Contact Us" → Different variations based on brand personality

### 2. Technical Term Consistency
Ensure consistent translation of technical or domain-specific terms:
- Product names that should remain in original language
- Industry-specific terminology
- Internal jargon or branded terms

### 3. Cultural Adaptation
Adjust translations for cultural appropriateness:
- Idioms that don't translate literally
- Culturally sensitive content
- Local market preferences

### 4. Quality Improvements
Fix occasional AI translation errors:
- Mistranslated context
- Grammar or syntax issues
- Missing nuance

## Methods to Override Translations

### Method 1: Direct Cache Update (Recommended for Single Corrections)

Use the WordPress AJAX endpoint to update a specific translation:

```bash
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -d 'action=lg_aitrans_update_translation' \
  -d 'nonce=YOUR_NONCE' \
  -d 'cache_key=text_abc123def456_ja' \
  -d 'translation=あなたの修正された翻訳'
```

**JavaScript Example:**
```javascript
jQuery.post(ajaxurl, {
  action: 'lg_aitrans_update_translation',
  nonce: lgAITranslator.nonce,
  cache_key: 'text_abc123def456_ja',
  translation: 'あなたの修正された翻訳'
}, function(response) {
  if (response.success) {
    console.log('Updated:', response.data.message);
  }
});
```

**Requirements:**
- Must be authenticated as administrator
- Requires valid WordPress nonce (`lg_aitranslator_frontend`)
- Cache key must match existing translation entry format: `text_{md5hash}_{language_code}`

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Translation updated successfully",
    "cache_key": "text_abc123def456_ja"
  }
}
```

### Method 2: Increment Cache Version (Bulk Refresh)

When you need to refresh ALL translations (e.g., after improving translation prompts):

**Via Admin Panel:**
1. Navigate to **Settings → Lifegence AITranslator → Cache** tab
2. Find the **Cache Version** section
3. Click **Increment Cache Version (Force Re-translate)** button
4. Confirm the action
5. All translations will be invalidated and re-translated on next page load

**Via AJAX API:**
```bash
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -d 'action=lg_aitrans_increment_cache_version' \
  -d 'nonce=YOUR_NONCE'
```

**JavaScript Example:**
```javascript
jQuery.post(ajaxurl, {
  action: 'lg_aitrans_increment_cache_version',
  nonce: lgAITranslator.nonce
}, function(response) {
  if (response.success) {
    console.log(response.data.message);
  }
});
```

### Method 3: Clear All Cache

Complete cache reset (removes all cached translations):

**Via Admin Panel:**
1. Go to **Settings → Lifegence AITranslator → Cache** tab
2. Click **Clear All Cache** button
3. Confirm the action

**Via AJAX API:**
```bash
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -d 'action=lg_aitrans_clear_cache' \
  -d 'nonce=YOUR_NONCE'
```

**JavaScript Example:**
```javascript
jQuery.post(ajaxurl, {
  action: 'lg_aitrans_clear_cache',
  nonce: lgAITranslator.nonce
}, function(response) {
  if (response.success) {
    console.log(response.data.message);
  }
});
```

## How Cache Versioning Works

### Version Number System
- Each translation cache entry includes a version number
- Current version stored in WordPress options: `lg_aitranslator_cache_version`
- Default starting version: `1`

### Cache Key Format
```
lg_aitrans_text_{md5_hash}_{language_code}
```

Example: `lg_aitrans_text_5d41402abc4b2a76b9719d911017c592_ja`

Components:
- `lg_aitrans_` - Plugin prefix
- `text_` - Content type identifier
- `{md5_hash}` - MD5 hash of source text
- `{language_code}` - Target language (ISO 639-1 code)

### Version Checking Process
1. Plugin requests translation
2. Checks cached entry version against current system version
3. If versions match → use cached translation
4. If versions differ → fetch new translation from AI
5. Store new translation with current version number

## Workflow Examples

### Example 1: Fix Single Translation Error

**Scenario:** Homepage headline translated incorrectly

```javascript
// 1. Identify the cache key (inspect browser network tab or logs)
const cacheKey = 'text_abc123def456_ja';

// 2. Update with corrected translation using WordPress AJAX
jQuery.post(ajaxurl, {
  action: 'lg_aitrans_update_translation',
  nonce: lgAITranslator.nonce,
  cache_key: cacheKey,
  translation: '正しい翻訳がここに入ります'
})
.done(function(response) {
  if (response.success) {
    console.log('Updated:', response.data.message);
    // 3. Refresh page to see corrected translation
    location.reload();
  } else {
    console.error('Error:', response.data.error);
  }
});
```

### Example 2: Bulk Refresh After Prompt Improvement

**Scenario:** You improved translation prompts and want fresh translations

```bash
# 1. Increment cache version via admin panel
# Go to Settings → Lifegence AITranslator → Cache tab
# Click "Increment Cache Version (Force Re-translate)" button

# Or via AJAX:
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -H "Cookie: wordpress_logged_in_xxx=..." \
  -d 'action=lg_aitrans_increment_cache_version' \
  -d 'nonce=YOUR_NONCE'

# Response: { "success": true, "data": { "message": "Cache version incremented from 1 to 2. All translations will be refreshed." }}

# 2. Visit pages in each language to trigger re-translation
# Or wait for users to visit pages naturally
```

### Example 3: Content Update Workflow

**Scenario:** Major content update requires translation refresh

```bash
# Option A: Complete cache clear (via admin panel or AJAX)
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -d 'action=lg_aitrans_clear_cache' \
  -d 'nonce=YOUR_NONCE'

# Option B: Version increment (preserves cache structure, via admin panel or AJAX)
curl -X POST https://your-site.com/wp-admin/admin-ajax.php \
  -d 'action=lg_aitrans_increment_cache_version' \
  -d 'nonce=YOUR_NONCE'
```

## Best Practices

### 1. Document Custom Translations
Keep a record of manually overridden translations:
```json
{
  "overrides": {
    "en:ja": {
      "Get Started": "今すぐ始める",
      "Learn More": "詳細を見る"
    }
  }
}
```

### 2. Version Control for Cache Management
- Use cache version increment for controlled, gradual refreshes
- Reserve complete cache clear for emergency situations
- Test translation changes on staging before production

### 3. Monitoring Translation Quality
- Regularly review translated pages
- Collect user feedback on translation quality
- Monitor cache hit rates to balance cost vs freshness

### 4. Backup Before Major Changes
```bash
# Export current cache version
wp option get lg_aitranslator_cache_version

# Backup translations (if using database)
wp db export translations_backup.sql --tables=wp_options
```

## Security Considerations

### Authentication Required
- All cache override operations require administrator privileges
- Nonce verification prevents CSRF attacks
- Cache key validation prevents arbitrary data injection

### Input Validation
- Cache keys validated against expected format pattern
- Translation content sanitized with `wp_kses_post()`
- Language codes validated against supported languages

### Rate Limiting
Configure rate limits to prevent abuse:
```php
// In plugin settings
$settings['rate_limit_enabled'] = true;
$settings['rate_limit_per_hour'] = 1000;
```

## Troubleshooting

### Override Not Applied
**Check:**
1. Cache key format is correct
2. User has administrator permissions
3. Nonce is valid and not expired
4. Translation content is properly sanitized

### Cache Version Not Incrementing
**Check:**
1. WordPress option table write permissions
2. Database connection stable
3. No caching plugin interfering with options

### Translations Still Old After Increment
**Possible causes:**
1. Browser caching - clear browser cache
2. CDN caching - purge CDN cache
3. Object cache (Redis/Memcached) - flush object cache
4. Page cache plugin - clear page cache

## Performance Considerations

### Cache Override Impact
- **Single update**: Minimal impact, instant effect
- **Version increment**: No immediate impact, gradual refresh as pages accessed
- **Complete clear**: High API usage spike, significant cost increase

### Recommended Approach
1. Use single updates for corrections (low cost)
2. Use version increment for bulk updates (controlled cost)
3. Avoid complete cache clear unless necessary (high cost)

### Cost Estimation
```
Scenario: 10,000 cached translations

Single Update:    $0 (no API calls)
Version Increment: $1-10 (gradual re-translation over time)
Complete Clear:    $10-50 (immediate re-translation on next access)
```

## API Reference

### Update Translation Endpoint

```
POST /wp-admin/admin-ajax.php
Action: lg_aitrans_update_translation
```

**Parameters:**
- `action` (string, required) - Must be `lg_aitrans_update_translation`
- `nonce` (string, required) - WordPress nonce for `lg_aitranslator_frontend`
- `cache_key` (string, required) - Cache key in format `text_{hash}_{lang}`
- `translation` (string, required) - Corrected translation text

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Translation updated successfully",
    "cache_key": "text_abc123_ja"
  }
}
```

**Error Codes:**
- `403` - Unauthorized (not administrator) or security check failed
- `400` - Invalid cache key or missing translation
- `500` - Cache update failed

### Increment Cache Version Endpoint

```
POST /wp-admin/admin-ajax.php
Action: lg_aitrans_increment_cache_version
```

**Parameters:**
- `action` (string, required) - Must be `lg_aitrans_increment_cache_version`
- `nonce` (string, required) - WordPress nonce for `lg_aitranslator_admin`

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Cache version incremented from 1 to 2. All translations will be refreshed."
  }
}
```

### Clear Cache Endpoint

```
POST /wp-admin/admin-ajax.php
Action: lg_aitrans_clear_cache
```

**Parameters:**
- `action` (string, required) - Must be `lg_aitrans_clear_cache`
- `nonce` (string, required) - WordPress nonce for `lg_aitranslator_admin`

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Cache cleared successfully!"
  }
}
```

## Related Documentation

- [Admin Settings Configuration](admin-settings-configuration.md)
- [Translation Cache System](../lg-aitranslator/includes/class-translation-cache.php)
- [REST API Documentation](api-reference.md)

## Support

For issues with cache override functionality:
1. Check WordPress debug log for errors
2. Verify administrator permissions
3. Test with default WordPress theme
4. Review browser console for JavaScript errors
5. Open GitHub issue with reproduction steps
