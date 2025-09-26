<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/srv_aliases.php';
require_once __DIR__ . '/demo_data.php';

const PERIOD_CONFIG = [
    '24h' => ['interval' => 'PT24H', 'bucket_format' => '%Y-%m-%d %H:%i:00'],
    '7d'  => ['interval' => 'P7D',  'bucket_format' => '%Y-%m-%d %H:00:00'],
    '30d' => ['interval' => 'P30D', 'bucket_format' => '%Y-%m-%d 00:00:00'],
];

/**
 * Validate and normalise the requested period.
 */
function normalisePeriod(string $period): string
{
    $key = strtolower(trim($period));
    return array_key_exists($key, PERIOD_CONFIG) ? $key : '24h';
}

/**
 * Determine the earliest timestamp to include for the requested period.
 */
function resolveStartTime(string $period): string
{
    $settings = PERIOD_CONFIG[$period];
    $interval = new DateInterval($settings['interval']);
    $timezone = new DateTimeZone(date_default_timezone_get());

    $now = new DateTimeImmutable('now', $timezone);
    $start = $now->sub($interval);

    return $start->format('Y-m-d H:i:s');
}

/**
 * Fetch the latest snapshot for a specific account type.
 *
 * @return array<string, mixed>|null
 */
function fetchLatestSnapshot(mysqli $conn, string $accountType): ?array
{
    $sql = "SELECT account_type, balance, equity, profit, margin, free_margin, open_positions, total_volume, timestamp
            FROM trading_history
            WHERE account_type = ?
            ORDER BY timestamp DESC
            LIMIT 1";

    $statement = $conn->prepare($sql);
    if ($statement === false) {
        error_log('Failed to prepare latest snapshot query: ' . $conn->error);
        return null;
    }

    $statement->bind_param('s', $accountType);

    if (!$statement->execute()) {
        error_log('Failed to execute latest snapshot query: ' . $statement->error);
        $statement->close();
        return null;
    }

    $result = $statement->get_result();
    $row = $result !== false ? $result->fetch_assoc() : null;
    $statement->close();

    if (!$row) {
        return null;
    }

    return [
        'account_type' => strtoupper($row['account_type'] ?? $accountType),
        'balance' => round((float)($row['balance'] ?? 0), 2),
        'equity' => round((float)($row['equity'] ?? 0), 2),
        'profit' => round((float)($row['profit'] ?? 0), 2),
        'margin' => round((float)($row['margin'] ?? 0), 2),
        'free_margin' => round((float)($row['free_margin'] ?? 0), 2),
        'open_positions' => (int)($row['open_positions'] ?? 0),
        'total_volume' => isset($row['total_volume']) ? (float)$row['total_volume'] : 0.0,
        'timestamp' => $row['timestamp'] ?? null,
    ];
}

/**
 * Fetch historical data grouped into the appropriate time buckets for both SRV
 * and MT5 accounts.
 *
 * @return array<int, array<string, mixed>>
 */
function fetchHistory(mysqli $conn, string $period, string $startTime): array
{
    $bucketFormat = PERIOD_CONFIG[$period]['bucket_format'];

    $sql = "SELECT DATE_FORMAT(timestamp, ?) AS bucket,
                   account_type,
                   AVG(balance) AS balance,
                   AVG(equity) AS equity,
                   AVG(profit) AS profit
            FROM trading_history
            WHERE timestamp >= ? AND account_type IN ('SRV', 'MT5')
            GROUP BY bucket, account_type
            ORDER BY bucket ASC";

    $statement = $conn->prepare($sql);
    if ($statement === false) {
        error_log('Failed to prepare history query: ' . $conn->error);
        return [];
    }

    $statement->bind_param('ss', $bucketFormat, $startTime);

    if (!$statement->execute()) {
        error_log('Failed to execute history query: ' . $statement->error);
        $statement->close();
        return [];
    }

    $result = $statement->get_result();
    $history = [];

    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            $history[] = [
                'timestamp' => $row['bucket'],
                'account_type' => strtoupper($row['account_type'] ?? ''),
                'balance' => round((float)($row['balance'] ?? 0), 2),
                'equity' => round((float)($row['equity'] ?? 0), 2),
                'profit' => round((float)($row['profit'] ?? 0), 2),
            ];
        }
    }

    $statement->close();

    return $history;
}

