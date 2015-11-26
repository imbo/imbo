<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image;

use Imbo\Image\TransformationManager,
    Imbo\Http\Request\Request,
    Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @covers Imbo\Image\TransformationManager
 * @group unit
 */
class TransformationManagerTest extends \PHPUnit_Framework_TestCase {
    protected $manager;
    protected $request;
    protected $response;
    protected $event;
    protected $query;
    protected $image;

    public function setUp() {
        $this->config = require __DIR__ . '/../../../../config/config.default.php';
        $this->manager = new TransformationManager();
        $this->manager->addTransformations($config['transformations']);
        $this->query = new ParameterBag([]);
        $this->request = new Request();
        $this->request->query = $this->query;
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->image = $this->getMock('Imbo\Model\Image');
        $this->response = $this->getMock('Imbo\Http\Response\Response');

        $this->response->expects($this->any())->method('getModel')->will($this->returnValue($this->image));

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->image->expects($this->any())->method('getWidth')->will($this->returnValue(1600));
        $this->image->expects($this->any())->method('getHeight')->will($this->returnValue(900));
    }

    public function tearDown() {
        $this->manager = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->query = null;
        $this->image = null;
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForSingleTransformation() {
        $this->query->set('t', ['maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForMultipleTransformations() {
        $this->query->set('t', ['maxSize:width=1024', 'maxSize:height=620']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1102, $minimum['width']);
        $this->assertSame(620, $minimum['height']);

        // Regardless of order
        $this->query->set('t', ['maxSize:height=620', 'maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1102, $minimum['width']);
        $this->assertSame(620, $minimum['height']);
    }
}
