<?php
// Manual initialization script for Passbolt
// Access this at https://passbolt.theportlandcompany.com/init.php

header('Content-Type: text/plain');

echo "Passbolt Database Initialization\n";
echo "=================================\n\n";

// Check environment
echo "Environment Variables:\n";
echo "Host: " . $_ENV['DATASOURCES_DEFAULT_HOST'] . "\n";
echo "User: " . $_ENV['DATASOURCES_DEFAULT_USERNAME'] . "\n";
echo "Database: " . $_ENV['DATASOURCES_DEFAULT_DATABASE'] . "\n\n";

// Try to connect to MySQL
$host = $_ENV['DATASOURCES_DEFAULT_HOST'];
$user = $_ENV['DATASOURCES_DEFAULT_USERNAME'];
$pass = $_ENV['DATASOURCES_DEFAULT_PASSWORD'];
$db = $_ENV['DATASOURCES_DEFAULT_DATABASE'];

echo "Testing MySQL connection...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "✓ MySQL connection successful!\n\n";
    
    // Run Passbolt commands
    echo "Running migrations...\n";
    chdir('/usr/share/php/passbolt');
    system('./bin/cake passbolt migrate 2>&1');
    
    echo "\n\nInstalling Passbolt...\n";
    system('./bin/cake passbolt install --no-admin --force 2>&1');
    
    echo "\n\nClearing cache...\n";
    system('./bin/cake cache clear_all 2>&1');
    
    echo "\n\n=================================\n";
    echo "Initialization complete!\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    
} catch (Exception $e) {
    echo "✗ MySQL Error: " . $e->getMessage() . "\n";
}
?>