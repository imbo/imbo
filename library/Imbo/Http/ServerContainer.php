<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http;

/**
 * Server container
 *
 * This container will hold parameters usually found in the $_SERVER superglobal.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class ServerContainer extends ParameterContainer {
    /**
     * {@inheritdoc}
     */
    public function getHeaders() {
        $headers = array();

        foreach ($this->parameters as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headers[substr($key, 5)] = $value;
            }
        }

        foreach (array('CONTENT_LENGTH', 'CONTENT_TYPE') as $key) {
            if (isset($this->parameters[$key])) {
                $headers[$key] = $this->parameters[$key];
            }
        }

        return $headers;
    }
}
