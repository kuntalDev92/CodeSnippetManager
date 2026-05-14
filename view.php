<?php
/**
 * ============================================================================
 * View Snippet Page
 * ============================================================================
 * 
 * Displays a single snippet with full code, syntax highlighting,
 * one-click copy, version history, sharing, and related actions.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    Session::setFlash('error', 'Invalid snippet ID.');
    redirect(APP_URL . '/index.php');
}

$snippetModel = new Snippet();
$favoriteModel = new Favorite();
$shareModel = new Share();

$snippet = $snippetModel->findById($id);

if (!$snippet) {
    Session::setFlash('error', 'Snippet not found.');
    redirect(APP_URL . '/index.php');
}

// Check permissions
$userId = Auth::userId();
$isOwner = ($snippet['user_id'] == $userId);
$hasAccess = $isOwner || $snippet['is_public'] || $shareModel->hasPermission($id, $userId);

if (!$hasAccess) {
    Session::setFlash('error', 'You do not have permission to view this snippet.');
    redirect(APP_URL . '/index.php');
}

// Track view
$snippetModel->incrementViews($id);

// Check favorite status
$isFavorited = $favoriteModel->isFavorited($userId, $id);
$favoriteCount = $favoriteModel->getCount($id);

// Get shares
$shares = $shareModel->getSnippetShares($id);

$pageTitle = $snippet['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <!-- Back Button & Actions -->
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-3">
            <div>
                <a href="<?= APP_URL ?>/index.php" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="bi bi-arrow-left me-1"></i>Back to Snippets
                </a>
                <h2 class="fw-bold mb-1">
                    <?php if ($snippet['is_pinned']): ?>
                    <i class="bi bi-pin-fill text-warning me-1"></i>
                    <?php endif; ?>
                    <?= sanitize($snippet['title']) ?>
                </h2>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <!-- Language Badge -->
                    <span class="language-badge" style="background: <?= getLanguageInfo($snippet['language'])['color'] ?>20; color: <?= getLanguageInfo($snippet['language'])['color'] ?>">
                        <span class="language-dot" style="background: <?= getLanguageInfo($snippet['language'])['color'] ?>"></span>
                        <?= getLanguageInfo($snippet['language'])['label'] ?>
                    </span>
                    
                    <!-- Category -->
                    <?php if ($snippet['category_name']): ?>
                    <span class="badge bg-secondary">
                        <i class="bi bi-folder me-1"></i><?= sanitize($snippet['category_name']) ?>
                    </span>
                    <?php endif; ?>

                    <!-- Visibility -->
                    <span class="badge <?= $snippet['is_public'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <i class="bi <?= $snippet['is_public'] ? 'bi-globe' : 'bi-lock' ?> me-1"></i>
                        <?= $snippet['is_public'] ? 'Public' : 'Private' ?>
                    </span>

                    <!-- Version -->
                    <span class="badge bg-info text-dark">v<?= $snippet['version'] ?></span>

                    <!-- Tags -->
                    <?php foreach ($snippet['tags'] as $tag): ?>
                    <span class="tag-badge" style="border-color: <?= $tag['color'] ?>; color: <?= $tag['color'] ?>">
                        #<?= sanitize($tag['name']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 flex-wrap">
                <!-- Copy Button -->
                <button class="btn btn-success" data-copy data-snippet-id="<?= $snippet['id'] ?>" title="Copy code to clipboard">
                    <i class="bi bi-clipboard me-1"></i>Copy Code
                </button>

                <!-- Favorite Button -->
                <button class="btn <?= $isFavorited ? 'btn-danger' : 'btn-outline-danger' ?>" 
                        data-favorite data-snippet-id="<?= $snippet['id'] ?>">
                    <i class="bi <?= $isFavorited ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
                    <?= $favoriteCount ?>
                </button>

                <?php if ($isOwner): ?>
                <!-- Edit Button -->
                <a href="<?= APP_URL ?>/edit.php?id=<?= $snippet['id'] ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>

                <!-- Share Button -->
                <button class="btn btn-outline-info" data-share data-snippet-id="<?= $snippet['id'] ?>">
                    <i class="bi bi-share me-1"></i>Share
                </button>

                <!-- More Actions -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <li><button class="dropdown-item" data-pin data-snippet-id="<?= $snippet['id'] ?>">
                            <i class="bi bi-pin me-2"></i><?= $snippet['is_pinned'] ? 'Unpin' : 'Pin' ?>
                        </button></li>
                        <li><button class="dropdown-item" onclick="loadVersionHistory(<?= $snippet['id'] ?>)" data-bs-toggle="modal" data-bs-target="#versionModal">
                            <i class="bi bi-clock-history me-2"></i>Version History
                        </button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item text-danger" data-delete data-snippet-id="<?= $snippet['id'] ?>" data-title="<?= sanitize($snippet['title']) ?>">
                            <i class="bi bi-trash me-2"></i>Delete
                        </button></li>
                    </ul>
                </div>
                <?php else: ?>
                <!-- Not owner: show Save to My Snippets button -->
                <button class="btn btn-outline-success" id="cloneBtn"
                        onclick="cloneToMySnippets(<?php echo $snippet['id']; ?>, this)"
                        title="Save an independent copy to your collection">
                    <i class="bi bi-download me-1"></i>Save to My Snippets
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Description -->
        <?php if ($snippet['description']): ?>
        <div class="card bg-dark border-secondary mb-4">
            <div class="card-body">
                <p class="mb-0"><?= nl2br(sanitize($snippet['description'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Code Block -->
        <div class="code-container mb-4">
            <div class="d-flex justify-content-between align-items-center bg-secondary bg-opacity-25 px-3 py-2 rounded-top">
                <span class="text-muted small">
                    <i class="bi bi-file-code me-1"></i><?= getLanguageInfo($snippet['language'])['label'] ?>
                </span>
                <button class="btn btn-sm btn-outline-success" data-copy data-snippet-id="<?= $snippet['id'] ?>">
                    <i class="bi bi-clipboard me-1"></i>Copy
                </button>
            </div>
            <pre class="mb-0 rounded-top-0"><code class="language-<?= $snippet['language'] ?>" id="code-<?= $snippet['id'] ?>"><?= sanitize($snippet['code']) ?></code></pre>
        </div>

        <!-- Metadata -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Details</h6>
                        <table class="table table-dark table-sm mb-0">
                            <tr>
                                <td class="text-muted">Author</td>
                                <td class="fw-semibold"><?= sanitize($snippet['author_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created</td>
                                <td><?= date('M j, Y g:i A', strtotime($snippet['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Updated</td>
                                <td><?= timeAgo($snippet['updated_at']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Version</td>
                                <td>v<?= $snippet['version'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-dark border-secondary">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3"><i class="bi bi-bar-chart me-2"></i>Statistics</h6>
                        <div class="row text-center g-3">
                            <div class="col-4">
                                <div class="fs-3 fw-bold text-primary"><?= formatNumber($snippet['views_count']) ?></div>
                                <small class="text-muted">Views</small>
                            </div>
                            <div class="col-4">
                                <div class="fs-3 fw-bold text-success"><?= formatNumber($snippet['copies_count']) ?></div>
                                <small class="text-muted">Copies</small>
                            </div>
                            <div class="col-4">
                                <div class="fs-3 fw-bold text-danger"><?= $favoriteCount ?></div>
                                <small class="text-muted">Favorites</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shared With -->
        <?php if ($isOwner && !empty($shares)): ?>
        <div class="card bg-dark border-secondary mt-3">
            <div class="card-header border-secondary">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i>Shared with (<?= count($shares) ?>)</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($shares as $share): ?>
                    <div class="d-flex align-items-center gap-2 bg-secondary bg-opacity-25 rounded px-3 py-2">
                        <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">
                            <span class="text-white small fw-bold"><?= strtoupper(substr($share['full_name'], 0, 1)) ?></span>
                        </div>
                        <div>
                            <strong class="small"><?= sanitize($share['full_name']) ?></strong>
                            <small class="text-muted d-block"><?= $share['permission'] ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Version History Modal -->
<div class="modal fade" id="versionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Version History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="versionHistoryList">
                <div class="text-center p-3"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-share me-2"></i>Share Snippet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="shareForm">
                <div class="modal-body">
                    <input type="hidden" name="snippet_id" value="<?= $snippet['id'] ?>">
                    <input type="hidden" name="shared_with" id="selectedUserId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Search User</label>
                        <input type="text" class="form-control bg-dark border-secondary text-light" 
                               id="shareUserSearch" placeholder="Type username or email...">
                        <div id="userSearchResults" class="mt-2"></div>
                        <div id="selectedUserName" class="d-none mt-2 alert alert-info py-2"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Permission</label>
                        <select name="permission" class="form-select bg-dark border-secondary text-light">
                            <option value="view">View Only</option>
                            <option value="edit">Can Edit</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message (optional)</label>
                        <textarea name="message" class="form-control bg-dark border-secondary text-light" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-share me-1"></i>Share</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Clone/Save a snippet to the current user's own collection
 */
async function cloneToMySnippets(snippetId, btn) {
    if (!confirm('Save a copy of this snippet to your collection?\n\nThis creates an independent copy that you own.')) {
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    const result = await SnippetManager.ajax('clone_snippet', { snippet_id: snippetId });

    if (result.success) {
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Saved!';
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
        SnippetManager.showToast(result.message + ' Redirecting...', 'success');
        setTimeout(function() {
            window.location.href = SnippetManager.getAppUrl() + '/view.php?id=' + result.id;
        }, 1500);
    } else {
        btn.disabled = false;
        btn.innerHTML = originalText;
        SnippetManager.showToast(result.message || 'Failed to save snippet.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
