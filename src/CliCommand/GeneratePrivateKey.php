<?php declare(strict_types=1);
namespace Imbo\CliCommand;

use Imbo\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePrivateKey extends Command
{
    /**
     * How many times to try to generate before we give up
     *
     * @var int
     */
    public $maxTries = 10;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('generate-private-key');

        $this->setDescription('Generate a private key');
        $this->setHelp('Generate a private key that you can use in the auth configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->generate());
        return self::SUCCESS;
    }

    /**
     * Generate a private key
     *
     * @return string
     */
    public function generate()
    {
        $strong = false;
        $i = 0;

        while (!$strong && $this->maxTries > $i++) {
            $data = openssl_random_pseudo_bytes(32, $strong);
        }

        if (!$strong || !isset($data)) {
            throw new RuntimeException('Could not generate private key');
        }

        // base64_encode to get a decent ascii compatible format, and trim ending ='s.
        $key = rtrim(base64_encode($data), '=');

        // We change +/ into -_ to avoid any human confusion with paths
        return strtr($key, '+/', '-_');
    }
}
