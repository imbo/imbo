<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

/**
 * Interface for Imbo feature context classes
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface ImboFeatureContext {
    /**
     * Get the database adapter for the current suite / context
     *
     * @return Imbo\Database\DatabaseInterface
     */
    static function getDatabaseAdapter();

    /**
     * Get the storage adapter for the current suite / context
     *
     * @return Imbo\Storage\StorageInterface
     */
    static function getStorageAdapter();
}
