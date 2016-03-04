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

use Imbo\Model;

/**
 * XML formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
class XML extends Formatter implements FormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'application/xml';
    }

    /**
     * {@inheritdoc}
     */
    public function formatError(Model\Error $model) {
        $imageIdentifierXml = '';

        if ($imageIdentifier = $model->getImageIdentifier()) {
            $imageIdentifierXml = '<imageIdentifier>' . $imageIdentifier . '</imageIdentifier>';
        }

        return <<<ERROR
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <error>
    <code>{$model->getHttpCode()}</code>
    <message>{$model->getErrorMessage()}</message>
    <date>{$this->dateFormatter->formatDate($model->getDate())}</date>
    <imboErrorCode>{$model->getImboErrorCode()}</imboErrorCode>
  </error>
  {$imageIdentifierXml}
</imbo>
ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function formatStatus(Model\Status $model) {
        $database = (int) $model->getDatabaseStatus();
        $storage = (int) $model->getStorageStatus();

        return <<<STATUS
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <status>
    <date>{$this->dateFormatter->formatDate($model->getDate())}</date>
    <database>{$database}</database>
    <storage>{$storage}</storage>
  </status>
</imbo>
STATUS;
    }

    /**
     * {@inheritdoc}
     */
    public function formatUser(Model\User $model) {
        return <<<USER
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <user>
    <user>{$model->getUserId()}</user>
    <numImages>{$model->getNumImages()}</numImages>
    <lastModified>{$this->dateFormatter->formatDate($model->getLastModified())}</lastModified>
  </user>
</imbo>
USER;
    }

    /**
     * {@inheritdoc}
     */
    public function formatImages(Model\Images $model) {
        $images = '';

        if ($fields = $model->getFields()) {
            $fields = array_fill_keys($fields, 1);
        }

        foreach ($model->getImages() as $image) {
            $images .= '<image>';

            if (empty($fields) || isset($fields['user'])) {
                $images .= '<user>' . $image->getUser() . '</user>';
            }

            if (empty($fields) || isset($fields['imageIdentifier'])) {
                $images .= '<imageIdentifier>' . $image->getImageIdentifier() . '</imageIdentifier>';
            }

            if (empty($fields) || isset($fields['checksum'])) {
                $images .= '<checksum>' . $image->getChecksum() . '</checksum>';
            }

            if (empty($fields) || isset($fields['originalChecksum'])) {
                $images .= '<originalChecksum>' . $image->getOriginalChecksum() . '</originalChecksum>';
            }

            if (empty($fields) || isset($fields['mime'])) {
                $images .= '<mime>' . $image->getMimeType() . '</mime>';
            }

            if (empty($fields) || isset($fields['extension'])) {
                $images .= '<extension>' . $image->getExtension() . '</extension>';
            }

            if (empty($fields) || isset($fields['added'])) {
                $images .= '<added>' . $this->dateFormatter->formatDate($image->getAddedDate()) . '</added>';
            }

            if (empty($fields) || isset($fields['updated'])) {
                $images .= '<updated>' . $this->dateFormatter->formatDate($image->getUpdatedDate()) . '</updated>';
            }

            if (empty($fields) || isset($fields['size'])) {
                $images .= '<size>' . $image->getFilesize() . '</size>';
            }

            if (empty($fields) || isset($fields['width'])) {
                $images .= '<width>' . $image->getWidth() . '</width>';
            }

            if (empty($fields) || isset($fields['height'])) {
                $images .= '<height>' . $image->getHeight() . '</height>';
            }

            $metadata = $image->getMetadata();

            if (is_array($metadata) && (empty($fields) || isset($fields['metadata']))) {
                $images .= '<metadata>' . $this->formatMetadata($metadata) . '</metadata>';
            }

            $images .= '</image>';
        }

        return <<<IMAGES
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <search>
    <hits>{$model->getHits()}</hits>
    <page>{$model->getPage()}</page>
    <limit>{$model->getLimit()}</limit>
    <count>{$model->getCount()}</count>
  </search>
  <images>{$images}</images>
</imbo>
IMAGES;
    }

    /**
     * {@inheritdoc}
     */
    public function formatMetadataModel(Model\Metadata $model) {
        $metadata = $this->formatMetadata($model->getData());

        return <<<METADATA
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <metadata>{$metadata}</metadata>
</imbo>
METADATA;
    }

    /**
     * {@inheritdoc}
     */
    public function formatArrayModel(Model\ArrayModel $model) {
        $data = $this->formatArray($model->getData());

        return <<<DATA
<?xml version="1.0" encoding="UTF-8"?>
<imbo>{$data}</imbo>
DATA;
    }

    /**
     * {@inheritdoc}
     */
    public function formatListModel(Model\ListModel $model) {
        $data = '';
        $entries = '';

        $container = $model->getContainer();
        $entry = $model->getEntry();
        $list = $model->getList();

        foreach ($list as $element) {
            $entries .= '<' . $entry . '>' . $this->formatValue($element) . '</' . $entry . '>';
        }

        $data = '<' . $container . '>' . $entries . '</' . $container . '>';

        return <<<DATA
<?xml version="1.0" encoding="UTF-8"?>
<imbo>{$data}</imbo>
DATA;
    }

    /**
     * {@inheritdoc}
     */
    public function formatGroups(Model\Groups $model) {
        $data = $model->getGroups();

        $entries = '';
        foreach ($data as $group) {
            $resources = array_map(array($this, 'formatValue'), $group['resources']);

            $entries .= '<group>';
            $entries .= '  <name>' . $this->formatValue($group['name']) . '</name>';
            $entries .= '  <resources>';
            $entries .= '    <resource>' . implode($resources, '</resource><resource>') . '</resource>';
            $entries .= '  </resources>';
            $entries .= '</group>';
        }

        return <<<GROUPS
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <search>
    <hits>{$model->getHits()}</hits>
    <page>{$model->getPage()}</page>
    <limit>{$model->getLimit()}</limit>
    <count>{$model->getCount()}</count>
  </search>
  <groups>{$entries}</groups>
</imbo>
GROUPS;
    }

    /**
     * {@inheritdoc}
     */
    public function formatGroup(Model\Group $model) {
        $entries = '';

        foreach ($model->getResources() as $resource) {
            $entries .= '<resource>' . $this->formatValue($resource) . '</resource>';
        }

        return <<<GROUP
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <name>{$model->getName()}</name>
  <resources>{$entries}</resources>
</imbo>
GROUP;
    }

    /**
     * {@inheritdoc}
     */
    public function formatStats(Model\Stats $model) {
        $total = $this->formatArray([
            'numImages' => $model->getNumImages(),
            'numUsers' => $model->getNumUsers(),
            'numBytes' => $model->getNumBytes(),
        ]);
        $custom = $this->formatArray($model->getCustomStats() ?: []);

        return <<<STATS
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <stats>
    {$total}
    <custom>{$custom}</custom>
  </stats>
</imbo>
STATS;
    }

    /**
     * {@inheritdoc}
     */
    public function formatAccessRule(Model\AccessRule $model) {
        $rule = $this->formatAccessRuleArray([
            'id' => $model->getId(),
            'users' => $model->getUsers(),
            'group' => $model->getGroup(),
            'resources' => $model->getResources(),
        ]);

        return <<<RULE
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  {$rule}
</imbo>
RULE;
    }

    /**
     * {@inheritdoc}
     */
    public function formatAccessRules(Model\AccessRules $model) {
        $rules = '';

        foreach ($model->getRules() as $rule) {
            $rules .= $this->formatAccessRuleArray($rule);
        }

        return <<<RULES
<?xml version="1.0" encoding="UTF-8"?>
<imbo>
  <access>{$rules}</access>
</imbo>
RULES;
    }

    /**
     * Format a value, and CDATA wrap it if it contains special characters
     *
     * @param string $value
     * @return string
     */
    private function formatValue($value) {
        if (strpbrk($value, '<>&"\'') !== false) {
            return '<![CDATA[' . $value . ']]>';
        }

        return $value;
    }

    /**
     * Format dataset containing metadata
     *
     * @param array $data
     * @return string
     */
    private function formatMetadata(array $data) {
        $metadata = '';

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $metadata .= '<tag key="' . $key . '">';
                $metadata .= $this->formatArray($value);
                $metadata .= '</tag>';

                continue;
            }

            $metadata .= '<tag key="' . $key . '">' . $this->formatValue($value) . '</tag>';
        }

        return $metadata;
    }

    /**
     * Format a nested dataset
     *
     * @param array $data A nested array
     * @return string
     */
    private function formatArray(array $data) {
        $xml = '';

        if (isset($data[0])) {
            $xml .= '<list>';

            foreach ($data as $value) {
                $xml .= '<value>';

                if (is_array($value)) {
                    $xml .= $this->formatArray($value);
                } else {
                    $xml .= $this->formatValue($value);
                }

                $xml .= '</value>';
            }

            $xml .= '</list>';
        } else {
            foreach ($data as $key => $value) {
                $xml .= '<' . $key . '>';

                if (is_array($value)) {
                    $xml .= $this->formatArray($value);
                } else {
                    $xml .= $this->formatValue($value);
                }

                $xml .= '</' . $key . '>';
            }
        }

        return $xml;
    }

    /**
     * Format access rule data array
     *
     * @param array $accessRule
     * @return string
     */
    private function formatAccessRuleArray(array $accessRule) {
        $rule = '<rule id="' . $accessRule['id'] . '">';

        if (isset($accessRule['resources']) && $accessRule['resources']) {
            $rule .= '<resources>';
            foreach ($accessRule['resources'] as $resource) {
                $rule .= '<resource>' . $this->formatValue($resource) . '</resource>';
            }
            $rule .= '</resources>';
        }

        if (isset($accessRule['group'])) {
            $rule .= '<group>' . $this->formatValue($accessRule['group']) . '</group>';
        }

        if (isset($accessRule['users']) && $accessRule['users']) {
            $users = (array) $accessRule['users'];

            $rule .= '<users>';
            foreach ($users as $user) {
                $rule .= '<user>' . $this->formatValue($user) . '</user>';
            }
            $rule .= '</users>';
        }

        $rule .= '</rule>';

        return $rule;
    }
}
