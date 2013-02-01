<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response;

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventListener\ListenerInterface,
    Imbo\Container,
    Imbo\ContainerAware,
    Imbo\Exception;

/**
 * This event listener will correctly format the response body based on the Accept headers in the
 * request
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ResponseFormatter implements ContainerAware, ListenerInterface {
    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('response.send', array($this, 'send'), 10),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * Response send hook
     *
     * @param EventInterface $event The current event
     */
    public function send(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Default mode for the response writer
        $strict = true;

        prepareResponse:

        // Fetch the response body
        $model = $response->getModel();

        // Write the correct response body. This will throw an exception if the client does not
        // accept any of the supported content types and the $strict flag has been set to true.
        try {
            $this->container->get('responseWriter')->write($model, $request, $response, $strict);
        } catch (Exception $exception) {
            // Generate an error
            $response->createError($exception, $request);

            // The response writer could not produce acceptable content. Flip flag and prepare
            // the response one more time
            $strict = false;

            // Go back up and prepare the new response
            goto prepareResponse;
        }
    }
}
