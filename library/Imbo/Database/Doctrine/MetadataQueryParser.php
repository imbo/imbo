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

use Doctrine\DBAL\Query\QueryBuilder,
    Doctrine\DBAL\Connection;

/**
 * Metadata query parser
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
 */
class MetadataQueryParser {
    /**
     * Parse a metadata query
     *
     * @param array $query The metadata query, as an array
     * @param QueryBuilder $qb The Doctrine query builder
     */
    public function parseMetadataQuery(array $query, QueryBuilder $qb) {
        // Add a LEFT JOIN on the metadata table
        $qb->leftJoin('i', 'metadata', 'm', 'i.id = m.imageId');

        if (empty($query)) {
            // No query to parse
            return;
        }

        // Fetch the first field
        $field = key($query);

        if ($field === '$or') {
            $expression = $this->createExpression($query[$field], $qb, 'orX');
        } else if ($field === '$and') {
            $expression = $this->createExpression($query[$field], $qb, 'andX');
        } else {
            $expression = $this->createExpression(array_chunk($query, 1, true), $qb, 'andX');
        }

        // Add the expression as a WHERE clause in the query builder
        $qb->where($expression);
    }

    /**
     * Create a composite expression
     *
     * @param array $queryParts A numerical array with query parts
     * @param QueryBuilder $qb The Doctrine query builder
     * @param string $callback The callback to use when creating composite expressions, 'andX' or
     *                         'orX'
     * @return CompositeExpression
     */
    private function createExpression(array $queryParts, QueryBuilder $qb, $callback) {
        $expr = $qb->expr();
        $composite = $expr->$callback();

        foreach ($queryParts as $query) {
            list($key, $value) = each($query);

            if ($key[0] !== '$') {
                // Quote and add the namespace to the key if we are not dealing with an operation
                $key = '`m.' . $key . '`';
            }

            if ($key === '$or') {
                // Add an OR expression
                $composite->add($this->createExpression($value, $qb, 'orX'));
            } else if ($key === '$and') {
                // Add and AND expression
                $composite->add($this->createExpression($value, $qb, 'andX'));
            } else if (is_array($value)) {
                // The value is an array, which means that it's an expression, with the key as the
                // operation, and the value as the parameters for the operation
                list($operation, $param) = each($value);

                // Handle all the supported operations
                if ($operation === '$ne') {
                    $composite->add($expr->neq($key, $qb->createPositionalParameter($param)));
                } else if ($operation === '$gt') {
                    $composite->add($expr->gt($key, $qb->createPositionalParameter($param)));
                } else if ($operation === '$gte') {
                    $composite->add($expr->gte($key, $qb->createPositionalParameter($param)));
                } else if ($operation === '$lt') {
                    $composite->add($expr->lt($key, $qb->createPositionalParameter($param)));
                } else if ($operation === '$lte') {
                    $composite->add($expr->lte($key, $qb->createPositionalParameter($param)));
                } else if ($operation === '$in') {
                    $composite->add($key . ' IN (' . $qb->createPositionalParameter($param, Connection::PARAM_STR_ARRAY) . ')');
                } else if ($operation === '$nin') {
                    $composite->add($key . ' NOT IN (' . $qb->createPositionalParameter($param, Connection::PARAM_STR_ARRAY) . ')');
                } else if ($operation === '$wildcard') {
                    $composite->add($expr->like($key, $qb->createPositionalParameter(str_replace('*', '%', $param))));
                }
            } else {
                // We have a regular key => value query
                $composite->add($expr->eq($key, $qb->createPositionalParameter($value)));
            }
        }

        return $composite;
    }
}
