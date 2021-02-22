<?php


namespace PhpBeansTest\Bean;


use PhpBeans\Factory\ContainerBuilder;
use PhpBeansTest\Stub\BarComponent;
use PhpBeansTest\Stub\BazComponent;
use PhpBeansTest\Stub\BeanComponent;
use PhpBeansTest\Stub\FooComponent;
use PHPUnit\Framework\TestCase;
use Vox\Cache\Factory;

class BeanRegistererTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testShouldRegisterBeans($withCache) {
        $builder = new ContainerBuilder();

        $builder->withAllNamespaces()
            ->withBeans(['someValue' => 'lorem ipsum']);

        if ($withCache) {
            $builder->withCache(
                (new Factory)
                    ->createSimpleCache(Factory::PROVIDER_DOCTRINE, Factory::TYPE_FILE, 'build/cache')
            );
        }

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

        $this->assertEquals(
            'lorem ipsum',
            $container->get(BazComponent::class)->someValue
        );

        $this->assertInstanceOf(FooComponent::class, $container->get(BazComponent::class)->getFooComponent());
    }

    public function provider() {
        return [
            [false],
            [true],
            [true],
        ];
    }
}