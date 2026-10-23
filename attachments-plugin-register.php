<?php
/**
 * Build-time patch (run inside the image): insert a single `require` of the
 * plugin's own register.php at the top of addFeaturePlugins(). register.php
 * carries its own PSR-4 autoloader + addPlugin call, so no composer regen and
 * no fragile escaped code injection. Idempotent.
 */

$appDir = '/usr/share/php/passbolt';
$bootstrapper = $appDir . '/src/BaseSolutionBootstrapper.php';
$registerPath = $appDir . '/plugins/Passbolt/Attachments/register.php';

$src = file_get_contents($bootstrapper);
if (strpos($src, 'Passbolt/Attachments/register.php') !== false) {
    echo "BaseSolutionBootstrapper.php: already patched\n";
    exit(0);
}

$inject = "{\n        require '" . $registerPath . "'; // Passbolt/Attachments (out-of-tree)\n";
$pattern = '/(public function addFeaturePlugins\s*\([^)]*\)\s*:\s*void\s*)\{/';
$patched = preg_replace($pattern, '$1' . $inject, $src, 1, $count);
if ($count !== 1) {
    fwrite(STDERR, "FATAL: could not locate addFeaturePlugins() opening brace\n");
    exit(1);
}
file_put_contents($bootstrapper, $patched);
echo "BaseSolutionBootstrapper.php: inserted require of register.php\n";

exec('php -l ' . escapeshellarg($bootstrapper) . ' 2>&1', $out, $rc);
echo implode("\n", $out) . "\n";
exit($rc);
