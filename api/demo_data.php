<?php

declare(strict_types=1);

const DEMO_PERIOD_SETTINGS = [
    '24h' => ['points' => 25, 'stepMinutes' => 60],
    '7d'  => ['points' => 28, 'stepMinutes' => 360],
    '30d' => ['points' => 30, 'stepMinutes' => 1440],
];

/**
 * Normalise the requested demo period to one of the supported presets.
 */
function normaliseDemoPeriod(string $period): string
{
    $key = strtolower(trim($period));
    return array_key_exists($key, DEMO_PERIOD_SETTINGS) ? $key : '24h';
}

/**
 * Build deterministic SRV and MT5 timelines that trend upward while keeping the
 * profit figures visually dominant. The generated snapshots intentionally
 * mirror the live payload structure so the frontend can render them without
 * special casing.
 *
 * @return array{
 *     history: array<int, array<string, mixed>>,
 *     srvHistory: array<int, array<string, mixed>>,
 *     mt5History: array<int, array<string, mixed>>
 * }
 */
function buildDemoTimelines(string $period): array
{
    $settings = DEMO_PERIOD_SETTINGS[$period];
    $points = $settings['points'];
    $stepMinutes = $settings['stepMinutes'];

    $timezone = new DateTimeZone(date_default_timezone_get());
    $now = new DateTimeImmutable('now', $timezone);

    $history = [];
    $srvHistory = [];
    $mt5History = [];

    for ($index = 0; $index < $points; $index++) {
        $offset = ($points - 1 - $index) * $stepMinutes;
        $timestamp = $offset === 0
            ? $now
            : $now->sub(new DateInterval('PT' . $offset . 'M'));

        $progress = $points > 1 ? $index / ($points - 1) : 1.0;

        $balanceBase = 11800 + ($progress * 1800);
        $equityBase = $balanceBase - 120 + (sin($index / 3) * 65);
        $profitBase = max($balanceBase, $equityBase) + 1750 + (cos($index / 2.7) * 85);

        $srvSnapshot = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'account_type' => 'SRV',
            'balance' => round($balanceBase + (sin($index / 1.8) * 70), 2),
            'equity' => round($equityBase, 2),
            'profit' => round($profitBase, 2),
            'margin' => round(($balanceBase * 0.11) + 25, 2),
            'free_margin' => round(($balanceBase * 0.8) + 40, 2),
            'open_positions' => 4,
            'total_volume' => 12.8,
        ];

        $mt5BalanceBase = $balanceBase - 240 + (cos($index / 2.3) * 55);
        $mt5EquityBase = $mt5BalanceBase - 95 + (sin($index / 2.1) * 45);
        $mt5ProfitBase = max($mt5BalanceBase, $mt5EquityBase) + 1480 + (sin($index / 1.7) * 60);

        $mt5Snapshot = [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'account_type' => 'MT5',
            'balance' => round($mt5BalanceBase, 2),
            'equity' => round($mt5EquityBase, 2),
            'profit' => round($mt5ProfitBase, 2),
            'margin' => round(($mt5BalanceBase * 0.09) + 35, 2),
            'free_margin' => round(($mt5BalanceBase * 0.76) + 50, 2),
            'open_positions' => 3,
            'total_volume' => 9.6,
        ];

        $srvHistory[] = $srvSnapshot;
        $mt5History[] = $mt5Snapshot;
        $history[] = [
            'timestamp' => $srvSnapshot['timestamp'],
            'account_type' => 'SRV',
            'balance' => $srvSnapshot['balance'],
            'equity' => $srvSnapshot['equity'],
            'profit' => $srvSnapshot['profit'],
        ];
        $history[] = [
            'timestamp' => $mt5Snapshot['timestamp'],
            'account_type' => 'MT5',
            'balance' => $mt5Snapshot['balance'],
            'equity' => $mt5Snapshot['equity'],
            'profit' => $mt5Snapshot['profit'],
        ];
    }

    return [
        'history' => $history,
        'srvHistory' => $srvHistory,
        'mt5History' => $mt5History,
    ];
}

