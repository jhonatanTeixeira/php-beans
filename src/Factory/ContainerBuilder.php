<?php

namespace PhpBeans\Factory;

use Composer\Autoload\ClassLoader;
use PhpBeans\Bean\BeanRegisterer;
use PhpBeans\Container\Container;
use PhpBeans\Metadata\ClassMetadata;
use PhpBeans\Scanner\ComponentScanner;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Vox\Event\EventDispatcher;
use Vox\Metadata\Cache\PsrSimpleCacheAdapter;
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

    private $components = [];

    private $factories = [];

    private EventDispatcherInterface $eventDispatcher;

    private ?string $withYamlMetadata = null;

    private ?CacheInterface $cache = null;

    private bool $debug;

    public function __construct($debug = false)
    {
        $this->loader = require 'vendor/autoload.php';
        $this->eventDispatcher = new EventDispatcher();
        $this->debug = $debug;
    }

    public function withAllNamespaces()
    {
        $this->namespaces = [
            ...array_keys($this->loader->getPrefixes()),
            ...array_keys($this->loader->getPrefixesPsr4())
        ];

        return $this;
    }

    public function withAppNamespaces() {
        $composer = json_decode(file_get_contents('composer.json'), true);

        if (isset($composer['autoload']['psr-4'])) {
            $this->namespaces = array_merge($this->namespaces, array_keys($composer['autoload']['psr-4']));
        }

        if (isset($composer['autoload']['psr-0'])) {
            $this->namespaces = array_merge($this->namespaces, array_keys($composer['autoload']['psr-0']));
        }

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

        return $this;
    }

    public function withBeans(array $beans) {
        $this->beans = array_merge($this->beans, $beans);

        return $this;
    }

    public function withEventDispatcher(EventDispatcherInterface $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function withCache(CacheInterface $cache) {
        $this->cache = $cache;

        return $this;
    }

    public function withComponents(string ...$components) {
        $this->components = array_merge($this->components, $components);

        return $this;
    }

    public function withFactories(array $factories) {
        $this->factories = array_merge($this->factories, $factories);

        return $this;
    }

    public function build(): Container {
        $container = new Container($this->eventDispatcher, $this->debug, $this->cache);

        foreach ($this->beans as $id => $value) {
            $container->set($id, $value);
        }

        $factory = $this->buildMetadataFactory();

        $container->set(get_class($factory), $factory);
        $container->set(get_class($this->eventDispatcher), $this->eventDispatcher);

        if (isset($this->cache)) {
            $factory->setCache(new PsrSimpleCacheAdapter($this->cache));
        }

        $registerer = new BeanRegisterer(
            new ComponentScanner($factory, $this->debug, $this->cache),
            $container,
            $this->namespaces,
            $this->stereotypes,
            $factory,
            $this->components,
            $this->factories,
        );

        $registerer->registerBeans();

        if (!$this->debug) {
            $container->cacheUp();
        }


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