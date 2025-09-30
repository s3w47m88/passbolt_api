<?php
// Recover admin user and generate new setup token
// Access this at https://passbolt.theportlandcompany.com/recover-admin.php

header('Content-Type: text/plain');

echo "Passbolt Admin Recovery\n";
echo "=======================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Generating recovery token for admin user...\n";
$command = './bin/cake passbolt recover_user --username="admin@theportlandcompany.com" 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nRecovery process completed.\n";
echo "Use the recovery URL above to complete the admin setup.\n";
?>