# Debugging Sessions (2025)

This document consolidates all debugging session reports and findings.

---

## Table of Contents

1. [Authentication Debugging](#authentication-debugging)
2. [Content Reordering Debug](#content-reordering-debug)
3. [Production Debug Sessions](#production-debug-sessions)
4. [General Debug Reports](#general-debug-reports)

---

## Authentication Debugging

> Consolidated from: docs/AUTHENTICATION_DEBUGGING_REPORT.md

### Session Management Issues

**Date:** 2025
**Issue:** Users being logged out unexpectedly
**Environment:** Production

### Investigation Steps
1. Checked session configuration in php.ini
2. Reviewed session lifetime settings
3. Examined cookie settings
4. Analyzed error logs

### Findings
- Session lifetime was too short (1 hour default)
- Remember me feature needed implementation
- CSRF tokens expiring with session

### Resolution
- Increased session lifetime to 24 hours
- Implemented remember me functionality
- Enhanced session logging
- Added session activity tracking

### Code Changes
- `src/Utils/Auth.php` - Enhanced check() method
- `config/config.php` - Updated session_lifetime
- `.htaccess` - Set session.gc_maxlifetime

---

## Content Reordering Debug

> Consolidated from: docs/CONTENT_REORDERING_DEBUG_REPORT.md

### Reorder Functionality Issues

**Date:** 2025
**Issue:** Content reordering not saving correctly
**Environment:** Development & Production

### Investigation Steps
1. Reviewed JavaScript drag-and-drop code
2. Checked AJAX endpoint responses
3. Examined database update queries
4. Tested with various content types

### Findings
- JavaScript was sending incorrect order data format
- Backend expected array, receiving JSON string
- No validation on order data
- Missing error handling

### Resolution
- Fixed JavaScript to send proper format
- Added JSON parsing on backend
- Implemented order data validation
- Enhanced error messages

### Code Changes
- `src/Views/Layouts/admin.php` - Fixed JavaScript
- `src/Controllers/Admin/*.php` - Added validation
- `src/Models/Content.php` - Improved updateSortOrder()

---

## Production Debug Sessions

> Consolidated from: docs/PRODUCTION_DEBUG_REPORT.md

### Various Production Issues

#### Issue #1: Image Upload Failures
**Date:** 2025-09
**Symptoms:** Images not uploading via TinyMCE
**Cause:** File permissions on uploads directory
**Resolution:** Set correct permissions (755 for dirs, 644 for files)

#### Issue #2: CSS Not Loading
**Date:** 2025-09
**Symptoms:** Styles not applying on certain pages
**Cause:** Tailwind CDN blocked by CSP
**Resolution:** Added cdn.tailwindcss.com to CSP

#### Issue #3: Menu Items Not Displaying
**Date:** 2025-09
**Symptoms:** Navigation menus empty on frontend
**Cause:** Database column name mismatch (menu_name vs name)
**Resolution:** Updated queries to use correct column names

#### Issue #4: Session Timeouts
**Date:** 2025-10
**Symptoms:** Users redirected to login during work
**Cause:** Session lifetime too short
**Resolution:** Increased to 24 hours, added logging

---

## General Debug Reports

> Consolidated from: docs/debug-report.md

### Debugging Techniques Used

#### 1. Error Log Analysis
- Location: `/home/dalthaus/public_html/logs/error.log`
- Useful for PHP errors, warnings, and custom log entries
- Added context-rich logging throughout codebase

#### 2. Browser DevTools
- Network tab for AJAX requests
- Console for JavaScript errors
- Application tab for session/cookie inspection

#### 3. Database Queries
- Direct MySQL queries to verify data
- Explain plans for slow queries
- Transaction logs for data integrity

#### 4. SSH Debugging
- Real-time log monitoring with `tail -f`
- File permission checks
- PHP configuration verification

### Common Debug Patterns

#### Pattern 1: Authentication Issues
1. Check session cookie exists
2. Verify session data in PHP
3. Check session lifetime hasn't expired
4. Review CSRF token validity

#### Pattern 2: Upload Issues
1. Check file permissions
2. Verify upload_max_filesize setting
3. Check post_max_size setting
4. Review error logs for PHP errors

#### Pattern 3: Database Issues
1. Test query in MySQL directly
2. Check table structure matches code
3. Verify foreign key constraints
4. Review transaction logs

---

## Debugging Tools & Resources

### PHP Debugging
```php
// Enable error display (development only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Custom logging
error_log("Debug: " . print_r($data, true));

// Variable inspection
var_dump($variable);
die();
```

### JavaScript Debugging
```javascript
// Console logging
console.log('Debug:', data);
console.table(arrayData);

// Breakpoints
debugger;

// Network monitoring
console.time('ajax-request');
// ... ajax call ...
console.timeEnd('ajax-request');
```

### MySQL Debugging
```sql
-- Show table structure
DESCRIBE table_name;

-- Explain query performance
EXPLAIN SELECT * FROM content WHERE status = 'published';

-- Show processlist
SHOW PROCESSLIST;
```

---

## Lessons Learned

### 1. Always Log Context
- Include request URI, user ID, and timestamp
- Use structured logging format
- Log both successes and failures

### 2. Test in Production-Like Environment
- Match PHP versions
- Use same database
- Similar server resources

### 3. Monitor Continuously
- Set up error log monitoring
- Track response times
- Watch database query performance

### 4. Document Issues
- Record symptoms, cause, and resolution
- Note code changes made
- Update runbooks

---

## Related Documentation

- [CLAUDE.md](../../../CLAUDE.md) - Project overview and common issues
- [DEPLOYMENT_WORKFLOW.md](../../DEPLOYMENT_WORKFLOW.md) - Deployment debugging
- [implementation/](../../implementation/) - Feature-specific debugging

---

*This document consolidates historical debugging sessions. For active issues, check error logs and GitHub issues.*
