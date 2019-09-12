<?php
namespace Imbo;

use Imbo\Version,
    Symfony\Component\Console\Application as BaseApplication;

/**
 * Cli application
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cli
 */
class CliApplication extends BaseApplication {
    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct('Imbo', Version::VERSION);

        // Register commands
        $this->addCommands([
            new CliCommand\GeneratePrivateKey(),
            new CliCommand\AddPublicKey(),
        ]);
    }
}
