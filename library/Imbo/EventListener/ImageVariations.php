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
    Imbo\Image\Transformation\Resize,
    Imbo\Exception\TransformationException;

/**
 * Image variations generator
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ImageVariations implements ListenerInterface {
    /**
     * Parameters for the event listener
     *
     * @var array
     */
    private $params = array(
        // When the width of the image falls below this, don't generate any more variations
        'minWidth' => 1024,

        // If the diff between two images falls below this value no more variations will be
        // generated
        'minDiff' => 100,

        // Specify some specific widths that will be generated instead of using the minimum sizes
        'widths' => array(),

        // Whether or not to allow smaller images if they are closer in pixel size. This may result
        // in slightly lesser quality as this can result in smaller images being resized upwards.
        'allowSmaller' => false,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     */
    public function __construct(array $params = array()) {
        $this->params = array_replace($this->params, $params);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'image.put' => array('generateVariations' => -10),
            'storage.image.load' => array('chooseVariation' => 10),
        );
    }

    /**
     * Choose an image variation based on the transformations and the original size of the image
     *
     * @param EventInterface $event The current event
     */
    public function chooseVariation(EventInterface $event) {
        // Fetch the original width / height of the image to use for ratio calculations
        $image = $event->getResponse()->getModel();
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        $transformations = $event->getRequest()->getTransformations();
        $width = $this->getMinWidth($imageWidth, $imageHeight, $transformations);

        if ($width === $imageWidth) {
            // The minimum width is the same as the original, use the original
            return;
        }

        // WE HAVE A WINNER! Find a fitting image
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
    }
}
