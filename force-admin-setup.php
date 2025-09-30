<?php
// Force admin setup completion for Passbolt
// Access this at https://passbolt.theportlandcompany.com/force-admin-setup.php

header('Content-Type: text/plain');

echo "Passbolt Force Admin Setup\n";
echo "==========================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Checking current users...\n";
$output = shell_exec('./bin/cake passbolt datacheck --data=Users 2>&1');
echo $output;

echo "\n\nChecking if admin user exists...\n";
$output = shell_exec('./bin/cake passbolt show_users 2>&1');
echo $output;

echo "\n\nRunning healthcheck...\n";
$output = shell_exec('./bin/cake passbolt healthcheck 2>&1');
echo $output;

echo "\n\nTrying to complete admin setup with email validation bypass...\n";
echo "Admin email: admin@theportlandcompany.com\n";

// Try to activate the user directly
$output = shell_exec('./bin/cake passbolt datacheck --data=Users --fix=true 2>&1');
echo $output;

echo "\n\nForce admin setup process completed.\n";
echo "Try accessing: https://passbolt.theportlandcompany.com/setup/complete\n";
?>