<?php

declare(strict_types=1);

/**
 * Vibe4Dock setup CLI entry point.
 *
 * Runs with PHP 8.0+ only - no Composer installation or vendor/ directory required.
 * A minimal PSR-4 autoloader loads the Vibe4Dock\ classes directly from src/.
 */

use Vibe4Dock\Cli;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Vibe4Dock\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);

$cli = new Cli(__DIR__ . '/skeleton');

exit($cli->run(array_values($argv)));
