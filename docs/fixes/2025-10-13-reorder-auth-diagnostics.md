# Reorder Button Authentication Issue - Investigation & Fix

## Problem Report

User reported that clicking the **"Reorder"** button in the admin area redirects them to the admin login screen instead of showing the reorder interface.

## Investigation Summary

### Affected Areas
- `/admin/content/reorder` - Content reordering
- `/admin/articles/reorder` - Articles reordering
- `/admin/photobooks/reorder` - Photobooks reordering
- `/admin/pages/reorder` - Pages reordering
- `/admin/menus/reorder` (POST) - Menu items reordering

### Root Cause Analysis

The issue is caused by **session expiration** or **session data not persisting properly** between requests. Here's the flow:

1. **User clicks "Reorder" button** → GET request to `/admin/articles/reorder`

2. **Router middleware checks authentication** ([Router.php:88-92](src/Utils/Router.php#L88-L92))
   ```php
   if (!empty($matchedRoute['middleware'])) {
       foreach ($matchedRoute['middleware'] as $middleware) {
           $this->executeMiddleware($middleware);
       }
   }
   ```

3. **Auth middleware calls `Auth::check()`** ([Router.php:100-101](src/Utils/Router.php#L100-L101))
   ```php
   if ($middleware === 'auth') {
       $isAuthenticated = $this->auth->check();
   ```

4. **`Auth::check()` verifies session**:
   - Checks if `$_SESSION['logged_in']` is set
   - Calls `isSessionExpired()` to check `$_SESSION['last_activity']`
   - Updates `$_SESSION['last_activity'] = time()`

5. **If session check fails** → Redirect to `/admin/login`

### Why Sessions Were Expiring

The issue could occur due to several scenarios:

#### Scenario 1: `last_activity` Not Persisting
- `$_SESSION['last_activity']` was being updated in `Auth::check()` line 174
- **However**, session data is only written to disk at script end or with `session_write_close()`
- If PHP didn't properly save the session between requests, `last_activity` would remain stale
- On the next request, `isSessionExpired()` would see the old timestamp and return true

#### Scenario 2: User Idle Time
- Session lifetime is configured as **24 hours** (86400 seconds)
- If user leaves admin area open and doesn't interact for >24 hours, session expires
- Next click on "Reorder" fails authentication

#### Scenario 3: Server Session Garbage Collection
- PHP's session garbage collector (`gc_maxlifetime`) was set to 86400 in `.htaccess`
- But on shared hosting, PHP might use different settings
- Sessions could be deleted before expected expiration

### Logs Showed Insufficient Detail

Previous logging in `Auth::check()` only showed:
```
Auth::check() - Session logged_in: true
Auth::check() - Session valid, returning true
```

This didn't show:
- What the `last_activity` timestamp was
- How long ago it was set
- Whether session was actually expiring

## Solution Applied

### Enhanced Logging in Auth::check()

Added detailed logging to diagnose the issue ([Auth.php:162-186](src/Utils/Auth.php#L162-L186)):

```php
error_log("Auth::check() - Session ID: " . session_id());
error_log("Auth::check() - Session last_activity: " . date('Y-m-d H:i:s', $_SESSION['last_activity']));
error_log("Auth::check() - Current time: " . date('Y-m-d H:i:s'));
```

This allows us to see:
- ✅ Session ID (verify session is active)
- ✅ Last activity timestamp (when user last made a request)
- ✅ Current time (to calculate elapsed time)
- ✅ If session expired, log the elapsed time vs lifetime

### Improved Expiration Logging

When session expires, now logs detailed information:

```php
if ($this->isSessionExpired()) {
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $elapsed = time() - $lastActivity;
    $sessionLifetime = $this->config['session_lifetime'] ?? 3600;
    error_log("Auth::check() - Session EXPIRED! Last activity was {$elapsed} seconds ago (lifetime: {$sessionLifetime})");
    $this->logout();
    return false;
}
```

Output example:
```
Auth::check() - Session EXPIRED! Last activity was 90000 seconds ago (lifetime: 86400)
```

This tells us **exactly why** the session expired.

### Better Activity Tracking

Now logs before/after when updating `last_activity`:

```php
$oldActivity = $_SESSION['last_activity'] ?? 0;
$_SESSION['last_activity'] = time();
error_log("Auth::check() - Updated last_activity from " . date('Y-m-d H:i:s', $oldActivity) . " to " . date('Y-m-d H:i:s', $_SESSION['last_activity']));
```

This shows if `last_activity` is being updated on every request as expected.

## How This Fixes the Issue

### Before the Fix:
- User clicks "Reorder"
- Session check fails (unknown why)
- User redirected to login
- **No visibility into what went wrong**

### After the Fix:
- User clicks "Reorder"
- If session check fails, logs show:
  - ✅ Session ID
  - ✅ Last activity time (e.g., "2025-10-13 10:00:00")
  - ✅ Current time (e.g., "2025-10-13 11:30:00")
  - ✅ Elapsed time (5400 seconds = 1.5 hours)
  - ✅ Whether it exceeded lifetime (86400 seconds)
- **We can now diagnose the exact cause**

### Expected Log Output

**Normal successful check:**
```
Auth::check() - Starting authentication check
Auth::check() - Session ID: abc123def456
Auth::check() - Session logged_in: true
Auth::check() - Session last_activity: 2025-10-13 12:00:00
Auth::check() - Current time: 2025-10-13 12:05:00
Auth::check() - User has active session
Auth::check() - Updated last_activity from 2025-10-13 12:00:00 to 2025-10-13 12:05:00
Auth::check() - Session valid, returning true
```

**Session expired scenario:**
```
Auth::check() - Starting authentication check
Auth::check() - Session ID: abc123def456
Auth::check() - Session logged_in: true
Auth::check() - Session last_activity: 2025-10-12 12:00:00
Auth::check() - Current time: 2025-10-13 13:00:00
Auth::check() - User has active session
Auth::check() - Session EXPIRED! Last activity was 90000 seconds ago (lifetime: 86400)
```

## Testing Recommendations

1. **Monitor Error Logs**: After deployment, check `/home/dalthaus/public_html/logs/error.log` for Auth::check() entries

2. **Test Reorder Functionality**:
   - Log into admin area
   - Wait 5-10 minutes (verify session stays active)
   - Click "Reorder" button on any content type
   - Should work without redirect to login

3. **Test After Extended Idle**:
   - Log into admin area
   - Leave tab open for >24 hours
   - Click "Reorder" button
   - Should redirect to login (expected behavior)
   - Check logs to confirm expiration reason

4. **Verify Session Updates**:
   - Log into admin area
   - Click through multiple pages
   - Check logs show `last_activity` being updated on each request

## Configuration

Current session settings:

**config/config.php:**
```php
'session_lifetime' => 86400  // 24 hours
```

**.htaccess:**
```apache
php_value session.gc_maxlifetime 86400  // 24 hours
```

Both are configured to 24 hours, so sessions should remain valid for a full day of activity.

## Next Steps

If users continue to report the issue after this fix:

1. **Check error logs** for Auth::check() entries showing why session failed
2. **Verify shared hosting settings** - Some hosts override php.ini values
3. **Consider implementing** a JavaScript ping to keep sessions alive
4. **Increase session lifetime** if 24 hours proves insufficient

## Files Modified

- `src/Utils/Auth.php` - Enhanced logging in check() method

## Status

✅ **Enhanced logging deployed** - Ready to diagnose issue if it occurs again
⏳ **Monitoring required** - Need user feedback and log analysis to confirm fix
