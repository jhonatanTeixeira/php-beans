<?php

namespace PhpBeans\Factory;

use Composer\Autoload\ClassLoader;
use PhpBeans\Bean\BeanRegisterer;
use PhpBeans\Container\Container;
use PhpBeans\Event\EventDispatcher;
use PhpBeans\Metadata\ClassMetadata;
use PhpBeans\Scanner\ComponentScanner;
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
        $this->namespaces = $namespaces;

        return $this;
    }

    public function withStereotypes(string ...$stereotypes) {
        $this->stereotypes = $stereotypes;

        return $this;
    }

    public function withBeans(array $beans) {
        $this->beans = $beans;

        return $this;
    }

    public function build(): Container {
        $container = new Container(new EventDispatcher());

        foreach ($this->beans as $id => $value) {
            $container->set($id, $value);
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
        return (new MetadataFactoryFactory())->createAnnotationMetadataFactory(ClassMetadata::class);
    }
}