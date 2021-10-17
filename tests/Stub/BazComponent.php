<?php


namespace PhpBeansTest\Stub;

use PhpBeans\Annotation\Autowired;
use PhpBeans\Annotation\Component;
use PhpBeans\Annotation\Value;

#[Component]
class BazComponent
{
    /**
     * @Autowired
     *
     * @var FooComponent
     */
    private FooComponent $fooComponent;

    /**
     * @Value("someValue")
     *
     * @var string
     */
    public string $someValue = '';

    /**
     * @return FooComponent
     */
    public function getFooComponent(): FooComponent
    {
        return $this->fooComponent;
    }
}