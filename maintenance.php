<?php
require_once 'api/config.php';

// Clean old data (keep last 90 days)
$conn = getDBConnection();
$result = $conn->query("DELETE FROM trading_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)");
echo "Cleaned " . $conn->affected_rows . " old records\n";

// Optimize database
$conn->query("OPTIMIZE TABLE trading_history");
echo "Database optimized\n";

// Check API log size and rotate if needed
$log_file = __DIR__ . '/logs/api.log';
if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) { // 10MB
    rename($log_file, $log_file . '.' . date('Y-m-d'));
    touch($log_file);
    echo "Log rotated\n";
}
?>
