<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * Page Model
 * 
 * Handles static pages with content management capabilities.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class Page extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'pages';

    /**
     * Primary key
     */
    protected string $primaryKey = 'page_id';

    /**
     * Find page by URL alias
     * 
     * @param string $alias URL alias
     * @return static|null
     */
    public static function findByAlias(string $alias): ?static
    {
        $instance = new static();
        
        $data = $instance->db->fetchRow(
            "SELECT * FROM {$instance->table} WHERE url_alias = ?",
            [$alias]
        );

        if ($data === false) {
            return null;
        }

        $instance->data = $data;
        $instance->original = $data;
        
        return $instance;
    }

    /**
     * Get all pages for admin
     * 
     * @param array $filters Filter parameters
     * @param int|null $limit Limit
     * @param int|null $offset Offset
     * @return array
     */
    public static function getForAdmin(array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table}";
        $params = [];
        $whereClauses = [];

        // Search filter
        if (!empty($filters['search'])) {
            $whereClauses[] = "(title LIKE ? OR body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
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
        $query .= " ORDER BY {$sortBy} {$sortDir}";

        // Pagination
        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
            
            if ($offset !== null) {
                $query .= " OFFSET {$offset}";
            }
        }

        $results = self::query($query, $params);
        
        // Convert model objects to arrays for views
        return array_map(function($page) {
            if (is_object($page)) {
                return $page->toArray();
            }
            return $page;
        }, $results);
    }

    /**
     * Count pages for admin with filters
     * 
     * @param array $filters Filter parameters
     * @return int
     */
    public static function countForAdmin(array $filters = []): int
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table}";
        $params = [];
        $whereClauses = [];

        // Search filter
        if (!empty($filters['search'])) {
            $whereClauses[] = "(title LIKE ? OR body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
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
     * Get formatted updated date
     * 
     * @param string $format Date format
     * @return string
     */
    public function getFormattedUpdatedDate(string $format = 'F j, Y'): string
    {
        $updatedAt = $this->getAttribute('updated_at');
        return $updatedAt ? date($format, strtotime($updatedAt)) : '';
    }

    /**
     * Get page URL
     * 
     * @return string
     */
    public function getUrl(): string
    {
        $alias = $this->getAttribute('url_alias');
        return $alias ? "/page/{$alias}" : '#';
    }

    /**
     * Check if URL alias is available
     * 
     * @param string $alias URL alias to check
     * @param int|null $excludePageId Page ID to exclude (for updates)
     * @return bool
     */
    public static function isAliasAvailable(string $alias, ?int $excludePageId = null): bool
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table} WHERE url_alias = ?";
        $params = [$alias];

        if ($excludePageId !== null) {
            $query .= " AND page_id != ?";
            $params[] = $excludePageId;
        }

        $count = (int) $instance->db->fetchColumn($query, $params);
        return $count === 0;
    }

    /**
     * Generate URL alias from title
     * 
     * @param string $title Page title
     * @param int|null $excludePageId Page ID to exclude (for updates)
     * @return string
     */
    public static function generateAlias(string $title, ?int $excludePageId = null): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $alias = strtolower(trim($title));
        $alias = preg_replace('/[^a-z0-9\s-]/', '', $alias);
        $alias = preg_replace('/[\s-]+/', '-', $alias);
        $alias = trim($alias, '-');

        if (empty($alias)) {
            $alias = 'page';
        }

        // Ensure uniqueness
        $originalAlias = $alias;
        $counter = 1;

        while (!self::isAliasAvailable($alias, $excludePageId)) {
            $alias = $originalAlias . '-' . $counter;
            $counter++;
        }

        return $alias;
    }

    /**
     * Split page body by pagebreak delimiters
     * 
     * @return array Array of content pages
     */
    public function getContentPages(): array
    {
        $body = $this->getAttribute('body') ?? '';
        
        // Split by TinyMCE pagebreak delimiter
        $pages = preg_split('/<hr\s+class=["\']mce-pagebreak["\'][^>]*>/i', $body);
        
        // Remove empty pages and trim
        return array_filter(array_map('trim', $pages));
    }

    /**
     * Get all pages for public listing
     * 
     * @param int|null $limit Limit
     * @param int|null $offset Offset
     * @return array
     */
    public static function getAllPublic(?int $limit = null, ?int $offset = null): array
    {
        $instance = new static();
        
        $query = "SELECT page_id, title, url_alias, meta_description, updated_at 
                  FROM {$instance->table} 
                  ORDER BY title ASC";

        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
            
            if ($offset !== null) {
                $query .= " OFFSET {$offset}";
            }
        }

        return self::query($query);
    }

    /**
     * Get pages for menu linking
     * 
     * @return array
     */
    public static function getForMenuLinking(): array
    {
        $instance = new static();
        
        return $instance->db->fetchAll(
            "SELECT page_id, title, url_alias FROM {$instance->table} ORDER BY title ASC"
        );
    }

    /**
     * Validate page data
     * 
     * @param array $data Page data
     * @param int|null $excludePageId Page ID to exclude (for updates)
     * @return array Array of validation errors
     */
    public static function validatePageData(array $data, ?int $excludePageId = null): array
    {
        $errors = [];

        // Title validation
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'Title must be less than 255 characters';
        }

        // URL alias validation
        if (empty($data['url_alias'])) {
            $errors['url_alias'] = 'URL alias is required';
        } elseif (strlen($data['url_alias']) > 255) {
            $errors['url_alias'] = 'URL alias must be less than 255 characters';
        } elseif (!preg_match('/^[a-z0-9-]+$/', $data['url_alias'])) {
            $errors['url_alias'] = 'URL alias can only contain lowercase letters, numbers, and hyphens';
        } elseif (!self::isAliasAvailable($data['url_alias'], $excludePageId)) {
            $errors['url_alias'] = 'URL alias is already taken';
        }

        // Meta keywords validation
        if (!empty($data['meta_keywords']) && strlen($data['meta_keywords']) > 500) {
            $errors['meta_keywords'] = 'Meta keywords must be less than 500 characters';
        }

        // Meta description validation
        if (!empty($data['meta_description']) && strlen($data['meta_description']) > 500) {
            $errors['meta_description'] = 'Meta description must be less than 500 characters';
        }

        return $errors;
    }

    /**
     * Get page meta tags
     * 
     * @return array
     */
    public function getMetaTags(): array
    {
        return [
            'title' => $this->getAttribute('title'),
            'description' => $this->getAttribute('meta_description') ?: 
                            substr(strip_tags($this->getAttribute('body') ?? ''), 0, 160),
            'keywords' => $this->getAttribute('meta_keywords')
        ];
    }

    /**
     * Get recent pages
     * 
     * @param int $limit Number of pages to retrieve
     * @return array
     */
    public static function getRecent(int $limit = 5): array
    {
        $instance = new static();
        
        return $instance->db->fetchAll(
            "SELECT page_id, title, url_alias, updated_at 
             FROM {$instance->table} 
             ORDER BY updated_at DESC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Update the updated_at timestamp
     * 
     * @return bool
     */
    public function touch(): bool
    {
        $this->setAttribute('updated_at', date('Y-m-d H:i:s'));
        return $this->save();
    }
}
