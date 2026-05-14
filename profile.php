<?php
/**
 * ============================================================================
 * Profile Page
 * ============================================================================
 * @package  CodeSnippetManager
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$userModel = new User();
$user = $userModel->findById(Auth::userId());
$stats = $userModel->getStats(Auth::userId());

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h3 class="fw-bold mb-4"><i class="bi bi-person-circle me-2 text-primary"></i>My Profile</h3>

        <!-- Profile Info Card -->
        <div class="card bg-dark border-secondary mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-4 mb-4">
                    <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center">
                        <span class="text-white fw-bold"><?= strtoupper(substr($user['full_name'], 0, 2)) ?></span>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= sanitize($user['full_name']) ?></h4>
                        <p class="text-muted mb-0">@<?= sanitize($user['username']) ?> • <?= ucfirst($user['role']) ?></p>
                        <small class="text-muted">Member since <?= date('M Y', strtotime($user['created_at'])) ?></small>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="row text-center g-3">
                    <div class="col-3">
                        <div class="fw-bold fs-4"><?= $stats['snippets'] ?></div>
                        <small class="text-muted">Snippets</small>
                    </div>
                    <div class="col-3">
                        <div class="fw-bold fs-4"><?= $stats['favorites'] ?></div>
                        <small class="text-muted">Favorites</small>
                    </div>
                    <div class="col-3">
                        <div class="fw-bold fs-4"><?= $stats['shares'] ?></div>
                        <small class="text-muted">Shares</small>
                    </div>
                    <div class="col-3">
                        <div class="fw-bold fs-4"><?= formatNumber($stats['views']) ?></div>
                        <small class="text-muted">Views</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Profile Form -->
        <div class="card bg-dark border-secondary mb-4">
            <div class="card-header border-secondary">
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Update Profile</h5>
            </div>
            <div class="card-body p-4">
                <form id="profileForm" onsubmit="updateProfile(event)">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control bg-dark border-secondary text-light"
                               value="<?= sanitize($user['full_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control bg-dark border-secondary text-light"
                               value="<?= sanitize($user['email']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary">
                <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Change Password</h5>
            </div>
            <div class="card-body p-4">
                <form id="passwordForm" onsubmit="changePassword(event)">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control bg-dark border-secondary text-light" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control bg-dark border-secondary text-light" required minlength="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control bg-dark border-secondary text-light" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-lock me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function updateProfile(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const result = await SnippetManager.ajax('update_profile', data);
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
    }
}

async function changePassword(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    if (data.new_password !== data.confirm_password) {
        SnippetManager.showToast('Passwords do not match.', 'danger');
        return;
    }
    
    const result = await SnippetManager.ajax('change_password', data);
    SnippetManager.showToast(result.message, result.success ? 'success' : 'danger');
    if (result.success) e.target.reset();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
