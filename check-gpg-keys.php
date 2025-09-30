<?php
// Check and manage GPG keys for Passbolt
// Access this at https://passbolt.theportlandcompany.com/check-gpg-keys.php

header('Content-Type: text/plain');

echo "Passbolt GPG Key Status Check\n";
echo "=============================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Checking server GPG key status...\n";
$command = './bin/cake passbolt healthcheck --gpg 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nChecking GPG key configuration...\n";
echo "PASSBOLT_GPG_SERVER_KEY_FINGERPRINT: " . (getenv('PASSBOLT_GPG_SERVER_KEY_FINGERPRINT') ?: 'not set') . "\n";
echo "PASSBOLT_KEY_EMAIL: " . (getenv('PASSBOLT_KEY_EMAIL') ?: 'not set') . "\n\n";

echo "Listing existing GPG keys...\n";
$command = 'gpg --list-keys 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nListing secret keys...\n";
$command = 'gpg --list-secret-keys 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nChecking Passbolt keyring...\n";
$command = './bin/cake passbolt show_logs_path 2>&1';
$logs_output = shell_exec($command);
echo "Logs path: " . $logs_output;

echo "\nGPG key status check completed.\n";
echo "If no server key exists, you'll need to generate one or the admin user needs to create their GPG key pair.\n";
?>