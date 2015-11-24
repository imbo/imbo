<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\Model\Image,
    Imbo\Image\TransformationManager,
    Imbo\EventManager\EventInterface,
    Imbo\Exception\TransformationException,
    Imbo\Image\Transformation\Transformation;

/**
 * Image transformer listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ImageTransformer implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transform' => 'transform',
        ];
    }

    /**
     * Transform images
     *
     * @param EventInterface $event The current event
     */
    public function transform(EventInterface $event) {
        $request = $event->getRequest();
        $image = $event->getResponse()->getModel();
        $presets = $event->getConfig()['transformationPresets'];
        $transformationManager = $event->getTransformationManager();

        // Fetch transformations specifed in the query and transform the image
        foreach ($request->getTransformations() as $transformation) {
            if (isset($presets[$transformation['name']])) {
                // Preset
                foreach ($presets[$transformation['name']] as $name => $params) {
                    if (is_int($name)) {
                        // No hardcoded params, use the ones from the request
                        $name = $params;
                        $params = $transformation['params'];
                    } else {
                        // Some hardcoded params. Merge with the ones from the request, making the
                        // hardcoded params overwrite the ones from the request
                        $params = array_replace($transformation['params'], $params);
                    }

                    $this
                        ->getHandler($transformationManager, $name)
                        ->setImage($image)
                        ->setEvent($event)
                        ->transform($params);
                }
            } else {
                // Regular transformation
                $params = $transformation['params'];
                $name = $transformation['name'];

                $this
                    ->getHandler($transformationManager, $name)
                    ->setImage($image)
                    ->setEvent($event)
                    ->transform($params);
            }
        }
    }

    /**
     * Get the transformation with the given name, or throw exception if it is not registered
     *
     * @param TransformationManager $manager Transformation manager
     * @param string $name Transformation name
     * @return Transformation
     * @throws TransformationException
     */
    private function getHandler(TransformationManager $manager, $name) {
        $handler = $manager->getTransformation($name);

        if (!$handler) {
            throw new TransformationException('Transformation "' . $name . '" not registered', 400);
        }

        return $handler;
    }
}
