<?php
// Enable Passbolt access by creating initial admin
// Access this at https://passbolt.theportlandcompany.com/enable-access.php

header('Content-Type: text/plain');

echo "Enable Passbolt Access\n";
echo "======================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Creating server GPG keys...\n";
$command = './bin/cake passbolt create_gpg_keys --force 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nInstalling Passbolt without admin...\n";
$command = './bin/cake passbolt install --no-admin --force 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nPassbolt is now accessible.\n";
echo "Go to: https://passbolt.theportlandcompany.com\n";
?>