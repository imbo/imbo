<?php
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Exception\ResourceException;

/**
 * Limit the number of transformations that can be applied in a request.
 */
class ImageTransformationLimiter implements ListenerInterface {
    /**
     * Number of transformations to allow
     *
     * @var int
     */
    private $transformationLimit;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the limit listener. `limit` (int) is required, and is the max number of
     *                      transformations to allow. 0 will disable the check, but allow the listener to remain
     *                      active.
     * @throws InvalidArgumentException Throws an exception if the "limit" element is missing in params
     */
    public function __construct(array $params) {
        if (!isset($params['limit'])) {
            throw new InvalidArgumentException(
                'The image transformation limiter needs the "limit" argument to be configured.',
                500
            );
        }

        $this->setTransformationLimit($params['limit']);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.get' => ['checkTransformationCount' => 20],
        ];
    }

    /**
     * Check the number of transformations in a request and generate a 403 if there's an excessive number of
     * transformations.
     *
     * @param EventInterface $event The triggered event
     * @throws ResourceException Throws an exception if the transformation count exceeds the allowed value.
     */
    public function checkTransformationCount(EventInterface $event) {
        $transformations = $event->getRequest()->getTransformations();

        if ($this->transformationLimit && (count($transformations) > $this->transformationLimit)) {
            throw new ResourceException('Too many transformations applied to resource. The limit is ' .
                                        $this->transformationLimit . ' transformations.', 403);
        }
    }

    /**
     * Get the current transformation limit applied
     *
     * @return int
     */
    public function getTransformationLimit() {
        return $this->transformationLimit;
    }

    /**
     * Set the current transformation limit. Set value to 0 to disable the check without removing the listener.
     *
     * @param int $transformationLimit
     */
    public function setTransformationLimit($transformationLimit) {
        $this->transformationLimit = (int) $transformationLimit;
    }
}
