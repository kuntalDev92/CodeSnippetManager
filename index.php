<?php
/**
 * ============================================================================
 * Snippets Listing Page (Main Dashboard)
 * ============================================================================
 * 
 * Displays all user's snippets with filtering, sorting, search,
 * and category-based browsing.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

// Initialize models
$snippetModel = new Snippet();
$categoryModel = new Category();
$tagModel = new Tag();
$favoriteModel = new Favorite();

// Get filter parameters from URL
$filters = [
    'user_id'     => Auth::userId(),
    'category_id' => $_GET['category'] ?? null,
    'language'    => $_GET['language'] ?? null,
    'search'      => $_GET['search'] ?? null,
    'tag_id'      => $_GET['tag'] ?? null,
    'sort'        => $_GET['sort'] ?? 'newest',
    'current_user_id' => Auth::userId(),
];

// Check for favorites or shared filter
if (isset($_GET['view'])) {
    if ($_GET['view'] === 'favorites') {
        $filters['favorites_only'] = true;
        $filters['user_id'] = null; // Show all favorited snippets
    }
    if ($_GET['view'] === 'shared') {
        $filters['shared_with_me'] = true;
        $filters['user_id'] = null;
    }
    if ($_GET['view'] === 'public') {
        $filters['is_public'] = 1;
        $filters['user_id'] = null;
    }
}

$page = getCurrentPage();
$result = $snippetModel->getAll($filters, $page);

// Get favorites for current user
$favoriteIds = $favoriteModel->getUserFavoriteIds(Auth::userId());

// Get categories, tags and languages for sidebar — filtered by current user
$currentUserId = Auth::userId();
$categories = $categoryModel->getAll($currentUserId);
$tags = $tagModel->getAll($currentUserId);
$languageCounts = $snippetModel->getLanguageDistribution($currentUserId);

// Build a lookup map: language_key => count
$langCountMap = [];
foreach ($languageCounts as $lc) {
    $langCountMap[$lc['language']] = (int) $lc['count'];
}

$pageTitle = 'My Snippets';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <!-- ================================================================== -->
    <!-- Sidebar - Filters                                                   -->
    <!-- ================================================================== -->
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="filter-sidebar">
            <!-- Quick Actions -->
            <div class="d-grid gap-2 mb-4">
                <a href="<?= APP_URL ?>/create.php" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-plus-circle"></i> New Snippet
                </a>
            </div>

            <!-- View Filters -->
            <div class="card bg-dark border-secondary mb-3">
                <div class="card-header border-secondary">
                    <h6 class="mb-0"><i class="bi bi-filter me-2"></i>Views</h6>
                </div>
                <div class="list-group list-group-flush bg-dark">
                    <a href="<?= APP_URL ?>/index.php" class="list-group-item list-group-item-action bg-dark text-light <?= !isset($_GET['view']) ? 'active' : '' ?>">
                        <i class="bi bi-code-square me-2"></i>My Snippets
                        <span class="badge bg-secondary float-end"><?= $result['total'] ?></span>
                    </a>
                    <a href="<?= APP_URL ?>/index.php?view=favorites" class="list-group-item list-group-item-action bg-dark text-light <?= ($_GET['view'] ?? '') === 'favorites' ? 'active' : '' ?>">
                        <i class="bi bi-heart me-2 text-danger"></i>Favorites
                    </a>
                    <a href="<?= APP_URL ?>/index.php?view=shared" class="list-group-item list-group-item-action bg-dark text-light <?= ($_GET['view'] ?? '') === 'shared' ? 'active' : '' ?>">
                        <i class="bi bi-share me-2 text-info"></i>Shared with me
                    </a>
                    <a href="<?= APP_URL ?>/index.php?view=public" class="list-group-item list-group-item-action bg-dark text-light <?= ($_GET['view'] ?? '') === 'public' ? 'active' : '' ?>">
                        <i class="bi bi-globe me-2 text-success"></i>Public Snippets
                    </a>
                </div>
            </div>

            <!-- Categories Filter -->
            <div class="card bg-dark border-secondary mb-3">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-folder me-2"></i>Categories</h6>
                    <a href="<?php echo APP_URL; ?>/categories.php" class="btn btn-sm btn-outline-secondary" title="Manage">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </div>
                <div class="list-group list-group-flush bg-dark">
                    <?php
                        // Filter: only show categories that have this user's snippets
                        $userCategories = array_filter($categories, function($cat) {
                            return (int) $cat['snippet_count'] > 0;
                        });
                    ?>
                    <?php if (empty($userCategories)): ?>
                        <div class="text-muted small p-3 text-center">
                            <i class="bi bi-folder me-1"></i>No categories used yet.
                            Assign a category when creating a snippet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($userCategories as $cat): ?>
                        <a href="<?php echo APP_URL; ?>/index.php?category=<?php echo $cat['id']; ?>" 
                           class="list-group-item list-group-item-action bg-dark text-light d-flex justify-content-between align-items-center <?php echo ($_GET['category'] ?? '') == $cat['id'] ? 'active' : ''; ?>">
                            <span>
                                <span class="language-dot me-2" style="background: <?php echo $cat['color']; ?>"></span>
                                <?php echo sanitize($cat['name']); ?>
                            </span>
                            <span class="badge bg-secondary rounded-pill"><?php echo $cat['snippet_count']; ?></span>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tags Cloud -->
            <div class="card bg-dark border-secondary mb-3">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-tags me-2"></i>Tags</h6>
                    <a href="<?php echo APP_URL; ?>/tags.php" class="btn btn-sm btn-outline-secondary" title="Manage Tags">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($tags)): ?>
                        <p class="text-muted small mb-0 text-center">
                            <i class="bi bi-tag me-1"></i>No tags created yet. 
                            Add tags when creating a snippet.
                        </p>
                    <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                        <?php
                            $isActive = ($_GET['tag'] ?? '') == $tag['id'];
                            $count = (int) $tag['usage_count'];
                            $opacity = $count > 0 ? '' : 'opacity-50';
                        ?>
                        <a href="<?php echo APP_URL; ?>/index.php?tag=<?php echo $tag['id']; ?>" 
                           class="tag-badge text-decoration-none <?php echo $isActive ? 'bg-primary text-white border-primary' : $opacity; ?>"
                           style="<?php echo !$isActive ? 'border-color: ' . $tag['color'] . '; color: ' . $tag['color'] : ''; ?>"
                           title="<?php echo $count; ?> snippet(s)">
                            #<?php echo sanitize($tag['name']); ?>
                            <small>(<?php echo $count; ?>)</small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Language Filter -->
            <div class="card bg-dark border-secondary">
                <div class="card-header border-secondary">
                    <h6 class="mb-0"><i class="bi bi-braces me-2"></i>Languages</h6>
                </div>
                <div class="list-group list-group-flush bg-dark">
                    <?php if (empty($langCountMap)): ?>
                        <div class="text-muted small p-3 text-center">
                            <i class="bi bi-braces me-1"></i>No snippets yet
                        </div>
                    <?php else: ?>
                        <?php foreach (SUPPORTED_LANGUAGES as $key => $label): ?>
                            <?php
                                $langCount = $langCountMap[$key] ?? 0;
                                if ($langCount === 0) continue; // Hide languages with 0 snippets
                                $isActive = ($_GET['language'] ?? '') === $key;
                                $langInfo = getLanguageInfo($key);
                            ?>
                            <a href="<?php echo APP_URL; ?>/index.php?language=<?php echo $key; ?>" 
                               class="list-group-item list-group-item-action bg-dark text-light py-2 d-flex justify-content-between align-items-center <?php echo $isActive ? 'active' : ''; ?>">
                                <span>
                                    <span class="language-dot me-2" style="background: <?php echo $langInfo['color']; ?>"></span>
                                    <?php echo $label; ?>
                                </span>
                                <span class="badge bg-secondary rounded-pill"><?php echo $langCount; ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- Main Content - Snippet Grid                                         -->
    <!-- ================================================================== -->
    <div class="col-lg-9 col-md-8">
        <!-- Sort & Search Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="mb-0 fw-bold">
                    <?php if (isset($_GET['view']) && $_GET['view'] === 'favorites'): ?>
                        <i class="bi bi-heart-fill text-danger me-2"></i>Favorite Snippets
                    <?php elseif (isset($_GET['view']) && $_GET['view'] === 'shared'): ?>
                        <i class="bi bi-share-fill text-info me-2"></i>Shared with Me
                    <?php elseif (!empty($_GET['search'])): ?>
                        <i class="bi bi-search me-2"></i>Search: "<?= sanitize($_GET['search']) ?>"
                    <?php else: ?>
                        <i class="bi bi-code-slash me-2"></i>My Snippets
                    <?php endif; ?>
                </h4>
                <small class="text-muted"><?= $result['total'] ?> snippet(s) found</small>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <!-- Search -->
                <form method="GET" class="input-group" style="max-width: 300px;">
                    <input type="text" name="search" class="form-control form-control-sm bg-dark border-secondary text-light" 
                           placeholder="Search..." value="<?= sanitize($_GET['search'] ?? '') ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <!-- Sort -->
                <select class="form-select form-select-sm bg-dark border-secondary text-light" style="width: auto;"
                        onchange="window.location.href=this.value">
                    <option value="<?= APP_URL ?>/index.php?sort=newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="<?= APP_URL ?>/index.php?sort=oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                    <option value="<?= APP_URL ?>/index.php?sort=title" <?= ($_GET['sort'] ?? '') === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                    <option value="<?= APP_URL ?>/index.php?sort=most_views" <?= ($_GET['sort'] ?? '') === 'most_views' ? 'selected' : '' ?>>Most Viewed</option>
                    <option value="<?= APP_URL ?>/index.php?sort=most_copies" <?= ($_GET['sort'] ?? '') === 'most_copies' ? 'selected' : '' ?>>Most Copied</option>
                    <option value="<?= APP_URL ?>/index.php?sort=updated" <?= ($_GET['sort'] ?? '') === 'updated' ? 'selected' : '' ?>>Recently Updated</option>
                </select>
            </div>
        </div>

        <!-- Snippet Cards Grid -->
        <?php if (empty($result['data'])): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No snippets found</h5>
            <p class="text-muted">Create your first snippet to get started!</p>
            <a href="<?= APP_URL ?>/create.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Create Snippet
            </a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($result['data'] as $snippet): ?>
            <div class="col-lg-6 col-xl-4" id="snippet-<?= $snippet['id'] ?>">
                <div class="snippet-card card h-100 fade-in">
                    <!-- Card Header -->
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 me-2">
                            <h6 class="mb-1">
                                <a href="<?= APP_URL ?>/view.php?id=<?= $snippet['id'] ?>" class="text-decoration-none text-light fw-bold">
                                    <?php if ($snippet['is_pinned']): ?>
                                        <i class="bi bi-pin-fill text-warning me-1" title="Pinned"></i>
                                    <?php endif; ?>
                                    <?= sanitize(truncate($snippet['title'], 40)) ?>
                                </a>
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="language-badge" style="background: <?= getLanguageInfo($snippet['language'])['color'] ?>20; color: <?= getLanguageInfo($snippet['language'])['color'] ?>">
                                    <span class="language-dot" style="background: <?= getLanguageInfo($snippet['language'])['color'] ?>"></span>
                                    <?= getLanguageInfo($snippet['language'])['label'] ?>
                                </span>
                                <?php if ($snippet['category_name']): ?>
                                <small class="text-muted">
                                    <span class="language-dot" style="background: <?= $snippet['category_color'] ?>"></span>
                                    <?= sanitize($snippet['category_name']) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Actions Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-dark" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/view.php?id=<?= $snippet['id'] ?>"><i class="bi bi-eye me-2"></i>View</a></li>
                                <li><a class="dropdown-item" href="<?= APP_URL ?>/edit.php?id=<?= $snippet['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                <li><button class="dropdown-item" data-share data-snippet-id="<?= $snippet['id'] ?>"><i class="bi bi-share me-2"></i>Share</button></li>
                                <li><button class="dropdown-item" data-pin data-snippet-id="<?= $snippet['id'] ?>"><i class="bi bi-pin me-2"></i>Pin/Unpin</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item text-danger" data-delete data-snippet-id="<?= $snippet['id'] ?>" data-title="<?= sanitize($snippet['title']) ?>"><i class="bi bi-trash me-2"></i>Delete</button></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Code Preview -->
                    <div class="card-body">
                        <?php if ($snippet['description']): ?>
                        <p class="small text-muted px-3 pt-3 mb-0"><?= sanitize(truncate($snippet['description'], 100)) ?></p>
                        <?php endif; ?>
                        <div class="code-preview">
                            <pre><code class="language-<?= $snippet['language'] ?>" id="code-<?= $snippet['id'] ?>"><?= sanitize(truncate($snippet['code'], 300)) ?></code></pre>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-3">
                            <!-- Copy Button -->
                            <button class="btn btn-sm btn-outline-success border-0" data-copy data-snippet-id="<?= $snippet['id'] ?>" title="Copy code">
                                <i class="bi bi-clipboard"></i>
                            </button>
                            <!-- Favorite Button -->
                            <button class="btn btn-sm btn-outline-danger border-0" data-favorite data-snippet-id="<?= $snippet['id'] ?>" title="Toggle favorite">
                                <i class="bi <?= in_array($snippet['id'], $favoriteIds) ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-3 text-muted small">
                            <span title="Views"><i class="bi bi-eye me-1"></i><?= formatNumber($snippet['views_count']) ?></span>
                            <span title="Copies"><i class="bi bi-clipboard me-1"></i><?= formatNumber($snippet['copies_count']) ?></span>
                            <span title="Created"><?= timeAgo($snippet['created_at']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            <?= renderPagination($result['page'], $result['total_pages'], APP_URL . '/index.php') ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ====================================================================== -->
<!-- Share Modal                                                             -->
<!-- ====================================================================== -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-share me-2"></i>Share Snippet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="shareForm">
                <div class="modal-body">
                    <input type="hidden" name="snippet_id" value="">
                    <input type="hidden" name="shared_with" id="selectedUserId" value="">
                    
                    <!-- User Search -->
                    <div class="mb-3">
                        <label class="form-label">Search User</label>
                        <input type="text" class="form-control bg-dark border-secondary text-light" 
                               id="shareUserSearch" placeholder="Type username or email...">
                        <div id="userSearchResults" class="mt-2"></div>
                        <div id="selectedUserName" class="d-none mt-2 alert alert-info py-2">
                            <i class="bi bi-person-check me-1"></i> Selected user
                        </div>
                    </div>

                    <!-- Permission -->
                    <div class="mb-3">
                        <label class="form-label">Permission</label>
                        <select name="permission" class="form-select bg-dark border-secondary text-light">
                            <option value="view">View Only</option>
                            <option value="edit">Can Edit</option>
                        </select>
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label class="form-label">Message (optional)</label>
                        <textarea name="message" class="form-control bg-dark border-secondary text-light" rows="2" 
                                  placeholder="Add a note..."></textarea>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
