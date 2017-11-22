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

use Imbo\Image\TransformationManager;
use Imbo\Http\Request\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\TransformationManager
 * @group unit
 */
class TransformationManagerTest extends TestCase {
    protected $manager;
    protected $request;
    protected $response;
    protected $event;
    protected $query;
    protected $image;

    public function setUp() {
        $this->config = require __DIR__ . '/../../../../config/config.default.php';
        $this->manager = new TransformationManager();
        $this->manager->addTransformations($this->config['transformations']);
        $this->query = new ParameterBag([]);
        $this->request = new Request();
        $this->request->query = $this->query;
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->image = $this->createMock('Imbo\Model\Image');
        $this->response = $this->createMock('Imbo\Http\Response\Response');

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

        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);

        // Regardless of order
        $this->query->set('t', ['maxSize:height=620', 'maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForRotatedImages() {
        $this->query->set('t', ['rotate:angle=90', 'maxSize:width=600']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1067, $minimum['width']);
        $this->assertSame(600, $minimum['height']);
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForDoublyRotatedImages() {
        $this->query->set('t', [
            'rotate:angle=90',
            'maxSize:width=500',
            'rotate:angle=-90',
            'maxSize:width=320'
        ]);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(320, $minimum['width']);
        $this->assertSame(180, $minimum['height']);
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testReturnsFalseIfMinimumSizeIsLargerThanOriginal() {
        $this->query->set('t', ['resize:width=3800,height=1800']);
        $this->assertFalse($this->manager->getMinimumImageInputSize($this->event));
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testSkipsTransformationsThatReturnNullAsMinInputSize() {
        $this->query->set('t', ['maxSize:width=10000']);
        $this->assertFalse($this->manager->getMinimumImageInputSize($this->event));
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testReturnsCorrectSizeIfChainIsNotStopped() {
        // Sanity check for the test that follows
        $this->query->set('t', ['maxSize:width=750', 'maxSize:width=320']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertSame(320, $minimum['width']);
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testStopsMinSizeChainIfTransformationReturnsFalse() {
        $this->query->set('t', ['maxSize:width=750', 'rotate:angle=17.3', 'maxSize:width=320']);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertSame(750, $minimum['width']);
        $this->assertSame(422, $minimum['height']);
    }

    /**
     * @covers Imbo\Image\TransformationManager::getMinimumImageInputSize
     */
    public function testFindsRightSizeWhenRegionIsExtracted() {
        $this->query->set('t', ['crop:width=784,height=700,x=384,y=200', 'maxSize:width=320']);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertSame(654, $minimum['width']);
        $this->assertSame(368, $minimum['height']);
    }
}
