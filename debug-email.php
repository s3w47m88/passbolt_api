<?php
// Debug email connectivity and configuration
// Access this at https://passbolt.theportlandcompany.com/debug-email.php

header('Content-Type: text/plain');

echo "Email Connectivity Debug\n";
echo "========================\n\n";

// Test basic connectivity to Resend SMTP server
echo "Testing connectivity to smtp.resend.com...\n";

$host = 'smtp.resend.com';
$ports = [25, 587, 465, 2587, 2465];

foreach ($ports as $port) {
    echo "Testing port $port: ";
    $connection = @fsockopen($host, $port, $errno, $errstr, 10);
    if ($connection) {
        echo "✓ Connected\n";
        fclose($connection);
    } else {
        echo "✗ Failed ($errno: $errstr)\n";
    }
}

echo "\nTesting DNS resolution...\n";
$ip = gethostbyname($host);
echo "smtp.resend.com resolves to: $ip\n";

echo "\nEnvironment variables:\n";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'EMAIL') !== false || strpos($key, 'SMTP') !== false) {
        echo "$key = " . (strlen($value) > 50 ? substr($value, 0, 20) . '...' : $value) . "\n";
    }
}

echo "\nPHP SMTP extension check:\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'Available' : 'Not available') . "\n";
echo "Sockets: " . (extension_loaded('sockets') ? 'Available' : 'Not available') . "\n";

echo "\nDone.\n";
?>