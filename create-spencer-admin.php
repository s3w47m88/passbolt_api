<?php
// Create Spencer as admin user for Passbolt
// Access this at https://passbolt.theportlandcompany.com/create-spencer-admin.php

header('Content-Type: text/plain');

echo "Passbolt Spencer Admin Creation\n";
echo "===============================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Creating admin user for Spencer...\n";
echo "Email: spencerhill@theportlandcompany.com\n";
echo "First Name: Spencer\n";
echo "Last Name: Hill\n\n";

// Create admin user
$command = './bin/cake passbolt register_user --username="spencerhill@theportlandcompany.com" --first-name="Spencer" --last-name="Hill" --role="admin" 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nSpencer admin user creation process completed.\n";
echo "Check the output above for the setup URL.\n";
echo "Visit the setup URL to complete the admin registration.\n";
?>