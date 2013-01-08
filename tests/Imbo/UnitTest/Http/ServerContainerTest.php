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

use Imbo\Http\ServerContainer;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\Http\ServerContainer
 */
class ServerContainerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Http\ServerContainer::__construct
     * @covers Imbo\Http\ServerContainer::getHeaders
     */
    public function testCanSetAndGetHeaders() {
        $parameters = array(
            'key' => 'value',
            'otherKey' => 'otherValue',
            'content-length' => 123,
            'CONTENT_LENGTH' => 234,
            'content-type' => 'text/html',
            'CONTENT_TYPE' => 'image/png',
            'HTTP_IF_NONE_MATCH' => 'asdf',
        );

        $container = new ServerContainer($parameters);
        $headers = $container->getHeaders();
        $this->assertSame(3, count($headers));
        $this->assertSame(234, $headers['CONTENT_LENGTH']);
        $this->assertSame('image/png', $headers['CONTENT_TYPE']);
        $this->assertSame('asdf', $headers['IF_NONE_MATCH']);
    }
}
