<?php
/**
 * ============================================================================
 * Registration Page
 * ============================================================================
 * 
 * User registration with validation and secure password hashing.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';

if (Auth::isLoggedIn()) {
    redirect(APP_URL . '/index.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $old = $_POST;
        $auth = new Auth();
        $result = $auth->register($_POST);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
            redirect(APP_URL . '/login.php');
        } else {
            $errors = $result['errors'] ?? ['Registration failed.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card card bg-dark" style="max-width: 500px;">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                    <i class="bi bi-code-slash fs-1 text-primary"></i>
                </div>
                <h3 class="fw-bold mb-1">Create Account</h3>
                <p class="text-muted mb-0">Join <?= APP_NAME ?> today</p>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?= sanitize($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrfField() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person me-1"></i>Username</label>
                            <input type="text" name="username" class="form-control bg-dark border-secondary text-light"
                                   required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+"
                                   placeholder="johndoe" value="<?= sanitize($old['username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person-badge me-1"></i>Full Name</label>
                            <input type="text" name="full_name" class="form-control bg-dark border-secondary text-light"
                                   required minlength="2" placeholder="John Doe" value="<?= sanitize($old['full_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
                        <input type="email" name="email" class="form-control bg-dark border-secondary text-light"
                               required placeholder="john@example.com" value="<?= sanitize($old['email'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                            <input type="password" name="password" class="form-control bg-dark border-secondary text-light"
                                   required minlength="8" placeholder="Min. 8 characters">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill me-1"></i>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control bg-dark border-secondary text-light"
                                   required placeholder="Repeat password">
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer text-center py-3 border-secondary">
                <p class="mb-0 text-muted">
                    Already have an account?
                    <a href="<?= APP_URL ?>/login.php" class="text-primary text-decoration-none fw-semibold">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
