<?php

declare(strict_types=1);

// Native classes used below are referenced with their global names to keep the
// file compatible with a wide range of PHP versions.

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://example.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

/**
 * Small helper for sending JSON responses while ensuring logging happens once
 * per request. All early exits in this file should go through this function.
 *
 * @param int   $statusCode   HTTP status code to send back to the client.
 * @param array $payload      Data payload to encode as JSON.
 * @param array $requestData  The sanitized payload used for logging purposes.
 */
function respond(int $statusCode, array $payload, array $requestData = []): void
{
    http_response_code($statusCode);
    echo json_encode($payload);

    $statusLabel = $payload['status'] ?? ($statusCode >= 400 ? 'error' : 'success');
    logAPICall('receive_data', $requestData, ['status' => $statusLabel]);

    exit;
}

/**
 * Rate limiting: we rely on PHP's session storage which is adequate for this
 * lightweight API. The MetaTrader EA does not re-use cookies, so the limiter
 * effectively applies per IP address.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['api_calls']) || !is_array($_SESSION['api_calls'])) {
    $_SESSION['api_calls'] = [];
}

$now = time();
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$_SESSION['api_calls'][$ipAddress] = array_filter(
    $_SESSION['api_calls'][$ipAddress] ?? [],
    static fn (int $timestamp): bool => ($now - $timestamp) < 60
);

if (count($_SESSION['api_calls'][$ipAddress]) >= 100) {
    respond(429, ['status' => 'error', 'error' => 'Rate limit exceeded']);
}

$_SESSION['api_calls'][$ipAddress][] = $now;

// Handle CORS pre-flight checks quickly.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    respond(200, ['status' => 'ok']);
}

// API key validation
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($providedKey !== API_KEY) {
    respond(403, ['status' => 'error', 'error' => 'Unauthorized access']);
}

$rawInput = file_get_contents('php://input') ?: '';
if (trim($rawInput) === '') {
    respond(400, ['status' => 'error', 'error' => 'No data received']);
}

/**
 * Attempt to decode a JSON payload while being tolerant of BOM markers or
 * stray null bytes that sometimes appear in MetaTrader generated strings.
 * Falls back to parsing URL encoded bodies should the EA be misconfigured.
 *
 * @throws RuntimeException when decoding fails and no suitable fallback exists.
 */
function decodeRequestPayload(string $rawInput): array
{
    $sanitized = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput); // Remove UTF-8 BOM
    $sanitized = str_replace("\0", '', $sanitized ?? '');
    $sanitized = trim($sanitized);

    if ($sanitized === '') {
        return [];
    }

    try {
        if (defined('JSON_THROW_ON_ERROR')) {
            return json_decode($sanitized, true, 512, JSON_THROW_ON_ERROR);
        }

        $decoded = json_decode($sanitized, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }

        return $decoded;
    } catch (Throwable $jsonException) {
        // Attempt a graceful fallback for url-encoded payloads
        $fallback = [];
        parse_str($sanitized, $fallback);

        if (!empty($fallback)) {
            return $fallback;
        }

        if ($jsonException instanceof RuntimeException) {
            throw $jsonException;
        }

        throw new RuntimeException($jsonException->getMessage(), (int) $jsonException->getCode(), $jsonException);
    }
}

try {
    $decodedPayload = decodeRequestPayload($rawInput);
} catch (Throwable $exception) {
    respond(400, [
        'status' => 'error',
        'error' => 'Invalid JSON payload',
        'details' => $exception->getMessage(),
    ]);
}

if (empty($decodedPayload)) {
    respond(400, ['status' => 'error', 'error' => 'Request payload was empty']);
}

/**
 * Helper utilities to coerce incoming values into safe numeric types.
 */
function toFloat(mixed $value): float
{
    if (is_float($value) || is_int($value)) {
        return (float) $value;
    }

    if (is_string($value)) {
        $normalized = preg_replace('/[^0-9+\-\.eE]/', '', $value);
        if ($normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }

    return 0.0;
}

function toInt(mixed $value): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_float($value)) {
        return (int) round($value);
    }

    if (is_string($value) && preg_match('/-?\d+/', $value, $matches)) {
        return (int) $matches[0];
    }

    return 0;
}

