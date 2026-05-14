<?php
/**
 * ============================================================================
 * Share Model Class
 * ============================================================================
 * 
 * Manages team sharing functionality - share snippets with specific
 * users with view/edit permissions and notifications.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Share
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
     * Share a snippet with another user
     * 
     * @param int    $snippetId  Snippet ID
     * @param int    $sharedBy   User sharing the snippet
     * @param int    $sharedWith User receiving the snippet
     * @param string $permission Permission level (view/edit)
     * @param string $message    Optional sharing message
     * @return array Result
     */
    public function shareWith(int $snippetId, int $sharedBy, int $sharedWith, string $permission = 'view', string $message = ''): array
    {
        // Prevent sharing with yourself
        if ($sharedBy === $sharedWith) {
            return ['success' => false, 'message' => 'You cannot share a snippet with yourself.'];
        }

        try {
            // Check if already shared
            $stmt = $this->db->prepare(
                "SELECT id FROM shared_snippets WHERE snippet_id = ? AND shared_by = ? AND shared_with = ?"
            );
            $stmt->execute([$snippetId, $sharedBy, $sharedWith]);

            if ($stmt->fetch()) {
                // Update existing share
                $updateStmt = $this->db->prepare(
                    "UPDATE shared_snippets SET permission = ?, message = ?, is_read = 0 
                     WHERE snippet_id = ? AND shared_by = ? AND shared_with = ?"
                );
                $updateStmt->execute([$permission, $message, $snippetId, $sharedBy, $sharedWith]);
                return ['success' => true, 'message' => 'Share permissions updated.'];
            }

            // Create new share
            $insertStmt = $this->db->prepare(
                "INSERT INTO shared_snippets (snippet_id, shared_by, shared_with, permission, message) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insertStmt->execute([$snippetId, $sharedBy, $sharedWith, $permission, $message]);

            // Log activity
            ActivityLog::log($sharedBy, 'share', 'snippet', $snippetId, [
                'shared_with' => $sharedWith,
                'permission'  => $permission,
            ]);

            return ['success' => true, 'message' => 'Snippet shared successfully!'];

        } catch (PDOException $e) {
            error_log('[Share] Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to share snippet.'];
        }
    }

    /**
     * Remove a share
     * 
     * @param int $shareId  Share record ID
     * @param int $userId   User removing the share (must be sharer or recipient)
     * @return bool
     */
    public function removeShare(int $shareId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM shared_snippets WHERE id = ? AND (shared_by = ? OR shared_with = ?)"
        );
        $stmt->execute([$shareId, $userId, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get snippets shared with a user
     * 
     * @param int $userId User ID
     * @return array Shared snippets
     */
    public function getSharedWithMe(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ss.*, s.title, s.description, s.language, s.code,
                    u.username as shared_by_name, u.full_name as shared_by_fullname,
                    c.name as category_name, c.color as category_color
             FROM shared_snippets ss
             INNER JOIN snippets s ON ss.snippet_id = s.id
             INNER JOIN users u ON ss.shared_by = u.id
             LEFT JOIN categories c ON s.category_id = c.id
             WHERE ss.shared_with = ?
             ORDER BY ss.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get users a snippet is shared with
     * 
     * @param int $snippetId Snippet ID
     * @return array Share details
     */
    public function getSnippetShares(int $snippetId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ss.*, u.username, u.full_name, u.email, u.avatar
             FROM shared_snippets ss
             INNER JOIN users u ON ss.shared_with = u.id
             WHERE ss.snippet_id = ?
             ORDER BY ss.created_at DESC"
        );
        $stmt->execute([$snippetId]);
        return $stmt->fetchAll();
    }

    /**
     * Get unread share notifications count
     * 
     * @param int $userId User ID
     * @return int Unread count
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM shared_snippets WHERE shared_with = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Mark shares as read
     * 
     * @param int $userId User ID
     * @return void
     */
    public function markAsRead(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE shared_snippets SET is_read = 1 WHERE shared_with = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Check if a user has permission to view/edit a snippet
     * 
     * @param int    $snippetId  Snippet ID
     * @param int    $userId     User ID
     * @param string $permission Required permission level
     * @return bool
     */
    public function hasPermission(int $snippetId, int $userId, string $permission = 'view'): bool
    {
        $sql = "SELECT id FROM shared_snippets WHERE snippet_id = ? AND shared_with = ?";
        $params = [$snippetId, $userId];

        if ($permission === 'edit') {
            $sql .= " AND permission = 'edit'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
