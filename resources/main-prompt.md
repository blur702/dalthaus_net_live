# AI Prompt for PHP/MySQL CMS Build (Final Version v5)

## Project Goal
Build a secure, portable, and high-performance Content Management System (CMS) using a framework-free, object-oriented PHP and MySQL backend. The application must be a direct and precise implementation of the provided architectural specifications, feature set, and visual design. The final deliverable must include clean, PSR-12 compliant code, comprehensive PHPDoc comments, and clear Markdown documentation.

## Layout sketches are in ../instructions

---
## Core Architecture & Technical Specifications

### Development Approach
- **Front Controller Pattern:** All web requests must be routed through a single `index.php` entry point. This script will be responsible for parsing the request URI and delegating control to the appropriate logic handler or controller.
- **Separation of Concerns (MVC-like):** Strictly enforce the separation of application logic (Controllers/Models) from presentation (Views). PHP files should handle business logic and data retrieval, then pass prepared data to separate view template files that are primarily HTML.
- **Code Style:** All PHP code must strictly adhere to the **PSR-12 Extended Coding Style Guide**.
- **Single filee with all needed information for database connection and URL and other items that may need manual adjustment.**

### Backend
- **PHP Version:** 8.0+ using a modern, object-oriented approach.
- **Frameworks:** **No PHP frameworks** (e.g., Laravel, Symfony) are permitted.
- **Database:** Use MySQL or MariaDB. All database interactions must be handled through a custom database wrapper class that utilizes **PDO with prepared statements**.
- **Dependencies:** All dependencies must be managed locally within the project.

### Frontend
- **CSS:** Use **Tailwind CSS**, linked via its CDN for simplicity.
- **JavaScript:** Use modern, lightweight libraries for specific tasks:
    - **SortableJS:** For all drag-and-drop interfaces.
    - **Axios or `fetch` API:** For all AJAX/asynchronous requests.
- **Layout Sketches for Pages aree in /sketches**

### Security & Authentication
- **Admin-Only Registration:** **Public user registration must be disabled**. User accounts can only be created from within the admin panel.
- **Secure Login:** Implement a secure user authentication system using `password_hash()` and `password_verify()`. The admin login page **must not** include a password reset or "Forgot Password" feature.
- **Input Sanitization:** Sanitize all user-generated output to prevent XSS attacks.
- **CSRF Protection:** Implement CSRF tokens on all state-changing forms (create, edit, delete, settings) and validate them server-side.
- **File Permissions:** The application must be designed to run with secure file permissions, where the web server user only has write access to the `/uploads` directory.

---
## Database Schema

Provide a `database.sql` file to create the following tables. This file must also include an `INSERT` statement to create a default administrative user.
- **Default User:** username **`kevin`**, password **`(130Bpm)`**. The password must be securely hashed with `password_hash()` in the `INSERT` statement.

- **`users`**: (`user_id` PK, `username` UNIQUE, `email` UNIQUE, `password_hash`, `created_at`)
- **`content`**: (`content_id` PK, `user_id` FK, `content_type` ENUM('article', 'photobook'), `status` ENUM('published', 'draft'), `title`, `url_alias` UNIQUE, `teaser` TEXT, `body` LONGTEXT, `featured_image`, `teaser_image`, `process_document`, `meta_keywords`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `published_at`)
- **`pages`**: (`page_id` PK, `title`, `url_alias` UNIQUE, `body` LONGTEXT, `updated_at`)
- **`settings`**: Key-value store. (`setting_id` PK, `setting_name` UNIQUE, `setting_value` TEXT)
- **`menus`**: Defines navigation groups. (`menu_id` PK, `menu_name` UNIQUE)
- **`menu_items`**: Individual links within a menu. (`item_id` PK, `menu_id` FK, `label`, `link`, `parent_id` FK self-referencing, `sort_order`)

---
## Admin Panel Functionality

### Admin Area Core
- **Authentication & Dashboard:** Secure login at `/admin/login.php`. A placeholder dashboard at `/admin/dashboard.php`.
- **Admin Header:** A persistent header for all admin pages with navigation links as specified in the sketches.

### User Management (`/admin/users.php`)
- Implement a secure interface for administrators to create and view user accounts. This page must support creating new users (by providing a username, email, and password) and listing all existing users.

### Content Management (Articles & Photobooks)
- **Overview (`/admin/content.php`):** A unified list view for both content types, filterable by type. The view must include search and status filters.
- **Create/Edit Form:** A single form for creating/editing content. It must feature a **locally hosted TinyMCE editor with the pagebreak plugin enabled**. An AJAX-based autosave feature must trigger every 60 seconds.
- **Reordering (`/admin/reorder.php`):** A view, filterable by content type, that uses **SortableJS** to enable drag-and-drop reordering of content, which updates the `sort_order` column.

### Static Page Management
- Implement full CRUD functionality for static pages, mirroring the content management system but with a simplified fieldset (Title, Body, Meta).

### Settings Page (`/admin/settings.php`)
- A form to update values in the `settings` table. It must include fields for Site Title, Site Motto, Site Logo (image upload), and Favicon (image upload).

### Menu Management (`/admin/menus.php`)
- An interface to manage menus defined in the `menus` table. The UI must allow an admin to select a menu to edit, add Pages/Articles/Photobooks/Custom Links, and use **SortableJS** to reorder and create nested sub-menus.

---
## Public-Facing Website Layouts

### Global Elements
- **Header & Footer:** The site header and footer must be dynamically populated with data from the `settings` and `menus` tables.
- **Design & Typography:** All visual elements must adhere to the "dead-flat-simple" design philosophy, implemented with **Tailwind CSS**. Use a `max-w-7xl` container for the main content width. All teaser/featured images must maintain a strict **4:3 aspect ratio**.

### Homepage (`/index.php`)
- Implement a two-column layout using Tailwind's grid system (`grid-cols-3`).
- **Left Column (Articles):** Spans 2 columns (`col-span-2`), creating a **66% width**. It displays the 3 most recently published articles.
- **Right Column (Photobooks):** Spans 1 column (`col-span-1`), creating a **33% width**. It displays the 3 most recently published photobooks.
- Each listed item must be a "teaser" component showing the teaser image, linked title, author/date, teaser text, and an italicized "Read More" link.

### Listing Pages (`/articles`, `/photobooks`)
- A single-column, paginated list of all published content of that type, ordered by the custom `sort_order`. Uses the same "teaser" component as the homepage. Features simple numeric pagination.

### Single Item View (`/article/{url_alias}`)
- Displays the full content of an article, photobook, or page.
- **Internal Pagination:** Content from the `body` field must be split by the TinyMCE pagebreak delimiter (`<hr class="mce-pagebreak" />`). The page must display one segment at a time with navigation controls.