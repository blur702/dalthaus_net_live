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
        
        $query = "SELECT c.*, u.username, u.display_name 
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
        
        $query = "SELECT c.*, u.username, u.display_name 
                  FROM {$instance->table} c 
                  LEFT JOIN users u ON c.user_id = u.user_id
                  WHERE c.url_alias = ? AND c.status = ?";

        return self::queryFirst($query, [$alias, self::STATUS_PUBLISHED]);
    }

    /**
     * Find content by URL alias (any status)
     * Used for checking uniqueness during creation
     * 
     * @param string $urlAlias The URL alias to search for
     * @return static|null
     */
    public static function findByUrlAlias(string $urlAlias): ?static
    {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table} WHERE url_alias = ?";
        
        return self::queryFirst($query, [$urlAlias]);
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
        
        $query = "SELECT c.*, u.username, u.display_name 
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

        // Date filters
        if (!empty($filters['updated_after'])) {
            $whereClauses[] = "c.updated_at >= ?";
            $params[] = $filters['updated_after'];
        }
        
        if (!empty($filters['updated_before'])) {
            $whereClauses[] = "c.updated_at <= ?";
            $params[] = $filters['updated_before'];
        }

        // Add WHERE clause
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortDir = $filters['sort_dir'] ?? 'ASC';
        $allowedSortBy = ['title', 'content_type', 'status', 'created_at', 'updated_at', 'published_at', 'sort_order'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'sort_order';
        }

        // When sorting by sort_order, add secondary sort by published_at to match public behavior
        if ($sortBy === 'sort_order') {
            $query .= " ORDER BY c.sort_order ASC, c.published_at DESC";
        } else {
            $query .= " ORDER BY c.{$sortBy} {$sortDir}";
        }

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

        // Date filters
        if (!empty($filters['updated_after'])) {
            $whereClauses[] = "c.updated_at >= ?";
            $params[] = $filters['updated_after'];
        }
        
        if (!empty($filters['updated_before'])) {
            $whereClauses[] = "c.updated_at <= ?";
            $params[] = $filters['updated_before'];
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

        $query = "SELECT content_id, title, content_type, sort_order, status,
                         teaser, teaser_image, url_alias
                  FROM {$instance->table}";
        $params = [];

        if ($contentType !== null) {
            $query .= " WHERE content_type = ?";
            $params[] = $contentType;
        }

        $query .= " ORDER BY sort_order ASC, title ASC";

        error_log("[Content::getForReordering] Query: $query");
        error_log("[Content::getForReordering] Params: " . json_encode($params));

        // Return arrays directly instead of model objects for view compatibility
        $results = $instance->db->fetchAll($query, $params);

        error_log("[Content::getForReordering] Found " . count($results) . " items");

        return $results;
    }

    /**
     * Update sort order for multiple items
     *
     * @param array $orderData Array of ['id' => order] pairs
     * @param string|null $contentType Optional content type to reorder all items of that type
     * @return bool
     */
    public static function updateSortOrder(array $orderData, ?string $contentType = null): bool
    {
        $instance = new static();

        error_log("[Content::updateSortOrder] Starting update with " . count($orderData) . " items");
        error_log("[Content::updateSortOrder] Content type: " . ($contentType ?? 'ALL'));
        error_log("[Content::updateSortOrder] Order data: " . json_encode($orderData));

        try {
            $instance->db->beginTransaction();

            // Update the items in the order data
            foreach ($orderData as $id => $order) {
                error_log("[Content::updateSortOrder] Updating content_id=$id to sort_order=$order");

                $result = $instance->db->update(
                    $instance->table,
                    ['sort_order' => $order],
                    'content_id = ?',
                    [$id]
                );

                error_log("[Content::updateSortOrder] Update result for content_id=$id: " . ($result ? 'success' : 'failed'));
            }

            // If content type is specified, update remaining items of that type
            if ($contentType !== null) {
                $maxOrder = !empty($orderData) ? max(array_values($orderData)) : 0;
                $orderedIds = array_keys($orderData);

                // Get all items of this type that weren't in the order data
                $placeholders = implode(',', array_fill(0, count($orderedIds), '?'));
                $query = "SELECT content_id FROM {$instance->table}
                         WHERE content_type = ?
                         AND content_id NOT IN ($placeholders)
                         ORDER BY sort_order ASC, published_at DESC";

                $params = array_merge([$contentType], $orderedIds);
                $remainingItems = $instance->db->fetchAll($query, $params);

                error_log("[Content::updateSortOrder] Found " . count($remainingItems) . " remaining items to update");

                // Assign sequential sort orders to remaining items
                $nextOrder = $maxOrder + 1;
                foreach ($remainingItems as $item) {
                    error_log("[Content::updateSortOrder] Updating remaining content_id={$item['content_id']} to sort_order=$nextOrder");

                    $instance->db->update(
                        $instance->table,
                        ['sort_order' => $nextOrder],
                        'content_id = ?',
                        [$item['content_id']]
                    );

                    $nextOrder++;
                }
            }

            $instance->db->commit();
            error_log("[Content::updateSortOrder] Transaction committed successfully");
            return true;
        } catch (\Exception $e) {
            $instance->db->rollback();
            error_log("[Content::updateSortOrder] ERROR: " . $e->getMessage());
            error_log("[Content::updateSortOrder] Stack trace: " . $e->getTraceAsString());
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
        
        // Split by TinyMCE pagebreak delimiters (supports both formats)
        // TinyMCE 6 uses <!-- pagebreak --> comments, older versions use <hr class="mce-pagebreak" />
        $pages = preg_split('/(?:<!--\s*pagebreak\s*-->|<hr\s+class=["\']mce-pagebreak["\'][^>]*>)/i', $body);
        
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
        
        // Replace all page break tags with the visual indicator (supports both formats)
        // First replace TinyMCE 6 comment format
        $content = preg_replace(
            '/<!--\s*pagebreak\s*-->/i',
            $pageBreakHtml,
            $body
        );
        
        // Then replace older hr tag format
        $content = preg_replace(
            '/<hr\s+class=["\']mce-pagebreak["\'][^>]*>/i',
            $pageBreakHtml,
            $content
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
            'SELECT user_id, username, email, display_name FROM users WHERE user_id = ?',
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
        if (!$image) {
            return '';
        }
        // Handle both old format (content/teasers/...) and new format (/uploads/content/teasers/...)
        return (strpos($image, '/') === 0) ? $image : '/uploads/' . $image;
    }

    /**
     * Get featured image URL
     *
     * @return string
     */
    public function getFeaturedImageUrl(): string
    {
        $image = $this->getAttribute('featured_image');
        if (!$image) {
            return '';
        }
        // Handle both old format (content/featured/...) and new format (/uploads/content/featured/...)
        return (strpos($image, '/') === 0) ? $image : '/uploads/' . $image;
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
            'sort_by' => $filters['sort_by'] ?? 'sort_order',
            'sort_dir' => $filters['sort_dir'] ?? 'ASC'
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

    /**
     * Get teaser text or content excerpt for listing pages
     * 
     * @param int $maxLength Maximum length of excerpt
     * @return string
     */
    public function getTeaserOrExcerpt(int $maxLength = 200): string
    {
        $teaser = trim($this->getAttribute('teaser') ?? '');
        
        // If we have a teaser, use it
        if (!empty($teaser)) {
            return $teaser;
        }
        
        // Otherwise, create an excerpt from the body content
        $body = strip_tags($this->getAttribute('body') ?? '');
        $body = preg_replace('/\s+/', ' ', $body); // Normalize whitespace
        $body = trim($body);
        
        if (empty($body)) {
            return '';
        }
        
        if (strlen($body) <= $maxLength) {
            return $body;
        }
        
        // Truncate at word boundary
        $excerpt = substr($body, 0, $maxLength);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
}
