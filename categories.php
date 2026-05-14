<?php
/**
 * ============================================================================
 * Categories Management Page
 * ============================================================================
 * @package  CodeSnippetManager
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$categoryModel = new Category();
// Show all categories, but counts should reflect only the current user's snippets
$categories = $categoryModel->getAll(Auth::userId());

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-folder me-2 text-primary"></i>Manage Categories</h3>
                <p class="text-muted mb-0">Categories are shared across users, but counts below show only your snippets.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" 
                    onclick="resetCategoryForm()">
                <i class="bi bi-plus-lg me-1"></i>New Category
            </button>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col-md-6">
                <div class="card bg-dark border-secondary h-100" style="border-left: 4px solid <?= $cat['color'] ?> !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold mb-1"><?= sanitize($cat['name']) ?></h5>
                                <p class="text-muted small mb-2"><?= sanitize($cat['description'] ?? 'No description') ?></p>
                                <span class="badge bg-secondary"><?= $cat['snippet_count'] ?> snippets</span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-dark" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-dark">
                                    <li><button class="dropdown-item" onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                        <i class="bi bi-pencil me-2"></i>Edit
                                    </button></li>
                                    <li><button class="dropdown-item text-danger" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= sanitize($cat['name']) ?>')">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="categoryModalTitle">New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="catId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="catName" class="form-control bg-dark border-secondary text-light" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="catDesc" class="form-control bg-dark border-secondary text-light" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" id="catColor" class="form-control form-control-color" value="#6366f1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="catSort" class="form-control bg-dark border-secondary text-light" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetCategoryForm() {
    document.getElementById('categoryModalTitle').textContent = 'New Category';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catColor').value = '#6366f1';
    document.getElementById('catSort').value = '0';
}

function editCategory(cat) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('catColor').value = cat.color;
    document.getElementById('catSort').value = cat.sort_order;
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

async function deleteCategory(id, name) {
    if (!confirm(`Delete category "${name}"? Snippets will be uncategorized.`)) return;
    const result = await SnippetManager.ajax('delete_category', { id: id });
    if (result.success) {
        SnippetManager.showToast(result.message, 'success');
        setTimeout(() => location.reload(), 800);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
