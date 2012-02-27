<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

// Fetch the container
$container = require __DIR__ . '/../bootstrap/bootstrap.php';

// Fetch some entries from the container
$request = $container->request;
$response = $container->response;

// Add a version header
$response->getHeaders()->set('X-Imbo-Version', Imbo\Version::getVersionNumber());

// Create the front controller and handle the request
$frontController = new Imbo\FrontController($container);

try {
    $frontController->handle($request, $response);
} catch (Imbo\Exception $exception) {
    $response->setStatusCode($exception->getCode());
    $internalCode = $exception->getImboErrorCode();

    if ($internalCode === null) {
        $internalCode = Imbo\Exception::ERR_UNSPECIFIED;
    }

    $data = array(
        'error' => array(
            'code'          => $exception->getCode(),
            'message'       => $exception->getMessage(),
            'timestamp'     => gmdate('Y-m-d\TH:i:s\Z'),
            'imboErrorCode' => $internalCode,
        ),
    );

    // Fetch the real image identifier (PUT only) or the one from the URL (if present)
    if (($identifier = $request->getRealImageIdentifier()) || ($identifier = $request->getImageIdentifier())) {
        $data['imageIdentifier'] = $identifier;
    }

    $responseWriter = new Imbo\Http\Response\ResponseWriter();
    $responseWriter->write($data, $request, $response);
}

// Send the response to the client
$response->send();
