<?php
/**
 * Rate limiting helper functions
 * Tracks failed attempts by IP address and endpoint to prevent brute force attacks
 */

require_once __DIR__ . "/logger.php";

/**
 * Initialize the rate limiting table if it doesn't exist
 */
function init_rate_limit_table() {
    global $conn;

    $create_table_sql = "CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        endpoint VARCHAR(50) NOT NULL,
        attempt_count INT DEFAULT 0,
        window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        locked_until TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_ip_endpoint (ip_address, endpoint),
        INDEX idx_window_start (window_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    try {
        $conn->query($create_table_sql);

        $column_check = $conn->query("SHOW COLUMNS FROM rate_limits LIKE 'locked_until'");
        if ($column_check && $column_check->num_rows === 0) {
            $conn->query("ALTER TABLE rate_limits ADD COLUMN locked_until TIMESTAMP NULL DEFAULT NULL AFTER last_attempt");
        }
        if ($column_check) {
            $column_check->close();
        }
    } catch (mysqli_sql_exception $e) {
        log_error("Failed to create rate_limits table: " . $e->getMessage(), "init_rate_limit_table");
    }
}

/**
 * Check if an IP address is rate limited for a specific endpoint
 *
 * @param string $ip_address The IP address to check
 * @param string $endpoint The endpoint (login, register, forgot_password)
 * @param int $max_attempts Maximum attempts allowed in the time window
 * @param int $window_seconds Time window in seconds
 * @param int|null $lockout_seconds Optional lockout duration for endpoints with explicit lockouts
 * @return bool True if rate limited (should block), False if not rate limited
 */
function is_rate_limited($ip_address, $endpoint, $max_attempts = 5, $window_seconds = 300, $lockout_seconds = null) {
    global $conn;

    init_rate_limit_table();

    $cleanup_sql = "DELETE FROM rate_limits
                    WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)
                    AND (locked_until IS NULL OR locked_until <= NOW())";
    $stmt = $conn->prepare($cleanup_sql);
    $window_cleanup = max($window_seconds, (int) ($lockout_seconds ?? 0)) * 2;
    $stmt->bind_param("i", $window_cleanup);
    $stmt->execute();
    $stmt->close();

    if ($lockout_seconds !== null) {
        $select_sql = "SELECT id FROM rate_limits
                       WHERE ip_address = ? AND endpoint = ?
                       AND locked_until > CURRENT_TIMESTAMP
                       ORDER BY locked_until DESC LIMIT 1";
        $stmt = $conn->prepare($select_sql);
        $stmt->bind_param("ss", $ip_address, $endpoint);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_locked = $result->num_rows > 0;
        $stmt->close();

        return $is_locked;
    }

    $select_sql = "SELECT MAX(attempt_count) AS attempt_count FROM rate_limits
                   WHERE ip_address = ? AND endpoint = ?
                   AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = $conn->prepare($select_sql);
    $stmt->bind_param("ssi", $ip_address, $endpoint, $window_seconds);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempt_count = (int) $row["attempt_count"];
        $stmt->close();

        return $attempt_count >= $max_attempts;
    }

    $stmt->close();
    return false;
}

/**
 * Return the latest recorded failure count for an IP and endpoint.
 */
function get_latest_attempt_count($ip_address, $endpoint, $window_seconds = 300) {
    global $conn;

    $stmt = $conn->prepare("SELECT MAX(attempt_count) AS attempt_count
                            FROM rate_limits
                            WHERE ip_address = ? AND endpoint = ?
                            AND ((locked_until IS NULL AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND))
                                 OR locked_until > CURRENT_TIMESTAMP)");
    $stmt->bind_param("ssi", $ip_address, $endpoint, $window_seconds);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row["attempt_count"] ?? 0);
}

/**
 * Return the advisory lock name for an IP and endpoint.
 */
function get_rate_limit_lock_name($ip_address, $endpoint) {
    return "pms_rl_" . substr(hash("sha256", $ip_address . "\0" . $endpoint), 0, 57);
}

