<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface,
    Imbo\Auth\AccessControl\GroupQuery,
    Imbo\Model\Groups as GroupsModel;

/**
 * Abstract access control adapter
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Auth\AccessControl
 */
abstract class AbstractAdapter implements AdapterInterface {
    /**
     * {@inheritdoc}
     */
    abstract public function hasAccess($publicKey, $resource, $user = null);

    /**
     * {@inheritdoc}
     */
    abstract public function getGroups(GroupQuery $query = null, GroupsModel $model);

    /**
     * {@inheritdoc}
     */
    abstract public function getGroup($groupName);
}
