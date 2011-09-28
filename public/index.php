<?php
/**
 * Imbo
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
 * @package Imbo
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

set_include_path(__DIR__ . '/../library' . PATH_SEPARATOR . get_include_path());

/** @see Imbo\Autoload */
require_once 'Imbo/Autoload.php';

$loader = new Imbo\Autoload();
$loader->register();

// Initialize request and response
$request = new Imbo\Http\Request\Request($_GET, $_POST, $_SERVER);
$responseWriter = new Imbo\Http\Response\ResponseWriter($request);
$response = new Imbo\Http\Response\Response($responseWriter);

try {
    // Load configuration
    $config = require __DIR__ . '/../config/server.php';

    // Create the front controller
    $frontController = new Imbo\FrontController($config);

    // Handle the current request
    $frontController->handle($request, $response);
} catch (Imbo\Exception $e) {
    $response->setError($e->getCode(), $e->getMessage());
}

// Send the response to the client
$response->send($request);
