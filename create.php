<?php
/**
 * ============================================================================
 * Create Snippet Page
 * ============================================================================
 * 
 * Form to create a new code snippet with all metadata fields,
 * category selection, tags, and visibility settings.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$categoryModel = new Category();
$tagModel = new Tag();

$categories = $categoryModel->getAll();
$tags = $tagModel->getAll();

$pageTitle = 'Create Snippet';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Create New Snippet
                </h3>
                <p class="text-muted mb-0">Save your frequently used code for quick access</p>
            </div>
            <a href="<?= APP_URL ?>/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>

        <!-- Snippet Form -->
        <form id="snippetForm" class="card bg-dark border-secondary">
            <?= csrfField() ?>
            <div class="card-body p-4">
                <div class="row">
                    <!-- Title -->
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label fw-semibold">
                            <i class="bi bi-type me-1"></i>Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="title" name="title" 
                               class="form-control bg-dark border-secondary text-light"
                               placeholder="e.g., PDO Database Connection" required maxlength="255">
                    </div>

                    <!-- Language -->
                    <div class="col-md-4 mb-3">
                        <label for="language" class="form-label fw-semibold">
                            <i class="bi bi-braces me-1"></i>Language
                        </label>
                        <select id="language" name="language" class="form-select bg-dark border-secondary text-light">
                            <?php foreach (SUPPORTED_LANGUAGES as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $key === 'php' ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">
                        <i class="bi bi-text-paragraph me-1"></i>Description
                    </label>
                    <textarea id="description" name="description" rows="2"
                              class="form-control bg-dark border-secondary text-light"
                              placeholder="Brief description of what this snippet does..."></textarea>
                </div>

                <!-- Code Editor -->
                <div class="mb-3">
                    <label for="codeEditor" class="form-label fw-semibold">
                        <i class="bi bi-code-slash me-1"></i>Code <span class="text-danger">*</span>
                    </label>
                    <textarea id="codeEditor" name="code" rows="15"
                              class="form-control bg-dark border-secondary text-light code-editor"
                              placeholder="Paste your code here..." required
                              spellcheck="false"></textarea>
                    <small class="text-muted">Tip: Use Tab for indentation. Auto-saved every 30 seconds.</small>
                </div>

                <div class="row">
                    <!-- Category -->
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label fw-semibold">
                            <i class="bi bi-folder me-1"></i>Category
                        </label>
                        <select id="category_id" name="category_id" class="form-select bg-dark border-secondary text-light">
                            <option value="">-- No Category --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Visibility -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-eye me-1"></i>Visibility & Options
                        </label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_public" value="1" id="isPublic">
                                <label class="form-check-label" for="isPublic">
                                    <i class="bi bi-globe me-1"></i>Public
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_pinned" value="1" id="isPinned">
                                <label class="form-check-label" for="isPinned">
                                    <i class="bi bi-pin me-1"></i>Pinned
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold mb-0">
                            <i class="bi bi-tags me-1"></i>Tags
                        </label>
                        <a href="<?php echo APP_URL; ?>/tags.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-gear-fill me-1"></i>Manage Tags
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tags[]" 
                                   value="<?= $tag['id'] ?>" id="tag-<?= $tag['id'] ?>">
                            <label class="form-check-label" for="tag-<?= $tag['id'] ?>" 
                                   style="color: <?= $tag['color'] ?>">
                                #<?= sanitize($tag['name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card-footer border-secondary d-flex justify-content-between p-3">
                <a href="<?= APP_URL ?>/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-check-lg me-1"></i>Save Snippet
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
