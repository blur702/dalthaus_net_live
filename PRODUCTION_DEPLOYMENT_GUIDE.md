# 🚀 Dalthaus.net Production Deployment Guide

## Overview

This guide provides complete instructions for deploying and testing the Dalthaus Photography CMS on the production server at dalthaus.net. All scripts and tools have been created and pushed to GitHub.

## 📋 Created Tools & Scripts

### 1. Auto-Deploy Script (`auto-deploy.php`)
**URL:** `https://dalthaus.net/auto-deploy.php?token=deploy-YYYYMMDD`

**Features:**
- Pull latest code from GitHub
- Enable/disable maintenance mode
- Check git status and deployment history
- Database-driven maintenance mode control
- Cache clearing functionality

**Daily Token Format:** `deploy-20250829` (changes daily for security)

**Actions Available:**
- `?action=status` - Check current deployment status
- `?action=pull` - Pull latest code from GitHub
- `?action=maintenance_on` - Enable maintenance mode
- `?action=maintenance_off` - Disable maintenance mode

### 2. Production Test Suite (`production-test-suite.php`)
**URL:** `https://dalthaus.net/production-test-suite.php?token=test-YYYYMMDD`

**Features:**
- Automated infrastructure testing
- Database connection and schema validation
- File permissions verification
- HTTP endpoint testing
- CSS/JS asset loading verification
- Content existence checks
- Maintenance mode functionality testing
- Font loading verification

**Daily Token Format:** `test-20250829` (changes daily for security)

### 3. Feature Checklist (`feature-checklist.php`)
**URL:** `https://dalthaus.net/feature-checklist.php?token=checklist-YYYYMMDD`

**Features:**
- Interactive manual testing checklist
- 50+ comprehensive test cases across 7 categories
- Progress tracking with completion percentages
- Notes and issue tracking for each feature
- Real-time status updates
- Completion report generation

**Daily Token Format:** `checklist-20250829` (changes daily for security)

### 4. Complete Deployment Pipeline (`deploy-and-test.php`)
**URL:** `https://dalthaus.net/deploy-and-test.php?token=deploy-test-YYYYMMDD`

**Features:**
- 5-step deployment workflow
- Integrated code deployment
- Automated test execution
- Manual testing guidance
- Final deployment reporting
- Progress tracking with visual indicators

**Daily Token Format:** `deploy-test-20250829` (changes daily for security)

## 🔧 Deployment Steps

### Step 1: Deploy Latest Code
1. Visit: `https://dalthaus.net/auto-deploy.php?action=pull&token=deploy-20250829`
2. Verify all git commands executed successfully
3. Check for any file permission issues
4. Confirm latest commit is deployed

### Step 2: Run Automated Tests
1. Visit: `https://dalthaus.net/production-test-suite.php?token=test-20250829`
2. Review all test results (should show green checkmarks)
3. Address any failed tests or warnings
4. Save test report for documentation

### Step 3: Manual Feature Testing
1. Visit: `https://dalthaus.net/feature-checklist.php?token=checklist-20250829`
2. Work through all 7 categories systematically:
   - 🔧 Infrastructure & Configuration
   - 👤 Authentication & Security
   - 📝 Content Management
   - 🔄 Version Control & Autosave
   - 🌐 Public Interface
   - ⚙️ Admin Interface
   - 🚀 Advanced Features
3. Mark each feature as PASS/FAIL/SKIP
4. Add notes for any issues found
5. Generate completion report

### Step 4: Verify Critical Functionality
Test these core features manually:

#### Authentication
- [ ] Admin login works (`https://dalthaus.net/admin/login.php`)
- [ ] Username: `kevin`, Password: `(130Bpm)`
- [ ] Session persists across pages
- [ ] Logout functionality works

#### Public Site
- [ ] Homepage loads with proper styling (`https://dalthaus.net/`)
- [ ] Articles list page works (`https://dalthaus.net/articles`)
- [ ] Photobooks list page works (`https://dalthaus.net/photobooks`)
- [ ] Individual content pages load properly
- [ ] Arimo and Gelasio fonts display correctly

#### Admin Interface
- [ ] Dashboard accessible and functional
- [ ] Article creation and editing works
- [ ] Photobook creation and editing works
- [ ] Image upload functionality works
- [ ] TinyMCE editor loads and functions
- [ ] Drag & drop sorting works

#### Advanced Features
- [ ] Autosave works (every 30 seconds)
- [ ] Version history and restore works
- [ ] Maintenance mode toggle works
- [ ] Cache clearing functions

## 🔍 Troubleshooting

### Common Issues

