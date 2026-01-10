<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\EventListener\ImagickAware;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\TransformationException;
use Imbo\Model\Image;

use function is_callable;

/**
 * Abstract transformation.
 */
abstract class Transformation implements ListenerInterface, ImagickAware
{
    protected Imagick $imagick;
    protected EventInterface $event;
    protected Image $image;

    public function setImagick(Imagick $imagick): self
    {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * @return static
     */
    public function setImage(Image $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Set the transformation event that triggered the transformation.
     *
     * @param EventInterface $event An Event instance
     *
     * @return self
     */
    public function setEvent(EventInterface $event)
    {
        $this->event = $event;

        return $this;
    }

    protected function formatColor(string $color): string
    {
        if (preg_match('/^[A-F0-9]{3,6}$/i', $color)) {
            return '#'.$color;
        }

        return $color;
    }

    protected function getQuantumRange(): int
    {
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
     * Adjust the parameters for this transformation, in the event that the size of the
     * input image has changed, for instance if the `ImageVariations`-listener is in place.
     *
     * @param float $ratio      Ratio (input image width / original image width)
     * @param array $parameters Transformation parameters
     *
     * @return array Adjusted parameters
     */
    public function adjustParameters(float $ratio, array $parameters): array
    {
        return $parameters;
    }

    /**
     * Transform the image.
     *
     * @param array $params Parameters for the transformation
     *
     * @throws TransformationException
     */
    abstract public function transform(array $params);

    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
