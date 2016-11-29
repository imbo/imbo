<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;
use Imbo\Exception\ConfigurationException;
use Imbo\Exception\StorageException;

/**
 * Federating Storage Adapter
 *
 * The Federating Storage Adapter wraps one or more other adapters and allows you to create a pool of adapters that
 * receives read and write requests. You can use this feature to write images to multiple locations, read from multiple
 * S3 buckets or create almost any configuration of hierarchical storage needs.
 *
 * Configuration options supported by this driver:
 *
 * - <pre>(string) strategy</pre> The strategy to use for selecting a node for an operation. Valid values are 'weighted'
 *                                and 'priority'.
 * - <pre>(string) writeStrategy</pre> How writes should be performed, and how to handle possible write errors.
 * - <pre>(array) adapters</pre>  Absolute path to the base directory the images should be stored in
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Storage
 */
class Federating implements StorageInterface {
    /**
     * Federating adapters definition
     */
    private $configuration = [
        'strategy' => 'priority',
        'writeStrategy' => 'all',
        'adapters' => [
            'read' => [],
            'write' => [],
        ],
    ];

    /**
     * Valid federationstrategies
     *
     * Decides how a federation adapter is picked from the available adapters.
     */
    protected $federationStrategies = [
        'weighted' => true,   // Randomly pick an adapter based on weights
        'priority' => true,   // Use the adapters in sequence as they're defined
    ];

    /**
     * Valid write modes
     *
     * Decides how writes are handled - written to a single node, written to all nodes and how errors are handled.
     *
     * Possible values:
     *     `single`: Write to a single node, picked according to federationMode
     *     `single-retry-next`: Write to a single node, but if that node fails, retry with one of the other available
     *                          nodes. Repeat until success or throw storage exception if all fails.
     *     `all`: Write to all defined adapters, throwing an exception as soon as one fails
     *     `all-allow-failures`: Write to all defined adapters, but if one fails, skip to the next one. Throw an
     *                           exception if all nodes fails.
     */
    protected $writeStrategies = [
        'single' => true,
        'single-retry-next' => true,
        'all' => true,
        'all-allow-failures' => true,
    ];

    /**
     * Create a federating storage adapter that can utilize multiple other adapters
     *
     * @param $federatingDefinitions array A definition of the adapters to federate across
     */
    public function __construct($federatingDefinitions) {
        if (empty($federatingDefinitions['adapters']) || !is_array($federatingDefinitions['adapters'])) {
            throw new ConfigurationException('Missing required element (or wrong type - must be an array) "adapters" when creating a federating adapter.');
        }

        if (isset($federatingDefinitions['adapters']['read']) || isset($federatingDefinitions['adapters']['write'])) {
            // for each key in config, populate the proper array in configuration
            $checkKeys = ['read', 'write'];

            foreach ($checkKeys as $check) {
                if (!empty($federatingDefinitions['adapters'][$check])) {
                    if (!is_array($federatingDefinitions['adapters'][$check])) {
                        $federatingDefinitions['adapters'][$check] = [$federatingDefinitions['adapters'][$check]];
                    }

                    foreach ($federatingDefinitions['adapters'][$check] as $idx => $adapter) {
                        if (!$adapter instanceof StorageInterface) {
                            throw new ConfigurationException('Adapter configured for ' . $check . ' index ' . $idx . ' does not implement StorageInterface');
                        }
                    }

                    $this->configuration['adapters'][$check] = $federatingDefinitions['adapters'][$check];
                }
            }
        } else {
            // Populate both read/write with the same adapters
            foreach ($federatingDefinitions['adapters'] as $idx => $adapter) {
                if (!$adapter instanceof StorageInterface) {
                    throw new ConfigurationException('Adapter configured for index ' . $idx . ' does not implement StorageInterface');
                }

                $this->configuration['adapters']['read'][] = $adapter;
                $this->configuration['adapters']['write'][] = $adapter;
            }
        }

        if (!empty($federatingDefinitions['strategy']) &&
            isset($this->federationStrategies[$federatingDefinitions['strategy']])
        ) {
            $this->configuration['strategy'] = $federatingDefinitions['strategy'];
        } else if (!empty($federatingDefinitions['strategy'])) {
            throw new ConfigurationException('Invalid strategy configured for the Federating Storage Adapter: ' . $federatingDefinitions['strategy']);
        }

        if (!empty($federatingDefinitions['writeStrategy']) &&
            isset($this->writeStrategies[$federatingDefinitions['writeStrategy']])
        ) {
            $this->configuration['writeStrategy'] = $federatingDefinitions['writeStrategy'];
        } else if (!empty($federatingDefinitions['writeStrategy'])) {
            throw new ConfigurationException('Invalid writeStrategy configured for the Federating Storage Adapter: ' . $federatingDefinitions['writeStrategy']);
        }
    }

    /**
     * @inheritDoc
     */
    function store($user, $imageIdentifier, $imageData)
    {
        // TODO: Implement store() method.
    }

    /**
     * @inheritDoc
     */
    function delete($user, $imageIdentifier)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    function getImage($user, $imageIdentifier)
    {
        // TODO: Implement getImage() method.
    }

    /**
     * @inheritDoc
     */
    function getLastModified($user, $imageIdentifier)
    {
        // TODO: Implement getLastModified() method.
    }

    /**
     * @inheritDoc
     */
    function getStatus()
    {
        // TODO: Implement getStatus() method.
    }

    /**
     * @inheritDoc
     */
    function imageExists($user, $imageIdentifier)
    {
        // TODO: Implement imageExists() method.
    }

    protected function getNextAdapter($pool = 'read') {
        $adapters = $this->configuration['adapters'][$pool];
    }
}