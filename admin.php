<?php
/**
 * ============================================================================
 * Admin Panel
 * ============================================================================
 * 
 * Admin-only page for user management and system overview.
 * 
 * Features:
 *   - View all registered users with stats
 *   - Activate / Deactivate user accounts
 *   - Change user roles (admin / member)
 *   - View system-wide statistics
 *   - View all snippets across users
 *   - Activity log overview
 * 
 * @package  CodeSnippetManager
 * @author   Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

// Admin only
if (!Auth::isAdmin()) {
    Session::setFlash('error', 'Access denied. Admin privileges required.');
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$userModel = new User();
$snippetModel = new Snippet();
$categoryModel = new Category();
$tagModel = new Tag();

// Get all users with their stats
$allUsers = $userModel->getAllActive(null); // null = don't exclude anyone

// Get system-wide stats
$db = Database::getInstance()->getConnection();

$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSnippets = $db->query("SELECT COUNT(*) FROM snippets")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalTags = $db->query("SELECT COUNT(*) FROM tags")->fetchColumn();
$totalShares = $db->query("SELECT COUNT(*) FROM shared_snippets")->fetchColumn();
$publicSnippets = $db->query("SELECT COUNT(*) FROM snippets WHERE is_public = 1")->fetchColumn();

// Per-user snippet counts
$userSnippetCounts = $db->query(
    "SELECT u.id, u.username, u.full_name, u.email, u.role, u.is_active, u.last_login, u.created_at,
            COUNT(s.id) as snippet_count,
            COALESCE(SUM(s.views_count), 0) as total_views
     FROM users u
     LEFT JOIN snippets s ON u.id = s.user_id
     GROUP BY u.id
     ORDER BY u.created_at DESC"
)->fetchAll();

// Recent activity (all users)
$recentActivity = ActivityLog::getAllRecent(20);

$pageTitle = 'Admin Panel';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <!-- Admin Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="bi bi-shield-lock me-2 text-primary"></i>Admin Panel
                    <span class="admin-badge ms-2">ADMIN</span>
                </h3>
                <p class="text-muted mb-0">System overview and user management</p>
            </div>
            <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Snippets
            </a>
        </div>

        <!-- System Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card text-center">
                    <div class="stat-value text-primary"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Users</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card text-center">
                    <div class="stat-value text-success"><?php echo $totalSnippets; ?></div>
                    <div class="stat-label">Snippets</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card text-center">
                    <div class="stat-value text-info"><?php echo $publicSnippets; ?></div>
                    <div class="stat-label">Public</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card text-center">
                    <div class="stat-value text-warning"><?php echo $totalCategories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card text-center">
                    <div class="stat-value text-purple" style="color: #a855f7;"><?php echo $totalTags; ?></div>
                    <div class="stat-label">Tags</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="stat-card text-center">
                    <div class="stat-value text-danger"><?php echo $totalShares; ?></div>
                    <div class="stat-label">Shares</div>
                </div>
            </div>
        </div>

        <!-- User Management Table -->
        <div class="card bg-dark border-secondary mb-4">
            <div class="card-header border-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>All Users (<?php echo count($userSnippetCounts); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead>
                            <tr class="border-secondary">
                                <th class="ps-3">User</th>
                                <th>Email</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Snippets</th>
                                <th class="text-center">Views</th>
                                <th class="text-center">Status</th>
                                <th>Last Login</th>
                                <th>Joined</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userSnippetCounts as $u): ?>
                            <tr class="user-row border-secondary" id="user-row-<?php echo $u['id']; ?>">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm <?php echo $u['role'] === 'admin' ? 'bg-primary' : 'bg-secondary'; ?> rounded-circle d-flex align-items-center justify-content-center">
                                            <span class="text-white fw-bold" style="font-size: 0.7rem;">
                                                <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <strong class="d-block" style="font-size: 0.9rem;"><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                            <small class="text-muted">@<?php echo htmlspecialchars($u['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-primary' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong><?php echo (int) $u['snippet_count']; ?></strong>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted"><?php echo formatNumber((int) $u['total_views']); ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $u['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?php echo $u['last_login'] ? timeAgo($u['last_login']) : 'Never'; ?></small></td>
                                <td><small class="text-muted"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></small></td>
                                <td class="text-center">
                                    <?php if ($u['id'] != Auth::userId()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                            <!-- Toggle Role -->
                                            <li>
                                                <button class="dropdown-item" onclick="toggleRole(<?php echo $u['id']; ?>, '<?php echo $u['role']; ?>')">
                                                    <i class="bi bi-arrow-left-right me-2"></i>
                                                    Make <?php echo $u['role'] === 'admin' ? 'Member' : 'Admin'; ?>
                                                </button>
                                            </li>
                                            <!-- Toggle Active Status -->
                                            <li>
                                                <button class="dropdown-item <?php echo $u['is_active'] ? 'text-warning' : 'text-success'; ?>" 
                                                        onclick="toggleUserStatus(<?php echo $u['id']; ?>, <?php echo $u['is_active']; ?>, '<?php echo htmlspecialchars($u['full_name']); ?>')">
                                                    <i class="bi <?php echo $u['is_active'] ? 'bi-person-slash' : 'bi-person-check'; ?> me-2"></i>
                                                    <?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <!-- View User's Snippets -->
                                            <li>
                                                <a class="dropdown-item" href="<?php echo APP_URL; ?>/index.php?view=public">
                                                    <i class="bi bi-code-slash me-2"></i>View Snippets
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php else: ?>
                                    <span class="badge bg-info text-dark">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity (All Users) -->
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity (All Users)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                <p class="text-muted text-center mb-0">No activity recorded yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-sm mb-0">
                        <thead>
                            <tr class="border-secondary">
                                <th>User</th>
                                <th>Action</th>
                                <th>When</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $act): ?>
                            <tr class="border-secondary">
                                <td><strong><?php echo htmlspecialchars($act['full_name'] ?? $act['username'] ?? 'Unknown'); ?></strong></td>
                                <td><?php echo ActivityLog::getActionText($act['action'], $act['entity_type']); ?></td>
                                <td><small class="text-muted"><?php echo timeAgo($act['created_at']); ?></small></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($act['ip_address'] ?? '—'); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Toggle user role between admin and member
 */
async function toggleRole(userId, currentRole) {
    const newRole = currentRole === 'admin' ? 'member' : 'admin';
    if (!confirm('Change this user\'s role to ' + newRole + '?')) return;

    const result = await SnippetManager.ajax('admin_toggle_role', { user_id: userId, new_role: newRole });
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        SnippetManager.showToast(result.message || 'Failed to change role.', 'danger');
    }
}

/**
 * Activate or deactivate a user account
 */
async function toggleUserStatus(userId, isActive, name) {
    const action = isActive ? 'deactivate' : 'activate';
    if (!confirm(action.charAt(0).toUpperCase() + action.slice(1) + ' user "' + name + '"?\n\n' +
        (isActive ? 'They will not be able to log in.' : 'They will be able to log in again.'))) return;

    const result = await SnippetManager.ajax('admin_toggle_status', { user_id: userId });
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        SnippetManager.showToast(result.message || 'Failed to update status.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
