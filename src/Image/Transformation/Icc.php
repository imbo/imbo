<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Exception\ConfigurationException,
    Imbo\Exception\InvalidArgumentException;

/**
 * Transformation for applying ICC profiles to an image.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\Transformations
 */
class Icc extends Transformation {
    /**
     * @var array
     */
    protected $profiles;

    public function __construct($profiles) {
        if (!is_array($profiles)) {
            throw new ConfigurationException(get_class() . ' requires an array with name => profile file (.icc) mappings when created.', 500);
        }

        $this->profiles = $profiles;
    }

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        if (empty($params['name']) && empty($this->profiles['default'])) {
            throw new InvalidArgumentException('No name given for ICC profile to use and no profile assigned to the "default" name.', 400);
        } else if (!empty($params['name']) && empty($this->profiles[$params['name']])) {
            throw new InvalidArgumentException('The given ICC profile alias ("' . $params['name'] . '") is unknown to the server.', 400);
        }

        $file = empty($params['name']) ? $this->profiles['default'] : $this->profiles[$params['name']];

        if (!file_exists($file)) {
            throw new ConfigurationException('Could not load ICC profile referenced by "' . $params['name'] . '": ' . $file, 500);
        }

        $iccProfile = file_get_contents($file);
        $this->imagick->profileImage('icc', $iccProfile);
        $this->image->hasBeenTransformed(true);
    }
}