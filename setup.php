<?php
// Manual setup script for Passbolt
// This bypasses the migration issues and sets up the database directly

header('Content-Type: text/plain');

echo "Passbolt Manual Setup\n";
echo "=====================\n\n";

// Database connection
$host = 'mysql.railway.internal';
$user = 'root';
$pass = 'fbLifSlquYxetOalWxSTZYecHwOKIqgM';
$db = 'railway';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to MySQL\n\n";
    
    // Create passbolt config file with correct database settings
    $configFile = '/etc/passbolt/passbolt.php';
    if (file_exists($configFile)) {
        echo "Updating Passbolt configuration...\n";
        $config = file_get_contents($configFile);
        
        // Update database configuration
        $config = preg_replace(
            "/'driver' => '.*?'/",
            "'driver' => 'Cake\\\Database\\\Driver\\\Mysql'",
            $config
        );
        
        file_put_contents($configFile, $config);
        echo "✓ Configuration updated\n\n";
    }
    
    // Import Passbolt schema manually
    echo "Creating database schema...\n";
    
    // Download and execute the schema
    $schemaUrl = 'https://raw.githubusercontent.com/passbolt/passbolt_api/master/config/Migrations/schema-dump-default.sql';
    $schema = file_get_contents($schemaUrl);
    
    if ($schema) {
        // Split by semicolon and execute each statement
        $statements = explode(';', $schema);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                    // Table might already exist
                    if (!str_contains($e->getMessage(), 'already exists')) {
                        echo "Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✓ Schema created\n\n";
    }
    
    // Generate GPG keys
    echo "Setting up GPG keys...\n";
    chdir('/usr/share/php/passbolt');
    
    // Create GPG directory if it doesn't exist
    if (!file_exists('/etc/passbolt/gpg')) {
        mkdir('/etc/passbolt/gpg', 0755, true);
    }
    
    // Generate keys if they don't exist
    if (!file_exists('/etc/passbolt/gpg/serverkey.asc')) {
        $gpgConfig = <<<EOF
%echo Generating GPG key
Key-Type: RSA
Key-Length: 2048
Subkey-Type: RSA
Subkey-Length: 2048
Name-Real: Passbolt Server
Name-Email: passbolt@theportlandcompany.com
Expire-Date: 0
%no-protection
%commit
%echo done
EOF;
        
        file_put_contents('/tmp/gpg-batch', $gpgConfig);
        exec('gpg --batch --gen-key /tmp/gpg-batch 2>&1', $output);
        exec('gpg --armor --export passbolt@theportlandcompany.com > /etc/passbolt/gpg/serverkey.asc 2>&1');
        exec('gpg --armor --export-secret-keys passbolt@theportlandcompany.com > /etc/passbolt/gpg/serverkey_private.asc 2>&1');
        
        echo "✓ GPG keys generated\n";
    }
    
    // Clear cache
    echo "\nClearing cache...\n";
    system('./bin/cake cache clear_all 2>&1');
    
    echo "\n=================================\n";
    echo "Setup complete!\n";
    echo "Go to: https://passbolt.theportlandcompany.com\n";
    echo "You should now be able to create your admin account.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>