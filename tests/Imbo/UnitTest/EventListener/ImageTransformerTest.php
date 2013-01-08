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
    Imbo\Image\Image;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\EventListener\ImageTransformer
 */
class ImageTransformerTest extends ListenerTests {
    /**
     * @var ImageTransformer
     */
    private $listener;

    private $container;
    private $request;
    private $response;
    private $event;
    private $image;
    private $cn;

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\ImageTransformer::setContainer
     */
    public function setUp() {
        $this->cn = $this->getMock('Imbo\Http\ContentNegotiation');
        $this->container = $this->getMock('Imbo\Container');
        $this->container->expects($this->any())->method('get')->with('contentNegotiation')->will($this->returnValue($this->cn));
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->image = $this->getMock('Imbo\Image\Image');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->expects($this->any())->method('getImage')->will($this->returnValue($this->image));
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new ImageTransformer();
        $this->listener->setContainer($this->container);
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->container = null;
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
        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/png' => 1.0)));
        $this->image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/png'));
        $this->cn->expects($this->once())->method('isAcceptable')->with('image/png', array('image/png' => 1.0))->will($this->returnValue(true));
        $this->cn->expects($this->never())->method('bestMatch');
        $this->image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $this->expectOutputString('resizethumbnail');
        $this->listener->transform($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testWillConvertImageIfMimeTypeIsNotAcceptable() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/png' => 1.0)));
        $this->image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/png'));
        $this->cn->expects($this->once())->method('isAcceptable')->with('image/png', array('image/png' => 1.0))->will($this->returnValue(false));
        $this->cn->expects($this->once())->method('bestMatch')->with($this->isType('array'), $this->isType('array'))->will($this->returnValue('image/jpeg'));
        $this->image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $this->listener->registerTransformationHandler('convert', function($params) {
            return new SomeTransformation('convert');
        });

        $this->expectOutputString('convert');
        $this->listener->transform($this->event);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Not Acceptable
     * @expectedExceptionCode 406
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testThrowsExceptionWhenClientDoesNotAcceptSupportedMimeTypes() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array()));

        $this->request->expects($this->once())->method('getExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getAcceptableContentTypes')->will($this->returnValue(array('image/png' => 1.0)));
        $this->image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/gif'));
        $this->cn->expects($this->once())->method('isAcceptable')->with('image/gif', array('image/png' => 1.0))->will($this->returnValue(false));
        $this->cn->expects($this->once())->method('bestMatch')->with($this->isType('array'), $this->isType('array'))->will($this->returnValue(false));

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
}

class SomeTransformation extends Transformation implements TransformationInterface {
    private $output;

    public function __construct($output) {
        $this->output = $output;
    }

    public function applyToImage(Image $image) {
        echo $this->output;
    }
}
