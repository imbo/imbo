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
 * @package Tests/Behat
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

use Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Exception\PendingException;

// Use the RESTContext
require 'RESTContext.php';

/**
 * Imbo Context
 *
 * @package Tests/Behat
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class ImboContext extends RESTContext {
    private $privateKey;

    /**
     * @BeforeSuite
     * @AfterSuite
     */
    public static function backupConfig(SuiteEvent $event) {
        $originalConfig = __DIR__ . '/../../config/config.php';
        $backup = __DIR__ . '/../../config/config.php.behat.bak';

        if ($event->getName() === 'beforeSuite') {
            if (is_file($originalConfig)) {
                rename($originalConfig, $backup);
            }

        } else {
            if (is_file($backup)) {
                rename($backup, $originalConfig);
            }
        }
    }

    /**
     * Write to the config file
     *
     * @param array $config The config array to write
     */
    private function writeConfig(array $config) {
        $path = __DIR__ . '/../../config/config.php';

        $config = sprintf(
            "<?php return %s;",
            var_export($config, true)
        );

        file_put_contents($path, $config);
    }

    /**
     * @Given /^there are no Imbo issues$/
     */
    public function thereAreNoImboIssues() {
        $this->writeConfig(array());
    }

    /**
     * @Given /^the user "([^"]*)" exists with private key "([^"]*)"$/
     */
    public function generateConfig($publicKey, $privateKey) {
        $this->privateKey = $privateKey;

        $this->writeConfig(array(
            'auth' => array(
                $publicKey => $privateKey,
            )
        ));
    }

    /**
     * @Given /^I include an access token in the query$/
     */
    public function appendAccessToken() {
        $privateKey = $this->privateKey;

        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) use ($privateKey) {
            $request = $event['request'];
            $accessToken = hash_hmac('sha256', $request->getUrl(), $privateKey);
            $request->getQuery()->set('accessToken', $accessToken);
        });
    }

    /**
     * @Given /^the Imbo error message is "([^"]*)"(?: and the error code is "([^"]*)")?$/
     */
    public function assertImboError($message, $code = null) {
        $contentType = $this->response->getContentType();

        if ($contentType === 'application/json') {
            $data = $this->response->json();
            $errorMessage = $data['error']['message'];
            $errorCode = $data['error']['imboErrorCode'];
        } else if ($contentType === 'application/xml') {
            $data = $this->response->xml();
            $errorMessage = (string) $data->error->message;
            $errorCode = $data->error->imboErrorCode;
        } else {
            throw new PendingException('Not added support for html yet');
        }

        assertSame($message, $errorMessage);

        if ($code !== null) {
            assertSame((int) $code, (int) $errorCode);
        }
    }
}
