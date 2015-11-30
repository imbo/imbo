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
    Imbo\Exception\DatabaseException;

/**
 * Exif metadata event listener
 *
 * This listener will look for properties stored in the image, and store them as metadata in Imbo.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ExifMetadata implements ListenerInterface {
    /**
     * An array of allowed tags
     *
     * @var array
     */
    protected $allowedTags = [
        'exif:*',
    ];

    /**
     * Exif properties
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Imagick instance
     *
     * @var \Imagick
     */
    private $imagick;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     */
    public function __construct(array $params = null) {
        if ($params && isset($params['allowedTags'])) {
            $this->allowedTags = $params['allowedTags'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // High priority to prevent other listeners from stripping EXIF-data
            'images.post' => ['populate' => 45],

            // When image has been inserted to database, insert metadata
            'db.image.insert' => ['save' => -100],
        ];
    }

    /**
     * Set an Imagick instance
     *
     * @param \Imagick $imagick An instance of Imagick
     * @return self
     */
    public function setImagick(\Imagick $imagick) {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * Get an Imagick instance
     *
     * @return \Imagick
     */
    public function getImagick() {
        if ($this->imagick === null) {
            $this->imagick = new \Imagick();
        }

        return $this->imagick;
    }

    /**
     * Read exif data from incoming image
     *
     * @param EventInterface $event The triggered event
     * @return array
     */
    public function populate(EventInterface $event) {
        $image = $event->getRequest()->getImage();

        // Get EXIF-properties from image
        $imagick = $this->getImagick();
        $imagick->readImageBlob($image->getBlob());
        $properties = $imagick->getImageProperties();

        // Fix trailing spaces
        foreach ($properties as $key => $value) {
            unset($properties[$key]);
            $properties[trim($key)] = $value;
        }

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

        $user = $request->getUser();
        $imageIdentifier = $request->getImage()->getImageIdentifier();

        try {
            $database->updateMetadata(
                $user,
                $imageIdentifier,
                $this->properties
            );
        } catch (DatabaseException $e) {
            $database->deleteImage($user, $imageIdentifier);

            throw new RuntimeException('Could not store EXIF-metadata', 500);
        }
    }

    /**
     * Filter out any unwanted properties
     *
     * @param array $properties An array of properties to filter
     * @return array A filtered array of properties
     */
    protected function filterProperties(array $properties) {
        $tags = array_fill_keys($this->allowedTags, 1);

        if (empty($tags) || isset($tags['*'])) {
            return $properties;
        }

        $filtered = [];

        foreach ($properties as $key => $value) {
            if (isset($tags[$key])) {
                $filtered[$key] = $value;
                continue;
            }

            if (($pos = strpos($key, ':')) !== false) {
                $namespace = substr($key, 0, $pos);

                if (isset($tags[$namespace . ':*'])) {
                    $filtered[$key] = $value;
                    continue;
                }
            }
        }

        return $filtered;
    }

    /**
     * Parse an array of properties into a storable format
     *
     * @param array $rawProperties An array of properties to parse
     * @return array Parsed array of properties
     */
    protected function parseProperties(array $rawProperties) {
        if (isset($rawProperties['exif:GPSLatitude']) &&
            isset($rawProperties['exif:GPSLongitude'])) {

            // We store coordinates in GeoJSON-format (lng/lat)
            $rawProperties['gps:location'] = [
                $this->parseGpsCoordinate(
                    $rawProperties['exif:GPSLongitude'],
                    $rawProperties['exif:GPSLongitudeRef']
                ),
                $this->parseGpsCoordinate(
                    $rawProperties['exif:GPSLatitude'],
                    $rawProperties['exif:GPSLatitudeRef']
                ),
            ];
        }

        if (isset($rawProperties['exif:GPSAltitude'])) {
            $alt = explode('/', $rawProperties['exif:GPSAltitude'], 2);
            $rawProperties['gps:altitude'] = $alt[0] / (int) $alt[1];
        }

        $properties = [];
        foreach ($rawProperties as $key => $val) {
            // Get rid of dots in property names
            $key = str_replace('.', ':', $key);

            // Replace underscore with dash for png properties
            if (substr($key, 0, 3) === 'png') {
                $key = str_replace('_', '-', $key);
            }

            $properties[$key] = $val;
        }

        return $properties;
    }

    /**
     * Parse GPS coordinates in hours/minutes/seconds-format to decimal degrees
     *
     * @param string $coordinate Coordinate in hours/minutes/seconds format
     * @param string $hemisphere Hemisphere identifier (N, E, S, W)
     * @return float
     */
    protected function parseGpsCoordinate($coordinate, $hemisphere) {
        $coordinates = explode(' ', $coordinate);

        for ($i = 0; $i < 3; $i++) {
            $part = explode('/', $coordinates[$i]);
            $parts = count($part);

            if ($parts === 1) {
                $coordinates[$i] = $part[0];
            } else if ($parts === 2) {
                $coordinates[$i] = floatval($part[0]) / floatval($part[1]);
            } else {
                $coordinates[$i] = 0;
            }
        }

        $sign = ($hemisphere === 'W' || $hemisphere === 'S') ? -1 : 1;
        $degrees = ($coordinates[0] + ($coordinates[1] / 60) + ($coordinates[2] / 3600));

        return $sign * $degrees;
    }
}
