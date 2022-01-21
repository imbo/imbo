<?php declare(strict_types=1);
namespace Imbo\Resource;

use DateTime;
use DateTimeZone;
use Imbo\EventManager\EventInterface;
use Imbo\Http\Response\Response;
use Imbo\Model;

/**
 * Status resource
 *
 * This resource can be used to monitor the imbo installation to see if it has access to the
 * current database and storage.
 */
class Status implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
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
    public function get(EventInterface $event): void
    {
        $response = $event->getResponse();
        $database = $event->getDatabase();
        $storage = $event->getStorage();

        $databaseStatus = $database->getStatus();
        $storageStatus = $storage->getStatus();

        if (!$databaseStatus || !$storageStatus) {
            if (!$databaseStatus && !$storageStatus) {
                $message = 'Database and storage error';
            } elseif (!$storageStatus) {
                $message = 'Storage error';
            } else {
                $message = 'Database error';
            }

            $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE, $message);
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
