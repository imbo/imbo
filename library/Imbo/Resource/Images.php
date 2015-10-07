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
    Imbo\Model;

/**
 * Images resource
 *
 * This resource will let users fetch images based on queries. The following query parameters can
 * be used:
 *
 * page     => Page number. Defaults to 1
 * limit    => Limit to a number of images pr. page. Defaults to 20
 * metadata => Whether or not to include metadata pr. image. Set to 1 to enable
 * query    => urlencoded json data to use in the query
 * from     => Unix timestamp to fetch from
 * to       => Unit timestamp to fetch to
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Images implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'HEAD', 'POST'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'images.get' => 'getImages',
            'images.head' => 'getImages',
            'images.post' => 'addImage',
        ];
    }

    /**
     * Handle GET and HEAD requests
     *
     * @param EventInterface $event The current event
     */
    public function getImages(EventInterface $event) {
        $event->getManager()->trigger('db.images.load');
    }

    /**
     * Handle POST requests
     *
     * @param EventInterface
     */
    public function addImage(EventInterface $event) {
        $event->getManager()->trigger('db.image.insert');
        $event->getManager()->trigger('storage.image.insert');

        $request = $event->getRequest();
        $response = $event->getResponse();
        $image = $request->getImage();

        $model = new Model\ArrayModel();
        $model->setData([
            'imageIdentifier' => $image->getImageIdentifier(),
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'extension' => $image->getExtension(),
        ]);

        $response->setModel($model);
    }

}
