<?php declare(strict_types=1);

namespace Imbo\Model;

class AccessRules implements ModelInterface
{
    /**
     * List of rules.
     *
     * @var array<array>
     */
    private array $rules = [];

    /**
     * Set the rules.
     *
     * @param array<array>
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Get the rules.
     *
     * @return array<array>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return array<array>
     */
    public function getData(): array
    {
        return $this->getRules();
    }
}
