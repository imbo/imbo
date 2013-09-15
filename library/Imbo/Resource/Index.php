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
    Imbo\EventListener\ListenerDefinition,
    Imbo\Model,
    Imbo\Version;

/**
 * Index resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Index implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('index.get', array($this, 'get')),
            new ListenerDefinition('index.head', array($this, 'get')),
        );
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $response->setStatusCode(200, 'Hell Yeah');

        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();

        $model = new Model\ArrayModel();
        $model->setData(array(
            'version' => Version::VERSION,
            'urls' => array(
                'site' => 'http://www.imbo-project.org',
                'github' => 'https://github.com/imbo/imbo',
                'docs' => 'http://docs.imbo-project.org',
            ),
            'endpoints' => array(
                'status' => $baseUrl . '/status',
                'stats' => $baseUrl . '/stats',
                'user' => $baseUrl . '/users/{publicKey}',
                'images' => $baseUrl . '/users/{publicKey}/images',
                'image' => $baseUrl . '/users/{publicKey}/images/{imageIdentifier}',
                'shortImageUrl' => $baseUrl . '/s/{id}',
                'metadata' => $baseUrl . '/users/{publicKey}/images/{imageIdentifier}/metadata',
            ),
        ));

        $response->setModel($model);
    }
}
