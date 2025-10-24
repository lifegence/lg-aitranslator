# How to Edit Translations

## Overview

If you don't like an AI translation, you can edit it directly on your website.

## When to Use

- AI translation sounds unnatural
- Need to match your company's brand voice
- Technical terms need correction
- Cultural adaptation required

## How to Edit Translations

### 1. Enable Edit Mode

While logged in as administrator, visit the translated page.

**Method 1: From Admin Bar**
1. Look at the admin bar (black bar) at the top of the page
2. Click the "✏️ Edit Translation" button
3. Edit mode is now enabled

**Method 2: Add to URL**
Add `?edit_translation=1` to the end of the URL.

Example:
```
Normal URL: https://yoursite.com/ja/about
Edit Mode:  https://yoursite.com/ja/about?edit_translation=1
```

### 2. Edit the Translation

When edit mode is on, translated text on the page becomes **clickable**.

1. **Click the text you want to fix**
   - Hover over text to see a yellow border
   - Click to open the edit box

2. **Type the correct translation**
   - Enter your new translation in the text box
   - Line breaks and HTML formatting are preserved

3. **Save**
   - Click the "Save" button
   - You'll see "Translation updated successfully" when complete

4. **Refresh the page**
   - Press the browser refresh button (F5 key)
   - Your corrected translation will appear

### 3. Exit Edit Mode

- Click "✏️ Edit Translation" in the admin bar again
- Or remove `?edit_translation=1` from the URL

## Usage Examples

### Example 1: Change Button Text

**Original (AI)**: "Buy Now"
**Corrected**: "Shop Now"

1. Add `?edit_translation=1` to the URL
2. Click the "Buy Now" button text
3. Type "Shop Now"
4. Save and refresh the page

### Example 2: Fix Company Name

**Original (AI)**: "Lifegence株式会社" (translated to Japanese)
**Corrected**: "Lifegence Corporation" (keep in English)

1. Turn on edit mode
2. Click the company name
3. Type the correct version
4. Save

### Example 3: Standardize Technical Terms

**Original (AI)**: "Cloud Computing Platform"
**Corrected**: "Cloud Platform" (your company's standard term)

1. Turn on edit mode
2. Click the term
3. Enter your standardized term
4. Save

## Re-translate Everything

If you want to re-translate all pages after improving AI translation quality:

1. **Log in to WordPress admin**
2. Go to **Settings → Lifegence AITranslator → Cache** tab
3. Click **"Increment Cache Version (Force Re-translate)"** button
4. Confirm and execute

All pages will be re-translated on next visit.

## Frequently Asked Questions

### Q: Edit mode doesn't appear

**A**: Make sure you're logged in as an administrator. Regular users cannot see the edit feature.

### Q: Changes don't appear immediately after saving

**A**: Browser cache is the cause. Try these:
- Browser refresh (F5 key)
- Hard reload (Ctrl + F5 / Cmd + Shift + R)
- Clear browser cache

### Q: My edits reverted to the original

**A**: The cache may have been cleared. Edit and save again.

### Q: I want to edit many sentences at once

**A**: Edit one at a time is the standard method. For bulk changes, use "Increment Cache Version" to re-translate everything.

## Notes

### What You Can Do
- ✅ Edit any displayed text
- ✅ Change words or expressions
- ✅ Edit as many times as needed
- ✅ HTML formatting is preserved

### What You Cannot Do
- ❌ Translate images
- ❌ Some menu items (depends on theme)
- ❌ Dynamically generated JavaScript text
- ❌ Change multiple languages at once (edit each language separately)

## Support

If you have problems with the edit feature:

1. Check browser console for errors (press F12 and go to "Console" tab)
2. Check WordPress debug log
3. Contact support if the problem persists

---

**Tip**: Keep a record of your edits. This makes it easier to track what you've changed during large-scale modifications.