/**
 * Acquire the advisory lock used to serialize rate-limit updates.
 */
function acquire_rate_limit_lock($lock_name) {
    global $conn;

    $stmt = $conn->prepare("SELECT GET_LOCK(?, 5) AS acquired");
    $stmt->bind_param("s", $lock_name);
    $stmt->execute();
    $acquired = (int) ($stmt->get_result()->fetch_assoc()["acquired"] ?? 0) === 1;
    $stmt->close();

    if (!$acquired) {
        throw new RuntimeException("Unable to serialize rate-limit update.");
    }
}

/**
 * Release a previously acquired rate-limit advisory lock.
 */
function release_rate_limit_lock($lock_name) {
    global $conn;

    $stmt = $conn->prepare("SELECT RELEASE_LOCK(?)");
    $stmt->bind_param("s", $lock_name);
    $stmt->execute();
    $stmt->close();
}

/**
 * Record a failed attempt for rate limiting
 *
 * @param string $ip_address The IP address
 * @param string $endpoint The endpoint (login, register, forgot_password)
 * @param int|null $max_attempts Optional threshold for setting a lockout timestamp
 * @param int|null $lockout_seconds Optional lockout duration in seconds
 * @return int The current recorded attempt count
 */
function record_failed_attempt($ip_address, $endpoint, $window_seconds, $max_attempts = null, $lockout_seconds = null) {
    global $conn;

    init_rate_limit_table();

    $lock_name = get_rate_limit_lock_name($ip_address, $endpoint);
    acquire_rate_limit_lock($lock_name);

    try {
        if ($max_attempts !== null && $lockout_seconds !== null) {
            $lock_check = $conn->prepare("SELECT id FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND locked_until > CURRENT_TIMESTAMP LIMIT 1");
            $lock_check->bind_param("ss", $ip_address, $endpoint);
            $lock_check->execute();
            $lock_result = $lock_check->get_result();
            $lock_active = $lock_result->num_rows > 0;
            $lock_check->close();
            if ($lock_active) {
                return get_latest_attempt_count($ip_address, $endpoint, $window_seconds);
            }
        }

        if ($max_attempts !== null && $lockout_seconds !== null) {
            $update_sql = "UPDATE rate_limits SET
                           locked_until = CASE
                               WHEN attempt_count >= ? - 1 THEN DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ? SECOND)
                               ELSE locked_until
                           END,
                           attempt_count = attempt_count + 1,
                           last_attempt = CURRENT_TIMESTAMP
                           WHERE ip_address = ? AND endpoint = ?
                           AND locked_until IS NULL
                           AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("iissi", $max_attempts, $lockout_seconds, $ip_address, $endpoint, $window_seconds);
        } else {
            $update_sql = "UPDATE rate_limits SET attempt_count = attempt_count + 1,
                           last_attempt = CURRENT_TIMESTAMP
                           WHERE ip_address = ? AND endpoint = ?
                           AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $ip_address, $endpoint, $window_seconds);
        }
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            $insert_sql = "INSERT INTO rate_limits (ip_address, endpoint, attempt_count, window_start)
                           VALUES (?, ?, 1, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ss", $ip_address, $endpoint);
            $stmt->execute();
        }

        $stmt->close();
        return get_latest_attempt_count($ip_address, $endpoint, $window_seconds);
    } finally {
        release_rate_limit_lock($lock_name);
    }
}

/**
 * Clear rate limit records for successful authentication
 *
 * @param string $ip_address The IP address
 * @param string $endpoint The endpoint (login, register, forgot_password)
 */
function clear_rate_limit($ip_address, $endpoint) {
    global $conn;

    $lock_name = get_rate_limit_lock_name($ip_address, $endpoint);
    acquire_rate_limit_lock($lock_name);

    try {
        $delete_sql = "DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ss", $ip_address, $endpoint);
        $stmt->execute();
        $stmt->close();
    } finally {
        release_rate_limit_lock($lock_name);
    }
}

/**
 * Get the client IP address
 *
 * @return string The client IP address
 */
function get_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>