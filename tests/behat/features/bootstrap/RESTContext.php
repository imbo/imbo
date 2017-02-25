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
     * Check the size of the response body (not the Content-Length response header)
     *
     * @param int $expetedSize The size we are expecting
     * @Then the response body size is :expectedSize
     */
    public function assertResponseBodySize($expectedSize) {
        $this->requireResponse();

        Assertion::same(
            $actualSize = strlen((string) $this->response->getBody()),
            (int) $expectedSize,
            sprintf('Expected response body size: %d, actual: %d', $expectedSize, $actualSize)
        );
    }
}
