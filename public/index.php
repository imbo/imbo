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
$response = new PHPIMS\Response\Response();

try {
    // The request initialization might throw an exception
    $request = new PHPIMS\Request\Request($_SERVER['REQUEST_METHOD'], $resource, $config['auth']);

    // Create the front controller, and handle the current request
    $frontController = new PHPIMS\FrontController($config);
    $frontController->handle($request, $response);
} catch (PHPIMS\Request\Exception $e) {
    $response->setErrorFromException($e);
}

$code = $response->getCode();
$header = sprintf("HTTP/1.0 %d %s", $code, PHPIMS\Response\Response::$codes[$code]);

header($header);

foreach ($response->getHeaders() as $name => $value) {
    header($name . ':' . $value);
}

if ($response->hasImage()) {
    $image = $response->getImage();
    $output = $image->getBlob();
    $contentType = $image->getMimeType();
} else {
    $output = json_encode($response->getBody());
    $contentType = $response->getContentType();
}

header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($output));

print($output);
