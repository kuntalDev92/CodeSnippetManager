<?php
/**
 * ============================================================================
 * Activity Log Class
 * ============================================================================
 * 
 * Tracks all user activities for audit trail and activity feed.
 * Supports various action types and stores additional context.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class ActivityLog
{
    /**
     * Log a user activity
     * 
     * @param int         $userId     User who performed the action
     * @param string      $action     Action type (create, update, delete, login, etc.)
     * @param string      $entityType Entity type (snippet, category, user, etc.)
     * @param int|null    $entityId   Entity ID
     * @param array|null  $details    Additional details as key-value pairs
     * @return void
     */
    public static function log(int $userId, string $action, string $entityType, ?int $entityId = null, ?array $details = null): void
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $details ? json_encode($details) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

        } catch (PDOException $e) {
            // Silently fail - logging should not break the application
            error_log('[ActivityLog] Error: ' . $e->getMessage());
        }
    }

    /**
     * Get recent activities for a user
     * 
     * @param int $userId User ID
     * @param int $limit  Number of activities
     * @return array Activities
     */
    public static function getRecent(int $userId, int $limit = 20): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT al.*, u.username, u.full_name
                 FROM activity_log al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE al.user_id = ?
                 ORDER BY al.created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('[ActivityLog] Fetch error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all recent activities (admin view)
     * 
     * @param int $limit Number of activities
     * @return array Activities
     */
    public static function getAllRecent(int $limit = 50): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT al.*, u.username, u.full_name
                 FROM activity_log al
                 LEFT JOIN users u ON al.user_id = u.id
                 ORDER BY al.created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('[ActivityLog] Fetch error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get human-readable action description
     * 
     * @param string $action     Action type
     * @param string $entityType Entity type
     * @return string Description
     */
    public static function getActionText(string $action, string $entityType): string
    {
        $actions = [
            'create'      => "created a new {$entityType}",
            'update'      => "updated a {$entityType}",
            'delete'      => "deleted a {$entityType}",
            'clone'       => "saved a shared {$entityType} to collection",
            'login'       => "logged in",
            'logout'      => "logged out",
            'register'    => "registered an account",
            'favorite'    => "favorited a {$entityType}",
            'unfavorite'  => "unfavorited a {$entityType}",
            'share'       => "shared a {$entityType}",
            'copy'        => "copied a {$entityType}",
            'role_change' => "changed role of a {$entityType}",
            'activated'   => "activated a {$entityType} account",
            'deactivated' => "deactivated a {$entityType} account",
            'view'       => "viewed a {$entityType}",
        ];

        return $actions[$action] ?? "{$action} a {$entityType}";
    }
}
