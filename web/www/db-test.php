<?php
require_once __DIR__ . '/../opt/src/Database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Fetch current date
    $row = Database::fetchOne("SELECT current_date AS today");
    echo "✅ Connected successfully.<br>";
    echo "Today's date: " . htmlspecialchars($row['today']) . "<br><br>";

    // Example: list all tables
    $tables = Database::fetchAll("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        ORDER BY table_name;
    ");

    echo "<b>Tables:</b><br>";
    foreach ($tables as $t) {
        echo htmlspecialchars($t['table_name']) . "<br>";
    }

} catch (Throwable $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
} finally {
    Database::close();
}
