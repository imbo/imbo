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
    Imbo\Model,
    DateTime,
    DateTimeZone;

/**
 * Status resource
 *
 * This resource can be used to monitor the imbo installation to see if it has access to the
 * current database and storage.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Status implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'HEAD'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'status.get' => 'get',
            'status.head' => 'get',
        ];
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $response = $event->getResponse();
        $database = $event->getDatabase();
        $storage = $event->getStorage();

        $databaseStatus = $database->getStatus();
        $storageStatus = $storage->getStatus();

        if (!$databaseStatus || !$storageStatus) {
            if (!$databaseStatus && !$storageStatus) {
                $message = 'Database and storage error';
            } else if (!$storageStatus) {
                $message = 'Storage error';
            } else {
                $message = 'Database error';
            }

            $response->setStatusCode(503, $message);
        }

        $response->setMaxAge(0)
                 ->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        $statusModel = new Model\Status();
        $statusModel->setDate(new DateTime('now', new DateTimeZone('UTC')))
                    ->setDatabaseStatus($databaseStatus)
                    ->setStorageStatus($storageStatus);

        $response->setModel($statusModel);
    }
}
