<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Resource;

use Imbo\Resource\ShortUrls;

/**
 * @covers Imbo\Resource\ShortUrls
 * @group unit
 * @group resources
 */
class ShortUrlsTest extends ResourceTests {
    /**
     * @var ShortUrls
     */
    private $resource;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new ShortUrls();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
    }
}
