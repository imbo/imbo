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
 * Response sender listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ResponseSender implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('response.send', array($this, 'send')),
        );
    }

    /**
     * Send the response
     *
     * @param EventInterface $event The current event
     */
    public function send(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Optionally mark this response as not modified
        $response->isNotModified($request);

        // Inject a possible image identifier into the response headers
        $imageIdentifier = null;

        if ($image = $request->getImage()) {
            // The request has an image. This means that an image was just added. Use the image's
            // checksum
            $imageIdentifier = $image->getChecksum();
        } else if ($identifier = $request->getImageIdentifier()) {
            // An image identifier exists in the request, use that one (and not a possible image
            // checksum for an image attached to the response)
            $imageIdentifier = $identifier;
        }

        if ($imageIdentifier) {
            $response->headers->set('X-Imbo-ImageIdentifier', $imageIdentifier);
        }

        $response->send();
    }
}
