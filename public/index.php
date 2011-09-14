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

/** @see PHPIMS\Autoload */
require_once 'PHPIMS/Autoload.php';

$loader = new PHPIMS\Autoload();
$loader->register();

// Fetch configuration
$config = require __DIR__ . '/../config/server.php';

$excessDir = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
$resource  = str_replace($excessDir, '', $_SERVER['REDIRECT_URL']);

// Create the response object
$response = new PHPIMS\Http\Response\Response();

try {
    // The request initialization might throw an exception
    $request = new PHPIMS\Http\Request\Request($_SERVER['REQUEST_METHOD'], $resource, $config['auth']);

    // Create the front controller, and handle the current request
    $frontController = new PHPIMS\FrontController($config);
    $frontController->handle($request, $response);
} catch (PHPIMS\Http\Request\Exception $e) {
    $response->setErrorFromException($e);
}

// Prepare the status line
$code = $response->getStatusCode();
$header = sprintf("HTTP/1.1 %d %s", $code, PHPIMS\Http\Response\Response::$statusCodes[$code]);

// Send status line
header($header);

// Send the content type of the response
header('Content-Type: ' . $response->getContentType());

// Send additional headers
foreach ($response->getHeaders() as $name => $value) {
    header($name . ':' . $value);
}

// Make sure we don't send any message body for 204 and 304
if ($code !== 204 && $code !== 304) {
    if ($response->hasImage()) {
        $output = $response->getImage()->getBlob();
    } else {
        $output = json_encode($response->getBody());
    }

    // Send a correct content-length header
    header('Content-Length: ' . strlen($output));

    // Send message body
    print($output);
}
