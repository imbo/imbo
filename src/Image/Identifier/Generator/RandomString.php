<?php declare(strict_types=1);

namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;

use function strlen;

class RandomString implements GeneratorInterface
{
    private int $stringLength;

    /**
     * Class constructor.
     *
     * @param int $length The length of the randomly generated string
     */
    public function __construct(int $length = 12)
    {
        $this->stringLength = $length;
    }

    public function generate(Image $image): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-';
        $charsLen = strlen($chars);
        $key = '';

        for ($i = 0; $i < $this->stringLength; ++$i) {
            $key .= $chars[mt_rand(0, $charsLen - 1)];
        }

        return $key;
    }

    public function isDeterministic(): bool
    {
        return false;
    }
}
