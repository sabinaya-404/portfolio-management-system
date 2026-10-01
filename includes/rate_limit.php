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
        INDEX idx_ip_endpoint (ip_address, endpoint),
        INDEX idx_window_start (window_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    try {
        $conn->query($create_table_sql);
    } catch (mysqli_sql_exception $e) {
        // Log error but don't break the application
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
 * @return bool True if rate limited (should block), False if not rate limited
 */
function is_rate_limited($ip_address, $endpoint, $max_attempts = 5, $window_seconds = 300) {
    global $conn;

    // Initialize table if needed
    init_rate_limit_table();

    // Clean up old records first (older than window)
    $cleanup_sql = "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = $conn->prepare($cleanup_sql);
    $window_cleanup = $window_seconds * 2; // Clean up records older than 2x window
    $stmt->bind_param("i", $window_cleanup);
    $stmt->execute();
    $stmt->close();

    // Get current attempt count for this IP and endpoint
    $select_sql = "SELECT attempt_count, window_start FROM rate_limits
                   WHERE ip_address = ? AND endpoint = ?
                   AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = $conn->prepare($select_sql);
    $stmt->bind_param("ssi", $ip_address, $endpoint, $window_seconds);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempt_count = (int)$row['attempt_count'];
        $window_start = $row['window_start'];
        $stmt->close();

        // If attempt count exceeds max, check if we're still in the window
        if ($attempt_count >= $max_attempts) {
            // Still rate limited
            return true;
        }
    } else {
        $stmt->close();
    }

    return false;
}

/**
 * Record a failed attempt for rate limiting
 *
 * @param string $ip_address The IP address
 * @param string $endpoint The endpoint (login, register, forgot_password)
 */
function record_failed_attempt($ip_address, $endpoint, $window_seconds) {
    global $conn;

    // Initialize table if needed
    init_rate_limit_table();

    // Try to update existing record first
    $update_sql = "UPDATE rate_limits SET attempt_count = attempt_count + 1,
                   last_attempt = CURRENT_TIMESTAMP
                   WHERE ip_address = ? AND endpoint = ?
                   AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $ip_address, $endpoint, $window_seconds);
    $stmt->execute();

    // If no record was updated, insert a new one
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $insert_sql = "INSERT INTO rate_limits (ip_address, endpoint, attempt_count, window_start)
                       VALUES (?, ?, 1, CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ss", $ip_address, $endpoint);
        $stmt->execute();
    }

    $stmt->close();
}

/**
 * Clear rate limit records for successful authentication
 *
 * @param string $ip_address The IP address
 * @param string $endpoint The endpoint (login, register, forgot_password)
 */
function clear_rate_limit($ip_address, $endpoint) {
    global $conn;

    $delete_sql = "DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ss", $ip_address, $endpoint);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get the client IP address
 *
 * @return string The client IP address
 */
function get_client_ip() {
    // Only use REMOTE_ADDR as it cannot be spoofed by the client
    // Headers like HTTP_X_FORWARDED_FOR can be set by clients to bypass rate limiting
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>