<?php
// Final Spencer admin setup with token creation
// Access this at https://passbolt.theportlandcompany.com/final-spencer-setup.php

header('Content-Type: text/plain');

echo "Final Spencer Admin Setup\n";
echo "========================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Creating recovery token for spencerhill@theportlandcompany.com...\n";
$command = './bin/cake passbolt recover_user --username="spencerhill@theportlandcompany.com" --create 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\n=================================\n";
echo "Spencer's admin setup completed!\n";
echo "Use the setup URL above to complete registration.\n";
?>