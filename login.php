<?php
/**
 * ============================================================================
 * Login Page
 * ============================================================================
 * 
 * Handles user authentication with secure login form.
 * Includes CSRF protection and input validation.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

require_once __DIR__ . '/config/app.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    redirect(APP_URL . '/index.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $auth = new Auth();
        $result = $auth->login(
            $_POST['username'] ?? '',
            $_POST['password'] ?? ''
        );

        if ($result['success']) {
            // Build redirect URL using same detection as APP_URL
            // but ensure it points to index.php in the same directory
            $redirectUrl = dirname($_SERVER['SCRIPT_NAME']) . '/index.php';
            $redirectUrl = str_replace('//', '/', $redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card card bg-dark">
            <!-- Header -->
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                    <i class="bi bi-code-slash fs-1 text-primary"></i>
                </div>
                <h3 class="fw-bold mb-1"><?= APP_NAME ?></h3>
                <p class="text-muted mb-0">Sign in to your account</p>
            </div>

            <!-- Login Form -->
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= sanitize($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person me-1"></i>Username or Email
                        </label>
                        <input type="text" class="form-control bg-dark border-secondary text-light" 
                               id="username" name="username" required autofocus
                               placeholder="Enter your username or email"
                               value="<?= sanitize($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control bg-dark border-secondary text-light" 
                                   id="password" name="password" required
                                   placeholder="Enter your password">
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="card-footer text-center py-3 border-secondary">
                <p class="mb-0 text-muted">
                    Don't have an account? 
                    <a href="<?= APP_URL ?>/register.php" class="text-primary text-decoration-none fw-semibold">
                        Create one
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>
