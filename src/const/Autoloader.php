<?php
/**
 * Autoloader
 */

$map = [
    'requires' => [
        'Slimdown' => $_SERVER['DOCUMENT_ROOT'] . '/lib/jbroadway/Slimdown',
    ],
    'classes' => [
        'Astrotomic\Twemoji\\' => $_SERVER['DOCUMENT_ROOT'] . '/lib/Astrotomic/',
        'Michelf\Markdown\\' => $_SERVER['DOCUMENT_ROOT'] . '/lib/Michelf/',
    ],
];

spl_autoload_register(function (string $classname) use ($map) {
    foreach ($map['classes'] as $namespace => $dir) {
        if (strpos($classname, $namespace) === 0) {
            $relative_class = substr($classname, strlen($namespace));
            $file = $dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    foreach ($map['requires'] as $namespace => $dir) {
        $file = $dir . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>