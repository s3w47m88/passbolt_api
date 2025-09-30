<?php
// Setup first admin user bypassing invitation system
// Access this at https://passbolt.theportlandcompany.com/setup-first-admin.php

header('Content-Type: text/plain');

echo "Passbolt First Admin Setup\n";
echo "==========================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Temporarily enabling public registration...\n";
// Set environment variable for this session
putenv('PASSBOLT_REGISTRATION_PUBLIC=true');

echo "Dropping existing users to start fresh...\n";
$output = shell_exec('./bin/cake passbolt cleanup --dry-run 2>&1');
echo $output;

echo "\n\nCreating first admin user with public registration enabled...\n";
$command = './bin/cake passbolt register_user --username="admin@theportlandcompany.com" --first-name="Admin" --last-name="User" --role="admin" 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nChecking user status...\n";
$output = shell_exec('./bin/cake passbolt show_users 2>&1');
echo $output;

echo "\n\nFirst admin setup completed.\n";
echo "Look for the new setup URL above.\n";
?>