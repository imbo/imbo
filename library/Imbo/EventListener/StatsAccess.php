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
    Imbo\Exception\RuntimeException;

/**
 * Stats access listener
 *
 * This event listener lets you control the access to the /stats endpoint by white-/blacklisting ip
 * addresses or subnets (using CIDR notation). If you disable the listener from the configuration
 * it will be open to anyone (and it does not require an access token by default).
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class StatsAccess implements ListenerInterface {
    /**
     * Parameters for the listener
     *
     * The only supported parameter is "allow" which is an array of IP addresses/subnets that is
     * allowed access. If one of the entries in the array is "*", all clients are allowed.
     *
     * @var array
     */
    private $params = [
        'allow' => [],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = []) {
        if (isset($params['allow'])) {
            $this->params = array_replace_recursive($this->params, $params);

            // Exand all IPv6 addresses in the filters
            array_walk($this->params['allow'], [$this, 'expandIPv6InFilters']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'stats.get' => ['checkAccess' => 1],
            'stats.head' => ['checkAccess' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkAccess(EventInterface $event) {
        $request = $event->getRequest();
        $ip = $request->getClientIp();

        if ($this->isIPv6($ip)) {
            $ip = $this->expandIPv6($ip);
        }

        $access = $this->isAllowed($ip);

        if (!$access) {
            throw new RuntimeException('Access denied', 403);
        }
    }

    /**
     * See if an IP address is allowed
     *
     * @param string $ip An IP address
     * @return boolean
     */
    private function isAllowed($ip) {
        // Look for a wildcard
        if (in_array('*', $this->params['allow'])) {
            // There is a wildcard in the list, all IP's are allowed
            return true;
        }

        // Remove IP's which is not of the same type as $ip before matching
        if ($this->isIPv6($ip)) {
            $list = array_filter($this->params['allow'], [$this, 'isIPv6']);
        } else {
            $list = array_filter($this->params['allow'], [$this, 'isIPv4']);
        }

        foreach ($list as $range) {
            if ((strpos($range, '/') !== false && $this->cidrMatch($ip, $range)) || $ip === $range) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is in a subnet
     *
     * @param string $ip The IP address to check
     * @param string $range A CIDR notated IP address and routing prefix
     * @return boolean
     */
    private function cidrMatch($ip, $range) {
        if ($this->isIPv6($ip)) {
            return $this->cidr6Match($ip, $range);
        } else {
            return $this->cidr4Match($ip, $range);
        }
    }

    /**
     * Check an IPv4 address is in a subnet
     *
     * @param string $ip The IP address to check (for instance 192.168.1.10)
     * @param string $range A CIDR notated IP address and routing prefix (for instance 192.168.1.0/24)
     * @return boolean
     */
    private function cidr4Match($ip, $range) {
        // Split CIDR on /
        list($subnet, $bits) = explode('/', $range);

        // Convert ip's to long
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);

        // Generate mask and align the subnet if necessary
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        // Check for match
        return ($ip & $mask) === $subnet;
    }

    /**
     * Check an IPv6 address is in a subnet
     *
     * @param string $ip The IP address to check (for instance 2001:db8:0:0:0:0:0:0)
     * @param string $range A CIDR notated IP address and routing prefix (for instance 2001:db8::/48)
     * @return boolean
     */
    private function cidr6Match($ip, $range) {
        // Split CIDR on /
        list($subnet, $mask) = explode('/', $range);

        // Convert IP's to their in_addr representations
        $ip = inet_pton($ip);
        $subnet = inet_pton($subnet);

        // Convert the mask
        $mask = $this->getBinaryMask((int) $mask);

        return ($ip & $mask) === $subnet;
    }

    /**
     * Fetch the binary representation of a mask
     *
     * @param int $mask A mask from a CIDR
     * @return string
     */
    private function getBinaryMask($mask) {
        // Prefix the string
        $hexMask = str_repeat('f', $mask / 4);

        // Add the remainder
        switch ($mask % 4) {
            case 1:
                $hexMask .= '8';
                break;
            case 2:
                $hexMask .= 'c';
                break;
            case 3:
                $hexMask .= 'e';
                break;
        }

        // Pad with 0 to 32 in length
        $hexMask = str_pad($hexMask, 32, '0');

        // Pack into binary string
        return hex2bin($hexMask);
    }

    /**
     * Check if an IP address is an IPv6 address or not
     *
     * @param string $ip The address to check
     * @return boolean True if the ip address looks like an IPv6 address
     */
    private function isIPv6($ip) {
        return strpos($ip, ':') !== false;
    }

    /**
     * Check if an IP address ia an IPv4 address or not
     *
     * @param string $ip The address to check
     * @return boolean True if the ip address looks like an IPv4 address
     */
    private function isIPv4($ip) {
        return !$this->isIPv6($ip);
    }

    /**
     * Expand a short IPv6
     *
     * @param string $ip For instance 2a00:1b60:1011::1338
     * @return string 2a00:1b60:1011:0000:0000:0000:0000:1338
     */
    private function expandIPv6($ip) {
        // Convert to in_addr an unpack as hex
        $hex = strtolower(bin2hex(inet_pton($ip)));

        // Inject colons
        return substr(preg_replace('/([a-f0-9]{4})/', '$1:', $hex), 0, -1);
    }

    /**
     * Expand all IPv6 addresses in a filter
     *
     * @param string $ip An IP address that might be expanded
     */
    private function expandIPv6InFilters(&$ip) {
        if ($this->isIPv6($ip)) {
            if (($pos = strpos($ip, '/')) !== false) {
                // The IPv6 has a routing prefix attached to it
                $mask = substr($ip, $pos);
                $ip = $this->expandIPv6(substr($ip, 0, $pos)) . $mask;
            } else {
                // Regular IPv6 address
                $ip = $this->expandIPv6($ip);
            }
        }
    }
}
