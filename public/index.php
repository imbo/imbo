<?php
namespace Imbo;

use Exception as BaseException;

try {
    // Fetch the configuration
    $configPath = defined('IMBO_CONFIG_PATH') ? IMBO_CONFIG_PATH : __DIR__ . '/../config/config.default.php';

    $config = require $configPath;

    $application = new Application();
    $application->run($config);
} catch (BaseException $e) {
    header('HTTP/1.1 500 Internal Server Error');

    // check if we should rethrow the exception and let PHP generate a fatal exception error with a proper stack trace
    if (!empty($config['rethrowFinalException'])) {
        throw $e;
    } else {
        trigger_error('Uncaught Exception with message: ' . $e->getMessage(), E_USER_ERROR);
    }
}
