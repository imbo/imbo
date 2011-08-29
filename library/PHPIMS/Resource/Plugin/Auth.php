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
 * @subpackage Plugins
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource\Plugin;

use PHPIMS\Request\RequestInterface;
use PHPIMS\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;

/**
 * Auth plugin
 *
 * This plugin will kick in prior to any write operations and will make sure that the signature in
 * the URL is correct. The signature is created client side using a public and a private key. This
 * is done in the same manner here as well. A timestamp is used in the generating and PHPIMS allows
 * a margin of +-2 minutes.
 *
 * The signature is generated using the following elements in the following order:
 *
 * - HTTP method in use (POST or DELETE)
 * - The image identifier
 * - The public key (from the URL) and the private key (from the configuration file)
 * - A provided timestamp
 *
 * @package PHPIMS
 * @subpackage Plugins
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Auth implements PluginInterface {
    /**
     * @see PHPIMS\Resource\Plugin\PluginInterface::exec()
     */
    public function exec(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        // Required parameters
        $requiredParams = array('signature', 'timestamp');

        foreach ($requiredParams as $param) {
            if (!$request->has($param)) {
                throw new Exception('Missing required parameter: ' . $param, 400);
            }
        }

        $timestamp = $request->getTimestamp();

        // Make sure the timestamp is in the correct format
        if (!preg_match('/[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}Z/', $timestamp)) {
            throw new Exception('Invalid timestamp format: ' . $timestamp, 400);
        }

        $year   = substr($timestamp, 0, 4);
        $month  = substr($timestamp, 5, 2);
        $day    = substr($timestamp, 8, 2);
        $hour   = substr($timestamp, 11, 2);
        $minute = substr($timestamp, 14, 2);

        $timestamp = gmmktime($hour, $minute, null, $month, $day, $year);

        $diff = time() - $timestamp;

        if ($diff > 120 || $diff < -120) {
            throw new Exception('Timestamp expired', 401);
        }

        // Generate data for the HMAC
        $data = $request->getMethod() . $request->getResource() . $request->getPublicKey() . $timestamp;

        // Generate binary hash key
        $actualSignature = hash_hmac('sha256', $data, $request->getPrivateKey(), true);

        if ($actualSignature !== base64_decode($request->getSignature())) {
            throw new Exception('Signature mismatch', 401);
        }
    }
}
