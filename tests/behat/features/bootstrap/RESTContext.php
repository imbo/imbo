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
use Behat\Gherkin\Node\TableNode;
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

    /**
     * Set a query string parameter
     *
     * @param string $name The name of the parameter
     * @param mixed $value The value for the parameter
     * @Given the query string parameter :name is set to :value
     */
    public function setRequestQueryParameter($name, $value) {
        if (!is_array($this->requestOptions['query'])) {
            $this->requestOptions['query'] = [];
        }

        $this->requestOptions['query'][$name] = $value;
    }

    /**
     * Set multiple query string parameters
     *
     * @param TableNode $table Query parameters
     * @Given the following query string parameters are set:
     */
    public function setRequestQueryParameters(TableNode $table) {
        foreach ($table as $row) {
            $this->addRequestQueryParameter($row['name'], $row['value']);
        }
    }

    /**
     * Set an array-type query parameter
     *
     * @param string $name The name of the parameter
     * @param TableNode $table Values for the query parameter
     * @Given the query string parameter :name has the following values:
     */
    public function setRequestQueryParameterValues($name, TableNode $table) {
        $values = [];

        foreach ($table as $row) {
            $values[] = $row['value'];
        }

        $this->addRequestQueryParameter($name, $values);
    }
}
