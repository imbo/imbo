<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener;

use Imbo\EventListener\Vector,
    Imbo\Model\Image;

/**
 * @covers Imbo\EventListener\Vector
 * @group integration
 * @group listeners
 */
class VectorTest extends \PHPUnit_Framework_TestCase {
    public function testCanRender() {
        $listener = new Vector();

        $model = new Image();
        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('getModel')->will($this->returnValue($model));
        $request = $this->getMock('Imbo\Http\Request\Request');
        $event = $this->getMock('Imbo\EventManager\Event');
        $storage = $this->getMock('Imbo\Storage\StorageInterface');
        $storage->expects($this->once())->method('getImage')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/test.pdf')));

        $event->expects($this->once())->method('getManager')->will($this->returnValue($this->getMock('Imbo\EventManager\EventManager')));
        $event->expects($this->once())->method('getStorage')->will($this->returnValue($storage));
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $listener->rasterizeImage($event);

        $this->assertNotEmpty($model->getBlob());
        $this->assertGreaterThan(1, $model->getWidth());
        $this->assertGreaterThan(1, $model->getHeight());
    }
}