# Uploads Directory Structure

This directory contains all uploaded files for the CMS, organized by type and date.

## Directory Structure

```
uploads/
├── content/               # Content-related uploads
│   ├── featured/          # Featured images for articles/photobooks
│   │   └── YYYY/MM/       # Organized by year and month
│   ├── teasers/           # Teaser images for listings
│   │   └── YYYY/MM/       # Organized by year and month
│   └── images/            # Images uploaded via TinyMCE editor
│       └── YYYY/MM/       # Organized by year and month
├── pages/                 # Page-related uploads
│   └── images/            # Images for static pages
│       └── YYYY/MM/       # Organized by year and month
├── users/                 # User-related uploads
│   └── avatars/           # User profile pictures
│       └── YYYY/MM/       # Organized by year and month
└── settings/              # Site settings uploads
    ├── logo.png           # Site logo
    └── favicon.ico        # Site favicon
```

## Security

- The `.htaccess` file restricts direct access to only image files
- PHP files cannot be executed from this directory
- Directory listing is disabled
- All uploaded files are processed and validated before storage

## File Naming

Files are automatically renamed with secure, unique names to prevent:
- Filename collisions
- Path traversal attacks
- Special character issues

## Maximum File Sizes

- Individual files: 5MB
- Total upload per request: 20MB

## Allowed File Types

- Images: JPG, JPEG, PNG, GIF, WebP
- Icons: ICO, PNG (for favicons)

## Automatic Organization

Files are automatically organized into year/month subdirectories to:
- Prevent directory size issues
- Improve file management
- Make backups easier
- Facilitate cleanup of old files