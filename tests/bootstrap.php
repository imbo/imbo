<?php
require __DIR__ . '/../library/PHPIMS/Autoload.php';

spl_autoload_register(function($class) {
    $path = str_replace('_', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/' . $path . '.php';

    if (file_exists($file)) {
        require $file;
        return true;
    }
});