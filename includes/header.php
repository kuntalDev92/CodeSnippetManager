<?php
/**
 * ============================================================================
 * Header Template
 * ============================================================================
 * 
 * Fully responsive navbar that works on all devices.
 * Layout: Brand | Search (desktop) | Actions + User Menu
 * Mobile: Brand + Hamburger → stacked menu
 * 
 * @package  CodeSnippetManager
 * @version  1.0.0
 */

$unreadCount = 0;
if (Auth::isLoggedIn()) {
    $shareModel = new Share();
    $unreadCount = $shareModel->getUnreadCount(Auth::userId());
}

$userInitial = 'U';
$userFullName = 'User';
if (Session::has('full_name')) {
    $userFullName = Session::get('full_name');
    $userInitial = strtoupper(substr($userFullName, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Code Snippet Manager - Store, organize, and share your code snippets">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php if (Auth::isLoggedIn()): ?>

    <!-- ================================================================== -->
    <!-- Top Navbar                                                          -->
    <!-- ================================================================== -->
    <nav class="navbar navbar-dark bg-dark border-bottom border-secondary fixed-top">
        <div class="container-fluid px-3">

            <!-- Row 1: Brand + Mobile icons + Hamburger -->
            <div class="d-flex align-items-center justify-content-between w-100">

                <!-- Left: Brand -->
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold me-3 flex-shrink-0" href="<?php echo APP_URL; ?>/index.php">
                    <i class="bi bi-code-slash fs-5 text-primary"></i>
                    <span class="d-none d-sm-inline" style="font-size:0.95rem;"><?php echo APP_NAME; ?></span>
                    <span class="d-inline d-sm-none" style="font-size:0.9rem;">Snippets</span>
                </a>

                <!-- Center: Search (hidden on mobile, shown on md+) -->
                <div class="flex-grow-1 d-none d-md-block mx-3" style="max-width:480px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark border-secondary border-end-0">
                            <i class="bi bi-search text-muted" style="font-size:0.8rem;"></i>
                        </span>
                        <input type="text" class="form-control bg-dark border-secondary text-light border-start-0"
                               id="globalSearch" placeholder="Search... (Ctrl+K)" autocomplete="off"
                               style="font-size:0.85rem;">
                        <div id="searchResults" class="search-dropdown d-none"></div>
                    </div>
                </div>

                <!-- Right: Action icons + User dropdown -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">

                    <!-- New Snippet (icon on mobile, button on desktop) -->
                    <a href="<?php echo APP_URL; ?>/create.php" class="btn btn-primary btn-sm d-flex align-items-center gap-1" title="New Snippet">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-lg-inline">New Snippet</span>
                    </a>

                    <!-- Notifications -->
                    <a href="<?php echo APP_URL; ?>/shared.php" class="btn btn-dark btn-sm position-relative" title="Shared with me">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                            <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-dark btn-sm dropdown-toggle d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-xs bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:26px;height:26px;">
                                <span class="text-white fw-bold" style="font-size:0.65rem;"><?php echo htmlspecialchars($userInitial); ?></span>
                            </div>
                            <span class="d-none d-lg-inline" style="font-size:0.85rem;"><?php echo htmlspecialchars($userFullName); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <li class="dropdown-header small text-muted px-3 pb-1">
                                <strong><?php echo htmlspecialchars($userFullName); ?></strong><br>
                                <small>@<?php echo htmlspecialchars(Session::get('username', '')); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <?php if (Auth::isAdmin()): ?>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel <span class="admin-badge ms-1">ADMIN</span></a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/favorites.php"><i class="bi bi-heart me-2"></i>Favorites</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/categories.php"><i class="bi bi-folder me-2"></i>Categories</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/tags.php"><i class="bi bi-tags me-2"></i>Tags</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>

                    <!-- Mobile search toggle -->
                    <button class="btn btn-dark btn-sm d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSearch" aria-label="Toggle search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <!-- Row 2: Mobile search (collapsible, only on small screens) -->
            <div class="collapse w-100 mt-2 d-md-none" id="mobileSearch">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark border-secondary">
                        <i class="bi bi-search text-muted" style="font-size:0.8rem;"></i>
                    </span>
                    <input type="text" class="form-control bg-dark border-secondary text-light"
                           id="globalSearchMobile" placeholder="Search snippets..."
                           autocomplete="off" style="font-size:0.85rem;">
                    <div id="searchResultsMobile" class="search-dropdown d-none"></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Spacer for fixed navbar -->
    <div style="height: 56px;"></div>
    <?php endif; ?>

    <!-- Flash Messages -->
    <div class="container-fluid px-3 mt-2" id="flashMessages">
        <?php echo renderFlashMessages(); ?>
    </div>

    <!-- Main Content -->
    <main class="container-fluid px-3 py-2">
