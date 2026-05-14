<?php
/**
 * ============================================================================
 * AJAX Request Handler
 * ============================================================================
 * 
 * Centralized AJAX endpoint that routes requests to appropriate handlers.
 * All AJAX calls go through this file for consistency and security.
 * 
 * Supports: Snippets CRUD, Search, Favorites, Sharing, Tags, Categories
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

// Load application configuration
require_once dirname(__DIR__) . '/config/app.php';

// Set JSON content type for all responses
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Require authentication for all AJAX requests
if (!Auth::isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

// Get the action from the request
$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Route the request based on the action
    match ($action) {
        // ====================================================================
        // Snippet Operations
        // ====================================================================
        'create_snippet'    => handleCreateSnippet(),
        'update_snippet'    => handleUpdateSnippet(),
        'delete_snippet'    => handleDeleteSnippet(),
        'get_snippet'       => handleGetSnippet(),
        'search_snippets'   => handleSearchSnippets(),
        'quick_search'      => handleQuickSearch(),
        'copy_snippet'      => handleCopySnippet(),
        'toggle_pin'        => handleTogglePin(),
        'get_versions'      => handleGetVersions(),
        'restore_version'   => handleRestoreVersion(),
        'clone_snippet'     => handleCloneSnippet(),

        // ====================================================================
        // Favorite Operations
        // ====================================================================
        'toggle_favorite'   => handleToggleFavorite(),
        'get_favorites'     => handleGetFavorites(),

        // ====================================================================
        // Share Operations
        // ====================================================================
        'share_snippet'     => handleShareSnippet(),
        'remove_share'      => handleRemoveShare(),
        'get_shares'        => handleGetShares(),
        'get_shared_with_me'=> handleGetSharedWithMe(),
        'mark_shares_read'  => handleMarkSharesRead(),
        'get_unread_count'  => handleGetUnreadCount(),
        'search_users'      => handleSearchUsers(),

        // ====================================================================
        // Category Operations
        // ====================================================================
        'create_category'   => handleCreateCategory(),
        'update_category'   => handleUpdateCategory(),
        'delete_category'   => handleDeleteCategory(),
        'get_categories'    => handleGetCategories(),

        // ====================================================================
        // Tag Operations
        // ====================================================================
        'create_tag'        => handleCreateTag(),
        'update_tag'        => handleUpdateTag(),
        'delete_tag'        => handleDeleteTag(),
        'get_tags'          => handleGetTags(),

        // ====================================================================
        // User/Profile Operations
        // ====================================================================
        'update_profile'    => handleUpdateProfile(),
        'change_password'   => handleChangePassword(),
        'get_dashboard'     => handleGetDashboard(),
        'get_activity'      => handleGetActivity(),

        // ====================================================================
        // Admin Operations (admin role required)
        // ====================================================================
        'admin_toggle_role'   => handleAdminToggleRole(),
        'admin_toggle_status' => handleAdminToggleStatus(),

        // Default: unknown action
        default => jsonResponse(['success' => false, 'message' => 'Unknown action: ' . $action], 400),
    };

} catch (Exception $e) {
    error_log('[AJAX] Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
}

// ============================================================================
// SNIPPET HANDLERS
// ============================================================================

/**
 * Create a new snippet
 */
