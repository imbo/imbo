<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

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
