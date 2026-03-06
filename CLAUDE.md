# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP/MySQL CMS application with MVC architecture. The system provides content management, user authentication, page management, and menu system capabilities.

## 🚀 Deployment Workflow

**IMPORTANT**: This project uses an SSH deployment agent for production deployments. See `DEPLOYMENT_WORKFLOW.md` for complete instructions.

### Quick Deployment
```bash
# Deploy to production server
python agents/deploy_agent.py deploy main

# Check server status
python agents/deploy_agent.py status
```

### SSH Agent Setup
1. Create a `.env` file in the project root with your SSH credentials:
   ```
   SSH_USER=your_username
   SSH_PASS=your_password
   WEB_ROOT=/home/username/public_html
   CONFIG_PATH=/home/username/public_html/config/config.php
   ```
2. Install dependencies: `pip install paramiko python-dotenv`
3. Test connection: `python3 agents/deploy_agent.py status`

**Note**: `.env` contains credentials and is gitignored for security.

### Deployment Commands
```bash
# Check server status and git repository
python3 agents/deploy_agent.py status

# Deploy code to production
python3 agents/deploy_agent.py deploy main

# Pull latest code only
python3 agents/deploy_agent.py pull main

# Test database connection
python3 agents/deploy_agent.py db

# Server health check
python3 agents/deploy_agent.py health
```

### Production Server Details
- **Host:** mi3-cl9-its2.a2hosting.com
- **Port:** 7822
- **Web Root:** /home/dalthaus/public_html
- **Config:** /home/dalthaus/public_html/config/config.php

## Key Architecture

### MVC Structure
- **Controllers**: Located in `src/Controllers/` with Admin and Public namespaces
  - Admin controllers require authentication and handle backend operations
  - Public controllers serve frontend pages
  - All controllers extend `BaseController` which provides common functionality

### Routing System
- Routes defined in `config/routes.php` using route groups
- Router has been refactored to use namespace groups and prefix support
- Route parameters are passed directly as method arguments to controller actions
- Pattern: `/admin/resource/{id}/action` maps to `Admin\Resource::action($id)`

### Database Access
- Models extend `BaseModel` providing CRUD operations
- Database class uses PDO with prepared statements
- Models return arrays for views (use `toArray()` method when converting from objects)
- Connection credentials in `config/config.php`

### View System
- Views located in `src/Views/admin/` and `src/Views/public/`
- View class provides escaping, CSRF tokens, and template rendering
- CSRF token is passed as `$csrf_token` variable to views
- Use `$this->escape()` for XSS protection in views

## Development Commands

### Starting the Application
```bash
# Start PHP built-in server
php -S localhost:8000 router.php

# With increased upload limits
php -d upload_max_filesize=20M -d post_max_size=25M -S localhost:8000 router.php
```

### Database Setup
```bash
# Import database schema
mysql -u cms_user -p'cms_password' cms_db < database.sql

# Default admin credentials
Username: kevin
Password: (130Bpm)
```

### Testing
```bash
# Run PHPUnit tests
composer test

# Run with coverage
composer test-coverage

# Run Playwright E2E tests
npm test

# Run specific test
npm run test:diagnosis

# Run tests with headed browser
npm run test:headed

# Debug tests
npm run test:debug
```

### Code Quality
```bash
# Check coding standards (PSR-12)
composer cs-check

# Fix coding standards automatically
composer cs-fix

# Run static analysis (PHPStan)
composer analyse

# Run all quality checks
composer quality
```

### Development Workflow
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Start development server
php -S localhost:8000 router.php

