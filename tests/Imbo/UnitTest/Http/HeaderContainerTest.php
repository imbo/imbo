<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http;

use Imbo\Http\HeaderContainer;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class HeaderContainerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Http\HeaderContainer::__construct
     * @covers Imbo\Http\HeaderContainer::get
     * @covers Imbo\Http\HeaderContainer::remove
     * @covers Imbo\Http\HeaderContainer::has
     */
    public function testCanSetAndGetValues() {
        $parameters = array(
            'key' => 'value',
            'otherKey' => 'otherValue',
            'content-length' => 123,
            'CONTENT_LENGTH' => 234,
            'content-type' => 'text/html',
            'CONTENT_TYPE' => 'image/png',
            'IF_NONE_MATCH' => 'asdf',
        );

        $container = new HeaderContainer($parameters);
        $this->assertSame('value', $container->get('key'));
        $this->assertSame(234, $container->get('CONTENT_LENGTH'));
        $this->assertSame(234, $container->get('content-length'));
        $this->assertSame('asdf', $container->get('if-none-match'));
        $this->assertSame($container, $container->remove('if-none-match'));
        $this->assertFalse($container->has('if-none-match'));
        $this->assertSame($container, $container->set('IF_NONE_MATCH', 'asd'));
        $this->assertSame('asd', $container->get('if-none-match'));
    }
}
