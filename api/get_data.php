<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Database connection
$conn = new mysqli('localhost', 'username', 'password', 'database');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$period = $_GET['period'] ?? '24h';

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

// Get current data (latest entry for each account type)
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
WHERE id IN (
    SELECT MAX(id) FROM trading_history GROUP BY account_type
)";

$currentResult = $conn->query($currentQuery);
$currentData = [];
while ($row = $currentResult->fetch_assoc()) {
    $currentData[$row['account_type']] = $row;
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
WHERE timestamp >= $timeRange 
GROUP BY time_group, account_type
ORDER BY time_group ASC";

$historyResult = $conn->query($historyQuery);
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

// Calculate maximum drawdown for the selected period
function calculateDrawdown($conn, $timeRange) {
    // Get equity values over time for both accounts
    $equityQuery = "SELECT 
        timestamp,
        SUM(equity) as total_equity
    FROM trading_history 
    WHERE timestamp >= $timeRange 
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

// Calculate totals
$totalBalance = ($currentData['MT4']['balance'] ?? 0) + ($currentData['MT5']['balance'] ?? 0);
$totalEquity = ($currentData['MT4']['equity'] ?? 0) + ($currentData['MT5']['equity'] ?? 0);
$totalProfit = ($currentData['MT4']['profit'] ?? 0) + ($currentData['MT5']['profit'] ?? 0);
$totalPositions = ($currentData['MT4']['open_positions'] ?? 0) + ($currentData['MT5']['open_positions'] ?? 0);

// Calculate performance metrics
$startBalance = 0;
$currentBalance = $totalBalance;
$performancePercent = 0;

if (count($historyData) > 0) {
    // Get initial balance from history
    $firstMT4 = array_values(array_filter($historyData, function($item) { 
        return $item['account_type'] === 'MT4'; 
    }))[0] ?? null;
    
    $firstMT5 = array_values(array_filter($historyData, function($item) { 
        return $item['account_type'] === 'MT5'; 
    }))[0] ?? null;
    
    $startBalance = ($firstMT4['balance'] ?? 0) + ($firstMT5['balance'] ?? 0);
    
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
        'mt4' => $currentData['MT4'] ?? [
            'balance' => 0,
            'equity' => 0,
            'profit' => 0,
            'margin' => 0,
            'free_margin' => 0,
            'open_positions' => 0
        ],
        'mt5' => $currentData['MT5'] ?? [
            'balance' => 0,
            'equity' => 0,
            'profit' => 0,
            'margin' => 0,
            'free_margin' => 0,
            'open_positions' => 0
        ],
        'last_update' => date('Y-m-d H:i:s')
    ],
    'history' => $historyData,
    'drawdown' => $drawdownData,
    'period' => $period,
    'status' => 'success',
    'data_points' => count($historyData)
];

echo json_encode($response);
$conn->close();
?>
