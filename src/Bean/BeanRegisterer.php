<?php

namespace PhpBeans\Bean;

use Metadata\MetadataFactory;
use PhpBeans\Annotation\Bean;
use PhpBeans\Annotation\Component;
use PhpBeans\Annotation\Configuration;
use PhpBeans\Annotation\PostBeanProcessor;
use PhpBeans\Annotation\Value;
use PhpBeans\Container\Container;
use PhpBeans\Metadata\ClassMetadata;
use PhpBeans\Processor\AbstractStereotypeProcessor;
use PhpBeans\Processor\PostBeanStereotypeProcessor;
use PhpBeans\Scanner\ComponentScanner;
use Psr\Log\LoggerInterface;
use Vox\Log\Logger;

class BeanRegisterer
{
    use ValueProcessorTrait;

    /**
     * @var string[]
     */
    private array $namespaces = [];

    /**
     * @var string[]
     */
    private array $stereotypes = [];

    /**
     * @var string[]
     */
    private array $components = [];

    /**
     * @var string[]
     */
    private array $factories = [];

    /**
     * @var string[]
     */
    private array $configurators = [];

    private ?ComponentScanner $componentScanner;

    private Container $container;

    private MetadataFactory $metadataFactory;

    private LoggerInterface $logger;

    public function __construct(
        Container $container,
        MetadataFactory $metadataFactory,
        ComponentScanner $componentScanner = null,
        array $namespaces = [],
        array $stereotypes = [],
        array $components = [],
        array $factories = [],
    ) {
        $this->componentScanner = $componentScanner;
        $this->container = $container;

        $this->namespaces = array_merge(['PhpBeans\\'], $namespaces);
        $this->stereotypes = array_merge(
            [
                Component::class,
                Configuration::class,
                PostBeanProcessor::class,
                AbstractStereotypeProcessor::class,
                AbstractInterfaceImplementor::class,
            ],
            $stereotypes
        );
        $this->components = array_merge(
            [
                ValuePostBeanProcessor::class,
                PostBeanStereotypeProcessor::class,
                AutowirePostBeanProcessor::class,
            ],
            $components
        );
        $this->factories = $factories;

        $this->metadataFactory = $metadataFactory;
        $this->logger = Logger::getLogger(__CLASS__);
    }

    public function addNamespace(string $namespace) {
        $this->namespaces[] = $namespace;

        return $this;
    }

    public function addStereotype(string $class) {
        $this->stereotypes[] = $class;

        return $this;
    }

    public function addComponent(string $class) {
        $this->components[] = $class;

        return $this;
    }

    public function addConfigurator(string $configurator): BeanRegisterer {
        $this->configurators[] = $configurator;

        return $this;
    }

    public function getAllComponentsMetadata() {
        return array_map(fn($class) => $this->metadataFactory->getMetadataForClass($class), $this->components);
    }

    public function getAllConfigurators() {
        $configurators = array_map(fn($c) => $this->metadataFactory->getMetadataForClass($c), $this->configurators);

        if ($this->componentScanner) {
            $configurators = array_merge($configurators, $this->componentScanner
                ->scanComponentsFor(BeanRegistererConfiguratorInterface::class, ...$this->namespaces));
        }

        return array_map(fn($c) => $c->reflection->newInstance(), $configurators);
    }

    public function registerClassComponents(ClassMetadata $classMetadata) {
        foreach ($classMetadata->getAnnotations() as $stereotype) {
            $this->registerComponent($classMetadata, get_class($stereotype));
        }
    }

    public function registerComponents() {
        $configurators = $this->getAllConfigurators();

        /* @var $configurator ClassMetadata */
        foreach ($configurators as $configurator) {
            $configurator->configure($this);
        }

        if (!$this->componentScanner) {
            array_map([$this, 'registerClassComponents'], $this->getAllComponentsMetadata());

            return;
        }

        foreach ($this->stereotypes as $stereotypeClass) {
            $namespaces = $this->namespaces;

            $components = array_merge(
                $this->componentScanner->scanComponentsFor($stereotypeClass, ...$namespaces),
                $this->getAllComponentsMetadata(),
            );

            foreach ($components as $metadata) {
                $this->registerComponent($metadata, $stereotypeClass);
            }
        }
    }

    public function registerFactories() {
        foreach ($this->container->getMetadataByComponent(Configuration::class) as $cfgid => $config) {
            foreach ($config->getAnnotatedMethods(Bean::class) as $factory) {
                $type = $factory->getReturnType();
                $id = $factory->getAnnotation(Bean::class)->name ?? $type ?? $factory->name;
                $this->container->set($id, $factory);
            }
        }

        foreach ($this->factories as $id => $factory) {
            $this->container->setFactory($id, $factory);
        }
    }

    public function registerComponent(ClassMetadata $metadata, string $stereotypeClass = null) {
        $this->container->set($metadata->name, $metadata);

        if ($stereotypeClass && $metadata->hasAnnotation($stereotypeClass)) {
            $component = $metadata->getAnnotation($stereotypeClass);

            if (property_exists($component, 'name') && $component->name) {
                $this->container->set($component->name, $metadata);
            }
        }

        foreach ($metadata->reflection->getInterfaceNames() as $interface) {
            $this->container->set($interface, $metadata);
        }

        return $this;
    }

    public function resolveConfigurationValues() {
        foreach ($this->container->getBeansByComponent(Configuration::class) as $configuration) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($configuration));

            foreach($metadata->getAnnotatedProperties(Value::class) as $property) {
                $this->process($property, $configuration, $this->container, $property->getAnnotation(Value::class));
            }
        }
    }

    public function registerBeans() {
        $this->registerComponents();
        $this->resolveConfigurationValues();
        $this->registerFactories();
        $this->implementInterfaces();
        $this->postProccess();
    }

    public function implementInterfaces()
    {
        $interfaceImplementors = $this->container
            ->getBeansByComponent(AbstractInterfaceImplementor::class);

        foreach ($this->container->getInterfaces() as $interface) {
            /* @var $interfaceImplementor AbstractInterfaceImplementor */
            foreach ($interfaceImplementors as $interfaceImplementor) {
                if (!$interfaceImplementor->accept($interface)) {
                    continue;
                }

                $interfaceImplementor->implementsClass($interface);
            }
        }
    }

    public function postProccess()
    {
        /* @var $processor AbstractStereotypeProcessor */
        foreach ($this->container->getBeansByComponent(AbstractStereotypeProcessor::class) as $processor) {
            $processor->findAndProcess();
        }
    }
}
