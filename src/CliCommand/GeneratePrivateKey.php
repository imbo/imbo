<?php declare(strict_types=1);

namespace Imbo\CliCommand;

use Random\Engine\Secure as SecureEngine;
use Random\Randomizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePrivateKey extends Command
{
    public function __construct()
    {
        parent::__construct('generate-private-key');

        $this->setDescription('Generate a private key');
        $this->setHelp('Generate a cryptographically secure private key that you can use in the auth configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->generate());

        return self::SUCCESS;
    }

    /**
     * Generate a private key.
     *
     * @return string
     */
    public function generate()
    {
        $data = (new Randomizer(new SecureEngine()))->getBytes(32);

        // base64_encode to get a decent ascii compatible format, and trim ending ='s.
        $key = rtrim(base64_encode($data), '=');

        // We change +/ into -_ to avoid any human confusion with paths
        return strtr($key, '+/', '-_');
    }
}
