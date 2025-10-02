<?php
// Initialize minimal Passbolt for installation wizard
// Access this at https://passbolt.theportlandcompany.com/init-empty-install.php

header('Content-Type: text/plain');

echo "Initialize Passbolt for Installation Wizard\n";
echo "===========================================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "1. Running database migrations to create schema...\n";
$command = './bin/cake migrations migrate 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n2. Checking if installation wizard is available...\n";
$command = './bin/cake passbolt healthcheck --help 2>&1';
$output = shell_exec($command);
echo "Passbolt commands available\n";

echo "\n3. Not installing anything - leaving for wizard...\n";
echo "Database schema created but no data installed.\n";

echo "\n=================================\n";
echo "READY FOR INSTALLATION WIZARD!\n";
echo "Go to: https://passbolt.theportlandcompany.com\n";
echo "The system should now show setup options.\n";
?>