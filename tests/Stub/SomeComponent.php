<?php

namespace PhpBeansTest\Stub;

use PhpBeans\Annotation\Component;

#[Component('some-component')]
class SomeComponent
{
    public function getSomeName() {
        return 'some name';
    }
}