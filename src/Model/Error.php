<?php declare(strict_types=1);
namespace Imbo\Model;

use DateTime;
use DateTimeZone;
use Imbo\Exception;
use Imbo\Http\Request\Request;

class Error implements ModelInterface
{
    /**
     * HTTP code
     */
    private ?int $httpCode = null;

    /**
     * Error message from Imbo
     */
    private ?string $errorMessage = null;

    /**
     * Current date
     */
    private ?DateTime $date = null;

    /**
     * Internal Imbo error code
     */
    private ?int $imboErrorCode = null;

    /**
     * Optional image identifier
     */
    private ?string $imageIdentifier = null;

    /**
     * Set the HTTP code
     */
    public function setHttpCode(int $code): self
    {
        $this->httpCode = $code;
        return $this;
    }

    /**
     * Get the HTTP code
     */
    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    /**
     * Set the error message
     */
    public function setErrorMessage(string $message): self
    {
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * Get the error message
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Set the date
     */
    public function setDate(DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get the date
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * Set the imbo error code
     */
    public function setImboErrorCode(int $code): self
    {
        $this->imboErrorCode = $code;
        return $this;
    }

    /**
     * Get the imbo error code
     */
    public function getImboErrorCode(): ?int
    {
        return $this->imboErrorCode;
    }

    /**
     * Set the image identifier
     */
    public function setImageIdentifier(string $imageIdentifier): self
    {
        $this->imageIdentifier = $imageIdentifier;
        return $this;
    }

    /**
     * Get the image identifier
     */
    public function getImageIdentifier(): ?string
    {
        return $this->imageIdentifier;
    }

    /**
     * @return array{httpCode:?int,errorMessage:?string,date:?DateTime,imboErrorCode:?int,imageIdentifier:?string}
     */
    public function getData(): array
    {
        return [
            'httpCode' => $this->getHttpCode(),
            'errorMessage' => $this->getErrorMessage(),
            'date' => $this->getDate(),
            'imboErrorCode' => $this->getImboErrorCode(),
            'imageIdentifier' => $this->getImageIdentifier(),
        ];
    }

    /**
     * Create an error based on an exception instance
     *
     * @param Exception $exception An Imbo\Exception instance
     * @param Request The current request
     * @return Error
     */
    public static function createFromException(Exception $exception, Request $request)
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));

        $model = new self();
        $model->setHttpCode($exception->getCode())
              ->setErrorMessage($exception->getMessage())
              ->setDate($date)
              ->setImboErrorCode($exception->getImboErrorCode() ?: Exception::ERR_UNSPECIFIED);

        if ($image = $request->getImage()) {
            $model->setImageIdentifier($image->getImageIdentifier());
        } elseif ($identifier = $request->getImageIdentifier()) {
            $model->setImageIdentifier($identifier);
        }

        return $model;
    }
}
