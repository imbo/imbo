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
}
