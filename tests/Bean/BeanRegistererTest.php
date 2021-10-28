<?php


namespace ScannedTest\Bean;


use PhpBeans\Factory\ContainerBuilder;
use Shared\Annotation\TestImport;
use Shared\Stub\BarComponent;
use Shared\Stub\BazComponent;
use Shared\Stub\BeanComponent;
use Shared\Stub\FooComponent;
use Shared\Stub\TestImportService;
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
            ->withNamespaces('ScannedTest\\')
            ->withNamespaces('Shared\\')
            ->withBeans(['someValue' => 'lorem ipsum'])
            ->withStereotypes(TestImport::class)
            ->withConfigFile('tests/example/shared/configs/application.yaml')
        ;

        if ($withCache) {
            $builder->withCache(
                (new Factory)
                    ->createSimpleCache(Factory::PROVIDER_DOCTRINE, Factory::TYPE_FILE, 'build/cache')
            );
        }

        $container = $builder->build();

        /* @var $foo \ScannedTest\Stub\FooComponent */
        $foo = $container->get(FooComponent::class);

        $this->assertInstanceOf(FooComponent::class, $foo);
        $this->assertEquals('lorem ipsum', $foo->getSomeValue());

        /* @var $bar \ScannedTest\Stub\BarComponent */
        $bar = $container->get(BarComponent::class);

        $this->assertEquals('lorem ipsum', $bar->getFooComponent()->getSomeValue());
        $this->assertEquals('default', $bar->getDefaultValue());
        $this->assertEquals(10, $bar->getValue());

        $this->assertEquals(
            'lorem ipsum',
            $container->get(BeanComponent::class)->getBarComponent()->getFooComponent()->getSomeValue()
        );

        $this->assertEquals(
            'lorem ipsum',
            $container->get(BazComponent::class)->someValue
        );

        $this->assertEquals(
            'some name',
            $container->get(BazComponent::class)->getSomeComponent()->getSomeName()
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