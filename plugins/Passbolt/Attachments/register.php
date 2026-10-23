<?php
/**
 * Registers the out-of-tree Passbolt/Attachments plugin without composer.
 * `require`d from BaseSolutionBootstrapper::addFeaturePlugins(), so `$app`
 * (the Application) is in scope. Self-contained PSR-4 autoloader + addPlugin.
 */

spl_autoload_register(function ($class) {
    $prefix = 'Passbolt\\Attachments\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = __DIR__ . '/src/' . $rel . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

/** @var \Cake\Http\BaseApplication $app */
$app->addPlugin('Passbolt/Attachments', ['bootstrap' => true, 'routes' => true]);
