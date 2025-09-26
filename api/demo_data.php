<?php

declare(strict_types=1);

/**
 * Provide deterministic demo payloads so the dashboard can function even when
 * the live database is offline. The data is intentionally simple but mimics
 * an improving equity curve so charts and KPIs remain meaningful.
 */
function getDemoData(string $period, string $reason = ''): array
{
    $period = in_array($period, ['24h', '7d', '30d'], true) ? $period : '24h';

    $config = [
        '24h' => ['points' => 24, 'stepMinutes' => 60],
        '7d'  => ['points' => 28, 'stepMinutes' => 360],
        '30d' => ['points' => 30, 'stepMinutes' => 1440],
    ];

    $settings = $config[$period];
    $points = $settings['points'];
    $stepMinutes = $settings['stepMinutes'];

    $now = new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get()));
    $history = [];
    $srvHistory = [];
    $mt5History = [];

    for ($index = $points - 1; $index >= 0; $index--) {
        $offsetMinutes = $index * $stepMinutes;
        $timestamp = $offsetMinutes === 0
            ? $now
            : $now->sub(new DateInterval('PT' . $offsetMinutes . 'M'));

        $progress = 1 - ($index / max(1, $points - 1));
        $balance = 12000 + (1500 * $progress) + (sin($index / 2) * 80);
        $equity = $balance - 80 + (cos($index / 3) * 45);

        // Keep profit visually dominant by ensuring it always exceeds both balance
        // and equity values in the demo payload. This makes the dashboard reflect
        // the user's request for a larger profit figure while maintaining a smooth
        // progression for the charts.
        $profitBase = max($balance, $equity) + 1800;
        $profit = $profitBase + (sin($index / 1.5) * 75);

        $srvSnapshot = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'account_type' => 'SRV',
            'balance' => round($balance, 2),
            'equity' => round($equity, 2),
            'profit' => round($profit, 2),
        ];

        $mt5Balance = $balance - 220 + (sin($index / 1.8) * 60);
        $mt5Equity = $mt5Balance - 110 + (cos($index / 2.4) * 30);
        $mt5ProfitBase = max($mt5Balance, $mt5Equity) + 1450;
        $mt5Profit = $mt5ProfitBase + (cos($index / 1.6) * 55);

        $mt5Snapshot = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'account_type' => 'MT5',
            'balance' => round($mt5Balance, 2),
            'equity' => round($mt5Equity, 2),
            'profit' => round($mt5Profit, 2),
        ];

        $srvHistory[] = $srvSnapshot;
        $mt5History[] = $mt5Snapshot;

        $history[] = $srvSnapshot;
        $history[] = $mt5Snapshot;
    }

    $latestSrvSnapshot = end($srvHistory) ?: [];
    $latestMt5Snapshot = end($mt5History) ?: [];
    $firstSrvSnapshot = reset($srvHistory) ?: [];
    $latestSnapshot = $latestSrvSnapshot ?: ($history !== [] ? end($history) : []);

    $currentBalance = $latestSrvSnapshot['balance'] ?? 0.0;
    $currentEquity = $latestSrvSnapshot['equity'] ?? 0.0;
    $currentProfit = $latestSrvSnapshot['profit'] ?? 0.0;
    $currentMt5Profit = $latestMt5Snapshot['profit'] ?? 0.0;

    $performancePercent = 0.0;
    if ($firstSrvSnapshot && ($firstSrvSnapshot['balance'] ?? 0) > 0) {
        $performancePercent = (($currentBalance - $firstSrvSnapshot['balance']) / $firstSrvSnapshot['balance']) * 100;
    }

    $equityValues = array_column($srvHistory, 'equity');
    $peakEquity = $equityValues !== [] ? max($equityValues) : 0.0;
    $troughEquity = $equityValues !== [] ? min($equityValues) : 0.0;
    $peakIndex = $equityValues !== [] ? array_search($peakEquity, $equityValues, true) : null;
    $troughIndex = $equityValues !== [] ? array_search($troughEquity, $equityValues, true) : null;

    $maxDrawdown = $peakEquity > 0
        ? (($peakEquity - $troughEquity) / $peakEquity) * 100
        : 0.0;

    $currentSnapshot = [
        'balance' => round($currentBalance, 2),
        'equity' => round($currentEquity, 2),
        'profit' => round($currentProfit, 2),
        'margin' => round($currentBalance * 0.12, 2),
        'free_margin' => round($currentBalance * 0.82, 2),
        'open_positions' => 4,
        'total_volume' => 12.4,
        'timestamp' => $latestSnapshot['timestamp'] ?? $now->format('Y-m-d H:i:s'),
    ];

    $mt5Balance = $latestMt5Snapshot['balance'] ?? $currentBalance;
    $mt5SnapshotCurrent = [
        'balance' => round($mt5Balance, 2),
        'equity' => round($latestMt5Snapshot['equity'] ?? $currentEquity, 2),
        'profit' => round($currentMt5Profit, 2),
        'margin' => round($mt5Balance * 0.1, 2),
        'free_margin' => round($mt5Balance * 0.78, 2),
        'open_positions' => 3,
        'total_volume' => 9.8,
        'timestamp' => $latestMt5Snapshot['timestamp'] ?? $currentSnapshot['timestamp'],
    ];

    return [
        'status' => 'success',
        'mode' => 'demo',
        'message' => $reason !== '' ? $reason : 'Live data unavailable — displaying demo metrics.',
        'period' => $period,
        'current' => [
            'total_balance' => $currentSnapshot['balance'],
            'total_equity' => $currentSnapshot['equity'],
            'total_profit' => max($currentSnapshot['profit'], $currentMt5Profit),
            'total_positions' => $currentSnapshot['open_positions'],
            'performance_percent' => round($performancePercent, 2),
            'mt5' => $mt5SnapshotCurrent,
            'srv' => $currentSnapshot,
            'last_update' => $latestSnapshot['timestamp'] ?? $now->format('Y-m-d H:i:s'),
            'is_online' => false,
        ],
        'history' => $history,
        'drawdown' => [
            'max_drawdown' => round(abs($maxDrawdown), 2),
            'peak_equity' => round($peakEquity, 2),
            'trough_equity' => round($troughEquity, 2),
            'peak_date' => ($peakIndex !== null && isset($srvHistory[$peakIndex]))
                ? $srvHistory[$peakIndex]['timestamp']
                : ($latestSnapshot['timestamp'] ?? $now->format('Y-m-d H:i:s')),
            'trough_date' => ($troughIndex !== null && isset($srvHistory[$troughIndex]))
                ? $srvHistory[$troughIndex]['timestamp']
                : ($latestSnapshot['timestamp'] ?? $now->format('Y-m-d H:i:s')),
        ],
        'data_points' => count($srvHistory),
    ];
}
