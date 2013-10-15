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

use Imbo\EventListener\ImageTransformer,
    Imbo\Image\Transformation\Transformation,
    Imbo\Image\Transformation\TransformationInterface,
    Imbo\Storage\ImageReaderAware,
    Imbo\Storage\ImageReaderAwareTrait,
    Imbo\Model\Image;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\EventListener\ImageTransformer
 */
class ImageTransformerTest extends ListenerTests {
    /**
     * @var ImageTransformer
     */
    private $listener;

    private $request;
    private $response;
    private $event;
    private $image;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->image = $this->getMock('Imbo\Model\Image');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->expects($this->any())->method('getModel')->will($this->returnValue($this->image));
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new ImageTransformer();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->image = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::initialize
     */
    public function testWillInitializeImageTransformationHandlers() {
        $config = array(
            'imageTransformations' => array(
                'border' => 'Imbo\Image\Transformation\Border',
                'thumbnail' => 'Imbo\Image\Transformation\Thumbnail',
                'borderedThumbnail' => array(
                    'border', 'thumbnail',
                ),

            ),
        );
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue('publickey'));
        $this->event->expects($this->once())->method('getStorage')->will($this->returnValue($this->getMock('Imbo\Storage\StorageInterface')));
        $this->image->expects($this->at(0))->method('setImageReader')->with($this->isInstanceOf('Imbo\Storage\ImageReader'));
        $this->event->expects($this->once())->method('getConfig')->will($this->returnValue($config));
        $this->image->expects($this->at(1))->method('setTransformationHandler')->with('border', 'Imbo\Image\Transformation\Border');
        $this->image->expects($this->at(2))->method('setTransformationHandler')->with('thumbnail', 'Imbo\Image\Transformation\Thumbnail');
        $this->image->expects($this->at(3))->method('setTransformationHandler')->with('borderedThumbnail', array('border', 'thumbnail'));

        $this->listener->initialize($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testCanApplyTransformationsToAnImage() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array(
            array(
                'name' => 'resize',
                'params' => array(
                    'width' => 100,
                ),
            ),
            array(
                'name' => 'thumbnail',
                'params' => array(
                    'some' => 'value',
                ),
            ),
        )));

        $this->image->expects($this->at(0))->method('transform')->with('resize', array('width' => 100));
        $this->image->expects($this->at(1))->method('transform')->with('thumbnail', array('some' => 'value'));
        $this->image->expects($this->at(2))->method('hasBeenTransformed')->with(true);

        $this->listener->transform($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testDoesNotMarkImageAsTransformedIfNoTransformationsExists() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));
        $this->image->expects($this->at(0))->method('hasBeenTransformed')->with(false);
        $this->listener->transform($this->event);
    }
}
