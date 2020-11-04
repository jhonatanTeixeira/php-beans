<?php

namespace PhpBeansTest\Stub;

class BeanComponent
{
    private BarComponent $barComponent;

    public function __construct(BarComponent $barComponent)
    {
        $this->barComponent = $barComponent;
    }

    public function getBarComponent(): BarComponent
    {
        return $this->barComponent;
    }
}