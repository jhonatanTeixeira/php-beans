<?php

namespace PhpBeansTest\Implementor;

use ScannedTest\Interfaces\InterfaceForGeneration;
use PhpBeansTest\TestCase;

class InterfaceImplementorTest extends TestCase
{
    public function testShouldImplementInterfaceAndInjectIntoContainer() {
        $this->assertEquals(20, $this->container->get(InterfaceForGeneration::class)->processValue(10));
    }
}