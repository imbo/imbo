<?php
namespace Imbo\Helpers;

/**
 * Helper class for useful functions for building/manipulating URLs
 */
class Parameters {
    /**
     * @param $fields array List of fields to ensure is present in $params
     * @param $params array Associative array with field => value pairs to check for fields being present
     * @return array
     */
    public static function getEmptyOrMissingParamFields($fields, $params) {
        $missing = [];

        foreach ($fields as $field) {
            if (empty($params[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}