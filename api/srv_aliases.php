<?php

declare(strict_types=1);

/**
 * Mapping helpers that align SRV-facing identifiers with the legacy MT5
 * nomenclature still used by the data ingestion layer.
 */

/**
 * Returns the DOM identifier aliases between the SRV dashboard and the legacy
 * MT5 markup. Each key represents the SRV identifier while the value contains
 * the historical MT5 equivalent.
 *
 * @return array<string, string>
 */
function getSrvDomIdMapping(): array
{
    return [
        'srv-status' => 'mt5-status',
        'srv-balance' => 'mt5-balance',
        'srv-equity' => 'mt5-equity',
        'srv-positions' => 'mt5-positions',
        'srv-profit' => 'mt5-profit',
        'srv-margin' => 'mt5-margin',
        'srv-free-margin' => 'mt5-free-margin',
    ];
}

/**
 * Convert a legacy MT5 snapshot into the SRV schema expected by the frontend.
 *
 * @param array<string, mixed> $mt5Data
 * @return array<string, mixed>
 */
function mapMt5SnapshotToSrv(array $mt5Data): array
{
    return [
        'balance' => (float)($mt5Data['balance'] ?? 0),
        'equity' => (float)($mt5Data['equity'] ?? 0),
        'profit' => (float)($mt5Data['profit'] ?? 0),
        'margin' => (float)($mt5Data['margin'] ?? 0),
        'free_margin' => (float)($mt5Data['free_margin'] ?? 0),
        'open_positions' => (int)($mt5Data['open_positions'] ?? 0),
        'total_volume' => isset($mt5Data['total_volume']) ? (float)$mt5Data['total_volume'] : 0.0,
    ];
}

