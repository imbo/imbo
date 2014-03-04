<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Database\Doctrine;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Metadata query parser
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
 */
class MetadataQueryParser {
    public function parseMetadataQuery(array $query, QueryBuilder $qb) {
        $qb->leftJoin('i', 'metadata', 'm', 'i.id = m.imageId');

        foreach ($query as $key => $value) {
            if (is_string($key) && $key[0] === '$') {
            } else if (is_array($value) {
            } else {
            }
        }
    }
}
