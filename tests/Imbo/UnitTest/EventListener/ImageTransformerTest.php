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
 */
class ImageTransformerTest extends ListenerTests {
    /**
     * @var ImageTransformer
     */
    private $listener;

    private $request;
    private $response;
    private $storage;
    private $event;
    private $image;
    private $cn;
    private $publicKey = 'pubKey';

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->cn = $this->getMock('Imbo\Http\ContentNegotiation');
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->image = $this->getMock('Imbo\Model\Image');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->expects($this->any())->method('getImage')->will($this->returnValue($this->image));
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

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
        $this->cn = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     * @covers Imbo\EventListener\ImageTransformer::registerTransformationHandler
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
                'params' => array(),
            ),
        )));

        $this->listener->registerTransformationHandler('resize', function($params) {
            return new SomeTransformation('resize');
        });
        $this->listener->registerTransformationHandler('thumbnail', function($params) {
            return function($image) {
                echo 'thumbnail';
            };
        });
        $this->image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $this->expectOutputString('resizethumbnail');
        $this->listener->transform($this->event);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Unknown transformation: foo
     * @expectedExceptionCode 400
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testThrowsExceptionWhenApplyingUnknownTransformation() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array(
            array(
                'name' => 'foo',
                'params' => array(
                    'width' => 100,
                ),
            )
        )));

        $this->listener->transform($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     * @covers Imbo\EventListener\ImageTransformer::registerTransformationHandler
     */
    public function testSetsImageReaderIfTransformationImplementsImageReaderAware() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array(
            array(
                'name' => 'thumbnail',
                'params' => array(),
            ),
        )));

        $this->storage->expects($this->once())->method('getImage')->will($this->returnValue($this->publicKey));

        $this->listener->registerTransformationHandler('thumbnail', function($params) {
            return new ImageReaderAwareTransformation('imagereaderaware');
        });
        $this->image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $this->expectOutputString('imagereaderaware-' . $this->publicKey);
        $this->listener->transform($this->event);
    }
}

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class SomeTransformation extends Transformation implements TransformationInterface {
    protected $output;

    public function __construct($output) {
        $this->output = $output;
    }

    public function applyToImage(Image $image) {
        echo $this->output;
    }
}

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class ImageReaderAwareTransformation extends SomeTransformation implements ImageReaderAware, TransformationInterface {
    use ImageReaderAwareTrait;

    public function applyToImage(Image $image) {
        echo $this->output . '-' . $this->getImageReader()->getImage('someImg');
    }
}
