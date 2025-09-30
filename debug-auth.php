<?php
// Debug Passbolt authentication issues
// Access this at https://passbolt.theportlandcompany.com/debug-auth.php

header('Content-Type: text/plain');

echo "Passbolt Authentication Debug\n";
echo "=============================\n\n";

// Set working directory
chdir('/usr/share/php/passbolt');

echo "1. Checking Passbolt API status...\n";
$command = './bin/cake passbolt healthcheck --help 2>&1';
$output = shell_exec($command);
echo "Healthcheck options available\n\n";

echo "2. Running full healthcheck...\n";
$command = './bin/cake passbolt healthcheck 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n3. Checking server key status...\n";
$command = './bin/cake passbolt healthcheck --gpg 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n4. Checking database connectivity...\n";
$command = './bin/cake passbolt healthcheck --database 2>&1';
$output = shell_exec($command);
echo $output;

echo "\n5. Testing API endpoints...\n";
$base_url = 'https://passbolt.theportlandcompany.com';

// Test auth verify endpoint
echo "Testing /auth/verify...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/auth/verify.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response: " . substr($response, 0, 200) . "...\n\n";

// Test auth login endpoint
echo "Testing /auth/login...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response length: " . strlen($response) . " bytes\n\n";

echo "6. Checking environment variables...\n";
echo "APP_FULL_BASE_URL: " . (getenv('APP_FULL_BASE_URL') ?: 'not set') . "\n";
echo "PASSBOLT_SSL_FORCE: " . (getenv('PASSBOLT_SSL_FORCE') ?: 'not set') . "\n";
echo "PASSBOLT_SECURITY_PROXIES: " . (getenv('PASSBOLT_SECURITY_PROXIES') ?: 'not set') . "\n\n";

echo "Authentication debug completed.\n";
?>