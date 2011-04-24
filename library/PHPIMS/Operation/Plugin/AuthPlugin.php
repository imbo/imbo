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
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Operation\Plugin;

use PHPIMS\Operation\Plugin;
use PHPIMS\Operation\PluginInterface;
use PHPIMS\Operation;

/**
 * Auth plugin
 *
 * This plugin will kick in prior to any write operations and make sure that the signature in the
 * URL is correct. The signature is created client siden using a public and a private key. This is
 * done in the same manner here as well. A timestamp is used in the generating and PHPIMS allows a
 * margin of +-5 minutes.
 *
 * The signature is generated using the following elements:
 *
 * - HTTP method in use (POST or DELETE)
 * - The image hash
 * - A provided timestamp
 * - The public key (from the URL) and the private key (from the configuration file)
 *
 * @package PHPIMS
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class AuthPlugin extends Plugin implements PluginInterface {
    /**
     * @see PHPIMS\Operation\Plugin::$events
     */
    static public $events = array(
        'getImagePreExec'            => 100,
        'addImagePreExec'            => 100,
        'deleteImagePreExec'         => 100,
        'deleteImageMetadataPreExec' => 100,
        'editImageMetadataPreExec'   => 100,
    );

    /**
     * @see PHPIMS\Operation\PluginInterface::exec()
     */
    public function exec(Operation $operation) {
        $requiredParams = array('signature', 'publicKey', 'timestamp');

        foreach ($requiredParams as $param) {
            if (empty($_GET[$param])) {
                throw new Exception('Missing required parameter: ' . $param, 400);
            }
        }

        // Make sure the timestamp is in the correct format
        if (!preg_match('/[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}Z/', $_GET['timestamp'])) {
            throw new Exception('Invalid timestamp format: ' . $_GET['timestamp'], 400);
        }

        $year   = substr($_GET['timestamp'], 0, 4);
        $month  = substr($_GET['timestamp'], 5, 2);
        $day    = substr($_GET['timestamp'], 8, 2);
        $hour   = substr($_GET['timestamp'], 11, 2);
        $minute = substr($_GET['timestamp'], 14, 2);

        $timestamp = gmmktime($hour, $minute, null, $month, $day, $year);

        $diff = time() - $timestamp;

        if ($diff > 300 || $diff < 0) {
            throw new Exception('Timestamp expired', 401);
        }

        $config = $operation->getConfig('auth');
        $data = $operation->getMethod() . $operation->getResource() . $_GET['publicKey'] . $_GET['timestamp'];

        // Generate binary hash key
        $actualSignature = hash_hmac('sha256', $data, $config['privateKey'], true);

        if ($actualSignature !== base64_decode($_GET['signature'])) {
            throw new Exception('Signature mismatch', 401);
        }
    }
}