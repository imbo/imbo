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

use Symfony\Component\Console\Command\Command as BaseCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    RuntimeException;

/**
 * Generate a private key that can be used in the configuration
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cli
 */
class GeneratePrivateKey extends BaseCommand {
    /**
     * How many times to try to generate before we give up
     *
     * @var int
     */
    public $maxTries = 10;

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        parent::__construct('generate-private-key');

        $this->setDescription('Generate a private key');
        $this->setHelp('Generate a private key that you can use in the auth configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln($this->generate());
    }

    /**
     * Generate a private key
     *
     * @return string
     */
    public function generate() {
        $strong = false;
        $i = 0;

        while (!$strong && $this->maxTries > $i++) {
            $data = openssl_random_pseudo_bytes(32, $strong);
        }

        if (!$strong) {
            throw new RuntimeException('Could not generate private key');
        }

        // base64_encode to get a decent ascii compatible format, and trim ending ='s.
        $key = rtrim(base64_encode($data), '=');

        // We change +/ into -_ to avoid any human confusion with paths
        return strtr($key, '+/', '-_');
    }
}
