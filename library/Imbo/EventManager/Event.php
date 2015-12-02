<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventManager;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event
 */
class Event extends GenericEvent implements EventInterface {
    /**
     * {@inheritdoc}
     */
    public function getRequest() {
        return $this->getArgument('request');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse() {
        return $this->getArgument('response');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase() {
        return $this->getArgument('database');
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->getArgument('storage');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessControl() {
        return $this->getArgument('accessControl');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager() {
        return $this->getArgument('manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        return $this->getArgument('config');
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler() {
        return $this->getArgument('handler');
    }
}
