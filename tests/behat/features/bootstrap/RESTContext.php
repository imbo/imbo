<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\BehatApiExtension\Context\ApiContext;
use Assert\Assertion;

/**
 * REST context for Behat tests
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
 */
class RESTContext extends ApiContext {
    /**
     * Assert HTTP reason phrase
     *
     * @param string $phrase Expected HTTP reason phrase
     * @Then the response reason phrase is :phrase
     */
    public function assertResponseReasonPhrase($phrase) {
        Assertion::same($phrase, $actual = $this->response->getReasonPhrase(), sprintf(
            'Invalid HTTP reason phrase, expected "%s", got "%s"',
            $phrase,
            $actual
        ));
    }

    /**
     * Set the request body to a string or a resource
     *
     * @param string $content The content to set as the request body. If the string is in fact a
     *                        path to a file, the resource will be attached instead of the literal
     *                        string. If a resource is used the step will also figure out the mime
     *                        type of the file and set the Content-Type header.
     * @param boolean $forceString If the " as a string" part is used in the step, the content is
     *                             treated as a string even if it might be a valid path
     * @Given /^the request body contains "([^"]+)"( as a string)?$/
     */
    public function setRequestBody($content, $forceString = false) {
        $forceString = (boolean) $forceString;

        if (!$forceString && file_exists($content)) {
            // Set the Content-Type request header
            $this->requestOptions['headers']['Content-Type'] = mime_content_type($content);

            // Create a resource to the file
            $content = fopen($filename = $content, 'r');

            if ($content === false) {
                throw new InvalidArgumentException(sprintf('Could not open "%s" for reading.', $filename));
            }
        }

        $this->requestOptions['body'] = $content;
    }
}
