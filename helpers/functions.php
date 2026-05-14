<?php
/**
 * ============================================================================
 * Helper Functions
 * ============================================================================
 * 
 * Collection of utility functions used throughout the application.
 * Includes sanitization, formatting, and common operations.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $input Raw input string
 * @return string Sanitized string
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 * 
 * @param string $url Target URL
 * @return void
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Send a JSON response (for AJAX requests)
 * 
 * @param array $data    Response data
 * @param int   $status  HTTP status code
 * @return void
 */
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Check if the current request is an AJAX request
 * 
 * @return bool
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get the current page number from query string
 * 
 * @return int Page number (minimum 1)
 */
function getCurrentPage(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}

/**
 * Generate a CSRF token input field for forms
 * 
 * Uses getCsrfToken() which returns the SAME token for the entire
 * request. This ensures form hidden field and JS config get the
 * same token value.
 * 
 * @return string HTML hidden input with CSRF token
 */
function csrfField(): string
{
    $token = Session::getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify the CSRF token from a form submission
 * Checks POST body first, then X-CSRF-TOKEN header
 * 
 * @return bool True if valid
 */
function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return Session::validateCsrfToken($token);
}

/**
 * Format a datetime string to a human-readable relative time
 * 
 * @param string $datetime MySQL datetime string
 * @return string Relative time (e.g., "2 hours ago")
 */
function timeAgo(string $datetime): string
{
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Format a number with abbreviation (e.g., 1.2K, 3.5M)
 * 
 * @param int $number Number to format
 * @return string Formatted number
 */
function formatNumber(int $number): string
{
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    }
    if ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return (string) $number;
}

/**
 * Truncate text to a maximum length with ellipsis
 * 
 * @param string $text   Text to truncate
 * @param int    $length Maximum length
 * @return string Truncated text
 */
function truncate(string $text, int $length = 100): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Get the language icon/color mapping
 * 
 * @param string $language Language identifier
 * @return array Color and label
 */
function getLanguageInfo(string $language): array
{
    $languages = [
        'php'        => ['color' => '#777BB4', 'label' => 'PHP'],
        'javascript' => ['color' => '#F7DF1E', 'label' => 'JavaScript'],
        'html'       => ['color' => '#E34F26', 'label' => 'HTML'],
        'css'        => ['color' => '#1572B6', 'label' => 'CSS'],
        'sql'        => ['color' => '#4479A1', 'label' => 'SQL'],
        'python'     => ['color' => '#3776AB', 'label' => 'Python'],
        'bash'       => ['color' => '#4EAA25', 'label' => 'Bash'],
        'json'       => ['color' => '#000000', 'label' => 'JSON'],
        'xml'        => ['color' => '#FF6600', 'label' => 'XML'],
        'typescript' => ['color' => '#3178C6', 'label' => 'TypeScript'],
    ];

    return $languages[$language] ?? ['color' => '#6B7280', 'label' => ucfirst($language)];
}

/**
 * Generate pagination HTML using Bootstrap 5
 * 
 * @param int    $currentPage Current page number
 * @param int    $totalPages  Total number of pages
 * @param string $baseUrl     Base URL for pagination links
 * @return string HTML pagination
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous button
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= "<li class='page-item {$prevDisabled}'><a class='page-link' href='{$baseUrl}?page={$prevPage}'>&laquo;</a></li>";

    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page=1'>1</a></li>";
        if ($start > 2) $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}?page={$i}'>{$i}</a></li>";
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$totalPages}'>{$totalPages}</a></li>";
    }

    // Next button
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= "<li class='page-item {$nextDisabled}'><a class='page-link' href='{$baseUrl}?page={$nextPage}'>&raquo;</a></li>";

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Get the flash message alert HTML
 * 
 * @return string HTML alerts
 */
function renderFlashMessages(): string
{
    $html = '';
    $types = ['success', 'error', 'warning', 'info'];
    $bsTypes = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];

    foreach ($types as $type) {
        $messages = Session::getFlash($type);
        foreach ($messages as $message) {
            $bsType = $bsTypes[$type];
            $html .= "<div class='alert alert-{$bsType} alert-dismissible fade show' role='alert'>
                        {$message}
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                       </div>";
        }
    }

    return $html;
}
