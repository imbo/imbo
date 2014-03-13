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
            $expression = $this->createExpression($query[$field], $qb);
        } else {
            $expression = $this->createExpression(array_chunk($query, 1, true), $qb);
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
     *                         'orX'. Defaults to 'andX'
     * @return CompositeExpression
     */
    private function createExpression(array $queryParts, QueryBuilder $qb, $callback = 'andX') {
        $expr = $qb->expr();
        $composite = $expr->$callback();

        foreach ($queryParts as $query) {
            list($key, $value) = each($query);

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
                    // Not equals
                    $pair = $expr->andX();
                    $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $pair->add($expr->neq('m.tagValue', $qb->createPositionalParameter($param)));

                    $composite->add($pair);
                } else if ($operation === '$gt') {
                    $pair = $expr->andX();
                    $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $pair->add($expr->gt('m.tagValue', $qb->createPositionalParameter($param)));

                    $composite->add($pair);
                } else if ($operation === '$gte') {
                    $pair = $expr->andX();
                    $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $pair->add($expr->gte('m.tagValue', $qb->createPositionalParameter($param)));

                    $composite->add($pair);
                } else if ($operation === '$lt') {
                    $pair = $expr->andX();
                    $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $pair->add($expr->lt('m.tagValue', $qb->createPositionalParameter($param)));

                    $composite->add($pair);
                } else if ($operation === '$lte') {
                    $pair = $expr->andX();
                    $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $pair->add($expr->lte('m.tagValue', $qb->createPositionalParameter($param)));

                    $composite->add($pair);
                } else if ($operation === '$in') {
                    $pair = $expr->andX();

                    $keys = $expr->orX();
                    $values = $expr->orX();

                    $keys->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $keys->add($expr->like('m.tagName', $qb->createPositionalParameter($key . '::%')));

                    foreach ($param as $inValue) {
                        $values->add($expr->eq('m.tagValue', $qb->createPositionalParameter($inValue)));
                    }

                    $pair->add($keys);
                    $pair->add($values);

                    $composite->add($pair);
                } else if ($operation === '$wildcard') {
                    $pair = $expr->andX();
                    $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                    $pair->add($expr->like('m.tagValue', $qb->createPositionalParameter(str_replace('*', '%', $param))));

                    $composite->add($pair);
                } else if ($operation === '$exists') {
                    $method = $param ? 'eq' : 'neq';
                    $composite->add($expr->$method('m.tagName', $qb->createPositionalParameter($key)));
                }
            } else {
                // We have a regular key => value query, match the key and value columns
                $pair = $expr->andX();
                $pair->add($expr->eq('m.tagName', $qb->createPositionalParameter($key)));
                $pair->add($expr->eq('m.tagValue', $qb->createPositionalParameter($value)));

                $composite->add($pair);
            }
        }

        return $composite;
    }
}
