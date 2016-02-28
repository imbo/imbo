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

use Imbo\Model,
    Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Response object from the server to the client
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class Response extends SymfonyResponse {
    /**
     * Model instance
     *
     * @var Model\ModelInterface
     */
    private $model;

    /**
     * Get the model instance
     *
     * @return null|ModelInterface
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Set the model instance
     *
     * @param ModelInterface $model A model instance
     * @return Response
     */
    public function setModel(Model\ModelInterface $model = null) {
        $this->model = $model;

        return $this;
    }

    /**
     * Marks the response as not modified as per the Symfony
     */
    public function setNotModified() {
        parent::setNotModified();
        $this->setModel(null);

        return $this;
    }

    /**
     * Set an error model and update some parts of the response object
     *
     * @param Model\Error $error An error model instance
     * @return Response
     */
    public function setError(Model\Error $error) {
        $errorMessage = $error->getErrorMessage();

        $this->headers->add([
            'X-Imbo-Error-Message' => $errorMessage,
            'X-Imbo-Error-InternalCode' => $error->getImboErrorCode(),
            'X-Imbo-Error-Date' => $error->getDate()->format('D, d M Y H:i:s') . ' GMT',
        ]);

        $this->setStatusCode($error->getHttpCode(), $errorMessage)
             ->setEtag(null)
             ->setLastModified(null);

        $this->setModel($error);

        return $this;
    }
}
