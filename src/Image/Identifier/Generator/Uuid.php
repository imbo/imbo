<?php declare(strict_types=1);

namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;
use Ramsey\Uuid\Uuid as UuidFactory;

class Uuid implements GeneratorInterface
{
    public function generate(Image $image): string
    {
        return (string) UuidFactory::uuid4();
    }

    public function isDeterministic(): bool
    {
        return false;
    }
}
