<?php
namespace ImboIntegrationTest\EventListener;

use Imbo\EventListener\ExifMetadata;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventListener\ExifMetadata
 */
class ExifMetadataTest extends TestCase {
    /**
     * @covers ::__construct
     * @covers ::populate
     */
    public function testCanGetPropertiesFromImageUnfiltered() : void {
        $listener = new ExifMetadata();

        $image = new Image();
        $image->setBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $properties = $listener->populate($event);

        $this->assertSame('SAMSUNG', $properties['exif:Make']);
        $this->assertSame('GT-I9100', $properties['exif:Model']);
        $this->assertSame('254/5', $properties['exif:GPSAltitude']);
        $this->assertSame('63/1, 40/1, 173857/3507', $properties['exif:GPSLatitude']);
        $this->assertSame('9/1, 5/1, 38109/12500', $properties['exif:GPSLongitude']);
    }

    /**
     * @covers ::__construct
     * @covers ::populate
     * @covers ::filterProperties
     */
    public function testCanGetPropertiesFromImageFiltered() : void {
        $listener = new ExifMetadata([
            'allowedTags' => [
                'exif:Flash',
                'exif:YResolution',
            ],
        ]);

        $image = new Image();
        $image->setBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $properties = $listener->populate($event);

        $this->assertSame('16', $properties['exif:Flash']);
        $this->assertSame('72/1', $properties['exif:YResolution']);
        $this->assertArrayNotHasKey('exif:GPSAltitude', $properties);
    }

    /**
     * @covers ::__construct
     * @covers ::populate
     * @covers ::parseProperties
     * @covers ::parseGpsCoordinate
     */
    public function testCanParseGpsValues() : void {
        $listener = new ExifMetadata();

        $image = new Image();
        $image->setBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $properties = $listener->populate($event);

        $this->assertEqualsWithDelta(9.0841802, $properties['gps:location'][0], 0.05);
        $this->assertEqualsWithDelta(63.680437300003, $properties['gps:location'][1], 0.05);
        $this->assertEqualsWithDelta(50.8, $properties['gps:altitude'], 0.05);
    }

    /**
     * @covers ::__construct
     * @covers ::populate
     * @covers ::save
     */
    public function testCanGetAndSaveProperties() : void {
        $listener = new ExifMetadata();
        $user = 'foobar';
        $imageIdentifier = 'imageId';

        $image = new Image();
        $image->setBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));
        $image->setImageIdentifier($imageIdentifier);

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->exactly(2))->method('getImage')->will($this->returnValue($image));
        $request->expects($this->once())->method('getUser')->will($this->returnValue($user));

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('updateMetadata')->with(
            $this->equalTo($user),
            $this->equalTo($imageIdentifier),
            $this->arrayHasKey('gps:location')
        );

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->exactly(2))->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getDatabase')->will($this->returnValue($database));

        $properties = $listener->populate($event);

        $this->assertSame('SAMSUNG', $properties['exif:Make']);
        $this->assertSame('GT-I9100', $properties['exif:Model']);

        $listener->save($event);
    }
}
