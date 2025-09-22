<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'trading_user');
define('DB_PASS', 'your_secure_password_123!');
define('DB_NAME', 'trading_dashboard');

// API Security
define('API_KEY', 'your-ultra-secure-api-key-2024-' . md5('algotradingresearch.com'));

// Timezone
date_default_timezone_set('America/New_York'); // Adjust to your timezone

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api.log');

// Function to get database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
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
        'response_status' => $response['status'] ?? 'unknown'
    ];
    
    error_log("API_CALL: " . json_encode($log_entry));
}
?>
