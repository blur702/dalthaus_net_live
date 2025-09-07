# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP/MySQL CMS application with MVC architecture. The system provides content management, user authentication, page management, and menu system capabilities.

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
# Run E2E tests
php run_e2e_tests.php

# Run PHPUnit tests (if configured)
make test
make test-unit
make test-integration

# Run specific test
vendor/bin/phpunit tests/path/to/TestFile.php
```

### Code Quality
```bash
# Check coding standards
make cs-check

# Fix coding standards
make cs-fix

# Run static analysis
make analyse

# Run all quality checks
make quality
```

### Development Workflow
```bash
# Setup development environment
make setup

# Start development server
make dev-server

# Watch tests during development
make test-watch
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