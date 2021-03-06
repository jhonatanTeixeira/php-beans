<?php


namespace PhpBeansTest\Bean;


use PhpBeans\Factory\ContainerBuilder;
use PhpBeansTest\Annotation\TestImport;
use PhpBeansTest\Stub\BarComponent;
use PhpBeansTest\Stub\BazComponent;
use PhpBeansTest\Stub\BeanComponent;
use PhpBeansTest\Stub\FooComponent;
use PhpBeansTest\Stub\TestImportService;
use PHPUnit\Framework\TestCase;
use Vox\Cache\Factory;

class BeanRegistererTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testShouldRegisterBeans($withCache) {
        $builder = new ContainerBuilder();

        $builder->withAppNamespaces()
            ->withNamespaces('PhpBeansTest\\')
            ->withBeans(['someValue' => 'lorem ipsum'])
            ->withStereotypes(TestImport::class)
        ;

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
        $this->assertInstanceOf(TestImportService::class, $container->get(TestImportService::class));
        $this->assertEquals("lorem ipsum", $container->get(TestImportService::class)->value);
    }

    public function provider() {
        return [
            [false],
            [true],
            [true],
        ];
    }
}