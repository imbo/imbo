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

/**
 * Client that interacts with the server part of PHPIMS
 *
 * This client includes methods that can be used to easily interact with a PHPIMS server
 *
 * @package PHPIMS
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_FrontController {
    /**
     * Handle a request
     */
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path   = trim($_SERVER['REQUEST_URI'], '/');
        $parts  = explode('/', $path);
        $hash   = array_shift($parts);

        if (!empty($hash) && !preg_match('/^[a-zA-Z0-9]{32}$/', $hash)) {
            print('Invalid hash');
            exit;
        }

        $params      = array();
        $validParams = array();

        switch ($method) {
            case 'GET':
            case 'DELETE':
                $validParams = array(
                    'w' => 'width',     // Width of the resulting image
                    'h' => 'height',    // Height of the resulting image
                    'f' => 'format',    // Output format: "gif", "png" or "jpg"
                    'q' => 'quality',   // Output quality: 0-100 (valid only when format is "jpg"
                    's' => 'scaleMode', // Scale mode: "fit" or "crop"
                    'x' => 'x',         // Crop X offset when scale is "crop"
                    'y' => 'y',         // Crop Y offset when scale is "crop"
                    'z' => 'zoom',      // Zoom factor used when scale is "crop"
                );
                break;
            case 'POST':
            case 'HEAD':
                // Remove all parts since they are not important for POST and HEAD
                $parts = array();
                break;
            default:
                print('unsupported method');
                exit;
        }

        foreach ($parts as $part) {
            if (isset($validParams[$part[0]])) {
                $params[$validParams[$part[0]]] = substr($part, 2);
            }
        }

        if ($method === 'POST') {
            $collection = new PHPIMS_Image_Metadata_Collection();

            $image = new PHPIMS_Image();
            $image->setFilename($_FILES['file']['name'])
                  ->setFilesize($_FILES['file']['size'])
                  ->setMetadataCollection($collection);

            $database = new PHPIMS_Database_Driver_MongoDB();
            $database->insertNewImage($image);

            print(json_encode(array('id' => $image->getId())));
        }
    }
}