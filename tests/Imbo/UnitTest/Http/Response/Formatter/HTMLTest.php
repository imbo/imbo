<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Response\Formatter;

use Imbo\Http\Response\Formatter\HTML;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class HTMLTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var HTML
     */
    private $formatter;

    private $dateFormatter;

    /**
     * Set up the formatter
     *
     * @covers Imbo\Http\Response\Formatter\Formatter::__construct
     */
    public function setUp() {
        $this->dateFormatter = $this->getMock('Imbo\Helpers\DateFormatter');
        $this->formatter = new HTML($this->dateFormatter);
    }

    /**
     * Tear down the formatter
     */
    public function tearDown() {
        $this->dateFormatter;
        $this->formatter = null;
    }

    /**
     * @covers Imbo\Http\Response\Formatter\HTML::getContentType
     */
    public function testReturnsCurrectContentType() {
        $this->assertSame('text/html', $this->formatter->getContentType());
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatError
     * @covers Imbo\Http\Response\Formatter\HTML::getDocument
     */
    public function testCanFormatAnErrorModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getHttpCode')->will($this->returnValue(404));
        $model->expects($this->once())->method('getErrorMessage')->will($this->returnValue('Unknown public key'));
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImboErrorCode')->will($this->returnValue(100));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('identifier'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'title', 'content' => 'Error'), $html, 'Title is not correct');
        $this->assertTag(array('tag' => 'h1', 'content' => 'Error'), $html, 'Title is not correct');

        $this->assertTag(array('tag' => 'dd', 'content' => '404'), $html, 'Missing HTTP status code');
        $this->assertTag(array('tag' => 'dd', 'content' => 'Unknown public key'), $html, 'Missing error message');
        $this->assertTag(array('tag' => 'dd', 'content' => $formattedDate), $html, 'Missing date');
        $this->assertTag(array('tag' => 'dd', 'content' => '100'), $html, 'Missing imbo error code');
        $this->assertTag(array('tag' => 'dd', 'content' => 'identifier'), $html, 'Missing image identifier');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatStatus
     */
    public function testCanFormatAStatusModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\Status');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getDatabaseStatus')->will($this->returnValue(true));
        $model->expects($this->once())->method('getStorageStatus')->will($this->returnValue(false));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'title', 'content' => 'Status'), $html, 'Title is not correct');
        $this->assertTag(array('tag' => 'h1', 'content' => 'Status'), $html, 'Title is not correct');

        $this->assertTag(array('tag' => 'dd', 'content' => $formattedDate), $html, 'Missing date');
        $this->assertTag(array('tag' => 'dd', 'attributes' => array('class' => 'database'), 'content' => '1'), $html, 'Missing database status result');
        $this->assertTag(array('tag' => 'dd', 'attributes' => array('class' => 'storage'), 'content' => '0'), $html, 'Missing storage status result');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatUser
     */
    public function testCanFormatAUserModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\User');
        $model->expects($this->once())->method('getLastModified')->will($this->returnValue($date));
        $model->expects($this->once())->method('getNumImages')->will($this->returnValue(123));
        $model->expects($this->once())->method('getPublicKey')->will($this->returnValue('christer'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'title', 'content' => 'User'), $html, 'Title is not correct');
        $this->assertTag(array('tag' => 'h1', 'content' => 'User'), $html, 'Title is not correct');

        $this->assertTag(array('tag' => 'dd', 'content' => $formattedDate), $html, 'Missing date');
        $this->assertTag(array('tag' => 'dd', 'content' => '123'), $html, 'Missing number of images');
        $this->assertTag(array('tag' => 'dd', 'content' => 'christer'), $html, 'Missing public key');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatImages
     */
    public function testCanFormatAnImagesModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';

        $addedDate = $this->getMock('DateTime');
        $updatedDate = $this->getMock('DateTime');
        $publicKey = 'christer';
        $imageIdentifier = 'identifier';
        $checksum = 'checksum';
        $extension = 'png';
        $mimeType = 'image/png';
        $filesize = 123123;
        $width = 800;
        $height = 600;
        $metadata = array(
            'some key' => 'some value',
            'some other key' => 'some other value',
        );

        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $image->expects($this->once())->method('getChecksum')->will($this->returnValue($checksum));
        $image->expects($this->once())->method('getExtension')->will($this->returnValue($extension));
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue($mimeType));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue($addedDate));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue($updatedDate));
        $image->expects($this->once())->method('getFilesize')->will($this->returnValue($filesize));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue($height));
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue($metadata));

        $images = array($image);
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $this->dateFormatter->expects($this->any())->method('formatDate')->with($this->isInstanceOf('DateTime'))->will($this->returnValue($formattedDate));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'title', 'content' => 'Images'), $html, 'Title is not correct');
        $this->assertTag(array('tag' => 'h1', 'content' => 'Images'), $html, 'Title is not correct');

        $this->assertTag(array('tag' => 'dd', 'content' => $formattedDate), $html, 'Missing date');
        $this->assertTag(array('tag' => 'dd', 'content' => $publicKey), $html, 'Missing public key');
        $this->assertTag(array('tag' => 'dd', 'content' => $imageIdentifier), $html, 'Missing image identifier');
        $this->assertTag(array('tag' => 'dd', 'content' => $checksum), $html, 'Missing checksum');
        $this->assertTag(array('tag' => 'dd', 'content' => $extension), $html, 'Missing extension');
        $this->assertTag(array('tag' => 'dd', 'content' => $mimeType), $html, 'Missing mime type');
        $this->assertTag(array('tag' => 'dd', 'content' => (string) $filesize), $html, 'Missing filesize');
        $this->assertTag(array('tag' => 'dd', 'content' => (string) $width), $html, 'Missing width');
        $this->assertTag(array('tag' => 'dd', 'content' => (string) $height), $html, 'Missing height');

        $this->assertTag(array('tag' => 'dt', 'content' => 'Metadata'), $html, 'Missing metadata');
        $this->assertTag(array('tag' => 'dt', 'content' => 'some key'), $html, 'Missing metadata key');
        $this->assertTag(array('tag' => 'dt', 'content' => 'some other key'), $html, 'Missing metadata key');
        $this->assertTag(array('tag' => 'dd', 'content' => 'some value'), $html, 'Missing metadata value');
        $this->assertTag(array('tag' => 'dd', 'content' => 'some other value'), $html, 'Missing metadata value');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadata() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue(array()));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue($this->getMock('DateTime')));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue($this->getMock('DateTime')));

        $images = array($image);
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $html = $this->formatter->format($model);

        $this->assertNotTag(array('tag' => 'dt', 'content' => 'Metadata'), $html, 'Metadata should not be present');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoImages() {
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue(array()));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'p', 'content' => 'No images'), $html, 'Metadata should not be present');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatMetadata
     */
    public function testCanFormatAMetadataModel() {
        $model = $this->getMock('Imbo\Model\Metadata');
        $model->expects($this->once())->method('getData')->will($this->returnValue(array(
            'some key' => 'some value',
            'some other key' => 'some other value',
        )));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'title', 'content' => 'Metadata'), $html, 'Title is not correct');
        $this->assertTag(array('tag' => 'h1', 'content' => 'Metadata'), $html, 'Title is not correct');

        $this->assertTag(array('tag' => 'dt', 'content' => 'some key'), $html, 'Missing metadata key');
        $this->assertTag(array('tag' => 'dt', 'content' => 'some other key'), $html, 'Missing metadata key');
        $this->assertTag(array('tag' => 'dd', 'content' => 'some value'), $html, 'Missing metadata value');
        $this->assertTag(array('tag' => 'dd', 'content' => 'some other value'), $html, 'Missing metadata value');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\HTML::formatMetadata
     */
    public function testCanFormatAMetadataModelWithNoMetadata() {
        $model = $this->getMock('Imbo\Model\Metadata');
        $model->expects($this->once())->method('getData')->will($this->returnValue(array()));

        $html = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'p', 'content' => 'No metadata'), $html, 'Metadata should not be present');
    }
}
