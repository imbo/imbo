<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;

use const JSON_ERROR_NONE;

class Keys implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['POST'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'keys.post' => 'createKey',
        ];
    }

    public function createKey(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        if (!$acl instanceof MutableAdapterInterface) {
            throw new ResourceException('Access control adapter is immutable', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $request = $event->getRequest();
        $body = $request->getContent();

        if (empty($body)) {
            throw new InvalidArgumentException('Missing JSON data', Response::HTTP_BAD_REQUEST);
        }

        $body = json_decode($body, true);

        if (null === $body || JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Invalid JSON data', Response::HTTP_BAD_REQUEST);
        }

        $publicKey = $body['publicKey'] ?? null;
        $privateKey = $body['privateKey'] ?? null;

        if (null === $publicKey) {
            throw new InvalidArgumentException('Missing public key', Response::HTTP_BAD_REQUEST);
        }

        if (null === $privateKey) {
            throw new InvalidArgumentException('Missing private key', Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^[a-z0-9_-]{1,}$/', $publicKey)) {
            throw new InvalidArgumentException('Invalid public key', Response::HTTP_BAD_REQUEST);
        }

        if ($acl->publicKeyExists($publicKey)) {
            throw new InvalidArgumentException('Public key already exists', Response::HTTP_BAD_REQUEST);
        }

        $acl->addKeyPair($publicKey, $privateKey);

        $event->getResponse()
            ->setStatusCode(Response::HTTP_CREATED)
            ->setModel((new ArrayModel())->setData(['publicKey' => $publicKey]));
    }
}
