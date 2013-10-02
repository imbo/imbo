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
    Imbo\EventListener\ListenerInterface,
    Imbo\Model\Error,
    Imbo\Exception;

/**
 * This event listener will correctly format the response body based on the Accept headers in the
 * request
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ResponseFormatter implements ListenerInterface {
    /**
     * A response writer instance
     *
     * @var ResponseWriter
     */
    private $responseWriter;

    /**
     * Class constructor
     *
     * @param ResponseWriter $responseWriter A instance of the respones writer
     */
    public function __construct(ResponseWriter $responseWriter) {
        $this->responseWriter = $responseWriter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'response.send' => array('send' => 20),
        );
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

        try {
            $this->responseWriter->write($model, $request, $response);
        } catch (Exception $exception) {
            $error = Error::createFromException($exception, $request);
            $response->setError($error);

            // Write the error in non-strict mode
            $this->responseWriter->write($error, $request, $response, false);
        }
    }
}
