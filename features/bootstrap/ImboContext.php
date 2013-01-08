<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Exception\PendingException;

// Use the RESTContext
require 'RESTContext.php';

/**
 * Imbo Context
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
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
