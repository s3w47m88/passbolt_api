<?php
// Create admin user script for Passbolt
// Access this at https://passbolt.theportlandcompany.com/create-admin.php

header('Content-Type: text/plain');

echo "Passbolt Admin User Creation\n";
echo "============================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Creating admin user...\n";
echo "Email: admin@theportlandcompany.com\n";
echo "First Name: Admin\n";
echo "Last Name: User\n\n";

// Create admin user
$command = './bin/cake passbolt register_user --username="admin@theportlandcompany.com" --first-name="Admin" --last-name="User" --role="admin" 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nAdmin user creation process completed.\n";
echo "Check the output above for the setup URL.\n";
echo "Visit the setup URL to complete the admin registration.\n";
?>