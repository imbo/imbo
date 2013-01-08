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
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
abstract class ListenerTests extends \PHPUnit_Framework_TestCase {
    /**
     * Get the listener we are testing
     *
     * @return ListenerInterface
     */
    abstract protected function getListener();

    /**
     * @covers Imbo\EventListener\AccessToken::getDefinition
     */
    public function testReturnsDefinitions() {
        $definition = $this->getListener()->getDefinition();
        $this->assertInternalType('array', $definition);

        foreach ($definition as $d) {
            $this->assertInstanceOf('Imbo\EventListener\ListenerDefinition', $d);
        }
    }
}
