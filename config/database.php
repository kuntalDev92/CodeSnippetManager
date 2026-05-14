<?php
/**
 * ============================================================================
 * Database Configuration
 * ============================================================================
 * 
 * Central database configuration file using singleton pattern.
 * Provides a single PDO connection instance throughout the application.
 * 
 * @package  CodeSnippetManager
 * @author   PHP Developer
 * @version  1.0.0
 */

// Prevent direct access
defined('BASE_PATH') || exit('Direct access not allowed');

/**
 * Class Database
 * 
 * Implements Singleton pattern for database connection management.
 * Ensures only one connection instance exists per request lifecycle.
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static ?Database $instance = null;

    /** @var PDO Active database connection */
    private PDO $connection;

    /** @var array Database configuration parameters */
    private array $config = [
        'host'     => 'localhost',      // Database host
        'port'     => 3306,             // Database port
        'dbname'   => 'snippet_manager', // Database name
        'username' => 'root',           // Database username
        'password' => '',               // Database password
        'charset'  => 'utf8mb4',        // Character set
    ];

    /**
     * Private constructor - prevents direct instantiation
     * Establishes PDO connection with optimized settings
     * 
     * @throws RuntimeException If connection fails
     */
    private function __construct()
    {
        try {
            // Build DSN string
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset']
            );

            // Calculate MySQL timezone offset from PHP timezone
            $now = new DateTime('now', new DateTimeZone(APP_TIMEZONE));
            $offset = $now->format('P'); // e.g., "+05:30" for Asia/Kolkata

            // PDO connection options for optimal performance and security
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Return associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                     // Use native prepared statements
                PDO::ATTR_STRINGIFY_FETCHES  => false,                     // Keep native data types
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,                      // Return found rows count
                // Sync MySQL session timezone with PHP timezone
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '{$offset}'",
            ];

            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);

        } catch (PDOException $e) {
            // Log the error and throw a generic exception (don't expose DB details)
            error_log('[Database] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Please check configuration.');
        }
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Get the singleton database instance
     * 
     * @return self Database instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection object
     * 
     * @return PDO Active PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Begin a database transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback the current transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
}