function handleCreateSnippet(): void
{
    if (!verifyCsrf()) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    $snippet = new Snippet();
    $data = [
        'title'       => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'code'        => $_POST['code'] ?? '',
        'language'    => $_POST['language'] ?? 'php',
        'category_id' => $_POST['category_id'] ?? null,
        'user_id'     => Auth::userId(),
        'is_public'   => $_POST['is_public'] ?? 0,
        'is_pinned'   => $_POST['is_pinned'] ?? 0,
        'tags'        => !empty($_POST['tags']) ? explode(',', $_POST['tags']) : [],
    ];

    // Validation
    if (empty($data['title'])) {
        jsonResponse(['success' => false, 'message' => 'Title is required.'], 422);
    }
    if (empty($data['code'])) {
        jsonResponse(['success' => false, 'message' => 'Code is required.'], 422);
    }

    $id = $snippet->create($data);
    if ($id) {
        jsonResponse(['success' => true, 'message' => 'Snippet created successfully!', 'id' => $id]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to create snippet.'], 500);
    }
}

/**
 * Update an existing snippet
 */
function handleUpdateSnippet(): void
{
    if (!verifyCsrf()) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Snippet ID is required.'], 422);
    }

    $snippet = new Snippet();
    $data = [
        'title'       => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'code'        => $_POST['code'] ?? '',
        'language'    => $_POST['language'] ?? 'php',
        'category_id' => $_POST['category_id'] ?? null,
        'is_public'   => $_POST['is_public'] ?? 0,
        'is_pinned'   => $_POST['is_pinned'] ?? 0,
        'tags'        => !empty($_POST['tags']) ? explode(',', $_POST['tags']) : [],
        'change_note' => $_POST['change_note'] ?? 'Updated',
    ];

    if ($snippet->update($id, $data, Auth::userId())) {
        jsonResponse(['success' => true, 'message' => 'Snippet updated successfully!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update snippet.'], 500);
    }
}

/**
 * Delete a snippet
 */
function handleDeleteSnippet(): void
{
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $snippet = new Snippet();

    if ($snippet->delete($id, Auth::userId())) {
        jsonResponse(['success' => true, 'message' => 'Snippet deleted successfully!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to delete snippet.'], 500);
    }
}

/**
 * Get a single snippet by ID
 */
function handleGetSnippet(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $snippet = new Snippet();
    $data = $snippet->findById($id);

    if (!$data) {
        jsonResponse(['success' => false, 'message' => 'Snippet not found.'], 404);
    }

    // Check permissions
    $userId = Auth::userId();
    $share = new Share();
    if ($data['user_id'] != $userId && !$data['is_public'] && !$share->hasPermission($id, $userId)) {
        jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
    }

    // Increment view count
    $snippet->incrementViews($id);

    // Check if favorited
    $favorite = new Favorite();
    $data['is_favorited'] = $favorite->isFavorited($userId, $id);
    $data['favorite_count'] = $favorite->getCount($id);

    jsonResponse(['success' => true, 'data' => $data]);
}

/**
 * Search snippets with filters
 */
function handleSearchSnippets(): void
{
    $snippet = new Snippet();
    $filters = [
        'user_id'         => $_GET['user_id'] ?? Auth::userId(),
        'category_id'     => $_GET['category_id'] ?? null,
        'language'        => $_GET['language'] ?? null,
        'search'          => $_GET['search'] ?? null,
        'tag_id'          => $_GET['tag_id'] ?? null,
        'is_public'       => $_GET['is_public'] ?? null,
        'sort'            => $_GET['sort'] ?? 'newest',
        'favorites_only'  => $_GET['favorites_only'] ?? null,
        'shared_with_me'  => $_GET['shared_with_me'] ?? null,
        'current_user_id' => Auth::userId(),
    ];

    $page = getCurrentPage();
    $result = $snippet->getAll($filters, $page);

    // Attach favorite status
    $favorite = new Favorite();
    $favoriteIds = $favorite->getUserFavoriteIds(Auth::userId());
    foreach ($result['data'] as &$s) {
        $s['is_favorited'] = in_array($s['id'], $favoriteIds);
    }

    $result['success'] = true;
    jsonResponse($result);
}

/**
 * Quick search for autocomplete
 */
function handleQuickSearch(): void
{
    $query = trim($_GET['q'] ?? '');
    if (strlen($query) < 2) {
        jsonResponse(['success' => true, 'data' => []]);
    }

    $snippet = new Snippet();
    $results = $snippet->quickSearch($query, Auth::userId());
    jsonResponse(['success' => true, 'data' => $results]);
}

/**
 * Track copy action
 */
function handleCopySnippet(): void
{
    $id = (int)($_POST['id'] ?? 0);
    $snippet = new Snippet();
    $snippet->incrementCopies($id);
    ActivityLog::log(Auth::userId(), 'copy', 'snippet', $id);
    jsonResponse(['success' => true, 'message' => 'Code copied to clipboard!']);
}

/**
 * Toggle snippet pin status
 */
