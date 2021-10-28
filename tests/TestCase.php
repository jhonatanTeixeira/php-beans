<?php

namespace PhpBeansTest;

use PhpBeans\Container\Container;
use PhpBeans\Factory\ContainerBuilder;
use PhpBeans\Scanner\ComponentScanner;
use Shared\Annotation\GeneratedClass;
use Shared\Annotation\TestImport;
use Vox\Cache\Factory;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Container $container;

    protected bool $withCache = false;

    protected string $configFile = 'tests/example/shared/configs/application.yaml';

    protected array $components = [];

    protected ?string $withComponentScanner = ComponentScanner::class;

    public function getContainer(): Container
    {
        return $this->container;
    }

    protected function setUp(): void
    {
        $builder = new ContainerBuilder();

        $builder->withAppNamespaces()
            ->withNamespaces('ScannedTest\\')
            ->withNamespaces('Shared\\')
            ->withBeans(['someValue' => 'lorem ipsum'])
            ->withStereotypes(TestImport::class, GeneratedClass::class)
            ->withConfigFile($this->configFile)
            ->withComponents(...$this->components)
            ->withComponentScanner($this->withComponentScanner)
        ;

        if ($this->withCache) {
            $builder->withCache(
                (new Factory)
                    ->createSimpleCache(Factory::PROVIDER_DOCTRINE, Factory::TYPE_FILE, 'build/cache')
            );
        }

        $this->container = $builder->build();
    }
}