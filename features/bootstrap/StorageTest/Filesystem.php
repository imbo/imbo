<?php declare(strict_types=1);
namespace Imbo\Behat\StorageTest;

use Imbo\Behat\AdapterTest;
use Imbo\Storage\Filesystem as StorageAdapter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Filesystem implements AdapterTest
{
    public static function setUp(array $config): array
    {
        $dataDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imbo_behat_test_storage';
        self::clearPath($dataDir);
        mkdir($dataDir);

        return [
            'dataDir' => $dataDir,
        ];
    }

    public static function tearDown(array $config): void
    {
        if (!empty($config['dataDir']) && is_dir($config['dataDir'])) {
            self::clearPath($config['dataDir']);
        }
    }

    /**
     * Clear the storage path
     *
     * @param string $dir The directory to wipe
     */
    private static function clearPath(string $dir): void
    {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                $name = $file->getPathname();

                if (substr($name, -1) === '.') {
                    continue;
                }

                if ($file->isDir()) {
                    rmdir($name);
                } else {
                    unlink($name);
                }
            }

            rmdir($dir);
        }
    }

    public static function getAdapter(array $config): StorageAdapter
    {
        return new StorageAdapter($config['dataDir']);
    }
}
