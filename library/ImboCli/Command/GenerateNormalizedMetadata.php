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
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    MongoClient;

/**
 * Generate normalized metadata in the MongoDB collection
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cli
 */
class GenerateNormalizedMetadata extends BaseCommand {
    /**
     * @var MongoClient
     */
    private $mongoClient;

    /**
     * {@inheritdoc}
     */
    public function __construct() {
        parent::__construct('generate-normalized-metadata');

        $this->setDescription('Generate normalized metadata in MongoDB');
        $this->setHelp('Imbo-1.2.0 introduced a metadata query feature. For this to work with the MongoDB adapter you will need to run this command to generate normalized metadata that will be used for queries. If you did not install Imbo prior to 1.2.0, or if you haven\'t added any metadata you don\'t need to execute this command.');

        $this->addOption('server', null, InputOption::VALUE_OPTIONAL, 'Specify the server to connect to.', 'mongodb://localhost:27017');
        $this->addOption('database', null, InputOption::VALUE_OPTIONAL, 'Specify the name of the database.', 'imbo');
        $this->addOption('collection', null, InputOption::VALUE_OPTIONAL, 'Specify the name of the collection.', 'image');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!class_exists('MongoClient')) {
            $output->writeln('<error>The ext-mongo extension is required for this command to run</error>');
            return;
        }

        $client = $this->getMongoClient($input->getOption('server'));
        $collection = $client->selectCollection($input->getOption('database'), $input->getOption('collection'));
        $query = array('metadata_n' => array('$exists' => false));
        $numImages = $collection->count($query);

        if (!$numImages) {
            $output->writeln('<info>There are no more images to update.</info>');
            return;
        }

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation($output, '<question>You are about to update ' . $numImages . ' document' . ($numImages !== 1 ? 's' : '') . '. Continue? [yN]</question> ', false)) {
            return;
        }

        $progress = $this->getHelperSet()->get('progress');
        $progress->setBarWidth(60);
        $progress->start($output, $numImages);
        $progress->setRedrawFrequency(1000);

        // Counter used to make sure the progress does not go further than the number of images
        // fetched earlier. This might happen if Imbo users adds images during the execution of
        // this command.
        $i = 0;

        foreach ($collection->find($query) as $image) {
            $image['metadata_n'] = $this->lowercaseArray($image['metadata']);
            $collection->save($image);

            if ($i++ < $numImages) {
                $progress->advance();
            }
        }

        // Make sure the counter ends up with 100%
        $progress->setCurrent($numImages);
        $progress->finish();

        $output->writeln('<info>Done. You should now be able to search for images using a metadata query.</info>');
    }

    /**
     * Get the MongoClient instance
     *
     * @param string $server
     * @return MongoClient
     */
    public function getMongoClient($server) {
        if ($this->mongoClient === null) {
            $this->mongoClient = new MongoClient($server);
        }

        return $this->mongoClient;
    }

    /**
     * Set the MongoClient instance
     *
     * @param MongoClient $client
     */
    public function setMongoClient(MongoClient $client) {
        $this->mongoClient = $client;
    }

    /**
     * Lowercase an array, both keys and values
     *
     * @param array $data The data to lowercase
     * @return array
     */
    private function lowercaseArray(array $data) {
        $result = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->lowercaseArray($value);
            } else if (is_string($value)) {
                $value = strtolower($value);
            }

            $result[strtolower($key)] = $value;
        }

        return $result;
    }
}
