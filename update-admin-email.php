<?php
// Update existing admin user email to Spencer's email
// Access this at https://passbolt.theportlandcompany.com/update-admin-email.php

header('Content-Type: text/plain');

echo "Update Admin Email to Spencer\n";
echo "=============================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    // Update the existing admin user email
    echo "Updating admin user email from admin@theportlandcompany.com to spencerhill@theportlandcompany.com...\n";
    
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE username = ?");
    $result = $stmt->execute(['spencerhill@theportlandcompany.com', 'admin@theportlandcompany.com']);
    
    if ($result && $stmt->rowCount() > 0) {
        echo "✓ Email updated successfully!\n\n";
        
        // Also update the profile table if it exists
        $stmt = $pdo->prepare("UPDATE profiles SET user_id = (SELECT id FROM users WHERE username = ?) WHERE user_id = (SELECT id FROM users WHERE username = ? LIMIT 1)");
        $stmt->execute(['spencerhill@theportlandcompany.com', 'spencerhill@theportlandcompany.com']);
        
        echo "Now generating recovery token for Spencer...\n";
        chdir('/usr/share/php/passbolt');
        $output = shell_exec('./bin/cake passbolt recover_user --username="spencerhill@theportlandcompany.com" 2>&1');
        echo $output;
        
    } else {
        echo "No user found with admin@theportlandcompany.com email\n";
        
        echo "Checking what users exist...\n";
        $stmt = $pdo->query("SELECT id, username, active, created FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            echo "- " . $user['username'] . " (Active: " . ($user['active'] ? 'Yes' : 'No') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>