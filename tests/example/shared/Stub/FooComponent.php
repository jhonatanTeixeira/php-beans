<?php


namespace Shared\Stub;

use PhpBeans\Annotation\Component;

/**
 * @Component
 */
class FooComponent
{
    private string $someValue;

    public function __construct(string $someValue)
    {
        $this->someValue = $someValue;
    }

    public function getSomeValue(): string
    {
        return $this->someValue;
    }
}