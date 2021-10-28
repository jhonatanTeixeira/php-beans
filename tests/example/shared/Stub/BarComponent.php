<?php


namespace Shared\Stub;

use PhpBeans\Annotation\Component;
use PhpBeans\Annotation\Injects;
use PhpBeans\Annotation\Value;

/**
 * @Component
 */
class BarComponent
{
    private FooComponent $fooComponent;

    private int $value;

    #[Value('app.some_value', defaultValue: 'default')]
    private string $defaultValue;

    public function __construct(
        FooComponent $fooComponent,
        #[Injects('app.bar.value')]
        $value,
    ) {
        $this->fooComponent = $fooComponent;
        $this->value = $value;
    }

    public function getFooComponent(): FooComponent
    {
        return $this->fooComponent;
    }

    public function getValue() {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }
}