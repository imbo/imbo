<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\EventManager\EventInterface;
use Imbo\Model\ArrayModel;
use Imbo\Resource\ResourceInterface;

class CustomResource implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'custom1.get' => 'get',
        ];
    }

    public function get(EventInterface $event): void
    {
        $model = new ArrayModel();
        $model->setData([
            'event' => $event->getName(),
            'id' => $event->getRequest()->getRoute()->get('id'),
        ]);
        $event->getResponse()->setModel($model);
    }
}

/**
 * Attach a couple of custom resources
 */
class CustomResource2 implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'PUT'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'custom2.get' => 'get',
            'custom2.put' => 'put',
        ];
    }

    public function get(EventInterface $event): void
    {
        $model = new ArrayModel();
        $model->setData([
            'event' => $event->getName(),
        ]);
        $event->getResponse()->setModel($model);
    }

    public function put(EventInterface $event): void
    {
        $model = new ArrayModel();
        $model->setData([
            'event' => $event->getName(),
        ]);
        $event->getResponse()->setModel($model);
    }
}

return [
    'routes' => [
        'custom1' => '#^/custom/(?<id>[a-zA-Z0-9]{7})$#',
        'custom2' => '#^/custom(?:\.(?<extension>json|xml))?$#',
    ],
    'resources' => [
        'custom1' => CustomResource::class,
        'custom2' => fn () => new CustomResource2(),
    ],
];
