<?php declare(strict_types=1);

namespace Imbo\Http\Response\Formatter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Helpers\DateFormatter;
use Imbo\Http\Response\Response;
use Imbo\Model;

abstract class Formatter implements FormatterInterface
{
    protected DateFormatter $dateFormatter;

    public function __construct(?DateFormatter $formatter = null)
    {
        if (null === $formatter) {
            $formatter = new DateFormatter();
        }

        $this->dateFormatter = $formatter;
    }

    public function format(Model\ModelInterface $model): string
    {
        if ($model instanceof Model\Error) {
            return $this->formatError($model);
        } elseif ($model instanceof Model\Status) {
            return $this->formatStatus($model);
        } elseif ($model instanceof Model\User) {
            return $this->formatUser($model);
        } elseif ($model instanceof Model\Images) {
            return $this->formatImages($model);
        } elseif ($model instanceof Model\Metadata) {
            return $this->formatMetadataModel($model);
        } elseif ($model instanceof Model\Groups) {
            return $this->formatGroups($model);
        } elseif ($model instanceof Model\Group) {
            return $this->formatGroup($model);
        } elseif ($model instanceof Model\AccessRule) {
            return $this->formatAccessRule($model);
        } elseif ($model instanceof Model\AccessRules) {
            return $this->formatAccessRules($model);
        } elseif ($model instanceof Model\ArrayModel) {
            return $this->formatArrayModel($model);
        } elseif ($model instanceof Model\Stats) {
            return $this->formatStats($model);
        }

        throw new InvalidArgumentException('Unsupported model type', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
