<?php
// Bypass admin setup by connecting directly to database
// Access this at https://passbolt.theportlandcompany.com/bypass-setup.php

header('Content-Type: text/plain');

echo "Passbolt Admin Setup Bypass\n";
echo "===========================\n\n";

// Get database credentials from environment
$host = getenv('DATASOURCES_DEFAULT_HOST') ?: 'mysql.railway.internal';
$user = getenv('DATASOURCES_DEFAULT_USERNAME') ?: 'root';
$pass = getenv('DATASOURCES_DEFAULT_PASSWORD') ?: '';
$db = getenv('DATASOURCES_DEFAULT_DATABASE') ?: 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    // Check current admin user
    echo "Checking current admin user...\n";
    $stmt = $pdo->prepare("SELECT id, username, active, deleted FROM users WHERE username = ?");
    $stmt->execute(['admin@theportlandcompany.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Found admin user:\n";
        echo "- ID: " . $admin['id'] . "\n";
        echo "- Username: " . $admin['username'] . "\n";
        echo "- Active: " . ($admin['active'] ? 'Yes' : 'No') . "\n";
        echo "- Deleted: " . ($admin['deleted'] ? 'Yes' : 'No') . "\n\n";
        
        // Activate the user if not active
        if (!$admin['active']) {
            echo "Activating admin user...\n";
            $stmt = $pdo->prepare("UPDATE users SET active = 1 WHERE id = ?");
            $stmt->execute([$admin['id']]);
            echo "✓ Admin user activated\n\n";
        }
        
        // Check authentication tokens
        echo "Checking authentication tokens...\n";
        $stmt = $pdo->prepare("SELECT id, token, active, type FROM authentication_tokens WHERE user_id = ?");
        $stmt->execute([$admin['id']]);
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($tokens) {
            echo "Found tokens:\n";
            foreach ($tokens as $token) {
                echo "- Type: " . $token['type'] . ", Active: " . ($token['active'] ? 'Yes' : 'No') . "\n";
                echo "  Setup URL: https://passbolt.theportlandcompany.com/setup/start/" . $admin['id'] . "/" . $token['token'] . "\n";
            }
        } else {
            echo "No tokens found. Creating new setup token...\n";
            chdir('/usr/share/php/passbolt');
            $output = shell_exec('./bin/cake passbolt recover_user --username="admin@theportlandcompany.com" 2>&1');
            echo $output;
        }
        
    } else {
        echo "Admin user not found!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
}
?>