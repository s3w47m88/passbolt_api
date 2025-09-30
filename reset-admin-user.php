<?php
// Reset admin user to allow fresh setup
// Access this at https://passbolt.theportlandcompany.com/reset-admin-user.php

header('Content-Type: text/plain');

echo "Reset Admin User for Fresh Setup\n";
echo "================================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    // Delete existing admin user and related data
    echo "Removing existing admin user data...\n";
    
    // Get user ID first
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['spencerhill@theportlandcompany.com']);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $user_id = $user_data['id'];
        echo "Found user ID: $user_id\n";
        
        // Delete authentication tokens
        $stmt = $pdo->prepare("DELETE FROM authentication_tokens WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo "✓ Deleted authentication tokens\n";
        
        // Delete user profiles
        $stmt = $pdo->prepare("DELETE FROM profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo "✓ Deleted user profile\n";
        
        // Delete GPG keys
        $stmt = $pdo->prepare("DELETE FROM gpgkeys WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo "✓ Deleted GPG keys\n";
        
        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        echo "✓ Deleted user account\n\n";
    } else {
        echo "No existing user found\n\n";
    }
    
    // Create fresh admin user
    echo "Creating fresh admin user...\n";
    chdir('/usr/share/php/passbolt');
    $command = './bin/cake passbolt register_user --username="spencerhill@theportlandcompany.com" --first-name="Spencer" --last-name="Hill" --role="admin" 2>&1';
    $output = shell_exec($command);
    echo $output;
    
    echo "\n\n=================================\n";
    echo "Admin user reset completed!\n";
    echo "Look for the new setup URL above.\n";
    echo "This should now allow first-time setup with key generation.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>