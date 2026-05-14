<?php
/**
 * ============================================================================
 * Dashboard Page
 * ============================================================================
 * 
 * Overview dashboard with statistics, recent snippets,
 * language distribution, and activity timeline.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';
Auth::requireLogin();

$userModel = new User();
$snippetModel = new Snippet();
$categoryModel = new Category();

$stats = $userModel->getStats(Auth::userId());
$recentSnippets = $snippetModel->getRecent(Auth::userId(), 5);
$languages = $snippetModel->getLanguageDistribution(Auth::userId());
$categories = $categoryModel->getAll(Auth::userId());
$activities = ActivityLog::getRecent(Auth::userId(), 10);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card bg-dark border-secondary mb-4" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1)) !important;">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-2">Welcome back, <?= sanitize(Session::get('full_name', 'Developer')) ?>! 👋</h2>
                <p class="text-muted mb-0">Here's an overview of your code snippet library.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= APP_URL ?>/create.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>New Snippet
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value text-primary"><?= formatNumber($stats['snippets']) ?></div>
                    <div class="stat-label mt-1">Total Snippets</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-code-slash"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value text-danger"><?= formatNumber($stats['favorites']) ?></div>
                    <div class="stat-label mt-1">Favorites</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-heart-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value text-info"><?= formatNumber($stats['shares']) ?></div>
                    <div class="stat-label mt-1">Shared</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-share-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value text-success"><?= formatNumber($stats['views']) ?></div>
                    <div class="stat-label mt-1">Total Views</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-eye-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Snippets -->
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Snippets</h5>
                <a href="<?= APP_URL ?>/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentSnippets)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    No snippets yet. Create your first one!
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Language</th>
                                <th>Category</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSnippets as $s): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/view.php?id=<?= $s['id'] ?>" class="text-decoration-none text-light fw-semibold">
                                        <?= sanitize(truncate($s['title'], 40)) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="language-badge" style="background: <?= getLanguageInfo($s['language'])['color'] ?>20; color: <?= getLanguageInfo($s['language'])['color'] ?>">
                                        <?= getLanguageInfo($s['language'])['label'] ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= sanitize($s['category_name'] ?? '—') ?></td>
                                <td class="text-muted small"><?= timeAgo($s['updated_at']) ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Language Distribution -->
        <?php if (!empty($languages)): ?>
        <div class="card bg-dark border-secondary mt-4">
            <div class="card-header border-secondary">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Language Distribution</h5>
            </div>
            <div class="card-body">
                <?php 
                $totalSnippets = array_sum(array_column($languages, 'count'));
                foreach ($languages as $lang): 
                    $percent = round(($lang['count'] / $totalSnippets) * 100, 1);
                    $info = getLanguageInfo($lang['language']);
                ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3" style="width: 100px;">
                        <span class="language-dot me-1" style="background: <?= $info['color'] ?>"></span>
                        <small class="fw-semibold"><?= $info['label'] ?></small>
                    </div>
                    <div class="flex-grow-1">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: <?= $percent ?>%; background: <?= $info['color'] ?>"></div>
                        </div>
                    </div>
                    <div class="ms-3 text-muted small" style="width: 70px; text-align: right;">
                        <?= $lang['count'] ?> (<?= $percent ?>%)
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Activity Timeline -->
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (empty($activities)): ?>
                <p class="text-muted text-center">No activity yet.</p>
                <?php else: ?>
                <div class="activity-timeline">
                    <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <small class="fw-semibold">
                            <?= ActivityLog::getActionText($activity['action'], $activity['entity_type']) ?>
                        </small>
                        <br>
                        <small class="text-muted"><?= timeAgo($activity['created_at']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Categories -->
        <div class="card bg-dark border-secondary mt-4">
            <div class="card-header border-secondary">
                <h5 class="mb-0"><i class="bi bi-folder me-2"></i>Categories</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                <a href="<?= APP_URL ?>/index.php?category=<?= $cat['id'] ?>" 
                   class="list-group-item list-group-item-action bg-dark text-light border-secondary d-flex justify-content-between align-items-center">
                    <span>
                        <span class="language-dot me-2" style="background: <?= $cat['color'] ?>"></span>
                        <?= sanitize($cat['name']) ?>
                    </span>
                    <span class="badge bg-secondary"><?= $cat['snippet_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
