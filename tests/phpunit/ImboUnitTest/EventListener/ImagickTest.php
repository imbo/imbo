<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\Imagick;

/**
 * @covers Imbo\EventListener\Imagick
 * @group unit
 * @group listeners
 */
class ImagickTest extends ListenerTests {
    /**
     * @var Imagick
     */
    private $listener;

    private $request;
    private $response;
    private $event;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new Imagick();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
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
     * @covers Imbo\EventListener\Imagick::readImageBlob
     * @covers Imbo\EventListener\Imagick::setImagick
     */
    public function testFetchesImageFromRequest() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image'));

        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->once())->method('readImageBlob')->with('image');

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('images.post'));

        $this->listener->setImagick($imagick)->readImageBlob($this->event);
    }

    /**
     * @covers Imbo\EventListener\Imagick::readImageBlob
     * @covers Imbo\EventListener\Imagick::setImagick
     */
    public function testFetchesImageFromResponse() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('image'));

        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->once())->method('readImageBlob')->with('image');

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('storage.image.load'));

        $this->listener->setImagick($imagick)->readImageBlob($this->event);
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function hasImageBeenTransformed() {
        return [
            'has been transformed' => [true],
            'has not been transformed' => [false],
        ];
    }

    /**
     * @covers Imbo\EventListener\Imagick::readImageBlob
     * @covers Imbo\EventListener\Imagick::setImagick
     * @dataProvider hasImageBeenTransformed
     */
    public function testUpdatesModelBeforeStoring($hasBeenTransformed) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue($hasBeenTransformed));

        $imagick = $this->getMock('Imagick');

        if ($hasBeenTransformed) {
            $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('image'));
            $image->expects($this->once())->method('setBlob')->with('image');
        } else {
            $imagick->expects($this->never())->method('getImageBlob');
            $image->expects($this->never())->method('setBlob');
        }

        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $this->listener->setImagick($imagick)
                       ->updateModelBeforeStoring($this->event);
    }

    /**
     * @covers Imbo\EventListener\Imagick::readImageBlob
     * @covers Imbo\EventListener\Imagick::setImagick
     * @dataProvider hasImageBeenTransformed
     */
    public function testUpdatesModelBeforeSendingResponse($hasBeenTransformed) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->will($this->returnValue($hasBeenTransformed));

        $imagick = $this->getMock('Imagick');

        if ($hasBeenTransformed) {
            $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('image'));
            $image->expects($this->once())->method('setBlob')->with('image');
        } else {
            $imagick->expects($this->never())->method('getImageBlob');
            $image->expects($this->never())->method('setBlob');
        }

        $this->event->expects($this->once())->method('getArgument')->with('image')->will($this->returnValue($image));

        $this->listener->setImagick($imagick)
                       ->updateModel($this->event);
    }
}