# Watch tests during development
npm run test:headed
```

## Critical Implementation Details

### Authentication
- Session-based authentication with `$_SESSION['user_id']` and `$_SESSION['is_admin']`
- CSRF protection on all POST requests using `_token` field
- Admin routes check authentication in BaseController constructor

### File Uploads
- Organized in `/uploads/` with subdirectories by type and year/month
- Structure: `/uploads/content/featured/YYYY/MM/`
- Upload limits configured in `.user.ini` (20MB files, 25MB POST)

### Menu System
- Database tables: `menus` (menu groups) and `menu_items` (individual links)
- Menu columns: `menu_id`, `menu_name` (not `name` or `location`)
- Three default menus: main (ID:1), footer (ID:2), sidebar (ID:3)

### Content Types
- Articles and Photobooks stored in `content` table with `type` field
- Pages stored in separate `pages` table
- URL aliases for SEO-friendly URLs

### Common Pitfalls to Avoid
1. Views expect arrays, not objects - always use `toArray()` on models
2. Controller methods with route parameters must accept them as arguments
3. CSRF token in views is `$csrf_token`, not `$this->csrfToken()`
4. Database columns: `menu_name` not `name`, no `location` or `is_active` fields in menus
5. Form actions must match routes exactly (e.g., `/admin/content/store` not `/admin/content/create`)

## Testing Endpoints

### Public Pages
- `/` - Homepage
- `/articles` - Articles listing
- `/photobooks` - Photobooks listing
- `/article/{alias}` - Single article
- `/page/{alias}` - Static page

### Admin Pages (requires authentication)
- `/admin/login` - Login page
- `/admin/dashboard` - Main dashboard
- `/admin/content` - Content management
- `/admin/pages` - Page management
- `/admin/users` - User management
- `/admin/settings` - Settings
- `/admin/menus` - Menu management
- `/admin/menus/{id}` - Edit specific menu

## Database Credentials
- Host: localhost
- Database: cms_db
- Username: cms_user
- Password: cms_password
- Charset: utf8mb4

## Session Configuration
- Session name: cms_session
- Session lifetime: 24 hours (86400 seconds)
- Cookies are HTTP-only for security

## Error Handling
- Debug mode configured in `config/config.php`
- Custom exception handler for database connection errors
- 404 and 500 error pages with appropriate HTTP status codes

## 📚 Documentation Structure

### Essential Documentation
- **CLAUDE.md** (this file) - Primary project documentation for AI assistants
- **docs/DEPLOYMENT_WORKFLOW.md** - Complete deployment procedures
- **docs/SSH_AGENT_README.md** - SSH deployment agent usage
- **docs/CLOUDFLARE_SETUP.md** - Infrastructure and CDN configuration
- **docs/CLAUDE_QUICK_REFERENCE.md** - Quick reference guide

### Recent Fixes & Features
Located in **docs/fixes/** (3-6 month retention):
- Recent bug fixes and feature implementations
- Dated format: `YYYY-MM-DD-description.md`
- Periodically archived when no longer actively referenced

### Implementation Guides
Located in **docs/implementation/**:
- **media-browser.md** - Media browser system
- **image-404-fix.md** - Image path fixes
- **upload-debugging.md** - Upload troubleshooting
- **logout-fix.md** - Logout functionality
- **enhancements.md** - Feature enhancements
- **form-styling.md** - UI/form improvements

### Historical Records
Located in **docs/archive/**:
- **tests/** - Test reports and verification
  - `autosave-implementation-2025.md`
  - `production-deployment-tests-2025.md`
  - `feature-tests-2025.md`
- **debugging/** - Debugging session logs
  - `debugging-sessions-2025.md`

### Documentation Guidelines
1. **Current docs** stay in root or docs/ main directory
2. **Recent fixes** (< 6 months) go in docs/fixes/
3. **Implementation guides** go in docs/implementation/
4. **Historical records** are archived in docs/archive/
5. **Consolidate** related documents to reduce clutter

## Cloudflare Development Mode

### Disabling Cloudflare Cache During Development
When actively developing, Cloudflare's caching can interfere with seeing changes immediately. Use these scripts to control caching:

#### Disable Caching (Development Mode)
```bash
# Add cache bypass headers to .htaccess
python3 disable_cloudflare_cache.py
```
This script adds headers that tell Cloudflare and browsers not to cache pages.

#### Re-enable Caching (Production Mode)
```bash
# Remove development headers from .htaccess
python3 remove_dev_headers.py
```
Run this when development is complete to restore normal caching.

#### Alternative Methods
1. **Cloudflare Dashboard**: Enable "Development Mode" in Cloudflare dashboard
   - Go to: Settings > Caching > Configuration > Development Mode
   - This bypasses cache for 3 hours

2. **Page Rules**: Create a Page Rule in Cloudflare
   - Pattern: `*dalthaus.net/*`
   - Setting: Cache Level = Bypass

### Important Notes
- **index.html redirect**: The site uses an index.html that redirects to index.php for compatibility
- When index.html is missing, direct access to `/index.php` may return 404 due to Apache/Cloudflare interaction
- Always test with cache bypass during development: `curl "https://dalthaus.net/?nocache=$(date +%s)"`