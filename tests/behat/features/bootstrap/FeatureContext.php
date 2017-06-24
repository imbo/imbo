<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboBehatFeatureContext;

use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;

/**
 * Interface for Imbo feature context classes
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface FeatureContext {
    /**
     * Get the database adapter for the current suite / context
     *
     * @return DatabaseInterface
     */
    static function getDatabaseAdapter();

    /**
     * Get the storage adapter for the current suite / context
     *
     * @return StorageInterface
     */
    static function getStorageAdapter();
}
