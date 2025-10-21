# LG AITranslator - .htaccess Management Solution

## Problem Identified

The plugin was attempting to update `.htaccess` during activation, but the changes were immediately overwritten by WordPress or other plugins. This caused 404 errors when accessing language-prefixed URLs like `/ja/?p=1`.

## Root Cause

- `.htaccess` file gets managed by WordPress and other plugins
- Writing to `.htaccess` during plugin activation is unreliable
- WordPress `flush_rewrite_rules()` doesn't guarantee `.htaccess` persistence
- Other plugins (like "Fix Plugin Errors") may overwrite `.htaccess` after activation

## Solution Implemented

Following the approach used by the successful `gtranslate` plugin:

### 1. Removed .htaccess Update from Activation Hook
- Removed `update_htaccess()` method from `lg-aitranslator.php`
- Removed all debug logging and `.htaccess` writing from activation
- Keep only essential initialization in `activate()` method

### 2. Added .htaccess Management to Settings Save
- Updated `class-admin-settings.php` to handle `.htaccess` on settings save
- New `update_htaccess()` method that:
  - Reads current `.htaccess` content
  - Removes old LG-AITranslator rules if they exist
  - Generates new rewrite rules based on supported languages
  - **Prepends** rules to existing content (important!)
  - Writes updated content back to `.htaccess`
  - Provides user feedback via admin notices

### 3. Rewrite Rules Format

```apache
# BEGIN LG-AITranslator
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^(en|ja|zh-CN|es|fr)(/(.*))?/?$ index.php?lang=$1&lg_translated_path=$3 [L,QSA]
</IfModule>
# END LG-AITranslator
```

- Pattern dynamically generated from `supported_languages` setting
- Rules placed BEFORE WordPress rules (prepended)
- Uses markers for easy identification and removal

## How It Works

1. **Plugin Installation**:
   - Default settings saved
   - Rewrite rules registered with WordPress
   - `.htaccess` NOT modified yet

2. **First Settings Save**:
   - Admin saves settings (even without changes)
   - `update_htaccess()` adds rewrite rules to `.htaccess`
   - Rules prepended to existing WordPress rules
   - Language URLs immediately start working

3. **URL Processing**:
   - Apache routes `/ja/?p=1` to `index.php?lang=ja&lg_translated_path=`
   - WordPress loads normally
   - `LG_URL_Rewriter` detects language from query var
   - Content translated to Japanese

## Installation Instructions

1. Upload and activate `lg-aitranslator.zip` in WordPress admin
2. Go to Settings → Lifegence AITranslator
3. Configure API keys and languages
4. **Click "Save Settings"** - This triggers `.htaccess` update
5. Test language URLs: `http://yoursite.com/ja/`

## User Workflow

### First-Time Setup
```
Install Plugin → Activate → Settings Page → Save Settings → .htaccess Updated
```

### Changing Languages
```
Settings Page → Modify Supported Languages → Save Settings → .htaccess Regenerated
```

## Benefits of This Approach

1. ✅ **Reliable**: Updates happen when admin explicitly saves settings
2. ✅ **User Control**: Admin sees when `.htaccess` is modified
3. ✅ **Feedback**: Success/error messages shown in admin
4. ✅ **Safe**: Prepends rules instead of replacing entire file
5. ✅ **Maintainable**: Clear markers for rule identification
6. ✅ **Flexible**: Rules regenerated when languages change

## Comparison with Previous Approach

### ❌ Old Approach (Activation Hook)
- Unreliable - often overwritten immediately
- No user feedback
- Silent failures
- Debug logs showed writes succeeded but `.htaccess` still empty

### ✅ New Approach (Settings Save)
- Reliable - happens in admin context
- User sees feedback messages
- Predictable timing
- Proven by gtranslate plugin

## Testing

1. Install plugin
2. Activate plugin
3. Go to Settings → Lifegence AITranslator
4. Click "Save Settings" (no changes needed)
5. Check admin notices for ".htaccess file updated" message
6. Verify `.htaccess` contains LG-AITranslator rules
7. Test language URL: `curl -I http://localhost:8080/ja/?p=1`
8. Should return `HTTP/1.1 200 OK`

## File Changes

### Modified Files
1. `lg-aitranslator/lg-aitranslator.php`
   - Simplified `activate()` method
   - Removed `update_htaccess()` method
   - Removed debug logging

2. `lg-aitranslator/admin/class-admin-settings.php`
   - Added `update_htaccess()` call in `save_settings()`
   - Added new `update_htaccess()` method
   - Added admin notices for `.htaccess` status

## Next Steps

1. Install the new plugin ZIP
2. Save settings to trigger `.htaccess` update
3. Test language URLs
4. Confirm translation functionality works
