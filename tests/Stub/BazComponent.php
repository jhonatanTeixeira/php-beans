<?php


namespace PhpBeansTest\Stub;

use PhpBeans\Annotation\Autowired;
use PhpBeans\Annotation\Component;

/**
 * @Component
 */
class BazComponent
{
    /**
     * @Autowired
     *
     * @var FooComponent
     */
    private FooComponent $fooComponent;

    /**
     * @return FooComponent
     */
    public function getFooComponent(): FooComponent
    {
        return $this->fooComponent;
    }
}