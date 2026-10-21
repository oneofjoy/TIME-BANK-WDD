<?php
declare(strict_types=1);

function database(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) return $connection;
    $file = __DIR__ . '/config.local.php';
    if (!is_file($file)) throw new RuntimeException('Local database configuration is missing.');
    $config = require $file;
    $connection = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    // Reject an out-of-range write instead of silently clipping the balance and
    // committing a misleading ledger entry. Keep any existing SQL mode flags.
    $connection->exec("SET SESSION sql_mode = IF(FIND_IN_SET('STRICT_TRANS_TABLES', @@SESSION.sql_mode)
        OR FIND_IN_SET('STRICT_ALL_TABLES', @@SESSION.sql_mode), @@SESSION.sql_mode,
        CONCAT_WS(',', NULLIF(@@SESSION.sql_mode, ''), 'STRICT_TRANS_TABLES'))");
    // DATETIME lesson times and TIMESTAMP ledger entries are read consistently in WIB.
    $connection->exec("SET time_zone = '+07:00'");
    return $connection;
}
