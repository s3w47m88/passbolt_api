<?php
// Reinstall Passbolt completely for clean setup
// Access this at https://passbolt.theportlandcompany.com/reinstall-passbolt.php

header('Content-Type: text/plain');

echo "Passbolt Complete Reinstallation\n";
echo "=================================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "1. Dropping all Passbolt tables...\n";
$command = './bin/cake passbolt drop_tables --force 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n2. Running fresh database migrations...\n";
$command = './bin/cake migrations migrate 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n3. Creating new server GPG keys...\n";
$command = './bin/cake passbolt create_gpg_keys --force 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n4. Installing Passbolt with no admin user...\n";
$command = './bin/cake passbolt install --no-admin --force 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n5. Clearing all caches...\n";
$command = './bin/cake cache clear_all 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n6. Running final healthcheck...\n";
$command = './bin/cake passbolt healthcheck 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\n=================================\n";
echo "PASSBOLT REINSTALLATION COMPLETED!\n";
echo "The system should now be ready for fresh setup.\n";
echo "Go to: https://passbolt.theportlandcompany.com\n";
echo "You should now be able to register through the normal interface.\n";
?>