function handleTogglePin(): void
{
    $id = (int)($_POST['id'] ?? 0);
    $snippet = new Snippet();
    $pinned = $snippet->togglePin($id, Auth::userId());
    jsonResponse(['success' => true, 'pinned' => $pinned, 'message' => $pinned ? 'Snippet pinned!' : 'Snippet unpinned!']);
}

/**
 * Get version history
 */
function handleGetVersions(): void
{
    $snippetId = (int)($_GET['snippet_id'] ?? 0);
    $snippet = new Snippet();
    $versions = $snippet->getVersionHistory($snippetId);
    jsonResponse(['success' => true, 'data' => $versions]);
}

/**
 * Restore a specific version
 */
function handleRestoreVersion(): void
{
    $snippetId = (int)($_POST['snippet_id'] ?? 0);
    $version = (int)($_POST['version'] ?? 0);

    $snippet = new Snippet();
    $versionData = $snippet->getVersion($snippetId, $version);

    if (!$versionData) {
        jsonResponse(['success' => false, 'message' => 'Version not found.'], 404);
    }

    $current = $snippet->findById($snippetId);
    if ($current) {
        $snippet->update($snippetId, [
            'title'       => $current['title'],
            'description' => $current['description'],
            'code'        => $versionData['code'],
            'language'    => $current['language'],
            'category_id' => $current['category_id'],
            'is_public'   => $current['is_public'],
            'is_pinned'   => $current['is_pinned'],
            'change_note' => "Restored from version {$version}",
        ], Auth::userId());
    }

    jsonResponse(['success' => true, 'message' => "Restored to version {$version}!"]);
}

/**
 * Clone/Fork a shared snippet to the current user's own collection
 */
function handleCloneSnippet(): void
{
    $snippetId = (int)($_POST['snippet_id'] ?? 0);
    if (!$snippetId) {
        jsonResponse(['success' => false, 'message' => 'Snippet ID is required.'], 422);
    }

    $snippet = new Snippet();
    $original = $snippet->findById($snippetId);

    if (!$original) {
        jsonResponse(['success' => false, 'message' => 'Original snippet not found.'], 404);
    }

    // Check access: must be public, owned by user, or shared with user
    $userId = Auth::userId();
    $share = new Share();
    $hasAccess = ($original['user_id'] == $userId) 
                 || $original['is_public'] 
                 || $share->hasPermission($snippetId, $userId);

    if (!$hasAccess) {
        jsonResponse(['success' => false, 'message' => 'You do not have access to this snippet.'], 403);
    }

    // Don't allow cloning your own snippet (they can just duplicate)
    if ($original['user_id'] == $userId) {
        // Still allow it — user might want a copy
    }

    $newId = $snippet->cloneSnippet($snippetId, $userId);

    if ($newId) {
        jsonResponse([
            'success' => true,
            'message' => 'Snippet saved to your collection!',
            'id'      => $newId,
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to save snippet.'], 500);
    }
}

// ============================================================================
// FAVORITE HANDLERS
// ============================================================================

function handleToggleFavorite(): void
{
    $snippetId = (int)($_POST['snippet_id'] ?? 0);
    $favorite = new Favorite();
    $result = $favorite->toggle(Auth::userId(), $snippetId);
    $result['success'] = true;
    jsonResponse($result);
}

function handleGetFavorites(): void
{
    $favorite = new Favorite();
    $ids = $favorite->getUserFavoriteIds(Auth::userId());
    jsonResponse(['success' => true, 'data' => $ids]);
}

// ============================================================================
// SHARE HANDLERS
// ============================================================================

function handleShareSnippet(): void
{
    if (!verifyCsrf()) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    $share = new Share();
    $result = $share->shareWith(
        (int)$_POST['snippet_id'],
        Auth::userId(),
        (int)$_POST['shared_with'],
        $_POST['permission'] ?? 'view',
        $_POST['message'] ?? ''
    );

    jsonResponse($result);
}

function handleRemoveShare(): void
{
    $shareId = (int)($_POST['share_id'] ?? 0);
    $share = new Share();
    $removed = $share->removeShare($shareId, Auth::userId());
    jsonResponse(['success' => $removed, 'message' => $removed ? 'Share removed.' : 'Failed to remove share.']);
}

function handleGetShares(): void
{
    $snippetId = (int)($_GET['snippet_id'] ?? 0);
    $share = new Share();
    $shares = $share->getSnippetShares($snippetId);
    jsonResponse(['success' => true, 'data' => $shares]);
}

function handleGetSharedWithMe(): void
{
    $share = new Share();
    $shared = $share->getSharedWithMe(Auth::userId());
    jsonResponse(['success' => true, 'data' => $shared]);
}

function handleMarkSharesRead(): void
{
    $share = new Share();
    $share->markAsRead(Auth::userId());
    jsonResponse(['success' => true, 'message' => 'Marked as read.']);
}

function handleGetUnreadCount(): void
{
    $share = new Share();
    $count = $share->getUnreadCount(Auth::userId());
    jsonResponse(['success' => true, 'count' => $count]);
}

function handleSearchUsers(): void
{
    $query = trim($_GET['q'] ?? '');
    if (strlen($query) < 2) {
        jsonResponse(['success' => true, 'data' => []]);
    }

    $user = new User();
    $results = $user->search($query, Auth::userId());
    jsonResponse(['success' => true, 'data' => $results]);
}

// ============================================================================
// CATEGORY HANDLERS
// ============================================================================

function handleCreateCategory(): void
{
    if (!verifyCsrf()) jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);

    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Category name is required.'], 422);
    }

    $category = new Category();
    $id = $category->create($_POST);

    if ($id) {
        jsonResponse(['success' => true, 'message' => 'Category created!', 'id' => $id]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Category with this name already exists.'], 409);
    }
}

function handleUpdateCategory(): void
{
    if (!verifyCsrf()) jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);

    $category = new Category();
    $id = (int)($_POST['id'] ?? 0);

    if ($category->update($id, $_POST)) {
        jsonResponse(['success' => true, 'message' => 'Category updated!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update category.'], 500);
    }
}

