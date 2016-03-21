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
    Imbo\Exception\ResourceException,
    Imbo\Model\ArrayModel;

/**
 * Short URL collection
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class ShortUrl implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['DELETE'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'shorturl.delete' => 'deleteShortUrl',
        ];
    }

    /**
     * Delete a single short URL
     *
     * @param EventInterface $event
     */
    public function deleteShortUrl(EventInterface $event) {
        $database = $event->getDatabase();
        $request = $event->getRequest();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();
        $shortUrlId = $request->getRoute()->get('shortUrlId');

        if (!$params = $database->getShortUrlParams($shortUrlId)) {
            throw new ResourceException('ShortURL not found', 404);
        }

        if ($params['user'] !== $user || $params['imageIdentifier'] !== $imageIdentifier) {
            throw new ResourceException('ShortURL not found', 404);
        }

        $database->deleteShortUrls(
            $user,
            $imageIdentifier,
            $shortUrlId
        );

        $model = new ArrayModel();
        $model->setData([
            'id' => $shortUrlId,
        ]);

        $event->getResponse()->setModel($model);
    }
}
