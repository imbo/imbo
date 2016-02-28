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

use Imbo\EventManager\EventInterface;

/**
 * HashTwo event listener
 *
 * This event listener can be used to send HashTwo headers for Varnish.
 *
 * @link https://www.varnish-software.com/blog/advanced-cache-invalidation-strategies
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class VarnishHashTwo implements ListenerInterface {
    /**
     * The response header to use
     *
     * @var string
     */
    private $header = 'X-HashTwo';

    /**
     * Class constructor
     *
     * @param array $params Parameters for the event listener
     */
    public function __construct(array $params = null) {
        if ($params && isset($params['headerName'])) {
            $this->header = $params['headerName'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.get' => ['addHeader' => -1],
            'image.head' => ['addHeader' => -1],
        ];
    }

    /**
     * Add the HashTwo header to the response
     *
     * @param EventInterface $event The current event
     */
    public function addHeader(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $user = $request->getUser();
        $imageIdentifier = $response->getModel()->getImageIdentifier();

        $response->headers->set(
            $this->header,
            [
                'imbo;image;' . $user . ';' . $imageIdentifier,
                'imbo;user;' . $user,
            ]
        );
    }
}
