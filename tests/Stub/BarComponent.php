<?php


namespace PhpBeansTest\Stub;

use PhpBeans\Annotation\Component;

/**
 * @Component
 */
class BarComponent
{
    private FooComponent $fooComponent;

    public function __construct(FooComponent $fooComponent)
    {
        $this->fooComponent = $fooComponent;
    }

    public function getFooComponent(): FooComponent
    {
        return $this->fooComponent;
    }
}