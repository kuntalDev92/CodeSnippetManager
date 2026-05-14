<?php
/**
 * ============================================================================
 * Shared Snippets Page
 * ============================================================================
 * 
 * Shows all snippets shared with the current user.
 * Provides options to:
 *   - View the shared snippet
 *   - Save/Clone to own collection (independent copy)
 *   - Remove the share link (hides from your list)
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$shareModel = new Share();
$shares = $shareModel->getSharedWithMe(Auth::userId());
$shareModel->markAsRead(Auth::userId());

$pageTitle = 'Shared with Me';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-share me-2 text-info"></i>Shared with Me</h3>
                <p class="text-muted mb-0">Snippets shared by other team members. Save a copy to keep it permanently.</p>
            </div>
            <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-4" role="alert">
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div>
                <strong>Tip:</strong> Shared snippets belong to the original author. 
                Click <strong>"Save to My Snippets"</strong> to create your own independent copy 
                that won't disappear if the author deletes theirs.
            </div>
        </div>

        <?php if (empty($shares)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No shared snippets</h5>
            <p class="text-muted">When someone shares a snippet with you, it will appear here.</p>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($shares as $share): ?>
            <div class="col-md-6 col-lg-4" id="share-<?php echo $share['id']; ?>">
                <div class="snippet-card card h-100 fade-in">
                    <!-- Card Header -->
                    <div class="card-header">
                        <h6 class="fw-bold mb-1">
                            <a href="<?php echo APP_URL; ?>/view.php?id=<?php echo $share['snippet_id']; ?>" class="text-decoration-none text-light">
                                <?php echo htmlspecialchars($share['title']); ?>
                            </a>
                        </h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-secondary">
                                <?php echo htmlspecialchars($share['language']); ?>
                            </span>
                            <span class="badge <?php echo $share['permission'] === 'edit' ? 'bg-success' : 'bg-info text-dark'; ?>">
                                <i class="bi <?php echo $share['permission'] === 'edit' ? 'bi-pencil' : 'bi-eye'; ?> me-1"></i><?php echo $share['permission']; ?>
                            </span>
                            <?php if ($share['category_name']): ?>
                            <span class="badge bg-dark">
                                <span class="language-dot me-1" style="background: <?php echo $share['category_color'] ?? '#6366f1'; ?>"></span>
                                <?php echo htmlspecialchars($share['category_name']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body px-3 pt-3">
                        <!-- Shared by info -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">
                                <span class="text-white fw-bold small"><?php echo strtoupper(substr($share['shared_by_fullname'], 0, 1)); ?></span>
                            </div>
                            <div>
                                <small class="fw-semibold"><?php echo htmlspecialchars($share['shared_by_fullname']); ?></small>
                                <small class="text-muted d-block">@<?php echo htmlspecialchars($share['shared_by_name']); ?></small>
                            </div>
                        </div>

                        <?php if (!empty($share['message'])): ?>
                        <div class="alert alert-secondary py-2 mb-2 small">
                            <i class="bi bi-chat-dots me-1"></i><?php echo htmlspecialchars($share['message']); ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($share['description'])): ?>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars(substr($share['description'], 0, 120)); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Card Footer with Actions -->
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <!-- View Button -->
                                <a href="<?php echo APP_URL; ?>/view.php?id=<?php echo $share['snippet_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="View snippet">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <!-- Save to My Snippets Button -->
                                <button class="btn btn-sm btn-outline-success" 
                                        onclick="cloneSharedSnippet(<?php echo $share['snippet_id']; ?>, this)"
                                        title="Save an independent copy to your collection">
                                    <i class="bi bi-download me-1"></i>Save
                                </button>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <small class="text-muted"><?php echo timeAgo($share['created_at']); ?></small>
                                <!-- Remove Share Link -->
                                <button class="btn btn-sm btn-outline-danger border-0" 
                                        onclick="removeSharedSnippet(<?php echo $share['id']; ?>)"
                                        title="Remove from your shared list">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
/**
 * Clone/Save a shared snippet to the current user's own collection
 * Creates an independent copy that the user owns
 * 
 * @param {number} snippetId - The shared snippet ID to clone
 * @param {HTMLElement} btn  - The button element (for loading state)
 */
async function cloneSharedSnippet(snippetId, btn) {
    // Confirm before cloning
    if (!confirm('Save a copy of this snippet to your collection?\n\nThis creates an independent copy that you own.')) {
        return;
    }

    // Show loading state
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    const result = await SnippetManager.ajax('clone_snippet', { snippet_id: snippetId });

    btn.disabled = false;
    btn.innerHTML = originalText;

    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        // Change button to show it's been saved
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Saved!';
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
        btn.disabled = true;
    } else {
        SnippetManager.showToast(result.message || 'Failed to save snippet.', 'danger');
    }
}

/**
 * Remove a shared snippet from the user's shared list
 * Does NOT delete the original snippet — only removes the share link
 * 
 * @param {number} shareId - The share record ID
 */
async function removeSharedSnippet(shareId) {
    if (!confirm('Remove this snippet from your shared list?\n\nThe original snippet will not be deleted.')) {
        return;
    }

    const result = await SnippetManager.ajax('remove_share', { share_id: shareId });

    if (result.success) {
        SnippetManager.showToast('Removed from shared list.', 'success');
        // Animate removal
        const card = document.getElementById('share-' + shareId);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(function() { 
                card.remove(); 
                // Check if no more shares
                const remaining = document.querySelectorAll('[id^="share-"]');
                if (remaining.length === 0) {
                    location.reload(); // Show empty state
                }
            }, 300);
        }
    } else {
        SnippetManager.showToast(result.message || 'Failed to remove.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
