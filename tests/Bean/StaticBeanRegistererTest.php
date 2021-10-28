<?php

namespace PhpBeansTest\Bean;

use PhpBeansTest\TestCase;
use Shared\Stub\BarComponent;
use Shared\Stub\BazComponent;
use Shared\Stub\FooComponent;
use Shared\Stub\SomeComponent;

class StaticBeanRegistererTest extends TestCase {
    protected ?string $withComponentScanner = null;

    protected array $components = [
        BarComponent::class,
        BazComponent::class,
        SomeComponent::class,
        FooComponent::class,
    ];

    public function testShouldFetchServices() {
        $this->assertInstanceOf(SomeComponent::class, $this->container->get('some-component'));
    }
}