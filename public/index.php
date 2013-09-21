<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

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
    trigger_error('Uncaught Exception with message: ' . $e->getMessage(), E_USER_ERROR);
}
