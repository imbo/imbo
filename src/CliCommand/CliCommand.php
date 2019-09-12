<?php
namespace Imbo\CliCommand;

use Symfony\Component\Console\Command\Command;

/**
 * Base command
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cli
 */
abstract class CliCommand extends Command {
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
            $this->config = require __DIR__ . '/../../config/config.default.php';
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
