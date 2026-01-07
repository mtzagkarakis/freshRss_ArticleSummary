<?php

/**
 * Simple PSR-4 autoloader for bundled Readability dependencies
 */
spl_autoload_register(function ($class) {
    $prefixes = [
        'fivefilters\\Readability\\' => __DIR__ . '/readability/',
        'Psr\\Log\\' => __DIR__ . '/psr/log/',
        'Masterminds\\' => __DIR__ . '/masterminds/html5/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
