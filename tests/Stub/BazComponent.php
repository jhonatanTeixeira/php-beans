<?php


namespace PhpBeansTest\Stub;

use PhpBeans\Annotation\Autowired;
use PhpBeans\Annotation\Component;
use PhpBeans\Annotation\Injects;
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

    private SomeComponent $someComponent;

    public function __construct(
        #[Injects('some-component')]
        SomeComponent $someComponent
    ) {
        $this->someComponent = $someComponent;
    }

    /**
     * @return FooComponent
     */
    public function getFooComponent(): FooComponent
    {
        return $this->fooComponent;
    }

    public function getSomeComponent(): SomeComponent
    {
        return $this->someComponent;
    }
}