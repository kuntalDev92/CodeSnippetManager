<?php
/**
 * ============================================================================
 * Tag Model Class
 * ============================================================================
 * 
 * Manages tags for snippet organization. Supports CRUD operations
 * and tag-based filtering.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Tag
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
     * Get all tags with user-specific usage counts
     * 
     * Always returns ALL tags, but counts are filtered to the
     * specified user's snippets. Tags with 0 count still appear
     * so users know what tags are available.
     * 
     * @param int|null $userId Filter counts to this user's snippets only
     * @return array Tags with snippet counts
     */
    public function getAll(?int $userId = null): array
    {
        if ($userId !== null) {
            // Count only snippets owned by this user, but show ALL tags
            $stmt = $this->db->prepare(
                "SELECT t.*, COUNT(st.snippet_id) as usage_count
                 FROM tags t
                 LEFT JOIN snippet_tags st ON t.id = st.tag_id
                    AND st.snippet_id IN (SELECT id FROM snippets WHERE user_id = ?)
                 GROUP BY t.id
                 ORDER BY usage_count DESC, t.name ASC"
            );
            $stmt->execute([$userId]);
        } else {
            // Global count (all users)
            $stmt = $this->db->prepare(
                "SELECT t.*, COUNT(st.snippet_id) as usage_count
                 FROM tags t
                 LEFT JOIN snippet_tags st ON t.id = st.tag_id
                 GROUP BY t.id
                 ORDER BY usage_count DESC, t.name ASC"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    /**
     * Find a tag by ID
     * 
     * @param int $id Tag ID
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a tag by name (case-insensitive)
     * 
     * @param string $name Tag name
     * @return array|null Tag data or null
     */
    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tags WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([trim($name)]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create a new tag
     * 
     * @param string $name  Tag name
     * @param string $color Tag color hex
     * @return int|false New tag ID or false
     */
    public function create(string $name, string $color = '#8b5cf6'): int|false
    {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $stmt = $this->db->prepare("INSERT INTO tags (name, slug, color) VALUES (?, ?, ?)");
            $stmt->execute([trim($name), $slug, $color]);

            return (int) $this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log('[Tag] Create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a tag
     * 
     * @param int    $id    Tag ID
     * @param string $name  New name
     * @param string $color New color
     * @return bool
     */
    public function update(int $id, string $name, string $color): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE tags SET name = ?, color = ? WHERE id = ?");
            return $stmt->execute([trim($name), $color, $id]);
        } catch (PDOException $e) {
            error_log('[Tag] Update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a tag
     * 
     * @param int $id Tag ID
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log('[Tag] Delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find or create a tag by name
     * 
     * @param string $name Tag name
     * @return int Tag ID
     */
    public function findOrCreate(string $name): int
    {
        $stmt = $this->db->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([trim($name)]);
        $tag = $stmt->fetch();

        if ($tag) {
            return (int) $tag['id'];
        }

        return $this->create($name);
    }

    /**
     * Get popular tags (most used)
     * 
     * @param int $limit Number of tags
     * @return array Popular tags
     */
    public function getPopular(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, COUNT(st.snippet_id) as usage_count
             FROM tags t
             INNER JOIN snippet_tags st ON t.id = st.tag_id
             GROUP BY t.id
             ORDER BY usage_count DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
