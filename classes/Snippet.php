<?php
/**
 * ============================================================================
 * Snippet Model Class
 * ============================================================================
 * 
 * Core model for managing code snippets. Handles CRUD operations,
 * search, filtering, pagination, version history, and copy tracking.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Snippet
{
    /** @var PDO Database connection */
    private PDO $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new snippet
     * 
     * @param array $data Snippet data
     * @return int|false New snippet ID or false on failure
     */
    public function create(array $data): int|false
    {
        try {
            $this->db->beginTransaction();

            // Generate URL-friendly slug
            $slug = $this->generateSlug($data['title']);

            $stmt = $this->db->prepare(
                "INSERT INTO snippets (title, slug, description, code, language, category_id, user_id, is_public, is_pinned)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                trim($data['title']),
                $slug,
                trim($data['description'] ?? ''),
                $data['code'],
                $data['language'] ?? 'php',
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                (int)$data['user_id'],
                isset($data['is_public']) ? (int)$data['is_public'] : 0,
                isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0,
            ]);

            $snippetId = (int) $this->db->lastInsertId();

            // Save initial version
            $this->saveVersion($snippetId, $data['code'], 1, 'Initial version', (int)$data['user_id']);

            // Handle tags
            if (!empty($data['tags'])) {
                $this->syncTags($snippetId, $data['tags']);
            }

            $this->db->commit();

            // Log activity
            ActivityLog::log((int)$data['user_id'], 'create', 'snippet', $snippetId);

            return $snippetId;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('[Snippet] Create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing snippet
     * 
     * @param int   $id     Snippet ID
     * @param array $data   Updated data
     * @param int   $userId User performing the update
     * @return bool Success status
     */
    public function update(int $id, array $data, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            // Get current snippet for version comparison
            $current = $this->findById($id);
            if (!$current) {
                return false;
            }

            $stmt = $this->db->prepare(
                "UPDATE snippets SET 
                    title = ?, description = ?, code = ?, language = ?, 
                    category_id = ?, is_public = ?, is_pinned = ?,
                    version = version + 1
                 WHERE id = ?"
            );

            $stmt->execute([
                trim($data['title']),
                trim($data['description'] ?? ''),
                $data['code'],
                $data['language'] ?? 'php',
                !empty($data['category_id']) ? (int)$data['category_id'] : null,
                isset($data['is_public']) ? (int)$data['is_public'] : 0,
                isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0,
                $id,
            ]);

            // Save new version if code changed
            if ($current['code'] !== $data['code']) {
                $newVersion = $current['version'] + 1;
                $this->saveVersion($id, $data['code'], $newVersion, $data['change_note'] ?? 'Updated', $userId);
            }

            // Sync tags
            if (isset($data['tags'])) {
                $this->syncTags($id, $data['tags']);
            }

            $this->db->commit();

            // Log activity
            ActivityLog::log($userId, 'update', 'snippet', $id);

            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('[Snippet] Update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a snippet
     * 
     * @param int $id     Snippet ID
     * @param int $userId User performing deletion
     * @return bool Success status
     */
    public function delete(int $id, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM snippets WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$id, $userId]);
            
            if ($stmt->rowCount() > 0) {
                ActivityLog::log($userId, 'delete', 'snippet', $id);
                return true;
            }
            return false;

        } catch (PDOException $e) {
            error_log('[Snippet] Delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clone/Fork a snippet to another user's collection
     * Creates a complete independent copy owned by the target user
     * 
     * @param int $snippetId    Source snippet ID to clone
     * @param int $targetUserId User who will own the clone
     * @return int|false New snippet ID or false on failure
     */
    public function cloneSnippet(int $snippetId, int $targetUserId): int|false
    {
        try {
            // Get the original snippet
            $original = $this->findById($snippetId);
            if (!$original) {
                return false;
            }

            // Create a new snippet as a copy
            $data = [
                'title'       => $original['title'] . ' (copy)',
                'description' => $original['description'] ?? '',
                'code'        => $original['code'],
                'language'    => $original['language'],
                'category_id' => $original['category_id'],
                'user_id'     => $targetUserId,
                'is_public'   => 0,  // Private by default
                'is_pinned'   => 0,
                'tags'        => array_column($original['tags'] ?? [], 'id'),
            ];

            $newId = $this->create($data);

            if ($newId) {
                ActivityLog::log($targetUserId, 'clone', 'snippet', $newId, [
                    'original_id' => $snippetId,
                    'original_title' => $original['title'],
                ]);
            }

            return $newId;

        } catch (PDOException $e) {
            error_log('[Snippet] Clone error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find a snippet by ID with all related data
     * 
     * @param int $id Snippet ID
     * @return array|null Snippet data or null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name as category_name, c.color as category_color,
                    u.username, u.full_name as author_name, u.avatar as author_avatar
             FROM snippets s
             LEFT JOIN categories c ON s.category_id = c.id
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $snippet = $stmt->fetch();

        if ($snippet) {
            $snippet['tags'] = $this->getTags($id);
        }

        return $snippet ?: null;
    }

    /**
     * Get paginated snippets with optional filters
     * 
     * @param array $filters Filter criteria
     * @param int   $page    Current page number
     * @param int   $limit   Items per page
     * @return array Paginated results with metadata
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = ITEMS_PER_PAGE): array
    {
        $where = [];
        $params = [];

        // Filter by user (own snippets)
        if (!empty($filters['user_id'])) {
            $where[] = "s.user_id = ?";
            $params[] = (int) $filters['user_id'];
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $where[] = "s.category_id = ?";
            $params[] = (int) $filters['category_id'];
        }

        // Filter by language
        if (!empty($filters['language'])) {
            $where[] = "s.language = ?";
            $params[] = $filters['language'];
        }

        // Filter by public/private
        if (isset($filters['is_public'])) {
            $where[] = "s.is_public = ?";
            $params[] = (int) $filters['is_public'];
        }

        // Search query (full-text search)
        if (!empty($filters['search'])) {
            $where[] = "MATCH(s.title, s.description, s.code) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $filters['search'];
        }

        // Filter by tag
        if (!empty($filters['tag_id'])) {
            $where[] = "s.id IN (SELECT snippet_id FROM snippet_tags WHERE tag_id = ?)";
            $params[] = (int) $filters['tag_id'];
        }

        // Filter favorites only
        if (!empty($filters['favorites_only']) && !empty($filters['current_user_id'])) {
            $where[] = "s.id IN (SELECT snippet_id FROM favorites WHERE user_id = ?)";
            $params[] = (int) $filters['current_user_id'];
        }

        // Filter shared with me
        if (!empty($filters['shared_with_me']) && !empty($filters['current_user_id'])) {
            $where[] = "s.id IN (SELECT snippet_id FROM shared_snippets WHERE shared_with = ?)";
            $params[] = (int) $filters['current_user_id'];
        }

        // Build WHERE clause
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total results
        $countSql = "SELECT COUNT(*) as total FROM snippets s {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        // Calculate pagination
        $totalPages = max(1, ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        // Determine sort order
        $orderBy = match ($filters['sort'] ?? 'newest') {
            'oldest'     => 's.created_at ASC',
            'title'      => 's.title ASC',
            'most_views' => 's.views_count DESC',
            'most_copies'=> 's.copies_count DESC',
            'updated'    => 's.updated_at DESC',
            default      => 's.is_pinned DESC, s.created_at DESC',
        };

        // Fetch snippets
        $sql = "SELECT s.*, c.name as category_name, c.color as category_color,
                       u.username, u.full_name as author_name
                FROM snippets s
                LEFT JOIN categories c ON s.category_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $snippets = $stmt->fetchAll();

        // Attach tags to each snippet
        foreach ($snippets as &$snippet) {
            $snippet['tags'] = $this->getTags($snippet['id']);
        }

        return [
            'data'        => $snippets,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $limit,
            'total_pages' => $totalPages,
            'has_prev'    => $page > 1,
            'has_next'    => $page < $totalPages,
        ];
    }

    /**
     * Quick search snippets by title/description (for AJAX autocomplete)
     * 
     * @param string $query  Search query
     * @param int    $userId Current user ID
     * @param int    $limit  Maximum results
     * @return array Matching snippets
     */
    public function quickSearch(string $query, int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.title, s.language, s.description, c.name as category_name
             FROM snippets s
             LEFT JOIN categories c ON s.category_id = c.id
             WHERE (s.user_id = ? OR s.is_public = 1 OR s.id IN (SELECT snippet_id FROM shared_snippets WHERE shared_with = ?))
               AND (s.title LIKE ? OR s.description LIKE ? OR s.code LIKE ?)
             ORDER BY s.title ASC
             LIMIT ?"
        );
        $searchTerm = "%{$query}%";
        $stmt->execute([$userId, $userId, $searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Increment the view count for a snippet
     * 
     * @param int $id Snippet ID
     * @return void
     */
    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE snippets SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Increment the copy count for a snippet
     * 
     * @param int $id Snippet ID
     * @return void
     */
    public function incrementCopies(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE snippets SET copies_count = copies_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Get tags associated with a snippet
     * 
     * @param int $snippetId Snippet ID
     * @return array Tags
     */
    public function getTags(int $snippetId): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.* FROM tags t
             INNER JOIN snippet_tags st ON t.id = st.tag_id
             WHERE st.snippet_id = ?
             ORDER BY t.name ASC"
        );
        $stmt->execute([$snippetId]);
        return $stmt->fetchAll();
    }

    /**
     * Sync tags for a snippet (remove old, add new)
     * 
     * @param int   $snippetId Snippet ID
     * @param array $tagIds    Array of tag IDs
     * @return void
     */
    private function syncTags(int $snippetId, array $tagIds): void
    {
        // Remove existing tags
        $stmt = $this->db->prepare("DELETE FROM snippet_tags WHERE snippet_id = ?");
        $stmt->execute([$snippetId]);

        // Insert new tags
        if (!empty($tagIds)) {
            $insertStmt = $this->db->prepare("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tagId) {
                $insertStmt->execute([$snippetId, (int)$tagId]);
            }
        }
    }

    /**
     * Save a version snapshot of the snippet code
     * 
     * @param int    $snippetId  Snippet ID
     * @param string $code       Code content
     * @param int    $version    Version number
     * @param string $changeNote Description of changes
     * @param int    $userId     User who made the change
     * @return void
     */
    private function saveVersion(int $snippetId, string $code, int $version, string $changeNote, int $userId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO snippet_versions (snippet_id, code, version, change_note, created_by) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$snippetId, $code, $version, $changeNote, $userId]);
    }

    /**
     * Get version history for a snippet
     * 
     * @param int $snippetId Snippet ID
     * @return array Version history
     */
    public function getVersionHistory(int $snippetId): array
    {
        $stmt = $this->db->prepare(
            "SELECT sv.*, u.username, u.full_name 
             FROM snippet_versions sv
             LEFT JOIN users u ON sv.created_by = u.id
             WHERE sv.snippet_id = ?
             ORDER BY sv.version DESC"
        );
        $stmt->execute([$snippetId]);
        return $stmt->fetchAll();
    }

    /**
     * Get a specific version's code
     * 
     * @param int $snippetId Snippet ID
     * @param int $version   Version number
     * @return array|null Version data
     */
    public function getVersion(int $snippetId, int $version): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM snippet_versions WHERE snippet_id = ? AND version = ?"
        );
        $stmt->execute([$snippetId, $version]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Toggle pin status of a snippet
     * 
     * @param int $id     Snippet ID
     * @param int $userId User ID
     * @return bool New pin status
     */
    public function togglePin(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT is_pinned FROM snippets WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $snippet = $stmt->fetch();

        if (!$snippet) return false;

        $newStatus = $snippet['is_pinned'] ? 0 : 1;
        $updateStmt = $this->db->prepare("UPDATE snippets SET is_pinned = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $id]);

        return (bool) $newStatus;
    }

    /**
     * Generate a unique URL-friendly slug from title
     * 
     * @param string $title Snippet title
     * @return string Unique slug
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM snippets WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) break;
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Get snippet count by language for dashboard stats
     * 
     * @param int $userId User ID
     * @return array Language distribution
     */
    public function getLanguageDistribution(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT language, COUNT(*) as count FROM snippets WHERE user_id = ? GROUP BY language ORDER BY count DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent snippets for dashboard
     * 
     * @param int $userId User ID
     * @param int $limit  Number of recent snippets
     * @return array Recent snippets
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name as category_name, c.color as category_color
             FROM snippets s
             LEFT JOIN categories c ON s.category_id = c.id
             WHERE s.user_id = ?
             ORDER BY s.updated_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
