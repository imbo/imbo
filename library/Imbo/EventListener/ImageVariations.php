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
    Imbo\EventListener\ImageVariations\Database\DatabaseInterface,
    Imbo\EventListener\ImageVariations\Storage\StorageInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\TransformationException,
    Imbo\Exception\StorageException,
    Imbo\Exception\DatabaseException;

/**
 * Image variations generator
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ImageVariations implements ListenerInterface {
    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * Parameters for the event listener
     *
     * @var array
     */
    private $params = array(
        // Flip to false to turn off autoscaling
        'autoScale' => true,

        // The scale factor
        'scaleFactor' => .5,

        // If the diff between two images falls below this value no more variations will be
        // generated
        'minDiff' => 100,

        // When the width of the image falls below this, don't generate any more variations
        'minWidth' => 100,

        // Don't start resizing until the size falls below this limit
        'maxWidth' => 1024,

        // Specific widths to generate, in addition to the auto scaling
        'widths' => array(),
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array()) {
        $this->params = array_replace($this->params, $params);

        if (!isset($this->params['database']) || !isset($this->params['database']['adapter'])) {
            throw new InvalidArgumentException('Missing database adapter for the image variations event listener', 500);
        }

        $dbConfig = $this->params['database'];
        $dbParams = isset($dbConfig['params']) ? $dbConfig['params'] : null;
        $this->database = new $dbConfig['adapter']($dbParams);

        if (!($this->database instanceof DatabaseInterface)) {
            throw new InvalidArgumentException('Invalid database adapter for the image variations event listener', 500);
        }

        if (!isset($this->params['storage']) || !isset($this->params['storage']['adapter'])) {
            throw new InvalidArgumentException('Missing storage adapter for the image variations event listener', 500);
        }

        $storageConfig = $this->params['storage'];
        $storageParams = isset($storageConfig['params']) ? $storageConfig['params'] : null;
        $this->storage = new $storageConfig['adapter']($storageParams);

        if (!($this->storage instanceof StorageInterface)) {
            throw new InvalidArgumentException('Invalid storage adapter for the image variations event listener', 500);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'images.post' => array('generateVariations' => -10),
            'storage.image.load' => array('chooseVariation' => 10),
            'image.delete' => array('deleteVariations' => -10),
        );
    }

    /**
     * Choose an image variation based on the transformations and the original size of the image
     *
     * @param EventInterface $event The current event
     */
    public function chooseVariation(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        // Fetch the original width / height of the image to use for ratio calculations
        $image = $response->getModel();
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        $transformations = $request->getTransformations();
        $width = $this->getMinWidth($imageWidth, $imageHeight, $transformations);

        if ($width === $imageWidth) {
            // The minimum width is the same as the original, use the original
            return;
        }

        // WE HAVE A WINNER! Find a fitting image
        $variation = $this->database->getBestMatch($publicKey, $imageIdentifier, $width);

        if (!$variation) {
            return;
        }

        $width = $variation['width'];
        $height = $variation['height'];

        $imageBlob = $this->storage->getImageVariation($publicKey, $imageIdentifier, $width);

        $lastModified = $event->getStorage()->getLastModified($publicKey, $imageIdentifier);

        $response->setLastModified($lastModified);
        $model = $response->getModel();
        $model->setBlob($imageBlob)->setWidth($width)->setHeight($height);

        $response->headers->set('X-Imbo-ImageVariation', $width . 'x' . $height);

        $event->stopPropagation();
        $event->getManager()->trigger('image.loaded');
    }

    /**
     * Fetch the minimum width of an image and a set of transformations
     *
     * @param int $width The width of the existing image
     * @param int $height The height of the existing image
     * @param array $transformations Transformations from the URL
     * @return int Returns the minimum width
     */
    public function getMinWidth($width, $height, array $transformations) {
        $minWidth = $width;

        // Calculate the aspect ratio
        $ratio = $width / $height;

        foreach ($transformations as $transformation) {
            $name = $transformation['name'];
            $params = $transformation['params'];

            if ($name === 'maxSize') {
                // MaxSize transformation
                if (isset($params['width'])) {
                    // width detected
                    $width = min((int) $params['width'], $width);
                } else if (isset($params['height'])) {
                    // height detected, calculate ratio
                    $width = min((int) $params['height'] * $ratio, $width);
                }
            } else if ($name === 'resize') {
                // Resize transformation
                if (isset($params['width'])) {
                    // width detected
                    $width = min((int) $params['width'], $width);
                } else if (isset($params['height'])) {
                    // height detected, calculate ratio
                    $width = min((int) $params['height'] * $ratio, $width);
                }
            } else if ($name === 'thumbnail') {
                if (isset($params['width'])) {
                    // Width have been specified
                    $width = min((int) $params['width'], $width);
                } else if (isset($params['height']) && isset($params['fit']) && $params['fit'] === 'inset') {
                    // Height have been specified, and the fit mode is inset, calculate width
                    $width = min((int) $params['height'] * $ratio, $width);
                } else {
                    // No width or height/inset fit combo. Use default width for thumbnails
                    $width = 50;
                }
            }
        }

        return $width;
    }

    /**
     * Generate multiple variations based on the configuration
     *
     * @param EventInterface $event
     */
    public function generateVariations(EventInterface $event) {
        $eventManager = $event->getManager();
        $request = $event->getRequest();
        $image = $event->getRequest()->getImage();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $image->getChecksum();
        $width = $variationWidth = $previousWidth = $image->getWidth();
        $minWidth = $this->params['minWidth'];
        $maxWidth = $this->params['maxWidth'];
        $minDiff = $this->params['minDiff'];
        $scaleFactor = $this->params['scaleFactor'];
        $widths = array_filter($this->params['widths'], function($value) use ($width) {
            return $value < $width;
        });

        if ($this->params['autoScale'] === true) {
            while ($variationWidth > $minWidth) {
                $variationWidth = floor($variationWidth * $scaleFactor);

                if ((($previousWidth - $variationWidth) < $minDiff) || ($variationWidth < $minWidth)) {
                    // The diff is too small, or the variation is too small
                    break;
                }

                $previousWidth = $variationWidth;

                if ($variationWidth > $maxWidth) {
                    // Width too big, try again (twss)
                    continue;
                }

                $widths[] = $variationWidth;
            }
        }

        foreach ($widths as $width) {
            try {
                // Trigger a resize of the image (the transformation handles aspect ratio)
                $eventManager->trigger('image.transformation.resize', array(
                    'image' => $image,
                    'params' => array(
                        'width' => $width,
                    ),
                ));

                // Trigger an update of the model
                $eventManager->trigger('image.transformed', array(
                    'image' => $image,
                ));

                // Store the image
                $this->storage->storeImageVariation($publicKey, $imageIdentifier, $image->getBlob(), $image->getWidth());

                // Store some data about the variation
                $this->database->storeImageVariationMetadata($publicKey, $imageIdentifier, $image->getWidth(), $image->getHeight());
            } catch (TransformationException $e) {
                // Could not transform the image
                trigger_error(sprintf('Could not generate image variation for %s (%s), width: %d', $publicKey, $imageIdentifier, $width), E_USER_WARNING);
            } catch (StorageException $e) {
                // Could not store the image
                trigger_error(sprintf('Could not store image variation for %s (%s), width: %d', $publicKey, $imageIdentifier, $width), E_USER_WARNING);
            } catch (DatabaseException $e) {
                // Could not store metadata about the variation
                trigger_error(sprintf('Could not store image variation metadata for %s (%s), width: %d', $publicKey, $imageIdentifier, $width), E_USER_WARNING);

                try {
                    $this->storage->deleteImageVariations($publicKey, $imageIdentifier, $width);
                } catch (StorageException $e) {
                    // Whatevah
                    trigger_error('Could not remove the stored variation', E_USER_WARNING);
                }
            }
        }
    }

    /**
     * Delete all image variations attached to an image
     *
     * @param EventInterface $event The current event
     */
    public function deleteVariations(EventInterface $event) {
        $request = $event->getRequest();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        try {
            $this->database->deleteImageVariations($publicKey, $imageIdentifier);
        } catch (DatabaseException $e) {
            trigger_error(sprintf('Could not delete image variation meadata for %s (%s)', $publicKey, $imageIdentifier), E_USER_WARNING);
        }

        try {
            $this->storage->deleteImageVariations($publicKey, $imageIdentifier);
        } catch (StorageException $e) {
            trigger_error(sprintf('Could not delete image variations for %s (%s)', $publicKey, $imageIdentifier), E_USER_WARNING);
        }
    }
}
