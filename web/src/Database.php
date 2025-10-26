<?php
require_once __DIR__ . '/Config.php';

final class Database
{
    private static $connection = null;

    public static function conn()
    {
        if (!self::$connection) {
            self::$connection = Config::connect();
        }
        return self::$connection;
    }

    public static function query(string $sql)
    {
        $conn = self::conn();
        $result = pg_query($conn, $sql);

        if (!$result) {
            $error = pg_last_error($conn);
            die("Query failed: $error");
        }

        return $result;
    }

    public static function queryParams(string $sql, array $params)
    {
        $conn = self::conn();

        // ✅ Convert PHP booleans to PostgreSQL 'true' / 'false'
        foreach ($params as &$p) {
            if (is_bool($p)) {
                $p = $p ? 'true' : 'false';
            }
        }

        $result = pg_query_params($conn, $sql, $params);

        if (!$result) {
            $error = pg_last_error($conn);
            die("Parameterized query failed: $error");
        }

        return $result;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $result = $params
            ? self::queryParams($sql, $params)
            : self::query($sql);

        return pg_fetch_all($result) ?: [];
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $params
            ? self::queryParams($sql, $params)
            : self::query($sql);

        $row = pg_fetch_assoc($result);
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $result = $params
            ? self::queryParams($sql, $params)
            : self::query($sql);

        return pg_affected_rows($result);
    }
}
