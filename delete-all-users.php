<?php
// Delete ALL users to allow clean setup through interface
// Access this at https://passbolt.theportlandcompany.com/delete-all-users.php

header('Content-Type: text/plain');

echo "Delete All Users - Clean Setup\n";
echo "==============================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    echo "Deleting ALL user data for clean setup...\n";
    
    // Delete all authentication tokens
    $stmt = $pdo->prepare("DELETE FROM authentication_tokens");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Deleted $count authentication tokens\n";
    
    // Delete all user profiles
    $stmt = $pdo->prepare("DELETE FROM profiles");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Deleted $count user profiles\n";
    
    // Delete all GPG keys
    $stmt = $pdo->prepare("DELETE FROM gpgkeys");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Deleted $count GPG keys\n";
    
    // Delete all users
    $stmt = $pdo->prepare("DELETE FROM users");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Deleted $count users\n";
    
    echo "\n=================================\n";
    echo "ALL USERS DELETED!\n";
    echo "You can now set up through the normal Passbolt interface.\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>