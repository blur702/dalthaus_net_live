# EMERGENCY DEPLOYMENT INSTRUCTIONS - DALTHAUS.NET

## CRITICAL SITUATION - IMMEDIATE ACTION REQUIRED

The production server needs emergency fixes deployed. Automated deployment is blocked by local changes.

## DEPLOYMENT METHOD 1: Direct File Upload (RECOMMENDED)

### Step 1: Download the deployment script
1. Download this file from GitHub: https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/deploy-emergency.php
2. Save it as `deploy-emergency.php`

### Step 2: Upload to server
Upload `deploy-emergency.php` to the server root directory (same level as index.php)

### Step 3: Execute emergency deployment
Visit this URL in your browser:
```
https://dalthaus.net/deploy-emergency.php?action=deploy&token=fix2025
```

### Step 4: Verify deployment
After the script completes, test these URLs:
- https://dalthaus.net/ (main site should load)
- https://dalthaus.net/admin/ (admin should be accessible)  
- https://dalthaus.net/setup.php (should be blocked)

---

## DEPLOYMENT METHOD 2: Manual File Upload

If Method 1 fails, upload these files directly from GitHub:

### Files to Download and Upload:
1. **EMERGENCY_MASTER_FIX.php** - https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_MASTER_FIX.php
2. **EMERGENCY_FIX_01_DATABASE.php** - https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_01_DATABASE.php
3. **EMERGENCY_FIX_02_SECURITY.php** - https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_02_SECURITY.php
4. **EMERGENCY_FIX_03_CLEANUP.php** - https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_03_CLEANUP.php
5. **EMERGENCY_FIX_04_VERIFY.php** - https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/EMERGENCY_FIX_04_VERIFY.php

### Execute via SSH/Terminal:
```bash
cd /path/to/website/root
php EMERGENCY_MASTER_FIX.php
```

### Execute via Web Browser:
If you have PHP execution enabled via web, visit:
```
https://dalthaus.net/EMERGENCY_MASTER_FIX.php
```

---

## DEPLOYMENT METHOD 3: Fix Git Issues First

If you have SSH access to the server:

### Step 1: Fix git conflicts
```bash
cd /path/to/website/root
git stash  # Save local changes
git pull origin main  # Pull latest code
git stash pop  # Restore local changes if needed
```

### Step 2: Execute emergency fixes
```bash
php EMERGENCY_MASTER_FIX.php
```

---

## What the Emergency Fixes Do:

1. **Database Fix** - Corrects database connection issues
2. **Security Fix** - Enables HTTPS redirect, secures setup.php
3. **Cleanup** - Removes debug files and test scripts
4. **Verification** - Tests that all fixes are working

---

## Expected Results:

After successful deployment:
- ✅ Site loads at https://dalthaus.net
- ✅ Admin accessible at https://dalthaus.net/admin/
- ✅ setup.php is blocked (shows 403 or redirect)
- ✅ HTTPS redirect working
- ✅ Database connections working
- ✅ Debug files removed

---

## Troubleshooting:

### If deployment script fails:
1. Check file permissions (should be 644 for PHP files)
2. Verify PHP has write permissions to create files
3. Check server error logs

### If fixes fail:
1. Check that all 5 emergency fix files are present
2. Verify PHP CLI is available (`which php`)
3. Check database credentials in includes/config.php

### If site still has issues:
1. Check web server error logs
2. Verify .htaccess syntax
3. Test database connection manually

---

## Post-Deployment Cleanup:

Once everything is working, clean up the emergency files:
```bash
rm EMERGENCY_*.php
rm deploy-emergency.php
```

---

## Emergency Contacts:

If deployment fails, document the exact error messages and:
1. Check server error logs
2. Verify file permissions
3. Test individual fix scripts
4. Contact system administrator if needed

---

**TIME SENSITIVE: Execute immediately to restore site functionality**