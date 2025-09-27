<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'dashboard_user');
define('DB_PASS', 'change_me_password');
define('DB_NAME', 'dashboard_database');

// API Security
define('API_KEY', 'api123');

// Timezone
date_default_timezone_set('America/New_York'); // Adjust to your timezone

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api.log');

// Prevent mysqli from throwing exceptions so we can gracefully fall back.
mysqli_report(MYSQLI_REPORT_OFF);

// Function to get database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        } catch (mysqli_sql_exception $exception) {
            error_log("Database connection failed: " . $exception->getMessage());
            $conn = null;
            return false;
        }

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            $conn = null;
            return false;
        }

        $conn->set_charset("utf8");
    }
    
    return $conn;
}

// Function to log API calls
function logAPICall($endpoint, $data = [], $response = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data_size' => strlen(json_encode($data)),
        'response_status' => $response['status'] ?? 'unknown',
        'response_message' => $response['message'] ?? ($response['error'] ?? 'n/a')
    ];

    error_log("API_CALL: " . json_encode($log_entry));
}
?>
