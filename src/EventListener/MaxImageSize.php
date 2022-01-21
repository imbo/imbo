<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;

/**
 * Max image size event listener
 */
class MaxImageSize implements ListenerInterface
{
    /**
     * Max width
     *
     * @var int
     */
    private $width;

    /**
     * Max height
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     */
    public function __construct(array $params)
    {
        $this->width = isset($params['width']) ? (int) $params['width'] : 0;
        $this->height = isset($params['height']) ? (int) $params['height'] : 0;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'images.post' => ['enforceMaxSize' => 25],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function enforceMaxSize(EventInterface $event)
    {
        $image = $event->getRequest()->getImage();

        $width = $image->getWidth();
        $height = $image->getHeight();

        if (($this->width && ($width > $this->width)) || ($this->height && ($height > $this->height))) {
            $event->getTransformationManager()
                ->getTransformation('maxSize')
                ->setImage($image)
                ->transform([
                    'width' => $this->width,
                    'height' => $this->height,
                ]);
        }
    }
}
