<?php
require_once __DIR__ . '\config.php';

final class Database
{
    public static function connect()
    {
        static $connection = null;
        if ($connection) {
            return $connection;
        }

        $db = Config::db();

        $connectionString = sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s",
            $db['host'],
            $db['port'],
            $db['database'],
            $db['user'],
            $db['password']
        );

        $connection = pg_connect($connectionString);

        if (!$connection) {
            die("could not connect");
        }
    }
}

$testConn = Database::connect();
if ($testConn) {
    echo "✅ Connection works!<br>";
    // Example query
    $res = pg_query($testConn, "SELECT current_date;");
    $row = pg_fetch_assoc($res);
    echo "Server date: " . $row['current_date'];
}
