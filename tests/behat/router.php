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
 * Router for the built in httpd in php-5.4. Route everything through index.php. When ran from the
 * base project directory, the command looks like this:
 *
 * php -S localhost:8888 -t public tests/router.php
 */
// Hack to bypass limited support for non-standard HTTP verbs in the built-in PHP HTTP server
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    // Set request method
    $_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);

    // Unset the header
    unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

if (isset($_SERVER['HTTP_X_COLLECT_COVERAGE']) && isset($_SERVER['HTTP_X_TEST_SESSION_ID'])) {
    // Output code coverage stored in the .cov files
    $coverageDir = sys_get_temp_dir() . '/behat-coverage';

    if (!is_dir($coverageDir)) {
        // Create tmp dir
        mkdir($coverageDir);
    }

    $files = new FilesystemIterator(
        $coverageDir,
        FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
    );
    $data = [];
    $suffix = $_SERVER['HTTP_X_TEST_SESSION_ID'] . '.cov';

    foreach ($files as $filename) {
        if (!preg_match('/' . preg_quote($suffix, '/') . '$/', $filename)) {
            continue;
        }

        $content = unserialize(file_get_contents($filename));
        unlink($filename);

        foreach ($content as $file => $lines) {
            if (is_file($file)) {
                if (!isset($data[$file])) {
                    $data[$file] = $lines;
                } else {
                    foreach ($lines as $line => $flag) {
                        if (!isset($data[$file][$line]) || $flag > $data[$file][$line]) {
                            $data[$file][$line] = $flag;
                        }
                    }
                }
            }
        }
    }

    echo serialize($data);
    exit;
}

if (isset($_SERVER['HTTP_X_ENABLE_COVERAGE']) && isset($_SERVER['HTTP_X_TEST_SESSION_ID']) && extension_loaded('xdebug')) {
    // Register a shutdown function that stops code coverage and stores the coverage of the current
    // request
    register_shutdown_function(function() {
        $data = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        $coverageDir = sys_get_temp_dir() . '/behat-coverage';

        if (is_dir($coverageDir) || mkdir($coverageDir, 0775, true)) {
            $filename = sprintf(
                '%s/%s.%s.cov',
                $coverageDir,
                md5(uniqid('', true)),
                $_SERVER['HTTP_X_TEST_SESSION_ID']
            );

            file_put_contents($filename, serialize($data));
        }
    });

    // Start code coverage
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
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
