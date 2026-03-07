<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\BlogPost;
use CMS\Models\Content as ContentModel;
use CMS\Utils\FileUpload;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

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
        $this->view->layout('admin');
    }

    /**
     * Display blog posts listing
     */
    public function index(): void
    {
        $page = (int) $this->request->get('page', 1);
        $search = $this->request->get('search', '');
        $status = $this->request->get('status', '');
        $tag = $this->request->get('tag', '');
        $sortBy = $this->request->get('sort_by', 'updated_at');
        $sortDir = $this->request->get('sort_dir', 'DESC');
        
        $filters = [
            'search' => $search,
            'status' => $status,
            'tag' => $tag,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];
        
        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = $this->blogPost->getTotalCountForAdmin($status);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;
        
        $posts = $this->blogPost->getAllForAdmin($status, $itemsPerPage, $offset);
        
        // Get all tags for filter dropdown
        $allTags = $this->blogPost->getAllTags();
        
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
        ];
        
        $this->render('admin/blog/index', [
            'posts' => $posts,
            'filters' => $filters,
            'pagination' => $pagination,
            'all_tags' => $allTags,
            'page_title' => 'Blog Management',
        ]);
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        // Get available content for linking
        $contentModel = new ContentModel($this->db);
        $availableContent = $contentModel->getPublishedByType('article') + $contentModel->getPublishedByType('photobook');

        $this->render('admin/blog/create', [
            'available_content' => $availableContent,
            'page_title' => 'Create Blog Post',
        ]);
    }

    /**
     * Store new blog post
     */
    public function store(): void
    {
        try {
            $data = [
                'user_id' => $_SESSION['user_id'],
                'title' => $this->request->post('title', ''),
                'url_alias' => $this->request->post('url_alias', ''),
                'excerpt' => $this->request->post('excerpt', ''),
                'body' => $this->request->post('body', ''),
                'tags' => $this->request->post('tags', ''),
                'status' => $this->request->post('status', BlogPost::STATUS_DRAFT),
                'meta_title' => $this->request->post('meta_title', ''),
                'meta_description' => $this->request->post('meta_description', ''),
                'meta_keywords' => $this->request->post('meta_keywords', ''),
                'related_content_ids' => implode(',', array_filter($this->request->post('related_content', []))),
            ];

            // Validate required fields
            if (empty($data['title']) || empty($data['url_alias'])) {
                throw new Exception('Title and URL alias are required');
            }

            // Handle file upload
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $uploader = new FileUpload([
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                    'max_file_size' => 5 * 1024 * 1024, // 5MB
                    'upload_path' => 'uploads/blog/'
                ]);

                $uploadResult = $uploader->upload($_FILES['featured_image']);
                
                if ($uploadResult['success']) {
                    $data['featured_image'] = $uploadResult['file_path'];
                } else {
                    throw new Exception('Image upload failed: ' . $uploadResult['error']);
                }
            }

            // Generate URL alias if empty
            if (empty($data['url_alias'])) {
                $data['url_alias'] = $this->generateUrlAlias($data['title']);
            }

            $postId = $this->blogPost->createOrUpdate($data);

            if ($postId) {
                $_SESSION['flash_message'] = 'Blog post created successfully!';
                $_SESSION['flash_type'] = 'success';
                
                if ($data['status'] === BlogPost::STATUS_PUBLISHED) {
                    header('Location: /admin/blog');
                } else {
                    header('Location: /admin/blog/edit/' . $postId);
                }
            } else {
                throw new Exception('Failed to create blog post');
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            header('Location: /admin/blog/create');
        }
        exit;
    }

    /**
     * Show edit form
     */
    public function edit(int $postId): void
    {
        $post = $this->blogPost->getByIdFull($postId);
        
        if (!$post) {
            $_SESSION['flash_message'] = 'Blog post not found';
            $_SESSION['flash_type'] = 'error';
            header('Location: /admin/blog');
            exit;
        }

        // Get available content for linking
        $contentModel = new ContentModel($this->db);
        $availableContent = $contentModel->getPublishedByType('article') + $contentModel->getPublishedByType('photobook');

        // Parse related content IDs
        $relatedContentIds = !empty($post['related_content_ids']) ? 
            explode(',', $post['related_content_ids']) : [];

        $this->render('admin/blog/edit', [
            'post' => $post,
            'available_content' => $availableContent,
            'related_content_ids' => $relatedContentIds,
            'page_title' => 'Edit Blog Post: ' . $post['title'],
        ]);
    }

    /**
     * Update blog post
     */
    public function update(int $postId): void
    {
        try {
            $post = $this->blogPost->getByIdFull($postId);
            
            if (!$post) {
                throw new Exception('Blog post not found');
            }

            $data = [
                'post_id' => $postId,
                'title' => $this->request->post('title', ''),
                'url_alias' => $this->request->post('url_alias', ''),
                'excerpt' => $this->request->post('excerpt', ''),
                'body' => $this->request->post('body', ''),
                'tags' => $this->request->post('tags', ''),
                'status' => $this->request->post('status', BlogPost::STATUS_DRAFT),
                'meta_title' => $this->request->post('meta_title', ''),
                'meta_description' => $this->request->post('meta_description', ''),
                'meta_keywords' => $this->request->post('meta_keywords', ''),
                'related_content_ids' => implode(',', array_filter($this->request->post('related_content', []))),
            ];

            // Validate required fields
            if (empty($data['title']) || empty($data['url_alias'])) {
                throw new Exception('Title and URL alias are required');
            }

            // Handle file upload
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $uploader = new FileUpload([
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                    'max_file_size' => 5 * 1024 * 1024, // 5MB
                    'upload_path' => 'uploads/blog/'
                ]);

                $uploadResult = $uploader->upload($_FILES['featured_image']);
                
                if ($uploadResult['success']) {
                    // Delete old image if exists
                    if (!empty($post['featured_image']) && file_exists($post['featured_image'])) {
                        unlink($post['featured_image']);
                    }
                    $data['featured_image'] = $uploadResult['file_path'];
                }
            }

            $result = $this->blogPost->createOrUpdate($data);

            if ($result) {
                $_SESSION['flash_message'] = 'Blog post updated successfully!';
                $_SESSION['flash_type'] = 'success';
            } else {
                throw new Exception('Failed to update blog post');
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
        
        header('Location: /admin/blog/edit/' . $postId);
        exit;
    }

    /**
     * Delete blog post
     */
    public function delete(int $postId): void
    {
        try {
            $post = $this->blogPost->getByIdFull($postId);
            
            if (!$post) {
                throw new Exception('Blog post not found');
            }

            // Delete associated image file
            if (!empty($post['featured_image']) && file_exists($post['featured_image'])) {
                unlink($post['featured_image']);
            }

            $result = $this->blogPost->delete($postId);

            if ($result) {
                $_SESSION['flash_message'] = 'Blog post deleted successfully!';
                $_SESSION['flash_type'] = 'success';
            } else {
                throw new Exception('Failed to delete blog post');
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
        }
        
        header('Location: /admin/blog');
        exit;
    }

    /**
     * Reorder blog posts
     */
    public function reorder(): void
    {
        try {
            $items = $this->request->post('items', []);
            
            foreach ($items as $item) {
                if (isset($item['id']) && isset($item['sort_order'])) {
                    $this->blogPost->createOrUpdate([
                        'post_id' => (int) $item['id'],
                        'sort_order' => (int) $item['sort_order']
                    ]);
                }
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Generate URL alias from title
     */
    private function generateUrlAlias(string $title): string
    {
        $alias = strtolower($title);
        $alias = preg_replace('/[^a-z0-9]+/', '-', $alias);
        $alias = trim($alias, '-');
        
        // Ensure uniqueness
        $counter = 0;
        $baseAlias = $alias;
        
        while ($this->blogPost->getByUrlAlias($alias)) {
            $counter++;
            $alias = $baseAlias . '-' . $counter;
        }
        
        return $alias;
    }
}