function handleDeleteCategory(): void
{
    $category = new Category();
    $id = (int)($_POST['id'] ?? 0);

    if ($category->delete($id)) {
        jsonResponse(['success' => true, 'message' => 'Category deleted!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to delete category.'], 500);
    }
}

function handleGetCategories(): void
{
    $category = new Category();
    jsonResponse(['success' => true, 'data' => $category->getAll()]);
}

// ============================================================================
// TAG HANDLERS
// ============================================================================

/**
 * Create a new tag (Any logged-in user)
 * Duplicate names are rejected (case-insensitive check)
 */
function handleCreateTag(): void
{
    $name = trim($_POST['name'] ?? '');
    $color = $_POST['color'] ?? '#8b5cf6';

    if (empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Tag name is required.'], 422);
    }

    // Validate tag name format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        jsonResponse(['success' => false, 'message' => 'Tag name can only contain letters, numbers, hyphens, and underscores.'], 422);
    }

    $tag = new Tag();

    // Check for duplicate (case-insensitive)
    $existing = $tag->findByName($name);
    if ($existing) {
        jsonResponse(['success' => false, 'message' => 'Tag "#' . $name . '" already exists.'], 409);
    }

    $id = $tag->create($name, $color);
    if ($id) {
        jsonResponse(['success' => true, 'message' => 'Tag "#' . $name . '" created!', 'id' => $id, 'name' => $name]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to create tag.'], 500);
    }
}

/**
 * Update an existing tag (Any logged-in user)
 */
function handleUpdateTag(): void
{
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $color = $_POST['color'] ?? '#8b5cf6';

    if (!$id || empty($name)) {
        jsonResponse(['success' => false, 'message' => 'Tag ID and name are required.'], 422);
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        jsonResponse(['success' => false, 'message' => 'Tag name can only contain letters, numbers, hyphens, and underscores.'], 422);
    }

    $tag = new Tag();
    if ($tag->update($id, $name, $color)) {
        jsonResponse(['success' => true, 'message' => 'Tag updated!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update tag.'], 500);
    }
}

/**
 * Delete a tag (Any logged-in user)
 */
