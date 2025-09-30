<?php
// Delete all users and clean everything for user self-registration
// Access this at https://passbolt.theportlandcompany.com/clean-everything.php

header('Content-Type: text/plain');

echo "Clean Everything - Remove All Users\n";
echo "===================================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    echo "Deleting ALL user-related data...\n";
    
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
    
    // Delete all roles_users entries
    $stmt = $pdo->prepare("DELETE FROM roles_users");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Deleted $count role assignments\n";
    
    echo "\n=================================\n";
    echo "ALL USERS AND SCRIPTS REMOVED!\n";
    echo "Database is clean for your own registration.\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>