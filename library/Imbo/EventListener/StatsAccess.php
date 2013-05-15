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
    Imbo\Http\Request\Request,
    Imbo\Exception\RuntimeException;

/**
 * Stats access listener
 *
 * This event listener lets you control the access to the /stats endpoint by white-/blacklisting ip
 * addresses. If you disable the listener from the configuration it will be open to anyone (and it
 * does not require an access token by default)
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class StatsAccess implements ListenerInterface {
    /**
     * Parameters for the listener
     *
     * If the whitelist is populated with one or more ip addresses, all others will automatically
     * be blacklisted. If the blacklist is populated with one or more ip addresses, all other will
     * automatically be whitelisted. If both filters contain values, the current ip must be in the
     * whitelits to gain access. If the current ip is located in both filters, it will not gain
     * access.
     *
     * @var array
     */
    private $params = array(
        'whitelist' => array(),
        'blacklist' => array(),
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = array()) {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('stats.get', array($this, 'checkAccess')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkAccess(EventInterface $event) {
        $access = false;
        $request = $event->getRequest();
        $ip = $request->getClientIp();

        if (empty($this->params['whitelist']) && !empty($this->params['blacklist'])) {
            $access = !$this->isBlacklisted($ip);
        } else if (empty($this->params['blacklist']) && !empty($this->params['whitelist'])) {
            $access = $this->isWhitelisted($ip);
        } else {
            $access = $this->isWhitelisted($ip) && !$this->isBlacklisted($ip);
        }

        if (!$access) {
            throw new RuntimeException('Access denied', 403);
        }
    }

    /**
     * Check if an ip address is white listed
     *
     * @param string $ip The IP address
     * @return boolean
     */
    private function isWhitelisted($ip) {
        return in_array($ip, $this->params['whitelist']);
    }

    /**
     * Check if an ip address is black listed
     *
     * @param string $ip The IP address
     * @return boolean
     */
    private function isBlacklisted($ip) {
        return in_array($ip, $this->params['blacklist']);
    }
}
