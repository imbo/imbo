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
    Imbo\Model\Error,
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
            new ListenerDefinition('response.send', array($this, 'send'), 20),
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
        $response = $event->getResponse();
        $model = $response->getModel();

        if ($response->getStatusCode() === 204 || !$model) {
            // No content to write
            return;
        }

        $request = $event->getRequest();
        $responseWriter = $this->container->get('responseWriter');

        try {
            $responseWriter->write($model, $request, $response);
        } catch (Exception $exception) {
            $error = Error::createFromException($exception, $request);
            $response->setError($error);

            // Write the error in non-strict mode
            $responseWriter->write($error, $request, $response, false);
        }
    }
}
