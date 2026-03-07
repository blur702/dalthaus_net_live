<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\BlogPost;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Blog extends BaseController
{
    private BlogPost $blogPost;

    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
        $this->blogPost = new BlogPost($db);
    }

    protected function initialize(): void
    {
        $this->view->layout('default');
    }

    /**
     * Display blog posts listing
     */
    public function index(): void
    {
        $page = (int) $this->request->get('page', 1);
        $tag = $this->request->get('tag', '');
        $search = $this->request->get('search', '');
        
        $itemsPerPage = (int) ($this->config['app']['blog_posts_per_page'] ?? 10);
        
        $filters = [];
        if (!empty($tag)) {
            $filters[] = $tag;
        }
        
        // Handle search
        if (!empty($search)) {
            $totalItems = count($this->blogPost->search($search));
            $posts = $this->blogPost->search($search, $itemsPerPage, ($page - 1) * $itemsPerPage);
        } else {
            $totalItems = $this->blogPost->getPublishedCount($filters);
            $posts = $this->blogPost->getPublished($itemsPerPage, ($page - 1) * $itemsPerPage, $filters);
        }
        
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        
        // Get all tags for sidebar
        $allTags = $this->blogPost->getAllTags();
        
        // Get recent posts for sidebar
        $recentPosts = $this->blogPost->getRecent(5);
        
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'base_url' => '/blog'
        ];
        
        // SEO metadata for blog listing
        $metaTitle = 'Blog';
        $metaDescription = $this->config['app']['blog_meta_description'] ?? 'Latest blog posts and insights';
        
        if (!empty($tag)) {
            $metaTitle = "Blog Posts Tagged '{$tag}'";
            $metaDescription = "Blog posts tagged with {$tag}";
        }
        
        if (!empty($search)) {
            $metaTitle = "Blog Search Results for '{$search}'";
            $metaDescription = "Search results for '{$search}' in blog posts";
        }
        
        $this->render('public/blog/index', [
            'posts' => $posts,
            'pagination' => $pagination,
            'current_tag' => $tag,
            'search_term' => $search,
            'all_tags' => $allTags,
            'recent_posts' => $recentPosts,
            'page_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $this->config['app']['blog_meta_keywords'] ?? 'blog, posts, articles',
        ]);
    }

    /**
     * Display single blog post
     */
    public function show(string $urlAlias): void
    {
        $post = $this->blogPost->getByUrlAlias($urlAlias);
        
        if (!$post) {
            $this->render404();
            return;
        }
        
        // Get related content if specified
        $relatedContent = [];
        if (!empty($post['related_content_ids'])) {
            $relatedContent = $this->blogPost->getRelatedContent($post['related_content_ids']);
        }
        
        // Get related posts by tags
        $relatedPosts = [];
        if (!empty($post['tags'])) {
            $tags = array_map('trim', explode(',', $post['tags']));
            $relatedPosts = $this->blogPost->getPublished(4, 0, $tags);
            
            // Remove current post from related posts
            $relatedPosts = array_filter($relatedPosts, function($relatedPost) use ($post) {
                return $relatedPost['post_id'] !== $post['post_id'];
            });
        }
        
        // Parse tags for display
        $postTags = [];
        if (!empty($post['tags'])) {
            $postTags = array_map('trim', explode(',', $post['tags']));
        }
        
        // SEO metadata
        $metaTitle = !empty($post['meta_title']) ? $post['meta_title'] : $post['title'];
        $metaDescription = !empty($post['meta_description']) ? $post['meta_description'] : 
            $this->truncateText(strip_tags($post['excerpt'] ?: $post['body']), 160);
        $metaKeywords = !empty($post['meta_keywords']) ? $post['meta_keywords'] : 
            implode(', ', $postTags);
        
        // Structured data for SEO
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => $metaDescription,
            'author' => [
                '@type' => 'Person',
                'name' => $post['username']
            ],
            'datePublished' => $post['published_at'],
            'dateModified' => $post['updated_at'],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->config['app']['site_name'] ?? 'Blog'
            ]
        ];
        
        if (!empty($post['featured_image'])) {
            $structuredData['image'] = $this->getFullUrl($post['featured_image']);
        }
        
        $this->render('public/blog/show', [
            'post' => $post,
            'post_tags' => $postTags,
            'related_content' => $relatedContent,
            'related_posts' => $relatedPosts,
            'page_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'structured_data' => $structuredData,
            'canonical_url' => $this->getFullUrl('/blog/' . $post['url_alias']),
        ]);
    }

    /**
     * Display posts by tag
     */
    public function tag(string $tag): void
    {
        // Redirect to index with tag parameter
        $queryString = http_build_query(['tag' => $tag]);
        header('Location: /blog?' . $queryString);
        exit;
    }

    /**
     * Handle blog search
     */
    public function search(): void
    {
        $searchTerm = $this->request->get('search', '');
        
        if (empty($searchTerm)) {
            header('Location: /blog');
            exit;
        }
        
        // Redirect to index with search parameter
        $queryString = http_build_query(['search' => $searchTerm]);
        header('Location: /blog?' . $queryString);
        exit;
    }

    /**
     * RSS feed for blog posts
     */
    public function rss(): void
    {
        $posts = $this->blogPost->getPublished(20); // Latest 20 posts
        
        header('Content-Type: application/rss+xml; charset=UTF-8');
        
        $siteName = $this->config['app']['site_name'] ?? 'Blog';
        $siteUrl = $this->getBaseUrl();
        $siteDescription = $this->config['app']['site_description'] ?? 'Latest blog posts';
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n";
        echo '<channel>' . "\n";
        echo '<title>' . htmlspecialchars($siteName . ' - Blog') . '</title>' . "\n";
        echo '<link>' . htmlspecialchars($siteUrl . '/blog') . '</link>' . "\n";
        echo '<description>' . htmlspecialchars($siteDescription) . '</description>' . "\n";
        echo '<language>en-us</language>' . "\n";
        echo '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>' . "\n";
        
        foreach ($posts as $post) {
            $postUrl = $siteUrl . '/blog/' . $post['url_alias'];
            $postDate = date(DATE_RSS, strtotime($post['published_at']));
            
            echo '<item>' . "\n";
            echo '<title>' . htmlspecialchars($post['title']) . '</title>' . "\n";
            echo '<link>' . htmlspecialchars($postUrl) . '</link>' . "\n";
            echo '<guid>' . htmlspecialchars($postUrl) . '</guid>' . "\n";
            echo '<pubDate>' . $postDate . '</pubDate>' . "\n";
            echo '<author>' . htmlspecialchars($post['username']) . '</author>' . "\n";
            
            if (!empty($post['excerpt'])) {
                echo '<description>' . htmlspecialchars($post['excerpt']) . '</description>' . "\n";
            }
            
            if (!empty($post['body'])) {
                echo '<content:encoded><![CDATA[' . $post['body'] . ']]></content:encoded>' . "\n";
            }
            
            if (!empty($post['tags'])) {
                $tags = array_map('trim', explode(',', $post['tags']));
                foreach ($tags as $tag) {
                    echo '<category>' . htmlspecialchars($tag) . '</category>' . "\n";
                }
            }
            
            echo '</item>' . "\n";
        }
        
        echo '</channel>' . "\n";
        echo '</rss>' . "\n";
        exit;
    }

    /**
     * Truncate text to specified length
     */
    private function truncateText(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }

    /**
     * Get full URL for a path
     */
    private function getFullUrl(string $path): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        
        return $scheme . '://' . $host . $path;
    }

    /**
     * Get base URL
     */
    private function getBaseUrl(): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $scheme . '://' . $host;
    }

    /**
     * Render 404 page
     */
    private function render404(): void
    {
        http_response_code(404);
        $this->render('public/404', [
            'page_title' => 'Blog Post Not Found',
            'message' => 'The requested blog post could not be found.'
        ]);
    }
}