<?php
require __DIR__ . '/config.php';

header('Content-Type: text/plain; version=0.0.4');

echo "# HELP portfolio_web_up PHP application is running\n";
echo "# TYPE portfolio_web_up gauge\n";
echo "portfolio_web_up 1\n";

$start = microtime(true);

try {
    $pdo = db();
    $pdo->query("SELECT 1")->fetchColumn();

    echo "# HELP portfolio_database_up Database connection status\n";
    echo "# TYPE portfolio_database_up gauge\n";
    echo "portfolio_database_up 1\n";

    $allowed = [
        'Uptime' => 'portfolio_mysql_uptime_seconds',
        'Threads_connected' => 'portfolio_mysql_threads_connected',
        'Questions' => 'portfolio_mysql_questions_total',
    ];

    $rows = $pdo->query("
        SHOW GLOBAL STATUS
        WHERE Variable_name IN (
            'Uptime', 'Threads_connected', 'Questions'
        )
    ")->fetchAll();

    foreach ($rows as $row) {
        $metric = $allowed[$row['Variable_name']] ?? null;

        if ($metric !== null && is_numeric($row['Value'])) {
            $type = $row['Variable_name'] === 'Questions'
                ? 'counter'
                : 'gauge';

            echo "# TYPE {$metric} {$type}\n";
            echo "{$metric} " . (float) $row['Value'] . "\n";
        }
    }
} catch (Throwable $exception) {
    echo "# TYPE portfolio_database_up gauge\n";
    echo "portfolio_database_up 0\n";
}

$duration = microtime(true) - $start;

echo "# TYPE portfolio_database_check_duration_seconds gauge\n";
echo "portfolio_database_check_duration_seconds {$duration}\n";