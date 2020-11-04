<?php


namespace PhpBeansTest\Bean;


use PhpBeans\Factory\ContainerBuilder;
use PhpBeansTest\Stub\BarComponent;
use PhpBeansTest\Stub\BazComponent;
use PhpBeansTest\Stub\BeanComponent;
use PhpBeansTest\Stub\FooComponent;
use PHPUnit\Framework\TestCase;

class BeanRegistererTest extends TestCase
{
    public function testShouldRegisterBeans() {
        $builder = new ContainerBuilder();

        $builder->withAllNamespaces()
            ->withBeans(['someValue' => 'lorem ipsum']);

        $container = $builder->build();

        /* @var $foo \PhpBeansTest\Stub\FooComponent */
        $foo = $container->get(FooComponent::class);

        $this->assertInstanceOf(FooComponent::class, $foo);
        $this->assertEquals('lorem ipsum', $foo->getSomeValue());

        /* @var $bar \PhpBeansTest\Stub\BarComponent */
        $bar = $container->get(BarComponent::class);

        $this->assertEquals('lorem ipsum', $bar->getFooComponent()->getSomeValue());

        $this->assertEquals(
            'lorem ipsum',
            $container->get(BeanComponent::class)->getBarComponent()->getFooComponent()->getSomeValue()
        );

        $this->assertInstanceOf(FooComponent::class, $container->get(BazComponent::class)->getFooComponent());
    }
}