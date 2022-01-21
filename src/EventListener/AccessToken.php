<?php declare(strict_types=1);
namespace Imbo\EventListener;

use GuzzleHttp\Psr7\Query;
use Imbo\EventListener\AccessToken\AccessTokenInterface;
use Imbo\EventListener\AccessToken\SHA256;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\ConfigurationException;
use Imbo\Exception\RuntimeException;
use Imbo\Helpers\Urls;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Resource;

/**
 * Access token event listener
 *
 * This event listener will listen to all GET and HEAD requests and make sure that they include a
 * valid access token. The official PHP-based imbo client (https://github.com/imbo/imboclient-php)
 * appends this token to all such requests by default. If the access token is missing or invalid
 * the event listener will throw an exception resulting in a HTTP response with 400 Bad Request.
 */
class AccessToken implements ListenerInterface
{
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

        /**
         * The generator used to create signatures for URLs so that they can be compared to the submitted
         * signatures. The client and the server needs to used the same signature algorithm.
         */
        'accessTokenGenerator' => null,
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = null)
    {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        // If no accessTokenGenerator is included, use the SHA256 one by default
        if (!$this->params['accessTokenGenerator']) {
            $this->params['accessTokenGenerator'] = new SHA256();
        }

        if (!$this->params['accessTokenGenerator'] instanceof AccessTokenInterface) {
            throw new ConfigurationException(
                'Invalid accessTokenGenerator defined (does not implement the AccessTokenInterface).',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        $callbacks = [];
        $events = [
            Resource::GROUPS_GET,
            Resource::GROUPS_HEAD,
            Resource::GROUP_GET,
            Resource::GROUP_HEAD,
            Resource::KEY_HEAD,
            Resource::ACCESS_RULE_GET,
            Resource::ACCESS_RULE_HEAD,
            Resource::ACCESS_RULES_GET,
            Resource::ACCESS_RULES_GET,
            Resource::USER_GET,
            Resource::USER_HEAD,
            Resource::IMAGE_GET,
            Resource::IMAGE_HEAD,
            Resource::IMAGES_GET,
            Resource::IMAGES_HEAD,
            Resource::GLOBAL_IMAGES_GET,
            Resource::GLOBAL_IMAGES_HEAD,
            Resource::METADATA_GET,
            Resource::METADATA_HEAD,
            Resource::SHORTURL_GET,
            Resource::SHORTURL_HEAD,

            'auth.accesstoken',
        ];

        foreach ($events as $event) {
            $callbacks[$event] = ['checkAccessToken' => 100];
        }

        return $callbacks;
    }

    /**
     * Check the access token of the request
     *
     * @param EventInterface $event
     * @throws RuntimeException
     * @return null|true Returns true on a successful access token comparion, null otherwise
     */
    public function checkAccessToken(EventInterface $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $query = $request->query;
        $eventName = $event->getName();
        $config = $event->getConfig();
        $accessTokenGenerator = $this->params['accessTokenGenerator'];

        if (($eventName === 'image.get' || $eventName === 'image.head') && $this->isWhitelisted($request)) {
            // All transformations in the request are whitelisted. Skip the access token check
            return;
        }

        // If the response has a short URL header, we can skip the access token check
        if ($response->headers->has('X-Imbo-ShortUrl')) {
            return;
        }

        // Loop through possible access token keys and see if either is present
        $presentAccessTokenArgumentKeys = [];

        foreach ($accessTokenGenerator->getArgumentKeys() as $argumentKey) {
            if ($query->has($argumentKey)) {
                $presentAccessTokenArgumentKeys[$argumentKey] = $query->get($argumentKey);
            }
        }

        if (!$presentAccessTokenArgumentKeys) {
            throw new RuntimeException('Missing access token', Response::HTTP_BAD_REQUEST);
        }

        // First the the raw un-encoded URI, then the URI as is
        $uris = [$request->getRawUri(), $request->getUriAsIs()];
        $privateKey = $event->getAccessControl()->getPrivateKey($request->getPublicKey());

        // append uris with [] expanded or [0] reduced
        $uris[] = $this->getUnescapedAlternativeURL($request->getRawUri());
        $uris[] = $this->getEscapedAlternativeURL($request->getRawUri());

        // See if we should modify the protocol for the incoming request
        $protocol = $config['authentication']['protocol'];
        if ($protocol === 'both') {
            $uris = array_reduce($uris, function ($dest, $uri) {
                $baseUrl = preg_replace('#^https?#', '', $uri);
                $dest[] = 'http' . $baseUrl;
                $dest[] = 'https' . $baseUrl;
                return $dest;
            }, []);
        } elseif (in_array($protocol, ['http', 'https'])) {
            $uris = array_map(function ($uri) use ($protocol) {
                return preg_replace('#^https?#', $protocol, $uri);
            }, $uris);
        }

        foreach (array_filter($uris) as $uri) {
            foreach ($presentAccessTokenArgumentKeys as $argumentKey => $token) {
                // Remove the access token from the query string as it's not used to generate the signature
                $uriWithoutAccessToken = rtrim(preg_replace('/(?<=(\?|&))' . $argumentKey . '=[^&]+&?/', '', $uri), '&?');
                $correctToken = $accessTokenGenerator->generateSignature($argumentKey, $uriWithoutAccessToken, $privateKey);

                if ($correctToken === $token) {
                    return true;
                }
            }
        }

        throw new RuntimeException('Incorrect access token', Response::HTTP_BAD_REQUEST);
    }

    /**
     * Helper method to generate an alternative form of an URL, where array indices have either
     * been added or removed. foo[] is transformed into foo[0], while foo[0] is transformed into foo[].
     *
     * The result for URLs with both formats is undefined, or for URLs that intermingle their parameters,
     * i.e. t[]=foo&b[]=bar&t[]=baz
     *
     * This was introduced because of differences between the URLs generated by the different clients, and
     * because Facebook (at least) generates URLs were []s in URL arguments are expanded to [0] when
     * requested from the backend. Since we sign our URLs, this breaks the token generation and thus breaks
     * URLs when Facebook attempts to retrieve them.
     *
     * @param string $url The URL to generate the alternative form of
     * @param int $encoding The encoding to use - from GuzzleHttp\Psr7
     * @return string
     */
    protected function getAlternativeURL($url, $encoding = PHP_QUERY_RFC3986)
    {
        $urlParts = parse_url($url);

        if (!isset($urlParts['query'])) {
            return $url;
        }

        $queryString = $urlParts['query'];
        $fixKeyPattern = '#\[[0-9]+\]$#';

        $parsed = Query::parse($queryString);
        $newArguments = [];

        foreach ($parsed as $key => $value) {
            $fixedKey = preg_replace($fixKeyPattern, '', $key);

            // if the key came out different, we're talking about a t[x] format - so we store those
            // to allow for the correct sequence when regenerating the URL further below.
            if ($fixedKey != $key) {
                $fixedKey .= '[]';

                if (!isset($newArguments[$fixedKey])) {
                    $newArguments[$fixedKey] = [];
                }

                $newArguments[$fixedKey][] = $value;
            } elseif (is_array($value) && substr($key, -2) == '[]') {
                // if the value is an array, and we have the [] format already, we expand the keys
                foreach ($value as $innerKey => $innerValue) {
                    // remove [] from the key and append the inner array key
                    $indexedKey = substr($key, 0, -2) . '[' . $innerKey . ']';
                    $newArguments[$indexedKey] = $innerValue;
                }
            } else {
                $newArguments[$key] = $value;
            }
        }

        $urlParts['query'] = Query::build($newArguments, $encoding);
        $url = Urls::buildFromParseUrlParts($urlParts);

        return $url;
    }

    /**
     * Generate an unescaped, alternative version of an url.
     *
     * @see AccessToken::getAlternativeURL()
     * @param $url string The URL to generate the alternative version of
     * @return string
     */
    protected function getUnescapedAlternativeURL($url)
    {
        return $this->getAlternativeURL($url, false);
    }

    /**
     * Generate an escaped, alternative version of an url.
     *
     * @see AccessToken::getAlternativeURL()
     * @param $url string The URL to generate the alternative version of
     * @return string
     */
    protected function getEscapedAlternativeURL($url)
    {
        return $this->getAlternativeURL($url, PHP_QUERY_RFC3986);
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
    private function isWhitelisted(Request $request)
    {
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
