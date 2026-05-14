<?php
/**
 * ============================================================================
 * Authentication Class
 * ============================================================================
 * 
 * Handles user authentication including login, logout, registration,
 * and session-based auth state management.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

class Auth
{
    /** @var PDO Database connection */
    private PDO $db;

    /**
     * Constructor - initialize with database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Attempt to log in a user with credentials
     * 
     * @param string $username Username or email
     * @param string $password Plain text password
     * @return array Result with status and message
     */
    public function login(string $username, string $password): array
    {
        try {
            // Find user by username or email
            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE (username = :login_user OR email = :login_email) AND is_active = 1 LIMIT 1"
            );
            $trimmed = trim($username);
            $stmt->execute(['login_user' => $trimmed, 'login_email' => $trimmed]);
            $user = $stmt->fetch();

            // Verify user exists and password matches
            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // ================================================================
            // IMPORTANT: Regenerate session ID SAFELY
            // Using false (don't delete old session) to avoid race conditions
            // on XAMPP/Windows where file locking can lose session data
            // ================================================================
            session_regenerate_id(false);

            // Clear any previous session data and set fresh auth data
            $_SESSION = [];
            $_SESSION['_created'] = time();
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['logged_in'] = true;

            // Force session write before redirect
            session_write_close();
            session_start();

            // Update last login timestamp
            $updateStmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            // Log the activity
            ActivityLog::log($user['id'], 'login', 'user', $user['id']);

            return ['success' => true, 'message' => 'Login successful!', 'user' => $user];

        } catch (PDOException $e) {
            error_log('[Auth] Login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Register a new user account
     * 
     * @param array $data Registration data (username, email, password, full_name)
     * @return array Result with status and message
     */
    public function register(array $data): array
    {
        $errors = [];

        // Validate username
        $username = trim($data['username'] ?? '');
        if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }

        // Validate email
        $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $errors[] = 'Please provide a valid email address.';
        }

        // Validate full name
        $fullName = trim($data['full_name'] ?? '');
        if (empty($fullName) || strlen($fullName) < 2) {
            $errors[] = 'Full name is required (minimum 2 characters).';
        }

        // Validate password
        $password = $data['password'] ?? '';
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== ($data['confirm_password'] ?? '')) {
            $errors[] = 'Passwords do not match.';
        }

        // Check for duplicate username/email
        if (empty($errors)) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            // Hash password with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$username, $email, $hashedPassword, $fullName]);

            $userId = (int) $this->db->lastInsertId();

            // Log the registration
            ActivityLog::log($userId, 'register', 'user', $userId);

            return ['success' => true, 'message' => 'Registration successful! You can now log in.', 'user_id' => $userId];

        } catch (PDOException $e) {
            error_log('[Auth] Registration error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }

    /**
     * Log out the current user
     * 
     * @return void
     */
    public function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            ActivityLog::log($userId, 'logout', 'user', $userId);
        }
        Session::destroy();
    }

    /**
     * Check if a user is currently logged in
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return Session::get('logged_in', false) === true && Session::has('user_id');
    }

    /**
     * Get the currently logged-in user's ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public static function userId(): ?int
    {
        return Session::has('user_id') ? (int) Session::get('user_id') : null;
    }

    /**
     * Check if the current user is an admin
     * 
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return Session::get('user_role') === 'admin';
    }

    /**
     * Require authentication - redirect to login if not logged in
     * 
     * @return void
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            Session::setFlash('error', 'Please log in to access this page.');
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
     * Update user password
     * 
     * @param int    $userId      User ID
     * @param string $currentPass Current password
     * @param string $newPass     New password
     * @return array Result
     */
    public function changePassword(int $userId, string $currentPass, string $newPass): array
    {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPass, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        if (strlen($newPass) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
        }

        $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $userId]);

        return ['success' => true, 'message' => 'Password updated successfully!'];
    }
}
