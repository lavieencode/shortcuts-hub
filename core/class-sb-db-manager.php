<?php
/**
 * Switchblade Database Connection Manager
 * 
 * Manages database connections to the Switchblade server using a singleton pattern
 * to prevent exceeding max_connections limit.
 */

class SB_DB_Manager {
    /**
     * The single instance of this class
     */
    private static $instance = null;

    /**
     * PDO connection instance
     */
    private $connection = null;

    /**
     * Last time the connection was used
     */
    private $last_used = null;

    /**
     * Connection timeout in seconds (5 minutes)
     */
    const CONNECTION_TIMEOUT = 300;

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct() {}

    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a database connection, creating it if necessary
     */
    public function get_connection() {
        try {
            // If connection exists, check if it's still valid
            if ($this->connection !== null) {
                // If connection hasn't been used for a while, close it
                if ($this->last_used && (time() - $this->last_used > self::CONNECTION_TIMEOUT)) {
                    $this->close_connection();
                } elseif ($this->ping()) {
                    $this->last_used = time();
                    return $this->connection;
                }
            }

            // Create new connection
            $host = get_option('remote_db_host');
            $dbname = get_option('remote_db_name');
            $username = get_option('remote_db_user');
            $password = get_option('remote_db_password');

            // DEBUG: Log connection attempt
            sh_debug_log('Database Connection Attempt', array(
                'message' => 'Attempting to establish new database connection',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'host' => $host,
                    'database' => $dbname,
                    'username' => $username
                ),
                'debug' => true
            ));

            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                )
            );

            $this->last_used = time();
            $this->log_connection_status('connected');
            return $this->connection;

        } catch (PDOException $e) {
            // DEBUG: Log connection error
            sh_debug_log('Database Connection Error', array(
                'message' => 'Failed to establish database connection',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ),
                'debug' => true
            ));
            throw $e;
        }
    }

    /**
     * Check if the connection is still alive
     */
    private function ping() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Close the database connection
     */
    public function close_connection() {
        if ($this->connection !== null) {
            // DEBUG: Log connection closure
            sh_debug_log('Database Connection Closure', array(
                'message' => 'Closing database connection',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'connection_age' => time() - $this->last_used
                ),
                'debug' => true
            ));

            $this->connection = null;
            $this->last_used = null;
            $this->log_connection_status('disconnected');
        }
    }

    /**
     * Execute a query with proper connection management
     */
    public function execute_query($callback) {
        try {
            $connection = $this->get_connection();
            $result = $callback($connection);
            return $result;
        } catch (PDOException $e) {
            // If we get a connection error, try to reconnect once
            if ($this->is_connection_error($e->getCode())) {
                $this->close_connection();
                $connection = $this->get_connection();
                return $callback($connection);
            }
            throw $e;
        }
    }

    /**
     * Check if the error code is related to connection issues
     */
    private function is_connection_error($code) {
        $connection_error_codes = array(
            2006, // MySQL server has gone away
            2013, // Lost connection to MySQL server during query
            2055  // Lost connection to MySQL server
        );
        return in_array($code, $connection_error_codes);
    }

    /**
     * Get connection status for monitoring
     */
    public function get_connection_status() {
        return array(
            'connected' => ($this->connection !== null),
            'last_used' => $this->last_used,
            'idle_time' => $this->last_used ? (time() - $this->last_used) : null
        );
    }

    /**
     * Log connection status
     */
    private function log_connection_status($event) {
        sh_debug_log('Database Connection Status', array(
            'message' => 'Connection status check',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'event' => $event,
                'status' => $this->get_connection_status()
            ),
            'debug' => true
        ));
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    private function __wakeup() {}
}
