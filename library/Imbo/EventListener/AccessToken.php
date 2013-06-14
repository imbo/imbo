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
 * Access token event listener
 *
 * This event listener will listen to all GET and HEAD requests and make sure that they include a
 * valid access token. The official PHP-based imbo client (https://github.com/imbo/imboclient-php)
 * appends this token to all such requests by default. If the access token is missing or invalid
 * the event listener will throw an exception resulting in a HTTP response with 400 Bad Request.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class AccessToken implements ListenerInterface {
    /**
     * Parameters for the listener
     *
     * @var array
     */
    private $params = array(
        /**
         * Use this parameter to enforce the access token listener for only some of the image
         * transformations (if the request is against an image). Each transformation must be
         * specified using the short names of the transformations (the name used in the query to
         * trigger the transformation).
         *
         * 'transformations' => array(
         *     'whitelist' => array(
         *         'border',
         *         'thumbnail',
         *      ),
         * )
         *
         * Use the 'whitelist' for making the listener skip the access token check for some
         * transformations, and the 'blacklist' key for the opposite:
         *
         * 'whitelist' => array('border') means that the access token
         * will *not* be enforced for the Border transformation, but for all others.
         *
         * 'blacklist' => array('border') means that the access token
         * will be enforced *only* when the Border transformation is in effect.
         *
         * If both 'whitelist' and 'blacklist' are specified all transformations will require an
         * access token unless included in the 'whitelist'.
         */
        'transformations' => array(
            'whitelist' => array(),
            'blacklist' => array(),
        ),
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
        $callback = array($this, 'invoke');
        $priority = 100;
        $events = array(
            'user.get', 'images.get', 'image.get', 'metadata.get',
            'user.head', 'images.head', 'image.head', 'metadata.head'
        );

        $definition = array();

        foreach($events as $eventName) {
            $definition[] = new ListenerDefinition($eventName, $callback, $priority);
        }

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $request = $event->getRequest();
        $query = $request->query;
        $eventName = $event->getName();

        if (($eventName === 'image.get' || $eventName === 'image.head') && $this->isWhitelisted($request)) {
            // All transformations in the request are whitelisted. Skip the access token check
            return;
        }

        if (!$query->has('accessToken')) {
            throw new RuntimeException('Missing access token', 400);
        }

        $token = $query->get('accessToken');
        $uri = $request->getAccessTokenUri();

        // Remove the access token from the query string as it's not used to generate the HMAC
        $uri = rtrim(preg_replace('/(?<=(\?|&))accessToken=[^&]+&?/', '', $uri), '&?');

        $correctToken = hash_hmac('sha256', $uri, $request->getPrivateKey());

        if ($correctToken !== $token) {
            throw new RuntimeException('Incorrect access token', 400);
        }
    }

    /**
     * Check if the request is whitelisted
     *
     * @param Request $request The request instance
     * @return boolean
     */
    private function isWhitelisted(Request $request) {
        $filter = $this->params['transformations'];

        if (empty($filter['whitelist']) && empty($filter['blacklist'])) {
            return false;
        }

        $whitelist = array_flip($filter['whitelist']);
        $blacklist = array_flip($filter['blacklist']);
        $blacklisted = false;

        $transformations = array();

        foreach ($request->getTransformations() as $transformation) {
            $name = $transformation['name'];
            $flag = false;

            if (isset($blacklist[$name])) {
                $blacklisted = true;
                break;
            }

            if (isset($whitelist[$name]) || (empty($whitelist) && !empty($blacklist))) {
                $flag = true;
            }

            $transformations[$name] = $flag;
        }

        if ($blacklisted) {
            // Some of the transformations in the chain are blacklisted
            return false;
        }

        return count($transformations) === count(array_filter($transformations));
    }
}