/**
 * Aggregate SRV and MT5 equity values to derive drawdown metrics.
 *
 * @param array<int, array<string, mixed>> $history
 */
function calculateDrawdownFromHistory(array $history): array
{
    if ($history === []) {
        return [
            'max_drawdown' => 0.0,
            'peak_equity' => 0.0,
            'trough_equity' => 0.0,
            'peak_date' => null,
            'trough_date' => null,
        ];
    }

    $aggregated = [];
    foreach ($history as $entry) {
        $timestamp = $entry['timestamp'];
        if (!isset($aggregated[$timestamp])) {
            $aggregated[$timestamp] = ['timestamp' => $timestamp, 'equity' => 0.0];
        }
        $aggregated[$timestamp]['equity'] += (float)$entry['equity'];
    }

    ksort($aggregated);

    $peakEquity = 0.0;
    $peakDate = null;
    $troughEquity = 0.0;
    $troughDate = null;
    $maxDrawdown = 0.0;

    foreach ($aggregated as $point) {
        $equity = (float)$point['equity'];
        $timestamp = $point['timestamp'];

        if ($equity > $peakEquity) {
            $peakEquity = $equity;
            $peakDate = $timestamp;
        }

        if ($peakEquity > 0) {
            $drawdown = (($peakEquity - $equity) / $peakEquity) * 100;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
                $troughEquity = $equity;
                $troughDate = $timestamp;
            }
        }
    }

    return [
        'max_drawdown' => round($maxDrawdown, 2),
        'peak_equity' => round($peakEquity, 2),
        'trough_equity' => round($troughEquity, 2),
        'peak_date' => $peakDate,
        'trough_date' => $troughDate,
    ];
}

/**
 * Aggregate balances for performance metrics.
 *
 * @param array<int, array<string, mixed>> $history
 */
function calculatePerformancePercent(array $history): float
{
    if ($history === []) {
        return 0.0;
    }

    $aggregated = [];
    foreach ($history as $entry) {
        $timestamp = $entry['timestamp'];
        if (!isset($aggregated[$timestamp])) {
            $aggregated[$timestamp] = 0.0;
        }
        $aggregated[$timestamp] += (float)$entry['balance'];
    }

    ksort($aggregated);

    $first = reset($aggregated);
    $last = end($aggregated);

    if ($first === false || $first <= 0) {
        return 0.0;
    }

    return (($last - $first) / $first) * 100;
}

/**
 * Determine the latest update timestamp between the available account
 * snapshots.
 *
 * @param array<string, array<string, mixed>> $snapshots
 */
function resolveLastUpdate(array $snapshots): ?string
{
    $latest = null;
    foreach ($snapshots as $snapshot) {
        $timestamp = $snapshot['timestamp'] ?? null;
        if ($timestamp === null) {
            continue;
        }

        if ($latest === null || strtotime($timestamp) > strtotime($latest)) {
            $latest = $timestamp;
        }
    }

    return $latest;
}

/**
 * Merge SRV and MT5 totals by taking the strongest values available so the top
 * line metrics stay consistent even if one data source lags behind.
 *
 * @param array<string, array<string, mixed>> $snapshots
 */
function summariseTotals(array $snapshots): array
{
    $metricDefaults = [
        'balance' => 0.0,
        'equity' => 0.0,
        'profit' => 0.0,
        'open_positions' => 0,
    ];

    $totals = $metricDefaults;

    foreach (['SRV', 'MT5'] as $type) {
        if (!isset($snapshots[$type])) {
            continue;
        }

        $snapshot = $snapshots[$type];
        $totals['balance'] = max($totals['balance'], (float)($snapshot['balance'] ?? 0));
        $totals['equity'] = max($totals['equity'], (float)($snapshot['equity'] ?? 0));
        $totals['profit'] = max($totals['profit'], (float)($snapshot['profit'] ?? 0));
        $totals['open_positions'] = max($totals['open_positions'], (int)($snapshot['open_positions'] ?? 0));
    }

    return $totals;
}

