<?php

declare(strict_types=1);

namespace CMS\Models;

use CMS\Utils\QueryBuilder;

/**
 * BlogPost Model
 * 
 * Handles blog posts with tags, SEO metadata, and content linking
 * functionality separate from articles and photobooks.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class BlogPost extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'blog_posts';

    /**
     * Primary key
     */
    protected string $primaryKey = 'post_id';

    /**
     * Blog post statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /**
     * Get published blog posts
     * 
     * @param int|null $limit Number of posts to retrieve
     * @param int|null $offset Offset for pagination
     * @param array $tags Optional tags to filter by
     * @return array
     */
    public function getPublished(?int $limit = null, ?int $offset = null, array $tags = []): array
    {
        $query = $this->queryBuilder()
            ->select([
                'bp.post_id',
                'bp.title',
                'bp.url_alias',
                'bp.excerpt',
                'bp.body',
                'bp.tags',
                'bp.featured_image',
                'bp.published_at',
                'bp.created_at',
                'bp.updated_at',
                'u.username'
            ])
            ->from($this->table . ' bp')
            ->leftJoin('users u', 'bp.user_id = u.user_id')
            ->where('bp.status = ?', [self::STATUS_PUBLISHED])
            ->orderBy('bp.published_at DESC');

        // Add tag filtering if specified
        if (!empty($tags)) {
            $tagConditions = [];
            $tagParams = [];
            foreach ($tags as $tag) {
                $tagConditions[] = 'bp.tags LIKE ?';
                $tagParams[] = '%' . $tag . '%';
            }
            $query->where('(' . implode(' OR ', $tagConditions) . ')', $tagParams);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        return $query->get();
    }

    /**
     * Get published post count
     * 
     * @param array $tags Optional tags to filter by
     * @return int
     */
    public function getPublishedCount(array $tags = []): int
    {
        $query = $this->queryBuilder()
            ->select(['COUNT(*) as count'])
            ->from($this->table)
            ->where('status = ?', [self::STATUS_PUBLISHED]);

        // Add tag filtering if specified
        if (!empty($tags)) {
            $tagConditions = [];
            $tagParams = [];
            foreach ($tags as $tag) {
                $tagConditions[] = 'tags LIKE ?';
                $tagParams[] = '%' . $tag . '%';
            }
            $query->where('(' . implode(' OR ', $tagConditions) . ')', $tagParams);
        }

        $result = $query->get();
        return (int) $result[0]['count'];
    }

    /**
     * Get post by URL alias
     * 
     * @param string $urlAlias
     * @return array|null
     */
    public function getByUrlAlias(string $urlAlias): ?array
    {
        $posts = $this->queryBuilder()
            ->select([
                'bp.post_id',
                'bp.title',
                'bp.url_alias',
                'bp.excerpt',
                'bp.body',
                'bp.tags',
                'bp.featured_image',
                'bp.meta_title',
                'bp.meta_description',
                'bp.meta_keywords',
                'bp.related_content_ids',
                'bp.published_at',
                'bp.created_at',
                'bp.updated_at',
                'u.username'
            ])
            ->from($this->table . ' bp')
            ->leftJoin('users u', 'bp.user_id = u.user_id')
            ->where('bp.url_alias = ?', [$urlAlias])
            ->where('bp.status = ?', [self::STATUS_PUBLISHED])
            ->get();

        return !empty($posts) ? $posts[0] : null;
    }

    /**
     * Get all posts for admin (including drafts)
     * 
     * @param string|null $status Filter by status
     * @param int|null $limit Number of posts to retrieve
     * @param int|null $offset Offset for pagination
     * @return array
     */
    public function getAllForAdmin(?string $status = null, ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->queryBuilder()
            ->select([
                'bp.post_id',
                'bp.title',
                'bp.url_alias',
                'bp.excerpt',
                'bp.tags',
                'bp.status',
                'bp.featured_image',
                'bp.published_at',
                'bp.created_at',
                'bp.updated_at',
                'u.username'
            ])
            ->from($this->table . ' bp')
            ->leftJoin('users u', 'bp.user_id = u.user_id')
            ->orderBy('bp.updated_at DESC');

        if ($status !== null) {
            $query->where('bp.status = ?', [$status]);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        return $query->get();
    }

    /**
     * Get total count for admin
     * 
     * @param string|null $status Filter by status
     * @return int
     */
    public function getTotalCountForAdmin(?string $status = null): int
    {
        $query = $this->queryBuilder()
            ->select(['COUNT(*) as count'])
            ->from($this->table);

        if ($status !== null) {
            $query->where('status = ?', [$status]);
        }

        $result = $query->get();
        return (int) $result[0]['count'];
    }

    /**
     * Get post by ID with all fields
     * 
     * @param int $postId
     * @return array|null
     */
    public function getByIdFull(int $postId): ?array
    {
        $posts = $this->queryBuilder()
            ->select(['*'])
            ->from($this->table)
            ->where('post_id = ?', [$postId])
            ->get();

        return !empty($posts) ? $posts[0] : null;
    }

    /**
     * Get all unique tags
     * 
     * @return array
     */
    public function getAllTags(): array
    {
        $posts = $this->queryBuilder()
            ->select(['tags'])
            ->from($this->table)
            ->where('status = ?', [self::STATUS_PUBLISHED])
            ->where('tags IS NOT NULL AND tags != ""')
            ->get();

        $allTags = [];
        foreach ($posts as $post) {
            if (!empty($post['tags'])) {
                $tags = array_map('trim', explode(',', $post['tags']));
                $allTags = array_merge($allTags, $tags);
            }
        }

        return array_unique(array_filter($allTags));
    }

    /**
     * Get posts by tag
     * 
     * @param string $tag
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function getByTag(string $tag, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getPublished($limit, $offset, [$tag]);
    }

    /**
     * Get related content for a blog post
     * 
     * @param string $relatedContentIds Comma-separated content IDs
     * @return array
     */
    public function getRelatedContent(string $relatedContentIds): array
    {
        if (empty($relatedContentIds)) {
            return [];
        }

        $contentIds = array_map('trim', explode(',', $relatedContentIds));
        $contentIds = array_filter($contentIds, 'is_numeric');

        if (empty($contentIds)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($contentIds) - 1) . '?';

        return $this->queryBuilder()
            ->select([
                'content_id',
                'title',
                'url_alias',
                'content_type',
                'teaser_image',
                'published_at'
            ])
            ->from('content')
            ->where("content_id IN ($placeholders)", $contentIds)
            ->where('status = ?', ['published'])
            ->orderBy('published_at DESC')
            ->get();
    }

    /**
     * Create or update blog post
     * 
     * @param array $data
     * @return int|bool
     */
    public function createOrUpdate(array $data)
    {
        // Set published_at when status changes to published
        if (isset($data['status']) && $data['status'] === self::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        // Generate meta fields if not provided
        if (!isset($data['meta_title']) || empty($data['meta_title'])) {
            $data['meta_title'] = $data['title'] ?? '';
        }

        if (!isset($data['meta_description']) || empty($data['meta_description'])) {
            $data['meta_description'] = $this->generateMetaDescription($data['excerpt'] ?? $data['body'] ?? '');
        }

        return parent::save($data);
    }

    /**
     * Generate meta description from content
     * 
     * @param string $content
     * @return string
     */
    private function generateMetaDescription(string $content): string
    {
        // Strip HTML tags and get first 160 characters
        $text = strip_tags($content);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        if (strlen($text) > 160) {
            $text = substr($text, 0, 157) . '...';
        }
        
        return $text;
    }

    /**
     * Get recent posts for sidebar/widgets
     * 
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->queryBuilder()
            ->select([
                'post_id',
                'title',
                'url_alias',
                'featured_image',
                'published_at'
            ])
            ->from($this->table)
            ->where('status = ?', [self::STATUS_PUBLISHED])
            ->orderBy('published_at DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Search blog posts
     * 
     * @param string $searchTerm
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function search(string $searchTerm, ?int $limit = null, ?int $offset = null): array
    {
        $searchTerm = '%' . $searchTerm . '%';
        
        $query = $this->queryBuilder()
            ->select([
                'bp.post_id',
                'bp.title',
                'bp.url_alias',
                'bp.excerpt',
                'bp.tags',
                'bp.featured_image',
                'bp.published_at',
                'u.username'
            ])
            ->from($this->table . ' bp')
            ->leftJoin('users u', 'bp.user_id = u.user_id')
            ->where('bp.status = ?', [self::STATUS_PUBLISHED])
            ->where('(bp.title LIKE ? OR bp.body LIKE ? OR bp.tags LIKE ?)', [$searchTerm, $searchTerm, $searchTerm])
            ->orderBy('bp.published_at DESC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        return $query->get();
    }
}