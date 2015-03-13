<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\Model\AccessRules as AccessRulesModel;

/**
 * Access rule resource
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Resources
 */
class AccessRule implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD', 'DELETE');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'accessrule.get' => 'getRule',
            'accessrule.head' => 'getRule',
            'accessrule.delete' => 'deleteRule'
        ];
    }

    public function getRule(EventInterface $event) {
        throw new \Imbo\Exception\RuntimeException('Not Implemented', 501);
    }

    public function updateAccessRules(EventInterface $event) {
        throw new \Imbo\Exception\RuntimeException('Not Implemented', 501);
    }
}
