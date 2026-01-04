<?php declare(strict_types=1);
namespace Imbo\Helpers;

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

class BSONToArray
{
    /**
     * Convert to array, recursively
     *
     * @param BSONDocument|BSONArray|array<mixed> $document
     * @return array<mixed>
     */
    public function toArray(BSONDocument|BSONArray|array $document): array
    {
        $result = [];

        foreach ($document as $key => $value) {
            if ($value instanceof BSONDocument || $value instanceof BSONArray) {
                $value = $this->toArray($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
