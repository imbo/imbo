<?php declare(strict_types=1);

namespace Imbo\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function is_dir;
use function mkdir;
use function rmdir;
use function substr;
use function unlink;

class Filesystem
{
    /**
     * Remove a directory and its contents, recursively.
     */
    public static function removeDir(string $dirPath, bool $recreateBaseDir = false): void
    {
        if (!is_dir($dirPath)) {
            if ($recreateBaseDir) {
                mkdir($dirPath, 0700);
            }

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $name = $file->getPathname();

            if ('.' === substr($name, -1)) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($name);
            } else {
                unlink($name);
            }
        }

        if (!$recreateBaseDir) {
            rmdir($dirPath);
        }
    }
}
