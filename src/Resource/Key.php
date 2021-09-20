<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\ResourceException;
use Imbo\Exception\RuntimeException;
use Imbo\Model\ArrayModel;

class Key implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD', 'PUT', 'DELETE'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'key.head'   => 'getKey',
            'key.put'    => 'updateKey',
            'key.delete' => 'deleteKey',
        ];
    }

    public function getKey(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        $publicKey = $event->getRequest()->getRoute()->get('publickey');

        if (!$acl->publicKeyExists($publicKey)) {
            throw new RuntimeException('Public key not found', 404);
        }

        $event->getResponse()->setModel((new ArrayModel())->setData(['publicKey' => $publicKey]));
    }

    public function updateKey(EventInterface $event): void
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

        $privateKey = $body['privateKey'] ?? null;

        if (null === $privateKey) {
            throw new InvalidArgumentException('Missing private key', 400);
        }

        $publicKey = $request->getRoute()->get('publickey');
        $privateKey = $privateKey;

        if (!$acl->publicKeyExists($publicKey)) {
            throw new InvalidArgumentException('Public key does not exist', 404);
        }

        $acl->updatePrivateKey($publicKey, $privateKey);
        $event->getResponse()->setModel((new ArrayModel())->setData(['publicKey' => $publicKey]));
    }

    public function deleteKey(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        if (!($acl instanceof MutableAdapterInterface)) {
            throw new ResourceException('Access control adapter is immutable', 405);
        }

        $request = $event->getRequest();
        $publicKey = $request->getRoute()->get('publickey');

        if (!$acl->publicKeyExists($publicKey)) {
            throw new RuntimeException('Public key does not exist', 404);
        }

        $acl->deletePublicKey($publicKey);
        $event->getResponse()->setModel((new ArrayModel())->setData(['publicKey' => $publicKey]));
    }
}
