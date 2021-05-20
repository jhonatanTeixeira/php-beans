<?php


namespace PhpBeansTest\Factory;


use Monolog\Test\TestCase;
use PhpBeans\Factory\ContainerBuilder;

class ContainerBuilderTest extends TestCase
{
    public function testShouldBuildContainer() {
        $cb = new ContainerBuilder();

        $cb->withAppNamespaces()
            ->withNamespaces('PhpBeansTest\\')
            ->withBeans(['someValue' => 'lorem ipsum'])
            ->withComponents(SomeTestComponent::class, SomeInjectedComponent::class)
            ->withBeans([
                SomeTestBean::class => new SomeTestBean(),
            ])
            ->withFactories([
                'factoryTest' => fn(SomeTestComponent $someTestComponent) => new SomeInjectedComponent($someTestComponent),
            ])
        ;

        $container = $cb->build();

        $this->assertInstanceOf(SomeTestComponent::class, $container->get(SomeTestComponent::class));
        $this->assertEquals(
            'test component',
            $container->get(SomeInjectedComponent::class)->getSomeTestComponent()->getName()
        );
        $this->assertEquals(
            'test component',
            $container->get('factoryTest')->getSomeTestComponent()->getName()
        );
        $this->assertEquals(
            'test bean',
            $container->get(SomeTestBean::class)->getName()
        );
        $this->assertEquals(
            'test component',
            $container->get(SomeRegisteredTestComponent::class)->getName()
        );

        $this->assertTrue($container->get(SomeTestBehaviorImplementation::class)->isBehavior());
    }
}

class SomeTestComponent {
    public function getName() {
        return 'test component';
    }
}

class SomeInjectedComponent {
    private SomeTestComponent $someTestComponent;

    public function __construct(SomeTestComponent $someTestComponent)
    {
        $this->someTestComponent = $someTestComponent;
    }

    public function getSomeTestComponent(): SomeTestComponent
    {
        return $this->someTestComponent;
    }
}

class SomeTestBean {
    public function getName() {
        return 'test bean';
    }
}