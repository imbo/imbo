<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;

/**
 * Response sender listener
 */
class ResponseSender implements ListenerInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'response.send' => 'send',
        ];
    }

    /**
     * Send the response
     *
     * @param EventInterface $event The current event
     */
    public function send(EventInterface $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Vary on public key header. Public key specified in query and URL path doesn't have to be
        // taken into consideration, since they will have varying URLs
        $response->setVary('X-Imbo-PublicKey', false);

        // Optionally mark this response as not modified
        $response->isNotModified($request);

        // Inject a possible image identifier into the response headers
        $imageIdentifier = null;

        if ($image = $request->getImage()) {
            // The request has an image. This means that an image was just added.
            // Get the image identifier from the image model
            $imageIdentifier = $image->getImageIdentifier();
        } elseif ($identifier = $request->getImageIdentifier()) {
            // An image identifier exists in the request URI, use that
            $imageIdentifier = $identifier;
        }

        if ($imageIdentifier) {
            $response->headers->set('X-Imbo-ImageIdentifier', $imageIdentifier);
        }

        $response->send();
    }
}
