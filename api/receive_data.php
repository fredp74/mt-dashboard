<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://algotradingresearch.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Rate limiting
session_start();
$ip = $_SERVER['REMOTE_ADDR'];
$current_time = time();

if (!isset($_SESSION['api_calls'])) {
    $_SESSION['api_calls'] = [];
}

// Clean old entries (older than 1 minute)
$_SESSION['api_calls'] = array_filter($_SESSION['api_calls'], function($timestamp) use ($current_time) {
    return ($current_time - $timestamp) < 60;
});

// Check rate limit (max 100 calls per minute)
if (count($_SESSION['api_calls']) >= 100) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

$_SESSION['api_calls'][] = $current_time;

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API key validation
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== API_KEY) {
    http_response_code(403);
    logAPICall('receive_data', [], ['status' => 'unauthorized']);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get and validate input
$input = file_get_contents('php://input');
if (empty($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received']);
    exit;
}

$data = json_decode($input, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields and data types
$required_fields = [
    'balance' => 'numeric',
    'equity' => 'numeric',
    'profit' => 'numeric'
];

foreach ($required_fields as $field => $type) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
    
    if ($type === 'numeric' && !is_numeric($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field $field must be numeric"]);
        exit;
    }
}

$accountType = 'MT5';
if (isset($data['account_type'])) {
    $normalizedType = strtoupper(trim($data['account_type']));
    if ($normalizedType !== 'MT5') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid account type']);
        exit;
    }
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Insert data
$stmt = $conn->prepare("INSERT INTO trading_history 
    (account_type, balance, equity, profit, margin, free_margin, open_positions, total_volume) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$margin = floatval($data['margin'] ?? 0);
$free_margin = floatval($data['free_margin'] ?? 0);
$open_positions = intval($data['open_positions'] ?? 0);
$total_volume = floatval($data['total_volume'] ?? 0);

$stmt->bind_param("sdddddid",
    $accountType,
    floatval($data['balance']),
    floatval($data['equity']),
    floatval($data['profit']),
    $margin,
    $free_margin,
    $open_positions,
    $total_volume
);

if ($stmt->execute()) {
    $response = [
        'status' => 'success',
        'message' => 'Data received and stored',
        'timestamp' => date('Y-m-d H:i:s'),
        'account_type' => $accountType
    ];
    
    logAPICall('receive_data', $data, $response);
    echo json_encode($response);
} else {
    http_response_code(500);
    $error_response = ['error' => 'Failed to store data'];
    logAPICall('receive_data', $data, $error_response);
    echo json_encode($error_response);
}

$stmt->close();
?>