function handleDeleteTag(): void
{
    $tag = new Tag();
    $id = (int)($_POST['id'] ?? 0);

    if ($tag->delete($id)) {
        jsonResponse(['success' => true, 'message' => 'Tag deleted!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to delete tag.'], 500);
    }
}

/**
 * Get all tags
 */
function handleGetTags(): void
{
    $tag = new Tag();
    jsonResponse(['success' => true, 'data' => $tag->getAll()]);
}

// ============================================================================
// USER / DASHBOARD HANDLERS
// ============================================================================

function handleUpdateProfile(): void
{
    if (!verifyCsrf()) jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);

    $user = new User();
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'email'     => $_POST['email'] ?? '',
    ];

    if ($user->updateProfile(Auth::userId(), $data)) {
        Session::set('full_name', $data['full_name']);
        jsonResponse(['success' => true, 'message' => 'Profile updated!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update profile.'], 500);
    }
}

function handleChangePassword(): void
{
    if (!verifyCsrf()) jsonResponse(['success' => false, 'message' => 'Invalid token.'], 403);

    $auth = new Auth();
    $result = $auth->changePassword(
        Auth::userId(),
        $_POST['current_password'] ?? '',
        $_POST['new_password'] ?? ''
    );

    jsonResponse($result);
}

function handleGetDashboard(): void
{
    $userId = Auth::userId();
    $user = new User();
    $snippet = new Snippet();

    $stats = $user->getStats($userId);
    $recent = $snippet->getRecent($userId, 5);
    $languages = $snippet->getLanguageDistribution($userId);

    $share = new Share();
    $unreadShares = $share->getUnreadCount($userId);

    jsonResponse([
        'success'       => true,
        'stats'         => $stats,
        'recent'        => $recent,
        'languages'     => $languages,
        'unread_shares' => $unreadShares,
    ]);
}

function handleGetActivity(): void
{
    $activities = ActivityLog::getRecent(Auth::userId(), 20);

    // Add human-readable text
    foreach ($activities as &$activity) {
        $activity['text'] = ActivityLog::getActionText($activity['action'], $activity['entity_type']);
    }

    jsonResponse(['success' => true, 'data' => $activities]);
}

// ============================================================================
// ADMIN HANDLERS
// ============================================================================

/**
 * Toggle a user's role between admin and member (Admin only)
 */
function handleAdminToggleRole(): void
{
    if (!Auth::isAdmin()) {
        jsonResponse(['success' => false, 'message' => 'Admin access required.'], 403);
    }

    $userId = (int)($_POST['user_id'] ?? 0);
    $newRole = $_POST['new_role'] ?? '';

    if (!$userId || !in_array($newRole, ['admin', 'member'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid parameters.'], 422);
    }

    // Cannot change own role
    if ($userId === Auth::userId()) {
        jsonResponse(['success' => false, 'message' => 'You cannot change your own role.'], 403);
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);

    if ($stmt->rowCount() > 0) {
        ActivityLog::log(Auth::userId(), 'role_change', 'user', $userId, ['new_role' => $newRole]);
        jsonResponse(['success' => true, 'message' => 'User role changed to ' . $newRole . '.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }
}

/**
 * Toggle a user's active/inactive status (Admin only)
 */
function handleAdminToggleStatus(): void
{
    if (!Auth::isAdmin()) {
        jsonResponse(['success' => false, 'message' => 'Admin access required.'], 403);
    }

    $userId = (int)($_POST['user_id'] ?? 0);

    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'User ID is required.'], 422);
    }

    // Cannot deactivate yourself
    if ($userId === Auth::userId()) {
        jsonResponse(['success' => false, 'message' => 'You cannot deactivate your own account.'], 403);
    }

    $db = Database::getInstance()->getConnection();

    // Get current status
    $stmt = $db->prepare("SELECT is_active, full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }

    $newStatus = $user['is_active'] ? 0 : 1;
    $statusText = $newStatus ? 'activated' : 'deactivated';

    $updateStmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $userId]);

    ActivityLog::log(Auth::userId(), $statusText, 'user', $userId);
    jsonResponse(['success' => true, 'message' => 'User "' . $user['full_name'] . '" has been ' . $statusText . '.']);
}
