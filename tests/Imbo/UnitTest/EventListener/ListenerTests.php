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

use Imbo\EventListener\ListenerInterface;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
abstract class ListenerTests extends \PHPUnit_Framework_TestCase {
    /**
     * Get the listener we are testing
     *
     * @return ListenerInterface
     */
    abstract protected function getListener();

    public function testReturnsDefinitions() {
        $listener = get_class($this->getListener());
        $this->assertInternalType('array', $listener::getSubscribedEvents());
    }
}
