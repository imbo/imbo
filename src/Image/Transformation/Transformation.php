<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imagick,
    Imbo\Model\Image,
    Imbo\EventManager\Event,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface;

/**
 * Abstract transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
abstract class Transformation implements ListenerInterface {
    /**
     * Imagick instance
     *
     * @var Imagick
     */
    protected $imagick;

    /**
     * Event that triggered this transformation
     *
     * @var Event
     */
    protected $event;

    /**
     * Set the Imagick instance
     *
     * @param Imagick $imagick An Imagick instance
     * @return self
     */
    public function setImagick(Imagick $imagick) {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * Set the Image model instance
     *
     * @param Image $image An Image instance
     * @return self
     */
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Set the transformation event that triggered the transformation
     *
     * @param Event $event An Event instance
     * @return self
     */
    public function setEvent(Event $event) {
        $this->event = $event;

        return $this;
    }

    /**
     * Attempt to format a color-string into a string Imagick can understand
     *
     * @param string $color
     * @return string
     */
    protected function formatColor($color) {
        if (preg_match('/^[A-F0-9]{3,6}$/i', $color)) {
            return '#' . $color;
        }

        return $color;
    }

    /**
     * Get the quantum range of an image
     *
     * @return int
     */
    protected function getQuantumRange() {
        // Newer versions of Imagick expose getQuantumRange as a static method,
        // and won't allow phpunit to mock it even when called on an instance
        if (is_callable([$this->imagick, 'getQuantumRange'])) {
            $quantumRange = $this->imagick->getQuantumRange();
        } else {
            $quantumRange = \Imagick::getQuantumRange();
        }

        return $quantumRange['quantumRangeLong'];
    }

    /**
     * Get the imagick version
     *
     * @return string
     */
    protected function getImagickVersion() {
        // Newer versions of Imagick expose getVersion as a static method,
        // and won't allow phpunit to mock it even when called on an instance
        if (method_exists('Imagick', 'getVersion')) {
            return Imagick::getVersion();
        }

        return $this->imagick->getVersion();
    }

    /**
     * Adjust the parameters for this transformation, in the event that the size of the
     * input image has changed, for instance if the `ImageVariations`-listener is in place
     *
     * @param float $ratio Ratio (input image width / original image width)
     * @param array $parameters Transformation parameters
     * @return array Adjusted parameters
     */
    public function adjustParameters($ratio, array $parameters) {
        return $parameters;
    }

    /**
     * Transform the image
     *
     * @param array $params Parameters for the transformation
     * @throws TransformationException
     */
    abstract public function transform(array $params);

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [];
    }
}
