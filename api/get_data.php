<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/srv_aliases.php';
require_once __DIR__ . '/demo_data.php';

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

$period = $_GET['period'] ?? '24h';

// Database connection
$conn = getDBConnection();
if (!$conn) {
    respondWithDemoData($period, 'Database connection failed. Showing demo data.');
}

// Calculate time range based on period
switch ($period) {
    case '24h':
        $timeRange = 'DATE_SUB(NOW(), INTERVAL 24 HOUR)';
        $groupBy = 'MINUTE(timestamp) DIV 15'; // Group by 15-minute intervals
        break;
    case '7d':
        $timeRange = 'DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $groupBy = 'HOUR(timestamp)'; // Group by hour
        break;
    case '30d':
        $timeRange = 'DATE_SUB(NOW(), INTERVAL 30 DAY)';
        $groupBy = 'DAY(timestamp)'; // Group by day
        break;
    default:
        $timeRange = 'DATE_SUB(NOW(), INTERVAL 24 HOUR)';
        $groupBy = 'MINUTE(timestamp) DIV 15';
}

// Get latest MT5 account snapshot
$currentQuery = "SELECT
    account_type,
    balance,
    equity,
    profit,
    margin,
    free_margin,
    open_positions,
    total_volume,
    timestamp
FROM trading_history
WHERE account_type = 'MT5'
ORDER BY id DESC
LIMIT 1";

$currentResult = $conn->query($currentQuery);
if ($currentResult === false) {
    respondWithDemoData($period, 'Unable to fetch current account snapshot. Showing demo data.');
}
$currentData = [];
if ($row = $currentResult->fetch_assoc()) {
    $currentData['MT5'] = $row;
}

$srvSnapshot = mapMt5SnapshotToSrv($currentData['MT5'] ?? []);

$lastSnapshotTimestamp = $currentData['MT5']['timestamp'] ?? null;
$isOnline = false;

if ($lastSnapshotTimestamp) {
    $snapshotTime = strtotime($lastSnapshotTimestamp);

    if ($snapshotTime !== false) {
        // Treat the SRV as online if we have received data within the last 5 minutes.
        $isOnline = $snapshotTime >= strtotime('-5 minutes');
    }
}

// Get historical data for charts
$historyQuery = "SELECT
    DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i:00') as time_group,
    account_type,
    AVG(balance) as balance,
    AVG(equity) as equity,
    AVG(profit) as profit,
    timestamp
FROM trading_history
WHERE timestamp >= $timeRange AND account_type = 'MT5'
GROUP BY time_group, account_type
ORDER BY time_group ASC";

$historyResult = $conn->query($historyQuery);
if ($historyResult === false) {
    respondWithDemoData($period, 'Unable to fetch historical account data. Showing demo data.');
}
$historyData = [];
while ($row = $historyResult->fetch_assoc()) {
    $historyData[] = [
        'timestamp' => $row['time_group'],
        'account_type' => 'SRV',
        'balance' => round(floatval($row['balance']), 2),
        'equity' => round(floatval($row['equity']), 2),
        'profit' => round(floatval($row['profit']), 2)
    ];
}

if (empty($currentData) && empty($historyData)) {
    respondWithDemoData($period, 'No trading activity recorded yet. Showing demo data.');
}

// Calculate maximum drawdown for the selected period
function calculateDrawdown($conn, $timeRange) {
    // Get equity values over time for both accounts
    $equityQuery = "SELECT
        timestamp,
        SUM(equity) as total_equity
    FROM trading_history
    WHERE timestamp >= $timeRange AND account_type = 'MT5'
    GROUP BY timestamp
    ORDER BY timestamp ASC";
    
    $equityResult = $conn->query($equityQuery);
    
    if (!$equityResult || $equityResult->num_rows === 0) {
        return [
            'max_drawdown' => 0,
            'peak_equity' => 0,
            'trough_equity' => 0,
            'peak_date' => null,
            'trough_date' => null
        ];
    }
    
    $peak = 0;
    $maxDrawdown = 0;
    $peakDate = null;
    $troughDate = null;
    $troughEquity = 0;
    
    while ($row = $equityResult->fetch_assoc()) {
        $equity = floatval($row['total_equity']);
        $date = $row['timestamp'];
        
        // Update peak if current equity is higher
        if ($equity > $peak) {
            $peak = $equity;
            $peakDate = $date;
        }
        
        // Calculate drawdown from peak
        if ($peak > 0) {
            $drawdown = (($peak - $equity) / $peak) * 100;
            
            // Update max drawdown if current is worse
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
                $troughEquity = $equity;
                $troughDate = $date;
            }
        }
    }
    
    return [
        'max_drawdown' => round($maxDrawdown, 2),
        'peak_equity' => round($peak, 2),
        'trough_equity' => round($troughEquity, 2),
        'peak_date' => $peakDate,
        'trough_date' => $troughDate
    ];
}

$drawdownData = calculateDrawdown($conn, $timeRange);

// Calculate totals (MT5 only)
$totalBalance = $currentData['MT5']['balance'] ?? 0;
$totalEquity = $currentData['MT5']['equity'] ?? 0;
$totalProfit = $currentData['MT5']['profit'] ?? 0;
$totalPositions = $currentData['MT5']['open_positions'] ?? 0;

// Calculate performance metrics
$startBalance = 0;
$currentBalance = $totalBalance;
$performancePercent = 0;

if (count($historyData) > 0) {
    $firstSnapshot = array_values(array_filter($historyData, function($item) {
        return in_array($item['account_type'], ['SRV', 'MT5'], true);
    }))[0] ?? null;

    $startBalance = $firstSnapshot['balance'] ?? 0;

    if ($startBalance > 0) {
        $performancePercent = (($currentBalance - $startBalance) / $startBalance) * 100;
    }
}

$response = [
    'current' => [
        'total_balance' => round($totalBalance, 2),
        'total_equity' => round($totalEquity, 2),
        'total_profit' => round($totalProfit, 2),
        'total_positions' => $totalPositions,
        'performance_percent' => round($performancePercent, 2),
        'mt5' => $currentData['MT5'] ?? [
            'balance' => 0,
            'equity' => 0,
            'profit' => 0,
            'margin' => 0,
            'free_margin' => 0,
            'open_positions' => 0
        ],
        'srv' => $srvSnapshot,
        'last_update' => $lastSnapshotTimestamp,
        'is_online' => $isOnline
    ],
    'history' => $historyData,
    'drawdown' => $drawdownData,
    'period' => $period,
    'status' => 'success',
    'data_points' => count($historyData)
];

logAPICall('get_data', ['period' => $period], $response);

echo json_encode($response);
$conn->close();
?>
