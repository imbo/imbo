<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Http\Response\Response;

/**
 * Limit the number of transformations that can be applied in a request.
 */
class ImageTransformationLimiter implements ListenerInterface
{
    private int $transformationLimit;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the limit listener. `limit` (int) is required, and is the max number of
     *                      transformations to allow. 0 will disable the check, but allow the listener to remain
     *                      active.
     * @throws InvalidArgumentException Throws an exception if the "limit" element is missing in params
     */
    public function __construct(array $params)
    {
        if (!isset($params['limit'])) {
            throw new InvalidArgumentException(
                'The image transformation limiter needs the "limit" argument to be configured.',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        $this->setTransformationLimit($params['limit']);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'image.get' => ['checkTransformationCount' => 20],
        ];
    }

    /**
     * Check the number of transformations in a request and generate a 403 if there's an excessive number of
     * transformations.
     *
     * @throws ResourceException Throws an exception if the transformation count exceeds the allowed value.
     */
    public function checkTransformationCount(EventInterface $event): void
    {
        $transformations = $event->getRequest()->getTransformations();

        if ($this->transformationLimit && (count($transformations) > $this->transformationLimit)) {
            throw new ResourceException(
                'Too many transformations applied to resource. The limit is ' . $this->transformationLimit . ' transformations.',
                Response::HTTP_FORBIDDEN,
            );
        }
    }

    public function getTransformationLimit(): int
    {
        return $this->transformationLimit;
    }

    public function setTransformationLimit(int $transformationLimit): void
    {
        $this->transformationLimit = $transformationLimit;
    }
}
