<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Http\Response\Formatter;

use Imbo\Http\Response\Formatter\XML,
    DOMDocument,
    DOMXpath,
    DateTime;

/**
 * @covers Imbo\Http\Response\Formatter\XML
 * @group unit
 * @group http
 * @group formatters
 */
class XMLTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var XML
     */
    private $formatter;

    /**
     * @var Imbo\Helpers\DateFormatter
     */
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
        $date = new DateTime($formattedDate);

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getHttpCode')->will($this->returnValue(404));
        $model->expects($this->once())->method('getErrorMessage')->will($this->returnValue('Public key not found'));
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImboErrorCode')->will($this->returnValue(100));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('identifier'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        $this->assertXPathMatches('//error/code[.=404]', $xml, 'Missing HTTP status code');
        $this->assertXPathMatches('//error/message[.="Public key not found"]', $xml, 'Missing error message');
        $this->assertXPathMatches('//error/date[.="' . $formattedDate . '"]', $xml, 'Missing date');
        $this->assertXPathMatches('//error/imboErrorCode[.=100]', $xml, 'Missing imbo error code');
        $this->assertXPathMatches('/imbo/imageIdentifier[.="identifier"]', $xml, 'Missing image identifier');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\XML::formatError
     */
    public function testCanFormatAnErrorModelWhenNoImageIdentifierExists() {
        $date = new DateTime();

        $model = $this->getMock('Imbo\Model\Error');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(null));

        $xml = $this->formatter->format($model);

        $this->assertXPathDoesNotMatch('//imageIdentifier', $xml, 'Image identifier should not be present');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatStatus
     */
    public function testCanFormatAStatusModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->getMock('Imbo\Model\Status');
        $model->expects($this->once())->method('getDate')->will($this->returnValue($date));
        $model->expects($this->once())->method('getDatabaseStatus')->will($this->returnValue(true));
        $model->expects($this->once())->method('getStorageStatus')->will($this->returnValue(false));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        $this->assertXPathMatches('//status/database[.=1]', $xml, 'Missing database status');
        $this->assertXPathMatches('//status/storage[.=0]', $xml, 'Missing storage status');
        $this->assertXPathMatches('//status/date[.="' . $formattedDate . '"]', $xml, 'Missing date');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatUser
     */
    public function testCanFormatAUserModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';
        $date = new DateTime($formattedDate);

        $model = $this->getMock('Imbo\Model\User');
        $model->expects($this->once())->method('getLastModified')->will($this->returnValue($date));
        $model->expects($this->once())->method('getNumImages')->will($this->returnValue(123));
        $model->expects($this->once())->method('getUserId')->will($this->returnValue('christer'));

        $this->dateFormatter->expects($this->once())->method('formatDate')->with($date)->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        $this->assertXPathMatches('//user/id[.="christer"]', $xml, 'Missing user');
        $this->assertXPathMatches('//user/numImages[.="123"]', $xml, 'Missing num key');
        $this->assertXPathMatches('//user/lastModified[.="' . $formattedDate . '"]', $xml, 'Missing date');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModel() {
        $formattedDate = 'Wed, 30 Jan 2013 10:53:11 GMT';

        $date = new DateTime();

        $addedDate = $date;
        $updatedDate = $date;
        $user = 'christer';
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
        $image->expects($this->once())->method('getUser')->will($this->returnValue($user));
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
        $model->expects($this->once())->method('getHits')->will($this->returnValue(100));
        $model->expects($this->once())->method('getPage')->will($this->returnValue(2));
        $model->expects($this->once())->method('getLimit')->will($this->returnValue(20));
        $model->expects($this->once())->method('getCount')->will($this->returnValue(1));

        $this->dateFormatter->expects($this->any())->method('formatDate')->with($this->isInstanceOf('DateTime'))->will($this->returnValue($formattedDate));

        $xml = $this->formatter->format($model);

        // Check the search data
        foreach (array('hits' => 100, 'page' => 2, 'limit' => 20, 'count' => 1) as $tag => $value) {
            $this->assertXPathMatches('/imbo/search/'. $tag . '[.="' . $value . '"]', $xml, $tag . ' is not correct');
        }

        // Check image
        foreach (array(
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'checksum' => $checksum,
            'mime' => $mimeType,
            'extension' => $extension,
            'added' => $formattedDate,
            'updated' => $formattedDate,
            'size' => (string) $filesize, // Need to case to string since 100 !== "100"
            'width' => (string) $width,
            'height' => (string) $height,
        ) as $tag => $value) {
            $this->assertXPathMatches('/imbo/images/image/'. $tag . '[.="' . $value . '"]', $xml);
        }

        // Check metadata
        foreach ($metadata as $key => $value) {
            $this->assertXPathMatches(
                '//images/image/metadata/tag[@key="' . $key . '" and .="' . $value . '"]',
                $xml,
                'element "tag" with value "' . $value . '" and attr key="' . $key . '" not found'
            );
        }
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadataSet() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue(null));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue(new DateTime()));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue(new DateTime()));

        $images = array($image);
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $xml = $this->formatter->format($model);

        $this->assertXPathDoesNotMatch('//metadata', $xml, 'Image model without metadata contained metadata');
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoMetadata() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMetadata')->will($this->returnValue(array()));
        $image->expects($this->once())->method('getAddedDate')->will($this->returnValue(new DateTime()));
        $image->expects($this->once())->method('getUpdatedDate')->will($this->returnValue(new DateTime()));

        $images = array($image);
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue($images));

        $xml = $this->formatter->format($model);

        $this->assertXPathMatches('//metadata[.=""]', $xml);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatImages
     */
    public function testCanFormatAnImagesModelWithNoImages() {
        $model = $this->getMock('Imbo\Model\Images');
        $model->expects($this->once())->method('getImages')->will($this->returnValue(array()));

        $xml = $this->formatter->format($model);

        $this->assertXPathDoesNotMatch('//image', $xml);
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
            $this->assertXPathMatches('/imbo/metadata/tag[@key="' . $key . '" and .="' . $value . '"]', $xml);
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

        $this->assertXPathMatches('//metadata[.=""]', $xml);
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
            $this->assertXPathMatches('/imbo/' . $key . '[.="' . $value . '"]', $xml);
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

        $this->assertXPathMatches('/imbo/key/sub/subsub[.="value"]', $xml);
        $this->assertXPathMatches('/imbo/key2[.="value"]', $xml);
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     * @covers Imbo\Http\Response\Formatter\XML::formatArrayModel
     */
    public function testCanFormatAnEmptyArrayModel() {
        $model = $this->getMock('Imbo\Model\ArrayModel');
        $model->expects($this->once())->method('getData')->will($this->returnValue(array()));

        $xml = $this->formatter->format($model);

        $this->assertXPathMatches('//imbo[.=""]', $xml);
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

    /**
     * Assert that an xpath query have matches for the given XML tree
     *
     * @param  string $query XPath query
     * @param  string $xml XML document
     * @param  string $message
     */
    protected function assertXPathMatches($query, $xml, $message = '') {
        $matches = $this->queryXPath($query, $xml);
        $this->assertNotNull($matches->item(0), $message);
    }

    /**
     * Assert that an xpath query have no matches for the given XML tree
     *
     * @param  string $query XPath query
     * @param  string $xml XML document
     * @param  string $message
     */
    protected function assertXPathDoesNotMatch($query, $xml, $message = '') {
        $matches = $this->queryXPath($query, $xml);

        $this->assertNull($matches->item(0), $message);
    }

    /**
     * Run an XPath query on the given DOM document, returning matched elements
     *
     * @param  string $query XPath query
     * @param  string $xml XML document
     * @return DOMNodeList|null
     */
    protected function queryXPath($query, $xml) {
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $xpath = new DOMXpath($doc);
        $elements = $xpath->query($query);

        return $elements;
    }
}
