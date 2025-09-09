<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * Content Model
 * 
 * Handles articles and photobooks content with relationships
 * and specialized methods for content management.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class Content extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'content';

    /**
     * Primary key
     */
    protected string $primaryKey = 'content_id';

    /**
     * Content types
     */
    public const TYPE_ARTICLE = 'article';
    public const TYPE_PHOTOBOOK = 'photobook';

    /**
     * Content statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /**
     * Get published content by type
     * 
     * @param string $contentType Content type (article or photobook)
     * @param int|null $limit Number of items to retrieve
     * @param int|null $offset Offset for pagination
     * @return array
     */
    public static function getPublishedContentByType(string $contentType, ?int $limit = null, ?int $offset = null): array
    {
        $instance = new static();
        
        $query = "SELECT c.*, u.username 
                  FROM {$instance->table} c 
                  LEFT JOIN users u ON c.user_id = u.user_id
                  WHERE c.content_type = ? AND c.status = ?
                  ORDER BY c.sort_order ASC, c.published_at DESC";

        $params = [$contentType, self::STATUS_PUBLISHED];

        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
            
            if ($offset !== null) {
                $query .= " OFFSET {$offset}";
            }
        }

        return self::query($query, $params);
    }

    /**
     * Get published articles
     *
     * @param int|null $limit Number of articles to retrieve
     * @param int|null $offset Offset for pagination
     * @return array
     */
    public static function getPublishedArticles(?int $limit = null, ?int $offset = null): array
    {
        return self::getPublishedContentByType(self::TYPE_ARTICLE, $limit, $offset);
    }

    /**
     * Get published photobooks
     * 
     * @param int|null $limit Number of photobooks to retrieve
     * @param int|null $offset Offset for pagination
     * @return array
     */
    public static function getPublishedPhotobooks(?int $limit = null, ?int $offset = null): array
    {
        return self::getPublishedContentByType(self::TYPE_PHOTOBOOK, $limit, $offset);
    }

    /**
     * Find content by URL alias
     * 
     * @param string $alias URL alias
     * @return static|null
     */
    public static function findByAlias(string $alias): ?static
    {
        $instance = new static();
        
        $query = "SELECT c.*, u.username 
                  FROM {$instance->table} c 
                  LEFT JOIN users u ON c.user_id = u.user_id
                  WHERE c.url_alias = ? AND c.status = ?";

        return self::queryFirst($query, [$alias, self::STATUS_PUBLISHED]);
    }

    /**
     * Get content for admin with search and filters
     * 
     * @param array $filters Filter parameters
     * @param int|null $limit Limit
     * @param int|null $offset Offset
     * @return array
     */
    public static function getForAdmin(array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        $instance = new static();
        
        $query = "SELECT c.*, u.username 
                  FROM {$instance->table} c 
                  LEFT JOIN users u ON c.user_id = u.user_id";
        
        $params = [];
        $whereClauses = [];

        // Content type filter
        if (!empty($filters['type'])) {
            $whereClauses[] = "c.content_type = ?";
            $params[] = $filters['type'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $whereClauses[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $whereClauses[] = "(c.title LIKE ? OR c.teaser LIKE ? OR c.body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add WHERE clause
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortDir = $filters['sort_dir'] ?? 'DESC';
        $allowedSortBy = ['title', 'content_type', 'status', 'created_at', 'updated_at', 'published_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'updated_at';
        }
        $query .= " ORDER BY c.{$sortBy} {$sortDir}";

        // Pagination
        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
            
            if ($offset !== null) {
                $query .= " OFFSET {$offset}";
            }
        }

        return self::query($query, $params);
    }

    /**
     * Count content for admin with filters
     * 
     * @param array $filters Filter parameters
     * @return int
     */
    public static function countForAdmin(array $filters = []): int
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table} c";
        $params = [];
        $whereClauses = [];

        // Content type filter
        if (!empty($filters['type'])) {
            $whereClauses[] = "c.content_type = ?";
            $params[] = $filters['type'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $whereClauses[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $whereClauses[] = "(c.title LIKE ? OR c.teaser LIKE ? OR c.body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add WHERE clause
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        return (int) $instance->db->fetchColumn($query, $params);
    }

    /**
     * Get content for reordering
     * 
     * @param string|null $contentType Content type filter
     * @return array
     */
    public static function getForReordering(?string $contentType = null): array
    {
        $instance = new static();
        
        $query = "SELECT content_id, title, content_type, sort_order 
                  FROM {$instance->table}";
        $params = [];

        if ($contentType !== null) {
            $query .= " WHERE content_type = ?";
            $params[] = $contentType;
        }

        $query .= " ORDER BY sort_order ASC, title ASC";

        return self::query($query, $params);
    }

    /**
     * Update sort order for multiple items
     * 
     * @param array $orderData Array of ['id' => order] pairs
     * @return bool
     */
    public static function updateSortOrder(array $orderData): bool
    {
        $instance = new static();
        
        try {
            $instance->db->beginTransaction();
            
            foreach ($orderData as $id => $order) {
                $instance->db->update(
                    $instance->table,
                    ['sort_order' => $order],
                    'content_id = ?',
                    [$id]
                );
            }
            
            $instance->db->commit();
            return true;
        } catch (\Exception $e) {
            $instance->db->rollback();
            return false;
        }
    }

    /**
     * Get next sort order value
     * 
     * @return int
     */
    public static function getNextSortOrder(): int
    {
        $instance = new static();
        
        $maxOrder = $instance->db->fetchColumn(
            "SELECT MAX(sort_order) FROM {$instance->table}"
        );

        return ((int) $maxOrder) + 1;
    }

    /**
     * Split content body by pagebreak delimiters
     * 
     * @return array Array of content pages
     */
    public function getContentPages(): array
    {
        $body = $this->getAttribute('body') ?? '';
        
        // If no body content, return empty array
        if (empty(trim($body))) {
            return [];
        }
        
        // Split by TinyMCE pagebreak delimiter
        $pages = preg_split('/<hr\s+class=["\']mce-pagebreak["\'][^>]*>/i', $body);
        
        // Trim pages and filter out completely empty ones
        $pages = array_map('trim', $pages);
        $pages = array_filter($pages, function($page) {
            return !empty($page);
        });
        
        // Re-index array to ensure sequential keys
        return array_values($pages);
    }
    
    /**
     * Get content with visual page break indicators
     * Replaces TinyMCE page breaks with styled HTML
     * 
     * @return string Content with visual page breaks
     */
    public function getContentWithPageBreaks(): string
    {
        $body = $this->getAttribute('body') ?? '';
        
        if (empty(trim($body))) {
            return '';
        }
        
        // Replace TinyMCE page breaks with a visual indicator
        $pageBreakHtml = '
        <div class="page-break-indicator" style="margin: 3rem 0; position: relative; height: 40px;">
            <div style="position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: linear-gradient(to right, transparent, #ddd 20%, #ddd 80%, transparent); transform: translateY(-50%);"></div>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 0.5rem 1.5rem; border: 2px solid #ddd; border-radius: 20px;">
                <span style="color: #888; font-size: 0.85em; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">Page Break</span>
            </div>
        </div>';
        
        // Replace all page break tags with the visual indicator
        $content = preg_replace(
            '/<hr\s+class=["\']mce-pagebreak["\'][^>]*>/i',
            $pageBreakHtml,
            $body
        );
        
        return $content;
    }

    /**
     * Get author information
     * 
     * @return array|null
     */
    public function getAuthor(): ?array
    {
        $userId = $this->getAttribute('user_id');
        
        if (!$userId) {
            return null;
        }

        return $this->db->fetchRow(
            'SELECT user_id, username, email FROM users WHERE user_id = ?',
            [$userId]
        ) ?: null;
    }

    /**
     * Check if content is published
     * 
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->getAttribute('status') === self::STATUS_PUBLISHED;
    }

    /**
     * Check if content is draft
     * 
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->getAttribute('status') === self::STATUS_DRAFT;
    }

    /**
     * Check if content is article
     * 
     * @return bool
     */
    public function isArticle(): bool
    {
        return $this->getAttribute('content_type') === self::TYPE_ARTICLE;
    }

    /**
     * Check if content is photobook
     * 
     * @return bool
     */
    public function isPhotobook(): bool
    {
        return $this->getAttribute('content_type') === self::TYPE_PHOTOBOOK;
    }

    /**
     * Get formatted created date
     * 
     * @param string $format Date format
     * @return string
     */
    public function getFormattedCreatedDate(string $format = 'F j, Y'): string
    {
        $createdAt = $this->getAttribute('created_at');
        return $createdAt ? date($format, strtotime($createdAt)) : '';
    }

    /**
     * Get formatted published date
     * 
     * @param string $format Date format
     * @return string
     */
    public function getFormattedPublishedDate(string $format = 'F j, Y'): string
    {
        $publishedAt = $this->getAttribute('published_at');
        return $publishedAt ? date($format, strtotime($publishedAt)) : '';
    }

    /**
     * Get teaser image URL
     * 
     * @return string
     */
    public function getTeaserImageUrl(): string
    {
        $image = $this->getAttribute('teaser_image');
        return $image ? '/uploads/' . $image : '';
    }

    /**
     * Get featured image URL
     * 
     * @return string
     */
    public function getFeaturedImageUrl(): string
    {
        $image = $this->getAttribute('featured_image');
        return $image ? '/uploads/' . $image : '';
    }

    /**
     * Get content URL
     * 
     * @return string
     */
    public function getUrl(): string
    {
        $alias = $this->getAttribute('url_alias');
        $type = $this->getAttribute('content_type');
        
        if (!$alias) {
            return '#';
        }

        return $type === self::TYPE_ARTICLE ? "/article/{$alias}" : "/photobook/{$alias}";
    }

    /**
     * Find content with filters (alias for getForAdmin)
     * 
     * @param array $filters Filter parameters
     * @param int|null $limit Limit
     * @param int|null $offset Offset
     * @return array
     */
    public static function findWithFilters(array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        // Map the filter keys to what getForAdmin expects
        $mappedFilters = [
            'type' => $filters['content_type'] ?? '',
            'status' => $filters['status'] ?? '',
            'search' => $filters['search'] ?? '',
            'sort_by' => $filters['sort_by'] ?? 'updated_at',
            'sort_dir' => $filters['sort_dir'] ?? 'DESC'
        ];
        
        $results = self::getForAdmin($mappedFilters, $limit, $offset);
        
        // Convert objects to arrays for the view
        return array_map(function($item) {
            if (is_object($item)) {
                return $item->toArray();
            }
            return $item;
        }, $results);
    }

    /**
     * Count content with filters (alias for countForAdmin)
     * 
     * @param array $filters Filter parameters
     * @return int
     */
    public static function countWithFilters(array $filters = []): int
    {
        // Map the filter keys to what countForAdmin expects
        $mappedFilters = [
            'type' => $filters['content_type'] ?? '',
            'status' => $filters['status'] ?? '',
            'search' => $filters['search'] ?? ''
        ];
        
        return self::countForAdmin($mappedFilters);
    }
}
