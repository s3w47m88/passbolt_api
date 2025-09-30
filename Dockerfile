FROM php:8.2-apache

# Create test files
RUN echo '<?php phpinfo(); ?>' > /var/www/html/index.php && \
    echo '<?php \
    echo "<h1>Railway Debug Page</h1>"; \
    echo "<h2>Environment Variables:</h2><pre>"; \
    print_r($_ENV); \
    echo "</pre><h2>MySQL Test:</h2>"; \
    $host = $_ENV["DATASOURCES_DEFAULT_HOST"] ?? "not set"; \
    $user = $_ENV["DATASOURCES_DEFAULT_USERNAME"] ?? "not set"; \
    $pass = $_ENV["DATASOURCES_DEFAULT_PASSWORD"] ?? "not set"; \
    $db = $_ENV["DATASOURCES_DEFAULT_DATABASE"] ?? "not set"; \
    echo "Host: $host<br>"; \
    echo "User: $user<br>"; \
    echo "Database: $db<br>"; \
    if ($host != "not set") { \
        try { \
            $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass); \
            echo "<span style=\"color:green\">✓ MySQL Connection Successful!</span>"; \
        } catch (Exception $e) { \
            echo "<span style=\"color:red\">✗ MySQL Error: " . $e->getMessage() . "</span>"; \
        } \
    } else { \
        echo "<span style=\"color:red\">✗ MySQL variables not set</span>"; \
    } \
    ?>' > /var/www/html/debug.php && \
    docker-php-ext-install pdo pdo_mysql

EXPOSE 80

CMD ["apache2-foreground"]