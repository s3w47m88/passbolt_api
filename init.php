<?php
// Manual initialization script for Passbolt
// Access this at https://passbolt.theportlandcompany.com/init.php

header('Content-Type: text/plain');

echo "Passbolt Database Initialization\n";
echo "=================================\n\n";

// Check environment - try different methods
echo "Environment Variables (ENV):\n";
echo "Host: " . ($_ENV['DATASOURCES_DEFAULT_HOST'] ?? 'not set') . "\n";
echo "User: " . ($_ENV['DATASOURCES_DEFAULT_USERNAME'] ?? 'not set') . "\n";
echo "Database: " . ($_ENV['DATASOURCES_DEFAULT_DATABASE'] ?? 'not set') . "\n\n";

echo "Environment Variables (getenv):\n";
echo "Host: " . (getenv('DATASOURCES_DEFAULT_HOST') ?: 'not set') . "\n";
echo "User: " . (getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'not set') . "\n";
echo "Database: " . (getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'not set') . "\n\n";

// Hardcode for testing
echo "Using hardcoded values for testing:\n";
$host = 'mysql.railway.internal';
$user = 'root';
$pass = 'fbLifSlquYxetOalWxSTZYecHwOKIqgM';
$db = 'railway';
echo "Host: $host\n";
echo "User: $user\n";
echo "Database: $db\n\n";

// Use hardcoded values since env vars aren't accessible in PHP
$host = 'mysql.railway.internal';
$user = 'root';
$pass = 'fbLifSlquYxetOalWxSTZYecHwOKIqgM';
$db = 'railway';

echo "Testing MySQL connection...\n";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "✓ MySQL connection successful!\n\n";
    
    // Run Passbolt commands
    echo "Running migrations...\n";
    chdir('/usr/share/php/passbolt');
    system('./bin/cake passbolt migrate 2>&1');
    
    echo "\n\nGenerating GPG keys...\n";
    system('./bin/cake passbolt create_gpg_keys 2>&1');
    
    echo "\n\nInstalling Passbolt...\n";
    system('./bin/cake passbolt install --no-admin 2>&1');
    
    echo "\n\nClearing cache...\n";
    system('./bin/cake cache clear_all 2>&1');
    
    echo "\n\n=================================\n";
    echo "Initialization complete!\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    
} catch (Exception $e) {
    echo "✗ MySQL Error: " . $e->getMessage() . "\n";
}
?>