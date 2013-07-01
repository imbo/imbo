<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\RuntimeException,
    Imbo\Exception\DatabaseException,
    Imagick;

/**
 * Exif metadata event listener
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class ExifMetadata implements ListenerInterface {
    /**
     * An array of allowed tags
     *
     * @var array
     */
    protected $allowedTags = array();

    /**
     * Exif properties
     *
     * @var array
     */
    protected $properties = array();

    /**
     * Class constructor
     *
     * @param array $allowedTags An array of tags to use as metadata, if present
     */
    public function __construct(array $allowedTags = array()) {
        $this->allowedTags = $allowedTags;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            // High priority to prevent other listeners from stripping EXIF-data
            new ListenerDefinition('image.put', array($this, 'populate'), 45),

            // When image has been inserted to database, insert metadata
            new ListenerDefinition('db.image.insert', array($this, 'save'), -100),
        );
    }

    /**
     * Read exif data from incoming image
     *
     * @param EventInterface $event The triggered event
     */
    public function populate(EventInterface $event) {
        $image = $event->getRequest()->getImage();

        // Get EXIF-properties from image
        $imagick = new Imagick();
        $imagick->readImageBlob($image->getBlob());
        $properties = $imagick->getImageProperties();

        // Filter and parse properties
        $properties = $this->filterProperties($properties);
        $properties = $this->parseProperties($properties);

        // Set properties to save on successful image insertion
        $this->properties = $properties;

        return $properties;
    }

    /**
     * Save metadata to database
     *
     * @param  EventInterface $event The triggered event
     * @throws RuntimeException
     */
    public function save(EventInterface $event) {
        $request = $event->getRequest();
        $database = $event->getDatabase();

        try {
            $database->updateMetadata(
                $request->getPublicKey(),
                $request->getImage()->getChecksum(),
                $this->properties
            );
        } catch (DatabaseException $e) {
            throw new RuntimeException('Could not store EXIF-metadata: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Filter out any unwanted properties
     *
     * @param array $properties An array of properties to filter
     * @return array A filtered array of properties
     */
    protected function filterProperties(array $properties) {
        if (empty($this->allowedTags)) {
            return $properties;
        }

        return array_intersect_key(
            $properties,
            array_flip($this->allowedTags)
        );
    }

    /**
     * Parse an array of properties into a storable format
     *
     * @param array $properties An array of properties to parse
     * @return array Parsed array of properties
     */
    protected function parseProperties(array $properties) {
        if (isset($properties['exif:GPSLatitude']) &&
            isset($properties['exif:GPSLongitude'])) {

            // We store coordinates in GeoJSON-format (lng/lat)
            $properties['gps:location'] = array(
                $this->parseGpsCoordinate(
                    $properties['exif:GPSLongitude'],
                    $properties['exif:GPSLongitudeRef']
                ),
                $this->parseGpsCoordinate(
                    $properties['exif:GPSLatitude'],
                    $properties['exif:GPSLatitudeRef']
                ),
            );
        }

        if (isset($properties['exif:GPSAltitude'])) {
            $alt = explode('/', $properties['exif:GPSAltitude'], 2);
            $properties['gps:altitude'] = $alt[0] / (int) $alt[1];
        }

        return $properties;
    }

    /**
     * Parse GPS coordinates in hours/minutes/seconds-format to decimal degrees
     *
     * @param  string $coordinate Coordinate in hours/minutes/seconds format
     * @param  string $hemisphere Hemisphere identifier (N, E, S, W)
     * @return float
     */
    protected function parseGpsCoordinate($coordinate, $hemisphere) {
        $coordinates = explode(' ', $coordinate);

        for ($i = 0; $i < 3; $i++) {
            $part = explode('/', $coordinates[$i]);
            if (count($part) == 1) {
                $coordinates[$i] = $part[0];
            } else if (count($part) == 2) {
                $coordinates[$i] = floatval($part[0]) / floatval($part[1]);
            } else {
                $coordinates[$i] = 0;
            }
        }

        $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
        $degrees = ($coordinates[0] + ($coordinates[1] / 60) + ($coordinates[2] / 3600));

        return $sign * $degrees;
    }
}
