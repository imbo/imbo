<?php declare(strict_types=1);
namespace Imbo\Router;

use Symfony\Component\HttpFoundation\ParameterBag;

class Route extends ParameterBag
{
    private ?string $name = null;

    /**
     * Set the route name
     *
     * @param string $name The name of the route
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return the route name
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name ?: '';
    }
}
