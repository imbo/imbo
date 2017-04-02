<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

/**
 * Router for the built in httpd in PHP. Route everything through index.php. When ran from the base
 * project directory, the command looks like this:
 *
 * php -S localhost:8080 -t public tests/behat/router.php
 */
// Hack to bypass limited support for non-standard HTTP verbs in the built-in PHP HTTP server
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    // Set request method
    $_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);

    // Unset the header
    unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

// Define a custom configuration file
define('IMBO_CONFIG_PATH', __DIR__ . '/imbo-configs/config.testing.php');

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
    // The file exists, serve the file as is
    return false;
}

// Imbo uses SCRIPT_FILENAME for path resolution, so set that to the expected value
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . '/index.php';

require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
