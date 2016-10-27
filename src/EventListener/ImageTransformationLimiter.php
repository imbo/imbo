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
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\ResourceException;

/**
 * Limit the number of transformations that can be applied in a request.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Event\Listeners
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
     * @param int $transformationLimit The number of transformations to allow. Any count > this number will
     *                                 generate an error. 0 (or invalid integer) will disable the check.
     */
    public function __construct($transformationLimit) {
        $this->setTransformationLimit($transformationLimit);
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
