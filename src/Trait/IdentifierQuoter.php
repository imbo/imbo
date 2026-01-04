<?php declare(strict_types=1);
namespace Imbo\Trait;

trait IdentifierQuoter
{
    abstract protected function getIdentifierQuote(): string;

    /**
     * Quote database tables / columns
     *
     * @param string $identifier Identifier to quote
     * @return string
     */
    protected function quote(string $identifier): string
    {
        return sprintf('%1$s%2$s%1$s', $this->getIdentifierQuote(), $identifier);
    }
}
