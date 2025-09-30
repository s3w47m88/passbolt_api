<?php
// Fix Passbolt email configuration directly
// Access this at https://passbolt.theportlandcompany.com/fix-email-config.php

header('Content-Type: text/plain');

echo "Passbolt Email Configuration Fix\n";
echo "================================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "Current environment variables:\n";
echo "EMAIL_TRANSPORT_DEFAULT_HOST: " . (getenv('EMAIL_TRANSPORT_DEFAULT_HOST') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_PORT: " . (getenv('EMAIL_TRANSPORT_DEFAULT_PORT') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_USERNAME: " . (getenv('EMAIL_TRANSPORT_DEFAULT_USERNAME') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_PASSWORD: " . (getenv('EMAIL_TRANSPORT_DEFAULT_PASSWORD') ? '[SET]' : 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_TLS: " . (getenv('EMAIL_TRANSPORT_DEFAULT_TLS') ?: 'not set') . "\n";
echo "EMAIL_TRANSPORT_DEFAULT_STARTTLS: " . (getenv('EMAIL_TRANSPORT_DEFAULT_STARTTLS') ?: 'not set') . "\n\n";

echo "Testing SMTP connection directly...\n";
$host = getenv('EMAIL_TRANSPORT_DEFAULT_HOST') ?: 'smtp.resend.com';
$port = getenv('EMAIL_TRANSPORT_DEFAULT_PORT') ?: '587';
$username = getenv('EMAIL_TRANSPORT_DEFAULT_USERNAME') ?: 'resend';
$password = getenv('EMAIL_TRANSPORT_DEFAULT_PASSWORD') ?: '';

// Test SMTP connection
echo "Connecting to $host:$port...\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if ($socket) {
    echo "✓ TCP connection successful\n";
    $response = fgets($socket);
    echo "Server response: " . trim($response) . "\n";
    
    // Try EHLO
    fwrite($socket, "EHLO test\r\n");
    $response = fgets($socket);
    echo "EHLO response: " . trim($response) . "\n";
    
    // Try STARTTLS if on port 587
    if ($port == '587') {
        fwrite($socket, "STARTTLS\r\n");
        $response = fgets($socket);
        echo "STARTTLS response: " . trim($response) . "\n";
    }
    
    fclose($socket);
} else {
    echo "✗ TCP connection failed: $errstr ($errno)\n";
}

echo "\nTesting with Passbolt's native email test...\n";
$command = './bin/cake passbolt send_test_email --recipient="spencerhill@theportlandcompany.com" --verbose 2>&1';
$output = shell_exec($command);
echo $output;

echo "\nEmail configuration fix completed.\n";
?>