<?php
namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image,
    Ramsey\Uuid\Uuid as UuidFactory;

/**
 * UUID image identifier generator
 *
 * @package Core\Image\Identifier\Generator
 */
class Uuid implements GeneratorInterface {
    /**
     * {@inheritdoc}
     */
    public function generate(Image $image) {
        return (string) UuidFactory::uuid4();
    }

    /**
     * {@inheritdoc}
     */
    public function isDeterministic() {
        return false;
    }
}
