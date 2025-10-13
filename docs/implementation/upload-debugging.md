# TinyMCE Image Upload Issue - Debugging Findings

## 🔍 Root Cause Identified

The error `SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON` is caused by:

### Primary Issue: Database Connection Failure
**File:** `index.php:19-25`

When the MySQL database server is not running, the application catches the PDOException and returns an HTML "503 Service Unavailable" page instead of JSON for API requests.

```php
if ($exception instanceof PDOException && !$config['app']['debug'] && (...)) {
    http_response_code(503);
    echo "<!DOCTYPE html>... [Service Unavailable HTML] ...";
    exit;
}
```

This happens BEFORE the Router middleware can check if it's an AJAX request and return JSON.

## 🧪 Evidence

1. **From your browser console:**
   ```
   AutoSave: Failed SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
   Upload error: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
   ```

2. **From Playwright test:**
   ```
   📥 RESPONSE: 503 http://localhost:8000/admin/login
   Content-Type: text/html; charset=UTF-8
   ```

3. **Direct curl test would show:**
   ```
   HTTP/1.1 503 Service Unavailable
   ```

## ✅ Solutions

### Immediate Fix (for Development)

**Option 1: Start MySQL Server**
```bash
# Windows
net start MySQL80  # or your MySQL service name

# Linux/Mac
sudo systemctl start mysql
```

**Option 2: Fix Maintenance Mode** (if DB is running)
```sql
UPDATE settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode';
```

### Long-term Fix: Improve Exception Handler

The exception handler in `index.php` should detect AJAX requests and return JSON errors:

```php
// In index.php exception handler (around line 19)
if ($exception instanceof PDOException && !$config['app']['debug'] && (...)) {
    // Check if this is an AJAX/API request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $isApiRequest = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
                   strpos($_SERVER['REQUEST_URI'] ?? '', '/upload/') !== false ||
                   strpos($_SERVER['REQUEST_URI'] ?? '', '/autosave') !== false;

    if ($isAjax || $isApiRequest) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Service temporarily unavailable',
            'message' => 'Database connection failed. Please try again later.'
        ]);
        exit;
    }

    // Otherwise return HTML error page
    http_response_code(503);
    echo "<!DOCTYPE html>...";
    exit;
}
```

## 📋 What We Fixed (Already Applied)

### 1. Router Middleware ✅
**File:** `src/Utils/Router.php:98-121`
- Now returns JSON for AJAX/API requests when authentication fails
- Checks for `X-Requested-With: XMLHttpRequest` header
- Checks URL patterns (/api/, /upload/, /autosave)

### 2. TinyMCE Upload Handler ✅
**File:** `assets/js/tinymce-single.js:176-219`
- Custom `images_upload_handler` that sets `X-Requested-With` header
- Better error handling with proper 401 messages

### 3. Autosave AJAX Requests ✅
**File:** `assets/js/autosave.js:428-435`
- Added `X-Requested-With: XMLHttpRequest` header

### 4. Dual Image Upload ✅
**File:** `assets/js/tinymce-single.js:740-746`
- Added `X-Requested-With: XMLHttpRequest` header

### 5. Media Browser API ✅
**File:** `src/Views/admin/media/browser.php`
- Added headers to all API calls

## 🚀 Testing Steps

1. **Start MySQL Server:**
   ```bash
   # Check if MySQL is running
   mysql -u cms_user -p'cms_password' cms_db -e "SELECT 1"
   ```

2. **Check Maintenance Mode:**
   ```bash
   mysql -u cms_user -p'cms_password' cms_db -e "SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'"
   ```

3. **Restart PHP Server:**
   ```bash
   # Kill all PHP servers on port 8000
   taskkill //F //PID <pid>

   # Start fresh server
   php -S localhost:8000 router.php
   ```

4. **Hard Refresh Browser:**
   - Press `Ctrl+Shift+R` or `Ctrl+F5`
   - This forces reload of all JavaScript files

5. **Test Upload:**
   - Navigate to `/admin/content/create?type=photobook`
   - Try uploading an image through TinyMCE
   - Check browser console for errors

## 🔧 Quick Diagnostic Commands

```bash
# Check if MySQL is running (Windows)
sc query MySQL80

# Check if PHP server is running
netstat -ano | findstr :8000

# Check if maintenance mode is on
php -r "
require 'vendor/autoload.php';
\$config = require 'config/config.php';
\$db = CMS\Utils\Database::getInstance(\$config['database']);
\$stmt = \$db->query('SELECT setting_value FROM settings WHERE setting_key = \"maintenance_mode\"');
echo \$stmt->fetch()['setting_value'];
"

# Test upload endpoint directly
curl -X POST http://localhost:8000/admin/upload/tinymce \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Cookie: cms_session=YOUR_SESSION_ID" \
  -F "file=@testing/test-images/test.jpg"
```

## 📝 Summary

The issue was a **two-layer problem**:

1. **Database not running** → Exception handler returns HTML 503 page
2. **Original code didn't handle AJAX properly** → Even with auth, would redirect to login (HTML) instead of returning JSON 401

We fixed layer #2, but layer #1 (database issue) is preventing testing. Once you start MySQL and disable maintenance mode, all uploads should work correctly.

## ✨ Next Steps

1. Start MySQL database server
2. Verify maintenance mode is off
3. Hard refresh browser to clear JavaScript cache
4. Test image upload
5. If still failing, apply the exception handler fix above
