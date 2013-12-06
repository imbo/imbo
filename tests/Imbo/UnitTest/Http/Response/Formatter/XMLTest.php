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

use Imbo\Http\Response\Formatter\XML;

/**
 * @group unit
 */
class XMLTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var XML
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
        $this->formatter = new XML($this->dateFormatter);
    }

    /**
     * Tear down the formatter
     */
    public function tearDown() {
        $this->dateFormatter;
        $this->formatter = null;
    }

    /**
     * @covers Imbo\Http\Response\Formatter\XML::getContentType
     */
    public function testReturnsCurrectContentType() {
        $this->assertSame('application/xml', $this->formatter->getContentType());
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatError
     */
    public function testCanFormatAnErrorModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getHttpCode')->will($this->returnValue(404));
        $model->expects($this->once())->method('getErrorMessage')->will($this->returnValue('Public key not found'));
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImboErrorCode')->will($this->returnValue(100));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('identifier'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'code', 'content' => '404', 'parent' => array('tag' => 'error')), $xml, 'Missing HTTP status code', false);
        $this->assertTag(array('tag' => 'message', 'content' => 'Public key not found', 'parent' => array('tag' => 'error')), $xml, 'Missing error message', false);
        $this->assertTag(array('tag' => 'date', 'content' => $formattedDate, 'parent' => array('tag' => 'error')), $xml, 'Missing date', false);
        $this->assertTag(array('tag' => 'imboErrorCode', 'content' => '100', 'parent' => array('tag' => 'error')), $xml, 'Missing imbo error code', false);
        $this->assertTag(array('tag' => 'imageIdentifier', 'content' => 'identifier', 'parent' => array('tag' => 'imbo')), $xml, 'Missing image identifier', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\XML::formatError
     */
    public function testCanFormatAnErrorModelWhenNoImageIdentifierExists() {
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(null));

        $xml = $this->formatter->format($model);

        $this->assertNotTag(array('tag' => 'imageIdentifier'), $xml, 'Image identifier should not be present', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatStatus
     */
    public function testCanFormatAStatusModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\Status');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getDatabaseStatus')->will($this->returnValue(true));
        $model->expects($this->once())->method('getStorageStatus')->will($this->returnValue(false));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'database', 'content' => '1', 'parent' => array('tag' => 'status')), $xml, 'Missing database status', false);
        $this->assertTag(array('tag' => 'storage', 'content' => '0', 'parent' => array('tag' => 'status')), $xml, 'Missing storage status', false);
        $this->assertTag(array('tag' => 'date', 'content' => $formattedDate, 'parent' => array('tag' => 'status')), $xml, 'Missing date', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatUser
     */
    public function testCanFormatAUserModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = $this->getMock('DateTime');

        $model = $this->getMock('Imbo\Model\User');
        $model->expects($this->once())->method('getLastModified')->will($this->returnValue($date));
        $model->expects($this->once())->method('getNumImages')->will($this->returnValue(123));
        $model->expects($this->once())->method('getPublicKey')->will($this->returnValue('christer'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'publicKey', 'content' => 'christer', 'parent' => array('tag' => 'user')), $xml, 'Missing public key', false);
        $this->assertTag(array('tag' => 'numImages', 'content' => '123', 'parent' => array('tag' => 'user')), $xml, 'Missing num images', false);
        $this->assertTag(array('tag' => 'lastModified', 'content' => $formattedDate, 'parent' => array('tag' => 'user')), $xml, 'Missing date', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
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

        $xml = $this->formatter->format($model);

        foreach (array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
            'checksum' => $checksum,
            'mime' => $mimeType,
            'extension' => $extension,
            'added' => $formattedDate,
            'updated' => $formattedDate,
            'size' => (string) $filesize,
            'width' => (string) $width,
            'height' => (string) $height,
        ) as $tag => $value) {
            $this->assertTag(
                array(
                    'tag' => $tag,
                    'content' => $value,
                    'parent' => array(
                        'tag' => 'image',
                        'parent' => array(
                            'tag' => 'images',
                        ),
                    ),
                ), $xml, '', false);
        }

        foreach ($metadata as $key => $value) {
            $this->assertTag(
                array(
                    'tag' => 'tag',
                    'attributes' => array(
                        'key' => $key,
                    ),
                    'content' => $value,
                    'parent' => array(
                        'tag' => 'metadata',
                        'parent' => array(
                            'tag' => 'image',
                            'parent' => array(
                                'tag' => 'images',
                            ),
                        ),
                    ),
                ), $xml, '', false);
        }
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadataSet() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue(null));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue($this->getMock('DateTime')));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue($this->getMock('DateTime')));

        $images = array($image);
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $xml = $this->formatter->format($model);

        $this->assertNotTag(array('tag' => 'metadata'), $xml, '', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadata() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue(array()));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue($this->getMock('DateTime')));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue($this->getMock('DateTime')));

        $images = array($image);
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $xml = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'metadata', 'content' => ''), $xml, '', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoImages() {
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue(array()));

        $xml = $this->formatter->format($model);

        $this->assertNotTag(array('tag' => 'image'), $xml, '', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatMetadata
     */
    public function testCanFormatAMetadataModel() {
        $metadata = array(
            'some key' => 'some value',
            'some other key' => 'some other value',
        );
        $model = $this->getMock('Imbo\Model\Metadata');
        $model->expects($this->once())->method('getData')->will($this->returnValue($metadata));

        $xml = $this->formatter->format($model);

        foreach ($metadata as $key => $value) {
            $this->assertTag(
                array(
                    'tag' => 'tag',
                    'attributes' => array(
                        'key' => $key,
                    ),
                    'content' => $value,
                    'parent' => array(
                        'tag' => 'metadata',
                        'parent' => array(
                            'tag' => 'imbo',
                        ),
                    ),
                ), $xml, '', false);
        }
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatMetadata
     */
    public function testCanFormatAMetadataModelWithNoMetadata() {
        $model = $this->getMock('Imbo\Model\Metadata');
        $model->expects($this->once())->method('getData')->will($this->returnValue(array()));

        $xml = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'metadata', 'content' => ''), $xml, '', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatArrayModel
     */
    public function testCanFormatAnArrayModel() {
        $data = array(
            'key1' => 'value1',
            'key2' => 'value2',
        );
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue($data));

        $xml = $this->formatter->format($model);

        foreach ($data as $key => $value) {
            $this->assertTag(
                array(
                    'tag' => $key,
                    'content' => $value,
                    'parent' => array(
                        'tag' => 'imbo',
                    ),
                ), $xml, '', false);
        }
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatArrayModel
     * @covers Imbo\Http\Response\Formatter\XML::formatArray
     */
    public function testCanFormatArrayModelWithLists() {
        $data = array(
            'key' => array(1, 2, 3),
        );
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue($data));

        $xml = $this->formatter->format($model);

        $this->assertContains('<imbo><key><list><value>1</value><value>2</value><value>3</value></list></key></imbo>', $xml);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatArrayModel
     * @covers Imbo\Http\Response\Formatter\XML::formatArray
     */
    public function testCanFormatAnArrayModelWithNestedArrays() {
        $data = array(
            'key' => array(
                'sub' => array(
                    'subsub' => 'value',
                ),
            ),
            'key2' => 'value',
        );
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue($data));

        $xml = $this->formatter->format($model);

        $this->assertTag(array(
            'tag' => 'subsub',
            'content' => 'value',
            'parent' => array(
                'tag' => 'sub',
                'parent' => array(
                    'tag' => 'key',
                    'parent' => array(
                        'tag' => 'imbo',
                    ),
                ),
            ),
        ), $xml, '', false);

        $this->assertTag(array(
            'tag' => 'key2',
            'content' => 'value',
            'parent' => array(
                'tag' => 'imbo',
            ),
        ), $xml, '', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatArrayModel
     */
    public function testCanFormatAnEmptyArrayModel() {
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue(array()));

        $xml = $this->formatter->format($model);

        $this->assertTag(array('tag' => 'imbo', 'content' => ''), $xml, '', false);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatListModel
     */
    public function testCanFormatAListModel() {
        $list = array('foo', 'bar');
        $container = 'users';
        $entry = 'user';

        $model = $this->getMock('Imbo\Model\ListModel');
        $model->expects($this->once())->method('getList')->will($this->returnValue($list));
        $model->expects($this->once())->method('getContainer')->will($this->returnValue($container));
        $model->expects($this->once())->method('getEntry')->will($this->returnValue($entry));

        $this->assertContains('<imbo><users><user>foo</user><user>bar</user></users></imbo>', $this->formatter->format($model));
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatListModel
     */
    public function testCanFormatAnEmptyListModel() {
        $list = array();
        $container = 'users';
        $entry = 'user';

        $model = $this->getMock('Imbo\Model\ListModel');
        $model->expects($this->once())->method('getList')->will($this->returnValue($list));
        $model->expects($this->once())->method('getContainer')->will($this->returnValue($container));
        $model->expects($this->once())->method('getEntry')->will($this->returnValue($entry));

        $this->assertContains('<imbo><users></users></imbo>', $this->formatter->format($model));
    }
}
