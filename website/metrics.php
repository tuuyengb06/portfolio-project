<?php

header('Content-Type: text/plain; version=0.0.4; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    databaseConnection();
    $databaseUp = 1;
} catch (PDOException) {
    $databaseUp = 0;
}

echo "# HELP portfolio_database_up Whether the application can connect to its database.\n";
echo "# TYPE portfolio_database_up gauge\n";
echo "portfolio_database_up {$databaseUp}\n";
