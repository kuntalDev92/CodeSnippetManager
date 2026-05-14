<?php
/**
 * ============================================================================
 * Edit Snippet Page
 * ============================================================================
 * 
 * Edit form for existing snippets with pre-filled data.
 * Supports version tracking with change notes.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
$snippetModel = new Snippet();
$snippet = $snippetModel->findById($id);

if (!$snippet || ($snippet['user_id'] != Auth::userId() && !Auth::isAdmin())) {
    Session::setFlash('error', 'Snippet not found or access denied.');
    redirect(APP_URL . '/index.php');
}

$categoryModel = new Category();
$tagModel = new Tag();
$categories = $categoryModel->getAll();
$tags = $tagModel->getAll();
$snippetTagIds = array_column($snippet['tags'], 'id');

$pageTitle = 'Edit: ' . $snippet['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Snippet</h3>
                <p class="text-muted mb-0">Current version: v<?= $snippet['version'] ?></p>
            </div>
            <a href="<?= APP_URL ?>/view.php?id=<?= $snippet['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to View
            </a>
        </div>

        <form id="snippetForm" class="card bg-dark border-secondary">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $snippet['id'] ?>">
            
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control bg-dark border-secondary text-light"
                               value="<?= sanitize($snippet['title']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="language" class="form-label fw-semibold">Language</label>
                        <select id="language" name="language" class="form-select bg-dark border-secondary text-light">
                            <?php foreach (SUPPORTED_LANGUAGES as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $snippet['language'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea id="description" name="description" rows="2"
                              class="form-control bg-dark border-secondary text-light"><?= sanitize($snippet['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="codeEditor" class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <textarea id="codeEditor" name="code" rows="15"
                              class="form-control bg-dark border-secondary text-light code-editor"
                              required spellcheck="false"><?= sanitize($snippet['code']) ?></textarea>
                </div>

                <!-- Change Note -->
                <div class="mb-3">
                    <label for="change_note" class="form-label fw-semibold">
                        <i class="bi bi-pencil me-1"></i>Change Note
                    </label>
                    <input type="text" id="change_note" name="change_note" 
                           class="form-control bg-dark border-secondary text-light"
                           placeholder="Describe what you changed...">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select bg-dark border-secondary text-light">
                            <option value="">-- No Category --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $snippet['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Options</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_public" value="1" <?= $snippet['is_public'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Public</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_pinned" value="1" <?= $snippet['is_pinned'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Pinned</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold mb-0">Tags</label>
                        <a href="<?php echo APP_URL; ?>/tags.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-gear-fill me-1"></i>Manage Tags
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tags[]" 
                                   value="<?= $tag['id'] ?>" id="tag-<?= $tag['id'] ?>"
                                   <?= in_array($tag['id'], $snippetTagIds) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="tag-<?= $tag['id'] ?>" style="color: <?= $tag['color'] ?>">
                                #<?= sanitize($tag['name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card-footer border-secondary d-flex justify-content-between p-3">
                <a href="<?= APP_URL ?>/view.php?id=<?= $snippet['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