$requiredFields = ['balance', 'equity', 'profit'];
foreach ($requiredFields as $field) {
    if (!array_key_exists($field, $decodedPayload)) {
        respond(400, ['status' => 'error', 'error' => "Missing required field: {$field}"], $decodedPayload);
    }
}

$accountType = strtoupper((string) ($decodedPayload['account_type'] ?? 'MT5'));
if ($accountType === '') {
    $accountType = 'MT5';
}

$balance        = toFloat($decodedPayload['balance']);
$equity         = toFloat($decodedPayload['equity']);
$profit         = toFloat($decodedPayload['profit']);
$margin         = toFloat($decodedPayload['margin'] ?? 0);
$freeMargin     = toFloat($decodedPayload['free_margin'] ?? 0);
$openPositions  = toInt($decodedPayload['open_positions'] ?? 0);
$totalVolume    = toFloat($decodedPayload['total_volume'] ?? 0);

$timestampValue = $decodedPayload['timestamp'] ?? $decodedPayload['server_time'] ?? null;
$timestamp      = null;

if (is_string($timestampValue) && $timestampValue !== '') {
    $timestampCandidate = DateTime::createFromFormat('Y-m-d H:i:s', $timestampValue);

    if (!$timestampCandidate) {
        try {
            $timestampCandidate = new DateTime($timestampValue);
        } catch (Throwable $exception) {
            $timestampCandidate = null;
        }
    }

    if ($timestampCandidate instanceof DateTimeInterface) {
        $timestamp = $timestampCandidate->format('Y-m-d H:i:s');
    }
}

if ($timestamp === null) {
    $timestamp = date('Y-m-d H:i:s');
}

$drawdown = 0.0;
if ($balance > 0) {
    $drawdown = (($balance - $equity) / $balance) * 100.0;
}

$sanitizedPayload = [
    'account_type'   => $accountType,
    'balance'        => $balance,
    'equity'         => $equity,
    'profit'         => $profit,
    'margin'         => $margin,
    'free_margin'    => $freeMargin,
    'open_positions' => $openPositions,
    'total_volume'   => $totalVolume,
    'drawdown'       => $drawdown,
    'timestamp'      => $timestamp,
];

$connection = getDBConnection();
if (!$connection instanceof mysqli) {
    respond(500, ['status' => 'error', 'error' => 'Database connection failed'], $sanitizedPayload);
}

$statement = $connection->prepare(
    'INSERT INTO trading_history '
    . '(`account_type`, `balance`, `equity`, `profit`, `margin`, `free_margin`, `open_positions`, `total_volume`, `drawdown`, `timestamp`) '
    . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' 
);

if (!$statement) {
    respond(500, [
        'status' => 'error',
        'error' => 'Failed to prepare insert statement',
        'mysql_error' => $connection->error,
    ], $sanitizedPayload);
}

$statement->bind_param(
    'sdddddidds',
    $sanitizedPayload['account_type'],
    $sanitizedPayload['balance'],
    $sanitizedPayload['equity'],
    $sanitizedPayload['profit'],
    $sanitizedPayload['margin'],
    $sanitizedPayload['free_margin'],
    $sanitizedPayload['open_positions'],
    $sanitizedPayload['total_volume'],
    $sanitizedPayload['drawdown'],
    $sanitizedPayload['timestamp']
);

if (!$statement->execute()) {
    $errorDetails = [
        'status' => 'error',
        'error' => 'Failed to store data',
        'mysql_error' => $statement->error,
    ];

    $statement->close();
    respond(500, $errorDetails, $sanitizedPayload);
}

$statement->close();

$responsePayload = [
    'status' => 'success',
    'message' => 'Data received and stored',
    'stored_at' => date('Y-m-d H:i:s'),
    'account_type' => $sanitizedPayload['account_type'],
    'drawdown' => round($sanitizedPayload['drawdown'], 2),
    'timestamp' => $sanitizedPayload['timestamp'],
];

respond(200, $responsePayload, $sanitizedPayload);
