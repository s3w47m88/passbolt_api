<?php
// Test email configuration for Passbolt
// Access this at https://passbolt.theportlandcompany.com/test-email.php

header('Content-Type: text/plain');

echo "Passbolt Email Configuration Test\n";
echo "==================================\n\n";

// Show current email configuration
echo "Current Email Environment Variables:\n";
echo "EMAIL_DEFAULT_FROM: " . (getenv('EMAIL_DEFAULT_FROM') ?: 'not set') . "\n";
echo "EMAIL_DEFAULT_FROM_NAME: " . (getenv('EMAIL_DEFAULT_FROM_NAME') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_HOST: " . (getenv('EMAIL_TRANSPORT_DEFAULT_HOST') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_PORT: " . (getenv('EMAIL_TRANSPORT_DEFAULT_PORT') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_USERNAME: " . (getenv('EMAIL_TRANSPORT_DEFAULT_USERNAME') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_PASSWORD: " . (getenv('EMAIL_TRANSPORT_DEFAULT_PASSWORD') ? '[SET]' : 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_TLS: " . (getenv('EMAIL_TRANSPORT_DEFAULT_TLS') ?: 'not set') . "\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Testing email configuration with Passbolt...\n";
$command = './bin/cake passbolt send_test_email --recipient="spencerhill@theportlandcompany.com" 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n\nEmail test completed.\n";
?>