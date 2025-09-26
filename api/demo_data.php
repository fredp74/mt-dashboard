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

    for ($index = $points - 1; $index >= 0; $index--) {
        $offsetMinutes = $index * $stepMinutes;
        $timestamp = $offsetMinutes === 0
            ? $now
            : $now->sub(new DateInterval('PT' . $offsetMinutes . 'M'));

        $progress = 1 - ($index / max(1, $points - 1));
        $balance = 12000 + (1500 * $progress) + (sin($index / 2) * 80);
        $equity = $balance - 80 + (cos($index / 3) * 45);
        $profit = 800 + (650 * $progress) + (sin($index / 1.5) * 35);

        $history[] = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'account_type' => 'SRV',
            'balance' => round($balance, 2),
            'equity' => round($equity, 2),
            'profit' => round($profit, 2),
        ];
    }

    $latestSnapshot = end($history);
    $firstSnapshot = reset($history);

    $currentBalance = $latestSnapshot['balance'];
    $currentEquity = $latestSnapshot['equity'];
    $currentProfit = $latestSnapshot['profit'];

    $performancePercent = 0.0;
    if ($firstSnapshot && $firstSnapshot['balance'] > 0) {
        $performancePercent = (($currentBalance - $firstSnapshot['balance']) / $firstSnapshot['balance']) * 100;
    }

    $equityValues = array_column($history, 'equity');
    $peakEquity = max($equityValues);
    $troughEquity = min($equityValues);
    $peakIndex = array_search($peakEquity, $equityValues, true);
    $troughIndex = array_search($troughEquity, $equityValues, true);

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
        'timestamp' => $latestSnapshot['timestamp'],
    ];

    return [
        'status' => 'demo',
        'message' => $reason !== '' ? $reason : 'Live data unavailable — displaying demo metrics.',
        'period' => $period,
        'current' => [
            'total_balance' => $currentSnapshot['balance'],
            'total_equity' => $currentSnapshot['equity'],
            'total_profit' => $currentSnapshot['profit'],
            'total_positions' => $currentSnapshot['open_positions'],
            'performance_percent' => round($performancePercent, 2),
            'mt5' => $currentSnapshot,
            'srv' => $currentSnapshot,
            'last_update' => $latestSnapshot['timestamp'],
            'is_online' => false,
        ],
        'history' => $history,
        'drawdown' => [
            'max_drawdown' => round(abs($maxDrawdown), 2),
            'peak_equity' => round($peakEquity, 2),
            'trough_equity' => round($troughEquity, 2),
            'peak_date' => $history[$peakIndex]['timestamp'] ?? $latestSnapshot['timestamp'],
            'trough_date' => $history[$troughIndex]['timestamp'] ?? $latestSnapshot['timestamp'],
        ],
        'data_points' => count($history),
    ];
}
