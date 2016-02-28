<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Model;

/**
 * Metadata resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Metadata implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'POST', 'PUT', 'DELETE', 'HEAD'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'metadata.head' => 'get',
            'metadata.get' => 'get',
            'metadata.post' => [
                'post',
                'validateMetadata' => 10,
            ],
            'metadata.put' => [
                'put',
                'validateMetadata' => 10,
            ],
            'metadata.delete' => 'delete',
        ];
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface $event The current event
     */
    public function delete(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.delete');
        $event->getResponse()->setModel(new Model\Metadata());
    }

    /**
     * Handle PUT requests
     *
     * @param EventInterface $event The current event
     */
    public function put(EventInterface $event) {
        $request = $event->getRequest();
        $metadata = json_decode($request->getContent(), true);

        $event->getManager()
            ->trigger('db.metadata.delete')
            ->trigger('db.metadata.update', [
                'metadata' => $metadata,
            ]);

        $model = new Model\Metadata();
        $model->setData($metadata);

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle POST requests
     *
     * @param EventInterface $event The current event
     */
    public function post(EventInterface $event) {
        $request = $event->getRequest();

        $event->getManager()->trigger('db.metadata.update', [
            'metadata' => json_decode($request->getContent(), true),
        ]);

        $model = new Model\Metadata();
        $model->setData($event->getDatabase()->getMetadata($request->getUser(), $request->getImageIdentifier()));

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $event->getManager()->trigger('db.metadata.load');
    }

    /**
     * Validate metadata found in the request body
     *
     * @param EventInterface $event The event instance
     * @throws InvalidArgumentException
     */
    public function validateMetadata(EventInterface $event) {
        $request = $event->getRequest();
        $metadata = $request->getContent();

        if (empty($metadata)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $metadata = json_decode($metadata, true);

            if ($metadata === null) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }

            foreach (array_keys($metadata) as $key) {
                if (strpos($key, '.') === false) {
                    continue;
                }

                throw new InvalidArgumentException('Invalid metadata. Dot characters (\'.\') are not allowed in metadata keys', 400);
            }
        }
    }
}
