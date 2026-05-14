<?php
/**
 * ============================================================================
 * Session Management Class
 * ============================================================================
 * 
 * Handles secure session management with CSRF token generation,
 * flash messages, and session security hardening.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Session
{
    /**
     * Start a secure session with hardened configuration
     * 
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session security settings
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');

            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'domain'   => '',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly'  => true,
                'samesite'  => 'Lax',
            ]);

            session_start();

            // Track session creation time
            if (!isset($_SESSION['_created'])) {
                $_SESSION['_created'] = time();
            }

            // Regenerate session ID periodically (every 30 min) to prevent fixation
            // Use false to keep old session file until garbage collection
            if (isset($_SESSION['_last_regenerate'])) {
                if (time() - $_SESSION['_last_regenerate'] > 1800) {
                    session_regenerate_id(false);
                    $_SESSION['_last_regenerate'] = time();
                }
            } else {
                $_SESSION['_last_regenerate'] = time();
            }
        }
    }

    /**
     * Set a session value
     * 
     * @param string $key   Session key
     * @param mixed  $value Session value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     * 
     * @param string $key     Session key
     * @param mixed  $default Default value if key not found
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists
     * 
     * @param string $key Session key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session key
     * 
     * @param string $key Session key
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the entire session
     * 
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Get the current CSRF token, or generate one if it doesn't exist.
     * 
     * IMPORTANT: This method reuses the same token for the entire request
     * lifecycle. It does NOT generate a new token on every call.
     * This prevents the bug where form token and JS token are different.
     * 
     * @return string CSRF token
     */
    public static function getCsrfToken(): string
    {
        if (!self::has('csrf_token') || empty(self::get('csrf_token'))) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a CSRF token against the stored session token
     * 
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function validateCsrfToken(string $token): bool
    {
        $storedToken = self::get('csrf_token', '');
        
        // Empty tokens should never validate
        if (empty($token) || empty($storedToken)) {
            return false;
        }
        
        return hash_equals($storedToken, $token);
    }

    /**
     * Regenerate the CSRF token (call after successful form submission if needed)
     * 
     * @return string New CSRF token
     */
    public static function regenerateCsrfToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Set a flash message (available only for the next request)
     * 
     * @param string $type    Message type (success, error, warning, info)
     * @param string $message The message text
     * @return void
     */
    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * Get and clear flash messages
     * 
     * @param string|null $type Specific type to retrieve, or null for all
     * @return array Flash messages
     */
    public static function getFlash(?string $type = null): array
    {
        if ($type !== null) {
            $messages = $_SESSION['_flash'][$type] ?? [];
            unset($_SESSION['_flash'][$type]);
            return $messages;
        }

        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /**
     * Check if flash messages exist
     * 
     * @param string|null $type Specific type to check
     * @return bool
     */
    public static function hasFlash(?string $type = null): bool
    {
        if ($type !== null) {
            return !empty($_SESSION['_flash'][$type]);
        }
        return !empty($_SESSION['_flash']);
    }
}
