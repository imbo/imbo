<?php declare(strict_types=1);
namespace Imbo\Model;

interface ModelInterface
{
    /**
     * Return the "data" found in the model
     *
     * @return array<mixed,mixed>
     */
    public function getData(): array;
}
