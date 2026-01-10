<?php declare(strict_types=1);

namespace Imbo\Http\Response\Formatter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Model;

interface FormatterInterface
{
    /**
     * @throws InvalidArgumentException Throws an exception if the model is not supported
     */
    public function format(Model\ModelInterface $model): string;

    public function formatError(Model\Error $model): string;

    public function formatStatus(Model\Status $model): string;

    public function formatUser(Model\User $model): string;

    public function formatImages(Model\Images $model): string;

    public function formatMetadataModel(Model\Metadata $model): string;

    public function formatGroups(Model\Groups $model): string;

    public function formatGroup(Model\Group $model): string;

    public function formatAccessRule(Model\AccessRule $model): string;

    public function formatAccessRules(Model\AccessRules $model): string;

    public function formatArrayModel(Model\ArrayModel $model): string;

    public function formatStats(Model\Stats $model): string;

    public function getContentType(): string;
}
