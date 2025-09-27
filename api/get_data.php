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

// Get latest snapshot for each known account type
$accountTypes = ['MT5', 'SRV'];
$escapedAccountTypes = array_map(static function (string $type) use ($conn): string {
    return "'" . $conn->real_escape_string($type) . "'";
}, $accountTypes);
$accountTypeList = implode(',', $escapedAccountTypes);

$currentQuery = "SELECT th.account_type, th.balance, th.equity, th.profit, th.margin, th.free_margin, th.open_positions, th.total_volume, th.timestamp
FROM trading_history th
INNER JOIN (
    SELECT account_type, MAX(id) AS latest_id
    FROM trading_history
    WHERE account_type IN ($accountTypeList)
    GROUP BY account_type
) latest ON latest.account_type = th.account_type AND latest.latest_id = th.id";

$currentResult = $conn->query($currentQuery);
if ($currentResult === false) {
    respondWithDemoData($period, 'Unable to fetch current account snapshot. Showing demo data.');
}

$currentData = [];
while ($row = $currentResult->fetch_assoc()) {
    $currentData[$row['account_type']] = $row;
}

$primaryAccountType = isset($currentData['SRV']) ? 'SRV' : (isset($currentData['MT5']) ? 'MT5' : null);
$primarySnapshot = $primaryAccountType ? $currentData[$primaryAccountType] : [];

$srvSnapshot = mapMt5SnapshotToSrv($currentData['SRV'] ?? $currentData['MT5'] ?? []);

$startSnapshot = null;
$startBalance = null;
$startEquity = null;
$startProfit = null;

if ($primaryAccountType !== null) {
    $escapedPrimaryAccount = $conn->real_escape_string($primaryAccountType);
    $startSnapshotQuery = "SELECT balance, equity, profit FROM trading_history WHERE account_type = '$escapedPrimaryAccount' AND timestamp >= $timeRange ORDER BY timestamp ASC LIMIT 1";
    $startSnapshotResult = $conn->query($startSnapshotQuery);

    if ($startSnapshotResult instanceof mysqli_result && $startSnapshotResult->num_rows > 0) {
        $startSnapshot = $startSnapshotResult->fetch_assoc();

        if ($startSnapshot !== null) {
            $startBalance = floatval($startSnapshot['balance']);
            $startEquity = floatval($startSnapshot['equity']);
            $startProfit = floatval($startSnapshot['profit']);
        }

        $startSnapshotResult->free();
    }
}

$lastSnapshotTimestamp = $primarySnapshot['timestamp'] ?? null;
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
WHERE timestamp >= $timeRange AND account_type IN ($accountTypeList)
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
        'account_type' => $row['account_type'],
        'balance' => round(floatval($row['balance']), 2),
        'equity' => round(floatval($row['equity']), 2),
        'profit' => round(floatval($row['profit']), 2)
    ];
}

if (empty($currentData) && empty($historyData)) {
    respondWithDemoData($period, 'No trading activity recorded yet. Showing demo data.');
}

// Calculate maximum drawdown for the selected period
function calculateDrawdown($conn, $timeRange, $accountType = null) {
    $accountTypeCondition = '';
    if ($accountType !== null) {
        $accountTypeCondition = " AND account_type = '" . $conn->real_escape_string($accountType) . "'";
    }

    $equityQuery = "SELECT
        timestamp,
        equity
    FROM trading_history
    WHERE timestamp >= $timeRange" . $accountTypeCondition . "
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
        $equity = floatval($row['equity']);
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

$drawdownData = calculateDrawdown($conn, $timeRange, $primaryAccountType);

// Calculate totals using the primary account snapshot
$totalBalance = floatval($primarySnapshot['balance'] ?? 0);
$totalEquity = floatval($primarySnapshot['equity'] ?? 0);
$totalProfit = floatval($primarySnapshot['profit'] ?? 0);
$totalPositions = $primarySnapshot['open_positions'] ?? 0;

// Calculate performance metrics
$performancePercent = 0.0;
$periodProfit = null;
$endBalance = $totalBalance;
$endProfit = $totalProfit;

if (count($historyData) > 0) {
    $filteredHistory = array_values(array_filter($historyData, static function ($item) use ($primaryAccountType) {
        if ($primaryAccountType !== null) {
            return $item['account_type'] === $primaryAccountType;
        }

        return in_array($item['account_type'], ['SRV', 'MT5'], true);
    }));

    $firstSnapshot = $filteredHistory[0] ?? null;
    $lastSnapshot = $filteredHistory[count($filteredHistory) - 1] ?? null;

    if ($firstSnapshot !== null) {
        if ($startBalance === null) {
            $startBalance = floatval($firstSnapshot['balance']);
        }

        if ($startEquity === null) {
            $startEquity = floatval($firstSnapshot['equity']);
        }

        if ($startProfit === null && array_key_exists('profit', $firstSnapshot)) {
            $startProfit = floatval($firstSnapshot['profit']);
        }
    }

    if ($primaryAccountType === null && $lastSnapshot !== null) {
        $endBalance = floatval($lastSnapshot['balance'] ?? $endBalance);
        $endProfit = array_key_exists('profit', $lastSnapshot) ? floatval($lastSnapshot['profit']) : $endProfit;
        $totalBalance = $endBalance;
        $totalEquity = floatval($lastSnapshot['equity'] ?? $totalEquity);

        if (array_key_exists('profit', $lastSnapshot)) {
            $totalProfit = $endProfit;
        }
    }
}

$balanceDelta = $startBalance !== null ? $endBalance - $startBalance : null;
$profitDelta = $startProfit !== null ? $endProfit - $startProfit : null;
$equityDelta = $startEquity !== null ? $totalEquity - $startEquity : null;

if ($balanceDelta !== null) {
    $periodProfit = $balanceDelta;

    if ($profitDelta !== null) {
        if (abs($balanceDelta) < 0.01) {
            $periodProfit = $profitDelta;
        } elseif (($balanceDelta >= 0 && $profitDelta >= 0) || ($balanceDelta <= 0 && $profitDelta <= 0)) {
            $periodProfit += $profitDelta;
        }
    }
} elseif ($profitDelta !== null) {
    $periodProfit = $profitDelta;
} elseif ($equityDelta !== null) {
    $periodProfit = $equityDelta;
}

if ($periodProfit === null) {
    $periodProfit = $totalProfit;
}

if ($startBalance !== null && $startBalance > 0) {
    $performancePercent = (($endBalance - $startBalance) / $startBalance) * 100;
} elseif ($startEquity !== null && $startEquity > 0) {
    $performancePercent = (($totalEquity - $startEquity) / $startEquity) * 100;
}

if (abs(floatval($totalProfit)) < 0.01 && $periodProfit !== null) {
    $totalProfit = $periodProfit;
}

$response = [
    'current' => [
        'account_type' => $primaryAccountType,
        'total_balance' => round($totalBalance, 2),
        'total_equity' => round($totalEquity, 2),
        'total_profit' => round($totalProfit, 2),
        'total_positions' => $totalPositions,
        'performance_percent' => round($performancePercent, 2),
        'period_profit' => round($periodProfit ?? 0, 2),
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