/**
 * Calculate the aggregate drawdown metrics from the combined SRV/MT5 equity
 * timeline.
 *
 * @param array<int, array{timestamp: string, equity: float}> $aggregateEquity
 *
 * @return array{max_drawdown: float, peak_equity: float, trough_equity: float, peak_date: string|null, trough_date: string|null}
 */
function calculateDemoDrawdown(array $aggregateEquity): array
{
    if ($aggregateEquity === []) {
        return [
            'max_drawdown' => 0.0,
            'peak_equity' => 0.0,
            'trough_equity' => 0.0,
            'peak_date' => null,
            'trough_date' => null,
        ];
    }

    $peakEquity = 0.0;
    $peakDate = null;
    $troughEquity = 0.0;
    $troughDate = null;
    $maxDrawdown = 0.0;

    foreach ($aggregateEquity as $point) {
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
 * Provide deterministic demo payloads so the dashboard can function even when
 * the live database is offline.
 */
function getDemoData(string $period, string $reason = ''): array
{
    $periodKey = normaliseDemoPeriod($period);
    $timelines = buildDemoTimelines($periodKey);

    $srvHistory = $timelines['srvHistory'];
    $mt5History = $timelines['mt5History'];
    $history = $timelines['history'];

    $latestSrv = $srvHistory !== [] ? end($srvHistory) : [];
    $latestMt5 = $mt5History !== [] ? end($mt5History) : [];
    $latestTimestamp = $latestSrv['timestamp'] ?? ($latestMt5['timestamp'] ?? null);

    $aggregateEquity = [];
    $aggregateBalance = [];
    foreach ($history as $entry) {
        $timestamp = $entry['timestamp'];
        if (!isset($aggregateEquity[$timestamp])) {
            $aggregateEquity[$timestamp] = ['timestamp' => $timestamp, 'equity' => 0.0];
            $aggregateBalance[$timestamp] = 0.0;
        }

        $aggregateEquity[$timestamp]['equity'] += (float)$entry['equity'];
        $aggregateBalance[$timestamp] += (float)$entry['balance'];
    }

    ksort($aggregateEquity);
    ksort($aggregateBalance);

    $drawdown = calculateDemoDrawdown(array_values($aggregateEquity));

    $firstBalance = $aggregateBalance !== [] ? reset($aggregateBalance) : 0.0;
    $lastBalance = $aggregateBalance !== [] ? end($aggregateBalance) : 0.0;
    $performancePercent = $firstBalance > 0
        ? (($lastBalance - $firstBalance) / $firstBalance) * 100
        : 0.0;

    $totalBalance = max((float)($latestSrv['balance'] ?? 0), (float)($latestMt5['balance'] ?? 0));
    $totalEquity = max((float)($latestSrv['equity'] ?? 0), (float)($latestMt5['equity'] ?? 0));
    $totalProfit = max((float)($latestSrv['profit'] ?? 0), (float)($latestMt5['profit'] ?? 0));
    $totalPositions = max((int)($latestSrv['open_positions'] ?? 0), (int)($latestMt5['open_positions'] ?? 0));

    $defaultAccount = [
        'balance' => 0.0,
        'equity' => 0.0,
        'profit' => 0.0,
        'margin' => 0.0,
        'free_margin' => 0.0,
        'open_positions' => 0,
        'total_volume' => 0.0,
        'timestamp' => $latestTimestamp,
    ];

    $srvCurrent = $latestSrv + ['timestamp' => $latestTimestamp];
    $mt5Current = $latestMt5 + ['timestamp' => $latestTimestamp];

    return [
        'status' => 'success',
        'mode' => 'demo',
        'message' => $reason !== '' ? $reason : 'Live data unavailable — displaying demo metrics.',
        'period' => $periodKey,
        'current' => [
            'total_balance' => round($totalBalance, 2),
            'total_equity' => round($totalEquity, 2),
            'total_profit' => round($totalProfit, 2),
            'total_positions' => $totalPositions,
            'performance_percent' => round($performancePercent, 2),
            'mt5' => array_merge($defaultAccount, $mt5Current),
            'srv' => array_merge($defaultAccount, $srvCurrent),
            'last_update' => $latestTimestamp,
            'is_online' => false,
        ],
        'history' => $history,
        'drawdown' => $drawdown,
        'data_points' => count($srvHistory),
    ];
}
