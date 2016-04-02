<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Config;

/**
 * PHP File Config Implementation
 *
 * An ConfigInterface implementation that loads configuration from a PHP array.
 *
 * @author André Roaldseth <andre@roaldseth.net>
 * @package Config
 */
class ArrayConfig implements ConfigInterface {
    private $config = [];

    /**
     * Class constructor
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessControl() {
        return $this->config['accessControl'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase() {
        return $this->config['database'];
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->config['storage'];
    }

    /**
     * {@inheritdoc}
     */
    public function getImageIdentifierGenerator() {
        return $this->config['imageIdentifierGenerator'];
    }

    /**
     * {@inheritdoc}
     */
    public function getContentNegotiateImages() {
        return $this->config['contentNegotiateImages'];
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpCacheHeaders() {
        return $this->config['httpCacheHeaders'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthentication() {
        return $this->config['authentication'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEventListeners() {
        return $this->config['eventListeners'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEventListenerInitializers() {
        return $this->config['eventListenerInitializers'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformationPresets() {
        return $this->config['transformationPresets'];
    }

    /**
     * {@inheritdoc}
     */
    public function getResources() {
        return $this->config['resources'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes() {
        return $this->config['routes'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTrustedProxies() {
        return $this->config['trustedProxies'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexRedirect() {
        return $this->config['indexRedirect'];
    }
}
