<?php
// Fix GPG key configuration for Passbolt
// Access this at https://passbolt.theportlandcompany.com/fix-gpg-keys.php

header('Content-Type: text/plain');

echo "Passbolt GPG Key Configuration Fix\n";
echo "===================================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Fixing GPG key configuration...\n\n";

echo "1. Clearing existing keyring issues...\n";
$command = './bin/cake passbolt cleanup 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n2. Re-initializing GPG keyring...\n";
$command = './bin/cake passbolt keyring_init 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n3. Creating fresh server GPG keys...\n";
$command = './bin/cake passbolt create_gpg_keys --force 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n4. Running GPG healthcheck...\n";
$command = './bin/cake passbolt healthcheck --gpg 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n5. Checking final key status...\n";
$command = 'gpg --list-secret-keys 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\n=================================\n";
echo "GPG key configuration fix completed!\n";
echo "Try accessing Passbolt setup again.\n";
?>