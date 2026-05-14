<?php
/**
 * ============================================================================
 * Footer Template
 * ============================================================================
 * 
 * Common footer included on all pages. Contains scripts and
 * closing HTML tags. Global JS config is set BEFORE app.js loads.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */
?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark border-top border-secondary py-3 mt-5">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0 small">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted small text-decoration-none me-3">Documentation</a>
                    <a href="#" class="text-muted small text-decoration-none">GitHub</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
    
    <!-- IMPORTANT: Global config MUST be set BEFORE app.js loads -->
    <script>
        window.SnippetConfig = {
            appUrl: '<?php echo rtrim(APP_URL, "/"); ?>',
            csrfToken: '<?php echo Session::getCsrfToken(); ?>',
            ajaxUrl: '<?php echo rtrim(APP_URL, "/"); ?>/ajax/handler.php'
        };
    </script>
    
    <!-- Custom JavaScript (loads AFTER config is set) -->
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
