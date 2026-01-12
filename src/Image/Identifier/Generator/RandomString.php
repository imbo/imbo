<?php declare(strict_types=1);

namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;
use Random\Randomizer;

class RandomString implements GeneratorInterface
{
    /**
     * Class constructor.
     *
     * @param int $length The length of the randomly generated string
     */
    public function __construct(
        private int $length = 12,
    ) {
    }

    public function generate(Image $image): string
    {
        return (new Randomizer())->getBytesFromString(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-',
            $this->length,
        );
    }

    public function isDeterministic(): bool
    {
        return false;
    }
}
