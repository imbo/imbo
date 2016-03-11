<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Helpers;

use MongoDB\Model\BSONDocument,
    MongoDB\Model\BSONArray;

/**
 * Convert BSONDocument and BSONArray to their array counterparts, recursively
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Helpers
 */
class BSONToArray {
    /**
     * Convert to array, recursively
     *
     * @param array|BSONDocument|BSONArray $document
     * @return mixed
     */
    public function toArray($document) {
        if ($this->isBSONModel($document)) {
            // If the document is a BSON model, get the array copy first
            $document = $document->getArrayCopy();
        } else if (!is_array($document)) {
            // The variable to convert is not an array, simply return it as-is
            return $document;
        }

        $result = [];

        foreach ($document as $key => $value) {
            if ($this->isBSONModel($value)) {
                // The value is another model, convert it as well
                $value = $this->toArray($value->getArrayCopy());
            }

            // Regular value, set it
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Check if the value is a valid BSON model
     *
     * @param mixed $value
     * @return boolean
     */
    private function isBSONModel($value) {
        return ($value instanceof BSONDocument || $value instanceof BSONArray);
    }
}
