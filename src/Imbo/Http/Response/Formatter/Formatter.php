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
    Imbo\Helpers\DateFormatter,
    Imbo\Exception\InvalidArgumentException;

/**
 * Abstract formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
abstract class Formatter implements FormatterInterface {
    /**
     * Date formatter helper
     *
     * @var DateFormatter
     */
    protected $dateFormatter;

    /**
     * Class constructor
     *
     * @param DateFormatter $formatter An instance of the date formatter helper
     */
    public function __construct(DateFormatter $formatter = null) {
        if ($formatter === null) {
            $formatter = new DateFormatter();
        }

        $this->dateFormatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function format(Model\ModelInterface $model) {
        if ($model instanceof Model\Error) {
            return $this->formatError($model);
        } else if ($model instanceof Model\Status) {
            return $this->formatStatus($model);
        } else if ($model instanceof Model\User) {
            return $this->formatUser($model);
        } else if ($model instanceof Model\Images) {
            return $this->formatImages($model);
        } else if ($model instanceof Model\Metadata) {
            return $this->formatMetadataModel($model);
        } else if ($model instanceof Model\Groups) {
            return $this->formatGroups($model);
        } else if ($model instanceof Model\Group) {
            return $this->formatGroup($model);
        } else if ($model instanceof Model\AccessRule) {
            return $this->formatAccessRule($model);
        } else if ($model instanceof Model\AccessRules) {
            return $this->formatAccessRules($model);
        } else if ($model instanceof Model\ArrayModel) {
            return $this->formatArrayModel($model);
        } else if ($model instanceof Model\ListModel) {
            return $this->formatListModel($model);
        } else if ($model instanceof Model\Stats) {
            return $this->formatStats($model);
        }

        throw new InvalidArgumentException('Unsupported model type', 500);
    }
}
