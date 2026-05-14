<?php
/**
 * ============================================================================
 * Favorite Model Class
 * ============================================================================
 * 
 * Manages user's favorite/bookmarked snippets with toggle functionality.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Favorite
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
     * Toggle favorite status for a snippet
     * 
     * @param int $userId    User ID
     * @param int $snippetId Snippet ID
     * @return array Result with new status
     */
    public function toggle(int $userId, int $snippetId): array
    {
        if ($this->isFavorited($userId, $snippetId)) {
            // Remove from favorites
            $stmt = $this->db->prepare("DELETE FROM favorites WHERE user_id = ? AND snippet_id = ?");
            $stmt->execute([$userId, $snippetId]);

            ActivityLog::log($userId, 'unfavorite', 'snippet', $snippetId);

            return ['favorited' => false, 'message' => 'Removed from favorites'];
        } else {
            // Add to favorites
            $stmt = $this->db->prepare("INSERT INTO favorites (user_id, snippet_id) VALUES (?, ?)");
            $stmt->execute([$userId, $snippetId]);

            ActivityLog::log($userId, 'favorite', 'snippet', $snippetId);

            return ['favorited' => true, 'message' => 'Added to favorites'];
        }
    }

    /**
     * Check if a snippet is favorited by a user
     * 
     * @param int $userId    User ID
     * @param int $snippetId Snippet ID
     * @return bool
     */
    public function isFavorited(int $userId, int $snippetId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM favorites WHERE user_id = ? AND snippet_id = ?");
        $stmt->execute([$userId, $snippetId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Get all favorite snippet IDs for a user
     * 
     * @param int $userId User ID
     * @return array Array of snippet IDs
     */
    public function getUserFavoriteIds(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT snippet_id FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'snippet_id');
    }

    /**
     * Get favorite count for a snippet
     * 
     * @param int $snippetId Snippet ID
     * @return int Count
     */
    public function getCount(int $snippetId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM favorites WHERE snippet_id = ?");
        $stmt->execute([$snippetId]);
        return (int) $stmt->fetch()['count'];
    }
}