#### 1. Database Connection Errors
- Check `includes/config.php` for correct credentials
- Verify database server is running
- Test connection directly through phpMyAdmin or CLI

#### 2. File Permission Issues
```bash
# Fix permissions if needed
chmod 755 uploads/ cache/ logs/ temp/
chown -R dalthaus:dalthaus uploads/ cache/ logs/ temp/
```

#### 3. PHP Errors
- Check `logs/php_errors.log` for detailed error messages
- Ensure all required PHP extensions are installed
- Verify PHP version is 8.3+

#### 4. Git Issues
```bash
# If git pull fails
cd /home/dalthaus/public_html
git reset --hard HEAD
git clean -fd
git pull origin main
```

#### 5. CSS/Styling Issues
- Clear browser cache
- Check if CSS files are accessible:
  - `https://dalthaus.net/assets/css/public.css`
  - `https://dalthaus.net/assets/css/admin.css`
- Verify font loading from Google Fonts

### Emergency Procedures

#### Enable Maintenance Mode
```
https://dalthaus.net/auto-deploy.php?action=maintenance_on&token=deploy-20250829
```

#### Disable Maintenance Mode
```
https://dalthaus.net/auto-deploy.php?action=maintenance_off&token=deploy-20250829
```

#### Check Deployment Status
```
https://dalthaus.net/auto-deploy.php?action=status&token=deploy-20250829
```

## 📊 Test Categories & Requirements

### Infrastructure Tests (Must Pass)
- Database connection and schema integrity
- File system permissions and writability
- Configuration validation
- Basic HTTP endpoint accessibility

### Functionality Tests (Must Pass)
- Authentication and session management
- CRUD operations for all content types
- File upload and processing
- Cache management

### Performance Tests (Should Pass)
- Page load times under 3 seconds
- Proper image optimization
- Font loading performance
- Responsive design functionality

### Advanced Features (Optional)
- Document import functionality
- Search capabilities (if implemented)
- Advanced caching strategies
- Monitoring and analytics

## 🎯 Success Criteria

### Minimum Requirements (Must Pass)
- [ ] 100% of infrastructure tests pass
- [ ] All authentication functionality works
- [ ] Content can be created, edited, and displayed
- [ ] Public site displays properly with styling
- [ ] Admin interface is fully functional

### Optimal Requirements (Should Pass)
- [ ] 95%+ of all tests pass
- [ ] All advanced features work properly
- [ ] Performance meets expectations (<3s load times)
- [ ] No PHP errors or warnings in logs
- [ ] All security features functioning

### Excellence Requirements (Nice to Have)
- [ ] 100% of all tests pass including optional features
- [ ] Document import works flawlessly
- [ ] Cache optimization provides significant performance boost
- [ ] Monitoring and alerting configured
- [ ] Backup procedures validated

## 📝 Final Checklist

After completing all testing, verify:

- [ ] Site is publicly accessible and properly styled
- [ ] Admin panel is secure and functional
- [ ] All content displays correctly
- [ ] Performance is acceptable
- [ ] No security vulnerabilities identified
- [ ] Backup and recovery procedures documented
- [ ] Monitoring is in place for ongoing operations

## 🚨 Emergency Contacts & Procedures

### If Critical Issues Are Found:
1. **Immediately enable maintenance mode**
2. **Document the issue with screenshots**
3. **Check logs for detailed error information**
4. **Use git to revert to last known good commit if needed**
5. **Test fix in staging before deploying to production**

### Log Files to Check:
- `/home/dalthaus/public_html/logs/app.log`
- `/home/dalthaus/public_html/logs/php_errors.log`
- Server error logs (varies by hosting provider)

## 🔗 Quick Access URLs

All URLs require daily tokens in format: `token-20250829`

- **Main Site:** `https://dalthaus.net/`
- **Admin Login:** `https://dalthaus.net/admin/login.php`
- **Deployment Control:** `https://dalthaus.net/auto-deploy.php?token=deploy-YYYYMMDD`
- **Test Suite:** `https://dalthaus.net/production-test-suite.php?token=test-YYYYMMDD`
- **Feature Checklist:** `https://dalthaus.net/feature-checklist.php?token=checklist-YYYYMMDD`
- **Full Pipeline:** `https://dalthaus.net/deploy-and-test.php?token=deploy-test-YYYYMMDD`

---

**Note:** All deployment scripts use daily-rotating tokens for security. Replace `YYYYMMDD` with current date (e.g., `20250829`).

This deployment has been thoroughly planned and all tools are ready for use. Follow the steps systematically and document any issues found for future reference.