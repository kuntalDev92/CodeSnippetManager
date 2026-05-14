<?php
/**
 * ============================================================================
 * Category Model Class
 * ============================================================================
 * 
 * Manages snippet categories with CRUD operations, hierarchical
 * structure support, and snippet counting.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Category
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
     * Get all categories with snippet counts
     * 
     * When userId is provided, counts only that user's snippets.
     * When null, counts all snippets (admin/management view).
     * 
     * @param int|null $userId Filter counts to this user's snippets only
     * @return array Categories with counts
     */
    public function getAll(?int $userId = null): array
    {
        if ($userId !== null) {
            // Count only snippets owned by this user
            $stmt = $this->db->prepare(
                "SELECT c.*, COUNT(s.id) as snippet_count
                 FROM categories c
                 LEFT JOIN snippets s ON c.id = s.category_id AND s.user_id = ?
                 GROUP BY c.id
                 ORDER BY c.sort_order ASC, c.name ASC"
            );
            $stmt->execute([$userId]);
        } else {
            // Global count (all users)
            $stmt = $this->db->prepare(
                "SELECT c.*, COUNT(s.id) as snippet_count
                 FROM categories c
                 LEFT JOIN snippets s ON c.id = s.category_id
                 GROUP BY c.id
                 ORDER BY c.sort_order ASC, c.name ASC"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    /**
     * Find a category by ID
     * 
     * @param int $id Category ID
     * @return array|null Category data
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category data
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create a new category
     * Checks for duplicate name before inserting.
     * 
     * @param array $data Category data
     * @return int|false New category ID or false
     */
    public function create(array $data): int|false
    {
        try {
            $name = trim($data['name'] ?? '');
            
            // Check for duplicate category name
            $checkStmt = $this->db->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
            $checkStmt->execute([$name]);
            if ($checkStmt->fetch()) {
                return false; // Category with this name already exists
            }
            
            $slug = $this->generateSlug($name);

            $stmt = $this->db->prepare(
                "INSERT INTO categories (name, slug, description, color, icon, parent_id, sort_order, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $slug,
                trim($data['description'] ?? ''),
                $data['color'] ?? '#6366f1',
                $data['icon'] ?? 'folder',
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                (int)($data['sort_order'] ?? 0),
                (int)($data['created_by'] ?? Auth::userId()),
            ]);

            return (int) $this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log('[Category] Create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing category
     * 
     * @param int   $id   Category ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE categories SET name = ?, description = ?, color = ?, icon = ?, sort_order = ?
                 WHERE id = ?"
            );

            return $stmt->execute([
                trim($data['name']),
                trim($data['description'] ?? ''),
                $data['color'] ?? '#6366f1',
                $data['icon'] ?? 'folder',
                (int)($data['sort_order'] ?? 0),
                $id,
            ]);

        } catch (PDOException $e) {
            error_log('[Category] Update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a category (snippets will have category_id set to NULL)
     * 
     * @param int $id Category ID
     * @return bool Success status
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log('[Category] Delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a unique slug from category name
     * 
     * @param string $name Category name
     * @return string Unique slug
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) break;
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
