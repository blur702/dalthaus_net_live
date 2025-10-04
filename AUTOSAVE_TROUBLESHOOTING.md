# Auto-save Troubleshooting Guide

## Problem: Auto-save indicators not visible

The auto-save functionality is deployed but users aren't seeing the status indicators. This guide helps diagnose and fix the issue.

## Quick Diagnosis Steps

### 1. Check Browser Console
1. Open browser DevTools (F12)
2. Go to Console tab
3. Navigate to a content create/edit page
4. Look for auto-save related messages:
   - `AutoSave: DOM loaded, checking for content form...`
   - `AutoSave: Content form found: true`
   - `AutoSave: Creating AutoSave instance...`
   - `AutoSave: Starting initialization...`
   - `AutoSave: Creating status indicator...`

### 2. Run Debug Script
1. Copy and paste the contents of `debug_autosave.js` into the browser console
2. Review the diagnostic output
3. Run the manual tests: `manualAutoSaveTest()` and `forceStatusTest()`

### 3. Visual Inspection
Look for the auto-save indicator in the **top-right corner** of the page:
- Should appear as a colored notification box
- Colors: Blue (info), Green (success), Orange (saving), Red (error), Purple (draft created)

## Common Issues and Solutions

### Issue 1: JavaScript Not Loading
**Symptoms:** No console messages about auto-save
**Solution:** 
- Check that `/assets/js/autosave.js` loads successfully in Network tab
- Verify the script tag exists in content create/edit templates
- Clear browser cache

### Issue 2: Form Not Found
**Symptoms:** Console shows "Content form found: false"
**Solution:**
- Verify the form has `id="contentForm"`
- Check that you're on the correct page (/admin/content/create or /admin/content/{id}/edit)

### Issue 3: Status Indicator Not Visible
**Symptoms:** AutoSave initializes but no visual indicator appears
**Solutions:**
1. **CSS Conflicts:** 
   - Run `forceStatusTest()` in console to test if it's a CSS issue
   - Check for other stylesheets overriding the indicator styles
   
2. **Z-index Issues:**
   - The indicator uses `z-index: 9999` 
   - Check if other elements have higher z-index values
   
3. **Positioning Issues:**
   - The indicator is positioned `fixed` at `top: 20px; right: 20px`
   - Check if page layout is interfering

### Issue 4: CSRF Token Issues
**Symptoms:** Auto-save attempts fail with 403 or security errors
**Solution:**
- Verify CSRF token is present in the form: `<input name="_token" value="...">`
- Check that the token is being sent with auto-save requests

### Issue 5: Server Endpoint Issues
**Symptoms:** Auto-save attempts result in 404 or 500 errors
**Solution:**
- Test endpoints manually:
  - POST to `/admin/content/autosave` (for edit mode)
  - POST to `/admin/content/create-draft` (for create mode)
- Check server logs for errors
- Verify routes are configured correctly

## Manual Testing

### Test Auto-save on Edit Page
1. Go to an existing content edit page
2. Open browser console
3. Type some text in the title field
4. Wait 2 seconds after stopping typing
5. Should see "Saving..." then "Saved at [time]" indicators

### Test Draft Creation on Create Page
1. Go to content create page
2. Open browser console
3. Enter a title and wait 2 seconds
4. Should see "Creating draft..." then "Draft created - auto-save enabled"
5. Continue editing - subsequent changes should auto-save

### Force Test Status Indicator
Run this in console to test if the indicator can be displayed:
```javascript
forceStatusTest()
```

### Manual AutoSave Initialization
If auto-save doesn't initialize automatically:
```javascript
manualAutoSaveTest()
```

## Debug Console Commands

### Check Current State
```javascript
console.log('AutoSave instance:', window.autoSave);
console.log('Status element:', document.getElementById('autosave-status'));
console.log('Styles element:', document.getElementById('autosave-styles'));
```

### Force Status Display
```javascript
if (window.autoSave) {
    window.autoSave.showStatus('info', 'Test message');
}
```

### Verify Endpoints
```javascript
// Test autosave endpoint (edit mode only)
fetch('/admin/content/autosave', {method: 'POST'})
  .then(r => console.log('Autosave endpoint status:', r.status));

// Test create-draft endpoint  
fetch('/admin/content/create-draft', {method: 'POST'})
  .then(r => console.log('Create-draft endpoint status:', r.status));
```

## Browser Compatibility

Auto-save is designed to work with:
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

### Known Issues
- **Internet Explorer:** Not supported (uses modern JavaScript features)
- **Safari < 12:** May have issues with fetch API
- **Firefox < 55:** May have CSS grid layout issues

## Cache Issues

If changes aren't taking effect:

1. **Hard Refresh:** Ctrl+F5 (Ctrl+Cmd+R on Mac)
2. **Clear Browser Cache:** 
   - Chrome: DevTools > Application > Storage > Clear site data
   - Firefox: DevTools > Storage > Clear All
3. **Disable Cloudflare Cache:** Use development mode scripts:
   ```bash
   python3 disable_cloudflare_cache.py
   ```

## File Locations

- **AutoSave Script:** `/assets/js/autosave.js`
- **Debug Script:** `/debug_autosave.js`
- **Edit Template:** `/src/Views/Admin/content/edit.php`
- **Create Template:** `/src/Views/Admin/content/create.php`
- **Controller:** `/src/Controllers/Admin/Content.php`
- **Routes:** `/config/routes.php`

## Expected Behavior

### Edit Mode (Existing Content)
1. Page loads → "Auto-save enabled for content ID: X" appears immediately
2. User types → Indicator disappears  
3. User stops typing for 2 seconds → "Saving..." appears
4. Save completes → "Saved at [time]" appears for 3 seconds

### Create Mode (New Content)
1. Page loads → "Auto-save will start after entering title" appears
2. User enters title → "Creating draft..." appears
3. Draft created → "Draft created - auto-save enabled" appears
4. Subsequent edits → Same as edit mode behavior

## Getting Help

If the issue persists after following this guide:

1. **Collect Information:**
   - Browser and version
   - Console error messages
   - Network tab showing failed requests
   - Screenshots of the issue

2. **Run Full Diagnosis:**
   - Run the debug script: `debug_autosave.js`
   - Try manual tests: `manualAutoSaveTest()` and `forceStatusTest()`
   - Test on different browsers

3. **Check Server Logs:**
   - Look for PHP errors in server error logs
   - Check database connection issues
   - Verify auto-save endpoint responses

The auto-save system is designed to be robust and provide clear feedback. If you can't see the indicators, it's likely a CSS/JavaScript loading issue rather than a functionality problem.