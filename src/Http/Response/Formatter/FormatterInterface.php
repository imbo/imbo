<?php declare(strict_types=1);
namespace Imbo\Http\Response\Formatter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Model;

/**
 * Interface for formatters
 */
interface FormatterInterface
{
    /**
     * Format a model
     *
     * @param Model\ModelInterface $model The model to format
     * @return string
     * @throws InvalidArgumentException Throws an exception if the model is not supported
     */
    public function format(Model\ModelInterface $model);

    /**
     * Format an error model
     *
     * @param Model\Error $model The model to format
     * @return string Formatted data
     */
    public function formatError(Model\Error $model);

    /**
     * Format a status model
     *
     * @param Model\Status $model The model to format
     * @return string Formatted data
     */
    public function formatStatus(Model\Status $model);

    /**
     * Format a user model
     *
     * @param Model\User $model The model to format
     * @return string Formatted data
     */
    public function formatUser(Model\User $model);

    /**
     * Format an images model
     *
     * @param Model\Images $model The model to format
     * @return string Formatted data
     */
    public function formatImages(Model\Images $model);

    /**
     * Format a metadata model
     *
     * @param Model\Metadata $model The model to format
     * @return string Formatted data
     */
    public function formatMetadataModel(Model\Metadata $model);

    /**
     * Format a groups model
     *
     * @param  Model\Groups $model The model to format
     * @return string Formatted data
     */
    public function formatGroups(Model\Groups $model);

    /**
     * Format a group model
     *
     * @param  Model\Group $model The model to format
     * @return string Formatted data
     */
    public function formatGroup(Model\Group $model);

    /**
     * Format an access rule model
     *
     * @param  Model\AccessRule $model The model to format
     * @return string Formatted data
     */
    public function formatAccessRule(Model\AccessRule $model);

    /**
     * Format an access rules model
     *
     * @param  Model\AccessRules $model The model to format
     * @return string Formatted data
     */
    public function formatAccessRules(Model\AccessRules $model);

    /**
     * Format an array model
     *
     * @param Model\ArrayModel $model The model to format
     * @return string Formatted data
     */
    public function formatArrayModel(Model\ArrayModel $model);

    /**
     * Format a stats model
     *
     * @param Model\Stats $model The model to format
     * @return string Formatted data
     */
    public function formatStats(Model\Stats $model);

    /**
     * Get the content type for the current formatter
     *
     * Return the content type for the current formatter, excluding the character set, for instance
     * 'application/json'.
     *
     * @return string
     */
    public function getContentType();
}
