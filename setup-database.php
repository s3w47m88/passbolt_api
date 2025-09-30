<?php
// Database setup script for Passbolt
// Access this at https://passbolt.theportlandcompany.com/setup-database.php

header('Content-Type: text/plain');

echo "Passbolt Database Setup\n";
echo "=======================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

echo "Database Configuration:\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Database: $db\n\n";

echo "Testing MySQL connection...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL connection successful!\n\n";
    
    // Set working directory
    chdir('/usr/share/php/passbolt');
    
    echo "Dropping existing tables (fresh install)...\n";
    system('./bin/cake passbolt drop_tables --force 2>&1');
    
    echo "\n\nRunning database migrations...\n";
    system('./bin/cake migrations migrate 2>&1');
    
    echo "\n\nInstalling Passbolt data...\n";
    system('./bin/cake passbolt install --no-admin 2>&1');
    
    echo "\n\nGenerating GPG keys...\n";
    system('./bin/cake passbolt create_gpg_keys 2>&1');
    
    echo "\n\nClearing cache...\n";
    system('./bin/cake cache clear_all 2>&1');
    
    echo "\n\n=================================\n";
    echo "Database setup complete!\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    
} catch (Exception $e) {
    echo "✗ MySQL Error: " . $e->getMessage() . "\n";
}
?>