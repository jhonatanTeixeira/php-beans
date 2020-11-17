<?php

namespace PhpBeans\Factory;

use Composer\Autoload\ClassLoader;
use Metadata\Cache\CacheInterface;
use Metadata\Cache\PsrCacheAdapter;
use PhpBeans\Bean\BeanRegisterer;
use PhpBeans\Container\Container;
use PhpBeans\Event\EventDispatcher;
use PhpBeans\Metadata\ClassMetadata;
use PhpBeans\Scanner\ComponentScanner;
use Psr\EventDispatcher\EventDispatcherInterface;
use Vox\Metadata\Factory\MetadataFactoryFactory;

class ContainerBuilder
{
    /**
     * @var string[]
     */
    private array $namespaces = [];

    /**
     * @var string[]
     */
    private array $stereotypes = [];

    private ClassLoader $loader;

    private $beans = [];

    private EventDispatcherInterface $eventDispatcher;

    private ?string $withYamlMetadata = null;

    private CacheInterface $cache;

    public function __construct()
    {
        $this->loader = require 'vendor/autoload.php';
    }

    /**
     * @return string[]
     */
    public function withAllNamespaces()
    {
        $this->namespaces = [
            ...array_keys($this->loader->getPrefixes()),
            ...array_keys($this->loader->getPrefixesPsr4())
        ];

        return $this;
    }

    public function withNamespaces(string ...$namespaces) {
        $this->namespaces = [...$this->namespaces, ...$namespaces];

        return $this;
    }

    public function withStereotypes(string ...$stereotypes) {
        $this->stereotypes = [...$this->stereotypes, ...$stereotypes];

        return $this;
    }

    public function withYamlMetadata(string $metadataPath) {
        $this->withYamlMetadata = $metadataPath;
    }

    public function withBeans(array $beans) {
        $this->beans = $beans;

        return $this;
    }

    public function withEventDispatcher(EventDispatcherInterface $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function withCache(CacheInterface $cache) {
        $this->cache = $cache;

        return $this;
    }

    public function build(): Container {
        $container = new Container($this->eventDispatcher ?? new EventDispatcher());

        foreach ($this->beans as $id => $value) {
            $container->set($id, $value);
        }

        $factory = $this->buildMetadataFactory();

        if (isset($this->cache)) {
            $factory->setCache(new PsrCacheAdapter());
        }

        $registerer = new BeanRegisterer(
            new ComponentScanner($this->buildMetadataFactory()),
            $container,
            $this->namespaces,
            $this->stereotypes
        );

        $registerer->registerBeans();

        return $container;
    }

    private function buildMetadataFactory() {
        $factory = new MetadataFactoryFactory();

        if ($this->withYamlMetadata) {
            return $factory->createYmlMetadataFactory($this->withYamlMetadata);
        }

        return $factory->createAnnotationMetadataFactory(ClassMetadata::class);
    }
}