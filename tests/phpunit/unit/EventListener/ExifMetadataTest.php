<?php
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ExifMetadata;
use Imbo\Exception\RuntimeException;

/**
 * @coversDefaultClass Imbo\EventListener\ExifMetadata
 */
class ExifMetadataTest extends ListenerTests {
    /**
     * @var ExifMetadata
     */
    private $listener;

    public function setUp() : void {
        $this->listener = new ExifMetadata();
    }

    protected function getListener() : ExifMetadata {
        return $this->listener;
    }

    public function getFilterData() : array {
        $data = [
            'date:create' => '2013-11-26T19:42:48+01:00',
            'date:modify' => '2013-11-26T19:42:48+01:00',
            'exif:Flash' => '16',
            'exif:GPSAltitude' => '254/5',
            'exif:GPSAltitudeRef' => '0',
            'exif:GPSDateStamp' => '2012:06:09',
            'exif:GPSInfo' => '730',
            'exif:GPSLatitude' => '63/1, 40/1, 173857/3507',
            'exif:GPSLatitudeRef' => 'N',
            'exif:GPSLongitude' => '9/1, 5/1, 38109/12500',
            'exif:GPSLongitudeRef' => 'E',
            'exif:GPSProcessingMethod' => '65, 83, 67, 73, 73, 0, 0, 0',
            'exif:GPSTimeStamp' => '17/1, 17/1, 51/1',
            'exif:GPSVersionID' => '2, 2, 0, 0',
            'exif:Make' => 'SAMSUNG',
            'exif:Model' => 'GT-I9100',
            'jpeg:colorspace' => '2',
            'jpeg:sampling-factor' => '2x2,1x1,1x1',
        ];
        return [
            'all values' => [
                'data' => $data,
                'tags' => ['*'],
                'expectedData' => array_merge($data, [
                    'gps:location' => [9.0841802, 63.680437300003],
                    'gps:altitude' => 50.8,
                ]),

            ],
            'specific value' => [
                'data' => $data,
                'tags' => ['exif:Make'],
                'expectedData' => [
                    'exif:Make' => 'SAMSUNG',
                ],
            ],
            'default' => [
                'data' => $data,
                'tags' => null,
                'expectedData' => [
                    'exif:Flash' => '16',
                    'exif:GPSAltitude' => '254/5',
                    'exif:GPSAltitudeRef' => '0',
                    'exif:GPSDateStamp' => '2012:06:09',
                    'exif:GPSInfo' => '730',
                    'exif:GPSLatitude' => '63/1, 40/1, 173857/3507',
                    'exif:GPSLatitudeRef' => 'N',
                    'exif:GPSLongitude' => '9/1, 5/1, 38109/12500',
                    'exif:GPSLongitudeRef' => 'E',
                    'exif:GPSProcessingMethod' => '65, 83, 67, 73, 73, 0, 0, 0',
                    'exif:GPSTimeStamp' => '17/1, 17/1, 51/1',
                    'exif:GPSVersionID' => '2, 2, 0, 0',
                    'exif:Make' => 'SAMSUNG',
                    'exif:Model' => 'GT-I9100',
                    'gps:location' => [9.0841802, 63.680437300003],
                    'gps:altitude' => 50.8,
                ],
            ],
            'mixed' => [
                'data' => $data,
                'tags' => ['exif:Model', 'jpeg:*', 'date:*'],
                'expectedData' => [
                    'date:create' => '2013-11-26T19:42:48+01:00',
                    'date:modify' => '2013-11-26T19:42:48+01:00',
                    'exif:Model' => 'GT-I9100',
                    'jpeg:colorspace' => '2',
                    'jpeg:sampling-factor' => '2x2,1x1,1x1',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFilterData
     * @covers ::setImagick
     * @covers ::getImagick
     * @covers ::populate
     * @covers ::save
     * @covers ::filterProperties
     * @covers ::parseProperties
     */
    public function testCanFilterData(array $data, ?array $tags, array $expectedData) : void {
        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = 'blob';

        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $image->expects($this->once())->method('getBlob')->will($this->returnValue($blob));

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('readImageBlob')->will($this->returnValue($blob));
        $imagick->expects($this->once())->method('getImageProperties')->will($this->returnValue($data));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $request->expects($this->any())->method('getImage')->will($this->returnValue($image));

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('updateMetadata')->with($user, $imageIdentifier, $expectedData);

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->exactly(2))->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getDatabase')->will($this->returnValue($database));

        $listener = new ExifMetadata(['allowedTags' => $tags]);
        $listener->setImagick($imagick);
        $listener->populate($event);
        $listener->save($event);
    }

    /**
     * @covers ::save
     */
    public function testWillDeleteImageWhenUpdatingMetadataFails() : void {
        $databaseException = $this->createMock('Imbo\Exception\DatabaseException');
        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('updateMetadata')->with('user', 'imageidentifier', [])->will($this->throwException($databaseException));
        $database->expects($this->once())->method('deleteImage')->with('user', 'imageidentifier');

        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageidentifier'));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getDatabase')->will($this->returnValue($database));
        $this->expectExceptionObject(new RuntimeException('Could not store EXIF-metadata', 500));
        $this->listener->save($event);
    }

    /**
     * @covers ::getImagick
     */
    public function testCanInstantiateImagickItself() : void {
        $this->assertInstanceOf('Imagick', $this->listener->getImagick());
    }
}
