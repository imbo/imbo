<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imagick;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;

/**
 * Exif metadata event listener
 *
 * This listener will look for properties stored in the image, and store them as metadata in Imbo.
 */
class ExifMetadata implements ListenerInterface
{
    protected array $allowedTags = [
        'exif:*',
    ];
    protected array $properties = [];
    private ?Imagick $imagick = null;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     */
    public function __construct(array $params = null)
    {
        if ($params && isset($params['allowedTags'])) {
            $this->allowedTags = $params['allowedTags'];
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to prevent other listeners from stripping EXIF-data
            'images.post' => ['populate' => 45],

            // When image has been inserted to database, insert metadata
            'db.image.insert' => ['save' => -100],
        ];
    }

    public function setImagick(Imagick $imagick): self
    {
        $this->imagick = $imagick;
        return $this;
    }

    public function getImagick(): Imagick
    {
        if (null === $this->imagick) {
            $this->imagick = new Imagick();
        }

        return $this->imagick;
    }

    public function populate(EventInterface $event): array
    {
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
     * @throws RuntimeException
     */
    public function save(EventInterface $event): void
    {
        $request = $event->getRequest();
        $database = $event->getDatabase();

        $user = $request->getUser();
        $imageIdentifier = $request->getImage()->getImageIdentifier();

        try {
            $database->updateMetadata(
                $user,
                $imageIdentifier,
                $this->properties,
            );
        } catch (DatabaseException $e) {
            $database->deleteImage($user, $imageIdentifier);
            throw new RuntimeException('Could not store EXIF-metadata', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function filterProperties(array $properties): array
    {
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

    protected function parseProperties(array $rawProperties): array
    {
        if (isset($rawProperties['exif:GPSLatitude']) && isset($rawProperties['exif:GPSLongitude'])) {
            // We store coordinates in GeoJSON-format (lng/lat)
            $rawProperties['gps:location'] = [
                $this->parseGpsCoordinate(
                    $rawProperties['exif:GPSLongitude'],
                    $rawProperties['exif:GPSLongitudeRef'],
                ),
                $this->parseGpsCoordinate(
                    $rawProperties['exif:GPSLatitude'],
                    $rawProperties['exif:GPSLatitudeRef'],
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

    protected function parseGpsCoordinate(string $coordinate, string $hemisphere): float
    {
        $coordinates = explode(' ', $coordinate);

        for ($i = 0; $i < 3; $i++) {
            $part = explode('/', $coordinates[$i]);
            $parts = count($part);

            if ($parts === 1) {
                $coordinates[$i] = $part[0];
            } elseif ($parts === 2) {
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
