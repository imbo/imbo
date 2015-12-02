<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response\Formatter;

use Imbo\Model,
    Imbo\Exception\InvalidArgumentException;

/**
 * Interface for formatters
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
interface FormatterInterface {
    /**
     * Format a model
     *
     * @param Model\ModelInterface $model The model to format
     * @return string
     * @throws InvalidArgumentException Throws an exception if the model is not supported
     */
    function format(Model\ModelInterface $model);

    /**
     * Format an error model
     *
     * @param Model\Error $model The model to format
     * @return string Formatted data
     */
    function formatError(Model\Error $model);

    /**
     * Format a status model
     *
     * @param Model\Status $model The model to format
     * @return string Formatted data
     */
    function formatStatus(Model\Status $model);

    /**
     * Format a user model
     *
     * @param Model\User $model The model to format
     * @return string Formatted data
     */
    function formatUser(Model\User $model);

    /**
     * Format an images model
     *
     * @param Model\Images $model The model to format
     * @return string Formatted data
     */
    function formatImages(Model\Images $model);

    /**
     * Format a metadata model
     *
     * @param Model\Metadata $model The model to format
     * @return string Formatted data
     */
    function formatMetadataModel(Model\Metadata $model);

    /**
     * Format a groups model
     *
     * @param  Model\Groups $model The model to format
     * @return string Formatted data
     */
    function formatGroups(Model\Groups $model);

    /**
     * Format a group model
     *
     * @param  Model\Group $model The model to format
     * @return string Formatted data
     */
    function formatGroup(Model\Group $model);

    /**
     * Format an access rules model
     *
     * @param  Model\AccessRules $model The model to format
     * @return string Formatted data
     */
    function formatAccessRules(Model\AccessRules $model);

    /**
     * Format an array model
     *
     * @param Model\ArrayModel $model The model to format
     * @return string Formatted data
     */
    function formatArrayModel(Model\ArrayModel $model);

    /**
     * Format a list model
     *
     * @param Model\ListModel $model The model to format
     * @return string Formatted data
     */
    function formatListModel(Model\ListModel $model);

    /**
     * Format a stats model
     *
     * @param Model\Stats $model The model to format
     * @return string Formatted data
     */
    function formatStats(Model\Stats $model);

    /**
     * Get the content type for the current formatter
     *
     * Return the content type for the current formatter, excluding the character set, for instance
     * 'application/json'.
     *
     * @return string
     */
    function getContentType();
}
