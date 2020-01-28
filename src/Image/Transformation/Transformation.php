<?php
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventListener\ImagickAware;

/**
 * Abstract transformation
 */
abstract class Transformation implements ListenerInterface, ImagickAware {
    /**
     * Imagick instance
     *
     * @var Imagick
     */
    protected $imagick;

    /**
     * Event that triggered this transformation
     *
     * @var EventInterface
     */
    protected $event;

    /**
     * Image instance
     *
     * @var Image
     */
    protected $image;

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
     * @param EventInterface $event An Event instance
     * @return self
     */
    public function setEvent(EventInterface $event) {
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
            $quantumRange = Imagick::getQuantumRange();
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