/**
 * Provide a consistent account payload for the frontend.
 */
function normaliseAccountSnapshot(array $snapshot, ?string $fallbackTimestamp): array
{
    $defaults = [
        'balance' => 0.0,
        'equity' => 0.0,
        'profit' => 0.0,
        'margin' => 0.0,
        'free_margin' => 0.0,
        'open_positions' => 0,
        'total_volume' => 0.0,
    ];

    $normalised = array_merge($defaults, $snapshot);

    $normalised['balance'] = round((float)$normalised['balance'], 2);
    $normalised['equity'] = round((float)$normalised['equity'], 2);
    $normalised['profit'] = round((float)$normalised['profit'], 2);
    $normalised['margin'] = round((float)$normalised['margin'], 2);
    $normalised['free_margin'] = round((float)$normalised['free_margin'], 2);
    $normalised['open_positions'] = (int)$normalised['open_positions'];
    $normalised['total_volume'] = (float)$normalised['total_volume'];
    $normalised['timestamp'] = $normalised['timestamp'] ?? $fallbackTimestamp;
    if (isset($normalised['account_type'])) {
        $normalised['account_type'] = strtoupper((string)$normalised['account_type']);
    }

    return $normalised;
}

function respondWithDemoData(string $period, string $reason = ''): void
{
    http_response_code(200);
    $demoPayload = getDemoData($period, $reason);
    logAPICall('get_data', ['period' => $period, 'mode' => 'demo'], $demoPayload);
    echo json_encode($demoPayload);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

$period = normalisePeriod($_GET['period'] ?? '24h');
$startTime = resolveStartTime($period);

$conn = getDBConnection();
if (!$conn) {
    respondWithDemoData($period, 'Database connection failed. Showing demo data.');
}

$mt5Snapshot = fetchLatestSnapshot($conn, 'MT5');
$srvSnapshot = fetchLatestSnapshot($conn, 'SRV');

if ($srvSnapshot === null && $mt5Snapshot !== null) {
    $srvSnapshot = mapMt5SnapshotToSrv($mt5Snapshot);
    $srvSnapshot['account_type'] = 'SRV';
    $srvSnapshot['timestamp'] = $mt5Snapshot['timestamp'] ?? null;
}

$snapshots = [];
if ($mt5Snapshot !== null) {
    $snapshots['MT5'] = $mt5Snapshot;
}
if ($srvSnapshot !== null) {
    $snapshots['SRV'] = $srvSnapshot;
}

$history = fetchHistory($conn, $period, $startTime);

if ($history === [] && $snapshots === []) {
    respondWithDemoData($period, 'No trading history available. Showing demo data.');
}

$drawdown = calculateDrawdownFromHistory($history);
$performancePercent = calculatePerformancePercent($history);

$lastUpdate = resolveLastUpdate($snapshots);
$isOnline = false;
if ($lastUpdate !== null) {
    $timestamp = strtotime($lastUpdate);
    if ($timestamp !== false) {
        $isOnline = $timestamp >= strtotime('-5 minutes');
    }
}

$totals = summariseTotals($snapshots);

$current = [
    'total_balance' => round($totals['balance'], 2),
    'total_equity' => round($totals['equity'], 2),
    'total_profit' => round($totals['profit'], 2),
    'total_positions' => $totals['open_positions'],
    'performance_percent' => round($performancePercent, 2),
    'mt5' => normaliseAccountSnapshot($snapshots['MT5'] ?? [], $lastUpdate),
    'srv' => normaliseAccountSnapshot($snapshots['SRV'] ?? [], $lastUpdate),
    'last_update' => $lastUpdate,
    'is_online' => $isOnline,
];

$response = [
    'status' => 'success',
    'mode' => 'live',
    'period' => $period,
    'current' => $current,
    'history' => $history,
    'drawdown' => $drawdown,
    'data_points' => $history !== [] ? count(array_unique(array_column($history, 'timestamp'))) : 0,
];

logAPICall('get_data', ['period' => $period, 'mode' => 'live'], $response);

echo json_encode($response);
$conn->close();
