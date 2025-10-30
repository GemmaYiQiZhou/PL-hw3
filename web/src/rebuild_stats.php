<?php
require_once __DIR__ . '/Config.php';
$conn = Config::connect();

$sql = <<<SQL
-- Drop the view if it exists (not table!)
DROP VIEW IF EXISTS hw3_user_stats CASCADE;

-- Now create the real table version
CREATE TABLE hw3_user_stats (
    user_id BIGINT PRIMARY KEY,
    games_played INT DEFAULT 0,
    games_won INT DEFAULT 0,
    highest_score INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0,
    win_pct DECIMAL(5,2) DEFAULT 0
);
SQL;

$result = pg_query($conn, $sql);

if (!$result) {
    die("❌ Error rebuilding hw3_user_stats: " . pg_last_error($conn));
}

echo "✅ hw3_user_stats table successfully rebuilt as a real table!";
pg_close($conn);
?>
