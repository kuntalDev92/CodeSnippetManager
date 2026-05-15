<?php
/**
 * ============================================================================
 * Password Hash Generator
 * ============================================================================
 * 
 * Run this file ONCE to generate bcrypt hashes for the default users.
 * Then copy the output SQL and run it in phpMyAdmin.
 * 
 * ⚠️ DELETE THIS FILE AFTER USE!
 * 
 * Usage: Open in browser → http://localhost/snippet-manager/generate_hash.php
 * 
 * @package  CodeSnippetManager
 * @version  1.0.0
 */

$adminPass = 'admin123';
$demoPass  = 'demo123';

$adminHash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
$demoHash  = password_hash($demoPass, PASSWORD_BCRYPT, ['cost' => 12]);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background: #0f0f23; min-height: 100vh; }</style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card bg-dark border-secondary" style="max-width:800px; width:100%; border-radius:12px;">
        <div class="card-header text-center border-secondary" style="background: rgba(99,102,241,0.1); padding:1.5rem;">
            <h3 class="fw-bold mb-1">🔐 Password Hash Generator</h3>
            <p class="text-muted mb-0 small">Copy the SQL below and run it in phpMyAdmin</p>
        </div>
        <div class="card-body p-4">
            <h6 class="fw-bold mb-2">Generated Hashes:</h6>
            <table class="table table-dark table-sm table-bordered mb-4">
                <thead><tr><th>User</th><th>Password</th><th>Bcrypt Hash</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code>admin</code></td>
                        <td><code><?php echo $adminPass; ?></code></td>
                        <td><code style="font-size:0.7rem; word-break:break-all;"><?php echo $adminHash; ?></code></td>
                    </tr>
                    <tr>
                        <td><code>demo</code></td>
                        <td><code><?php echo $demoPass; ?></code></td>
                        <td><code style="font-size:0.7rem; word-break:break-all;"><?php echo $demoHash; ?></code></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="fw-bold mb-2">Ready-to-run SQL:</h6>
            <div class="bg-black rounded p-3 mb-3" style="border:1px solid rgba(99,102,241,0.2);">
                <pre class="text-success mb-0" style="white-space:pre-wrap; font-size:0.8rem;" id="sqlOutput">INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@snippetmanager.com', '<?php echo $adminHash; ?>', 'Administrator', 'admin'),
('demo', 'demo@snippetmanager.com', '<?php echo $demoHash; ?>', 'Demo User', 'member');</pre>
            </div>
            <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('sqlOutput').textContent); this.innerHTML='<i class=\'bi bi-check-lg me-1\'></i>Copied!'; this.classList.add('btn-success'); this.classList.remove('btn-primary');">
                <i class="bi bi-clipboard me-1"></i>Copy SQL
            </button>

            <div class="alert alert-danger mt-4 py-2 small mb-0">
                <strong>⚠️ DELETE this file after copying the SQL!</strong> Never leave it on a live server.
            </div>
        </div>
    </div>
</body>
</html>
