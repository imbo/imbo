<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboCli\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

/**
 * Base command
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cli
 */
abstract class Command extends BaseCommand {
    /**
     * Configuration array
     *
     * @var array
     */
    private $config;

    /**
     * Fetch the configuration array
     *
     * @return array
     */
    public function getConfig() {
        if ($this->config === null) {
            $this->config = require 'config/config.default.php';
        }

        return $this->config;
    }

    /**
     * Set the configuration array
     *
     * @param array $config The configuration to set
     */
    public function setConfig(array $config) {
        $this->config = $config;
    }
}
