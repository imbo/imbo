<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package PHPIMS
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

set_include_path(__DIR__ . '/../library' . PATH_SEPARATOR . get_include_path());

/** @see PHPIMS_Autoload */
require_once 'PHPIMS/Autoload.php';

// Fetch configuration
$config = require __DIR__ . '/../config/server.php';

try {
    $frontController = new PHPIMS_FrontController($config);
    $response = $frontController->handle($_SERVER['REQUEST_METHOD'],
                                         $_SERVER['REDIRECT_URL']);
} catch (PHPIMS_Exception $e) {
    $response = PHPIMS_Server_Response::fromException($e);
}

$code = $response->getCode();
$header = sprintf("HTTP/1.0 %d %s", $code, PHPIMS_Server_Response::$codes[$code]);

header($header);

foreach ($response->getHeaders() as $header => $value) {
    header($header . ': ' . $value);
}

print($response);