<?php
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Exception\DatabaseException;

/**
 * 8BIM metadata event listener
 *
 * This listener will look for properties stored in the image, and store certain metadata (at the
 * moment, the available clipping paths) in Imbo.
 *
 * @package Event\Listeners
 */
class EightbimMetadata implements ListenerInterface, ImagickAware {
    /**
     * @var \Imagick
     */
    protected $imagick;

    /**
     * Metadata extracted from 8BIM profile
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Set the Imagick instance to be used - the instance will be cloned to avoid polluting the
     * existing imagick object.
     *
     * @param \Imagick $imagick
     * @return null
     */
    public function setImagick(\Imagick $imagick) {
        $this->imagick = clone $imagick;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // High priority to prevent other listeners from stripping metadata
            'images.post' => ['populate' => 45],

            // When image has been inserted to database, insert metadata
            'db.image.insert' => ['save' => -100],
        ];
    }

    /**
     * Read 8BIM data from incoming image
     *
     * @param EventInterface $event The triggered event
     * @return array
     */
    public function populate(EventInterface $event) {
        // Read the image
        $image = $event->getRequest()->getImage();
        $this->imagick->readImageBlob($image->getBlob());

        // Get 8BIM-properties from image - this seems to be the best way to get the identifiers
        // with path names in a raw format
        $this->imagick->setImageFormat('8bimtext');
        $data = $this->imagick->getImageBlob();

        // Find paths - all paths are located in identifiers 2000 through 2997
        preg_match_all('/^8BIM#2(\d{3})#(.+?)=/m', $data, $matches);

        if ($matches) {
            // Order the paths by their ids in the 8BIM format
            $paths = [];

            foreach ($matches[1] as $idx => $pathId) {
                $paths[$pathId] = $matches[2][$idx];
            }

            ksort($paths);

            $this->properties['paths'] = array_values($paths);
        }

        return $this->properties;
    }

    /**
     * Save metadata to database
     *
     * @param  EventInterface $event The triggered event
     * @throws RuntimeException
     */
    public function save(EventInterface $event) {
        if (!$this->properties) {
            return;
        }

        $request = $event->getRequest();
        $database = $event->getDatabase();
        $user = $request->getUser();

        $imageIdentifier = $request->getImage()->getImageIdentifier();

        try {
            $database->updateMetadata($user, $imageIdentifier, $this->properties);
        } catch (DatabaseException $e) {
            $database->deleteImage($user, $imageIdentifier);

            throw new RuntimeException('Could not store 8BIM-metadata', 500);
        }
    }
}
