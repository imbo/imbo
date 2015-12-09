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

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Question\Question,
    Symfony\Component\Console\Question\ChoiceQuestion,
    Symfony\Component\Console\Question\ConfirmationQuestion,
    Symfony\Component\Console\Output\OutputInterface,
    Imbo\Auth\AccessControl\Adapter\AdapterInterface,
    Imbo\Auth\AccessControl\Adapter\MutableAdapterInterface,
    Imbo\Resource,
    RuntimeException;

/**
 * Add a public key to the configured access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Cli
 */
class AddPublicKey extends Command {
    const RESOURCES_READ_ONLY = 'Read-only on user resources';
    const RESOURCES_READ_WRITE = 'Read+write on user resources';
    const RESOURCES_SPECIFIC = 'Specific resources';
    const RESOURCES_CUSTOM = 'Custom resources';
    const RESOURCES_ALL = 'All resources (master user)';

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        parent::__construct('add-public-key');

        $this
            ->setDescription('Add a public key')
            ->setHelp('Add a public key to the configured access control adapter')
            ->addArgument(
                'publicKey',
                InputArgument::REQUIRED,
                'What should be the name of this public key?'
            )
            ->addArgument(
                'privateKey',
                InputArgument::OPTIONAL,
                'What should be the private key for this public key?'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $adapter = $this->getAclAdapter();
        $publicKey = $input->getArgument('publicKey');

        if ($adapter->publicKeyExists($publicKey)) {
            throw new RuntimeException('Public key with that name already exists');
        }

        // Use an interactive prompt to get a private key from the user,
        // unless it is already specified as a command line argument
        $privateKey = $input->getArgument('privateKey');
        if (empty($privateKey)) {
            $privateKey = $this->askForPrivateKey($input, $output);
        }

        // Continue asking for ACL rules until the user tells us otherwise
        $aclRules = [];
        $isFinished = false;
        do {
            $aclRules[] = [
                'resources' => $this->askForResources($input, $output),
                'users' => $this->askForUsers($input, $output)
            ];

            $isFinished = !$this->askForAnotherAclRule($input, $output);
        } while (!$isFinished);

        // Add key pair and ACL rules to database
        $adapter->addKeyPair($publicKey, $privateKey);

        foreach ($aclRules as $rule) {
            $adapter->addAccessRule($publicKey, $rule);
        }

        // Write success information
        $output->writeln('Successfully added new key pair');
        $output->writeln('Public key:  ' . $publicKey);
        $output->writeln('Private key: ' . $privateKey);
        $output->writeln('ACL rules added: ' . count($aclRules));
    }

    /**
     * Ask user which resources the public key should have access to
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    private function askForResources(InputInterface $input, OutputInterface $output) {
        $question = new ChoiceQuestion(
            'Which resources should the public key have access to? ',
            [
                self::RESOURCES_READ_ONLY,
                self::RESOURCES_READ_WRITE,
                self::RESOURCES_ALL,
                self::RESOURCES_SPECIFIC,
                self::RESOURCES_CUSTOM,
            ],
            self::RESOURCES_SPECIFIC
        );

        $type = $this->getHelper('question')->ask($input, $output, $question);
        switch ($type) {
            case self::RESOURCES_READ_ONLY:
                return Resource::getReadOnlyResources();
            case self::RESOURCES_READ_WRITE:
                return Resource::getReadWriteResources();
            case self::RESOURCES_ALL:
                return Resource::getAllResources();
            case self::RESOURCES_CUSTOM:
                return $this->askForCustomResources($input, $output);
        }

        return $this->askForSpecificResources($input, $output);
    }

    /**
     * Ask user which specific resources the public key should have access to
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    private function askForSpecificResources(InputInterface $input, OutputInterface $output) {
        $resources = Resource::getAllResources();
        sort($resources);

        $question = new ChoiceQuestion(
            'Which resources should the public key have access to? (comma-separated) ',
            $resources
        );
        $question->setMultiselect(true);

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * Ask the user which custom resources the public key should have access to
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array|string
     */
    private function askForCustomResources(InputInterface $input, OutputInterface $output) {
        $question = new Question(
            'Which custom resources should the public key have access to?' . PHP_EOL .
            '(comma-separated) '
        );

        $question->setValidator(function($answer) {
            $resources = array_filter(array_map('trim', explode(',', $answer)));

            if (empty($resources)) {
                throw new RuntimeException(
                    'You must specify at least one resource'
                );
            }

            return $resources;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * Ask the user which users the public key should have access to (for the current ACL-rule)
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array|string
     */
    private function askForUsers(InputInterface $input, OutputInterface $output) {
        $question = new Question(
            'On which users should the public key have access to these resources?' . PHP_EOL .
            '(comma-separated, specify "*" for all users) '
        );
        $question->setValidator(function($answer) {
            $users = array_filter(array_map('trim', explode(',', $answer)));

            if (empty($users)) {
                throw new RuntimeException(
                    'You must specify at least one user, alternatively a wildcard character (*)'
                );
            }

            return array_search('*', $users) === false ? $users : '*';
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * Ask the user if she wants to add more ACL-rules
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return boolean
     */
    private function askForAnotherAclRule(InputInterface $input, OutputInterface $output) {
        return $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
            'Create more ACL-rules for this public key? (y/N) ',
            false
        ));
    }

    /**
     * Ask the user for a private key (or generate one if user does not specify)
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    private function askForPrivateKey(InputInterface $input, OutputInterface $output) {
        $privateKeyGenerator = new GeneratePrivateKey();

        $question = new Question(
            'What do you want the private key to be (leave blank to generate)',
            $privateKeyGenerator->generate()
        );

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * Get the configured ACL adapter and ensure it is mutable
     *
     * @return MutableAdapterInterface
     */
    private function getAclAdapter() {
        $config = $this->getConfig();

        // Access control adapter
        $accessControl = $config['accessControl'];

        if (is_callable($accessControl) && !($accessControl instanceof AdapterInterface)) {
            $accessControl = $accessControl();
        }

        if (!$accessControl instanceof AdapterInterface) {
            throw new RuntimeException('Invalid access control adapter');
        }

        if (!$accessControl instanceof MutableAdapterInterface) {
            throw new RuntimeException('The configured access control adapter is not mutable');
        }

        return $accessControl;
    }
}
