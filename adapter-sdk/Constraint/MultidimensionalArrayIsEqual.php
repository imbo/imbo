<?php declare(strict_types=1);
namespace ImboSDK\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Util\Exporter;
use RuntimeException;

class MultidimensionalArrayIsEqual extends Constraint
{
    /**
     * @var array<mixed>
     */
    private array $value;

    /**
     * @param array<mixed> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return 'is the same as ' . Exporter::export($this->value);
    }

    /**
     * @param mixed $other
     */
    public function matches($other): bool
    {
        if (!is_array($other)) {
            throw new RuntimeException('Can only compare arrays');
        }

        return [] === $this->getArrayDiff($this->value, $other);
    }

    /**
     * @param array<mixed> $expected
     * @param array<mixed> $actual
     * @return array<mixed>
     */
    private function getArrayDiff(array $expected, array $actual): array
    {
        $diff = [];

        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                $diff[$key] = $value;
            } elseif (is_array($value)) {
                if (!is_array($actual[$key])) {
                    $diff[$key] = $value;
                } else {
                    $subDiff = $this->getArrayDiff($value, $actual[$key]);

                    if (count($subDiff)) {
                        $diff[$key] = $subDiff;
                    }
                }
            } elseif ($actual[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * @param array<mixed> $other
     */
    protected function additionalFailureDescription($other): string
    {
        return 'Array difference: ' . Exporter::export($this->getArrayDiff($this->value, $other));
    }
}
