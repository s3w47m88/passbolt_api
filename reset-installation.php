<?php
// Reset entire Passbolt installation to trigger setup wizard
// Access this at https://passbolt.theportlandcompany.com/reset-installation.php

header('Content-Type: text/plain');

echo "Passbolt Complete Installation Reset\n";
echo "====================================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    echo "Dropping ALL Passbolt tables...\n";
    
    // Get all tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE `$table`");
            echo "✓ Dropped table: $table\n";
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "\n✓ All tables dropped successfully\n";
    } else {
        echo "No tables found to drop\n";
    }
    
    echo "\n=================================\n";
    echo "INSTALLATION COMPLETELY RESET!\n";
    echo "Database is now empty.\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    echo "This should now trigger the Passbolt installation wizard.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>