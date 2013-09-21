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
     * @param string $header
     */
    public function __construct($header = null) {
        if ($header !== null) {
            $this->header = $header;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array('image.get' => 'addHeader');
    }

    /**
     * Add the HashTwo header to the response
     *
     * @param EventInterface $event The current event
     */
    public function addHeader(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $response->headers->set(
            $this->header,
            $request->getPublicKey() . '|' . $response->getImage()->getImageIdentifier()
        );
    }
}
