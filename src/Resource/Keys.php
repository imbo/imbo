<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Model\ArrayModel;

class Keys implements ResourceInterface
{
    public function getAllowedMethods()
    {
        return ['POST'];
    }

    public static function getSubscribedEvents()
    {
        return [
            'keys.post' => 'createKey',
        ];
    }

    public function createKey(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        if (!($acl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

        $request = $event->getRequest();
        $body    = $request->getContent();

        if (empty($body)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $body = json_decode($body, true);

            if ($body === null || json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }
        }

        $publicKey  = $body['publicKey'] ?? null;
        $privateKey = $body['privateKey'] ?? null;

        if (null === $publicKey) {
            throw new InvalidArgumentException('Missing public key', 400);
        } elseif (null === $privateKey) {
            throw new InvalidArgumentException('Missing private key', 400);
        } elseif (!preg_match('/^[a-z0-9_-]{1,}$/', $publicKey)) {
            throw new InvalidArgumentException('Invalid public key', 400);
        }

        if ($acl->publicKeyExists($publicKey)) {
            throw new InvalidArgumentException('Public key already exists', 400);
        }

        $acl->addKeyPair($publicKey, $privateKey);

        $event->getResponse()
            ->setStatusCode(201)
            ->setModel((new ArrayModel())->setData(['publicKey' => $publicKey]));
    }
}
