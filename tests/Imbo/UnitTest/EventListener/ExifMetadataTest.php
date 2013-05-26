<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\ExifMetadata;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Unit tests
 */
class ExifMetadataTest extends ListenerTests {
    /**
     * @var ExifMetadata
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new ExifMetadata();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }
}
