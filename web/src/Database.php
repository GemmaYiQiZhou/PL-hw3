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

        //Convert PHP booleans to PostgreSQL 'true' / 'false'
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

    public static function getUserStats($user_id)
    {
        $conn = self::conn();

        $sql = <<<SQL
        SELECT
            u.user_id,
            u.name,
            u.email,
            COUNT(g.game_id) AS games_played,
            COALESCE(ROUND(AVG(g.score)::numeric, 2), 0) AS avg_score,
            COALESCE(MAX(g.score), 0) AS best_score,
            COALESCE(ROUND(100.0 * AVG(CASE WHEN g.won THEN 1 ELSE 0 END)::numeric, 2), 0) AS win_pct
        FROM hw3_users u
        LEFT JOIN hw3_games g ON g.user_id = u.user_id
        WHERE u.user_id = $1
        GROUP BY u.user_id, u.name, u.email
        SQL;

        $result = pg_query_params($conn, $sql, [$user_id]);
        if (!$result) {
            die("❌ Failed to fetch user stats: " . pg_last_error($conn));
        }

        return pg_fetch_assoc($result);
}

}
