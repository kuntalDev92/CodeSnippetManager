<?php
/**
 * ============================================================================
 * Tags Management Page (Admin Only)
 * ============================================================================
 * 
 * Allows admins to create, edit, and delete tags.
 * Members can view tags but cannot modify them.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$tagModel = new Tag();
// Show all tags, but counts should reflect only the current user's snippets
$tags = $tagModel->getAll(Auth::userId());

$pageTitle = 'Manage Tags';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-tags me-2 text-primary"></i>Manage Tags</h3>
                <p class="text-muted mb-0">Create, edit, and delete tags. Tags are shared across users, but counts below show only your snippets.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="resetTagForm()">
                <i class="bi bi-plus-lg me-1"></i>New Tag
            </button>
        </div>

        <!-- Tags Grid -->
        <?php if (empty($tags)): ?>
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <h5>No tags created yet</h5>
            <p class="text-muted">Create your first tag to help organize snippets.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="resetTagForm()">
                <i class="bi bi-plus-lg me-1"></i>Create Tag
            </button>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($tags as $tag): ?>
            <div class="col-md-4 col-sm-6" id="tag-card-<?php echo $tag['id']; ?>">
                <div class="card bg-dark border-secondary h-100" style="border-left: 4px solid <?php echo $tag['color']; ?> !important;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1" style="color: <?php echo $tag['color']; ?>">
                                #<?php echo htmlspecialchars($tag['name']); ?>
                            </h6>
                            <small class="text-muted">
                                <?php echo (int) $tag['usage_count']; ?> snippet(s)
                            </small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-dark" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" onclick="editTag(<?php echo htmlspecialchars(json_encode($tag)); ?>)">
                                        <i class="bi bi-pencil me-2"></i>Edit
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger" onclick="deleteTag(<?php echo $tag['id']; ?>, '<?php echo htmlspecialchars($tag['name']); ?>', <?php echo (int) $tag['usage_count']; ?>)">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Info Box -->
        <div class="card bg-dark border-secondary mt-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-info"></i>About Tags</h6>
                <ul class="text-muted small mb-0">
                    <li>Tags help organize snippets across multiple categories.</li>
                    <li>A snippet can have multiple tags for cross-referencing.</li>
                    <li>Tags are shared across all users — everyone uses the same tag pool.</li>
                    <li>Any user can create, edit, and delete tags.</li>
                    <li>Deleting a tag removes it from all snippets but doesn't delete the snippets.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Tag Create/Edit Modal -->
<div class="modal fade" id="tagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="tagModalTitle">New Tag</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="tagForm">
                <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                <input type="hidden" name="id" id="tagId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tag Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted">#</span>
                            <input type="text" name="name" id="tagName" 
                                   class="form-control bg-dark border-secondary text-light" 
                                   required maxlength="50" placeholder="e.g. laravel, api, helper"
                                   pattern="[a-zA-Z0-9_-]+"
                                   title="Only letters, numbers, hyphens, and underscores">
                        </div>
                        <small class="text-muted">Only letters, numbers, hyphens, and underscores. No spaces.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Color</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" name="color" id="tagColor" 
                                   class="form-control form-control-color" value="#8b5cf6"
                                   style="width: 60px; height: 40px;">
                            <div id="tagPreview" class="tag-badge" style="border-color: #8b5cf6; color: #8b5cf6;">
                                #preview
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Reset form for creating a new tag
 */
function resetTagForm() {
    document.getElementById('tagModalTitle').textContent = 'New Tag';
    document.getElementById('tagId').value = '';
    document.getElementById('tagName').value = '';
    document.getElementById('tagColor').value = '#8b5cf6';
    updateTagPreview();
}

/**
 * Populate form for editing an existing tag
 * @param {object} tag - Tag object with id, name, color
 */
function editTag(tag) {
    document.getElementById('tagModalTitle').textContent = 'Edit Tag';
    document.getElementById('tagId').value = tag.id;
    document.getElementById('tagName').value = tag.name;
    document.getElementById('tagColor').value = tag.color || '#8b5cf6';
    updateTagPreview();
    new bootstrap.Modal(document.getElementById('tagModal')).show();
}

/**
 * Delete a tag with confirmation
 * @param {number} id    - Tag ID
 * @param {string} name  - Tag name for confirmation
 * @param {number} count - Number of snippets using this tag
 */
async function deleteTag(id, name, count) {
    let msg = 'Delete tag "#' + name + '"?';
    if (count > 0) {
        msg += '\n\nThis tag is used by ' + count + ' snippet(s). It will be removed from all of them.';
    }
    msg += '\n\nThis action cannot be undone.';
    
    if (!confirm(msg)) return;
    
    const result = await SnippetManager.ajax('delete_tag', { id: id });
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        // Animate removal
        const card = document.getElementById('tag-card-' + id);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(function() { card.remove(); }, 300);
        }
    } else {
        SnippetManager.showToast(result.message || 'Failed to delete tag.', 'danger');
    }
}

/**
 * Update the tag preview badge when name/color changes
 */
function updateTagPreview() {
    const name = document.getElementById('tagName').value || 'preview';
    const color = document.getElementById('tagColor').value;
    const preview = document.getElementById('tagPreview');
    preview.textContent = '#' + name;
    preview.style.borderColor = color;
    preview.style.color = color;
}

// Live preview updates
document.getElementById('tagName').addEventListener('input', updateTagPreview);
document.getElementById('tagColor').addEventListener('input', updateTagPreview);

// Form submission
document.getElementById('tagForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Prevent duplicate submits / duplicate toasts
    if (this.dataset.submitting === '1') {
        return;
    }
    this.dataset.submitting = '1';
    
    const formData = new FormData(this);
    const data = {};
    formData.forEach(function(value, key) { data[key] = value; });
    
    const action = data.id ? 'update_tag' : 'create_tag';
    const submitBtn = this.querySelector('[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Save Tag';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    }

    const result = await SnippetManager.ajax(action, data);

    this.dataset.submitting = '0';
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
    
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        SnippetManager.showToast(result.message || 'Failed to save tag.', 'danger');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
