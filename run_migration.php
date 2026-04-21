<?php
/**
 * Run Database Migration - Multi-tenancy
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>🚀 Running Multi-tenancy Migration</h1>";
echo "<pre>";

try {
    $pdo = getConnection();
    $sql = file_get_contents(__DIR__ . '/database/migration_multi_tenancy.sql');
    
    // Split by semicolons (simple approach)
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
            echo "Running query: " . substr($query, 0, 50) . "...\n";
            $pdo->exec($query);
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "You should now delete this file (run_migration.php) for security.";
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
