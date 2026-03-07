-- Migration: Create blog_posts table
-- Date: 2026-03-07
-- Description: Add dedicated blog functionality separate from articles

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url_alias` varchar(255) NOT NULL,
  `excerpt` text,
  `body` longtext,
  `tags` varchar(500) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `featured_image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `related_content_ids` varchar(255) DEFAULT NULL COMMENT 'Comma-separated list of related content IDs',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `url_alias` (`url_alias`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tags` (`tags`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Add Blog menu item to main navigation
--

INSERT IGNORE INTO `menu_items` (`menu_id`, `label`, `link`, `sort_order`) VALUES
(1, 'Blog', '/blog', 4);

-- Add Blog menu item to footer navigation
INSERT IGNORE INTO `menu_items` (`menu_id`, `label`, `link`, `sort_order`) VALUES
(2, 'Blog', '/blog', 4);

-- --------------------------------------------------------

--
-- Add blog-related settings
--

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('blog_posts_per_page', '10'),
('blog_excerpt_length', '200'),
('blog_meta_description', 'Latest blog posts and insights'),
('blog_meta_keywords', 'blog, photography, insights, behind the scenes');