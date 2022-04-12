<?php declare(strict_types=1);
namespace Imbo;

use Symfony\Component\Console\Application as BaseApplication;

class CliApplication extends BaseApplication
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('Imbo', Version::VERSION);

        // Register commands
        $this->addCommands([
            new CliCommand\GeneratePrivateKey(),
            new CliCommand\AddPublicKey(),
        ]);
    }
}
