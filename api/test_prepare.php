<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$conn = getDBConnection();
if (!$conn) {
    die("❌ Database connection failed.\n");
}

echo "✅ DB Connected!\n";

$sql = "INSERT INTO trading_history 
(account_type, balance, equity, profit, margin, free_margin, open_positions, total_volume) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("❌ Prepare failed: " . $conn->error . "\n");
}

echo "✅ Prepare succeeded!\n";
