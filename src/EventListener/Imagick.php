<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;

/**
 * Imagick event listener
 *
 * This event listener is responsible for reading the initial image data, and updating the model
 * before sending back transformed images to the client, or when storing transformed images in the
 * storage.
 */
class Imagick implements ListenerInterface, ImagickAware
{
    /**
     * Imagick instance that is injected by an initializer
     *
     * @var \Imagick
     */
    private $imagick;

    public function setImagick(\Imagick $imagick): self
    {
        $this->imagick = $imagick;

        return $this;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Update the model after all transformations have been applied
            'image.transformed' => 'updateModel',

            'images.post' => [
                // Update the model before storing the image data in case an event listener has
                // changed the incoming image
                'updateModelBeforeStoring' => 1,

                // Inject the image blob into the image model after preparing the image based on
                // the request body
                'readImageBlob' => 40,
            ],

            // Inject the image blob into the image model after loading it from the database
            'image.loaded' => ['readImageBlob' => 100],
        ];
    }

    /**
     * Inject the image blob from the image model into the shared imagick instance
     *
     * @param EventInterface $event The event instance
     */
    public function readImageBlob(EventInterface $event): void
    {
        $eventName = $event->getName();
        $config = $event->getConfig();
        $jpegSizeHintEnabled = $config['optimizations']['jpegSizeHint'];

        if ($event->hasArgument('image')) {
            // The image has been specified as an argument to the event
            $image = $event->getArgument('image');
        } elseif ($eventName === 'images.post') {
            // The image is found in the request
            $image = $event->getRequest()->getImage();
        } else {
            // The image is found in the response
            $image = $event->getResponse()->getModel();
        }

        $shouldOptimize = $jpegSizeHintEnabled && !$event->hasArgument('skipOptimization');

        if ($shouldOptimize && $eventName === 'image.loaded') {
            // See if we can hint to imagick that we expect a smaller output
            $minSize = $event->getTransformationManager()->getMinimumImageInputSize($event);

            if ($minSize) {
                $inputSize = $minSize['width'] . 'x' . $minSize['height'];
                $this->imagick->setOption('jpeg:size', $inputSize);
            }
        }

        // Inject the image blob
        $event->getInputLoaderManager()->load($image->getMimeType(), $image->getBlob());

        // If we have specified a size hint, check if we have a different input size
        // than the original and set the ratio as an argument for any other listeners
        if (isset($inputSize) && isset($minSize)) {
            $newSize = $this->imagick->getImageGeometry();
            $ratio = $image->getWidth() / $newSize['width'];
            $event->setArgument('ratio', $ratio);
            $event->setArgument('transformationIndex', $minSize['index']);
        }
    }

    /**
     * Update the image model blob before storing it in case an event listener has changed the
     * image
     *
     * @param EventInterface $event The event instance
     */
    public function updateModelBeforeStoring(EventInterface $event): void
    {
        $image = $event->getRequest()->getImage();

        if ($image->getHasBeenTransformed()) {
            $image->setBlob($this->imagick->getImageBlob());
        }
    }

    /**
     * Update the model data if the image has been changed
     *
     * @param EventInterface $event The event instance
     */
    public function updateModel(EventInterface $event): void
    {
        $image = $event->getArgument('image');

        if ($image->getHasBeenTransformed()) {
            $image->setBlob($this->imagick->getImageBlob());
        }
    }
}
