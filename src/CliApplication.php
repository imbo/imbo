<?php
namespace Imbo;

use Imbo\Version;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Cli application
 *
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
