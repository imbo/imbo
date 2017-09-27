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
 * 8bim metadata event listener
 *
 * This listener will look for properties stored in the image, and store certain metadata (at the moment, the available clipping paths) in Imbo.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Event\Listeners
 */
class EightbimMetadata implements ListenerInterface {
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
     * Set the Imagick instance to be used - the instance will be cloned to avoid polluting the existing imagick object.
     *
     * @param $imagick
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
     * Read 8bim data from incoming image
     *
     * @param EventInterface $event The triggered event
     * @return array
     */
    public function populate(EventInterface $event) {
        $image = $event->getRequest()->getImage();

        // This can be replaced with ImagickAware and clone $imagick when the ImagickAware patch has been merged
        $imagick = new \Imagick();
        $imagick->readImageBlob($image->getBlob());

        // Get 8BIM-properties from image - this seems to be the best way to get the identifiers with path names in a raw format
        $imagick->setImageFormat('8bimtext');
        $data = $imagick->getImageBlob();

        // Find paths - all paths are located in identifiers 2000 through 2997
        preg_match_all('/^8BIM#2(\d{3})#(.+?)=/m', $data, $matches);

        if ($matches) {
            // Order the paths by their ids in the 8bim format
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
            $database->updateMetadata(
                $user,
                $imageIdentifier,
                $this->properties
            );
        } catch (DatabaseException $e) {
            $database->deleteImage($user, $imageIdentifier);

            throw new RuntimeException('Could not store 8BIM-metadata', 500);
        }
    }
}