<?php
/**
 * ============================================================================
 * User Model Class
 * ============================================================================
 * 
 * Handles all user-related database operations including CRUD,
 * profile management, and user queries.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class User
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
     * Find a user by their ID
     * 
     * @param int $id User ID
     * @return array|null User data or null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, full_name, avatar, role, is_active, last_login, created_at 
             FROM users WHERE id = ?"
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Get all active users (for team sharing)
     * 
     * @param int|null $excludeId User ID to exclude (usually current user)
     * @return array List of users
     */
    public function getAllActive(?int $excludeId = null): array
    {
        $sql = "SELECT id, username, full_name, email, avatar, role 
                FROM users WHERE is_active = 1";
        $params = [];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " ORDER BY full_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update user profile
     * 
     * @param int   $id   User ID
     * @param array $data Profile data to update
     * @return bool Success status
     */
    public function updateProfile(int $id, array $data): bool
    {
        $allowedFields = ['full_name', 'email', 'avatar'];
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get user statistics (snippet count, favorites, shares)
     * 
     * @param int $userId User ID
     * @return array Statistics
     */
    public function getStats(int $userId): array
    {
        // Total snippets
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM snippets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $snippets = $stmt->fetch()['total'];

        // Total favorites
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        $favorites = $stmt->fetch()['total'];

        // Total shares
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM shared_snippets WHERE shared_by = ?");
        $stmt->execute([$userId]);
        $shares = $stmt->fetch()['total'];

        // Total views
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(views_count), 0) as total FROM snippets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $views = $stmt->fetch()['total'];

        return [
            'snippets'  => (int) $snippets,
            'favorites' => (int) $favorites,
            'shares'    => (int) $shares,
            'views'     => (int) $views,
        ];
    }

    /**
     * Search users by name or email
     * 
     * @param string $query Search query
     * @param int    $excludeId User to exclude
     * @return array Matching users
     */
    public function search(string $query, int $excludeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, full_name, email, avatar 
             FROM users 
             WHERE is_active = 1 
               AND id != ? 
               AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)
             ORDER BY full_name ASC 
             LIMIT 20"
        );
        $searchTerm = "%{$query}%";
        $stmt->execute([$excludeId, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
