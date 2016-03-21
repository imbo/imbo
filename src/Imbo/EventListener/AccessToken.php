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
    private $params = [
        /**
         * Use this parameter to enforce the access token listener for only some of the image
         * transformations (if the request is against an image). Each transformation must be
         * specified using the short names of the transformations (the name used in the query to
         * trigger the transformation).
         *
         * 'transformations' => [
         *     'whitelist' => [
         *         'border',
         *         'thumbnail',
         *      ],
         * ]
         *
         * Use the 'whitelist' for making the listener skip the access token check for some
         * transformations, and the 'blacklist' key for the opposite:
         *
         * 'whitelist' => ['border'] means that the access token
         * will *not* be enforced for the Border transformation, but for all others.
         *
         * 'blacklist' => ['border'] means that the access token
         * will be enforced *only* when the Border transformation is in effect.
         *
         * If both 'whitelist' and 'blacklist' are specified all transformations will require an
         * access token unless included in the 'whitelist'.
         */
        'transformations' => [
            'whitelist' => [],
            'blacklist' => [],
        ],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = null) {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $callbacks = [];
        $events = [
            'groups.get',
            'groups.head',
            'group.get',
            'group.head',
            'accessrule.get',
            'accessrule.head',
            'accessrules.get',
            'accessrules.head',
            'user.get',
            'user.header',
            'image.get',
            'image.head',
            'images.get',
            'images.head',
            'globalimages.get',
            'globalimages.head',
            'metadata.get',
            'metadata.head',
            'shorturl.get',
            'shorturl.head',

            'auth.accesstoken'
        ];

        foreach ($events as $event) {
            $callbacks[$event] = ['checkAccessToken' => 100];
        }

        return $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAccessToken(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $query = $request->query;
        $eventName = $event->getName();
        $config = $event->getConfig();

        if (($eventName === 'image.get' || $eventName === 'image.head') && $this->isWhitelisted($request)) {
            // All transformations in the request are whitelisted. Skip the access token check
            return;
        }

        // If the response has a short URL header, we can skip the access token check
        if ($response->headers->has('X-Imbo-ShortUrl')) {
            return;
        }

        if (!$query->has('accessToken')) {
            throw new RuntimeException('Missing access token', 400);
        }

        $token = $query->get('accessToken');

        // First the the raw un-encoded URI, then the URI as is
        $uris = [$request->getRawUri(), $request->getUriAsIs()];
        $privateKey = $event->getAccessControl()->getPrivateKey($request->getPublicKey());

        // See if we should modify the protocol for the incoming request
        $protocol = $config['authentication']['protocol'];
        if ($protocol === 'both') {
            $uris = array_reduce($uris, function($dest, $uri) {
                $baseUrl = preg_replace('#^https?#', '', $uri);
                $dest[] = 'http' . $baseUrl;
                $dest[] = 'https' . $baseUrl;
                return $dest;
            }, []);
        } else if (in_array($protocol, ['http', 'https'])) {
            $uris = array_map(function($uri) use ($protocol) {
                return preg_replace('#^https?#', $protocol, $uri);
            }, $uris);
        }

        foreach ($uris as $uri) {
            // Remove the access token from the query string as it's not used to generate the HMAC
            $uri = rtrim(preg_replace('/(?<=(\?|&))accessToken=[^&]+&?/', '', $uri), '&?');

            $correctToken = hash_hmac('sha256', $uri, $privateKey);

            if ($correctToken === $token) {
                return;
            }
        }

        throw new RuntimeException('Incorrect access token', 400);
    }

    /**
     * Check if the request is whitelisted
     *
     * This method will whitelist a request only if all the transformations present in the request
     * are listed in the whitelist filter OR if the whitelist filter is empty, and the blacklist
     * filter has enties, but none of the transformations in the request are present in the
     * blacklist.
     *
     * @param Request $request The request instance
     * @return boolean
     */
    private function isWhitelisted(Request $request) {
        $filter = $this->params['transformations'];

        if (empty($filter['whitelist']) && empty($filter['blacklist'])) {
            // No filter has been configured
            return false;
        }

        // Fetch transformations from the request
        $transformations = $request->getTransformations();

        if (empty($transformations)) {
            // No transformations are present in the request, no need to check
            return false;
        }

        $whitelist = array_flip($filter['whitelist']);
        $blacklist = array_flip($filter['blacklist']);

        foreach ($transformations as $transformation) {
            if (isset($blacklist[$transformation['name']])) {
                // Transformation is explicitly blacklisted
                return false;
            }

            if (!empty($whitelist) && !isset($whitelist[$transformation['name']])) {
                // We have a whitelist, but the transformation is not listed in it, so we must deny
                // the request
                return false;
            }
        }

        // All transformations in the request are whitelisted
        return true;
    }
}
