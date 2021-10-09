<?php declare(strict_types=1);
namespace Imbo\Http\Response;

use Imbo\Model\Error;
use Imbo\Model\ModelInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Response object from the server to the client
 */
class Response extends SymfonyResponse
{
    private ?ModelInterface $model = null;

    public function getModel(): ?ModelInterface
    {
        return $this->model;
    }

    public function setModel(ModelInterface $model = null): self
    {
        $this->model = $model;
        return $this;
    }

    public function setNotModified(): self
    {
        parent::setNotModified();
        $this->setModel(null);
        return $this;
    }

    public function setError(Error $error): self
    {
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
