<?php declare(strict_types=1);
namespace ImboBehatFeatureContext\StorageTest;

use ImboBehatFeatureContext\AdapterTest;
use Imbo\Storage\Filesystem as Storage;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class for suites that want to use the Filesystem storage adapter
 */
class Filesystem implements AdapterTest {
    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        // Generate directory for the files
        $dataDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imbo_behat_test_storage';

        // Clear the path and create the base directory
        self::clearPath($dataDir);
        mkdir($dataDir);

        return [
            'dataDir' => $dataDir
        ];
    }

    /**
     * {@inheritdoc}
     */
    static public function tearDown(array $config) {
        if (!empty($config['dataDir']) && is_dir($config['dataDir'])) {
            self::clearPath($config['dataDir']);
        }
    }

    /**
     * Clear the storage path
     *
     * @param string $dir The directory to wipe
     */
    static private function clearPath($dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                $name = $file->getPathname();

                if (substr($name, -1) === '.') {
                    continue;
                }

                if ($file->isDir()) {
                    // Remove dir
                    rmdir($name);
                } else {
                    // Remove file
                    unlink($name);
                }
            }

            // Remove the directory itself
            rmdir($dir);
        }
    }

    static public function getAdapter(array $config) : Storage {
        return new Storage([
            'dataDir' => $config['dataDir'],
        ]);
    }
}
