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
    private $params = [
        // Flip to true to converts variations to a lossless format (PNG) before saving
        'lossless' => false,

        // Flip to false to turn off auto scaling
        'autoScale' => true,

        // The scale factor to use when auto scaling
        'scaleFactor' => .5,

        // If the diff between two images falls below this value the auto scaling will stop
        'minDiff' => 100,

        // When the width of the image variation falls below this the auto scaling will stop
        'minWidth' => 100,

        // Don't start resizing until the size falls below this limit
        'maxWidth' => 1024,

        // Specific widths to generate, in addition to the auto scaling
        'widths' => [],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = []) {
        $this->params = array_replace($this->params, $params);

        // Make sure the scale factor is a negative number if it exists
        if (isset($this->params['scaleFactor']) && $this->params['scaleFactor'] >= 1) {
            throw new InvalidArgumentException('Scale factor must be below 1', 503);
        }

        $this->configureDatabase($this->params);
        $this->configureStorage($this->params);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // Generate image variations that can be used in resize operations later on
            'images.post' => ['generateVariations' => -10],

            // Choose a more suitable variation that can be used for resizing
            'storage.image.load' => ['chooseVariation' => 10],

            // Delete variations of an image when the image itself is deleted
            'image.delete' => ['deleteVariations' => -10],
        ];
    }

    /**
     * Choose an image variation based on the transformations and the original size of the image
     *
     * @param EventInterface $event The current event
     */
    public function chooseVariation(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();

        // Fetch the transformations from the request and find the max width used in the set
        $transformations = $request->getTransformations();

        if (!$transformations) {
            // No transformations in the request
            return;
        }

        $transformationManager = $event->getTransformationManager();
        $maxSize = $transformationManager->getMinimumImageInputSize($event);

        if (!$maxSize) {
            // No need to use a variation based on the set of transformations
            return;
        }

        // Fetch the original width / height of the image to use for ratio calculations
        $image = $response->getModel();
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        // Fetch the index of the transformation that decided the max width, and the width itself
        $transformationIndex = $maxSize['index'];
        $maxWidth = $maxSize['width'];

        if ($maxWidth >= $imageWidth) {
            // The width is the same or above the original, use the original
            return;
        }

        // WE HAVE A WINNER! Find the best variation. The width of the variation is the first
        // available one above the $maxWidth value
        $variation = $this->database->getBestMatch($user, $imageIdentifier, $maxWidth);

        if (!$variation) {
            // Could not find any :(
            return;
        }

        // Now that we have a variation we can use we need to adjust some of the transformation
        // parameters.
        $event->getManager()->trigger('image.transformations.adjust', [
            'transformationIndex' => $transformationIndex,
            'ratio' => $imageWidth / $variation['width'],
        ]);

        // Fetch the image variation blob from the storage adapter
        $imageBlob = $this->storage->getImageVariation($user, $imageIdentifier, $variation['width']);

        if (!$imageBlob) {
            // The image blob does not exist in the storage, which it should. Trigger an error and
            // return
            trigger_error('Image variation storage is not in sync with the image variation database', E_USER_WARNING);
            return;
        }

        // Set some data that the storage operations listener usually sets, since that will be
        // skipped since we have an image variation
        $lastModified = $event->getStorage()->getLastModified($user, $imageIdentifier);
        $response->setLastModified($lastModified);

        // Update the model
        $model = $response->getModel();
        $model->setBlob($imageBlob)
              ->setWidth($variation['width'])
              ->setHeight($variation['height']);

        // Set a HTTP header that informs the user agent on which image variation that was used in
        // the transformations
        $response->headers->set('X-Imbo-ImageVariation', $variation['width'] . 'x' . $variation['height']);

        // Stop the propagation of this event
        $event->stopPropagation();
        $event->getManager()->trigger('image.loaded');
    }

    /**
     * Generate multiple variations based on the configuration
     *
     * If any of the operations fail Imbo will trigger errors
     *
     * @param EventInterface $event
     */
    public function generateVariations(EventInterface $event) {
        $eventManager = $event->getManager();
        $transformationManager = $event->getTransformationManager();

        $request = $event->getRequest();
        $user = $request->getUser();
        $originalImage = $request->getImage();
        $imageIdentifier = $originalImage->getImageIdentifier();
        $originalWidth = $originalImage->getWidth();

        // Fetch parameters specified in the Imbo configuration related to what sort of variations
        // should be generated
        $minWidth = $this->params['minWidth'];
        $maxWidth = $this->params['maxWidth'];
        $minDiff = $this->params['minDiff'];
        $scaleFactor = $this->params['scaleFactor'];

        // Remove widths which are larger than the original image
        $widths = array_filter($this->params['widths'], function($value) use ($originalWidth) {
            return $value < $originalWidth;
        });

        if ($this->params['autoScale'] === true) {
            // Have Imbo figure out the widths to generate in addition to the ones in the "width"
            // configuration parameter
            $variationWidth = $previousWidth = $originalWidth;

            while ($variationWidth > $minWidth) {
                $variationWidth = round($variationWidth * $scaleFactor);

                if ($variationWidth > $maxWidth) {
                    // Width too big, try again
                    continue;
                }

                if ((($previousWidth - $variationWidth) < $minDiff) || ($variationWidth < $minWidth)) {
                    // The diff is too small, or the variation is too small, stop generating more widths
                    break;
                }

                $previousWidth = $variationWidth;

                $widths[] = $variationWidth;
            }
        }

        foreach ($widths as $width) {
            // Clone the image so that the resize operation will happen on the original every time
            $image = clone $originalImage;

            try {
                // Trigger a loading of the image, using the clone of the original as an argument
                $eventManager->trigger('image.loaded', [
                    'image' => $image,

                    // Don't apply any sort of optimization to the image before saving, we want
                    // as high quality on the stored variation as possible
                    'skipOptimization' => true,
                ]);

                // If configured, use a lossless variation format
                if ($this->params['lossless'] === true) {
                    $transformationManager
                        ->getTransformation('convert')
                        ->setImage($image)
                        ->transform(['type' => 'png']);
                }

                // Trigger a resize of the image (the transformation handles aspect ratio)
                $transformationManager
                    ->getTransformation('resize')
                    ->setImage($image)
                    ->transform(['width' => $width]);

                // Trigger an update of the model
                $eventManager->trigger('image.transformed', [
                    'image' => $image,
                ]);

                // Store the image
                $this->storage->storeImageVariation($user, $imageIdentifier, $image->getBlob(), $width);

                // Store some data about the variation
                $this->database->storeImageVariationMetadata($user, $imageIdentifier, $image->getWidth(), $image->getHeight());
            } catch (TransformationException $e) {
                // Could not transform the image
                trigger_error(sprintf('Could not generate image variation for %s (%s), width: %d', $user, $imageIdentifier, $width), E_USER_WARNING);
            } catch (StorageException $e) {
                // Could not store the image
                trigger_error(sprintf('Could not store image variation for %s (%s), width: %d', $user, $imageIdentifier, $width), E_USER_WARNING);
            } catch (DatabaseException $e) {
                // Could not store metadata about the variation
                trigger_error(sprintf('Could not store image variation metadata for %s (%s), width: %d', $user, $imageIdentifier, $width), E_USER_WARNING);

                try {
                    $this->storage->deleteImageVariations($user, $imageIdentifier, $width);
                } catch (StorageException $e) {
                    trigger_error('Could not remove the stored variation', E_USER_WARNING);
                }
            }
        }
    }

    /**
     * Delete all image variations attached to an image
     *
     * If any of the delete operations fail Imbo will trigger an error
     *
     * @param EventInterface $event The current event
     */
    public function deleteVariations(EventInterface $event) {
        $request = $event->getRequest();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();

        try {
            $this->database->deleteImageVariations($user, $imageIdentifier);
        } catch (DatabaseException $e) {
            trigger_error(sprintf('Could not delete image variation metadata for %s (%s)', $user, $imageIdentifier), E_USER_WARNING);
        }

        try {
            $this->storage->deleteImageVariations($user, $imageIdentifier);
        } catch (StorageException $e) {
            trigger_error(sprintf('Could not delete image variations from storage for %s (%s)', $user, $imageIdentifier), E_USER_WARNING);
        }
    }

    /**
     * Configure the database adapter
     *
     * @param array $config The event listener configuration
     * @throws InvalidArgumentException
     */
    private function configureDatabase(array $config) {
        if (!isset($config['database']) || !isset($config['database']['adapter'])) {
            throw new InvalidArgumentException('Missing database adapter configuration for the image variations event listener', 500);
        }

        $config = $config['database'];

        if (is_callable($config['adapter'])) {
            $databaseAdapter = $config['adapter']();
        } else if (is_string($config['adapter'])) {
            $databaseAdapter = new $config['adapter'](isset($config['params']) ? $config['params'] : null);
        } else {
            $databaseAdapter = $config['adapter'];
        }

        if (!($databaseAdapter instanceof DatabaseInterface)) {
            throw new InvalidArgumentException('Invalid database adapter for the image variations event listener', 500);
        }

        $this->database = $databaseAdapter;
    }

    /**
     * Configure the storage adapter
     *
     * @param array $config The event listener configuration
     * @throws InvalidArgumentException
     */
    private function configureStorage(array $config) {
        if (!isset($config['storage']) || !isset($config['storage']['adapter'])) {
            throw new InvalidArgumentException('Missing storage adapter configuration for the image variations event listener', 500);
        }

        $config = $config['storage'];

        if (is_callable($config['adapter'])) {
            $storageAdapter = $config['adapter']();
        } else if (is_string($config['adapter'])) {
            $storageAdapter = new $config['adapter'](isset($config['params']) ? $config['params'] : null);
        } else {
            $storageAdapter = $config['adapter'];
        }

        if (!($storageAdapter instanceof StorageInterface)) {
            throw new InvalidArgumentException('Invalid storage adapter for the image variations event listener', 500);
        }

        $this->storage = $storageAdapter;
    }
}
